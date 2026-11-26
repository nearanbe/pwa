<?php
/**
 * Sanal eşya yerleştirme (1.6), Telegram akışı:
 *  1. İlan hazırlanınca (Telegram) fotoğraflar Claude'a gösterilir: boş (eşyasız) iç mekânlar bulunur.
 *  2. Varsa emlakçıya sorulur: "3. ve 5. fotoğraftaki odalar boş görünüyor. Eşyalı hâlini de göstermek ister misiniz?"
 *  3. [Evet] → Cloudflare Workers AI (@cf/black-forest-labs/flux-2-klein-9b, görsel düzenleme) aynı odaya eşya ekler;
 *     duvar, pencere ve ölçü korunur. Önizlemeler Telegram'a gelir: [✅ İlana ekle] [Vazgeç].
 *  4. [İlana ekle] → eşyalı görsel asıl fotoğrafın HEMEN ARKASINA eklenir (asıl fotoğraf kalır), photos.staged = 1;
 *     sitede üzerinde "Temsili eşyalı görünüm" yazar.
 * Ayar: Emlak → Ayarlar → Cloudflare (Account ID + Workers AI yetkili API Token), ec_cloudflare seçeneği.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_STAGE_MODEL = '@cf/black-forest-labs/flux-2-klein-9b';
const EC_STAGE_MAX = 4;

function ec_stage_dir(): string
{
    $dir = ec_receipt_dir() . '/stage';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
        @file_put_contents($dir . '/index.php', "<?php // sessizlik\n");
    }
    return $dir;
}

/** WebP/JPEG/PNG → en fazla 1024 px JPEG (16'nın katı ölçülerle). @return array{0:string,1:int,2:int}|null [jpeg, w, h] */
function ec_stage_jpeg(string $file): ?array
{
    $info = @getimagesize($file);
    if (!$info) {
        return null;
    }
    $src = match ($info[2]) {
        IMAGETYPE_WEBP => @imagecreatefromwebp($file),
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
        IMAGETYPE_PNG => @imagecreatefrompng($file),
        default => null,
    };
    if (!$src) {
        return null;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $sc = min(1, 1024 / max($w, $h));
    $nw = max(256, (int) (floor($w * $sc / 16) * 16));
    $nh = max(256, (int) (floor($h * $sc / 16) * 16));
    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    ob_start();
    imagejpeg($dst, null, 88);
    return [(string) ob_get_clean(), $nw, $nh];
}

/** Claude: hangi fotoğraflar boş iç mekân? @return array<int, string> [foto_id => oda türü] */
function ec_stage_detect_rooms(array $photos): array
{
    $s = ec_ai_settings();
    if (!$s['key'] || !$photos) {
        return [];
    }
    $content = [];
    $ids = [];
    foreach (array_slice($photos, 0, 12) as $i => $p) {
        $file = ec_photo_base()['dir'] . '/' . $p['path'] . '-s.webp';
        $j = is_file($file) ? ec_stage_jpeg($file) : null;
        if (!$j) {
            continue;
        }
        $ids[] = (int) $p['id'];
        $content[] = ['type' => 'text', 'text' => 'Photo ' . count($ids) . ':'];
        $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => base64_encode($j[0])]];
    }
    if (!$ids) {
        return [];
    }
    $content[] = ['type' => 'text', 'text' => 'For each photo, is it an EMPTY (unfurnished, no or almost no furniture) INDOOR room that could be virtually staged? Room type: living, bedroom, dining, office, kitchen, bathroom, other. Only living, bedroom, dining and office rooms count as stageable.'];
    $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['photos'], 'properties' => ['photos' => ['type' => 'array', 'items' => [
        'type' => 'object', 'additionalProperties' => false, 'required' => ['n', 'empty_indoor', 'room'],
        'properties' => ['n' => ['type' => 'integer'], 'empty_indoor' => ['type' => 'boolean'], 'room' => ['type' => 'string', 'enum' => ['living', 'bedroom', 'dining', 'office', 'kitchen', 'bathroom', 'other']]],
    ]]]];
    $res = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 60,
        'headers' => ['content-type' => 'application/json', 'x-api-key' => $s['key'], 'anthropic-version' => '2026-10-21'],
        'body' => wp_json_encode([
            'model' => apply_filters('ec_stage_vision_model', 'claude-haiku-5-5'),
            'max_tokens' => 1200,
            'messages' => [['role' => 'user', 'content' => $content]],
            'output_config' => ['format' => ['type' => 'json_schema', 'schema' => $schema]],
        ]),
    ]);
    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
        return [];
    }
    $out = '';
    foreach ((json_decode((string) wp_remote_retrieve_body($res), true)['content'] ?? []) as $b) {
        $out .= ($b['type'] ?? '') === 'text' ? $b['text'] : '';
    }
    $rooms = [];
    foreach ((array) (json_decode($out, true)['photos'] ?? []) as $x) {
        $k = (int) ($x['n'] ?? 0) - 1;
        if (!empty($x['empty_indoor']) && in_array($x['room'] ?? '', ['living', 'bedroom', 'dining', 'office'], true) && isset($ids[$k])) {
            $rooms[$ids[$k]] = $x['room'];
        }
    }
    return array_slice($rooms, 0, EC_STAGE_MAX, true);
}

/** Taslak ilan hazırlanınca: boş oda varsa emlakçıya sor */
function ec_stage_offer(int $draftId): void
{
    $d = ec_tg_draft_get($draftId);
    if (!$d || !$d['listing_id']) {
        return;
    }
    $data = ec_tg_j($d['data']);
    if (!empty($data['stage_asked'])) {
        return;
    }
    $row = ec_listing_row((int) $d['listing_id']);
    $photos = $row ? array_values(array_filter(ec_stage_photos((int) $row['id']), fn($p) => empty($p['staged']))) : [];
    $rooms = ec_stage_detect_rooms($photos);
    $data['stage_asked'] = 1;
    if (!$rooms) {
        ec_tg_draft_save($draftId, ['data' => $data]);
        return;
    }
    $data['stage_rooms'] = $rooms;
    ec_tg_draft_save($draftId, ['data' => $data]);
    $nums = [];
    foreach ($photos as $i => $p) {
        if (isset($rooms[(int) $p['id']])) {
            $nums[] = ($i + 1) . '.';
        }
    }
    ec_tg_send($d['chat_id'], '🛋 ' . implode(' ve ', $nums) . " fotoğraftaki oda" . (count($nums) > 1 ? 'lar' : '') . " boş görünüyor. Eşyalı hâlini de göstermek ister misiniz?\n\nAsıl fotoğraf yerinde kalır; eşyalı görsel yanına “Temsili eşyalı görünüm” etiketiyle eklenir. Önce size önizleme göndereceğim.", [
        [['text' => '✅ Evet, hazırla', 'callback_data' => 'stg:' . $draftId], ['text' => 'Hayır', 'callback_data' => 'stn:' . $draftId]],
    ]);
}

function ec_stage_photos(int $listingId): array
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('photos') . ' WHERE listing_id = %d ORDER BY sort, id', $listingId), ARRAY_A) ?: [];
}

/** Cloudflare görsel düzenleme. @return string|WP_Error JPEG ikili */
function ec_stage_render(string $jpeg, int $w, int $h, string $room)
{
    $cf = (array) get_option('ec_cloudflare', []);
    if (empty($cf['account']) || empty($cf['token'])) {
        return new WP_Error('ec_cf', 'Cloudflare ayarı eksik');
    }
    $what = [
        'living' => 'a modern light grey sofa with cushions, a wooden coffee table, a neutral rug, a floor lamp and a green plant',
        'bedroom' => 'a double bed with white linen, two bedside tables with lamps, a neutral rug and a small plant',
        'dining' => 'a wooden dining table with six chairs, a pendant light and a sideboard',
        'office' => 'a simple desk, an office chair, a bookshelf and a plant',
    ][$room] ?? 'tasteful modern furniture';
    $prompt = "Virtual staging of this exact empty room: add $what. Keep the walls, floor, ceiling, windows, doors, view, camera angle, room size and lighting exactly the same. Do not add or remove windows or doors. Photorealistic real estate photo, natural daylight.";
    $b = wp_generate_password(24, false, false);
    $body = '';
    foreach (['prompt' => $prompt, 'width' => $w, 'height' => $h] as $k => $v) {
        $body .= "--$b\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n$v\r\n";
    }
    $body .= "--$b\r\nContent-Disposition: form-data; name=\"input_image_0\"; filename=\"room.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n$jpeg\r\n--$b--\r\n";
    $res = wp_remote_post('https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($cf['account']) . '/ai/run/' . EC_STAGE_MODEL, [
        'timeout' => 150,
        'headers' => ['Authorization' => 'Bearer ' . $cf['token'], 'Content-Type' => 'multipart/form-data; boundary=' . $b],
        'body' => $body,
    ]);
    if (is_wp_error($res)) {
        return $res;
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    $img = $j['result']['image'] ?? null;
    if ((int) wp_remote_retrieve_response_code($res) !== 200 || !$img) {
        update_option('ec_stage_last_error', gmdate('Y-m-d H:i') . ' ' . substr((string) wp_remote_retrieve_body($res), 0, 300), false);
        return new WP_Error('ec_cf', 'Görsel üretilemedi: ' . ($j['errors'][0]['message'] ?? ('HTTP ' . wp_remote_retrieve_response_code($res))));
    }
    return (string) base64_decode($img, true);
}

/** [Evet] → önizlemeleri üret ve gönder */
function ec_stage_make(int $draftId): void
{
    $d = ec_tg_draft_get($draftId);
    $data = $d ? ec_tg_j($d['data']) : [];
    if (!$d || empty($data['stage_rooms'])) {
        return;
    }
    $ready = [];
    $media = [];
    $err = '';
    foreach (ec_stage_photos((int) $d['listing_id']) as $p) {
        $room = $data['stage_rooms'][$p['id']] ?? $data['stage_rooms'][(string) $p['id']] ?? null;
        if (!$room) {
            continue;
        }
        $j = ec_stage_jpeg(ec_photo_base()['dir'] . '/' . $p['path'] . '-l.webp');
        if (!$j) {
            continue;
        }
        $img = ec_stage_render($j[0], $j[1], $j[2], $room);
        if (is_wp_error($img)) {
            $err = $img->get_error_message();
            continue;
        }
        $name = $draftId . '-' . $p['id'] . '-' . wp_generate_password(8, false, false) . '.jpg';
        file_put_contents(ec_stage_dir() . '/' . $name, $img);
        $ready[] = [(int) $p['id'], $name];
        $media[] = $name;
    }
    $data['stage_ready'] = $ready;
    ec_tg_draft_save($draftId, ['data' => $data]);
    if (!$ready) {
        ec_tg_send($d['chat_id'], '⚠️ Eşyalı görünüm şu an hazırlanamadı.' . ($err ? ' (' . ec_tg_esc($err) . ')' : '') . ' İlanınız etkilenmedi.');
        ec_notify_admin('Sanal eşya yerleştirme çalışmadı: ' . $err . ' — Emlak → Ayarlar → Cloudflare ayarını kontrol edin (Workers AI yetkili token).');
        return;
    }
    $s = ec_tg_settings();
    foreach ($media as $i => $name) {
        $b = 'ecstage' . $i;
        $body = "--$b\r\nContent-Disposition: form-data; name=\"chat_id\"\r\n\r\n{$d['chat_id']}\r\n--$b\r\nContent-Disposition: form-data; name=\"caption\"\r\n\r\nTemsili eşyalı görünüm " . ($i + 1) . '/' . count($media) . "\r\n--$b\r\nContent-Disposition: form-data; name=\"photo\"; filename=\"s.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n" . file_get_contents(ec_stage_dir() . '/' . $name) . "\r\n--$b--\r\n";
        wp_remote_post('https://api.telegram.org/bot' . $s['token'] . '/sendPhoto', ['timeout' => 30, 'headers' => ['Content-Type' => 'multipart/form-data; boundary=' . $b], 'body' => $body]);
    }
    ec_tg_send($d['chat_id'], '🛋 Eşyalı görünümler hazır. Duvar, pencere ve oda ölçüsü korunmuş olmalı; değişmiş bir şey görürseniz eklemeyin.', [
        [['text' => '✅ İlana ekle', 'callback_data' => 'sta:' . $draftId], ['text' => 'Vazgeç', 'callback_data' => 'stx:' . $draftId]],
    ]);
}

/** [İlana ekle] → asıl fotoğrafın arkasına ekle */
function ec_stage_apply(int $draftId, bool $add): string
{
    global $wpdb;
    $d = ec_tg_draft_get($draftId);
    $data = $d ? ec_tg_j($d['data']) : [];
    $ready = (array) ($data['stage_ready'] ?? []);
    $n = 0;
    foreach ($ready as [$pid, $name]) {
        $file = ec_stage_dir() . '/' . $name;
        if ($add && is_file($file)) {
            $tmp = wp_tempnam('stage.jpg');
            copy($file, $tmp);
            $r = ec_photo_add((int) $d['listing_id'], $tmp);
            @unlink($tmp);
            if (!is_wp_error($r)) {
                $wpdb->update(ec_table('photos'), ['staged' => 1], ['id' => $r['id']]);
                // Sıralama: eşyalı görsel, asıl fotoğrafın hemen arkasına
                $ids = array_map(fn($p) => (int) $p['id'], ec_stage_photos((int) $d['listing_id']));
                $ids = array_values(array_diff($ids, [(int) $r['id']]));
                $at = array_search((int) $pid, $ids, true);
                array_splice($ids, $at === false ? count($ids) : $at + 1, 0, [(int) $r['id']]);
                ec_photo_reorder((int) $d['listing_id'], $ids);
                $n++;
            }
        }
        @unlink($file);
    }
    unset($data['stage_ready'], $data['stage_rooms']);
    if ($d) {
        ec_tg_draft_save($draftId, ['data' => $data]);
    }
    if ($n) {
        do_action('ec_listing_changed', (int) $d['listing_id']);
    }
    return $add ? "✅ $n eşyalı görünüm ilana eklendi (“Temsili eşyalı görünüm” etiketiyle)." : 'Tamam, eşyalı görünüm eklenmedi.';
}
