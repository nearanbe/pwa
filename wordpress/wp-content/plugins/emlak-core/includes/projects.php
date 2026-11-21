<?php
/**
 * Yeni projeler (inşaat firmalarının satıştaki projeleri).
 * Kapak görseli uploads/ilan-foto/proje-{no}/ altında durur.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_project_present(array $p, string $lang): array
{
    $desc = $p['desc_' . $lang] ?? '';
    if (!$desc) {
        $desc = $p['desc_tr'] ?: ($p['desc_en'] ?: ($p['desc_ru'] ?? ''));
    }
    return [
        'id' => (int) $p['id'],
        'name' => $p['name'],
        'developer' => $p['developer'],
        'city' => $p['city'],
        'city_name' => ec_city_name($p['city'], $lang),
        'district' => $p['district'],
        'units' => $p['units'] ? (int) $p['units'] : null,
        'delivery_date' => $p['delivery_date'],
        'price_min_gbp' => $p['price_min_gbp'] !== null ? (float) $p['price_min_gbp'] : null,
        'price_max_gbp' => $p['price_max_gbp'] !== null ? (float) $p['price_max_gbp'] : null,
        'payment_plan' => $p['payment_plan'],
        'description' => (string) $desc,
        'cover' => $p['cover_path'] ? ec_image_urls($p['cover_path']) : null,
        'agent_id' => $p['agent_id'] ? (int) $p['agent_id'] : null,
        'url' => ec_url_project((int) $p['id'], $p['slug'] ?: ec_slugify($p['name']), $lang),
    ];
}

function ec_projects(string $lang, int $limit = 24): array
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('projects') . " WHERE status = 'published' ORDER BY delivery_date ASC, id DESC LIMIT " . (int) $limit, ARRAY_A);
    return array_map(fn($p) => ec_project_present($p, $lang), $rows);
}

function ec_project_get(int $id, string $lang): ?array
{
    global $wpdb;
    $p = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('projects') . " WHERE id = %d AND status = 'published'", $id), ARRAY_A);
    return $p ? ec_project_present($p, $lang) : null;
}

/** @return int|WP_Error */
function ec_project_create(array $in, string $source = 'panel')
{
    global $wpdb;
    $name = sanitize_text_field($in['name'] ?? '');
    $city = sanitize_key($in['city'] ?? '');
    if (!$name || !isset(ec_cities()[$city])) {
        return new WP_Error('ec_invalid', 'Proje adı ve şehir zorunlu', ['status' => 400]);
    }
    $num = fn($k) => isset($in[$k]) && is_numeric($in[$k]) ? (float) $in[$k] : null;
    $date = sanitize_text_field($in['delivery_date'] ?? '');
    if (preg_match('/^\d{4}-\d{2}$/', $date)) {
        $date .= '-01';
    }
    $wpdb->insert(ec_table('projects'), [
        'agent_id' => !empty($in['agent_id']) ? (int) $in['agent_id'] : null,
        'status' => 'published',
        'name' => mb_substr($name, 0, 190),
        'slug' => ec_slugify($name),
        'developer' => sanitize_text_field($in['developer'] ?? '') ?: null,
        'city' => $city,
        'district' => sanitize_text_field($in['district'] ?? '') ?: null,
        'units' => !empty($in['units']) ? (int) $in['units'] : null,
        'delivery_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null,
        'price_min_gbp' => $num('price_min_gbp'),
        'price_max_gbp' => $num('price_max_gbp'),
        'payment_plan' => sanitize_text_field($in['payment_plan'] ?? '') ?: null,
        'desc_tr' => sanitize_textarea_field($in['desc_tr'] ?? ''),
        'desc_en' => sanitize_textarea_field($in['desc_en'] ?? ''),
        'desc_ru' => sanitize_textarea_field($in['desc_ru'] ?? ''),
        'source' => substr($source, 0, 20),
        'created_at' => ec_now(),
        'updated_at' => ec_now(),
    ]);
    return (int) $wpdb->insert_id ?: new WP_Error('ec_db', 'Proje kaydedilemedi', ['status' => 500]);
}

/** @return true|WP_Error */
function ec_project_set_cover(int $id, string $file)
{
    global $wpdb;
    $old = $wpdb->get_var($wpdb->prepare('SELECT cover_path FROM ' . ec_table('projects') . ' WHERE id = %d', $id));
    $rel = 'proje-' . $id . '/' . strtolower(wp_generate_password(10, false, false));
    $r = ec_image_variants($file, $rel);
    if (is_wp_error($r)) {
        return $r;
    }
    if ($old) {
        ec_image_delete($old);
    }
    $wpdb->update(ec_table('projects'), ['cover_path' => $rel, 'updated_at' => ec_now()], ['id' => $id]);
    return true;
}

function ec_project_delete(int $id): void
{
    global $wpdb;
    $old = $wpdb->get_var($wpdb->prepare('SELECT cover_path FROM ' . ec_table('projects') . ' WHERE id = %d', $id));
    if ($old) {
        ec_image_delete($old);
        @rmdir(ec_photo_base()['dir'] . '/proje-' . $id);
    }
    $wpdb->delete(ec_table('projects'), ['id' => $id]);
}
