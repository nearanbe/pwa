<?php
/**
 * REST API v1 — Telegram botu ve Chrome eklentisi siteyle SADECE bu API üzerinden konuşur.
 * Adres: /api/v1/...  (WordPress karşılığı: /wp-json/emlak/v1/...)
 * Sözleşme: docs/api.md. Next.js geçişinde aynı uçlar aynı cevaplarla yeniden yazılır.
 *
 * Kimlik doğrulama: "Authorization: Bearer ec_..." veya "X-Emlak-Key: ec_..." başlığı.
 * Yöneticiler WordPress oturumuyla (nonce) tüm ilanlara erişebilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_NS = 'emlak/v1';

function ec_rest_lang(WP_REST_Request $r): string
{
    $l = (string) $r->get_param('lang');
    return in_array($l, EC_LANGS, true) ? $l : EC_DEFAULT_LANG;
}

/**
 * İsteği yapan danışman: API anahtarı (bot, eklenti) ya da sitede oturum açmış üye (Panelim).
 * Oturum için WordPress'in X-WP-Nonce başlığı gerekir; nonce yoksa WordPress kullanıcıyı tanımaz.
 */
function ec_rest_agent(WP_REST_Request $r): ?array
{
    $h = (string) $r->get_header('authorization');
    $key = str_starts_with($h, 'Bearer ') ? trim(substr($h, 7)) : (string) $r->get_header('x_emlak_key');
    if ($key) {
        return ec_agent_by_key($key);
    }
    $a = is_user_logged_in() ? ec_current_agent() : null;
    return ($a && $a['email_verified'] && $a['status'] !== 'suspended') ? $a : null;
}

function ec_rest_require_agent(WP_REST_Request $r)
{
    if (current_user_can('manage_options') || ec_rest_agent($r)) {
        return true;
    }
    return new WP_Error('ec_unauthorized', 'Geçerli bir API anahtarı gerekli', ['status' => 401]);
}

/** İlan bu danışmanın mı? (yönetici her ilana erişir) */
function ec_rest_own_listing(WP_REST_Request $r)
{
    $ok = ec_rest_require_agent($r);
    if (is_wp_error($ok)) {
        return $ok;
    }
    $row = ec_listing_row((int) $r['id']);
    if (!$row) {
        return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
    }
    if (current_user_can('manage_options')) {
        return true;
    }
    $agent = ec_rest_agent($r);
    if ((int) $row['agent_id'] !== (int) $agent['id']) {
        return new WP_Error('ec_forbidden', 'Bu ilan size ait değil', ['status' => 403]);
    }
    if (!empty($row['hold_office_id']) && $r->get_method() !== 'GET') {
        return new WP_Error('ec_locked', 'Bu ilan ayrıldığınız ofisin portföyünde; ofis aktarımı tamamlanana kadar değiştirilemez.', ['status' => 423]);
    }
    return true;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/meta', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            $lang = ec_rest_lang($r);
            $cities = [];
            foreach (ec_cities() as $k => $c) {
                $cities[] = ['key' => $k, 'name' => $c['name'][$lang], 'districts' => $c['districts']];
            }
            $types = [];
            foreach (ec_property_types() as $k => $t) {
                $sub = [];
                foreach ($t['sub'] as $sk => $sn) {
                    $sub[] = ['key' => $sk, 'name' => $sn[$lang]];
                }
                $types[] = ['key' => $k, 'name' => $t['name'][$lang], 'subtypes' => $sub];
            }
            $deeds = [];
            foreach (ec_deed_types() as $k => $n) {
                $deeds[] = ['key' => $k, 'name' => $n[$lang]];
            }
            return [
                'api_version' => 1,
                'languages' => EC_LANGS,
                'modes' => ec_modes(),
                'currencies' => EC_CURRENCIES,
                'rates_per_gbp' => ec_rates(),
                'cities' => $cities,
                'property_types' => $types,
                'rooms' => ec_room_options(),
                'deed_types' => $deeds,
            ];
        },
    ]);

    register_rest_route(EC_NS, '/listings', [
        [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $r) {
                $f = $r->get_params();
                if (isset($f['ids']) && is_string($f['ids'])) {
                    $f['ids'] = explode(',', $f['ids']);
                }
                return ec_listing_search($f, ec_rest_lang($r), (int) ($r['page'] ?: 1), (int) ($r['per_page'] ?: 24));
            },
        ],
        [
            'methods' => 'POST',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                $agent = ec_rest_agent($r);
                $body = $r->get_json_params() ?: $r->get_body_params();
                $agentId = $agent ? (int) $agent['id'] : (isset($body['agent_id']) ? (int) $body['agent_id'] : null);
                $source = sanitize_key($body['source'] ?? ($agent ? 'api' : 'panel'));
                $requested = in_array($body['status'] ?? 'draft', ['draft', 'published'], true) ? ($body['status'] ?? 'draft') : 'draft';
                $body['status'] = current_user_can('manage_options') && !$agent ? ($body['status'] ?? 'draft') : ec_listing_target_status($agent, $requested);
                $id = ec_listing_create($body, $agentId, $source);
                if (is_wp_error($id)) {
                    return $id;
                }
                if ($body['status'] === 'pending') {
                    ec_notify_pending_listing($id);
                }
                $row = ec_listing_row($id);
                $item = ec_listings_present([$row], ec_rest_lang($r), true)[0];
                return new WP_REST_Response($item, 201);
            },
        ],
    ]);

    register_rest_route(EC_NS, '/listings/(?P<id>\d+)', [
        [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $r) {
                $owner = ec_rest_own_listing($r) === true;
                $item = ec_listing_get((int) $r['id'], ec_rest_lang($r), !$owner);
                return $item ?: new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
            },
        ],
        [
            'methods' => 'PATCH',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => function (WP_REST_Request $r) {
                $body = $r->get_json_params() ?: $r->get_body_params();
                $row = ec_listing_row((int) $r['id']);
                $agent = ec_rest_agent($r);
                if (isset($body['status']) && $agent && !current_user_can('manage_options')) {
                    // Üye sadece taslak, yayına gönder, satıldı, pasif seçebilir; onay kuralı uygulanır
                    $want = (string) $body['status'];
                    if (!in_array($want, ['draft', 'published', 'sold', 'passive'], true)) {
                        return new WP_Error('ec_status', 'Bu durumu seçemezsiniz', ['status' => 403]);
                    }
                    if ($want === 'published' && $row['status'] !== 'published') {
                        $want = ec_listing_target_status($agent, 'published');
                    }
                    $body['status'] = $want;
                }
                unset($body['featured_until'], $body['agent_id']);
                $res = ec_listing_update((int) $r['id'], $body);
                if (!is_wp_error($res) && ($body['status'] ?? '') === 'pending' && $row['status'] !== 'pending') {
                    ec_notify_pending_listing((int) $r['id']);
                }
                if (is_wp_error($res)) {
                    return $res;
                }
                return ec_listings_present([ec_listing_row((int) $r['id'])], ec_rest_lang($r), true)[0];
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => function (WP_REST_Request $r) {
                ec_listing_update((int) $r['id'], ['status' => 'passive']);
                return ['id' => (int) $r['id'], 'status' => 'passive'];
            },
        ],
    ]);

    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/photos', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            $files = $r->get_file_params();
            if (!$files) {
                return new WP_Error('ec_no_file', '"photos[]" alanında en az bir görsel gönderin', ['status' => 400]);
            }
            $added = [];
            $errors = [];
            foreach ($files as $f) {
                $list = is_array($f['tmp_name']) ? $f['tmp_name'] : [$f['tmp_name']];
                $errs = is_array($f['error']) ? $f['error'] : [$f['error']];
                foreach ($list as $i => $tmp) {
                    if ($errs[$i] !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                        $errors[] = 'yükleme hatası';
                        continue;
                    }
                    $p = ec_photo_add((int) $r['id'], $tmp);
                    is_wp_error($p) ? $errors[] = $p->get_error_message() : $added[] = $p;
                }
            }
            return new WP_REST_Response(['added' => $added, 'errors' => $errors], $added ? 201 : 400);
        },
    ]);

    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/photos/order', [
        'methods' => 'PUT',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            ec_photo_reorder((int) $r['id'], (array) $r->get_param('order'));
            return ['ok' => true];
        },
    ]);

    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/photos/(?P<pid>\d+)', [
        'methods' => 'DELETE',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            return ec_photo_delete((int) $r['id'], (int) $r['pid'])
                ? ['ok' => true]
                : new WP_Error('ec_not_found', 'Fotoğraf bulunamadı', ['status' => 404]);
        },
    ]);

    register_rest_route(EC_NS, '/me', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            if (!$a) {
                return new WP_Error('ec_no_agent', 'Bu uç bir danışman anahtarı ister', ['status' => 400]);
            }
            global $wpdb;
            $counts = $wpdb->get_results($wpdb->prepare('SELECT status, COUNT(*) n FROM ' . ec_table('listings') . ' WHERE agent_id = %d GROUP BY status', $a['id']), OBJECT_K);
            return ec_agent_public($a) + [
                'email' => $a['email'],
                'listing_counts' => array_map(fn($c) => (int) $c->n, $counts),
            ];
        },
    ]);

    register_rest_route(EC_NS, '/me/listings', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $a = ec_rest_agent($r);
            if (!$a) {
                return new WP_Error('ec_no_agent', 'Bu uç bir danışman anahtarı ister', ['status' => 400]);
            }
            $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('listings') . ' WHERE agent_id = %d ORDER BY id DESC LIMIT 500', $a['id']), ARRAY_A);
            return ec_listings_present($rows, ec_rest_lang($r));
        },
    ]);

});
