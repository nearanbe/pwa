<?php
/**
 * Vitrin / doping (1.1)
 *   featured  Öne çıkar        → altın çerçeve + rozet, aramada en üstte                    (featured_until)
 *   home      Ana sayfa vitrini → ana sayfadaki "Vitrin" bölümü                              (home_until)
 *   cat       Kategori vitrini  → ilgili arama sayfasının (satılık/kiralık + şehir) üst şeridi (cat_until)
 *   urgent    Acil             → kırmızı "ACİL" rozeti                                      (urgent_until)
 *   bump      Yukarı taşı      → ilan sıralamada yeni ilan gibi en üste çıkar (tek seferlik) (bumped_at)
 *
 * Krediler pakette tanımlanır: packages.promos = "featured:5,home:2,cat:5,urgent:5,bump:10", promo_days = süre (gün).
 * Paket verilince aboneliğe kopyalanır; üye Panelim → İlanlarım'dan uygular, her uygulama 1 kredi düşer.
 * Yönetici kredi harcamadan istediği süreyle uygular (İlanlar listesi).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_promo_kinds(): array
{
    return [
        'featured' => ['col' => 'featured_until', 'name' => ['tr' => 'Öne çıkar', 'en' => 'Featured', 'ru' => 'Выделить'], 'icon' => '★'],
        'home' => ['col' => 'home_until', 'name' => ['tr' => 'Ana sayfa vitrini', 'en' => 'Homepage showcase', 'ru' => 'Витрина на главной'], 'icon' => '🏠'],
        'cat' => ['col' => 'cat_until', 'name' => ['tr' => 'Kategori vitrini', 'en' => 'Category showcase', 'ru' => 'Витрина категории'], 'icon' => '▦'],
        'urgent' => ['col' => 'urgent_until', 'name' => ['tr' => 'Acil', 'en' => 'Urgent', 'ru' => 'Срочно'], 'icon' => '⚡'],
        'bump' => ['col' => 'bumped_at', 'name' => ['tr' => 'Yukarı taşı', 'en' => 'Bump to top', 'ru' => 'Поднять'], 'icon' => '↑'],
    ];
}

function ec_promo_name(string $kind, string $lang = 'tr'): string
{
    return ec_promo_kinds()[$kind]['name'][$lang] ?? $kind;
}

/** "featured:5,home:2" → ['featured' => 5, 'home' => 2] */
function ec_promos_parse(?string $s): array
{
    $out = [];
    foreach (explode(',', (string) $s) as $p) {
        [$k, $n] = array_pad(explode(':', trim($p)), 2, 0);
        if (isset(ec_promo_kinds()[$k]) && (int) $n > 0) {
            $out[$k] = (int) $n;
        }
    }
    return $out;
}

function ec_promos_format(array $a): string
{
    $out = [];
    foreach (ec_promo_kinds() as $k => $_) {
        if (!empty($a[$k]) && (int) $a[$k] > 0) {
            $out[] = $k . ':' . (int) $a[$k];
        }
    }
    return implode(',', $out);
}

/** Bir abonelikte bu türden kalan kredi */
function ec_promo_left(array $sub, string $kind): int
{
    global $wpdb;
    $total = ec_promos_parse($sub['promos'] ?? '')[$kind] ?? 0;
    if (!$total) {
        return 0;
    }
    $used = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ec_table('promo_usage') . ' WHERE subscription_id = %d AND kind = %s', $sub['id'], $kind));
    return max(0, $total - $used);
}

/** Üyenin tüm geçerli paketlerindeki kalan krediler: ['featured' => 3, ...] */
function ec_promo_credits(int $agent_id): array
{
    $out = array_fill_keys(array_keys(ec_promo_kinds()), 0);
    $teamLeft = function_exists('ec_team_left') ? ec_team_left($agent_id, 'promos') : null;
    foreach (ec_subscriptions_active($agent_id) as $s) {
        foreach (array_keys($out) as $k) {
            $left = ec_promo_left($s, $k);
            if ((int) $s['agent_id'] !== $agent_id && $teamLeft !== null) {
                $left = min($left, $teamLeft); // ofisin koyduğu aylık vitrin sınırı
            }
            $out[$k] += $left;
        }
    }
    return $out;
}

/** Bu ilandaki etkin vitrinler: ['featured' => '2026-12-01 10:00:00', ...] (bump hariç) */
function ec_promo_state(array $row): array
{
    $out = [];
    foreach (ec_promo_kinds() as $k => $d) {
        if ($k === 'bump') {
            continue;
        }
        $v = $row[$d['col']] ?? null;
        if ($v && strtotime($v . ' UTC') > time()) {
            $out[$k] = $v;
        }
    }
    return $out;
}

/**
 * Vitrini uygular. $agent null ise yönetici (kredi düşmez, $days kullanılır).
 * @return array|WP_Error ['kind', 'until']
 */
function ec_promo_apply(int $listing_id, string $kind, ?array $agent = null, ?int $days = null)
{
    global $wpdb;
    $kinds = ec_promo_kinds();
    if (!isset($kinds[$kind])) {
        return new WP_Error('ec_invalid', 'Geçersiz vitrin türü', ['status' => 400]);
    }
    $row = ec_listing_row($listing_id);
    if (!$row) {
        return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
    }
    if ($row['status'] !== 'published') {
        return new WP_Error('ec_not_published', 'Vitrin sadece yayındaki ilana uygulanır.', ['status' => 400]);
    }
    $sub = null;
    if ($agent) {
        if ((int) $row['agent_id'] !== (int) $agent['id']) {
            return new WP_Error('ec_forbidden', 'Bu ilan sizin değil', ['status' => 403]);
        }
        foreach (ec_subscriptions_active((int) $agent['id']) as $s) {
            if ((int) $s['agent_id'] !== (int) $agent['id'] && function_exists('ec_team_left') && ec_team_left((int) $agent['id'], 'promos') === 0) {
                continue;
            }
            if (ec_promo_left($s, $kind) > 0) {
                $sub = $s;
                break;
            }
        }
        if (!$sub) {
            return new WP_Error('ec_no_credit', 'Bu vitrin için krediniz yok. Paketim bölümünden paket alabilirsiniz.', ['status' => 402]);
        }
        $days = (int) ($sub['promo_days'] ?: 7);
    }
    $days = max(1, (int) ($days ?: 7));
    $col = $kinds[$kind]['col'];
    if ($kind === 'bump') {
        if ($row['bumped_at'] && strtotime($row['bumped_at'] . ' UTC') > time() - 12 * HOUR_IN_SECONDS && $agent) {
            return new WP_Error('ec_too_soon', 'Bu ilan son 12 saat içinde zaten yukarı taşındı.', ['status' => 429]);
        }
        $until = ec_now();
    } else {
        // Süre devam ediyorsa üstüne eklenir
        $base = $row[$col] && strtotime($row[$col] . ' UTC') > time() ? strtotime($row[$col] . ' UTC') : time();
        $until = gmdate('Y-m-d H:i:s', $base + $days * DAY_IN_SECONDS);
    }
    $wpdb->update(ec_table('listings'), [$col => $until], ['id' => $listing_id]);
    $wpdb->insert(ec_table('promo_usage'), [
        'subscription_id' => $sub['id'] ?? null, 'agent_id' => $agent['id'] ?? null, 'listing_id' => $listing_id,
        'kind' => $kind, 'until' => $kind === 'bump' ? null : $until, 'created_at' => ec_now(),
    ]);
    do_action('ec_listing_changed', $listing_id);
    return ['kind' => $kind, 'until' => $until];
}

/** Yönetici: vitrini kaldırır */
function ec_promo_remove(int $listing_id, string $kind): void
{
    global $wpdb;
    $d = ec_promo_kinds()[$kind] ?? null;
    if ($d && $kind !== 'bump') {
        $wpdb->update(ec_table('listings'), [$d['col'] => null], ['id' => $listing_id]);
        do_action('ec_listing_changed', $listing_id);
    }
}

/**
 * Vitrin ilanları. $kind: home | cat. $f: mode, city (kategori vitrini için).
 * Sıra her gün değişir (adil dönüşüm): RAND(gün).
 */
function ec_listing_showcase(string $kind, string $lang, array $f = [], int $limit = 12): array
{
    global $wpdb;
    $col = ['home' => 'home_until', 'cat' => 'cat_until'][$kind] ?? null;
    if (!$col) {
        return [];
    }
    $t = ec_table('listings');
    $sql = "SELECT * FROM $t WHERE status = 'published' AND $col > UTC_TIMESTAMP()";
    if (!empty($f['mode'])) {
        $sql .= $wpdb->prepare(' AND mode = %s', $f['mode']);
    }
    if (!empty($f['city'])) {
        $sql .= $wpdb->prepare(' AND city = %s', $f['city']);
    }
    if (!empty($f['ptype'])) {
        $sql .= $wpdb->prepare(' AND ptype = %s', $f['ptype']);
    }
    $sql .= ' ORDER BY RAND(' . (int) gmdate('Ymd') . ') LIMIT ' . (int) $limit;
    return ec_listings_present($wpdb->get_results($sql, ARRAY_A), $lang);
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/promote', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            $kind = sanitize_key((string) $r->get_param('kind'));
            $agent = ec_rest_agent($r);
            if (!$agent && current_user_can('manage_options')) {
                $res = ec_promo_apply((int) $r['id'], $kind, null, (int) ($r->get_param('days') ?: 7));
            } else {
                $res = ec_promo_apply((int) $r['id'], $kind, $agent);
            }
            if (is_wp_error($res)) {
                return $res;
            }
            return $res + ['credits' => $agent ? ec_promo_credits((int) $agent['id']) : null];
        },
    ]);
    register_rest_route(EC_NS, '/me/promos', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $names = [];
            foreach (ec_promo_kinds() as $k => $d) {
                $names[$k] = ['name' => $d['name'][ec_rest_lang($r)] ?? $d['name']['tr'], 'icon' => $d['icon']];
            }
            return ['credits' => $a ? ec_promo_credits((int) $a['id']) : [], 'kinds' => $names];
        },
    ]);
});
