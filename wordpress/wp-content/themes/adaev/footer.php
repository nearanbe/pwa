<?php
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$page = function (string $slug): string {
    $p = get_page_by_path($slug);
    return $p ? get_permalink($p) : '#';
};
$blog = ae_blog_url();
?>
<?php if (!empty($GLOBALS['ae_quicklinks'])) : ?>
<section class="qwrap" aria-label="<?php ae_e('listings'); ?>"><div class="qin">
  <div id="qtabs" role="tablist">
    <button type="button" data-q="sale" class="on"><?php ae_e('sale'); ?></button>
    <button type="button" data-q="rent"><?php ae_e('rent'); ?></button>
    <button type="button" data-q="daily"><?php ae_e('daily'); ?></button>
  </div>
  <?php
  $qt = [['konut', ''], ['konut', 'daire'], ['konut', 'villa'], ['arsa', ''], ['ticari', 'isyeri'], ['ticari', 'dukkan']];
  foreach (['sale', 'rent', 'daily'] as $i => $m) : ?>
    <div class="qgrid" data-qg="<?php echo $m; ?>" <?php echo $i ? 'hidden' : ''; ?>>
      <?php foreach (ec_cities() as $ck => $c) : ?>
        <div><h4><?php echo esc_html($c['name'][$lang]); ?></h4>
          <?php foreach ($qt as [$pt, $st]) :
              if ($m === 'daily' && $pt !== 'konut') {
                  continue;
              }
              $label = $c['name'][$lang] . ' ' . ae_t($m) . ' ' . ($st ? ec_subtype_name($pt, $st, $lang) : ec_type_name($pt, $lang)); ?>
            <a href="<?php echo esc_url(ec_url_section($m, $lang, $ck, ['ptype' => $pt, 'subtype' => $st])); ?>"><?php echo esc_html($label); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div></section>
<?php endif; ?>

<footer class="site-footer"><div class="fin"><div class="fcols">
  <div><h5><?php ae_e('corporate'); ?></h5>
    <a href="<?php echo esc_url($page('hakkimizda')); ?>"><?php ae_e('about'); ?></a>
    <a href="<?php echo esc_url($page('iletisim')); ?>"><?php ae_e('contact'); ?></a>
    <a href="<?php echo esc_url($page('gizlilik-politikasi')); ?>"><?php ae_e('privacy'); ?></a>
    <a href="<?php echo esc_url($page('kullanim-sartlari')); ?>"><?php ae_e('terms'); ?></a>
  </div>
  <div><h5><?php ae_e('listings'); ?></h5>
    <a href="<?php echo esc_url(ec_url_section('sale')); ?>"><?php ae_e('sale'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('rent')); ?>"><?php ae_e('rent'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('daily')); ?>"><?php ae_e('daily'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('project')); ?>"><?php ae_e('projects'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('index')); ?>"><?php ae_e('price_index'); ?></a>
  </div>
  <div><h5><?php ae_e('services'); ?></h5>
    <a href="<?php echo esc_url(ec_url_section('add')); ?>"><?php ae_e('add_listing'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tur', 'ofis', ec_url_section('register'))); ?>"><?php ae_e('corp_member'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('add')); ?>#paketler"><?php ae_e('packages'); ?></a>
    <a href="<?php echo esc_url($blog); ?>"><?php ae_e('blog'); ?></a>
  </div>
  <div><div class="flogo"><img src="<?php echo esc_url(get_theme_file_uri('assets/img/favicon.svg')); ?>" alt="" width="40" height="40"><?php echo esc_html(ae_brand()); ?></div><div class="fdesc"><?php ae_e('footer_note'); ?></div>
    <?php $soc = ae_social_icons((array) get_option('ec_social', []), 'socials fsocial'); if ($soc) : ?><div class="ffollow"><?php ae_e('follow_us'); ?></div><?php echo $soc; endif; ?></div>
</div>
<div class="fnote">© <?php echo esc_html(gmdate('Y') . ' ' . ae_brand()); ?>. <?php ae_e('rights'); ?> <?php if (get_option('ec_demo_agents')) : ?><br><?php ae_e('demo_note'); ?><?php endif; ?></div>
</div></footer>


<?php wp_footer(); ?>
</body>
</html>
