<?php
/**
 * Reklam alanları (1.2.8): AdSense'e bağlı kalmadan doğrudan (satılan) reklam.
 *
 * Her alan için kip (ec_reklam[alan]['mode']):
 *   auto   → AdSense kodu girildiyse AdSense, yoksa "Bu alana reklam verebilirsiniz" görseli (varsayılan)
 *   direct → yönetici yüklediği masaüstü + mobil görsel, bağlantı, başlangıç/bitiş tarihi. Süresi dolunca auto'ya düşer.
 *   house  → her zaman "Bu alana reklam verebilirsiniz"
 *   off    → alan gösterilmez
 * Doğrudan reklam tıklamaları /?ec_reklam=alan üzerinden sayılır (ec_reklam_stats[alan][YYYY-AA]).
 * AdSense yayıncı/slot kimlikleri eskisi gibi ec_ads seçeneğinde (Emlak → Ayarlar).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Alanlar: ad, yer, masaüstü ve mobil önerilen ölçü */
function ec_ad_slots(): array
{
    return ec_i18n_fill([
        'top' => ['name' => 'Ana sayfa üst', 'where' => 'Ana sayfa, son ilanların üstü', 'd' => [970, 250], 'm' => [320, 100]],
        'feed' => ['name' => 'İlan arası kart', 'where' => 'Ana sayfa ve arama sonuçlarında ilan kartlarının arasında', 'd' => [300, 250], 'm' => [300, 250]],
        'mid' => ['name' => 'Orta şerit', 'where' => 'Ana sayfa ortası ve arama sonuçları altı', 'd' => [728, 90], 'm' => [320, 100]],
        'detail' => ['name' => 'İlan detay', 'where' => 'Her ilan sayfasında, açıklamanın altında', 'd' => [728, 90], 'm' => [320, 100]],
    ]);
}

function ec_ad_config(string $slot): array
{
    $all = (array) get_option('ec_reklam', []);
    return array_merge(['mode' => 'auto', 'img' => '', 'img_m' => '', 'url' => '', 'alt' => '', 'from' => '', 'until' => '', 'name' => ''], (array) ($all[$slot] ?? []));
}

/** Reklam ver bağlantısı ("Bu alana reklam verebilirsiniz" görselinin gittiği yer) */
function ec_ad_house_url(): string
{
    $u = trim((string) get_option('ec_reklam_iletisim', ''));
    return $u !== '' ? $u : 'mailto:bilgi@adacasa.com?subject=' . rawurlencode('AdaCasa reklam');
}

/** Doğrudan reklam şu an yayında mı? */
function ec_ad_direct_live(array $c): bool
{
    if ($c['mode'] !== 'direct' || !$c['img'] || !$c['url']) {
        return false;
    }
    $today = wp_date('Y-m-d');
    if ($c['from'] && $today < $c['from']) {
        return false;
    }
    if ($c['until'] && $today > $c['until']) {
        return false;
    }
    return true;
}

/**
 * Bir alanda ne gösterilecek: ['type' => direct|adsense|house|none, ...]
 */
function ec_ad_resolve(string $slot): array
{
    $c = ec_ad_config($slot);
    if ($c['mode'] === 'off') {
        return ['type' => 'none'];
    }
    if (ec_ad_direct_live($c)) {
        return ['type' => 'direct', 'img' => $c['img'], 'img_m' => $c['img_m'] ?: $c['img'], 'alt' => $c['alt'], 'href' => add_query_arg('ec_reklam', $slot, home_url('/'))];
    }
    $ads = (array) get_option('ec_ads', []);
    if ($c['mode'] !== 'house' && !empty($ads['client']) && !empty($ads[$slot])) {
        return ['type' => 'adsense', 'client' => $ads['client'], 'id' => $ads[$slot]];
    }
    return ['type' => 'house', 'href' => ec_ad_house_url()];
}

/* ---------- Tıklama sayacı ve yönlendirme ---------- */

add_action('init', function () {
    if (!isset($_GET['ec_reklam'])) {
        return;
    }
    $slot = sanitize_key((string) $_GET['ec_reklam']);
    $c = isset(ec_ad_slots()[$slot]) ? ec_ad_config($slot) : null;
    if (!$c || !ec_ad_direct_live($c) || !wp_http_validate_url($c['url'])) {
        wp_safe_redirect(home_url('/'), 302);
        exit;
    }
    $bot = preg_match('/bot|crawl|spider|preview|facebookexternalhit|curl|wget/i', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if (!$bot && !current_user_can('manage_options')) {
        $st = (array) get_option('ec_reklam_stats', []);
        $m = wp_date('Y-m');
        $st[$slot][$m] = (int) ($st[$slot][$m] ?? 0) + 1;
        update_option('ec_reklam_stats', $st, false);
    }
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow');
    wp_redirect($c['url'], 302); // reklam verenin sitesi: dış bağlantı
    exit;
});

/* ---------- Yönetim sayfası: Emlak → Reklamlar ---------- */

add_action('admin_menu', function () {
    add_submenu_page('ec-listings', 'Reklamlar', 'Reklamlar', 'manage_options', 'ec-reklam', 'ec_admin_ads');
}, 20);

add_action('admin_enqueue_scripts', function ($hook) {
    if (str_contains((string) $hook, 'ec-reklam')) {
        wp_enqueue_media();
    }
});

function ec_admin_ads(): void
{
    $slots = ec_ad_slots();
    $st = (array) get_option('ec_reklam_stats', []);
    $ads = (array) get_option('ec_ads', []);
    $m = wp_date('Y-m');
    $prev = wp_date('Y-m', strtotime('first day of last month'));
    $modes = ['auto' => 'Otomatik (AdSense kodu varsa AdSense, yoksa “Reklam verebilirsiniz”)', 'direct' => 'Doğrudan reklam (görsel + bağlantı)', 'house' => '“Bu alana reklam verebilirsiniz” görseli', 'off' => 'Kapalı (alan gösterilmez)'];
    echo '<div class="wrap"><h1>Reklam alanları</h1>';
    if (!empty($_GET['kaydedildi'])) {
        echo '<div class="notice notice-success"><p>Reklam ayarları kaydedildi.</p></div>';
    }
    echo '<p>Her alana Google AdSense’ten bağımsız olarak kendi sattığınız reklamı koyabilirsiniz. Masaüstü ve mobil için ayrı görsel yükleyin (önerilen ölçüler aşağıda; daha net görünmesi için <b>2 katı</b> çözünürlükte hazırlayın, ör. 970×250 yerine 1940×500). Mobil görsel boş bırakılırsa masaüstü görseli kullanılır. Bitiş tarihi geçince alan kendiliğinden “Reklam verebilirsiniz” görseline (ya da AdSense’e) döner.</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ec_reklam_kaydet">';
    wp_nonce_field('ec_reklam_kaydet');
    echo '<table class="form-table"><tr><th>“Reklam ver” bağlantısı</th><td><input name="iletisim" class="regular-text" value="' . esc_attr((string) get_option('ec_reklam_iletisim', '')) . '" placeholder="' . esc_attr(ec_ad_house_url()) . '"><p class="description">“Bu alana reklam verebilirsiniz” görseline tıklayan nereye gitsin? Boşsa: bilgi@adacasa.com adresine e-posta. WhatsApp için ör. <code>https://wa.me/90533XXXXXXX</code></p></td></tr></table>';
    foreach ($slots as $k => $s) {
        $c = ec_ad_config($k);
        $r = ec_ad_resolve($k);
        $now = ['direct' => '🟢 Doğrudan reklam yayında', 'adsense' => '🔵 AdSense gösteriliyor', 'house' => '⚪ “Reklam verebilirsiniz” gösteriliyor', 'none' => '⛔ Kapalı'][$r['type']];
        $clk = (int) ($st[$k][$m] ?? 0);
        $clp = (int) ($st[$k][$prev] ?? 0);
        echo '<h2 style="margin-top:2em">' . esc_html($s['name']) . '</h2><p class="description">' . esc_html($s['where']) . ' · Önerilen ölçü: masaüstü <b>' . $s['d'][0] . '×' . $s['d'][1] . '</b>, mobil <b>' . $s['m'][0] . '×' . $s['m'][1] . '</b> · Şu an: ' . esc_html($now) . ' · Tıklama: bu ay <b>' . $clk . '</b>, geçen ay ' . $clp . '</p>';
        echo '<table class="form-table">';
        echo '<tr><th>Gösterim</th><td><select name="r[' . $k . '][mode]">';
        foreach ($modes as $mk => $mn) {
            echo '<option value="' . $mk . '"' . selected($c['mode'], $mk, false) . '>' . esc_html($mn) . '</option>';
        }
        echo '</select>' . (empty($ads['client']) ? ' <span class="description">(AdSense kodu girilmemiş)</span>' : '') . '</td></tr>';
        echo '<tr><th>Reklam veren</th><td><input name="r[' . $k . '][name]" class="regular-text" value="' . esc_attr($c['name']) . '" placeholder="ör. Kuzey Yapı İnşaat"><p class="description">Yalnızca sizin takibiniz için; sitede görünmez.</p></td></tr>';
        foreach (['img' => 'Masaüstü görsel (' . $s['d'][0] . '×' . $s['d'][1] . ')', 'img_m' => 'Mobil görsel (' . $s['m'][0] . '×' . $s['m'][1] . ')'] as $f => $fn) {
            echo '<tr><th>' . esc_html($fn) . '</th><td><input name="r[' . $k . '][' . $f . ']" class="regular-text ec-rk-img" value="' . esc_attr($c[$f]) . '" placeholder="https://..."> <button type="button" class="button ec-rk-sec">Medyadan seç</button>'
                . ($c[$f] ? '<br><img src="' . esc_url($c[$f]) . '" alt="" style="max-width:360px;max-height:120px;margin-top:6px;border:1px solid #ddd">' : '') . '</td></tr>';
        }
        echo '<tr><th>Bağlantı</th><td><input name="r[' . $k . '][url]" class="regular-text" type="url" value="' . esc_attr($c['url']) . '" placeholder="https://reklamveren.com"></td></tr>';
        echo '<tr><th>Görsel açıklaması</th><td><input name="r[' . $k . '][alt]" class="regular-text" value="' . esc_attr($c['alt']) . '" placeholder="ör. Kuzey Yapı – Girne’de yeni proje"></td></tr>';
        echo '<tr><th>Yayın tarihleri</th><td><input type="date" name="r[' . $k . '][from]" value="' . esc_attr($c['from']) . '"> – <input type="date" name="r[' . $k . '][until]" value="' . esc_attr($c['until']) . '"><p class="description">Boş bırakılırsa süresiz.</p></td></tr>';
        echo '</table>';
    }
    submit_button('Kaydet');
    echo '</form></div>';
    ?>
<script>
jQuery(function ($) {
  $(document).on('click', '.ec-rk-sec', function (e) {
    e.preventDefault();
    var inp = $(this).prev('.ec-rk-img');
    var f = wp.media({ title: 'Reklam görseli', library: { type: 'image' }, button: { text: 'Bu görseli kullan' }, multiple: false });
    f.on('select', function () { inp.val(f.state().get('selection').first().toJSON().url); });
    f.open();
  });
});
</script>
    <?php
}

add_action('admin_post_ec_reklam_kaydet', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_reklam_kaydet')) {
        wp_die('Yetkiniz yok.');
    }
    $in = (array) wp_unslash($_POST['r'] ?? []);
    $out = [];
    $date = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : '';
    foreach (array_keys(ec_ad_slots()) as $k) {
        $r = (array) ($in[$k] ?? []);
        $out[$k] = [
            'mode' => in_array($r['mode'] ?? '', ['auto', 'direct', 'house', 'off'], true) ? $r['mode'] : 'auto',
            'name' => sanitize_text_field((string) ($r['name'] ?? '')),
            'img' => esc_url_raw(trim((string) ($r['img'] ?? '')), ['https', 'http']),
            'img_m' => esc_url_raw(trim((string) ($r['img_m'] ?? '')), ['https', 'http']),
            'url' => esc_url_raw(trim((string) ($r['url'] ?? '')), ['https', 'http']),
            'alt' => sanitize_text_field((string) ($r['alt'] ?? '')),
            'from' => $date($r['from'] ?? ''),
            'until' => $date($r['until'] ?? ''),
        ];
    }
    update_option('ec_reklam', $out);
    $ilet = trim((string) wp_unslash($_POST['iletisim'] ?? ''));
    update_option('ec_reklam_iletisim', $ilet === '' ? '' : esc_url_raw($ilet, ['https', 'http', 'mailto', 'tel']));
    if (function_exists('ec_cf_purge')) {
        ec_cf_purge(); // Cloudflare'deki sayfalar yeni reklamla yenilensin
    }
    do_action('ec_reklam_kaydedildi');
    wp_safe_redirect(admin_url('admin.php?page=ec-reklam&kaydedildi=1'));
    exit;
});
