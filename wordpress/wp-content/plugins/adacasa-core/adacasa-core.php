<?php
/**
 * Plugin Name: AdaCasa Core
 * Plugin URI: https://adacasa.com
 * Description: AdaCasa ilan portalının çekirdeği: kendi veri tabloları, çok dilli link yapısı (TR/EN/RU), REST API (/api/v1) ve yönetim ekranları. Tüm iş mantığı burada; tema sadece görünümdür. (Eski adı: Emlak Core)
 * Version: 1.7.0
 * Requires PHP: 8.1
 * Text Domain: adacasa-core
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cloudflare arkasında gerçek ziyaretçi IP'si (1.1.7): her şeyden önce
require_once __DIR__ . '/includes/realip.php';

/*
 * Eski "Emlak Core" (emlak-core/emlak-core.php) ile aynı fonksiyonları tanımlar; ikisi birlikte yüklenirse
 * "Cannot redeclare" hatası oluşur. Eski eklenti etkinse bu eklenti hiçbir şey yüklemez, uyarı gösterir.
 * Etkinleştirme sırasında eski eklenti sessizce devre dışı bırakılır (veri tabloları ve ayarlar aynen kalır).
 */
const EC_LEGACY_PLUGIN = 'emlak-core/emlak-core.php';

register_activation_hook(__FILE__, function () {
    if (in_array(EC_LEGACY_PLUGIN, (array) get_option('active_plugins', []), true)) {
        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        // silent=true: eski eklentinin kapanış kancası zamanlanmış görevleri silmesin (aynı adlarla devam ederler)
        deactivate_plugins(EC_LEGACY_PLUGIN, true);
        update_option('ec_flush_rewrites', 1);
        update_option('ec_legacy_replaced', 1);
        return; // şema ve link kuralları bir sonraki istekte plugins_loaded içinde kurulur
    }
    ec_install_schema();
    ec_add_member_role();
    ec_register_rewrites();
    flush_rewrite_rules();
});

if (defined('EC_VERSION') || in_array(EC_LEGACY_PLUGIN, (array) get_option('active_plugins', []), true)) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><b>AdaCasa Core</b> çalışmıyor: eski <b>Emlak Core</b> eklentisi hâlâ etkin. '
            . 'Eklentiler sayfasında Emlak Core’u devre dışı bırakıp silin; veriler ve ayarlar korunur.</p></div>';
    });
    return;
}

add_action('admin_notices', function () {
    if (get_option('ec_legacy_replaced') && current_user_can('activate_plugins')) {
        delete_option('ec_legacy_replaced');
        echo '<div class="notice notice-success is-dismissible"><p><b>AdaCasa Core</b> etkin. Eski Emlak Core devre dışı bırakıldı; '
            . 'veriler ve ayarlar aynen kullanılıyor. Eski eklentiyi artık Eklentiler sayfasından silebilirsiniz.</p></div>';
    }
});

define('EC_VERSION', '1.7.0');
define('EC_DB_VERSION', '16');
define('EC_DIR', __DIR__);
define('EC_FILE', __FILE__);

require_once EC_DIR . '/includes/data.php';
require_once EC_DIR . '/includes/schema.php';
require_once EC_DIR . '/includes/listings.php';
require_once EC_DIR . '/includes/agents.php';
require_once EC_DIR . '/includes/members.php';
require_once EC_DIR . '/includes/social-login.php';
require_once EC_DIR . '/includes/photos.php';
require_once EC_DIR . '/includes/panos.php';
require_once EC_DIR . '/includes/videos.php';
require_once EC_DIR . '/includes/duplicates.php';
require_once EC_DIR . '/includes/projects.php';
require_once EC_DIR . '/includes/rates.php';
require_once EC_DIR . '/includes/rates-live.php';
require_once EC_DIR . '/includes/price-index.php';
require_once EC_DIR . '/includes/demo.php';
require_once EC_DIR . '/includes/ai-images.php';
require_once EC_DIR . '/includes/leads.php';
require_once EC_DIR . '/includes/nearby.php';
require_once EC_DIR . '/includes/ai-text.php';
require_once EC_DIR . '/includes/telegram.php';
require_once EC_DIR . '/includes/push.php';
require_once EC_DIR . '/includes/confirm.php';
require_once EC_DIR . '/includes/costs.php';
require_once EC_DIR . '/includes/verify.php';
require_once EC_DIR . '/includes/crm.php';
require_once EC_DIR . '/includes/ai-search.php';
require_once EC_DIR . '/includes/areas.php';
require_once EC_DIR . '/includes/staging.php';
require_once EC_DIR . '/includes/extension.php';
require_once EC_DIR . '/includes/packages.php';
require_once EC_DIR . '/includes/promos.php';
require_once EC_DIR . '/includes/team.php';
require_once EC_DIR . '/includes/orders.php';
require_once EC_DIR . '/includes/legal.php';
require_once EC_DIR . '/includes/tracking.php';
require_once EC_DIR . '/includes/social.php';
require_once EC_DIR . '/includes/rss.php';
require_once EC_DIR . '/includes/ads.php';
require_once EC_DIR . '/includes/speed.php';
require_once EC_DIR . '/includes/seo.php';
require_once EC_DIR . '/includes/routing.php';
require_once EC_DIR . '/includes/rest.php';
require_once EC_DIR . '/includes/rest-panel.php';
require_once EC_DIR . '/includes/settings-rest.php';

if (is_admin()) {
    require_once EC_DIR . '/includes/admin.php';
    require_once EC_DIR . '/includes/admin-packages.php';
    require_once EC_DIR . '/includes/admin-media.php';
}
if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
    require_once EC_DIR . '/includes/admin-launch.php';
    require_once EC_DIR . '/includes/admin-transfer.php';
}
if (defined('WP_CLI') && WP_CLI) {
    require_once EC_DIR . '/includes/cli.php';
}

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ec_daily');
    wp_clear_scheduled_hook('ec_alerts_hourly');
    wp_clear_scheduled_hook('ec_rates_hourly');
    wp_clear_scheduled_hook('ec_rates_live');
    flush_rewrite_rules();
});

/** Günlük görev: kurları güncelle, fiyat endeksini hesapla */
add_action('init', function () {
    if (!wp_next_scheduled('ec_daily')) {
        wp_schedule_event(time() + 300, 'daily', 'ec_daily');
    }
});
add_action('ec_daily', function () {
    ec_index_compute(); // kurlar ayrıca saatlik kontrol edilir (ec_rates_hourly)
});

add_action('plugins_loaded', function () {
    if (get_option('ec_db_version') !== EC_DB_VERSION) {
        ec_install_schema();
        update_option('ec_flush_rewrites', 1);
    }
    // Yeni sürümde yeni adresler (ör. /tr/firmalar/) olabilir: link kurallarını bir kez yenile
    if (get_option('ec_rewrite_ver') !== EC_VERSION) {
        update_option('ec_rewrite_ver', EC_VERSION);
        update_option('ec_flush_rewrites', 1);
    }
});
