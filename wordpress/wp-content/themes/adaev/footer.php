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
    <a href="#" data-open="regov" data-kind="kurumsal"><?php ae_e('corp_member'); ?></a>
    <a href="<?php echo esc_url(ec_url_section('add')); ?>#paketler"><?php ae_e('packages'); ?></a>
    <a href="<?php echo esc_url($blog); ?>"><?php ae_e('blog'); ?></a>
  </div>
  <div><div class="flogo"><img src="<?php echo esc_url(get_theme_file_uri('assets/img/favicon.svg')); ?>" alt="" width="40" height="40"><?php echo esc_html(ae_brand()); ?></div><div class="fdesc"><?php ae_e('footer_note'); ?></div></div>
</div>
<div class="fnote">© <?php echo esc_html(gmdate('Y') . ' ' . ae_brand()); ?>. <?php ae_e('rights'); ?> <?php if (get_option('ec_demo_agents')) : ?><br><?php ae_e('demo_note'); ?><?php endif; ?></div>
</div></footer>

<div class="ov" id="regov" role="dialog" aria-modal="true" aria-labelledby="reg-h" hidden><form class="sheet" id="regform" novalidate>
  <h2 id="reg-h"><?php ae_e('reg_title'); ?></h2>
  <div class="tabs" id="rtabs" role="tablist">
    <button type="button" data-k="bireysel" class="on"><?php ae_e('reg_individual'); ?></button>
    <button type="button" data-k="kurumsal"><?php ae_e('reg_corporate'); ?></button>
  </div>
  <input type="hidden" name="kind" value="bireysel">
  <div class="fg two">
    <label class="vh" for="r-type"><?php ae_e('reg_agency'); ?></label>
    <select id="r-type" name="company_type" class="corp-only wide" hidden>
      <option><?php ae_e('reg_agency'); ?></option><option><?php ae_e('reg_builder'); ?></option><option><?php ae_e('reg_consultant'); ?></option>
    </select>
    <label class="vh" for="r-name"><?php ae_e('reg_name'); ?></label><input id="r-name" name="name" placeholder="<?php ae_e('reg_name'); ?>" autocomplete="name" required>
    <label class="vh" for="r-mail"><?php ae_e('reg_email'); ?></label><input id="r-mail" name="email" type="email" placeholder="<?php ae_e('reg_email'); ?>" autocomplete="email" required>
    <label class="vh" for="r-tel"><?php ae_e('reg_phone'); ?></label><input id="r-tel" name="phone" inputmode="tel" placeholder="<?php ae_e('reg_phone'); ?>" autocomplete="tel">
    <label class="vh" for="r-city"><?php ae_e('city'); ?></label>
    <select id="r-city" name="city"><option value=""><?php ae_e('city'); ?></option>
      <?php foreach (ec_cities() as $k => $c) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($c['name'][$lang]); ?></option><?php endforeach; ?>
    </select>
    <label class="vh" for="r-auth"><?php ae_e('reg_contact'); ?></label><input id="r-auth" name="contact_person" placeholder="<?php ae_e('reg_contact'); ?>" class="corp-only wide" hidden>
    <input name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
  </div>
  <label class="chk"><input type="checkbox" name="ok" value="1"> <?php ae_e('reg_terms'); ?></label>
  <p class="formmsg" role="status"></p>
  <div class="row"><button type="button" class="ghost" data-close><?php ae_e('cancel'); ?></button><button class="btn" type="submit"><?php ae_e('reg_submit'); ?></button></div>
</form></div>
<?php wp_footer(); ?>
</body>
</html>
