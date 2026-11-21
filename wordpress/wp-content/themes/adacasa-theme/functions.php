<?php
/**
 * AdaCasa teması. Sadece görünüm katmanıdır; veri ve iş mantığı "AdaCasa Core" eklentisindedir.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AE_VER', '1.0.5');

if (!function_exists('ec_table')) {
    // Eklenti kapalıysa: hiçbir eklenti fonksiyonu TANIMLANMAZ (aksi halde eklenti etkinleştirilirken
    // "Cannot redeclare" hatası oluşur). Yöneticiye uyarı, ziyaretçiye sade bir bakım sayfası gösterilir.
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>AdaCasa teması için <b>AdaCasa Core</b> eklentisini etkinleştirin.</p></div>';
    });
    add_filter('template_include', fn() => __DIR__ . '/templates/no-plugin.php', 99);
    return;
}

require_once __DIR__ . '/inc/i18n-panel.php';
require_once __DIR__ . '/inc/i18n.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/helpers-detail.php';
require_once __DIR__ . '/inc/i18n-detail.php';
require_once __DIR__ . '/inc/faq.php';
require_once __DIR__ . '/inc/pwa.php';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ae-main', get_theme_file_uri('assets/css/main.css'), [], AE_VER);
    wp_enqueue_script('ae-main', get_theme_file_uri('assets/js/main.js'), [], AE_VER, ['in_footer' => true, 'strategy' => 'defer']);
    wp_enqueue_script('ae-converter', get_theme_file_uri('assets/js/converter.js'), ['ae-main'], AE_VER, ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('ae-main', 'AE', [
        'lang' => ec_lang(),
        'rates' => ec_rates(),
        'sym' => AE_SYM,
        'api' => home_url('/api/v1/'),
        'favsUrl' => ec_url_section('favs'),
        'rateInfo' => function_exists('ec_rates_info') ? (function () {
            $i = ec_rates_info();
            $src = ['tcmb' => 'TCMB', 'kktcmb' => 'KKTC Merkez Bankası', 'frankfurter' => 'ECB', 'er-api' => 'open.er-api.com'][$i['source_key']] ?? $i['source'];
            $o = [
                'source' => $src ? ae_t('conv_source', $src) : '',
                'date' => $i['date'] ? ae_t('conv_date', wp_date('d.m.Y', strtotime($i['date'] . ' 12:00'))) : '',
            ];
            if (!empty($i['live'])) {
                // Gün içi kur kullanılıyor: çevirici ayrıca resmi TCMB kurunu gösterir
                $o['live'] = ae_t('conv_live', $i['live']['time']);
                $o['liveSource'] = $i['live']['source'];
                $o['official'] = $i['official'];
                $o['officialLabel'] = ae_t('conv_official', $src, $o['date'] ? wp_date('d.m.Y', strtotime($i['date'] . ' 12:00')) : '');
            }
            return $o;
        })() : [],
        'msg' => [
            'notFoundNo' => ae_t('not_found_no'),
            'copied' => ae_t('copied'),
            'noPhone' => ae_t('no_phone'),
            'convTitle' => ae_t('conv_title'),
            'convAmount' => ae_t('conv_amount'),
            'convOpen' => ae_t('conv_open'),
            'convNote' => ae_t('conv_note'),
            'close' => ae_t('close'),
        ],
    ]);
    if (in_array(ec_page(), ['listing', 'projectd'], true)) {
        wp_enqueue_script('ae-detail', get_theme_file_uri('assets/js/detail.js'), ['ae-main'], AE_VER, ['in_footer' => true, 'strategy' => 'defer']);
        wp_localize_script('ae-detail', 'AED', ['leaflet' => ['js' => get_theme_file_uri('assets/vendor/leaflet/leaflet.js'), 'css' => get_theme_file_uri('assets/vendor/leaflet/leaflet.css')], 'pannellum' => ['js' => get_theme_file_uri('assets/vendor/pannellum/pannellum.js'), 'css' => get_theme_file_uri('assets/vendor/pannellum/pannellum.css')], 't' => [
            'panoLoading' => ae_t('pano_loading'), 'panoFail' => ae_t('pano_fail'),
            'gallery' => ae_t('lb_gallery'), 'slideshow' => ae_t('lb_slideshow'), 'allPhotos' => ae_t('lb_grid'), 'close' => ae_t('close'),
            'nearbyNone' => ae_t('nearby_none'), 'nearbyFail' => ae_t('nearby_fail'), 'nearbySource' => ae_t('nearby_source', '%s'),
            'leadName' => ae_t('lead_err_name'), 'leadContact' => ae_t('lead_err_contact'), 'leadConsent' => ae_t('lead_err_consent'),
            'leadOk' => ae_t('lead_ok'), 'leadErr' => ae_t('lead_err'), 'reportReason' => ae_t('report_err_reason'), 'reportOk' => ae_t('report_ok'),
        ]]);
    }
    $ads = get_option('ec_ads', []);
    if (!empty($ads['client'])) {
        wp_enqueue_script('adsense', 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode($ads['client']), [], null, ['strategy' => 'async']);
    }
});

// Gereksiz WordPress çıktıları (hız için)
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 100);

/** Eklentinin yönlendirdiği sayfaları tema şablonlarına bağla */
add_filter('template_include', function ($tpl) {
    if (is_404()) {
        return get_theme_file_path('templates/404.php');
    }
    $map = [
        'home' => 'home',
        'search' => 'search',
        'listing' => 'listing',
        'project' => 'projects',
        'projectd' => 'project',
        'index' => 'price-index',
        'add' => 'add',
        'favs' => 'favs',
        'login' => 'login',
        'register' => 'register',
        'verify' => 'verify',
        'panel' => 'panel',
        'review' => 'review',
        'office' => 'office',
        'offices' => 'offices',
        'faq' => 'faq',
    ];
    $p = ec_page();
    return isset($map[$p]) ? get_theme_file_path('templates/' . $map[$p] . '.php') : $tpl;
});

/** En çok kullanılan iki yazı tipini önceden yükle (ilk görüntü daha hızlı) */
add_action('wp_head', function () {
    foreach (['inter-latin-400-normal.woff2', 'cormorant-garamond-latin-600-normal.woff2'] as $f) {
        echo '<link rel="preload" href="' . esc_url(get_theme_file_uri('assets/fonts/' . $f)) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
    // Yazı tipi tanımları sayfaya gömülü (ayrı CSS isteği yok)
    $css = (string) @file_get_contents(get_theme_file_path('assets/fonts/fonts.css'));
    echo '<style id="ae-fonts">' . str_replace('url(', 'url(' . esc_url(get_theme_file_uri('assets/fonts/')), preg_replace('~/\*.*?\*/~s', '', $css)) . '</style>' . "\n";
}, 1);

add_filter('language_attributes', function ($out) {
    return 'lang="' . esc_attr(ec_lang()) . '"';
});

/**
 * Tema etkinleştirilince: eski temanın ayarlarını (logo, Özelleştir seçenekleri, menü yerleri) bu temaya bir kez taşı.
 * Tema klasörü adaev → emlak-tema (0.6.1) → adacasa-theme (1.0.1) olarak değişti; en yeni eski tema önce denenir.
 */
add_action('after_switch_theme', function () {
    $new = get_option('theme_mods_' . get_stylesheet());
    if (is_array($new) && count(array_filter($new)) > 1) {
        return; // bu temada zaten ayar var
    }
    foreach (['emlak-tema', 'adaev'] as $slug) {
        $old = get_option('theme_mods_' . $slug);
        if (is_array($old) && $old) {
            update_option('theme_mods_' . get_stylesheet(), $old);
            return;
        }
    }
}, 5);

/** Tema etkinleştirilince blog sayfasını hazırla */
add_action('after_switch_theme', function () {
    if (function_exists('ec_ensure_blog_page')) {
        ec_ensure_blog_page();
    }
});

add_filter('excerpt_length', fn() => 26);
add_filter('excerpt_more', fn() => '…');
