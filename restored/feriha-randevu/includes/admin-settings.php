<?php
/**
 * Randevu ayarları + Apple Takvim bağlantısı.
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=' . FR_CPT, 'Randevu ayarları', 'Ayarlar ve Apple Takvim', 'manage_options', 'fr-settings', 'fr_render_settings_page', 5 );
	}
);

/** iCal abonelik adresi (https). */
function fr_ical_url() {
	$token = fr_settings()['ical_token'];
	return $token ? add_query_arg( 'fr_ical', $token, home_url( '/' ) ) : '';
}

/** Ayar ekranı. */
function fr_render_settings_page() {
	$s = fr_settings();
	if ( empty( $s['ical_token'] ) ) {
		$s['ical_token'] = wp_generate_password( 32, false, false );
		update_option( 'fr_settings', $s );
	}
	$https  = fr_ical_url();
	$webcal = preg_replace( '#^https?://#', 'webcal://', $https );
	?>
	<div class="wrap">
		<h1>Randevu ayarları</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>Kaydedildi.</p></div>
		<?php endif; ?>

		<div style="background:#fff;border:1px solid #dcebe1;border-radius:14px;padding:20px 24px;margin:20px 0;max-width:900px">
			<h2 style="margin-top:0;color:#2F5A43">Apple Takvim (iPhone / Mac) bağlantısı</h2>
			<p style="font-size:14px">Randevularınızın iPhone ve Mac takviminizde otomatik görünmesi için <strong>bir kez</strong> abone olmanız yeterli:</p>
			<p><a class="button button-primary button-hero" href="<?php echo esc_url( $webcal, array( 'webcal', 'https', 'http' ) ); ?>">iPhone / Mac'te Takvime ekle</a></p>
			<p style="font-size:14px">Bu düğmeyi iPhone'unuzda ya da Mac'inizde açın ve "Abone ol"a dokunun. Düğme çalışmazsa: iPhone'da <em>Ayarlar › Takvim › Hesaplar › Hesap Ekle › Diğer › Abone Olunan Takvim Ekle</em> yolunu izleyip aşağıdaki adresi yapıştırın.</p>
			<p><input type="text" class="large-text code" readonly value="<?php echo esc_attr( $https ); ?>" onclick="this.select()"></p>
			<p class="description">Bu adres gizlidir; randevu bilgileri içerdiği için kimseyle paylaşmayın. Paylaşıldığını düşünüyorsanız aşağıdan yenileyin (sonra takvime yeniden abone olmanız gerekir). Apple Takvim abone olunan takvimleri belirli aralıklarla yeniler; Mac'te takvime sağ tıklayıp "Yenile" süresini "Her 5 dakikada" yapabilirsiniz.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Gizli adres yenilensin mi? Eski adres çalışmayı durduracak.')">
				<input type="hidden" name="action" value="fr_reset_token">
				<?php wp_nonce_field( 'fr_reset_token' ); ?>
				<button class="button">Gizli adresi yenile</button>
			</form>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fr_save_settings">
			<?php wp_nonce_field( 'fr_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="fr-email">Bildirim e-postası</label></th>
					<td><input id="fr-email" type="email" class="regular-text" name="fr_s[notify_email]" value="<?php echo esc_attr( $s['notify_email'] ); ?>"><p class="description">Yeni randevu geldiğinde bu adrese e-posta (takvime eklenebilir dosyayla) gönderilir.</p></td></tr>
				<tr><th scope="row">Onay şekli</th>
					<td><label><input type="radio" name="fr_s[auto_confirm]" value="0" <?php checked( ! $s['auto_confirm'] ); ?>> Randevuları ben onaylayayım (önerilen)</label><br>
						<label><input type="radio" name="fr_s[auto_confirm]" value="1" <?php checked( (bool) $s['auto_confirm'] ); ?>> Otomatik onaylansın</label></td></tr>
				<tr><th scope="row"><label for="fr-len">Seans süresi (dakika)</label></th>
					<td><input id="fr-len" type="number" min="15" max="240" step="5" name="fr_s[session_minutes]" value="<?php echo (int) $s['session_minutes']; ?>"></td></tr>
				<tr><th scope="row"><label for="fr-step">Randevular kaç dakikada bir başlasın?</label></th>
					<td><input id="fr-step" type="number" min="15" max="240" step="5" name="fr_s[step_minutes]" value="<?php echo (int) $s['step_minutes']; ?>"><p class="description">Örnek: 50 dk seans + 10 dk ara için 60 yazın.</p></td></tr>
				<tr><th scope="row"><label for="fr-notice">En az kaç saat önceden?</label></th>
					<td><input id="fr-notice" type="number" min="0" max="168" name="fr_s[min_notice]" value="<?php echo (int) $s['min_notice']; ?>"> saat</td></tr>
				<tr><th scope="row"><label for="fr-horizon">En fazla kaç gün ileriye?</label></th>
					<td><input id="fr-horizon" type="number" min="7" max="365" name="fr_s[horizon_days]" value="<?php echo (int) $s['horizon_days']; ?>"> gün</td></tr>
				<tr><th scope="row"><label for="fr-online">Online görüşme notu</label></th>
					<td><textarea id="fr-online" class="large-text" rows="2" name="fr_s[online_note]"><?php echo esc_textarea( $s['online_note'] ); ?></textarea><p class="description">Online randevu onaylandığında danışana giden e-postada yer alır (ör. "Görüşme Zoom/WhatsApp görüntülü arama ile yapılacaktır").</p></td></tr>
				<tr><th scope="row"><label for="fr-office">Yüz yüze görüşme notu</label></th>
					<td><textarea id="fr-office" class="large-text" rows="2" name="fr_s[office_note]"><?php echo esc_textarea( $s['office_note'] ); ?></textarea><p class="description">Örn. adres tarifi, otopark bilgisi.</p></td></tr>
				<tr><th scope="row"><label for="fr-extra">Ek hizmetler</label></th>
					<td><textarea id="fr-extra" class="large-text" rows="3" name="fr_s[services_extra]"><?php echo esc_textarea( $s['services_extra'] ); ?></textarea><p class="description">Formdaki hizmet listesine eklemek istedikleriniz (her satıra bir tane).</p></td></tr>
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>

		<h2>E-postalar ulaşmıyor mu?</h2>
		<p>Bazı sunucularda e-postalar gereksiz (spam) klasörüne düşer ya da hiç ulaşmaz. Bunu çözmek için <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-smtp' ) ); ?>"><strong>E-posta (SMTP)</strong></a> ekranından Gmail/Outlook hesabınızı bağlayın ve test e-postası gönderin. Ayrı bir eklenti kurmanıza gerek yoktur.</p>
	</div>
	<?php
}

add_action(
	'admin_post_fr_save_settings',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fr_settings' );
		$in = isset( $_POST['fr_s'] ) ? wp_unslash( $_POST['fr_s'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$s  = fr_settings();
		$s['notify_email']    = sanitize_email( $in['notify_email'] ?? '' );
		$s['auto_confirm']    = empty( $in['auto_confirm'] ) ? 0 : 1;
		$s['session_minutes'] = min( 240, max( 15, absint( $in['session_minutes'] ?? 50 ) ) );
		$s['step_minutes']    = min( 240, max( 15, absint( $in['step_minutes'] ?? 60 ) ) );
		$s['min_notice']      = min( 168, absint( $in['min_notice'] ?? 12 ) );
		$s['horizon_days']    = min( 365, max( 7, absint( $in['horizon_days'] ?? 60 ) ) );
		$s['online_note']     = sanitize_textarea_field( $in['online_note'] ?? '' );
		$s['office_note']     = sanitize_textarea_field( $in['office_note'] ?? '' );
		$s['services_extra']  = sanitize_textarea_field( $in['services_extra'] ?? '' );
		update_option( 'fr_settings', $s );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-settings&saved=1' ) );
		exit;
	}
);

add_action(
	'admin_post_fr_reset_token',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fr_reset_token' );
		$s               = fr_settings();
		$s['ical_token'] = wp_generate_password( 32, false, false );
		update_option( 'fr_settings', $s );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-settings&saved=1' ) );
		exit;
	}
);
