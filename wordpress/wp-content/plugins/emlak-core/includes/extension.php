<?php
/**
 * Chrome eklentisi (101evler ve hangiev modülleri) için API uçları. Eklenti üyenin API anahtarıyla çalışır.
 *   GET  /ext/config                site modüllerinin ayarları (adresler, alan eşleştirmeleri) — eklentiyi
 *                                   yeniden yüklemeden sunucudan güncellenebilir
 *   GET  /ext/listing/{id}          form doldurmak için ilanın tüm alanları + 3 dildeki metinler + fotoğraflar
 *   GET  /ext/syndication           üyenin diğer sitelere girdiği ilanlar
 *   POST /ext/syndication           {"listing_id", "site", "status": filled|published|failed, "remote_url", "remote_id", "note"}
 *   POST /ext/sms-request           {"site": "101evler"} → bot üyeye "SMS kodunu yazın" der
 *   GET  /ext/sms-code?site=...     kod geldiyse bir kez döndürür
 * Son "Yayınla" tıkı her zaman insandadır; captcha aşılmaz.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_EXT_SITES = ['101evler', 'hangiev'];

/** Varsayılan modül ayarları. Siteler formlarını değiştirirse Emlak → Ayarlar → Chrome eklentisi'nden JSON olarak güncellenir. */
function ec_ext_default_config(): array
{
    return [
        'version' => 1,
        'sites' => [
            '101evler' => [
                'name' => '101evler.com',
                'match' => ['https://www.101evler.com/*', 'https://101evler.com/*'],
                'new_url' => 'https://www.101evler.com/ilan-ver',
                // Alan bulma: önce "selectors" (CSS), bulunamazsa etiket metnine göre ("labels") aranır.
                'fields' => [
                    'title' => ['selectors' => ['input[name="title"]', '#title'], 'labels' => ['İlan Başlığı', 'Başlık', 'Title']],
                    'description' => ['selectors' => ['textarea[name="description"]', '#description'], 'labels' => ['Açıklama', 'Description']],
                    'price' => ['selectors' => ['input[name="price"]', '#price'], 'labels' => ['Fiyat', 'Price']],
                    'currency' => ['selectors' => ['select[name="currency"]', '#currency'], 'labels' => ['Para Birimi', 'Döviz', 'Currency'], 'map' => ['GBP' => ['GBP', 'STG', '£', 'Sterlin'], 'EUR' => ['EUR', '€', 'Euro'], 'USD' => ['USD', '$', 'Dolar'], 'TRY' => ['TRY', 'TL', '₺']]],
                    'mode' => ['selectors' => ['select[name="type"]', 'select[name="listing_type"]'], 'labels' => ['İlan Tipi', 'Satılık / Kiralık'], 'map' => ['sale' => ['Satılık', 'For Sale'], 'rent' => ['Kiralık', 'For Rent'], 'daily' => ['Günlük Kiralık', 'Günlük', 'Holiday']]],
                    'subtype' => ['selectors' => ['select[name="category"]', 'select[name="property_type"]'], 'labels' => ['Emlak Tipi', 'Kategori', 'Property Type'], 'map' => ['daire' => ['Daire', 'Apartment'], 'villa' => ['Villa'], 'penthouse' => ['Penthouse'], 'mustakil' => ['Müstakil', 'Müstakil Ev', 'Detached'], 'ikiz' => ['İkiz Villa', 'Semi'], 'bungalow' => ['Bungalow'], 'studyo' => ['Stüdyo', 'Studio'], 'konut-imarli' => ['Arsa', 'Konut İmarlı'], 'ticari-imarli' => ['Ticari İmarlı'], 'tarla' => ['Tarla'], 'dukkan' => ['Dükkan'], 'ofis' => ['Ofis'], 'isyeri' => ['İş Yeri', 'Depo'], 'otel' => ['Otel']]],
                    'city' => ['selectors' => ['select[name="city"]', '#city'], 'labels' => ['Şehir', 'İl', 'City'], 'map' => ['lefkosa' => ['Lefkoşa', 'Nicosia'], 'girne' => ['Girne', 'Kyrenia'], 'gazimagusa' => ['Gazimağusa', 'Mağusa', 'Famagusta'], 'iskele' => ['İskele', 'Iskele'], 'guzelyurt' => ['Güzelyurt'], 'lefke' => ['Lefke']]],
                    'district' => ['selectors' => ['select[name="district"]', 'select[name="region"]', '#district'], 'labels' => ['Bölge', 'Semt', 'Köy', 'Area'], 'after' => 'city', 'wait' => 1500],
                    'rooms' => ['selectors' => ['select[name="rooms"]', 'select[name="room"]'], 'labels' => ['Oda Sayısı', 'Oda', 'Bedrooms']],
                    'baths' => ['selectors' => ['select[name="bathrooms"]', 'input[name="bathrooms"]'], 'labels' => ['Banyo Sayısı', 'Banyo']],
                    'area_m2' => ['selectors' => ['input[name="area"]', 'input[name="m2"]', 'input[name="size"]'], 'labels' => ['Brüt m²', 'm²', 'Metrekare', 'Alan']],
                    'deed_type' => ['selectors' => ['select[name="title_deed"]', 'select[name="deed"]'], 'labels' => ['Tapu Tipi', 'Tapu', 'Koçan', 'Title Deed'], 'map' => ['turk-kocani' => ['Türk Koçanı', 'Türk Malı'], 'esdeger' => ['Eşdeğer', 'Eşdeğer Koçan'], 'tahsis' => ['Tahsis'], 'vakif' => ['Vakıf', 'Evkaf'], 'proje' => ['Proje', 'Sözleşme'], 'diger' => ['Diğer']]],
                    'floor' => ['selectors' => ['select[name="floor"]', 'input[name="floor"]'], 'labels' => ['Bulunduğu Kat', 'Kat']],
                    'total_floors' => ['selectors' => ['select[name="total_floors"]', 'input[name="building_floors"]'], 'labels' => ['Kat Sayısı', 'Bina Kat Sayısı']],
                    'year_built' => ['selectors' => ['input[name="year"]', 'select[name="building_age"]'], 'labels' => ['Yapım Yılı', 'Bina Yaşı']],
                    'furnished' => ['selectors' => ['select[name="furnished"]'], 'labels' => ['Eşyalı', 'Eşya Durumu'], 'map' => ['1' => ['Eşyalı', 'Evet', 'Furnished'], '0' => ['Eşyasız', 'Hayır', 'Unfurnished']]],
                    'photos' => ['selectors' => ['input[type="file"][multiple]', 'input[type="file"][accept*="image"]', 'input[type="file"]']],
                ],
                'features' => ['labels_from' => 'tr'], // özellik onay kutuları Türkçe adlarıyla eşleştirilir
                'sms' => ['selectors' => ['input[name*="sms"]', 'input[name*="code"]', 'input[autocomplete="one-time-code"]']],
                'success' => ['url_contains' => ['/ilan/', 'basarili', 'success'], 'id_regex' => '(\\d{5,})'],
            ],
            'hangiev' => [
                'name' => 'hangiev.com',
                'match' => ['https://www.hangiev.com/*', 'https://hangiev.com/*'],
                'new_url' => 'https://www.hangiev.com/ilan-ekle',
                'fields' => [
                    'title' => ['selectors' => ['input[name="title"]', 'input[name="baslik"]'], 'labels' => ['İlan Başlığı', 'Başlık']],
                    'description' => ['selectors' => ['textarea[name="description"]', 'textarea[name="aciklama"]'], 'labels' => ['Açıklama', 'İlan Açıklaması']],
                    'price' => ['selectors' => ['input[name="price"]', 'input[name="fiyat"]'], 'labels' => ['Fiyat']],
                    'currency' => ['selectors' => ['select[name="currency"]', 'select[name="doviz"]'], 'labels' => ['Para Birimi', 'Döviz'], 'map' => ['GBP' => ['GBP', 'STG', '£', 'Sterlin'], 'EUR' => ['EUR', '€', 'Euro'], 'USD' => ['USD', '$', 'Dolar'], 'TRY' => ['TRY', 'TL', '₺']]],
                    'mode' => ['selectors' => ['select[name="status"]', 'select[name="durum"]'], 'labels' => ['İlan Durumu', 'Satılık / Kiralık'], 'map' => ['sale' => ['Satılık'], 'rent' => ['Kiralık'], 'daily' => ['Günlük Kiralık', 'Günlük']]],
                    'subtype' => ['selectors' => ['select[name="type"]', 'select[name="tip"]'], 'labels' => ['Emlak Tipi', 'Kategori'], 'map' => ['daire' => ['Daire'], 'villa' => ['Villa'], 'penthouse' => ['Penthouse'], 'mustakil' => ['Müstakil Ev', 'Müstakil'], 'ikiz' => ['İkiz Villa'], 'bungalow' => ['Bungalow'], 'studyo' => ['Stüdyo'], 'konut-imarli' => ['Arsa'], 'ticari-imarli' => ['Ticari Arsa'], 'tarla' => ['Tarla'], 'dukkan' => ['Dükkan'], 'ofis' => ['Ofis'], 'isyeri' => ['İşyeri'], 'otel' => ['Otel']]],
                    'city' => ['selectors' => ['select[name="city"]', 'select[name="il"]'], 'labels' => ['Şehir', 'İl'], 'map' => ['lefkosa' => ['Lefkoşa'], 'girne' => ['Girne'], 'gazimagusa' => ['Gazimağusa', 'Mağusa'], 'iskele' => ['İskele'], 'guzelyurt' => ['Güzelyurt'], 'lefke' => ['Lefke']]],
                    'district' => ['selectors' => ['select[name="district"]', 'select[name="bolge"]'], 'labels' => ['Bölge', 'Köy / Semt'], 'after' => 'city', 'wait' => 1500],
                    'rooms' => ['selectors' => ['select[name="rooms"]', 'select[name="oda"]'], 'labels' => ['Oda Sayısı']],
                    'baths' => ['selectors' => ['select[name="bathrooms"]', 'select[name="banyo"]'], 'labels' => ['Banyo Sayısı']],
                    'area_m2' => ['selectors' => ['input[name="m2"]', 'input[name="area"]'], 'labels' => ['m²', 'Metrekare']],
                    'deed_type' => ['selectors' => ['select[name="tapu"]', 'select[name="deed"]'], 'labels' => ['Tapu Durumu', 'Koçan'], 'map' => ['turk-kocani' => ['Türk Koçanı', 'Türk Malı'], 'esdeger' => ['Eşdeğer'], 'tahsis' => ['Tahsis'], 'vakif' => ['Vakıf'], 'proje' => ['Proje'], 'diger' => ['Diğer']]],
                    'furnished' => ['selectors' => ['select[name="esya"]'], 'labels' => ['Eşya Durumu'], 'map' => ['1' => ['Eşyalı'], '0' => ['Eşyasız']]],
                    'photos' => ['selectors' => ['input[type="file"][multiple]', 'input[type="file"]']],
                ],
                'features' => ['labels_from' => 'tr'],
                'sms' => ['selectors' => ['input[name*="sms"]', 'input[name*="kod"]', 'input[autocomplete="one-time-code"]']],
                'success' => ['url_contains' => ['/ilan/', 'tesekkur', 'basarili'], 'id_regex' => '(\\d{4,})'],
            ],
        ],
    ];
}

function ec_ext_config(): array
{
    $saved = get_option('ec_ext_config');
    $cfg = is_string($saved) ? json_decode($saved, true) : null;
    return is_array($cfg) && !empty($cfg['sites']) ? $cfg : ec_ext_default_config();
}

function ec_syndication_for(int $listing_id): array
{
    global $wpdb;
    $out = [];
    foreach ($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('syndication') . ' WHERE listing_id = %d', $listing_id), ARRAY_A) as $r) {
        $out[$r['site']] = ['status' => $r['status'], 'remote_url' => $r['remote_url'], 'remote_id' => $r['remote_id'], 'updated_at' => $r['updated_at']];
    }
    return $out;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/ext/config', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => fn() => ec_ext_config(),
    ]);

    register_rest_route(EC_NS, '/ext/listing/(?P<id>\d+)', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $id = (int) $r['id'];
            // Paket ve kota: aynı ilan aynı pakette bir kez sayılır (101evler + hangiev = 1 ilan)
            $owner = ec_agent((int) ec_listing_row($id)['agent_id']);
            if (!current_user_can('manage_options') && $owner) {
                $q = ec_quota_use($owner, $id, 'syndication');
                if (is_wp_error($q)) {
                    return $q;
                }
            }
            $it = ec_listings_present([ec_listing_row($id)], 'tr', true)[0];
            $it['i18n'] = [];
            foreach ($wpdb->get_results($wpdb->prepare('SELECT lang, title, description FROM ' . ec_table('listing_i18n') . ' WHERE listing_id = %d', $id), ARRAY_A) as $t) {
                $it['i18n'][$t['lang']] = ['title' => $t['title'], 'description' => $t['description']];
            }
            $it['feature_labels'] = [];
            foreach ($it['features'] as $k) {
                $it['feature_labels'][$k] = ec_features()[$k] ?? [];
            }
            $it['syndication'] = ec_syndication_for($id);
            return $it;
        },
    ]);

    register_rest_route(EC_NS, '/ext/syndication', [
        [
            'methods' => 'GET',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                global $wpdb;
                $a = ec_rest_agent($r);
                $rows = $a ? $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('syndication') . ' WHERE agent_id = %d ORDER BY updated_at DESC LIMIT 500', $a['id']), ARRAY_A) : [];
                return ['items' => $rows];
            },
        ],
        [
            'methods' => 'POST',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                global $wpdb;
                $a = ec_rest_agent($r);
                $b = $r->get_json_params() ?: [];
                $lid = (int) ($b['listing_id'] ?? 0);
                $row = ec_listing_row($lid);
                $site = (string) ($b['site'] ?? '');
                if (!$a || !$row || (int) $row['agent_id'] !== (int) $a['id'] || !in_array($site, EC_EXT_SITES, true)) {
                    return new WP_Error('ec_invalid', 'Geçersiz ilan veya site', ['status' => 400]);
                }
                $st = in_array($b['status'] ?? '', ['filled', 'published', 'failed', 'removed'], true) ? $b['status'] : 'filled';
                $t = ec_table('syndication');
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE listing_id = %d AND site = %s", $lid, $site));
                $d = [
                    'status' => $st,
                    'remote_url' => isset($b['remote_url']) ? (esc_url_raw((string) $b['remote_url']) ?: null) : null,
                    'remote_id' => isset($b['remote_id']) ? mb_substr(sanitize_text_field((string) $b['remote_id']), 0, 60) : null,
                    'note' => isset($b['note']) ? mb_substr(sanitize_text_field((string) $b['note']), 0, 255) : null,
                    'updated_at' => ec_now(),
                ];
                $d = array_filter($d, fn($v) => $v !== null) + ['status' => $st];
                if ($exists) {
                    $wpdb->update($t, $d, ['id' => $exists]);
                } else {
                    $wpdb->insert($t, $d + ['listing_id' => $lid, 'agent_id' => $a['id'], 'site' => $site, 'created_at' => ec_now()]);
                }
                return ['ok' => true, 'syndication' => ec_syndication_for($lid)];
            },
        ],
    ]);

    register_rest_route(EC_NS, '/ext/sms-request', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $site = (string) (($r->get_json_params() ?: [])['site'] ?? '');
            if (!$a || !in_array($site, EC_EXT_SITES, true)) {
                return new WP_Error('ec_invalid', 'Geçersiz site', ['status' => 400]);
            }
            if (empty($a['telegram_chat_id']) || !function_exists('ec_tg_send') || !ec_tg_ready()) {
                return new WP_Error('ec_no_telegram', 'Telegram bağlı değil; kodu eklentiye elle yazın', ['status' => 409]);
            }
            delete_transient('ec_sms_code_' . $a['id']);
            set_transient('ec_sms_wait_' . $a['id'], $site, 5 * MINUTE_IN_SECONDS);
            ec_tg_send($a['telegram_chat_id'], '🔐 <b>' . esc_html($site) . "</b> telefonunuza bir SMS kodu gönderdi.\nKodu buraya yazın (5 dakika geçerli), eklenti otomatik dolduracak.");
            return ['ok' => true, 'expires_in' => 300];
        },
    ]);

    register_rest_route(EC_NS, '/ext/sms-code', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $c = $a ? get_transient('ec_sms_code_' . $a['id']) : null;
            if (!is_array($c)) {
                return ['code' => null, 'waiting' => (bool) ($a && get_transient('ec_sms_wait_' . $a['id']))];
            }
            delete_transient('ec_sms_code_' . $a['id']);
            return ['code' => $c['code'], 'site' => $c['site']];
        },
    ]);
});
