<?php
/**
 * Gün içi (piyasa) kuru. Türkiye'deki hızlı kur değişimlerinde günlük TCMB kuru geride kaldığı için
 * GBP/TRY, EUR/TRY ve USD/TRY piyasa kurları birkaç dakikada bir çekilir.
 *
 * Kaynak: Twelve Data (ücretsiz anahtarla; 8 istek/dk, 800 istek/gün) → anahtar yoksa ya da hata verirse Yahoo Finance.
 * Güvenlik: her değer resmi (TCMB) kurla karşılaştırılır; ayarlanan yüzdeden (varsayılan %5) fazla sapan değer
 * veri hatası sayılır ve kullanılmaz. Değer 30 dakikadan eskiyse site kendiliğinden resmi kura döner.
 *
 * Sadece TL kurları gün içinde güncellenir. EUR ve USD'nin sterline karşı kuru gün içinde çok az oynadığı için
 * resmi kurdan alınır; böylece Twelve Data'da her çekim 1 istek harcar (5 dakikada bir = günde 288).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_rates_live_settings(): array
{
    return array_merge([
        'enabled' => 0,
        'provider' => 'auto',   // auto | twelvedata | yahoo
        'key' => '',            // Twelve Data API anahtarı
        'interval' => 5,        // dakika; 0 = Twelve Data günlük kotasına göre (~109 sn)
        'max_dev' => 5,         // TCMB'ye göre en çok sapma (%)
        'max_age' => 30,        // dakika; daha eski değer kullanılmaz
    ], (array) get_option('ec_rates_live', []));
}

/** Twelve Data ücretsiz plan: günde 800 istek. Bir istek pay bırakılır (günde en çok 799). */
const EC_TD_DAILY = 799;

/**
 * Gün içi çekim aralığı (saniye). interval=0 → "kotaya göre": görev her dakika uyanır, ama Twelve Data isteği
 * ec_td_due() ile güne eşit yayılır (ortalama 86400/799 ≈ 108 sn, günde en çok 799). Sunucu zamanlayıcısı
 * wp-cron.php'yi dakikada bir çağırmalı (Plesk görev 71).
 */
function ec_rates_live_seconds(): int
{
    $m = (int) ec_rates_live_settings()['interval'];
    return $m > 0 ? $m * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS;
}

/** Kotaya göre modda şimdi istek sırası geldi mi? (UTC günün geçen kısmına düşen pay) */
function ec_td_due(): bool
{
    $elapsed = time() - strtotime(gmdate('Y-m-d') . ' 00:00:00 UTC');
    return ec_td_used() < min(EC_TD_DAILY, (int) floor($elapsed * EC_TD_DAILY / DAY_IN_SECONDS) + 1);
}

/** Bugün (UTC; Twelve Data kotası UTC gece yarısı sıfırlanır) yapılan Twelve Data isteği */
function ec_td_used(): int
{
    $d = (array) get_option('ec_td_count', []);
    return ($d['day'] ?? '') === gmdate('Y-m-d') ? (int) $d['n'] : 0;
}

/** Kaynaktan GBP/TRY çeker. @return array{rate: float, time: int, source: string}|WP_Error */
function ec_rates_live_fetch_twelvedata(string $key)
{
    $used = ec_td_used();
    if ($used >= EC_TD_DAILY) {
        return new WP_Error('ec_live', 'Twelve Data: günlük kota doldu (' . $used . '), Yahoo kullanılıyor');
    }
    update_option('ec_td_count', ['day' => gmdate('Y-m-d'), 'n' => $used + 1], false);
    $url = add_query_arg(['symbol' => 'GBP/TRY', 'apikey' => $key], 'https://api.twelvedata.com/exchange_rate');
    $res = wp_remote_get($url, ['timeout' => 10]);
    if (is_wp_error($res)) {
        return $res;
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    if (!is_array($j) || !isset($j['rate'])) {
        return new WP_Error('ec_live', 'Twelve Data: ' . (is_array($j) ? ($j['message'] ?? 'beklenmeyen cevap') : 'cevap okunamadı'));
    }
    return ['rate' => (float) $j['rate'], 'time' => (int) ($j['timestamp'] ?? time()), 'source' => 'twelvedata'];
}

function ec_rates_live_fetch_yahoo()
{
    $res = wp_remote_get('https://query1.finance.yahoo.com/v8/finance/chart/GBPTRY=X?interval=5m&range=1d', [
        'timeout' => 10,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
    ]);
    if (is_wp_error($res)) {
        return $res;
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    $m = $j['chart']['result'][0]['meta'] ?? null;
    if (!$m || empty($m['regularMarketPrice'])) {
        return new WP_Error('ec_live', 'Yahoo: ' . ($j['chart']['error']['description'] ?? 'beklenmeyen cevap'));
    }
    return ['rate' => (float) $m['regularMarketPrice'], 'time' => (int) ($m['regularMarketTime'] ?? time()), 'source' => 'yahoo'];
}

/** Sapma kontrolü: TCMB'ye göre yüzde fark */
function ec_rates_live_deviation(float $live, float $official): float
{
    return $official > 0 ? abs($live - $official) / $official * 100 : 100.0;
}

/** @return array|WP_Error kaydedilen değer */
function ec_rates_live_update()
{
    $s = ec_rates_live_settings();
    $off = ec_rates_official();
    $tries = [];
    if ($s['provider'] !== 'yahoo' && $s['key']) {
        $tries[] = fn() => ec_rates_live_fetch_twelvedata($s['key']);
    }
    if ($s['provider'] !== 'twelvedata' || !$s['key']) {
        $tries[] = 'ec_rates_live_fetch_yahoo';
    }
    $errors = [];
    foreach ($tries as $f) {
        $r = $f();
        if (is_wp_error($r)) {
            $errors[] = $r->get_error_message();
            continue;
        }
        $dev = ec_rates_live_deviation($r['rate'], (float) $off['TRY']);
        if ($dev > (float) $s['max_dev']) {
            $errors[] = sprintf('%s: %.4f reddedildi (TCMB %.4f, sapma %%%.1f)', $r['source'], $r['rate'], $off['TRY'], $dev);
            continue;
        }
        if (time() - $r['time'] > 6 * HOUR_IN_SECONDS && $r['source'] === 'yahoo') {
            // Hafta sonu piyasa kapalı: son fiyat geçerli, ama zamanı olduğu gibi saklanır
        }
        $data = [
            'rates' => ['TRY' => round($r['rate'], 4)],
            'market_time' => $r['time'],
            'fetched_at' => time(),
            'source' => $r['source'],
            'deviation' => round($dev, 2),
            'error' => $errors ? implode(' | ', $errors) : '',
        ];
        update_option('ec_rates_live_data', $data, false);
        unset($GLOBALS['ec_live_cache']);
        ec_recompute_price_gbp_currency('TRY');
        return $data;
    }
    $data = (array) get_option('ec_rates_live_data', []);
    $data['error'] = implode(' | ', $errors) ?: 'kaynak yok';
    $data['failed_at'] = time();
    update_option('ec_rates_live_data', $data, false);
    return new WP_Error('ec_live', $data['error']);
}

/** Kullanılabilir gün içi kur (açık, taze ve doğrulanmış), yoksa null */
function ec_rates_live_current(): ?array
{
    if (array_key_exists('ec_live_cache', $GLOBALS)) {
        return $GLOBALS['ec_live_cache'];
    }
    $cache = &$GLOBALS['ec_live_cache'];
    $s = ec_rates_live_settings();
    $d = get_option('ec_rates_live_data');
    if (!$s['enabled'] || !is_array($d) || empty($d['rates']['TRY']) || empty($d['fetched_at'])) {
        return $cache = null;
    }
    if (time() - (int) $d['fetched_at'] > (int) $s['max_age'] * MINUTE_IN_SECONDS) {
        return $cache = null;
    }
    return $cache = $d;
}

/** Tek para birimindeki ilanların GBP karşılığını yeniden hesaplar */
function ec_recompute_price_gbp_currency(string $cur): void
{
    global $wpdb;
    $r = ec_rates()[$cur] ?? 0;
    if ($r > 0) {
        $wpdb->query($wpdb->prepare('UPDATE ' . ec_table('listings') . ' SET price_gbp = ROUND(price / %f, 2) WHERE currency = %s', $r, $cur));
    }
}

add_filter('cron_schedules', function ($s) {
    $sec = ec_rates_live_seconds();
    $s['ec_live_rates'] = ['interval' => $sec, 'display' => "Her $sec saniyede bir (gün içi kur)"];
    return $s;
});

add_action('init', function () {
    $s = ec_rates_live_settings();
    $next = wp_next_scheduled('ec_rates_live');
    if ($s['enabled'] && !$next) {
        wp_schedule_event(time() + 30, 'ec_live_rates', 'ec_rates_live');
    } elseif (!$s['enabled'] && $next) {
        wp_clear_scheduled_hook('ec_rates_live');
    }
});

add_action('ec_rates_live', function () {
    $s = ec_rates_live_settings();
    if (!$s['enabled'] || get_transient('ec_rates_live_lock')) {
        return;
    }
    // Kotaya göre mod: sıra gelmediyse bekle (mevcut kur 30 dk geçerli kalır)
    if ((int) $s['interval'] === 0 && $s['key'] && $s['provider'] !== 'yahoo' && !ec_td_due()) {
        return;
    }
    set_transient('ec_rates_live_lock', 1, 50);
    ec_rates_live_update();
    delete_transient('ec_rates_live_lock');
});

/** Ayar değişince zamanlamayı yenile */
add_action('update_option_ec_rates_live', function () {
    unset($GLOBALS['ec_live_cache']);
    wp_clear_scheduled_hook('ec_rates_live');
});

/** Zamanlanmış görevlerin çalıştığını izle (Canlıya çıkış kontrolü okur) */
add_action('init', function () {
    if (wp_doing_cron() && time() - (int) get_option('ec_cron_last', 0) > 60) {
        update_option('ec_cron_last', time(), false);
    }
});
