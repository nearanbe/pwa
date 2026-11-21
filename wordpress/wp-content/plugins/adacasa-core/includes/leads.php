<?php
/**
 * Talepler (lead) ve hatalı ilan bildirimleri.
 *   POST /listings/{id}/lead     ilan sayfasındaki "Mesaj gönder" formu
 *   POST /projects/{id}/lead     proje sayfasındaki form
 *   POST /listings/{id}/report   "Hatalı ilan bildir"
 *   GET  /me/leads               üyenin talepleri       PATCH /me/leads/{id} {"status":"read|done|spam"}
 *   GET  /reports                açık bildirimler (yönetici)   PATCH /reports/{id} {"status":"closed"}
 * Talep, ilan sahibine e-posta (ve bağlıysa Telegram) ile gider; yöneticiye kopya düşer (Ayarlar'dan kapatılabilir).
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_LEAD_STATUSES = ['new', 'read', 'done', 'spam'];

function ec_report_reasons(): array
{
    return [
        'sold'      => ['tr' => 'Satıldı / kiralandı', 'en' => 'Already sold / let', 'ru' => 'Уже продано / сдано'],
        'price'     => ['tr' => 'Fiyat yanlış', 'en' => 'Wrong price', 'ru' => 'Неверная цена'],
        'photos'    => ['tr' => 'Fotoğraflar bu mülke ait değil', 'en' => 'Photos are not of this property', 'ru' => 'Фото не этого объекта'],
        'location'  => ['tr' => 'Konum yanlış', 'en' => 'Wrong location', 'ru' => 'Неверное расположение'],
        'duplicate' => ['tr' => 'Aynı ilan birden fazla kez girilmiş', 'en' => 'Duplicate listing', 'ru' => 'Дубликат'],
        'fraud'     => ['tr' => 'Dolandırıcılık şüphesi', 'en' => 'Suspected fraud', 'ru' => 'Подозрение на мошенничество'],
        'other'     => ['tr' => 'Diğer', 'en' => 'Other', 'ru' => 'Другое'],
    ];
}

function ec_ip_hash(): string
{
    return sha1(($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt('nonce'));
}

/** Basit hız sınırı: aynı IP'den $max istek / $window saniye */
function ec_rate_limited(string $bucket, int $max, int $window): bool
{
    $k = 'ec_rl_' . $bucket . '_' . substr(ec_ip_hash(), 0, 16);
    $n = (int) get_transient($k);
    if ($n >= $max) {
        return true;
    }
    set_transient($k, $n + 1, $window);
    return false;
}

/**
 * Yeni talep. $target = ['listing_id' => ..] ya da ['project_id' => ..]
 * @return int|WP_Error
 */
function ec_lead_create(array $in, array $target)
{
    global $wpdb;
    if (!empty($in['website'])) { // bal küpü (bot)
        return new WP_Error('ec_spam', 'Gönderilemedi', ['status' => 400]);
    }
    $name = mb_substr(sanitize_text_field((string) ($in['name'] ?? '')), 0, 120);
    $email = sanitize_email((string) ($in['email'] ?? ''));
    $phone = mb_substr(preg_replace('/[^0-9+ ()-]/', '', (string) ($in['phone'] ?? '')), 0, 40);
    $message = mb_substr(ec_clean_textarea((string) ($in['message'] ?? '')), 0, 2000);
    $err = [];
    if ($name === '') {
        $err['name'] = 'zorunlu';
    }
    if (!$email && strlen(preg_replace('/\D/', '', $phone)) < 7) {
        $err['phone'] = 'telefon veya e-posta zorunlu';
    }
    if (!empty($in['email']) && !is_email($email)) {
        $err['email'] = 'geçersiz';
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
    if (ec_rate_limited('lead', 6, HOUR_IN_SECONDS)) {
        return new WP_Error('ec_too_many', 'Çok fazla mesaj gönderildi, lütfen daha sonra deneyin', ['status' => 429]);
    }

    $agent = null;
    $title = '';
    $url = '';
    if (!empty($target['listing_id'])) {
        $row = ec_listing_row((int) $target['listing_id']);
        if (!$row || !in_array($row['status'], ['published', 'sold'], true)) {
            return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
        }
        $agent = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
        $item = ec_listings_present([$row], 'tr')[0];
        $title = '#' . $row['id'] . ' ' . $item['title'];
        $url = $item['url'];
    } elseif (!empty($target['project_id'])) {
        $p = ec_project_row((int) $target['project_id']);
        if (!$p || $p['status'] !== 'published') {
            return new WP_Error('ec_not_found', 'Proje bulunamadı', ['status' => 404]);
        }
        $agent = $p['agent_id'] ? ec_agent((int) $p['agent_id']) : null;
        $title = $p['name'];
        $url = ec_project_present($p, 'tr')['url'];
    }
    $lang = in_array($in['lang'] ?? '', EC_LANGS, true) ? $in['lang'] : 'tr';

    $wpdb->insert(ec_table('leads'), [
        'agent_id' => $agent['id'] ?? null,
        'listing_id' => $target['listing_id'] ?? null,
        'project_id' => $target['project_id'] ?? null,
        'name' => $name, 'email' => $email ?: null, 'phone' => $phone ?: null, 'message' => $message,
        'lang' => $lang, 'status' => 'new', 'ip_hash' => ec_ip_hash(), 'created_at' => ec_now(),
    ]);
    $id = (int) $wpdb->insert_id;

    $body = "📩 Yeni mesaj: $title\n\nAd: $name\n" . ($phone ? "Telefon: $phone\n" : '') . ($email ? "E-posta: $email\n" : '') . 'Dil: ' . strtoupper($lang) . "\n\n$message\n\n$url";
    if ($agent) {
        if (!empty($agent['email'])) {
            $headers = $email ? ['Reply-To: ' . $name . ' <' . $email . '>'] : [];
            wp_mail($agent['email'], get_bloginfo('name') . ': ilanınız için yeni mesaj', $body . "\n\nTüm mesajlar: " . add_query_arg('bolum', 'talepler', ec_url_section('panel', ec_member_lang((int) $agent['wp_user_id']))), $headers);
        }
        if (!empty($agent['telegram_chat_id']) && function_exists('ec_tg_send')) {
            ec_tg_send($agent['telegram_chat_id'], $body);
        }
    }
    if (!$agent || get_option('ec_lead_admin_copy', 1)) {
        ec_notify_admin($body . ($agent ? "\n\nİlan sahibi: {$agent['name']}" : ''));
    }
    return $id;
}

function ec_lead_present(array $r): array
{
    $ctx = null;
    if ($r['listing_id'] && ($row = ec_listing_row((int) $r['listing_id']))) {
        $it = ec_listings_present([$row], ec_lang())[0];
        $ctx = ['type' => 'listing', 'id' => (int) $r['listing_id'], 'title' => $it['title'], 'url' => $it['url']];
    } elseif ($r['project_id'] && ($p = ec_project_row((int) $r['project_id']))) {
        $ctx = ['type' => 'project', 'id' => (int) $r['project_id'], 'title' => $p['name'], 'url' => ec_project_present($p, ec_lang())['url']];
    }
    return [
        'id' => (int) $r['id'], 'name' => $r['name'], 'email' => $r['email'], 'phone' => $r['phone'], 'message' => $r['message'],
        'lang' => $r['lang'], 'status' => $r['status'], 'created_at' => $r['created_at'], 'about' => $ctx,
    ];
}

function ec_leads_for_agent(int $agent_id, int $limit = 200): array
{
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('leads') . " WHERE agent_id = %d AND status <> 'spam' ORDER BY id DESC LIMIT %d", $agent_id, $limit), ARRAY_A);
    return array_map('ec_lead_present', $rows);
}

function ec_leads_unread(int $agent_id): int
{
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ec_table('leads') . " WHERE agent_id = %d AND status = 'new'", $agent_id));
}

/** @return int|WP_Error */
function ec_report_create(int $listing_id, array $in)
{
    global $wpdb;
    $row = ec_listing_row($listing_id);
    if (!$row || !in_array($row['status'], ['published', 'sold'], true)) {
        return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
    }
    if (!empty($in['website'])) {
        return new WP_Error('ec_spam', 'Gönderilemedi', ['status' => 400]);
    }
    $reason = (string) ($in['reason'] ?? '');
    if (!isset(ec_report_reasons()[$reason])) {
        return new WP_Error('ec_invalid', 'Sebep seçin', ['status' => 400, 'fields' => ['reason' => 'zorunlu']]);
    }
    if (ec_rate_limited('report', 5, HOUR_IN_SECONDS)) {
        return new WP_Error('ec_too_many', 'Çok fazla bildirim', ['status' => 429]);
    }
    $note = mb_substr(sanitize_textarea_field((string) ($in['note'] ?? '')), 0, 1000);
    $email = sanitize_email((string) ($in['email'] ?? ''));
    $wpdb->insert(ec_table('reports'), [
        'listing_id' => $listing_id, 'reason' => $reason, 'note' => $note, 'email' => $email ?: null,
        'status' => 'open', 'ip_hash' => ec_ip_hash(), 'created_at' => ec_now(),
    ]);
    $rid = (int) $wpdb->insert_id;
    do_action('ec_report_created', $rid, $listing_id, $reason);
    $item = ec_listings_present([$row], 'tr')[0];
    ec_notify_admin("⚠️ Hatalı ilan bildirimi #$listing_id\n{$item['title']}\nSebep: " . ec_report_reasons()[$reason]['tr'] . ($note ? "\nNot: $note" : '') . ($email ? "\nBildiren: $email" : ''), ec_url_section('review', 'tr'));
    return $rid;
}

function ec_reports_open(int $limit = 100): array
{
    global $wpdb;
    $t = ec_table('reports');
    $rows = $wpdb->get_results("SELECT r.*, (SELECT COUNT(*) FROM $t x WHERE x.listing_id = r.listing_id AND x.status = 'open') AS n FROM $t r WHERE r.status = 'open' ORDER BY r.id DESC LIMIT " . (int) $limit, ARRAY_A);
    return $rows ?: [];
}

add_action('rest_api_init', function () {
    $public = function (string $kind) {
        return function (WP_REST_Request $r) use ($kind) {
            $in = $r->get_json_params() ?: $r->get_body_params();
            $in['lang'] = ec_rest_lang($r);
            $res = ec_lead_create($in, [$kind . '_id' => (int) $r['id']]);
            return is_wp_error($res) ? $res : new WP_REST_Response(['ok' => true, 'id' => $res], 201);
        };
    };
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/lead', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => $public('listing')]);
    register_rest_route(EC_NS, '/projects/(?P<id>\d+)/lead', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => $public('project')]);

    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/report', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            $res = ec_report_create((int) $r['id'], $r->get_json_params() ?: $r->get_body_params());
            return is_wp_error($res) ? $res : new WP_REST_Response(['ok' => true], 201);
        },
    ]);

    register_rest_route(EC_NS, '/me/leads', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            return $a ? ['items' => ec_leads_for_agent((int) $a['id']), 'unread' => ec_leads_unread((int) $a['id'])] : ['items' => [], 'unread' => 0];
        },
    ]);
    register_rest_route(EC_NS, '/me/leads/(?P<id>\d+)', [
        'methods' => 'PATCH',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $a = ec_rest_agent($r);
            $st = (string) ($r->get_json_params()['status'] ?? $r->get_param('status'));
            if (!$a || !in_array($st, EC_LEAD_STATUSES, true)) {
                return new WP_Error('ec_invalid', 'Geçersiz durum', ['status' => 400]);
            }
            $n = $wpdb->update(ec_table('leads'), ['status' => $st], ['id' => (int) $r['id'], 'agent_id' => (int) $a['id']]);
            return $n ? ['ok' => true] : new WP_Error('ec_not_found', 'Talep bulunamadı', ['status' => 404]);
        },
    ]);

    $admin = fn() => current_user_can('manage_options') ? true : new WP_Error('ec_forbidden', 'Yetkisiz', ['status' => 403]);
    register_rest_route(EC_NS, '/reports', ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => fn() => ['items' => ec_reports_open()]]);
    register_rest_route(EC_NS, '/reports/(?P<id>\d+)', [
        'methods' => 'PATCH',
        'permission_callback' => $admin,
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $wpdb->update(ec_table('reports'), ['status' => 'closed'], ['id' => (int) $r['id']]);
            return ['ok' => true];
        },
    ]);
});
