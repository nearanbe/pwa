<?php
/**
 * "Hakkımda metni" ekranı: Feriha Hanım'ın eğitim, uzmanlık, deneyim ve
 * tanıtım bilgilerini teknik bilgi gerektirmeden kendisinin güncelleyebilmesi
 * için basit bir form. İçerik [fc_hakkimda] kısa koduyla Hakkımda sayfasında
 * otomatik görünür.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

/**
 * Varsayılan Hakkımda içeriği.
 *
 * @return array
 */
function fc_about_defaults() {
	$name = fc_opt( 'person_name' );
	return array(
		'photo'       => 0,
		'intro'       => 'Merhaba, ben ' . $name . '. Kuzey Kıbrıs\'ta yetişkinler, çocuklar, ergenler, çiftler ve ailelerle yüz yüze ve online olarak çalışan bir uzman klinik psikoloğum.',
		'education'   => "Lisans: Psikoloji — (üniversite adı, yıl)\nYüksek Lisans: Klinik Psikoloji — (üniversite adı, yıl)",
		'certificates' => '',
		'specialties' => "Bireysel ve yetişkin terapisi\nBilişsel Davranışçı Terapi (BDT)\nÇift, evlilik ve aile terapisi\nÇocuk ve ergen terapisi\nPsikolojik test ve ölçekler (MMPI, Beck, AGTE)",
		'experience'  => '',
		'approach'    => 'Her danışanın hikâyesinin kendine özgü olduğuna inanıyorum. Görüşmelerde yargısız, güvenli ve saygılı bir alan oluşturmayı; bilimsel temelli yöntemleri sizin ihtiyaçlarınıza göre uyarlamayı önemsiyorum.',
	);
}

/**
 * Bir Hakkımda alanını okur.
 *
 * @param string $key Anahtar.
 * @return mixed
 */
function fc_about( $key ) {
	$opts     = get_option( 'fc_about', array() );
	$defaults = fc_about_defaults();
	if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
		return $opts[ $key ];
	}
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/**
 * Çok satırlı metni <ul> listesine çevirir (boş satırlar atlanır).
 *
 * @param string $text Metin.
 * @return string
 */
function fc_about_list( $text ) {
	$items = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) );
	if ( ! $items ) {
		return '';
	}
	$out = '<ul>';
	foreach ( $items as $item ) {
		$out .= '<li>' . esc_html( $item ) . '</li>';
	}
	return $out . '</ul>';
}

/**
 * Çok satırlı metni paragraflara çevirir.
 *
 * @param string $text Metin.
 * @return string
 */
function fc_about_paras( $text ) {
	$paras = array_filter( array_map( 'trim', preg_split( '/\r\n\r\n|\n\n/', (string) $text ) ) );
	$out   = '';
	foreach ( $paras as $p ) {
		$out .= '<p>' . nl2br( esc_html( $p ) ) . '</p>';
	}
	return $out;
}

/**
 * [fc_hakkimda] — Yönetimdeki "Hakkımda metni" bilgilerini gösterir.
 */
add_shortcode(
	'fc_hakkimda',
	function () {
		$photo_id = (int) fc_about( 'photo' );
		$photo    = $photo_id ? wp_get_attachment_image( $photo_id, 'large', false, array( 'class' => 'fc-about__photo', 'loading' => 'eager' ) ) : '';

		$out  = '<div class="fc-about">';
		if ( $photo ) {
			$out .= '<figure class="fc-about__figure">' . $photo . '</figure>';
		}
		$out .= '<div class="fc-about__intro">' . fc_about_paras( fc_about( 'intro' ) ) . '</div>';

		$sections = array(
			'Eğitim'              => fc_about_list( fc_about( 'education' ) ),
			'Sertifikalar'        => fc_about_list( fc_about( 'certificates' ) ),
			'Uzmanlık alanlarım'  => fc_about_list( fc_about( 'specialties' ) ),
			'Deneyim'             => fc_about_list( fc_about( 'experience' ) ),
		);
		foreach ( $sections as $title => $html ) {
			if ( $html ) {
				$out .= '<h2>' . esc_html( $title ) . '</h2>' . $html;
			}
		}

		$approach = fc_about_paras( fc_about( 'approach' ) );
		if ( $approach ) {
			$out .= '<h2>Çalışma yaklaşımım</h2>' . $approach;
		}
		$out .= '</div>';
		return $out;
	}
);

/* Yönetim menüsü: Site Bilgileri › Hakkımda metni. */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'fc-site', 'Hakkımda metni', 'Hakkımda metni', 'edit_theme_options', 'fc-about', 'fc_render_about_page' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'fc_about_group',
			'fc_about',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'fc_sanitize_about',
				'default'           => array(),
			)
		);
	}
);

/**
 * Hakkımda alanlarını temizler.
 *
 * @param array $input Formdan gelen değerler.
 * @return array
 */
function fc_sanitize_about( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	$out['photo']        = absint( $input['photo'] ?? 0 );
	$out['intro']        = sanitize_textarea_field( $input['intro'] ?? '' );
	$out['education']    = sanitize_textarea_field( $input['education'] ?? '' );
	$out['certificates'] = sanitize_textarea_field( $input['certificates'] ?? '' );
	$out['specialties']  = sanitize_textarea_field( $input['specialties'] ?? '' );
	$out['experience']   = sanitize_textarea_field( $input['experience'] ?? '' );
	$out['approach']     = sanitize_textarea_field( $input['approach'] ?? '' );
	return $out;
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( false === strpos( (string) $hook, 'fc-about' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_register_script( 'fc-about-admin', false, array( 'jquery', 'media-views' ), FC_VERSION, true );
		wp_enqueue_script( 'fc-about-admin' );
		wp_add_inline_script(
			'fc-about-admin',
			"jQuery(function($){\$('.fc-pick').on('click',function(e){e.preventDefault();var b=\$(this),f=wp.media({title:'Fotoğraf seçin',button:{text:'Bu fotoğrafı kullan'},library:{type:'image'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();b.siblings('input').val(a.id);b.siblings('.fc-prev').html('<img src=\"'+(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)+'\" style=\"max-width:220px;border-radius:12px\">');});f.open();});\$('.fc-clear').on('click',function(e){e.preventDefault();\$(this).siblings('input').val('');\$(this).siblings('.fc-prev').empty();});});"
		);
	}
);

/** Hakkımda metni ekranı. */
function fc_render_about_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$fields = array(
		'intro'        => array( 'Kısa tanıtım', 'Kendinizi birkaç cümleyle tanıtın. Alt alta yazarsanız ayrı paragraf olur.' ),
		'education'    => array( 'Eğitim', 'Her satıra bir tane. Örnek: "Lisans: Psikoloji — Doğu Akdeniz Üniversitesi, 2015".' ),
		'certificates' => array( 'Sertifikalar', 'Her satıra bir tane. Boş bırakırsanız bu başlık gösterilmez.' ),
		'specialties'  => array( 'Uzmanlık alanları', 'Her satıra bir tane.' ),
		'experience'   => array( 'Deneyim', 'Çalıştığınız kurumlar, yıllar vb. Her satıra bir tane. Boş bırakılabilir.' ),
		'approach'     => array( 'Çalışma yaklaşımım', 'Danışanlarınıza yaklaşımınızı anlatın. Alt alta yazarsanız ayrı paragraf olur.' ),
	);
	$about_page = get_page_by_path( 'hakkimda' );
	?>
	<div class="wrap">
		<h1>Hakkımda metni</h1>
		<p style="font-size:15px;max-width:720px">Bu bilgiler <strong>Hakkımda</strong> sayfanızda otomatik görünür. Doldurup <strong>Kaydet</strong>'e basın. Boş bıraktığınız başlıklar sayfada gösterilmez.</p>
		<?php if ( isset( $_GET['applied'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>Hakkımda sayfası bu bilgilerle güncellendi.</p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'fc_about_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Fotoğrafınız</th>
					<td>
						<input type="hidden" name="fc_about[photo]" value="<?php echo esc_attr( fc_about( 'photo' ) ); ?>">
						<div class="fc-prev" style="margin-bottom:8px"><?php echo (int) fc_about( 'photo' ) ? wp_get_attachment_image( (int) fc_about( 'photo' ), 'medium', false, array( 'style' => 'max-width:220px;height:auto;border-radius:12px' ) ) : ''; ?></div>
						<button class="button button-primary fc-pick">Fotoğraf seç / yükle</button>
						<button class="button fc-clear">Kaldır</button>
						<p class="description">Hakkımda sayfasında görünür. İsteğe bağlıdır.</p>
					</td>
				</tr>
				<?php foreach ( $fields as $key => $f ) : ?>
					<tr>
						<th scope="row"><label for="fc-ab-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
						<td>
							<textarea class="large-text" rows="<?php echo in_array( $key, array( 'intro', 'approach' ), true ) ? 4 : 5; ?>" id="fc-ab-<?php echo esc_attr( $key ); ?>" name="fc_about[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( fc_about( $key ) ); ?></textarea>
							<p class="description"><?php echo esc_html( $f[1] ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>

		<hr>
		<h2>Hakkımda sayfasına uygula</h2>
		<?php if ( $about_page ) : ?>
			<p>Yukarıdaki bilgilerin <a href="<?php echo esc_url( get_permalink( $about_page ) ); ?>" target="_blank" rel="noopener">Hakkımda sayfanızda</a> görünmesi için bir kez bu düğmeye basın. (Önce yukarıdan <strong>Kaydet</strong> demeyi unutmayın.)</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Hakkımda sayfasının içeriği, buradaki bilgilerle değiştirilecek. Devam edilsin mi?')">
				<input type="hidden" name="action" value="fc_about_apply">
				<?php wp_nonce_field( 'fc_about_apply' ); ?>
				<?php submit_button( 'Hakkımda sayfasını bu bilgilerle güncelle', 'secondary', 'submit', false ); ?>
			</form>
		<?php else : ?>
			<p>Henüz bir "Hakkımda" sayfası yok. Önce <a href="<?php echo esc_url( admin_url( 'admin.php?page=fc-site' ) ); ?>">Site Bilgileri &rsaquo; Sayfaları hazırla</a> adımını çalıştırın.</p>
		<?php endif; ?>
	</div>
	<?php
}

add_action(
	'admin_post_fc_about_apply',
	function () {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'fc_about_apply' );
		$page = get_page_by_path( 'hakkimda' );
		if ( $page ) {
			$content = "<!-- wp:shortcode -->\n[fc_hakkimda]\n<!-- /wp:shortcode -->\n\n"
				. '<!-- wp:pattern {"slug":"feriha-celik/cta"} /-->';
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => $content,
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=fc-about&applied=1' ) );
		exit;
	}
);
