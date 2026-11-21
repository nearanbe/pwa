<?php
/**
 * İlan kayıtları: doğrulama, oluşturma, güncelleme, arama ve sunum.
 * Tema ve REST API bu fonksiyonlardan başka bir yoldan veritabanına dokunmaz.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_LISTING_STATUSES = ['draft', 'pending', 'published', 'rejected', 'sold', 'passive'];

function ec_now(): string
{
    return current_time('mysql', true);
}

/**
 * Gelen veriyi doğrular ve temizler.
 * @return array{0: array, 1: array} [temiz alanlar, hatalar]
 */
function ec_listing_clean(array $in, bool $partial = false): array
{
    $out = [];
    $err = [];
    $types = ec_property_types();
    $cities = ec_cities();

    $req = function (string $k) use ($in, $partial, &$err): bool {
        if (!array_key_exists($k, $in) || $in[$k] === '' || $in[$k] === null) {
            if (!$partial) {
                $err[$k] = 'zorunlu';
            }
            return false;
        }
        return true;
    };

    if ($req('mode')) {
        in_array($in['mode'], ec_modes(), true) ? $out['mode'] = $in['mode'] : $err['mode'] = 'geçersiz';
    }
    if ($req('ptype')) {
        isset($types[$in['ptype']]) ? $out['ptype'] = $in['ptype'] : $err['ptype'] = 'geçersiz';
    }
    if (array_key_exists('subtype', $in) && $in['subtype'] !== '' && $in['subtype'] !== null) {
        $pt = $out['ptype'] ?? ($in['ptype'] ?? '');
        isset($types[$pt]['sub'][$in['subtype']]) ? $out['subtype'] = $in['subtype'] : $err['subtype'] = 'geçersiz';
    }
    if ($req('city')) {
        isset($cities[$in['city']]) ? $out['city'] = $in['city'] : $err['city'] = 'geçersiz';
    }
    if (array_key_exists('district', $in)) {
        $out['district'] = sanitize_text_field((string) $in['district']) ?: null;
    }
    if ($req('price')) {
        is_numeric($in['price']) && $in['price'] >= 0 ? $out['price'] = round((float) $in['price'], 2) : $err['price'] = 'geçersiz';
    }
    if (array_key_exists('currency', $in)) {
        in_array($in['currency'], EC_CURRENCIES, true) ? $out['currency'] = $in['currency'] : $err['currency'] = 'geçersiz';
    }
    foreach (['area_m2', 'baths'] as $k) {
        if (array_key_exists($k, $in)) {
            $out[$k] = ($in[$k] === '' || $in[$k] === null) ? null : max(0, (int) $in[$k]);
        }
    }
    if (array_key_exists('rooms', $in)) {
        $out['rooms'] = $in['rooms'] ? substr(sanitize_text_field((string) $in['rooms']), 0, 10) : null;
    }
    if (array_key_exists('deed_type', $in)) {
        $d = $in['deed_type'];
        ($d === null || $d === '' || isset(ec_deed_types()[$d])) ? $out['deed_type'] = $d ?: null : $err['deed_type'] = 'geçersiz';
    }
    if (array_key_exists('furnished', $in)) {
        $out['furnished'] = $in['furnished'] === null || $in['furnished'] === '' ? null : (int) (bool) $in['furnished'];
    }
    foreach (['lat', 'lng'] as $k) {
        if (array_key_exists($k, $in)) {
            $out[$k] = is_numeric($in[$k]) ? (float) $in[$k] : null;
        }
    }
    if (array_key_exists('status', $in)) {
        in_array($in['status'], EC_LISTING_STATUSES, true) ? $out['status'] = $in['status'] : $err['status'] = 'geçersiz';
    }
    if (array_key_exists('features', $in)) {
        $f = array_values(array_intersect(array_map('strval', (array) $in['features']), array_keys(ec_features())));
        $out['features'] = $f ? wp_json_encode($f) : null;
    }
    foreach (['vat_paid', 'transformer_paid'] as $k) {
        if (array_key_exists($k, $in)) {
            $out[$k] = ($in[$k] === '' || $in[$k] === null) ? null : (int) (bool) $in[$k];
        }
    }
    foreach (['sea_distance_m' => 100000, 'total_floors' => 200, 'year_built' => 2100] as $k => $max) {
        if (array_key_exists($k, $in)) {
            $v = ($in[$k] === '' || $in[$k] === null) ? null : (int) $in[$k];
            $out[$k] = ($v !== null && ($v < 0 || $v > $max)) ? null : $v;
        }
    }
    if (array_key_exists('floor', $in)) {
        $out['floor'] = $in['floor'] === '' || $in['floor'] === null ? null : mb_substr(sanitize_text_field((string) $in['floor']), 0, 10);
    }
    if (array_key_exists('payment_plan', $in)) {
        $out['payment_plan'] = mb_substr(ec_clean_text((string) $in['payment_plan']), 0, 190) ?: null;
    }
    foreach (['video_url', 'tour_url'] as $k) {
        if (array_key_exists($k, $in)) {
            $u = trim((string) $in[$k]);
            $out[$k] = $u === '' ? null : (esc_url_raw($u, ['https', 'http']) ?: null);
        }
    }
    if (array_key_exists('project_id', $in)) {
        $out['project_id'] = $in['project_id'] ? (int) $in['project_id'] : null;
    }

    // Çeviriler: ['tr' => ['title' => .., 'description' => ..], 'en' => ..]
    if (array_key_exists('i18n', $in)) {
        $out['i18n'] = [];
        foreach ((array) $in['i18n'] as $lang => $t) {
            if (!in_array($lang, EC_LANGS, true) || !is_array($t)) {
                continue;
            }
            $title = ec_clean_text((string) ($t['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out['i18n'][$lang] = [
                'title' => mb_substr($title, 0, 200),
                'description' => ec_clean_textarea((string) ($t['description'] ?? '')),
            ];
        }
        if (!$partial && empty($out['i18n'])) {
            $err['i18n'] = 'en az bir dilde başlık zorunlu';
        }
    } elseif (!$partial) {
        $err['i18n'] = 'en az bir dilde başlık zorunlu';
    }

    return [$out, $err];
}

/** @return int|WP_Error yeni ilan numarası */
function ec_listing_create(array $in, ?int $agent_id = null, string $source = 'panel')
{
    global $wpdb;
    [$d, $err] = ec_listing_clean($in);
    if (!$err && $d['mode'] === 'sale' && ($d['status'] ?? 'draft') !== 'draft' && empty($d['deed_type'])) {
        $err['deed_type'] = 'satılık ilanda tapu türü zorunlu';
    }
    if ($err) {
        return new WP_Error('ec_invalid', 'Geçersiz ilan verisi', ['status' => 400, 'fields' => $err]);
    }
    $i18n = $d['i18n'];
    unset($d['i18n']);
    $d['currency'] = $d['currency'] ?? 'GBP';
    $d['price_gbp'] = round(ec_to_gbp($d['price'], $d['currency']), 2);
    $d['status'] = $d['status'] ?? 'draft';
    $d['agent_id'] = $agent_id;
    $d['source'] = substr($source, 0, 20);
    $d['created_at'] = $d['updated_at'] = ec_now();
    if ($d['status'] === 'published') {
        $d['published_at'] = ec_now();
    }

    if (!$wpdb->insert(ec_table('listings'), $d)) {
        return new WP_Error('ec_db', 'İlan kaydedilemedi', ['status' => 500]);
    }
    $id = (int) $wpdb->insert_id;
    ec_listing_save_i18n($id, $i18n);
    if ($d['status'] === 'published') {
        do_action('ec_listing_published', $id);
    }
    return $id;
}

/** @return true|WP_Error */
function ec_listing_update(int $id, array $in)
{
    global $wpdb;
    $row = ec_listing_row($id);
    if (!$row) {
        return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
    }
    if (isset($in['subtype']) && !isset($in['ptype'])) {
        $in['ptype'] = $row['ptype'];
    }
    [$d, $err] = ec_listing_clean($in, true);
    // KKTC: yayına giden satılık ilanda tapu türü zorunlu (taslak serbest)
    $final = array_merge($row, $d);
    if (!$err && $final['mode'] === 'sale' && in_array($final['status'], ['published', 'pending'], true) && empty($final['deed_type'])) {
        $err['deed_type'] = 'satılık ilanda tapu türü zorunlu';
    }
    if ($err) {
        return new WP_Error('ec_invalid', 'Geçersiz ilan verisi', ['status' => 400, 'fields' => $err]);
    }
    $i18n = $d['i18n'] ?? null;
    unset($d['i18n']);
    if (isset($d['price']) || isset($d['currency'])) {
        $d['price_gbp'] = round(ec_to_gbp($d['price'] ?? (float) $row['price'], $d['currency'] ?? $row['currency']), 2);
    }
    if (($d['status'] ?? null) === 'published' && !$row['published_at']) {
        $d['published_at'] = ec_now();
    }
    $d['updated_at'] = ec_now();
    $wpdb->update(ec_table('listings'), $d, ['id' => $id]);
    if ($i18n) {
        ec_listing_save_i18n($id, $i18n);
    }
    if (($d['status'] ?? null) === 'published' && $row['status'] !== 'published') {
        do_action('ec_listing_published', $id);
    } elseif ($row['status'] === 'published' || in_array($d['status'] ?? '', ['sold', 'passive'], true)) {
        do_action('ec_listing_changed', $id);
    }
    return true;
}

function ec_listing_save_i18n(int $id, array $i18n): void
{
    global $wpdb;
    foreach ($i18n as $lang => $t) {
        $wpdb->replace(ec_table('listing_i18n'), [
            'listing_id' => $id,
            'lang' => $lang,
            'title' => $t['title'],
            'slug' => ec_slugify($t['title']),
            'description' => $t['description'],
        ]);
    }
}

function ec_listing_row(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('listings') . ' WHERE id = %d', $id), ARRAY_A);
    return $r ?: null;
}

/** İlanları verilen dilde (yoksa TR, yoksa ilk bulunan dilde) metinleriyle hazırlar */
function ec_listings_present(array $rows, string $lang, bool $full = false): array
{
    global $wpdb;
    if (!$rows) {
        return [];
    }
    $ids = array_map(fn($r) => (int) $r['id'], $rows);
    $in = implode(',', $ids);

    $texts = [];
    foreach ($wpdb->get_results('SELECT * FROM ' . ec_table('listing_i18n') . " WHERE listing_id IN ($in)", ARRAY_A) as $t) {
        $texts[$t['listing_id']][$t['lang']] = $t;
    }
    $photos = [];
    $photoSql = 'SELECT * FROM ' . ec_table('photos') . " WHERE listing_id IN ($in) ORDER BY listing_id, sort, id";
    foreach ($wpdb->get_results($photoSql, ARRAY_A) as $p) {
        $photos[$p['listing_id']][] = $p;
    }
    $agentIds = array_filter(array_unique(array_map(fn($r) => (int) $r['agent_id'], $rows)));
    $agents = $agentIds ? ec_agents_by_ids($agentIds) : [];

    $out = [];
    foreach ($rows as $r) {
        $id = (int) $r['id'];
        $all = $texts[$id] ?? [];
        $t = $all[$lang] ?? $all[EC_DEFAULT_LANG] ?? (reset($all) ?: ['title' => '#' . $id, 'slug' => 'ilan', 'description' => '']);
        $ph = array_map('ec_photo_present', $photos[$id] ?? []);
        $item = [
            'id' => $id,
            'status' => $r['status'],
            'mode' => $r['mode'],
            'ptype' => $r['ptype'],
            'ptype_name' => ec_type_name($r['ptype'], $lang),
            'subtype' => $r['subtype'],
            'subtype_name' => $r['subtype'] ? ec_subtype_name($r['ptype'], $r['subtype'], $lang) : ec_type_name($r['ptype'], $lang),
            'city' => $r['city'],
            'city_name' => ec_city_name($r['city'], $lang),
            'district' => $r['district'],
            'price' => (float) $r['price'],
            'currency' => $r['currency'],
            'price_gbp' => (float) $r['price_gbp'],
            'area_m2' => $r['area_m2'] !== null ? (int) $r['area_m2'] : null,
            'rooms' => $r['rooms'],
            'baths' => $r['baths'] !== null ? (int) $r['baths'] : null,
            'deed_type' => $r['deed_type'],
            'deed_name' => $r['deed_type'] ? (ec_deed_types()[$r['deed_type']][$lang] ?? $r['deed_type']) : null,
            'agent_id' => $r['agent_id'] ? (int) $r['agent_id'] : null,
            'views' => (int) $r['views'],
            'furnished' => $r['furnished'] === null ? null : (bool) $r['furnished'],
            'featured' => $r['featured_until'] && strtotime($r['featured_until'] . ' UTC') > time(),
            'title' => $t['title'],
            'url' => ec_url_listing($id, $t['slug'], $lang),
            'cover' => $ph[0] ?? null,
            'agent' => isset($agents[(int) $r['agent_id']]) ? ec_agent_public($agents[(int) $r['agent_id']]) : null,
            'published_at' => $r['published_at'],
        ];
        if ($full) {
            $feat = $r['features'] ? (array) json_decode($r['features'], true) : [];
            $item['features'] = $feat;
            $item['feature_names'] = array_values(array_filter(array_map(fn($k) => ec_features()[$k][$lang] ?? null, $feat)));
            $item['deed_desc'] = $r['deed_type'] ? ec_deed_desc($r['deed_type'], $lang) : '';
            foreach (['vat_paid', 'transformer_paid'] as $k) {
                $item[$k] = $r[$k] === null ? null : (bool) $r[$k];
            }
            foreach (['sea_distance_m', 'total_floors', 'year_built', 'project_id'] as $k) {
                $item[$k] = $r[$k] !== null ? (int) $r[$k] : null;
            }
            foreach (['floor', 'payment_plan', 'video_url', 'tour_url', 'reject_reason', 'district', 'created_at', 'updated_at'] as $k) {
                $item[$k] = $r[$k];
            }
            $item['description'] = $t['description'];
            $item['photos'] = $ph;
            $item['lat'] = $r['lat'] !== null ? (float) $r['lat'] : null;
            $item['lng'] = $r['lng'] !== null ? (float) $r['lng'] : null;
            $item['translations'] = [];
            foreach ($all as $l => $tt) {
                $item['translations'][$l] = ['title' => $tt['title'], 'url' => ec_url_listing($id, $tt['slug'], $l)];
            }
        }
        $out[] = $item;
    }
    return $out;
}

function ec_listing_get(int $id, string $lang, bool $only_public = true): ?array
{
    $r = ec_listing_row($id);
    if (!$r || ($only_public && !in_array($r['status'], ['published', 'sold'], true))) {
        return null;
    }
    return ec_listings_present([$r], $lang, true)[0];
}

/**
 * Arama. Filtreler: mode, ptype, subtype, city, district, rooms, min, max (seçili para biriminde),
 * currency, ids (favoriler), sort (new|price_asc|price_desc), agent_id.
 */
function ec_listing_search(array $f, string $lang, int $page = 1, int $per = 24): array
{
    global $wpdb;
    $t = ec_table('listings');
    $where = ["status = 'published'"];
    $args = [];
    foreach (['mode', 'ptype', 'subtype', 'city', 'district', 'rooms', 'deed_type'] as $k) {
        if (!empty($f[$k])) {
            $where[] = "$k = %s";
            $args[] = (string) $f[$k];
        }
    }
    if (!empty($f['agent_id'])) {
        $where[] = 'agent_id = %d';
        $args[] = (int) $f['agent_id'];
    }
    $cur = in_array($f['currency'] ?? '', EC_CURRENCIES, true) ? $f['currency'] : 'GBP';
    if (isset($f['min']) && is_numeric($f['min'])) {
        $where[] = 'price_gbp >= %f';
        $args[] = ec_to_gbp((float) $f['min'], $cur);
    }
    if (isset($f['max']) && is_numeric($f['max']) && (float) $f['max'] > 0) {
        $where[] = 'price_gbp <= %f';
        $args[] = ec_to_gbp((float) $f['max'], $cur);
    }
    if (isset($f['ids'])) {
        $ids = array_filter(array_map('intval', (array) $f['ids']));
        if (!$ids) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
        $where[] = 'id IN (' . implode(',', $ids) . ')';
    }
    $order = match ($f['sort'] ?? 'new') {
        'price_asc' => 'price_gbp ASC',
        'price_desc' => 'price_gbp DESC',
        default => '(featured_until IS NOT NULL AND featured_until > UTC_TIMESTAMP()) DESC, published_at DESC, id DESC',
    };
    $w = implode(' AND ', $where);
    $page = max(1, $page);
    $per = min(60, max(1, $per));
    $off = ($page - 1) * $per;

    $countSql = "SELECT COUNT(*) FROM $t WHERE $w";
    $listSql = "SELECT * FROM $t WHERE $w ORDER BY $order LIMIT $per OFFSET $off";
    $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($countSql, $args)) : $wpdb->get_var($countSql));
    $rows = $args ? $wpdb->get_results($wpdb->prepare($listSql, $args), ARRAY_A) : $wpdb->get_results($listSql, ARRAY_A);

    return [
        'items' => ec_listings_present($rows, $lang),
        'total' => $total,
        'page' => $page,
        'pages' => (int) ceil($total / $per),
    ];
}

/** Öne çıkan (ücretli) ilanlar: slider ve "Öne çıkan ilanlar" bölümü için */
function ec_listing_featured(string $lang, ?string $mode = null, int $limit = 8): array
{
    global $wpdb;
    $t = ec_table('listings');
    $sql = "SELECT * FROM $t WHERE status = 'published' AND featured_until > UTC_TIMESTAMP()";
    if ($mode) {
        $sql .= $wpdb->prepare(' AND mode = %s', $mode);
    }
    $sql .= ' ORDER BY featured_until DESC LIMIT ' . (int) $limit;
    return ec_listings_present($wpdb->get_results($sql, ARRAY_A), $lang);
}

function ec_listing_count(?string $mode = null): int
{
    global $wpdb;
    $t = ec_table('listings');
    $sql = "SELECT COUNT(*) FROM $t WHERE status = 'published'";
    if ($mode) {
        $sql .= $wpdb->prepare(' AND mode = %s', $mode);
    }
    return (int) $wpdb->get_var($sql);
}

function ec_listing_add_view(int $id): void
{
    global $wpdb;
    $wpdb->query($wpdb->prepare('UPDATE ' . ec_table('listings') . ' SET views = views + 1 WHERE id = %d', $id));
}

/**
 * Yeni / yeniden yayına gönderilen ilanın gerçek durumu.
 * Yönetici: istenen durum. Ofis ve inşaat firması (onaylı hesap): yayında.
 * Bireysel danışman ve mülk sahibi ya da onaysız hesap: yönetici onayı bekler.
 */
function ec_listing_target_status(?array $agent, string $requested): string
{
    if ($requested !== 'published') {
        return $requested;
    }
    if (!$agent) {
        return 'published';
    }
    $kind = ec_member_kinds()[$agent['kind']] ?? ['needs_review' => true];
    if ($agent['status'] !== 'active' || !empty($kind['needs_review'])) {
        return 'pending';
    }
    return 'published';
}

function ec_listing_count_pending(): int
{
    global $wpdb;
    return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('listings') . " WHERE status = 'pending'");
}
