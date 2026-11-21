<?php
/**
 * Emlak danışmanları / ofisleri ve API anahtarları.
 * Anahtarın kendisi saklanmaz, sadece SHA-256 özeti tutulur; anahtar bir kez gösterilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_agents_by_ids(array $ids): array
{
    global $wpdb;
    $ids = array_filter(array_map('intval', $ids));
    if (!$ids) {
        return [];
    }
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('agents') . ' WHERE id IN (' . implode(',', $ids) . ')', ARRAY_A);
    $out = [];
    foreach ($rows as $r) {
        $out[(int) $r['id']] = $r;
    }
    return $out;
}

function ec_agent(int $id): ?array
{
    return ec_agents_by_ids([$id])[$id] ?? null;
}

/** Herkese açık alanlar (e-posta ve anahtar asla dışarı verilmez) */
function ec_agent_public(array $a): array
{
    return [
        'id' => (int) $a['id'],
        'kind' => $a['kind'],
        'name' => $a['name'],
        'office' => $a['office'],
        'slug' => $a['slug'] ?? null,
        'phone' => $a['phone'],
        'whatsapp' => $a['whatsapp'] ?: $a['phone'],
        'city' => $a['city'],
        'website' => $a['website'] ?? null,
        'bio' => $a['bio'] ?? '',
        'verified' => !empty($a['verified']),
        'logo' => !empty($a['logo_path']) ? ec_image_urls($a['logo_path']) : null,
        'socials' => !empty($a['socials']) ? (array) json_decode($a['socials'], true) : [],
        'url' => !empty($a['slug']) ? ec_url_office($a['slug']) : null,
        'team_of' => !empty($a['parent_id']) && function_exists('ec_team_parent') && ($tp = ec_team_parent($a)) ? ['name' => $tp['name'], 'url' => !empty($tp['slug']) ? ec_url_office($tp['slug']) : null] : null,
    ];
}

function ec_agent_by_user(int $uid): ?array
{
    global $wpdb;
    if (!$uid) {
        return null;
    }
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('agents') . ' WHERE wp_user_id = %d', $uid), ARRAY_A);
    return $r ?: null;
}

function ec_agent_by_slug(string $slug): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('agents') . " WHERE slug = %s AND status = 'active'", $slug), ARRAY_A);
    return $r ?: null;
}

/** Oturum açmış kullanıcının danışman kaydı */
function ec_current_agent(): ?array
{
    static $cache = null;
    if ($cache === null) {
        $cache = ec_agent_by_user(get_current_user_id()) ?: false;
    }
    return $cache ?: null;
}

/** Alt alan adı / vitrin adresi için uygun ve benzersiz kısa ad */
function ec_agent_unique_slug(string $name, int $exclude_id = 0): string
{
    global $wpdb;
    $reserved = ['www', 'mail', 'smtp', 'ftp', 'api', 'admin', 'panel', 'blog', 'cdn', 'static', 'webmail', 'cpanel', 'plesk', 'ns1', 'ns2', 'tr', 'en', 'ru', 'test', 'dev', 'app', 'destek', 'help'];
    $base = substr(trim(preg_replace('/-+/', '-', ec_slugify($name)), '-'), 0, 40) ?: 'ofis';
    $slug = in_array($base, $reserved, true) ? $base . '-ofis' : $base;
    $i = 2;
    while ($wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('agents') . ' WHERE slug = %s AND id <> %d', $slug, $exclude_id))) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function ec_agents_active(int $limit = 12): array
{
    global $wpdb;
    $t = ec_table('agents');
    $l = ec_table('listings');
    $rows = $wpdb->get_results(
        "SELECT a.*, (SELECT COUNT(*) FROM $l x WHERE x.agent_id = a.id AND x.status = 'published') AS listing_count
         FROM $t a WHERE a.status = 'active' ORDER BY listing_count DESC, a.id ASC LIMIT " . (int) $limit,
        ARRAY_A
    );
    return array_map(fn($a) => ec_agent_public($a) + ['listing_count' => (int) $a['listing_count']], $rows);
}

/**
 * Firmalar dizini: aktif üyeler (mülk sahipleri hariç), ada / bölgeye göre aranabilir.
 * @return array{items: array, total: int, pages: int}
 */
function ec_agents_directory(string $q = '', string $kind = '', string $city = '', int $page = 1, int $per = 24): array
{
    global $wpdb;
    $t = ec_table('agents');
    $l = ec_table('listings');
    $p = ec_table('projects');
    $where = ["a.status = 'active'", "a.kind <> 'sahibi'"];
    $args = [];
    if ($q !== '') {
        $where[] = '(a.name LIKE %s OR a.office LIKE %s OR a.address LIKE %s)';
        $like = '%' . $wpdb->esc_like($q) . '%';
        array_push($args, $like, $like, $like);
    }
    if ($kind !== '' && isset(ec_member_kinds()[$kind])) {
        $where[] = 'a.kind = %s';
        $args[] = $kind;
    }
    if ($city !== '' && isset(ec_cities()[$city])) {
        $where[] = 'a.city = %s';
        $args[] = $city;
    }
    $w = implode(' AND ', $where);
    $page = max(1, $page);
    $off = ($page - 1) * $per;
    $sqlCount = "SELECT COUNT(*) FROM $t a WHERE $w";
    $sql = "SELECT a.*, (SELECT COUNT(*) FROM $l x WHERE x.agent_id = a.id AND x.status = 'published') AS listing_count,
            (SELECT COUNT(*) FROM $p y WHERE y.agent_id = a.id AND y.status = 'published') AS project_count
            FROM $t a WHERE $w ORDER BY a.verified DESC, listing_count DESC, a.name ASC LIMIT $per OFFSET $off";
    $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($sqlCount, $args)) : $wpdb->get_var($sqlCount));
    $rows = $args ? $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);
    return [
        'items' => array_map(fn($a) => ec_agent_public($a) + ['listing_count' => (int) $a['listing_count'], 'project_count' => (int) $a['project_count'], 'created_at' => $a['created_at'], 'address' => $a['address']], $rows),
        'total' => $total,
        'pages' => (int) ceil($total / $per),
    ];
}

/** Yeni API anahtarı üretir (eskisini geçersiz kılar) ve anahtarı bir kez döndürür. */
function ec_agent_issue_key(int $id): string
{
    global $wpdb;
    $key = 'ec_' . bin2hex(random_bytes(24));
    $wpdb->update(ec_table('agents'), ['api_key_hash' => hash('sha256', $key), 'updated_at' => ec_now()], ['id' => $id]);
    return $key;
}

function ec_agent_by_key(string $key): ?array
{
    global $wpdb;
    if (!str_starts_with($key, 'ec_') || strlen($key) < 20) {
        return null;
    }
    $row = $wpdb->get_row($wpdb->prepare(
        'SELECT * FROM ' . ec_table('agents') . " WHERE api_key_hash = %s AND status = 'active'",
        hash('sha256', $key)
    ), ARRAY_A);
    return $row ?: null;
}
