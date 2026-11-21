<?php
/**
 * Ofis ekibi (1.1): emlak ofisi / inşaat firması, danışman hesaplarını kendi ekibine ekler ya da çıkarır.
 *
 * - Ofis, danışmanın e-postasıyla davet eder (agents.team_invite = ofis no). Danışman Panelim'de kabul/ret eder.
 * - Kabul edince agents.parent_id = ofis no:
 *     · ofisin paketleri (otomasyon kotası, vitrin kredileri) danışmana da geçerlidir (kullanım ortak sayılır),
 *     · onaylı ofisin ekibindeki danışmanın ilanları onay beklemeden yayına girer,
 *     · ilan sayfasında danışman kartında "X ekibi" yazar.
 * - Ofis istediği zaman danışmanı çıkarır; danışman istediği zaman ayrılır. Danışmanın ilanları kendisinde kalır.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Ekip kurabilen hesap türleri */
function ec_team_can_lead(?array $agent): bool
{
    return $agent && in_array($agent['kind'], ['ofis', 'insaat'], true) && $agent['status'] === 'active';
}

/** Danışmanın bağlı olduğu (etkin) ofis */
function ec_team_parent(?array $agent): ?array
{
    if (!$agent || empty($agent['parent_id'])) {
        return null;
    }
    $p = ec_agent((int) $agent['parent_id']);
    return $p && $p['status'] === 'active' ? $p : null;
}

function ec_team_members(int $office_id): array
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        'SELECT a.*, (SELECT COUNT(*) FROM ' . ec_table('listings') . " l WHERE l.agent_id = a.id AND l.status = 'published') AS live
         FROM " . ec_table('agents') . ' a WHERE a.parent_id = %d ORDER BY a.name',
        $office_id
    ), ARRAY_A) ?: [];
}

function ec_team_pending(int $office_id): array
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare('SELECT id, name, email FROM ' . ec_table('agents') . ' WHERE team_invite = %d', $office_id), ARRAY_A) ?: [];
}

/** @return array|WP_Error davet edilen danışman */
function ec_team_invite(array $office, string $email)
{
    global $wpdb;
    if (!ec_team_can_lead($office)) {
        return new WP_Error('ec_forbidden', 'Sadece onaylı emlak ofisi ve inşaat firması hesapları ekip kurabilir.', ['status' => 403]);
    }
    $email = sanitize_email($email);
    $m = $email ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('agents') . ' WHERE email = %s AND id <> %d', $email, $office['id']), ARRAY_A) : null;
    if (!$m) {
        return new WP_Error('ec_not_found', 'Bu e-postayla kayıtlı üye yok. Danışmanın önce siteye ücretsiz “Bireysel Danışman” olarak kayıt olması gerekir.', ['status' => 404]);
    }
    if (in_array($m['kind'], ['ofis', 'insaat'], true)) {
        return new WP_Error('ec_invalid', 'Ofis ya da firma hesapları başka bir ekibe eklenemez.', ['status' => 400]);
    }
    if ((int) $m['parent_id'] === (int) $office['id']) {
        return new WP_Error('ec_invalid', 'Bu danışman zaten ekibinizde.', ['status' => 400]);
    }
    if ($m['parent_id']) {
        return new WP_Error('ec_invalid', 'Bu danışman başka bir ofisin ekibinde. Önce oradan ayrılması gerekir.', ['status' => 409]);
    }
    $wpdb->update(ec_table('agents'), ['team_invite' => (int) $office['id']], ['id' => $m['id']]);
    $lang = ec_member_lang((int) $m['wp_user_id']);
    ec_notify_member($m, 'Ekip daveti: ' . $office['name'], "Merhaba {$m['name']},\n\n{$office['name']} sizi ekibine eklemek istiyor. Kabul ederseniz ilanlarınız onay beklemeden yayına girer; ofis isterse paketlerini (101evler/hangiev aktarımı, sosyal medya, vitrin) sizinle paylaşır.\n\nPanelim'den kabul ya da ret edebilirsiniz:\n" . ec_url_section('panel', $lang));
    if (!empty($m['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
        ec_tg_send($m['telegram_chat_id'], "👥 {$office['name']} sizi ekibine davet etti. Panelim'den kabul edebilirsiniz.");
    }
    return $m;
}

function ec_team_accept(array $member)
{
    global $wpdb;
    $office = $member['team_invite'] ? ec_agent((int) $member['team_invite']) : null;
    if (!$office || !ec_team_can_lead($office)) {
        $wpdb->update(ec_table('agents'), ['team_invite' => null], ['id' => $member['id']]);
        return new WP_Error('ec_not_found', 'Davet geçerli değil.', ['status' => 404]);
    }
    $wpdb->update(ec_table('agents'), ['parent_id' => (int) $office['id'], 'team_invite' => null, 'updated_at' => ec_now()], ['id' => $member['id']]);
    ec_notify_member($office, 'Ekibinize katıldı: ' . $member['name'], "{$member['name']} ekip davetinizi kabul etti. Paketleriniz artık bu danışman için de geçerli.");
    return $office;
}

function ec_team_decline(array $member): void
{
    global $wpdb;
    $wpdb->update(ec_table('agents'), ['team_invite' => null], ['id' => $member['id']]);
}

/** Ofis danışmanı çıkarır ya da danışman ayrılır */
function ec_team_remove(int $member_id, ?int $office_id = null): bool
{
    global $wpdb;
    $m = ec_agent($member_id);
    if (!$m || !$m['parent_id'] || ($office_id && (int) $m['parent_id'] !== $office_id)) {
        return false;
    }
    $wpdb->update(ec_table('agents'), ['parent_id' => null, 'team_share' => 0, 'team_lim_listings' => 0, 'team_lim_promos' => 0, 'updated_at' => ec_now()], ['id' => $member_id]);
    $office = ec_agent((int) $m['parent_id']);
    if ($office_id) {
        ec_notify_member($m, 'Ofis ekibinden çıkarıldınız', ($office['name'] ?? 'Ofis') . ' sizi ekibinden çıkardı. İlanlarınız hesabınızda kalır; ofisin paketleri artık sizin için geçerli değil.');
    } elseif ($office) {
        ec_notify_member($office, 'Ekibinizden ayrıldı: ' . $m['name'], "{$m['name']} ekibinizden ayrıldı.");
    }
    return true;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/me/team', [
        'methods' => 'GET',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            if (!$a) {
                return ['members' => []];
            }
            $inv = $a['team_invite'] ? ec_agent((int) $a['team_invite']) : null;
            $par = ec_team_parent($a);
            return [
                'can_lead' => ec_team_can_lead($a),
                'members' => array_map(fn($m) => ['id' => (int) $m['id'], 'name' => $m['name'], 'email' => $m['email'], 'live' => (int) $m['live']], ec_team_can_lead($a) ? ec_team_members((int) $a['id']) : []),
                'pending' => ec_team_can_lead($a) ? ec_team_pending((int) $a['id']) : [],
                'invite' => $inv ? ['id' => (int) $inv['id'], 'name' => $inv['name']] : null,
                'office' => $par ? ['id' => (int) $par['id'], 'name' => $par['name']] : null,
            ];
        },
    ]);
    register_rest_route(EC_NS, '/me/team/(?P<action>invite|accept|decline|leave|remove|cancel)', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $a = ec_rest_agent($r);
            if (!$a) {
                return new WP_Error('ec_no_agent', 'Üye hesabı gerekli', ['status' => 400]);
            }
            switch ($r['action']) {
                case 'invite':
                    $m = ec_team_invite($a, (string) $r->get_param('email'));
                    return is_wp_error($m) ? $m : ['ok' => true, 'name' => $m['name']];
                case 'accept':
                    $o = ec_team_accept($a);
                    return is_wp_error($o) ? $o : ['ok' => true, 'office' => $o['name']];
                case 'decline':
                    ec_team_decline($a);
                    return ['ok' => true];
                case 'leave':
                    return ['ok' => ec_team_remove((int) $a['id'])];
                case 'remove':
                    return ec_team_remove((int) $r->get_param('member'), (int) $a['id']) ? ['ok' => true] : new WP_Error('ec_not_found', 'Ekip üyesi bulunamadı', ['status' => 404]);
                case 'cancel':
                    $wpdb->update(ec_table('agents'), ['team_invite' => null], ['id' => (int) $r->get_param('member'), 'team_invite' => (int) $a['id']]);
                    return ['ok' => true];
            }
            return new WP_Error('ec_invalid', 'Geçersiz işlem', ['status' => 400]);
        },
    ]);
});


/* ---------------- Paket paylaşımı (1.1.2) ---------------- */

/**
 * Bu ay danışmanın OFİS paketinden kullandığı: ['listings' => n, 'promos' => n]
 * (kendi paketinden kullandıkları sayılmaz)
 */
function ec_team_month_usage(int $member_id): array
{
    global $wpdb;
    $since = gmdate('Y-m-01 00:00:00');
    $l = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . ec_table('usage') . ' u JOIN ' . ec_table('subscriptions') . ' s ON s.id = u.subscription_id WHERE u.agent_id = %d AND s.agent_id <> %d AND u.created_at >= %s',
        $member_id, $member_id, $since
    ));
    $p = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . ec_table('promo_usage') . ' u JOIN ' . ec_table('subscriptions') . ' s ON s.id = u.subscription_id WHERE u.agent_id = %d AND s.agent_id <> %d AND u.created_at >= %s',
        $member_id, $member_id, $since
    ));
    return ['listings' => $l, 'promos' => $p];
}

/** Ofis paketinden bu ay kalan hak. null = sınırsız, 0 = bitti / paylaşım kapalı */
function ec_team_left(int $member_id, string $what): ?int
{
    $m = ec_agent($member_id);
    if (!$m || empty($m['parent_id'])) {
        return null;
    }
    if (empty($m['team_share'])) {
        return 0;
    }
    $lim = (int) ($what === 'listings' ? $m['team_lim_listings'] : $m['team_lim_promos']);
    if (!$lim) {
        return null;
    }
    return max(0, $lim - ec_team_month_usage($member_id)[$what]);
}

/** Ofis, ekip üyesinin paylaşım ayarını değiştirir */
function ec_team_set_share(int $office_id, int $member_id, bool $share, int $limListings, int $limPromos): bool
{
    global $wpdb;
    $m = ec_agent($member_id);
    if (!$m || (int) $m['parent_id'] !== $office_id) {
        return false;
    }
    $wpdb->update(ec_table('agents'), [
        'team_share' => $share ? 1 : 0, 'team_lim_listings' => max(0, $limListings), 'team_lim_promos' => max(0, $limPromos), 'updated_at' => ec_now(),
    ], ['id' => $member_id]);
    return true;
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/me/team/share', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            if (!$a || !ec_team_can_lead($a)) {
                return new WP_Error('ec_forbidden', 'Yetkisiz', ['status' => 403]);
            }
            $ok = ec_team_set_share((int) $a['id'], (int) $r->get_param('member'), (bool) $r->get_param('share'), (int) $r->get_param('lim_listings'), (int) $r->get_param('lim_promos'));
            return $ok ? ['ok' => true] : new WP_Error('ec_not_found', 'Ekip üyesi bulunamadı', ['status' => 404]);
        },
    ]);
});
