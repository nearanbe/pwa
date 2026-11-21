<?php
/** Sıkça sorulan sorular: /tr/sss/ · /en/faq/ · /ru/voprosy/ (içerik: inc/faq.php) */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$idx = ['tr' => 0, 'en' => 1, 'ru' => 2][$lang];
$faq = ae_faq();
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('faq', $l);
}
$ld = [];
foreach ($faq as $g) {
    foreach ($g['items'] as [$q, $a]) {
        $ld[] = ['@type' => 'Question', 'name' => ae_faq_text($q[$idx]), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => ae_faq_text($a[$idx])]];
    }
}
ae_seo([
    'title' => ae_t('faq_title'),
    'description' => ae_t('faq_sub'),
    'canonical' => $alts[$lang],
    'alternates' => $alts,
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld],
]);
$wa = preg_replace('/\D/', '', (string) (get_option('ec_contact')['whatsapp'] ?? ''));
get_header();
?>
<div class="pagehead slim"><div class="in">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <span><?php ae_e('faq_title'); ?></span></nav>
  <h1><?php ae_e('faq_title'); ?></h1>
</div></div>
<main id="main" class="main faqpage">
  <p class="lead"><?php ae_e('faq_sub'); ?></p>
  <label class="faqsearch"><span class="vh"><?php ae_e('faq_search'); ?></span><input type="search" id="faqq" placeholder="<?php ae_e('faq_search'); ?>" autocomplete="off"></label>
  <nav class="faqnav" aria-label="<?php ae_e('sections'); ?>"><?php foreach ($faq as $gk => $g) : ?><a href="#<?php echo esc_attr($gk); ?>"><?php echo esc_html($g['title'][$idx]); ?></a><?php endforeach; ?></nav>
  <?php foreach ($faq as $gk => $g) : ?>
    <section class="faqgroup" id="<?php echo esc_attr($gk); ?>">
      <h2><?php echo esc_html($g['title'][$idx]); ?></h2>
      <?php foreach ($g['items'] as $i => [$q, $a]) : ?>
        <details class="faq" id="<?php echo esc_attr($gk . '-' . ($i + 1)); ?>">
          <summary><?php echo esc_html(ae_faq_text($q[$idx])); ?></summary>
          <div class="faqa"><?php echo wpautop(esc_html(ae_faq_text($a[$idx]))); ?></div>
        </details>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
  <p class="empty" id="faqnone" hidden><?php ae_e('faq_none'); ?></p>
  <div class="panel faqmore">
    <h2><?php ae_e('faq_more'); ?></h2>
    <p class="row"><?php if ($wa) : ?><a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?><a class="btn" href="<?php echo esc_url(ec_url_section('add')); ?>"><?php ae_e('add_listing'); ?></a></p>
  </div>
</main>
<script>
(function () {
  var q = document.getElementById('faqq'), none = document.getElementById('faqnone');
  var norm = function (s) { return s.toLocaleLowerCase(document.documentElement.lang || 'tr'); };
  q.addEventListener('input', function () {
    var v = norm(q.value.trim()), any = false;
    document.querySelectorAll('.faq').forEach(function (d) {
      var hit = !v || norm(d.textContent).indexOf(v) > -1;
      d.hidden = !hit; if (hit) any = true; d.open = !!v && hit;
    });
    document.querySelectorAll('.faqgroup').forEach(function (g) { g.hidden = !g.querySelector('.faq:not([hidden])'); });
    none.hidden = any;
  });
  if (location.hash) { var t = document.getElementById(location.hash.slice(1)); if (t && t.tagName === 'DETAILS') t.open = true; }
})();
</script>
<?php
get_footer();
