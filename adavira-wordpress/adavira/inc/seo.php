<?php
/**
 * SEO: <title>, meta description, canonical, Open Graph, Twitter, schema.org (JSON-LD).
 * Rank Math veya Yoast kuruluysa başlık/meta/OG işini eklentiye bırakır, yalnızca
 * kurumsal (Organization) şemayı ve breadcrumb'ı çıkarmaya devam eder.
 */
defined('ABSPATH') || exit;

function adv_seo_plugin() { return defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION'); }

const ADV_DEF_DESC = 'Girne merkezli dijital ajans AdaVira: web tasarım ve yazılım, SEO, sosyal medya yönetimi, video ve drone çekimi. Emlak, oto galeri, sağlık, otel ve daha fazlası.';

/** Geçerli sayfa için başlık, açıklama, breadcrumb, görsel ve tür bilgisi */
function adv_meta() {
	static $m;
	if ($m !== null) return $m;
	$m = ['title' => '', 'desc' => '', 'crumbs' => [], 'type' => 'website', 'image' => 0, 'robots' => ''];
	$S = $GLOBALS['ADV_SECTORS']; $V = $GLOBALS['ADV_SERVICES'];
	$v = adv_view(); $slug = (string) get_query_var('adv_slug');

	if (is_404()) {
		$m['title'] = 'Sayfa bulunamadı | AdaVira'; $m['robots'] = 'noindex';
	} elseif (is_front_page()) {
		$m['title'] = 'AdaVira | Girne Dijital Ajans: Web Tasarım, SEO, Sosyal Medya';
		$m['desc'] = ADV_DEF_DESC;
	} elseif ($v === 'hizmetler') {
		$m['title'] = 'Hizmetlerimiz | AdaVira Girne';
		$m['desc'] = 'Web tasarım, sosyal medya yönetimi, SEO, dijital reklam, marka kimliği ve video prodüksiyon hizmetleri.';
		$m['crumbs'] = [['Hizmetler', '/hizmetler']];
	} elseif ($v === 'hizmet' && isset($V[$slug])) {
		$m['title'] = $V[$slug][0] . ' | AdaVira Girne'; $m['desc'] = $V[$slug][2];
		$m['crumbs'] = [['Hizmetler', '/hizmetler'], [$V[$slug][0], '/hizmet/' . $slug]];
	} elseif ($v === 'sektorler') {
		$m['title'] = 'Sektörler | AdaVira Girne';
		$m['desc'] = 'Emlak, oto galeri, güzellik, spor salonu, sağlık, e-ticaret, otel ve seyahat acenteleri için dijital çözümler.';
		$m['crumbs'] = [['Sektörler', '/sektorler']];
	} elseif ($v === 'sektor' && isset($S[$slug])) {
		$m['title'] = $S[$slug][1] . ' | AdaVira'; $m['desc'] = $S[$slug][2];
		$m['crumbs'] = [['Sektörler', '/sektorler'], [$S[$slug][0], '/sektor/' . $slug]];
	} elseif ($v === 'yazar' && ($uid = adv_author_by_slug($slug))) {
		$n = adv_author_name($uid);
		$m['title'] = $n . ' | AdaVira Yazarı';
		$m['desc'] = trim((string) get_the_author_meta('description', $uid)) ?: $n . ' tarafından yazılan AdaVira haberleri ve yazıları.';
		$m['crumbs'] = [['Yazarlar', ''], [$n, '/yazar/' . $slug]];
		$m['author'] = $uid;
	} elseif (is_post_type_archive(['blog', 'haber'])) {
		$nm = is_post_type_archive('blog') ? 'Blog' : 'Bizden Haberler';
		$m['title'] = $nm . ' | AdaVira Girne' . (get_query_var('paged') > 1 ? ' – Sayfa ' . (int) get_query_var('paged') : '');
		$m['desc'] = 'AdaVira ' . $nm . ': dijital pazarlama, SEO ve web tasarım üzerine güncel içerikler.';
		$m['crumbs'] = [[$nm, is_post_type_archive('blog') ? '/blog' : '/bizden-haberler']];
	} elseif (is_tax('konu')) {
		$t = get_queried_object();
		$m['title'] = $t->name . ' | AdaVira';
		$m['desc'] = $t->description ?: 'AdaVira içerikleri: ' . $t->name;
		$m['crumbs'] = [[$t->name, wp_make_link_relative(get_term_link($t))]];
	} elseif (is_singular(['blog', 'haber'])) {
		$p = get_queried_object();
		$nm = $p->post_type === 'blog' ? 'Blog' : 'Bizden Haberler';
		$m['title'] = get_the_title($p) . ' | AdaVira'; $m['desc'] = adv_excerpt($p);
		$m['crumbs'] = [[$nm, $p->post_type === 'blog' ? '/blog' : '/bizden-haberler'], [get_the_title($p), wp_make_link_relative(get_permalink($p))]];
		$m['type'] = 'article'; $m['image'] = get_post_thumbnail_id($p);
	} elseif (is_page()) {
		$p = get_queried_object();
		$t = get_the_title($p);
		if ($p->post_name === 'hakkimizda') $m['title'] = $t . ' | AdaVira Girne Dijital Ajans';
		elseif ($p->post_name === 'iletisim') { $m['title'] = 'İletişim | AdaVira Girne'; $m['desc'] = 'AdaVira ile iletişime geçin: telefon numaranızı bırakın, sizi arayalım. WhatsApp ve e-posta ile de ulaşabilirsiniz.'; }
		else $m['title'] = $t . ' | AdaVira';
		if (!$m['desc']) $m['desc'] = adv_excerpt($p);
		$m['crumbs'] = [[$t, wp_make_link_relative(get_permalink($p))]];
		$m['image'] = get_post_thumbnail_id($p);
	} elseif (is_search()) {
		$m['title'] = 'Arama | AdaVira'; $m['robots'] = 'noindex, follow';
	} else {
		$m['title'] = wp_get_document_title();
	}
	if (!$m['desc'] && !is_404()) $m['desc'] = ADV_DEF_DESC;
	return $m;
}

function adv_canonical() {
	if (is_singular()) return get_permalink();
	$path = adv_req_path();
	return home_url(user_trailingslashit($path === '/' ? '/' : untrailingslashit($path)));
}

remove_action('wp_head', 'rel_canonical');

add_action('wp_head', function () {
	$m = adv_meta();
	$wa = adv_wa();
	$h = '';
	if (!adv_seo_plugin()) {
		$url = adv_canonical();
		$h .= '<title>' . esc_html($m['title']) . "</title>\n";
		if ($m['desc']) $h .= '<meta name="description" content="' . esc_attr($m['desc']) . '">' . "\n";
		if ($m['robots']) $h .= '<meta name="robots" content="' . esc_attr($m['robots']) . '">' . "\n";
		if (!is_404()) $h .= '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
		// Open Graph
		$img = home_url('/assets/og-default.png'); $iw = 1200; $ih = 630; $ialt = $m['title'];
		if ($m['image']) {
			$src = wp_get_attachment_image_src($m['image'], 'adv-og');
			if ($src && ($src[1] == 1200 || empty($src[3]))) { [$img, $iw, $ih] = $src; }
			elseif ($full = wp_get_attachment_image_src($m['image'], 'full')) { [$img, $iw, $ih] = $full; }
			$ialt = trim((string) get_post_meta($m['image'], '_wp_attachment_image_alt', true)) ?: $m['title'];
		} else {
			$img = ADV_URI . '/assets/og-default.png';
		}
		$og = [
			'og:type' => $m['type'], 'og:title' => $m['title'], 'og:description' => $m['desc'], 'og:url' => $url,
			'og:locale' => 'tr_TR', 'og:site_name' => 'AdaVira', 'og:image' => $img, 'og:image:width' => $iw, 'og:image:height' => $ih, 'og:image:alt' => $ialt,
		];
		foreach ($og as $k => $v) $h .= '<meta property="' . $k . '" content="' . esc_attr($v) . '">';
		if ($m['type'] === 'article') {
			$h .= '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '"><meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">';
		}
		$h .= '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . esc_attr($m['title']) . '"><meta name="twitter:description" content="' . esc_attr($m['desc']) . '"><meta name="twitter:image" content="' . esc_attr($img) . '">' . "\n";
	}

	// schema.org: yerel işletme
	$org = [
		'@context' => 'https://schema.org', '@type' => 'ProfessionalService', '@id' => home_url('/#org'),
		'name' => 'AdaVira', 'url' => home_url('/'), 'logo' => ADV_URI . '/assets/icon-512.png', 'image' => ADV_URI . '/assets/og-default.png',
		'email' => adv_email(), 'telephone' => '+' . $wa,
		'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Girne', 'addressCountry' => 'CY', 'streetAddress' => adv_opt('address', 'Ozanköy - Girne / KKTC')],
		'areaServed' => 'Kuzey Kıbrıs', 'priceRange' => '₺₺',
		'openingHoursSpecification' => [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 'opens' => '09:00', 'closes' => '18:00']],
		'description' => 'Girne merkezli dijital ajans: web tasarım, SEO, sosyal medya, video prodüksiyon.',
	];
	$same = array_values(array_filter(array_map(function ($k) { return adv_social_url($k); }, ['facebook', 'instagram', 'x', 'youtube', 'linkedin', 'tiktok'])));
	if ($same) $org['sameAs'] = $same;
	if (is_front_page() || adv_view() === 'hizmetler') {
		$org['hasOfferCatalog'] = ['@type' => 'OfferCatalog', 'name' => 'Hizmetler', 'itemListElement' => array_values(array_map(function ($s, $v) {
			return ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $v[0], 'description' => $v[2], 'url' => home_url('/hizmet/' . $s)]];
		}, array_keys($GLOBALS['ADV_SERVICES']), $GLOBALS['ADV_SERVICES']))];
	}
	$ld = [$org];
	if (is_front_page()) $ld[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'AdaVira', 'url' => home_url('/'), 'inLanguage' => 'tr-TR', 'publisher' => ['@id' => home_url('/#org')]];

	if ($m['crumbs'] && !(adv_seo_plugin() && function_exists('rank_math'))) {
		$it = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => home_url('/')]];
		foreach (array_values(array_filter($m['crumbs'], function ($c) { return $c[1] !== ''; })) as $i => $c) $it[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $c[0], 'item' => home_url($c[1])];
		$ld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $it];
	}
	if ($m['type'] === 'article' && !adv_seo_plugin()) {
		$p = get_queried_object();
		$img = $m['image'] ? wp_get_attachment_image_url($m['image'], 'full') : ADV_URI . '/assets/og-default.png';
		$ld[] = [
			'@context' => 'https://schema.org', '@type' => $p->post_type === 'haber' ? 'NewsArticle' : 'BlogPosting',
			'headline' => get_the_title($p), 'description' => $m['desc'], 'image' => [$img],
			'datePublished' => get_the_date('c', $p), 'dateModified' => get_the_modified_date('c', $p),
			'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => get_permalink($p)], 'inLanguage' => 'tr-TR',
			'author' => adv_person_schema((int) $p->post_author), 'isAccessibleForFree' => true,
			'publisher' => ['@type' => 'Organization', 'name' => 'AdaVira', 'logo' => ['@type' => 'ImageObject', 'url' => ADV_URI . '/assets/icon-512.png']],
		];
	}
	if (is_singular(['blog', 'haber']) && ($yid = adv_yt_id(get_post_meta(get_queried_object_id(), 'adv_youtube', true)))) {
		$p = get_queried_object();
		$ld[] = ['@context' => 'https://schema.org', '@type' => 'VideoObject', 'name' => get_the_title($p), 'description' => $m['desc'] ?: get_the_title($p),
			'thumbnailUrl' => ['https://i.ytimg.com/vi/' . $yid . '/hqdefault.jpg'], 'uploadDate' => get_the_date('c', $p),
			'embedUrl' => 'https://www.youtube.com/embed/' . $yid, 'contentUrl' => 'https://www.youtube.com/watch?v=' . $yid, 'publisher' => ['@id' => home_url('/#org')]];
	}
	if (!empty($m['author'])) $ld[] = ['@context' => 'https://schema.org', '@type' => 'ProfilePage', 'mainEntity' => adv_person_schema($m['author'])];
	foreach ($ld as $x) $h .= '<script type="application/ld+json">' . wp_json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";

	$h .= '<meta name="theme-color" content="#0F2730">';
	if (!has_site_icon()) $h .= '<link rel="icon" href="' . esc_url(ADV_URI . '/assets/favicon-32.png') . '" sizes="32x32" type="image/png"><link rel="icon" href="' . esc_url(ADV_URI . '/assets/icon-192.png') . '" sizes="192x192" type="image/png"><link rel="apple-touch-icon" href="' . esc_url(ADV_URI . '/assets/apple-touch-icon.png') . '">' . "\n";
	echo $h;
}, 1);


// Sayfalanmış arşivlerde, aramada ve ekler sayfasında gereksiz dizinlemeyi önle
add_filter('wp_robots', function ($r) {
	if (is_attachment() || is_author() || is_date()) $r['noindex'] = true;
	return $r;
});
// Ek (attachment) sayfalarını yazıya/ana sayfaya yönlendir
add_action('template_redirect', function () {
	if (is_attachment()) { $p = get_post(); wp_redirect($p && $p->post_parent ? get_permalink($p->post_parent) : home_url('/'), 301); exit; }
	if (is_author() || is_date()) { wp_redirect(home_url('/blog'), 301); exit; }
});

// Kullanıcı adı sızmasını önle: /?author=1 ve /author/... adresleri kullanıcı adını göstermesin
add_action('parse_request', function ($wp) {
	if (!is_admin() && (isset($_GET['author']) || !empty($wp->query_vars['author']) || !empty($wp->query_vars['author_name']))) { wp_redirect(home_url('/'), 301); exit; }
});
add_filter('rest_endpoints', function ($e) {
	if (!is_user_logged_in()) { unset($e['/wp/v2/users'], $e['/wp/v2/users/(?P<id>[\\d]+)']); }
	return $e;
});
add_filter('oembed_response_data', function ($d) { unset($d['author_name'], $d['author_url']); return $d; });
