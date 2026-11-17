<?php
/**
 * Hizmet kataloğu — anasayfadaki 4 grup kart, hizmet alt sayfaları,
 * randevu formundaki hizmet listesi ve schema.org çıktısı buradan beslenir.
 *
 * @package feriha-celik
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hizmet gruplarını döndürür.
 *
 * Her hizmet: title (sayfa başlığı), slug (adres), short (kart/özet cümlesi),
 * for (kimler için — liste maddeleri).
 *
 * @return array<string, array>
 */
function fc_service_groups() {
	$groups = array(
		'bireysel'   => array(
			'title' => 'Bireysel / Yetişkin',
			'icon'  => 'person',
			'intro' => 'Kaygı, stres, depresif duygular ve yaşam geçişlerinde size özel, güvenli bir alan.',
			'items' => array(
				array(
					'title' => 'Bireysel Terapi',
					'slug'  => 'bireysel-terapi',
					'short' => 'Duygularınızı, düşüncelerinizi ve ilişkilerinizi birlikte anlamlandırdığımız, size özel planlanan görüşmeler.',
					'for'   => array( 'Sürekli kaygı, gerginlik veya huzursuzluk hissedenler', 'Moral bozukluğu, isteksizlik ve yorgunluk yaşayanlar', 'Kayıp, ayrılık ya da yaşam değişikliğiyle baş etmeye çalışanlar', 'Kendini daha iyi tanımak isteyen herkes' ),
				),
				array(
					'title' => 'Yetişkin Terapisi',
					'slug'  => 'yetiskin-terapisi',
					'short' => 'İş, ilişki ve aile yaşamının yükleriyle baş ederken yetişkinlere yönelik psikolojik destek.',
					'for'   => array( 'İş ve özel yaşam dengesini kurmakta zorlananlar', 'Tükenmişlik ve yoğun stres yaşayanlar', 'Uyku, iştah ve enerji değişiklikleri fark edenler', 'İlişkilerinde tekrar eden sorunlar yaşayanlar' ),
				),
				array(
					'title' => 'Psikoterapi',
					'slug'  => 'psikoterapi',
					'short' => 'Bilimsel temelli yöntemlerle, ruhsal zorlukların kökenini anlamaya ve değişimi desteklemeye yönelik süreç.',
					'for'   => array( 'Uzun süredir devam eden duygusal zorluklar yaşayanlar', 'Geçmiş deneyimlerin bugününü etkilediğini düşünenler', 'Kalıcı ve derinlemesine bir değişim isteyenler' ),
				),
				array(
					'title' => 'Bilişsel Davranışçı Terapi (BDT)',
					'slug'  => 'bilissel-davranisci-terapi',
					'short' => 'Düşünce, duygu ve davranış arasındaki bağı ele alan, yapılandırılmış ve kanıta dayalı bir terapi yaklaşımı.',
					'for'   => array( 'Kaygı ve panik yakınmaları olanlar', 'Olumsuz düşünce döngülerinde takılı kalanlar', 'Takıntılı düşünceler ve tekrarlayan davranışlar yaşayanlar', 'Somut hedeflerle çalışmak isteyenler' ),
				),
				array(
					'title' => 'Fobilerden Kurtulmak',
					'slug'  => 'fobi-tedavisi',
					'short' => 'Uçak, asansör, hayvan, kalabalık gibi yoğun korkularla adım adım ve güvenle yüzleşmeye yönelik çalışma.',
					'for'   => array( 'Belirli bir nesne ya da durumdan yoğun korku duyanlar', 'Korkusu nedeniyle günlük yaşamı kısıtlananlar', 'Sosyal ortamlarda aşırı kaygı yaşayanlar' ),
				),
			),
		),
		'cift-aile'  => array(
			'title' => 'Çift ve Aile',
			'icon'  => 'heart',
			'intro' => 'İlişkilerde iletişimi güçlendirmek, çatışmaları sağlıklı biçimde çözmek için birlikte çalışıyoruz.',
			'items' => array(
				array(
					'title' => 'Çift Terapisi',
					'slug'  => 'cift-terapisi',
					'short' => 'Birbirini daha iyi anlamak ve ilişkisini güçlendirmek isteyen çiftler için yapılandırılmış görüşmeler.',
					'for'   => array( 'Sık tartışan ve birbirini duymakta zorlanan çiftler', 'Güven sorunu yaşayan çiftler', 'Birlikte önemli bir karar aşamasında olan çiftler' ),
				),
				array(
					'title' => 'Evlilik Terapisi',
					'slug'  => 'evlilik-terapisi',
					'short' => 'Evliliğin farklı dönemlerinde ortaya çıkan sorunlara yönelik, eşlerin birlikte katıldığı destek süreci.',
					'for'   => array( 'Evliliğinde uzaklaşma ya da kopukluk hisseden eşler', 'Çocuk sonrası değişen dengeleri yeniden kurmak isteyenler', 'Aile büyükleriyle sınır sorunları yaşayan eşler' ),
				),
				array(
					'title' => 'Aile Terapisi',
					'slug'  => 'aile-terapisi',
					'short' => 'Aileyi bir bütün olarak ele alan, aile üyelerinin birlikte katıldığı terapi süreci.',
					'for'   => array( 'Ailede tekrar eden çatışmalar yaşayanlar', 'Boşanma, taşınma, kayıp gibi değişimlerden geçen aileler', 'Çocuğunun ya da ergeninin zorlandığı durumlarda tüm aile' ),
				),
				array(
					'title' => 'Aile Danışmanlığı',
					'slug'  => 'aile-danismanligi',
					'short' => 'Anne-babalık, sınır koyma ve aile içi rollerde yol gösterici, kısa süreli danışmanlık.',
					'for'   => array( 'Çocuk yetiştirme konusunda desteğe ihtiyaç duyan ebeveynler', 'Kardeş kıskançlığı, ekran kullanımı gibi konularda yol arayanlar', 'Ebeveynler arasında ortak tutum geliştirmek isteyenler' ),
				),
				array(
					'title' => 'Aile İçi İletişim Sorunları',
					'slug'  => 'aile-ici-iletisim',
					'short' => 'Aile bireylerinin birbirini dinleyebildiği, duygularını güvenle ifade edebildiği bir iletişim için destek.',
					'for'   => array( 'Evde konuşmanın tartışmaya dönüştüğü aileler', 'Duygularını ifade etmekte zorlanan aile üyeleri', 'Ergen çocuğuyla iletişimi kopan ebeveynler' ),
				),
			),
		),
		'cocuk-ergen' => array(
			'title' => 'Çocuk ve Ergen',
			'icon'  => 'child',
			'intro' => 'Çocukların ve gençlerin dünyasını anlayarak, aileyle iş birliği içinde ilerleyen destek.',
			'items' => array(
				array(
					'title' => 'Ergen Terapisi',
					'slug'  => 'ergen-terapisi',
					'short' => 'Ergenlik döneminin duygusal ve sosyal zorluklarında gençlere özel, güvene dayalı görüşmeler.',
					'for'   => array( 'Okul, sınav ve gelecek kaygısı yaşayan gençler', 'Arkadaş ilişkilerinde ve özgüvende zorlananlar', 'Ailesiyle iletişimi zayıflayan ergenler' ),
				),
				array(
					'title' => 'Davranış Bozuklukları',
					'slug'  => 'davranis-bozukluklari',
					'short' => 'Kurallara uymakta zorlanma, saldırganlık, dikkat ve uyum sorunlarında çocuk ve aileye yönelik destek.',
					'for'   => array( 'Evde ya da okulda sık sorun yaşayan çocuklar', 'Dikkat ve odaklanma güçlüğü fark edilen çocuklar', 'Davranışlarıyla başa çıkmakta zorlanan ebeveynler' ),
				),
				array(
					'title' => 'Ağlama ve Öfke Nöbetleri',
					'slug'  => 'aglama-ve-ofke-nobetleri',
					'short' => 'Çocuklarda sık ve yoğun ağlama, öfke patlamaları ve duygu düzenleme güçlüklerine yönelik çalışma.',
					'for'   => array( 'Sık öfke nöbetleri geçiren çocuklar', 'Ayrılık sırasında yoğun ağlayan çocuklar', 'Bu anlarda nasıl davranacağını bilemeyen ebeveynler' ),
				),
			),
		),
		'testler'    => array(
			'title' => 'Testler ve Ölçekler',
			'icon'  => 'clipboard',
			'intro' => 'Değerlendirme sürecini destekleyen, bilimsel geçerliliği olan psikolojik test ve ölçekler.',
			'items' => array(
				array(
					'title' => 'MMPI (Minnesota Çok Yönlü Kişilik Envanteri)',
					'slug'  => 'mmpi-testi',
					'short' => 'Kişilik özelliklerini ve ruhsal durumu çok yönlü olarak değerlendiren, yaygın kullanılan bir envanter.',
					'for'   => array( 'Kapsamlı psikolojik değerlendirme gereken durumlar', 'Kurum ya da iş başvurusu için değerlendirme isteyenler', 'Terapi sürecine başlarken yol haritası çıkarmak isteyenler' ),
				),
				array(
					'title' => 'Beck Anksiyete Ölçeği',
					'slug'  => 'beck-anksiyete-olcegi',
					'short' => 'Kaygı belirtilerinin şiddetini değerlendirmeye yardımcı olan kısa bir öz bildirim ölçeği.',
					'for'   => array( 'Kaygı düzeyini değerlendirmek isteyenler', 'Terapi sürecindeki değişimi izlemek isteyenler' ),
				),
				array(
					'title' => 'Beck Depresyon Ölçeği',
					'slug'  => 'beck-depresyon-olcegi',
					'short' => 'Depresif belirtilerin düzeyini değerlendirmeye yardımcı olan, yaygın kullanılan bir öz bildirim ölçeği.',
					'for'   => array( 'Moral bozukluğu ve isteksizlik yaşayanlar', 'Terapi sürecindeki değişimi izlemek isteyenler' ),
				),
				array(
					'title' => 'AGTE (Ankara Gelişim Tarama Envanteri)',
					'slug'  => 'agte-testi',
					'short' => '0–6 yaş arası çocukların gelişimini dil, motor, sosyal ve öz bakım alanlarında değerlendiren tarama envanteri.',
					'for'   => array( 'Çocuğunun gelişimini takip etmek isteyen aileler', 'Konuşma ya da hareket gelişiminde gecikme fark edenler', 'Okul öncesi dönem değerlendirmesi isteyenler' ),
				),
			),
		),
	);

	/**
	 * Hizmet kataloğunu değiştirmek için filtre.
	 */
	return apply_filters( 'fc_service_groups', $groups );
}

/**
 * Tüm hizmetleri tek listede döndürür (grup anahtarı eklenmiş olarak).
 *
 * @return array
 */
function fc_services_flat() {
	$out = array();
	foreach ( fc_service_groups() as $gkey => $group ) {
		foreach ( $group['items'] as $item ) {
			$item['group']       = $gkey;
			$item['group_title'] = $group['title'];
			$out[]               = $item;
		}
	}
	return $out;
}

/**
 * Bir hizmet sayfasının adresini döndürür. Sayfa yoksa Hizmetler sayfasına döner.
 *
 * @param string $slug Hizmet slug'ı.
 * @return string
 */
function fc_service_url( $slug ) {
	$page = get_page_by_path( 'hizmetler/' . $slug );
	if ( $page && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}
	return home_url( '/hizmetler/' . $slug . '/' );
}

/**
 * Basit satır içi SVG ikonlar (harici istek yok, hızlı).
 *
 * @param string $name İkon adı.
 * @return string
 */
function fc_icon( $name ) {
	$paths = array(
		'person'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
		'heart'     => '<path d="M12 21s-7.5-4.6-9.5-9.3C1.1 8.3 3.3 5 6.7 5c2 0 3.4 1.1 4.3 2.4h2C13.9 6.1 15.3 5 17.3 5c3.4 0 5.6 3.3 4.2 6.7C19.5 16.4 12 21 12 21z"/>',
		'child'     => '<circle cx="12" cy="6" r="3"/><path d="M7 21v-5l-2-3 3-3h8l3 3-2 3v5"/><path d="M10 21v-4h4v4"/>',
		'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M8.5 11l2 2 4-4"/><path d="M8.5 17h7"/>',
		'chat'      => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.9A8 8 0 1 1 21 12z"/>',
		'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'video'     => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3"/>',
		'pin'       => '<path d="M12 21s-7-6.3-7-12a7 7 0 0 1 14 0c0 5.7-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
		'phone'     => '<path d="M5 3h3.5l1.5 5-2 1.5a12 12 0 0 0 6.5 6.5l1.5-2 5 1.5V19a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/>',
		'leaf'      => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19l8-8"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="fc-icon" aria-hidden="true" focusable="false" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $paths[ $name ] . '</svg>';
}
