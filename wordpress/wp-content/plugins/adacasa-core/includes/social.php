<?php
/**
 * Sosyal medya paylaşımı (0.5): Facebook sayfası ve Instagram (işletme/içerik üretici hesabı).
 * Facebook gruplarına paylaşım YOKTUR (Meta 2024'te kaldırdı; bot gibi paylaşım hesap kapattırır).
 *
 * Yapı (yönetici kontrolünde):
 *  - Yönetici bir kez Meta uygulaması açar (Uygulama No + Gizli Anahtar), "Facebook ile bağlan" der.
 *  - Emlakçı, Facebook sayfasında yöneticiye erişim verir (Meta Business Suite → Sayfa erişimi).
 *    Böylece yöneticinin hesabı o sayfayı görür; sayfa listesi buradan çekilir, her sayfa bir üyeye atanır.
 *  - Sayfa anahtarları uzun ömürlü kullanıcı anahtarından türetildiği için süresi dolmaz.
 *  - İlan yayına girince: sitenin kendi sayfası her zaman, üyenin sayfası (paketi varsa ve otomatik paylaşımı
 *    açıksa) kuyruğa alınır; zamanlanmış görev 1 dakika içinde paylaşır.
 * Anahtarlar veritabanında şifreli saklanır (AES-256-GCM, anahtar wp-config tuzlarından türetilir).
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_FB_SCOPES = 'pages_show_list,pages_read_engagement,pages_manage_posts,instagram_basic,instagram_content_publish,business_management';

function ec_social_settings(): array
{
    return array_merge([
        'app_id' => '', 'app_secret' => '', 'graph_ver' => 'v24.0', 'user_token' => '', 'user_name' => '', 'connected_at' => '',
        'site_auto' => 1, 'max_photos' => 10, 'fb_360' => 1,
        'tpl_tr' => '', 'tpl_en' => '', 'tpl_ru' => '',
    ], (array) get_option('ec_social', []));
}

function ec_social_save(array $s): void
{
    update_option('ec_social', $s, false);
}

/* ---------------- Şifreleme ---------------- */

function ec_secret_key(): string
{
    return hash('sha256', (defined('EC_SECRET_KEY') ? EC_SECRET_KEY : '') . wp_salt('auth') . 'ec-social', true);
}

function ec_encrypt(string $plain): string
{
    if ($plain === '') {
        return '';
    }
    $iv = random_bytes(12);
    $ct = openssl_encrypt($plain, 'aes-256-gcm', ec_secret_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return 'g1:' . base64_encode($iv . $tag . $ct);
}

function ec_decrypt(?string $enc): string
{
    if (!$enc || !str_starts_with($enc, 'g1:')) {
        return '';
    }
    $raw = base64_decode(substr($enc, 3));
    $p = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', ec_secret_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $p === false ? '' : $p;
}

/* ---------------- Graph API ---------------- */

/** @return array|WP_Error */
function ec_graph(string $method, string $path, array $params = [], int $timeout = 30)
{
    $s = ec_social_settings();
    $url = 'https://graph.facebook.com/' . $s['graph_ver'] . '/' . ltrim($path, '/');
    $args = ['timeout' => $timeout, 'user-agent' => 'EmlakCore/' . EC_VERSION];
    if ($method === 'GET') {
        $res = wp_remote_get(add_query_arg(array_map('rawurlencode', $params), $url), $args);
    } else {
        $res = wp_remote_post($url, $args + ['body' => $params]);
    }
    if (is_wp_error($res)) {
        return new WP_Error('ec_graph_http', 'Facebook’a bağlanılamadı: ' . $res->get_error_message(), ['retry' => true]);
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    if (!is_array($j)) {
        return new WP_Error('ec_graph', 'Facebook cevabı okunamadı (HTTP ' . wp_remote_retrieve_response_code($res) . ')', ['retry' => true]);
    }
    if (isset($j['error'])) {
        $e = $j['error'];
        $code = (int) ($e['code'] ?? 0);
        return new WP_Error('ec_graph', ec_graph_error_text($e), [
            'code' => $code, 'subcode' => (int) ($e['error_subcode'] ?? 0),
            'token' => $code === 190 || in_array((int) ($e['error_subcode'] ?? 0), [458, 459, 460, 463, 464, 467], true),
            'retry' => in_array($code, [1, 2, 4, 17, 32, 341, 613], true) || !empty($e['is_transient']),
        ]);
    }
    return $j;
}

/** Facebook hatalarını anlaşılır Türkçeye çevirir */
function ec_graph_error_text(array $e): string
{
    $code = (int) ($e['code'] ?? 0);
    $sub = (int) ($e['error_subcode'] ?? 0);
    $msg = (string) ($e['error_user_msg'] ?? $e['message'] ?? '');
    $map = [
        190 => 'Facebook bağlantısının süresi doldu ya da izin geri alındı. Yönetim → Sosyal medya → “Facebook ile bağlan” ile yenileyin.',
        10 => 'İzin eksik: Facebook ile bağlanırken istenen izinlerin hepsi onaylanmalı.',
        200 => 'İzin eksik: sayfada gönderi paylaşma izni yok (sayfa erişimi “tam kontrol” olmalı).',
        4 => 'Facebook istek sınırı aşıldı; biraz sonra kendiliğinden tekrar denenecek.',
        17 => 'Facebook istek sınırı aşıldı; biraz sonra kendiliğinden tekrar denenecek.',
        32 => 'Sayfa için istek sınırı aşıldı; biraz sonra tekrar denenecek.',
        368 => 'Facebook bu paylaşımı spam şüphesiyle engelledi. Bir süre bekleyin; aynı metni sık paylaşmayın.',
        9004 => 'Instagram fotoğrafı indiremedi. Site adresi herkese açık ve https olmalı.',
        36003 => 'Instagram fotoğraf oranını kabul etmedi (4:5 ile 1.91:1 arası olmalı).',
        9007 => 'Instagram gönderisi hazır değildi; tekrar denenecek.',
    ];
    if ($code === 190 && $sub === 460) {
        return 'Facebook şifresi değiştiği için bağlantı koptu. “Facebook ile bağlan” ile yenileyin.';
    }
    if ($sub === 2207042 || stripos($msg, 'limit') !== false && $code === 9) {
        return 'Instagram’ın günlük paylaşım sınırı doldu (24 saatte 50 gönderi). Yarın kendiliğinden devam eder.';
    }
    return ($map[$code] ?? 'Facebook hatası') . ($msg && !isset($map[$code]) ? ': ' . mb_substr($msg, 0, 180) : '') . " (#$code)";
}

/* ---------------- Bağlantı (OAuth) ---------------- */

function ec_fb_redirect_uri(): string
{
    return admin_url('admin-post.php?action=ec_fb_callback');
}

function ec_fb_login_url(): string
{
    $s = ec_social_settings();
    $state = wp_create_nonce('ec_fb_state');
    return 'https://www.facebook.com/' . $s['graph_ver'] . '/dialog/oauth?' . http_build_query([
        'client_id' => $s['app_id'], 'redirect_uri' => ec_fb_redirect_uri(), 'state' => $state, 'scope' => EC_FB_SCOPES, 'response_type' => 'code',
    ]);
}

/** Kısa ömürlü kullanıcı anahtarını uzun ömürlüye (60 gün) çevirir. @return string|WP_Error */
function ec_fb_long_token(string $short)
{
    $s = ec_social_settings();
    $r = ec_graph('GET', 'oauth/access_token', ['grant_type' => 'fb_exchange_token', 'client_id' => $s['app_id'], 'client_secret' => ec_decrypt($s['app_secret']), 'fb_exchange_token' => $short]);
    return is_wp_error($r) ? $r : (string) ($r['access_token'] ?? '');
}

/** Kullanıcı anahtarını kaydeder ve yönettiği sayfaları çeker. @return int|WP_Error sayfa sayısı */
function ec_fb_connect_user(string $userToken)
{
    $long = ec_fb_long_token($userToken);
    if (is_wp_error($long)) {
        return $long;
    }
    $me = ec_graph('GET', 'me', ['fields' => 'id,name', 'access_token' => $long]);
    if (is_wp_error($me)) {
        return $me;
    }
    $s = ec_social_settings();
    $s['user_token'] = ec_encrypt($long);
    $s['user_name'] = (string) $me['name'];
    $s['connected_at'] = ec_now();
    ec_social_save($s);
    return ec_fb_sync_pages();
}

/** Yöneticinin erişebildiği sayfaları (ve bağlı Instagram hesaplarını) çeker. @return int|WP_Error */
function ec_fb_sync_pages()
{
    global $wpdb;
    $tok = ec_decrypt(ec_social_settings()['user_token']);
    if (!$tok) {
        return new WP_Error('ec_fb', 'Önce “Facebook ile bağlan”');
    }
    $t = ec_table('social_accounts');
    $n = 0;
    $after = '';
    do {
        $r = ec_graph('GET', 'me/accounts', array_filter(['fields' => 'id,name,access_token,tasks,instagram_business_account{id,username}', 'limit' => 100, 'after' => $after, 'access_token' => $tok]));
        if (is_wp_error($r)) {
            return $r;
        }
        foreach (($r['data'] ?? []) as $p) {
            $row = [
                'page_name' => mb_substr((string) $p['name'], 0, 190),
                'ig_user_id' => $p['instagram_business_account']['id'] ?? null,
                'ig_username' => $p['instagram_business_account']['username'] ?? null,
                'token_enc' => ec_encrypt((string) $p['access_token']),
                'status' => 'ok', 'last_error' => null, 'updated_at' => ec_now(),
            ];
            if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE page_id = %s", $p['id']))) {
                $wpdb->update($t, $row, ['page_id' => $p['id']]);
            } else {
                $wpdb->insert($t, $row + ['page_id' => (string) $p['id'], 'agent_id' => null, 'created_at' => ec_now()]);
            }
            $n++;
        }
        $after = $r['paging']['cursors']['after'] ?? '';
    } while ($after && !empty($r['paging']['next']));
    return $n;
}

/** Elle sayfa anahtarı ekleme (yedek yol: Graph API Explorer'dan alınan sayfa anahtarı) @return int|WP_Error hesap no */
function ec_fb_add_page_token(string $pageToken)
{
    global $wpdb;
    $p = ec_graph('GET', 'me', ['fields' => 'id,name,instagram_business_account{id,username}', 'access_token' => $pageToken]);
    if (is_wp_error($p)) {
        return $p;
    }
    if (empty($p['id'])) {
        return new WP_Error('ec_fb', 'Bu bir sayfa anahtarı değil');
    }
    $t = ec_table('social_accounts');
    $row = ['page_name' => (string) $p['name'], 'ig_user_id' => $p['instagram_business_account']['id'] ?? null, 'ig_username' => $p['instagram_business_account']['username'] ?? null, 'token_enc' => ec_encrypt($pageToken), 'status' => 'ok', 'last_error' => null, 'updated_at' => ec_now()];
    $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE page_id = %s", $p['id']));
    if ($id) {
        $wpdb->update($t, $row, ['id' => $id]);
        return (int) $id;
    }
    $wpdb->insert($t, $row + ['page_id' => (string) $p['id'], 'agent_id' => null, 'created_at' => ec_now()]);
    return (int) $wpdb->insert_id;
}

function ec_social_accounts(?int $agent_id = null): array
{
    global $wpdb;
    $t = ec_table('social_accounts');
    $sql = $agent_id === null ? "SELECT * FROM $t ORDER BY (agent_id IS NULL) DESC, agent_id, page_name" : $wpdb->prepare("SELECT * FROM $t WHERE agent_id = %d ORDER BY page_name", $agent_id);
    return $wpdb->get_results($sql, ARRAY_A) ?: [];
}

function ec_social_account(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('social_accounts') . ' WHERE id = %d', $id), ARRAY_A);
    return $r ?: null;
}

/* ---------------- Paylaşım metni ---------------- */

function ec_social_default_template(string $lang): string
{
    return [
        'tr' => "🏡 {baslik}\n📍 {konum}\n💰 {fiyat}\n{bilgi}\n{ozellikler}\n\n{imza}\n👉 {link}\n\n{etiketler}",
        'en' => "🏡 {baslik}\n📍 {konum}\n💰 {fiyat}\n{bilgi}\n{ozellikler}\n\n{imza}\n👉 {link}\n\n{etiketler}",
        'ru' => "🏡 {baslik}\n📍 {konum}\n💰 {fiyat}\n{bilgi}\n{ozellikler}\n\n{imza}\n👉 {link}\n\n{etiketler}",
    ][$lang] ?? '';
}

function ec_social_tag(string $s): string
{
    return '#' . preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $s)));
}

/** Bir dilde gönderi metni. $network: facebook | instagram */
function ec_social_caption_one(array $row, string $lang, string $network, ?array $agent): string
{
    $it = ec_listings_present([$row], $lang, true)[0];
    $sym = ['GBP' => '£', 'EUR' => '€', 'USD' => '$', 'TRY' => '₺'][$it['currency']] ?? '';
    $suffix = ['rent' => ['tr' => ' / ay', 'en' => ' / month', 'ru' => ' / мес.'], 'daily' => ['tr' => ' / gece', 'en' => ' / night', 'ru' => ' / ночь']][$it['mode']][$lang] ?? '';
    $mode = ['sale' => ['tr' => 'Satılık', 'en' => 'For sale', 'ru' => 'Продажа'], 'rent' => ['tr' => 'Kiralık', 'en' => 'For rent', 'ru' => 'Аренда'], 'daily' => ['tr' => 'Günlük kiralık', 'en' => 'Holiday rental', 'ru' => 'Посуточно']][$it['mode']][$lang];
    $bilgi = implode(' · ', array_filter([
        $mode . ' ' . mb_strtolower($it['subtype_name']),
        $it['rooms'] ? '🛏 ' . $it['rooms'] : null,
        $it['area_m2'] ? '📐 ' . $it['area_m2'] . ' m²' : null,
        $it['deed_name'] ? '📜 ' . $it['deed_name'] : null,
        $it['pano_count'] ? '🌐 360°' : null,
    ]));
    $feats = array_slice($it['feature_names'], 0, 6);
    $site = preg_replace('~^https?://(www\.)?~', '', untrailingslashit(home_url()));
    $link = $network === 'instagram'
        ? ['tr' => "İlan no #{$it['id']} · $site (profildeki link)", 'en' => "Ref. #{$it['id']} · $site (link in bio)", 'ru' => "№ #{$it['id']} · $site (ссылка в профиле)"][$lang]
        : $it['url'];
    $tags = array_unique(array_filter([
        ec_social_tag($lang === 'ru' ? 'недвижимостькипр' : ($lang === 'en' ? 'northcyprusproperty' : 'kktcemlak')),
        ec_social_tag($lang === 'en' ? 'cyprusrealestate' : ($lang === 'ru' ? 'северныйкипр' : 'kıbrısemlak')),
        ec_social_tag($it['city_name']),
        $it['district'] && $it['district'] !== 'Merkez' ? ec_social_tag($it['district']) : null,
        ec_social_tag($it['subtype_name']),
        ec_social_tag($mode),
    ]));
    $tpl = ec_social_settings()['tpl_' . $lang] ?: ec_social_default_template($lang);
    $out = strtr($tpl, [
        '{baslik}' => $it['title'],
        '{konum}' => trim(($it['district'] ? $it['district'] . ', ' : '') . $it['city_name']),
        '{fiyat}' => $sym . number_format($it['price'], 0, ',', '.') . $suffix,
        '{bilgi}' => $bilgi,
        '{ozellikler}' => $feats ? '✨ ' . implode(', ', $feats) : '',
        '{imza}' => (string) ($agent['social_sign'] ?? '') ?: ($agent ? $agent['name'] . ($agent['phone'] ? ' · 📞 ' . $agent['phone'] : '') : get_bloginfo('name')),
        '{link}' => $link,
        '{ilan_no}' => '#' . $it['id'],
        '{etiketler}' => implode(' ', $tags),
        '{aciklama}' => wp_trim_words((string) $it['description'], 40, '…'),
    ]);
    return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace("/^[ \t]+$/m", '', $out)));
}

/** Üyenin dil tercihine göre metin (tr | en | ru | tr+en | all) */
function ec_social_caption(array $row, string $network, ?array $agent): string
{
    $pref = $agent['social_lang'] ?? 'tr+en';
    $langs = ['tr' => ['tr'], 'en' => ['en'], 'ru' => ['ru'], 'tr+en' => ['tr', 'en'], 'all' => ['tr', 'en', 'ru']][$pref] ?? ['tr'];
    $parts = array_map(fn($l) => ec_social_caption_one($row, $l, $network, $agent), $langs);
    // Birden çok dilde: link ilk dilde, etiketler en sonda bir kez
    $tags = [];
    foreach ($parts as $i => $p) {
        $lines = [];
        foreach (explode("\n", $p) as $ln) {
            if (str_starts_with(trim($ln), '#')) {
                $tags = array_merge($tags, preg_split('/\s+/', trim($ln)));
            } elseif (!($i > 0 && str_starts_with(trim($ln), '👉'))) {
                $lines[] = $ln;
            }
        }
        $parts[$i] = trim(implode("\n", $lines));
    }
    $txt = implode("\n\n— — —\n\n", $parts) . ($tags ? "\n\n" . implode(' ', array_slice(array_unique($tags), 0, 20)) : '');
    return $network === 'instagram' ? mb_substr($txt, 0, 2150) : $txt;
}

/* ---------------- Instagram için JPEG ---------------- */

/** İlan fotoğrafının Instagram'a uygun JPEG kopyası (en çok 1440 px, oran 4:5 ile 1.91:1 arası). @return string|null URL */
function ec_social_jpeg(string $path): ?string
{
    $b = ec_photo_base();
    $dst = $b['dir'] . '/' . $path . '-ig.jpg';
    if (!file_exists($dst)) {
        $src = $b['dir'] . '/' . $path . '-l.webp';
        if (!file_exists($src) || !function_exists('imagecreatefromwebp')) {
            return null;
        }
        $im = @imagecreatefromwebp($src);
        if (!$im) {
            return null;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $r = $w / $h;
        [$cx, $cy, $cw, $ch] = [0, 0, $w, $h];
        if ($r > 1.91) {
            $cw = (int) round($h * 1.91);
            $cx = (int) (($w - $cw) / 2);
        } elseif ($r < 0.8) {
            $ch = (int) round($w / 0.8);
            $cy = (int) (($h - $ch) / 2);
        }
        $scale = min(1, 1440 / $cw);
        $out = imagecreatetruecolor((int) round($cw * $scale), (int) round($ch * $scale));
        imagecopyresampled($out, $im, 0, 0, $cx, $cy, imagesx($out), imagesy($out), $cw, $ch);
        imagejpeg($out, $dst, 88);
        imagedestroy($im);
        imagedestroy($out);
    }
    return $b['url'] . '/' . $path . '-ig.jpg';
}

/* ---------------- Paylaşım ---------------- */

/** @return array{id: string, permalink: ?string}|WP_Error */
function ec_social_post_facebook(array $acc, array $row, string $caption, array $photoUrls)
{
    $tok = ec_decrypt($acc['token_enc']);
    $it = ec_listings_present([$row], 'tr')[0];
    if ($photoUrls) {
        $media = [];
        foreach ($photoUrls as $u) {
            $r = ec_graph('POST', $acc['page_id'] . '/photos', ['url' => $u, 'published' => 'false', 'access_token' => $tok], 60);
            if (is_wp_error($r)) {
                return $r;
            }
            $media[] = $r['id'];
        }
        $params = ['message' => $caption, 'access_token' => $tok];
        foreach ($media as $i => $m) {
            $params["attached_media[$i]"] = wp_json_encode(['media_fbid' => $m]);
        }
        $r = ec_graph('POST', $acc['page_id'] . '/feed', $params);
    } else {
        $r = ec_graph('POST', $acc['page_id'] . '/feed', ['message' => $caption, 'link' => $it['url'], 'access_token' => $tok]);
    }
    if (is_wp_error($r)) {
        return $r;
    }
    $pl = ec_graph('GET', $r['id'], ['fields' => 'permalink_url', 'access_token' => $tok]);
    return ['id' => (string) $r['id'], 'permalink' => is_wp_error($pl) ? null : ($pl['permalink_url'] ?? null)];
}

/** Facebook'ta çevrilebilir 360° fotoğraf gönderisi (ilan başına bir tane) */
function ec_social_post_facebook_360(array $acc, array $row, array $pano, ?array $owner): void
{
    $url = ec_pano_jpeg($pano);
    if (!$url) {
        return;
    }
    $it = ec_listings_present([$row], 'tr')[0];
    $meta = wp_json_encode([
        'ProjectionType' => 'equirectangular',
        'CroppedAreaImageWidthPixels' => $pano['width'], 'CroppedAreaImageHeightPixels' => $pano['height'],
        'FullPanoWidthPixels' => $pano['width'], 'FullPanoHeightPixels' => $pano['height'],
        'CroppedAreaLeftPixels' => 0, 'CroppedAreaTopPixels' => 0,
    ]);
    ec_graph('POST', $acc['page_id'] . '/photos', [
        'url' => $url, 'allow_spherical_photo' => 'true', 'spherical_metadata' => $meta,
        'caption' => '🌐 360° · ' . $it['title'] . ($pano['title'] ? ' — ' . $pano['title'] : '') . "\n👉 " . $it['url'],
        'access_token' => ec_decrypt($acc['token_enc']),
    ], 90);
}

/** @return array{id: string, permalink: ?string}|WP_Error */
function ec_social_post_instagram(array $acc, string $caption, array $jpgUrls)
{
    $tok = ec_decrypt($acc['token_enc']);
    $ig = $acc['ig_user_id'];
    if (!$jpgUrls) {
        return new WP_Error('ec_ig_nophoto', 'Instagram fotoğrafsız gönderi kabul etmez', ['skip' => true]);
    }
    if (count($jpgUrls) === 1) {
        $c = ec_graph('POST', "$ig/media", ['image_url' => $jpgUrls[0], 'caption' => $caption, 'access_token' => $tok], 60);
    } else {
        $kids = [];
        foreach ($jpgUrls as $u) {
            $k = ec_graph('POST', "$ig/media", ['image_url' => $u, 'is_carousel_item' => 'true', 'access_token' => $tok], 60);
            if (is_wp_error($k)) {
                return $k;
            }
            $kids[] = $k['id'];
        }
        $c = ec_graph('POST', "$ig/media", ['media_type' => 'CAROUSEL', 'children' => implode(',', $kids), 'caption' => $caption, 'access_token' => $tok], 60);
    }
    if (is_wp_error($c)) {
        return $c;
    }
    // Instagram görselleri işleyene kadar bekle (en çok ~40 sn)
    for ($i = 0; $i < 8; $i++) {
        $st = ec_graph('GET', $c['id'], ['fields' => 'status_code', 'access_token' => $tok]);
        $code = is_wp_error($st) ? '' : ($st['status_code'] ?? '');
        if ($code === 'FINISHED') {
            break;
        }
        if ($code === 'ERROR') {
            return new WP_Error('ec_ig', 'Instagram görseli işleyemedi (fotoğraf adresi herkese açık mı?)');
        }
        sleep(min(5, 1 + $i));
    }
    $p = ec_graph('POST', "$ig/media_publish", ['creation_id' => $c['id'], 'access_token' => $tok], 60);
    if (is_wp_error($p)) {
        return $p;
    }
    $pl = ec_graph('GET', $p['id'], ['fields' => 'permalink', 'access_token' => $tok]);
    return ['id' => (string) $p['id'], 'permalink' => is_wp_error($pl) ? null : ($pl['permalink'] ?? null)];
}

/**
 * İlanı kuyruğa ekler: sitenin hesapları + (paketi ve otomatik izni varsa) üyenin hesapları.
 * $manual: üye "Paylaş" düğmesine bastı (otomatik ayarından bağımsız)
 * @return int kuyruğa eklenen gönderi sayısı
 */
function ec_social_queue(int $listing_id, bool $manual = false, bool $onlyAgent = false): int
{
    global $wpdb;
    $row = ec_listing_row($listing_id);
    if (!$row || $row['status'] !== 'published') {
        return 0;
    }
    $s = ec_social_settings();
    $accs = [];
    if (!$onlyAgent && $s['site_auto'] && $row['source'] !== 'demo') {
        $accs = array_merge($accs, ec_social_accounts(0));
    }
    $agent = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    if ($agent && ($manual || !empty($agent['social_auto'])) && ec_member_can($agent, 'social')) {
        $accs = array_merge($accs, ec_social_accounts((int) $agent['id']));
    }
    $n = 0;
    foreach ($accs as $a) {
        if ($a['status'] !== 'ok') {
            continue;
        }
        foreach (['facebook' => $a['fb_on'], 'instagram' => $a['ig_on'] && $a['ig_user_id']] as $net => $on) {
            if (!$on) {
                continue;
            }
            $n += (int) $wpdb->query($wpdb->prepare(
                'INSERT IGNORE INTO ' . ec_table('social_posts') . " (listing_id, account_id, network, status, created_at, updated_at) VALUES (%d, %d, %s, 'queued', %s, %s)",
                $listing_id, $a['id'], $net, ec_now(), ec_now()
            ));
        }
    }
    if ($n && !wp_next_scheduled('ec_social_run')) {
        wp_schedule_single_event(time() + 45, 'ec_social_run'); // fotoğraflar yüklensin diye kısa bekleme
    }
    return $n;
}

/** Kuyruktaki gönderileri paylaşır (zamanlanmış görev) */
function ec_social_run(int $limit = 6): array
{
    global $wpdb;
    if (get_transient('ec_social_lock')) {
        return [];
    }
    set_transient('ec_social_lock', 1, 5 * MINUTE_IN_SECONDS);
    @set_time_limit(300);
    $t = ec_table('social_posts');
    $done = [];
    $rows = $wpdb->get_results("SELECT * FROM $t WHERE status = 'queued' AND (attempts = 0 OR updated_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL POW(2, attempts) * 10 MINUTE)) ORDER BY id LIMIT " . (int) $limit, ARRAY_A);
    foreach ($rows as $p) {
        $done[] = ec_social_process($p);
    }
    delete_transient('ec_social_lock');
    if ($wpdb->get_var("SELECT id FROM $t WHERE status = 'queued' LIMIT 1") && !wp_next_scheduled('ec_social_run')) {
        wp_schedule_single_event(time() + 120, 'ec_social_run');
    }
    return $done;
}
add_action('ec_social_run', 'ec_social_run');

/** Tek gönderi. @return array sonuç */
function ec_social_process(array $p): array
{
    global $wpdb;
    $t = ec_table('social_posts');
    $set = function (array $f) use ($wpdb, $t, $p) {
        $wpdb->update($t, $f + ['updated_at' => ec_now()], ['id' => $p['id']]);
    };
    $acc = ec_social_account((int) $p['account_id']);
    $row = ec_listing_row((int) $p['listing_id']);
    if (!$acc || !$row || $row['status'] !== 'published') {
        $set(['status' => 'skipped', 'error' => 'İlan yayında değil ya da hesap silindi']);
        return ['id' => $p['id'], 'status' => 'skipped'];
    }
    $agent = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    // Üyenin kendi hesabına paylaşım paketten düşer (sitenin hesabı ücretsiz)
    if ((int) $acc['agent_id'] > 0) {
        $q = $agent ? ec_quota_use($agent, (int) $row['id'], 'social') : new WP_Error('x', 'Üye yok');
        if (is_wp_error($q)) {
            $set(['status' => 'skipped', 'error' => $q->get_error_message()]);
            return ['id' => $p['id'], 'status' => 'skipped'];
        }
    }
    $owner = (int) $acc['agent_id'] > 0 ? ec_agent((int) $acc['agent_id']) : null;
    $caption = ec_social_caption($row, $p['network'], $owner);
    $max = max(1, min(10, (int) ec_social_settings()['max_photos']));
    $photos = $wpdb->get_col($wpdb->prepare('SELECT path FROM ' . ec_table('photos') . ' WHERE listing_id = %d ORDER BY sort, id LIMIT %d', $row['id'], $max));
    $urls = array_values(array_filter(array_map('ec_social_jpeg', $photos)));
    $panos = function_exists('ec_panos') ? ec_panos((int) $row['id']) : [];
    if ($p['network'] === 'instagram' && $panos && count($urls) < 10 && ($flat = ec_pano_flat($panos[0]))) {
        $urls[] = $flat; // Instagram 360° göstermez: ortadan düz bir kesit eklenir
    }
    $res = $p['network'] === 'instagram' ? ec_social_post_instagram($acc, $caption, $urls) : ec_social_post_facebook($acc, $row, $caption, $urls);
    if (!is_wp_error($res) && $p['network'] === 'facebook' && $panos && ec_social_settings()['fb_360']) {
        ec_social_post_facebook_360($acc, $row, $panos[0], $owner);
    }
    if (is_wp_error($res)) {
        $d = (array) $res->get_error_data();
        $err = mb_substr($res->get_error_message(), 0, 255);
        if (!empty($d['token'])) {
            $wpdb->update(ec_table('social_accounts'), ['status' => 'expired', 'last_error' => $err, 'updated_at' => ec_now()], ['id' => $acc['id']]);
            ec_notify_admin("⚠️ Sosyal medya bağlantısı koptu: {$acc['page_name']}\n$err", admin_url('admin.php?page=ec-social'));
        }
        $retry = !empty($d['retry']) && (int) $p['attempts'] < 4;
        $set(['status' => $retry ? 'queued' : (!empty($d['skip']) ? 'skipped' : 'failed'), 'attempts' => (int) $p['attempts'] + 1, 'error' => $err]);
        return ['id' => $p['id'], 'status' => 'failed', 'error' => $err];
    }
    $set(['status' => 'done', 'remote_id' => $res['id'], 'permalink' => $res['permalink'], 'error' => null, 'attempts' => (int) $p['attempts'] + 1]);
    if ($owner && !empty($owner['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
        ec_tg_send($owner['telegram_chat_id'], '📣 #' . $row['id'] . ' ' . ($p['network'] === 'instagram' ? 'Instagram' : 'Facebook') . '’da paylaşıldı.' . ($res['permalink'] ? "\n" . $res['permalink'] : ''));
    }
    return ['id' => $p['id'], 'status' => 'done', 'permalink' => $res['permalink']];
}

/** İlan yayına girince kuyruğa al */
add_action('ec_listing_published', function (int $id) {
    ec_social_queue($id);
});

function ec_social_recent(?int $agent_id, int $limit = 30): array
{
    global $wpdb;
    $p = ec_table('social_posts');
    $a = ec_table('social_accounts');
    $w = $agent_id === null ? '1=1' : $wpdb->prepare('a.agent_id = %d', $agent_id);
    return $wpdb->get_results("SELECT p.*, a.page_name, a.ig_username, a.agent_id FROM $p p JOIN $a a ON a.id = p.account_id WHERE $w ORDER BY p.id DESC LIMIT " . (int) $limit, ARRAY_A) ?: [];
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/me/social', [
        [
            'methods' => 'GET',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                $a = ec_rest_agent($r);
                if (!$a) {
                    return new WP_Error('ec_no_agent', 'Üye hesabı gerekli', ['status' => 400]);
                }
                return [
                    'allowed' => ec_member_can($a, 'social'),
                    'auto' => (bool) $a['social_auto'], 'lang' => $a['social_lang'], 'sign' => (string) $a['social_sign'],
                    'accounts' => array_map(fn($x) => ['page' => $x['page_name'], 'instagram' => $x['ig_username'], 'status' => $x['status']], ec_social_accounts((int) $a['id'])),
                    'recent' => array_map(fn($x) => ['listing_id' => (int) $x['listing_id'], 'network' => $x['network'], 'status' => $x['status'], 'permalink' => $x['permalink'], 'error' => $x['error'], 'at' => $x['updated_at']], ec_social_recent((int) $a['id'], 20)),
                ];
            },
        ],
        [
            'methods' => 'PATCH',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                global $wpdb;
                $a = ec_rest_agent($r);
                $b = $r->get_json_params() ?: [];
                $d = [];
                if (array_key_exists('auto', $b)) {
                    $d['social_auto'] = $b['auto'] ? 1 : 0;
                }
                if (isset($b['lang']) && in_array($b['lang'], ['tr', 'en', 'ru', 'tr+en', 'all'], true)) {
                    $d['social_lang'] = $b['lang'];
                }
                if (array_key_exists('sign', $b)) {
                    $d['social_sign'] = mb_substr(ec_clean_text((string) $b['sign']), 0, 255) ?: null;
                }
                if ($a && $d) {
                    $wpdb->update(ec_table('agents'), $d + ['updated_at' => ec_now()], ['id' => $a['id']]);
                }
                return ['ok' => true];
            },
        ],
    ]);
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/share', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            $row = ec_listing_row((int) $r['id']);
            $a = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
            if (!current_user_can('manage_options') && !ec_member_can($a, 'social')) {
                return new WP_Error('ec_no_package', 'Sosyal medya paylaşımı paketinizde yok', ['status' => 402]);
            }
            if ($row['status'] !== 'published') {
                return new WP_Error('ec_invalid', 'Sadece yayındaki ilanlar paylaşılır', ['status' => 400]);
            }
            $n = ec_social_queue((int) $r['id'], true, !current_user_can('manage_options'));
            return ['queued' => $n, 'message' => $n ? 'Paylaşım sıraya alındı; 1-2 dakika içinde yayında.' : 'Bu ilan bu hesaplarda zaten paylaşıldı ya da bağlı hesap yok.'];
        },
    ]);
});
