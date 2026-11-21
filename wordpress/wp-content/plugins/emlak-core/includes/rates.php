<?php
/**
 * Otomatik döviz kurları. Günde bir kez (ec_daily) çekilir.
 * Kaynak 1: Frankfurter (Avrupa Merkez Bankası referans kurları, anahtar gerektirmez)
 * Kaynak 2: open.er-api.com (yedek, anahtar gerektirmez)
 * Gelen değer makul aralıkta değilse kaydedilmez; eski kur korunur.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 1 GBP için kabul edilebilir aralıklar (bozuk veriye karşı koruma) */
const EC_RATE_BOUNDS = ['EUR' => [0.8, 2.0], 'USD' => [0.8, 2.6], 'TRY' => [10, 1000]];

function ec_rates_sources(): array
{
    return [
        'frankfurter' => [
            'url' => 'https://api.frankfurter.app/latest?from=GBP&to=EUR,USD,TRY',
            'pick' => fn(array $j) => $j['rates'] ?? null,
            'label' => 'Avrupa Merkez Bankası (Frankfurter)',
        ],
        'er-api' => [
            'url' => 'https://open.er-api.com/v6/latest/GBP',
            'pick' => fn(array $j) => ($j['result'] ?? '') === 'success' ? ($j['rates'] ?? null) : null,
            'label' => 'ExchangeRate-API (open.er-api.com)',
        ],
    ];
}

/**
 * Kurları çeker, doğrular, kaydeder ve ilanların GBP karşılığını yeniden hesaplar.
 * @return array|WP_Error yeni kurlar
 */
function ec_rates_update()
{
    $errors = [];
    foreach (ec_rates_sources() as $key => $src) {
        $res = wp_remote_get($src['url'], ['timeout' => 15, 'headers' => ['Accept' => 'application/json']]);
        if (is_wp_error($res)) {
            $errors[] = $key . ': ' . $res->get_error_message();
            continue;
        }
        if (wp_remote_retrieve_response_code($res) !== 200) {
            $errors[] = $key . ': HTTP ' . wp_remote_retrieve_response_code($res);
            continue;
        }
        $json = json_decode(wp_remote_retrieve_body($res), true);
        $rates = is_array($json) ? ($src['pick'])($json) : null;
        if (!is_array($rates)) {
            $errors[] = $key . ': beklenmeyen cevap';
            continue;
        }
        $new = ['GBP' => 1];
        foreach (EC_RATE_BOUNDS as $c => [$lo, $hi]) {
            $v = isset($rates[$c]) ? (float) $rates[$c] : 0;
            if ($v < $lo || $v > $hi) {
                $errors[] = "$key: $c değeri makul değil ($v)";
                continue 2;
            }
            $new[$c] = round($v, 4);
        }
        update_option('ec_rates', $new);
        update_option('ec_rates_meta', ['source' => $src['label'], 'updated_at' => ec_now(), 'error' => '']);
        ec_recompute_price_gbp();
        return $new;
    }
    $meta = (array) get_option('ec_rates_meta', []);
    $meta['error'] = implode(' | ', $errors);
    $meta['failed_at'] = ec_now();
    update_option('ec_rates_meta', $meta);
    return new WP_Error('ec_rates', 'Kurlar güncellenemedi: ' . $meta['error']);
}

/** Kur değişince tüm ilanların GBP karşılığını yeniden hesaplar */
function ec_recompute_price_gbp(): void
{
    global $wpdb;
    $t = ec_table('listings');
    foreach (ec_rates() as $c => $r) {
        if ($r > 0) {
            $wpdb->query($wpdb->prepare("UPDATE $t SET price_gbp = ROUND(price / %f, 2) WHERE currency = %s", $r, $c));
        }
    }
}
