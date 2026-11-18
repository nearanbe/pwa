<?php
/**
 * Tema etkinleştirildiğinde sayfaları ve temel ayarları hazırlar.
 * Var olan sayfalara asla dokunmaz; yalnızca eksikleri oluşturur.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Blok yardımcıları ---------- */

function fc_b_p( $html ) {
	return "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function fc_b_h( $text, $level = 2 ) {
	$attr = 2 === $level ? '' : ' {"level":' . (int) $level . '}';
	return '<!-- wp:heading' . $attr . " -->\n<h" . $level . ' class="wp-block-heading">' . $text . '</h' . $level . ">\n<!-- /wp:heading -->\n\n";
}

function fc_b_list( $items ) {
	$out = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
	foreach ( $items as $item ) {
		$out .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ul>\n<!-- /wp:list -->\n\n";
}

function fc_b_details( $q, $a ) {
	return "<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>" . $q . "</summary><!-- wp:paragraph -->\n<p>" . $a . "</p>\n<!-- /wp:paragraph --></details>\n<!-- /wp:details -->\n\n";
}

function fc_b_pattern( $slug ) {
	return '<!-- wp:pattern {"slug":"feriha-celik/' . $slug . "\"} /-->\n\n";
}

function fc_b_shortcode( $code ) {
	return "<!-- wp:shortcode -->\n" . $code . "\n<!-- /wp:shortcode -->\n\n";
}

/* ---------- Sayfa içerikleri ---------- */

/**
 * Bir hizmet sayfasının örnek içeriği.
 *
 * @param array  $item  Hizmet.
 * @param string $gkey  Grup anahtarı.
 * @return string
 */
function fc_service_content( $item, $gkey ) {
	$city  = esc_html( fc_opt( 'city' ) );
	$title = esc_html( $item['title'] );
	$c     = fc_b_p( '<strong>' . $title . '</strong> görüşmeleri ' . $city . ' ve Kuzey Kıbrıs genelinde <strong>yüz yüze</strong> ya da her yerden <strong>online</strong> olarak yapılabilir. Aşağıda kimler için uygun olduğunu ve sürecin nasıl ilerlediğini bulabilirsiniz.' );

	$c .= fc_b_h( 'Kimler için uygun?' );
	$c .= fc_b_list( array_map( 'esc_html', $item['for'] ) );

	if ( 'testler' === $gkey ) {
		$c .= fc_b_h( 'Değerlendirme nasıl yapılır?' );
		$c .= fc_b_p( 'Önce kısa bir ön görüşmeyle değerlendirmenin amacını birlikte netleştiriyoruz. Ardından test, sakin ve uygun bir ortamda uygulanır. Sonuçlar tek başına bir tanı anlamına gelmez; klinik görüşmeyle birlikte yorumlanır ve size anlaşılır bir dille aktarılır.' );
		$c .= fc_b_p( 'Değerlendirme sonuçları kişisel ve gizlidir; yalnızca sizinle (çocuklarda ebeveynle) paylaşılır.' );
	} else {
		$c .= fc_b_h( 'Süreç nasıl ilerler?' );
		$c .= fc_b_p( 'İlk görüşmede sizi neyin zorladığını, beklentilerinizi ve hedeflerinizi konuşuyoruz. Ardından size uygun bir yol haritası birlikte belirlenir. Görüşmeler genellikle 50 dakika sürer ve çoğunlukla haftada bir planlanır; süre ve sıklık ihtiyaca göre değişebilir.' );
		$c .= fc_b_p( 'Görüşmelerde paylaştığınız her şey meslek etiği ve gizlilik ilkeleri çerçevesinde korunur.' );
	}

	$c .= fc_b_h( 'Online mı, yüz yüze mi?' );
	$c .= fc_b_p( 'Muayenehanede yüz yüze görüşebileceğimiz gibi, Kıbrıs\'ın her yerinden ya da yurt dışından görüntülü <a href="' . esc_url( home_url( '/online-terapi/' ) ) . '">online terapi</a> ile de görüşebiliriz. Online görüşmeler için yalnızca internet bağlantısı ve sessiz bir oda yeterlidir.' );

	$c .= fc_b_pattern( 'cta' );

	$c .= fc_b_h( 'Sık sorulan sorular' );
	$c .= fc_b_details( 'Görüşmelerde konuştuklarım gizli kalır mı?', 'Evet. Görüşmeler meslek etiği ve gizlilik ilkelerine bağlı olarak yürütülür. Yasal zorunluluklar dışında bilgileriniz kimseyle paylaşılmaz.' );
	$c .= fc_b_details( 'Nasıl randevu alabilirim?', 'Sitedeki <a href="' . esc_url( home_url( '/randevu-al/' ) ) . '">Randevu Al</a> sayfasından uygun gün ve saati seçebilir ya da WhatsApp üzerinden yazabilirsiniz.' );
	$c .= fc_b_details( 'Online görüşme yüz yüze kadar etkili mi?', 'Pek çok konu için online görüşmeler yüz yüze görüşmeler kadar verimli olabilir. Hangisinin size daha uygun olduğuna ilk görüşmede birlikte karar verebiliriz.' );

	return $c;
}

/**
 * Tüm sayfa tanımları: slug => [title, content, template, parent].
 *
 * @return array
 */
function fc_page_definitions() {
	$name = esc_html( fc_opt( 'person_name' ) );

	$about  = fc_b_shortcode( '[fc_hakkimda]' );
	$about .= fc_b_pattern( 'cta' );

	$services  = fc_b_p( 'Kuzey Kıbrıs\'ta yüz yüze ve online olarak sunduğum psikolojik danışmanlık, terapi ve değerlendirme hizmetleri aşağıdadır. Ayrıntılı bilgi için ilgili hizmete tıklayabilirsiniz.' );
	$services .= fc_b_pattern( 'services-grid' );
	$services .= fc_b_pattern( 'cta' );

	$tests = fc_b_p( 'Psikolojik test ve ölçekler, değerlendirme sürecini destekleyen bilimsel araçlardır. Sonuçlar her zaman klinik görüşmeyle birlikte yorumlanır.' );
	foreach ( fc_service_groups()['testler']['items'] as $t ) {
		$tests .= fc_b_h( '<a href="' . esc_url( home_url( '/hizmetler/' . $t['slug'] . '/' ) ) . '">' . esc_html( $t['title'] ) . '</a>', 3 );
		$tests .= fc_b_p( esc_html( $t['short'] ) );
	}
	$tests .= fc_b_pattern( 'cta' );

	$online  = fc_b_p( 'Kıbrıs\'ın neresinde olursanız olun — ya da yurt dışında yaşıyorsanız — evinizin rahatlığında, görüntülü görüşmeyle <strong>online terapi</strong> alabilirsiniz.' );
	$online .= fc_b_h( 'Online terapi nasıl yapılır?' );
	$online .= fc_b_list( array( '<a href="' . esc_url( home_url( '/randevu-al/' ) ) . '">Randevu Al</a> sayfasından "Online" seçeneğiyle size uygun gün ve saati seçin.', 'Randevunuz onaylandığında görüşme bağlantısı size iletilir.', 'Görüşme saatinde sessiz ve rahat bir odada, telefon ya da bilgisayarınızdan bağlanın.' ) );
	$online .= fc_b_h( 'Kimler için uygun?' );
	$online .= fc_b_list( array( 'Muayenehaneye ulaşımı zor olanlar', 'Yoğun iş temposu nedeniyle zaman ayırmakta zorlananlar', 'Yurt dışında yaşayan ve Türkçe terapi almak isteyenler', 'Kendi ortamında daha rahat hisseden danışanlar' ) );
	$online .= fc_b_h( 'Gizlilik' );
	$online .= fc_b_p( 'Online görüşmeler de yüz yüze görüşmelerle aynı gizlilik ve etik ilkelerle yürütülür. Görüşmeler kaydedilmez.' );
	$online .= fc_b_pattern( 'cta' );

	$booking  = fc_b_p( 'Aşağıdan size uygun gün ve saati seçerek randevu talebinizi iletebilirsiniz. Talebiniz en kısa sürede onaylanır ve size bilgi verilir.' );
	$booking .= fc_b_shortcode( '[feriha_randevu]' );

	$contact  = fc_b_pattern( 'contact-info' );

	$kvkk  = fc_b_p( '<em>Bu metin örnek bir şablondur; yayımlamadan önce bilgilerinizi kontrol edip gerekirse bir hukukçuya danışmanız önerilir.</em>' );
	$kvkk .= fc_b_h( 'Veri sorumlusu' );
	$kvkk .= fc_b_p( $name . ' (' . esc_html( fc_opt( 'title' ) ) . ') — ' . esc_html( fc_opt( 'address' ) ? fc_opt( 'address' ) : 'adres' ) . '. Bu metin, 89/2007 sayılı Kişisel Verilerin Korunması Yasası (KKTC) ve ilgili mevzuat kapsamında hazırlanmıştır.' );
	$kvkk .= fc_b_h( 'Hangi verileri topluyoruz?' );
	$kvkk .= fc_b_list( array( 'Randevu formunda paylaştığınız ad soyad, telefon, e-posta, görüşme türü, tercih ettiğiniz hizmet ve not alanına yazdıklarınız', 'Siteyi ziyaret ettiğinizde sunucu tarafından tutulan teknik kayıtlar (IP adresi, tarayıcı bilgisi)' ) );
	$kvkk .= fc_b_h( 'Verileri hangi amaçla kullanıyoruz?' );
	$kvkk .= fc_b_list( array( 'Randevunuzu planlamak, onaylamak ve sizinle iletişim kurmak', 'Yasal yükümlülükleri yerine getirmek' ) );
	$kvkk .= fc_b_h( 'Sağlık verileri' );
	$kvkk .= fc_b_p( 'Randevu formuna yazacağınız bilgiler sağlıkla ilgili özel nitelikli veri içerebilir. Bu nedenle formda yalnızca randevu için gerekli bilgileri paylaşmanızı öneririz; ayrıntıları görüşmede konuşabiliriz. Bu veriler açık rızanız ile işlenir ve üçüncü kişilerle paylaşılmaz.' );
	$kvkk .= fc_b_h( 'Saklama süresi' );
	$kvkk .= fc_b_p( 'Randevu bilgileri, randevu süreci ve yasal saklama süreleri boyunca güvenli biçimde saklanır, süre sonunda silinir.' );
	$kvkk .= fc_b_h( 'Haklarınız' );
	$kvkk .= fc_b_p( 'Verilerinize erişme, düzeltilmesini veya silinmesini isteme ve rızanızı geri çekme hakkına sahipsiniz. Talepleriniz için ' . ( fc_opt( 'email' ) ? esc_html( fc_opt( 'email' ) ) : 'e-posta adresimiz' ) . ' üzerinden bize ulaşabilirsiniz.' );
	$kvkk .= fc_b_h( 'Çerezler' );
	$kvkk .= fc_b_p( 'Bu site yalnızca çalışması için zorunlu olan çerezleri kullanır. Reklam ya da takip çerezi kullanılmaz.' );

	$pages = array(
		'anasayfa'                  => array( 'Anasayfa', '', '', '' ),
		'hakkimda'                  => array( 'Hakkımda', $about, '', '' ),
		'hizmetler'                 => array( 'Hizmetler', $services, '', '' ),
		'testler-ve-olcekler'       => array( 'Testler ve Ölçekler', $tests, '', '' ),
		'online-terapi'             => array( 'Online Terapi', $online, 'page-hizmet', '' ),
		'blog'                      => array( 'Blog', '', '', '' ),
		'randevu-al'                => array( 'Randevu Al', $booking, '', '' ),
		'iletisim'                  => array( 'İletişim', $contact, '', '' ),
		'kvkk-gizlilik-politikasi'  => array( 'KVKK ve Gizlilik Politikası', $kvkk, '', '' ),
	);

	foreach ( fc_service_groups() as $gkey => $group ) {
		foreach ( $group['items'] as $item ) {
			$pages[ 'hizmetler/' . $item['slug'] ] = array( $item['title'], fc_service_content( $item, $gkey ), 'page-hizmet', 'hizmetler', $item['short'] );
		}
	}
	return $pages;
}

/**
 * Kurulumu çalıştırır.
 */
function fc_run_setup() {
	$ids = array();
	foreach ( fc_page_definitions() as $path => $def ) {
		$existing = get_page_by_path( $path, OBJECT, 'page' );
		if ( $existing ) {
			$ids[ $path ] = $existing->ID;
			continue;
		}
		$slug   = basename( $path );
		$parent = $def[3] && isset( $ids[ $def[3] ] ) ? $ids[ $def[3] ] : 0;
		$id     = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $def[0],
				'post_name'    => $slug,
				'post_content' => $def[1],
				'post_excerpt' => isset( $def[4] ) ? $def[4] : '',
				'post_parent'  => $parent,
				'menu_order'   => count( $ids ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			continue;
		}
		if ( $def[2] ) {
			update_post_meta( $id, '_wp_page_template', $def[2] );
		}
		$ids[ $path ] = $id;
	}

	if ( isset( $ids['anasayfa'], $ids['blog'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['anasayfa'] );
		update_option( 'page_for_posts', $ids['blog'] );
	}
	if ( isset( $ids['kvkk-gizlilik-politikasi'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['kvkk-gizlilik-politikasi'] );
	}

	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	if ( ! get_option( 'timezone_string' ) ) {
		update_option( 'timezone_string', 'Europe/Istanbul' );
	}
	update_option( 'date_format', 'j F Y' );
	update_option( 'time_format', 'H:i' );
	update_option( 'start_of_week', 1 );
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	if ( ! get_option( 'blogdescription' ) || 'Just another WordPress site' === get_option( 'blogdescription' ) ) {
		update_option( 'blogdescription', 'Kuzey Kıbrıs\'ta yüz yüze ve online psikolojik danışmanlık ve terapi' );
	}
	update_option( 'fc_setup_done', FC_VERSION );
	flush_rewrite_rules();
}

add_action(
	'after_switch_theme',
	function () {
		if ( ! get_option( 'fc_setup_done' ) ) {
			fc_run_setup();
		}
	}
);

/*
 * Tema, aynı adlı eski bir temanın üzerine yüklendiyse "etkinleştirme" olayı
 * tetiklenmez. Bu durumda kurulum, panele ilk girişte bir kez çalışır.
 */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'fc_setup_done' ) || ! current_user_can( 'edit_theme_options' ) || wp_doing_ajax() ) {
			return;
		}
		fc_run_setup();
	}
);
