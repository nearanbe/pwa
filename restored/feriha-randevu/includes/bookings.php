<?php
/**
 * Randevu kayıtları (özel içerik türü) ve yönetim listesi.
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

const FR_CPT = 'fr_booking';

add_action(
	'init',
	function () {
		register_post_type(
			FR_CPT,
			array(
				'labels'              => array(
					'name'               => 'Randevular',
					'singular_name'      => 'Randevu',
					'menu_name'          => 'Randevular',
					'all_items'          => 'Randevularım',
					'add_new'            => 'Elle randevu ekle',
					'add_new_item'       => 'Elle randevu ekle',
					'edit_item'          => 'Randevu',
					'not_found'          => 'Randevu bulunamadı.',
					'not_found_in_trash' => 'Çöp kutusunda randevu yok.',
					'search_items'       => 'Randevu ara',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'menu_icon'           => 'dashicons-calendar-alt',
				'menu_position'       => 2,
				'supports'            => array( 'nothing' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}
);

/**
 * Randevu verisini okur.
 *
 * @param int $id Kayıt kimliği.
 * @return array
 */
function fr_get_booking( $id ) {
	$keys = array( 'date', 'time', 'end', 'start', 'name', 'phone', 'email', 'type', 'service', 'note', 'status', 'uid' );
	$b    = array( 'id' => (int) $id );
	foreach ( $keys as $k ) {
		$b[ $k ] = get_post_meta( $id, '_fr_' . $k, true );
	}
	if ( ! $b['status'] ) {
		$b['status'] = 'pending';
	}
	return $b;
}

/**
 * Randevu kaydeder / günceller.
 *
 * @param array $data Alanlar.
 * @param int   $id   Varsa güncellenecek kayıt.
 * @return int|WP_Error
 */
function fr_save_booking( $data, $id = 0 ) {
	$s     = fr_settings();
	$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $data['date'] . ' ' . $data['time'], fr_tz() );
	if ( ! $start ) {
		return new WP_Error( 'fr_date', 'Tarih veya saat geçersiz.' );
	}
	$end   = $start->modify( '+' . (int) $s['session_minutes'] . ' minutes' );
	$title = $data['name'] . ' — ' . $start->format( 'd.m.Y H:i' );
	$args  = array(
		'post_type'   => FR_CPT,
		'post_status' => 'publish',
		'post_title'  => $title,
	);
	if ( $id ) {
		$args['ID'] = $id;
		$res        = wp_update_post( $args, true );
	} else {
		$res = wp_insert_post( $args, true );
	}
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$id   = (int) $res;
	$meta = array(
		'date'    => $start->format( 'Y-m-d' ),
		'time'    => $start->format( 'H:i' ),
		'end'     => $end->format( 'H:i' ),
		'start'   => $start->getTimestamp(),
		'name'    => $data['name'],
		'phone'   => $data['phone'],
		'email'   => $data['email'],
		'type'    => $data['type'],
		'service' => $data['service'],
		'note'    => $data['note'],
		'status'  => $data['status'],
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, '_fr_' . $k, $v );
	}
	if ( ! get_post_meta( $id, '_fr_uid', true ) ) {
		update_post_meta( $id, '_fr_uid', wp_generate_uuid4() . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) );
	}
	return $id;
}

/**
 * Belirli bir tarih aralığındaki iptal edilmemiş randevular.
 *
 * @param int $from Başlangıç (unix).
 * @param int $to   Bitiş (unix).
 * @return int[]
 */
function fr_active_booking_ids( $from, $to ) {
	return get_posts(
		array(
			'post_type'      => FR_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array( 'key' => '_fr_start', 'value' => array( (int) $from, (int) $to ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ),
				array( 'key' => '_fr_status', 'value' => 'cancelled', 'compare' => '!=' ),
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_fr_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'ASC',
		)
	);
}

/** Bekleyen randevu sayısı. */
function fr_pending_count() {
	$q = new WP_Query(
		array(
			'post_type'      => FR_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array( 'key' => '_fr_status', 'value' => 'pending' ),
				array( 'key' => '_fr_start', 'value' => time(), 'compare' => '>=', 'type' => 'NUMERIC' ),
			),
		)
	);
	return (int) $q->found_posts;
}

/* ---------- Menü rozeti ---------- */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$count = fr_pending_count();
		if ( ! $count ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( 'edit.php?post_type=' . FR_CPT === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $count . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
	},
	99
);

/* ---------- Liste sütunları ---------- */
add_filter(
	'manage_' . FR_CPT . '_posts_columns',
	function () {
		return array(
			'cb'         => '<input type="checkbox" />',
			'fr_when'    => 'Tarih / saat',
			'fr_name'    => 'Danışan',
			'fr_contact' => 'İletişim',
			'fr_type'    => 'Tür',
			'fr_service' => 'Hizmet',
			'fr_status'  => 'Durum',
		);
	}
);

add_action(
	'manage_' . FR_CPT . '_posts_custom_column',
	function ( $col, $id ) {
		$b = fr_get_booking( $id );
		switch ( $col ) {
			case 'fr_when':
				echo '<strong><a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( fr_format_date( $b['date'] ) ) . '</a></strong><br>' . esc_html( $b['time'] . ' – ' . $b['end'] );
				break;
			case 'fr_name':
				echo esc_html( $b['name'] );
				if ( $b['note'] ) {
					echo '<br><small style="color:#646970">' . esc_html( wp_trim_words( $b['note'], 12 ) ) . '</small>';
				}
				break;
			case 'fr_contact':
				if ( $b['phone'] ) {
					$digits = preg_replace( '/\D+/', '', $b['phone'] );
					echo '<a href="tel:+' . esc_attr( $digits ) . '">' . esc_html( $b['phone'] ) . '</a> · <a href="https://wa.me/' . esc_attr( $digits ) . '" target="_blank" rel="noopener">WhatsApp</a>';
				}
				if ( $b['email'] ) {
					echo '<br><a href="mailto:' . esc_attr( $b['email'] ) . '">' . esc_html( $b['email'] ) . '</a>';
				}
				break;
			case 'fr_type':
				echo esc_html( fr_type_label( $b['type'] ) );
				break;
			case 'fr_service':
				echo esc_html( $b['service'] );
				break;
			case 'fr_status':
				$colors = array( 'pending' => '#b26200', 'confirmed' => '#1a7f37', 'cancelled' => '#a00' );
				echo '<span style="font-weight:600;color:' . esc_attr( $colors[ $b['status'] ] ?? '#333' ) . '">' . esc_html( fr_status_labels()[ $b['status'] ] ?? $b['status'] ) . '</span>';
				break;
		}
	},
	10,
	2
);

/* Satır işlemleri: Onayla / İptal et. */
add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		if ( FR_CPT !== $post->post_type ) {
			return $actions;
		}
		$b   = fr_get_booking( $post->ID );
		$new = array( 'edit' => '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Aç / düzenle</a>' );
		if ( 'confirmed' !== $b['status'] ) {
			$new['fr_confirm'] = '<a style="color:#1a7f37;font-weight:600" href="' . esc_url( fr_status_url( $post->ID, 'confirmed' ) ) . '">Onayla</a>';
		}
		if ( 'cancelled' !== $b['status'] ) {
			$new['fr_cancel'] = '<a style="color:#a00" href="' . esc_url( fr_status_url( $post->ID, 'cancelled' ) ) . '" onclick="return confirm(\'Randevu iptal edilsin mi?\')">İptal et</a>';
		}
		if ( isset( $actions['trash'] ) ) {
			$new['trash'] = $actions['trash'];
		}
		return $new;
	},
	10,
	2
);

/**
 * Durum değiştirme bağlantısı.
 *
 * @param int    $id     Kayıt.
 * @param string $status Yeni durum.
 * @return string
 */
function fr_status_url( $id, $status ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=fr_status&id=' . (int) $id . '&status=' . $status ), 'fr_status_' . (int) $id );
}

add_action(
	'admin_post_fr_status',
	function () {
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		check_admin_referer( 'fr_status_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || FR_CPT !== get_post_type( $id ) || ! isset( fr_status_labels()[ $status ] ) ) {
			wp_die( 'İşlem yapılamadı.' );
		}
		fr_change_status( $id, $status );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FR_CPT . '&fr_msg=' . $status ) );
		exit;
	}
);

/**
 * Durumu değiştirir ve danışana bildirir.
 *
 * @param int    $id     Kayıt.
 * @param string $status Yeni durum.
 */
function fr_change_status( $id, $status ) {
	$old = get_post_meta( $id, '_fr_status', true );
	update_post_meta( $id, '_fr_status', $status );
	if ( $old !== $status ) {
		do_action( 'fr_status_changed', $id, $status, $old );
	}
}

add_action(
	'admin_notices',
	function () {
		if ( empty( $_GET['fr_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$msg = sanitize_key( $_GET['fr_msg'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$txt = 'confirmed' === $msg ? 'Randevu onaylandı. Danışana e-posta gönderildi (e-posta adresi varsa).' : ( 'cancelled' === $msg ? 'Randevu iptal edildi. Danışana bilgi verildi (e-posta adresi varsa).' : 'Kaydedildi.' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $txt ) . '</p></div>';
	}
);

/* Varsayılan liste: yaklaşan randevular, tarihe göre. */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || FR_CPT !== $q->get( 'post_type' ) ) {
			return;
		}
		$view = isset( $_GET['fr_view'] ) ? sanitize_key( $_GET['fr_view'] ) : 'upcoming'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q->set( 'meta_key', '_fr_start' );
		$q->set( 'orderby', 'meta_value_num' );
		$today = fr_now()->setTime( 0, 0 )->getTimestamp();
		if ( 'past' === $view ) {
			$q->set( 'order', 'DESC' );
			$q->set( 'meta_query', array( array( 'key' => '_fr_start', 'value' => $today, 'compare' => '<', 'type' => 'NUMERIC' ) ) );
		} elseif ( 'upcoming' === $view && 'trash' !== $q->get( 'post_status' ) ) {
			$q->set( 'order', 'ASC' );
			$q->set( 'meta_query', array( array( 'key' => '_fr_start', 'value' => $today, 'compare' => '>=', 'type' => 'NUMERIC' ) ) );
		}
	}
);

add_filter(
	'views_edit-' . FR_CPT,
	function ( $views ) {
		$view = isset( $_GET['fr_view'] ) ? sanitize_key( $_GET['fr_view'] ) : 'upcoming'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base = admin_url( 'edit.php?post_type=' . FR_CPT );
		$out  = array(
			'upcoming' => '<a href="' . esc_url( $base ) . '"' . ( 'upcoming' === $view ? ' class="current"' : '' ) . '>Yaklaşan</a>',
			'past'     => '<a href="' . esc_url( $base . '&fr_view=past' ) . '"' . ( 'past' === $view ? ' class="current"' : '' ) . '>Geçmiş</a>',
			'all'      => '<a href="' . esc_url( $base . '&fr_view=all' ) . '"' . ( 'all' === $view ? ' class="current"' : '' ) . '>Tümü</a>',
		);
		if ( isset( $views['trash'] ) ) {
			$out['trash'] = $views['trash'];
		}
		return $out;
	}
);

/* ---------- Düzenleme kutusu ---------- */
add_action(
	'add_meta_boxes_' . FR_CPT,
	function () {
		add_meta_box( 'fr_booking_box', 'Randevu bilgileri', 'fr_render_booking_box', FR_CPT, 'normal', 'high' );
	}
);

/**
 * Düzenleme kutusu.
 *
 * @param WP_Post $post Kayıt.
 */
function fr_render_booking_box( $post ) {
	$b = fr_get_booking( $post->ID );
	if ( 'auto-draft' === $post->post_status ) {
		$b['status'] = 'confirmed';
	}
	wp_nonce_field( 'fr_box', 'fr_box_nonce' );
	$row = function ( $label, $html ) {
		echo '<tr><th scope="row" style="width:160px"><label>' . esc_html( $label ) . '</label></th><td>' . $html . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	};
	echo '<table class="form-table" role="presentation">';
	$row( 'Tarih', '<input type="date" name="fr[date]" required value="' . esc_attr( $b['date'] ) . '">' );
	$row( 'Saat', '<input type="time" name="fr[time]" required step="300" value="' . esc_attr( $b['time'] ) . '">' );
	$row( 'Ad soyad', '<input type="text" class="regular-text" name="fr[name]" required value="' . esc_attr( $b['name'] ) . '">' );
	$row( 'Telefon', '<input type="tel" class="regular-text" name="fr[phone]" value="' . esc_attr( $b['phone'] ) . '">' );
	$row( 'E-posta', '<input type="email" class="regular-text" name="fr[email]" value="' . esc_attr( $b['email'] ) . '">' );
	$types = '';
	foreach ( array( 'yuzyuze', 'online' ) as $t ) {
		$types .= '<label style="margin-right:16px"><input type="radio" name="fr[type]" value="' . $t . '"' . checked( $b['type'] ? $b['type'] : 'yuzyuze', $t, false ) . '> ' . esc_html( fr_type_label( $t ) ) . '</label>';
	}
	$row( 'Görüşme türü', $types );
	$opts = '';
	foreach ( array_unique( array_merge( fr_services(), $b['service'] ? array( $b['service'] ) : array() ) ) as $svc ) {
		$opts .= '<option' . selected( $b['service'], $svc, false ) . '>' . esc_html( $svc ) . '</option>';
	}
	$row( 'Hizmet', '<select name="fr[service]">' . $opts . '</select>' );
	$row( 'Not', '<textarea name="fr[note]" rows="3" class="large-text">' . esc_textarea( $b['note'] ) . '</textarea>' );
	$st = '';
	foreach ( fr_status_labels() as $k => $l ) {
		$st .= '<option value="' . esc_attr( $k ) . '"' . selected( $b['status'], $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	$row( 'Durum', '<select name="fr[status]">' . $st . '</select><p class="description">Durumu "Onaylandı" ya da "İptal edildi" yaptığınızda danışana otomatik e-posta gider.</p>' );
	echo '</table>';
	if ( $b['phone'] ) {
		$digits = preg_replace( '/\D+/', '', $b['phone'] );
		echo '<p><a class="button" href="tel:+' . esc_attr( $digits ) . '">Ara</a> <a class="button" target="_blank" rel="noopener" href="https://wa.me/' . esc_attr( $digits ) . '">WhatsApp\'tan yaz</a></p>';
	}
}

add_action(
	'save_post_' . FR_CPT,
	function ( $id ) {
		if ( ! isset( $_POST['fr_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fr_box_nonce'] ) ), 'fr_box' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) || empty( $_POST['fr'] ) ) {
			return;
		}
		$in   = wp_unslash( $_POST['fr'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = fr_clean_input( $in );
		$new  = isset( fr_status_labels()[ $in['status'] ?? '' ] ) ? $in['status'] : 'pending';
		$old  = get_post_meta( $id, '_fr_status', true );
		$data['status'] = $old ? $old : $new;
		remove_all_actions( 'save_post_' . FR_CPT );
		fr_save_booking( $data, $id );
		if ( $old && $old !== $new ) {
			fr_change_status( $id, $new );
		} elseif ( ! $old ) {
			update_post_meta( $id, '_fr_status', $new );
		}
	}
);

/**
 * Formdan gelen alanları temizler.
 *
 * @param array $in Ham veri.
 * @return array
 */
function fr_clean_input( $in ) {
	return array(
		'date'    => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in['date'] ?? '' ) ? $in['date'] : '',
		'time'    => preg_match( '/^\d{2}:\d{2}$/', $in['time'] ?? '' ) ? $in['time'] : '',
		'name'    => sanitize_text_field( $in['name'] ?? '' ),
		'phone'   => sanitize_text_field( $in['phone'] ?? '' ),
		'email'   => sanitize_email( $in['email'] ?? '' ),
		'type'    => ( $in['type'] ?? '' ) === 'online' ? 'online' : 'yuzyuze',
		'service' => sanitize_text_field( $in['service'] ?? '' ),
		'note'    => sanitize_textarea_field( $in['note'] ?? '' ),
	);
}

/* Elle eklenen randevu ekranında başlık alanını gizle. */
add_filter(
	'enter_title_here',
	function ( $t, $post ) {
		return FR_CPT === $post->post_type ? '' : $t;
	},
	10,
	2
);

/* ---------- Pano kutusu: yaklaşan randevular ---------- */
add_action(
	'wp_dashboard_setup',
	function () {
		wp_add_dashboard_widget( 'fr_upcoming', 'Yaklaşan randevular', 'fr_render_dashboard_widget' );
	}
);

/** Pano kutusu. */
function fr_render_dashboard_widget() {
	$now = time();
	$ids = fr_active_booking_ids( $now - HOUR_IN_SECONDS, $now + 14 * DAY_IN_SECONDS );
	if ( ! $ids ) {
		echo '<p>Önümüzdeki 14 gün için randevu yok.</p>';
	} else {
		echo '<ul style="margin:0">';
		foreach ( array_slice( $ids, 0, 10 ) as $id ) {
			$b = fr_get_booking( $id );
			echo '<li style="padding:8px 0;border-bottom:1px solid #eee"><strong>' . esc_html( fr_format_date( $b['date'] ) . ' ' . $b['time'] ) . '</strong> — <a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( $b['name'] ) . '</a> (' . esc_html( fr_type_label( $b['type'] ) ) . ')';
			if ( 'pending' === $b['status'] ) {
				echo ' <span style="color:#b26200;font-weight:600">· Onay bekliyor</span> <a class="button button-small" href="' . esc_url( fr_status_url( $id, 'confirmed' ) ) . '">Onayla</a>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=' . FR_CPT ) ) . '">Tüm randevular</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=fr-availability' ) ) . '">Müsait günlerim</a></p>';
}
