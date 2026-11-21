<?php
/**
 * Kalıcı link yapısı (Next.js'te birebir aynısı kurulacak, yönlendirme gerekmeyecek):
 *   /tr/                                   ana sayfa (en, ru aynı)
 *   /tr/satilik/  /tr/satilik/girne/       arama (kiralik, gunluk-kiralik)
 *   /tr/ilan/{slug}-{no}/                  ilan detayı  (en/ru: /property/)
 *   /tr/projeler/  /tr/proje/{slug}-{no}/  /tr/fiyat-endeksi/  /tr/ilan-ver/  /tr/favoriler/
 *   /api/v1/...                            REST API (= /wp-json/emlak/v1/...)
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_register_rewrites(): void
{
    $s = ec_sections();
    foreach (EC_LANGS as $l) {
        add_rewrite_rule("^$l/?$", "index.php?ec_lang=$l&ec_page=home", 'top');
        add_rewrite_rule("^$l/{$s['listing'][$l]}/([a-z0-9-]+)-([0-9]+)/?$", 'index.php?ec_lang=' . $l . '&ec_page=listing&ec_id=$matches[2]&ec_slug=$matches[1]', 'top');
        foreach (ec_modes() as $m) {
            add_rewrite_rule("^$l/{$s[$m][$l]}/?$", "index.php?ec_lang=$l&ec_page=search&ec_mode=$m", 'top');
            add_rewrite_rule("^$l/{$s[$m][$l]}/([a-z]+)/?$", 'index.php?ec_lang=' . $l . '&ec_page=search&ec_mode=' . $m . '&ec_city=$matches[1]', 'top');
        }
        add_rewrite_rule("^$l/{$s['projectd'][$l]}/([a-z0-9-]+)-([0-9]+)/?$", 'index.php?ec_lang=' . $l . '&ec_page=projectd&ec_id=$matches[2]', 'top');
        foreach (['project', 'add', 'favs', 'index'] as $p) {
            add_rewrite_rule("^$l/{$s[$p][$l]}/?$", "index.php?ec_lang=$l&ec_page=$p", 'top');
        }
    }
    add_rewrite_rule('^api/v1/?(.*)?$', 'index.php?rest_route=/emlak/v1/$matches[1]', 'top');
}

add_action('init', function () {
    ec_register_rewrites();
    if (get_option('ec_flush_rewrites')) {
        delete_option('ec_flush_rewrites');
        flush_rewrite_rules();
    }
});

add_filter('query_vars', function ($v) {
    return array_merge($v, ['ec_lang', 'ec_page', 'ec_id', 'ec_slug', 'ec_mode', 'ec_city']);
});

/** Geçerli dil */
function ec_lang(): string
{
    $l = get_query_var('ec_lang');
    return in_array($l, EC_LANGS, true) ? $l : EC_DEFAULT_LANG;
}

function ec_page(): string
{
    return (string) get_query_var('ec_page');
}

function ec_url_home(?string $lang = null): string
{
    return home_url('/' . ($lang ?? ec_lang()) . '/');
}

function ec_url_section(string $section, ?string $lang = null, ?string $city = null, array $query = []): string
{
    $lang = $lang ?? ec_lang();
    $slug = ec_sections()[$section][$lang] ?? $section;
    $u = home_url('/' . $lang . '/' . $slug . '/' . ($city ? $city . '/' : ''));
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    return $query ? add_query_arg($query, $u) : $u;
}

function ec_url_listing(int $id, string $slug, ?string $lang = null): string
{
    $lang = $lang ?? ec_lang();
    return home_url('/' . $lang . '/' . ec_sections()['listing'][$lang] . '/' . $slug . '-' . $id . '/');
}

function ec_url_project(int $id, string $slug, ?string $lang = null): string
{
    $lang = $lang ?? ec_lang();
    return home_url('/' . $lang . '/' . ec_sections()['projectd'][$lang] . '/' . $slug . '-' . $id . '/');
}

/**
 * Kök adres (/) ziyaretçinin tarayıcı diline göre /tr/, /en/ veya /ru/ adresine gider.
 * Yanlış slug ile gelen ilan linkleri doğru adrese 301 ile yönlendirilir.
 */
add_action('template_redirect', function () {
    $page = ec_page();
    if (!$page && (is_front_page() || is_home()) && !is_paged() && trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') === trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/')) {
        $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $lang = str_starts_with($accept, 'ru') ? 'ru' : (str_starts_with($accept, 'tr') || $accept === '' ? 'tr' : 'en');
        wp_safe_redirect(ec_url_home($lang), 302);
        exit;
    }
    if ($page === 'listing') {
        $id = (int) get_query_var('ec_id');
        $item = ec_listing_get($id, ec_lang());
        if (!$item) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        if (untrailingslashit($item['url']) !== untrailingslashit(home_url(strtok($_SERVER['REQUEST_URI'], '?')))) {
            wp_safe_redirect($item['url'], 301);
            exit;
        }
        $GLOBALS['ec_current_listing'] = $item;
    }
    if ($page === 'projectd') {
        $p = ec_project_get((int) get_query_var('ec_id'), ec_lang());
        if (!$p) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        if (untrailingslashit($p['url']) !== untrailingslashit(home_url(strtok($_SERVER['REQUEST_URI'], '?')))) {
            wp_safe_redirect($p['url'], 301);
            exit;
        }
        $GLOBALS['ec_current_project'] = $p;
    }
    if ($page === 'search') {
        $city = get_query_var('ec_city');
        if ($city && !isset(ec_cities()[$city])) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
        }
    }
});

/** Bizim sayfalarımızda WordPress'in kendi 404/canonical davranışını kapat */
add_filter('redirect_canonical', function ($url) {
    return ec_page() ? false : $url;
});

add_filter('pre_handle_404', function ($handled, $q) {
    return ec_page() ? true : $handled;
}, 10, 2);
