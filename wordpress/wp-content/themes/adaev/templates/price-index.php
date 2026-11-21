<?php
/** Fiyat endeksi: /tr/fiyat-endeksi/?sehir=girne&tur=rent */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$mode = ($_GET['tur'] ?? '') === 'rent' ? 'rent' : 'sale';
$city = sanitize_key($_GET['sehir'] ?? '');
$city = isset(ec_cities()[$city]) ? $city : EC_INDEX_ALL;
$cityName = fn(string $c) => $c === EC_INDEX_ALL ? ae_t('idx_all') : ec_city_name($c, $lang);
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('index', $l);
}
ae_seo([
    'title' => ae_t('price_index_title'),
    'description' => ae_t('price_index_sub') . '. ' . ae_t('idx_note'),
    'canonical' => $alts[$lang],
    'alternates' => $alts,
]);
$table = ec_index_table($mode);
$series = ec_index_series($city, $mode, 'konut', 13);
$hasDemo = (bool) array_filter($series, fn($p) => $p['demo']);
$metric = ae_t($mode === 'sale' ? 'idx_m2_sale' : 'idx_m2_rent');
$link = fn(array $q) => esc_url(add_query_arg(array_filter($q + ['tur' => $mode === 'rent' ? 'rent' : '', 'sehir' => $city === EC_INDEX_ALL ? '' : $city]), $alts[$lang]));
$last = $series ? end($series) : null;
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php ae_e('price_index_title'); ?></h1><p><?php ae_e('price_index_sub'); ?></p></div></div>
<main id="main" class="main">
  <div class="panel idxbox">
    <div class="idxtop">
      <div class="seg" role="tablist">
        <a href="<?php echo $link(['tur' => '']); ?>" class="<?php echo $mode === 'sale' ? 'on' : ''; ?>"><?php ae_e('sale'); ?></a>
        <a href="<?php echo $link(['tur' => 'rent']); ?>" class="<?php echo $mode === 'rent' ? 'on' : ''; ?>"><?php ae_e('rent'); ?></a>
      </div>
      <div class="chips">
        <?php foreach (array_merge([EC_INDEX_ALL], array_keys(ec_cities())) as $c) : ?>
          <a href="<?php echo $link(['sehir' => $c === EC_INDEX_ALL ? '' : $c]); ?>" class="<?php echo $c === $city ? 'on' : ''; ?>"><?php echo esc_html($cityName($c)); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($last) : ?>
      <div class="idxhead">
        <div>
          <div class="sub"><?php echo esc_html($cityName($city) . ' · ' . $metric); ?></div>
          <div class="price big"><?php echo ae_money($last['m2']); ?> <span class="sub"><?php ae_e('per_m2'); ?></span></div>
        </div>
        <?php $row = current(array_filter($table, fn($r) => $r['city'] === $city)); ?>
        <?php if ($row) : ?>
          <div class="idxchg"><div><span class="sub"><?php ae_e('idx_chg1'); ?></span><?php echo ae_pct($row['chg1']); ?></div><div><span class="sub"><?php ae_e('idx_chg12'); ?></span><?php echo ae_pct($row['chg12']); ?></div></div>
        <?php endif; ?>
      </div>
      <?php echo ae_chart($series, $cityName($city) . ' – ' . $metric); ?>
    <?php else : ?>
      <p class="empty"><?php ae_e('idx_empty'); ?></p>
    <?php endif; ?>
  </div>

  <?php if ($table) : ?>
    <div class="panel tablewrap">
      <table class="idxtable">
        <thead><tr><th><?php ae_e('idx_city'); ?></th><th><?php echo esc_html($metric); ?></th><th class="hide-s"><?php ae_e('idx_avg'); ?></th><th><?php ae_e('idx_chg1'); ?></th><th><?php ae_e('idx_chg12'); ?></th><th class="hide-s"><?php ae_e('idx_n'); ?></th></tr></thead>
        <tbody>
          <?php foreach ($table as $r) : ?>
            <tr class="<?php echo $r['city'] === EC_INDEX_ALL ? 'total' : ''; ?><?php echo $r['city'] === $city ? ' on' : ''; ?>">
              <td><a href="<?php echo $link(['sehir' => $r['city'] === EC_INDEX_ALL ? '' : $r['city']]); ?>"><?php echo esc_html($cityName($r['city'])); ?></a></td>
              <td><b><?php echo ae_money($r['m2']); ?></b></td>
              <td class="hide-s"><?php echo ae_money($r['avg']) . esc_html($mode === 'rent' ? ae_t('per_month') : ''); ?></td>
              <td><?php echo ae_pct($r['chg1']); ?></td>
              <td><?php echo ae_pct($r['chg12']); ?></td>
              <td class="hide-s"><?php echo (int) $r['n']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <p class="note"><?php ae_e('idx_note'); ?><?php if ($hasDemo) : ?> <b><?php ae_e('idx_demo'); ?></b><?php endif; ?></p>
</main>
<?php
get_footer();
