<?php
/**
 * Otomatik döviz kurları. Günde bir kez (ec_daily) çekilir.
 * Kaynak 1: KKTC Merkez Bankası günlük kur XML'i (TL bazlı)
 * Kaynak 2: Frankfurter (Avrupa Merkez Bankası referans kurları, anahtar gerektirmez)
 * Kaynak 3: open.er-api.com (yedek, anahtar gerektirmez)
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
        'kktcmb' => [
            'urls' => ['https://mb.gov.ct.tr/kur/gunluk.xml', 'https://www.kktcmerkezbankasi.org/kur/gunluk.xml', 'http://www.kktcmerkezbankasi.org/kur/gunluk.xml'],
            'parse' => 'ec_rates_parse_kktcmb',
            'label' => 'KKTC Merkez Bankası',
        ],
        'frankfurter' => [
            'urls' => ['https://api.frankfurter.dev/v1/latest?base=GBP&symbols=EUR,USD,TRY', 'https://api.frankfurter.app/latest?from=GBP&to=EUR,USD,TRY'],
            'parse' => fn(string $b) => (json_decode($b, true)['rates'] ?? null),
            'label' => 'Avrupa Merkez Bankası (Frankfurter)',
        ],
        'er-api' => [
            'urls' => ['https://open.er-api.com/v6/latest/GBP'],
            'parse' => function (string $b) {
                $j = json_decode($b, true);
                return ($j['result'] ?? '') === 'success' ? ($j['rates'] ?? null) : null;
            },
            'label' => 'ExchangeRate-API (open.er-api.com)',
        ],
    ];
}

/** "58,1234" / "58.1234" / "1.058,12" → float */
function ec_parse_number(string $v): ?float
{
    $v = trim(str_replace(["\xc2\xa0", ' '], '', $v));
    if ($v === '' || !preg_match('/^-?[0-9.,]+$/', $v)) {
        return null;
    }
    if (str_contains($v, ',') && str_contains($v, '.')) {
        $v = strrpos($v, ',') > strrpos($v, '.') ? str_replace(['.', ','], ['', '.'], $v) : str_replace(',', '', $v);
    } else {
        $v = str_replace(',', '.', $v);
    }
    return is_numeric($v) ? (float) $v : null;
}

/**
 * KKTC Merkez Bankası günlük kur XML'i. Kurlar TL karşılığıdır (1 birim döviz = x TL).
 * Alan adlarına bağlı kalmamak için esnek okunur: her kayıtta döviz kodu (USD/EUR/GBP),
 * varsa "Birim" ve alış/satış rakamları aranır; ikisi de varsa ortalaması alınır.
 * @return array|null 1 GBP karşılığı ['EUR'=>..,'USD'=>..,'TRY'=>..]
 */
function ec_rates_parse_kktcmb(string $body): ?array
{
    if (preg_match('/<\?xml[^>]*encoding=["\']([^"\']+)["\']/i', $body, $m) && strtolower($m[1]) !== 'utf-8') {
        $conv = @mb_convert_encoding($body, 'UTF-8', strtoupper($m[1]) === 'WINDOWS-1254' ? 'Windows-1254' : 'ISO-8859-9');
        if ($conv) {
            $body = preg_replace('/encoding="[^"]+"/i', 'encoding="UTF-8"', $conv, 1);
        }
    }
    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_use_internal_errors($prev);
    if (!$xml) {
        return null;
    }
    $try = []; // kod => 1 birim dövizin TL karşılığı
    foreach ($xml->xpath('//*[*]') as $node) {
        $fields = [];
        foreach ($node->children() as $k => $c) {
            $fields[strtolower((string) $k)] = trim((string) $c);
        }
        foreach ($node->attributes() as $k => $a) {
            $fields[strtolower((string) $k)] = trim((string) $a);
        }
        $code = null;
        foreach ($fields as $v) {
            if (preg_match('/^(USD|EUR|GBP)$/i', $v)) {
                $code = strtoupper($v);
                break;
            }
        }
        if (!$code || isset($try[$code])) {
            continue;
        }
        $unit = 1.0;
        $buy = $sell = $any = null;
        foreach ($fields as $k => $v) {
            $n = ec_parse_number($v);
            if ($n === null) {
                continue;
            }
            if (preg_match('/birim|unit/', $k)) {
                $unit = $n > 0 ? $n : 1.0;
            } elseif (preg_match('/efektif|banknote/', $k)) {
                continue; // efektif (nakit) kurları değil, döviz kurları kullanılır
            } elseif (preg_match('/al[iı]s|buy/u', $k)) {
                $buy = $n;
            } elseif (preg_match('/sat[iı]s|sell/u', $k)) {
                $sell = $n;
            } elseif ($any === null && $n > 0.5) {
                $any = $n;
            }
        }
        $val = ($buy && $sell) ? ($buy + $sell) / 2 : ($buy ?: ($sell ?: $any));
        if ($val) {
            $try[$code] = $val / $unit;
        }
    }
    if (empty($try['GBP']) || empty($try['EUR']) || empty($try['USD'])) {
        return null;
    }
    return ['EUR' => $try['GBP'] / $try['EUR'], 'USD' => $try['GBP'] / $try['USD'], 'TRY' => $try['GBP']];
}

/**
 * Kurları çeker, doğrular, kaydeder ve ilanların GBP karşılığını yeniden hesaplar.
 * Sıra: KKTC Merkez Bankası → Avrupa Merkez Bankası → open.er-api.com
 * @return array|WP_Error yeni kurlar
 */
function ec_rates_update()
{
    $errors = [];
    $sample = '';
    foreach (ec_rates_sources() as $key => $src) {
        foreach ($src['urls'] as $url) {
            $res = wp_remote_get($url, ['timeout' => 15, 'redirection' => 3, 'user-agent' => 'Mozilla/5.0 (compatible; EmlakCore/' . EC_VERSION . ')']);
            if (is_wp_error($res)) {
                $errors[] = $key . ': ' . $res->get_error_message();
                continue;
            }
            $code = wp_remote_retrieve_response_code($res);
            $body = (string) wp_remote_retrieve_body($res);
            if ($code !== 200) {
                $errors[] = "$key: HTTP $code";
                continue;
            }
            $rates = ($src['parse'])($body);
            if (!is_array($rates)) {
                $errors[] = "$key: cevap okunamadı";
                if ($key === 'kktcmb' && !$sample) {
                    $sample = mb_substr(wp_strip_all_tags(str_replace('><', "> <", $body)), 0, 600);
                }
                continue;
            }
            $new = ['GBP' => 1];
            foreach (EC_RATE_BOUNDS as $c => [$lo, $hi]) {
                $v = isset($rates[$c]) ? (float) $rates[$c] : 0;
                if ($v < $lo || $v > $hi) {
                    $errors[] = "$key: $c değeri makul değil ($v)";
                    continue 3;
                }
                $new[$c] = round($v, 4);
            }
            update_option('ec_rates', $new);
            update_option('ec_rates_meta', ['source' => $src['label'], 'updated_at' => ec_now(), 'error' => $errors ? implode(' | ', $errors) : '', 'sample' => '']);
            ec_recompute_price_gbp();
            return $new;
        }
    }
    $meta = (array) get_option('ec_rates_meta', []);
    $meta['error'] = implode(' | ', $errors);
    $meta['failed_at'] = ec_now();
    $meta['sample'] = $sample;
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
