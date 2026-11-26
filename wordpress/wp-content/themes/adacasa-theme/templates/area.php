<?php
/** Bölge rehberi: /tr/bolge/{slug}/ ve liste /tr/bolge/ */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$slug = sanitize_key((string) get_query_var('ec_slug'));
$all = function_exists('ec_areas_all') ? ec_areas_all() : [];
$alts = [];

/* ---------- Liste ---------- */
if ($slug === '') {
    foreach (EC_LANGS as $l) {
        $alts[$l] = ec_url_area(null, $l);
    }
    ae_seo(['title' => ae_t('ar_list_title'), 'description' => ae_t('ar_list_desc'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
    get_header(); ?>
<div class="pagehead"><div class="in"><h1><?php ae_e('ar_list_title'); ?></h1><p><?php ae_e('ar_list_desc'); ?></p></div></div>
<main id="main" class="main">
  <?php foreach (ec_cities() as $ck => $c) :
      $items = array_filter(ec_area_list(), fn($x, $s) => $x[0] === $ck && !empty($all[$s]), ARRAY_FILTER_USE_BOTH);
      if (!$items) {
          continue;
      } ?>
    <h2 class="sec"><?php echo esc_html($c['name'][$lang]); ?></h2>
    <div class="arlist">
      <?php foreach ($items as $s => [$cc, $d]) : ?>
        <a class="arcard" href="<?php echo esc_url(ec_url_area($s)); ?>"><b><?php echo esc_html($d ?: $c['name'][$lang]); ?></b><span class="sub"><?php echo esc_html(mb_substr($all[$s][$lang]['desc'] ?? ($all[$s]['en']['desc'] ?? ''), 0, 110)); ?>…</span></a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</main>
<?php
    get_footer();
    return;
}

/* ---------- Tek rehber ---------- */
$a = function_exists('ec_area') ? ec_area($slug) : null;
$tx = $a['texts'][$lang] ?? ($a['texts']['en'] ?? ($a['texts']['tr'] ?? null));
if (!$a || !$tx) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    include get_theme_file_path('templates/404.php');
    return;
}
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_area($slug, $l);
}
$cityName = ec_city_name($a['city'], $lang);
$place = $a['district'] ?: $cityName;
$base = array_filter(['city' => $a['city'], 'district' => $a['district']]);
$nSale = ec_listing_search($base + ['mode' => 'sale'], $lang, 1, 1)['total'];
$nRent = ec_listing_search($base + ['mode' => 'rent'], $lang, 1, 1)['total'];
$latest = ec_listing_search($base + ['mode' => 'sale'], $lang, 1, 6)['items'];
if (!$latest) {
    $latest = ec_listing_search($base + ['mode' => 'rent'], $lang, 1, 6)['items'];
}
$distQ = $a['district'] ? ['district' => $a['district']] : [];
$saleUrl = add_query_arg($distQ, ec_url_section('sale', $lang, $a['city']));
$rentUrl = add_query_arg($distQ, ec_url_section('rent', $lang, $a['city']));
$idx = function_exists('ec_index_series') ? ec_index_series($a['city'], 'sale', 'konut', 2) : [];
$idxLast = $idx ? end($idx) : null;
$idxR = function_exists('ec_index_series') ? ec_index_series($a['city'], 'rent', 'konut', 2) : [];
$idxRLast = $idxR ? end($idxR) : null;
$siblings = array_filter(ec_area_list(), fn($x, $s) => $x[0] === $a['city'] && $s !== $slug && !empty($all[$s]), ARRAY_FILTER_USE_BOTH);

ae_seo([
    'title' => $tx['title'],
    'description' => $tx['desc'],
    'canonical' => $alts[$lang],
    'alternates' => $alts,
    'image' => $latest[0]['cover']['large'] ?? '',
    'jsonld' => ['@context' => 'https://schema.org', '@graph' => [
        ae_breadcrumb_ld([[ae_t('home'), ec_url_home()], [ae_t('ar_list_title'), ec_url_area()], [$place, $alts[$lang]]]),
        ['@type' => 'Article', 'headline' => $tx['title'], 'description' => $tx['desc'], 'inLanguage' => $lang, 'url' => $alts[$lang],
            'about' => ['@type' => 'Place', 'name' => $place, 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $place, 'addressRegion' => $cityName, 'addressCountry' => 'CY']],
            'publisher' => ['@type' => 'Organization', 'name' => ae_brand(), 'url' => ec_url_home()],
            'dateModified' => gmdate('c', strtotime(($a['texts']['reviewed'] ?? $a['texts']['generated_at'] ?? ec_now()) . ' UTC'))],
    ]],
]);
get_header();
?>
<div class="pagehead"><div class="in">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <a href="<?php echo esc_url(ec_url_area()); ?>"><?php ae_e('ar_list_title'); ?></a> › <span><?php echo esc_html($place); ?></span></nav>
  <h1><?php echo esc_html($tx['title']); ?></h1>
</div></div>
<main id="main" class="main area">
  <div class="arstats">
    <a class="arstat" href="<?php echo esc_url($saleUrl); ?>"><b><?php echo (int) $nSale; ?></b><span><?php ae_e('ar_sale_n', $place); ?></span></a>
    <a class="arstat" href="<?php echo esc_url($rentUrl); ?>"><b><?php echo (int) $nRent; ?></b><span><?php ae_e('ar_rent_n', $place); ?></span></a>
    <?php if ($idxLast && !empty($idxLast['m2'])) : ?><a class="arstat" href="<?php echo esc_url(add_query_arg('sehir', $a['city'], ec_url_section('index'))); ?>"><b><?php echo ae_money((float) $idxLast['m2']); ?></b><span><?php ae_e('ar_m2', $cityName); ?></span></a><?php endif; ?>
    <?php if ($idxRLast && !empty($idxRLast['m2'])) : ?><a class="arstat" href="<?php echo esc_url(add_query_arg(['sehir' => $a['city'], 'tur' => 'rent'], ec_url_section('index'))); ?>"><b><?php echo ae_money((float) $idxRLast['m2']); ?></b><span><?php ae_e('ar_m2_rent', $cityName); ?></span></a><?php endif; ?>
  </div>
  <article class="panel arbody"><?php echo wp_kses_post($tx['body']); ?>
    <p class="sub ardisc"><?php ae_e('ar_disclaimer'); ?></p>
  </article>
  <?php if ($latest) : ?>
    <h2 class="sec"><?php ae_e('ar_latest', $place); ?></h2>
    <div class="grid"><?php foreach ($latest as $it) {
        echo ae_card($it);
    } ?></div>
    <p class="center"><a class="btn" href="<?php echo esc_url($saleUrl); ?>"><?php ae_e('ar_all_sale', $place); ?> →</a> <a class="ghost" href="<?php echo esc_url($rentUrl); ?>"><?php ae_e('ar_all_rent', $place); ?> →</a></p>
  <?php endif; ?>
  <?php if ($siblings) : ?>
    <h2 class="sec"><?php ae_e('ar_more', $cityName); ?></h2>
    <div class="chips arsib"><?php foreach ($siblings as $s => [$cc, $d]) : ?><a href="<?php echo esc_url(ec_url_area($s)); ?>"><?php echo esc_html($d ?: $cityName); ?></a><?php endforeach; ?></div>
  <?php endif; ?>
</main>
<?php get_footer();
