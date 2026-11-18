<?php
/**
 * Eski sitenin URL'leri aynen korunur:
 *   /hizmetler, /hizmet/{slug}, /sektorler, /sektor/{slug}, /sitemap.xml
 *   /blog, /blog/{slug}, /bizden-haberler, /bizden-haberler/{slug}  (yazı türleri)
 *   /hakkimizda, /iletisim  (sayfalar)
 */
defined('ABSPATH') || exit;

add_filter('query_vars', function ($v) { $v[] = 'adv_view'; $v[] = 'adv_slug'; return $v; });

add_action('init', function () {
	add_rewrite_rule('^hizmetler/?$', 'index.php?adv_view=hizmetler', 'top');
	add_rewrite_rule('^hizmet/([^/]+)/?$', 'index.php?adv_view=hizmet&adv_slug=$matches[1]', 'top');
	add_rewrite_rule('^sektorler/?$', 'index.php?adv_view=sektorler', 'top');
	add_rewrite_rule('^sektor/([^/]+)/?$', 'index.php?adv_view=sektor&adv_slug=$matches[1]', 'top');
	add_rewrite_rule('^sitemap\.xml$', 'index.php?adv_view=sitemap', 'top');
	// Tema güncellenince kuralları bir kez yenile
	if (get_option('adv_rules_ver') !== ADV_VER) { flush_rewrite_rules(false); update_option('adv_rules_ver', ADV_VER); }
});

// WordPress'in kendi site haritası kapalı; /sitemap.xml tema tarafından üretilir
add_filter('wp_sitemaps_enabled', '__return_false');

function adv_view() { return (string) get_query_var('adv_view'); }

add_filter('redirect_canonical', function ($r) { return adv_view() ? false : $r; });

/** Geçersiz sektör/hizmet adresleri gerçek 404 versin */
add_action('wp', function () {
	$v = adv_view();
	if (!$v) return;
	global $wp_query;
	$ok = ($v === 'hizmetler' || $v === 'sektorler' || $v === 'sitemap' || $v === 'newsmap')
		|| ($v === 'yazar' && adv_author_by_slug(get_query_var('adv_slug')))
		|| ($v === 'hizmet' && isset($GLOBALS['ADV_SERVICES'][get_query_var('adv_slug')]))
		|| ($v === 'sektor' && isset($GLOBALS['ADV_SECTORS'][get_query_var('adv_slug')]));
	if ($ok) { $wp_query->is_home = false; $wp_query->is_404 = false; status_header(200); }
	else { $wp_query->set_404(); status_header(404); nocache_headers(); }
});

add_filter('template_include', function ($t) {
	$v = adv_view();
	if (!$v || is_404()) return $t;
	$map = ['hizmetler' => 'hizmetler', 'hizmet' => 'hizmet', 'sektorler' => 'sektorler', 'sektor' => 'sektor', 'yazar' => 'yazar'];
	if (isset($map[$v])) return ADV_DIR . '/templates/' . $map[$v] . '.php';
	return $t;
});

add_filter('body_class', function ($c) { if ($v = adv_view()) $c[] = 'v-' . sanitize_html_class($v); return $c; });

/** Eski adresler için 301 yönlendirmeleri ve kök dosyalar */
add_action('template_redirect', function () {
	// www.adavira.com -> adavira.com (tek adres; Google için yinelenen site olmasın)
	$want = parse_url(home_url('/'), PHP_URL_HOST);
	$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
	if ($want && $host && $host !== $want && preg_replace('/^www\./', '', $host) === preg_replace('/^www\./', '', $want)) {
		wp_redirect(set_url_scheme('https://' . $want . ($_SERVER['REQUEST_URI'] ?? '/')), 301); exit;
	}
	$path = adv_req_path();

	if (adv_view() === 'sitemap') { adv_sitemap(); exit; }
	if (adv_view() === 'newsmap') { adv_news_sitemap(); exit; }

	$redirects = [
		'/blog/kibris-ta-milyonluk-villalar-i-lanlarda-neden-aylarca-bekliyor-pahali-portfoyun-ucuz-fotograf-cikmazi' => '/blog/kibris-ta-milyonluk-villalar-neden-ilanlarda-aylarca-bekliyor',
		'/blog/kibris-ta-milyonluk-villalar-i-lanlarda-neden-aylarca-bekliyor' => '/blog/kibris-ta-milyonluk-villalar-neden-ilanlarda-aylarca-bekliyor',
		'/blog/kibris-ta-milyonluk-villalar-ilanlarda-neden-aylarca-bekliyor-pahali-portfoyun-ucuz-fotograf-cikmazi' => '/blog/kibris-ta-milyonluk-villalar-neden-ilanlarda-aylarca-bekliyor',
		'/blog/kibris-ta-milyonluk-villalar-neden-ilanlarda-neden-aylarca-bekliyor' => '/blog/kibris-ta-milyonluk-villalar-neden-ilanlarda-aylarca-bekliyor',
		'/yonetim.php' => '/wp-admin/',
	];
	// Eski sitenin Google'da kayıtlı yazıları: yeni sistemde yoksa Blog / Bizden Haberler sayfasına yönlendir
	foreach (['/blog/kktc-ve-girne-deki-oto-galeriler-icin-arac-satislarini-hizlandiran-dijital-vitrin-rehberi', '/blog/girne-ve-kktc-de-klinik-dis-hekimi-ve-uzmanlar-icin-hasta-kazandiran-dijital-rehber', '/blog/kibris-ta-milyonluk-villalar-neden-ilanlarda-aylarca-bekliyor', '/blog/girne-isletmeleri-icin-google-seo'] as $o) $redirects[$o] = $redirects[$o] ?? '/blog';
	foreach (['/bizden-haberler/girne-ve-kktc-isletmeleri-icin-dijitalde-yeni-donem-saha-tecrubesi-ve-olculebilir-buyume', '/bizden-haberler/adavira-yayinda'] as $o) $redirects[$o] = '/bizden-haberler';
	$k = rtrim($path, '/');
	// Yalnızca sayfa yoksa yönlendir (aynı adreste gerçek bir yazı varsa o gösterilir)
	if (isset($redirects[$k]) && (is_404() || $k === '/yonetim.php')) { wp_safe_redirect(home_url($redirects[$k]), 301); exit; }

	// Eski görsel adresleri: /uploads/2026/11/x.webp -> /wp-content/uploads/2026/11/x.webp
	if (preg_match('#^/uploads/(\d{4}/\d{2}/[\w.-]+\.(?:webp|jpe?g|png))$#i', $path, $m)) {
		$up = wp_upload_dir();
		if (is_file($up['basedir'] . '/' . $m[1])) { wp_redirect($up['baseurl'] . '/' . $m[1], 301); exit; }
	}
	// Kökteki ikonlar (sunucuya ayrıca kopyalanmadıysa)
	$root = ['/favicon.ico' => 'image/png', '/favicon-32.png' => 'image/png', '/icon-512.png' => 'image/png', '/apple-touch-icon.png' => 'image/png', '/icon-192.png' => 'image/png', '/assets/og-default.png' => 'image/png'];
	if (isset($root[$path])) {
		$f = ADV_DIR . '/assets/' . ($path === '/favicon.ico' ? 'favicon-32.png' : basename($path));
		if (is_file($f)) { status_header(200); header('Content-Type: ' . $root[$path]); header('Cache-Control: public, max-age=31536000'); readfile($f); exit; }
	}
}, 1);

add_filter('robots_txt', function ($out, $public) {
	if (!$public) return $out;
	return "User-agent: *\nAllow: /\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /?s=\nDisallow: /search/\n\nSitemap: " . home_url('/sitemap.xml') . "\nSitemap: " . home_url('/news-sitemap.xml') . "\n";
}, 10, 2);

function adv_sitemap() {
	status_header(200);
	header('Content-Type: application/xml; charset=utf-8');
	$u = [];
	foreach (['/', '/hizmetler', '/sektorler', '/blog', '/bizden-haberler', '/hakkimizda', '/iletisim'] as $x) $u[] = [$x, '', ''];
	foreach ($GLOBALS['ADV_SERVICES'] as $s => $_) $u[] = ['/hizmet/' . $s, '', ''];
	foreach ($GLOBALS['ADV_SECTORS'] as $s => $_) $u[] = ['/sektor/' . $s, '', ''];
	foreach (get_posts(['post_type' => ['blog', 'haber'], 'post_status' => 'publish', 'numberposts' => 1000, 'orderby' => 'date', 'order' => 'DESC']) as $p) {
		$img = has_post_thumbnail($p) ? wp_get_attachment_image_url(get_post_thumbnail_id($p), 'full') : '';
		$u[] = [wp_make_link_relative(get_permalink($p)), get_post_modified_time('Y-m-d', true, $p), $img];
	}
	$terms = get_terms(['taxonomy' => 'konu', 'hide_empty' => true]);
	if (!is_wp_error($terms)) foreach ($terms as $t) $u[] = [wp_make_link_relative(get_term_link($t)), '', ''];
	$au = [];
	foreach (get_posts(['post_type' => ['blog', 'haber'], 'numberposts' => 1000, 'fields' => 'ids']) as $pid) $au[(int) get_post_field('post_author', $pid)] = 1;
	foreach (array_keys($au) as $uid) $u[] = ['/yazar/' . adv_author_slug($uid), '', ''];
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
	foreach ($u as $x) {
		echo '<url><loc>' . esc_url(home_url($x[0])) . '</loc>' . ($x[1] ? '<lastmod>' . $x[1] . '</lastmod>' : '') . ($x[2] ? '<image:image><image:loc>' . esc_url($x[2]) . '</image:loc></image:image>' : '') . '</url>';
	}
	echo '</urlset>';
}
