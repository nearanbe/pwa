<?php
/**
 * Müşteri takibi (1.4): Panelim → Talepler artık basit bir müşteri takip ekranı.
 *
 * - Her talebin (ve elle eklenen müşterinin) aşaması (stage), notları ve hatırlatma zamanı (follow_at, UTC) olur.
 * - Hatırlatma, üyenin bildirim açtığı cihazlara 1 gün, 1 saat ve 5 dakika önce gider (rem_sent: d, h, m bayrakları);
 *   cihazı yoksa ya da gönderilemezse Telegram'dan gider.
 * - Görüntülü gezi talepleri kind = video ile gelir; tarih ve saat mesajda yazar.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_crm_stages(): array
{
    return [
        'new' => ['Yeni', 'New', 'Новый'],
        'contacted' => ['Arandı', 'Contacted', 'Связались'],
        'viewing' => ['Görüşme / gezi planlandı', 'Viewing planned', 'Показ запланирован'],
        'offer' => ['Teklif aşamasında', 'Offer stage', 'Предложение'],
        'won' => ['Satış / kiralama oldu', 'Deal closed', 'Сделка'],
        'lost' => ['Olumsuz', 'Not interested', 'Отказ'],
    ];
}

/** Panelden gelen yerel saat (Europe/Istanbul, "YYYY-MM-DDTHH:MM") → UTC */
function ec_crm_local_to_utc(string $v): ?string
{
    $v = trim($v);
    if ($v === '') {
        return null;
    }
    try {
        $d = new DateTime(str_replace('T', ' ', $v), wp_timezone());
    } catch (Exception $e) {
        return null;
    }
    $d->setTimezone(new DateTimeZone('UTC'));
    return $d->format('Y-m-d H:i:s');
}

function ec_crm_update(int $lead_id, int $agent_id, array $in)
{
    global $wpdb;
    $t = ec_table('leads');
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d AND agent_id = %d", $lead_id, $agent_id), ARRAY_A);
    if (!$row) {
        return new WP_Error('ec_not_found', 'Talep bulunamadı', ['status' => 404]);
    }
    $d = [];
    if (isset($in['stage']) && isset(ec_crm_stages()[$in['stage']])) {
        $d['stage'] = $in['stage'];
        if ($row['status'] === 'new') {
            $d['status'] = 'read';
        }
        if (in_array($in['stage'], ['won', 'lost'], true)) {
            $d['status'] = 'done';
        }
    }
    if (array_key_exists('notes', $in)) {
        $d['notes'] = mb_substr(ec_clean_textarea((string) $in['notes']), 0, 4000);
    }
    if (array_key_exists('follow_at', $in)) {
        $f = ec_crm_local_to_utc((string) $in['follow_at']);
        $d['follow_at'] = $f;
        if ($f !== $row['follow_at']) {
            $d['rem_sent'] = '';
        }
    }
    if (!$d) {
        return true;
    }
    $wpdb->update($t, $d, ['id' => $lead_id]);
    return true;
}

/** Elle müşteri ekleme (telefonla arayan, ofise gelen…) */
function ec_crm_add(int $agent_id, array $in)
{
    global $wpdb;
    $name = mb_substr(sanitize_text_field((string) ($in['name'] ?? '')), 0, 120);
    if ($name === '') {
        return new WP_Error('ec_invalid', 'Ad zorunlu', ['status' => 400, 'fields' => ['name' => 'zorunlu']]);
    }
    $lid = (int) ($in['listing_id'] ?? 0);
    if ($lid && (!($l = ec_listing_row($lid)) || (int) $l['agent_id'] !== $agent_id)) {
        $lid = 0;
    }
    $wpdb->insert(ec_table('leads'), [
        'agent_id' => $agent_id,
        'listing_id' => $lid ?: null,
        'name' => $name,
        'email' => sanitize_email((string) ($in['email'] ?? '')) ?: null,
        'phone' => mb_substr(preg_replace('/[^0-9+ ()-]/', '', (string) ($in['phone'] ?? '')), 0, 40) ?: null,
        'message' => '',
        'notes' => mb_substr(ec_clean_textarea((string) ($in['notes'] ?? '')), 0, 4000),
        'follow_at' => ec_crm_local_to_utc((string) ($in['follow_at'] ?? '')),
        'lang' => 'tr', 'status' => 'read', 'stage' => 'new', 'kind' => 'manual', 'source' => 'manual',
        'created_at' => ec_now(),
    ]);
    return (int) $wpdb->insert_id;
}

/* ---- Hatırlatmalar: 5 dakikada bir ---- */

add_filter('cron_schedules', function ($s) {
    $s['ec_five'] = ['interval' => 300, 'display' => 'Her 5 dakika (AdaCasa)'];
    return $s;
});
add_action('init', function () {
    if (!wp_next_scheduled('ec_crm_tick')) {
        wp_schedule_event(time() + 120, 'ec_five', 'ec_crm_tick');
    }
});
add_action('ec_crm_tick', 'ec_crm_reminders');

/** @return int gönderilen hatırlatma sayısı */
function ec_crm_reminders(): int
{
    global $wpdb;
    $t = ec_table('leads');
    $rows = $wpdb->get_results("SELECT * FROM $t WHERE follow_at IS NOT NULL AND stage NOT IN ('won','lost') AND status <> 'spam'
        AND follow_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE) AND follow_at < DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY) LIMIT 300", ARRAY_A);
    $n = 0;
    foreach ($rows as $r) {
        $left = strtotime($r['follow_at'] . ' UTC') - time();
        $flag = null;
        foreach (['m' => 5 * MINUTE_IN_SECONDS, 'h' => HOUR_IN_SECONDS, 'd' => DAY_IN_SECONDS] as $f => $sec) {
            if ($left <= $sec && !str_contains((string) $r['rem_sent'], $f)) {
                $flag = $f;
                break;
            }
        }
        if (!$flag) {
            continue;
        }
        // Daha büyük aralıkların bayraklarını da işaretle (geç kurulan hatırlatma üç kez birden gitmesin)
        $sent = (string) $r['rem_sent'] . ($flag === 'm' ? 'mhd' : ($flag === 'h' ? 'hd' : 'd'));
        $wpdb->update($t, ['rem_sent' => implode('', array_unique(str_split($sent)))], ['id' => $r['id']]);
        $when = ['m' => '5 dakika sonra', 'h' => '1 saat sonra', 'd' => 'yarın bu saatte'][$flag];
        $time = wp_date('d.m H:i', strtotime($r['follow_at'] . ' UTC'));
        $about = '';
        if ($r['listing_id'] && ($l = ec_listing_row((int) $r['listing_id']))) {
            $about = ' · #' . $l['id'];
        }
        $title = '⏰ Hatırlatma: ' . $r['name'];
        $body = $when . ' (' . $time . ')' . $about . ($r['phone'] ? ' · 📞 ' . $r['phone'] : '') . ($r['notes'] ? "\n" . mb_substr((string) $r['notes'], 0, 120) : '');
        $url = add_query_arg(['bolum' => 'talepler', 'talep' => $r['id']], ec_url_section('panel', 'tr'));
        $pushed = function_exists('ec_push_agent') ? ec_push_agent((int) $r['agent_id'], ['title' => $title, 'body' => $body, 'url' => $url, 'tag' => 'ec-rem-' . $r['id']]) : 0;
        $a = ec_agent((int) $r['agent_id']);
        if (!$pushed && $a && !empty($a['telegram_chat_id']) && function_exists('ec_tg_send') && ec_tg_ready()) {
            ec_tg_send($a['telegram_chat_id'], $title . "\n" . $body . "\n" . $url);
        }
        $n++;
    }
    return $n;
}

/* ---- REST ---- */

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/me/leads/(?P<id>\d+)/crm', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $res = $a ? ec_crm_update((int) $r['id'], (int) $a['id'], (array) ($r->get_json_params() ?: [])) : new WP_Error('ec_forbidden', 'Yetkisiz', ['status' => 403]);
            return is_wp_error($res) ? $res : ['ok' => true];
        },
    ]);
    register_rest_route(EC_NS, '/me/leads/add', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            $a = ec_rest_agent($r);
            $res = $a ? ec_crm_add((int) $a['id'], (array) ($r->get_json_params() ?: [])) : new WP_Error('ec_forbidden', 'Yetkisiz', ['status' => 403]);
            return is_wp_error($res) ? $res : ['ok' => true, 'id' => $res];
        },
    ]);
});
