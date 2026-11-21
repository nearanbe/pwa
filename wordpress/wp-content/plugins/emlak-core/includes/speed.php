<?php
/**
 * Hız (0.5): sayfa önbelleği başlıkları, Cloudflare önbellek temizleme, gereksiz WordPress çıktılarını kapatma.
 *
 * Önbellek açıkken ziyaretçi sayfaları (giriş yapmamış kullanıcı) şu başlıkla gider:
 *   Cache-Control: public, max-age=0, s-maxage=600   → Cloudflare kenar sunucusunda 10 dk
 *   X-Accel-Expires: 120                              → sunucudaki nginx önbelleğinde 2 dk
 * Panel, giriş, favoriler, önizleme gibi kişisel sayfalar "private, no-store" gider ve hiç önbelleğe alınmaz.
 * İlan eklenince / değişince ilgili adresler Cloudflare'den anında silinir (API anahtarı girildiyse).
 * Seçilen para birimi ve favoriler önbellekli sayfada tarayıcıda uygulanır (sayfa herkese aynı gider).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_speed_settings(): array
{
    return array_merge(['cache' => 0, 'edge_ttl' => 600, 'origin_ttl' => 120, 'cf_zone' => '', 'cf_token' => ''], (array) get_option('ec_speed', []));
}

function ec_page_cache_on(): bool
{
    return (bool) ec_speed_settings()['cache'];
}

/** Bu istek herkese aynı gidebilir mi? */
function ec_request_cacheable(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || is_user_logged_in() || is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }
    if (!empty($GLOBALS['ec_preview']) || is_404() || is_search() || is_preview()) {
        return false;
    }
    $page = function_exists('ec_page') ? ec_page() : '';
    if (in_array($page, ['panel', 'review', 'login', 'register', 'verify', 'favs', 'add'], true)) {
        return false;
    }
    // Kök adres dile göre yönlendirir; yönlendirme önbelleğe alınmamalı
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($path === '' && !$page) {
        return false;
    }
    foreach (array_keys($_COOKIE) as $c) {
        if (preg_match('/^(wordpress_logged_in|wp-postpass|comment_author|woocommerce_)/', $c)) {
            return false;
        }
    }
    return true;
}

add_action('template_redirect', function () {
    if (!ec_page_cache_on() || headers_sent()) {
        return;
    }
    $s = ec_speed_settings();
    if (ec_request_cacheable()) {
        header_remove('Pragma');
        header_remove('Expires');
        header('Cache-Control: public, max-age=0, s-maxage=' . (int) $s['edge_ttl'] . ', stale-while-revalidate=60');
        header('X-Accel-Expires: ' . (int) $s['origin_ttl']);
        header('X-EC-Cache: public');
    } else {
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Accel-Expires: 0');
        header('X-EC-Cache: bypass');
    }
}, 99);

/* ---------------- Cloudflare önbellek temizleme ---------------- */

function ec_cf_purge_add(array $urls): void
{
    if (!ec_speed_settings()['cf_token']) {
        return;
    }
    $GLOBALS['ec_cf_purge'] = array_unique(array_merge($GLOBALS['ec_cf_purge'] ?? [], array_filter($urls)));
}

/** @return true|WP_Error */
function ec_cf_purge(?array $urls = null)
{
    $s = ec_speed_settings();
    $tok = function_exists('ec_decrypt') ? ec_decrypt($s['cf_token']) : '';
    if (!$tok || !$s['cf_zone']) {
        return new WP_Error('ec_cf', 'Cloudflare bilgileri girilmemiş');
    }
    $chunks = $urls === null ? [null] : array_chunk(array_values($urls), 30);
    foreach ($chunks as $chunk) {
        $res = wp_remote_post('https://api.cloudflare.com/client/v4/zones/' . rawurlencode($s['cf_zone']) . '/purge_cache', [
            'timeout' => 10,
            'headers' => ['Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json'],
            'body' => wp_json_encode($chunk === null ? ['purge_everything' => true] : ['files' => $chunk]),
        ]);
        if (is_wp_error($res)) {
            return $res;
        }
        $j = json_decode((string) wp_remote_retrieve_body($res), true);
        if (empty($j['success'])) {
            return new WP_Error('ec_cf', 'Cloudflare: ' . ($j['errors'][0]['message'] ?? 'HTTP ' . wp_remote_retrieve_response_code($res)));
        }
    }
    return true;
}

add_action('shutdown', function () {
    if (!empty($GLOBALS['ec_cf_purge'])) {
        $u = $GLOBALS['ec_cf_purge'];
        $GLOBALS['ec_cf_purge'] = [];
        ec_cf_purge($u);
    }
}, 5);

/** İlan değişince silinecek adresler: ilanın 3 dildeki sayfası, ana sayfalar, ilgili liste sayfaları, ofis vitrini */
function ec_cf_listing_urls(int $id): array
{
    $row = ec_listing_row($id);
    if (!$row) {
        return [];
    }
    $u = [];
    foreach (EC_LANGS as $l) {
        $it = ec_listings_present([$row], $l, true)[0];
        $u[] = $it['url'];
        $u[] = ec_url_home($l);
        $u[] = ec_url_section($row['mode'], $l);
        $u[] = ec_url_section($row['mode'], $l, $row['city']);
        if (!empty($it['agent']['url'])) {
            $u[] = $it['agent']['url'];
        }
    }
    return $u;
}

add_action('ec_listing_published', fn($id) => ec_cf_purge_add(ec_cf_listing_urls((int) $id)));
add_action('ec_listing_changed', fn($id) => ec_cf_purge_add(ec_cf_listing_urls((int) $id)));
add_action('ec_project_changed', function ($id) {
    $p = ec_project_row((int) $id);
    if ($p) {
        foreach (EC_LANGS as $l) {
            ec_cf_purge_add([ec_project_present($p, $l)['url'], ec_url_section('project', $l), ec_url_home($l)]);
        }
    }
});
/** Kur değişince fiyatlar değişir: günde bir kez tüm önbellek */
add_action('update_option_ec_rates', function () {
    if (ec_speed_settings()['cf_token'] && !get_transient('ec_cf_rates_purged')) {
        set_transient('ec_cf_rates_purged', 1, 12 * HOUR_IN_SECONDS);
        ec_cf_purge();
    }
});

/* ---------------- Gereksiz çıktılar ---------------- */

add_action('init', function () {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('template_redirect', 'rest_output_link_header', 11);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'wp_resource_hints', 2);
});
add_action('wp_enqueue_scripts', function () {
    if (!is_user_logged_in()) {
        wp_dequeue_style('dashicons');
    }
    wp_dequeue_script('wp-embed');
}, 100);
/** Ön yüzde Heartbeat'i kapat (sunucuya her dakika istek atmasın) */
add_action('init', function () {
    if (!is_admin()) {
        wp_deregister_script('heartbeat');
    }
}, 1);

/* ---------------- Yönetim: Emlak → Hız ---------------- */

add_action('admin_menu', function () {
    add_submenu_page('ec-listings', 'Hız', 'Hız', 'manage_options', 'ec-speed', 'ec_admin_speed');
}, 16);

function ec_admin_speed(): void
{
    $s = ec_speed_settings();
    echo '<div class="wrap"><h1>Hız</h1>';
    if (function_exists('ec_admin_notice')) {
        ec_admin_notice();
    }
    echo '<p>Tarif: <code>docs/hiz.md</code>. Kısaca: 1) Plesk’te PHP 8.3 + OPcache + nginx önbelleği, 2) Cloudflare’de bir önbellek kuralı, 3) aşağıdaki kutuyu açmak.</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">' . wp_nonce_field('ec_speed', '_wpnonce', true, false) . '<input type="hidden" name="action" value="ec_speed"><table class="form-table">';
    echo '<tr><th>Sayfa önbelleği</th><td><label><input type="checkbox" name="cache" value="1"' . checked((int) $s['cache'], 1, false) . '> Ziyaretçi sayfaları önbelleğe alınabilsin</label><p class="description">Açınca ziyaretçi sayfaları herkese aynı gider; seçilen para birimi ve favoriler tarayıcıda uygulanır. Panel, giriş ve favoriler hiçbir zaman önbelleğe alınmaz. Cloudflare kuralı ve nginx ayarı olmadan açmanın zararı yoktur.</p></td></tr>';
    echo '<tr><th>Cloudflare’de saklama</th><td><input type="number" name="edge_ttl" min="60" max="86400" value="' . (int) $s['edge_ttl'] . '"> sn · sunucuda (nginx) <input type="number" name="origin_ttl" min="0" max="3600" value="' . (int) $s['origin_ttl'] . '"> sn</td></tr>';
    echo '<tr><th>Cloudflare Zone ID</th><td><input name="cf_zone" class="regular-text" value="' . esc_attr($s['cf_zone']) . '"><p class="description">Cloudflare → alan adı → Genel Bakış (Overview) → sağ altta <b>Zone ID</b>.</p></td></tr>';
    echo '<tr><th>Cloudflare API anahtarı</th><td><input name="cf_token" type="password" class="regular-text" autocomplete="new-password" placeholder="' . ($s['cf_token'] ? '•••••••• (kayıtlı)' : '') . '"><p class="description">My Profile → API Tokens → Create Token → “Custom token”: izin <b>Zone · Cache Purge · Purge</b>, kaynak: bu alan adı. İlan eklenince ilgili sayfalar anında yenilenir.</p></td></tr>';
    echo '</table>';
    submit_button('Kaydet');
    echo '</form>';
    $u = wp_nonce_url(admin_url('admin-post.php?action=ec_speed_purge'), 'ec_speed_purge');
    echo '<p><a class="button" href="' . esc_url($u) . '">Cloudflare önbelleğini şimdi tamamen temizle</a></p>';
    // Basit ölçüm: sunucunun ana sayfayı üretme süresi
    $t = microtime(true);
    $r = wp_remote_get(ec_url_home('tr'), ['timeout' => 15, 'headers' => ['Cache-Control' => 'no-cache']]);
    $ms = (int) round((microtime(true) - $t) * 1000);
    if (!is_wp_error($r)) {
        $h = wp_remote_retrieve_headers($r);
        echo '<h2>Ölçüm</h2><p>Ana sayfa (sunucudan, önbelleksiz): <b>' . $ms . ' ms</b>. Hedef: 300 ms altı. '
            . 'Gelen başlık: <code>' . esc_html((string) ($h['cache-control'] ?? '—')) . '</code>'
            . (isset($h['cf-cache-status']) ? ' · Cloudflare: <code>' . esc_html($h['cf-cache-status']) . '</code>' : '') . '</p>';
        echo '<p>OPcache: <b>' . (function_exists('opcache_get_status') && @opcache_get_status(false)['opcache_enabled'] ? 'açık ✅' : 'kapalı ⛔ (Plesk → PHP ayarları → opcache.enable = on)') . '</b> · PHP ' . esc_html(PHP_VERSION)
            . ' · Kalıcı nesne önbelleği (Redis): <b>' . (wp_using_ext_object_cache() ? 'açık ✅' : 'yok (isteğe bağlı)') . '</b></p>';
    }
    echo '</div>';
}

add_action('admin_post_ec_speed', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_speed')) {
        wp_die('Yetkisiz');
    }
    $p = wp_unslash($_POST);
    $s = ec_speed_settings();
    update_option('ec_speed', [
        'cache' => !empty($p['cache']) ? 1 : 0,
        'edge_ttl' => max(60, min(86400, (int) $p['edge_ttl'])),
        'origin_ttl' => max(0, min(3600, (int) $p['origin_ttl'])),
        'cf_zone' => preg_replace('/[^a-f0-9]/', '', strtolower((string) $p['cf_zone'])),
        'cf_token' => trim((string) $p['cf_token']) !== '' ? ec_encrypt(trim((string) $p['cf_token'])) : $s['cf_token'],
    ]);
    $msg = 'Kaydedildi.';
    if (trim((string) $p['cf_token']) !== '') {
        $r = ec_cf_purge([ec_url_home('tr')]);
        $msg .= is_wp_error($r) ? ' Cloudflare denemesi başarısız: ' . esc_html($r->get_error_message()) : ' Cloudflare bağlantısı çalışıyor ✅';
    }
    ec_admin_back('ec-speed', $msg);
});

add_action('admin_post_ec_speed_purge', function () {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_speed_purge')) {
        wp_die('Yetkisiz');
    }
    $r = ec_cf_purge();
    ec_admin_back('ec-speed', is_wp_error($r) ? esc_html($r->get_error_message()) : 'Cloudflare önbelleği temizlendi.');
});
