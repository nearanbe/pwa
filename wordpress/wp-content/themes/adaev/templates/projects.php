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
ae_seo(['title' => ae_t('new_projects') . ' – ' . ae_t('tagline'), 'description' => ae_t('hero_sub'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
$projects = ec_projects($lang, 60);
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php ae_e('new_projects'); ?></h1></div></div>
<main id="main" class="main">
  <?php ae_search_box(['mode' => 'project']); ?>
  <div class="grid" style="margin-top:24px">
    <?php foreach ($projects as $p) {
        echo ae_project_card($p);
    } ?>
    <?php if (!$projects) : ?><div class="empty"><?php ae_e('no_results'); ?></div><?php endif; ?>
  </div>
</main>
<?php
get_footer();
