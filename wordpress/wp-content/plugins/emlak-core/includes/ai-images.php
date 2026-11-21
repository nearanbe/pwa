<?php
/**
 * Cloudflare Workers AI ile TEMSİLİ görsel üretimi (blog, slider, fotoğrafı olmayan projeler).
 * İlan fotoğrafı olarak kullanılmaz: alıcıyı yanıltır. Projede kullanılırsa "Temsili görsel" etiketi gösterilir.
 * Model: @cf/black-forest-labs/flux-1-schnell (hızlı, düşük maliyetli). Ayarlar: Emlak → Ayarlar → Cloudflare.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_CF_MODEL = '@cf/black-forest-labs/flux-1-schnell';

/** @return string|WP_Error üretilen JPEG'in geçici dosya yolu */
function ec_cf_generate_image(string $prompt)
{
    $cf = (array) get_option('ec_cloudflare', []);
    if (empty($cf['account']) || empty($cf['token'])) {
        return new WP_Error('ec_cf', 'Cloudflare Account ID ve API Token ayarlanmamış (Emlak → Ayarlar).');
    }
    $prompt = trim(wp_strip_all_tags($prompt));
    if (mb_strlen($prompt) < 5) {
        return new WP_Error('ec_cf', 'Görsel tarifi çok kısa.');
    }
    $res = wp_remote_post('https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($cf['account']) . '/ai/run/' . EC_CF_MODEL, [
        'timeout' => 60,
        'headers' => ['Authorization' => 'Bearer ' . $cf['token'], 'Content-Type' => 'application/json'],
        'body' => wp_json_encode(['prompt' => mb_substr($prompt, 0, 2000), 'steps' => 6]),
    ]);
    if (is_wp_error($res)) {
        return $res;
    }
    $code = wp_remote_retrieve_response_code($res);
    $json = json_decode(wp_remote_retrieve_body($res), true);
    $b64 = $json['result']['image'] ?? null;
    if ($code !== 200 || !$b64) {
        $msg = $json['errors'][0]['message'] ?? ('HTTP ' . $code);
        return new WP_Error('ec_cf', 'Cloudflare görsel üretemedi: ' . $msg);
    }
    $bin = base64_decode($b64, true);
    if (!$bin || !@imagecreatefromstring($bin)) {
        return new WP_Error('ec_cf', 'Cloudflare geçersiz bir görsel döndürdü.');
    }
    $file = wp_tempnam('ec-ai') ;
    file_put_contents($file, $bin);
    return $file;
}

/** Üretilen görseli medya kütüphanesine ekler. @return int|WP_Error ek (attachment) numarası */
function ec_cf_to_media(string $file, string $title, int $parent = 0)
{
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $tmp = ['name' => sanitize_file_name(ec_slugify($title) . '-temsili.jpg'), 'tmp_name' => $file];
    $id = media_handle_sideload($tmp, $parent, $title . ' (temsili görsel)');
    if (!is_wp_error($id)) {
        update_post_meta($id, '_ec_ai_image', 1);
        wp_update_post(['ID' => $id, 'post_excerpt' => 'Temsili görsel (yapay zekâ ile üretildi)']);
    }
    return $id;
}
