<?php
/** Proje detayı: /tr/proje/{slug}-{no}/ */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$p = $GLOBALS['ec_current_project'];
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_project($p['id'], ec_slugify($p['name']), $l);
}
$loc = $p['city_name'] . ($p['district'] ? ' / ' . $p['district'] : '');
ae_seo([
    'title' => $p['name'] . ' – ' . $loc,
    'description' => $p['description'] ?: $p['name'],
    'canonical' => $p['url'],
    'alternates' => $alts,
    'image' => $p['cover']['large'] ?? '',
]);
$agent = $p['agent_id'] ? ec_agent($p['agent_id']) : null;
$wa = $agent ? preg_replace('/\D/', '', (string) ($agent['whatsapp'] ?: $agent['phone'])) : preg_replace('/\D/', '', (string) (get_option('ec_contact')['whatsapp'] ?? ''));
$others = array_values(array_filter(ec_projects($lang, 8), fn($x) => $x['id'] !== $p['id']));
get_header();
?>
<main id="main" class="main detail">
  <nav class="crumbs" aria-label="breadcrumb">
    <a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> ›
    <a href="<?php echo esc_url(ec_url_section('project')); ?>"><?php ae_e('projects'); ?></a> ›
    <span><?php echo esc_html($p['name']); ?></span>
  </nav>
  <div class="dgrid">
    <div class="dgal">
      <div class="gallery">
        <?php if ($p['cover']) : ?>
          <div class="gtrack"><figure class="gslide on"><img src="<?php echo esc_url($p['cover']['large']); ?>" alt="<?php echo esc_attr($p['name']); ?>"></figure></div>
        <?php else : ?>
          <div class="img noimg"><?php echo AE_ICON; ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="ddesc">
      <?php if ($p['description']) : ?>
        <section class="panel"><h2><?php ae_e('description'); ?></h2><div class="desc"><?php echo wpautop(esc_html($p['description'])); ?></div></section>
      <?php endif; ?>
    </div>
    <aside class="dside">
      <div class="panel sticky">
        <?php if ($p['price_min_gbp']) : ?>
          <div class="sub"><?php ae_e('price_range'); ?></div>
          <div class="price big"><?php echo ae_money($p['price_min_gbp']); ?><?php if ($p['price_max_gbp']) : ?> – <?php echo ae_money($p['price_max_gbp']); ?><?php endif; ?></div>
        <?php endif; ?>
        <h1 class="dtitle"><?php echo esc_html($p['name']); ?></h1>
        <div class="sub"><?php echo esc_html($loc); ?></div>
        <table class="facts">
          <?php if ($p['developer']) : ?><tr><td><?php ae_e('developer'); ?></td><td><?php echo esc_html($p['developer']); ?></td></tr><?php endif; ?>
          <?php if ($p['units']) : ?><tr><td><?php ae_e('units_label'); ?></td><td><?php echo (int) $p['units']; ?></td></tr><?php endif; ?>
          <?php if ($p['delivery_date']) : ?><tr><td><?php ae_e('delivery_label'); ?></td><td><?php echo esc_html(ae_month_label($p['delivery_date'])); ?></td></tr><?php endif; ?>
          <?php if ($p['payment_plan']) : ?><tr><td><?php ae_e('payment_plan'); ?></td><td><b><?php echo esc_html($p['payment_plan']); ?></b></td></tr><?php endif; ?>
          <tr><td><?php ae_e('location'); ?></td><td><?php echo esc_html($loc); ?></td></tr>
        </table>
        <?php if ($wa) : ?>
          <div class="row"><a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>?text=<?php echo esc_attr(rawurlencode(ae_t('wa_project', $p['name'], $p['url']))); ?>" target="_blank" rel="noopener"><?php ae_e('ask_project'); ?></a></div>
        <?php endif; ?>
        <p class="note"><?php ae_e('deed_warning'); ?></p>
      </div>
    </aside>
  </div>
  <?php if ($others) : ?>
    <h2 class="sec"><?php ae_e('new_projects'); ?></h2>
    <div class="grid"><?php foreach (array_slice($others, 0, 4) as $o) {
        echo ae_project_card($o);
    } ?></div>
  <?php endif; ?>
</main>
<?php
get_footer();
