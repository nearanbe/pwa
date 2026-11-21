<?php
/**
 * İlan videosu (1.0.4): bağlantı (YouTube, Vimeo, .mp4 adresi) ya da dosya yükleme (MP4, WebM, MOV).
 * Tek alan: wp_ec_listings.video_url. Yüklenen dosya ilanın fotoğraf klasörüne (ilan-foto/{id}/video-xxxx.mp4) kaydedilir
 * ve video_url o dosyanın adresi olur; yeni yükleme ya da silme eski dosyayı kaldırır.
 *   POST   /api/v1/listings/{id}/video   (alan: video)  → {url, info}
 *   DELETE /api/v1/listings/{id}/video                 → video_url boşaltılır, yüklenmiş dosya silinir
 * Dönüştürme yapılmaz (sunucuda ffmpeg gerekmez); en büyük boyut sunucunun yükleme sınırıdır (upload_max_filesize).
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_VIDEO_TYPES = ['mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];

/** Video adresinin türü: youtube | vimeo | file | link. ['type' => .., 'id' => .., 'src' => oynatılacak adres, 'url' => özgün] */
function ec_video_info(?string $url): ?array
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return ['type' => 'youtube', 'id' => $m[1], 'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&playsinline=1', 'thumb' => 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg', 'url' => $url];
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d{6,12})~', $url, $m)) {
        return ['type' => 'vimeo', 'id' => $m[1], 'src' => 'https://player.vimeo.com/video/' . $m[1] . '?dnt=1', 'thumb' => '', 'url' => $url];
    }
    $path = strtolower((string) parse_url($url, PHP_URL_PATH));
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (isset(EC_VIDEO_TYPES[$ext])) {
        return ['type' => 'file', 'id' => '', 'src' => $url, 'mime' => EC_VIDEO_TYPES[$ext] === 'video/quicktime' ? 'video/mp4' : EC_VIDEO_TYPES[$ext], 'thumb' => '', 'url' => $url];
    }
    return ['type' => 'link', 'id' => '', 'src' => $url, 'thumb' => '', 'url' => $url];
}

/** Bu ilana yüklenmiş (sitedeki) video dosyasının yolu, yoksa null */
function ec_video_local_path(int $id, ?string $url): ?string
{
    $base = ec_photo_base();
    $prefix = rtrim($base['url'], '/') . '/' . $id . '/video-';
    if (!$url || !str_starts_with($url, $prefix)) {
        return null;
    }
    $file = $base['dir'] . '/' . $id . '/' . basename((string) parse_url($url, PHP_URL_PATH));
    return is_file($file) ? $file : null;
}

/** İlanın yüklenmiş video dosyasını siler (ilan silinirken de çağrılır) */
function ec_video_cleanup(int $id): void
{
    foreach ((array) glob(ec_photo_base()['dir'] . '/' . $id . '/video-*') as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
}

function ec_video_set(int $id, ?string $url): void
{
    global $wpdb;
    $wpdb->update(ec_table('listings'), ['video_url' => $url, 'updated_at' => ec_now()], ['id' => $id]);
    do_action('ec_listing_changed', $id);
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/video', [
        [
            'methods' => 'POST',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => function (WP_REST_Request $r) {
                $id = (int) $r['id'];
                $row = ec_listing_row($id);
                if (!$row) {
                    return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
                }
                $f = $r->get_file_params()['video'] ?? null;
                if (!$f || is_array($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                    $max = size_format(wp_max_upload_size());
                    return new WP_Error('ec_no_file', "Video yüklenemedi. Dosya çok büyük olabilir (sunucu sınırı: $max).", ['status' => 400]);
                }
                $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
                $real = function_exists('mime_content_type') ? (string) @mime_content_type($f['tmp_name']) : '';
                if (!isset(EC_VIDEO_TYPES[$ext]) || ($real !== '' && !str_starts_with($real, 'video/') && $real !== 'application/octet-stream')) {
                    return new WP_Error('ec_bad_type', 'Sadece MP4, WebM ya da MOV video yüklenebilir.', ['status' => 400]);
                }
                $dir = ec_photo_base()['dir'] . '/' . $id;
                wp_mkdir_p($dir);
                $name = 'video-' . strtolower(wp_generate_password(10, false, false)) . '.' . ($ext === 'm4v' ? 'mp4' : $ext);
                if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
                    return new WP_Error('ec_io', 'Video kaydedilemedi', ['status' => 500]);
                }
                $old = ec_video_local_path($id, $row['video_url']);
                if ($old) {
                    @unlink($old);
                }
                $url = rtrim(ec_photo_base()['url'], '/') . '/' . $id . '/' . $name;
                ec_video_set($id, $url);
                return new WP_REST_Response(['url' => $url, 'info' => ec_video_info($url)], 201);
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => 'ec_rest_own_listing',
            'callback' => function (WP_REST_Request $r) {
                $id = (int) $r['id'];
                $row = ec_listing_row($id);
                if (!$row) {
                    return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
                }
                $old = ec_video_local_path($id, $row['video_url']);
                if ($old) {
                    @unlink($old);
                }
                ec_video_set($id, null);
                return ['ok' => true];
            },
        ],
    ]);
});

/* ---------------- Proje videosu (1.1): aynı kurallar, klasör ilan-foto/proje-{id} ---------------- */

function ec_project_video_local_path(int $id, ?string $url): ?string
{
    $base = ec_photo_base();
    $prefix = rtrim($base['url'], '/') . '/proje-' . $id . '/video-';
    if (!$url || !str_starts_with($url, $prefix)) {
        return null;
    }
    $file = $base['dir'] . '/proje-' . $id . '/' . basename((string) parse_url($url, PHP_URL_PATH));
    return is_file($file) ? $file : null;
}

function ec_project_video_set(int $id, ?string $url): void
{
    global $wpdb;
    $wpdb->update(ec_table('projects'), ['video_url' => $url, 'updated_at' => ec_now()], ['id' => $id]);
    do_action('ec_project_changed', $id);
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/projects/(?P<id>\d+)/video', [
        [
            'methods' => 'POST',
            'permission_callback' => 'ec_rest_own_project',
            'callback' => function (WP_REST_Request $r) {
                $id = (int) $r['id'];
                $row = ec_project_row($id);
                $f = $r->get_file_params()['video'] ?? null;
                if (!$row) {
                    return new WP_Error('ec_not_found', 'Proje bulunamadı', ['status' => 404]);
                }
                if (!$f || is_array($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                    return new WP_Error('ec_no_file', 'Video yüklenemedi. Dosya çok büyük olabilir (sunucu sınırı: ' . size_format(wp_max_upload_size()) . ').', ['status' => 400]);
                }
                $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
                $real = function_exists('mime_content_type') ? (string) @mime_content_type($f['tmp_name']) : '';
                if (!isset(EC_VIDEO_TYPES[$ext]) || ($real !== '' && !str_starts_with($real, 'video/') && $real !== 'application/octet-stream')) {
                    return new WP_Error('ec_bad_type', 'Sadece MP4, WebM ya da MOV video yüklenebilir.', ['status' => 400]);
                }
                $dir = ec_photo_base()['dir'] . '/proje-' . $id;
                wp_mkdir_p($dir);
                $name = 'video-' . strtolower(wp_generate_password(10, false, false)) . '.' . ($ext === 'm4v' ? 'mp4' : $ext);
                if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
                    return new WP_Error('ec_io', 'Video kaydedilemedi', ['status' => 500]);
                }
                $old = ec_project_video_local_path($id, $row['video_url']);
                if ($old) {
                    @unlink($old);
                }
                $url = rtrim(ec_photo_base()['url'], '/') . '/proje-' . $id . '/' . $name;
                ec_project_video_set($id, $url);
                return new WP_REST_Response(['url' => $url, 'info' => ec_video_info($url)], 201);
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => 'ec_rest_own_project',
            'callback' => function (WP_REST_Request $r) {
                $id = (int) $r['id'];
                $row = ec_project_row($id);
                if ($row && ($old = ec_project_video_local_path($id, $row['video_url']))) {
                    @unlink($old);
                }
                ec_project_video_set($id, null);
                return ['ok' => true];
            },
        ],
    ]);
});
