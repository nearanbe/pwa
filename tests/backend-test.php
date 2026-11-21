<?php
// Dış servisleri taklit ederek (Telegram, Claude, Overpass, Yahoo, Twelve Data) arka ucu dener.
$GLOBALS['tg_sent'] = [];
add_filter('pre_http_request', function ($pre, $args, $url) {
    $ok = fn($b) => ['headers' => [], 'body' => is_string($b) ? $b : wp_json_encode($b), 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => [], 'filename' => null];
    if (str_contains($url, 'api.telegram.org/file/')) {
        $f = $args['filename'] ?? null;
        $img = imagecreatetruecolor(800, 600); imagefill($img, 0, 0, imagecolorallocate($img, 30, 90, 120));
        ob_start(); imagejpeg($img); $jpg = ob_get_clean();
        if ($f) file_put_contents($f, $jpg);
        return $ok($jpg);
    }
    if (str_contains($url, 'api.telegram.org')) {
        $m = basename(parse_url($url, PHP_URL_PATH));
        $GLOBALS['tg_sent'][] = [$m, json_decode($args['body'] ?? '{}', true)];
        if ($m === 'getFile') return $ok(['ok' => true, 'result' => ['file_path' => 'photos/x.jpg']]);
        return $ok(['ok' => true, 'result' => ['message_id' => count($GLOBALS['tg_sent'])]]);
    }
    if (str_contains($url, 'api.anthropic.com')) {
        $req = json_decode($args['body'], true);
        $GLOBALS['ai_req'] = $req;
        $corr = str_contains($req['messages'][0]['content'], 'CORRECTION');
        $d = ['mode' => 'sale', 'ptype' => 'konut', 'subtype' => 'daire', 'city' => 'girne', 'district' => 'alsancak', 'price' => $corr ? 165000 : 145000, 'currency' => 'GBP',
            'area_m2' => 95, 'rooms' => '2+1', 'baths' => 1, 'deed_type' => $corr ? 'esdeger' : null, 'furnished' => null, 'vat_paid' => true, 'transformer_paid' => null,
            'floor' => null, 'total_floors' => null, 'year_built' => null, 'sea_distance_m' => null, 'payment_plan' => null, 'features' => ['deniz_manzarasi', 'ortak_havuz'],
            'i18n' => ['tr' => ['title' => 'Alsancak deniz manzaralı 2+1', 'description' => 'Açıklama'], 'en' => ['title' => 'Sea view 2-bed in Alsancak', 'description' => 'Desc'], 'ru' => ['title' => 'Квартира 2+1', 'description' => 'Описание']],
            'missing' => $corr ? [] : ['tapu türü']];
        return $ok(['content' => [['type' => 'text', 'text' => wp_json_encode($d)]], 'stop_reason' => 'end_turn']);
    }
    if (str_contains($url, 'overpass')) {
        return $ok(['elements' => [
            ['type' => 'node', 'lat' => 35.336, 'lon' => 33.321, 'tags' => ['amenity' => 'pharmacy', 'name' => 'Alsancak Eczanesi']],
            ['type' => 'way', 'center' => ['lat' => 35.34, 'lon' => 33.33], 'tags' => ['amenity' => 'school', 'name' => 'Alsancak İlkokulu', 'name:en' => 'Alsancak Primary']],
            ['type' => 'node', 'lat' => 35.33, 'lon' => 33.31, 'tags' => ['shop' => 'supermarket', 'name' => 'Lemar']],
            ['type' => 'node', 'lat' => 35.337, 'lon' => 33.318, 'tags' => ['natural' => 'beach', 'name' => 'Escape Beach']],
        ]]);
    }
    if (str_contains($url, 'yahoo')) {
        $try = ec_rates_official()['TRY'] * ($GLOBALS['yahoo_mult'] ?? 1.012);
        return $ok(['chart' => ['result' => [['meta' => ['regularMarketPrice' => $try, 'regularMarketTime' => time() - 120]]]]]);
    }
    if (str_contains($url, 'twelvedata')) return $ok(['code' => 401, 'message' => '**apikey** parameter is incorrect']);
    return $pre;
}, 10, 3);

function t($label, $cond) { echo ($cond ? '✔ ' : '✘ ') . $label . "\n"; }
global $wpdb;
update_option('ec_telegram', ['bot' => 'AdaEvBot', 'token' => '123:ABC', 'admin_chat' => '999', 'secret' => 'sekret']);
update_option('ec_ai', ['key' => 'sk-test', 'model' => 'claude-opus-5-5', 'effort' => 'medium']);

// Üye: bireysel danışman (ilanları onaya düşer)
$a = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ec_agents WHERE kind='ofis' LIMIT 1", ARRAY_A);
$wpdb->update(ec_table('agents'), ['kind' => 'danisman', 'telegram_chat_id' => null], ['id' => $a['id']]);
$code = ec_member_telegram_code((int) $a['id']);

$send = function (array $u) {
    static $n = 1000;
    $u['update_id'] = ++$n;
    $r = new WP_REST_Request('POST', '/emlak/v1/telegram/webhook');
    $r->set_header('X-Telegram-Bot-Api-Secret-Token', 'sekret');
    $r->set_header('content-type', 'application/json');
    $r->set_body(wp_json_encode($u));
    $res = rest_do_request($r);
    foreach ($GLOBALS['ec_tg_jobs'] ?? [] as $j) { $j(); }
    $GLOBALS['ec_tg_jobs'] = [];
    return $res->get_status();
};
$msg = fn($extra) => ['message' => array_merge(['message_id' => rand(1, 99999), 'chat' => ['id' => 555, 'type' => 'private'], 'from' => ['id' => 555, 'username' => 'emlakci']], $extra)];

// yanlış gizli anahtar
$r = new WP_REST_Request('POST', '/emlak/v1/telegram/webhook'); $r->set_header('X-Telegram-Bot-Api-Secret-Token', 'yanlis');
t('webhook yanlış anahtarı reddeder', in_array(rest_do_request($r)->get_status(), [401, 403], true));

$send($msg(['text' => '/start ' . $code]));
$ag = ec_agent((int) $a['id']);
t('eşleştirme: chat id kaydedildi', $ag['telegram_chat_id'] === '555' && $ag['telegram_username'] === 'emlakci');

$GLOBALS['tg_sent'] = [];
$send($msg(['photo' => [['file_id' => 'small1'], ['file_id' => 'big1']], 'media_group_id' => 'g1']));
$send($msg(['photo' => [['file_id' => 'small2'], ['file_id' => 'big2']], 'media_group_id' => 'g1']));
$send($msg(['text' => "Alsancak'ta satılık 2+1, 95 m2, deniz manzaralı, ortak havuz, 145.000 stg, KDV ödendi"]));
$d = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ec_tg_drafts WHERE chat_id='555' ORDER BY id DESC", ARRAY_A);
t('taslak: 2 fotoğraf + 1 metin toplandı', count(json_decode($d['photos'], true)) === 2 && count(json_decode($d['texts'], true)) === 1);
$statusMsgs = array_filter($GLOBALS['tg_sent'], fn($x) => in_array($x[0], ['sendMessage', 'editMessageText'], true));
t('albüm için durum mesajı tek gönderildi, sonra güncellendi', count(array_filter($statusMsgs, fn($x) => $x[0] === 'sendMessage')) === 1);

$send(['callback_query' => ['id' => 'cb1', 'from' => ['id' => 555], 'message' => ['chat' => ['id' => 555], 'message_id' => 1], 'data' => 'prep:' . $d['id']]]);
$d = ec_tg_draft_get((int) $d['id']);
$row = ec_listing_row((int) $d['listing_id']);
t('hazırla: ilan taslağı oluştu', $d['state'] === 'preview' && $row && $row['status'] === 'draft' && $row['source'] === 'telegram');
t('Claude isteği: model, json_schema, fallbacks', ($GLOBALS['ai_req']['model'] ?? '') === 'claude-opus-5-5' && ($GLOBALS['ai_req']['output_config']['format']['type'] ?? '') === 'json_schema' && ($GLOBALS['ai_req']['fallbacks'] ?? '') === 'default');
t('bölge adı düzeltildi (alsancak → Alsancak)', $row['district'] === 'Alsancak');
t('fotoğraflar indirildi ve eklendi (2)', (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ec_photos WHERE listing_id=" . (int) $row['id']) === 2);
$tr = $wpdb->get_results("SELECT lang FROM {$wpdb->prefix}ec_listing_i18n WHERE listing_id=" . (int) $row['id'], ARRAY_A);
t('3 dilde metin', count($tr) === 3);
$prev = array_values(array_filter($GLOBALS['tg_sent'], fn($x) => str_contains(wp_json_encode($x[1]), 'pub:')));
t('önizleme Yayınla/Düzelt düğmeleriyle gönderildi', count($prev) >= 1);

// yayınla: satılıkta tapu yok → hata ister
$send(['callback_query' => ['id' => 'cb2', 'from' => ['id' => 555], 'message' => ['chat' => ['id' => 555]], 'data' => 'pub:' . $d['id']]]);
t('tapu türü eksikken yayın engellendi', ec_listing_row((int) $row['id'])['status'] === 'draft' && ec_tg_draft_get((int) $d['id'])['state'] === 'editing');
// düzeltme
$send($msg(['text' => 'tapu eşdeğer, fiyat 165.000 sterlin']));
$row = ec_listing_row((int) $row['id']);
t('düzeltme uygulandı (fiyat 165000, tapu eşdeğer)', (float) $row['price'] === 165000.0 && $row['deed_type'] === 'esdeger');
$GLOBALS['tg_sent'] = [];
$send(['callback_query' => ['id' => 'cb3', 'from' => ['id' => 555], 'message' => ['chat' => ['id' => 555]], 'data' => 'pub:' . $d['id']]]);
$row = ec_listing_row((int) $row['id']);
t('danışman ilanı onaya düştü', $row['status'] === 'pending');
$admin = array_filter($GLOBALS['tg_sent'], fn($x) => ($x[1]['chat_id'] ?? '') === '999' && str_contains(wp_json_encode($x[1]), 'ap:' . $row['id']));
t('yöneticiye Onayla/Reddet kartı gitti', count($admin) === 1);
// yetkisiz onay denemesi
$send(['callback_query' => ['id' => 'cb4', 'from' => ['id' => 555], 'message' => ['chat' => ['id' => 555]], 'data' => 'ap:' . $row['id']]]);
t('üye kendi ilanını onaylayamaz', ec_listing_row((int) $row['id'])['status'] === 'pending');
$GLOBALS['tg_sent'] = [];
$send(['callback_query' => ['id' => 'cb5', 'from' => ['id' => 999], 'message' => ['chat' => ['id' => 999], 'message_id' => 7], 'data' => 'ap:' . $row['id']]]);
t('yönetici Telegram’dan onayladı', ec_listing_row((int) $row['id'])['status'] === 'published');
t('üyeye "yayında" bildirimi gitti', count(array_filter($GLOBALS['tg_sent'], fn($x) => ($x[1]['chat_id'] ?? '') === '555' && str_contains($x[1]['text'] ?? '', 'onaylandı'))) === 1);

// SMS kodu aktarımı
$req = new WP_REST_Request('POST', '/emlak/v1/ext/sms-request'); $req->set_header('content-type', 'application/json'); $req->set_body('{"site":"101evler"}');
$key = ec_agent_issue_key((int) $a['id']); $req->set_header('authorization', 'Bearer ' . $key);
t('eklenti SMS kodu istedi', rest_do_request($req)->get_status() === 200);
$send($msg(['text' => 'Kod: 482913']));
$g = new WP_REST_Request('GET', '/emlak/v1/ext/sms-code'); $g->set_header('authorization', 'Bearer ' . $key);
$res = rest_do_request($g)->get_data();
t('SMS kodu eklentiye ulaştı', ($res['code'] ?? '') === '482913');
t('kod bir kez okunur', rest_do_request($g)->get_data()['code'] === null);
// eklenti ilan verisi ve kayıt (0.5: paket gerekir)
$g = new WP_REST_Request('GET', '/emlak/v1/ext/listing/' . $row['id']); $g->set_header('authorization', 'Bearer ' . $key);
t('paketsiz eklenti isteği reddedildi (402)', rest_do_request($g)->get_status() === 402);
ec_packages_seed_defaults();
ec_subscription_grant((int) $a['id'], ['package_id' => (int) $wpdb->get_var('SELECT id FROM ' . ec_table('packages') . ' ORDER BY id LIMIT 1'), 'days' => 5]);
$ex = rest_do_request($g)->get_data();
t('eklenti ilan verisi (3 dil + fotoğraf)', count($ex['i18n']) === 3 && count($ex['photos']) === 2);
$s = new WP_REST_Request('POST', '/emlak/v1/ext/syndication'); $s->set_header('authorization', 'Bearer ' . $key); $s->set_header('content-type', 'application/json');
$s->set_body(wp_json_encode(['listing_id' => $row['id'], 'site' => '101evler', 'status' => 'published', 'remote_url' => 'https://www.101evler.com/ilan/123456']));
t('101evler kaydı tutuldu', (rest_do_request($s)->get_data()['syndication']['101evler']['status'] ?? '') === 'published');
$other = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ec_listings WHERE agent_id <> {$a['id']} LIMIT 1");
$s->set_body(wp_json_encode(['listing_id' => $other, 'site' => '101evler']));
t('başkasının ilanına kayıt reddedildi', rest_do_request($s)->get_status() === 400);

// Yakın yerler
$nb = ec_nearby(35.335, 33.32);
$pres = ec_nearby_present($nb['items'], 'en');
$school = array_values(array_filter($pres, fn($c) => $c['key'] === 'education'))[0]['items'][0] ?? [];
t('yakın yerler: kategoriler ve mesafe', ($school['name'] ?? '') === 'Alsancak Primary' && str_ends_with($school['dist'], 'm'));
t('yakın yerler önbellekte', ec_nearby(35.335, 33.32)['cached'] === true);

// Gün içi kur
update_option('ec_rates_live', ['enabled' => 1, 'provider' => 'auto', 'key' => 'bad', 'interval' => 5, 'max_dev' => 5]);
$r = ec_rates_live_update();
t('Twelve Data hata verdi → Yahoo kullanıldı', !is_wp_error($r) && $r['source'] === 'yahoo' && str_contains($r['error'], 'Twelve'));
t('sitede piyasa kuru kullanılıyor', abs(ec_rates()['TRY'] - ec_rates_official()['TRY'] * 1.012) < 0.01);
$GLOBALS['yahoo_mult'] = 1.2;
$r = ec_rates_live_update();
t('%20 sapan değer reddedildi', is_wp_error($r));
update_option('ec_rates_live', ['enabled' => 0]);
delete_option('ec_rates_live_data');
echo "\n";
