<?php
/**
 * Müsaitlik hesabı: hangi gün açık, hangi saatler boş.
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bir gün açık mı? (haftalık program + gün istisnası)
 *
 * @param string $ymd Tarih.
 * @return bool
 */
function fr_is_day_open( $ymd ) {
	$over = fr_day_overrides();
	if ( isset( $over[ $ymd ] ) ) {
		return 'open' === $over[ $ymd ];
	}
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, fr_tz() );
	if ( ! $d ) {
		return false;
	}
	$sched = fr_schedule();
	return ! empty( $sched[ (int) $d->format( 'N' ) ]['open'] );
}

/**
 * Günün çalışma saatleri. Haftalık programda kapalı olan ama tek seferlik açılan
 * günlerde, açık olan ilk iş gününün saatleri kullanılır.
 *
 * @param string $ymd Tarih.
 * @return array|null
 */
function fr_day_hours( $ymd ) {
	if ( ! fr_is_day_open( $ymd ) ) {
		return null;
	}
	$d     = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, fr_tz() );
	$sched = fr_schedule();
	$day   = $sched[ (int) $d->format( 'N' ) ];
	if ( empty( $day['open'] ) ) {
		foreach ( $sched as $other ) {
			if ( ! empty( $other['open'] ) ) {
				$day = $other;
				break;
			}
		}
	}
	return $day;
}

/**
 * "H:i" → gün başından itibaren dakika.
 *
 * @param string $hm Saat.
 * @return int|null
 */
function fr_minutes( $hm ) {
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', (string) $hm, $m ) ) {
		return null;
	}
	return (int) $m[1] * 60 + (int) $m[2];
}

/**
 * Bir gündeki boş saatler.
 *
 * @param string $ymd Tarih.
 * @param int    $ignore_id Bu kaydı dolu sayma (düzenleme için).
 * @return string[] "H:i" listesi.
 */
function fr_slots( $ymd, $ignore_id = 0 ) {
	$hours = fr_day_hours( $ymd );
	if ( ! $hours ) {
		return array();
	}
	$s       = fr_settings();
	$len     = max( 15, (int) $s['session_minutes'] );
	$step    = max( 15, (int) $s['step_minutes'] );
	$open    = fr_minutes( $hours['start'] );
	$close   = fr_minutes( $hours['end'] );
	$b_start = fr_minutes( $hours['break_start'] ?? '' );
	$b_end   = fr_minutes( $hours['break_end'] ?? '' );
	if ( null === $open || null === $close || $close <= $open ) {
		return array();
	}

	$day0    = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, fr_tz() );
	$horizon = fr_now()->modify( '+' . (int) $s['horizon_days'] . ' days' )->setTime( 23, 59 );
	if ( ! $day0 || $day0 > $horizon ) {
		return array();
	}
	$earliest = fr_now()->getTimestamp() + (int) $s['min_notice'] * HOUR_IN_SECONDS;

	// Dolu aralıklar.
	$busy = array();
	foreach ( fr_active_booking_ids( $day0->getTimestamp(), $day0->modify( '+1 day' )->getTimestamp() - 1 ) as $id ) {
		if ( (int) $id === (int) $ignore_id ) {
			continue;
		}
		$t = fr_minutes( get_post_meta( $id, '_fr_time', true ) );
		$e = fr_minutes( get_post_meta( $id, '_fr_end', true ) );
		if ( null !== $t ) {
			$busy[] = array( $t, null !== $e && $e > $t ? $e : $t + $len );
		}
	}

	$out = array();
	for ( $m = $open; $m + $len <= $close; $m += $step ) {
		$end = $m + $len;
		if ( null !== $b_start && null !== $b_end && $b_end > $b_start && $m < $b_end && $end > $b_start ) {
			continue; // Öğle arası.
		}
		if ( $day0->getTimestamp() + $m * 60 < $earliest ) {
			continue; // Çok yakın.
		}
		foreach ( $busy as $r ) {
			if ( $m < $r[1] && $end > $r[0] ) {
				continue 2; // Dolu.
			}
		}
		$out[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
	}
	return $out;
}

/**
 * Bir aydaki gün durumları (takvim için).
 *
 * @param int $year  Yıl.
 * @param int $month Ay.
 * @return array 'Y-m-d' => slot sayısı
 */
function fr_month_availability( $year, $month ) {
	$out   = array();
	$first = DateTimeImmutable::createFromFormat( '!Y-n-j', $year . '-' . $month . '-1', fr_tz() );
	if ( ! $first ) {
		return $out;
	}
	$days = (int) $first->format( 't' );
	for ( $i = 0; $i < $days; $i++ ) {
		$ymd         = $first->modify( "+$i days" )->format( 'Y-m-d' );
		$out[ $ymd ] = count( fr_slots( $ymd ) );
	}
	return $out;
}
