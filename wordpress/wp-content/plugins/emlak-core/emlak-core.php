<?php
/**
 * Plugin Name: Emlak Core
 * Description: İlan portalının çekirdeği: kendi veri tabloları, çok dilli link yapısı (TR/EN/RU), REST API (/api/v1) ve yönetim ekranları. Tüm iş mantığı burada; tema sadece görünümdür.
 * Version: 0.6.0
 * Requires PHP: 8.1
 * Text Domain: emlak-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EC_VERSION', '0.6.0');
define('EC_DB_VERSION', '6');
define('EC_DIR', __DIR__);
define('EC_FILE', __FILE__);

require_once EC_DIR . '/includes/data.php';
require_once EC_DIR . '/includes/schema.php';
require_once EC_DIR . '/includes/listings.php';
require_once EC_DIR . '/includes/agents.php';
require_once EC_DIR . '/includes/members.php';
require_once EC_DIR . '/includes/photos.php';
require_once EC_DIR . '/includes/panos.php';
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
require_once EC_DIR . '/includes/extension.php';
require_once EC_DIR . '/includes/packages.php';
require_once EC_DIR . '/includes/social.php';
require_once EC_DIR . '/includes/speed.php';
require_once EC_DIR . '/includes/routing.php';
require_once EC_DIR . '/includes/rest.php';
require_once EC_DIR . '/includes/rest-panel.php';

if (is_admin()) {
    require_once EC_DIR . '/includes/admin.php';
    require_once EC_DIR . '/includes/admin-packages.php';
}
if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
    require_once EC_DIR . '/includes/admin-launch.php';
}
if (defined('WP_CLI') && WP_CLI) {
    require_once EC_DIR . '/includes/cli.php';
}

register_activation_hook(__FILE__, function () {
    ec_install_schema();
    ec_add_member_role();
    ec_register_rewrites();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ec_daily');
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
