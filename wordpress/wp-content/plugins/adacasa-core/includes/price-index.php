<?php
/**
 * AdaCasa Fiyat Endeksi.
 * Her gün, yayındaki konut ilanlarının İSTENEN fiyatlarından şehir ve ilan türü (satılık/kiralık)
 * bazında m² medyanı hesaplanır ve o ayın satırına yazılır. Ay bitince satır sabit kalır,
 * böylece zamanla aylık bir fiyat geçmişi birikir.
 * Not: Bu gerçekleşen satış fiyatı değil, ilan fiyatı endeksidir; sayfada da böyle belirtilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_INDEX_MIN_N = 3;      // Bundan az ilan varsa değer gösterilmez
const EC_INDEX_ALL = 'all';    // Tüm KKTC satırı

function ec_median(array $v): float
{
    sort($v);
    $n = count($v);
    if (!$n) {
        return 0;
    }
    $m = intdiv($n, 2);
    return $n % 2 ? $v[$m] : ($v[$m - 1] + $v[$m]) / 2;
}

/** Bu ayın endeksini yayındaki ilanlardan hesaplar (gerçek veri, demo satırının üzerine yazar) */
function ec_index_compute(?string $period = null): int
{
    global $wpdb;
    $period = $period ?: gmdate('Y-m');
    $rows = $wpdb->get_results(
        'SELECT city, mode, ptype, price_gbp, area_m2 FROM ' . ec_table('listings') .
        " WHERE status = 'published' AND mode IN ('sale','rent') AND ptype = 'konut' AND area_m2 >= 20 AND price_gbp > 0",
        ARRAY_A
    );
    $groups = [];
    foreach ($rows as $r) {
        $m2 = (float) $r['price_gbp'] / (int) $r['area_m2'];
        foreach ([$r['city'], EC_INDEX_ALL] as $city) {
            $k = $city . '|' . $r['mode'] . '|' . $r['ptype'];
            $groups[$k]['m2'][] = $m2;
            $groups[$k]['price'][] = (float) $r['price_gbp'];
        }
    }
    $t = ec_table('price_index');
    // Bu ay için önceki hesaplamayı (ve varsa demo satırlarını) temizle
    $wpdb->delete($t, ['period' => $period]);
    foreach ($groups as $k => $g) {
        [$city, $mode, $ptype] = explode('|', $k);
        $wpdb->insert($t, [
            'period' => $period,
            'city' => $city,
            'mode' => $mode,
            'ptype' => $ptype,
            'median_m2_gbp' => round(ec_median($g['m2']), 2),
            'avg_price_gbp' => round(array_sum($g['price']) / count($g['price']), 2),
            'n' => count($g['m2']),
            'is_demo' => 0,
            'updated_at' => ec_now(),
        ]);
    }
    return count($groups);
}

/** Bir şehir için aylık seri (eskiden yeniye) */
function ec_index_series(string $city, string $mode, string $ptype = 'konut', int $months = 24): array
{
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT period, median_m2_gbp, avg_price_gbp, n, is_demo FROM ' . ec_table('price_index') .
        ' WHERE city = %s AND mode = %s AND ptype = %s AND n >= %d ORDER BY period DESC LIMIT %d',
        $city, $mode, $ptype, EC_INDEX_MIN_N, $months
    ), ARRAY_A);
    return array_reverse(array_map(fn($r) => [
        'period' => $r['period'],
        'm2' => (float) $r['median_m2_gbp'],
        'avg' => (float) $r['avg_price_gbp'],
        'n' => (int) $r['n'],
        'demo' => (bool) $r['is_demo'],
    ], $rows));
}

/**
 * Son ayın şehir tablosu: m² medyanı, ortalama fiyat, 1 ve 12 aylık değişim.
 * @return array<int, array> 'all' satırı başta
 */
function ec_index_table(string $mode, string $ptype = 'konut'): array
{
    $cities = array_merge([EC_INDEX_ALL], array_keys(ec_cities()));
    $out = [];
    foreach ($cities as $c) {
        $s = ec_index_series($c, $mode, $ptype, 13);
        if (!$s) {
            continue;
        }
        $last = end($s);
        $byPeriod = array_column($s, 'm2', 'period');
        $prev = $byPeriod[gmdate('Y-m', strtotime($last['period'] . '-01 -1 month'))] ?? null;
        $year = $byPeriod[gmdate('Y-m', strtotime($last['period'] . '-01 -12 months'))] ?? null;
        $out[] = [
            'city' => $c,
            'period' => $last['period'],
            'm2' => $last['m2'],
            'avg' => $last['avg'],
            'n' => $last['n'],
            'chg1' => $prev ? ($last['m2'] / $prev - 1) * 100 : null,
            'chg12' => $year ? ($last['m2'] / $year - 1) * 100 : null,
            'demo' => $last['demo'],
        ];
    }
    return $out;
}

/** İlanın m² fiyatının bölge (şehir) medyanına göre farkı, yüzde olarak */
function ec_index_compare(array $listing): ?array
{
    if ($listing['ptype'] !== 'konut' || !in_array($listing['mode'], ['sale', 'rent'], true) || !$listing['area_m2'] || $listing['area_m2'] < 20) {
        return null;
    }
    $s = ec_index_series($listing['city'], $listing['mode'], 'konut', 1);
    if (!$s || $s[0]['m2'] <= 0) {
        return null;
    }
    $m2 = $listing['price_gbp'] / $listing['area_m2'];
    return ['m2' => $m2, 'median' => $s[0]['m2'], 'diff' => ($m2 / $s[0]['m2'] - 1) * 100, 'demo' => $s[0]['demo']];
}
