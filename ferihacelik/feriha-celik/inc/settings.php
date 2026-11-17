<?php
/**
 * "Site Bilgileri" ekranı: telefon, WhatsApp, e-posta, adres, anasayfa fotoğrafı.
 * Teknik bilgi gerektirmeyen tek bir form.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alan tanımları.
 *
 * @return array
 */
function fc_setting_fields() {
	return array(
		'person_name'  => array( 'label' => 'Ad Soyad', 'default' => 'Feriha Çelik', 'help' => '' ),
		'title'        => array( 'label' => 'Unvan', 'default' => 'Uzman Klinik Psikolog', 'help' => '' ),
		'phone'        => array( 'label' => 'Telefon', 'default' => '', 'help' => 'Örnek: +90 548 123 45 67' ),
		'whatsapp'     => array( 'label' => 'WhatsApp numarası', 'default' => '', 'help' => 'Ülke koduyla, örnek: +90 548 123 45 67. Boş bırakılırsa WhatsApp butonları gizlenir.' ),
		'wa_message'   => array( 'label' => 'WhatsApp hazır mesajı', 'default' => 'Merhaba Feriha Hanım, randevu almak istiyorum.', 'help' => 'Ziyaretçi butona bastığında mesaj kutusunda hazır yazılı gelir.' ),
		'email'        => array( 'label' => 'E-posta', 'default' => '', 'help' => '' ),
		'address'      => array( 'label' => 'Muayenehane adresi', 'default' => '', 'help' => 'Yüz yüze görüşmeler için. Google İşletme Profili ile birebir aynı yazın.', 'type' => 'textarea' ),
		'city'         => array( 'label' => 'Şehir / bölge', 'default' => 'Lefkoşa', 'help' => 'Örnek: Lefkoşa, Girne, Gazimağusa' ),
		'maps_url'     => array( 'label' => 'Google Haritalar bağlantısı', 'default' => '', 'help' => 'Google Haritalar\'da konumunuzu açıp "Paylaş" > "Bağlantıyı kopyala" ile aldığınız adres.' ),
		'hours'        => array( 'label' => 'Çalışma saatleri (yazı)', 'default' => 'Pazartesi – Cumartesi, 09:00 – 19:00', 'help' => 'Sitenin alt bölümünde görünür.' ),
		'instagram'    => array( 'label' => 'Instagram adresi', 'default' => '', 'help' => 'Örnek: https://instagram.com/kullaniciadi' ),
		'facebook'     => array( 'label' => 'Facebook adresi', 'default' => '', 'help' => '' ),
		'hero_image'   => array( 'label' => 'Anasayfa fotoğrafı', 'default' => 0, 'help' => 'Dik (portre) bir fotoğraf en iyi sonucu verir.', 'type' => 'image' ),
		'hero_title'   => array( 'label' => 'Anasayfa ana başlığı', 'default' => 'Kıbrıs\'ta Klinik Psikolog: Kendinize Ayırdığınız Güvenli Alan', 'help' => '' ),
		'hero_text'    => array( 'label' => 'Anasayfa kısa tanıtım', 'default' => 'Yetişkinler, çocuklar, ergenler, çiftler ve aileler için Kuzey Kıbrıs\'ta yüz yüze ve online psikolojik danışmanlık ve terapi.', 'help' => '', 'type' => 'textarea' ),
	);
}

/**
 * Bir ayarı okur.
 *
 * @param string $key Ayar anahtarı.
 * @return mixed
 */
function fc_opt( $key ) {
	$opts   = get_option( 'fc_site', array() );
	$fields = fc_setting_fields();
	if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
		return $opts[ $key ];
	}
	return isset( $fields[ $key ] ) ? $fields[ $key ]['default'] : '';
}

/**
 * Telefon numarasını yalnız rakamlara indirger (wa.me ve tel: için).
 *
 * @param string $num Numara.
 * @return string
 */
function fc_digits( $num ) {
	return preg_replace( '/\D+/', '', (string) $num );
}

/**
 * WhatsApp bağlantısı; numara yoksa boş döner.
 *
 * @param string $message Hazır mesaj (boşsa ayardaki kullanılır).
 * @return string
 */
function fc_whatsapp_url( $message = '' ) {
	$num = fc_digits( fc_opt( 'whatsapp' ) );
	if ( '' === $num ) {
		return '';
	}
	$message = $message ? $message : fc_opt( 'wa_message' );
	return 'https://wa.me/' . $num . ( $message ? '?text=' . rawurlencode( $message ) : '' );
}

/**
 * Randevu sayfası adresi.
 *
 * @return string
 */
function fc_booking_url() {
	$page = get_page_by_path( 'randevu-al' );
	return $page ? get_permalink( $page ) : home_url( '/randevu-al/' );
}

/**
 * Anasayfa fotoğrafının adresi (yoksa temadaki geçici görsel).
 *
 * @param string $size Görsel boyutu.
 * @return string
 */
function fc_hero_image_url( $size = 'large' ) {
	$id = (int) fc_opt( 'hero_image' );
	if ( $id ) {
		$src = wp_get_attachment_image_url( $id, $size );
		if ( $src ) {
			return $src;
		}
	}
	return get_theme_file_uri( 'assets/images/hero-placeholder.svg' );
}

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Site Bilgileri',
			'Site Bilgileri',
			'edit_theme_options',
			'fc-site',
			'fc_render_settings_page',
			'dashicons-id-alt',
			3
		);
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'fc_site_group',
			'fc_site',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'fc_sanitize_settings',
				'default'           => array(),
			)
		);
	}
);

/**
 * Ayarları temizler.
 *
 * @param array $input Formdan gelen değerler.
 * @return array
 */
function fc_sanitize_settings( $input ) {
	$out = array();
	foreach ( fc_setting_fields() as $key => $field ) {
		$val  = isset( $input[ $key ] ) ? $input[ $key ] : '';
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		if ( 'image' === $type ) {
			$out[ $key ] = absint( $val );
		} elseif ( 'textarea' === $type ) {
			$out[ $key ] = sanitize_textarea_field( $val );
		} elseif ( in_array( $key, array( 'maps_url', 'instagram', 'facebook' ), true ) ) {
			$out[ $key ] = esc_url_raw( $val );
		} elseif ( 'email' === $key ) {
			$out[ $key ] = sanitize_email( $val );
		} else {
			$out[ $key ] = sanitize_text_field( $val );
		}
	}
	return $out;
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'toplevel_page_fc-site' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_register_script( 'fc-admin', false, array( 'jquery', 'media-views' ), FC_VERSION, true );
		wp_enqueue_script( 'fc-admin' );
		wp_add_inline_script(
			'fc-admin',
			"jQuery(function($){\$('.fc-pick').on('click',function(e){e.preventDefault();var b=\$(this),f=wp.media({title:'Fotoğraf seçin',button:{text:'Bu fotoğrafı kullan'},library:{type:'image'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();b.siblings('input').val(a.id);b.siblings('.fc-prev').html('<img src=\"'+(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)+'\" style=\"max-width:220px;border-radius:12px\">');});f.open();});\$('.fc-clear').on('click',function(e){e.preventDefault();\$(this).siblings('input').val('');\$(this).siblings('.fc-prev').empty();});});"
		);
	}
);

/**
 * Ayar ekranı.
 */
function fc_render_settings_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$opts = get_option( 'fc_site', array() );
	?>
	<div class="wrap">
		<h1>Site Bilgileri</h1>
		<p style="font-size:15px;max-width:720px">Buradaki bilgiler sitenin her yerinde (üst menü, alt bölüm, WhatsApp butonu, Google için yapısal veri) otomatik kullanılır. Değiştirip <strong>Kaydet</strong>'e basmanız yeterli.</p>
		<?php if ( isset( $_GET['fc_setup'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success"><p>Sayfalar hazırlandı. Eksik olanlar oluşturuldu, mevcut sayfalarınıza dokunulmadı.</p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'fc_site_group' ); ?>
			<table class="form-table" role="presentation">
				<?php
				foreach ( fc_setting_fields() as $key => $field ) :
					$type = isset( $field['type'] ) ? $field['type'] : 'text';
					$val  = isset( $opts[ $key ] ) && '' !== $opts[ $key ] ? $opts[ $key ] : $field['default'];
					$name = 'fc_site[' . $key . ']';
					?>
					<tr>
						<th scope="row"><label for="fc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php if ( 'textarea' === $type ) : ?>
								<textarea class="large-text" rows="3" id="fc-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $val ); ?></textarea>
							<?php elseif ( 'image' === $type ) : ?>
								<div>
									<input type="hidden" id="fc-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>">
									<div class="fc-prev" style="margin-bottom:8px"><?php echo $val ? wp_get_attachment_image( (int) $val, 'medium', false, array( 'style' => 'max-width:220px;height:auto;border-radius:12px' ) ) : ''; ?></div>
									<button class="button button-primary fc-pick">Fotoğraf seç / yükle</button>
									<button class="button fc-clear">Kaldır</button>
								</div>
							<?php else : ?>
								<input class="regular-text" type="<?php echo 'email' === $key ? 'email' : 'text'; ?>" id="fc-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>">
							<?php endif; ?>
							<?php if ( $field['help'] ) : ?>
								<p class="description"><?php echo esc_html( $field['help'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>

		<hr>
		<h2>Logo</h2>
		<p>Logoyu değiştirmek için: <a href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>">Görünüm &rsaquo; Düzenleyici</a> &rsaquo; üstteki logoya tıklayın &rsaquo; "Değiştir". Ya da <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Ayarlar &rsaquo; Genel</a> &rsaquo; "Site simgesi".</p>

		<hr>
		<h2>Sayfaları hazırla</h2>
		<p>Hakkımda, Hizmetler (ve her hizmetin kendi sayfası), Online Terapi, Blog, Randevu Al, İletişim ve KVKK sayfalarını örnek metinleriyle oluşturur. Daha önce oluşturulmuş sayfalara dokunmaz.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fc_setup">
			<?php wp_nonce_field( 'fc_setup' ); ?>
			<?php submit_button( 'Eksik sayfaları oluştur', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

add_action(
	'admin_post_fc_setup',
	function () {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fc_setup' );
		fc_run_setup();
		wp_safe_redirect( admin_url( 'admin.php?page=fc-site&fc_setup=1' ) );
		exit;
	}
);
