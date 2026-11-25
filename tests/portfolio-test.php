<?php
/**
 * Ofis portföyü (1.3) testi: ayrılan danışmanın ilanları kilitlenir, yönetici aktarır.
 * Çalıştırma: wp eval-file tests/portfolio-test.php
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
$A = ec_table('agents');
$L = ec_table('listings');
add_filter('pre_wp_mail', '__return_true'); // e-posta gönderme

$office = $wpdb->get_row("SELECT * FROM $A WHERE kind = 'ofis' AND status = 'active' ORDER BY id LIMIT 1", ARRAY_A);
$office2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $A WHERE kind = 'ofis' AND status = 'active' AND id <> %d ORDER BY id LIMIT 1", $office['id']), ARRAY_A);
$mk = function (string $email) use ($wpdb, $A) {
    $wpdb->delete($A, ['email' => $email]);
    $uid = username_exists('t_' . md5($email)) ?: wp_insert_user(['user_login' => 't_' . md5($email), 'user_email' => $email, 'user_pass' => wp_generate_password(), 'role' => 'ec_member']);
    $wpdb->insert($A, ['wp_user_id' => $uid, 'kind' => 'danisman', 'name' => 'Test ' . $email, 'email' => $email, 'phone' => '+905330000000', 'slug' => ec_agent_unique_slug('test ' . $email), 'status' => 'active', 'email_verified' => 1, 'created_at' => ec_now(), 'updated_at' => ec_now()]);
    return ec_agent((int) $wpdb->insert_id);
};
$deed = array_key_first(ec_deed_types());
$new = function (array $agent, string $status, string $title) use ($deed) {
    $tx = ['title' => $title, 'description' => 'Test ilanı açıklaması.'];
    return ec_listing_create(['mode' => 'sale', 'ptype' => 'konut', 'subtype' => 'daire', 'city' => 'girne', 'price' => 120000, 'currency' => 'GBP', 'deed_type' => $deed, 'rooms' => '2+1', 'area_m2' => 90, 'status' => $status, 'i18n' => ['tr' => $tx, 'en' => $tx, 'ru' => $tx]], (int) $agent['id'], 'panel');
};

$m = $mk('portfoy1@example.com');
$m2 = $mk('portfoy2@example.com');

$l0 = $new($m, 'draft', 'Ofis öncesi ilan');
t('ekipsiz danışmanın ilanında ofis yok', !is_wp_error($l0) && ec_listing_row($l0)['office_id'] === null);

ec_team_invite($office, 'portfoy1@example.com');
ec_team_accept(ec_agent((int) $m['id']));
ec_team_invite($office, 'portfoy2@example.com');
ec_team_accept(ec_agent((int) $m2['id']));
$m = ec_agent((int) $m['id']);
$l1 = $new($m, 'published', 'Ofiste yayındaki ilan');
$l2 = $new($m, 'draft', 'Ofiste taslak ilan');
t('ekipteki danışmanın ilanı ofise yazıldı', (int) ec_listing_row($l1)['office_id'] === (int) $office['id'] && (int) ec_listing_row($l2)['office_id'] === (int) $office['id']);

// Ayrılma
t('danışman ekipten ayrıldı', ec_team_remove((int) $m['id']) === true);
$r1 = ec_listing_row($l1);
$r2 = ec_listing_row($l2);
t('ofis ilanı taslağa alındı ve kilitlendi', $r1['status'] === 'draft' && (int) $r1['hold_office_id'] === (int) $office['id'] && $r1['hold_prev_status'] === 'published');
t('taslak ilan da kilitlendi', (int) $r2['hold_office_id'] === (int) $office['id'] && $r2['hold_prev_status'] === 'draft');
t('ofis öncesi ilan etkilenmedi', ec_listing_row($l0)['hold_office_id'] === null);
t('ilanlar hâlâ danışmanda', (int) $r1['agent_id'] === (int) $m['id']);

// Kilit: REST ve Telegram
wp_set_current_user((int) $m['wp_user_id']);
$q = new WP_REST_Request('PATCH', '/' . EC_NS . '/listings/' . $l1);
$q->set_body_params(['status' => 'published']);
$res = rest_do_request($q);
t('REST: kilitli ilan yayınlanamaz (423)', $res->get_status() === 423 && ec_listing_row($l1)['status'] === 'draft');
$q = new WP_REST_Request('DELETE', '/' . EC_NS . '/listings/' . $l1);
t('REST: kilitli ilan silinemez', rest_do_request($q)->get_status() === 423);
$q = new WP_REST_Request('PATCH', '/' . EC_NS . '/listings/' . $l0);
$q->set_body_params(['price' => 130000]);
t('REST: kendi (kilitsiz) ilanı düzenlenebilir', rest_do_request($q)->get_status() === 200);
wp_set_current_user(0);
t('Telegram: kilitli ilan düzenlenemez', !ec_tg_can_edit('x', $m, ec_listing_row($l1)) && ec_tg_can_edit('x', $m, ec_listing_row($l0)));

// Aktarım hedefleri
$targets = ec_transfer_targets((int) $office['id']);
t('hedefler: ofis hesabı ve şu anki ekip', in_array((int) $office['id'], $targets, true) && in_array((int) $m2['id'], $targets, true) && !in_array((int) $m['id'], $targets, true));

// Aktar: yalnız l1, ekipteki m2'ye
$n = ec_team_transfer_portfolio((int) $office['id'], (int) $m['id'], (int) $m2['id'], [$l1]);
$r1 = ec_listing_row($l1);
t('1 ilan aktarıldı, önceki durumuna (yayında) döndü', $n === 1 && (int) $r1['agent_id'] === (int) $m2['id'] && $r1['status'] === 'published' && $r1['hold_office_id'] === null);
t('aktarılan ilan ofis portföyünde kaldı', (int) $r1['office_id'] === (int) $office['id']);
t('l2 hâlâ kilitli', (int) ec_listing_row($l2)['hold_office_id'] === (int) $office['id']);

// Danışmana bırak
$n = ec_team_transfer_portfolio((int) $office['id'], (int) $m['id'], 0, [$l2]);
$r2 = ec_listing_row($l2);
t('danışmana bırakıldı: kilit ve ofis bağı kalktı, taslak', $n === 1 && (int) $r2['agent_id'] === (int) $m['id'] && $r2['hold_office_id'] === null && $r2['office_id'] === null && $r2['status'] === 'draft');

// Ofisin danışmanı çıkarması da kilitler; başka ofise geçince yeni ilan yeni ofise yazılır
$l3 = $new(ec_agent((int) $m2['id']), 'published', 'm2 ilanı');
t('ofis danışmanı çıkardı', ec_team_remove((int) $m2['id'], (int) $office['id']) === true);
t('çıkarılan danışmanın ofis ilanları kilitlendi (aktarılan dahil)', (int) ec_listing_row($l3)['hold_office_id'] === (int) $office['id'] && (int) ec_listing_row($l1)['hold_office_id'] === (int) $office['id']);
ec_team_invite($office2, 'portfoy2@example.com');
ec_team_accept(ec_agent((int) $m2['id']));
$l4 = $new(ec_agent((int) $m2['id']), 'draft', 'yeni ofiste ilan');
t('yeni ofiste açılan ilan yeni ofise ait', (int) ec_listing_row($l4)['office_id'] === (int) $office2['id']);
t('eski ofisin kilidi yeni ofise geçişte sürüyor', (int) ec_listing_row($l3)['hold_office_id'] === (int) $office['id']);
t('geçersiz hedefe (başka ofisin üyesi) aktarım listede yok', !in_array((int) $m2['id'], ec_transfer_targets((int) $office['id']), true));

// temizlik
foreach ([$l0, $l1, $l2, $l3, $l4] as $id) {
    $wpdb->delete(ec_table('listing_i18n'), ['listing_id' => $id]);
    $wpdb->delete($L, ['id' => $id]);
}
foreach ([$m, $m2] as $x) {
    $wpdb->delete($A, ['id' => $x['id']]);
    wp_delete_user((int) $x['wp_user_id']);
}
echo "\n" . $GLOBALS['t_ok'] . " başarılı, " . $GLOBALS['t_fail'] . " başarısız\n";
