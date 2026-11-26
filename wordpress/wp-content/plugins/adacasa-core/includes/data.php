<?php
/**
 * Sabit sözlükler: diller, bölümler, şehirler/bölgeler, emlak tipleri, tapu türleri.
 * Kod içinde her zaman anahtarlar (ör. "girne", "sale", "konut") kullanılır;
 * ekranda görünen adlar buradan dile göre çekilir. Next.js geçişinde bu dosya JSON'a çevrilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_LANGS = ['tr', 'en', 'ru', 'de', 'ar'];
/** Metinleri elle yazılmış diller; de/ar çevirisi includes/i18n-de-ar.php sözlüğünden (İngilizce metne göre) gelir */
const EC_LANGS_BASE = ['tr', 'en', 'ru'];

/** İngilizce metin → [de, ar] sözlüğü */
function ec_i18n_map(): array
{
    static $m = null;
    if ($m === null) {
        $f = __DIR__ . '/i18n-de-ar.php';
        $m = is_file($f) ? (array) include $f : [];
    }
    return $m;
}

/** İngilizce kaynaktan de/ar çevirisi (yoksa İngilizce) */
function ec_i18n_x(string $en, string $lang): string
{
    $i = ['de' => 0, 'ar' => 1][$lang] ?? null;
    if ($i === null) {
        return $en;
    }
    $t = ec_i18n_map()[$en][$i] ?? '';
    return $t !== '' ? $t : $en;
}

/** ['tr'=>,'en'=>,'ru'=>] biçimindeki her diziye de/ar ekler (iç içe) */
function ec_i18n_fill(array $a): array
{
    if (isset($a['tr'], $a['en'], $a['ru']) && is_string($a['en'])) {
        $a['de'] = $a['de'] ?? ec_i18n_x($a['en'], 'de');
        $a['ar'] = $a['ar'] ?? ec_i18n_x($a['en'], 'ar');
    }
    foreach ($a as $k => $v) {
        if (is_array($v)) {
            $a[$k] = ec_i18n_fill($v);
        }
    }
    return $a;
}

/** Bir metin dizisinden dile göre seçer: ['tr'=>..] ya da [tr, en, ru, (de), (ar)] */
function ec_i18n_pick(array $s, string $lang): string
{
    if (isset($s[$lang]) && is_string($s[$lang])) {
        return $s[$lang];
    }
    $pos = ['tr' => 0, 'en' => 1, 'ru' => 2, 'de' => 3, 'ar' => 4][$lang] ?? 0;
    if (isset($s[$pos]) && is_string($s[$pos])) {
        return $s[$pos];
    }
    $en = $s['en'] ?? $s[1] ?? $s['tr'] ?? $s[0] ?? '';
    return in_array($lang, ['de', 'ar'], true) ? ec_i18n_x((string) $en, $lang) : (string) $en;
}

function ec_is_rtl(string $lang): bool
{
    return $lang === 'ar';
}
const EC_DEFAULT_LANG = 'tr';
const EC_CURRENCIES = ['GBP', 'EUR', 'USD', 'TRY'];

/** URL bölümleri: anahtar => [dil => slug] */
function ec_sections(): array
{
    return [
        'listing' => ['tr' => 'ilan', 'en' => 'property', 'ru' => 'property', 'de' => 'immobilie', 'ar' => 'aqar'],
        'sale'    => ['tr' => 'satilik', 'en' => 'for-sale', 'ru' => 'prodazha', 'de' => 'kaufen', 'ar' => 'lilbay'],
        'rent'    => ['tr' => 'kiralik', 'en' => 'for-rent', 'ru' => 'arenda', 'de' => 'mieten', 'ar' => 'lilijar'],
        'daily'   => ['tr' => 'gunluk-kiralik', 'en' => 'holiday-rentals', 'ru' => 'posutochno', 'de' => 'ferienwohnungen', 'ar' => 'ijar-yawmi'],
        'project' => ['tr' => 'projeler', 'en' => 'projects', 'ru' => 'proekty', 'de' => 'projekte', 'ar' => 'mashari'],
        'add'     => ['tr' => 'ilan-ver', 'en' => 'list-property', 'ru' => 'razmestit', 'de' => 'inserieren', 'ar' => 'adif-ilan'],
        'favs'    => ['tr' => 'favoriler', 'en' => 'favourites', 'ru' => 'izbrannoe', 'de' => 'favoriten', 'ar' => 'mufaddala'],
        'projectd' => ['tr' => 'proje', 'en' => 'project', 'ru' => 'proekt', 'de' => 'projekt', 'ar' => 'mashru'],
        'index'   => ['tr' => 'fiyat-endeksi', 'en' => 'price-index', 'ru' => 'indeks-tsen', 'de' => 'preisindex', 'ar' => 'muashir-alasaar'],
        'login'   => ['tr' => 'giris', 'en' => 'login', 'ru' => 'vhod', 'de' => 'anmelden', 'ar' => 'dukhul'],
        'register' => ['tr' => 'kayit', 'en' => 'register', 'ru' => 'registratsiya', 'de' => 'registrieren', 'ar' => 'tasjil'],
        'verify'  => ['tr' => 'dogrula', 'en' => 'verify', 'ru' => 'podtverzhdenie', 'de' => 'bestaetigen', 'ar' => 'taakid'],
        'panel'   => ['tr' => 'panelim', 'en' => 'my-account', 'ru' => 'kabinet', 'de' => 'mein-konto', 'ar' => 'hisabi'],
        'review'  => ['tr' => 'onay', 'en' => 'review', 'ru' => 'moderatsiya', 'de' => 'pruefung', 'ar' => 'muraja'],
        'office'  => ['tr' => 'ofis', 'en' => 'agent', 'ru' => 'agent', 'de' => 'makler', 'ar' => 'wakil'],
        'offices' => ['tr' => 'firmalar', 'en' => 'agencies', 'ru' => 'agentstva', 'de' => 'makler-verzeichnis', 'ar' => 'wukala'],
        'faq'     => ['tr' => 'sss', 'en' => 'faq', 'ru' => 'voprosy', 'de' => 'haeufige-fragen', 'ar' => 'asila'],
        'legal'   => ['tr' => 'yasal', 'en' => 'legal', 'ru' => 'pravo', 'de' => 'rechtliches', 'ar' => 'qanuni'],
        'area'    => ['tr' => 'bolge', 'en' => 'area', 'ru' => 'rayon', 'de' => 'gebiete', 'ar' => 'manatiq'],
    ];
}

function ec_modes(): array
{
    return ['sale', 'rent', 'daily'];
}

/** Şehirler: anahtar => [adlar, bölgeler] */
function ec_cities(): array
{
    return ec_i18n_fill([
        'lefkosa' => [
            'name' => ['tr' => 'Lefkoşa', 'en' => 'Nicosia', 'ru' => 'Никосия'],
            'districts' => ['Merkez', 'Gönyeli', 'Hamitköy', 'Yenikent', 'Köşklüçiftlik', 'Ortaköy', 'Kızılbaş', 'Taşkınköy', 'Metehan', 'Alayköy', 'Haspolat', 'Değirmenlik'],
        ],
        'girne' => [
            'name' => ['tr' => 'Girne', 'en' => 'Kyrenia', 'ru' => 'Кирения'],
            'districts' => ['Merkez', 'Kaşgar', 'Alsancak', 'Lapta', 'Karşıyaka', 'Çatalköy', 'Ozanköy', 'Edremit', 'Karaoğlanoğlu', 'Doğanköy', 'Zeytinlik', 'Bellapais', 'Esentepe', 'Karmi'],
        ],
        'gazimagusa' => [
            'name' => ['tr' => 'Gazimağusa', 'en' => 'Famagusta', 'ru' => 'Фамагуста'],
            'districts' => ['Merkez', 'Sakarya', 'Karakol', 'Çanakkale', 'Mormenekşe', 'Tuzla', 'Yeniboğaziçi', 'Mutluyaka'],
        ],
        'iskele' => [
            'name' => ['tr' => 'İskele', 'en' => 'Iskele', 'ru' => 'Искеле'],
            'districts' => ['Merkez', 'Long Beach', 'Boğaz', 'Bafra', 'Yeni Erenköy', 'Mehmetçik', 'Kumyalı'],
        ],
        'guzelyurt' => [
            'name' => ['tr' => 'Güzelyurt', 'en' => 'Guzelyurt', 'ru' => 'Гюзельюрт'],
            'districts' => ['Merkez', 'Yeşilyurt', 'Kalkanlı', 'Gaziveren'],
        ],
        'lefke' => [
            'name' => ['tr' => 'Lefke', 'en' => 'Lefka', 'ru' => 'Лефке'],
            'districts' => ['Merkez', 'Gemikonağı', 'Yedidalga'],
        ],
    ]);
}

function ec_city_name(string $key, string $lang): string
{
    $c = ec_cities()[$key] ?? null;
    return $c ? ($c['name'][$lang] ?? $c['name']['tr']) : $key;
}

/** Emlak tipleri ve alt tipleri */
function ec_property_types(): array
{
    return ec_i18n_fill([
        'konut' => [
            'name' => ['tr' => 'Konut', 'en' => 'Residential', 'ru' => 'Жильё'],
            'sub' => [
                'daire'     => ['tr' => 'Daire', 'en' => 'Apartment', 'ru' => 'Квартира'],
                'villa'     => ['tr' => 'Villa', 'en' => 'Villa', 'ru' => 'Вилла'],
                'penthouse' => ['tr' => 'Penthouse', 'en' => 'Penthouse', 'ru' => 'Пентхаус'],
                'mustakil'  => ['tr' => 'Müstakil Ev', 'en' => 'Detached House', 'ru' => 'Частный дом'],
                'ikiz'      => ['tr' => 'İkiz Villa', 'en' => 'Semi-detached Villa', 'ru' => 'Таунхаус'],
                'bungalow'  => ['tr' => 'Bungalow', 'en' => 'Bungalow', 'ru' => 'Бунгало'],
                'studyo'    => ['tr' => 'Stüdyo', 'en' => 'Studio', 'ru' => 'Студия'],
            ],
        ],
        'arsa' => [
            'name' => ['tr' => 'Arsa', 'en' => 'Land', 'ru' => 'Земля'],
            'sub' => [
                'konut-imarli' => ['tr' => 'Konut İmarlı Arsa', 'en' => 'Residential Plot', 'ru' => 'Участок под жильё'],
                'ticari-imarli' => ['tr' => 'Ticari İmarlı Arsa', 'en' => 'Commercial Plot', 'ru' => 'Коммерческий участок'],
                'tarla'        => ['tr' => 'Tarla', 'en' => 'Agricultural Land', 'ru' => 'Сельхоз. земля'],
            ],
        ],
        'ticari' => [
            'name' => ['tr' => 'Ticari', 'en' => 'Commercial', 'ru' => 'Коммерческая'],
            'sub' => [
                'dukkan' => ['tr' => 'Dükkan', 'en' => 'Shop', 'ru' => 'Магазин'],
                'ofis'   => ['tr' => 'Ofis', 'en' => 'Office', 'ru' => 'Офис'],
                'isyeri' => ['tr' => 'İş Yeri', 'en' => 'Business Premises', 'ru' => 'Помещение'],
                'otel'   => ['tr' => 'Otel', 'en' => 'Hotel', 'ru' => 'Отель'],
            ],
        ],
    ]);
}

function ec_type_name(string $type, string $lang): string
{
    return ec_property_types()[$type]['name'][$lang] ?? $type;
}

function ec_subtype_name(string $type, string $sub, string $lang): string
{
    return ec_property_types()[$type]['sub'][$sub][$lang] ?? $sub;
}

function ec_room_options(): array
{
    return ['1+0', '1+1', '2+1', '3+1', '4+1', '5+1', '5+2', '6+'];
}

/**
 * KKTC'ye özel: tapu türleri. Yabancı alıcılar için fiyat kadar önemlidir;
 * satılık ilanlarda zorunludur ve ilan detayında açıklamasıyla gösterilir.
 */
function ec_deed_types(): array
{
    return ec_i18n_fill([
        'turk-kocani' => ['tr' => 'Türk Koçanı', 'en' => 'Turkish Title Deed', 'ru' => 'Турецкий титул (Türk Koçanı)'],
        'esdeger'     => ['tr' => 'Eşdeğer Koçan', 'en' => 'Exchange Title (Eşdeğer)', 'ru' => 'Эквивалентный титул (Eşdeğer)'],
        'tahsis'      => ['tr' => 'Tahsis Koçanı', 'en' => 'Allocation Title (Tahsis)', 'ru' => 'Титул по распределению (Tahsis)'],
        'vakif'       => ['tr' => 'Vakıf (Evkaf) Arazisi', 'en' => 'Evkaf (Foundation) Land', 'ru' => 'Земля фонда Эвкаф (Vakıf)'],
        'proje'       => ['tr' => 'Projeden (Sözleşmeli, tapu bekleniyor)', 'en' => 'Off-plan (Contract, title pending)', 'ru' => 'Строящийся объект (договор, титул ожидается)'],
        'diger'       => ['tr' => 'Diğer / İlan sahibine sorun', 'en' => 'Other / ask the seller', 'ru' => 'Другое / уточните у продавца'],
    ]);
}

/** Tapu türlerinin kısa, genel açıklamaları (hukuki tavsiye değildir) */
function ec_deed_desc(string $key, string $lang): string
{
    $d = [
        'turk-kocani' => [
            'tr' => '1974 öncesinden Kıbrıslı Türk mülkiyetinde olan arazi için verilen tapu. Mülkiyet geçmişi en az tartışmalı kabul edilir.',
            'en' => 'Title for land in Turkish Cypriot ownership since before 1974. Generally regarded as the least disputed type of title.',
            'ru' => 'Титул на землю, принадлежавшую киприотам-туркам ещё до 1974 года. Считается наименее спорным.',
        ],
        'esdeger' => [
            'tr' => 'Güneyde mal bırakan Kıbrıslı Türklere, karşılığında kuzeyde verilen mülk için düzenlenen tapu. Yaygın olarak alınıp satılır.',
            'en' => 'Issued to Turkish Cypriots for property in the north in exchange for property they left in the south. Widely traded.',
            'ru' => 'Выдан киприотам-туркам на имущество на севере взамен оставленного на юге. Широко используется в сделках.',
        ],
        'tahsis' => [
            'tr' => 'Devletin tahsis ettiği mülk. Hukuki durumu daha karmaşık olabilir; devrin mümkün olup olmadığını avukatınıza kontrol ettirin.',
            'en' => 'Property allocated by the state. Its legal position can be more complex; have a lawyer confirm it can be transferred.',
            'ru' => 'Имущество, выделенное государством. Правовой статус может быть сложнее; уточните у юриста возможность передачи.',
        ],
        'vakif' => [
            'tr' => 'Evkaf İdaresi’ne ait arazi. Genellikle mülkiyet değil, uzun süreli kullanım/kira hakkı devredilir. Koşulları mutlaka sorun.',
            'en' => 'Land owned by the Evkaf Administration. Usually a long-term lease or usage right is transferred, not ownership. Ask about the terms.',
            'ru' => 'Земля управления Эвкаф. Обычно передаётся право долгосрочной аренды, а не собственность. Уточните условия.',
        ],
        'proje' => [
            'tr' => 'İnşaatı süren projelerde daireye ait tapu inşaat bitince çıkar. Satış sözleşmesinin Tapu Dairesi’ne kaydı alıcıyı korur.',
            'en' => 'For developments under construction, the unit’s own title is issued on completion. Registering the sales contract at the Land Registry protects the buyer.',
            'ru' => 'Для строящихся объектов отдельный титул выдаётся после завершения. Регистрация договора в земельном реестре защищает покупателя.',
        ],
        'diger' => [
            'tr' => 'Tapu durumu ilan sahibinden öğrenilmeli ve satın almadan önce avukata kontrol ettirilmelidir.',
            'en' => 'Confirm the title status with the seller and have it checked by a lawyer before buying.',
            'ru' => 'Уточните статус титула у продавца и проверьте его у юриста перед покупкой.',
        ],
    ];
    return isset($d[$key]) ? ec_i18n_pick($d[$key], $lang) : '';
}

/** İlan özellikleri (çoklu seçim) */
function ec_features(): array
{
    return ec_i18n_fill([
        'deniz_manzarasi' => ['tr' => 'Deniz manzarası', 'en' => 'Sea view', 'ru' => 'Вид на море'],
        'dag_manzarasi'   => ['tr' => 'Dağ manzarası', 'en' => 'Mountain view', 'ru' => 'Вид на горы'],
        'denize_sifir'    => ['tr' => 'Denize sıfır', 'en' => 'Beachfront', 'ru' => 'Первая линия'],
        'ozel_havuz'      => ['tr' => 'Özel havuz', 'en' => 'Private pool', 'ru' => 'Частный бассейн'],
        'ortak_havuz'     => ['tr' => 'Ortak havuz', 'en' => 'Communal pool', 'ru' => 'Общий бассейн'],
        'bahce'           => ['tr' => 'Bahçe', 'en' => 'Garden', 'ru' => 'Сад'],
        'teras'           => ['tr' => 'Teras / çatı terası', 'en' => 'Terrace / roof terrace', 'ru' => 'Терраса'],
        'balkon'          => ['tr' => 'Balkon', 'en' => 'Balcony', 'ru' => 'Балкон'],
        'asansor'         => ['tr' => 'Asansör', 'en' => 'Lift', 'ru' => 'Лифт'],
        'otopark'         => ['tr' => 'Otopark', 'en' => 'Parking', 'ru' => 'Парковка'],
        'kapali_otopark'  => ['tr' => 'Kapalı otopark', 'en' => 'Covered parking', 'ru' => 'Крытая парковка'],
        'klima'           => ['tr' => 'Klima', 'en' => 'Air conditioning', 'ru' => 'Кондиционер'],
        'merkezi_isitma'  => ['tr' => 'Merkezi / yerden ısıtma', 'en' => 'Central / underfloor heating', 'ru' => 'Центр. / тёплый пол'],
        'somine'          => ['tr' => 'Şömine', 'en' => 'Fireplace', 'ru' => 'Камин'],
        'jenerator'       => ['tr' => 'Jeneratör', 'en' => 'Generator', 'ru' => 'Генератор'],
        'gunes_paneli'    => ['tr' => 'Güneş paneli / güneş enerjisi', 'en' => 'Solar panels', 'ru' => 'Солнечные панели'],
        'su_deposu'       => ['tr' => 'Su deposu', 'en' => 'Water tank', 'ru' => 'Резервуар для воды'],
        'site_icinde'     => ['tr' => 'Site içinde', 'en' => 'Gated complex', 'ru' => 'Закрытый комплекс'],
        'guvenlik'        => ['tr' => '7/24 güvenlik', 'en' => '24/7 security', 'ru' => 'Охрана 24/7'],
        'spor_salonu'     => ['tr' => 'Spor salonu', 'en' => 'Gym', 'ru' => 'Спортзал'],
        'beyaz_esya'      => ['tr' => 'Beyaz eşya', 'en' => 'White goods', 'ru' => 'Бытовая техника'],
        'akilli_ev'       => ['tr' => 'Akıllı ev', 'en' => 'Smart home', 'ru' => 'Умный дом'],
        'evcil_hayvan'    => ['tr' => 'Evcil hayvana uygun', 'en' => 'Pets allowed', 'ru' => 'Можно с животными'],
        'ogrenciye_uygun' => ['tr' => 'Öğrenciye uygun', 'en' => 'Suitable for students', 'ru' => 'Подходит студентам'],
        'engelli_erisimi' => ['tr' => 'Engelli erişimi', 'en' => 'Wheelchair access', 'ru' => 'Доступ для колясок'],
    ]);
}

/** İlan özelliklerinin grupları (ilan detayında dış / iç / konum diye gösterilir) */
function ec_feature_groups(): array
{
    return ec_i18n_fill([
        'outdoor' => [
            'name' => ['tr' => 'Dış özellikler', 'en' => 'Exterior', 'ru' => 'Снаружи'],
            'keys' => ['ozel_havuz', 'ortak_havuz', 'bahce', 'teras', 'balkon', 'otopark', 'kapali_otopark', 'gunes_paneli', 'su_deposu', 'jenerator'],
        ],
        'indoor' => [
            'name' => ['tr' => 'İç özellikler', 'en' => 'Interior', 'ru' => 'Внутри'],
            'keys' => ['klima', 'merkezi_isitma', 'somine', 'beyaz_esya', 'akilli_ev', 'asansor', 'engelli_erisimi'],
        ],
        'location' => [
            'name' => ['tr' => 'Konum ve çevre', 'en' => 'Location & surroundings', 'ru' => 'Расположение'],
            'keys' => ['deniz_manzarasi', 'dag_manzarasi', 'denize_sifir', 'site_icinde', 'guvenlik', 'spor_salonu'],
        ],
        'usage' => [
            'name' => ['tr' => 'Kullanım', 'en' => 'Suitability', 'ru' => 'Условия'],
            'keys' => ['evcil_hayvan', 'ogrenciye_uygun'],
        ],
    ]);
}

/** Proje tipleri */
function ec_project_types(): array
{
    return ec_i18n_fill([
        'konut'  => ['tr' => 'Konut projesi', 'en' => 'Residential', 'ru' => 'Жилой комплекс'],
        'villa'  => ['tr' => 'Villa projesi', 'en' => 'Villa development', 'ru' => 'Виллы'],
        'karma'  => ['tr' => 'Karma (konut + ticari)', 'en' => 'Mixed-use', 'ru' => 'Многофункциональный'],
        'ticari' => ['tr' => 'Ticari', 'en' => 'Commercial', 'ru' => 'Коммерческий'],
        'tatil'  => ['tr' => 'Tatil köyü / rezidans', 'en' => 'Resort / residence', 'ru' => 'Курортный комплекс'],
    ]);
}

/** İnşaat durumu */
function ec_build_statuses(): array
{
    return ec_i18n_fill([
        'planned'   => ['tr' => 'Projeden (başlamadı)', 'en' => 'Off-plan', 'ru' => 'Котлован / проект'],
        'ongoing'   => ['tr' => 'İnşaat devam ediyor', 'en' => 'Under construction', 'ru' => 'Строится'],
        'completed' => ['tr' => 'Tamamlandı', 'en' => 'Completed', 'ru' => 'Сдан'],
    ]);
}

/** Şehir merkezleri (harita ve yakın yerler için yaklaşık konum) */
function ec_city_coords(): array
{
    return ['lefkosa' => [35.185, 33.38], 'girne' => [35.335, 33.32], 'gazimagusa' => [35.125, 33.94], 'iskele' => [35.29, 33.89], 'guzelyurt' => [35.20, 32.99], 'lefke' => [35.11, 32.85]];
}

/** Proje sosyal alanları */
function ec_amenities(): array
{
    return ec_i18n_fill([
        'havuz'        => ['tr' => 'Açık havuz', 'en' => 'Outdoor pool', 'ru' => 'Открытый бассейн'],
        'kapali_havuz' => ['tr' => 'Kapalı havuz', 'en' => 'Indoor pool', 'ru' => 'Крытый бассейн'],
        'spa'          => ['tr' => 'SPA / sauna / hamam', 'en' => 'Spa / sauna / hammam', 'ru' => 'Спа / сауна / хаммам'],
        'spor_salonu'  => ['tr' => 'Spor salonu', 'en' => 'Gym', 'ru' => 'Спортзал'],
        'resepsiyon'   => ['tr' => 'Resepsiyon / concierge', 'en' => 'Reception / concierge', 'ru' => 'Ресепшн / консьерж'],
        'restoran'     => ['tr' => 'Restoran / kafe', 'en' => 'Restaurant / café', 'ru' => 'Ресторан / кафе'],
        'market'       => ['tr' => 'Market', 'en' => 'Mini market', 'ru' => 'Магазин'],
        'cocuk_parki'  => ['tr' => 'Çocuk parkı', 'en' => 'Playground', 'ru' => 'Детская площадка'],
        'tenis'        => ['tr' => 'Tenis / basketbol sahası', 'en' => 'Tennis / basketball court', 'ru' => 'Теннис / баскетбол'],
        'otopark'      => ['tr' => 'Otopark', 'en' => 'Parking', 'ru' => 'Парковка'],
        'guvenlik'     => ['tr' => '7/24 güvenlik', 'en' => '24/7 security', 'ru' => 'Охрана 24/7'],
        'jenerator'    => ['tr' => 'Merkezi jeneratör', 'en' => 'Central generator', 'ru' => 'Центральный генератор'],
        'plaj'         => ['tr' => 'Plaja erişim / servis', 'en' => 'Beach access / shuttle', 'ru' => 'Доступ к пляжу / трансфер'],
        'kira_garanti' => ['tr' => 'Kira garantisi', 'en' => 'Rental guarantee', 'ru' => 'Гарантированная аренда'],
    ]);
}

/** Üyelik türleri. needs_review: ilanları yönetici onayından geçer */
function ec_member_kinds(): array
{
    return ec_i18n_fill([
        'ofis'     => ['name' => ['tr' => 'Emlak Ofisi', 'en' => 'Estate Agency', 'ru' => 'Агентство недвижимости'], 'needs_review' => false, 'account_review' => true, 'projects' => true],
        'insaat'   => ['name' => ['tr' => 'İnşaat Firması', 'en' => 'Developer', 'ru' => 'Застройщик'], 'needs_review' => false, 'account_review' => true, 'projects' => true],
        'danisman' => ['name' => ['tr' => 'Bireysel Danışman', 'en' => 'Independent Agent', 'ru' => 'Частный агент'], 'needs_review' => true, 'account_review' => false, 'projects' => false],
        'sahibi'   => ['name' => ['tr' => 'Mülk Sahibi (Sahibinden)', 'en' => 'Private Owner', 'ru' => 'Собственник'], 'needs_review' => true, 'account_review' => false, 'projects' => false],
    ]);
}

/** Sosyal ağlar: anahtar => [ad, adres örneği] */
function ec_social_networks(): array
{
    return ec_i18n_fill([
        'instagram' => ['Instagram', 'https://instagram.com/...'],
        'facebook'  => ['Facebook', 'https://facebook.com/...'],
        'tiktok'    => ['TikTok', 'https://tiktok.com/@...'],
        'youtube'   => ['YouTube', 'https://youtube.com/@...'],
        'x'         => ['X (Twitter)', 'https://x.com/...'],
        'linkedin'  => ['LinkedIn', 'https://linkedin.com/...'],
        'telegram'  => ['Telegram', 'https://t.me/...'],
        'whatsapp'  => ['WhatsApp', 'https://wa.me/90...'],
    ]);
}

/** Sosyal ağ linklerini temizler (sadece http/https, bilinen ağlar) */
function ec_clean_socials(array $in): array
{
    $out = [];
    foreach (ec_social_networks() as $k => $_) {
        $u = trim((string) ($in[$k] ?? ''));
        if ($u === '') {
            continue;
        }
        if (!preg_match('#^https?://#i', $u)) {
            $u = 'https://' . ltrim($u, '/');
        }
        $u = esc_url_raw($u, ['http', 'https']);
        if ($u) {
            $out[$k] = mb_substr($u, 0, 255);
        }
    }
    return $out;
}

/** Resmi kurlar (TCMB → KKTC MB → ECB): 1 GBP karşılığı. Yönetim ekranından da güncellenir. */
function ec_rates_official(): array
{
    // Yedek değerler (Kasım 2026); ilk otomatik güncellemeden sonra kullanılmaz.
    $default = ['GBP' => 1, 'EUR' => 1.19, 'USD' => 1.31, 'TRY' => 66.3];
    $saved = get_option('ec_rates');
    return is_array($saved) ? array_merge($default, $saved) : $default;
}

/** Sitede kullanılan kurlar: gün içi (piyasa) kuru açık ve tazeyse onunla, değilse resmi kur */
function ec_rates(): array
{
    $r = ec_rates_official();
    if (function_exists('ec_rates_live_current') && ($live = ec_rates_live_current())) {
        $r = array_merge($r, $live['rates']);
    }
    return $r;
}

function ec_to_gbp(float $amount, string $currency): float
{
    $r = ec_rates()[$currency] ?? 1;
    return $r > 0 ? $amount / $r : $amount;
}

/** Türkçe ve Rusça karakterleri URL'ye uygun Latin harflere çevirir */
function ec_slugify(string $text): string
{
    $map = [
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $text = mb_strtolower(strtr($text, $map));
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return mb_substr($text, 0, 80) ?: 'ilan';
}

/**
 * sanitize_text_field, "%40" gibi dizileri URL kodu sanıp siler ("%40 peşin" → " peşin").
 * Yüzde işareti geçen metinler (ödeme planı, açıklama) için önce korunur.
 */
function ec_clean_text(string $v): string
{
    return str_replace('{{pct}}', '%', sanitize_text_field(str_replace('%', '{{pct}}', $v)));
}

function ec_clean_textarea(string $v): string
{
    return str_replace('{{pct}}', '%', sanitize_textarea_field(str_replace('%', '{{pct}}', $v)));
}
