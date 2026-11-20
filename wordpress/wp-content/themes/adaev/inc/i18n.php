<?php
/**
 * Arayüz metinleri (TR / EN / RU). Çeviri eklentisi kullanılmaz.
 * Yeni metin eklerken üç dili birlikte ekleyin; eksik dil Türkçeye düşer.
 */

if (!defined('ABSPATH')) {
    exit;
}

function adaev_strings(): array
{
    return [
        'tagline' => ['Kıbrıs Emlak İlanları', 'North Cyprus Property Listings', 'Недвижимость Северного Кипра'],
        'hero_title' => ['Adanın en seçkin evleri', 'The finest homes on the island', 'Лучшие дома острова'],
        'hero_sub' => ['Girne’den Gazimağusa’ya satılık, kiralık ve günlük kiralık ilanlar', 'Homes for sale, long-term and holiday rental from Kyrenia to Famagusta', 'Продажа, аренда и посуточная аренда от Кирении до Фамагусты'],
        'hero_cta' => ['İlanları keşfet', 'Explore listings', 'Смотреть объявления'],
        'view_listing' => ['İlanı incele', 'View property', 'Подробнее'],
        'listing_no' => ['İlan No #', 'Ref. No #', '№ объявления'],
        'ref' => ['İlan No', 'Ref.', '№'],
        'favourites' => ['Favoriler', 'Favourites', 'Избранное'],
        'register' => ['Kayıt Ol', 'Register', 'Регистрация'],
        'add_listing' => ['İlan Ver', 'List Property', 'Разместить'],
        'sale' => ['Satılık', 'For Sale', 'Продажа'],
        'rent' => ['Kiralık', 'For Rent', 'Аренда'],
        'daily' => ['Günlük Kiralık', 'Holiday Rentals', 'Посуточно'],
        'projects' => ['Projeler', 'Projects', 'Проекты'],
        'property_type' => ['Emlak tipi', 'Property type', 'Тип объекта'],
        'all' => ['Tümü', 'All', 'Все'],
        'city' => ['Şehir', 'City', 'Город'],
        'district' => ['Bölge', 'Area', 'Район'],
        'rooms' => ['Oda tipi', 'Bedrooms', 'Комнаты'],
        'min_price' => ['En az fiyat', 'Min price', 'Цена от'],
        'max_price' => ['En çok fiyat', 'Max price', 'Цена до'],
        'search' => ['Ara', 'Search', 'Найти'],
        'recent_search' => ['Son aramanız:', 'Recent searches:', 'Недавние поиски:'],
        'featured' => ['Öne çıkan ilanlar', 'Featured properties', 'Рекомендуемые объекты'],
        'featured_badge' => ['★ Öne çıkan', '★ Featured', '★ Рекомендуем'],
        'latest' => ['Son eklenen ilanlar', 'Latest properties', 'Новые объявления'],
        'listings' => ['İlanlar', 'Listings', 'Объявления'],
        'listings_count' => ['%s ilan', '%s properties', 'Объявлений: %s'],
        'see_all' => ['Tümünü gör', 'See all', 'Смотреть все'],
        'agents' => ['Emlak danışmanları', 'Estate agents', 'Агенты по недвижимости'],
        'agent_listings' => ['%s ilan', '%s listings', 'Объявлений: %s'],
        'no_results' => ['Bu kriterlere uygun ilan yok.', 'No properties match these criteria.', 'Нет объектов по этим критериям.'],
        'per_month' => [' / ay', ' / month', ' / мес.'],
        'per_night' => [' / gece', ' / night', ' / ночь'],
        'ad' => ['Reklam', 'Advertisement', 'Реклама'],
        'ad_space' => ['Reklam alanı', 'Ad space', 'Место для рекламы'],
        'sort' => ['Sırala', 'Sort', 'Сортировка'],
        'sort_new' => ['En yeni', 'Newest', 'Новые'],
        'sort_price_asc' => ['Fiyat (artan)', 'Price (low to high)', 'Цена (по возрастанию)'],
        'sort_price_desc' => ['Fiyat (azalan)', 'Price (high to low)', 'Цена (по убыванию)'],
        'prev' => ['Önceki', 'Previous', 'Назад'],
        'next' => ['Sonraki', 'Next', 'Далее'],
        'page' => ['Sayfa', 'Page', 'Страница'],
        'details' => ['İlan bilgileri', 'Property details', 'Характеристики'],
        'description' => ['Açıklama', 'Description', 'Описание'],
        'area' => ['Alan', 'Area', 'Площадь'],
        'bedrooms' => ['Oda sayısı', 'Rooms', 'Комнаты'],
        'baths' => ['Banyo', 'Bathrooms', 'Ванные'],
        'deed' => ['Tapu türü', 'Title deed', 'Тип титула'],
        'furnished' => ['Eşya durumu', 'Furnishing', 'Мебель'],
        'furnished_yes' => ['Eşyalı', 'Furnished', 'С мебелью'],
        'furnished_no' => ['Eşyasız', 'Unfurnished', 'Без мебели'],
        'listing_type' => ['İlan türü', 'Listing type', 'Тип сделки'],
        'location' => ['Konum', 'Location', 'Расположение'],
        'original_price' => ['İlan fiyatı', 'Asking price', 'Цена в объявлении'],
        'show_phone' => ['Telefonu göster', 'Show phone', 'Показать телефон'],
        'no_phone' => ['Numara yok', 'No number', 'Нет номера'],
        'whatsapp' => ['WhatsApp', 'WhatsApp', 'WhatsApp'],
        'wa_msg' => ['Merhaba, %s numaralı ilan hakkında bilgi almak istiyorum: %s', 'Hello, I would like information about property ref %s: %s', 'Здравствуйте, меня интересует объявление № %s: %s'],
        'share' => ['Paylaş', 'Share', 'Поделиться'],
        'copied' => ['Link kopyalandı', 'Link copied', 'Ссылка скопирована'],
        'sold' => ['Satıldı', 'Sold', 'Продано'],
        'deed_warning' => ['Satın almadan önce tapuyu ve belgeleri mutlaka bir avukata kontrol ettirin.', 'Always have the title deed and documents checked by a lawyer before you buy.', 'Перед покупкой обязательно проверьте титул и документы у юриста.'],
        'similar' => ['Benzer ilanlar', 'Similar properties', 'Похожие объекты'],
        'back' => ['Geri', 'Back', 'Назад'],
        'units' => ['%s konut', '%s units', '%s квартир'],
        'delivery' => ['Teslim: %s', 'Completion: %s', 'Сдача: %s'],
        'payment_plan' => ['Ödeme planı', 'Payment plan', 'План оплаты'],
        'not_found' => ['Aradığınız sayfa bulunamadı.', 'The page you are looking for was not found.', 'Страница не найдена.'],
        'home' => ['Ana sayfa', 'Home', 'Главная'],
        'fav_empty' => ['Henüz favori ilanınız yok. İlanlardaki ♥ işaretine dokunarak ekleyebilirsiniz.', 'You have no favourites yet. Tap ♥ on a listing to save it.', 'В избранном пока пусто. Нажмите ♥ на объявлении, чтобы сохранить его.'],
        'reg_title' => ['Kayıt Ol', 'Register', 'Регистрация'],
        'reg_individual' => ['Bireysel', 'Individual', 'Частное лицо'],
        'reg_corporate' => ['Kurumsal', 'Company', 'Компания'],
        'reg_agency' => ['Emlak Acentesi', 'Estate Agency', 'Агентство недвижимости'],
        'reg_builder' => ['İnşaat Firması', 'Developer', 'Застройщик'],
        'reg_consultant' => ['Bireysel Danışman', 'Independent Agent', 'Частный агент'],
        'reg_name' => ['İsim / Firma', 'Name / Company', 'Имя / Компания'],
        'reg_email' => ['E-posta', 'Email', 'Эл. почта'],
        'reg_phone' => ['Cep telefonu', 'Mobile phone', 'Мобильный телефон'],
        'reg_contact' => ['Yetkili isim soyisim', 'Contact person', 'Контактное лицо'],
        'reg_terms' => ['Şartları ve gizlilik politikasını kabul ediyorum', 'I accept the terms and privacy policy', 'Я принимаю условия и политику конфиденциальности'],
        'reg_submit' => ['Kayıt ol', 'Register', 'Зарегистрироваться'],
        'cancel' => ['Vazgeç', 'Cancel', 'Отмена'],
        'close' => ['Kapat', 'Close', 'Закрыть'],
        'reg_required' => ['İsim, e-posta ve onay zorunlu.', 'Name, email and consent are required.', 'Имя, e-mail и согласие обязательны.'],
        'reg_ok' => ['Başvurunuz alındı. Onaylandığında size e-posta ile dönüş yapacağız.', 'Thank you! We will email you once your account is approved.', 'Заявка принята. Мы напишем вам после одобрения.'],
        'reg_err' => ['Başvuru gönderilemedi: ', 'Could not submit: ', 'Не удалось отправить: '],
        'not_found_no' => ['Bu numarada ilan bulunamadı.', 'No property with this reference.', 'Объявление с таким номером не найдено.'],
        'add_title' => ['İlanınızı ücretsiz yayınlayın', 'List your property for free', 'Размещайте объявления бесплатно'],
        'add_sub' => ['AdaEv’de ilan vermek ücretsizdir. Telegram’dan fotoğrafları ve notlarınızı gönderin; ilanınızı 3 dilde hazırlayıp yayınlayalım.', 'Listing on AdaEv is free. Send your photos and notes on Telegram and we will prepare and publish your listing in 3 languages.', 'Размещение на AdaEv бесплатно. Отправьте фото и описание в Telegram — мы подготовим и опубликуем объявление на 3 языках.'],
        'add_step1' => ['Danışman olarak kaydolun', 'Register as an agent', 'Зарегистрируйтесь как агент'],
        'add_step2' => ['Telegram botumuza fotoğrafları ve bilgileri gönderin', 'Send photos and details to our Telegram bot', 'Отправьте фото и данные нашему Telegram-боту'],
        'add_step3' => ['İlanınız Türkçe, İngilizce ve Rusça yayında', 'Your listing goes live in Turkish, English and Russian', 'Объявление публикуется на турецком, английском и русском'],
        'packages' => ['Otomasyon paketleri', 'Automation packages', 'Пакеты автоматизации'],
        'packages_sub' => ['İlanlarınız AdaEv’de her zaman ücretsiz. Paketlerle aynı ilan, kendi tarayıcınızdan 101evler ve hangiev’e de otomatik girilir.', 'Your listings on AdaEv are always free. With a package, the same listing is also entered on 101evler and hangiev from your own browser.', 'На AdaEv объявления всегда бесплатны. С пакетом то же объявление автоматически вносится на 101evler и hangiev из вашего браузера.'],
        'pkg_single' => ['Tek ilan', 'Single listing', 'Одно объявление'],
        'pkg_weekly' => ['Haftalık', 'Weekly', 'Неделя'],
        'pkg_monthly' => ['Aylık', 'Monthly', 'Месяц'],
        'pkg_n' => ['%s ilan · 2 site', '%s listings · 2 sites', '%s объявлений · 2 сайта'],
        'soon' => ['Yakında', 'Coming soon', 'Скоро'],
        'contact_us' => ['Bize yazın', 'Contact us', 'Написать нам'],
        'corporate' => ['Kurumsal', 'Company', 'Компания'],
        'about' => ['Hakkımızda', 'About us', 'О нас'],
        'contact' => ['İletişim', 'Contact', 'Контакты'],
        'privacy' => ['Gizlilik Politikası', 'Privacy Policy', 'Конфиденциальность'],
        'terms' => ['Kullanım Şartları', 'Terms of Use', 'Условия использования'],
        'services' => ['Hizmetlerimiz', 'Services', 'Услуги'],
        'corp_member' => ['Kurumsal Üyelik', 'Agent Membership', 'Членство для агентов'],
        'feat_listing' => ['Öne Çıkan İlan', 'Featured Listing', 'Продвижение объявления'],
        'blog' => ['Blog', 'Blog', 'Блог'],
        'guide' => ['Emlak Rehberi', 'Buying Guide', 'Гид покупателя'],
        'footer_note' => ['Kıbrıs’ın seçkin emlak ilan platformu', 'North Cyprus’ finest property platform', 'Платформа недвижимости Северного Кипра'],
        'rights' => ['Tüm hakları saklıdır.', 'All rights reserved.', 'Все права защищены.'],
        'demo_note' => ['Demo aşaması: ilanlar, firmalar ve kişiler kurgusaldır; kurlar yaklaşıktır.', 'Demo stage: listings, companies and people are fictional; rates are approximate.', 'Демо-версия: объявления, компании и люди вымышлены; курсы приблизительные.'],
        'lang_name' => ['Türkçe', 'English', 'Русский'],
        'in_city' => ['%s', '%s', '%s'],
        'slide' => ['Slayt %s', 'Slide %s', 'Слайд %s'],
        'gallery' => ['Fotoğraf %s / %s', 'Photo %s of %s', 'Фото %s из %s'],
        'no_photo' => ['Fotoğraf yok', 'No photo', 'Нет фото'],
    ];
}

/** Metni geçerli dilde döndürür; %s yer tutucuları için ek argüman alır. */
function ae_t(string $key, ...$args): string
{
    static $idx = ['tr' => 0, 'en' => 1, 'ru' => 2];
    $s = adaev_strings()[$key] ?? null;
    if (!$s) {
        return $key;
    }
    $lang = function_exists('ec_lang') ? ec_lang() : 'tr';
    $txt = $s[$idx[$lang] ?? 0] ?? $s[0];
    return $args ? vsprintf($txt, $args) : $txt;
}

function ae_e(string $key, ...$args): void
{
    echo esc_html(ae_t($key, ...$args));
}
