<?php
/** Ofis vitrini: /tr/ofis/{kisa-ad}/ veya ofis-adi.alanadi.com */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$o = ec_agent_public($GLOBALS['ec_current_office']);
$kind = ec_member_kinds()[$o['kind']]['name'][$lang] ?? '';
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_office($o['slug'], $l);
}
ae_seo([
    'title' => $o['name'] . ' – ' . ($o['city'] ? ec_city_name($o['city'], $lang) . ' ' : '') . $kind,
    'description' => $o['bio'] ?: $o['name'],
    'canonical' => $alts[$lang],
    'alternates' => $alts,
    'image' => $o['logo']['large'] ?? '',
    'jsonld' => array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateAgent',
        'name' => $o['name'],
        'url' => $alts[$lang],
        'telephone' => $o['phone'],
        'image' => $o['logo']['large'] ?? null,
        'address' => $o['city'] ? ['@type' => 'PostalAddress', 'addressLocality' => ec_city_name($o['city'], $lang), 'addressCountry' => 'CY'] : null,
        'sameAs' => array_values($o['socials']) ?: null,
    ]),
]);
$pg = max(1, (int) ($_GET['sayfa'] ?? 1));
$res = ec_listing_search(['agent_id' => $o['id']], $lang, $pg, 24);
$projects = ec_projects($lang, 12, $o['id']);
$wa = preg_replace('/\D/', '', (string) $o['whatsapp']);
get_header();
?>
<div class="pagehead office"><div class="in">
  <div class="ohead">
    <div class="ologo"><?php echo $o['logo'] ? '<img src="' . esc_url($o['logo']['small']) . '" alt="' . esc_attr($o['name']) . '">' : '<span>' . esc_html(mb_substr($o['name'], 0, 1)) . '</span>'; ?></div>
    <div>
      <h1><?php echo esc_html($o['name']); ?></h1>
      <p><?php echo esc_html($kind . ($o['city'] ? ' · ' . ec_city_name($o['city'], $lang) : '')); ?><?php if ($o['verified']) : ?> · <span class="vbadge">✔ <?php ae_e('of_verified'); ?></span><?php endif; ?></p>
    </div>
  </div>
</div></div>
<main id="main" class="main">
  <div class="dgrid office-grid">
    <div class="ddesc">
      <?php if ($o['bio']) : ?><section class="panel"><h2><?php ae_e('pr_bio'); ?></h2><div class="desc"><?php echo wpautop(esc_html($o['bio'])); ?></div></section><?php endif; ?>
    </div>
    <aside class="dside">
      <div class="panel">
        <h2><?php ae_e('of_contact'); ?></h2>
        <div class="row">
          <?php if ($o['phone']) : ?><a class="btn" href="tel:<?php echo esc_attr($o['phone']); ?>">📞 <?php echo esc_html($o['phone']); ?></a><?php endif; ?>
          <?php if ($wa) : ?><a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
        </div>
        <?php echo ae_social_icons(array_merge($o['socials'], $o['website'] ? ['website' => $o['website']] : []), 'socials big'); ?>
      </div>
    </aside>
  </div>

  <?php if ($projects) : ?>
    <h2 class="sec"><?php ae_e('of_projects'); ?></h2>
    <div class="grid"><?php foreach ($projects as $p) {
        echo ae_project_card($p);
    } ?></div>
  <?php endif; ?>

  <h2 class="sec"><?php ae_e('of_listings'); ?> <span class="sub">(<?php echo (int) $res['total']; ?>)</span></h2>
  <div class="grid">
    <?php foreach ($res['items'] as $a) {
        echo ae_card($a);
    } ?>
    <?php if (!$res['items']) : ?><div class="empty"><?php ae_e('no_results'); ?></div><?php endif; ?>
  </div>
  <?php if ($res['pages'] > 1) : ?>
    <nav class="pager">
      <?php if ($pg > 1) : ?><a class="ghost" href="<?php echo esc_url(add_query_arg('sayfa', $pg - 1)); ?>">‹ <?php ae_e('prev'); ?></a><?php endif; ?>
      <span><?php echo (int) $pg; ?> / <?php echo (int) $res['pages']; ?></span>
      <?php if ($pg < $res['pages']) : ?><a class="ghost" href="<?php echo esc_url(add_query_arg('sayfa', $pg + 1)); ?>"><?php ae_e('next'); ?> ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</main>
<?php
get_footer();
