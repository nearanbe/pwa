<?php
/**
 * Yönetim: bir ilanın fotoğrafları ve 360° fotoğrafları (0.7).
 * Emlak → İlanlar → "Fotoğraflar / 360°" bağlantısıyla açılır: admin.php?page=ec-media&id=10001
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_submenu_page('', 'Fotoğraflar ve 360°', 'Fotoğraflar ve 360°', 'manage_options', 'ec-media', 'ec_admin_media');
}, 17);

/** Yüklenen dosyaları ayır: 2:1 oranlılar 360°, diğerleri normal fotoğraf. @return array [normal, 360, hata] */
function ec_admin_take_uploads(int $id, string $field, bool $forcePano = false): array
{
    $n = $p = 0;
    $err = [];
    if (empty($_FILES[$field]['tmp_name'])) {
        return [0, 0, []];
    }
    foreach ((array) $_FILES[$field]['tmp_name'] as $i => $tmp) {
        if (($_FILES[$field]['error'][$i] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            $err[] = ($_FILES[$field]['name'][$i] ?? '?') . ': yüklenemedi (dosya çok büyük olabilir; sınır ' . size_format(wp_max_upload_size()) . ')';
            continue;
        }
        $sz = @getimagesize($tmp);
        if ($forcePano || ($sz && ec_is_pano_size((int) $sz[0], (int) $sz[1]))) {
            $r = ec_pano_add($id, $tmp, (string) pathinfo((string) $_FILES[$field]['name'][$i], PATHINFO_FILENAME));
            is_wp_error($r) ? $err[] = $_FILES[$field]['name'][$i] . ': ' . $r->get_error_message() : $p++;
        } else {
            $r = ec_photo_add($id, $tmp);
            is_wp_error($r) ? $err[] = $_FILES[$field]['name'][$i] . ': ' . $r->get_error_message() : $n++;
        }
    }
    return [$n, $p, $err];
}

function ec_admin_media(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $row = ec_listing_row($id);
    if (!$row) {
        echo '<div class="wrap"><p>İlan bulunamadı.</p></div>';
        return;
    }
    $it = ec_listings_present([$row], 'tr', true)[0];
    $form = fn(string $action, string $inner, string $btn, string $cls = 'button') => '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline">'
        . wp_nonce_field('ec_media_' . $id, '_wpnonce', true, false) . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="id" value="' . $id . '">' . $inner . ' <button class="' . $cls . '">' . esc_html($btn) . '</button></form>';
    echo '<div class="wrap"><h1>#' . $id . ' ' . esc_html($it['title']) . '</h1><p><a href="' . esc_url(admin_url('admin.php?page=ec-listings')) . '">← İlanlar</a> · <a href="' . esc_url($it['url']) . '" target="_blank">Sitede gör ↗</a></p>';
    ec_admin_notice();

    echo '<h2>Fotoğraflar (' . count($it['photos']) . ' / ' . EC_PHOTO_MAX . ')</h2><div style="display:flex;flex-wrap:wrap;gap:10px">';
    foreach ($it['photos'] as $i => $ph) {
        echo '<div style="width:160px"><img src="' . esc_url($ph['small']) . '" style="width:160px;height:100px;object-fit:cover;border-radius:6px">' . ($i === 0 ? '<b>Kapak</b> ' : '')
            . $form('ec_media_cover', '<input type="hidden" name="pid" value="' . (int) $ph['id'] . '">', '★ Kapak yap', 'button-link') . ' · '
            . $form('ec_media_del', '<input type="hidden" name="pid" value="' . (int) $ph['id'] . '">', 'Sil', 'button-link button-link-delete') . '</div>';
    }
    echo '</div><p>' . $form('ec_media_up', '<input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp">', 'Fotoğraf yükle', 'button button-primary') . '</p>';
    echo '<p class="description">Genişliği yüksekliğinin 2 katı olan (360°) fotoğraflar buradan yüklense de otomatik olarak 360° bölümüne gider.</p>';

    echo '<h2>🌐 360° fotoğraflar (' . count($it['panos']) . ' / ' . EC_PANO_MAX . ')</h2><table class="widefat striped" style="max-width:900px"><tbody>';
    foreach ($it['panos'] as $pn) {
        echo '<tr><td style="width:170px"><img src="' . esc_url($pn['thumb']) . '" style="width:160px;height:80px;object-fit:cover;border-radius:6px"></td><td>'
            . $form('ec_media_pano_title', '<input type="hidden" name="pid" value="' . (int) $pn['id'] . '"><input name="title" value="' . esc_attr($pn['title']) . '" placeholder="Oda adı (Salon, Mutfak…)">', 'Kaydet')
            . '</td><td>' . esc_html($pn['width'] . '×' . $pn['height']) . '</td><td>' . $form('ec_media_pano_del', '<input type="hidden" name="pid" value="' . (int) $pn['id'] . '">', 'Sil', 'button-link button-link-delete') . '</td></tr>';
    }
    if (!$it['panos']) {
        echo '<tr><td>Henüz 360° fotoğraf yok.</td></tr>';
    }
    echo '</tbody></table><p>' . $form('ec_media_pano_up', '<input type="file" name="panos[]" multiple accept="image/jpeg,image/png,image/webp">', '360° fotoğraf yükle', 'button button-primary') . '</p>';
    echo '<p class="description">Küresel fotoğraf: genişlik = 2 × yükseklik (ör. 6000×3000). Dosya adı oda adı olarak kullanılır, sonra değiştirebilirsiniz. Dosya başına sunucu sınırı: ' . esc_html(size_format(wp_max_upload_size())) . '.</p></div>';
}

add_action('admin_init', function () {
    foreach (['up', 'del', 'cover', 'pano_up', 'pano_del', 'pano_title'] as $a) {
        add_action('admin_post_ec_media_' . $a, function () use ($a) {
            global $wpdb;
            $id = (int) ($_POST['id'] ?? 0);
            if (!current_user_can('manage_options') || !check_admin_referer('ec_media_' . $id)) {
                wp_die('Yetkisiz işlem');
            }
            $pid = (int) ($_POST['pid'] ?? 0);
            $msg = 'Kaydedildi.';
            switch ($a) {
                case 'up':
                case 'pano_up':
                    [$n, $p, $err] = ec_admin_take_uploads($id, $a === 'up' ? 'photos' : 'panos', $a === 'pano_up');
                    $msg = "$n fotoğraf, $p adet 360° fotoğraf eklendi." . ($err ? '<br>' . esc_html(implode(' · ', $err)) : '');
                    if ($n && function_exists('ec_dup_check_listing')) {
                        $d = ec_dup_check_listing($id);
                        if ($d) {
                            $msg .= '<br>⚠️ ' . esc_html(ec_dup_message($d, 'tr'));
                        }
                    }
                    break;
                case 'del':
                    ec_photo_delete($id, $pid);
                    break;
                case 'cover':
                    $order = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('photos') . ' WHERE listing_id = %d ORDER BY (id = %d) DESC, sort, id', $id, $pid));
                    ec_photo_reorder($id, array_map('intval', $order));
                    break;
                case 'pano_del':
                    ec_pano_delete($id, $pid);
                    break;
                case 'pano_title':
                    $wpdb->update(ec_table('panos'), ['title' => mb_substr(ec_clean_text((string) ($_POST['title'] ?? '')), 0, 60) ?: null], ['id' => $pid, 'listing_id' => $id]);
                    break;
            }
            do_action('ec_listing_changed', $id);
            set_transient('ec_admin_msg_' . get_current_user_id(), $msg, 60);
            wp_safe_redirect(admin_url('admin.php?page=ec-media&id=' . $id));
            exit;
        });
    }
}, 1);
