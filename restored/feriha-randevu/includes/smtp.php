<?php
/**
 * Dahili SMTP gönderimi + test e-postası.
 *
 * Paylaşımlı sunucularda WordPress'in varsayılan e-postaları çoğu zaman
 * spam'e düşer ya da hiç ulaşmaz. Bu modül, ayrı bir eklenti kurmadan
 * e-postaların Gmail/Outlook gibi gerçek bir posta sunucusu üzerinden
 * gönderilmesini sağlar. Ayarlar: Randevular › E-posta (SMTP).
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

/**
 * SMTP ayarlarını (varsayılanlarla birlikte) döndürür.
 *
 * @return array
 */
function fr_smtp() {
	$defaults = array(
		'enabled'    => 0,
		'host'       => '',
		'port'       => 587,
		'secure'     => 'tls', // none | ssl | tls
		'auth'       => 1,
		'user'       => '',
		'pass'       => '',
		'from_email' => '',
		'from_name'  => '',
	);
	$s = get_option( 'fr_smtp', array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), $defaults );
}

/**
 * Gönderen adresi/adı (belirtilmişse) uygula.
 */
add_filter(
	'wp_mail_from',
	function ( $email ) {
		$s = fr_smtp();
		return ( $s['enabled'] && is_email( $s['from_email'] ) ) ? $s['from_email'] : $email;
	}
);
add_filter(
	'wp_mail_from_name',
	function ( $name ) {
		$s = fr_smtp();
		return ( $s['enabled'] && $s['from_name'] ) ? $s['from_name'] : $name;
	}
);

/**
 * PHPMailer'ı SMTP için yapılandır.
 */
add_action(
	'phpmailer_init',
	function ( $phpmailer ) {
		$s = fr_smtp();
		if ( ! $s['enabled'] || '' === $s['host'] ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $s['host'];
		$phpmailer->Port       = (int) $s['port'];
		$phpmailer->SMTPAuth   = (bool) $s['auth'];
		$phpmailer->CharSet    = 'UTF-8';
		if ( $s['auth'] ) {
			$phpmailer->Username = $s['user'];
			$phpmailer->Password = $s['pass'];
		}
		if ( 'none' === $s['secure'] ) {
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		} else {
			$phpmailer->SMTPSecure = $s['secure']; // 'ssl' | 'tls'
		}
	}
);

/* Yönetim menüsü: Randevular › E-posta (SMTP). */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=' . FR_CPT, 'E-posta (SMTP)', 'E-posta (SMTP)', 'manage_options', 'fr-smtp', 'fr_render_smtp_page', 6 );
	}
);

/** SMTP ayar + test ekranı. */
function fr_render_smtp_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s   = fr_smtp();
	$set = fr_settings();
	?>
	<div class="wrap">
		<h1>E-posta (SMTP)</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>Kaydedildi.</p></div>
		<?php endif; ?>
		<?php
		if ( isset( $_GET['test'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ok  = '1' === $_GET['test']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$err = get_transient( 'fr_smtp_test_error' );
			delete_transient( 'fr_smtp_test_error' );
			?>
			<div class="notice <?php echo $ok ? 'notice-success' : 'notice-error'; ?>">
				<p>
				<?php if ( $ok ) : ?>
					Test e-postası gönderildi. Gelen kutunuzu (ve spam/gereksiz klasörünü) kontrol edin.
				<?php else : ?>
					Test e-postası <strong>gönderilemedi</strong>.
					<?php if ( $err ) : ?><br><code><?php echo esc_html( $err ); ?></code><?php endif; ?>
				<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<p style="font-size:15px;max-width:760px">Randevu bildirimlerinin size ve danışanlara ulaşması için e-postaların gerçek bir posta sunucusundan gönderilmesi gerekir. Aşağıya Gmail (veya başka bir) hesabınızın SMTP bilgilerini girin. <strong>Normal şifrenizi değil, uygulama şifresi (app password) kullanın.</strong></p>

		<div style="background:#f3faf5;border:1px solid #dcebe1;border-radius:14px;padding:16px 22px;margin:18px 0;max-width:760px">
			<h2 style="margin-top:0;color:#2F5A43">Gmail için hazır ayarlar</h2>
			<ol style="font-size:14px;line-height:1.7">
				<li>Google Hesabınızda <strong>2 Adımlı Doğrulama</strong> açık olmalı.</li>
				<li><a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a> adresinden bir <strong>uygulama şifresi</strong> oluşturun (16 haneli).</li>
				<li>Aşağıya şunları girin: Sunucu <code>smtp.gmail.com</code>, Port <code>587</code>, Şifreleme <code>TLS</code>, Kullanıcı adı Gmail adresiniz, Şifre ise oluşturduğunuz 16 haneli uygulama şifresi.</li>
			</ol>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fr_save_smtp">
			<?php wp_nonce_field( 'fr_save_smtp' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">SMTP'yi kullan</th>
					<td><label><input type="checkbox" name="fr_smtp[enabled]" value="1" <?php checked( (bool) $s['enabled'] ); ?>> E-postaları SMTP ile gönder</label></td></tr>
				<tr><th scope="row"><label for="fr-host">Sunucu (host)</label></th>
					<td><input id="fr-host" type="text" class="regular-text" name="fr_smtp[host]" value="<?php echo esc_attr( $s['host'] ); ?>" placeholder="smtp.gmail.com"></td></tr>
				<tr><th scope="row"><label for="fr-port">Port</label></th>
					<td><input id="fr-port" type="number" name="fr_smtp[port]" value="<?php echo (int) $s['port']; ?>" style="width:100px"> <span class="description">TLS için 587, SSL için 465</span></td></tr>
				<tr><th scope="row">Şifreleme</th>
					<td>
						<label><input type="radio" name="fr_smtp[secure]" value="tls" <?php checked( 'tls', $s['secure'] ); ?>> TLS (önerilen)</label>&nbsp;&nbsp;
						<label><input type="radio" name="fr_smtp[secure]" value="ssl" <?php checked( 'ssl', $s['secure'] ); ?>> SSL</label>&nbsp;&nbsp;
						<label><input type="radio" name="fr_smtp[secure]" value="none" <?php checked( 'none', $s['secure'] ); ?>> Yok</label>
					</td></tr>
				<tr><th scope="row">Kimlik doğrulama</th>
					<td><label><input type="checkbox" name="fr_smtp[auth]" value="1" <?php checked( (bool) $s['auth'] ); ?>> Kullanıcı adı ve şifre ile giriş yap</label></td></tr>
				<tr><th scope="row"><label for="fr-user">Kullanıcı adı</label></th>
					<td><input id="fr-user" type="text" class="regular-text" name="fr_smtp[user]" value="<?php echo esc_attr( $s['user'] ); ?>" placeholder="ornek@gmail.com" autocomplete="off"></td></tr>
				<tr><th scope="row"><label for="fr-pass">Şifre (uygulama şifresi)</label></th>
					<td>
						<input id="fr-pass" type="password" class="regular-text" name="fr_smtp[pass]" value="<?php echo esc_attr( $s['pass'] ); ?>" autocomplete="new-password">
						<p class="description">Gmail/Outlook normal şifreniz değil, <strong>uygulama şifresi</strong>. Kaydedildikten sonra güvenlik için gizlenmez ama kimseyle paylaşmayın.</p>
					</td></tr>
				<tr><th scope="row"><label for="fr-from">Gönderen e-posta</label></th>
					<td><input id="fr-from" type="email" class="regular-text" name="fr_smtp[from_email]" value="<?php echo esc_attr( $s['from_email'] ); ?>" placeholder="ornek@gmail.com"><p class="description">Genellikle kullanıcı adıyla aynı olmalıdır (Gmail farklı adresten göndermeye izin vermez).</p></td></tr>
				<tr><th scope="row"><label for="fr-fromname">Gönderen adı</label></th>
					<td><input id="fr-fromname" type="text" class="regular-text" name="fr_smtp[from_name]" value="<?php echo esc_attr( $s['from_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td></tr>
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>

		<hr>
		<h2>Test e-postası gönder</h2>
		<p>Ayarları kaydettikten sonra, gerçekten çalışıp çalışmadığını buradan test edin.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fr_smtp_test">
			<?php wp_nonce_field( 'fr_smtp_test' ); ?>
			<input type="email" name="to" class="regular-text" value="<?php echo esc_attr( $set['notify_email'] ); ?>" placeholder="alici@ornek.com">
			<?php submit_button( 'Test e-postası gönder', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

add_action(
	'admin_post_fr_save_smtp',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fr_save_smtp' );
		$in = isset( $_POST['fr_smtp'] ) ? wp_unslash( $_POST['fr_smtp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$s  = fr_smtp();

		$s['enabled']    = empty( $in['enabled'] ) ? 0 : 1;
		$s['host']       = sanitize_text_field( $in['host'] ?? '' );
		$s['port']       = min( 65535, max( 1, absint( $in['port'] ?? 587 ) ) );
		$s['secure']     = in_array( ( $in['secure'] ?? 'tls' ), array( 'none', 'ssl', 'tls' ), true ) ? $in['secure'] : 'tls';
		$s['auth']       = empty( $in['auth'] ) ? 0 : 1;
		$s['user']       = sanitize_text_field( $in['user'] ?? '' );
		// Şifre: boş gönderilirse mevcut korunur (yanlışlıkla silinmesin).
		$pass            = isset( $in['pass'] ) ? trim( (string) $in['pass'] ) : '';
		$s['pass']       = '' === $pass ? $s['pass'] : $pass;
		$s['from_email'] = sanitize_email( $in['from_email'] ?? '' );
		$s['from_name']  = sanitize_text_field( $in['from_name'] ?? '' );

		update_option( 'fr_smtp', $s );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-smtp&saved=1' ) );
		exit;
	}
);

add_action(
	'admin_post_fr_smtp_test',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fr_smtp_test' );
		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		if ( ! is_email( $to ) ) {
			$to = fr_settings()['notify_email'];
		}

		// Hata mesajını yakala.
		$capture = function ( $wp_error ) {
			set_transient( 'fr_smtp_test_error', $wp_error->get_error_message(), 60 );
		};
		add_action( 'wp_mail_failed', $capture );

		$ok = wp_mail(
			$to,
			'Test e-postası — ' . get_bloginfo( 'name' ),
			"Bu bir test e-postasıdır.\n\nBu mesajı aldıysanız randevu bildirim e-postalarınız doğru çalışıyor demektir.\n\n— " . get_bloginfo( 'name' )
		);

		remove_action( 'wp_mail_failed', $capture );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-smtp&test=' . ( $ok ? '1' : '0' ) ) );
		exit;
	}
);
