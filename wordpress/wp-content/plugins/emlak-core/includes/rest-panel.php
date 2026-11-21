<?php
/**
 * Panelim ve onay paneli için API uçları (oturum + X-WP-Nonce ya da API anahtarı ile).
 *   GET/PATCH /me                profil
 *   POST /me/logo                logo yükleme
 *   POST /me/telegram-code       Telegram eşleştirme kodu
 *   GET  /me/projects            üyenin projeleri
 *   POST /projects               proje oluştur      PATCH/DELETE /projects/{id}
 *   POST /projects/{id}/cover    kapak              POST /projects/{id}/media (kind=photo|plan|brochure)
 *   DELETE /projects/{id}/media/{mid}
 *   GET  /index/compare          fiyatın bölge medyanına göre konumu
 *   GET  /review                 onay bekleyenler (yönetici)
 *   POST /review/{type}/{id}     onayla / reddet (yönetici)
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Bekleyen ilan için yöneticiye bildirim */
function ec_notify_pending_listing(int $id): void
{
    $row = ec_listing_row($id);
    if (!$row) {
        return;
    }
    $item = ec_listings_present([$row], 'tr')[0];
    $a = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    $kind = $a ? (ec_member_kinds()[$a['kind']]['name']['tr'] ?? $a['kind']) : '-';
    ec_notify_admin(
        sprintf("🕓 Onay bekleyen ilan #%d\n%s\n%s · %s · £%s\nİlan sahibi: %s (%s) %s", $id, $item['title'], $item['subtype_name'], $item['city_name'], number_format($item['price_gbp'], 0, ',', '.'), $a['name'] ?? '-', $kind, $a['phone'] ?? ''),
        ec_url_section('review', 'tr'),
        !function_exists('ec_tg_admin_listing_card') // Telegram'a düğmeli kart ayrıca gider
    );
    if (function_exists('ec_tg_admin_listing_card') && ec_tg_ready()) {
        ec_tg_admin_listing_card($id);
    }
}

/** Onay / red işlemleri (REST ve yönetim paneli ortak kullanır) */
function ec_review_listing(int $id, string $action, string $reason = ''): bool
{
    global $wpdb;
    $row = ec_listing_row($id);
    if (!$row) {
        return false;
    }
    $a = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    $title = ec_listings_present([$row], 'tr')[0]['title'];
    if ($action === 'approve') {
        $wpdb->update(ec_table('listings'), ['status' => 'published', 'reject_reason' => null, 'published_at' => $row['published_at'] ?: ec_now(), 'updated_at' => ec_now()], ['id' => $id]);
        if ($a) {
            do_action('ec_listing_reviewed', $id, 'approve', '');
            ec_notify_member($a, 'İlanınız yayında', "#$id numaralı \"$title\" ilanınız onaylandı ve yayına alındı.\n\n" . ec_listings_present([ec_listing_row($id)], ec_member_lang((int) $a['wp_user_id']))[0]['url']);
        }
        return true;
    }
    if ($action === 'reject') {
        $wpdb->update(ec_table('listings'), ['status' => 'rejected', 'reject_reason' => mb_substr(sanitize_text_field($reason), 0, 255) ?: null, 'updated_at' => ec_now()], ['id' => $id]);
        do_action('ec_listing_reviewed', $id, 'reject', (string) $reason);
        if ($a) {
            ec_notify_member($a, 'İlanınız yayına alınamadı', "#$id numaralı \"$title\" ilanınız yayına alınamadı." . ($reason ? "\nSebep: $reason" : '') . "\n\nİlanı düzeltip tekrar gönderebilirsiniz:\n" . ec_url_section('panel', ec_member_lang((int) $a['wp_user_id'])));
        }
        return true;
    }
    return false;
}

function ec_review_agent(int $id, string $action): bool
{
    global $wpdb;
    $a = ec_agent($id);
    if (!$a) {
        return false;
    }
    $map = ['approve' => ['status' => 'active'], 'suspend' => ['status' => 'suspended'], 'verify' => ['verified' => 1], 'unverify' => ['verified' => 0]];
    if (!isset($map[$action])) {
        return false;
    }
    $wpdb->update(ec_table('agents'), $map[$action] + ['updated_at' => ec_now()], ['id' => $id]);
    if ($action === 'approve' && $a['status'] !== 'active') {
        ec_notify_member($a, 'Hesabınız onaylandı', "Merhaba {$a['name']},\n\nHesabınız onaylandı. İlanlarınız artık doğrudan yayına alınır.\n\n" . ec_url_section('panel', ec_member_lang((int) $a['wp_user_id'])));
        // Onay beklerken girilmiş ilanları da yayına al (ofis / inşaat firması)
        if (empty(ec_member_kinds()[$a['kind']]['needs_review'])) {
            $wpdb->query($wpdb->prepare('UPDATE ' . ec_table('listings') . " SET status = 'published', published_at = IFNULL(published_at, UTC_TIMESTAMP()) WHERE agent_id = %d AND status = 'pending'", $id));
            $wpdb->query($wpdb->prepare('UPDATE ' . ec_table('projects') . " SET status = 'published' WHERE agent_id = %d AND status = 'pending'", $id));
        }
    }
    return true;
}

function ec_review_project(int $id, string $action, string $reason = ''): bool
{
    global $wpdb;
    if (!ec_project_row($id) || !in_array($action, ['approve', 'reject'], true)) {
        return false;
    }
    $wpdb->update(ec_table('projects'), [
        'status' => $action === 'approve' ? 'published' : 'rejected',
        'reject_reason' => $action === 'reject' ? (mb_substr(sanitize_text_field($reason), 0, 255) ?: null) : null,
        'updated_at' => ec_now(),
    ], ['id' => $id]);
    return true;
}

function ec_rest_own_project(WP_REST_Request $r)
{
    $ok = ec_rest_require_agent($r);
    if (is_wp_error($ok)) {
        return $ok;
    }
    $p = ec_project_row((int) $r['id']);
    if (!$p) {
        return new WP_Error('ec_not_found', 'Proje bulunamadı', ['status' => 404]);
    }
    if (current_user_can('manage_options')) {
        return true;
    }
    $a = ec_rest_agent($r);
    return (int) $p['agent_id'] === (int) $a['id'] ? true : new WP_Error('ec_forbidden', 'Bu proje size ait değil', ['status' => 403]);
}

/** Yüklenen tek dosya (alan adı $field) */
function ec_rest_file(WP_REST_Request $r, string $field): ?string
{
    $f = $r->get_file_params()[$field] ?? null;
    if (!$f || is_array($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return null;
    }
    return $f['tmp_name'];
}

add_action('rest_api_init', function () {
    $admin = fn() => current_user_can('manage_options') ? true : new WP_Error('ec_forbidden', 'Yetkisiz', ['status' => 403]);

    register_rest_route(EC_NS, '/me', [
        'methods' => 'PATCH',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            if (!$a) {
                return new WP_Error('ec_no_agent', 'Üye hesabı gerekli', ['status' => 400]);
            }
            $res = ec_member_update_profile((int) $a['id'], $r->get_json_params() ?: $r->get_body_params());
            return is_wp_error($res) ? $res : ec_agent_public(ec_agent((int) $a['id'])) + ['telegram_opt_in' => (bool) ec_agent((int) $a['id'])['telegram_opt_in']];
        },
    ]);

    register_rest_route(EC_NS, '/me/logo', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $file = ec_rest_file($r, 'logo');
            if (!$a || !$file) {
                return new WP_Error('ec_no_file', 'Logo dosyası gerekli', ['status' => 400]);
            }
            $res = ec_member_set_logo((int) $a['id'], $file);
            return is_wp_error($res) ? $res : ['logo' => ec_agent_public(ec_agent((int) $a['id']))['logo']];
        },
    ]);

    register_rest_route(EC_NS, '/me/telegram-code', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $bot = (string) (get_option('ec_telegram')['bot'] ?? '');
            if (!$a) {
                return new WP_Error('ec_no_agent', 'Üye hesabı gerekli', ['status' => 400]);
            }
            $code = ec_member_telegram_code((int) $a['id']);
            return ['code' => $code, 'expires_in' => 600, 'link' => $bot ? 'https://t.me/' . $bot . '?start=' . $code : null];
        },
    ]);

    register_rest_route(EC_NS, '/me/projects', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $a = ec_rest_agent($r);
            $rows = $a ? $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('projects') . ' WHERE agent_id = %d ORDER BY id DESC', $a['id']), ARRAY_A) : [];
            return array_map(fn($p) => ec_project_present($p, ec_rest_lang($r)), $rows);
        },
    ]);

    register_rest_route(EC_NS, '/projects', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $body = $r->get_json_params() ?: $r->get_body_params();
            if ($a && empty(ec_member_kinds()[$a['kind']]['projects'])) {
                return new WP_Error('ec_forbidden', 'Proje eklemek için ofis veya inşaat firması hesabı gerekir', ['status' => 403]);
            }
            $want = ($body['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            $body['status'] = $a ? ($want === 'published' ? ($a['status'] === 'active' ? 'published' : 'pending') : 'draft') : $want;
            $body['agent_id'] = $a['id'] ?? ($body['agent_id'] ?? null);
            if (empty($body['developer']) && $a) {
                $body['developer'] = $a['name'];
            }
            $id = ec_project_create($body, 'panel');
            if (is_wp_error($id)) {
                return $id;
            }
            if ($body['status'] === 'pending') {
                ec_notify_admin("🏗️ Onay bekleyen proje #$id: " . sanitize_text_field($body['name'] ?? ''), ec_url_section('review', 'tr'));
            }
            return new WP_REST_Response(ec_project_present(ec_project_row($id), ec_rest_lang($r), true), 201);
        },
    ]);

    register_rest_route(EC_NS, '/projects/(?P<id>\d+)', [
        [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $r) {
                $owner = ec_rest_own_project($r) === true;
                return ec_project_get((int) $r['id'], ec_rest_lang($r), !$owner) ?: new WP_Error('ec_not_found', 'Proje bulunamadı', ['status' => 404]);
            },
        ],
        [
            'methods' => 'PATCH',
            'permission_callback' => 'ec_rest_own_project',
            'callback' => function (WP_REST_Request $r) {
                $body = $r->get_json_params() ?: $r->get_body_params();
                $a = ec_rest_agent($r);
                if (isset($body['status']) && $a && !current_user_can('manage_options')) {
                    $want = in_array($body['status'], ['draft', 'published', 'passive'], true) ? $body['status'] : 'draft';
                    $body['status'] = $want === 'published' ? ($a['status'] === 'active' ? 'published' : 'pending') : $want;
                }
                unset($body['agent_id']);
                $res = ec_project_update((int) $r['id'], $body);
                return is_wp_error($res) ? $res : ec_project_present(ec_project_row((int) $r['id']), ec_rest_lang($r), true);
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => 'ec_rest_own_project',
            'callback' => function (WP_REST_Request $r) {
                ec_project_update((int) $r['id'], ['status' => 'passive']);
                return ['id' => (int) $r['id'], 'status' => 'passive'];
            },
        ],
    ]);

    register_rest_route(EC_NS, '/projects/(?P<id>\d+)/cover', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_project',
        'callback' => function (WP_REST_Request $r) {
            $file = ec_rest_file($r, 'cover');
            if (!$file) {
                return new WP_Error('ec_no_file', 'Kapak görseli gerekli', ['status' => 400]);
            }
            $res = ec_project_set_cover((int) $r['id'], $file);
            return is_wp_error($res) ? $res : ['cover' => ec_project_present(ec_project_row((int) $r['id']), 'tr')['cover']];
        },
    ]);

    register_rest_route(EC_NS, '/projects/(?P<id>\d+)/media', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_project',
        'callback' => function (WP_REST_Request $r) {
            $file = ec_rest_file($r, 'file');
            if (!$file) {
                return new WP_Error('ec_no_file', 'Dosya gerekli', ['status' => 400]);
            }
            $res = ec_project_media_add((int) $r['id'], $file, (string) $r->get_param('kind'), (string) $r->get_param('title'));
            return is_wp_error($res) ? $res : new WP_REST_Response($res, 201);
        },
    ]);

    register_rest_route(EC_NS, '/projects/(?P<id>\d+)/media/(?P<mid>\d+)', [
        'methods' => 'DELETE',
        'permission_callback' => 'ec_rest_own_project',
        'callback' => fn(WP_REST_Request $r) => ec_project_media_delete((int) $r['id'], (int) $r['mid']) ? ['ok' => true] : new WP_Error('ec_not_found', 'Dosya bulunamadı', ['status' => 404]),
    ]);

    register_rest_route(EC_NS, '/index/compare', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            $cur = in_array($r['currency'], EC_CURRENCIES, true) ? $r['currency'] : 'GBP';
            $cmp = ec_index_compare([
                'ptype' => (string) $r['ptype'] ?: 'konut',
                'mode' => (string) $r['mode'],
                'city' => (string) $r['city'],
                'area_m2' => (int) $r['area_m2'],
                'price_gbp' => ec_to_gbp((float) $r['price'], $cur),
            ]);
            return $cmp ?: ['diff' => null];
        },
    ]);

    register_rest_route(EC_NS, '/review/(?P<type>listing|agent|project)/(?P<id>\d+)', [
        'methods' => 'POST',
        'permission_callback' => $admin,
        'callback' => function (WP_REST_Request $r) {
            $id = (int) $r['id'];
            $action = (string) $r->get_param('action');
            $reason = (string) $r->get_param('reason');
            $ok = match ($r['type']) {
                'listing' => ec_review_listing($id, $action, $reason),
                'agent' => ec_review_agent($id, $action),
                'project' => ec_review_project($id, $action, $reason),
            };
            return $ok ? ['ok' => true] : new WP_Error('ec_review', 'İşlem yapılamadı', ['status' => 400]);
        },
    ]);
});
