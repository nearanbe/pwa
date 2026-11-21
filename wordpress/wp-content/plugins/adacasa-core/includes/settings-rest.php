<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Şehir kartı fotoğrafları ve ana sayfa giriş görseli REST ayarları olarak da açık (1.0.3):
 * yönetici yetkisiyle /wp-json/wp/v2/settings üzerinden okunup yazılabilir (ör. uygulama parolasıyla toplu kurulum).
 */
add_action('init', function () {
    $cityProps = [];
    foreach (array_keys(ec_cities()) as $ck) {
        $cityProps[$ck] = ['type' => ['integer', 'string']];
    }
    register_setting('ec', 'ec_city_photos', [
        'type' => 'object',
        'default' => [],
        'show_in_rest' => ['name' => 'ec_city_photos', 'schema' => ['type' => 'object', 'properties' => $cityProps, 'additionalProperties' => false]],
        'sanitize_callback' => function ($v) {
            $out = [];
            foreach ((array) $v as $ck => $x) {
                $x = trim((string) $x);
                if (isset(ec_cities()[$ck]) && $x !== '') {
                    $out[$ck] = ctype_digit($x) ? (int) $x : esc_url_raw($x);
                }
            }
            return $out;
        },
    ]);
    register_setting('ec', 'ec_hero_photo', [
        'type' => 'string',
        'default' => '',
        'show_in_rest' => ['name' => 'ec_hero_photo'],
        'sanitize_callback' => fn($v) => ($v = trim((string) $v)) === '' ? '' : (ctype_digit($v) ? $v : esc_url_raw($v)),
    ]);
});
