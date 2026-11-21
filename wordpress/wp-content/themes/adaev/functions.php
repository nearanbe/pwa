<?php
/**
 * AdaEv teması. Sadece görünüm katmanıdır; veri ve iş mantığı "Emlak Core" eklentisindedir.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AE_VER', '0.2.0');

if (!function_exists('ec_lang')) {
    // Eklenti kapalıysa site çökmesin, yöneticiye uyarı gösterilsin.
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>AdaEv teması için <b>Emlak Core</b> eklentisini etkinleştirin.</p></div>';
    });
    function ec_lang(): string
    {
        return 'tr';
    }
    return;
}

require_once __DIR__ . '/inc/i18n.php';
require_once __DIR__ . '/inc/helpers.php';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ae-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;600&display=swap', [], null);
    wp_enqueue_style('ae-main', get_theme_file_uri('assets/css/main.css'), [], AE_VER);
    wp_enqueue_script('ae-main', get_theme_file_uri('assets/js/main.js'), [], AE_VER, ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('ae-main', 'AE', [
        'lang' => ec_lang(),
        'rates' => ec_rates(),
        'sym' => AE_SYM,
        'api' => home_url('/api/v1/'),
        'favsUrl' => ec_url_section('favs'),
        'msg' => [
            'regRequired' => ae_t('reg_required'),
            'regOk' => ae_t('reg_ok'),
            'regErr' => ae_t('reg_err'),
            'notFoundNo' => ae_t('not_found_no'),
            'copied' => ae_t('copied'),
            'noPhone' => ae_t('no_phone'),
        ],
    ]);
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
    ];
    $p = ec_page();
    return isset($map[$p]) ? get_theme_file_path('templates/' . $map[$p] . '.php') : $tpl;
});

add_filter('language_attributes', function ($out) {
    return 'lang="' . esc_attr(ec_lang()) . '"';
});

/** Tema etkinleştirilince blog sayfasını hazırla */
add_action('after_switch_theme', function () {
    if (function_exists('ec_ensure_blog_page')) {
        ec_ensure_blog_page();
    }
});

add_filter('excerpt_length', fn() => 26);
add_filter('excerpt_more', fn() => '…');
