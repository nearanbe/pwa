<?php
/**
 * Arama sonuçları: /tr/satilik/, /tr/satilik/girne/?ptype=konut&subtype=villa ...
 * Düzen (0.7): solda filtre paneli (telefonda alttan açılan çekmece), üstte sonuç sayısı, sıralama ve
 * Izgara / Liste / Harita görünümü, numaralı sayfalar.
 */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$mode = get_query_var('ec_mode') ?: 'sale';
$city = get_query_var('ec_city') ?: sanitize_key($_GET['city'] ?? '');
$city = isset(ec_cities()[$city]) ? $city : '';
$types = ec_property_types();
$g = fn(string $k) => isset($_GET[$k]) && !is_array($_GET[$k]) ? sanitize_text_field(wp_unslash($_GET[$k])) : '';
$ga = fn(string $k) => isset($_GET[$k]) ? array_values(array_filter(array_map('sanitize_text_field', (array) wp_unslash($_GET[$k])))) : [];
$rooms = $ga('rooms') ?: array_filter([$g('rooms')]);
$f = [
    'mode' => $mode,
    'city' => $city,
    'ptype' => isset($types[$g('ptype')]) ? $g('ptype') : '',
    'subtype' => $g('subtype'),
    'district' => $g('district'),
    'rooms' => array_values(array_intersect($rooms, ec_room_options())),
    'deed_type' => $mode === 'sale' && isset(ec_deed_types()[$g('deed_type')]) ? $g('deed_type') : '', // kiralıkta tapu yok
    'pano' => $g('pano') ? 1 : '',
    'min' => $g('min'),
    'max' => $g('max'),
    'amin' => $g('amin'),
    'amax' => $g('amax'),
    'baths' => $g('baths'),
    'furnished' => in_array($g('furnished'), ['0', '1'], true) ? $g('furnished') : '',
    'feat' => array_values(array_intersect($ga('feat'), array_keys(ec_features()))),
    'kind' => array_values(array_intersect($ga('kind'), array_keys(ec_member_kinds()))),
    'days' => in_array((int) $g('days'), [1, 3, 7, 30], true) ? (int) $g('days') : '',
    'q' => $g('q'),
    'currency' => strtoupper($g('currency')) ?: ae_currency(),
    'sort' => in_array($g('sort'), ['new', 'price_asc', 'price_desc', 'm2_asc', 'area_desc'], true) ? $g('sort') : 'new',
    'agent_id' => (int) $g('agent'),
];
if ($f['subtype'] && !isset($types[$f['ptype']]['sub'][$f['subtype']])) {
    $f['subtype'] = '';
}
$view = in_array($g('gorunum'), ['liste', 'harita'], true) ? $g('gorunum') : 'izgara';
$pg = max(1, (int) $g('sayfa'));
$res = ec_listing_search($f, $lang, $pg, $view === 'harita' ? 60 : 24);

// Başlık: "Girne Satılık Villa İlanları"
$what = $f['subtype'] ? ec_subtype_name($f['ptype'], $f['subtype'], $lang) : ($f['ptype'] ? ec_type_name($f['ptype'], $lang) : '');
$heading = trim(($city ? ec_city_name($city, $lang) . ' ' : '') . ($f['district'] ? $f['district'] . ' ' : '') . ae_t($mode) . ' ' . $what);
$keep = array_filter(['ptype' => $f['ptype'], 'subtype' => $f['subtype'], 'district' => $f['district']]);
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section($mode, $l, $city ?: null, $keep + ($pg > 1 ? ['sayfa' => $pg] : []));
}
$filtered = array_filter(array_diff_key($f, array_flip(['mode', 'city', 'ptype', 'subtype', 'district', 'currency', 'sort'])));
// SEO başlığı (uzun kuyruk): "Girne Satılık Ev, Daire ve Villa İlanları"
$seoPlace = trim(($city ? ec_city_name($city, $lang) : ae_t('seo_place_all')) . ($f['district'] ? ' ' . $f['district'] : ''));
$seoTitle = ae_t('seo_search_title', $seoPlace, ae_t($mode), $what ?: ae_t('seo_what_' . $mode));
$crumbs = [[ae_t('home'), ec_url_home()], [ae_t($mode), ec_url_section($mode)]];
if ($city) {
    $crumbs[] = [ec_city_name($city, $lang), ec_url_section($mode, $lang, $city)];
}
$ldItems = [];
foreach (array_slice($res['items'], 0, 24) as $i => $it) {
    $ldItems[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $it['url'], 'name' => $it['title']];
}
ae_seo([
    'title' => $seoTitle,
    'description' => ae_t('seo_search_desc', $seoTitle, number_format_i18n($res['total'])),
    'canonical' => $alts[$lang],
    'alternates' => $alts,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            ['@type' => 'CollectionPage', 'name' => $seoTitle, 'url' => $alts[$lang], 'inLanguage' => $lang, 'isPartOf' => ['@id' => home_url('/#website')]],
            ae_breadcrumb_ld($crumbs),
            $ldItems ? ['@type' => 'ItemList', 'numberOfItems' => (int) $res['total'], 'itemListElement' => $ldItems] : null,
        ])),
    ],
]);
if ($filtered || $view !== 'izgara') {
    add_action('wp_head', fn() => print('<meta name="robots" content="noindex, follow">' . "\n")); // filtre kombinasyonları dizine girmesin
}
$GLOBALS['ae_quicklinks'] = true;
// Linklerde taşınan filtreler (mode ve city adresin içinde)
$q = [];
foreach ($f as $k => $v) {
    if ($v !== '' && $v !== 0 && $v !== [] && !in_array($k, ['mode', 'city'], true) && !($k === 'sort' && $v === 'new') && !($k === 'currency' && !$f['min'] && !$f['max'])) {
        $q[$k === 'agent_id' ? 'agent' : $k] = $v;
    }
}
if ($view !== 'izgara') {
    $q['gorunum'] = $view;
}
$pageUrl = fn(int $p) => ec_url_section($mode, $lang, $city ?: null, $q + ['sayfa' => $p > 1 ? $p : '']);
$viewUrl = fn(string $v) => ec_url_section($mode, $lang, $city ?: null, array_diff_key($q, ['gorunum' => 1, 'sayfa' => 1]) + ($v !== 'izgara' ? ['gorunum' => $v] : []));
$cities = ec_cities();
$subs = [];
foreach ($types as $k => $t) {
    foreach ($t['sub'] as $sk => $sn) {
        $subs[$k][] = [$sk, $sn[$lang]];
    }
}
$topFeats = $mode === 'sale'
    ? ['deniz_manzarasi', 'denize_sifir', 'ozel_havuz', 'ortak_havuz', 'site_icinde', 'bahce', 'asansor', 'kapali_otopark', 'jenerator', 'akilli_ev']
    : ['deniz_manzarasi', 'ozel_havuz', 'ortak_havuz', 'site_icinde', 'klima', 'beyaz_esya', 'otopark', 'evcil_hayvan', 'ogrenciye_uygun', 'jenerator'];
$nFilters = count($filtered) + ($f['ptype'] && $f['ptype'] !== 'konut' ? 1 : 0) + ($f['subtype'] ? 1 : 0) + ($f['district'] ? 1 : 0);
if ($view === 'harita') {
    ae_enqueue_leaflet();
    add_action('wp_enqueue_scripts', function () {
        // main.js haritayı kurabilsin: Leaflet önce yüklensin
        wp_deregister_script('ae-main');
        wp_register_script('ae-main', get_theme_file_uri('assets/js/main.js'), ['leaflet'], AE_VER, ['in_footer' => true]);
    }, 5);
}
get_header();
?>
<div class="pagehead slim"><div class="in">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <a href="<?php echo esc_url(ec_url_section($mode)); ?>"><?php ae_e($mode); ?></a><?php if ($city) : ?> › <?php echo esc_html(ec_city_name($city, $lang)); ?><?php endif; ?></nav>
  <h1><?php echo esc_html($seoTitle); ?></h1>
</div></div>
<main id="main" class="main sres">
  <aside class="sfilter" id="sfilter" aria-label="<?php ae_e('filters'); ?>">
    <form method="get" action="<?php echo esc_url(ec_url_section($mode)); ?>" id="sform" data-subs="<?php echo esc_attr(wp_json_encode($subs)); ?>" data-dists="<?php echo esc_attr(wp_json_encode(array_map(fn($c) => $c['districts'], $cities))); ?>" data-base="<?php echo esc_attr(ec_url_section($mode)); ?>">
      <div class="sf-head"><b><?php ae_e('filters'); ?></b><button type="button" class="sf-x" data-sf-close aria-label="<?php ae_e('close'); ?>">✕</button></div>
      <div class="sf-modes" role="tablist">
        <?php foreach (ec_modes() as $m) : ?><a role="tab" aria-selected="<?php echo $m === $mode ? 'true' : 'false'; ?>" class="<?php echo $m === $mode ? 'on' : ''; ?>" href="<?php echo esc_url(ec_url_section($m, $lang, $city ?: null)); ?>"><?php ae_e($m); ?></a><?php endforeach; ?>
      </div>
      <label class="sf-q"><span class="vh"><?php ae_e('sf_q'); ?></span><input name="q" value="<?php echo esc_attr($f['q']); ?>" placeholder="<?php ae_e('sf_q'); ?>"></label>

      <details open><summary><?php ae_e('property_type'); ?></summary>
        <select name="ptype" id="sf-ptype"><?php foreach ($types as $k => $t) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($f['ptype'] ?: 'konut', $k); ?>><?php echo esc_html($t['name'][$lang]); ?></option><?php endforeach; ?></select>
        <select name="subtype" id="sf-sub"><option value=""><?php ae_e('all'); ?></option><?php foreach ($types[$f['ptype'] ?: 'konut']['sub'] as $sk => $sn) : ?><option value="<?php echo esc_attr($sk); ?>" <?php selected($f['subtype'], $sk); ?>><?php echo esc_html($sn[$lang]); ?></option><?php endforeach; ?></select>
      </details>
      <details open><summary><?php ae_e('location'); ?></summary>
        <select name="city" id="sf-city"><option value=""><?php ae_e('sf_all_cities'); ?></option><?php foreach ($cities as $k => $c) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($city, $k); ?>><?php echo esc_html($c['name'][$lang]); ?></option><?php endforeach; ?></select>
        <select name="district" id="sf-dist"><option value=""><?php ae_e('district'); ?></option><?php foreach (($cities[$city]['districts'] ?? []) as $d) : ?><option <?php selected($f['district'], $d); ?>><?php echo esc_html($d); ?></option><?php endforeach; ?></select>
      </details>
      <details open><summary><?php ae_e('sf_price'); ?></summary>
        <div class="sf-row"><input name="min" inputmode="numeric" placeholder="<?php ae_e('min_price'); ?>" value="<?php echo esc_attr($f['min']); ?>"><input name="max" inputmode="numeric" placeholder="<?php ae_e('max_price'); ?>" value="<?php echo esc_attr($f['max']); ?>">
          <select name="currency" class="sf-cur" aria-label="<?php ae_e('currency'); ?>"><?php foreach (EC_CURRENCIES as $c) : ?><option value="<?php echo $c; ?>" <?php selected($f['currency'], $c); ?>><?php echo esc_html(AE_SYM[$c]); ?></option><?php endforeach; ?></select></div>
      </details>
      <details open><summary><?php ae_e('rooms'); ?></summary>
        <div class="sf-chips"><?php foreach (ec_room_options() as $r) : ?><label class="chip"><input type="checkbox" name="rooms[]" value="<?php echo esc_attr($r); ?>" <?php checked(in_array($r, $f['rooms'], true)); ?>> <?php echo esc_html($r); ?></label><?php endforeach; ?></div>
      </details>
      <details <?php echo $f['baths'] || $f['amin'] || $f['amax'] ? 'open' : ''; ?>><summary><?php ae_e('sf_size'); ?></summary>
        <div class="sf-row"><input name="amin" inputmode="numeric" placeholder="m² min" value="<?php echo esc_attr($f['amin']); ?>"><input name="amax" inputmode="numeric" placeholder="m² max" value="<?php echo esc_attr($f['amax']); ?>"></div>
        <div class="sf-seg" role="radiogroup" aria-label="<?php ae_e('baths'); ?>"><span class="sub"><?php ae_e('baths'); ?></span><?php foreach (['' => ae_t('all'), '1' => '1+', '2' => '2+', '3' => '3+'] as $v => $n) : ?><label><input type="radio" name="baths" value="<?php echo $v; ?>" <?php checked((string) $f['baths'], (string) $v); ?>><span><?php echo esc_html($n); ?></span></label><?php endforeach; ?></div>
      </details>
      <?php if ($mode === 'sale') : ?>
        <details <?php echo $f['deed_type'] ? 'open' : ''; ?>><summary><?php ae_e('deed'); ?></summary>
          <select name="deed_type"><option value=""><?php ae_e('all'); ?></option><?php foreach (ec_deed_types() as $dk => $dn) : if ($dk === 'diger') { continue; } ?><option value="<?php echo esc_attr($dk); ?>" <?php selected($f['deed_type'], $dk); ?>><?php echo esc_html($dn[$lang]); ?></option><?php endforeach; ?></select>
        </details>
      <?php endif; ?>
      <details <?php echo $f['furnished'] !== '' ? 'open' : ''; ?>><summary><?php ae_e('furnished'); ?></summary>
        <div class="sf-seg" role="radiogroup"><?php foreach (['' => ae_t('all'), '1' => ae_t('furnished_yes'), '0' => ae_t('furnished_no')] as $v => $n) : ?><label><input type="radio" name="furnished" value="<?php echo $v; ?>" <?php checked((string) $f['furnished'], (string) $v); ?>><span><?php echo esc_html($n); ?></span></label><?php endforeach; ?></div>
      </details>
      <details <?php echo $f['feat'] ? 'open' : ''; ?>><summary><?php ae_e('f_features'); ?></summary>
        <div class="sf-chips"><?php foreach ($topFeats as $fk) : ?><label class="chip"><input type="checkbox" name="feat[]" value="<?php echo esc_attr($fk); ?>" <?php checked(in_array($fk, $f['feat'], true)); ?>> <?php echo esc_html(ec_features()[$fk][$lang]); ?></label><?php endforeach; ?></div>
      </details>
      <details <?php echo $f['kind'] ? 'open' : ''; ?>><summary><?php ae_e('sf_from'); ?></summary>
        <div class="sf-chips"><?php foreach (ec_member_kinds() as $k => $m) : ?><label class="chip"><input type="checkbox" name="kind[]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, $f['kind'], true)); ?>> <?php echo esc_html($m['name'][$lang]); ?></label><?php endforeach; ?></div>
      </details>
      <details <?php echo $f['days'] || $f['pano'] ? 'open' : ''; ?>><summary><?php ae_e('sf_more'); ?></summary>
        <select name="days" aria-label="<?php ae_e('sf_days'); ?>"><option value=""><?php ae_e('sf_days'); ?>: <?php ae_e('all'); ?></option><?php foreach ([1, 3, 7, 30] as $d) : ?><option value="<?php echo $d; ?>" <?php selected((int) $f['days'], $d); ?>><?php ae_e('sf_days_n', $d); ?></option><?php endforeach; ?></select>
        <label class="chip"><input type="checkbox" name="pano" value="1" <?php checked((bool) $f['pano']); ?>> 🌐 <?php ae_e('pano_filter'); ?></label>
      </details>
      <?php if ($f['agent_id']) : ?><input type="hidden" name="agent" value="<?php echo (int) $f['agent_id']; ?>"><?php endif; ?>
      <?php if ($view !== 'izgara') : ?><input type="hidden" name="gorunum" value="<?php echo esc_attr($view); ?>"><?php endif; ?>
      <div class="sf-foot">
        <a class="ghost" href="<?php echo esc_url(ec_url_section($mode, $lang, null, $view !== 'izgara' ? ['gorunum' => $view] : [])); ?>"><?php ae_e('sf_clear'); ?></a>
        <button class="btn" type="submit"><?php ae_e('sf_show', number_format_i18n($res['total'])); ?></button>
      </div>
    </form>
  </aside>

  <section class="sresults" aria-live="polite">
    <div class="stools">
      <button type="button" class="ghost sf-open" data-sf-open>☰ <?php ae_e('filters'); ?><?php if ($nFilters) : ?> <span class="count"><?php echo (int) $nFilters; ?></span><?php endif; ?></button>
      <b class="scount"><?php ae_e('listings_count', number_format_i18n($res['total'])); ?></b>
      <form method="get" class="sortf">
        <?php foreach (array_diff_key($q, ['sort' => 1, 'sayfa' => 1]) as $k => $v) : foreach ((array) $v as $vv) : ?>
          <input type="hidden" name="<?php echo esc_attr($k) . (is_array($v) ? '[]' : ''); ?>" value="<?php echo esc_attr((string) $vv); ?>">
        <?php endforeach; endforeach; ?>
        <label class="vh" for="sort"><?php ae_e('sort'); ?></label>
        <select id="sort" name="sort" class="mini" onchange="this.form.submit()">
          <?php foreach (['new', 'price_asc', 'price_desc', 'm2_asc', 'area_desc'] as $o) : ?><option value="<?php echo $o; ?>" <?php selected($f['sort'], $o); ?>><?php ae_e('sort_' . $o); ?></option><?php endforeach; ?>
        </select>
      </form>
      <nav class="views" aria-label="<?php ae_e('sf_view'); ?>">
        <?php foreach (['izgara' => ['grid', 'sf_grid'], 'liste' => ['note', 'sf_list'], 'harita' => ['pin', 'sf_map']] as $v => [$ic, $lb]) : ?>
          <a href="<?php echo esc_url($viewUrl($v)); ?>" class="<?php echo $view === $v ? 'on' : ''; ?>" aria-current="<?php echo $view === $v ? 'page' : 'false'; ?>" title="<?php ae_e($lb); ?>"><?php echo ae_icon($ic, 18); ?><span class="hide-s"> <?php ae_e($lb); ?></span></a>
        <?php endforeach; ?>
      </nav>
    </div>

    <?php if ($view === 'harita') :
        $pts = array_values(array_filter(array_map(fn($a) => $a['lat'] ? ['lat' => $a['lat'], 'lng' => $a['lng'], 't' => $a['title'], 'u' => $a['url'], 'p' => ae_format_money($a['price_gbp']) . ae_price_suffix($a['mode']), 'i' => $a['cover']['small'] ?? ''] : null, $res['items']))); ?>
      <div class="resmap" id="resmap" data-pts="<?php echo esc_attr(wp_json_encode($pts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>"></div>
      <p class="sub"><?php ae_e('sf_map_note', count($pts), count($res['items'])); ?></p>
    <?php endif; ?>

    <?php
    // Kategori vitrini: sadece ilk sayfada, aynı tür (satılık/kiralık/günlük) ve şehirde
    $catVit = ($pg === 1 && $view !== 'harita' && function_exists('ec_listing_showcase')) ? ec_listing_showcase('cat', $lang, ['mode' => $mode, 'city' => $f['city'] ?? '', 'ptype' => $f['ptype'] ?? ''], 4) : [];
    if ($catVit) : ?>
      <div class="catvit"><div class="catvit-h">✦ <?php echo esc_html(ae_t('showcase_cat', $heading)); ?></div>
        <div class="grid"><?php foreach ($catVit as $cv) {
            echo ae_card($cv);
        } ?></div>
      </div>
    <?php endif; ?>
    <div class="grid <?php echo $view === 'liste' ? 'list' : ''; ?> <?php echo $view === 'harita' ? 'compact' : ''; ?>">
      <?php foreach ($res['items'] as $i => $a) {
          echo ae_card($a);
          if ($view !== 'harita' && ($i + 1) % 6 === 0) {
              echo ae_ad_card();
          }
      } ?>
      <?php if (!$res['items']) : ?><div class="empty"><?php ae_e('no_results'); ?> <a href="<?php echo esc_url(ec_url_section($mode)); ?>"><?php ae_e('sf_clear'); ?> →</a></div><?php endif; ?>
    </div>

    <?php if ($res['pages'] > 1) :
        $nums = array_unique(array_filter([1, $pg - 2, $pg - 1, $pg, $pg + 1, $pg + 2, $res['pages']], fn($n) => $n >= 1 && $n <= $res['pages']));
        sort($nums); ?>
      <nav class="pager" aria-label="<?php ae_e('page'); ?>">
        <?php if ($pg > 1) : ?><a class="ghost" href="<?php echo esc_url($pageUrl($pg - 1)); ?>" rel="prev" aria-label="<?php ae_e('prev'); ?>">‹</a><?php endif; ?>
        <?php $last = 0; foreach ($nums as $n) : if ($n - $last > 1) : ?><span class="gap">…</span><?php endif; ?>
          <a class="<?php echo $n === $pg ? 'on' : 'ghost'; ?>" href="<?php echo esc_url($pageUrl($n)); ?>" <?php echo $n === $pg ? 'aria-current="page"' : ''; ?>><?php echo $n; ?></a>
        <?php $last = $n; endforeach; ?>
        <?php if ($pg < $res['pages']) : ?><a class="ghost" href="<?php echo esc_url($pageUrl($pg + 1)); ?>" rel="next" aria-label="<?php ae_e('next'); ?>">›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
    <?php echo ae_ad('mid'); ?>
  </section>
  <div class="sf-backdrop" data-sf-close hidden></div>
</main>
<?php
get_footer();
