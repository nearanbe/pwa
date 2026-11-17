<?php
/**
 * Sayfaya göre değişen küçük parçalar (kısa kodlar) ve blok filtreleri.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

/**
 * [fc_breadcrumb] — Anasayfa › Üst sayfa › Bu sayfa
 */
add_shortcode(
	'fc_breadcrumb',
	function () {
		$post = get_post();
		if ( ! $post || is_front_page() ) {
			return '';
		}
		$items = array( '<a href="' . esc_url( home_url( '/' ) ) . '">Anasayfa</a>' );
		if ( 'post' === $post->post_type ) {
			$blog    = get_option( 'page_for_posts' );
			$items[] = '<a href="' . esc_url( $blog ? get_permalink( $blog ) : home_url( '/blog/' ) ) . '">Blog</a>';
		}
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $pid ) {
			$items[] = '<a href="' . esc_url( get_permalink( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a>';
		}
		$items[] = '<span aria-current="page">' . esc_html( get_the_title( $post ) ) . '</span>';
		return '<nav class="fc-breadcrumb" aria-label="Sayfa yolu">' . implode( '<span class="fc-breadcrumb__sep" aria-hidden="true">›</span>', $items ) . '</nav>';
	}
);

/**
 * [fc_ilgili_hizmetler] — aynı gruptaki diğer hizmetler.
 */
add_shortcode(
	'fc_ilgili_hizmetler',
	function () {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		$current = $post->post_name;
		$found   = null;
		foreach ( fc_service_groups() as $group ) {
			foreach ( $group['items'] as $item ) {
				if ( $item['slug'] === $current ) {
					$found = $group;
					break 2;
				}
			}
		}
		if ( ! $found ) {
			$found = array(
				'title' => 'Hizmetler',
				'items' => array_slice( fc_services_flat(), 0, 6 ),
			);
		}
		$out = '<div class="fc-related"><p class="fc-related__title">' . esc_html( $found['title'] ) . '</p><ul>';
		foreach ( $found['items'] as $item ) {
			if ( $item['slug'] === $current ) {
				continue;
			}
			$out .= '<li><a href="' . esc_url( fc_service_url( $item['slug'] ) ) . '">' . esc_html( $item['title'] ) . '</a></li>';
		}
		$out .= '<li><a href="' . esc_url( home_url( '/hizmetler/' ) ) . '">Tüm hizmetler →</a></li>';
		return $out . '</ul></div>';
	}
);

/**
 * Öne çıkan görseli olmayan yazı kartlarında yaprak desenli bir yer tutucu göster.
 */
add_filter(
	'render_block_core/post-featured-image',
	function ( $content, $block, $instance ) {
		if ( '' !== trim( $content ) || empty( $instance->context['postId'] ) || is_singular() ) {
			return $content;
		}
		$id = (int) $instance->context['postId'];
		return '<figure class="wp-block-post-featured-image fc-thumb-placeholder"><a href="' . esc_url( get_permalink( $id ) ) . '" tabindex="-1" aria-hidden="true">' . fc_icon( 'leaf' ) . '</a></figure>';
	},
	10,
	3
);

/*
 * Şablonlarda kısa kodlar blok işlenmeden önce çalıştırıldığı için,
 * desenlerin (pattern) içindeki kısa kod blokları da işlensin.
 */
add_filter(
	'render_block_core/shortcode',
	function ( $content ) {
		return false !== strpos( $content, '[' ) ? do_shortcode( $content ) : $content;
	}
);
