<?php
/** Projeler: /tr/projeler/ */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('project', $l);
}
ae_seo(['title' => ae_t('projects') . ' – ' . ae_t('tagline'), 'description' => ae_t('hero_sub'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
$projects = ec_projects($lang);
$fmtDate = fn($d) => $d ? wp_date('F Y', strtotime($d)) : '—';
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php ae_e('projects'); ?></h1></div></div>
<main id="main" class="main">
  <?php ae_search_box(['mode' => 'project']); ?>
  <div class="grid" style="margin-top:24px">
    <?php foreach ($projects as $p) : ?>
      <article class="card proj">
        <div class="img"><?php echo AE_ICON; ?></div>
        <div class="body">
          <h3 class="ttl"><?php echo esc_html($p['name']); ?></h3>
          <div class="sub"><?php echo esc_html($p['developer'] . ' · ' . $p['city_name'] . ($p['district'] ? ' / ' . $p['district'] : '')); ?></div>
          <div class="pills">
            <?php if ($p['units']) : ?><span><?php ae_e('units', (int) $p['units']); ?></span><?php endif; ?>
            <span><?php ae_e('delivery', $fmtDate($p['delivery_date'])); ?></span>
            <?php if ($p['price_min_gbp']) : ?><span><?php echo ae_money((float) $p['price_min_gbp']); ?> – <?php echo ae_money((float) $p['price_max_gbp']); ?></span><?php endif; ?>
          </div>
          <?php if ($p['payment_plan']) : ?><div class="sub" style="margin-top:8px"><?php ae_e('payment_plan'); ?>: <?php echo esc_html($p['payment_plan']); ?></div><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$projects) : ?><div class="empty"><?php ae_e('no_results'); ?></div><?php endif; ?>
  </div>
</main>
<?php
get_footer();
