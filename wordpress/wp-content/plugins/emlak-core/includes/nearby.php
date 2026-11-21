<?php
/**
 * Yakındaki önemli yerler (sağlık, eğitim, banka, yemek, market, turizm, plaj).
 * Kaynak: OpenStreetMap, Overpass API (ücretsiz). Sonuç konuma göre 30 gün önbellekte tutulur,
 * böylece aynı bölgedeki ilanlar Overpass'a tekrar sorulmaz.
 *   GET /nearby?lat=35.33&lng=33.32&lang=tr
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_NEARBY_RADIUS = 2000; // metre

function ec_nearby_categories(): array
{
    return [
        'health'    => ['name' => ['tr' => 'Sağlık', 'en' => 'Health', 'ru' => 'Медицина'], 'icon' => '✚', 'q' => ['amenity' => 'hospital|clinic|doctors|pharmacy|dentist']],
        'education' => ['name' => ['tr' => 'Eğitim', 'en' => 'Education', 'ru' => 'Образование'], 'icon' => '🎓', 'q' => ['amenity' => 'school|kindergarten|university|college']],
        'bank'      => ['name' => ['tr' => 'Banka', 'en' => 'Banks', 'ru' => 'Банки'], 'icon' => '🏦', 'q' => ['amenity' => 'bank']],
        'food'      => ['name' => ['tr' => 'Yeme içme', 'en' => 'Food & drink', 'ru' => 'Кафе и рестораны'], 'icon' => '🍽', 'q' => ['amenity' => 'restaurant|cafe|fast_food|pub|bar']],
        'market'    => ['name' => ['tr' => 'Market', 'en' => 'Shopping', 'ru' => 'Магазины'], 'icon' => '🛒', 'q' => ['shop' => 'supermarket|convenience|bakery|greengrocer|mall']],
        'tourism'   => ['name' => ['tr' => 'Turizm ve plaj', 'en' => 'Leisure & beach', 'ru' => 'Досуг и пляж'], 'icon' => '⛱', 'q' => ['tourism' => 'attraction|museum|viewpoint', 'natural' => 'beach', 'leisure' => 'beach_resort|park|marina']],
    ];
}

function ec_haversine(float $lat1, float $lng1, float $lat2, float $lng2): int
{
    $r = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return (int) round(2 * $r * asin(min(1, sqrt($a))));
}

/** Overpass sorgusu (tek istekte tüm kategoriler) */
function ec_nearby_query(float $lat, float $lng, int $radius): string
{
    $parts = [];
    foreach (ec_nearby_categories() as $cat) {
        foreach ($cat['q'] as $k => $v) {
            $parts[] = sprintf('nwr(around:%d,%F,%F)["%s"~"^(%s)$"]["name"];', $radius, $lat, $lng, $k, $v);
        }
    }
    return '[out:json][timeout:20];(' . implode('', $parts) . ');out center tags 400;';
}

/** Ham Overpass cevabını kategorilere ayırır (dilden bağımsız; adlar tüm dillerde saklanır) */
function ec_nearby_parse(array $json, float $lat, float $lng): array
{
    $cats = ec_nearby_categories();
    $out = array_fill_keys(array_keys($cats), []);
    foreach (($json['elements'] ?? []) as $e) {
        $t = $e['tags'] ?? [];
        $elat = $e['lat'] ?? ($e['center']['lat'] ?? null);
        $elng = $e['lon'] ?? ($e['center']['lon'] ?? null);
        if ($elat === null || empty($t['name'])) {
            continue;
        }
        foreach ($cats as $ck => $c) {
            foreach ($c['q'] as $k => $v) {
                if (isset($t[$k]) && in_array($t[$k], explode('|', $v), true)) {
                    $out[$ck][] = [
                        'name' => $t['name'],
                        'name_en' => $t['name:en'] ?? null,
                        'name_ru' => $t['name:ru'] ?? null,
                        'name_tr' => $t['name:tr'] ?? null,
                        'kind' => $t[$k],
                        'm' => ec_haversine($lat, $lng, (float) $elat, (float) $elng),
                        'lat' => round((float) $elat, 5),
                        'lng' => round((float) $elng, 5),
                    ];
                    continue 3;
                }
            }
        }
    }
    foreach ($out as $k => $list) {
        usort($list, fn($a, $b) => $a['m'] <=> $b['m']);
        // Aynı adlı tekrarları (ör. bir zincirin iki kaydı) at
        $seen = [];
        $list = array_values(array_filter($list, function ($x) use (&$seen) {
            $key = mb_strtolower($x['name']);
            if (isset($seen[$key])) {
                return false;
            }
            return $seen[$key] = true;
        }));
        $out[$k] = array_slice($list, 0, 6);
    }
    return $out;
}

/** @return array|WP_Error ['items' => [kategori => [...]], 'cached' => bool] */
function ec_nearby(float $lat, float $lng)
{
    if ($lat < 34.4 || $lat > 35.8 || $lng < 32.2 || $lng > 34.7) {
        return new WP_Error('ec_nearby_range', 'Konum Kıbrıs dışında', ['status' => 400]);
    }
    $rl = round($lat, 3);
    $rg = round($lng, 3);
    $key = 'ec_nb_' . md5($rl . ',' . $rg . ',' . EC_NEARBY_RADIUS);
    $hit = get_transient($key);
    if (is_array($hit)) {
        return ['items' => $hit, 'cached' => true];
    }
    if (get_transient($key . '_fail')) {
        return new WP_Error('ec_nearby_fail', 'Yakın yerler şu an alınamıyor', ['status' => 503]);
    }
    if (get_transient($key . '_lock')) {
        return new WP_Error('ec_nearby_busy', 'Hazırlanıyor', ['status' => 503]);
    }
    set_transient($key . '_lock', 1, 40);
    $q = ec_nearby_query($rl, $rg, EC_NEARBY_RADIUS);
    $endpoints = apply_filters('ec_overpass_endpoints', ['https://overpass-api.de/api/interpreter', 'https://overpass.kumi.systems/api/interpreter', 'https://maps.mail.ru/osm/tools/overpass/api/interpreter']);
    $err = '';
    foreach ($endpoints as $ep) {
        $res = wp_remote_post($ep, ['timeout' => 25, 'body' => ['data' => $q], 'user-agent' => 'EmlakCore/' . EC_VERSION . ' (+' . home_url('/') . ')']);
        if (is_wp_error($res)) {
            $err = $res->get_error_message();
            continue;
        }
        if (wp_remote_retrieve_response_code($res) !== 200) {
            $err = 'HTTP ' . wp_remote_retrieve_response_code($res);
            continue;
        }
        $json = json_decode((string) wp_remote_retrieve_body($res), true);
        if (!is_array($json)) {
            $err = 'JSON okunamadı';
            continue;
        }
        $items = ec_nearby_parse($json, $rl, $rg);
        set_transient($key, $items, 30 * DAY_IN_SECONDS);
        delete_transient($key . '_lock');
        return ['items' => $items, 'cached' => false];
    }
    set_transient($key . '_fail', 1, HOUR_IN_SECONDS);
    delete_transient($key . '_lock');
    return new WP_Error('ec_nearby_fail', 'Yakın yerler alınamadı: ' . $err, ['status' => 503]);
}

/** Sunum: dilde ad, kategori adı, "350 m" / "1,2 km" */
function ec_nearby_present(array $items, string $lang): array
{
    $out = [];
    foreach (ec_nearby_categories() as $k => $c) {
        $list = array_map(fn($x) => [
            'name' => $x['name_' . $lang] ?? ($lang !== 'tr' ? ($x['name_en'] ?? $x['name']) : $x['name']),
            'kind' => $x['kind'],
            'm' => $x['m'],
            'dist' => $x['m'] < 1000 ? (round($x['m'] / 10) * 10) . ' m' : number_format($x['m'] / 1000, 1, ',', '.') . ' km',
            'lat' => $x['lat'],
            'lng' => $x['lng'],
        ], $items[$k] ?? []);
        $out[] = ['key' => $k, 'name' => $c['name'][$lang] ?? $c['name']['tr'], 'icon' => $c['icon'], 'items' => $list];
    }
    return $out;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/nearby', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'args' => ['lat' => ['required' => true], 'lng' => ['required' => true]],
        'callback' => function (WP_REST_Request $r) {
            $res = ec_nearby((float) $r['lat'], (float) $r['lng']);
            if (is_wp_error($res)) {
                return $res;
            }
            $resp = new WP_REST_Response(['radius' => EC_NEARBY_RADIUS, 'categories' => ec_nearby_present($res['items'], ec_rest_lang($r)), 'source' => '© OpenStreetMap']);
            $resp->header('Cache-Control', 'public, max-age=86400');
            return $resp;
        },
    ]);
});
