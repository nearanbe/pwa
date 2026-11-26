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
        add_rewrite_rule("^$l/{$s['office'][$l]}/([a-z0-9-]+)/?$", 'index.php?ec_lang=' . $l . '&ec_page=office&ec_slug=$matches[1]', 'top');
        add_rewrite_rule("^$l/{$s['legal'][$l]}/([a-z-]+)/?$", 'index.php?ec_lang=' . $l . '&ec_page=legal&ec_slug=$matches[1]', 'top');
        add_rewrite_rule("^$l/{$s['area'][$l]}/?$", "index.php?ec_lang=$l&ec_page=area", 'top');
        add_rewrite_rule("^$l/{$s['area'][$l]}/([a-z0-9-]+)/?$", 'index.php?ec_lang=' . $l . '&ec_page=area&ec_slug=$matches[1]', 'top');
        foreach (['project', 'add', 'favs', 'index', 'login', 'register', 'verify', 'panel', 'review', 'offices', 'faq'] as $p) {
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

/** Ofis vitrini: alt alan adı açıksa ofis.alanadi.com, değilse /tr/ofis/ofis-adi/ */
function ec_url_office(string $slug, ?string $lang = null): string
{
    $lang = $lang ?? ec_lang();
    if (get_option('ec_subdomains')) {
        $p = wp_parse_url(home_url('/'));
        $host = preg_replace('/^www\./', '', $p['host']);
        return $p['scheme'] . '://' . $slug . '.' . $host . (!empty($p['port']) ? ':' . $p['port'] : '') . '/' . ($lang !== EC_DEFAULT_LANG ? $lang . '/' : '');
    }
    return home_url('/' . $lang . '/' . ec_sections()['office'][$lang] . '/' . $slug . '/');
}

/** İstek bir ofis alt alan adına mı geldi? (ör. maviada.adacasa.com) → ofis kısa adı */
function ec_request_office_slug(): ?string
{
    if (!get_option('ec_subdomains')) {
        return null;
    }
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $base = preg_replace('/^www\./', '', strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST)));
    if ($host === $base || $host === 'www.' . $base || !str_ends_with($host, '.' . $base)) {
        return null;
    }
    $sub = substr($host, 0, -strlen('.' . $base));
    return preg_match('/^[a-z0-9-]{2,60}$/', $sub) ? $sub : null;
}

/** Alt alan adından gelen ana sayfa isteğini ofis vitrinine çevir */
add_filter('request', function ($vars) {
    $slug = ec_request_office_slug();
    if (!$slug) {
        return $vars;
    }
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($path === '' || in_array($path, EC_LANGS, true)) {
        return ['ec_page' => 'office', 'ec_slug' => $slug, 'ec_lang' => $path ?: EC_DEFAULT_LANG];
    }
    return $vars;
});

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
    // Ofis alt alan adları açıkken: eski /tr/ofis/ad/ adresleri alt alan adına, alt alan adındaki diğer sayfalar ana alan adına (yinelenen içerik olmasın)
    if (get_option('ec_subdomains') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $sub = ec_request_office_slug();
        if ($sub && $page !== 'office') {
            wp_safe_redirect(home_url($_SERVER['REQUEST_URI'] ?? '/'), 301);
            exit;
        }
        if (!$sub && $page === 'office' && ec_agent_by_slug((string) get_query_var('ec_slug'))) {
            wp_safe_redirect(ec_url_office((string) get_query_var('ec_slug'), ec_lang()), 301);
            exit;
        }
    }
    if (!$page && !ec_request_office_slug() && (is_front_page() || is_home()) && !is_paged() && trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') === trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/')) {
        $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $lang = str_starts_with($accept, 'ru') ? 'ru' : (str_starts_with($accept, 'tr') || $accept === '' ? 'tr' : 'en');
        wp_safe_redirect(ec_url_home($lang), 302);
        exit;
    }
    if ($page === 'listing') {
        $id = (int) get_query_var('ec_id');
        $row = ec_listing_row($id);
        $me = function_exists('ec_current_agent') ? ec_current_agent() : null;
        $private = $row && (current_user_can('manage_options') || ($me && (int) $row['agent_id'] === (int) $me['id']));
        $item = ec_listing_get($id, ec_lang(), !$private);
        if ($item && !in_array($item['status'], ['published', 'sold'], true)) {
            $GLOBALS['ec_preview'] = true; // yayında değil: sadece sahibi ve yönetici görür
        }
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
        $prow = ec_project_row((int) get_query_var('ec_id'));
        $me = function_exists('ec_current_agent') ? ec_current_agent() : null;
        $private = $prow && (current_user_can('manage_options') || ($me && (int) $prow['agent_id'] === (int) $me['id']));
        $p = ec_project_get((int) get_query_var('ec_id'), ec_lang(), !$private);
        if ($p && $p['status'] !== 'published') {
            $GLOBALS['ec_preview'] = true;
        }
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
    if ($page === 'office') {
        $a = ec_agent_by_slug((string) get_query_var('ec_slug'));
        if (!$a) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        $GLOBALS['ec_current_office'] = $a;
    }
    if (in_array($page, ['panel', 'review'], true) && !is_user_logged_in()) {
        wp_safe_redirect(add_query_arg('geri', rawurlencode($_SERVER['REQUEST_URI'] ?? ''), ec_url_section('login')));
        exit;
    }
    if ($page === 'review' && !current_user_can('manage_options')) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        return;
    }
    if (in_array($page, ['login', 'register'], true) && is_user_logged_in() && empty($_GET['cikis'])) {
        wp_safe_redirect(current_user_can('manage_options') && $page === 'login' ? ec_url_section('review') : ec_url_section('panel'));
        exit;
    }
    if (in_array($page, ['panel', 'review', 'login', 'register', 'verify'], true)) {
        nocache_headers();
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

/** wp_safe_redirect alt alan adına (ofis vitrini) yönlendirebilsin */
add_filter('allowed_redirect_hosts', function ($hosts, $host = '') {
    $base = preg_replace('/^www\./', '', strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST)));
    if (get_option('ec_subdomains') && $host && str_ends_with(strtolower($host), '.' . $base) && preg_match('/^[a-z0-9-]{2,60}\.' . preg_quote($base, '/') . '$/', strtolower($host))) {
        $hosts[] = strtolower($host);
    }
    return $hosts;
}, 10, 2);
