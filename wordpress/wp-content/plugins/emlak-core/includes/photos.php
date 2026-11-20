<?php
/**
 * İlan fotoğrafları. Dosyalar WordPress medya kütüphanesine DEĞİL,
 * uploads/ilan-foto/{ilan_no}/ klasörüne yazılır (geçişte klasör olduğu gibi kopyalanır).
 * Her fotoğrafın iki boyutu üretilir: -l (büyük, en çok 1600px) ve -s (küçük, 640px), WebP.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_PHOTO_MAX = 30;

function ec_photo_base(): array
{
    $u = wp_upload_dir(null, false);
    return ['dir' => $u['basedir'] . '/ilan-foto', 'url' => $u['baseurl'] . '/ilan-foto'];
}

function ec_photo_present(array $p): array
{
    $b = ec_photo_base()['url'] . '/' . $p['path'];
    return [
        'id' => (int) $p['id'],
        'large' => $b . '-l.webp',
        'small' => $b . '-s.webp',
        'width' => $p['width'] ? (int) $p['width'] : null,
        'height' => $p['height'] ? (int) $p['height'] : null,
    ];
}

/**
 * Bir görsel dosyasını ilana ekler.
 * @return array|WP_Error eklenen fotoğraf
 */
function ec_photo_add(int $listing_id, string $file)
{
    global $wpdb;
    $t = ec_table('photos');
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE listing_id = %d", $listing_id));
    if ($count >= EC_PHOTO_MAX) {
        return new WP_Error('ec_photo_limit', 'Bir ilana en çok ' . EC_PHOTO_MAX . ' fotoğraf eklenebilir', ['status' => 400]);
    }
    $info = @getimagesize($file);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return new WP_Error('ec_photo_type', 'Sadece JPG, PNG veya WebP yüklenebilir', ['status' => 400]);
    }

    $base = ec_photo_base();
    $dir = $base['dir'] . '/' . $listing_id;
    if (!wp_mkdir_p($dir)) {
        return new WP_Error('ec_photo_dir', 'Klasör oluşturulamadı', ['status' => 500]);
    }
    $name = wp_generate_password(10, false, false);
    $rel = $listing_id . '/' . strtolower($name);

    $size = null;
    foreach (['l' => 1600, 's' => 640] as $suffix => $max) {
        $ed = wp_get_image_editor($file);
        if (is_wp_error($ed)) {
            return $ed;
        }
        $ed->set_quality($suffix === 'l' ? 82 : 75);
        $s = $ed->get_size();
        if ($s['width'] > $max || $s['height'] > $max) {
            $ed->resize($max, $max, false);
        }
        $saved = $ed->save($base['dir'] . '/' . $rel . '-' . $suffix . '.webp', 'image/webp');
        if (is_wp_error($saved)) {
            return $saved;
        }
        if ($suffix === 'l') {
            $size = [$saved['width'], $saved['height']];
        }
    }

    $wpdb->insert($t, [
        'listing_id' => $listing_id,
        'sort' => $count,
        'path' => $rel,
        'width' => $size[0],
        'height' => $size[1],
        'created_at' => ec_now(),
    ]);
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $wpdb->insert_id), ARRAY_A);
    return ec_photo_present($row);
}

function ec_photo_delete(int $listing_id, int $photo_id): bool
{
    global $wpdb;
    $t = ec_table('photos');
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d AND listing_id = %d", $photo_id, $listing_id), ARRAY_A);
    if (!$row) {
        return false;
    }
    $dir = ec_photo_base()['dir'];
    foreach (['l', 's'] as $s) {
        @unlink($dir . '/' . $row['path'] . '-' . $s . '.webp');
    }
    $wpdb->delete($t, ['id' => $photo_id]);
    return true;
}

/** Fotoğraf sırasını değiştirir: $order = [foto_id, foto_id, ...] */
function ec_photo_reorder(int $listing_id, array $order): void
{
    global $wpdb;
    foreach (array_values($order) as $i => $pid) {
        $wpdb->update(ec_table('photos'), ['sort' => $i], ['id' => (int) $pid, 'listing_id' => $listing_id]);
    }
}
