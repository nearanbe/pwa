<?php
/**
 * Plugin Name: Emlak Core
 * Description: İlan portalının çekirdeği: kendi veri tabloları, çok dilli link yapısı (TR/EN/RU), REST API (/api/v1) ve yönetim ekranları. Tüm iş mantığı burada; tema sadece görünümdür.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Text Domain: emlak-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EC_VERSION', '0.1.0');
define('EC_DB_VERSION', '1');
define('EC_DIR', __DIR__);
define('EC_FILE', __FILE__);

require_once EC_DIR . '/includes/data.php';
require_once EC_DIR . '/includes/schema.php';
require_once EC_DIR . '/includes/listings.php';
require_once EC_DIR . '/includes/agents.php';
require_once EC_DIR . '/includes/photos.php';
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
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    if (get_option('ec_db_version') !== EC_DB_VERSION) {
        ec_install_schema();
        update_option('ec_flush_rewrites', 1);
    }
});
