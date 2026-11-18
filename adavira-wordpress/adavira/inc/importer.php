<?php
/**
 * Eski PHP siteden (posts, pages, messages tabloları + public/uploads) WordPress'e aktarım.
 * Panel: Araçlar > AdaVira Aktarım      WP-CLI: wp adavira import --old=/var/www/vhosts/adavira.com/httpdocs
 * Tekrar çalıştırmak güvenlidir: daha önce aktarılan kayıtlar güncellenir, çoğaltılmaz.
 */
defined('ABSPATH') || exit;

class ADV_Importer {
	public $log = [];
	private $pdo;
	private $old;
	private $env = [];
	private $json = null;

	public function __construct($old_dir, $db = []) {
		$this->old = rtrim((string) $old_dir, '/');
		// Yedek klasörü (adavira-yedek.zip içeriği: yazilar.json + uploads) verildiyse veritabanı gerekmez
		$jf = $this->old . '/yazilar.json';
		if ($this->old && !is_file($jf) && is_file($this->old . '/yedek/yazilar.json')) { $this->old .= '/yedek'; $jf = $this->old . '/yazilar.json'; }
		if ($this->old && is_file($jf) && !is_file($this->old . '/.env')) {
			$this->json = json_decode((string) file_get_contents($jf), true);
			if (!is_array($this->json)) throw new Exception('yazilar.json okunamadı.');
			$this->say('Yedek dosyasından aktarılıyor: ' . count($this->json) . ' kayıt');
			return;
		}
		if ($this->old && is_file($this->old . '/.env')) $this->env = @parse_ini_file($this->old . '/.env') ?: [];
		$host = $db['host'] ?? '' ?: ($this->env['DB_HOST'] ?? 'localhost');
		$name = $db['name'] ?? '' ?: ($this->env['DB_NAME'] ?? '');
		$user = $db['user'] ?? '' ?: ($this->env['DB_USER'] ?? '');
		$pass = $db['pass'] ?? '' ?: ($this->env['DB_PASS'] ?? '');
		if (!$name) throw new Exception('Eski veritabanı adı bulunamadı. Eski site klasörünü (içinde .env olan) doğru yazın ya da veritabanı bilgilerini girin.');
		$this->pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4', $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
		$this->say('Eski veritabanına bağlanıldı: ' . $name);
	}

	private function say($s) { $this->log[] = $s; if (defined('WP_CLI') && WP_CLI) WP_CLI::log($s); }

	/** Eski tablo satırları (veritabanından ya da yedek JSON'dan, aynı biçimde) */
	private function rows($table) {
		if ($this->json === null) return $this->has_table($table) ? $this->pdo->query('SELECT * FROM `' . $table . '`' . ($table === 'pages' ? '' : ' ORDER BY id'))->fetchAll() : [];
		$dt = function ($v) { if (!$v) return ''; $t = strtotime($v); return $t ? gmdate('Y-m-d H:i:s', $t + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS)) : ''; };
		$out = [];
		foreach ($this->json as $i => $x) {
			$isp = ($x['tur'] ?? '') === 'sayfa';
			if (($table === 'pages') !== $isp || $table === 'messages') continue;
			$out[] = ['id' => 'j' . ($x['slug'] ?? $i), 'type' => $x['tur'] ?? 'blog', 'slug' => $x['slug'] ?? '', 'title' => $x['baslik'] ?? '', 'excerpt' => $x['ozet'] ?? '',
				'body' => $x['icerik_html'] ?? '', 'published' => 1, 'created_at' => $dt($x['yayin_tarihi'] ?? '') ?: current_time('mysql'), 'updated_at' => $dt($x['guncelleme_tarihi'] ?? ''),
				'image' => $x['kapak'] ?? '', 'image_alt' => $x['kapak_alt'] ?? ''];
		}
		return $out;
	}

	private function has_col($t, $c) {
		try { return (bool) $this->pdo->query("SHOW COLUMNS FROM `$t` LIKE " . $this->pdo->quote($c))->fetch(); } catch (Throwable $e) { return false; }
	}
	private function has_table($t) {
		try { return (bool) $this->pdo->query('SHOW TABLES LIKE ' . $this->pdo->quote($t))->fetch(); } catch (Throwable $e) { return false; }
	}

	private function uploads_src() { if (!$this->old) return ''; return $this->json !== null ? $this->old . '/uploads' : $this->old . '/public/uploads'; }

	/** public/uploads -> wp-content/uploads (aynı yıl/ay klasörleri) */
	public function copy_uploads() {
		$src = $this->uploads_src();
		if (!$src || !is_dir($src)) { $this->say('Uyarı: eski görsel klasörü bulunamadı (' . $src . '). Görseller atlandı.'); return; }
		$dst = wp_upload_dir()['basedir'];
		$n = 0;
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
		foreach ($it as $f) {
			if (!$f->isFile() || !preg_match('/\.(webp|jpe?g|png|gif)$/i', $f->getFilename())) continue;
			$rel = ltrim(substr($f->getPathname(), strlen($src)), '/');
			$to = $dst . '/' . $rel;
			if (is_file($to) && filesize($to) === $f->getSize()) continue;
			wp_mkdir_p(dirname($to));
			if (@copy($f->getPathname(), $to)) $n++;
		}
		$this->say("Görseller kopyalandı: $n yeni dosya.");
	}

	/** "/uploads/2026/11/ad-1600.webp" için ortam kütüphanesi kaydı (mevcut 400/800/og varyantlarıyla) */
	private function attachment($old_url, $alt, $parent) {
		if (!preg_match('#^/uploads/((\d{4}/\d{2})/(.+?))(?:-(\d+))?\.(webp|jpe?g|png)$#i', (string) $old_url, $m)) return 0;
		$up = wp_upload_dir();
		$rel = $m[1] . (isset($m[4]) && $m[4] !== '' ? '-' . $m[4] : '') . '.' . $m[5];
		$abs = $up['basedir'] . '/' . $rel;
		if (!is_file($abs)) { $this->say('  Görsel bulunamadı: ' . $old_url); return 0; }
		$ex = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_wp_attached_file', 'meta_value' => $rel]);
		if ($ex) { $id = $ex[0]; }
		else {
			$ft = wp_check_filetype($abs);
			$id = wp_insert_attachment(['post_mime_type' => $ft['type'] ?: 'image/webp', 'post_title' => $alt ?: basename($m[3]), 'post_status' => 'inherit', 'post_parent' => $parent], $abs, $parent);
			if (!$id || is_wp_error($id)) return 0;
		}
		[$w, $h] = @getimagesize($abs) ?: [0, 0];
		$meta = ['width' => $w, 'height' => $h, 'file' => $rel, 'filesize' => filesize($abs), 'sizes' => [], 'image_meta' => []];
		$base = $up['basedir'] . '/' . $m[2] . '/' . $m[3];
		foreach ([400, 800, 1024, 1200, 1600] as $sw) {
			$f = $base . '-' . $sw . '.' . $m[5];
			if ($sw >= $w || !is_file($f)) continue;
			[$a, $b] = @getimagesize($f) ?: [0, 0];
			if ($a) $meta['sizes']['adv-' . $sw] = ['file' => basename($f), 'width' => $a, 'height' => $b, 'mime-type' => 'image/' . ($m[5] === 'jpg' ? 'jpeg' : $m[5]), 'filesize' => filesize($f)];
		}
		if (is_file($base . '-og.jpg')) $meta['sizes']['adv-og'] = ['file' => basename($base . '-og.jpg'), 'width' => 1200, 'height' => 630, 'mime-type' => 'image/jpeg', 'filesize' => filesize($base . '-og.jpg')];
		wp_update_attachment_metadata($id, $meta);
		if ($alt) update_post_meta($id, '_wp_attachment_image_alt', $alt);
		return (int) $id;
	}

	private function fix_body($html) {
		$base = wp_make_link_relative(wp_upload_dir()['baseurl']);
		$html = preg_replace('#(["\'(])(?:https?://(?:www\.)?adavira\.com)?/uploads/#i', '$1' . $base . '/', (string) $html);
		return $html;
	}

	private function find($key) {
		$q = get_posts(['post_type' => ['blog', 'haber', 'page', 'adv_mesaj'], 'post_status' => ['publish', 'draft', 'private', 'pending', 'future', 'trash'], 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_adv_old_key', 'meta_value' => $key]);
		return $q ? (int) $q[0] : 0;
	}

	private function set_dates($id, $created, $updated) {
		global $wpdb;
		$updated = $updated ?: $created;
		$d = ['post_date' => $created, 'post_date_gmt' => get_gmt_from_date($created), 'post_modified' => $updated, 'post_modified_gmt' => get_gmt_from_date($updated)];
		$wpdb->update($wpdb->posts, $d, ['ID' => $id]);
		clean_post_cache($id);
	}

	public function posts() {
		$rows = $this->rows('posts');
		if (!$rows) { $this->say('Aktarılacak yazı bulunamadı.'); return; }
		$c = 0;
		foreach ($rows as $r) {
			$key = 'post:' . $r['type'] . ':' . $r['slug'];
			$type = $r['type'] === 'haber' ? 'haber' : 'blog';
			$arr = [
				'post_type' => $type, 'post_status' => !empty($r['published']) ? 'publish' : 'draft',
				'post_name' => $r['slug'], 'post_title' => $r['title'], 'post_excerpt' => (string) $r['excerpt'],
				'post_content' => $this->fix_body($r['body']), 'post_date' => $r['created_at'],
				'post_author' => get_current_user_id() ?: 1,
			];
			$id = $this->find($key);
			if ($id) { $arr['ID'] = $id; $id = wp_update_post(wp_slash($arr), true); }
			else $id = wp_insert_post(wp_slash($arr), true);
			if (is_wp_error($id)) { $this->say('  HATA (' . $r['slug'] . '): ' . $id->get_error_message()); continue; }
			update_post_meta($id, '_adv_old_key', $key);
			if (!empty($r['image'])) { $aid = $this->attachment($r['image'], $r['image_alt'] ?? '', $id); if ($aid) set_post_thumbnail($id, $aid); }
			$this->set_dates($id, $r['created_at'], $r['updated_at'] ?? '');
			$got = get_post_field('post_name', $id);
			$this->say('  ' . ($type === 'blog' ? 'Blog' : 'Haber') . ': ' . $r['title'] . ($got !== $r['slug'] ? '  (UYARI: adres ' . $got . ' oldu)' : ''));
			$c++;
		}
		$this->say("Yazılar aktarıldı: $c");
	}

	public function pages() {
		foreach ($this->rows('pages') as $r) {
			$p = get_page_by_path($r['slug']);
			$arr = ['post_type' => 'page', 'post_status' => 'publish', 'post_name' => $r['slug'], 'post_title' => $r['title'] ?: 'Hakkımızda', 'post_excerpt' => (string) $r['excerpt'], 'post_content' => $this->fix_body($r['body'])];
			if ($p) { $arr['ID'] = $p->ID; $id = wp_update_post(wp_slash($arr), true); } else $id = wp_insert_post(wp_slash($arr), true);
			if (is_wp_error($id)) { $this->say('  HATA sayfa ' . $r['slug']); continue; }
			update_post_meta($id, '_adv_old_key', 'page:' . $r['slug']);
			if (!empty($r['image'])) { $aid = $this->attachment($r['image'], $r['image_alt'] ?? '', $id); if ($aid) set_post_thumbnail($id, $aid); }
			$this->say('Sayfa aktarıldı: /' . $r['slug']);
		}
	}

	public function messages() {
		$c = 0;
		foreach ($this->rows('messages') as $r) {
			$key = 'msg:' . $r['id'];
			if ($this->find($key)) continue;
			$ph = $r['phone'] ?? '' ?: ($r['email'] ?? '');
			$sk = preg_match('/^Sektör: (.+)$/mu', $r['body'], $mm) ? trim($mm[1]) : '';
			$id = wp_insert_post(['post_type' => 'adv_mesaj', 'post_status' => 'private', 'post_title' => $r['name'] . ($ph ? ' · ' . $ph : ''), 'post_content' => $r['body'], 'post_date' => $r['created_at']], true);
			if (is_wp_error($id)) continue;
			update_post_meta($id, '_adv_old_key', $key); update_post_meta($id, '_phone', $ph); update_post_meta($id, '_sector', $sk);
			$this->set_dates($id, $r['created_at'], '');
			$c++;
		}
		$this->say("Form mesajları aktarıldı: $c");
	}

	/** .env içindeki WhatsApp, sosyal medya ve SMTP ayarları */
	public function settings() {
		if (!$this->env) { $this->say('.env bulunamadı, ayarlar aktarılmadı.'); return; }
		$o = get_option('adavira_settings', []);
		$map = ['WA_NUMBER' => 'wa_number', 'MAIL_TO' => 'mail_to', 'SMTP_HOST' => 'smtp_host', 'SMTP_PORT' => 'smtp_port', 'SMTP_SECURE' => 'smtp_secure', 'SMTP_USER' => 'smtp_user', 'SMTP_PASS' => 'smtp_pass', 'TURNSTILE_SITEKEY' => 'turnstile_sitekey', 'TURNSTILE_SECRET' => 'turnstile_secret',
			'SOCIAL_FACEBOOK' => 'social_facebook', 'SOCIAL_INSTAGRAM' => 'social_instagram', 'SOCIAL_X' => 'social_x', 'SOCIAL_YOUTUBE' => 'social_youtube', 'SOCIAL_LINKEDIN' => 'social_linkedin', 'SOCIAL_TIKTOK' => 'social_tiktok'];
		$n = 0;
		foreach ($map as $e => $k) if (isset($this->env[$e]) && $this->env[$e] !== '' && empty($o[$k])) { $o[$k] = (string) $this->env[$e]; $n++; }
		update_option('adavira_settings', $o);
		$this->say("Ayarlar aktarıldı: $n alan (WhatsApp, sosyal medya, SMTP). Var olan ayarların üzerine yazılmadı.");
	}

	public function run($what) {
		if (in_array('uploads', $what, true)) $this->copy_uploads();
		if (in_array('posts', $what, true)) $this->posts();
		if (in_array('pages', $what, true)) $this->pages();
		if (in_array('messages', $what, true)) $this->messages();
		if (in_array('settings', $what, true)) $this->settings();
		flush_rewrite_rules(false);
		$this->say('Bitti.');
	}
}

function adv_import_guess_dir() {
	foreach ([dirname(ABSPATH), ABSPATH . '..', ABSPATH . 'eski', dirname(ABSPATH) . '/httpdocs'] as $d) {
		$d = realpath($d);
		if ($d && is_file($d . '/.env') && is_dir($d . '/inc')) return $d;
	}
	return '';
}

add_action('admin_menu', function () {
	add_management_page('AdaVira Aktarım', 'AdaVira Aktarım', 'manage_options', 'adavira-aktarim', 'adv_import_page');
});

function adv_import_page() {
	if (!current_user_can('manage_options')) return;
	$log = []; $err = '';
	$dir = adv_import_guess_dir();
	if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && check_admin_referer('adv_import')) {
		$dir = sanitize_text_field(wp_unslash($_POST['old'] ?? ''));
		$what = array_map('sanitize_key', (array) ($_POST['what'] ?? []));
		@set_time_limit(600);
		try {
			$im = new ADV_Importer($dir, ['host' => sanitize_text_field(wp_unslash($_POST['db_host'] ?? '')), 'name' => sanitize_text_field(wp_unslash($_POST['db_name'] ?? '')), 'user' => sanitize_text_field(wp_unslash($_POST['db_user'] ?? '')), 'pass' => wp_unslash($_POST['db_pass'] ?? '')]);
			$im->run($what);
			$log = $im->log;
		} catch (Throwable $e) { $err = $e->getMessage(); }
	}
	echo '<div class="wrap"><h1>AdaVira: eski siteden aktarım</h1>';
	echo '<p>Eski PHP sitedeki Blog, Bizden Haberler, Hakkımızda, form mesajları, görseller ve ayarlar WordPress\'e aktarılır. Eski siteye dokunulmaz, yalnızca okunur. Tekrar çalıştırmak güvenlidir.</p>';
	if ($err) echo '<div class="notice notice-error"><p>' . esc_html($err) . '</p></div>';
	if ($log) echo '<div class="notice notice-success"><pre style="white-space:pre-wrap">' . esc_html(implode("\n", $log)) . '</pre></div>';
	echo '<form method="post">';
	wp_nonce_field('adv_import');
	echo '<table class="form-table"><tr><th><label for="old">Eski site klasörü</label></th><td><input class="large-text code" id="old" name="old" value="' . esc_attr($dir) . '" placeholder="/var/www/vhosts/adavira.com/httpdocs"><p class="description">İçinde <code>.env</code>, <code>inc</code> ve <code>public/uploads</code> olan klasör. Veritabanı bilgileri .env dosyasından okunur.<br>Eski site silindiyse: <code>adavira-yedek.zip</code> dosyasını sunucuda bir klasöre açıp o klasörün yolunu yazın (içinde <code>yazilar.json</code> ve <code>uploads</code> olmalı).</p></td></tr>';
	echo '<tr><th>Veritabanı (isteğe bağlı)</th><td><input name="db_host" placeholder="localhost"> <input name="db_name" placeholder="veritabanı adı"> <input name="db_user" placeholder="kullanıcı"> <input type="password" name="db_pass" placeholder="şifre" autocomplete="new-password"><p class="description">Boş bırakırsanız .env içindeki bilgiler kullanılır.</p></td></tr>';
	echo '<tr><th>Neler aktarılsın?</th><td>';
	foreach (['uploads' => 'Görseller (public/uploads)', 'posts' => 'Blog ve Bizden Haberler', 'pages' => 'Hakkımızda sayfası', 'messages' => 'İletişim formu mesajları', 'settings' => 'Ayarlar (.env: WhatsApp, sosyal medya, SMTP)'] as $k => $v)
		echo '<label style="display:block;margin:4px 0"><input type="checkbox" name="what[]" value="' . $k . '" checked> ' . esc_html($v) . '</label>';
	echo '</td></tr></table>';
	submit_button('Aktarımı başlat');
	echo '</form></div>';
}

if (defined('WP_CLI') && WP_CLI) {
	/**
	 * Eski AdaVira sitesinden aktarım.
	 * ## OPTIONS
	 * --old=<klasor>  : Eski site klasörü (.env burada)
	 * [--only=<liste>] : uploads,posts,pages,messages,settings
	 */
	WP_CLI::add_command('adavira import', function ($args, $a) {
		$what = isset($a['only']) ? explode(',', $a['only']) : ['uploads', 'posts', 'pages', 'messages', 'settings'];
		try { (new ADV_Importer($a['old'] ?? '', ['host' => $a['db-host'] ?? '', 'name' => $a['db-name'] ?? '', 'user' => $a['db-user'] ?? '', 'pass' => $a['db-pass'] ?? '']))->run($what); WP_CLI::success('Aktarım tamamlandı.'); }
		catch (Throwable $e) { WP_CLI::error($e->getMessage()); }
	});
}
