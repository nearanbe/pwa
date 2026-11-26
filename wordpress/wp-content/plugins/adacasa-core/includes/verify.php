<?php
/**
 * Doğrulanmış ilan (1.4).
 *
 * İlan sahibi tapu fotokopisini ya da mal sahibi yetki belgesini yükler (panel: ilan satırındaki "Doğrulama belgesi",
 * Telegram: /duzenle → "📄 Doğrulama belgesi" düğmesi, sonra belgenin fotoğrafı/PDF'i).
 * Belge özel klasörde (uploads/ec-ozel/belge, web'den erişilemez) rastgele adla saklanır; yöneticiye Telegram'dan
 * [✅ Onayla] [❌ Reddet] düğmeleriyle ve WP Admin → Emlak → Doğrulama ekranına düşer.
 * Onaylanınca listings.verified_at dolar, ilanda "✔ Doğrulanmış ilan" rozeti çıkar. Karar verilince belge dosyası
 * silinir (KVKK: gereğinden uzun saklanmaz); yalnızca kararın kaydı kalır.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_verify_kinds(): array
{
    return ['tapu' => 'Tapu (koçan) fotokopisi', 'yetki' => 'Mal sahibi yetki belgesi / satış yetkisi', 'sozlesme' => 'Müteahhit satış yetkisi / sözleşme'];
}

function ec_verify_dir(): string
{
    $dir = ec_receipt_dir() . '/belge';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
        @file_put_contents($dir . '/index.php', "<?php // sessizlik\n");
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    return $dir;
}

/**
 * Belgeyi kaydeder ve incelemeye gönderir. $file: geçici dosya yolu
 * @return int|WP_Error belge kaydı no
 */
function ec_verify_submit(int $listing_id, int $agent_id, string $file, string $kind = 'tapu')
{
    global $wpdb;
    $row = ec_listing_row($listing_id);
    if (!$row || (int) $row['agent_id'] !== $agent_id) {
        return new WP_Error('ec_forbidden', 'Bu ilan size ait değil', ['status' => 403]);
    }
    $mime = (string) (wp_check_filetype_and_ext($file, basename($file))['type'] ?: mime_content_type($file));
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? null;
    if (!$ext) {
        return new WP_Error('ec_invalid', 'Belge JPG, PNG, WebP ya da PDF olmalı', ['status' => 400]);
    }
    if (filesize($file) > 12 * MB_IN_BYTES) {
        return new WP_Error('ec_invalid', 'Belge en fazla 12 MB olabilir', ['status' => 400]);
    }
    $t = ec_table('verify_docs');
    if ((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE agent_id = %d AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)", $agent_id)) >= 30) {
        return new WP_Error('ec_too_many', 'Bugün çok fazla belge gönderildi', ['status' => 429]);
    }
    $name = wp_generate_password(24, false, false) . '.' . $ext;
    if (!@copy($file, ec_verify_dir() . '/' . $name)) {
        return new WP_Error('ec_io', 'Belge kaydedilemedi', ['status' => 500]);
    }
    // Aynı ilanın bekleyen eski belgesi varsa yenisiyle değiştir
    foreach ($wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE listing_id = %d AND status = 'pending'", $listing_id), ARRAY_A) as $old) {
        @unlink(ec_verify_dir() . '/' . $old['path']);
        $wpdb->update($t, ['status' => 'replaced', 'path' => ''], ['id' => $old['id']]);
    }
    $wpdb->insert($t, ['listing_id' => $listing_id, 'agent_id' => $agent_id, 'kind' => isset(ec_verify_kinds()[$kind]) ? $kind : 'tapu', 'path' => $name, 'mime' => $mime, 'status' => 'pending', 'created_at' => ec_now()]);
    $id = (int) $wpdb->insert_id;
    ec_verify_notify_admin($id);
    return $id;
}

function ec_verify_row(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('verify_docs') . ' WHERE id = %d', $id), ARRAY_A);
    return $r ?: null;
}

/** Yöneticiye Telegram'dan belge + düğmeler; ayrıca e-posta */
function ec_verify_notify_admin(int $id): void
{
    $d = ec_verify_row($id);
    $l = $d ? ec_listing_row((int) $d['listing_id']) : null;
    if (!$d || !$l) {
        return;
    }
    $a = ec_agent((int) $d['agent_id']);
    $it = ec_listings_present([$l], 'tr')[0];
    $text = "📄 Doğrulama belgesi #$id\nİlan #{$l['id']} " . $it['title'] . "\n" . $it['url'] . "\nGönderen: " . ($a['name'] ?? '-') . "\nBelge: " . (ec_verify_kinds()[$d['kind']] ?? $d['kind']) . "\n\nBelgedeki ada/parsel ve malik bilgisinin ilanla uyuştuğunu kontrol edin.";
    $s = function_exists('ec_tg_settings') ? ec_tg_settings() : [];
    if (!empty($s['admin_chat']) && ec_tg_ready()) {
        $kb = ['inline_keyboard' => [[['text' => '✅ Onayla', 'callback_data' => 'vok:' . $id], ['text' => '❌ Reddet', 'callback_data' => 'vrj:' . $id]]]];
        $path = ec_verify_dir() . '/' . $d['path'];
        $method = $d['mime'] === 'application/pdf' ? 'sendDocument' : 'sendPhoto';
        $field = $d['mime'] === 'application/pdf' ? 'document' : 'photo';
        $res = wp_remote_post('https://api.telegram.org/bot' . $s['token'] . '/' . $method, [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'multipart/form-data; boundary=ecverify'],
            'body' => ec_verify_multipart(['chat_id' => $s['admin_chat'], 'caption' => mb_substr($text, 0, 1000), 'reply_markup' => wp_json_encode($kb)], $field, $path, $d['mime']),
        ]);
        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
            ec_tg_send($s['admin_chat'], $text . "\n\nİnceleme: " . admin_url('admin.php?page=ec-verify'), $kb['inline_keyboard']);
        }
    } else {
        wp_mail(get_option('admin_email'), get_bloginfo('name') . ': doğrulama belgesi bekliyor', $text . "\n\nİnceleme: " . admin_url('admin.php?page=ec-verify'));
    }
}

function ec_verify_multipart(array $fields, string $fileField, string $path, string $mime): string
{
    $b = '';
    foreach ($fields as $k => $v) {
        $b .= "--ecverify\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n$v\r\n";
    }
    $b .= "--ecverify\r\nContent-Disposition: form-data; name=\"$fileField\"; filename=\"belge" . ($mime === 'application/pdf' ? '.pdf' : '.jpg') . "\"\r\nContent-Type: $mime\r\n\r\n" . (string) @file_get_contents($path) . "\r\n--ecverify--\r\n";
    return $b;
}

/** Karar: approve | reject. Belge dosyası silinir. */
function ec_verify_decide(int $id, string $action, string $note = ''): bool
{
    global $wpdb;
    $d = ec_verify_row($id);
    if (!$d || $d['status'] !== 'pending') {
        return false;
    }
    @unlink(ec_verify_dir() . '/' . $d['path']);
    $wpdb->update(ec_table('verify_docs'), ['status' => $action === 'approve' ? 'approved' : 'rejected', 'note' => mb_substr($note, 0, 255) ?: null, 'path' => '', 'reviewed_at' => ec_now()], ['id' => $id]);
    $l = ec_listing_row((int) $d['listing_id']);
    if ($action === 'approve') {
        $wpdb->update(ec_table('listings'), ['verified_at' => ec_now()], ['id' => $d['listing_id']]);
        do_action('ec_listing_changed', (int) $d['listing_id']);
    }
    $a = ec_agent((int) $d['agent_id']);
    if ($a && $l) {
        $msg = $action === 'approve'
            ? "✔ #{$l['id']} ilanınız doğrulandı. İlanda artık “Doğrulanmış ilan” rozeti görünüyor."
            : "❌ #{$l['id']} için gönderdiğiniz belge onaylanmadı." . ($note ? "\nSebep: $note" : '') . "\nYeni belge gönderebilirsiniz.";
        if (!empty($a['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
            ec_tg_send($a['telegram_chat_id'], $msg);
        } else {
            ec_notify_member($a, $action === 'approve' ? 'İlanınız doğrulandı' : 'Doğrulama belgesi onaylanmadı', $msg);
        }
    }
    return true;
}

/* ---- Yönetici ekranı: Emlak → Doğrulama ---- */

if (is_admin()) {
    add_action('admin_menu', function () {
        global $wpdb;
        $n = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('verify_docs') . " WHERE status = 'pending'");
        add_submenu_page('ec-listings', 'Doğrulama', 'Doğrulama' . ($n ? ' <span class="awaiting-mod">' . $n . '</span>' : ''), 'manage_options', 'ec-verify', 'ec_admin_verify');
    }, 22);
}

add_action('admin_post_ec_verify', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkisiz');
    }
    check_admin_referer('ec_verify');
    ec_verify_decide((int) ($_POST['id'] ?? 0), ($_POST['do'] ?? '') === 'approve' ? 'approve' : 'reject', sanitize_text_field(wp_unslash($_POST['note'] ?? '')));
    wp_safe_redirect(admin_url('admin.php?page=ec-verify&ok=1'));
    exit;
});

/* Belgeyi yalnız yönetici görüntüler */
add_action('admin_post_ec_verify_file', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkisiz');
    }
    $d = ec_verify_row((int) ($_GET['id'] ?? 0));
    $p = $d && $d['path'] ? ec_verify_dir() . '/' . $d['path'] : '';
    if (!$p || !is_file($p)) {
        wp_die('Belge yok (karar verildiyse silinmiştir).');
    }
    nocache_headers();
    header('Content-Type: ' . $d['mime']);
    header('Content-Disposition: inline; filename="belge-' . (int) $d['id'] . '"');
    header('X-Robots-Tag: noindex');
    readfile($p);
    exit;
});

function ec_admin_verify(): void
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('verify_docs') . " WHERE status = 'pending' ORDER BY id", ARRAY_A);
    echo '<div class="wrap"><h1>İlan doğrulama</h1><p>Belgedeki malik / ada-parsel bilgisinin ilanla uyuştuğunu kontrol edip onaylayın. Karar verilince belge dosyası silinir.</p>';
    if (!empty($_GET['ok'])) {
        echo '<div class="notice notice-success"><p>Kaydedildi.</p></div>';
    }
    if (!$rows) {
        echo '<p><b>Bekleyen belge yok.</b></p></div>';
        return;
    }
    echo '<table class="widefat striped"><thead><tr><th>#</th><th>İlan</th><th>Gönderen</th><th>Belge</th><th>Karar</th></tr></thead><tbody>';
    foreach ($rows as $d) {
        $l = ec_listing_row((int) $d['listing_id']);
        $it = $l ? ec_listings_present([$l], 'tr')[0] : null;
        $a = ec_agent((int) $d['agent_id']);
        echo '<tr><td>' . (int) $d['id'] . '</td><td>' . ($it ? '<a href="' . esc_url($it['url']) . '" target="_blank">#' . (int) $l['id'] . ' ' . esc_html($it['title']) . '</a>' : '-') . '</td><td>' . esc_html($a['name'] ?? '-') . '</td>';
        echo '<td><a class="button" target="_blank" href="' . esc_url(admin_url('admin-post.php?action=ec_verify_file&id=' . (int) $d['id'])) . '">Belgeyi aç</a><br>' . esc_html(ec_verify_kinds()[$d['kind']] ?? $d['kind']) . '</td><td>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('ec_verify');
        echo '<input type="hidden" name="action" value="ec_verify"><input type="hidden" name="id" value="' . (int) $d['id'] . '">';
        echo '<input name="note" placeholder="Ret sebebi (isteğe bağlı)" style="width:220px"> <button class="button button-primary" name="do" value="approve">Onayla</button> <button class="button" name="do" value="reject">Reddet</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}

/* ---- REST: panelden belge yükleme ---- */

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/verify', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            $file = ec_rest_file($r, 'doc');
            if (!$file) {
                return new WP_Error('ec_no_file', 'Belge seçin', ['status' => 400]);
            }
            $row = ec_listing_row((int) $r['id']);
            $res = ec_verify_submit((int) $r['id'], (int) $row['agent_id'], $file, (string) $r->get_param('kind'));
            return is_wp_error($res) ? $res : ['ok' => true, 'message' => 'Belge alındı. İnceleme sonucunu size bildireceğiz.'];
        },
    ]);
});
