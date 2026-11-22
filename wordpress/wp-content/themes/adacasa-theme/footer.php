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
  <div class="fbrand"><div class="flogo"><?php echo ae_logo_mark('f', 64) . ae_logo_word(); // phpcs:ignore -- sabit SVG, metin kaçışlı ?></div><div class="fdesc"><?php ae_e('footer_note'); ?></div>
    <?php $fc = (array) get_option('ec_contact', []); $fwa = preg_replace('/\D/', '', (string) ($fc['whatsapp'] ?? '')); $fem = (string) ($fc['email'] ?? ''); ?>
    <?php if ($fwa || $fem) : ?><div class="fcontact">
      <?php if ($fwa) : ?><a href="https://wa.me/<?php echo esc_attr($fwa); ?>" rel="noopener" target="_blank">WhatsApp · +<?php echo esc_html($fwa); ?></a><?php endif; ?>
      <?php if ($fem) : ?><a href="mailto:<?php echo esc_attr($fem); ?>"><?php echo esc_html($fem); ?></a><?php endif; ?>
    </div><?php endif; ?>
    <?php $soc = ae_social_icons((array) get_option('ec_social', []), 'socials fsocial'); if ($soc) : ?><div class="ffollow"><?php ae_e('follow_us'); ?></div><?php echo $soc; endif; ?></div>
  <div><h5><?php ae_e('corporate'); ?></h5>
    <a href="<?php echo esc_url(ec_url_legal('about')); ?>"><?php ae_e('about'); ?></a>
    <a href="<?php echo esc_url(ec_url_legal('contact')); ?>"><?php ae_e('contact'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('faq')); ?>"><?php ae_e('faq_title'); ?></a>
    <a href="<?php echo esc_url(ec_url_legal('privacy')); ?>"><?php ae_e('privacy'); ?></a>
    <a href="<?php echo esc_url(ec_url_legal('cookies')); ?>"><?php ae_e('cookie_policy'); ?></a>
    <a href="<?php echo esc_url(ec_url_legal('terms')); ?>"><?php ae_e('terms'); ?></a>
    <a href="<?php echo esc_url(ec_url_legal('sales')); ?>"><?php ae_e('sales_terms'); ?></a>
    <a href="#" data-cookie-settings><?php ae_e('cookie_settings'); ?></a>
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
    <a href="#" data-conv="1000">⇄ <?php ae_e('conv_title'); ?></a>
  </div>
</div>
<div class="fnote">© <?php echo esc_html(gmdate('Y') . ' ' . ae_brand()); ?>. <?php ae_e('rights'); ?> · <?php ae_e('credit'); ?> <a href="https://adavira.com/is/adacasa" target="_blank" rel="noopener">AdaVira</a> <?php if (get_option('ec_demo_agents')) : ?><br><?php ae_e('demo_note'); ?><?php endif; ?></div>
</div></footer>


<div class="pwabar" id="pwabar" hidden role="dialog" aria-live="polite" aria-label="<?php ae_e('pwa_install'); ?>">
  <img src="<?php echo esc_url(get_theme_file_uri('assets/img/icon-192.png')); ?>" alt="">
  <b><?php ae_e('pwa_q', ae_brand()); ?></b>
  <p><?php ae_e('pwa_why'); ?></p>
  <div class="ios" hidden data-safari="<?php echo esc_attr(ae_t('pwa_ios_steps')); ?>" data-other="<?php echo esc_attr(ae_t('pwa_ios_open_safari')); ?>"></div>
  <div class="row"><button type="button" class="ghost sm" data-pwa="later"><?php ae_e('pwa_later'); ?></button><button type="button" class="btn sm" data-pwa="install"><?php ae_e('pwa_yes'); ?></button></div>
</div>
<?php wp_footer(); ?>
</body>
</html>
