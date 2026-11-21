<?php
/**
 * Paketler (0.5). Tamamen yönetici kontrolündedir: yönetici paket tanımlar ve istediği üyeye
 * istediği süreyle (gün ya da bitiş tarihi) ve istediği kotayla verir, uzatır, iptal eder.
 *
 * Özellikler:
 *   syndication  101evler / hangiev aktarımı (Chrome eklentisi)
 *   social       Facebook sayfası ve Instagram'da otomatik paylaşım
 *   telegram     Telegram botuyla ilan verme (Ayarlar'da "paket gereksin" açıksa)
 * Kota: paket süresince otomasyonla işlenen FARKLI ilan sayısı (0 = sınırsız). Bir ilan hem 101evler'e
 * hem hangiev'e hem sosyal medyaya gitse de 1 sayılır.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_package_features(): array
{
    return [
        'syndication' => '101evler / hangiev aktarımı (Chrome eklentisi)',
        'social' => 'Facebook ve Instagram’da otomatik paylaşım',
        'telegram' => 'Telegram botuyla ilan verme',
    ];
}

/** Bu özellik paket gerektiriyor mu? (Ayarlar → Paketler) */
function ec_feature_requires_package(string $f): bool
{
    $req = array_merge(['syndication' => 1, 'social' => 1, 'telegram' => 0], (array) get_option('ec_pkg_required', []));
    return $f === 'social' ? true : !empty($req[$f]); // sosyal medya her zaman ücretli
}

function ec_packages(bool $onlyActive = false): array
{
    global $wpdb;
    return $wpdb->get_results('SELECT * FROM ' . ec_table('packages') . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, id', ARRAY_A) ?: [];
}

/** İlk kurulumda örnek paketler (fiyatlar bilgi amaçlı; ödeme şimdilik elle) */
function ec_packages_seed_defaults(): void
{
    global $wpdb;
    if ((int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('packages'))) {
        return;
    }
    $all = 'syndication,social,telegram';
    foreach ([
        ['Aylık 100 ilan', 30, 100, $all, '50 €', 1],
        ['Haftalık 30 ilan', 7, 30, $all, '20 €', 2],
        ['Tek ilan', 30, 1, $all, '2 €', 3],
        ['Sadece sosyal medya (aylık)', 30, 0, 'social', '', 4],
    ] as [$n, $d, $q, $f, $p, $s]) {
        $wpdb->insert(ec_table('packages'), ['name' => $n, 'days' => $d, 'quota' => $q, 'features' => $f, 'price' => $p, 'amount' => (float) preg_replace('/[^0-9.]/', '', $p) ?: null, 'currency' => 'EUR', 'active' => 1, 'sort' => $s, 'created_at' => ec_now()]);
    }
    ec_promo_packages_seed();
}

/** 1.1: örnek vitrin paketleri (yoksa eklenir) */
function ec_promo_packages_seed(): void
{
    global $wpdb;
    if (get_option('ec_promo_pkgs_seeded')) {
        return;
    }
    update_option('ec_promo_pkgs_seeded', 1);
    // Eski paketler: "50 €" gibi fiyat yazısından satış tutarını bir kez çıkar (online satın alma için)
    foreach ($wpdb->get_results('SELECT id, price FROM ' . ec_table('packages') . ' WHERE amount IS NULL', ARRAY_A) as $old) {
        if (preg_match('/(\d+(?:[.,]\d{1,2})?)/', (string) $old['price'], $m)) {
            $cur = str_contains($old['price'], '£') ? 'GBP' : (str_contains($old['price'], '$') ? 'USD' : (str_contains($old['price'], '₺') || stripos($old['price'], 'TL') !== false ? 'TRY' : 'EUR'));
            $wpdb->update(ec_table('packages'), ['amount' => (float) str_replace(',', '.', $m[1]), 'currency' => $cur], ['id' => $old['id']]);
        }
    }
    foreach ([
        ['Vitrin: Öne çıkar (7 gün)', 7, 'featured:1', 7, 5, 11],
        ['Vitrin: Ana sayfa vitrini (7 gün)', 7, 'home:1', 7, 15, 12],
        ['Vitrin: Kategori vitrini (7 gün)', 7, 'cat:1', 7, 8, 13],
        ['Vitrin: Acil etiketi (7 gün)', 7, 'urgent:1', 7, 3, 14],
        ['Vitrin: 5 × Yukarı taşı', 30, 'bump:5', 7, 4, 15],
        ['Vitrin Paketi Pro (aylık)', 30, 'featured:10,home:3,cat:10,urgent:10,bump:30', 7, 60, 16],
    ] as [$n, $d, $pr, $pd, $amt, $sort]) {
        $wpdb->insert(ec_table('packages'), ['name' => $n, 'days' => $d, 'quota' => 0, 'features' => '', 'price' => $amt . ' €', 'amount' => $amt, 'currency' => 'EUR',
            'promos' => $pr, 'promo_days' => $pd, 'active' => 1, 'sort' => $sort, 'created_at' => ec_now()]);
    }
}
add_action('admin_init', 'ec_promo_packages_seed', 20);
add_action('admin_init', 'ec_packages_seed_defaults');

/** Üyenin şu an geçerli paketleri (bitiş tarihi geçmemiş, iptal edilmemiş) */
function ec_subscriptions_active(int $agent_id): array
{
    global $wpdb;
    // 1.1: ofis ekibindeki danışman, ofisin paketlerini de kullanır (önce kendi paketi, sonra ofisinki)
    $ids = [$agent_id];
    $parent = (int) $wpdb->get_var($wpdb->prepare('SELECT a.parent_id FROM ' . ec_table('agents') . ' a JOIN ' . ec_table('agents') . " p ON p.id = a.parent_id AND p.status = 'active' WHERE a.id = %d AND a.team_share = 1", $agent_id));
    if ($parent) {
        $ids[] = $parent;
    }
    $in = implode(',', array_map('intval', $ids));
    return $wpdb->get_results($wpdb->prepare(
        'SELECT * FROM ' . ec_table('subscriptions') . " WHERE agent_id IN ($in) AND status = 'active' AND starts_at <= UTC_TIMESTAMP() AND ends_at > UTC_TIMESTAMP() ORDER BY (agent_id = %d) DESC, ends_at ASC",
        $agent_id
    ), ARRAY_A) ?: [];
}

function ec_subscription_used(int $sub_id): int
{
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ec_table('usage') . ' WHERE subscription_id = %d', $sub_id));
}

/** Özelliği içeren, kotası dolmamış ilk paket (önce bitecek olan kullanılır). $listing_id daha önce sayıldıysa o paket döner. */
function ec_subscription_for(int $agent_id, string $feature, ?int $listing_id = null): ?array
{
    global $wpdb;
    foreach (ec_subscriptions_active($agent_id) as $s) {
        if (!in_array($feature, explode(',', $s['features']), true)) {
            continue;
        }
        if ($listing_id && $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('usage') . ' WHERE subscription_id = %d AND listing_id = %d', $s['id'], $listing_id))) {
            return $s; // bu ilan zaten bu pakette sayıldı
        }
        // Ofisin paketi: ofisin bu danışmana koyduğu aylık ilan sınırı
        if ((int) $s['agent_id'] !== $agent_id && function_exists('ec_team_left') && ec_team_left($agent_id, 'listings') === 0) {
            continue;
        }
        if ((int) $s['quota'] === 0 || ec_subscription_used((int) $s['id']) < (int) $s['quota']) {
            return $s;
        }
    }
    return null;
}

/** Üye bu özelliği kullanabilir mi? */
function ec_member_can(?array $agent, string $feature): bool
{
    if (!$agent) {
        return false;
    }
    if (!ec_feature_requires_package($feature)) {
        return true;
    }
    return (bool) ec_subscription_for((int) $agent['id'], $feature);
}

/**
 * İlanı kotadan düşer (aynı pakette aynı ilan bir kez sayılır).
 * @return true|WP_Error
 */
function ec_quota_use(array $agent, int $listing_id, string $feature)
{
    global $wpdb;
    if (!ec_feature_requires_package($feature)) {
        return true;
    }
    $s = ec_subscription_for((int) $agent['id'], $feature, $listing_id);
    if (!$s) {
        return new WP_Error('ec_no_package', 'Bu özellik için geçerli bir paketiniz yok ya da kotanız doldu. Site yönetimiyle iletişime geçin.', ['status' => 402]);
    }
    $wpdb->query($wpdb->prepare(
        'INSERT IGNORE INTO ' . ec_table('usage') . ' (subscription_id, agent_id, listing_id, feature, created_at) VALUES (%d, %d, %d, %s, %s)',
        $s['id'], $agent['id'], $listing_id, $feature, ec_now()
    ));
    return true;
}

/**
 * Üyeye paket verir. $o: package_id, days ya da ends (Y-m-d), starts (Y-m-d), quota, features[], note
 * @return int|WP_Error abonelik no
 */
function ec_subscription_grant(int $agent_id, array $o)
{
    global $wpdb;
    $pkg = !empty($o['package_id']) ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('packages') . ' WHERE id = %d', $o['package_id']), ARRAY_A) : null;
    if (!ec_agent($agent_id)) {
        return new WP_Error('ec_invalid', 'Üye bulunamadı');
    }
    $start = !empty($o['starts']) ? strtotime($o['starts'] . ' 00:00:00 UTC') : time();
    if (!empty($o['ends'])) {
        $end = strtotime($o['ends'] . ' 23:59:59 UTC');
    } else {
        $days = (int) ($o['days'] ?? ($pkg['days'] ?? 30));
        $end = $start + max(1, $days) * DAY_IN_SECONDS;
    }
    if (!$start || !$end || $end <= $start) {
        return new WP_Error('ec_invalid', 'Tarihler geçersiz');
    }
    $features = isset($o['features']) ? array_intersect((array) $o['features'], array_keys(ec_package_features())) : explode(',', (string) ($pkg['features'] ?? ''));
    $wpdb->insert(ec_table('subscriptions'), [
        'agent_id' => $agent_id,
        'package_id' => $pkg['id'] ?? null,
        'name' => mb_substr(sanitize_text_field($o['name'] ?? ($pkg['name'] ?? 'Özel paket')), 0, 120),
        'features' => implode(',', array_filter($features)),
        'quota' => isset($o['quota']) && $o['quota'] !== '' ? max(0, (int) $o['quota']) : (int) ($pkg['quota'] ?? 0),
        'promos' => isset($o['promos']) ? ec_promos_format((array) $o['promos']) : (string) ($pkg['promos'] ?? ''),
        'promo_days' => (int) ($o['promo_days'] ?? ($pkg['promo_days'] ?? 7)) ?: 7,
        'starts_at' => gmdate('Y-m-d H:i:s', $start),
        'ends_at' => gmdate('Y-m-d H:i:s', $end),
        'status' => 'active',
        'note' => mb_substr(sanitize_text_field($o['note'] ?? ''), 0, 255) ?: null,
        'created_at' => ec_now(),
    ]);
    $id = (int) $wpdb->insert_id;
    $a = ec_agent($agent_id);
    ec_notify_member($a, 'Paketiniz tanımlandı', "Merhaba {$a['name']},\n\n“" . ($pkg['name'] ?? $o['name'] ?? 'Paket') . '” paketiniz ' . wp_date('d.m.Y', $end) . " tarihine kadar tanımlandı.\n\n" . add_query_arg('bolum', 'paket', ec_url_section('panel', ec_member_lang((int) $a['wp_user_id']))));
    return $id;
}

function ec_subscription_extend(int $id, int $days): void
{
    global $wpdb;
    $wpdb->query($wpdb->prepare('UPDATE ' . ec_table('subscriptions') . " SET ends_at = DATE_ADD(GREATEST(ends_at, UTC_TIMESTAMP()), INTERVAL %d DAY), status = 'active', warned = 0 WHERE id = %d", $days, $id));
}

function ec_subscription_cancel(int $id): void
{
    global $wpdb;
    $wpdb->update(ec_table('subscriptions'), ['status' => 'cancelled'], ['id' => $id]);
}

/** Panelde gösterilecek özet */
function ec_subscriptions_summary(int $agent_id): array
{
    return array_map(function ($s) {
        $used = ec_subscription_used((int) $s['id']);
        return [
            'id' => (int) $s['id'], 'name' => $s['name'], 'features' => array_filter(explode(',', $s['features'])),
            'quota' => (int) $s['quota'], 'used' => $used, 'left' => (int) $s['quota'] ? max(0, (int) $s['quota'] - $used) : null,
            'ends_at' => $s['ends_at'], 'days_left' => max(0, (int) ceil((strtotime($s['ends_at'] . ' UTC') - time()) / DAY_IN_SECONDS)),
            'promos' => array_map(fn($k) => ['total' => ec_promos_parse($s['promos'] ?? '')[$k] ?? 0, 'left' => ec_promo_left($s, $k)], array_combine(array_keys(ec_promo_kinds()), array_keys(ec_promo_kinds()))),
            'promo_days' => (int) ($s['promo_days'] ?? 7),
        ];
    }, ec_subscriptions_active($agent_id));
}

/** Günlük: 3 gün kala uyarı, bitince bilgi */
add_action('ec_daily', function () {
    global $wpdb;
    $t = ec_table('subscriptions');
    foreach ($wpdb->get_results("SELECT * FROM $t WHERE status = 'active' AND warned = 0 AND ends_at > UTC_TIMESTAMP() AND ends_at < DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)", ARRAY_A) as $s) {
        $a = ec_agent((int) $s['agent_id']);
        if ($a) {
            $msg = "“{$s['name']}” paketiniz " . wp_date('d.m.Y', strtotime($s['ends_at'] . ' UTC')) . ' tarihinde bitiyor. Uzatmak için site yönetimiyle iletişime geçin.';
            ec_notify_member($a, 'Paketiniz bitmek üzere', $msg);
            if (!empty($a['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
                ec_tg_send($a['telegram_chat_id'], '⏳ ' . $msg);
            }
            ec_notify_admin("⏳ Paket bitiyor: {$a['name']} — {$s['name']} (" . wp_date('d.m.Y', strtotime($s['ends_at'] . ' UTC')) . ')', admin_url('admin.php?page=ec-packages'), false);
        }
        $wpdb->update($t, ['warned' => 1], ['id' => $s['id']]);
    }
    $wpdb->query("UPDATE $t SET status = 'expired' WHERE status = 'active' AND ends_at <= UTC_TIMESTAMP()");
});

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/me/packages', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            return $a ? ['items' => ec_subscriptions_summary((int) $a['id']), 'can' => array_map(fn($f) => ec_member_can($a, $f), array_combine(array_keys(ec_package_features()), array_keys(ec_package_features())))] : ['items' => []];
        },
    ]);
});
