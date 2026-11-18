<?php
defined('ABSPATH') || exit;

function adv_e($s) { return esc_html((string) $s); }

/** Ayar oku (Görünüm > AdaVira Ayarları). wp-config.php içindeki ADAVIRA_<AD> sabiti önceliklidir. */
function adv_opt($k, $d = '') {
	$c = 'ADAVIRA_' . strtoupper($k);
	if (defined($c)) return constant($c);
	$o = get_option('adavira_settings', []);
	return isset($o[$k]) && $o[$k] !== '' ? $o[$k] : $d;
}

function adv_wa() {
	$n = preg_replace('/\D/', '', (string) adv_opt('wa_number'));
	return strlen($n) >= 11 ? $n : '905488570107';
}

function adv_phone_fmt($d) {
	$d = preg_replace('/\D/', '', $d);
	return strlen($d) === 12 && substr($d, 0, 2) === '90'
		? '+90 ' . substr($d, 2, 3) . ' ' . substr($d, 5, 3) . ' ' . substr($d, 8, 2) . ' ' . substr($d, 10, 2)
		: '+' . $d;
}

function adv_email() { return adv_opt('email', 'bilgi@adavira.com'); }

/** Satır içi SVG ikonlar (stroke). */
function adv_icon($k, $cls = 'ic') {
	$p = [
		'web'       => '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 8h18M8 21h8M12 18v3"/>',
		'social'    => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/>',
		'seo'       => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 11h6M11 8v6"/>',
		'ads'       => '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>',
		'brand'     => '<path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
		'video'     => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4V8z"/>',
		'drone'     => '<circle cx="5" cy="5" r="2.5"/><circle cx="19" cy="5" r="2.5"/><circle cx="5" cy="19" r="2.5"/><circle cx="19" cy="19" r="2.5"/><path d="M7 7l3 3M17 7l-3 3M7 17l3-3M17 17l-3-3"/><rect x="9.5" y="9.5" width="5" height="5" rx="1"/>',
		'ai'        => '<rect x="5" y="7" width="14" height="12" rx="3"/><path d="M12 3v4M9 12h.01M15 12h.01M9.5 16h5M2 12h3M19 12h3"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'check'     => '<path d="M20 6 9 17l-5-5"/>',
		'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
		'pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
	];
	if (!isset($p[$k])) return '';
	return '<svg class="' . esc_attr($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$k] . '</svg>';
}

function adv_service_icon($slug) {
	$m = [
		'web-tasarim-yazilim' => 'web', 'sosyal-medya-yonetimi' => 'social', 'seo-yerel-arama' => 'seo',
		'dijital-reklam' => 'ads', 'marka-kimligi' => 'brand', 'video-drone-produksiyon' => 'drone',
	];
	return adv_icon($m[$slug] ?? 'web');
}

/** Sosyal medya adresi; yalnızca alan adı olan (profil yolu boş) adresler yok sayılır. */
function adv_social_url($k) {
	$u = trim((string) adv_opt('social_' . $k));
	if (!$u || trim((string) parse_url($u, PHP_URL_PATH), '/') === '') return '';
	return $u;
}

function adv_social_html() {
	$ic = [
		'facebook'  => ['Facebook', '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>'],
		'instagram' => ['Instagram', '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'],
		'x'         => ['X', '<path d="M4 4l16 16M20 4L4 20"/>'],
		'youtube'   => ['YouTube', '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>'],
		'linkedin'  => ['LinkedIn', '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'],
		'tiktok'    => ['TikTok', '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>'],
	];
	$h = '';
	foreach ($ic as $k => $v) {
		$u = adv_social_url($k);
		if (!$u) continue;
		$h .= '<a href="' . esc_url($u) . '" target="_blank" rel="noopener nofollow" aria-label="' . $v[0] . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $v[1] . '</svg></a>';
	}
	return $h;
}

/** Breadcrumb HTML. $a = [[ad, url|''], ...] */
function adv_crumbs($a) {
	$h = '<nav class="bc" aria-label="Sayfa yolu"><a href="' . esc_url(home_url('/')) . '">Ana Sayfa</a>';
	foreach ($a as $c) $h .= '<span aria-hidden="true">/</span>' . ($c[1] ? '<a href="' . esc_url(home_url($c[1])) . '">' . adv_e($c[0]) . '</a>' : '<span>' . adv_e($c[0]) . '</span>');
	return $h . '</nav>';
}

/** Sayfa başlığı bandı */
function adv_page_head($title, $lead = '', $crumbs = [], $eyebrow = '') {
	$h = '<section class="phead"><div class="w">' . ($crumbs ? adv_crumbs($crumbs) : '');
	if ($eyebrow) $h .= '<p class="eyebrow">' . adv_e($eyebrow) . '</p>';
	$h .= '<h1>' . adv_e($title) . '</h1>';
	if ($lead) $h .= '<p class="lead">' . adv_e($lead) . '</p>';
	return $h . '</div></section>';
}

function adv_posts($type, $n = 3) {
	return get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => $n, 'orderby' => 'date', 'order' => 'DESC']);
}

function adv_post_card($p, $sizes = '(max-width:820px) 100vw, 360px') {
	$u = get_permalink($p);
	$th = has_post_thumbnail($p)
		? '<a class="pc-img" href="' . esc_url($u) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail($p, 'adv-800', ['sizes' => $sizes, 'loading' => 'lazy', 'decoding' => 'async', 'alt' => adv_thumb_alt($p)]) . '</a>'
		: '<a class="pc-img pc-ph" href="' . esc_url($u) . '" tabindex="-1" aria-hidden="true"><span>VA</span></a>';
	$lbl = $p->post_type === 'haber' ? 'Bizden Haberler' : 'Blog';
	return '<article class="pc">' . $th . '<div class="pc-b"><p class="pc-m"><span>' . $lbl . '</span><time datetime="' . esc_attr(get_the_date('c', $p)) . '">' . esc_html(get_the_date('d.m.Y', $p)) . '</time></p><h3><a href="' . esc_url($u) . '">' . adv_e(get_the_title($p)) . '</a></h3><p>' . adv_e(adv_excerpt($p, 140)) . '</p></div></article>';
}

function adv_post_grid($rows) {
	if (!$rows) return '<p class="muted">Yakında yeni içerikler eklenecek.</p>';
	$h = '<div class="pgrid">';
	foreach ($rows as $p) $h .= adv_post_card($p);
	return $h . '</div>';
}

function adv_excerpt($p, $n = 155) {
	$p = get_post($p);
	if (!$p) return '';
	$x = trim($p->post_excerpt);
	if ($x === '') $x = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($p->post_content))));
	return mb_strlen($x) > $n ? rtrim(mb_substr($x, 0, $n - 1)) . '…' : $x;
}

function adv_thumb_alt($p) {
	$id = get_post_thumbnail_id($p);
	$a = $id ? trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) : '';
	return $a !== '' ? $a : get_the_title($p);
}

/** Kapak görseli (öne çıkan görsel), LCP için öncelikli yüklenir. */
function adv_cover($p) {
	if (!has_post_thumbnail($p)) return '';
	return '<figure class="cover">' . get_the_post_thumbnail($p, 'full', ['sizes' => '(max-width:820px) 100vw, 880px', 'loading' => false, 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => adv_thumb_alt($p)]) . '</figure>';
}

function adv_sector_tabs() {
	$S = $GLOBALS['ADV_SECTORS']; $L = $GLOBALS['ADV_SLOG'];
	$t = ''; $pn = ''; $i = 0;
	foreach ($S as $s => $v) {
		$li = '';
		foreach ($v[3] as $m) $li .= '<li>' . adv_icon('check') . adv_e($m) . '</li>';
		$t .= '<button type="button" class="tab" role="tab" id="t-' . $s . '" aria-controls="p-' . $s . '" data-i="' . $s . '" aria-selected="' . ($i ? 'false' : 'true') . '"><span class="tn">' . sprintf('%02d', $i + 1) . '</span>' . adv_e(explode(':', $v[0])[0]) . '</button>';
		$pn .= '<div class="panel" role="tabpanel" id="p-' . $s . '" aria-labelledby="t-' . $s . '"' . ($i ? ' hidden' : '') . '><h3>' . adv_e($L[$s][0]) . '</h3><p>' . adv_e($L[$s][1]) . '</p><ul>' . $li . '</ul><a class="more" href="' . esc_url(home_url('/sektor/' . $s)) . '">' . adv_e($v[1]) . ' ' . adv_icon('arrow') . '</a></div>';
		$i++;
	}
	return '<div class="sectors"><div class="tabs" role="tablist" aria-label="Sektörler">' . $t . '</div><div class="panels">' . $pn . '</div></div>';
}

function adv_svc_grid() {
	$h = '<div class="svcs">'; $i = 1;
	foreach ($GLOBALS['ADV_SERVICES'] as $s => $v) {
		$h .= '<a class="svc" href="' . esc_url(home_url('/hizmet/' . $s)) . '"><span class="svc-n">' . sprintf('%02d', $i++) . '</span>' . adv_service_icon($s) . '<h3>' . adv_e($v[0]) . '</h3><p>' . adv_e($v[2]) . '</p><span class="svc-go">Detaylı bilgi ' . adv_icon('arrow') . '</span></a>';
	}
	return $h . '</div>';
}

function adv_steps() {
	$st = [
		['Tanışma ve analiz', 'İşletmenizi, rakiplerinizi ve mevcut dijital varlığınızı inceleriz.'],
		['Strateji ve teklif', 'Hedefe uygun hizmetleri, takvimi ve fiyatı yazılı sunarız.'],
		['Üretim', 'Site, içerik ve çekimler onayınızla hazırlanır.'],
		['Yayın ve raporlama', 'Yayına alır, sonuçları aylık raporlarız.'],
	];
	$h = '<ol class="steps">';
	foreach ($st as $s) $h .= '<li><h3>' . adv_e($s[0]) . '</h3><p>' . adv_e($s[1]) . '</p></li>';
	return $h . '</ol>';
}

function adv_cta_band($title = 'Projenizi konuşalım', $lead = 'Telefon numaranızı bırakın, aynı gün içinde sizi arayalım. Ücretsiz ön analizle başlıyoruz.') {
	$wa = adv_wa();
	return '<section class="cta"><div class="w cta-in"><div><p class="eyebrow">Ücretsiz ön analiz</p><h2>' . adv_e($title) . '</h2><p class="lead">' . adv_e($lead) . '</p></div><div class="actions"><a class="btn" href="' . esc_url(home_url('/iletisim')) . '">Teklif formu ' . adv_icon('arrow') . '</a><a class="btn ghost" href="https://wa.me/' . $wa . '" target="_blank" rel="noopener">WhatsApp ile yazın</a></div></div></section>';
}

/** Sayfa bağlamını (başlık, açıklama, breadcrumb, OG) header.php'ye iletir. */
function adv_ctx($k = null, $v = null) {
	static $c = [];
	if ($k === null) return $c;
	if (is_array($k)) { $c = array_merge($c, $k); return $c; }
	if (func_num_args() === 2) { $c[$k] = $v; return $v; }
	return $c[$k] ?? null;
}
