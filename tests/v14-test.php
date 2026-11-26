<?php
/**
 * 1.4 testi: Web Push şifreleme, uyarılar (fiyat/arama), fiyat geçmişi, ilan teyidi.
 * Çalıştırma: wp eval-file tests/v14-test.php
 */
global $wpdb;
$GLOBALS['t_ok'] = 0;
$GLOBALS['t_fail'] = 0;
function t($name, $cond)
{
    echo ($cond ? '✔ ' : '✘ ') . $name . "\n";
    $cond ? $GLOBALS['t_ok']++ : $GLOBALS['t_fail']++;
}
ec_install_schema();
$sent = [];
add_filter('ec_push_dry_run', function ($dry, $sub, $msg) use (&$sent) {
    $sent[] = $msg;
    return true;
}, 10, 3);
$mails = [];
add_filter('pre_wp_mail', function ($r, $atts) use (&$mails) {
    $mails[] = $atts;
    return true;
}, 10, 2);
$tg = [];
add_filter('pre_http_request', function ($pre, $args, $url) use (&$tg) {
    if (str_contains($url, 'api.telegram.org')) {
        $tg[] = json_decode($args['body'], true);
        return ['response' => ['code' => 200], 'body' => '{"ok":true,"result":{"message_id":1}}', 'headers' => [], 'cookies' => []];
    }
    return $pre;
}, 10, 3);

// --- Push şifreleme: tarayıcı anahtarı üret, şifrele, çöz (RFC 8291) ---
$ua = ec_p256_new();
$uaPub = ec_p256_raw_pub($ua);
$auth = random_bytes(16);
$body = ec_push_encrypt('{"title":"merhaba"}', $uaPub, $auth);
$salt = substr($body, 0, 16);
$asPub = substr($body, 21, 65);
$shared = openssl_pkey_derive(openssl_pkey_get_public(ec_p256_pub_pem($asPub)), $ua, 32);
$ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPub . $asPub, $auth);
$cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
$nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
$ct = substr($body, 86);
$plain = openssl_decrypt(substr($ct, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($ct, -16));
t('push şifreleme çözülebiliyor', $plain === '{"title":"merhaba"}' . "\x02");
t('VAPID anahtarı ve başlık', !empty(ec_vapid()['pub']) && str_starts_with(ec_vapid_header('https://fcm.googleapis.com/fcm/send/x'), 'vapid t='));
t('bilinmeyen bildirim sunucusu reddedilir', !ec_push_endpoint_ok('https://evil.example.com/x') && ec_push_endpoint_ok('https://fcm.googleapis.com/fcm/send/abc'));

// --- Abonelik (REST) ---
$subJson = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test-' . wp_generate_password(8, false), 'keys' => ['p256dh' => ec_b64u($uaPub), 'auth' => ec_b64u($auth)]];
$rq = new WP_REST_Request('POST', '/' . EC_NS . '/push/sub');
$rq->set_header('content-type', 'application/json');
$rq->set_body(wp_json_encode(['sub' => $subJson, 'lang' => 'tr']));
$res = rest_do_request($rq);
$me = $res->get_data();
t('REST: abonelik kaydedildi', $res->get_status() === 200 && !empty($me['id']) && !empty($me['k']));
t('yanlış anahtarla abonelik doğrulanmaz', ec_push_sub_auth($me['id'], 'yanlis') === null && ec_push_sub_auth($me['id'], $me['k']) !== null);

// --- Fiyat uyarısı ---
$L = ec_table('listings');
$lid = (int) $wpdb->get_var("SELECT id FROM $L WHERE status = 'published' AND mode = 'sale' ORDER BY id LIMIT 1");
$row = ec_listing_row($lid);
$al = ec_alert_create(['kind' => 'price', 'listing_id' => $lid, 'sub' => $me['id'], 'k' => $me['k'], 'lang' => 'tr']);
t('fiyat uyarısı (bu cihaz) oluştu ve onay bildirimi gitti', !is_wp_error($al) && $al['channel'] === 'push' && end($sent)['tag'] === 'ec-on');
$again = ec_alert_create(['kind' => 'price', 'listing_id' => $lid, 'sub' => $me['id'], 'k' => $me['k'], 'lang' => 'tr']);
t('aynı uyarı ikinci kez oluşmaz', $again['token'] === $al['token']);
$mailAl = ec_alert_create(['kind' => 'price', 'listing_id' => $lid, 'email' => 'alici@example.org', 'lang' => 'en']);
t('e-posta uyarısı onay bekliyor ve onay e-postası gitti', !is_wp_error($mailAl) && !$mailAl['confirmed'] && str_contains(end($mails)['message'], 'ec_alert_ok=' . $mailAl['token']));
$wpdb->update(ec_table('alerts'), ['confirmed' => 1], ['token' => $mailAl['token']]);
t('geçersiz istek: kanal yok', is_wp_error(ec_alert_create(['kind' => 'price', 'listing_id' => $lid])));

$hBefore = count(ec_price_history($lid));
$n0 = count($sent);
$m0 = count($mails);
ec_listing_update($lid, ['price' => round((float) $row['price'] * 0.9)]);
t('fiyat değişince geçmişe yazıldı', count(ec_price_history($lid)) === $hBefore + 1);
$drop = ec_price_drop($lid);
t('fiyat düştü bilgisi', $drop && $drop[1] < $drop[0]);
t('kartta price_drop', !empty(ec_listings_present([ec_listing_row($lid)], 'tr')[0]['price_drop']));
$k = ec_alerts_price_run($lid);
t('fiyat düşünce cihaza ve e-postaya bildirim gitti', $k === 2 && count($sent) === $n0 + 1 && count($mails) === $m0 + 1 && str_contains(end($sent)['title'], 'Fiyat'));
t('aynı fiyata ikinci kez bildirim gitmez', ec_alerts_price_run($lid) === 0);
ec_listing_update($lid, ['price' => (float) $row['price'], 'currency' => $row['currency']]); // geri al
t('fiyat artınca bildirim yok', ec_alerts_price_run($lid) === 0);

// --- Kayıtlı arama ---
$sAl = ec_alert_create(['kind' => 'search', 'query' => ['mode' => 'sale', 'city' => $row['city']], 'url' => home_url('/tr/satilik/' . $row['city'] . '/'), 'sub' => $me['id'], 'k' => $me['k'], 'lang' => 'tr']);
t('kayıtlı arama oluştu', !is_wp_error($sAl));
$wpdb->update(ec_table('alerts'), ['last_check' => gmdate('Y-m-d H:i:s', time() - 3600)], ['token' => $sAl['token']]);
$n0 = count($sent);
t('yeni ilan yokken bildirim gitmez', ec_alerts_search_run() === 0 && count($sent) === $n0);
$wpdb->update($L, ['published_at' => gmdate('Y-m-d H:i:s', time() - 60)], ['id' => $lid]);
$wpdb->update(ec_table('alerts'), ['last_check' => gmdate('Y-m-d H:i:s', time() - 3600)], ['token' => $sAl['token']]);
t('yeni ilan gelince "yeni ilan" bildirimi', ec_alerts_search_run() === 1 && str_contains(end($sent)['body'], 'yeni ilan'));
$wpdb->update($L, ['published_at' => gmdate('Y-m-d H:i:s', time() - 30)], ['id' => $lid]);
$wpdb->update(ec_table('alerts'), ['last_check' => gmdate('Y-m-d H:i:s', time() - 3600)], ['token' => $sAl['token']]);
t('aynı gün ikinci bildirim gitmez', ec_alerts_search_run() === 0);
$wpdb->update($L, ['published_at' => $row['published_at']], ['id' => $lid]);

// --- Uyarı kapatma ---
$rq = new WP_REST_Request('DELETE', '/' . EC_NS . '/alerts/' . $sAl['token']);
t('uyarı kapatıldı', rest_do_request($rq)->get_data()['ok'] === true);

// --- İlan teyidi ---
$A = ec_table('agents');
$email = 'teyit@adacasa-test.org';
$wpdb->delete($A, ['email' => $email]);
$wpdb->insert($A, ['kind' => 'ofis', 'name' => 'Teyit Ofis', 'email' => $email, 'slug' => 'teyit-ofis-' . wp_generate_password(4, false), 'status' => 'active', 'email_verified' => 1, 'telegram_chat_id' => '999000', 'created_at' => ec_now(), 'updated_at' => ec_now()]);
$aid = (int) $wpdb->insert_id;
$tx = ['title' => 'Teyit test', 'description' => 'x'];
$cid = ec_listing_create(['mode' => 'sale', 'ptype' => 'konut', 'subtype' => 'daire', 'city' => 'girne', 'price' => 100000, 'currency' => 'GBP', 'deed_type' => array_key_first(ec_deed_types()), 'status' => 'published', 'i18n' => ['tr' => $tx, 'en' => $tx, 'ru' => $tx]], $aid, 'panel');
$wpdb->update($L, ['confirmed_at' => gmdate('Y-m-d H:i:s', time() - 31 * DAY_IN_SECONDS)], ['id' => $cid]);
$tg = [];
[$asked] = ec_confirm_run();
$q = $tg ? end($tg) : [];
t('30 gün dolunca Telegram\'dan teyit soruldu', $asked >= 1 && str_contains($q['text'] ?? '', '#' . $cid) && ($q['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? '') === 'cfy:' . $cid);
t('sorulan ilan işaretlendi', !empty(ec_listing_row($cid)['confirm_asked_at']));
ec_tg_handle_callback(['id' => 'x', 'data' => 'cfy:' . $cid, 'message' => ['chat' => ['id' => '999000'], 'message_id' => 5], 'from' => ['id' => '999000']]);
$r = ec_listing_row($cid);
t('"Evet" ile teyit edildi', empty($r['confirm_asked_at']) && strtotime($r['confirmed_at'] . ' UTC') > time() - 60);
ec_tg_handle_callback(['id' => 'x', 'data' => 'cfo:' . $cid, 'message' => ['chat' => ['id' => '123'], 'message_id' => 5], 'from' => ['id' => '123']]);
t('başkasının sohbeti ilanı kaldıramaz', ec_listing_row($cid)['status'] === 'published');
// 7 gün cevapsız
$wpdb->update($L, ['confirmed_at' => gmdate('Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS), 'confirm_asked_at' => gmdate('Y-m-d H:i:s', time() - 8 * DAY_IN_SECONDS)], ['id' => $cid]);
[, $off] = ec_confirm_run();
t('7 gün cevap yok: ilan pasife alındı', $off >= 1 && ec_listing_row($cid)['status'] === 'passive');
t('e-posta bağlantısı imzası', ec_confirm_sig($cid, 'yes') !== ec_confirm_sig($cid, 'off'));
ec_confirm_apply($cid, 'yes');
t('geç gelen "Evet" ilanı yeniden yayına aldı', ec_listing_row($cid)['status'] === 'published');
ec_confirm_apply($cid, 'sold');
t('"Satıldı" işaretlendi', ec_listing_row($cid)['status'] === 'sold');
// Demo ilan sorulmaz
$demo = (int) $wpdb->get_var("SELECT id FROM $L WHERE source = 'demo' AND status = 'published' LIMIT 1");
if ($demo) {
    $wpdb->update($L, ['confirmed_at' => gmdate('Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS), 'confirm_asked_at' => null], ['id' => $demo]);
    ec_confirm_run();
    $d = ec_listing_row($demo);
    t('demo ilana soru gitmez, ertelenir', empty($d['confirm_asked_at']) && $d['status'] === 'published');
}

// temizlik
$wpdb->delete(ec_table('listing_i18n'), ['listing_id' => $cid]);
$wpdb->delete($L, ['id' => $cid]);
$wpdb->delete(ec_table('price_history'), ['listing_id' => $cid]);
$wpdb->delete($A, ['id' => $aid]);
$wpdb->query($wpdb->prepare('DELETE FROM ' . ec_table('alerts') . ' WHERE sub_id = %d OR email = %s', $me['id'], 'alici@example.org'));
$wpdb->delete(ec_table('push_subs'), ['id' => $me['id']]);
echo "\n" . $GLOBALS['t_ok'] . ' başarılı, ' . $GLOBALS['t_fail'] . " başarısız\n";
