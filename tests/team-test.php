<?php
/**
 * Ofis ekibi (1.1) testi. Çalıştırma: wp eval-file tests/team-test.php (taze demo verisiyle)
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

// Ofis ve iki bireysel danışman (test hesapları)
$office = $wpdb->get_row("SELECT * FROM $A WHERE kind = 'ofis' AND status = 'active' ORDER BY id LIMIT 1", ARRAY_A);
$office2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $A WHERE kind = 'ofis' AND status = 'active' AND id <> %d ORDER BY id LIMIT 1", $office['id']), ARRAY_A);
$mk = function (string $email) use ($wpdb, $A) {
    $wpdb->delete($A, ['email' => $email]);
    $uid = username_exists('t_' . md5($email)) ?: wp_insert_user(['user_login' => 't_' . md5($email), 'user_email' => $email, 'user_pass' => wp_generate_password(), 'role' => 'ec_member']);
    $wpdb->insert($A, ['wp_user_id' => $uid, 'kind' => 'danisman', 'name' => 'Test ' . $email, 'email' => $email, 'phone' => '+905330000000', 'slug' => ec_agent_unique_slug('test ' . $email), 'status' => 'active', 'email_verified' => 1, 'created_at' => ec_now(), 'updated_at' => ec_now()]);
    return ec_agent((int) $wpdb->insert_id);
};
$m1 = $mk('ekip1@example.com');
$m2 = $mk('ekip2@example.com');

t('danışman ekip kuramaz', !ec_team_can_lead($m1) && is_wp_error(ec_team_invite($m1, 'ekip2@example.com')));
t('kayıtsız e-posta için açık hata', ($e = ec_team_invite($office, 'yok@example.com')) instanceof WP_Error && $e->get_error_code() === 'ec_not_found');
t('ofis başka ofisi ekibe ekleyemez', is_wp_error(ec_team_invite($office, $office2['email'])));
t('ekipsiz danışmanın ilanı onaya düşer', ec_listing_target_status($m1, 'published') === 'pending');

$r = ec_team_invite($office, 'ekip1@example.com');
$m1 = ec_agent((int) $m1['id']);
t('davet gönderildi (bekliyor)', !is_wp_error($r) && (int) $m1['team_invite'] === (int) $office['id'] && !$m1['parent_id']);
t('bekleyen davet ofiste listeleniyor', in_array((int) $m1['id'], array_map('intval', array_column(ec_team_pending((int) $office['id']), 'id')), true));
$acc = ec_team_accept($m1);
$m1 = ec_agent((int) $m1['id']);
t('kabul edince ekibe katıldı', !is_wp_error($acc) && (int) $m1['parent_id'] === (int) $office['id'] && !$m1['team_invite']);
t('ekip listesinde görünüyor', in_array((int) $m1['id'], array_map('intval', array_column(ec_team_members((int) $office['id']), 'id')), true));
t('ekipteki danışmanın ilanı onaysız yayına girer', ec_listing_target_status($m1, 'published') === 'published');

// Paket paylaşımı
$pro = $wpdb->get_var("SELECT id FROM " . ec_table('packages') . " WHERE name LIKE 'Vitrin Paketi Pro%' LIMIT 1");
$sub = ec_subscription_grant((int) $office['id'], ['package_id' => (int) $pro]);
t('ofisin paketi danışmana da geçerli', count(ec_subscriptions_active((int) $m1['id'])) >= 1 && ec_promo_credits((int) $m1['id'])['home'] === ec_promo_credits((int) $office['id'])['home']);
$lid = (int) $wpdb->get_var("SELECT id FROM " . ec_table('listings') . " WHERE status = 'published' ORDER BY id DESC LIMIT 1");
$wpdb->update(ec_table('listings'), ['agent_id' => $m1['id']], ['id' => $lid]);
$before = ec_promo_credits((int) $office['id'])['featured'];
$pa = ec_promo_apply($lid, 'featured', $m1);
t('danışman ofisin vitrin kredisini kullandı (ortak kredi düştü)', !is_wp_error($pa) && ec_promo_credits((int) $office['id'])['featured'] === $before - 1);
$pub = ec_agent_public($m1);
t('danışman kartında ofis adı', ($pub['team_of']['name'] ?? '') === $office['name']);

// Başka ofis aynı danışmanı davet edemez
t('başka ekipteki danışman davet edilemez', ($e = ec_team_invite($office2, 'ekip1@example.com')) instanceof WP_Error && $e->get_error_code() === 'ec_invalid');

// Ret ve çıkarma
ec_team_invite($office, 'ekip2@example.com');
ec_team_decline(ec_agent((int) $m2['id']));
t('reddedilen davet silindi', !ec_agent((int) $m2['id'])['team_invite'] && !ec_agent((int) $m2['id'])['parent_id']);
t('ofis başka ofisin üyesini çıkaramaz', ec_team_remove((int) $m1['id'], (int) $office2['id']) === false);
t('ofis danışmanı çıkardı', ec_team_remove((int) $m1['id'], (int) $office['id']) === true && !ec_agent((int) $m1['id'])['parent_id']);
t('çıkarılınca ofis paketi geçerli değil', ec_promo_credits((int) $m1['id'])['featured'] === 0);
ec_team_invite($office, 'ekip1@example.com');
ec_team_accept(ec_agent((int) $m1['id']));
t('danışman ekipten ayrıldı', ec_team_remove((int) $m1['id']) === true && !ec_agent((int) $m1['id'])['parent_id']);

// REST
wp_set_current_user((int) $m2['wp_user_id']);
ec_team_invite($office, 'ekip2@example.com');
$res = rest_do_request(new WP_REST_Request('GET', '/' . EC_NS . '/me/team'));
t('REST: danışman daveti görüyor', ($res->get_data()['invite']['id'] ?? 0) === (int) $office['id']);
$res = rest_do_request(new WP_REST_Request('POST', '/' . EC_NS . '/me/team/accept'));
t('REST: kabul', $res->get_status() === 200 && (int) ec_agent((int) $m2['id'])['parent_id'] === (int) $office['id']);
wp_set_current_user(0);

// temizlik
$wpdb->update(ec_table('listings'), ['agent_id' => $office['id']], ['id' => $lid]);
foreach ([$m1, $m2] as $m) {
    $wpdb->delete($A, ['id' => $m['id']]);
    wp_delete_user((int) $m['wp_user_id']);
}
echo "\n" . $GLOBALS['t_ok'] . " başarılı, " . $GLOBALS['t_fail'] . " başarısız\n";
