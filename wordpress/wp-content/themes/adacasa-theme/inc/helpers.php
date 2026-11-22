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
    return get_bloginfo('name') ?: 'AdaCasa';
}

/** Ziyaretçinin seçtiği para birimi (çerezde saklanır) */
function ae_currency(): string
{
    // Sayfa önbelleği açıkken sayfa herkese aynı gider: para birimi tarayıcıda (main.js) uygulanır
    $c = function_exists('ec_page_cache_on') && ec_page_cache_on() ? '' : strtoupper((string) ($_COOKIE['ec_cur'] ?? ''));
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

/** İkinci para birimi: seçili birim TL değilse TL, TL ise sterlin */
function ae_second_currency(?string $cur = null): string
{
    return ($cur ?? ae_currency()) === 'TRY' ? 'GBP' : 'TRY';
}

/** "1.234.567,89" biçiminde (kuruşlu) */
function ae_format_money2(float $gbp, string $cur): string
{
    return AE_SYM[$cur] . number_format($gbp * (ec_rates()[$cur] ?? 1), 2, ',', '.');
}

/** Ana fiyat + ek + parantez içinde ikinci para birimi: £1.800 / ay (₺119.038,39) */
function ae_price(float $gbp, string $mode = 'sale', bool $second = true): string
{
    $h = ae_money($gbp) . esc_html(ae_price_suffix($mode));
    if ($second && $gbp > 0) {
        $c2 = ae_second_currency();
        $h .= ' <small class="money2" role="button" tabindex="0" aria-haspopup="dialog" title="' . esc_attr(ae_t('conv_open')) . '" data-gbp="' . esc_attr((string) $gbp) . '">(' . esc_html(ae_format_money2($gbp, $c2)) . ')</small>';
    }
    return $h;
}

/** Sosyal medya ikonları (sadece linki girilmiş olanlar) */
function ae_social_icons(array $links, string $class = 'socials'): string
{
    $paths = [
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8a0 0 0 0 1 0 0z"/>',
        'tiktok' => '<path d="M15 3c.5 2.5 2 4 5 4v3.5c-2 0-3.6-.6-5-1.6V15a6 6 0 1 1-6-6v3.6A2.4 2.4 0 1 0 11.4 15V3z"/>',
        'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z" fill="currentColor"/>',
        'x' => '<path d="M4 4l16 16M20 4L4 20"/>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
        'telegram' => '<path d="M21 4L3 11l6 2 2 6 3-4 5 4z"/><path d="M9 13l8-6"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 1c-1-.5-2-1.5-2.5-2.5l1-1-1-2z" fill="currentColor" stroke="none"/>',
        'website' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>',
    ];
    $h = '';
    foreach ($links as $k => $u) {
        if (!$u || !isset($paths[$k])) {
            continue;
        }
        $name = $k === 'website' ? 'Web' : (ec_social_networks()[$k][0] ?? $k);
        $h .= '<a href="' . esc_url($u) . '" target="_blank" rel="noopener nofollow" aria-label="' . esc_attr($name) . '" title="' . esc_attr($name) . '"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$k] . '</svg></a>';
    }
    return $h ? '<div class="' . esc_attr($class) . '">' . $h . '</div>' : '';
}

/** Turnstile kutusu (anahtar girilmişse) */
function ae_turnstile(): string
{
    $k = function_exists('ec_turnstile_keys') ? ec_turnstile_keys() : ['site' => ''];
    if (!$k['site']) {
        return '';
    }
    wp_enqueue_script('cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, ['strategy' => 'async']);
    return '<div class="cf-turnstile" data-sitekey="' . esc_attr($k['site']) . '" data-language="' . esc_attr(ec_lang()) . '"></div>';
}

/** Form hata mesajı */
function ae_field_error(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }
    $code = $errors[$field];
    $key = 'err_' . (in_array($code, ['email_exists'], true) ? $code : $field);
    return '<p class="ferr" role="alert">' . esc_html(ae_t($key)) . '</p>';
}

function ae_price_suffix(string $mode): string
{
    return $mode === 'rent' ? ae_t('per_month') : ($mode === 'daily' ? ae_t('per_night') : '');
}

function ae_favs(): array
{
    if (function_exists('ec_page_cache_on') && ec_page_cache_on() && ec_page() !== 'favs') {
        return []; // önbellekli sayfada kalpler tarayıcıda işaretlenir
    }
    $raw = (string) ($_COOKIE['ec_favs'] ?? '');
    return array_values(array_filter(array_map('intval', explode(',', $raw))));
}

function ae_card(array $a): string
{
    $fav = in_array($a['id'], ae_favs(), true);
    $img = $a['cover']
        ? '<img src="' . esc_url($a['cover']['small']) . '" alt="' . esc_attr($a['title']) . '" loading="lazy" decoding="async" width="640" height="400">'
        : AE_ICON;
    // Simgeli bilgi satırı: oda · banyo · m² (101evler kartlarındaki gibi)
    $specs = array_filter([
        $a['rooms'] ? ['bed', $a['rooms'], ae_t('bedrooms')] : null,
        $a['baths'] ? ['bath', (string) $a['baths'], ae_t('baths')] : null,
        $a['area_m2'] ? ['area', $a['area_m2'] . ' m²', ae_t('area')] : null,
    ]);
    ob_start(); ?>
<article class="card<?php echo $a['featured'] ? ' fcard' : ''; ?>">
  <a class="cardlink" href="<?php echo esc_url($a['url']); ?>" aria-label="<?php echo esc_attr($a['title']); ?>"></a>
  <div class="img">
    <?php echo $img; ?>
    <span class="tag"><?php echo esc_html($a['subtype_name']); ?></span>
    <?php if ($a['featured']) : ?><span class="fbadge"><?php ae_e('featured_badge'); ?></span><?php endif; ?>
    <?php if (!empty($a['urgent'])) : ?><span class="ubadge"><?php ae_e('urgent_badge'); ?></span><?php endif; ?>
    <?php if ($a['status'] === 'sold') : ?><span class="sbadge"><?php ae_e('sold'); ?></span><?php endif; ?>
    <span class="cbadges"><?php if (!empty($a['photo_count'])) : ?><span class="phbadge" title="<?php ae_e('all_photos', (int) $a['photo_count']); ?>"><?php echo ae_icon('grid', 13); ?> <?php echo (int) $a['photo_count']; ?></span><?php endif; ?><?php if (!empty($a['pano_count'])) : ?><span class="pbadge" title="<?php ae_e('pano_tour'); ?>">360°</span><?php endif; ?></span>
    <button class="heart<?php echo $fav ? ' on' : ''; ?>" type="button" data-fav="<?php echo (int) $a['id']; ?>" aria-pressed="<?php echo $fav ? 'true' : 'false'; ?>" aria-label="<?php ae_e('favourites'); ?>">♥</button>
  </div>
  <div class="body">
    <div class="price"><?php echo ae_price($a['price_gbp'], $a['mode']); ?></div>
    <h3 class="ttl"><?php echo esc_html($a['title']); ?></h3>
    <div class="sub"><?php echo esc_html(trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name'])); ?></div>
    <?php if ($specs) : ?><ul class="cspecs"><?php foreach ($specs as [$ic, $v, $lb]) : ?><li title="<?php echo esc_attr($lb); ?>"><?php echo ae_icon($ic, 16); ?><span><?php echo esc_html($v); ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($a['deed_name']) : ?><div class="cdeed"><?php echo ae_icon('deed', 14); ?> <?php echo esc_html($a['deed_name']); ?></div><?php endif; ?>
    <?php if (!empty($a['agent'])) : $ag = $a['agent']; ?>
      <div class="cagent"><?php echo $ag['logo'] ? '<img src="' . esc_url($ag['logo']['small']) . '" alt="" loading="lazy" width="28" height="28">' : '<span class="av xs">' . esc_html(mb_substr($ag['name'], 0, 1)) . '</span>'; ?><span><?php echo esc_html($ag['name']); ?></span><?php if ($ag['verified']) : ?> <span class="vbadge sm" title="<?php ae_e('of_verified'); ?>">✔</span><?php endif; ?></div>
    <?php endif; ?>
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
    if (!($client && $id) && !current_user_can('manage_options')) {
        return ''; // reklam kodu girilmemiş: ziyaretçi boş kutu görmesin
    }
    $inner = ($client && $id)
        ? '<ins class="adsbygoogle" style="display:block" data-ad-client="' . esc_attr($client) . '" data-ad-slot="' . esc_attr($id) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>'
        : '<div class="adph">' . esc_html(ae_t('ad_space')) . '</div>';
    return '<aside class="ad ' . esc_attr($extra_class) . '" aria-label="' . esc_attr(ae_t('ad')) . '"><span class="adl">' . esc_html(ae_t('ad')) . '</span>' . $inner . '</aside>';
}

function ae_ad_card(): string
{
    $ad = ae_ad('feed');
    return $ad ? '<div class="card ad-feed">' . $ad . '</div>' : '';
}

/** Ana sayfa giriş görseli: Emlak → Ayarlar'dan seçilen (medya no ya da adres), yoksa temanın görseli. [src, srcset, mobil] */
function ae_hero_photo(): array
{
    $v = trim((string) get_option('ec_hero_photo', ''));
    if ($v !== '') {
        if (ctype_digit($v)) {
            $src = (string) wp_get_attachment_image_url((int) $v, 'full');
            $set = (string) wp_get_attachment_image_srcset((int) $v, 'full');
            if ($src) {
                $cap = (string) wp_get_attachment_caption((int) $v);
                return ['src' => $src, 'srcset' => $set, 'mobile' => '', 'credit' => str_starts_with($cap, 'Foto:') ? esc_html($cap) : ''];
            }
        } else {
            return ['src' => $v, 'srcset' => '', 'mobile' => '', 'credit' => ''];
        }
    }
    $u = fn($f) => get_theme_file_uri('assets/img/' . $f);
    // Temanın Girne limanı fotoğrafı (Wikimedia Commons, özgür lisans: atıf zorunlu)
    $c = json_decode((string) @file_get_contents(get_theme_file_path('assets/hero-credit.json')), true) ?: [];
    $credit = $c ? '<a href="' . esc_url($c['page']) . '" rel="noopener" target="_blank">' . esc_html('Foto: ' . $c['artist'] . ' · ' . $c['lic'] . ' · Wikimedia Commons') . '</a>' : '';
    return ['src' => $u('hero-1920.webp'), 'srcset' => $u('hero-1280.webp') . ' 1280w, ' . $u('hero-1920.webp') . ' 1920w', 'mobile' => $u('hero-m.webp'), 'credit' => $credit];
}

/** Tarih, sayfanın diline göre: 17 Kasım 2026 / 17 November 2026 / 17 ноября 2026 */
function ae_date(int $ts): string
{
    $m = [
        'tr' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'ru' => ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'],
    ][ec_lang()] ?? null;
    return $m ? wp_date('j', $ts) . ' ' . $m[(int) wp_date('n', $ts) - 1] . ' ' . wp_date('Y', $ts) : wp_date('j.m.Y', $ts);
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
    $more = (bool) array_filter(array_intersect_key($s, array_flip(['subtype', 'rooms', 'deed_type', 'min', 'max', 'pano'])));
    ?>
<form class="sbox<?php echo $more ? ' more' : ''; ?>" id="sbox" method="get" action="<?php echo esc_url($actions[$mode]); ?>" role="search"
      data-actions="<?php echo esc_attr(wp_json_encode($actions)); ?>"
      data-subs="<?php echo esc_attr(wp_json_encode($subs)); ?>"
      data-dists="<?php echo esc_attr(wp_json_encode($dists)); ?>">
  <div class="tabs" role="tablist">
    <?php foreach (['sale', 'rent', 'daily', 'project'] as $m) : ?>
      <button type="button" role="tab" data-m="<?php echo $m; ?>" class="<?php echo $m === $mode ? 'on' : ''; ?>" aria-selected="<?php echo $m === $mode ? 'true' : 'false'; ?>"><?php ae_e($m === 'project' ? 'projects' : $m); ?></button>
    <?php endforeach; ?>
  </div>
  <div class="sf">
    <div class="sf-main">
      <label class="vh" for="f-ptype"><?php ae_e('property_type'); ?></label>
      <select id="f-ptype" name="ptype">
        <?php foreach ($types as $k => $t) : ?>
          <option value="<?php echo esc_attr($k); ?>" <?php selected($ptype, $k); ?>><?php echo esc_html($t['name'][$lang]); ?></option>
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
      <button class="btn sbtn" type="submit"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><?php ae_e('search'); ?></button>
    </div>
    <button class="sf-tog" type="button" aria-controls="sf-more" aria-expanded="false"><?php ae_e('more_filters'); ?></button>
    <div class="sf-more" id="sf-more">
      <label class="vh" for="f-sub"><?php ae_e('property_type'); ?></label>
      <select id="f-sub" name="subtype">
        <option value=""><?php ae_e('property_type'); ?></option>
        <?php foreach ($types[$ptype]['sub'] as $sk => $sn) : ?>
          <option value="<?php echo esc_attr($sk); ?>" <?php selected($s['subtype'] ?? '', $sk); ?>><?php echo esc_html($sn[$lang]); ?></option>
        <?php endforeach; ?>
      </select>
      <label class="vh" for="f-rooms"><?php ae_e('rooms'); ?></label>
      <select id="f-rooms" name="rooms">
        <option value=""><?php ae_e('rooms'); ?></option>
        <?php foreach (ec_room_options() as $r) : ?>
          <option <?php selected($s['rooms'] ?? '', $r); ?>><?php echo esc_html($r); ?></option>
        <?php endforeach; ?>
      </select>
      <label class="vh" for="f-deed"><?php ae_e('deed_filter'); ?></label>
      <select id="f-deed" name="deed_type" class="sale-only" <?php echo $mode === 'sale' ? '' : 'hidden disabled'; ?>>
        <option value=""><?php ae_e('deed_filter'); ?></option>
        <?php foreach (ec_deed_types() as $dk => $dn) : if ($dk === 'diger') { continue; } ?>
          <option value="<?php echo esc_attr($dk); ?>" <?php selected($s['deed_type'] ?? '', $dk); ?>><?php echo esc_html($dn[$lang]); ?></option>
        <?php endforeach; ?>
      </select>
      <label class="vh" for="f-min"><?php ae_e('min_price'); ?></label>
      <input id="f-min" name="min" inputmode="numeric" placeholder="<?php ae_e('min_price'); ?>" value="<?php echo esc_attr($s['min'] ?? ''); ?>">
      <label class="vh" for="f-max"><?php ae_e('max_price'); ?></label>
      <input id="f-max" name="max" inputmode="numeric" placeholder="<?php ae_e('max_price'); ?>" value="<?php echo esc_attr($s['max'] ?? ''); ?>">
      <label class="vh" for="f-cur"><?php ae_e('currency'); ?></label>
      <select id="f-cur" name="currency" class="fcur" title="<?php ae_e('currency'); ?>">
        <?php foreach (EC_CURRENCIES as $c) : ?><option value="<?php echo esc_attr($c); ?>" <?php selected($s['currency'] ?? ae_currency(), $c); ?>><?php echo esc_html(AE_SYM[$c] . ' ' . $c); ?></option><?php endforeach; ?>
      </select>
      <label class="pano-chk" title="<?php ae_e('pano_filter'); ?>"><input type="checkbox" name="pano" value="1" <?php checked(!empty($s['pano'])); ?>> 🌐 <?php ae_e('pano_only'); ?></label>
    </div>
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
    if (!empty($GLOBALS['ec_preview'])) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    }
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

/** SEO: yapılandırılmış veride kullanılacak kurum bilgisi (logo: site simgesi ya da tema simgesi) */
function ae_org_ld(): array
{
    $logo = has_site_icon() ? get_site_icon_url(512) : get_theme_file_uri('assets/img/icon-512.png');
    return ['@type' => 'Organization', '@id' => home_url('/#org'), 'name' => ae_brand(), 'url' => home_url('/'), 'logo' => $logo];
}

/** SEO: içerik haritası (BreadcrumbList); $items = [[ad, adres], ...] */
function ae_breadcrumb_ld(array $items): array
{
    $out = [];
    foreach (array_values($items) as $i => $it) {
        $out[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags((string) $it[0]), 'item' => $it[1]];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $out];
}

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

/**
 * AdaCasa sembolü (çatı, dalgalar, altın kıvrım) satır içi SVG. Lacivert kısımlar CSS değişkeni --lgn ile
 * renklenir; koyu temada ve koyu alt bilgide açık renge döner. $k: aynı sayfada birden çok kullanımda gradyan kimliği.
 */
function ae_logo_mark(string $k = 'h', int $w = 52): string
{
    $h = (int) round($w * 200 / 330);
    return '<svg class="lgm" width="' . $w . '" height="' . $h . '" viewBox="0 0 330 200" aria-hidden="true">'
        . '<defs><linearGradient id="lsw' . $k . '" x1="0" x2="1"><stop offset="0" stop-color="#7db3df"/><stop offset=".55" stop-color="#4a8fc7"/><stop class="lgs" offset="1"/></linearGradient>'
        . '<linearGradient id="lau' . $k . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#d9b77a"/><stop offset=".5" stop-color="#b8893f"/><stop offset="1" stop-color="#8f6427"/></linearGradient></defs>'
        . '<path class="lgl" d="M128 122 180 14l44 78" stroke-width="14"/>'
        . '<path class="lgl" d="M218 86 262 30l60 66" stroke-width="11"/>'
        . '<path class="lgf" d="M284 30h10v34h-10zM289 62h9v88h-9z"/>'
        . '<path d="M262 46 232 82" stroke="url(#lau' . $k . ')" stroke-width="4" stroke-linecap="round"/>'
        . '<path d="M212 94c42 6 52 40 30 70 8-32-4-54-30-70z" fill="url(#lau' . $k . ')"/>'
        . '<path d="M50 106c50-36 110-6 162-22l28-32 10 8-22 34c-60 24-120-4-178 12z" fill="url(#lsw' . $k . ')"/>'
        . '<path d="M4 152c56-40 146-36 196-16 46 18 96 14 126-32-20 56-82 68-136 52C130 138 64 134 4 152z" fill="#7db3df"/>'
        . '<path d="M4 152c36-20 72-26 106-24-40 4-70 14-106 24z" fill="#4a8fc7"/>'
        . '<path class="lgf" d="M22 168c54-32 134-26 182-10 46 16 96 8 122-42-14 62-80 74-130 60-60-18-116-22-174-8z"/>'
        . '<path d="M56 178c54-18 114-8 158 4 40 10 76 4 98-18-20 30-62 36-102 26-50-12-102-20-154-12z" fill="url(#lau' . $k . ')"/>'
        . '</svg>';
}

/** Marka yazısı: site adı "AdaCasa" ise logodaki gibi "Ada" lacivert + "Casa" altın ve alt yazı, değilse düz site adı */
function ae_logo_word(): string
{
    $b = ae_brand();
    if (strcasecmp(str_replace(' ', '', $b), 'AdaCasa') !== 0) {
        return '<span class="lgw">' . esc_html($b) . '</span>';
    }
    $tag = ['tr' => 'GAYRİMENKUL & YATIRIM', 'en' => 'REAL ESTATE & INVESTMENT', 'ru' => 'НЕДВИЖИМОСТЬ И ИНВЕСТИЦИИ'][ec_lang()] ?? 'GAYRİMENKUL & YATIRIM';
    return '<span class="lgt"><span class="lgw">Ada<em>Casa</em></span><small>' . esc_html($tag) . '</small></span>';
}

/** Logo: Özelleştir → Site kimliği'nden logo yüklendiyse o, yoksa AdaCasa sembolü + marka yazısı */
function ae_logo(): void
{
    $home = ec_url_home();
    if (has_custom_logo()) {
        $id = (int) get_theme_mod('custom_logo');
        echo '<a class="logo custom" href="' . esc_url($home) . '">' . wp_get_attachment_image($id, 'medium', false, ['alt' => ae_brand(), 'class' => 'logo-img']) . '</a>';
        return;
    }
    echo '<a class="logo" href="' . esc_url($home) . '" aria-label="' . esc_attr(ae_brand()) . '">' . ae_logo_mark('h') . ae_logo_word() . '</a>';
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
        [ec_url_section('offices'), ae_t('firms'), in_array($page, ['offices', 'office'], true)],
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
    // manifest: inc/pwa.php (/manifest.webmanifest?lang=..)
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
  <div class="img"><?php echo $img; ?><span class="tag"><?php echo esc_html($p['city_name'] . ($p['district'] ? ' · ' . $p['district'] : '')); ?></span><?php if (!empty($p['cover_ai'])) : ?><span class="aitag"><?php ae_e('temsili'); ?></span><?php endif; ?></div>
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
    $n = number_format(abs($v), 1, ',', '.');
    $sign = $v > 0.05 ? '+' : ($v < -0.05 ? '−' : '');
    return '<span class="chg ' . $cls . '">' . esc_html(ec_lang() === 'tr' ? $sign . '%' . $n : $sign . $n . '%') . '</span>';
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

/** Yüzde biçimi: Türkçede "%28", diğer dillerde "28%" */
function ae_pct_label(float $v): string
{
    $n = number_format(abs($v), 0, ',', '.');
    return ec_lang() === 'tr' ? '%' . $n : $n . '%';
}

/** "Kur: TCMB · 20.11.2026" satırı */
function ae_rate_line(): string
{
    if (!function_exists('ec_rates_info')) {
        return '';
    }
    $i = ec_rates_info();
    if (!$i['date']) {
        return '';
    }
    if (!empty($i['live'])) {
        return ae_t('conv_live', $i['live']['time']);
    }
    $src = ['tcmb' => 'TCMB', 'kktcmb' => 'KKTC Merkez Bankası', 'frankfurter' => 'ECB', 'er-api' => 'open.er-api.com'][$i['source_key']] ?? $i['source'];
    return ae_t('conv_rates_line', $src, wp_date('d.m.Y', strtotime($i['date'] . ' 12:00')));
}

/** Google / Facebook ile giriş düğmeleri (adacasa-core 1.2.0; anahtarlar girilmemişse boş) */
function ae_social_buttons(string $intent): string
{
    if (!function_exists('ec_social_login_providers') || !($on = ec_social_login_providers())) {
        return '';
    }
    $icon = [
        'google' => '<svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.6 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.2-.1-2.3-.4-3.5z"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="#fff" d="M24 12a12 12 0 1 0-13.9 11.9v-8.4H7.1V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12z"/></svg>',
    ];
    $h = '<div class="sociallogin">';
    foreach ($on as $k => $ad) {
        $h .= '<a class="sl-btn sl-' . esc_attr($k) . '" rel="nofollow" href="' . esc_url(ec_sl_start_url($k, $intent)) . '">' . $icon[$k] . '<span>' . esc_html(ae_t('social_' . $k)) . '</span></a>';
    }
    return $h . '<div class="sl-or"><span>' . esc_html(ae_t('social_or')) . '</span></div></div>';
}

/** ?sosyal=<kod> hata mesajı */
function ae_social_error(): string
{
    $c = sanitize_key((string) ($_GET['sosyal'] ?? ''));
    return in_array($c, ['iptal', 'hata', 'eposta', 'yonetici', 'askida'], true) ? '<p class="errmsg" role="alert">' . esc_html(ae_t('social_err_' . $c)) . '</p>' : '';
}

