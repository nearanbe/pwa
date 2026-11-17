<?php
/**
 * Ön yüz randevu formu: [feriha_randevu]
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_register_style( 'fr-booking', FR_URL . 'assets/booking.css', array(), FR_VERSION );
		wp_register_script( 'fr-booking', FR_URL . 'assets/booking.js', array(), FR_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);

add_shortcode(
	'feriha_randevu',
	function () {
		wp_enqueue_style( 'fr-booking' );
		wp_enqueue_script( 'fr-booking' );

		$privacy = get_privacy_policy_url();
		$now     = fr_now();
		$config  = array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'today'    => $now->format( 'Y-m-d' ),
			'year'     => (int) $now->format( 'Y' ),
			'month'    => (int) $now->format( 'n' ),
			'maxMonth' => $now->modify( '+' . (int) fr_settings()['horizon_days'] . ' days' )->format( 'Y-m' ),
			'months'   => array_values( fr_month_names() ),
			'days'     => array_values( fr_day_names() ),
			'wa'       => function_exists( 'fc_whatsapp_url' ) ? fc_whatsapp_url() : '',
		);

		ob_start();
		?>
		<div class="fr-booking" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<noscript><p>Randevu formunu kullanmak için tarayıcınızda JavaScript açık olmalıdır. Dilerseniz telefon veya WhatsApp ile ulaşabilirsiniz.</p></noscript>
			<form class="fr-form" novalidate>
				<fieldset class="fr-step" data-step="1">
					<legend><span class="fr-num">1</span> Görüşme türü ve hizmet</legend>
					<div class="fr-choice">
						<label><input type="radio" name="type" value="yuzyuze" checked> <span><strong>Yüz yüze</strong><small>Muayenehanede</small></span></label>
						<label><input type="radio" name="type" value="online"> <span><strong>Online</strong><small>Görüntülü görüşme</small></span></label>
					</div>
					<label class="fr-field">
						<span>Hizmet</span>
						<select name="service" required>
							<?php foreach ( fr_services() as $svc ) : ?>
								<option><?php echo esc_html( $svc ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</fieldset>

				<fieldset class="fr-step" data-step="2">
					<legend><span class="fr-num">2</span> Gün seçin</legend>
					<div class="fr-cal" aria-live="polite">
						<div class="fr-cal__nav">
							<button type="button" class="fr-cal__prev" aria-label="Önceki ay">‹</button>
							<strong class="fr-cal__title"></strong>
							<button type="button" class="fr-cal__next" aria-label="Sonraki ay">›</button>
						</div>
						<div class="fr-cal__head"><span>Pt</span><span>Sa</span><span>Ça</span><span>Pe</span><span>Cu</span><span>Ct</span><span>Pz</span></div>
						<div class="fr-cal__days" role="grid"></div>
					</div>
				</fieldset>

				<fieldset class="fr-step" data-step="3">
					<legend><span class="fr-num">3</span> Saat seçin</legend>
					<p class="fr-slots-msg">Önce takvimden bir gün seçin.</p>
					<div class="fr-slots" role="radiogroup" aria-label="Uygun saatler"></div>
				</fieldset>

				<fieldset class="fr-step" data-step="4">
					<legend><span class="fr-num">4</span> Bilgileriniz</legend>
					<div class="fr-grid">
						<label class="fr-field"><span>Ad soyad *</span><input type="text" name="name" autocomplete="name" required maxlength="80"></label>
						<label class="fr-field"><span>Telefon *</span><input type="tel" name="phone" autocomplete="tel" required maxlength="30" placeholder="+90 5__ ___ __ __"></label>
						<label class="fr-field fr-field--wide"><span>E-posta <small>(onay ve hatırlatma için)</small></span><input type="email" name="email" autocomplete="email" maxlength="100"></label>
						<label class="fr-field fr-field--wide"><span>Not <small>(isteğe bağlı — ayrıntıları görüşmede konuşabiliriz)</small></span><textarea name="note" rows="3" maxlength="600"></textarea></label>
					</div>
					<label class="fr-hp" aria-hidden="true">Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					<label class="fr-consent"><input type="checkbox" name="consent" value="1" required> <span>Kişisel verilerimin randevu amacıyla işlenmesine <?php echo $privacy ? '<a href="' . esc_url( $privacy ) . '" target="_blank">KVKK ve Gizlilik Politikası</a>' : 'KVKK ve Gizlilik Politikası'; ?> kapsamında onay veriyorum. *</span></label>
				</fieldset>

				<div class="fr-summary" hidden></div>
				<div class="fr-error" role="alert" hidden></div>
				<button type="submit" class="fr-submit wp-element-button">Randevu talebini gönder</button>
			</form>
			<div class="fr-done" hidden tabindex="-1"></div>
		</div>
		<?php
		return ob_get_clean();
	}
);

/* ---------- AJAX ---------- */

/**
 * JSON yanıtı.
 *
 * @param bool  $ok   Başarılı mı.
 * @param mixed $data Veri.
 */
function fr_json( $ok, $data ) {
	nocache_headers();
	$ok ? wp_send_json_success( $data ) : wp_send_json_error( $data );
}

/* Ay görünümü. */
foreach ( array( 'wp_ajax_fr_month', 'wp_ajax_nopriv_fr_month' ) as $fr_hook ) {
	add_action(
		$fr_hook,
		function () {
			$y = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$m = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $y < 2020 || $y > 2100 || $m < 1 || $m > 12 ) {
				fr_json( false, 'Geçersiz ay.' );
			}
			fr_json( true, fr_month_availability( $y, $m ) );
		}
	);
}

/* Günün saatleri. */
foreach ( array( 'wp_ajax_fr_slots', 'wp_ajax_nopriv_fr_slots' ) as $fr_hook ) {
	add_action(
		$fr_hook,
		function () {
			$d = isset( $_GET['d'] ) ? sanitize_text_field( wp_unslash( $_GET['d'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) {
				fr_json( false, 'Geçersiz tarih.' );
			}
			fr_json( true, fr_slots( $d ) );
		}
	);
}

/* Randevu oluştur. */
foreach ( array( 'wp_ajax_fr_book', 'wp_ajax_nopriv_fr_book' ) as $fr_hook ) {
	add_action( $fr_hook, 'fr_handle_booking' );
}

/**
 * Randevu talebini işler.
 */
function fr_handle_booking() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Ziyaretçi formu; önbellekli sayfalarda nonce bozulacağından bal küpü + hız sınırı kullanılıyor.
	$in = wp_unslash( $_POST );
	if ( ! empty( $in['website'] ) ) {
		fr_json( true, array( 'message' => 'Teşekkürler.' ) ); // Bot.
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'fr_rl_' . md5( $ip );
	$hit = (int) get_transient( $key );
	if ( $hit >= 5 ) {
		fr_json( false, 'Çok fazla deneme yapıldı. Lütfen daha sonra tekrar deneyin ya da telefonla ulaşın.' );
	}

	$data = fr_clean_input( $in );
	if ( empty( $in['consent'] ) ) {
		fr_json( false, 'Devam etmek için KVKK onay kutusunu işaretleyin.' );
	}
	if ( mb_strlen( $data['name'] ) < 3 ) {
		fr_json( false, 'Lütfen adınızı ve soyadınızı yazın.' );
	}
	if ( strlen( preg_replace( '/\D+/', '', $data['phone'] ) ) < 7 ) {
		fr_json( false, 'Lütfen geçerli bir telefon numarası yazın.' );
	}
	if ( ! empty( $in['email'] ) && ! $data['email'] ) {
		fr_json( false, 'E-posta adresi geçersiz görünüyor.' );
	}
	if ( ! in_array( $data['service'], fr_services(), true ) ) {
		$data['service'] = 'Emin değilim / ön görüşme';
	}
	if ( ! $data['date'] || ! $data['time'] ) {
		fr_json( false, 'Lütfen gün ve saat seçin.' );
	}

	// Aynı saate iki kişinin aynı anda yazmasını önle.
	$lock = 'fr_lock_' . $data['date'] . '_' . $data['time'];
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( (int) get_option( $lock ) < time() - 30 ) {
			delete_option( $lock );
		}
		fr_json( false, 'Bu saat az önce başka biri tarafından seçildi. Lütfen başka bir saat seçin.' );
	}
	if ( ! in_array( $data['time'], fr_slots( $data['date'] ), true ) ) {
		delete_option( $lock );
		fr_json( false, 'Seçtiğiniz saat artık müsait değil. Lütfen başka bir saat seçin.' );
	}

	$data['status'] = fr_settings()['auto_confirm'] ? 'confirmed' : 'pending';
	$id             = fr_save_booking( $data );
	delete_option( $lock );
	if ( is_wp_error( $id ) ) {
		fr_json( false, 'Randevu kaydedilemedi. Lütfen tekrar deneyin.' );
	}
	set_transient( $key, $hit + 1, HOUR_IN_SECONDS );
	fr_notify_new( $id );

	$b   = fr_get_booking( $id );
	$msg = 'confirmed' === $b['status'] ? 'Randevunuz oluşturuldu.' : 'Randevu talebiniz alındı. Onaylandığında size bilgi verilecektir.';
	fr_json(
		true,
		array(
			'message' => $msg,
			'when'    => fr_format_date( $b['date'] ) . ', ' . $b['time'],
			'type'    => fr_type_label( $b['type'] ),
			'email'   => (bool) $b['email'],
		)
	);
	// phpcs:enable
}
