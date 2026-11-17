<?php
/**
 * Plugin Name: Feriha Randevu
 * Description: Basit online randevu sistemi — müsait gün/saat seçimi, randevu formu, Apple Takvim (iCal) aboneliği ve e-posta bildirimleri. Kısa kod: [feriha_randevu]
 * Version: 1.0.0
 * Author: Feriha Çelik
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * Text Domain: feriha-randevu
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

define( 'FR_VERSION', '1.0.0' );
define( 'FR_FILE', __FILE__ );
define( 'FR_DIR', plugin_dir_path( __FILE__ ) );
define( 'FR_URL', plugin_dir_url( __FILE__ ) );

require_once FR_DIR . 'includes/options.php';
require_once FR_DIR . 'includes/bookings.php';
require_once FR_DIR . 'includes/availability.php';
require_once FR_DIR . 'includes/admin-availability.php';
require_once FR_DIR . 'includes/admin-settings.php';
require_once FR_DIR . 'includes/booking-form.php';
require_once FR_DIR . 'includes/ical-feed.php';
require_once FR_DIR . 'includes/notifications.php';

register_activation_hook(
	__FILE__,
	function () {
		$s = fr_settings();
		if ( empty( $s['ical_token'] ) ) {
			$s['ical_token'] = wp_generate_password( 32, false, false );
			update_option( 'fr_settings', $s );
		}
		if ( false === get_option( 'fr_schedule' ) ) {
			update_option( 'fr_schedule', fr_default_schedule() );
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=fr-availability' ) ) . '">Müsait günler</a>' );
		return $links;
	}
);
