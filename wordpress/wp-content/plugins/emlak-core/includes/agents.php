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
        'name' => $a['name'],
        'office' => $a['office'],
        'phone' => $a['phone'],
        'whatsapp' => $a['whatsapp'] ?: $a['phone'],
        'city' => $a['city'],
    ];
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

/** Siteden gelen üyelik başvurusu. Yönetici onaylayana kadar "pending" kalır. */
function ec_agent_apply(array $in)
{
    global $wpdb;
    $email = sanitize_email($in['email'] ?? '');
    $name = sanitize_text_field($in['name'] ?? '');
    if (!$name || !is_email($email)) {
        return new WP_Error('ec_invalid', 'İsim ve geçerli bir e-posta zorunlu', ['status' => 400]);
    }
    $exists = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('agents') . ' WHERE email = %s', $email));
    if ($exists) {
        return new WP_Error('ec_exists', 'Bu e-posta ile zaten bir başvuru var', ['status' => 409]);
    }
    $kind = ($in['kind'] ?? '') === 'kurumsal' ? 'kurumsal' : 'bireysel';
    $city = sanitize_key($in['city'] ?? '');
    $wpdb->insert(ec_table('agents'), [
        'kind' => $kind,
        'company_type' => $kind === 'kurumsal' ? sanitize_text_field($in['company_type'] ?? '') : null,
        'name' => mb_substr($name, 0, 190),
        'office' => $kind === 'kurumsal' ? mb_substr($name, 0, 190) : null,
        'contact_person' => sanitize_text_field($in['contact_person'] ?? '') ?: null,
        'email' => $email,
        'phone' => preg_replace('/[^0-9+]/', '', (string) ($in['phone'] ?? '')) ?: null,
        'city' => isset(ec_cities()[$city]) ? $city : null,
        'status' => 'pending',
        'created_at' => ec_now(),
        'updated_at' => ec_now(),
    ]);
    $id = (int) $wpdb->insert_id;
    if ($id) {
        $admin = get_option('admin_email');
        wp_mail($admin, 'Yeni danışman başvurusu: ' . $name, "İsim: $name\nE-posta: $email\nTür: $kind\n\nOnaylamak için: " . admin_url('admin.php?page=ec-agents'));
    }
    return $id;
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
