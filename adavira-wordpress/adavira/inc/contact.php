<?php
/**
 * İletişim formu: ad, telefon (ülke kodu + (5xx) xxx xx xx), sektör, mesaj.
 * Bot koruması: gizli alan + süre kontrolü + matematik sorusu (veya Cloudflare Turnstile).
 * Mesajlar panelde "Mesajlar" menüsünde saklanır ve SMTP ile e-posta gönderilir.
 */
defined('ABSPATH') || exit;

const ADV_CC = ['90' => 'Türkiye (+90)', '49' => 'Almanya (+49)', '44' => 'Birleşik Krallık (+44)', '7' => 'Rusya (+7)', '380' => 'Ukrayna (+380)', '98' => 'İran (+98)', '994' => 'Azerbaycan (+994)', '31' => 'Hollanda (+31)', '33' => 'Fransa (+33)', '1' => 'ABD / Kanada (+1)', '357' => 'Güney Kıbrıs (+357)'];
const ADV_SECTOR_OPTS = ['Emlak', 'Oto galeri', 'Güzellik merkezi', 'Spor salonu', 'Sağlık', 'E-ticaret', 'Otel ve turizm', 'Seyahat acentesi', 'Diğer'];

add_action('init', function () {
	register_post_type('adv_mesaj', [
		'labels' => ['name' => 'Mesajlar', 'singular_name' => 'Mesaj', 'edit_item' => 'Mesaj', 'all_items' => 'Tüm mesajlar', 'search_items' => 'Mesajlarda ara', 'not_found' => 'Henüz mesaj yok'],
		'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_position' => 26, 'menu_icon' => 'dashicons-email-alt',
		'supports' => ['title', 'editor'], 'capability_type' => 'post', 'capabilities' => ['create_posts' => 'do_not_allow'], 'map_meta_cap' => true,
	]);
});
add_filter('manage_adv_mesaj_posts_columns', function ($c) { return ['cb' => $c['cb'], 'title' => 'Ad', 'adv_phone' => 'Telefon', 'adv_sector' => 'Sektör', 'date' => 'Tarih']; });
add_action('manage_adv_mesaj_posts_custom_column', function ($col, $id) {
	if ($col === 'adv_phone') { $p = get_post_meta($id, '_phone', true); $d = preg_replace('/\D/', '', $p); echo esc_html($p) . ($d ? ' · <a href="https://wa.me/' . esc_attr($d) . '" target="_blank" rel="noopener">WhatsApp</a> · <a href="tel:+' . esc_attr($d) . '">Ara</a>' : ''); }
	if ($col === 'adv_sector') echo esc_html(get_post_meta($id, '_sector', true));
}, 10, 2);

/** İmzalı form jetonu: oturum (session) gerektirmez, önbellekle de uyumludur */
function adv_form_token() {
	$a = random_int(2, 9); $b = random_int(2, 9); $ts = time();
	$d = $a . '.' . $b . '.' . $ts;
	return [$a, $b, $d . '.' . hash_hmac('sha256', $d, wp_salt('nonce'))];
}
function adv_form_token_check($tok, $ans, $use_math) {
	$p = explode('.', (string) $tok);
	if (count($p) !== 4) return 'Hata oluştu, sayfayı yenileyip tekrar deneyin.';
	[$a, $b, $ts, $sig] = $p;
	if (!hash_equals(hash_hmac('sha256', "$a.$b.$ts", wp_salt('nonce')), $sig)) return 'Hata oluştu, sayfayı yenileyip tekrar deneyin.';
	$age = time() - (int) $ts;
	if ($age < 3) return 'Formu çok hızlı gönderdiniz. Lütfen tekrar deneyin.';
	if ($age > 6 * HOUR_IN_SECONDS) return 'Sayfanın süresi doldu, lütfen yenileyip tekrar deneyin.';
	if ($use_math && (int) $ans !== (int) $a + (int) $b) return 'Güvenlik sorusunun cevabı yanlış. Lütfen tekrar deneyin.';
	if (get_transient('adv_used_' . md5($sig))) return 'Bu form zaten gönderildi.';
	return '';
}

function adv_turnstile_on() { return adv_opt('turnstile_sitekey') && adv_opt('turnstile_secret'); }
function adv_turnstile_ok() {
	$r = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', ['timeout' => 6, 'body' => [
		'secret' => adv_opt('turnstile_secret'), 'response' => sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'] ?? '')), 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
	]]);
	if (is_wp_error($r)) return false;
	$j = json_decode(wp_remote_retrieve_body($r), true);
	return !empty($j['success']);
}

/** POST işler. Dönüş: [mesaj, başarılı mı, eski değerler] */
function adv_contact_handle() {
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['adv_contact'])) return ['', false, []];
	$in = wp_unslash($_POST);
	$old = ['name' => sanitize_text_field($in['adsoyad'] ?? ''), 'phone' => sanitize_text_field($in['phone'] ?? ''), 'cc' => sanitize_text_field($in['cc'] ?? '90'), 'sektor' => sanitize_text_field($in['sektor'] ?? ''), 'text' => sanitize_textarea_field($in['text'] ?? '')];
	if (!empty($in['website'])) return ['Hata oluştu, tekrar deneyin.', false, $old];
	$ip = md5($_SERVER['REMOTE_ADDR'] ?? '');
	if (get_transient('adv_rl_' . $ip)) return ['Kısa süre önce bir mesaj gönderdiniz. Lütfen 30 saniye sonra tekrar deneyin.', false, $old];
	$ts = adv_turnstile_on();
	if ($e = adv_form_token_check($in['t'] ?? '', $in['cap'] ?? '', !$ts)) return [$e, false, $old];
	if ($ts && !adv_turnstile_ok()) return ['Bot doğrulaması başarısız oldu. Lütfen tekrar deneyin.', false, $old];

	$n = mb_substr(trim($old['name']), 0, 120);
	$cc = isset(ADV_CC[$old['cc']]) ? $old['cc'] : '90';
	$ph = preg_replace('/\D/', '', $old['phone']);
	$sk = in_array($old['sektor'], ADV_SECTOR_OPTS, true) ? $old['sektor'] : '';
	$tx = mb_substr(trim($old['text']), 0, 5000);
	if ($cc === '90') { $ph = ltrim($ph, '0'); $valid = strlen($ph) === 10 && $ph[0] === '5'; }
	else $valid = strlen($ph) >= 6 && strlen($ph) <= 14;
	if (!$n || !$valid || mb_strlen($tx) < 5) return ['Lütfen adınızı, geçerli bir telefon numarasını ((5xx) xxx xx xx) ve mesajınızı yazın.', false, $old];

	$full = $cc === '90' ? sprintf('+90 (%s) %s %s %s', substr($ph, 0, 3), substr($ph, 3, 3), substr($ph, 6, 2), substr($ph, 8, 2)) : '+' . $cc . ' ' . $ph;
	$id = wp_insert_post(['post_type' => 'adv_mesaj', 'post_status' => 'private', 'post_title' => $n . ' · ' . $full, 'post_content' => ($sk ? "Sektör: $sk\n\n" : '') . $tx], true);
	if (!is_wp_error($id)) { update_post_meta($id, '_phone', $full); update_post_meta($id, '_sector', $sk); }

	$to = adv_opt('mail_to', adv_email());
	$body = "Ad: $n\nTelefon: $full\nWhatsApp: https://wa.me/$cc$ph\nSektör: $sk\n\n$tx\n\n— " . home_url('/iletisim');
	$sent = wp_mail($to, 'Yeni mesaj: ' . $n . ' ' . $full, $body);
	if (!$sent && !is_wp_error($id)) update_post_meta($id, '_mail_failed', 1);

	$p = explode('.', (string) ($in['t'] ?? ''));
	set_transient('adv_used_' . md5(end($p)), 1, 6 * HOUR_IN_SECONDS);
	set_transient('adv_rl_' . $ip, 1, 30);
	return ['Mesajınız alındı. En kısa sürede sizi arayacağız.', true, []];
}

function adv_contact_form($msg = '', $good = false, $old = []) {
	[$a, $b, $tok] = adv_form_token();
	$cc_sel = $old['cc'] ?? '90';
	$opts = '';
	foreach (ADV_CC as $k => $v) $opts .= '<option value="' . esc_attr($k) . '"' . selected($cc_sel, (string) $k, false) . '>' . esc_html($v) . '</option>';
	$so = '<option value="">Seçiniz</option>';
	foreach (ADV_SECTOR_OPTS as $s) $so .= '<option' . selected($old['sektor'] ?? '', $s, false) . '>' . esc_html($s) . '</option>';
	$cap = adv_turnstile_on()
		? '<div class="cf-turnstile" data-sitekey="' . esc_attr(adv_opt('turnstile_sitekey')) . '"></div><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
		: '<label>Güvenlik sorusu: ' . $a . ' + ' . $b . ' = ? <span class="req">*</span><input name="cap" inputmode="numeric" autocomplete="off" required placeholder="Cevabınız"></label>';
	$h = '<div class="fcard" id="form"><h2>Sizi arayalım</h2><p class="muted">Formu doldurun, aynı gün içinde dönüş yapalım.</p>';
	if ($msg) $h .= '<p class="' . ($good ? 'ok' : 'er') . '" role="alert">' . esc_html($msg) . '</p>';
	$h .= '<form method="post" action="' . esc_url(home_url('/iletisim')) . '#form" class="fm" novalidate>'
		. '<input type="hidden" name="adv_contact" value="1"><input type="hidden" name="t" value="' . esc_attr($tok) . '"><input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">'
		. '<label>Adınız soyadınız <span class="req">*</span><input name="adsoyad" required autocomplete="name" placeholder="Örn. Ahmet Yılmaz" value="' . esc_attr($old['name'] ?? '') . '"></label>'
		. '<label>Telefon numaranız <span class="req">*</span><span class="prow"><select name="cc" id="cc" aria-label="Ülke kodu">' . $opts . '</select><input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel-national" required placeholder="(5xx) xxx xx xx" value="' . esc_attr($old['phone'] ?? '') . '"></span></label>'
		. '<label>Sektörünüz<select name="sektor">' . $so . '</select></label>'
		. '<label>Mesajınız <span class="req">*</span><textarea name="text" required placeholder="Örn. Yeni web sitesi ve portföy çekimi yaptırmak istiyorum">' . esc_textarea($old['text'] ?? '') . '</textarea></label>'
		. $cap . '<button class="btn" type="submit">Gönder ' . adv_icon('arrow') . '</button><p class="fnote">Bilgileriniz yalnızca size dönüş yapmak için kullanılır.</p></form></div>';
	return $h;
}

/** SMTP: Plesk'teki e-posta kutusu */
add_action('phpmailer_init', function ($m) {
	$host = adv_opt('smtp_host');
	$user = adv_opt('smtp_user');
	if ($host) {
		$sec = adv_opt('smtp_secure', 'ssl');
		$m->isSMTP();
		$m->Host = $host;
		$m->Port = (int) adv_opt('smtp_port', 465);
		$m->SMTPAuth = (bool) $user;
		$m->Username = $user;
		$m->Password = adv_opt('smtp_pass');
		$m->SMTPSecure = $sec === 'none' ? '' : $sec;
		$m->SMTPAutoTLS = $sec !== 'none';
		if (in_array($host, ['localhost', '127.0.0.1'], true) || adv_opt('smtp_verify') === '0') {
			$m->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
		}
	}
	if ($user && is_email($user)) { $m->setFrom($user, 'AdaVira Web', false); $m->Sender = $user; }
	$m->CharSet = 'UTF-8';
});
add_action('wp_mail_failed', function ($e) { error_log('[AdaVira mail] ' . $e->get_error_message()); });
