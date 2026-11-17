<?php
/**
 * "Müsait günlerim" ekranı: haftalık saatler + takvimden gün açma/kapatma.
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=' . FR_CPT, 'Müsait günlerim', 'Müsait günlerim', 'edit_posts', 'fr-availability', 'fr_render_availability_page', 1 );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( false === strpos( (string) $hook, 'fr-availability' ) ) {
			return;
		}
		wp_enqueue_style( 'fr-admin', FR_URL . 'assets/admin.css', array(), FR_VERSION );
		wp_enqueue_script( 'fr-admin', FR_URL . 'assets/admin.js', array(), FR_VERSION, true );
	}
);

/**
 * Ekran.
 */
function fr_render_availability_page() {
	$sched = fr_schedule();
	$over  = fr_day_overrides();
	$s     = fr_settings();
	$today = fr_now()->setTime( 0, 0 );

	// Takvimde gösterilecek günler (bugünden itibaren 4 ay).
	$months = array();
	$cursor = $today->modify( 'first day of this month' );
	$booked = array();
	foreach ( fr_active_booking_ids( $today->getTimestamp(), $today->modify( '+4 months' )->getTimestamp() ) as $id ) {
		$d            = get_post_meta( $id, '_fr_date', true );
		$booked[ $d ] = ( $booked[ $d ] ?? 0 ) + 1;
	}
	for ( $i = 0; $i < 4; $i++ ) {
		$months[] = $cursor->modify( "+$i months" );
	}
	?>
	<div class="wrap fr-wrap">
		<h1>Müsait günlerim</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>Kaydedildi. Sitedeki randevu takvimi güncellendi.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fr_save_availability">
			<?php wp_nonce_field( 'fr_availability' ); ?>

			<div class="fr-box">
				<h2>1. Takvimden gün seçin</h2>
				<p class="fr-help">Bir güne <strong>dokunarak</strong> o günü açın ya da kapatın. <span class="fr-key fr-key--open"></span> Açık (randevu alınabilir) &nbsp; <span class="fr-key fr-key--closed"></span> Kapalı &nbsp; <span class="fr-key fr-key--booked"></span> O gün randevu var</p>
				<input type="hidden" name="fr_days" id="fr-days" value="<?php echo esc_attr( wp_json_encode( (object) $over ) ); ?>">
				<div class="fr-cal-grid">
					<?php foreach ( $months as $m ) : ?>
						<div class="fr-cal">
							<div class="fr-cal__title"><?php echo esc_html( fr_month_names()[ (int) $m->format( 'n' ) ] . ' ' . $m->format( 'Y' ) ); ?></div>
							<div class="fr-cal__head"><span>Pt</span><span>Sa</span><span>Ça</span><span>Pe</span><span>Cu</span><span>Ct</span><span>Pz</span></div>
							<div class="fr-cal__days">
								<?php
								$pad = (int) $m->format( 'N' ) - 1;
								echo str_repeat( '<span class="fr-cal__pad"></span>', $pad ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$n = (int) $m->format( 't' );
								for ( $d = 1; $d <= $n; $d++ ) {
									$date    = $m->setDate( (int) $m->format( 'Y' ), (int) $m->format( 'n' ), $d );
									$ymd     = $date->format( 'Y-m-d' );
									$weekly  = ! empty( $sched[ (int) $date->format( 'N' ) ]['open'] );
									$is_open = fr_is_day_open( $ymd );
									$past    = $date < $today;
									$cls     = 'fr-day ' . ( $is_open ? 'is-open' : 'is-closed' ) . ( $past ? ' is-past' : '' ) . ( isset( $booked[ $ymd ] ) ? ' has-booking' : '' );
									printf(
										'<button type="button" class="%1$s" data-date="%2$s" data-weekly="%3$d" %4$s aria-pressed="%5$s" title="%6$s">%7$d</button>',
										esc_attr( $cls ),
										esc_attr( $ymd ),
										$weekly ? 1 : 0,
										$past ? 'disabled' : '',
										$is_open ? 'true' : 'false',
										esc_attr( fr_format_date( $ymd ) . ( isset( $booked[ $ymd ] ) ? ' — ' . $booked[ $ymd ] . ' randevu' : '' ) ),
										(int) $d
									);
								}
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="fr-box">
				<h2>2. Haftalık çalışma saatleri</h2>
				<p class="fr-help">Normalde hangi günler ve hangi saatler arasında çalıştığınızı seçin. Takvimdeki günler bu düzene göre otomatik açılır; istisnaları yukarıdaki takvimden değiştirebilirsiniz.</p>
				<table class="fr-week">
					<thead><tr><th>Gün</th><th>Çalışıyorum</th><th>Başlangıç</th><th>Bitiş</th><th>Ara başlangıç</th><th>Ara bitiş</th></tr></thead>
					<tbody>
					<?php foreach ( fr_day_names() as $n => $name ) : $d = $sched[ $n ]; ?>
						<tr>
							<th scope="row"><?php echo esc_html( $name ); ?></th>
							<td><label class="fr-switch"><input type="checkbox" name="fr_week[<?php echo (int) $n; ?>][open]" value="1" <?php checked( ! empty( $d['open'] ) ); ?>><span>Açık</span></label></td>
							<td><input type="time" step="900" name="fr_week[<?php echo (int) $n; ?>][start]" value="<?php echo esc_attr( $d['start'] ); ?>" aria-label="<?php echo esc_attr( $name ); ?> başlangıç"></td>
							<td><input type="time" step="900" name="fr_week[<?php echo (int) $n; ?>][end]" value="<?php echo esc_attr( $d['end'] ); ?>" aria-label="<?php echo esc_attr( $name ); ?> bitiş"></td>
							<td><input type="time" step="900" name="fr_week[<?php echo (int) $n; ?>][break_start]" value="<?php echo esc_attr( $d['break_start'] ); ?>" aria-label="<?php echo esc_attr( $name ); ?> ara başlangıç"></td>
							<td><input type="time" step="900" name="fr_week[<?php echo (int) $n; ?>][break_end]" value="<?php echo esc_attr( $d['break_end'] ); ?>" aria-label="<?php echo esc_attr( $name ); ?> ara bitiş"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="fr-help">Seans süresi: <strong><?php echo (int) $s['session_minutes']; ?> dk</strong>, randevular <strong><?php echo (int) $s['step_minutes']; ?> dakikada bir</strong> başlar. Bunu <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-settings' ) ); ?>">Randevu ayarları</a>ndan değiştirebilirsiniz. Ara istemiyorsanız ara saatlerini boş bırakın.</p>
			</div>

			<p class="fr-save"><button type="submit" class="button button-primary button-hero">Kaydet</button></p>
		</form>
	</div>
	<?php
}

add_action(
	'admin_post_fr_save_availability',
	function () {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fr_availability' );

		$week  = isset( $_POST['fr_week'] ) && is_array( $_POST['fr_week'] ) ? wp_unslash( $_POST['fr_week'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sched = array();
		foreach ( fr_day_names() as $n => $name ) {
			$row         = $week[ $n ] ?? array();
			$t           = function ( $v ) {
				return preg_match( '/^\d{2}:\d{2}$/', (string) $v ) ? $v : '';
			};
			$sched[ $n ] = array(
				'open'        => ! empty( $row['open'] ),
				'start'       => $t( $row['start'] ?? '' ) ? $t( $row['start'] ) : '09:00',
				'end'         => $t( $row['end'] ?? '' ) ? $t( $row['end'] ) : '18:00',
				'break_start' => $t( $row['break_start'] ?? '' ),
				'break_end'   => $t( $row['break_end'] ?? '' ),
			);
		}
		update_option( 'fr_schedule', $sched );

		$raw   = isset( $_POST['fr_days'] ) ? json_decode( wp_unslash( $_POST['fr_days'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$days  = array();
		$limit = fr_now()->modify( '-1 day' )->format( 'Y-m-d' );
		if ( is_array( $raw ) ) {
			foreach ( $raw as $ymd => $state ) {
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) && in_array( $state, array( 'open', 'closed' ), true ) && $ymd > $limit ) {
					$days[ $ymd ] = $state;
				}
			}
		}
		// Haftalık düzenle aynı olan istisnaları temizle.
		foreach ( $days as $ymd => $state ) {
			$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, fr_tz() );
			if ( $d && ( ! empty( $sched[ (int) $d->format( 'N' ) ]['open'] ) ) === ( 'open' === $state ) ) {
				unset( $days[ $ymd ] );
			}
		}
		ksort( $days );
		update_option( 'fr_days', $days, false );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FR_CPT . '&page=fr-availability&saved=1' ) );
		exit;
	}
);
