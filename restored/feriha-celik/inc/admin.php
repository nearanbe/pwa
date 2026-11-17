<?php
/**
 * Yönetim panelini sadeleştirir: büyük butonlu "Hoş geldiniz" kutusu,
 * gereksiz pano kutularının kaldırılması.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_dashboard_setup',
	function () {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );       // WordPress haberleri.
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );   // Hızlı taslak.
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' ); // Site sağlığı.
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		wp_add_dashboard_widget( 'fc_welcome', 'Hoş geldiniz', 'fc_render_welcome_widget' );

		// Kutumuzu en üste al.
		global $wp_meta_boxes;
		$normal = $wp_meta_boxes['dashboard']['normal']['core'];
		$ours   = array( 'fc_welcome' => $normal['fc_welcome'] );
		unset( $normal['fc_welcome'] );
		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $normal ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
);

/**
 * Hoş geldiniz kutusu.
 */
function fc_render_welcome_widget() {
	$buttons = array(
		array( 'Yeni blog yazısı yaz', admin_url( 'post-new.php' ), 'dashicons-edit' ),
		array( 'Yazılarım', admin_url( 'edit.php' ), 'dashicons-admin-post' ),
	);
	if ( defined( 'FR_VERSION' ) ) {
		$buttons[] = array( 'Randevularım', admin_url( 'edit.php?post_type=fr_booking' ), 'dashicons-calendar-alt' );
		$buttons[] = array( 'Müsait günlerimi seç', admin_url( 'admin.php?page=fr-availability' ), 'dashicons-yes-alt' );
	}
	$buttons[] = array( 'Telefon, adres, fotoğraf', admin_url( 'admin.php?page=fc-site' ), 'dashicons-id-alt' );
	$buttons[] = array( 'Siteyi görüntüle', home_url( '/' ), 'dashicons-visibility' );
	?>
	<style>
		.fc-dash{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin:4px 0 8px}
		.fc-dash a{display:flex;align-items:center;gap:10px;padding:16px;border-radius:12px;background:#EEF6F0;color:#2F5A43;text-decoration:none;font-size:15px;font-weight:600;border:1px solid #CFE6D6}
		.fc-dash a:hover,.fc-dash a:focus{background:#CFE6D6;color:#22312A}
		.fc-dash .dashicons{font-size:24px;width:24px;height:24px}
	</style>
	<p style="font-size:14px">Ne yapmak istersiniz?</p>
	<div class="fc-dash">
		<?php foreach ( $buttons as $b ) : ?>
			<a href="<?php echo esc_url( $b[1] ); ?>"><span class="dashicons <?php echo esc_attr( $b[2] ); ?>"></span><?php echo esc_html( $b[0] ); ?></a>
		<?php endforeach; ?>
	</div>
	<?php
}
