<?php
/**
 * Yeni projeler (inşaat firmalarının ve ofislerin satıştaki projeleri).
 * Kapak ve galeri uploads/ilan-foto/proje-{no}/ altında durur; broşür PDF aynı klasörde.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_PROJECT_STATUSES = ['draft', 'pending', 'published', 'rejected', 'passive'];

function ec_project_present(array $p, string $lang, bool $full = false): array
{
    $desc = $p['desc_' . $lang] ?? '';
    if (!$desc) {
        $desc = $p['desc_tr'] ?: ($p['desc_en'] ?: ($p['desc_ru'] ?? ''));
    }
    $out = [
        'id' => (int) $p['id'],
        'status' => $p['status'],
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
        'progress' => $p['progress'] !== null ? (int) $p['progress'] : null,
        'project_type' => $p['project_type'] ?? null,
        'project_type_name' => !empty($p['project_type']) ? (ec_project_types()[$p['project_type']][$lang] ?? $p['project_type']) : null,
        'build_status' => ec_project_build_status($p),
        'build_status_name' => ec_build_statuses()[ec_project_build_status($p)][$lang] ?? '',
        'land_m2' => !empty($p['land_m2']) ? (int) $p['land_m2'] : null,
        'created_at' => $p['created_at'],
        'description' => (string) $desc,
        'cover' => $p['cover_path'] ? ec_image_urls($p['cover_path']) : null,
        'cover_ai' => !empty($p['cover_ai']),
        'agent_id' => $p['agent_id'] ? (int) $p['agent_id'] : null,
        'url' => ec_url_project((int) $p['id'], $p['slug'] ?: ec_slugify($p['name']), $lang),
    ];
    if ($full) {
        $am = $p['amenities'] ? (array) json_decode($p['amenities'], true) : [];
        $out['amenities'] = $am;
        $out['amenity_names'] = array_values(array_filter(array_map(fn($k) => ec_amenities()[$k][$lang] ?? null, $am)));
        $out['unit_types'] = $p['unit_types'] ? (array) json_decode($p['unit_types'], true) : [];
        $out['down_payment_pct'] = $p['down_payment_pct'] !== null ? (int) $p['down_payment_pct'] : null;
        $out['installments'] = $p['installments'] !== null ? (int) $p['installments'] : null;
        $out['video_url'] = $p['video_url'];
        $out['lat'] = $p['lat'] !== null ? (float) $p['lat'] : null;
        $out['lng'] = $p['lng'] !== null ? (float) $p['lng'] : null;
        $out['reject_reason'] = $p['reject_reason'];
        $out['desc'] = ['tr' => (string) $p['desc_tr'], 'en' => (string) $p['desc_en'], 'ru' => (string) $p['desc_ru']];
        $out['media'] = ec_project_media((int) $p['id']);
    }
    return $out;
}

function ec_projects(string $lang, int $limit = 24, ?int $agent_id = null): array
{
    global $wpdb;
    $sql = 'SELECT * FROM ' . ec_table('projects') . " WHERE status = 'published'";
    if ($agent_id) {
        $sql .= $wpdb->prepare(' AND agent_id = %d', $agent_id);
    }
    $rows = $wpdb->get_results($sql . ' ORDER BY delivery_date ASC, id DESC LIMIT ' . (int) $limit, ARRAY_A);
    return array_map(fn($p) => ec_project_present($p, $lang), $rows);
}

function ec_project_row(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('projects') . ' WHERE id = %d', $id), ARRAY_A);
    return $r ?: null;
}

function ec_project_get(int $id, string $lang, bool $only_public = true): ?array
{
    $p = ec_project_row($id);
    if (!$p || ($only_public && $p['status'] !== 'published')) {
        return null;
    }
    return ec_project_present($p, $lang, true);
}

/** Gelen proje verisini temizler. @return array{0: array, 1: array} */
function ec_project_clean(array $in, bool $partial = false): array
{
    $out = [];
    $err = [];
    if (!$partial || array_key_exists('name', $in)) {
        $name = sanitize_text_field((string) ($in['name'] ?? ''));
        $name ? ($out['name'] = mb_substr($name, 0, 190)) && ($out['slug'] = ec_slugify($name)) : $err['name'] = 'zorunlu';
    }
    if (!$partial || array_key_exists('city', $in)) {
        $city = sanitize_key((string) ($in['city'] ?? ''));
        isset(ec_cities()[$city]) ? $out['city'] = $city : $err['city'] = 'zorunlu';
    }
    foreach (['developer' => 190, 'district' => 80, 'payment_plan' => 190] as $k => $max) {
        if (array_key_exists($k, $in)) {
            $out[$k] = mb_substr(ec_clean_text((string) $in[$k]), 0, $max) ?: null;
        }
    }
    if (array_key_exists('project_type', $in)) {
        $v = (string) $in['project_type'];
        $out['project_type'] = isset(ec_project_types()[$v]) ? $v : null;
    }
    if (array_key_exists('build_status', $in)) {
        $v = (string) $in['build_status'];
        $out['build_status'] = isset(ec_build_statuses()[$v]) ? $v : null;
    }
    foreach (['units' => 100000, 'land_m2' => 10000000, 'progress' => 100, 'down_payment_pct' => 100, 'installments' => 360] as $k => $max) {
        if (array_key_exists($k, $in)) {
            $v = ($in[$k] === '' || $in[$k] === null) ? null : (int) $in[$k];
            $out[$k] = ($v !== null && ($v < 0 || $v > $max)) ? null : $v;
        }
    }
    foreach (['price_min_gbp', 'price_max_gbp', 'lat', 'lng'] as $k) {
        if (array_key_exists($k, $in)) {
            $out[$k] = is_numeric($in[$k]) ? (float) $in[$k] : null;
        }
    }
    if (array_key_exists('delivery_date', $in)) {
        $d = sanitize_text_field((string) $in['delivery_date']);
        if (preg_match('/^\d{4}-\d{2}$/', $d)) {
            $d .= '-01';
        }
        $out['delivery_date'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }
    foreach (['tr', 'en', 'ru'] as $l) {
        if (array_key_exists('desc_' . $l, $in)) {
            $out['desc_' . $l] = ec_clean_textarea((string) $in['desc_' . $l]);
        }
    }
    if (array_key_exists('amenities', $in)) {
        $a = array_values(array_intersect(array_map('strval', (array) $in['amenities']), array_keys(ec_amenities())));
        $out['amenities'] = $a ? wp_json_encode($a) : null;
    }
    if (array_key_exists('unit_types', $in)) {
        $units = [];
        foreach ((array) $in['unit_types'] as $u) {
            if (!is_array($u) || empty($u['type'])) {
                continue;
            }
            $units[] = [
                'type' => mb_substr(sanitize_text_field((string) $u['type']), 0, 30),
                'm2_min' => is_numeric($u['m2_min'] ?? null) ? (int) $u['m2_min'] : null,
                'm2_max' => is_numeric($u['m2_max'] ?? null) ? (int) $u['m2_max'] : null,
                'price_from_gbp' => is_numeric($u['price_from_gbp'] ?? null) ? (float) $u['price_from_gbp'] : null,
                'available' => is_numeric($u['available'] ?? null) ? (int) $u['available'] : null,
            ];
        }
        $out['unit_types'] = $units ? wp_json_encode(array_slice($units, 0, 20)) : null;
        // Fiyat aralığı girilmediyse daire tiplerinden hesapla
        $prices = array_filter(array_column($units, 'price_from_gbp'));
        if ($prices && empty($out['price_min_gbp'])) {
            $out['price_min_gbp'] = min($prices);
        }
    }
    if (array_key_exists('video_url', $in)) {
        $u = trim((string) $in['video_url']);
        $out['video_url'] = $u === '' ? null : (esc_url_raw($u, ['https', 'http']) ?: null);
    }
    if (array_key_exists('status', $in)) {
        in_array($in['status'], EC_PROJECT_STATUSES, true) ? $out['status'] = $in['status'] : $err['status'] = 'geçersiz';
    }
    return [$out, $err];
}

/** @return int|WP_Error */
function ec_project_create(array $in, string $source = 'panel')
{
    global $wpdb;
    [$d, $err] = ec_project_clean($in);
    if ($err) {
        return new WP_Error('ec_invalid', 'Proje adı ve şehir zorunlu', ['status' => 400, 'fields' => $err]);
    }
    $d['agent_id'] = !empty($in['agent_id']) ? (int) $in['agent_id'] : null;
    $d['status'] = $d['status'] ?? 'published';
    $d['source'] = substr($source, 0, 20);
    $d['created_at'] = $d['updated_at'] = ec_now();
    $wpdb->insert(ec_table('projects'), $d);
    return (int) $wpdb->insert_id ?: new WP_Error('ec_db', 'Proje kaydedilemedi', ['status' => 500]);
}

/** @return true|WP_Error */
function ec_project_update(int $id, array $in)
{
    global $wpdb;
    if (!ec_project_row($id)) {
        return new WP_Error('ec_not_found', 'Proje bulunamadı', ['status' => 404]);
    }
    [$d, $err] = ec_project_clean($in, true);
    if ($err) {
        return new WP_Error('ec_invalid', 'Geçersiz proje verisi', ['status' => 400, 'fields' => $err]);
    }
    $d['updated_at'] = ec_now();
    $wpdb->update(ec_table('projects'), $d, ['id' => $id]);
    do_action('ec_project_changed', $id);
    return true;
}

/** @return true|WP_Error */
function ec_project_set_cover(int $id, string $file, bool $ai = false)
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
    $wpdb->update(ec_table('projects'), ['cover_path' => $rel, 'cover_ai' => $ai ? 1 : 0, 'updated_at' => ec_now()], ['id' => $id]);
    return true;
}

/** Galeri fotoğrafı, kat planı (photo|plan) ya da broşür (brochure, PDF) ekler. @return array|WP_Error */
function ec_project_media_add(int $id, string $file, string $kind, string $title = '')
{
    global $wpdb;
    $t = ec_table('project_media');
    $kind = in_array($kind, ['photo', 'plan', 'brochure'], true) ? $kind : 'photo';
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE project_id = %d AND kind = %s", $id, $kind));
    $limit = ['photo' => 40, 'plan' => 20, 'brochure' => 3][$kind];
    if ($count >= $limit) {
        return new WP_Error('ec_media_limit', 'Bu türde en çok ' . $limit . ' dosya eklenebilir', ['status' => 400]);
    }
    $base = 'proje-' . $id . '/' . $kind . '-' . strtolower(wp_generate_password(8, false, false));
    $w = $h = null;
    if ($kind === 'brochure') {
        $head = (string) @file_get_contents($file, false, null, 0, 5);
        if ($head !== '%PDF-' || filesize($file) > 15 * MB_IN_BYTES) {
            return new WP_Error('ec_media_type', 'Broşür PDF olmalı ve 15 MB’ı geçmemeli', ['status' => 400]);
        }
        $dir = ec_photo_base()['dir'] . '/proje-' . $id;
        wp_mkdir_p($dir);
        if (!@copy($file, ec_photo_base()['dir'] . '/' . $base . '.pdf')) {
            return new WP_Error('ec_media_save', 'Dosya kaydedilemedi', ['status' => 500]);
        }
    } else {
        $r = ec_image_variants($file, $base);
        if (is_wp_error($r)) {
            return $r;
        }
        [$w, $h] = [$r['width'], $r['height']];
    }
    $wpdb->insert($t, [
        'project_id' => $id, 'kind' => $kind, 'path' => $base, 'title' => mb_substr(sanitize_text_field($title), 0, 190) ?: null,
        'width' => $w, 'height' => $h, 'sort' => $count, 'created_at' => ec_now(),
    ]);
    return ec_project_media_present($wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $wpdb->insert_id), ARRAY_A));
}

function ec_project_media_present(array $m): array
{
    $base = ec_photo_base()['url'] . '/' . $m['path'];
    $o = ['id' => (int) $m['id'], 'kind' => $m['kind'], 'title' => $m['title']];
    if ($m['kind'] === 'brochure') {
        $o['url'] = $base . '.pdf';
    } else {
        $o += ec_image_urls($m['path']) + ['width' => $m['width'] ? (int) $m['width'] : null, 'height' => $m['height'] ? (int) $m['height'] : null];
    }
    return $o;
}

function ec_project_media(int $id): array
{
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('project_media') . ' WHERE project_id = %d ORDER BY kind, sort, id', $id), ARRAY_A);
    $out = ['photo' => [], 'plan' => [], 'brochure' => []];
    foreach ($rows as $r) {
        $out[$r['kind']][] = ec_project_media_present($r);
    }
    return $out;
}

function ec_project_media_delete(int $id, int $media_id): bool
{
    global $wpdb;
    $t = ec_table('project_media');
    $m = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d AND project_id = %d", $media_id, $id), ARRAY_A);
    if (!$m) {
        return false;
    }
    if ($m['kind'] === 'brochure') {
        @unlink(ec_photo_base()['dir'] . '/' . $m['path'] . '.pdf');
    } else {
        ec_image_delete($m['path']);
    }
    $wpdb->delete($t, ['id' => $media_id]);
    return true;
}

function ec_project_delete(int $id): void
{
    global $wpdb;
    $old = $wpdb->get_var($wpdb->prepare('SELECT cover_path FROM ' . ec_table('projects') . ' WHERE id = %d', $id));
    if ($old) {
        ec_image_delete($old);
    }
    foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('project_media') . ' WHERE project_id = %d', $id)) as $mid) {
        ec_project_media_delete($id, (int) $mid);
    }
    foreach ((array) glob(ec_photo_base()['dir'] . '/proje-' . $id . '/video-*') as $vf) {
        @unlink($vf);
    }
    @rmdir(ec_photo_base()['dir'] . '/proje-' . $id);
    $wpdb->delete(ec_table('projects'), ['id' => $id]);
}

/** İnşaat durumu: girilmediyse ilerleme ve teslim tarihinden tahmin edilir */
function ec_project_build_status(array $p): string
{
    if (!empty($p['build_status']) && isset(ec_build_statuses()[$p['build_status']])) {
        return $p['build_status'];
    }
    if ((int) ($p['progress'] ?? 0) >= 100 || (!empty($p['delivery_date']) && strtotime($p['delivery_date']) < time() - 90 * DAY_IN_SECONDS && (int) ($p['progress'] ?? 0) >= 90)) {
        return 'completed';
    }
    return (int) ($p['progress'] ?? 0) > 0 ? 'ongoing' : 'planned';
}

/** Aynı firmanın diğer projeleri */
function ec_projects_by_agent(int $agent_id, int $exclude, string $lang, int $limit = 4): array
{
    return array_values(array_filter(ec_projects($lang, $limit + 1, $agent_id), fn($x) => $x['id'] !== $exclude));
}

/** Aylık taksit: (fiyat − peşinat) / taksit sayısı */
function ec_installment(float $price, int $downPct, int $months): ?float
{
    return $months > 0 ? ($price * (100 - $downPct) / 100) / $months : null;
}
