<?php
/**
 * E-posta bildirimleri (.ics ekli).
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

/**
 * .ics ekli e-posta gönderir.
 *
 * @param string $to      Alıcı.
 * @param string $subject Konu.
 * @param string $body    Metin.
 * @param string $ics     Takvim içeriği (boş olabilir).
 * @param array  $headers Ek başlıklar.
 * @return bool
 */
function fr_mail( $to, $subject, $body, $ics = '', $headers = array() ) {
	if ( ! is_email( $to ) ) {
		return false;
	}
	$file = '';
	if ( $ics ) {
		$dir = trailingslashit( get_temp_dir() ) . 'fr-' . wp_generate_password( 12, false, false );
		wp_mkdir_p( $dir );
		$file = $dir . '/randevu.ics';
		file_put_contents( $file, $ics ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	$headers[] = 'Content-Type: text/plain; charset=UTF-8';
	$ok        = wp_mail( $to, $subject, $body, $headers, $file ? array( $file ) : array() );
	if ( $file ) {
		wp_delete_file( $file );
		@rmdir( dirname( $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	return $ok;
}

/** Randevu özet metni. */
function fr_booking_summary( $b, $for_owner = false ) {
	$lines = array(
		'Tarih: ' . fr_format_date( $b['date'] ),
		'Saat: ' . $b['time'] . ' – ' . $b['end'],
		'Görüşme türü: ' . fr_type_label( $b['type'] ),
		'Hizmet: ' . $b['service'],
	);
	if ( $for_owner ) {
		array_unshift( $lines, 'Danışan: ' . $b['name'] );
		$lines[] = 'Telefon: ' . $b['phone'];
		if ( $b['email'] ) {
			$lines[] = 'E-posta: ' . $b['email'];
		}
		if ( $b['note'] ) {
			$lines[] = 'Not: ' . $b['note'];
		}
	}
	return implode( "\n", $lines );
}

/** İmza. */
function fr_signature() {
	$name  = function_exists( 'fc_opt' ) ? fc_opt( 'title' ) . ' ' . fc_opt( 'person_name' ) : get_bloginfo( 'name' );
	$phone = function_exists( 'fc_opt' ) ? fc_opt( 'phone' ) : '';
	return "\n\nSevgiyle,\n" . $name . ( $phone ? "\n" . $phone : '' ) . "\n" . home_url( '/' );
}

/**
 * Yeni randevu: Feriha Hanım'a ve danışana e-posta.
 *
 * @param int $id Randevu.
 */
function fr_notify_new( $id ) {
	$b = fr_get_booking( $id );
	$s = fr_settings();

	// Feriha Hanım'a.
	$owner_body  = ( 'pending' === $b['status'] ? "Yeni bir randevu talebi var. Onaylamak için:\n" . fr_status_url( $id, 'confirmed' ) . "\n\n" : "Yeni bir randevu alındı (otomatik onaylandı).\n\n" );
	$owner_body .= fr_booking_summary( $b, true );
	$owner_body .= "\n\nTüm randevular: " . admin_url( 'edit.php?post_type=' . FR_CPT );
	$owner_body .= "\n\nEkteki randevu.ics dosyasına dokunarak bu randevuyu takviminize tek seferlik ekleyebilirsiniz. (Takvim aboneliğiniz varsa otomatik olarak da görünecektir.)";
	$headers     = array();
	if ( $b['email'] ) {
		$headers[] = 'Reply-To: ' . $b['name'] . ' <' . $b['email'] . '>';
	}
	fr_mail(
		$s['notify_email'] ? $s['notify_email'] : get_option( 'admin_email' ),
		( 'pending' === $b['status'] ? 'Yeni randevu talebi: ' : 'Yeni randevu: ' ) . $b['name'] . ' — ' . fr_format_date( $b['date'], false ) . ' ' . $b['time'],
		$owner_body,
		fr_ics_calendar( fr_ics_event( $b, 'owner' ) ),
		$headers
	);

	// Danışana.
	if ( $b['email'] ) {
		if ( 'confirmed' === $b['status'] ) {
			fr_notify_client_confirmed( $b );
		} else {
			$body = 'Merhaba ' . $b['name'] . ",\n\nRandevu talebiniz alındı. En kısa sürede onaylanarak size bilgi verilecektir.\n\n" . fr_booking_summary( $b ) . fr_signature();
			fr_mail( $b['email'], 'Randevu talebiniz alındı', $body );
		}
	}
}

/** Danışana onay e-postası. */
function fr_notify_client_confirmed( $b ) {
	$s    = fr_settings();
	$note = 'online' === $b['type'] ? $s['online_note'] : $s['office_note'];
	if ( 'online' !== $b['type'] && function_exists( 'fc_opt' ) && fc_opt( 'address' ) ) {
		$note = 'Adres: ' . fc_opt( 'address' ) . ( fc_opt( 'maps_url' ) ? "\nHarita: " . fc_opt( 'maps_url' ) : '' ) . ( $note ? "\n" . $note : '' );
	}
	$body = 'Merhaba ' . $b['name'] . ",\n\nRandevunuz onaylandı.\n\n" . fr_booking_summary( $b ) . ( $note ? "\n\n" . $note : '' ) . "\n\nEkteki dosyaya dokunarak randevuyu takviminize ekleyebilirsiniz. Gelemeyecekseniz lütfen en az 24 saat önce haber veriniz." . fr_signature();
	fr_mail( $b['email'], 'Randevunuz onaylandı — ' . fr_format_date( $b['date'], false ) . ' ' . $b['time'], $body, fr_ics_calendar( fr_ics_event( $b, 'client' ) ) );
}

/* Durum değişince danışana bildir. */
add_action(
	'fr_status_changed',
	function ( $id, $status ) {
		$b = fr_get_booking( $id );
		if ( ! $b['email'] ) {
			return;
		}
		if ( 'confirmed' === $status ) {
			fr_notify_client_confirmed( $b );
		} elseif ( 'cancelled' === $status ) {
			$body = 'Merhaba ' . $b['name'] . ",\n\nAşağıdaki randevunuz iptal edilmiştir. Yeni bir randevu için sitemizi ziyaret edebilir ya da bizimle iletişime geçebilirsiniz.\n\n" . fr_booking_summary( $b ) . fr_signature();
			fr_mail( $b['email'], 'Randevunuz iptal edildi', $body, fr_ics_calendar( fr_ics_event( $b, 'client', true ), 'CANCEL' ) );
		}
	},
	10,
	2
);
