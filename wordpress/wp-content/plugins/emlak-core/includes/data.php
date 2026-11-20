<?php
/**
 * Sabit sözlükler: diller, bölümler, şehirler/bölgeler, emlak tipleri, tapu türleri.
 * Kod içinde her zaman anahtarlar (ör. "girne", "sale", "konut") kullanılır;
 * ekranda görünen adlar buradan dile göre çekilir. Next.js geçişinde bu dosya JSON'a çevrilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_LANGS = ['tr', 'en', 'ru'];
const EC_DEFAULT_LANG = 'tr';
const EC_CURRENCIES = ['GBP', 'EUR', 'USD', 'TRY'];

/** URL bölümleri: anahtar => [dil => slug] */
function ec_sections(): array
{
    return [
        'listing' => ['tr' => 'ilan', 'en' => 'property', 'ru' => 'property'],
        'sale'    => ['tr' => 'satilik', 'en' => 'for-sale', 'ru' => 'prodazha'],
        'rent'    => ['tr' => 'kiralik', 'en' => 'for-rent', 'ru' => 'arenda'],
        'daily'   => ['tr' => 'gunluk-kiralik', 'en' => 'holiday-rentals', 'ru' => 'posutochno'],
        'project' => ['tr' => 'projeler', 'en' => 'projects', 'ru' => 'proekty'],
        'add'     => ['tr' => 'ilan-ver', 'en' => 'list-property', 'ru' => 'razmestit'],
        'favs'    => ['tr' => 'favoriler', 'en' => 'favourites', 'ru' => 'izbrannoe'],
    ];
}

function ec_modes(): array
{
    return ['sale', 'rent', 'daily'];
}

/** Şehirler: anahtar => [adlar, bölgeler] */
function ec_cities(): array
{
    return [
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
    ];
}

function ec_city_name(string $key, string $lang): string
{
    $c = ec_cities()[$key] ?? null;
    return $c ? ($c['name'][$lang] ?? $c['name']['tr']) : $key;
}

/** Emlak tipleri ve alt tipleri */
function ec_property_types(): array
{
    return [
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
    ];
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

/** KKTC'ye özel: tapu türleri (yabancı alıcılar için kritik bilgi) */
function ec_deed_types(): array
{
    return [
        'turk-kocani' => ['tr' => 'Türk Koçanı', 'en' => 'Turkish Title Deed', 'ru' => 'Турецкий титул'],
        'esdeger'     => ['tr' => 'Eşdeğer Koçan', 'en' => 'Exchange Title', 'ru' => 'Эквивалентный титул'],
        'tahsis'      => ['tr' => 'Tahsis', 'en' => 'Allocation Title', 'ru' => 'Титул по распределению'],
        'proje'       => ['tr' => 'Projeden (Sözleşmeli)', 'en' => 'Off-plan (Contract)', 'ru' => 'На этапе строительства'],
    ];
}

/** Kurlar: 1 GBP karşılığı. Yönetim ekranından güncellenir. */
function ec_rates(): array
{
    $default = ['GBP' => 1, 'EUR' => 1.17, 'USD' => 1.27, 'TRY' => 58];
    $saved = get_option('ec_rates');
    return is_array($saved) ? array_merge($default, $saved) : $default;
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
