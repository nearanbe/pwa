<?php
/**
 * AdaVira teması
 */
defined('ABSPATH') || exit;

define('ADV_VER', '1.2.5');
define('ADV_DIR', get_template_directory());
define('ADV_URI', get_template_directory_uri());

require ADV_DIR . '/inc/cloudflare.php';
require ADV_DIR . '/inc/data.php';
require ADV_DIR . '/inc/helpers.php';
require ADV_DIR . '/inc/settings.php';
require ADV_DIR . '/inc/setup.php';
require ADV_DIR . '/inc/routes.php';
require ADV_DIR . '/inc/seo.php';
require ADV_DIR . '/inc/contact.php';
require ADV_DIR . '/inc/importer.php';
require ADV_DIR . '/inc/news.php';
