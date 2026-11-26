<?php
/**
 * 1.4 testi (2): doğrulama belgesi, görüntülü gezi talebi, müşteri takibi hatırlatmaları, maliyet kuralları.
 * Çalıştırma: wp eval-file tests/v14b-test.php
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
$tg = [];
add_filter('pre_http_request', function ($pre, $args, $url) use (&$tg) {
    if (str_contains($url, 'api.telegram.org')) {
        $j = is_string($args['body']) ? json_decode($args['body'], true) : null;
        $tg[] = $url . ' ' . ($j['text'] ?? $j['caption'] ?? (is_string($args['body']) ? substr($args['body'], 0, 400) : ''));
        return ['response' => ['code' => 200], 'body' => '{"ok":true,"result":{"message_id":1}}', 'headers' => [], 'cookies' => []];
    }
    return $pre;
}, 10, 3);
add_filter('pre_wp_mail', '__return_true');
add_filter('pre_option_ec_turnstile', fn() => []);
add_filter('pre_transient_ec_rl_lead_' . substr(ec_ip_hash(), 0, 16), fn() => 0);
$pushed = [];
add_filter('ec_push_dry_run', function ($d, $s, $m) use (&$pushed) {
    $pushed[] = $m;
    return true;
}, 10, 3);

$A = ec_table('agents');
$L = ec_table('listings');
$wpdb->delete($A, ['email' => 'crm@adacasa-test.org']);
$wpdb->insert($A, ['kind' => 'ofis', 'name' => 'CRM Ofis', 'email' => 'crm@adacasa-test.org', 'slug' => 'crm-ofis-' . wp_generate_password(4, false), 'status' => 'active', 'email_verified' => 1, 'telegram_chat_id' => '777001', 'created_at' => ec_now(), 'updated_at' => ec_now()]);
$aid = (int) $wpdb->insert_id;
$tx = ['title' => 'CRM test', 'description' => 'x'];
$lid = ec_listing_create(['mode' => 'sale', 'ptype' => 'konut', 'subtype' => 'villa', 'city' => 'girne', 'price' => 300000, 'currency' => 'GBP', 'deed_type' => array_key_first(ec_deed_types()), 'area_m2' => 320, 'status' => 'published', 'i18n' => ['tr' => $tx, 'en' => $tx, 'ru' => $tx]], $aid, 'panel');

// Doğrulama belgesi
$im = imagecreatetruecolor(40, 40);
$tmp = wp_tempnam('b');
imagejpeg($im, $tmp);
$vid = ec_verify_submit($lid, $aid, $tmp);
t('belge alındı ve yöneticiye gitti', is_int($vid) && $tg && str_contains(end($tg), 'sendPhoto'));
t('başkası belge gönderemez', is_wp_error(ec_verify_submit($lid, $aid + 1000, $tmp)));
$pdfOk = ec_verify_submit($lid, $aid, __FILE__);
t('PHP dosyası belge olarak kabul edilmez', is_wp_error($pdfOk));
ec_tg_handle_callback(['id' => 'x', 'data' => 'vok:' . $vid, 'message' => ['chat' => ['id' => '12345'], 'message_id' => 3], 'from' => ['id' => '12345']]);
t('yönetici olmayan sohbet onaylayamaz', empty(ec_listing_row($lid)['verified_at']));
ec_verify_decide($vid, 'approve');
t('onay: rozet ve belge silindi', !empty(ec_listing_row($lid)['verified_at']) && ec_verify_row($vid)['path'] === '');

// Görüntülü gezi talebi
$res = ec_lead_create(['kind' => 'video', 'name' => 'Ayşe Test', 'phone' => '+905330001122', 'message' => "📹 Görüntülü gezi\n2026-12-01 · 14:00–16:00 · WhatsApp", 'consent' => 1, 'lang' => 'tr'], ['listing_id' => $lid]);
$lead = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('leads') . ' WHERE id = %d', $res), ARRAY_A);
t('görüntülü gezi talebi kaydedildi (kind=video)', is_int($res) && $lead['kind'] === 'video');
t('emlakçıya Telegram ile "Görüntülü gezi talebi"', str_contains(urldecode(implode(' ', $tg)), 'Görüntülü gezi talebi'));

// Müşteri takibi
$upd = ec_crm_update($res, $aid, ['stage' => 'viewing', 'notes' => 'Cuma arayacağım', 'follow_at' => wp_date('Y-m-d\TH:i', time() + 50 * MINUTE_IN_SECONDS)]);
$lead = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('leads') . ' WHERE id = %d', $res), ARRAY_A);
t('durum, not, hatırlatma kaydedildi', $upd === true && $lead['stage'] === 'viewing' && $lead['notes'] === 'Cuma arayacağım' && abs(strtotime($lead['follow_at'] . ' UTC') - (time() + 3000)) < 120);
t('başkasının talebi güncellenemez', is_wp_error(ec_crm_update($res, $aid + 1000, ['stage' => 'won'])));
$tg = [];
$n = ec_crm_reminders();
$l2 = $wpdb->get_row($wpdb->prepare('SELECT rem_sent FROM ' . ec_table('leads') . ' WHERE id = %d', $res), ARRAY_A);
t('50 dk kala "1 saat" hatırlatması gitti (cihaz yok → Telegram)', $n === 1 && str_contains(urldecode(implode(' ', $tg)), 'Hatırlatma') && str_contains($l2['rem_sent'], 'h') && str_contains($l2['rem_sent'], 'd'));
t('aynı hatırlatma tekrar gitmez', ec_crm_reminders() === 0);
$wpdb->update(ec_table('leads'), ['follow_at' => gmdate('Y-m-d H:i:s', time() + 4 * MINUTE_IN_SECONDS)], ['id' => $res]);
t('5 dk kala son hatırlatma', ec_crm_reminders() === 1);
$wpdb->insert(ec_table('push_subs'), ['ep_hash' => sha1('crm-test'), 'endpoint' => 'https://fcm.googleapis.com/fcm/send/crm-test', 'p256dh' => ec_b64u(ec_p256_raw_pub(ec_p256_new())), 'auth' => ec_b64u(random_bytes(16)), 'agent_id' => $aid, 'created_at' => ec_now()]);
$sid = (int) $wpdb->insert_id;
$wpdb->update(ec_table('leads'), ['follow_at' => gmdate('Y-m-d H:i:s', time() + 20 * HOUR_IN_SECONDS), 'rem_sent' => ''], ['id' => $res]);
$pushed = [];
ec_crm_reminders();
t('cihaz varsa "1 gün" hatırlatması bildirim olarak gitti', count($pushed) === 1 && str_contains($pushed[0]['title'], 'Hatırlatma'));
$mid = ec_crm_add($aid, ['name' => 'Telefonla arayan', 'phone' => '05331234567', 'notes' => '3+1 arıyor']);
t('elle müşteri eklendi', is_int($mid) && $mid > 0);

// Maliyet kuralları
$r = ec_cost_rules(strtotime('2026-11-26 12:00'));
$r2 = ec_cost_rules(strtotime('2027-02-01 12:00'));
t('Kasım 2026: indirimli harç (yabancı 1. taşınmaz %4)', $r['promo'] && $r['deed']['foreign'][0] == 4);
t('Şubat 2027: 2025 tablosu (yabancı %9)', !$r2['promo'] && $r2['deed']['foreign'][0] == 9);

// temizlik
$wpdb->delete(ec_table('push_subs'), ['id' => $sid]);
$wpdb->query($wpdb->prepare('DELETE FROM ' . ec_table('leads') . ' WHERE agent_id = %d', $aid));
$wpdb->delete(ec_table('verify_docs'), ['listing_id' => $lid]);
$wpdb->delete(ec_table('listing_i18n'), ['listing_id' => $lid]);
$wpdb->delete(ec_table('price_history'), ['listing_id' => $lid]);
$wpdb->delete($L, ['id' => $lid]);
$wpdb->delete($A, ['id' => $aid]);
echo "\n" . $GLOBALS['t_ok'] . ' başarılı, ' . $GLOBALS['t_fail'] . " başarısız\n";
