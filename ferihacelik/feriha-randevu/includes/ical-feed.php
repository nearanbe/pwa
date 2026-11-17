<?php
/**
 * iCal (.ics) üretimi ve Apple Takvim abonelik adresi.
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

/** iCal metin kaçışı. */
function fr_ics_escape( $text ) {
	$text = str_replace( array( '\\', ';', ',', "\r\n", "\n", "\r" ), array( '\\\\', '\;', '\,', '\n', '\n', '' ), (string) $text );
	return $text;
}

/** 75 bayt satır katlama (UTF-8 karakterlerini bölmeden). */
function fr_ics_fold( $line ) {
	$out = '';
	while ( strlen( $line ) > 75 ) {
		$cut = 75;
		while ( $cut > 0 && ( ord( $line[ $cut ] ) & 0xC0 ) === 0x80 ) {
			--$cut;
		}
		$out .= substr( $line, 0, $cut ) . "\r\n ";
		$line = substr( $line, $cut );
	}
	return $out . $line . "\r\n";
}

/**
 * Tek bir randevu için VEVENT.
 *
 * @param array  $b        Randevu.
 * @param string $audience 'owner' (Feriha Hanım) | 'client' (danışan).
 * @param bool   $cancel   İptal mi?
 * @return string
 */
function fr_ics_event( $b, $audience = 'owner', $cancel = false ) {
	$s     = fr_settings();
	$start = ( new DateTimeImmutable( '@' . (int) $b['start'] ) );
	$end   = $start->modify( '+' . (int) $s['session_minutes'] . ' minutes' );
	if ( $b['end'] ) {
		$e = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $b['date'] . ' ' . $b['end'], fr_tz() );
		if ( $e && $e > $start ) {
			$end = $e;
		}
	}
	$fmt  = 'Ymd\THis\Z';
	$utc  = new DateTimeZone( 'UTC' );
	$name = function_exists( 'fc_opt' ) ? fc_opt( 'person_name' ) : get_bloginfo( 'name' );

	if ( 'owner' === $audience ) {
		$prefix  = 'pending' === $b['status'] ? '[Onay bekliyor] ' : '';
		$summary = $prefix . 'Randevu: ' . $b['name'] . ' (' . fr_type_label( $b['type'] ) . ')';
		$desc    = 'Danışan: ' . $b['name'] . "\nTelefon: " . $b['phone'] . ( $b['email'] ? "\nE-posta: " . $b['email'] : '' ) . "\nHizmet: " . $b['service'] . "\nTür: " . fr_type_label( $b['type'] ) . ( $b['note'] ? "\nNot: " . $b['note'] : '' ) . "\nYönet: " . admin_url( 'post.php?post=' . (int) $b['id'] . '&action=edit' );
	} else {
		$summary = 'Psikolog randevusu — ' . $name;
		$desc    = 'Görüşme türü: ' . fr_type_label( $b['type'] ) . "\nHizmet: " . $b['service'] . "\n" . ( 'online' === $b['type'] ? $s['online_note'] : $s['office_note'] ) . "\n" . home_url( '/' );
	}
	$location = 'online' === $b['type'] ? 'Online görüşme' : ( function_exists( 'fc_opt' ) && fc_opt( 'address' ) ? fc_opt( 'address' ) : '' );

	$lines = array(
		'BEGIN:VEVENT',
		'UID:' . ( $b['uid'] ? $b['uid'] : 'fr-' . $b['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) ),
		'DTSTAMP:' . gmdate( $fmt ),
		'DTSTART:' . $start->setTimezone( $utc )->format( $fmt ),
		'DTEND:' . $end->setTimezone( $utc )->format( $fmt ),
		'SUMMARY:' . fr_ics_escape( $summary ),
		'DESCRIPTION:' . fr_ics_escape( trim( $desc ) ),
	);
	if ( $location ) {
		$lines[] = 'LOCATION:' . fr_ics_escape( $location );
	}
	$lines[] = 'STATUS:' . ( $cancel ? 'CANCELLED' : ( 'confirmed' === $b['status'] ? 'CONFIRMED' : 'TENTATIVE' ) );
	$lines[] = 'SEQUENCE:' . ( $cancel ? 2 : ( 'confirmed' === $b['status'] ? 1 : 0 ) );
	if ( ! $cancel && 'owner' === $audience ) {
		$lines = array_merge( $lines, array( 'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . fr_ics_escape( $summary ), 'TRIGGER:-PT30M', 'END:VALARM' ) );
	}
	if ( ! $cancel && 'client' === $audience ) {
		$lines = array_merge( $lines, array( 'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . fr_ics_escape( $summary ), 'TRIGGER:-PT2H', 'END:VALARM' ) );
	}
	$lines[] = 'END:VEVENT';
	return implode( '', array_map( 'fr_ics_fold', $lines ) );
}

/**
 * Takvim sarmalayıcı.
 *
 * @param string $events VEVENT'ler.
 * @param string $method PUBLISH | CANCEL.
 * @param bool   $feed   Abonelik akışı mı?
 * @return string
 */
function fr_ics_calendar( $events, $method = 'PUBLISH', $feed = false ) {
	$head = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Feriha Randevu//TR',
		'CALSCALE:GREGORIAN',
		'METHOD:' . $method,
	);
	if ( $feed ) {
		$head[] = 'X-WR-CALNAME:Randevular — ' . fr_ics_escape( get_bloginfo( 'name' ) );
		$head[] = 'X-WR-TIMEZONE:' . fr_tz()->getName();
		$head[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT15M';
		$head[] = 'X-PUBLISHED-TTL:PT15M';
	}
	return implode( '', array_map( 'fr_ics_fold', $head ) ) . $events . "END:VCALENDAR\r\n";
}

/* Abonelik akışı: https://site/?fr_ical=GİZLİ */
add_action(
	'init',
	function () {
		if ( empty( $_GET['fr_ical'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$token = sanitize_text_field( wp_unslash( $_GET['fr_ical'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$real  = fr_settings()['ical_token'];
		if ( ! $real || ! hash_equals( $real, $token ) ) {
			status_header( 403 );
			exit;
		}
		$from   = time() - 60 * DAY_IN_SECONDS;
		$to     = time() + 400 * DAY_IN_SECONDS;
		$events = '';
		foreach ( fr_active_booking_ids( $from, $to ) as $id ) {
			$events .= fr_ics_event( fr_get_booking( $id ), 'owner' );
		}
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="randevular.ics"' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		echo fr_ics_calendar( $events, 'PUBLISH', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	},
	1
);
