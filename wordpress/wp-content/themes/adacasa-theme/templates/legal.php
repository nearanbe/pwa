<?php
/** Yasal sayfalar: /tr/yasal/{belge}/ · /en/legal/{doc}/ · /ru/pravo/{doc}/ (metinler: Emlak Core legal-texts.php) */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$doc = function_exists('ec_legal_by_slug') ? ec_legal_by_slug((string) get_query_var('ec_slug'), $lang) : null;
if (!$doc) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    include get_theme_file_path('templates/404.php');
    return;
}
$r = ec_legal_render($doc, $lang);
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_legal($doc, $l);
}
ae_seo(['title' => $r['title'], 'description' => wp_trim_words(wp_strip_all_tags($r['html']), 26, '…'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
get_header();
?>
<div class="pagehead slim"><div class="in">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <span><?php echo esc_html($r['title']); ?></span></nav>
  <h1><?php echo esc_html($r['title']); ?></h1>
</div></div>
<main id="main" class="main legal">
  <div class="legal-grid">
    <article class="panel prose"><?php echo $r['html']; // phpcs:ignore -- sabit metin, yer tutucular kaçışlı ?></article>
    <nav class="legal-nav panel" aria-label="<?php ae_e('corporate'); ?>">
      <?php foreach (ec_legal_docs() as $k => $d) : ?><a href="<?php echo esc_url(ec_url_legal($k, $lang)); ?>" class="<?php echo $k === $doc ? 'on' : ''; ?>"><?php echo esc_html($d['title'][$lang]); ?></a><?php endforeach; ?>
      <a href="<?php echo esc_url(ec_url_section('faq', $lang)); ?>"><?php ae_e('faq_title'); ?></a>
      <button type="button" class="linkbtn" data-cookie-settings><?php ae_e('cookie_settings'); ?></button>
    </nav>
  </div>
</main>
<?php
get_footer();
