<?php
/**
 * Plugin Name: Emlak Core
 * Description: İlan portalının çekirdeği: kendi veri tabloları, çok dilli link yapısı (TR/EN/RU), REST API (/api/v1) ve yönetim ekranları. Tüm iş mantığı burada; tema sadece görünümdür.
 * Version: 0.2.0
 * Requires PHP: 8.1
 * Text Domain: emlak-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EC_VERSION', '0.2.0');
define('EC_DB_VERSION', '2');
define('EC_DIR', __DIR__);
define('EC_FILE', __FILE__);

require_once EC_DIR . '/includes/data.php';
require_once EC_DIR . '/includes/schema.php';
require_once EC_DIR . '/includes/listings.php';
require_once EC_DIR . '/includes/agents.php';
require_once EC_DIR . '/includes/photos.php';
require_once EC_DIR . '/includes/projects.php';
require_once EC_DIR . '/includes/rates.php';
require_once EC_DIR . '/includes/price-index.php';
require_once EC_DIR . '/includes/demo.php';
require_once EC_DIR . '/includes/routing.php';
require_once EC_DIR . '/includes/rest.php';

if (is_admin()) {
    require_once EC_DIR . '/includes/admin.php';
}
if (defined('WP_CLI') && WP_CLI) {
    require_once EC_DIR . '/includes/cli.php';
}

register_activation_hook(__FILE__, function () {
    ec_install_schema();
    ec_register_rewrites();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ec_daily');
    flush_rewrite_rules();
});

/** Günlük görev: kurları güncelle, fiyat endeksini hesapla */
add_action('init', function () {
    if (!wp_next_scheduled('ec_daily')) {
        wp_schedule_event(time() + 300, 'daily', 'ec_daily');
    }
});
add_action('ec_daily', function () {
    if (get_option('ec_rates_auto', 1)) {
        ec_rates_update();
    }
    ec_index_compute();
});

add_action('plugins_loaded', function () {
    if (get_option('ec_db_version') !== EC_DB_VERSION) {
        ec_install_schema();
        update_option('ec_flush_rewrites', 1);
    }
});
