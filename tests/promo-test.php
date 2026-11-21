<?php
/**
 * Vitrin + paket satın alma (1.1) testi. Çalıştırma: wp eval-file tests/promo-test.php (taze demo verisiyle)
 * Banka (NestPay) yanıtı taklit edilir: imza aynı algoritmayla üretilip doğrulama denenir.
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
delete_option('ec_promo_pkgs_seeded');
ec_promo_packages_seed();
$pro = $wpdb->get_row("SELECT * FROM " . ec_table('packages') . " WHERE name LIKE 'Vitrin Paketi Pro%' ORDER BY id DESC LIMIT 1", ARRAY_A);
t('örnek vitrin paketleri eklendi (Pro: featured:10…)', $pro && str_contains($pro['promos'], 'home:3') && (float) $pro['amount'] === 60.0);

$agent = $wpdb->get_row("SELECT * FROM " . ec_table('agents') . " WHERE status = 'active' ORDER BY id LIMIT 1", ARRAY_A);
$lid = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . ec_table('listings') . " WHERE agent_id = %d AND status = 'published' ORDER BY id DESC LIMIT 1", $agent['id']));
$other = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . ec_table('listings') . " WHERE agent_id <> %d AND status = 'published' LIMIT 1", $agent['id']));

$r = ec_promo_apply($lid, 'home', $agent);
t('kredisiz vitrin reddedildi', is_wp_error($r) && $r->get_error_code() === 'ec_no_credit');

$sub = ec_subscription_grant((int) $agent['id'], ['package_id' => (int) $pro['id']]);
t('paket verildi', is_int($sub));
$c = ec_promo_credits((int) $agent['id']);
t('krediler: ana sayfa 3, öne çıkar 10, yukarı taşı 30', $c['home'] === 3 && $c['featured'] === 10 && $c['bump'] === 30);

$r = ec_promo_apply($lid, 'home', $agent);
t('ana sayfa vitrini uygulandı (7 gün)', !is_wp_error($r) && abs(strtotime($r['until'] . ' UTC') - (time() + 7 * DAY_IN_SECONDS)) < 120);
t('kredi düştü (home 2)', ec_promo_credits((int) $agent['id'])['home'] === 2);
$r2 = ec_promo_apply($lid, 'home', $agent);
t('ikinci uygulama süreyi uzattı (14 gün)', !is_wp_error($r2) && abs(strtotime($r2['until'] . ' UTC') - (time() + 14 * DAY_IN_SECONDS)) < 120);
t('başkasının ilanına uygulanamaz', is_wp_error(ec_promo_apply($other, 'featured', $agent)));

$row = ec_listing_row($lid);
ec_promo_apply($lid, 'cat', $agent);
ec_promo_apply($lid, 'urgent', $agent);
$home = ec_listing_showcase('home', 'tr');
t('ana sayfa vitrininde görünüyor', in_array($lid, array_column($home, 'id'), true));
$cat = ec_listing_showcase('cat', 'tr', ['mode' => $row['mode'], 'city' => $row['city']]);
t('kategori vitrininde görünüyor (aynı tür + şehir)', in_array($lid, array_column($cat, 'id'), true));
$cat2 = ec_listing_showcase('cat', 'tr', ['mode' => $row['mode'] === 'sale' ? 'rent' : 'sale', 'city' => $row['city']]);
t('başka türün vitrininde görünmüyor', !in_array($lid, array_column($cat2, 'id'), true));
$p = ec_listings_present([ec_listing_row($lid)], 'tr')[0];
t('ilan kartında acil işareti', $p['urgent'] === true);

// Yukarı taşı: eski bir ilan sıralamada en üste çıkar
$old = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . ec_table('listings') . " WHERE agent_id = %d AND status = 'published' AND (featured_until IS NULL OR featured_until < UTC_TIMESTAMP()) ORDER BY published_at ASC LIMIT 1", $agent['id']));
$wpdb->query("UPDATE " . ec_table('listings') . " SET featured_until = NULL");
$r = ec_promo_apply($old, 'bump', $agent);
$first = ec_listing_search(['mode' => ec_listing_row($old)['mode']], 'tr', 1, 3)['items'][0]['id'] ?? 0;
t('yukarı taşınan ilan ilk sırada', !is_wp_error($r) && $first === $old);
t('12 saat içinde ikinci kez yukarı taşınamaz', is_wp_error(ec_promo_apply($old, 'bump', $agent)));

// Havale siparişi
update_option('ec_payment', ['iban' => 'TR00 0000 0000 0000 0000 0000 00', 'holder' => 'AdaCasa Ltd', 'bank_name' => 'Test Bank'] + ec_payment_settings());
$o = ec_order_create($agent, (int) $pro['id'], 'bank');
t('havale siparişi açıldı', !is_wp_error($o) && $o['status'] === 'pending' && str_starts_with($o['ref'], 'AC'));
t('kart yöntemi kapalıyken kartla sipariş reddedildi', is_wp_error(ec_order_create($agent, (int) $pro['id'], 'card')));
$before = count(ec_subscriptions_active((int) $agent['id']));
ec_order_mark_paid($o, '', 'test');
t('onaylanınca paket tanımlandı', count(ec_subscriptions_active((int) $agent['id'])) === $before + 1 && ec_order_row((int) $o['id'])['status'] === 'paid');
ec_order_mark_paid(ec_order_row((int) $o['id']));
t('ikinci onay paketi tekrar vermez', count(ec_subscriptions_active((int) $agent['id'])) === $before + 1);

// Kart (NestPay 3D Pay Hosting, hash ver3)
update_option('ec_payment', array_merge(ec_payment_settings(), ['pos_on' => 1, 'pos_client' => '190100000', 'pos_key' => ec_encrypt('TEST1234'), 'pos_url' => 'https://test.example/fim/est3Dgate']));
$o2 = ec_order_create($agent, (int) $pro['id'], 'card');
$form = ec_nestpay_form($o2, 'tr');
t('banka formu: alanlar ve imza', $form['fields']['oid'] === $o2['ref'] && $form['fields']['amount'] === '60.00' && $form['fields']['currency'] === '978' && strlen(base64_decode($form['fields']['hash'])) === 64);
// Bilinen bir örnekle imza (bağımsız hesap): sıralama natcasesort, kaçış \ ve |
$sample = ['b' => 'x|y', 'A' => 'a\\b', 'hash' => 'yok', 'encoding' => 'utf-8'];
$expect = base64_encode(hash('sha512', 'a\\\\b|x\\|y|KEY', true));
t('imza algoritması (sıralama + kaçış) doğru', ec_nestpay_hash($sample, 'KEY') === $expect);
$resp = ['oid' => $o2['ref'], 'amount' => '60.00', 'currency' => '978', 'mdStatus' => '1', 'Response' => 'Approved', 'ProcReturnCode' => '00', 'AuthCode' => 'P12345', 'MaskedPan' => '4355***1234', 'clientid' => '190100000', 'rnd' => 'abc'];
$bad = $resp + ['HASH' => 'yanlis'];
[$v1] = ec_nestpay_verify($bad);
t('imzası yanlış banka yanıtı reddedildi', $v1 === false);
$resp['HASH'] = ec_nestpay_hash($resp, 'TEST1234');
[$v2, $vo] = ec_nestpay_verify($resp);
t('imzası doğru ve onaylı yanıt kabul edildi', $v2 === true && $vo['id'] === $o2['id']);
$resp3 = array_merge($resp, ['amount' => '1.00']);
unset($resp3['HASH']);
$resp3['HASH'] = ec_nestpay_hash($resp3, 'TEST1234');
[$v3] = ec_nestpay_verify($resp3);
t('tutar tutmayan yanıt reddedildi', $v3 === false);
$resp4 = array_merge($resp, ['mdStatus' => '0', 'Response' => 'Declined', 'ProcReturnCode' => '99']);
unset($resp4['HASH']);
$resp4['HASH'] = ec_nestpay_hash($resp4, 'TEST1234');
[$v4] = ec_nestpay_verify($resp4);
t('reddedilen ödeme kabul edilmedi', $v4 === false);

// REST dönüş adresi: onaylı yanıt → sipariş ödendi, panele yönlendirme
$req = new WP_REST_Request('POST', '/' . EC_NS . '/pay/nestpay');
$req->set_body_params($resp);
$res = rest_do_request($req);
t('dönüş adresi: 303 yönlendirme ve sipariş ödendi', $res->get_status() === 303 && str_contains((string) ($res->get_headers()['Location'] ?? ''), 'odeme=ok') && ec_order_row((int) $o2['id'])['status'] === 'paid');

delete_option('ec_payment');
echo "\n" . $GLOBALS['t_ok'] . " başarılı, " . $GLOBALS['t_fail'] . " başarısız\n";
