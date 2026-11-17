<?php
/**
 * SEO: schema.org yapısal veri, meta açıklama ve Open Graph.
 * Yoast SEO veya Rank Math kuruluysa meta/OG etiketlerini onlara bırakır;
 * işletme (MedicalBusiness) ve kişi (Person) verisini her durumda ekler.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bir SEO eklentisi etkin mi?
 *
 * @return bool
 */
function fc_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Geçerli sayfa için açıklama metni.
 *
 * @return string
 */
function fc_meta_description() {
	if ( is_front_page() ) {
		return fc_opt( 'hero_text' );
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		$text = has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $post->post_content ) ) );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( $text ) {
			return wp_trim_words( $text, 28, '…' );
		}
	}
	if ( is_category() || is_tag() ) {
		$d = term_description();
		if ( $d ) {
			return wp_strip_all_tags( $d );
		}
	}
	return get_bloginfo( 'description' );
}

add_action(
	'wp_head',
	function () {
		if ( fc_has_seo_plugin() ) {
			return;
		}
		$desc  = fc_meta_description();
		$title = wp_get_document_title();
		$url   = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
		$image = '';
		if ( is_singular() && has_post_thumbnail() ) {
			$image = get_the_post_thumbnail_url( null, 'large' );
		} elseif ( (int) fc_opt( 'hero_image' ) ) {
			$image = fc_hero_image_url( 'large' );
		}
		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		echo '<meta property="og:locale" content="tr_TR">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:type" content="' . ( is_single() ? 'article' : 'website' ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		if ( $desc ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}
		echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	},
	1
);

/**
 * İşletme + kişi şeması.
 *
 * @return array
 */
function fc_schema_business() {
	$home   = home_url( '/' );
	$person = array(
		'@type'      => 'Person',
		'@id'        => $home . '#feriha-celik',
		'name'       => fc_opt( 'person_name' ),
		'jobTitle'   => fc_opt( 'title' ),
		'url'        => home_url( '/hakkimda/' ),
		'worksFor'   => array( '@id' => $home . '#isletme' ),
		'knowsAbout' => wp_list_pluck( fc_services_flat(), 'title' ),
	);
	if ( (int) fc_opt( 'hero_image' ) ) {
		$person['image'] = fc_hero_image_url( 'large' );
	}

	$catalog = array();
	foreach ( fc_service_groups() as $group ) {
		$offers = array();
		foreach ( $group['items'] as $item ) {
			$offers[] = array(
				'@type'       => 'Offer',
				'itemOffered' => array(
					'@type'       => 'Service',
					'name'        => $item['title'],
					'description' => $item['short'],
					'url'         => fc_service_url( $item['slug'] ),
				),
			);
		}
		$catalog[] = array(
			'@type'           => 'OfferCatalog',
			'name'            => $group['title'],
			'itemListElement' => $offers,
		);
	}

	$business = array(
		'@type'         => 'MedicalBusiness',
		'@id'           => $home . '#isletme',
		'name'          => get_bloginfo( 'name' ),
		'description'   => fc_opt( 'hero_text' ),
		'url'           => $home,
		'founder'       => array( '@id' => $home . '#feriha-celik' ),
		'areaServed'    => array(
			array( '@type' => 'AdministrativeArea', 'name' => 'Kuzey Kıbrıs' ),
			array( '@type' => 'Country', 'name' => 'Kıbrıs' ),
		),
		'availableLanguage' => 'tr',
		'hasOfferCatalog'   => array(
			'@type'           => 'OfferCatalog',
			'name'            => 'Psikolojik danışmanlık ve terapi hizmetleri',
			'itemListElement' => $catalog,
		),
	);
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$business['logo'] = wp_get_attachment_image_url( $logo_id, 'full' );
	}
	$business['image'] = (int) fc_opt( 'hero_image' ) ? fc_hero_image_url( 'large' ) : ( $logo_id ? $business['logo'] : get_theme_file_uri( 'screenshot.png' ) );
	if ( fc_opt( 'phone' ) ) {
		$business['telephone'] = fc_opt( 'phone' );
	}
	if ( fc_opt( 'email' ) ) {
		$business['email'] = fc_opt( 'email' );
	}
	if ( fc_opt( 'address' ) ) {
		$business['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => fc_opt( 'address' ),
			'addressLocality' => fc_opt( 'city' ),
			'addressCountry'  => 'CY',
		);
	}
	if ( fc_opt( 'maps_url' ) ) {
		$business['hasMap'] = fc_opt( 'maps_url' );
	}
	$same = array_values( array_filter( array( fc_opt( 'instagram' ), fc_opt( 'facebook' ) ) ) );
	if ( $same ) {
		$business['sameAs'] = $same;
	}
	return array( $business, $person );
}

add_action(
	'wp_head',
	function () {
		$graph = fc_schema_business();
		$home  = home_url( '/' );

		if ( is_singular( 'post' ) && ! fc_has_seo_plugin() ) {
			$post    = get_queried_object();
			$article = array(
				'@type'            => 'BlogPosting',
				'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
				'datePublished'    => get_the_date( 'c', $post ),
				'dateModified'     => get_the_modified_date( 'c', $post ),
				'author'           => array( '@id' => $home . '#feriha-celik' ),
				'publisher'        => array( '@id' => $home . '#isletme' ),
				'mainEntityOfPage' => get_permalink( $post ),
				'inLanguage'       => 'tr-TR',
				'description'      => fc_meta_description(),
			);
			if ( has_post_thumbnail( $post ) ) {
				$article['image'] = get_the_post_thumbnail_url( $post, 'large' );
			}
			$graph[] = $article;
		}

		if ( is_page() && ! is_front_page() && ! fc_has_seo_plugin() ) {
			$items = array(
				array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Anasayfa', 'item' => $home ),
			);
			$page  = get_queried_object();
			$chain = array_reverse( get_post_ancestors( $page ) );
			$chain[] = $page->ID;
			foreach ( $chain as $i => $pid ) {
				$items[] = array( '@type' => 'ListItem', 'position' => $i + 2, 'name' => wp_strip_all_tags( get_the_title( $pid ) ), 'item' => get_permalink( $pid ) );
			}
			$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $items );
		}

		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_UNICODE
		) . "</script>\n";
	},
	20
);

/* Başlık ayırıcı: "Sayfa – Site adı" */
add_filter(
	'document_title_separator',
	function () {
		return '–';
	}
);
