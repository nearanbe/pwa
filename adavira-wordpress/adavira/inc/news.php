<?php
/**
 * Haber / medya özellikleri:
 * - Yazar sayfaları (/yazar/{ad-soyad}): kullanıcı adını göstermez
 * - Röportaj (konuk) ve video (YouTube) alanları + VideoObject şeması
 * - Google News site haritası (/news-sitemap.xml)
 * - Okuma süresi, paylaşım butonları, yazar kutusu
 */
defined('ABSPATH') || exit;

/* ---------- Yazarlar ---------- */

function adv_author_slug($uid) {
	$s = (string) get_user_meta($uid, 'adv_slug', true);
	if ($s === '') {
		$u = get_userdata($uid);
		$s = $u ? sanitize_title(remove_accents($u->display_name)) : '';
		// Görünen ad kullanıcı adıyla aynıysa kullanıcı adını adreste gösterme
		if (!$u || $s === '' || $u->display_name === $u->user_login) $s = 'yazar-' . (int) $uid;
	}
	return $s;
}
function adv_author_url($uid) { return home_url('/yazar/' . adv_author_slug($uid)); }
function adv_author_name($uid) {
	$u = get_userdata($uid);
	if (!$u) return 'AdaVira';
	return $u->display_name === $u->user_login ? 'AdaVira Editör' : $u->display_name;
}
function adv_author_by_slug($slug) {
	$q = get_users(['meta_key' => 'adv_slug', 'meta_value' => $slug, 'number' => 1, 'fields' => 'ID']);
	if ($q) return (int) $q[0];
	foreach (get_users(['fields' => 'ID', 'number' => 200]) as $id) if (adv_author_slug($id) === $slug) return (int) $id;
	return 0;
}
function adv_author_photo($uid, $size = 96) {
	$p = (string) get_user_meta($uid, 'adv_photo', true);
	if ($p) return '<img class="av" src="' . esc_url($p) . '" width="' . $size . '" height="' . $size . '" alt="' . esc_attr(adv_author_name($uid)) . '" loading="lazy" decoding="async">';
	$n = adv_author_name($uid);
	$ini = mb_strtoupper(mb_substr($n, 0, 1) . (strpos($n, ' ') ? mb_substr(strrchr($n, ' '), 1, 1) : ''));
	return '<span class="av av-i" style="width:' . $size . 'px;height:' . $size . 'px" aria-hidden="true">' . esc_html($ini) . '</span>';
}
add_filter('author_link', function ($l, $uid) { return adv_author_url($uid); }, 10, 2);

// Profil sayfasına ek alanlar
function adv_user_fields($u) {
	$f = ['adv_title' => ['Unvan (ör. Editör, Emlak Muhabiri)', 'text'], 'adv_photo' => ['Profil fotoğrafı adresi (Ortam kütüphanesinden kopyalayın)', 'url'], 'adv_slug' => ['Yazar sayfası adresi: adavira.com/yazar/…', 'text'], 'adv_linkedin' => ['LinkedIn profil adresi', 'url'], 'adv_x' => ['X (Twitter) profil adresi', 'url']];
	echo '<h2>AdaVira yazar bilgileri</h2><p>Haberlerde yazar adı olarak <strong>Görünen ad</strong>, biyografi olarak <strong>Biyografik bilgi</strong> alanı kullanılır.</p><table class="form-table">';
	foreach ($f as $k => $v) echo '<tr><th><label for="' . $k . '">' . esc_html($v[0]) . '</label></th><td><input class="regular-text" type="' . $v[1] . '" id="' . $k . '" name="' . $k . '" value="' . esc_attr(get_user_meta($u->ID, $k, true)) . '"' . ($k === 'adv_slug' ? ' placeholder="' . esc_attr(adv_author_slug($u->ID)) . '"' : '') . '></td></tr>';
	echo '</table>';
}
add_action('show_user_profile', 'adv_user_fields');
add_action('edit_user_profile', 'adv_user_fields');
function adv_user_save($uid) {
	if (!current_user_can('edit_user', $uid)) return;
	foreach (['adv_title', 'adv_photo', 'adv_slug', 'adv_linkedin', 'adv_x'] as $k) {
		if (!isset($_POST[$k])) continue;
		$v = trim(wp_unslash($_POST[$k]));
		$v = in_array($k, ['adv_photo', 'adv_linkedin', 'adv_x'], true) ? esc_url_raw($v) : ($k === 'adv_slug' ? sanitize_title($v) : sanitize_text_field($v));
		update_user_meta($uid, $k, $v);
	}
}
add_action('personal_options_update', 'adv_user_save');
add_action('edit_user_profile_update', 'adv_user_save');

function adv_author_box($uid) {
	$bio = trim((string) get_the_author_meta('description', $uid));
	$t = (string) get_user_meta($uid, 'adv_title', true);
	return '<aside class="abox">' . adv_author_photo($uid, 72) . '<div><p class="abox-n"><a href="' . esc_url(adv_author_url($uid)) . '">' . esc_html(adv_author_name($uid)) . '</a>' . ($t ? '<span>' . esc_html($t) . '</span>' : '') . '</p>' . ($bio ? '<p>' . esc_html($bio) . '</p>' : '') . '</div></aside>';
}

function adv_person_schema($uid) {
	$s = ['@type' => 'Person', 'name' => adv_author_name($uid), 'url' => adv_author_url($uid)];
	if ($t = get_user_meta($uid, 'adv_title', true)) $s['jobTitle'] = $t;
	$same = array_values(array_filter([get_user_meta($uid, 'adv_linkedin', true), get_user_meta($uid, 'adv_x', true), get_the_author_meta('user_url', $uid)]));
	if ($same) $s['sameAs'] = $same;
	if ($p = get_user_meta($uid, 'adv_photo', true)) $s['image'] = $p;
	return $s;
}

/* ---------- Röportaj / video alanları ---------- */

const ADV_META = ['adv_youtube' => 'YouTube video adresi', 'adv_guest' => 'Röportaj konuğu (ad soyad)', 'adv_guest_title' => 'Konuğun unvanı / kurumu', 'adv_featured' => 'Manşet (1 = ana sayfada manşette göster)'];

add_action('init', function () {
	foreach (['blog', 'haber'] as $pt) foreach (ADV_META as $k => $_) {
		register_post_meta($pt, $k, ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => $k === 'adv_youtube' ? 'esc_url_raw' : 'sanitize_text_field', 'auth_callback' => function () { return current_user_can('edit_posts'); }]);
	}
});
add_action('add_meta_boxes', function () {
	add_meta_box('adv_media', 'Haber / Röportaj / Video', function ($p) {
		wp_nonce_field('adv_media', 'adv_media_n');
		foreach (ADV_META as $k => $l) {
			$v = get_post_meta($p->ID, $k, true);
			if ($k === 'adv_featured') { echo '<p><label><input type="checkbox" name="adv_featured" value="1"' . checked($v, '1', false) . '> Ana sayfada manşette göster</label></p>'; continue; }
			echo '<p><label for="' . $k . '"><strong>' . esc_html($l) . '</strong></label><br><input class="widefat" id="' . $k . '" name="' . $k . '" value="' . esc_attr($v) . '"></p>';
		}
		echo '<p class="description">YouTube adresi girilirse video yazının başında gösterilir ve Google video sonuçları için işaretlenir. Konuk girilirse yazı röportaj olarak gösterilir.</p>';
	}, ['blog', 'haber'], 'side');
});
add_action('save_post', function ($id) {
	if (!isset($_POST['adv_media_n']) || !wp_verify_nonce($_POST['adv_media_n'], 'adv_media') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $id)) return;
	foreach (ADV_META as $k => $_) {
		$v = $k === 'adv_featured' ? (empty($_POST[$k]) ? '' : '1') : trim(wp_unslash($_POST[$k] ?? ''));
		$v === '' ? delete_post_meta($id, $k) : update_post_meta($id, $k, $k === 'adv_youtube' ? esc_url_raw($v) : sanitize_text_field($v));
	}
});

function adv_yt_id($url) {
	return preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~', (string) $url, $m) ? $m[1] : '';
}
function adv_video_html($p) {
	$id = adv_yt_id(get_post_meta($p->ID, 'adv_youtube', true));
	if (!$id) return '';
	// Hafif yükleme: önce kapak görseli, tıklanınca video (sayfa hızı için)
	return '<figure class="vid"><a class="vid-in" href="https://www.youtube.com/watch?v=' . esc_attr($id) . '" data-yt="' . esc_attr($id) . '" target="_blank" rel="noopener"><img src="https://i.ytimg.com/vi/' . esc_attr($id) . '/hqdefault.jpg" alt="' . esc_attr(get_the_title($p)) . ' videosu" width="480" height="360" loading="eager" decoding="async"><span class="vid-play" aria-hidden="true"></span><span class="screen-reader-text">Videoyu oynat</span></a></figure>';
}
function adv_guest_html($p) {
	$g = get_post_meta($p->ID, 'adv_guest', true);
	if (!$g) return '';
	$t = get_post_meta($p->ID, 'adv_guest_title', true);
	return '<aside class="guest"><span class="eyebrow">Röportaj konuğu</span><p><strong>' . esc_html($g) . '</strong>' . ($t ? '<span>' . esc_html($t) . '</span>' : '') . '</p></aside>';
}
function adv_kind($p) {
	if (adv_yt_id(get_post_meta($p->ID, 'adv_youtube', true))) return 'Video';
	if (get_post_meta($p->ID, 'adv_guest', true)) return 'Röportaj';
	$t = get_the_terms($p, 'konu');
	if ($t && !is_wp_error($t)) return $t[0]->name;
	return $p->post_type === 'haber' ? 'Bizden Haberler' : 'Blog';
}

/* ---------- Okuma süresi ve paylaşım ---------- */

function adv_read_time($p) {
	$w = count(preg_split('/\s+/u', trim(wp_strip_all_tags($p->post_content)), -1, PREG_SPLIT_NO_EMPTY));
	return max(1, (int) round($w / 200));
}
function adv_share($p) {
	$u = rawurlencode(get_permalink($p)); $t = rawurlencode(get_the_title($p));
	$l = [
		'WhatsApp' => 'https://wa.me/?text=' . $t . '%20' . $u,
		'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u,
		'X' => 'https://x.com/intent/post?url=' . $u . '&text=' . $t,
		'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u,
	];
	$h = '<div class="share"><span>Paylaş</span>';
	foreach ($l as $n => $href) $h .= '<a href="' . esc_url($href) . '" target="_blank" rel="noopener nofollow">' . $n . '</a>';
	return $h . '<button type="button" class="copy" data-url="' . esc_attr(get_permalink($p)) . '">Bağlantıyı kopyala</button></div>';
}

/* ---------- Ana sayfa: son haberler ve manşet ---------- */

function adv_latest_news($n = 5) {
	return get_posts(['post_type' => ['haber', 'blog'], 'numberposts' => $n, 'post_status' => 'publish']);
}
function adv_headline() {
	$f = get_posts(['post_type' => ['haber', 'blog'], 'numberposts' => 1, 'meta_key' => 'adv_featured', 'meta_value' => '1']);
	if ($f) return $f[0];
	$h = get_posts(['post_type' => 'haber', 'numberposts' => 1]);
	return $h ? $h[0] : null;
}
function adv_news_bar() {
	$r = adv_latest_news(5);
	if (!$r) return '';
	$h = '<div class="nbar"><div class="w nbar-in"><span class="nbar-t">Son haberler</span><ul>';
	foreach ($r as $p) $h .= '<li><a href="' . esc_url(get_permalink($p)) . '"><time datetime="' . esc_attr(get_the_date('c', $p)) . '">' . esc_html(get_the_date('d.m', $p)) . '</time>' . esc_html(get_the_title($p)) . '</a></li>';
	return $h . '</ul></div></div>';
}
function adv_news_block() {
	$m = adv_headline();
	if (!$m) return '';
	$rest = get_posts(['post_type' => ['haber', 'blog'], 'numberposts' => 4, 'post__not_in' => [$m->ID]]);
	$u = get_permalink($m);
	$h = '<section class="sec news"><div class="w"><div class="sh"><div><p class="eyebrow">Gündem</p><h2>Sektörden haberler</h2></div><a class="more" href="' . esc_url(home_url('/bizden-haberler')) . '">Tüm haberler ' . adv_icon('arrow') . '</a></div><div class="nblk">';
	$h .= '<article class="lead-n"><a class="ln-img" href="' . esc_url($u) . '" tabindex="-1" aria-hidden="true">' . (has_post_thumbnail($m) ? get_the_post_thumbnail($m, 'adv-1600', ['sizes' => '(max-width:1024px) 100vw, 680px', 'loading' => 'lazy', 'alt' => adv_thumb_alt($m)]) : '<span class="pc-ph"><img src="' . esc_url(ADV_URI . '/assets/icon-192.png') . '" width="120" height="120" alt="" loading="lazy"></span>') . '</a><div><p class="pc-m"><span>' . esc_html(adv_kind($m)) . '</span><time datetime="' . esc_attr(get_the_date('c', $m)) . '">' . esc_html(get_the_date('d.m.Y', $m)) . '</time></p><h3><a href="' . esc_url($u) . '">' . esc_html(get_the_title($m)) . '</a></h3><p>' . esc_html(adv_excerpt($m, 180)) . '</p></div></article><ul class="nlist">';
	foreach ($rest as $p) $h .= '<li><p class="pc-m"><span>' . esc_html(adv_kind($p)) . '</span><time datetime="' . esc_attr(get_the_date('c', $p)) . '">' . esc_html(get_the_date('d.m.Y', $p)) . '</time></p><a href="' . esc_url(get_permalink($p)) . '">' . esc_html(get_the_title($p)) . '</a></li>';
	return $h . '</ul></div></div></section>';
}

/* ---------- Adresler: /yazar/{slug}, /news-sitemap.xml ---------- */

add_action('init', function () {
	add_rewrite_rule('^yazar/([^/]+)/?$', 'index.php?adv_view=yazar&adv_slug=$matches[1]', 'top');
	add_rewrite_rule('^yazar/([^/]+)/page/(\d+)/?$', 'index.php?adv_view=yazar&adv_slug=$matches[1]&paged=$matches[2]', 'top');
	add_rewrite_rule('^news-sitemap\.xml$', 'index.php?adv_view=newsmap', 'top');
}, 9);

function adv_news_sitemap() {
	status_header(200);
	header('Content-Type: application/xml; charset=utf-8');
	$r = get_posts(['post_type' => ['haber', 'blog'], 'numberposts' => 1000, 'date_query' => [['after' => '2 days ago', 'column' => 'post_date_gmt']]]);
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
	foreach ($r as $p) echo '<url><loc>' . esc_url(get_permalink($p)) . '</loc><news:news><news:publication><news:name>AdaVira</news:name><news:language>tr</news:language></news:publication><news:publication_date>' . esc_html(get_post_time('c', true, $p)) . '</news:publication_date><news:title>' . esc_html(get_the_title($p)) . '</news:title></news:news></url>';
	echo '</urlset>';
}

// RSS: kapak görselini akışa ekle
add_filter('the_excerpt_rss', 'adv_rss_img');
add_filter('the_content_feed', 'adv_rss_img');
function adv_rss_img($c) {
	global $post;
	return $post && has_post_thumbnail($post) ? '<p>' . get_the_post_thumbnail($post, 'adv-800') . '</p>' . $c : $c;
}
// Ana RSS akışında blog ve haberler yer alsın
add_action('pre_get_posts', function ($q) {
	if ($q->is_main_query() && $q->is_feed() && !$q->get('post_type')) $q->set('post_type', ['blog', 'haber']);
});

/* ---------- Sosyal medya paylaşım görseli (Instagram JPEG ister, WebP kabul etmez) ---------- */

/** Yazının kapak görselinden 1440x810 JPEG üretir: uploads/sosyal/{yazı}-{görsel}.jpg */
function adv_social_jpg($post, $create = true) {
	$post = get_post($post);
	$tid = $post ? (int) get_post_thumbnail_id($post) : 0;
	if (!$tid) return '';
	$up = wp_upload_dir();
	$rel = 'sosyal/' . $post->ID . '-' . $tid . '.jpg';
	$abs = $up['basedir'] . '/' . $rel;
	if (!is_file($abs)) {
		if (!$create) return '';
		$src = get_attached_file($tid);
		if (!$src || !is_file($src)) return '';
		$ed = wp_get_image_editor($src);
		if (is_wp_error($ed)) return '';
		$sz = $ed->get_size();
		// Instagram oranı 4:5 ile 1.91:1 arasında olmalı; 16:9'a ortadan kırp
		$w = $sz['width']; $h = $sz['height']; $tw = $w; $th = (int) round($w * 9 / 16);
		if ($th > $h) { $th = $h; $tw = (int) round($h * 16 / 9); }
		$ed->crop((int) (($w - $tw) / 2), (int) (($h - $th) / 2), $tw, $th, min(1440, $tw), (int) round(min(1440, $tw) * 9 / 16));
		$ed->set_quality(88);
		wp_mkdir_p(dirname($abs));
		$GLOBALS['adv_keep_jpeg'] = true; // WebP dönüşümünü bu kayıt için kapat
		$r = $ed->save($abs, 'image/jpeg');
		$GLOBALS['adv_keep_jpeg'] = false;
		if (is_wp_error($r) || !is_file($abs)) return '';
	}
	return $up['baseurl'] . '/' . $rel;
}
add_filter('image_editor_output_format', function ($f) {
	if (!empty($GLOBALS['adv_keep_jpeg'])) { unset($f['image/jpeg'], $f['image/png'], $f['image/webp']); $f['image/webp'] = 'image/jpeg'; }
	return $f;
}, 99);

// Yayınlanınca görseli hemen hazırla (n8n akışı okuduğunda hazır olsun)
add_action('transition_post_status', function ($new, $old, $post) {
	if ($new === 'publish' && in_array($post->post_type, ['blog', 'haber'], true)) adv_social_jpg($post);
}, 20, 3);
add_action('save_post', function ($id, $post) {
	if ($post->post_status === 'publish' && in_array($post->post_type, ['blog', 'haber'], true)) adv_social_jpg($post);
}, 30, 2);

// RSS: paylaşım görseli (enclosure), konu/hashtag ve kısa özet
add_action('rss2_item', function () {
	$p = get_post();
	if (!$p || !in_array($p->post_type, ['blog', 'haber'], true)) return;
	$u = adv_social_jpg($p);
	if ($u) {
		$f = wp_upload_dir()['basedir'] . '/sosyal/' . basename($u);
		echo '<enclosure url="' . esc_url($u) . '" length="' . (int) @filesize($f) . '" type="image/jpeg" />' . "\n";
	}
	$terms = get_the_terms($p, 'konu');
	if ($terms && !is_wp_error($terms)) foreach ($terms as $t) echo '<category><![CDATA[' . $t->name . ']]></category>' . "\n";
	echo '<category><![CDATA[' . ($p->post_type === 'haber' ? 'Bizden Haberler' : 'Blog') . ']]></category>' . "\n";
});
