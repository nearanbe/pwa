<?php
/**
 * Ayarlar ve varsayılanlar.
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

/** Gün adları (ISO: 1 = Pazartesi). */
function fr_day_names() {
	return array( 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar' );
}

/** Ay adları. */
function fr_month_names() {
	return array( 1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık' );
}

/** Haftalık varsayılan program. */
function fr_default_schedule() {
	$s = array();
	foreach ( fr_day_names() as $n => $name ) {
		$s[ $n ] = array(
			'open'        => $n <= 5,
			'start'       => '09:00',
			'end'         => '18:00',
			'break_start' => '12:30',
			'break_end'   => '13:30',
		);
	}
	return $s;
}

/** Haftalık program. */
function fr_schedule() {
	$s = get_option( 'fr_schedule' );
	return is_array( $s ) ? $s + fr_default_schedule() : fr_default_schedule();
}

/** Gün bazlı istisnalar: 'Y-m-d' => 'open' | 'closed'. */
function fr_day_overrides() {
	$d = get_option( 'fr_days', array() );
	return is_array( $d ) ? $d : array();
}

/** Genel ayarlar. */
function fr_settings() {
	$defaults = array(
		'session_minutes' => 50,
		'step_minutes'    => 60,
		'min_notice'      => 12,
		'horizon_days'    => 60,
		'auto_confirm'    => 0,
		'notify_email'    => get_option( 'admin_email' ),
		'ical_token'      => '',
		'services_extra'  => '',
		'online_note'     => 'Görüşme bağlantısı randevu onaylandığında size iletilecektir.',
		'office_note'     => '',
	);
	$s = get_option( 'fr_settings', array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), $defaults );
}

/** Saat dilimi (KKTC: Europe/Istanbul). */
function fr_tz() {
	$tz = wp_timezone();
	if ( 'UTC' === $tz->getName() || '+00:00' === $tz->getName() ) {
		return new DateTimeZone( 'Europe/Istanbul' );
	}
	return $tz;
}

/** Şu an. */
function fr_now() {
	return new DateTimeImmutable( 'now', fr_tz() );
}

/** Formdaki hizmet listesi. */
function fr_services() {
	$list = array();
	if ( function_exists( 'fc_services_flat' ) ) {
		foreach ( fc_services_flat() as $item ) {
			$list[] = $item['title'];
		}
	}
	$extra = array_filter( array_map( 'trim', explode( "\n", (string) fr_settings()['services_extra'] ) ) );
	$list  = array_merge( $list, $extra );
	if ( ! $list ) {
		$list = array( 'Bireysel terapi', 'Çift terapisi', 'Aile terapisi', 'Çocuk ve ergen', 'Test / ölçek' );
	}
	$list[] = 'Emin değilim / ön görüşme';
	return array_values( array_unique( $list ) );
}

/** Tarihi Türkçe yazar: 12 Aralık 2026 Cumartesi. */
function fr_format_date( $ymd, $with_day = true ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, fr_tz() );
	if ( ! $d ) {
		return $ymd;
	}
	$out = (int) $d->format( 'j' ) . ' ' . fr_month_names()[ (int) $d->format( 'n' ) ] . ' ' . $d->format( 'Y' );
	return $with_day ? $out . ' ' . fr_day_names()[ (int) $d->format( 'N' ) ] : $out;
}

/** Görüşme türü etiketi. */
function fr_type_label( $type ) {
	return 'online' === $type ? 'Online' : 'Yüz yüze';
}

/** Durum etiketleri. */
function fr_status_labels() {
	return array(
		'pending'   => 'Onay bekliyor',
		'confirmed' => 'Onaylandı',
		'cancelled' => 'İptal edildi',
	);
}
