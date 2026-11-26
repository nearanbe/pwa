<?php
/**
 * Anlık bildirim (Web Push) ve ziyaretçi uyarıları (1.4).
 *
 * - Kütüphanesiz Web Push (RFC 8291 aes128gcm + RFC 8292 VAPID), yalnızca OpenSSL. VAPID anahtarı `ec_vapid` seçeneğinde.
 * - ec_push_subs: tarayıcı abonelikleri (ziyaretçi ya da üye). Tarayıcı kendi aboneliğini {id, k} ile tanıtır
 *   (k = HMAC; çerez/hesap gerekmez).
 * - ec_alerts: ziyaretçi uyarıları. kind = price (ilanın fiyatı düşerse) | search (kayıtlı aramaya yeni ilan).
 *   Bildirim kanalı: bu cihaz (sub_id) ya da e-posta (onay bağlantısıyla, confirmed=1 olunca etkin).
 *   Her uyarının gizli `token`'ı vardır: /?ec_alert_off=TOKEN bağlantısı uyarıyı kapatır.
 * - Fiyat geçmişi: ec_price_history (ilan açılınca ve fiyat her değiştiğinde bir satır).
 *
 * REST (emlak/v1 = /api/v1): GET push/key · POST push/sub · POST alerts · GET alerts?sub=&k= · DELETE alerts/{token}
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ================= Web Push çekirdeği ================= */

function ec_b64u(string $v): string
{
    return rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
}

function ec_b64u_dec(string $v): string
{
    $v = strtr($v, '-_', '+/');
    if ($r = strlen($v) % 4) {
        $v .= str_repeat('=', 4 - $r);
    }
    return (string) base64_decode($v, true);
}

function ec_push_supported(): bool
{
    return function_exists('openssl_pkey_derive') && function_exists('hash_hkdf') && in_array('aes-128-gcm', openssl_get_cipher_methods(), true);
}

function ec_p256_pub_pem(string $raw): string
{
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function ec_p256_raw_pub($key): string
{
    $d = openssl_pkey_get_details($key);
    if (!$d || empty($d['ec']['x'])) {
        return '';
    }
    return "\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
}

function ec_p256_new()
{
    return openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
}

/** Sitenin VAPID anahtarları (ilk ihtiyaçta bir kez üretilir) */
function ec_vapid(): array
{
    $v = get_option('ec_vapid');
    if (is_array($v) && !empty($v['priv']) && !empty($v['pub'])) {
        return $v;
    }
    if (!ec_push_supported()) {
        return [];
    }
    $k = ec_p256_new();
    if (!$k || !openssl_pkey_export($k, $pem)) {
        return [];
    }
    $v = ['priv' => $pem, 'pub' => ec_b64u(ec_p256_raw_pub($k))];
    update_option('ec_vapid', $v, false);
    return $v;
}

function ec_der_to_raw(string $der): string
{
    $o = 2;
    if (ord($der[1]) & 0x80) {
        $o += ord($der[1]) & 0x7f;
    }
    $parts = [];
    for ($i = 0; $i < 2; $i++) {
        $len = ord($der[$o + 1]);
        $parts[] = str_pad(ltrim(substr($der, $o + 2, $len), "\0"), 32, "\0", STR_PAD_LEFT);
        $o += 2 + $len;
    }
    return $parts[0] . $parts[1];
}

/** @return string|false aes128gcm gövde */
function ec_push_encrypt(string $payload, string $uaPub, string $auth)
{
    if (strlen($uaPub) !== 65 || strlen($auth) !== 16) {
        return false;
    }
    $asPriv = ec_p256_new();
    $asPub = ec_p256_raw_pub($asPriv);
    $salt = random_bytes(16);
    $uaKey = openssl_pkey_get_public(ec_p256_pub_pem($uaPub));
    if (!$asPriv || !$uaKey) {
        return false;
    }
    $shared = openssl_pkey_derive($uaKey, $asPriv, 32);
    if ($shared === false) {
        return false;
    }
    $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPub . $asPub, $auth);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
    $tag = '';
    $enc = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    return $enc === false ? false : $salt . pack('N', 4096) . chr(65) . $asPub . $enc . $tag;
}

/** Yalnızca bilinen tarayıcı bildirim sunucuları */
function ec_push_endpoint_ok(string $url): bool
{
    $p = wp_parse_url($url);
    if (empty($p['scheme']) || $p['scheme'] !== 'https' || empty($p['host'])) {
        return false;
    }
    $h = strtolower($p['host']);
    foreach (['fcm.googleapis.com', 'android.googleapis.com', 'push.services.mozilla.com', 'notify.windows.com', 'push.apple.com'] as $ok) {
        if ($h === $ok || str_ends_with($h, '.' . $ok)) {
            return true;
        }
    }
    return false;
}

function ec_vapid_header(string $endpoint): string
{
    $v = ec_vapid();
    if (!$v) {
        return '';
    }
    $p = wp_parse_url($endpoint);
    $mail = sanitize_email((string) get_option('admin_email'));
    $body = ec_b64u(wp_json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.' . ec_b64u(wp_json_encode([
        'aud' => $p['scheme'] . '://' . $p['host'],
        'exp' => time() + 12 * HOUR_IN_SECONDS,
        'sub' => $mail ? 'mailto:' . $mail : home_url('/'),
    ]));
    $sig = '';
    if (!openssl_sign($body, $sig, openssl_pkey_get_private($v['priv']), OPENSSL_ALGO_SHA256)) {
        return '';
    }
    return 'vapid t=' . $body . '.' . ec_b64u(ec_der_to_raw($sig)) . ', k=' . $v['pub'];
}

/**
 * Bir aboneliğe bildirim gönderir. $msg = [title, body, url, tag]
 * @return string ok | gone (abonelik silindi) | error
 */
function ec_push_send(array $sub, array $msg): string
{
    global $wpdb;
    if (empty($sub['endpoint']) || !ec_push_endpoint_ok($sub['endpoint'])) {
        return 'gone';
    }
    $body = ec_push_encrypt(wp_json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ec_b64u_dec($sub['p256dh']), ec_b64u_dec($sub['auth']));
    $authz = ec_vapid_header($sub['endpoint']);
    if ($body === false || $authz === '') {
        return 'error';
    }
    if (apply_filters('ec_push_dry_run', false, $sub, $msg)) {
        return 'ok';
    }
    $res = wp_safe_remote_post($sub['endpoint'], [
        'timeout' => 8,
        'headers' => ['Content-Type' => 'application/octet-stream', 'Content-Encoding' => 'aes128gcm', 'TTL' => '86400', 'Urgency' => 'normal', 'Authorization' => $authz],
        'body' => $body,
    ]);
    if (is_wp_error($res)) {
        update_option('ec_push_last_error', gmdate('Y-m-d H:i') . ' ' . $res->get_error_message(), false);
        return 'error';
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    if (in_array($code, [404, 410], true)) {
        if (!empty($sub['id'])) {
            $wpdb->delete(ec_table('push_subs'), ['id' => (int) $sub['id']]);
            $wpdb->update(ec_table('alerts'), ['sub_id' => null], ['sub_id' => (int) $sub['id']]);
        }
        return 'gone';
    }
    if ($code < 200 || $code >= 300) {
        update_option('ec_push_last_error', gmdate('Y-m-d H:i') . ' HTTP ' . $code . ' ' . substr((string) wp_remote_retrieve_body($res), 0, 200), false);
        return 'error';
    }
    return 'ok';
}

function ec_push_sub_row(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('push_subs') . ' WHERE id = %d', $id), ARRAY_A);
    return $r ?: null;
}

/** Tarayıcının kendi aboneliğini kanıtladığı anahtar */
function ec_push_sub_key(int $id, string $epHash): string
{
    return substr(hash_hmac('sha256', $id . '|' . $epHash, wp_salt('auth')), 0, 32);
}

function ec_push_sub_auth($id, $k): ?array
{
    $row = ec_push_sub_row((int) $id);
    return $row && hash_equals(ec_push_sub_key((int) $row['id'], $row['ep_hash']), (string) $k) ? $row : null;
}

/** Bir üyenin (ofis/danışman) tüm cihazlarına bildirim */
function ec_push_agent(int $agent_id, array $msg): int
{
    global $wpdb;
    $n = 0;
    foreach ($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('push_subs') . ' WHERE agent_id = %d', $agent_id), ARRAY_A) as $s) {
        $n += ec_push_send($s, $msg) === 'ok' ? 1 : 0;
    }
    return $n;
}

/* ================= Metinler (bildirim ve e-posta) ================= */

function ec_alert_t(string $key, string $lang, ...$args): string
{
    $s = [
        'price_title' => ['🔻 Fiyat düştü', '🔻 Price reduced', '🔻 Цена снижена', '🔻 Preis gesenkt', '🔻 انخفض السعر'],
        'price_body' => ['%1$s: %2$s → %3$s', '%1$s: %2$s → %3$s', '%1$s: %2$s → %3$s', '%1$s: %2$s → %3$s', '%1$s: %2$s → %3$s'],
        'search_title' => ['🏠 Yeni ilan', '🏠 New listings', '🏠 Новые объявления', '🏠 Neue Inserate', '🏠 إعلانات جديدة'],
        'search_body' => ['Aradığınız kriterlerde %d yeni ilan eklendi. Göz atmak ister misiniz?', '%d new listings match your saved search. Want to take a look?', 'По вашему поиску добавлено новых объявлений: %d. Посмотрите?', '%d neue Inserate passen zu Ihrer Suche. Jetzt ansehen?', 'تمت إضافة %d إعلانات جديدة تطابق بحثك. هل تريد الاطلاع عليها؟'],
        'on_title' => ['🔔 Bildirimler açık', '🔔 Notifications on', '🔔 Уведомления включены', '🔔 Benachrichtigungen an', '🔔 تم تفعيل الإشعارات'],
        'on_price' => ['Fiyat düşerse bu cihaza haber vereceğiz.', 'We will notify this device if the price drops.', 'Сообщим на это устройство, если цена снизится.', 'Wir benachrichtigen dieses Gerät, wenn der Preis sinkt.', 'سنُعلمك على هذا الجهاز إذا انخفض السعر.'],
        'on_search' => ['Bu aramaya yeni ilan gelince bu cihaza haber vereceğiz.', 'We will notify this device when new listings match this search.', 'Сообщим на это устройство о новых объявлениях по этому поиску.', 'Wir benachrichtigen dieses Gerät bei neuen passenden Inseraten.', 'سنُعلمك على هذا الجهاز عند وصول إعلانات جديدة تطابق هذا البحث.'],
        'mail_confirm_subj' => ['E-posta bildirimini onaylayın', 'Confirm your email alert', 'Подтвердите уведомление по e-mail', 'Bestätigen Sie Ihre E-Mail-Benachrichtigung', 'أكّد تنبيه البريد الإلكتروني'],
        'mail_confirm' => ["Merhaba,\n\n%1\$s için e-posta bildirimi istendi.\nOnaylamak için tıklayın:\n%2\$s\n\nBu isteği siz yapmadıysanız bu e-postayı dikkate almayın.", "Hello,\n\nAn email alert was requested for: %1\$s\nClick to confirm:\n%2\$s\n\nIf you did not request this, ignore this email.", "Здравствуйте!\n\nЗапрошено уведомление по e-mail: %1\$s\nДля подтверждения нажмите:\n%2\$s\n\nЕсли это были не вы, просто проигнорируйте письмо.", "Hallo,\n\nEs wurde eine E-Mail-Benachrichtigung angefordert für: %1\$s\nZum Bestätigen klicken:\n%2\$s\n\nFalls Sie das nicht waren, ignorieren Sie diese E-Mail.", "مرحبًا،\n\nتم طلب تنبيه بالبريد الإلكتروني لـ: %1\$s\nاضغط للتأكيد:\n%2\$s\n\nإذا لم تطلب ذلك فتجاهل هذه الرسالة."],
        'mail_off' => ['Bu bildirimleri durdurmak için: %s', 'To stop these alerts: %s', 'Отписаться от уведомлений: %s', 'Benachrichtigungen beenden: %s', 'لإيقاف هذه التنبيهات: %s'],
        'ok_page' => ['E-posta bildirimi açıldı.', 'Email alert confirmed.', 'Уведомление по e-mail подтверждено.', 'E-Mail-Benachrichtigung bestätigt.', 'تم تأكيد تنبيه البريد الإلكتروني.'],
        'off_page' => ['Bildirim kapatıldı. Bir daha bu konuda bildirim almayacaksınız.', 'Alert turned off. You will not receive it again.', 'Уведомление отключено.', 'Benachrichtigung deaktiviert.', 'تم إيقاف التنبيه.'],
        'bad_link' => ['Bağlantı geçersiz ya da süresi dolmuş.', 'This link is invalid or has expired.', 'Ссылка недействительна или устарела.', 'Dieser Link ist ungültig oder abgelaufen.', 'هذا الرابط غير صالح أو منتهي الصلاحية.'],
        'search_label' => ['Kayıtlı arama', 'Saved search', 'Сохранённый поиск', 'Gespeicherte Suche', 'بحث محفوظ'],
    ][$key] ?? [$key];
    $idx = ['tr' => 0, 'en' => 1, 'ru' => 2, 'de' => 3, 'ar' => 4][$lang] ?? 0;
    $txt = $s[$idx] ?? $s[1] ?? $s[0];
    return $args ? vsprintf($txt, $args) : $txt;
}

function ec_alert_money(float $gbp, string $currency): string
{
    $sym = ['GBP' => '£', 'EUR' => '€', 'USD' => '$', 'TRY' => '₺'][$currency] ?? '£';
    $rate = (float) (ec_rates()[$currency] ?? 1) ?: 1;
    return $sym . number_format($currency === 'GBP' ? $gbp : $gbp * $rate, 0, ',', '.');
}

/* ================= Uyarılar ================= */

function ec_alert_langs(): array
{
    return defined('EC_LANGS') ? EC_LANGS : ['tr', 'en', 'ru'];
}

/** Kayıtlı aramada izin verilen filtreler */
function ec_alert_clean_query(array $q): array
{
    $out = [];
    foreach (['mode', 'city', 'ptype', 'subtype', 'district', 'deed_type', 'min', 'max', 'amin', 'amax', 'baths', 'furnished', 'q', 'currency', 'pano'] as $k) {
        if (isset($q[$k]) && !is_array($q[$k]) && $q[$k] !== '') {
            $out[$k] = mb_substr(sanitize_text_field((string) $q[$k]), 0, 80);
        }
    }
    foreach (['rooms', 'feat', 'kind'] as $k) {
        if (!empty($q[$k]) && is_array($q[$k])) {
            $out[$k] = array_slice(array_values(array_map(fn($v) => mb_substr(sanitize_text_field((string) $v), 0, 40), $q[$k])), 0, 12);
        }
    }
    $out['mode'] = in_array($out['mode'] ?? 'sale', ec_modes(), true) ? ($out['mode'] ?? 'sale') : 'sale';
    if (!empty($out['city']) && !isset(ec_cities()[$out['city']])) {
        unset($out['city']);
    }
    return $out;
}

/**
 * Uyarı oluşturur. $in: kind, listing_id | query+url, lang, ve kanal: sub_id+k ya da email
 * @return array|WP_Error
 */
function ec_alert_create(array $in)
{
    global $wpdb;
    $kind = (string) ($in['kind'] ?? '');
    if (!in_array($kind, ['price', 'search'], true)) {
        return new WP_Error('ec_invalid', 'Geçersiz istek', ['status' => 400]);
    }
    $lang = in_array($in['lang'] ?? '', ec_alert_langs(), true) ? $in['lang'] : 'tr';
    $sub = !empty($in['sub']) ? ec_push_sub_auth($in['sub'], $in['k'] ?? '') : null;
    $email = sanitize_email((string) ($in['email'] ?? ''));
    if (!$sub && !is_email($email)) {
        return new WP_Error('ec_invalid', 'Bildirim için bu cihazda izin verin ya da e-posta adresinizi yazın', ['status' => 400, 'fields' => ['email' => 'geçersiz']]);
    }
    if (!empty($in['website'])) {
        return new WP_Error('ec_spam', 'Gönderilemedi', ['status' => 400]);
    }
    if (ec_rate_limited('alert', $sub ? 40 : 8, HOUR_IN_SECONDS)) {
        return new WP_Error('ec_too_many', 'Çok fazla istek, lütfen daha sonra deneyin', ['status' => 429]);
    }
    $t = ec_table('alerts');
    $row = ['kind' => $kind, 'lang' => $lang, 'created_at' => ec_now(), 'token' => wp_generate_password(32, false, false)];
    if ($kind === 'price') {
        $l = ec_listing_row((int) ($in['listing_id'] ?? 0));
        if (!$l || $l['status'] !== 'published') {
            return new WP_Error('ec_not_found', 'İlan bulunamadı', ['status' => 404]);
        }
        $row['listing_id'] = (int) $l['id'];
        $row['last_value'] = (float) $l['price_gbp'];
        $row['label'] = mb_substr(ec_listings_present([$l], $lang)[0]['title'], 0, 190);
    } else {
        $q = ec_alert_clean_query((array) ($in['query'] ?? []));
        $url = esc_url_raw((string) ($in['url'] ?? ''));
        if (!$url || wp_parse_url($url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
            $url = ec_url_section($q['mode'], $lang, $q['city'] ?? null);
        }
        $row['query'] = wp_json_encode(['f' => $q, 'url' => remove_query_arg(['sayfa', 'gorunum'], $url)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['label'] = mb_substr(sanitize_text_field((string) ($in['label'] ?? '')) ?: ec_alert_t('search_label', $lang), 0, 190);
        $row['last_check'] = ec_now();
    }
    if ($sub) {
        // Aynı cihaz + aynı ilan / arama: tekrar oluşturma
        $dupSql = $kind === 'price'
            ? $wpdb->prepare("SELECT * FROM $t WHERE kind = 'price' AND sub_id = %d AND listing_id = %d", $sub['id'], $row['listing_id'])
            : $wpdb->prepare("SELECT * FROM $t WHERE kind = 'search' AND sub_id = %d AND query = %s", $sub['id'], $row['query']);
        if ($dup = $wpdb->get_row($dupSql, ARRAY_A)) {
            return ['token' => $dup['token'], 'channel' => 'push', 'confirmed' => true];
        }
        $row['sub_id'] = (int) $sub['id'];
        $row['confirmed'] = 1;
        if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE sub_id = %d", $sub['id'])) >= 60) {
            return new WP_Error('ec_too_many', 'Bu cihazda çok fazla bildirim var', ['status' => 429]);
        }
    } else {
        $row['email'] = $email;
        $row['confirmed'] = 0;
        if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE email = %s", $email)) >= 40) {
            return new WP_Error('ec_too_many', 'Bu e-posta için çok fazla bildirim var', ['status' => 429]);
        }
    }
    $wpdb->insert($t, $row);
    if ($sub) {
        ec_push_send($sub, ['title' => ec_alert_t('on_title', $lang), 'body' => ec_alert_t($kind === 'price' ? 'on_price' : 'on_search', $lang), 'url' => '', 'tag' => 'ec-on']);
        return ['token' => $row['token'], 'channel' => 'push', 'confirmed' => true];
    }
    $link = add_query_arg('ec_alert_ok', $row['token'], home_url('/'));
    wp_mail($email, get_bloginfo('name') . ': ' . ec_alert_t('mail_confirm_subj', $lang), ec_alert_t('mail_confirm', $lang, $row['label'], $link));
    return ['token' => $row['token'], 'channel' => 'email', 'confirmed' => false];
}

/** Bir uyarıya bildirimi gönderir (push ya da e-posta) */
function ec_alert_notify(array $al, string $title, string $body, string $url): bool
{
    if (!empty($al['sub_id']) && ($sub = ec_push_sub_row((int) $al['sub_id']))) {
        return ec_push_send($sub, ['title' => $title, 'body' => $body, 'url' => $url, 'tag' => 'ec-al-' . $al['id']]) === 'ok';
    }
    if (!empty($al['email']) && (int) $al['confirmed'] === 1) {
        $off = add_query_arg('ec_alert_off', $al['token'], home_url('/'));
        return (bool) wp_mail($al['email'], get_bloginfo('name') . ': ' . $title, $body . "\n\n" . $url . "\n\n— \n" . ec_alert_t('mail_off', $al['lang'], $off));
    }
    return false;
}

/* ---- Fiyat geçmişi ve fiyat düşüşü ---- */

function ec_price_history_add(int $listing_id, float $price, string $currency, float $gbp): void
{
    global $wpdb;
    $wpdb->insert(ec_table('price_history'), ['listing_id' => $listing_id, 'price' => $price, 'currency' => $currency, 'price_gbp' => $gbp, 'created_at' => ec_now()]);
}

function ec_price_history(int $listing_id): array
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare('SELECT price, currency, price_gbp, created_at FROM ' . ec_table('price_history') . ' WHERE listing_id = %d ORDER BY id ASC', $listing_id), ARRAY_A) ?: [];
}

/** Son 30 günde fiyat düştüyse [eski_gbp, yeni_gbp, tarih], yoksa null */
function ec_price_drop(int $listing_id, int $days = 30): ?array
{
    $h = ec_price_history($listing_id);
    if (count($h) < 2) {
        return null;
    }
    $last = end($h);
    $prev = $h[count($h) - 2];
    if ((float) $last['price_gbp'] < (float) $prev['price_gbp'] - 0.5 && strtotime($last['created_at'] . ' UTC') > time() - $days * DAY_IN_SECONDS) {
        return [(float) $prev['price_gbp'], (float) $last['price_gbp'], $last['created_at'], (float) $prev['price'], $prev['currency']];
    }
    return null;
}

add_action('ec_listing_price_changed', function (int $id, float $oldGbp, float $newGbp) {
    if ($newGbp < $oldGbp - 0.5) {
        wp_schedule_single_event(time() + 60, 'ec_alerts_price', [$id]);
    }
}, 10, 3);

add_action('ec_alerts_price', 'ec_alerts_price_run');
function ec_alerts_price_run(int $id): int
{
    global $wpdb;
    $l = ec_listing_row($id);
    if (!$l || $l['status'] !== 'published') {
        return 0;
    }
    $t = ec_table('alerts');
    $n = 0;
    foreach ($wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE kind = 'price' AND listing_id = %d AND confirmed = 1", $id), ARRAY_A) as $al) {
        if ((float) $l['price_gbp'] >= (float) $al['last_value'] - 0.5) {
            continue; // bu kişiye son bildirdiğimiz fiyattan düşük değil
        }
        $it = ec_listings_present([$l], $al['lang'])[0];
        $old = ec_alert_money((float) $al['last_value'], $l['currency']);
        $new = ec_alert_money((float) $l['price_gbp'], $l['currency']);
        if (ec_alert_notify($al, ec_alert_t('price_title', $al['lang']), ec_alert_t('price_body', $al['lang'], $it['title'], $old, $new), add_query_arg('utm_source', 'alert', $it['url']))) {
            $n++;
        }
        $wpdb->update($t, ['last_value' => (float) $l['price_gbp'], 'last_sent' => ec_now()], ['id' => $al['id']]);
    }
    return $n;
}

/* ---- Kayıtlı arama: saatlik kontrol, kişi başı günde en fazla bir bildirim ---- */

add_action('init', function () {
    if (!wp_next_scheduled('ec_alerts_hourly')) {
        wp_schedule_event(time() + 600, 'hourly', 'ec_alerts_hourly');
    }
});
add_action('ec_alerts_hourly', 'ec_alerts_search_run');

function ec_alerts_search_run(): int
{
    global $wpdb;
    $t = ec_table('alerts');
    $rows = $wpdb->get_results("SELECT * FROM $t WHERE kind = 'search' AND confirmed = 1 AND (last_sent IS NULL OR last_sent < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 20 HOUR)) ORDER BY last_check ASC LIMIT 300", ARRAY_A);
    $sent = 0;
    foreach ($rows as $al) {
        $q = json_decode((string) $al['query'], true) ?: [];
        $f = (array) ($q['f'] ?? []);
        $f['since'] = $al['last_check'] ?: $al['created_at'];
        $res = ec_listing_search($f, $al['lang'], 1, 1);
        $upd = ['last_check' => ec_now()];
        if ($res['total'] > 0) {
            $url = add_query_arg(['sort' => 'new', 'utm_source' => 'alert'], (string) ($q['url'] ?? ec_url_section($f['mode'] ?? 'sale', $al['lang'])));
            if (ec_alert_notify($al, ec_alert_t('search_title', $al['lang']), ec_alert_t('search_body', $al['lang'], (int) $res['total']), $url)) {
                $sent++;
            }
            $upd['last_sent'] = ec_now();
        }
        $wpdb->update($t, $upd, ['id' => $al['id']]);
    }
    return $sent;
}

/* ---- E-posta onayı ve kapatma bağlantıları ---- */

add_action('template_redirect', function () {
    global $wpdb;
    $ok = isset($_GET['ec_alert_ok']) ? sanitize_key(wp_unslash($_GET['ec_alert_ok'])) : '';
    $off = isset($_GET['ec_alert_off']) ? sanitize_key(wp_unslash($_GET['ec_alert_off'])) : '';
    if ($ok === '' && $off === '') {
        return;
    }
    $t = ec_table('alerts');
    $al = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE token = %s", $ok ?: $off), ARRAY_A);
    $lang = $al['lang'] ?? 'tr';
    if (!$al) {
        $msg = ec_alert_t('bad_link', $lang);
    } elseif ($ok) {
        $wpdb->update($t, ['confirmed' => 1], ['id' => $al['id']]);
        $msg = ec_alert_t('ok_page', $lang);
    } else {
        $wpdb->delete($t, ['id' => $al['id']]);
        $msg = ec_alert_t('off_page', $lang);
    }
    nocache_headers();
    header('X-Robots-Tag: noindex');
    $home = function_exists('ec_url_home') ? ec_url_home($lang) : home_url('/');
    wp_die('<p style="font-size:18px">' . esc_html($msg) . '</p><p><a href="' . esc_url($home) . '">' . esc_html(get_bloginfo('name')) . ' →</a></p>', esc_html(get_bloginfo('name')), ['response' => 200]);
});

/* ================= REST ================= */

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/push/key', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => fn() => ['key' => ec_vapid()['pub'] ?? ''],
    ]);

    register_rest_route(EC_NS, '/push/sub', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $in = $r->get_json_params() ?: [];
            $s = (array) ($in['sub'] ?? []);
            $ep = esc_url_raw((string) ($s['endpoint'] ?? ''), ['https']);
            $p256 = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($s['keys']['p256dh'] ?? ''));
            $auth = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($s['keys']['auth'] ?? ''));
            if (!$ep || !ec_push_endpoint_ok($ep) || strlen(ec_b64u_dec($p256)) !== 65 || strlen(ec_b64u_dec($auth)) !== 16) {
                return new WP_Error('ec_invalid', 'Geçersiz abonelik', ['status' => 400]);
            }
            if (ec_rate_limited('pushsub', 20, HOUR_IN_SECONDS)) {
                return new WP_Error('ec_too_many', 'Çok fazla istek', ['status' => 429]);
            }
            $lang = in_array($in['lang'] ?? '', ec_alert_langs(), true) ? $in['lang'] : 'tr';
            $hash = sha1($ep);
            $t = ec_table('push_subs');
            $agent = function_exists('ec_current_agent') ? ec_current_agent() : null;
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE ep_hash = %s", $hash), ARRAY_A);
            $data = ['endpoint' => $ep, 'p256dh' => $p256, 'auth' => $auth, 'lang' => $lang, 'seen_at' => ec_now()];
            if ($agent && !empty($in['agent'])) {
                $data['agent_id'] = (int) $agent['id']; // üye paneli: hatırlatmalar bu cihaza
            }
            if ($row) {
                $wpdb->update($t, $data, ['id' => $row['id']]);
                $id = (int) $row['id'];
            } else {
                $wpdb->insert($t, $data + ['ep_hash' => $hash, 'created_at' => ec_now()]);
                $id = (int) $wpdb->insert_id;
            }
            if (!empty($data['agent_id']) && (!$row || (int) $row['agent_id'] !== (int) $data['agent_id'])) {
                ec_push_send(ec_push_sub_row($id), ['title' => ec_alert_t('on_title', $lang), 'body' => 'Müşteri hatırlatmaları ve yeni talepler bu cihaza gelecek.', 'url' => ec_url_section('panel', $lang), 'tag' => 'ec-on']);
            }
            return ['id' => $id, 'k' => ec_push_sub_key($id, $hash)];
        },
    ]);

    register_rest_route(EC_NS, '/alerts', [
        [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $r) {
                $in = $r->get_json_params() ?: $r->get_body_params();
                $res = ec_alert_create((array) $in);
                return is_wp_error($res) ? $res : new WP_REST_Response($res, 201);
            },
        ],
        [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $r) {
                global $wpdb;
                $sub = ec_push_sub_auth($r['sub'], (string) $r['k']);
                if (!$sub) {
                    return ['price' => [], 'search' => []];
                }
                $rows = $wpdb->get_results($wpdb->prepare('SELECT kind, listing_id, query, token FROM ' . ec_table('alerts') . ' WHERE sub_id = %d', $sub['id']), ARRAY_A);
                $out = ['price' => [], 'search' => []];
                foreach ($rows as $x) {
                    if ($x['kind'] === 'price') {
                        $out['price'][(int) $x['listing_id']] = $x['token'];
                    } else {
                        $out['search'][] = ['token' => $x['token'], 'url' => json_decode((string) $x['query'], true)['url'] ?? ''];
                    }
                }
                return $out;
            },
        ],
    ]);

    register_rest_route(EC_NS, '/alerts/(?P<token>[A-Za-z0-9]{32})', [
        'methods' => 'DELETE',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $n = $wpdb->delete(ec_table('alerts'), ['token' => (string) $r['token']]);
            return ['ok' => (bool) $n];
        },
    ]);
});
