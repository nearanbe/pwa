<?php
/**
 * Benzer / kopya ilan denetimi (0.7).
 *
 * Fotoğraf: her fotoğrafın 64 bitlik görsel parmak izi (dHash). Boyut, sıkıştırma ya da biçim değişse de
 * neredeyse aynı kalır. İki fotoğrafın parmak izi en çok EC_DUP_PHOTO_BITS bit farklıysa "aynı fotoğraf" sayılır.
 * Açıklama: normalleştirilmiş metnin (küçük harf, noktalama ve telefonlar atılmış) birebir özeti (md5).
 * (simhash de saklanır; ileride “çok benzer metin” uyarısı için kullanılabilir, şimdilik sadece birebir aynı metin uyarır.)
 *
 * Ne zaman bakılır:
 *  - Fotoğraf yüklenince ve ilan kaydedilince → ekleyen üyeye "Buna benzer ilan zaten yayında" uyarısı
 *  - İlan yayına girince → başka üyenin ilanıyla eşleşirse yöneticiye otomatik "kopya ilan" bildirimi
 *  - Her gün → yayındaki tüm ilanlar yeniden taranır (yeni eklenenlerle eşleşenler yakalanır)
 *  - Biri "Hatalı ilan bildir"de "aynı ilan / fotoğraflar başka mülke ait" seçerse → bildirimin altına sonuç yazılır
 * Ayarlar → "Benzer ilan bulununca onaya düşür" açıksa, başka üyenin ilanıyla eşleşen ilan yayına girmez, onaya düşer.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_DUP_PHOTO_BITS = 4;   // yatay ve dikey parmak izinin her biri en çok 4 bit farklı = aynı fotoğraf
const EC_DUP_TEXT_BITS = 3;    // açıklama benzerliği
const EC_DUP_TEXT_MIN = 80;    // bundan kısa açıklamalar karşılaştırılmaz (yanlış alarm olmasın)

/** Görsel parmak izleri: yatay ve dikey dHash (64'er bit). @return array{0:int,1:int}|null */
function ec_dhash_file(string $file): ?array
{
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    $info = @getimagesize($file);
    $im = match ($info[2] ?? 0) {
        IMAGETYPE_WEBP => @imagecreatefromwebp($file),
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
        IMAGETYPE_PNG => @imagecreatefrompng($file),
        default => null,
    };
    if (!$im) {
        return null;
    }
    $s = imagecreatetruecolor(9, 9);
    imagecopyresampled($s, $im, 0, 0, 0, 0, 9, 9, imagesx($im), imagesy($im));
    imagedestroy($im);
    $g = [];
    for ($y = 0; $y < 9; $y++) {
        for ($x = 0; $x < 9; $x++) {
            $c = imagecolorat($s, $x, $y);
            $g[$y][$x] = (($c >> 16) & 255) * 0.299 + (($c >> 8) & 255) * 0.587 + ($c & 255) * 0.114;
        }
    }
    imagedestroy($s);
    $h = $v = 0;
    $bit = 0;
    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            if ($g[$y][$x + 1] > $g[$y][$x]) {
                $h |= (1 << $bit);
            }
            if ($g[$y + 1][$x] > $g[$y][$x]) {
                $v |= (1 << $bit);
            }
            $bit++;
        }
    }
    return [$h, $v];
}

/** Fotoğraf satırının parmak izini hesaplar ve kaydeder */
function ec_dup_hash_photo(array $photo): void
{
    global $wpdb;
    $h = ec_dhash_file(ec_photo_base()['dir'] . '/' . $photo['path'] . '-s.webp');
    if ($h !== null) {
        $wpdb->update(ec_table('photos'), ['phash' => $h[0], 'phash2' => $h[1]], ['id' => $photo['id']]);
    }
}

/** Metni karşılaştırma için sadeleştirir (küçük harf, Türkçe karakter, noktalama ve telefon numaraları atılır) */
function ec_dup_normalize(string $t): string
{
    $t = mb_strtolower(strtr($t, ['İ' => 'i', 'I' => 'ı']));
    $t = strtr($t, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u']);
    $t = preg_replace('/\+?\d[\d\s().-]{6,}\d/u', ' ', $t); // telefon numaraları
    $t = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $t);
    return trim(preg_replace('/\s+/', ' ', $t));
}

/** 64 bit simhash (3 kelimelik parçalar) */
function ec_simhash(string $norm): int
{
    $w = explode(' ', $norm);
    $v = array_fill(0, 64, 0);
    $n = max(1, count($w) - 2);
    for ($i = 0; $i < $n; $i++) {
        $sh = implode(' ', array_slice($w, $i, 3));
        $h = (crc32($sh) << 32) | crc32(strrev($sh) . '#');
        for ($b = 0; $b < 64; $b++) {
            $v[$b] += (($h >> $b) & 1) ? 1 : -1;
        }
    }
    $out = 0;
    for ($b = 0; $b < 64; $b++) {
        if ($v[$b] > 0) {
            $out |= (1 << $b);
        }
    }
    return $out;
}

/** İlanın açıklama özetlerini günceller (en uzun açıklama kullanılır) */
function ec_dup_hash_text(int $listing_id): void
{
    global $wpdb;
    $descs = $wpdb->get_col($wpdb->prepare('SELECT description FROM ' . ec_table('listing_i18n') . ' WHERE listing_id = %d', $listing_id));
    usort($descs, fn($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));
    $norm = ec_dup_normalize((string) ($descs[0] ?? ''));
    $ok = mb_strlen($norm) >= EC_DUP_TEXT_MIN;
    $wpdb->update(ec_table('listings'), ['text_md5' => $ok ? md5($norm) : null, 'text_hash' => $ok ? ec_simhash($norm) : null], ['id' => $listing_id]);
}

/**
 * İlanı diğer (yayındaki ya da onay bekleyen) ilanlarla karşılaştırır.
 * @return array [['id' => 10023, 'photos' => 3, 'text' => true, 'same_agent' => false, 'url' => '...', 'title' => '...'], ...] en güçlü eşleşme başta
 */
function ec_dup_check_listing(int $id): array
{
    global $wpdb;
    $row = ec_listing_row($id);
    if (!$row) {
        return [];
    }
    $L = ec_table('listings');
    $P = ec_table('photos');
    $hits = [];
    // Fotoğraflar
    $mine = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT phash, phash2 FROM $P WHERE listing_id = %d AND phash IS NOT NULL AND phash <> 0 AND phash2 IS NOT NULL", $id), ARRAY_A);
    foreach ($mine as $m) {
        // İki bağımsız parmak izi de tutmalı: birebir aynı fotoğraf (yeniden boyutlanmış / sıkıştırılmış olsa da)
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT p.listing_id FROM $P p JOIN $L l ON l.id = p.listing_id
             WHERE p.listing_id <> %d AND p.phash <> 0 AND BIT_COUNT(p.phash ^ %d) <= %d AND BIT_COUNT(p.phash2 ^ %d) <= %d AND l.status IN ('published','pending')",
            $id, (int) $m['phash'], EC_DUP_PHOTO_BITS, (int) $m['phash2'], EC_DUP_PHOTO_BITS
        ), ARRAY_A);
        foreach (array_unique(array_column($rows, 'listing_id')) as $lid) {
            $hits[(int) $lid]['photos'] = ($hits[(int) $lid]['photos'] ?? 0) + 1;
        }
    }
    // Açıklama
    if ($row['text_md5'] !== null) {
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $L WHERE id <> %d AND status IN ('published','pending') AND text_md5 = %s",
            $id, (string) $row['text_md5']
        ));
        foreach ($rows as $lid) {
            $hits[(int) $lid]['text'] = true;
        }
    }
    if (!$hits) {
        $wpdb->update($L, ['dup_of' => null, 'dup_checked' => ec_now()], ['id' => $id]);
        return [];
    }
    $others = ec_listings_present($wpdb->get_results('SELECT * FROM ' . $L . ' WHERE id IN (' . implode(',', array_keys($hits)) . ')', ARRAY_A), 'tr');
    $out = [];
    foreach ($others as $o) {
        $h = $hits[$o['id']];
        $out[] = [
            'id' => $o['id'], 'photos' => (int) ($h['photos'] ?? 0), 'text' => !empty($h['text']),
            'same_agent' => (int) $o['agent_id'] === (int) $row['agent_id'],
            'title' => $o['title'], 'url' => $o['url'], 'status' => $o['status'],
            'agent' => $o['agent']['name'] ?? '',
        ];
    }
    usort($out, fn($a, $b) => ($b['photos'] * 2 + ($b['text'] ? 3 : 0)) <=> ($a['photos'] * 2 + ($a['text'] ? 3 : 0)));
    $wpdb->update($L, ['dup_of' => $out[0]['id'], 'dup_checked' => ec_now()], ['id' => $id]);
    return $out;
}

/** Üyeye gösterilecek uyarı metni */
function ec_dup_message(array $dups, string $lang = 'tr'): string
{
    $d = $dups[0];
    $what = [];
    if ($d['photos']) {
        $what[] = ['tr' => $d['photos'] . ' fotoğraf', 'en' => $d['photos'] . ' photo(s)', 'ru' => 'фото: ' . $d['photos']][$lang];
    }
    if ($d['text']) {
        $what[] = ['tr' => 'açıklama', 'en' => 'the description', 'ru' => 'описание'][$lang];
    }
    $w = implode(['tr' => ' ve ', 'en' => ' and ', 'ru' => ' и '][$lang], $what);
    return [
        'tr' => "Buna benzer bir ilan zaten yayında: #{$d['id']} “{$d['title']}” ($w aynı). Aynı mülkü iki kez yayınlamayın; fotoğraf ve açıklama size ait olmalı.",
        'en' => "A similar listing is already live: #{$d['id']} “{$d['title']}” (same $w). Do not list the same property twice; photos and text must be your own.",
        'ru' => "Похожее объявление уже опубликовано: #{$d['id']} «{$d['title']}» (совпадает: $w). Не публикуйте один объект дважды.",
    ][$lang] . (count($dups) > 1 ? ['tr' => ' (+' . (count($dups) - 1) . ' benzer ilan daha)', 'en' => ' (+' . (count($dups) - 1) . ' more)', 'ru' => ' (+' . (count($dups) - 1) . ')'][$lang] : '');
}

/** Başka üyenin ilanıyla eşleşme varsa yöneticiye otomatik "kopya ilan" bildirimi (açık bildirim yoksa) */
function ec_dup_auto_report(int $id, array $dups): void
{
    global $wpdb;
    $row = ec_listing_row($id);
    if (!$row || $row['source'] === 'demo') {
        return;
    }
    $cross = array_values(array_filter($dups, fn($d) => !$d['same_agent']));
    if (!$cross) {
        return;
    }
    $t = ec_table('reports');
    if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE listing_id = %d AND reason = 'duplicate' AND status = 'open'", $id))) {
        return;
    }
    $note = 'Otomatik tespit: ' . implode('; ', array_map(fn($d) => "#{$d['id']} ({$d['agent']}) ile " . implode(' + ', array_filter([$d['photos'] ? $d['photos'] . ' fotoğraf aynı' : '', $d['text'] ? 'açıklama aynı' : ''])), array_slice($cross, 0, 3)));
    $wpdb->insert($t, ['listing_id' => $id, 'reason' => 'duplicate', 'note' => $note, 'email' => null, 'status' => 'open', 'ip_hash' => 'auto', 'created_at' => ec_now()]);
    ec_notify_admin("🔁 Kopya ilan şüphesi #$id\n$note", ec_url_section('review', 'tr'), false);
}

/* ---------------- Kancalar ---------------- */

/** Fotoğraf eklenince parmak izi */
add_action('ec_photo_added', function (array $photo) {
    ec_dup_hash_photo($photo);
});

/** Metin kaydedilince özet */
add_action('ec_listing_texts_saved', function (int $id) {
    ec_dup_hash_text($id);
});

/** Yayına girince: başka üyeyle eşleşme → bildirim; ayar açıksa onaya düşür */
add_action('ec_listing_published', function (int $id) {
    $dups = ec_dup_check_listing($id);
    if (!$dups) {
        return;
    }
    ec_dup_auto_report($id, $dups);
    $cross = array_filter($dups, fn($d) => !$d['same_agent']);
    $row = ec_listing_row($id);
    if ($cross && get_option('ec_dup_hold') && $row && $row['source'] !== 'demo') {
        global $wpdb;
        $wpdb->update(ec_table('listings'), ['status' => 'pending', 'reject_reason' => null], ['id' => $id]);
        ec_notify_pending_listing($id);
    }
}, 5);

/** "Hatalı ilan bildir" (aynı ilan / fotoğraflar) gelince taramayı çalıştır, sonucu bildirime yaz */
add_action('ec_report_created', function (int $report_id, int $listing_id, string $reason) {
    global $wpdb;
    if (!in_array($reason, ['duplicate', 'photos', 'fraud'], true)) {
        return;
    }
    $d = ec_dup_check_listing($listing_id);
    $txt = $d ? ' | Otomatik kontrol: ' . implode('; ', array_map(fn($x) => "#{$x['id']}" . ($x['photos'] ? " {$x['photos']} fotoğraf" : '') . ($x['text'] ? ' açıklama' : '') . ' aynı', array_slice($d, 0, 3))) : ' | Otomatik kontrol: eşleşme bulunamadı';
    $wpdb->query($wpdb->prepare('UPDATE ' . ec_table('reports') . ' SET note = CONCAT(IFNULL(note, \'\'), %s) WHERE id = %d', $txt, $report_id));
}, 10, 3);

/** Her gün: yayındaki ilanları tara (son taramadan sonra değişenler + en eski taranmışlar) */
add_action('ec_daily', function () {
    ec_dup_scan(400);
});

function ec_dup_scan(int $limit = 400): int
{
    global $wpdb;
    $L = ec_table('listings');
    $ids = $wpdb->get_col("SELECT id FROM $L WHERE status IN ('published','pending') ORDER BY (dup_checked IS NULL) DESC, (updated_at > dup_checked) DESC, dup_checked ASC LIMIT " . (int) $limit);
    $n = 0;
    foreach ($ids as $id) {
        $d = ec_dup_check_listing((int) $id);
        if ($d) {
            ec_dup_auto_report((int) $id, $d);
            $n++;
        }
    }
    return $n;
}

/** Eski fotoğrafların parmak izlerini parça parça hesapla (güncellemeden sonra) */
add_action('ec_dup_backfill', function () {
    global $wpdb;
    $rows = $wpdb->get_results('SELECT id, path FROM ' . ec_table('photos') . ' WHERE phash IS NULL OR phash2 IS NULL LIMIT 300', ARRAY_A);
    foreach ($rows as $p) {
        ec_dup_hash_photo($p);
        if (!$wpdb->get_var($wpdb->prepare('SELECT phash FROM ' . ec_table('photos') . ' WHERE id = %d', $p['id']))) {
            $wpdb->update(ec_table('photos'), ['phash' => 0, 'phash2' => 0], ['id' => $p['id']]); // okunamayan dosya: tekrar deneme
        }
    }
    foreach ($wpdb->get_col('SELECT id FROM ' . ec_table('listings') . ' WHERE text_md5 IS NULL AND dup_checked IS NULL LIMIT 500') as $id) {
        ec_dup_hash_text((int) $id);
    }
    if ($rows) {
        wp_schedule_single_event(time() + 60, 'ec_dup_backfill');
    }
});

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/duplicates', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            $d = ec_dup_check_listing((int) $r['id']);
            return ['items' => $d, 'message' => $d ? ec_dup_message($d, ec_rest_lang($r)) : ''];
        },
    ]);
});
