<?php
/**
 * 360° fotoğraflar (0.6). Küresel (equirectangular) fotoğraf: genişlik = 2 × yükseklik.
 * Dosyalar uploads/ilan-foto/{ilan}/360-xxxx-p.webp (en çok 6000 px) ve -t.webp (küçük resim) olarak durur.
 *   GET    /listings/{id}/panos
 *   POST   /listings/{id}/panos              multipart: pano (dosya), title (oda adı)
 *   PATCH  /listings/{id}/panos/{pid}        {"title": "Salon"}
 *   PUT    /listings/{id}/panos/order        {"order": [3,1,2]}
 *   DELETE /listings/{id}/panos/{pid}
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_PANO_MAX = 15;
const EC_PANO_WIDTH = 6000;

/** Oran 2:1'e yakın mı? (küçük sapmaları kabul et) */
function ec_is_pano_size(int $w, int $h): bool
{
    return $h > 0 && abs($w / $h - 2) < 0.06 && $w >= 1600;
}

function ec_pano_present(array $p): array
{
    $b = ec_photo_base()['url'] . '/' . $p['path'];
    return [
        'id' => (int) $p['id'], 'title' => (string) $p['title'],
        'url' => $b . '-p.webp', 'thumb' => $b . '-t.webp',
        'width' => (int) $p['width'], 'height' => (int) $p['height'],
    ];
}

function ec_panos(int $listing_id): array
{
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('panos') . ' WHERE listing_id = %d ORDER BY sort, id', $listing_id), ARRAY_A);
    return array_map('ec_pano_present', $rows ?: []);
}

/** Birden çok ilan için 360° sayısı (kartlarda rozet) */
function ec_pano_counts(array $ids): array
{
    global $wpdb;
    $ids = array_filter(array_map('intval', $ids));
    if (!$ids) {
        return [];
    }
    $out = [];
    foreach ($wpdb->get_results('SELECT listing_id, COUNT(*) n FROM ' . ec_table('panos') . ' WHERE listing_id IN (' . implode(',', $ids) . ') GROUP BY listing_id', ARRAY_A) as $r) {
        $out[(int) $r['listing_id']] = (int) $r['n'];
    }
    return $out;
}

/** @return array|WP_Error eklenen 360° fotoğraf */
function ec_pano_add(int $listing_id, string $file, string $title = '')
{
    global $wpdb;
    $t = ec_table('panos');
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE listing_id = %d", $listing_id));
    if ($count >= EC_PANO_MAX) {
        return new WP_Error('ec_pano_limit', 'Bir ilana en çok ' . EC_PANO_MAX . ' adet 360° fotoğraf eklenebilir', ['status' => 400]);
    }
    $info = @getimagesize($file);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return new WP_Error('ec_photo_type', 'Sadece JPG, PNG veya WebP yüklenebilir', ['status' => 400]);
    }
    if (!ec_is_pano_size((int) $info[0], (int) $info[1])) {
        return new WP_Error('ec_pano_ratio', sprintf('Bu bir 360° fotoğraf değil: genişlik yüksekliğin 2 katı olmalı (bu fotoğraf %d×%d)', $info[0], $info[1]), ['status' => 400]);
    }
    $rel = $listing_id . '/360-' . strtolower(wp_generate_password(10, false, false));
    $base = ec_photo_base()['dir'];
    wp_mkdir_p(dirname($base . '/' . $rel));
    $size = null;
    foreach (['p' => [EC_PANO_WIDTH, 80], 't' => [640, 72]] as $sfx => [$max, $q]) {
        $ed = wp_get_image_editor($file);
        if (is_wp_error($ed)) {
            return new WP_Error('ec_pano_mem', 'Fotoğraf işlenemedi (sunucu belleği yetmiyor olabilir; PHP memory_limit 256M olmalı)', ['status' => 500]);
        }
        $ed->set_quality($q);
        $s = $ed->get_size();
        if ($s['width'] > $max) {
            $ed->resize($max, (int) round($max / 2), false);
        }
        $saved = $ed->save($base . '/' . $rel . '-' . $sfx . '.webp', 'image/webp');
        if (is_wp_error($saved)) {
            return $saved;
        }
        if ($sfx === 'p') {
            $size = [(int) $saved['width'], (int) $saved['height']];
        }
        unset($ed);
    }
    $wpdb->insert($t, [
        'listing_id' => $listing_id, 'title' => mb_substr(ec_clean_text($title), 0, 60) ?: null,
        'path' => $rel, 'width' => $size[0], 'height' => $size[1], 'sort' => $count, 'created_at' => ec_now(),
    ]);
    do_action('ec_listing_changed', $listing_id);
    return ec_pano_present($wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $wpdb->insert_id), ARRAY_A));
}

function ec_pano_delete(int $listing_id, int $pid): bool
{
    global $wpdb;
    $t = ec_table('panos');
    $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d AND listing_id = %d", $pid, $listing_id), ARRAY_A);
    if (!$p) {
        return false;
    }
    foreach (['p', 't'] as $s) {
        @unlink(ec_photo_base()['dir'] . '/' . $p['path'] . '-' . $s . '.webp');
    }
    @unlink(ec_photo_base()['dir'] . '/' . $p['path'] . '-fb.jpg');
    $wpdb->delete($t, ['id' => $pid]);
    return true;
}

/** Facebook 360° gönderi için JPEG (Facebook WebP 360 kabul etmez) */
function ec_pano_jpeg(array $pano): ?string
{
    $rel = substr($pano['url'], strlen(ec_photo_base()['url']) + 1, -strlen('-p.webp'));
    $dst = ec_photo_base()['dir'] . '/' . $rel . '-fb.jpg';
    if (!file_exists($dst)) {
        $ed = wp_get_image_editor(ec_photo_base()['dir'] . '/' . $rel . '-p.webp');
        if (is_wp_error($ed)) {
            return null;
        }
        $ed->set_quality(85);
        if (is_wp_error($ed->save($dst, 'image/jpeg'))) {
            return null;
        }
    }
    return ec_photo_base()['url'] . '/' . $rel . '-fb.jpg';
}

/** Instagram 360 desteklemez: fotoğrafın ortasından düz bir kesit (16:10) */
function ec_pano_flat(array $pano): ?string
{
    $rel = substr($pano['url'], strlen(ec_photo_base()['url']) + 1, -strlen('-p.webp'));
    $dst = ec_photo_base()['dir'] . '/' . $rel . '-ig.jpg';
    if (!file_exists($dst)) {
        $src = ec_photo_base()['dir'] . '/' . $rel . '-p.webp';
        $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null;
        if (!$im) {
            return null;
        }
        $h = imagesy($im);
        $cw = (int) round($h * 1.6 * 0.55);
        $ch = (int) round($cw / 1.6);
        $out = imagecreatetruecolor(1440, 900);
        imagecopyresampled($out, $im, 0, 0, (int) ((imagesx($im) - $cw) / 2), (int) (($h - $ch) / 2), 1440, 900, $cw, $ch);
        imagejpeg($out, $dst, 86);
        imagedestroy($im);
        imagedestroy($out);
    }
    return ec_photo_base()['url'] . '/' . $rel . '-ig.jpg';
}

/** Gömülebilen sanal tur siteleri (iframe) */
function ec_tour_embed(?string $url): ?string
{
    if (!$url) {
        return null;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $ok = ['my.matterport.com', 'matterport.com', 'kuula.co', 'momento360.com', 'cloudpano.com', 'app.cloudpano.com', 'teliportme.com', 'tourmkr.com', 'roundme.com', 'vr.teliportme.com', 'panoee.com'];
    foreach ($ok as $d) {
        if ($host === $d || str_ends_with($host, '.' . $d)) {
            if ($d === 'kuula.co' && str_contains($url, '/share/')) {
                return str_replace('/share/', '/share/', $url) . (str_contains($url, '?') ? '&' : '?') . 'fs=1&vr=1&thumbs=1&chromeless=0&logo=0';
            }
            return $url;
        }
    }
    return null;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/panos', [
        [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => fn(WP_REST_Request $r) => ec_panos((int) $r['id']),
        ],
        [
            'methods' => 'POST',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => function (WP_REST_Request $r) {
                $f = ec_rest_file($r, 'pano');
                if (!$f) {
                    $max = size_format(wp_max_upload_size());
                    return new WP_Error('ec_no_file', "Dosya yüklenemedi. Dosya çok büyük olabilir (sunucu sınırı: $max).", ['status' => 400]);
                }
                $res = ec_pano_add((int) $r['id'], $f, (string) $r->get_param('title'));
                return is_wp_error($res) ? $res : new WP_REST_Response($res, 201);
            },
        ],
    ]);
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/panos/order', [
        'methods' => 'PUT',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            foreach (array_values((array) ($r->get_json_params()['order'] ?? [])) as $i => $pid) {
                $wpdb->update(ec_table('panos'), ['sort' => $i], ['id' => (int) $pid, 'listing_id' => (int) $r['id']]);
            }
            return ec_panos((int) $r['id']);
        },
    ]);
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/panos/(?P<pid>\d+)', [
        [
            'methods' => 'PATCH',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => function (WP_REST_Request $r) {
                global $wpdb;
                $wpdb->update(ec_table('panos'), ['title' => mb_substr(ec_clean_text((string) ($r->get_json_params()['title'] ?? '')), 0, 60) ?: null], ['id' => (int) $r['pid'], 'listing_id' => (int) $r['id']]);
                return ['ok' => true];
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => fn(WP_REST_Request $r) => ec_pano_delete((int) $r['id'], (int) $r['pid']) ? ['ok' => true] : new WP_Error('ec_not_found', 'Bulunamadı', ['status' => 404]),
        ],
    ]);
});
