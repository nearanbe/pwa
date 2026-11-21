<?php
/**
 * Telegram botu (tek merkezi bot). Sürüm 0.4.
 *
 * Akış:
 *  1. Üye Panelim → Telegram → "Telegram'ı bağla" der; 10 dakika geçerli kodla t.me/<bot>?start=KOD açılır.
 *     Bot kodu doğrular ve üyenin telegram_chat_id alanını doldurur.
 *  2. Üye fotoğrafları ve ilan metnini arka arkaya gönderir. Hepsi açık "taslak"ta (ec_tg_drafts) toplanır.
 *     Bot tek bir durum mesajını günceller: "4 fotoğraf, 1 metin alındı · [İlanı hazırla]".
 *  3. "İlanı hazırla" (ya da 45 sn sessizlik): Claude metinden alanları çıkarır, 3 dile çevirir,
 *     sitede taslak ilan açılır, fotoğraflar eklenir ve önizleme gönderilir: [✅ Yayınla] [✏️ Düzelt] [🗑 İptal].
 *  4. Düzelt: üye düzeltmeyi yazar ("fiyat 165.000 sterlin, tapu eşdeğer"), ilan güncellenir, yeni önizleme gelir.
 *  5. Yayınla: ofis ve inşaat firması → anında yayında. Bireysel danışman / mülk sahibi → onaya düşer ve
 *     yöneticiye Telegram'dan [Onayla] [Reddet] düğmeleriyle bildirim gider.
 *
 * Webhook adresi: /api/v1/telegram/webhook  (Telegram "X-Telegram-Bot-Api-Secret-Token" başlığı ile doğrulanır)
 * Komutlar: /start, /yeni (taslağı sıfırla), /iptal, /ilanlarim, /yardim, (yönetici) /bekleyen
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_tg_settings(): array
{
    return array_merge(['bot' => '', 'token' => '', 'admin_chat' => '', 'secret' => ''], (array) get_option('ec_telegram', []));
}

function ec_tg_ready(): bool
{
    $s = ec_tg_settings();
    return $s['token'] !== '';
}

/** Telegram Bot API çağrısı. @return array|WP_Error result alanı */
function ec_tg_api(string $method, array $params = [], bool $blocking = true)
{
    $s = ec_tg_settings();
    if (!$s['token']) {
        return new WP_Error('ec_tg_token', 'Bot anahtarı girilmemiş');
    }
    $res = wp_remote_post('https://api.telegram.org/bot' . $s['token'] . '/' . $method, [
        'timeout' => 15,
        'blocking' => $blocking,
        'headers' => ['content-type' => 'application/json'],
        'body' => wp_json_encode($params),
    ]);
    if (!$blocking) {
        return [];
    }
    if (is_wp_error($res)) {
        return $res;
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    if (empty($j['ok'])) {
        return new WP_Error('ec_tg_api', 'Telegram: ' . ($j['description'] ?? 'HTTP ' . wp_remote_retrieve_response_code($res)));
    }
    return is_array($j['result']) ? $j['result'] : ['value' => $j['result']];
}

function ec_tg_send($chat, string $text, array $buttons = [], array $extra = [])
{
    $p = ['chat_id' => (string) $chat, 'text' => mb_substr($text, 0, 4000), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true] + $extra;
    if ($buttons) {
        $p['reply_markup'] = ['inline_keyboard' => $buttons];
    }
    return ec_tg_api('sendMessage', $p);
}

function ec_tg_esc(string $s): string
{
    return htmlspecialchars($s, ENT_NOQUOTES, 'UTF-8');
}

/* ---------------- Taslak (gelen mesajları toplama) ---------------- */

function ec_tg_draft_open(string $chat, ?int $agent_id): array
{
    global $wpdb;
    $t = ec_table('tg_drafts');
    $d = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE chat_id = %s AND state IN ('collecting','processing','preview','editing') ORDER BY id DESC LIMIT 1", $chat), ARRAY_A);
    if ($d) {
        return $d;
    }
    $wpdb->insert($t, ['chat_id' => $chat, 'agent_id' => $agent_id, 'state' => 'collecting', 'texts' => '[]', 'photos' => '[]', 'data' => '{}', 'created_at' => ec_now(), 'updated_at' => ec_now()]);
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $wpdb->insert_id), ARRAY_A);
}

function ec_tg_draft_get(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('tg_drafts') . ' WHERE id = %d', $id), ARRAY_A);
    return $r ?: null;
}

function ec_tg_draft_save(int $id, array $f): void
{
    global $wpdb;
    foreach (['texts', 'photos', 'data'] as $k) {
        if (isset($f[$k]) && is_array($f[$k])) {
            $f[$k] = wp_json_encode($f[$k], JSON_UNESCAPED_UNICODE);
        }
    }
    $f['updated_at'] = ec_now();
    $wpdb->update(ec_table('tg_drafts'), $f, ['id' => $id]);
}

function ec_tg_j(?string $s): array
{
    $v = $s ? json_decode($s, true) : [];
    return is_array($v) ? $v : [];
}

/** Durum mesajını gönderir ya da günceller */
function ec_tg_status(array $d): void
{
    $texts = ec_tg_j($d['texts']);
    $photos = ec_tg_j($d['photos']);
    $data = ec_tg_j($d['data']);
    $txt = sprintf("📥 <b>%d fotoğraf</b>, <b>%d metin</b> alındı.\n\nHepsini gönderdiyseniz <b>İlanı hazırla</b>'ya basın. Bir şey eklemek isterseniz göndermeye devam edin.", count($photos), count($texts));
    if (!$texts) {
        $txt .= "\n\n✍️ İlanın metnini de yazın: tür, şehir/bölge, fiyat, m², oda, tapu türü, özellikler.";
    }
    $kb = ['inline_keyboard' => [[['text' => '🪄 İlanı hazırla', 'callback_data' => 'prep:' . $d['id']], ['text' => '🗑 Vazgeç', 'callback_data' => 'cancel:' . $d['id']]]]];
    if (!empty($data['status_msg'])) {
        $r = ec_tg_api('editMessageText', ['chat_id' => $d['chat_id'], 'message_id' => (int) $data['status_msg'], 'text' => $txt, 'parse_mode' => 'HTML', 'reply_markup' => $kb]);
        if (!is_wp_error($r) || str_contains($r->get_error_message(), 'not modified')) {
            return;
        }
    }
    $r = ec_tg_api('sendMessage', ['chat_id' => $d['chat_id'], 'text' => $txt, 'parse_mode' => 'HTML', 'reply_markup' => $kb]);
    if (!is_wp_error($r) && !empty($r['message_id'])) {
        $data['status_msg'] = (int) $r['message_id'];
        ec_tg_draft_save((int) $d['id'], ['data' => $data]);
    }
}

/* ---------------- Gelen güncellemeyi işleme ---------------- */

function ec_tg_agent_by_chat(string $chat): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('agents') . ' WHERE telegram_chat_id = %s', $chat), ARRAY_A);
    return $r ?: null;
}

function ec_tg_is_admin(string $chat): bool
{
    $a = ec_tg_settings()['admin_chat'];
    return $a !== '' && $a === $chat;
}

/** Ağır işler (yapay zekâ, fotoğraf indirme) cevaptan sonra yapılsın: Telegram beklemesin */
function ec_tg_defer(callable $job): void
{
    $GLOBALS['ec_tg_jobs'][] = $job;
}

add_action('shutdown', function () {
    if (empty($GLOBALS['ec_tg_jobs'])) {
        return;
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    ignore_user_abort(true);
    @set_time_limit(300);
    foreach ($GLOBALS['ec_tg_jobs'] as $job) {
        $job();
    }
});

function ec_tg_handle_update(array $u): void
{
    if (!empty($u['callback_query'])) {
        ec_tg_handle_callback($u['callback_query']);
        return;
    }
    $m = $u['message'] ?? $u['edited_message'] ?? null;
    if (!$m || empty($m['chat']['id']) || ($m['chat']['type'] ?? '') !== 'private') {
        return;
    }
    $chat = (string) $m['chat']['id'];
    $text = trim((string) ($m['text'] ?? $m['caption'] ?? ''));
    $agent = ec_tg_agent_by_chat($chat);

    // Eşleştirme
    if (preg_match('~^/start(?:\s+([A-Za-z0-9]{6,20}))?~', $text, $mm)) {
        if (!empty($mm[1])) {
            ec_tg_link($chat, strtoupper($mm[1]), $m['from'] ?? []);
            return;
        }
        ec_tg_send($chat, $agent ? ec_tg_help($agent) : "Merhaba! 👋\nBu bot, " . get_bloginfo('name') . " üyelerinin Telegram'dan ilan vermesi içindir.\n\nHesabınızı bağlamak için siteye giriş yapın: <b>Panelim → Telegram → Telegram'ı bağla</b>.\n" . ec_url_section('panel', 'tr'));
        return;
    }

    // Yönetici: red sebebi bekleniyor mu?
    if (ec_tg_is_admin($chat) && ($lid = get_transient('ec_tg_reject_' . $chat))) {
        delete_transient('ec_tg_reject_' . $chat);
        $reason = $text === '/atla' ? '' : $text;
        ec_review_listing((int) $lid, 'reject', $reason);
        ec_tg_send($chat, "❌ #$lid reddedildi." . ($reason ? "\nSebep: " . ec_tg_esc($reason) : ''));
        return;
    }
    if (ec_tg_is_admin($chat) && $text === '/bekleyen') {
        ec_tg_admin_pending($chat);
        return;
    }

    if (!$agent) {
        ec_tg_send($chat, "Bu sohbet bir üyeliğe bağlı değil.\nSiteye giriş yapıp <b>Panelim → Telegram → Telegram'ı bağla</b> düğmesine basın.\n" . ec_url_section('panel', 'tr'));
        return;
    }
    if ($agent['status'] === 'suspended') {
        ec_tg_send($chat, 'Hesabınız askıya alınmış. Lütfen site yönetimiyle iletişime geçin.');
        return;
    }

    // Chrome eklentisi SMS kodu bekliyor mu? (101evler / hangiev doğrulama kodu)
    if (($site = get_transient('ec_sms_wait_' . $agent['id'])) && preg_match('/^\D*(\d{4,8})\D*$/', $text, $sm)) {
        set_transient('ec_sms_code_' . $agent['id'], ['code' => $sm[1], 'site' => $site, 'at' => time()], 5 * MINUTE_IN_SECONDS);
        delete_transient('ec_sms_wait_' . $agent['id']);
        ec_tg_send($chat, '✅ Kod eklentiye iletildi (' . ec_tg_esc((string) $site) . ').');
        return;
    }

    switch (strtolower(strtok($text, ' @') ?: '')) {
        case '/yardim':
        case '/help':
            ec_tg_send($chat, ec_tg_help($agent));
            return;
        case '/yeni':
        case '/iptal':
        case '/cancel':
            ec_tg_cancel_open($chat);
            ec_tg_send($chat, $text === '/yeni' ? '🆕 Yeni ilan için fotoğrafları ve metni gönderin.' : '🗑 Açık taslak silindi.');
            return;
        case '/ilanlarim':
            ec_tg_my_listings($chat, $agent);
            return;
    }

    $d = ec_tg_draft_open($chat, (int) $agent['id']);
    if ($d['state'] === 'processing') {
        ec_tg_send($chat, '⏳ İlanınız hazırlanıyor, birkaç saniye içinde önizleme gelecek.');
        return;
    }
    if (in_array($d['state'], ['preview', 'editing'], true)) {
        if (!empty($m['photo']) || !empty($m['document'])) {
            ec_tg_collect_photo($d, $m);
            $d = ec_tg_draft_get((int) $d['id']);
            ec_tg_defer(function () use ($d, $chat) {
                ec_tg_sync_photos($d);
                ec_tg_send($chat, '📷 Fotoğraf ilana eklendi.');
            });
            return;
        }
        if ($text !== '') {
            ec_tg_send($chat, '✏️ Düzeltme uygulanıyor…');
            ec_tg_defer(fn() => ec_tg_correct((int) $d['id'], $text));
        }
        return;
    }

    // Toplama
    $texts = ec_tg_j($d['texts']);
    if ($text !== '' && $text[0] !== '/') {
        $texts[] = $text;
    }
    ec_tg_draft_save((int) $d['id'], ['texts' => $texts]);
    if (!empty($m['photo']) || !empty($m['document'])) {
        ec_tg_collect_photo($d, $m);
    }
    $d = ec_tg_draft_get((int) $d['id']);
    // Albümler (media_group) tek seferde birkaç güncelleme olarak gelir: durum mesajını bir kez güncelle
    $gk = 'ec_tg_grp_' . ($m['media_group_id'] ?? 'x' . $m['message_id']);
    if (empty($m['media_group_id']) || !get_transient($gk)) {
        set_transient($gk, 1, 20);
        ec_tg_status($d);
    }
    // 45 sn yeni mesaj gelmezse kendiliğinden hazırla (metin ve en az 1 fotoğraf varsa)
    wp_clear_scheduled_hook('ec_tg_auto', [(int) $d['id']]);
    wp_schedule_single_event(time() + 45, 'ec_tg_auto', [(int) $d['id']]);
}

function ec_tg_help(array $agent): string
{
    return "Merhaba <b>" . ec_tg_esc($agent['name']) . "</b> 👋\n\n"
        . "📸 İlan vermek için fotoğrafları ve ilan metnini gönderin (sırası önemli değil).\n"
        . "🪄 Sonra <b>İlanı hazırla</b>'ya basın: yapay zekâ bilgileri çıkarır, Türkçe, İngilizce ve Rusçaya çevirir.\n"
        . "✅ Önizlemeyi kontrol edip <b>Yayınla</b> ya da <b>Düzelt</b> deyin.\n\n"
        . "Komutlar:\n/yeni – yeni ilana başla\n/iptal – açık taslağı sil\n/ilanlarim – son ilanlarım\n/yardim – bu mesaj";
}

function ec_tg_link(string $chat, string $code, array $from): void
{
    global $wpdb;
    $t = ec_table('agents');
    $a = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE telegram_link_code = %s AND telegram_link_expires > UTC_TIMESTAMP()", $code), ARRAY_A);
    if (!$a) {
        ec_tg_send($chat, '⛔ Kod geçersiz ya da süresi dolmuş (10 dakika). Panelim → Telegram ekranından yeni kod alın.');
        return;
    }
    // Bu sohbet başka üyeye bağlıysa çöz
    $wpdb->query($wpdb->prepare("UPDATE $t SET telegram_chat_id = NULL WHERE telegram_chat_id = %s AND id <> %d", $chat, $a['id']));
    $wpdb->update($t, [
        'telegram_chat_id' => $chat,
        'telegram_username' => isset($from['username']) ? mb_substr((string) $from['username'], 0, 64) : null,
        'telegram_link_code' => null,
        'telegram_link_expires' => null,
        'telegram_opt_in' => 1,
        'updated_at' => ec_now(),
    ], ['id' => $a['id']]);
    ec_tg_send($chat, "✅ Hesabınız bağlandı: <b>" . ec_tg_esc($a['name']) . "</b>\n\n" . ec_tg_help(ec_agent((int) $a['id'])));
}

function ec_tg_cancel_open(string $chat): void
{
    global $wpdb;
    $t = ec_table('tg_drafts');
    foreach ($wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE chat_id = %s AND state IN ('collecting','processing','preview','editing')", $chat), ARRAY_A) as $d) {
        ec_tg_cancel($d);
    }
}

function ec_tg_cancel(array $d): void
{
    global $wpdb;
    // Yayına gönderilmemiş taslak ilanı sil
    if ($d['listing_id'] && ($row = ec_listing_row((int) $d['listing_id'])) && $row['status'] === 'draft' && $row['source'] === 'telegram') {
        foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('photos') . ' WHERE listing_id = %d', $row['id'])) as $pid) {
            ec_photo_delete((int) $row['id'], (int) $pid);
        }
        $wpdb->delete(ec_table('listing_i18n'), ['listing_id' => $row['id']]);
        $wpdb->delete(ec_table('listings'), ['id' => $row['id']]);
    }
    ec_tg_draft_save((int) $d['id'], ['state' => 'cancelled']);
    wp_clear_scheduled_hook('ec_tg_auto', [(int) $d['id']]);
}

function ec_tg_collect_photo(array $d, array $m): void
{
    $photos = ec_tg_j($d['photos']);
    $fid = null;
    if (!empty($m['photo'])) {
        $big = end($m['photo']); // en büyük boyut en sonda
        $fid = $big['file_id'] ?? null;
    } elseif (!empty($m['document']) && str_starts_with((string) ($m['document']['mime_type'] ?? ''), 'image/')) {
        $fid = $m['document']['file_id'];
    }
    if ($fid && count($photos) < EC_PHOTO_MAX) {
        $photos[] = ['file_id' => $fid, 'done' => false];
        ec_tg_draft_save((int) $d['id'], ['photos' => $photos]);
    }
}

/** Telegram'daki fotoğrafları indirip ilana ekler (eklenmemiş olanları) */
function ec_tg_sync_photos(array $d): void
{
    if (!$d['listing_id']) {
        return;
    }
    $s = ec_tg_settings();
    $photos = ec_tg_j($d['photos']);
    require_once ABSPATH . 'wp-admin/includes/file.php';
    foreach ($photos as $i => $p) {
        if (!empty($p['done'])) {
            continue;
        }
        $f = ec_tg_api('getFile', ['file_id' => $p['file_id']]);
        if (is_wp_error($f) || empty($f['file_path'])) {
            continue;
        }
        $tmp = download_url('https://api.telegram.org/file/bot' . $s['token'] . '/' . $f['file_path'], 60);
        if (is_wp_error($tmp)) {
            continue;
        }
        $r = ec_photo_add((int) $d['listing_id'], $tmp);
        @unlink($tmp);
        $photos[$i]['done'] = !is_wp_error($r);
    }
    ec_tg_draft_save((int) $d['id'], ['photos' => $photos]);
}

/* ---------------- İlanı hazırlama ve önizleme ---------------- */

add_action('ec_tg_auto', function ($id) {
    $d = ec_tg_draft_get((int) $id);
    if ($d && $d['state'] === 'collecting' && ec_tg_j($d['texts']) && ec_tg_j($d['photos']) && strtotime($d['updated_at'] . ' UTC') <= time() - 40) {
        ec_tg_prepare((int) $id);
    }
});

function ec_tg_prepare(int $id): void
{
    $d = ec_tg_draft_get($id);
    if (!$d || $d['state'] !== 'collecting') {
        return;
    }
    $chat = $d['chat_id'];
    $texts = ec_tg_j($d['texts']);
    if (!$texts) {
        ec_tg_send($chat, '✍️ Önce ilan metnini yazın (tür, şehir, fiyat, m², oda, tapu türü…).');
        return;
    }
    ec_tg_draft_save($id, ['state' => 'processing']);
    $agent = ec_agent((int) $d['agent_id']);
    $ai = ec_ai_extract(implode("\n\n", $texts));
    if (is_wp_error($ai)) {
        ec_tg_draft_save($id, ['state' => 'collecting', 'error' => mb_substr($ai->get_error_message(), 0, 255)]);
        ec_tg_send($chat, '⚠️ ' . ec_tg_esc($ai->get_error_message()) . "\n\nTekrar denemek için <b>İlanı hazırla</b>'ya basın.", [[['text' => '🔁 Tekrar dene', 'callback_data' => 'prep:' . $id]]]);
        return;
    }
    $in = ec_tg_ai_to_listing($ai);
    $in['status'] = 'draft';
    $lid = ec_listing_create($in, (int) $agent['id'], 'telegram');
    if (is_wp_error($lid)) {
        // Zorunlu alanlar (fiyat/şehir/tip) eksik: veriyi sakla, eksikleri sor
        $data = ec_tg_j($d['data']);
        $data['ai'] = $ai;
        ec_tg_draft_save($id, ['state' => 'editing', 'data' => $data]);
        $f = array_keys((array) ($lid->get_error_data()['fields'] ?? []));
        ec_tg_send($chat, "🤔 Bazı zorunlu bilgileri bulamadım: <b>" . ec_tg_esc(implode(', ', array_map('ec_tg_field_name', $f))) . "</b>\nLütfen bunları yazın, ilanı tamamlayayım.");
        return;
    }
    $data = ec_tg_j($d['data']);
    $data['ai'] = $ai;
    ec_tg_draft_save($id, ['state' => 'preview', 'listing_id' => $lid, 'data' => $data, 'error' => null]);
    ec_tg_sync_photos(ec_tg_draft_get($id));
    ec_tg_preview($id);
}

/** Yapay zekâ çıktısından ilan alanları (boş olanlar atlanır) */
function ec_tg_ai_to_listing(array $ai): array
{
    $in = [];
    foreach (['mode', 'ptype', 'subtype', 'city', 'district', 'price', 'currency', 'area_m2', 'rooms', 'baths', 'deed_type', 'furnished', 'vat_paid', 'transformer_paid', 'floor', 'total_floors', 'year_built', 'sea_distance_m', 'payment_plan', 'features', 'i18n'] as $k) {
        if (array_key_exists($k, $ai) && $ai[$k] !== null && $ai[$k] !== []) {
            $in[$k] = $ai[$k];
        }
    }
    return $in;
}

function ec_tg_field_name(string $f): string
{
    return ['mode' => 'ilan türü (satılık/kiralık)', 'ptype' => 'emlak tipi', 'city' => 'şehir', 'price' => 'fiyat', 'i18n' => 'başlık', 'deed_type' => 'tapu türü'][$f] ?? $f;
}

function ec_tg_preview(int $id): void
{
    $d = ec_tg_draft_get($id);
    $row = $d && $d['listing_id'] ? ec_listing_row((int) $d['listing_id']) : null;
    if (!$row) {
        return;
    }
    $it = ec_listings_present([$row], 'tr', true)[0];
    $ai = ec_tg_j($d['data'])['ai'] ?? [];
    $sym = ['GBP' => '£', 'EUR' => '€', 'USD' => '$', 'TRY' => '₺'][$it['currency']] ?? '';
    $lines = [
        '📝 <b>Önizleme</b> · #' . $it['id'],
        '',
        '<b>' . ec_tg_esc($it['title']) . '</b>',
        ec_tg_esc(implode(' · ', array_filter([['sale' => 'Satılık', 'rent' => 'Kiralık', 'daily' => 'Günlük kiralık'][$it['mode']], $it['subtype_name'], trim(($it['district'] ? $it['district'] . ', ' : '') . $it['city_name'])]))),
        '💰 ' . $sym . number_format($it['price'], 0, ',', '.') . ($it['mode'] === 'rent' ? ' / ay' : ($it['mode'] === 'daily' ? ' / gece' : '')),
        ec_tg_esc(implode(' · ', array_filter([$it['area_m2'] ? $it['area_m2'] . ' m²' : null, $it['rooms'], $it['baths'] ? $it['baths'] . ' banyo' : null]))),
        '📜 Tapu: ' . ec_tg_esc($it['deed_name'] ?: '— (belirtilmemiş)'),
    ];
    if ($it['feature_names']) {
        $lines[] = '✨ ' . ec_tg_esc(implode(', ', array_slice($it['feature_names'], 0, 10)));
    }
    $lines[] = '📷 ' . count($it['photos']) . ' fotoğraf · 🌐 ' . strtoupper(implode(' / ', array_keys($it['translations'])));
    if (!empty($ai['missing'])) {
        $lines[] = '';
        $lines[] = '⚠️ Eksik: ' . ec_tg_esc(implode(', ', $ai['missing']));
    }
    $lines[] = '';
    $lines[] = '<i>' . ec_tg_esc(mb_substr((string) $it['description'], 0, 500)) . (mb_strlen((string) $it['description']) > 500 ? '…' : '') . '</i>';
    $panel = add_query_arg(['bolum' => 'ilan', 'id' => $it['id']], ec_url_section('panel', 'tr'));
    $kb = [
        [['text' => '✅ Yayınla', 'callback_data' => 'pub:' . $id], ['text' => '✏️ Düzelt', 'callback_data' => 'edit:' . $id]],
        [['text' => '🖥 Panelde aç', 'url' => $panel], ['text' => '🗑 İptal', 'callback_data' => 'cancel:' . $id]],
    ];
    $text = implode("\n", $lines);
    $cover = $it['photos'][0]['large'] ?? null;
    $r = null;
    if ($cover && !str_contains($cover, '://localhost') && mb_strlen($text) <= 1000) {
        $r = ec_tg_api('sendPhoto', ['chat_id' => $d['chat_id'], 'photo' => $cover, 'caption' => $text, 'parse_mode' => 'HTML', 'reply_markup' => ['inline_keyboard' => $kb]]);
    }
    if (!$r || is_wp_error($r)) {
        $r = ec_tg_send($d['chat_id'], $text, $kb);
    }
    if (!is_wp_error($r) && !empty($r['message_id'])) {
        ec_tg_draft_save($id, ['preview_msg_id' => (int) $r['message_id']]);
    }
}

function ec_tg_correct(int $id, string $text): void
{
    $d = ec_tg_draft_get($id);
    if (!$d) {
        return;
    }
    $data = ec_tg_j($d['data']);
    $orig = implode("\n\n", ec_tg_j($d['texts']));
    $ai = ec_ai_extract($orig, $data['ai'] ?? null, $text);
    if (is_wp_error($ai)) {
        ec_tg_send($d['chat_id'], '⚠️ ' . ec_tg_esc($ai->get_error_message()));
        return;
    }
    $texts = ec_tg_j($d['texts']);
    $texts[] = 'Düzeltme: ' . $text;
    $data['ai'] = $ai;
    $in = ec_tg_ai_to_listing($ai);
    if ($d['listing_id']) {
        $r = ec_listing_update((int) $d['listing_id'], $in);
        $lid = (int) $d['listing_id'];
    } else {
        $in['status'] = 'draft';
        $r = $lid = ec_listing_create($in, (int) $d['agent_id'], 'telegram');
    }
    if (is_wp_error($r)) {
        ec_tg_draft_save($id, ['state' => 'editing', 'data' => $data, 'texts' => $texts]);
        $f = array_keys((array) ($r->get_error_data()['fields'] ?? []));
        ec_tg_send($d['chat_id'], '🤔 Hâlâ eksik: <b>' . ec_tg_esc(implode(', ', array_map('ec_tg_field_name', $f))) . '</b>');
        return;
    }
    ec_tg_draft_save($id, ['state' => 'preview', 'listing_id' => $lid, 'data' => $data, 'texts' => $texts]);
    ec_tg_sync_photos(ec_tg_draft_get($id));
    ec_tg_preview($id);
}

function ec_tg_publish(int $id): void
{
    $d = ec_tg_draft_get($id);
    if (!$d || !$d['listing_id']) {
        return;
    }
    $agent = ec_agent((int) $d['agent_id']);
    $target = ec_listing_target_status($agent, 'published');
    $r = ec_listing_update((int) $d['listing_id'], ['status' => $target]);
    if (is_wp_error($r)) {
        $f = array_keys((array) ($r->get_error_data()['fields'] ?? []));
        ec_tg_draft_save($id, ['state' => 'editing']);
        ec_tg_send($d['chat_id'], '⛔ Yayınlamadan önce gerekli: <b>' . ec_tg_esc(implode(', ', array_map('ec_tg_field_name', $f))) . "</b>\nLütfen yazın (ör. \"tapu Türk koçanı\").");
        return;
    }
    ec_tg_draft_save($id, ['state' => 'done']);
    $it = ec_listings_present([ec_listing_row((int) $d['listing_id'])], ec_member_lang((int) $agent['wp_user_id']))[0];
    if ($target === 'pending') {
        ec_notify_pending_listing((int) $d['listing_id']);
        ec_tg_send($d['chat_id'], "🕓 İlanınız onaya gönderildi (#{$it['id']}). Onaylanınca haber vereceğim.\n\nYeni ilan için fotoğrafları göndermeniz yeterli.");
    } else {
        ec_tg_send($d['chat_id'], "🎉 İlanınız yayında: <b>" . ec_tg_esc($it['title']) . "</b>\n" . $it['url'] . "\n\nYeni ilan için fotoğrafları göndermeniz yeterli.");
    }
    do_action('ec_listing_published_via_telegram', (int) $d['listing_id'], $agent);
}

/* ---------------- Düğmeler ---------------- */

function ec_tg_handle_callback(array $q): void
{
    $chat = (string) ($q['message']['chat']['id'] ?? $q['from']['id'] ?? '');
    $data = (string) ($q['data'] ?? '');
    [$act, $arg] = array_pad(explode(':', $data, 2), 2, '');
    $ack = fn(string $t = '') => ec_tg_api('answerCallbackQuery', ['callback_query_id' => $q['id'], 'text' => $t], false);

    // Yönetici onayı
    if (in_array($act, ['ap', 'rj'], true)) {
        if (!ec_tg_is_admin($chat)) {
            $ack('Yetkiniz yok');
            return;
        }
        $row = ec_listing_row((int) $arg);
        if (!$row || $row['status'] !== 'pending') {
            $ack('Bu ilan artık onay beklemiyor');
            return;
        }
        if ($act === 'ap') {
            ec_review_listing((int) $arg, 'approve');
            $ack('Onaylandı');
            ec_tg_send($chat, "✅ #$arg onaylandı ve yayına alındı.");
            if (!empty($q['message']['message_id'])) {
                ec_tg_api('editMessageReplyMarkup', ['chat_id' => $chat, 'message_id' => $q['message']['message_id'], 'reply_markup' => ['inline_keyboard' => []]], false);
            }
        } else {
            set_transient('ec_tg_reject_' . $chat, (int) $arg, 10 * MINUTE_IN_SECONDS);
            $ack();
            ec_tg_send($chat, "#$arg için red sebebini yazın (üyeye iletilir). Sebepsiz reddetmek için /atla yazın.");
        }
        return;
    }

    $d = ec_tg_draft_get((int) $arg);
    $agent = ec_tg_agent_by_chat($chat);
    if (!$d || $d['chat_id'] !== $chat || !$agent) {
        $ack('Bu taslak bulunamadı');
        return;
    }
    switch ($act) {
        case 'prep':
            if ($d['state'] !== 'collecting') {
                $ack('Zaten hazırlanıyor');
                return;
            }
            $ack('Hazırlanıyor…');
            wp_clear_scheduled_hook('ec_tg_auto', [(int) $d['id']]);
            ec_tg_send($chat, '⏳ İlanınız hazırlanıyor: bilgiler çıkarılıyor ve 3 dile çevriliyor…');
            ec_tg_defer(fn() => ec_tg_prepare((int) $d['id']));
            return;
        case 'pub':
            $ack();
            ec_tg_publish((int) $d['id']);
            return;
        case 'edit':
            ec_tg_draft_save((int) $d['id'], ['state' => 'editing']);
            $ack();
            ec_tg_send($chat, "✏️ Neyi değiştirelim? Doğal dille yazın, ör.:\n• <i>fiyat 165.000 sterlin</i>\n• <i>tapu eşdeğer, KDV ödendi</i>\n• <i>başlığa 'deniz manzaralı' ekle</i>\n\nFotoğraf eklemek için fotoğrafı göndermeniz yeterli.");
            return;
        case 'cancel':
            ec_tg_cancel($d);
            $ack('İptal edildi');
            ec_tg_send($chat, '🗑 Taslak silindi. Yeni ilan için fotoğrafları ve metni gönderin.');
            return;
    }
    $ack();
}

function ec_tg_my_listings(string $chat, array $agent): void
{
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('listings') . ' WHERE agent_id = %d ORDER BY id DESC LIMIT 8', $agent['id']), ARRAY_A);
    if (!$rows) {
        ec_tg_send($chat, 'Henüz ilanınız yok. Fotoğrafları ve metni göndererek başlayın.');
        return;
    }
    $st = ['draft' => '📝', 'pending' => '🕓', 'published' => '✅', 'rejected' => '❌', 'sold' => '🏷', 'passive' => '⏸'];
    $out = ["<b>Son ilanlarınız</b>"];
    foreach (ec_listings_present($rows, 'tr') as $it) {
        $out[] = ($st[$it['status']] ?? '') . ' #' . $it['id'] . ' <a href="' . esc_url($it['url']) . '">' . ec_tg_esc(mb_substr($it['title'], 0, 60)) . '</a>';
    }
    ec_tg_send($chat, implode("\n", $out));
}

function ec_tg_admin_pending(string $chat): void
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('listings') . " WHERE status = 'pending' ORDER BY id ASC LIMIT 10", ARRAY_A);
    if (!$rows) {
        ec_tg_send($chat, '👌 Onay bekleyen ilan yok.');
        return;
    }
    foreach ($rows as $row) {
        ec_tg_admin_listing_card((int) $row['id']);
    }
}

/** Yöneticiye onay kartı: [Onayla] [Reddet] [Sitede gör] */
function ec_tg_admin_listing_card(int $id): void
{
    $s = ec_tg_settings();
    $row = ec_listing_row($id);
    if (!$s['admin_chat'] || !$row) {
        return;
    }
    $it = ec_listings_present([$row], 'tr', true)[0];
    $a = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    $kind = $a ? (ec_member_kinds()[$a['kind']]['name']['tr'] ?? $a['kind']) : '-';
    $text = sprintf("🕓 <b>Onay bekleyen ilan #%d</b>\n%s\n%s · %s · £%s\nTapu: %s · 📷 %d\nİlan sahibi: %s (%s) %s",
        $id, ec_tg_esc($it['title']), ec_tg_esc($it['subtype_name']), ec_tg_esc($it['city_name']), number_format($it['price_gbp'], 0, ',', '.'),
        ec_tg_esc($it['deed_name'] ?: '—'), count($it['photos']), ec_tg_esc($a['name'] ?? '-'), ec_tg_esc($kind), ec_tg_esc((string) ($a['phone'] ?? '')));
    $kb = [[['text' => '✅ Onayla', 'callback_data' => 'ap:' . $id], ['text' => '❌ Reddet', 'callback_data' => 'rj:' . $id]], [['text' => '👁 Önizle', 'url' => ec_url_section('review', 'tr')]]];
    $cover = $it['photos'][0]['large'] ?? null;
    $r = $cover && !str_contains($cover, '://localhost') ? ec_tg_api('sendPhoto', ['chat_id' => $s['admin_chat'], 'photo' => $cover, 'caption' => mb_substr($text, 0, 1000), 'parse_mode' => 'HTML', 'reply_markup' => ['inline_keyboard' => $kb]]) : null;
    if (!$r || is_wp_error($r)) {
        ec_tg_send($s['admin_chat'], $text, $kb);
    }
}

/** Onay sonucu üyeye Telegram'dan da bildirilsin */
add_action('ec_listing_reviewed', function (int $id, string $action, string $reason) {
    $row = ec_listing_row($id);
    $a = $row && $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    if (!$a || empty($a['telegram_chat_id']) || !ec_tg_ready()) {
        return;
    }
    $it = ec_listings_present([$row], ec_member_lang((int) $a['wp_user_id']))[0];
    ec_tg_send($a['telegram_chat_id'], $action === 'approve'
        ? "✅ İlanınız onaylandı ve yayında: <b>" . ec_tg_esc($it['title']) . "</b>\n" . $it['url']
        : "❌ #$id ilanınız yayına alınamadı." . ($reason ? "\nSebep: " . ec_tg_esc($reason) : '') . "\nDüzeltmek için: " . add_query_arg(['bolum' => 'ilan', 'id' => $id], ec_url_section('panel', 'tr')));
}, 10, 3);

/** Webhook'u Telegram'a kaydeder. @return true|WP_Error */
function ec_tg_set_webhook()
{
    $s = ec_tg_settings();
    if (!$s['secret']) {
        $s['secret'] = wp_generate_password(32, false, false);
        update_option('ec_telegram', $s);
    }
    $r = ec_tg_api('setWebhook', [
        'url' => home_url('/api/v1/telegram/webhook'),
        'secret_token' => $s['secret'],
        'allowed_updates' => ['message', 'edited_message', 'callback_query'],
        'drop_pending_updates' => false,
    ]);
    if (is_wp_error($r)) {
        return $r;
    }
    ec_tg_api('setMyCommands', ['commands' => [
        ['command' => 'yeni', 'description' => 'Yeni ilana başla'],
        ['command' => 'ilanlarim', 'description' => 'Son ilanlarım'],
        ['command' => 'iptal', 'description' => 'Açık taslağı sil'],
        ['command' => 'yardim', 'description' => 'Nasıl çalışır?'],
    ]]);
    return true;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/telegram/webhook', [
        'methods' => 'POST',
        'permission_callback' => function (WP_REST_Request $r) {
            $s = ec_tg_settings();
            return $s['secret'] !== '' && hash_equals($s['secret'], (string) $r->get_header('x_telegram_bot_api_secret_token'));
        },
        'callback' => function (WP_REST_Request $r) {
            $u = $r->get_json_params();
            if (is_array($u) && isset($u['update_id'])) {
                // Aynı güncelleme iki kez gelirse (Telegram yeniden dener) bir kez işle
                $k = 'ec_tg_u_' . (int) $u['update_id'];
                if (!get_transient($k)) {
                    set_transient($k, 1, HOUR_IN_SECONDS);
                    ec_tg_handle_update($u);
                }
            }
            return ['ok' => true];
        },
    ]);
});
