<?php
/**
 * Yapay zekâ ile ilan metninden alan çıkarma ve TR/EN/RU çeviri (Claude API).
 * Telegram botu kullanır: emlakçının serbest yazdığı metin → yapılandırılmış ilan alanları + 3 dilde başlık/açıklama.
 *
 * Not: Eklenti .zip olarak yüklendiği ve Composer kullanılmadığı için Anthropic PHP SDK'sı yerine
 * Messages API'ye doğrudan HTTP ile bağlanılır. JSON şeması (output_config.format) cevabın her zaman
 * geçerli ve şemaya uygun olmasını sağlar; ayrıca eklenti tarafında tekrar doğrulanır (ec_listing_clean).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_ai_settings(): array
{
    return array_merge(['key' => '', 'model' => 'claude-opus-5-5', 'effort' => 'medium'], (array) get_option('ec_ai', []));
}

/** İlan alanları için JSON şeması (data.php sözlüklerinden üretilir) */
function ec_ai_listing_schema(): array
{
    $subs = [];
    foreach (ec_property_types() as $t) {
        $subs = array_merge($subs, array_keys($t['sub']));
    }
    $nullable = fn(array $s) => ['anyOf' => [$s, ['type' => 'null']]];
    $text = ['type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'description'], 'properties' => ['title' => ['type' => 'string'], 'description' => ['type' => 'string']]];
    $props = [
        'mode' => ['type' => 'string', 'enum' => ec_modes()],
        'ptype' => ['type' => 'string', 'enum' => array_keys(ec_property_types())],
        'subtype' => $nullable(['type' => 'string', 'enum' => $subs]),
        'city' => $nullable(['type' => 'string', 'enum' => array_keys(ec_cities())]),
        'district' => $nullable(['type' => 'string']),
        'price' => $nullable(['type' => 'number']),
        'currency' => ['type' => 'string', 'enum' => EC_CURRENCIES],
        'area_m2' => $nullable(['type' => 'integer']),
        'rooms' => $nullable(['type' => 'string', 'enum' => ec_room_options()]),
        'baths' => $nullable(['type' => 'integer']),
        'deed_type' => $nullable(['type' => 'string', 'enum' => array_keys(ec_deed_types())]),
        'furnished' => $nullable(['type' => 'boolean']),
        'vat_paid' => $nullable(['type' => 'boolean']),
        'transformer_paid' => $nullable(['type' => 'boolean']),
        'floor' => $nullable(['type' => 'string']),
        'total_floors' => $nullable(['type' => 'integer']),
        'year_built' => $nullable(['type' => 'integer']),
        'sea_distance_m' => $nullable(['type' => 'integer']),
        'payment_plan' => $nullable(['type' => 'string']),
        'features' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => array_keys(ec_features())]],
        'i18n' => ['type' => 'object', 'additionalProperties' => false, 'required' => EC_LANGS, 'properties' => array_fill_keys(EC_LANGS, $text)],
        'missing' => ['type' => 'array', 'items' => ['type' => 'string']],
    ];
    return ['type' => 'object', 'additionalProperties' => false, 'required' => array_keys($props), 'properties' => $props];
}

function ec_ai_system_prompt(): string
{
    $cities = [];
    foreach (ec_cities() as $k => $c) {
        $cities[] = $k . ' (' . $c['name']['tr'] . '): ' . implode(', ', $c['districts']);
    }
    $deeds = [];
    foreach (ec_deed_types() as $k => $d) {
        $deeds[] = $k . ' = ' . $d['tr'];
    }
    $subs = [];
    foreach (ec_property_types() as $pk => $t) {
        foreach ($t['sub'] as $sk => $sn) {
            $subs[] = "$sk = {$sn['tr']} ($pk)";
        }
    }
    $feats = [];
    foreach (ec_features() as $k => $f) {
        $feats[] = "$k = {$f['tr']}";
    }
    return "You turn a real-estate agent's free-form message (usually Turkish, sometimes English or Russian) about a property in Northern Cyprus into a structured listing for a property portal.\n\n"
        . "Rules:\n"
        . "- Only use facts stated in the message. If a field is not stated, return null (or an empty array). Never guess prices, sizes, title deed type or features.\n"
        . "- mode: sale (satılık), rent (kiralık, monthly), daily (günlük / holiday rental). ptype: konut (residential), arsa (land), ticari (commercial).\n"
        . "- Prices: Turkish writing uses '.' as thousands separator ('250.000' = 250000, '1,5 milyon' = 1500000). '£', 'stg', 'sterlin', 'pound' = GBP; '€', 'euro' = EUR; '$', 'dolar' = USD; 'TL', '₺' = TRY. If no currency is given for a sale, use GBP.\n"
        . "- rooms uses the Turkish format: '2+1' means 2 bedrooms + 1 living room; '3 yatak odalı' = '3+1'; a studio is '1+0'.\n"
        . "- KDV ödendi / KDV dahil → vat_paid true; trafo ödendi → transformer_paid true. 'eşyalı' → furnished true, 'eşyasız' → false.\n"
        . "- city and district must use these keys and names:\n  " . implode("\n  ", $cities) . "\n"
        . "- deed_type keys: " . implode('; ', $deeds) . ". 'Türk malı' / 'Türk koçanı' = turk-kocani. Only for sale listings; for rent and daily listings deed_type is always null and is never listed as missing.\n"
        . "- subtype keys: " . implode('; ', $subs) . "\n"
        . "- features keys: " . implode('; ', $feats) . "\n"
        . "- i18n: write a short, factual title (max 70 characters, no emojis, no all-caps, no phone numbers) and a clear description in Turkish (tr), English (en), Russian (ru), German (de) and Arabic (ar, Modern Standard Arabic). Translate the agent's facts faithfully; do not add marketing claims that are not in the message. Remove phone numbers, prices in the description are allowed.\n"
        . "- missing: list in Turkish the important fields that are missing for this listing to be published (for sale: fiyat, şehir, tapu türü; always: emlak tipi). Empty array if nothing important is missing.\n"
        . "- When a CURRENT LISTING JSON and a CORRECTION are given, return the full listing with the correction applied and everything else unchanged.";
}

/**
 * Metinden ilan alanları çıkarır. $current verilirse, $correction ile düzeltilmiş tam ilanı döndürür.
 * @return array|WP_Error
 */
function ec_ai_extract(string $text, ?array $current = null, string $correction = '')
{
    $s = ec_ai_settings();
    if (!$s['key']) {
        return new WP_Error('ec_ai_key', 'Claude API anahtarı girilmemiş (Emlak → Ayarlar → Yapay zekâ)');
    }
    $user = $current
        ? "ORIGINAL MESSAGE:\n" . $text . "\n\nCURRENT LISTING JSON:\n" . wp_json_encode($current, JSON_UNESCAPED_UNICODE) . "\n\nCORRECTION FROM THE AGENT:\n" . $correction
        : "AGENT MESSAGE:\n" . $text;

    $body = [
        'model' => $s['model'],
        'max_tokens' => 16000,
        'system' => [['type' => 'text', 'text' => ec_ai_system_prompt(), 'cache_control' => ['type' => 'ephemeral']]],
        'messages' => [['role' => 'user', 'content' => $user]],
        'output_config' => [
            'effort' => $s['effort'],
            'format' => ['type' => 'json_schema', 'schema' => ec_ai_listing_schema()],
        ],
        // Güvenlik sınıflandırıcısı bir isteği reddederse sunucu tarafında yedek modele geçilir
        'fallbacks' => 'default',
    ];
    $res = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 120,
        'headers' => [
            'content-type' => 'application/json',
            'x-api-key' => $s['key'],
            'anthropic-version' => '2026-10-21',
            'anthropic-beta' => 'server-side-fallback-2026-07-01',
        ],
        'body' => wp_json_encode($body),
    ]);
    if (is_wp_error($res)) {
        return new WP_Error('ec_ai_http', 'Claude API’ye bağlanılamadı: ' . $res->get_error_message());
    }
    $code = wp_remote_retrieve_response_code($res);
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    if ($code !== 200 || !is_array($j)) {
        $msg = is_array($j) ? ($j['error']['message'] ?? 'bilinmeyen hata') : 'cevap okunamadı';
        return new WP_Error('ec_ai_api', "Claude API hatası ($code): $msg", ['retry' => in_array($code, [429, 500, 502, 503, 529], true)]);
    }
    if (($j['stop_reason'] ?? '') === 'refusal') {
        return new WP_Error('ec_ai_refusal', 'Yapay zekâ bu mesajı işlemedi. İlanı panelden girebilirsiniz.');
    }
    if (($j['stop_reason'] ?? '') === 'max_tokens') {
        return new WP_Error('ec_ai_long', 'Metin çok uzun; lütfen kısaltın.');
    }
    $out = '';
    foreach (($j['content'] ?? []) as $b) {
        if (($b['type'] ?? '') === 'text') {
            $out .= $b['text'];
        }
    }
    $data = json_decode($out, true);
    if (!is_array($data)) {
        return new WP_Error('ec_ai_json', 'Yapay zekâ cevabı okunamadı');
    }
    return ec_ai_normalize($data);
}

/** Şemadan gelen veriyi ec_listing_create'e uygun hale getirir */
function ec_ai_normalize(array $d): array
{
    if (!empty($d['subtype'])) {
        foreach (ec_property_types() as $pk => $t) {
            if (isset($t['sub'][$d['subtype']])) {
                $d['ptype'] = $pk; // alt tip, ana tipi belirler
            }
        }
    }
    if (!empty($d['district']) && !empty($d['city'])) {
        foreach (ec_cities()[$d['city']]['districts'] ?? [] as $dist) {
            if (mb_strtolower($dist) === mb_strtolower($d['district'])) {
                $d['district'] = $dist;
            }
        }
    }
    foreach (EC_LANGS as $l) {
        if (isset($d['i18n'][$l]['title'])) {
            $d['i18n'][$l]['title'] = mb_substr(trim($d['i18n'][$l]['title']), 0, 120);
        }
    }
    if (($d['mode'] ?? 'sale') !== 'sale') {
        $d['deed_type'] = null;
        $d['missing'] = array_values(array_filter((array) ($d['missing'] ?? []), fn($m) => stripos($m, 'tapu') === false));
    }
    $d['features'] = array_values(array_unique((array) ($d['features'] ?? [])));
    $d['missing'] = array_values((array) ($d['missing'] ?? []));
    return $d;
}
