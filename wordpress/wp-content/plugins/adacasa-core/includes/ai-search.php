<?php
/**
 * Doğal dille arama (1.5): "Girne'de denize yakın, bahçeli 2+1, 1.000 sterlin altı kiralık" → arama filtreleri.
 *
 * POST /api/v1/ai-search {q, lang} → {url, filters, chips}. Claude (hızlı model) cümleyi arama formunun alanlarına
 * çevirir; tarayıcı sonuç sayfasına gider ve hangi filtrelerin seçildiği orada görünür (kullanıcı düzeltebilir).
 * Kötüye kullanıma karşı: IP başına saatte 15, sitede günde 600 istek; aynı cümle 7 gün önbellekte.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_ai_search_model(): string
{
    return (string) apply_filters('ec_ai_search_model', 'claude-haiku-5-5');
}

function ec_ai_search_schema(): array
{
    $subs = [];
    foreach (ec_property_types() as $t) {
        $subs = array_merge($subs, array_keys($t['sub']));
    }
    $n = fn(array $s) => ['anyOf' => [$s, ['type' => 'null']]];
    $p = [
        'mode' => ['type' => 'string', 'enum' => ec_modes()],
        'city' => $n(['type' => 'string', 'enum' => array_keys(ec_cities())]),
        'district' => $n(['type' => 'string']),
        'ptype' => $n(['type' => 'string', 'enum' => array_keys(ec_property_types())]),
        'subtype' => $n(['type' => 'string', 'enum' => $subs]),
        'rooms' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ec_room_options()]],
        'min' => $n(['type' => 'number']),
        'max' => $n(['type' => 'number']),
        'currency' => ['type' => 'string', 'enum' => EC_CURRENCIES],
        'amin' => $n(['type' => 'integer']),
        'feat' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => array_keys(ec_features())]],
        'furnished' => $n(['type' => 'boolean']),
        'deed_type' => $n(['type' => 'string', 'enum' => array_keys(ec_deed_types())]),
        'q' => $n(['type' => 'string']),
    ];
    return ['type' => 'object', 'additionalProperties' => false, 'required' => array_keys($p), 'properties' => $p];
}

function ec_ai_search_prompt(): string
{
    $cities = [];
    foreach (ec_cities() as $k => $c) {
        $cities[] = $k . ' (' . $c['name']['tr'] . ' / ' . $c['name']['en'] . '): ' . implode(', ', $c['districts']);
    }
    $subs = [];
    foreach (ec_property_types() as $pk => $t) {
        foreach ($t['sub'] as $sk => $sn) {
            $subs[] = "$sk = {$sn['tr']} / {$sn['en']} ($pk)";
        }
    }
    $feats = [];
    foreach (ec_features() as $k => $f) {
        $feats[] = "$k = {$f['tr']} / {$f['en']}";
    }
    $deeds = [];
    foreach (ec_deed_types() as $k => $d) {
        $deeds[] = "$k = {$d['tr']}";
    }
    return "You convert a home-seeker's free-text search (Turkish, English or Russian) for a Northern Cyprus property portal into search filters.\n"
        . "Rules:\n- Only set what the user asked for; everything else null or empty array. Never invent constraints.\n"
        . "- mode: sale (satılık/buy), rent (kiralık, monthly), daily (günlük/holiday). Default sale if unclear.\n"
        . "- Prices: 'X altı/under X/до X' → max; 'X üstü/over X' → min; '1,5 milyon' = 1500000, '200 bin' = 200000. Currency: £/sterlin/pound = GBP, €/euro = EUR, $/dolar = USD, TL = TRY; default GBP.\n"
        . "- rooms in Turkish format: '2 yatak odalı'/'2 bedroom' = 2+1, studio = 1+0. 'en az 3 oda' → 3+1, 4+1, 5+1, 5+2, 6+.\n"
        . "- 'denize sıfır/beachfront' → denize_sifir; 'deniz manzaralı/sea view' → deniz_manzarasi. Vague proximity ('denize yakın', 'near the sea', 'merkeze yakın') maps to no field: ignore it.\n"
        . "- 'havuzlu' → ozel_havuz if 'özel/private', else ortak_havuz. 'eşyalı/furnished' → furnished true.\n"
        . "- district must be one of the listed districts for the city (exact spelling) or null. If only a district is named, set its city too.\n"
        . "- q: only a proper name the user gave (project / building / site name) or a listing number; otherwise null.\n\n"
        . "Cities and districts:\n" . implode("\n", $cities) . "\n\nProperty subtypes:\n" . implode("\n", $subs)
        . "\n\nFeatures:\n" . implode("\n", $feats) . "\n\nTitle deed types:\n" . implode("\n", $deeds);
}

/** @return array|WP_Error filtreler */
function ec_ai_search(string $q, string $lang = 'tr')
{
    $q = trim(mb_substr(sanitize_text_field($q), 0, 300));
    if (mb_strlen($q) < 4) {
        return new WP_Error('ec_invalid', 'Lütfen ne aradığınızı birkaç kelimeyle yazın', ['status' => 400]);
    }
    $ck = 'ec_ais2_' . md5(mb_strtolower($q));
    if (is_array($c = get_transient($ck))) {
        return $c;
    }
    $s = ec_ai_settings();
    if (!$s['key']) {
        return new WP_Error('ec_ai_key', 'Akıllı arama şu an kullanılamıyor', ['status' => 503]);
    }
    if (ec_rate_limited('aisearch', 15, HOUR_IN_SECONDS)) {
        return new WP_Error('ec_too_many', 'Çok fazla arama yaptınız, biraz sonra tekrar deneyin', ['status' => 429]);
    }
    $day = 'ec_ais_day_' . gmdate('Ymd');
    $used = (int) get_transient($day);
    if ($used >= (int) apply_filters('ec_ai_search_daily', 600)) {
        return new WP_Error('ec_too_many', 'Akıllı arama bugün için yoğun, lütfen filtreleri kullanın', ['status' => 429]);
    }
    set_transient($day, $used + 1, DAY_IN_SECONDS);
    $res = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 25,
        'headers' => ['content-type' => 'application/json', 'x-api-key' => $s['key'], 'anthropic-version' => '2026-10-21'],
        'body' => wp_json_encode([
            'model' => ec_ai_search_model(),
            'max_tokens' => 800,
            'system' => [['type' => 'text', 'text' => ec_ai_search_prompt(), 'cache_control' => ['type' => 'ephemeral']]],
            'messages' => [['role' => 'user', 'content' => 'Search: ' . $q]],
            'output_config' => ['format' => ['type' => 'json_schema', 'schema' => ec_ai_search_schema()]],
        ]),
    ]);
    if (is_wp_error($res)) {
        return new WP_Error('ec_ai_http', 'Akıllı arama şu an yanıt vermiyor', ['status' => 503]);
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    if ((int) wp_remote_retrieve_response_code($res) !== 200 || !is_array($j)) {
        update_option('ec_ai_search_last_error', gmdate('Y-m-d H:i') . ' ' . substr((string) wp_remote_retrieve_body($res), 0, 300), false);
        return new WP_Error('ec_ai_api', 'Akıllı arama şu an yanıt vermiyor', ['status' => 503]);
    }
    $out = '';
    foreach (($j['content'] ?? []) as $b) {
        if (($b['type'] ?? '') === 'text') {
            $out .= $b['text'];
        }
    }
    $f = json_decode($out, true);
    if (!is_array($f)) {
        return new WP_Error('ec_ai_json', 'Arama anlaşılamadı, filtreleri kullanın', ['status' => 422]);
    }
    $f = ec_ai_search_clean($f);
    set_transient($ck, $f, 7 * DAY_IN_SECONDS);
    return $f;
}

function ec_ai_search_clean(array $f): array
{
    $out = ['mode' => in_array($f['mode'] ?? '', ec_modes(), true) ? $f['mode'] : 'sale'];
    if (!empty($f['city']) && isset(ec_cities()[$f['city']])) {
        $out['city'] = $f['city'];
    }
    if (!empty($f['district'])) {
        foreach (ec_cities() as $ck => $c) {
            foreach ($c['districts'] as $d) {
                if (mb_strtolower($d) === mb_strtolower((string) $f['district']) && (empty($out['city']) || $out['city'] === $ck)) {
                    $out['district'] = $d;
                    $out['city'] = $ck;
                }
            }
        }
    }
    if (!empty($f['subtype'])) {
        foreach (ec_property_types() as $pk => $t) {
            if (isset($t['sub'][$f['subtype']])) {
                $out['ptype'] = $pk;
                $out['subtype'] = $f['subtype'];
            }
        }
    } elseif (!empty($f['ptype']) && isset(ec_property_types()[$f['ptype']])) {
        $out['ptype'] = $f['ptype'];
    }
    $rooms = array_values(array_intersect((array) ($f['rooms'] ?? []), ec_room_options()));
    if ($rooms) {
        $out['rooms'] = $rooms;
    }
    foreach (['min', 'max', 'amin'] as $k) {
        if (isset($f[$k]) && is_numeric($f[$k]) && $f[$k] > 0) {
            $out[$k] = (int) round((float) $f[$k]);
        }
    }
    if (isset($out['min']) || isset($out['max'])) {
        $out['currency'] = in_array($f['currency'] ?? '', EC_CURRENCIES, true) ? $f['currency'] : 'GBP';
    }
    $feat = array_values(array_intersect((array) ($f['feat'] ?? []), array_keys(ec_features())));
    if ($feat) {
        $out['feat'] = $feat;
    }
    if (isset($f['furnished']) && is_bool($f['furnished'])) {
        $out['furnished'] = $f['furnished'] ? '1' : '0';
    }
    if ($out['mode'] === 'sale' && !empty($f['deed_type']) && isset(ec_deed_types()[$f['deed_type']])) {
        $out['deed_type'] = $f['deed_type'];
    }
    if (!empty($f['q'])) {
        $out['q'] = mb_substr(sanitize_text_field((string) $f['q']), 0, 60);
    }
    return $out;
}

/** Filtrelerden sonuç sayfası adresi */
function ec_ai_search_url(array $f, string $lang): string
{
    $q = array_diff_key($f, ['mode' => 1, 'city' => 1]);
    $q['ai'] = 1;
    $base = ec_url_section($f['mode'], $lang, $f['city'] ?? null);
    return $base . (str_contains($base, '?') ? '&' : '?') . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/ai-search', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            $in = $r->get_json_params() ?: $r->get_body_params();
            $lang = in_array($in['lang'] ?? '', EC_LANGS, true) ? $in['lang'] : 'tr';
            $f = ec_ai_search((string) ($in['q'] ?? ''), $lang);
            return is_wp_error($f) ? $f : ['filters' => $f, 'url' => ec_ai_search_url($f, $lang)];
        },
    ]);
});
