<?php
// Paketler + sosyal medya: Facebook / Instagram Graph API ve Cloudflare taklit edilir.
// Çalıştırma: wp transient delete --all && wp eval-file tests/social-test.php
$GLOBALS['graph'] = [];
add_filter('pre_http_request', function ($pre, $args, $url) {
    $ok = fn($b, $c = 200) => ['headers' => [], 'body' => wp_json_encode($b), 'response' => ['code' => $c, 'message' => 'OK'], 'cookies' => [], 'filename' => null];
    if (str_contains($url, 'api.cloudflare.com')) {
        $GLOBALS['cf'][] = json_decode($args['body'], true);
        return $ok(['success' => true]);
    }
    if (!str_contains($url, 'graph.facebook.com')) {
        return $pre;
    }
    $path = preg_replace('~^https://graph.facebook.com/v[\d.]+/~', '', strtok($url, '?'));
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    $b = is_array($args['body'] ?? null) ? $args['body'] : $q;
    $GLOBALS['graph'][] = [$args['method'] ?? 'GET', $path, $b];
    if (!empty($GLOBALS['fail_token']) && str_contains($path, '/feed')) {
        return $ok(['error' => ['message' => 'Error validating access token', 'code' => 190, 'error_subcode' => 463]], 400);
    }
    if ($path === 'oauth/access_token') return $ok(['access_token' => 'LONG_USER_TOKEN', 'token_type' => 'bearer']);
    if ($path === 'me') return $ok(['id' => '1001', 'name' => 'Site Yöneticisi']);
    if ($path === 'me/accounts') return $ok(['data' => [
        ['id' => '111', 'name' => 'Emlak392', 'access_token' => 'PAGE_TOKEN_SITE', 'instagram_business_account' => ['id' => '9111', 'username' => 'emlak392']],
        ['id' => '222', 'name' => 'Kuzey Yapı', 'access_token' => 'PAGE_TOKEN_KY', 'instagram_business_account' => ['id' => '9222', 'username' => 'kuzeyyapi']],
        ['id' => '333', 'name' => 'Instagramsız Sayfa', 'access_token' => 'PAGE_TOKEN_3'],
    ]]);
    if (preg_match('~^\d+/photos$~', $path)) return $ok(['id' => 'PH' . count($GLOBALS['graph'])]);
    if (preg_match('~^\d+/feed$~', $path)) return $ok(['id' => explode('/', $path)[0] . '_POST']);
    if (str_ends_with($path, '/media')) return $ok(['id' => 'C' . count($GLOBALS['graph'])]);
    if (str_ends_with($path, '/media_publish')) return $ok(['id' => 'IGM1']);
    if (preg_match('~^C\d+$~', $path)) return $ok(['status_code' => 'FINISHED']);
    if (str_ends_with($path, '_POST')) return $ok(['permalink_url' => 'https://facebook.com/p/1']);
    if ($path === 'IGM1') return $ok(['permalink' => 'https://instagram.com/p/X']);
    return $ok(['id' => 'X']);
}, 10, 3);

function t($label, $cond) { echo ($cond ? '✔ ' : '✘ ') . $label . "\n"; }
global $wpdb;
foreach (['social_accounts', 'social_posts', 'subscriptions', 'usage'] as $tb) { $wpdb->query('DELETE FROM ' . ec_table($tb)); }
ec_packages_seed_defaults();
update_option('ec_social', ['app_id' => '123', 'app_secret' => ec_encrypt('abcdef'), 'graph_ver' => 'v24.0', 'site_auto' => 1, 'max_photos' => 4]);
update_option('ec_speed', ['cache' => 1, 'cf_zone' => 'abc123', 'cf_token' => ec_encrypt('cftok'), 'edge_ttl' => 600, 'origin_ttl' => 120]);

t('şifreleme geri çözülüyor', ec_decrypt(ec_encrypt('gizli-ş')) === 'gizli-ş');
t('şifreli metin düz metni içermiyor', !str_contains(ec_encrypt('PAGE_TOKEN'), 'PAGE_TOKEN'));

// Bağlan: kullanıcı anahtarı → uzun ömürlü → sayfalar
$n = ec_fb_connect_user('SHORT');
t('Facebook bağlantısı: 3 sayfa bulundu', $n === 3);
$accs = ec_social_accounts();
t('Instagram hesabı sayfayla birlikte geldi', count(array_filter($accs, fn($a) => $a['ig_username'] === 'kuzeyyapi')) === 1);
t('sayfa anahtarı şifreli saklanıyor', ec_decrypt($accs[0]['token_enc']) !== '' && !str_contains($accs[0]['token_enc'], 'PAGE_TOKEN'));

$agent = $wpdb->get_row('SELECT * FROM ' . ec_table('agents') . ' LIMIT 1', ARRAY_A);
$site = $wpdb->get_var('SELECT id FROM ' . ec_table('social_accounts') . " WHERE page_id = '111'");
$ky = $wpdb->get_var('SELECT id FROM ' . ec_table('social_accounts') . " WHERE page_id = '222'");
$wpdb->update(ec_table('social_accounts'), ['agent_id' => 0], ['id' => $site]);
$wpdb->update(ec_table('social_accounts'), ['agent_id' => $agent['id']], ['id' => $ky]);

// Paket yok: üyenin sayfasına paylaşılmaz, sitenin sayfasına paylaşılır
$wpdb->update(ec_table('listings'), ['source' => 'panel'], ['agent_id' => $agent['id']]);
$lid = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('listings') . " WHERE agent_id = %d AND status = 'published' LIMIT 1", $agent['id']));
t('paketsiz üye: sosyal medya kapalı', !ec_member_can($agent, 'social'));
$q = ec_social_queue($lid);
t('paketsiz: sadece sitenin hesapları sıraya alındı (FB+IG = 2)', $q === 2);

// Paket ver (kota 2)
$pk = $wpdb->get_row('SELECT * FROM ' . ec_table('packages') . " WHERE name = 'Tek ilan'", ARRAY_A);
$sid = ec_subscription_grant((int) $agent['id'], ['package_id' => $pk['id'], 'quota' => 2, 'days' => 10]);
t('paket verildi', is_int($sid) && $sid > 0);
$agent = ec_agent((int) $agent['id']);
t('paketli üye: sosyal medya açık', ec_member_can($agent, 'social'));
t('paketli üye: 101evler aktarımı açık', ec_member_can($agent, 'syndication'));
$q = ec_social_queue($lid);
t('paketli: üyenin FB + IG hesabı da sıraya alındı', $q === 2);
t('aynı ilan tekrar sıraya alınmaz', ec_social_queue($lid) === 0);

if (!file_exists('/tmp/x/pano1.jpg')) { // 2:1 deneme panoraması (yoksa üret)
    wp_mkdir_p('/tmp/x');
    $im = imagecreatetruecolor(2000, 1000);
    for ($x = 0; $x < 2000; $x += 50) { imagefilledrectangle($im, $x, 0, $x + 49, 999, imagecolorallocate($im, $x % 255, 120, 200 - $x % 200)); }
    imagejpeg($im, '/tmp/x/pano1.jpg', 85);
}
ec_pano_add($lid, '/tmp/x/pano1.jpg', 'Salon'); // 0.6: 360° fotoğraf
$GLOBALS['graph'] = [];
$res = ec_social_run(10);
$done = count(array_filter($res, fn($r) => $r['status'] === 'done'));
t('4 gönderinin hepsi paylaşıldı', $done === 4);
$fb = array_values(array_filter($GLOBALS['graph'], fn($g) => $g[1] === '222/feed'))[0] ?? null;
t('FB: çoklu fotoğraf (attached_media) ve metinde link var', $fb && isset($fb[2]['attached_media[0]']) && str_contains($fb[2]['message'], '/ilan/'));
$car = array_values(array_filter($GLOBALS['graph'], fn($g) => $g[1] === '9222/media' && ($g[2]['media_type'] ?? '') === 'CAROUSEL'))[0] ?? null;
$igSingle = array_values(array_filter($GLOBALS['graph'], fn($g) => $g[1] === '9222/media'));
t('IG: gönderi oluşturuldu ve yayınlandı', $igSingle && count(array_filter($GLOBALS['graph'], fn($g) => $g[1] === '9222/media_publish')) === 1);
$imgUrl = $igSingle[0][2]['image_url'] ?? '';
t('IG: görsel JPEG ve dosya üretildi', str_ends_with($imgUrl, '-ig.jpg') && file_exists(ec_photo_base()['dir'] . '/' . substr($imgUrl, strlen(ec_photo_base()['url']) + 1)));
$cap = ($car[2]['caption'] ?? $igSingle[0][2]['caption'] ?? '');
t('IG metni: tıklanmayan link yerine ilan no', str_contains($cap, '#' . $lid) && !str_contains($cap, 'http'));
t('metin TR + EN (üyenin varsayılanı)', str_contains($cap, '— — —'));
t('kota: ilan 1 kez sayıldı', ec_subscription_used($sid) === 1);
$sph = array_filter($GLOBALS['graph'], fn($g) => str_ends_with($g[1], '/photos') && ($g[2]['allow_spherical_photo'] ?? '') === 'true');
t('FB: 360° fotoğraf ayrı gönderi olarak paylaşıldı (site + üye = 2)', count($sph) === 2 && str_ends_with(array_values($sph)[0][2]['url'], '-fb.jpg'));
$igItems = array_filter($GLOBALS['graph'], fn($g) => $g[1] === '9222/media' && str_ends_with($g[2]['image_url'] ?? '', '-ig.jpg') && str_contains($g[2]['image_url'], '/360-'));
t('IG: 360° fotoğrafın düz kesiti eklendi', count($igItems) === 1);

// Kota dolunca üyenin sayfası atlanır
$l2 = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('listings') . " WHERE agent_id = %d AND status = 'published' AND id <> %d LIMIT 1", $agent['id'], $lid));
$l3 = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('listings') . " WHERE agent_id = %d AND status = 'published' AND id NOT IN (%d,%d) LIMIT 1", $agent['id'], $lid, $l2));
ec_social_queue($l2); ec_social_run(10);
t('2. ilan paylaşıldı, kota 2/2', ec_subscription_used($sid) === 2);
t('kota dolu: üye artık paylaşamaz', !ec_member_can(ec_agent((int) $agent['id']), 'social'));
$q3 = ec_social_queue($l3);
t('kota dolu: 3. ilanda sadece site hesabı', $q3 === 2);

// Eklenti kotası: daha önce sayılan ilan tekrar düşmez, yeni ilan reddedilir
t('eklenti: sayılmış ilan için izin var', ec_quota_use(ec_agent((int) $agent['id']), $lid, 'syndication') === true);
t('eklenti: kota dolu → 402', is_wp_error(ec_quota_use(ec_agent((int) $agent['id']), $l3, 'syndication')));

// Uzat + süre bitimi
ec_subscription_extend($sid, 30);
$end = $wpdb->get_var($wpdb->prepare('SELECT ends_at FROM ' . ec_table('subscriptions') . ' WHERE id = %d', $sid));
t('paket 30 gün uzatıldı (~40 gün)', abs(strtotime($end . ' UTC') - (time() + 40 * DAY_IN_SECONDS)) < 2 * DAY_IN_SECONDS);
ec_subscription_cancel($sid);
t('iptal edilince erişim kapandı', !ec_member_can(ec_agent((int) $agent['id']), 'syndication'));

// Anahtar geçersiz → hesap "expired", yöneticiye bildirim
$GLOBALS['fail_token'] = true;
$wpdb->query('DELETE FROM ' . ec_table('social_posts'));
ec_social_queue($l3); $r = ec_social_run(10);
$acc = ec_social_account((int) $site);
t('geçersiz anahtar: hesap işaretlendi', $acc['status'] === 'expired' && str_contains($acc['last_error'], 'Facebook ile bağlan'));
unset($GLOBALS['fail_token']);

// Cloudflare temizleme: ilan değişince ilgili adresler
$GLOBALS['cf'] = [];
ec_listing_update($l3, ['price' => 123456]);
do_action('shutdown');
$files = $GLOBALS['cf'][0]['files'] ?? [];
t('Cloudflare: ilan + ana sayfa + liste adresleri temizlendi', count($files) >= 9 && count(array_filter($files, fn($f) => str_contains($f, '-' . $l3 . '/'))) === 3);
update_option('ec_speed', ['cache' => 0]);
$wpdb->update(ec_table('listings'), ['source' => 'demo'], ['agent_id' => $agent['id'], 'source' => 'panel']); // demo temizliği silebilsin
foreach (['social_posts', 'social_accounts', 'subscriptions', 'usage'] as $tb) { $wpdb->query('DELETE FROM ' . ec_table($tb)); }
delete_option('ec_social');
echo "\n";
