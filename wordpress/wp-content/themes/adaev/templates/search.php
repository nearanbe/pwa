<?php
/** Arama sonuçları: /tr/satilik/, /tr/satilik/girne/?ptype=konut&subtype=villa ... */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$mode = get_query_var('ec_mode') ?: 'sale';
$city = get_query_var('ec_city') ?: sanitize_key($_GET['city'] ?? '');
$city = isset(ec_cities()[$city]) ? $city : '';
$types = ec_property_types();
$g = fn(string $k) => isset($_GET[$k]) ? sanitize_text_field(wp_unslash($_GET[$k])) : '';
$f = [
    'mode' => $mode,
    'city' => $city,
    'ptype' => isset($types[$g('ptype')]) ? $g('ptype') : '',
    'subtype' => $g('subtype'),
    'district' => $g('district'),
    'rooms' => $g('rooms'),
    'deed_type' => isset(ec_deed_types()[$g('deed_type')]) ? $g('deed_type') : '',
    'min' => $g('min'),
    'max' => $g('max'),
    'currency' => strtoupper($g('currency')) ?: ae_currency(),
    'sort' => in_array($g('sort'), ['new', 'price_asc', 'price_desc'], true) ? $g('sort') : 'new',
    'agent_id' => (int) $g('agent'),
];
if ($f['subtype'] && !isset($types[$f['ptype']]['sub'][$f['subtype']])) {
    $f['subtype'] = '';
}
$pg = max(1, (int) $g('sayfa'));
$res = ec_listing_search($f, $lang, $pg, 24);

// Başlık: "Girne Satılık Villa İlanları"
$what = $f['subtype'] ? ec_subtype_name($f['ptype'], $f['subtype'], $lang) : ($f['ptype'] ? ec_type_name($f['ptype'], $lang) : '');
$heading = trim(($city ? ec_city_name($city, $lang) . ' ' : '') . ($f['district'] ? $f['district'] . ' ' : '') . ae_t($mode) . ' ' . $what);
$keep = array_filter(['ptype' => $f['ptype'], 'subtype' => $f['subtype'], 'district' => $f['district'], 'rooms' => $f['rooms']]);
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section($mode, $l, $city ?: null, $keep + ($pg > 1 ? ['sayfa' => $pg] : []));
}
ae_seo([
    'title' => $heading . ' – ' . ae_t('listings_count', number_format_i18n($res['total'])),
    'description' => $heading . '. ' . ae_t('hero_sub'),
    'canonical' => $alts[$lang],
    'alternates' => $alts,
]);
$GLOBALS['ae_quicklinks'] = true;
// Linklerde taşınan filtreler (mode ve city adresin içinde)
$q = [];
foreach ($f as $k => $v) {
    if ($v !== '' && $v !== 0 && !in_array($k, ['mode', 'city'], true)) {
        $q[$k === 'agent_id' ? 'agent' : $k] = $v;
    }
}
$pageUrl = fn(int $p) => ec_url_section($mode, $lang, $city ?: null, $q + ['sayfa' => $p > 1 ? $p : '']);
get_header();
?>
<div class="pagehead"><div class="in">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <a href="<?php echo esc_url(ec_url_section($mode)); ?>"><?php ae_e($mode); ?></a><?php if ($city) : ?> › <?php echo esc_html(ec_city_name($city, $lang)); ?><?php endif; ?></nav>
  <h1><?php echo esc_html($heading); ?></h1>
</div></div>
<main id="main" class="main">
  <?php ae_search_box(['ptype' => $f['ptype'] ?: 'konut'] + $f); ?>
  <div class="sechead">
    <h2 class="sec"><?php ae_e('listings_count', number_format_i18n($res['total'])); ?></h2>
    <form method="get" class="sortf">
      <?php foreach (array_diff_key($q, ['sort' => 1]) as $k => $v) : ?>
        <input type="hidden" name="<?php echo esc_attr($k); ?>" value="<?php echo esc_attr((string) $v); ?>">
      <?php endforeach; ?>
      <label for="sort"><?php ae_e('sort'); ?></label>
      <select id="sort" name="sort" class="mini" onchange="this.form.submit()">
        <?php foreach (['new', 'price_asc', 'price_desc'] as $o) : ?><option value="<?php echo $o; ?>" <?php selected($f['sort'], $o); ?>><?php ae_e('sort_' . $o); ?></option><?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="grid">
    <?php foreach ($res['items'] as $i => $a) {
        echo ae_card($a);
        if (($i + 1) % 6 === 0) {
            echo ae_ad_card();
        }
    } ?>
    <?php if (!$res['items']) : ?><div class="empty"><?php ae_e('no_results'); ?></div><?php endif; ?>
  </div>
  <?php if ($res['pages'] > 1) : ?>
    <nav class="pager" aria-label="<?php ae_e('page'); ?>">
      <?php if ($pg > 1) : ?><a class="ghost" href="<?php echo esc_url($pageUrl($pg - 1)); ?>" rel="prev">‹ <?php ae_e('prev'); ?></a><?php endif; ?>
      <span><?php ae_e('page'); ?> <?php echo (int) $pg; ?> / <?php echo (int) $res['pages']; ?></span>
      <?php if ($pg < $res['pages']) : ?><a class="ghost" href="<?php echo esc_url($pageUrl($pg + 1)); ?>" rel="next"><?php ae_e('next'); ?> ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
  <?php echo ae_ad('mid'); ?>
</main>
<?php
get_footer();
