<?php
defined('ABSPATH') || exit;

function adv_settings_fields() {
	return [
		'İletişim' => [
			'email'      => ['Görünen e-posta', 'bilgi@adavira.com'],
			'wa_number'  => ['WhatsApp / telefon (ülke koduyla)', '905488570107'],
			'address'    => ['Adres', 'Ozanköy - Girne / KKTC'],
			'hours'      => ['Çalışma saatleri', 'Pazartesi – Cumartesi: 09:00 – 18:00'],
		],
		'İletişim formu e-postası (SMTP)' => [
			'mail_to'     => ['Form mesajlarının gideceği adres', 'bilgi@adavira.com'],
			'smtp_host'   => ['SMTP sunucusu (boşsa sunucunun kendi maili kullanılır)', ''],
			'smtp_port'   => ['SMTP port', '465'],
			'smtp_secure' => ['Şifreleme (ssl, tls veya none)', 'ssl'],
			'smtp_user'   => ['SMTP kullanıcı adı (gönderen adres)', ''],
			'smtp_pass'   => ['SMTP şifresi', '', 'password'],
		],
		'Bot doğrulaması (isteğe bağlı: Cloudflare Turnstile)' => [
			'turnstile_sitekey' => ['Turnstile site key (boşsa matematik sorusu sorulur)', ''],
			'turnstile_secret'  => ['Turnstile secret key', '', 'password'],
		],
		'Sosyal medya' => [
			'social_facebook'  => ['Facebook', ''],
			'social_instagram' => ['Instagram', ''],
			'social_x'         => ['X', ''],
			'social_youtube'   => ['YouTube', ''],
			'social_linkedin'  => ['LinkedIn', ''],
			'social_tiktok'    => ['TikTok', ''],
		],
	];
}

add_action('admin_menu', function () {
	add_theme_page('AdaVira Ayarları', 'AdaVira Ayarları', 'manage_options', 'adavira-ayarlar', 'adv_settings_page');
});

add_action('init', function () {
	// REST API'de (uygulama parolasıyla, yalnızca yöneticiler) şifre olmayan alanlar okunup yazılabilir
	$props = [];
	foreach (adv_settings_fields() as $g) foreach ($g as $k => $f) if (($f[2] ?? '') !== 'password') $props[$k] = ['type' => 'string'];
	register_setting('adavira_settings', 'adavira_settings', [
		'type' => 'object',
		'default' => [],
		'show_in_rest' => ['schema' => ['type' => 'object', 'properties' => $props, 'additionalProperties' => false]],
		'sanitize_callback' => function ($in) {
			$old = get_option('adavira_settings', []);
			$in = (array) $in;
			$out = [];
			foreach (adv_settings_fields() as $g) foreach ($g as $k => $f) {
				if (!array_key_exists($k, $in)) { $out[$k] = $old[$k] ?? ''; continue; } // gönderilmeyen alan korunur
				$v = trim(wp_unslash((string) $in[$k]));
				if (($f[2] ?? '') === 'password' && $v === '') $v = $old[$k] ?? ''; // boş bırakılırsa eski şifre korunur
				$out[$k] = strpos($k, 'social_') === 0 ? esc_url_raw($v) : sanitize_text_field($v);
			}
			return $out;
		},
	]);
	// REST çıktısında şifre alanları hiç yer almaz
	add_filter('rest_pre_get_setting', function ($v, $name) use ($props) {
		if ($name !== 'adavira_settings') return $v;
		$o = (array) get_option('adavira_settings', []);
		$r = [];
		foreach ($props as $k => $_) $r[$k] = (string) ($o[$k] ?? '');
		return $r;
	}, 10, 2);
});

function adv_settings_page() {
	$o = get_option('adavira_settings', []);
	echo '<div class="wrap"><h1>AdaVira Ayarları</h1><form method="post" action="options.php">';
	settings_fields('adavira_settings');
	foreach (adv_settings_fields() as $title => $g) {
		echo '<h2>' . esc_html($title) . '</h2><table class="form-table" role="presentation">';
		foreach ($g as $k => $f) {
			$pw = ($f[2] ?? '') === 'password';
			$c = 'ADAVIRA_' . strtoupper($k);
			$val = $pw ? '' : ($o[$k] ?? '');
			echo '<tr><th scope="row"><label for="adv-' . esc_attr($k) . '">' . esc_html($f[0]) . '</label></th><td>';
			if (defined($c)) { echo '<em>wp-config.php içinde ' . esc_html($c) . ' sabitiyle tanımlı.</em>'; }
			else echo '<input class="regular-text" id="adv-' . esc_attr($k) . '" type="' . ($pw ? 'password' : 'text') . '" name="adavira_settings[' . esc_attr($k) . ']" value="' . esc_attr($val) . '" placeholder="' . esc_attr($pw && !empty($o[$k]) ? '•••••• (kayıtlı; değiştirmek için yazın)' : $f[1]) . '" autocomplete="off">';
			echo '</td></tr>';
		}
		echo '</table>';
	}
	submit_button();
	echo '</form></div>';
}
