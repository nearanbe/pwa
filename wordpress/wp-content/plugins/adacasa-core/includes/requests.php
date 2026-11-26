<?php
/**
 * Emlak talep formu: yapay zekâ aramasından sonra aradığını bulamayan ziyaretçi talebini bırakır,
 * talep AdaCasa'ya kayıtlı tüm aktif emlak ofislerine/danışmanlarına iletilir.
 * Her alıcı için leads tablosunda kind='request' satırı açılır (Talepler + müşteri takibi panelinde görünür).
 * Bildirimler (e-posta, Telegram, anlık bildirim) arka planda gönderilir.
 */
defined('ABSPATH') || exit;

/** Talebin gideceği üyeler: aktif, ekip üyesi olmayan (ofisin kendisi ya da bağımsız danışman), demo olmayan */
function ec_request_recipients(): array
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT id, name, email, telegram_chat_id, wp_user_id FROM ' . ec_table('agents') . " WHERE status = 'active' AND parent_id IS NULL ORDER BY id", ARRAY_A);
    return array_values(array_filter($rows, fn($a) => !preg_match('/@example\.(com|org|net)$/i', (string) $a['email'])));
}

function ec_request_timings(): array
{
    return ec_i18n_fill([
        'now' => ['tr' => 'Hemen', 'en' => 'Right away', 'ru' => 'Сразу'],
        '3m' => ['tr' => '1–3 ay içinde', 'en' => 'Within 1–3 months', 'ru' => 'В течение 1–3 месяцев'],
        'later' => ['tr' => '3 aydan sonra', 'en' => 'In more than 3 months', 'ru' => 'Более чем через 3 месяца'],
        'look' => ['tr' => 'Sadece fikir ediniyorum', 'en' => 'Just looking', 'ru' => 'Просто присматриваюсь'],
    ]);
}

/** Arama filtrelerinin Türkçe özeti (emlakçıya gider) */
function ec_request_filters_text(array $f): string
{
    $out = [];
    $modes = ['sale' => 'Satılık', 'rent' => 'Kiralık', 'daily' => 'Günlük kiralık'];
    if (!empty($f['mode']) && isset($modes[$f['mode']])) {
        $out[] = $modes[$f['mode']];
    }
    if (!empty($f['city'])) {
        $out[] = ec_city_name(sanitize_key((string) $f['city']), 'tr') . (!empty($f['district']) ? ' / ' . sanitize_text_field((string) $f['district']) : '');
    }
    if (!empty($f['subtype']) && !empty($f['ptype'])) {
        $out[] = ec_subtype_name(sanitize_key((string) $f['ptype']), sanitize_key((string) $f['subtype']), 'tr');
    } elseif (!empty($f['ptype'])) {
        $out[] = ec_type_name(sanitize_key((string) $f['ptype']), 'tr');
    }
    if (!empty($f['rooms'])) {
        $out[] = implode(', ', array_map('sanitize_text_field', (array) $f['rooms'])) . ' oda';
    }
    $cur = in_array($f['currency'] ?? '', EC_CURRENCIES, true) ? $f['currency'] : 'GBP';
    if (!empty($f['min']) || !empty($f['max'])) {
        $out[] = 'Fiyat: ' . (!empty($f['min']) ? number_format((float) $f['min'], 0, ',', '.') : '…') . ' – ' . (!empty($f['max']) ? number_format((float) $f['max'], 0, ',', '.') : '…') . ' ' . $cur;
    }
    if (!empty($f['feat'])) {
        $names = [];
        foreach ((array) $f['feat'] as $k) {
            if (is_string($k) && isset(ec_features()[$k])) {
                $names[] = ec_features()[$k]['tr'];
            }
        }
        if ($names) {
            $out[] = implode(', ', $names);
        }
    }
    if (isset($f['furnished']) && $f['furnished'] !== '' && $f['furnished'] !== null) {
        $out[] = $f['furnished'] ? 'Eşyalı' : 'Eşyasız';
    }
    return implode(' · ', $out);
}

/** @return array{count:int}|WP_Error */
function ec_request_create(array $in)
{
    global $wpdb;
    if (!empty($in['website'])) {
        return new WP_Error('ec_spam', 'Gönderilemedi', ['status' => 400]);
    }
    $name = mb_substr(sanitize_text_field((string) ($in['name'] ?? '')), 0, 120);
    $email = sanitize_email((string) ($in['email'] ?? ''));
    $phone = mb_substr(preg_replace('/[^0-9+ ()-]/', '', (string) ($in['phone'] ?? '')), 0, 40);
    $text = mb_substr(ec_clean_textarea((string) ($in['message'] ?? '')), 0, 1500);
    $aiq = mb_substr(sanitize_text_field((string) ($in['ai_q'] ?? '')), 0, 300);
    $timing = array_key_exists((string) ($in['timing'] ?? ''), ec_request_timings()) ? $in['timing'] : '';
    $contact = in_array($in['contact'] ?? '', ['phone', 'whatsapp', 'email'], true) ? $in['contact'] : 'phone';
    $f = is_array($in['filters'] ?? null) ? $in['filters'] : [];
    $err = [];
    if ($name === '') {
        $err['name'] = 'zorunlu';
    }
    if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
        $err['phone'] = 'zorunlu';
    }
    if (!empty($in['email']) && !is_email($email)) {
        $err['email'] = 'geçersiz';
    }
    if (mb_strlen($text) < 5 && $aiq === '') {
        $err['message'] = 'zorunlu';
    }
    if (empty($in['consent'])) {
        $err['consent'] = 'zorunlu';
    }
    if ($err) {
        return new WP_Error('ec_invalid', 'Eksik bilgi', ['status' => 400, 'fields' => $err]);
    }
    if (function_exists('ec_turnstile_keys') && ec_turnstile_keys()['site'] && !ec_turnstile_verify($in['cf-turnstile-response'] ?? null)) {
        return new WP_Error('ec_captcha', 'Doğrulama başarısız', ['status' => 400, 'fields' => ['captcha' => 'captcha']]);
    }
    if (ec_rate_limited('req', 3, DAY_IN_SECONDS)) {
        return new WP_Error('ec_too_many', 'too_many', ['status' => 429]);
    }
    $day = 'ec_req_day_' . gmdate('Ymd');
    if ((int) get_transient($day) >= 60) { // sitenin toplam günlük sınırı (kötüye kullanıma karşı)
        return new WP_Error('ec_too_many', 'too_many', ['status' => 429]);
    }
    set_transient($day, (int) get_transient($day) + 1, DAY_IN_SECONDS);

    $lang = in_array($in['lang'] ?? '', EC_LANGS, true) ? $in['lang'] : 'tr';
    $url = esc_url_raw((string) ($in['url'] ?? ''));
    if ($url && !str_starts_with($url, home_url('/'))) {
        $url = '';
    }
    $lines = [];
    if ($aiq !== '') {
        $lines[] = 'Arama cümlesi: “' . $aiq . '”';
    }
    if ($ft = ec_request_filters_text($f)) {
        $lines[] = 'Kriterler: ' . $ft;
    }
    if ($text !== '' && $text !== $aiq) {
        $lines[] = "Müşterinin notu:\n" . $text;
    }
    if ($timing) {
        $lines[] = 'Ne zaman: ' . ec_request_timings()[$timing]['tr'];
    }
    $lines[] = 'Tercih ettiği iletişim: ' . ['phone' => 'Telefon', 'whatsapp' => 'WhatsApp', 'email' => 'E-posta'][$contact];
    $message = implode("\n", $lines);

    $recips = ec_request_recipients();
    $ids = [];
    foreach ($recips as $a) {
        $wpdb->insert(ec_table('leads'), [
            'kind' => 'request', 'agent_id' => (int) $a['id'], 'listing_id' => null, 'project_id' => null,
            'name' => $name, 'email' => $email ?: null, 'phone' => $phone, 'message' => $message,
            'lang' => $lang, 'status' => 'new', 'source' => 'request', 'ip_hash' => ec_ip_hash(), 'created_at' => ec_now(),
        ]);
        $ids[] = (int) $wpdb->insert_id;
    }
    $admin = "🏠 Yeni emlak talebi (" . count($ids) . " üyeye iletildi)\n\nAd: $name\nTelefon: $phone\n" . ($email ? "E-posta: $email\n" : '') . 'Dil: ' . strtoupper($lang) . "\n\n$message" . ($url ? "\n\nArama: $url" : '');
    ec_notify_admin($admin);
    if ($ids) {
        wp_schedule_single_event(time(), 'ec_request_fanout', [$ids]);
        if (function_exists('spawn_cron')) {
            spawn_cron();
        }
    }
    return ['count' => count($ids)];
}

/** Arka planda: her alıcıya e-posta + Telegram + anlık bildirim */
add_action('ec_request_fanout', function ($ids) {
    global $wpdb;
    foreach ((array) $ids as $id) {
        $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('leads') . ' WHERE id = %d', (int) $id), ARRAY_A);
        if (!$r || !$r['agent_id'] || !($a = ec_agent((int) $r['agent_id']))) {
            continue;
        }
        $panel = add_query_arg('bolum', 'talepler', ec_url_section('panel', $a['wp_user_id'] ? ec_member_lang((int) $a['wp_user_id']) : 'tr'));
        $body = "🏠 Yeni emlak talebi\n\nAd: {$r['name']}\nTelefon: {$r['phone']}\n" . ($r['email'] ? "E-posta: {$r['email']}\n" : '') . 'Dil: ' . strtoupper($r['lang']) . "\n\n{$r['message']}\n\nBu talep AdaCasa'ya kayıtlı tüm emlak ofislerine iletildi; müşteriye hızlı dönen kazanır.";
        if (!empty($a['email'])) {
            $h = $r['email'] ? ['Reply-To: ' . $r['name'] . ' <' . $r['email'] . '>'] : [];
            wp_mail($a['email'], get_bloginfo('name') . ': yeni emlak talebi', $body . "\n\nTalepler ve müşteri takibi: $panel", $h);
        }
        if (!empty($a['telegram_chat_id']) && function_exists('ec_tg_send')) {
            ec_tg_send($a['telegram_chat_id'], $body);
        }
        if (function_exists('ec_push_agent')) {
            ec_push_agent((int) $a['id'], ['title' => '🏠 Yeni emlak talebi', 'body' => $r['name'] . ' · ' . mb_substr(preg_replace('/\s+/', ' ', $r['message']), 0, 110), 'url' => $panel, 'tag' => 'ec-req-' . $id]);
        }
    }
});

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/requests', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $req) {
            $r = ec_request_create((array) $req->get_json_params());
            return is_wp_error($r) ? $r : new WP_REST_Response(['ok' => true] + $r, 201);
        },
    ]);
});
