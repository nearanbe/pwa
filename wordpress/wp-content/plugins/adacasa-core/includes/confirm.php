<?php
/**
 * İlan teyidi (1.4): yayındaki ilan 30 günde bir "Hâlâ satışta/kirada mı?" diye ilan sahibine sorulur.
 *
 * - Soru Telegram'dan düğmelerle ([✅ Evet] [🏷 Satıldı/Kiralandı] [⏸ Yayından kaldır]) gider; Telegram bağlı değilse
 *   e-postayla imzalı bağlantılar gider (/?ec_cf=İLAN&a=yes|sold|off&t=İMZA).
 * - 7 gün cevap gelmezse ilan pasife alınır, ilan sahibine haber verilir (panelden ya da Telegram'dan yeniden yayınlanır).
 * - Sahibinin ilanı panelden düzenlemesi / "Hâlâ güncel" demesi de teyit sayılır (confirmed_at).
 * - İlan sayfasında "İlan sahibi X gün önce teyit etti" yazar.
 * - Demo ilanlar ve demo ofisler sorulmaz.
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_CONFIRM_DAYS = 30;
const EC_CONFIRM_GRACE = 7;

function ec_confirm_sig(int $id, string $a): string
{
    return substr(hash_hmac('sha256', 'cf|' . $id . '|' . $a, wp_salt('nonce')), 0, 24);
}

function ec_confirm_link(int $id, string $a): string
{
    return add_query_arg(['ec_cf' => $id, 'a' => $a, 't' => ec_confirm_sig($id, $a)], home_url('/'));
}

/** Teyit cevabını uygular. $a = yes | sold | off. @return string kullanıcıya gösterilecek metin */
function ec_confirm_apply(int $id, string $a): string
{
    global $wpdb;
    $row = ec_listing_row($id);
    if (!$row) {
        return 'İlan bulunamadı.';
    }
    if ($a === 'yes') {
        if ($row['status'] === 'passive' && $row['confirm_asked_at'] && !$row['hold_office_id']) {
            // Cevap gecikti ve ilan pasife alınmıştı: yeniden yayına al
            ec_listing_update($id, ['status' => 'published']);
        }
        $wpdb->update(ec_table('listings'), ['confirmed_at' => ec_now(), 'confirm_asked_at' => null], ['id' => $id]);
        return "✅ Teşekkürler! #$id güncel olarak işaretlendi. 30 gün sonra tekrar soracağız.";
    }
    if ($a === 'sold') {
        ec_listing_update($id, ['status' => 'sold']);
        $wpdb->update(ec_table('listings'), ['confirm_asked_at' => null], ['id' => $id]);
        return "🏷 #$id " . ($row['mode'] === 'sale' ? 'satıldı' : 'kiralandı') . ' olarak işaretlendi.';
    }
    if ($a === 'off') {
        ec_listing_update($id, ['status' => 'passive']);
        $wpdb->update(ec_table('listings'), ['confirm_asked_at' => null], ['id' => $id]);
        return "⏸ #$id yayından kaldırıldı. Panelden ya da Telegram'dan (/duzenle $id) yeniden yayınlayabilirsiniz.";
    }
    return 'Geçersiz işlem.';
}

function ec_confirm_is_demo(array $row, ?array $agent): bool
{
    return $row['source'] === 'demo' || !$agent || preg_match('/@example\.(com|org|net)$/i', (string) ($agent['email'] ?? ''));
}

/** Teyit sorusunu gönderir */
function ec_confirm_ask(array $row): bool
{
    global $wpdb;
    $agent = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
    if (ec_confirm_is_demo($row, $agent)) {
        $wpdb->update(ec_table('listings'), ['confirmed_at' => ec_now()], ['id' => $row['id']]); // demo: sormadan ertele
        return false;
    }
    $lang = ec_member_lang((int) $agent['wp_user_id']);
    $it = ec_listings_present([$row], $lang)[0];
    $soldWord = $row['mode'] === 'sale' ? 'Satıldı' : 'Kiralandı';
    $still = $row['mode'] === 'sale' ? 'hâlâ satışta' : 'hâlâ kiralık';
    $sent = false;
    if (!empty($agent['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
        $r = ec_tg_send($agent['telegram_chat_id'], "🔎 <b>#{$row['id']} {$still} mı?</b>\n" . ec_tg_esc($it['title']) . "\n" . $it['url'] . "\n\nAlıcılara yalnızca güncel ilanları gösteriyoruz. " . EC_CONFIRM_GRACE . ' gün içinde cevap gelmezse ilan yayından kaldırılır.', [
            [['text' => '✅ Evet, güncel', 'callback_data' => 'cfy:' . $row['id']], ['text' => '🏷 ' . $soldWord, 'callback_data' => 'cfs:' . $row['id']]],
            [['text' => '⏸ Yayından kaldır', 'callback_data' => 'cfo:' . $row['id']]],
        ]);
        $sent = !is_wp_error($r);
    }
    if (!$sent && !empty($agent['email'])) {
        $sent = (bool) wp_mail($agent['email'], get_bloginfo('name') . ": #{$row['id']} {$still} mı?",
            "Merhaba {$agent['name']},\n\n#{$row['id']} {$it['title']}\n{$it['url']}\n\nAlıcılara yalnızca güncel ilanları gösteriyoruz. Lütfen tek tıkla cevap verin:\n\n"
            . "✅ Evet, güncel: " . ec_confirm_link((int) $row['id'], 'yes') . "\n"
            . "🏷 $soldWord: " . ec_confirm_link((int) $row['id'], 'sold') . "\n"
            . "⏸ Yayından kaldır: " . ec_confirm_link((int) $row['id'], 'off') . "\n\n"
            . EC_CONFIRM_GRACE . " gün içinde cevap gelmezse ilan yayından kaldırılır; panelden yeniden yayınlayabilirsiniz.\n\nİpucu: Telegram'ı bağlarsanız bu soru tek dokunuşla cevaplanır: " . add_query_arg('bolum', 'telegram', ec_url_section('panel', $lang)));
    }
    $wpdb->update(ec_table('listings'), ['confirm_asked_at' => ec_now()], ['id' => $row['id']]);
    return $sent;
}

/** Saatlik: soruları gönder, süresi geçenleri pasife al. @return array [sorulan, pasife alınan] */
function ec_confirm_run(): array
{
    global $wpdb;
    $t = ec_table('listings');
    $asked = 0;
    $off = 0;
    $due = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $t WHERE status = 'published' AND confirm_asked_at IS NULL AND COALESCE(confirmed_at, published_at, created_at) < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY) ORDER BY id LIMIT 100",
        EC_CONFIRM_DAYS
    ), ARRAY_A);
    foreach ($due as $row) {
        $asked += ec_confirm_ask($row) ? 1 : 0;
    }
    $late = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $t WHERE status = 'published' AND confirm_asked_at IS NOT NULL AND confirm_asked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY) ORDER BY id LIMIT 100",
        EC_CONFIRM_GRACE
    ), ARRAY_A);
    foreach ($late as $row) {
        ec_listing_update((int) $row['id'], ['status' => 'passive']);
        $off++;
        $agent = $row['agent_id'] ? ec_agent((int) $row['agent_id']) : null;
        if ($agent) {
            $it = ec_listings_present([$row], 'tr')[0];
            $msg = "⏸ #{$row['id']} {$it['title']}\n" . EC_CONFIRM_GRACE . " gündür teyit gelmediği için yayından kaldırıldı.\nHâlâ güncelse tek tıkla yeniden yayına alın: " . ec_confirm_link((int) $row['id'], 'yes');
            if (!empty($agent['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
                ec_tg_send($agent['telegram_chat_id'], $msg, [[['text' => '✅ Güncel, yeniden yayınla', 'callback_data' => 'cfy:' . $row['id']]]]);
            } else {
                ec_notify_member($agent, "#{$row['id']} yayından kaldırıldı (teyit gelmedi)", $msg);
            }
        }
    }
    return [$asked, $off];
}
add_action('ec_alerts_hourly', 'ec_confirm_run');

/* E-postadaki bağlantılar */
add_action('template_redirect', function () {
    if (!isset($_GET['ec_cf'], $_GET['a'], $_GET['t'])) {
        return;
    }
    $id = absint($_GET['ec_cf']);
    $a = sanitize_key(wp_unslash($_GET['a']));
    $ok = in_array($a, ['yes', 'sold', 'off'], true) && hash_equals(ec_confirm_sig($id, $a), sanitize_text_field(wp_unslash($_GET['t'])));
    nocache_headers();
    header('X-Robots-Tag: noindex');
    $msg = $ok ? ec_confirm_apply($id, $a) : 'Bağlantı geçersiz.';
    wp_die('<p style="font-size:18px">' . esc_html($msg) . '</p><p><a href="' . esc_url(ec_url_section('panel', 'tr')) . '">Panelim →</a></p>', esc_html(get_bloginfo('name')), ['response' => 200]);
});

/* Panel: "Hâlâ güncel" düğmesi ve ilan sahibinin düzenlemesi teyit sayılır */
add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/listings/(?P<id>\d+)/confirm', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_own_listing',
        'callback' => function (WP_REST_Request $r) {
            return ['ok' => true, 'message' => ec_confirm_apply((int) $r['id'], 'yes')];
        },
    ]);
});
add_filter('rest_request_after_callbacks', function ($res, $handler, $req) {
    if ($req instanceof WP_REST_Request && $req->get_method() === 'PATCH' && preg_match('~^/' . preg_quote(EC_NS, '~') . '/listings/(\d+)$~', $req->get_route(), $m) && !is_wp_error($res)) {
        global $wpdb;
        $wpdb->update(ec_table('listings'), ['confirmed_at' => ec_now(), 'confirm_asked_at' => null], ['id' => (int) $m[1], 'status' => 'published']);
    }
    return $res;
}, 10, 3);
