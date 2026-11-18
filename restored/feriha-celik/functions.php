<?php
/**
 * Feriha Çelik teması — ana dosya.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

define( 'FC_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );

require_once get_template_directory() . '/inc/services.php';
require_once get_template_directory() . '/inc/settings.php';
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/admin.php';
require_once get_template_directory() . '/inc/shortcodes.php';
require_once get_template_directory() . '/inc/about.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 120,
				'width'       => 360,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_post_type_support( 'page', 'excerpt' );
		add_editor_style( 'assets/css/theme.css' );
		add_image_size( 'fc-card', 720, 450, true );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'feriha-celik', get_theme_file_uri( 'assets/css/theme.css' ), array(), FC_VERSION );
		wp_enqueue_script( 'feriha-celik', get_theme_file_uri( 'assets/js/theme.js' ), array(), FC_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
);

/* Kart görselleri için boyut seçimi listesinde Türkçe ad. */
add_filter(
	'image_size_names_choose',
	function ( $sizes ) {
		$sizes['fc-card'] = 'Kart (16:10)';
		return $sizes;
	}
);

/* Hız: emoji betiklerini kaldır (modern tarayıcılar emojiyi zaten gösterir). */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

/* Desen kategorisi. */
add_action(
	'init',
	function () {
		register_block_pattern_category( 'feriha-celik', array( 'label' => 'Feriha Çelik' ) );
	}
);

/**
 * Sabit (yüzen) WhatsApp butonu — her sayfada sağ altta.
 */
add_action(
	'wp_footer',
	function () {
		$url = fc_whatsapp_url();
		if ( ! $url ) {
			return;
		}
		echo '<a class="fc-wa-float" href="' . esc_url( $url ) . '" target="_blank" rel="noopener" aria-label="WhatsApp ile yazın">'
			. '<svg aria-hidden="true" width="30" height="30" viewBox="0 0 32 32" fill="currentColor"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.6c-2 0-4-.5-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.6 10.6 0 1 1 16 26.6zm5.8-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.8 5.1 2.2.9 3 1 4.1.8.7-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5l-.6-.3z"/></svg>'
			. '<span>WhatsApp</span></a>';
	}
);

/**
 * Randevu eklentisi kurulu değilse [feriha_randevu] kısa kodu
 * ham metin olarak görünmesin; iletişim seçeneklerini göstersin.
 */
add_action(
	'init',
	function () {
		if ( shortcode_exists( 'feriha_randevu' ) ) {
			return;
		}
		add_shortcode(
			'feriha_randevu',
			function () {
				$out = '<div class="fc-booking-fallback"><p>Randevu için bize WhatsApp veya telefonla ulaşabilirsiniz.</p><div class="wp-block-buttons">';
				if ( fc_whatsapp_url() ) {
					$out .= '<div class="wp-block-button is-style-whatsapp"><a class="wp-block-button__link wp-element-button" href="' . esc_url( fc_whatsapp_url() ) . '" target="_blank" rel="noopener">WhatsApp ile yazın</a></div>';
				}
				if ( fc_opt( 'phone' ) ) {
					$out .= '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="tel:+' . esc_attr( fc_digits( fc_opt( 'phone' ) ) ) . '">' . esc_html( fc_opt( 'phone' ) ) . '</a></div>';
				}
				return $out . '</div></div>';
			}
		);
	},
	20
);

/* Buton için "WhatsApp" stil seçeneği (editörde de seçilebilir). */
add_action(
	'init',
	function () {
		register_block_style(
			'core/button',
			array(
				'name'  => 'whatsapp',
				'label' => 'WhatsApp',
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'card',
				'label' => 'Kart',
			)
		);
	}
);

/**
 * Bilgi kısa kodları — editörde metin içine yazılabilir:
 * [fc_bilgi alan="phone"]  (phone, whatsapp, email, address, city, hours)
 */
add_shortcode(
	'fc_bilgi',
	function ( $atts ) {
		$atts    = shortcode_atts( array( 'alan' => 'phone' ), $atts );
		$allowed = array( 'phone', 'whatsapp', 'email', 'address', 'city', 'hours', 'person_name', 'title' );
		return in_array( $atts['alan'], $allowed, true ) ? esc_html( fc_opt( $atts['alan'] ) ) : '';
	}
);

/* Yazı özetlerini kısalt. */
add_filter(
	'excerpt_length',
	function () {
		return 24;
	}
);
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);
