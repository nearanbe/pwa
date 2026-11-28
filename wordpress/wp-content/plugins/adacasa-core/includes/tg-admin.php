<?php
/**
 * Telegram yönetici komutları (yalnızca ec_tg_settings()['admin_chat'] sohbeti).
 *
 *  /ilan 123      İlan kartı: ✏️ Düzenle · ▶️ Yayınla · ⏸ Yayından kaldır · 📝 Taslağa al · 🗑 Tamamen sil · 👤 Üye
 *  /son           Sitedeki son 10 ilan (tüm üyeler)
 *  /uye 12 | ad   Üye kartı: ✅ Aktif et · ⛔ Pasife al · 📋 İlanları
 *  /uyeler        Son 15 üye
 *
 * Yönetici her ilanı düzenleyebilir (ec_tg_can_edit). Üye pasife alınınca yayındaki ilanları yayından kalkar,
 * hangileri olduğu saklanır; üye yeniden aktif edilince aynı ilanlar geri yayına girer.
 * Tamamen silme geri alınamaz: iki kez onay ister.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** İlanı ve dosyalarını kalıcı olarak siler (geri alınamaz) */
function ec_listing_hard_delete(int $id): bool
{
    global $wpdb;
    if (!ec_listing_row($id)) {
        return false;
    }
    foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('photos') . ' WHERE listing_id = %d', $id)) as $pid) {
        ec_photo_delete($id, (int) $pid);
    }
    if (function_exists('ec_pano_delete')) {
        foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('panos') . ' WHERE listing_id = %d', $id)) as $pid) {
            ec_pano_delete($id, (int) $pid);
        }
    }
    if (function_exists('ec_video_cleanup')) {
        ec_video_cleanup($id);
    }
    @rmdir(ec_photo_base()['dir'] . '/' . $id);
    foreach (['listing_i18n', 'price_history', 'alerts', 'verify_docs'] as $t) {
        $tbl = ec_table($t);
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tbl)) === $tbl) {
            $wpdb->delete($tbl, ['listing_id' => $id]);
        }
    }
    $wpdb->delete(ec_table('listings'), ['id' => $id]);
    do_action('ec_listing_deleted', $id);
    return true;
}

/** Üyeyi aktif / pasif yapar. Pasifte yayındaki ilanları kaldırır, aktifte aynı ilanları geri yayına alır. @return int etkilenen ilan */
function ec_agent_set_active(int $agent_id, bool $active): int
{
    global $wpdb;
    $a = ec_agent($agent_id);
    if (!$a) {
        return 0;
    }
    $L = ec_table('listings');
    $key = 'ec_susp_listings_' . $agent_id;
    $n = 0;
    if (!$active) {
        $ids = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT id FROM $L WHERE agent_id = %d AND status = 'published'", $agent_id)));
        if ($ids) {
            $wpdb->query($wpdb->prepare("UPDATE $L SET status = 'passive', updated_at = %s WHERE agent_id = %d AND status = 'published'", ec_now(), $agent_id));
            update_option($key, array_values(array_unique(array_merge((array) get_option($key, []), $ids))), false);
            foreach ($ids as $id) {
                do_action('ec_listing_changed', $id);
            }
        }
        $wpdb->update(ec_table('agents'), ['status' => 'suspended', 'updated_at' => ec_now()], ['id' => $agent_id]);
        return count($ids);
    }
    $wpdb->update(ec_table('agents'), ['status' => 'active', 'updated_at' => ec_now()], ['id' => $agent_id]);
    foreach ((array) get_option($key, []) as $id) {
        $row = ec_listing_row((int) $id);
        if ($row && (int) $row['agent_id'] === $agent_id && $row['status'] === 'passive') {
            $wpdb->update($L, ['status' => 'published', 'updated_at' => ec_now()], ['id' => (int) $id]);
            do_action('ec_listing_changed', (int) $id);
            $n++;
        }
    }
    delete_option($key);
    return $n;
}

function ec_tga_status_name(string $s): string
{
    return ['draft' => '📝 Taslak', 'pending' => '🕓 Onay bekliyor', 'published' => '✅ Yayında', 'rejected' => '❌ Reddedildi', 'sold' => '🏷 Satıldı/kiralandı', 'passive' => '⏸ Yayında değil',
        'active' => '✅ Aktif', 'suspended' => '⛔ Pasif'][$s] ?? $s;
}

/** Yönetici ilan kartı */
function ec_tga_listing_card(string $chat, int $id): void
{
    $row = ec_listing_row($id);
    if (!$row) {
        ec_tg_send($chat, "#$id numaralı ilan bulunamadı.");
        return;
    }
    $it = ec_listings_present([$row], 'tr', true)[0];
    $a = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    $text = sprintf("🛠 <b>İlan #%d</b> · %s\n%s\n%s · %s · %s %s\nÜye: %s%s\n%s",
        $id, ec_tga_status_name($row['status']), ec_tg_esc($it['title']), ec_tg_esc($it['subtype_name'] ?? ''), ec_tg_esc($it['city_name'] ?? ''),
        number_format((float) $row['price'], 0, ',', '.'), ec_tg_esc((string) $row['currency']),
        ec_tg_esc($a['name'] ?? '-'), $a ? ' (#' . (int) $a['id'] . ', ' . ec_tga_status_name($a['status']) . ')' : '', $it['url']);
    $kb = [[['text' => '✏️ Düzenle', 'callback_data' => 'el:' . $id]]];
    $r = [];
    if ($row['status'] !== 'published') {
        $r[] = ['text' => '▶️ Yayınla', 'callback_data' => 'ax:pub:' . $id];
    }
    if ($row['status'] !== 'passive') {
        $r[] = ['text' => '⏸ Yayından kaldır', 'callback_data' => 'ax:pas:' . $id];
    }
    if ($row['status'] !== 'draft') {
        $r[] = ['text' => '📝 Taslağa al', 'callback_data' => 'ax:drf:' . $id];
    }
    $kb[] = $r;
    $kb[] = array_values(array_filter([['text' => '🗑 Tamamen sil', 'callback_data' => 'ax:del:' . $id], $a ? ['text' => '👤 Üye #' . (int) $a['id'], 'callback_data' => 'ax:u:' . (int) $a['id']] : null]));
    ec_tg_send($chat, $text, $kb);
}

/** Yönetici üye kartı */
function ec_tga_agent_card(string $chat, int $id): void
{
    global $wpdb;
    $a = ec_agent($id);
    if (!$a) {
        ec_tg_send($chat, "#$id numaralı üye bulunamadı.");
        return;
    }
    $L = ec_table('listings');
    $cnt = $wpdb->get_results($wpdb->prepare("SELECT status, COUNT(*) n FROM $L WHERE agent_id = %d GROUP BY status", $id), ARRAY_A);
    $c = [];
    foreach ($cnt as $r) {
        $c[] = ec_tga_status_name($r['status']) . ' ' . (int) $r['n'];
    }
    $kind = ec_member_kinds()[$a['kind']]['name']['tr'] ?? $a['kind'];
    $text = sprintf("👤 <b>Üye #%d</b> · %s\n%s (%s)\n✉ %s · 📞 %s\nTelegram: %s\nİlanlar: %s",
        $id, ec_tga_status_name($a['status']), ec_tg_esc($a['name']), ec_tg_esc($kind), ec_tg_esc($a['email']), ec_tg_esc((string) ($a['phone'] ?: '-')),
        $a['telegram_chat_id'] ? 'bağlı' . ($a['telegram_username'] ? ' (@' . ec_tg_esc($a['telegram_username']) . ')' : '') : 'bağlı değil',
        $c ? implode(' · ', $c) : 'yok');
    $kb = [[$a['status'] === 'active'
        ? ['text' => '⛔ Pasife al', 'callback_data' => 'ax:us:' . $id]
        : ['text' => '✅ Aktif et', 'callback_data' => 'ax:ua:' . $id], ['text' => '📋 İlanları', 'callback_data' => 'ax:ul:' . $id]]];
    ec_tg_send($chat, $text, $kb);
}

/** İlan listesi: her ilan için yönetici kartını açan düğme */
function ec_tga_listing_list(string $chat, array $rows, string $title): void
{
    if (!$rows) {
        ec_tg_send($chat, $title . "\nİlan yok.");
        return;
    }
    $out = ['<b>' . $title . '</b>'];
    $kb = [];
    foreach (ec_listings_present($rows, 'tr', true) as $i => $it) {
        $st = mb_substr(ec_tga_status_name($rows[$i]['status']), 0, 2);
        $out[] = $st . ' #' . $it['id'] . ' ' . ec_tg_esc(mb_substr($it['title'], 0, 55));
        $kb[] = [['text' => $st . ' #' . $it['id'] . ' ' . mb_substr($it['title'], 0, 38), 'callback_data' => 'ax:i:' . $it['id']]];
    }
    ec_tg_send($chat, implode("\n", $out), $kb);
}

/** Yönetici komutları. @return bool işlendi mi */
function ec_tga_command(string $chat, string $text): bool
{
    global $wpdb;
    if (!ec_tg_is_admin($chat) || $text === '' || $text[0] !== '/') {
        return false;
    }
    $cmd = strtolower(strtok($text, ' @') ?: '');
    $arg = trim((string) substr($text, strlen($cmd)));
    $L = ec_table('listings');
    $A = ec_table('agents');
    switch ($cmd) {
        case '/ilan':
            if (preg_match('/#?(\d+)/', $arg, $m)) {
                ec_tga_listing_card($chat, (int) $m[1]);
            } else {
                ec_tg_send($chat, 'Kullanım: <i>/ilan 123</i>');
            }
            return true;
        case '/son':
            $rows = $wpdb->get_results("SELECT * FROM $L ORDER BY id DESC LIMIT 10", ARRAY_A);
            ec_tga_listing_list($chat, $rows, 'Son 10 ilan (tüm üyeler)');
            return true;
        case '/uye':
            if (preg_match('/^#?(\d+)$/', $arg, $m)) {
                ec_tga_agent_card($chat, (int) $m[1]);
                return true;
            }
            if (mb_strlen($arg) < 2) {
                ec_tg_send($chat, 'Kullanım: <i>/uye 12</i> ya da <i>/uye ad, e-posta veya telefon</i>');
                return true;
            }
            $like = '%' . $wpdb->esc_like($arg) . '%';
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id, name, status FROM $A WHERE name LIKE %s OR email LIKE %s OR phone LIKE %s OR telegram_username LIKE %s ORDER BY id DESC LIMIT 10", $like, $like, $like, $like), ARRAY_A);
            if (count($rows) === 1) {
                ec_tga_agent_card($chat, (int) $rows[0]['id']);
                return true;
            }
            // break yok: birden fazla sonuç listelenir
            $title = $rows ? 'Bulunan üyeler' : 'Üye bulunamadı.';
            // no break
        case '/uyeler':
            if ($cmd === '/uyeler') {
                $rows = $wpdb->get_results("SELECT id, name, status FROM $A ORDER BY id DESC LIMIT 15", ARRAY_A);
                $title = 'Son 15 üye';
            }
            $kb = [];
            foreach ($rows as $r) {
                $kb[] = [['text' => mb_substr(ec_tga_status_name($r['status']), 0, 2) . ' #' . $r['id'] . ' ' . mb_substr($r['name'], 0, 40), 'callback_data' => 'ax:u:' . $r['id']]];
            }
            ec_tg_send($chat, '<b>' . $title . '</b>', $kb);
            return true;
        case '/yonetim':
            ec_tg_send($chat, "🛠 <b>Yönetici komutları</b>\n/ilan 123 – ilan kartı (düzenle, yayınla, kaldır, taslak, sil)\n/son – sitedeki son 10 ilan\n/uye 12 ya da /uye ad – üye kartı (aktif/pasif, ilanları)\n/uyeler – son 15 üye\n/bekleyen – onay bekleyen ilanlar");
            return true;
    }
    return false;
}

/** Yönetici düğmeleri: ax:<işlem>:<no> */
function ec_tga_callback(string $chat, string $arg, callable $ack, array $q): void
{
    if (!ec_tg_is_admin($chat)) {
        $ack('Yetkiniz yok');
        return;
    }
    global $wpdb;
    [$op, $id] = array_pad(explode(':', $arg, 2), 2, '0');
    $id = (int) $id;
    $clear = function () use ($q, $chat) {
        if (!empty($q['message']['message_id'])) {
            ec_tg_api('editMessageReplyMarkup', ['chat_id' => $chat, 'message_id' => $q['message']['message_id'], 'reply_markup' => ['inline_keyboard' => []]], false);
        }
    };
    switch ($op) {
        case 'i':
            $ack();
            ec_tga_listing_card($chat, $id);
            return;
        case 'u':
            $ack();
            ec_tga_agent_card($chat, $id);
            return;
        case 'pub':
        case 'pas':
        case 'drf':
            $to = ['pub' => 'published', 'pas' => 'passive', 'drf' => 'draft'][$op];
            $r = ec_listing_update($id, ['status' => $to]);
            if (is_wp_error($r)) {
                $ack('Olmadı');
                $f = array_keys((array) ($r->get_error_data()['fields'] ?? []));
                ec_tg_send($chat, '⚠️ #' . $id . ': ' . ec_tg_esc($f ? 'eksik bilgi: ' . implode(', ', array_map('ec_tg_field_name', $f)) : $r->get_error_message()));
                return;
            }
            $ack(ec_tga_status_name($to));
            $clear();
            ec_tga_listing_card($chat, $id);
            return;
        case 'del':
            $ack();
            ec_tg_send($chat, "⚠️ <b>#$id kalıcı olarak silinsin mi?</b>\nİlan, fotoğrafları ve fiyat geçmişi tamamen silinir. <b>Geri alınamaz.</b>\n(Yalnızca gizlemek için “⏸ Yayından kaldır” kullanın.)",
                [[['text' => '🗑 Evet, kalıcı olarak sil', 'callback_data' => 'ax:delok:' . $id], ['text' => '↩️ Vazgeç', 'callback_data' => 'ax:no:' . $id]]]);
            return;
        case 'delok':
            $clear();
            $ok = ec_listing_hard_delete($id);
            $ack($ok ? 'Silindi' : 'Bulunamadı');
            ec_tg_send($chat, $ok ? "🗑 #$id kalıcı olarak silindi." : "#$id bulunamadı (daha önce silinmiş olabilir).");
            return;
        case 'us':
            $ack();
            $a = ec_agent($id);
            $n = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ec_table('listings') . " WHERE agent_id = %d AND status = 'published'", $id));
            ec_tg_send($chat, '⛔ <b>' . ec_tg_esc($a['name'] ?? '#' . $id) . "</b> pasife alınsın mı?\nSiteye ve Telegram'a giriş yapamaz; yayındaki <b>$n ilanı</b> yayından kalkar. Yeniden aktif edince aynı ilanlar geri yayına girer.",
                [[['text' => '⛔ Evet, pasife al', 'callback_data' => 'ax:usok:' . $id], ['text' => '↩️ Vazgeç', 'callback_data' => 'ax:no:' . $id]]]);
            return;
        case 'usok':
            $clear();
            $n = ec_agent_set_active($id, false);
            $ack('Pasife alındı');
            ec_tg_send($chat, "⛔ Üye #$id pasife alındı; $n ilan yayından kalktı.");
            ec_tga_agent_card($chat, $id);
            return;
        case 'ua':
            $clear();
            $n = ec_agent_set_active($id, true);
            $ack('Aktif edildi');
            ec_tg_send($chat, "✅ Üye #$id aktif edildi" . ($n ? "; $n ilan yeniden yayında." : '.'));
            ec_tga_agent_card($chat, $id);
            return;
        case 'ul':
            $ack();
            $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('listings') . ' WHERE agent_id = %d ORDER BY id DESC LIMIT 15', $id), ARRAY_A);
            ec_tga_listing_list($chat, $rows, "Üye #$id ilanları (son 15)");
            return;
        case 'no':
            $clear();
            $ack('Vazgeçildi');
            return;
    }
    $ack();
}
