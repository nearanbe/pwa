<?php
/**
 * Tema yardımcıları: fiyat biçimi, ilan kartı, arama kutusu, reklam alanı, SEO etiketleri.
 */

if (!defined('ABSPATH')) {
    exit;
}

const AE_ICON = '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22 24 7l18 15"/><path d="M10 20v21h28V20"/><path d="M20 41V29h8v12"/></svg>';
const AE_SYM = ['GBP' => '£', 'EUR' => '€', 'USD' => '$', 'TRY' => '₺'];

function ae_brand(): string
{
    return get_bloginfo('name') ?: 'AdaEv';
}

/** Ziyaretçinin seçtiği para birimi (çerezde saklanır) */
function ae_currency(): string
{
    $c = strtoupper((string) ($_COOKIE['ec_cur'] ?? ''));
    if (in_array($c, EC_CURRENCIES, true)) {
        return $c;
    }
    return ec_lang() === 'ru' ? 'EUR' : 'GBP';
}

function ae_format_money(float $gbp, ?string $cur = null): string
{
    $cur = $cur ?? ae_currency();
    $v = $gbp * (ec_rates()[$cur] ?? 1);
    return AE_SYM[$cur] . number_format($v, 0, ',', '.');
}

/** Fiyat: data-gbp sayesinde para birimi değişince JS tarafından yeniden hesaplanır */
function ae_money(float $gbp): string
{
    return '<span class="money" data-gbp="' . esc_attr((string) $gbp) . '">' . esc_html(ae_format_money($gbp)) . '</span>';
}

function ae_price_suffix(string $mode): string
{
    return $mode === 'rent' ? ae_t('per_month') : ($mode === 'daily' ? ae_t('per_night') : '');
}

function ae_favs(): array
{
    $raw = (string) ($_COOKIE['ec_favs'] ?? '');
    return array_values(array_filter(array_map('intval', explode(',', $raw))));
}

function ae_card(array $a): string
{
    $fav = in_array($a['id'], ae_favs(), true);
    $img = $a['cover']
        ? '<img src="' . esc_url($a['cover']['small']) . '" alt="' . esc_attr($a['title']) . '" loading="lazy" decoding="async" width="640" height="400">'
        : AE_ICON;
    $meta = array_filter([
        $a['area_m2'] ? $a['area_m2'] . ' m²' : null,
        $a['rooms'] ?: null,
        $a['deed_name'] ?: null,
    ]);
    ob_start(); ?>
<article class="card<?php echo $a['featured'] ? ' fcard' : ''; ?>">
  <a class="cardlink" href="<?php echo esc_url($a['url']); ?>" aria-label="<?php echo esc_attr($a['title']); ?>"></a>
  <div class="img">
    <?php echo $img; ?>
    <span class="tag"><?php echo esc_html($a['subtype_name']); ?></span>
    <?php if ($a['featured']) : ?><span class="fbadge"><?php ae_e('featured_badge'); ?></span><?php endif; ?>
    <?php if ($a['status'] === 'sold') : ?><span class="sbadge"><?php ae_e('sold'); ?></span><?php endif; ?>
    <button class="heart<?php echo $fav ? ' on' : ''; ?>" type="button" data-fav="<?php echo (int) $a['id']; ?>" aria-pressed="<?php echo $fav ? 'true' : 'false'; ?>" aria-label="<?php ae_e('favourites'); ?>">♥</button>
  </div>
  <div class="body">
    <div class="price"><?php echo ae_money($a['price_gbp']) . esc_html(ae_price_suffix($a['mode'])); ?></div>
    <h3 class="ttl"><?php echo esc_html($a['title']); ?></h3>
    <div class="sub"><?php echo esc_html(trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name'])); ?></div>
    <?php if ($meta) : ?><div class="sub"><?php echo esc_html(implode(' · ', $meta)); ?></div><?php endif; ?>
  </div>
</article>
<?php
    return ob_get_clean();
}

function ae_ad(string $slot, string $extra_class = ''): string
{
    $ads = get_option('ec_ads', []);
    $client = $ads['client'] ?? '';
    $id = $ads[$slot] ?? '';
    $inner = ($client && $id)
        ? '<ins class="adsbygoogle" style="display:block" data-ad-client="' . esc_attr($client) . '" data-ad-slot="' . esc_attr($id) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>'
        : '<div class="adph">' . esc_html(ae_t('ad_space')) . '</div>';
    return '<aside class="ad ' . esc_attr($extra_class) . '" aria-label="' . esc_attr(ae_t('ad')) . '"><span class="adl">' . esc_html(ae_t('ad')) . '</span>' . $inner . '</aside>';
}

function ae_ad_card(): string
{
    return '<div class="card ad-feed">' . ae_ad('feed') . '</div>';
}

/** Arama kutusu. $s = geçerli filtreler */
function ae_search_box(array $s = []): void
{
    $lang = ec_lang();
    $mode = $s['mode'] ?? 'sale';
    $types = ec_property_types();
    $ptype = isset($types[$s['ptype'] ?? '']) ? $s['ptype'] : 'konut';
    $cities = ec_cities();
    $actions = [];
    foreach (ec_modes() as $m) {
        $actions[$m] = ec_url_section($m, $lang);
    }
    $actions['project'] = ec_url_section('project', $lang);
    $subs = [];
    foreach ($types as $k => $t) {
        foreach ($t['sub'] as $sk => $sn) {
            $subs[$k][] = [$sk, $sn[$lang]];
        }
    }
    $dists = array_map(fn($c) => $c['districts'], $cities);
    ?>
<form class="sbox" id="sbox" method="get" action="<?php echo esc_url($actions[$mode]); ?>" role="search"
      data-actions="<?php echo esc_attr(wp_json_encode($actions)); ?>"
      data-subs="<?php echo esc_attr(wp_json_encode($subs)); ?>"
      data-dists="<?php echo esc_attr(wp_json_encode($dists)); ?>">
  <div class="tabs" role="tablist">
    <?php foreach (['sale', 'rent', 'daily', 'project'] as $m) : ?>
      <button type="button" role="tab" data-m="<?php echo $m; ?>" class="<?php echo $m === $mode ? 'on' : ''; ?>" aria-selected="<?php echo $m === $mode ? 'true' : 'false'; ?>"><?php ae_e($m === 'project' ? 'projects' : $m); ?></button>
    <?php endforeach; ?>
  </div>
  <div class="fg">
    <label class="vh" for="f-ptype"><?php ae_e('property_type'); ?></label>
    <select id="f-ptype" name="ptype">
      <?php foreach ($types as $k => $t) : ?>
        <option value="<?php echo esc_attr($k); ?>" <?php selected($ptype, $k); ?>><?php echo esc_html($t['name'][$lang]); ?></option>
      <?php endforeach; ?>
    </select>
    <label class="vh" for="f-sub"><?php ae_e('property_type'); ?></label>
    <select id="f-sub" name="subtype">
      <option value=""><?php ae_e('property_type'); ?></option>
      <?php foreach ($types[$ptype]['sub'] as $sk => $sn) : ?>
        <option value="<?php echo esc_attr($sk); ?>" <?php selected($s['subtype'] ?? '', $sk); ?>><?php echo esc_html($sn[$lang]); ?></option>
      <?php endforeach; ?>
    </select>
    <label class="vh" for="f-city"><?php ae_e('city'); ?></label>
    <select id="f-city" name="city">
      <option value=""><?php ae_e('city'); ?></option>
      <?php foreach ($cities as $k => $c) : ?>
        <option value="<?php echo esc_attr($k); ?>" <?php selected($s['city'] ?? '', $k); ?>><?php echo esc_html($c['name'][$lang]); ?></option>
      <?php endforeach; ?>
    </select>
    <label class="vh" for="f-dist"><?php ae_e('district'); ?></label>
    <select id="f-dist" name="district">
      <option value=""><?php ae_e('district'); ?></option>
      <?php foreach (($cities[$s['city'] ?? '']['districts'] ?? []) as $d) : ?>
        <option <?php selected($s['district'] ?? '', $d); ?>><?php echo esc_html($d); ?></option>
      <?php endforeach; ?>
    </select>
    <label class="vh" for="f-rooms"><?php ae_e('rooms'); ?></label>
    <select id="f-rooms" name="rooms">
      <option value=""><?php ae_e('rooms'); ?></option>
      <?php foreach (ec_room_options() as $r) : ?>
        <option <?php selected($s['rooms'] ?? '', $r); ?>><?php echo esc_html($r); ?></option>
      <?php endforeach; ?>
    </select>
    <label class="vh" for="f-min"><?php ae_e('min_price'); ?></label>
    <input id="f-min" name="min" inputmode="numeric" placeholder="<?php ae_e('min_price'); ?>" value="<?php echo esc_attr($s['min'] ?? ''); ?>">
    <label class="vh" for="f-max"><?php ae_e('max_price'); ?></label>
    <input id="f-max" name="max" inputmode="numeric" placeholder="<?php ae_e('max_price'); ?>" value="<?php echo esc_attr($s['max'] ?? ''); ?>">
    <input type="hidden" name="currency" id="f-cur" value="<?php echo esc_attr(ae_currency()); ?>">
    <button class="btn" type="submit"><?php ae_e('search'); ?></button>
  </div>
  <div class="recent" id="recent" data-label="<?php ae_e('recent_search'); ?>"></div>
</form>
<?php
}

/** Sayfa başlığı ve açıklaması: şablonlar $GLOBALS['ae_seo'] doldurur */
function ae_seo(array $s): void
{
    $GLOBALS['ae_seo'] = $s;
}

add_filter('pre_get_document_title', function ($t) {
    $s = $GLOBALS['ae_seo'] ?? null;
    return $s && !empty($s['title']) ? $s['title'] . ' | ' . ae_brand() : $t;
});

add_action('wp_head', function () {
    $s = $GLOBALS['ae_seo'] ?? null;
    if (!$s) {
        return;
    }
    if (!empty($s['description'])) {
        echo '<meta name="description" content="' . esc_attr(wp_trim_words($s['description'], 30, '…')) . "\">\n";
    }
    if (!empty($s['canonical'])) {
        echo '<link rel="canonical" href="' . esc_url($s['canonical']) . "\">\n";
    }
    foreach (($s['alternates'] ?? []) as $l => $u) {
        echo '<link rel="alternate" hreflang="' . esc_attr($l) . '" href="' . esc_url($u) . "\">\n";
    }
    if (!empty($s['alternates']['tr'])) {
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($s['alternates']['tr']) . "\">\n";
    }
    echo '<meta property="og:site_name" content="' . esc_attr(ae_brand()) . "\">\n";
    echo '<meta property="og:title" content="' . esc_attr($s['title'] ?? ae_brand()) . "\">\n";
    if (!empty($s['canonical'])) {
        echo '<meta property="og:url" content="' . esc_url($s['canonical']) . "\">\n";
    }
    echo '<meta property="og:image" content="' . esc_url(!empty($s['image']) ? $s['image'] : get_theme_file_uri('assets/img/og-default.jpg')) . "\">\n";
    echo '<meta property="og:type" content="website"><meta name="twitter:card" content="summary_large_image">' . "\n";
    if (!empty($s['jsonld'])) {
        echo '<script type="application/ld+json">' . wp_json_encode($s['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}, 1);

/** Dil değiştiricide aynı sayfanın diğer dillerdeki adresleri */
function ae_lang_links(): array
{
    $s = $GLOBALS['ae_seo']['alternates'] ?? [];
    $out = [];
    foreach (EC_LANGS as $l) {
        $out[$l] = $s[$l] ?? ec_url_home($l);
    }
    return $out;
}

/** Logo: Özelleştir → Site kimliği'nden logo yüklendiyse o, yoksa temanın logosu + site adı */
function ae_logo(): void
{
    $home = ec_url_home();
    if (has_custom_logo()) {
        $id = (int) get_theme_mod('custom_logo');
        echo '<a class="logo custom" href="' . esc_url($home) . '">' . wp_get_attachment_image($id, 'medium', false, ['alt' => ae_brand(), 'class' => 'logo-img']) . '</a>';
        return;
    }
    echo '<a class="logo" href="' . esc_url($home) . '" aria-label="' . esc_attr(ae_brand()) . '">'
        . '<svg width="34" height="34" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M9 42V22a15 15 0 0 1 30 0v20" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="M14 35q5-4.5 10 0t10 0" stroke="#b48a45" stroke-width="3" stroke-linecap="round"/><circle cx="24" cy="21" r="3.2" fill="#b48a45"/></svg>'
        . esc_html(ae_brand()) . '</a>';
}

function ae_blog_url(): string
{
    $id = (int) get_option('page_for_posts');
    return $id ? get_permalink($id) : home_url('/blog/');
}

/** Üst menü: [adres, etiket, seçili mi] */
function ae_nav_items(): array
{
    $page = ec_page();
    $mode = get_query_var('ec_mode');
    $isBlog = is_home() || is_singular('post') || is_category() || is_tag();
    return [
        [ec_url_section('sale'), ae_t('sale'), $page === 'search' && $mode === 'sale'],
        [ec_url_section('rent'), ae_t('rent'), $page === 'search' && $mode === 'rent'],
        [ec_url_section('daily'), ae_t('daily'), $page === 'search' && $mode === 'daily'],
        [ec_url_section('project'), ae_t('projects'), in_array($page, ['project', 'projectd'], true)],
        [ec_url_section('index'), ae_t('price_index'), $page === 'index'],
        [ae_blog_url(), ae_t('blog'), $isBlog && !$page],
    ];
}

/** Site ikonu: WordPress'te Site Simgesi seçilmediyse temanın ikonları kullanılır */
add_action('wp_head', function () {
    if (!has_site_icon()) {
        $u = fn($f) => esc_url(get_theme_file_uri('assets/img/' . $f));
        echo '<link rel="icon" href="' . $u('favicon.svg') . '" type="image/svg+xml">' . "\n";
        echo '<link rel="icon" href="' . $u('icon-32.png') . '" sizes="32x32" type="image/png">' . "\n";
        echo '<link rel="apple-touch-icon" href="' . $u('apple-touch-icon.png') . '">' . "\n";
    }
    echo '<link rel="manifest" href="' . esc_url(get_theme_file_uri('assets/site.webmanifest')) . '">' . "\n";
}, 2);

/** Proje kartı */
function ae_project_card(array $p): string
{
    $img = $p['cover']
        ? '<img src="' . esc_url($p['cover']['small']) . '" alt="' . esc_attr($p['name']) . '" loading="lazy" decoding="async" width="640" height="400">'
        : AE_ICON;
    ob_start(); ?>
<article class="card proj">
  <a class="cardlink" href="<?php echo esc_url($p['url']); ?>" aria-label="<?php echo esc_attr($p['name']); ?>"></a>
  <div class="img"><?php echo $img; ?><span class="tag"><?php echo esc_html($p['city_name'] . ($p['district'] ? ' · ' . $p['district'] : '')); ?></span></div>
  <div class="body">
    <h3 class="ttl"><?php echo esc_html($p['name']); ?></h3>
    <div class="sub"><?php echo esc_html((string) $p['developer']); ?></div>
    <div class="pills">
      <?php if ($p['units']) : ?><span><?php ae_e('units', $p['units']); ?></span><?php endif; ?>
      <?php if ($p['delivery_date']) : ?><span><?php ae_e('delivery', ae_month_label($p['delivery_date'])); ?></span><?php endif; ?>
      <?php if ($p['price_min_gbp']) : ?><span><?php echo ae_money($p['price_min_gbp']); ?> – <?php echo ae_money((float) $p['price_max_gbp']); ?></span><?php endif; ?>
    </div>
  </div>
</article>
<?php
    return ob_get_clean();
}

/** "2027-12-01" ya da "2027-12" → "Ara 2027" (dile göre) */
function ae_month_label(string $date, bool $short = false): string
{
    $months = [
        'tr' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'ru' => ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'],
    ];
    $ts = strtotime(strlen($date) === 7 ? $date . '-01' : $date);
    if (!$ts) {
        return $date;
    }
    $m = $months[ec_lang()][(int) gmdate('n', $ts) - 1];
    if ($short) {
        $m = mb_substr($m, 0, 3);
        return $m . ' ' . gmdate('y', $ts);
    }
    return $m . ' ' . gmdate('Y', $ts);
}

function ae_pct(?float $v): string
{
    if ($v === null) {
        return '—';
    }
    $cls = $v > 0.05 ? 'up' : ($v < -0.05 ? 'down' : 'flat');
    $sign = $v > 0 ? '+' : '';
    return '<span class="chg ' . $cls . '">' . esc_html($sign . number_format($v, 1, ',', '.') . '%') . '</span>';
}

/**
 * Basit çizgi grafik (SVG). $s = [['period' => '2026-01', 'm2' => 1500.0], ...]
 * Değerler GBP; etiketler seçili para biriminde çizilir.
 */
function ae_chart(array $s, string $label): string
{
    if (count($s) < 2) {
        return '';
    }
    $W = 640;
    $H = 220;
    $pl = 8;
    $pr = 8;
    $pt = 16;
    $pb = 6;
    $vals = array_column($s, 'm2');
    $min = min($vals);
    $max = max($vals);
    $pad = ($max - $min) * 0.15 ?: $max * 0.05;
    $min -= $pad;
    $max += $pad;
    $n = count($s);
    $x = fn($i) => $pl + $i * ($W - $pl - $pr) / ($n - 1);
    $y = fn($v) => $pt + ($H - $pt - $pb) * (1 - ($v - $min) / ($max - $min));
    $pts = [];
    foreach ($s as $i => $p) {
        $pts[] = round($x($i), 1) . ',' . round($y($p['m2']), 1);
    }
    $area = 'M' . $pl . ',' . ($H - $pb) . ' L' . implode(' L', $pts) . ' L' . ($W - $pr) . ',' . ($H - $pb) . ' Z';
    $last = end($s);
    $lx = round($x($n - 1), 1);
    $ly = round($y($last['m2']), 1);
    $xl = '';
    foreach ([0, intdiv($n - 1, 2), $n - 1] as $i) {
        $xl .= '<span>' . esc_html(ae_month_label($s[$i]['period'], true)) . '</span>';
    }
    $grid = '';
    foreach ([0.25, 0.5, 0.75] as $g) {
        $gy = round($pt + ($H - $pt - $pb) * $g, 1);
        $grid .= '<line x1="' . $pl . '" x2="' . ($W - $pr) . '" y1="' . $gy . '" y2="' . $gy . '"/>';
    }
    return '<figure class="chart"><svg viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . esc_attr($label) . '" preserveAspectRatio="none">'
        . '<g class="cg">' . $grid . '</g>'
        . '<path class="ca" d="' . $area . '"/>'
        . '<polyline class="cl" points="' . implode(' ', $pts) . '"/>'
        . '<circle class="cd" cx="' . $lx . '" cy="' . $ly . '" r="4.5"/>'
        . '</svg>'
        . '<div class="cxl">' . $xl . '</div>'
        . '<figcaption><span>' . ae_money($min + $pad) . '</span><span>' . ae_money($max - $pad) . '</span></figcaption></figure>';
}
