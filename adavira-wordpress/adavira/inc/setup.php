<?php
defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
	add_theme_support('post-thumbnails');
	add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
	add_theme_support('responsive-embeds');
	add_theme_support('align-wide');
	add_theme_support('editor-styles');
	add_editor_style('assets/editor.css');
	// Bir SEO eklentisi (Rank Math / Yoast) kuruluysa başlığı o yönetsin.
	if (adv_seo_plugin()) add_theme_support('title-tag');
	add_post_type_support('page', 'excerpt');
	// Kapak görselleri: liste kartları, yazı içi ve paylaşım (Open Graph) boyutları
	add_image_size('adv-400', 400, 0);
	add_image_size('adv-800', 800, 0);
	add_image_size('adv-1600', 1600, 0);
	add_image_size('adv-og', 1200, 630, true);
});

add_action('init', function () {
	$common = [
		'public' => true, 'show_in_rest' => true, 'menu_position' => 5,
		'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author'],
	];
	register_post_type('blog', $common + [
		'labels' => ['name' => 'Blog', 'singular_name' => 'Blog yazısı', 'add_new' => 'Yeni yazı', 'add_new_item' => 'Yeni blog yazısı', 'edit_item' => 'Blog yazısını düzenle', 'all_items' => 'Tüm blog yazıları'],
		'menu_icon' => 'dashicons-welcome-write-blog',
		'has_archive' => 'blog',
		'rewrite' => ['slug' => 'blog', 'with_front' => false],
	]);
	register_post_type('haber', $common + [
		'labels' => ['name' => 'Bizden Haberler', 'singular_name' => 'Haber', 'add_new' => 'Yeni haber', 'add_new_item' => 'Yeni haber', 'edit_item' => 'Haberi düzenle', 'all_items' => 'Tüm haberler'],
		'menu_icon' => 'dashicons-megaphone',
		'has_archive' => 'bizden-haberler',
		'rewrite' => ['slug' => 'bizden-haberler', 'with_front' => false],
	]);
	// Sektör haberleri / röportaj / YouTube platformu için kategori altyapısı
	register_taxonomy('konu', ['blog', 'haber'], [
		'labels' => ['name' => 'Konular', 'singular_name' => 'Konu', 'add_new_item' => 'Yeni konu'],
		'public' => true, 'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
		'rewrite' => ['slug' => 'konu', 'with_front' => false],
	]);
});

// Yüklenen JPG/PNG görselleri otomatik WebP'ye çevir (sunucu destekliyorsa)
add_filter('image_editor_output_format', function ($f) {
	if (wp_image_editor_supports(['mime_type' => 'image/webp'])) {
		$f['image/jpeg'] = 'image/webp';
		$f['image/png'] = 'image/webp';
	}
	return $f;
});
add_filter('wp_editor_set_quality', function ($q, $mime) { return $mime === 'image/webp' ? 82 : $q; }, 10, 2);

// Arşivlerde sayfa başına yazı
add_action('pre_get_posts', function ($q) {
	if (!is_admin() && $q->is_main_query() && ($q->is_post_type_archive(['blog', 'haber']) || $q->is_tax('konu'))) $q->set('posts_per_page', 12);
});

add_action('wp_enqueue_scripts', function () {
	wp_enqueue_style('adv-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Manrope:wght@400;500;600;700&display=swap', [], null);
	wp_enqueue_style('adv-site', ADV_URI . '/assets/site.css', [], filemtime(ADV_DIR . '/assets/site.css'));
	wp_enqueue_script('adv-app', ADV_URI . '/assets/app.js', [], filemtime(ADV_DIR . '/assets/app.js'), ['strategy' => 'defer', 'in_footer' => false]);
	wp_dequeue_style('classic-theme-styles');
});
// Tema kendi stillerini kullanır; WordPress'in varsayılan renk/boyut ön ayar CSS'i gereksiz
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

add_filter('wp_resource_hints', function ($u, $rel) {
	if ($rel === 'preconnect') { $u[] = 'https://fonts.googleapis.com'; $u[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin']; }
	return $u;
}, 10, 2);

// Sayfayı hafif tutmak için gereksiz head çıktıları
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
add_filter('the_generator', '__return_empty_string');

// Tema etkinleştirilince: kalıcı bağlantılar, temel sayfalar, kurallar
add_action('after_switch_theme', 'adv_activate');
function adv_activate() {
	update_option('permalink_structure', '/%postname%');
	foreach (['hakkimizda' => 'Hakkımızda', 'iletisim' => 'İletişim'] as $slug => $title) {
		if (!get_page_by_path($slug)) {
			wp_insert_post([
				'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title,
				'post_content' => $slug === 'hakkimizda' ? '<p>AdaVira, Girne merkezli bir dijital ajanstır. Emlak, oto galeri, güzellik, spor, sağlık, e-ticaret, otel ve turizm işletmelerinin internette görünür olması ve daha fazla müşteriye ulaşması için çalışıyoruz.</p>' : '',
			]);
		}
	}
	flush_rewrite_rules();
}

// Yönetim paneli girişinde marka
add_action('login_enqueue_scripts', function () {
	echo '<style>body.login{background:#0B1A20}#login h1 a{background:none;width:auto;height:auto;text-indent:0;font:600 34px "Cormorant Garamond",Georgia,serif;color:#C9A86A}.login #backtoblog a,.login #nav a{color:#9FB0B5!important}</style>';
});
add_filter('login_headerurl', function () { return home_url('/'); });
add_filter('login_headertext', function () { return 'AdaVira'; });
