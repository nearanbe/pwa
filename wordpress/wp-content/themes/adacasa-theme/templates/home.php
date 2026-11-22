<?php
/** Ana sayfa: fotoğraflı giriş + arama kutusu + öne çıkanlar + son ilanlar + danışmanlar + hızlı linkler */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_home($l);
}
ae_seo([
    'title' => ae_t('seo_home_title'),
    'description' => ae_t('seo_home_desc'),
    'canonical' => ec_url_home(),
    'alternates' => $alts,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@graph' => [
            ae_org_ld(),
            [
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'name' => ae_brand(),
                'url' => ec_url_home(),
                'inLanguage' => $lang,
                'publisher' => ['@id' => home_url('/#org')],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => ec_url_section('sale') . '?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ],
]);
$GLOBALS['ae_quicklinks'] = true;

$featured = ec_listing_featured($lang, null, 8);
$latest = ec_listing_search(['mode' => 'sale'], $lang, 1, 12)['items'];
$agents = ec_agents_active(12);
$projects = ec_projects($lang, 4);
$idx = array_slice(ec_index_table('sale'), 0, 7);
$posts = get_posts(['numberposts' => 3]);
get_header();
?>
<?php
$hp = ae_hero_photo();
global $wpdb;
$nList = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . ec_table('listings') . " WHERE status = 'published'");
$nFirm = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . ec_table('agents') . " WHERE status = 'active'");
?>
<section class="hero hs">
  <picture class="hbg">
    <?php if ($hp['mobile']) : ?><source media="(max-width: 720px)" srcset="<?php echo esc_url($hp['mobile']); ?>"><?php endif; ?>
    <img src="<?php echo esc_url($hp['src']); ?>"<?php echo $hp['srcset'] ? ' srcset="' . esc_attr($hp['srcset']) . '" sizes="100vw"' : ''; ?> alt="" fetchpriority="high" decoding="async">
  </picture>
  <div class="hero-in">
    <h1><?php ae_e('hero_title'); ?></h1>
    <p class="hsub"><?php ae_e('hero_sub'); ?></p>
    <?php ae_search_box(['mode' => 'sale']); ?>
    <?php if ($nList >= 20) : ?><p class="hstats"><?php ae_e('hero_stats', number_format_i18n($nList), number_format_i18n($nFirm)); ?></p><?php endif; ?>
  </div>
  <?php if (!empty($hp['credit'])) : ?><p class="hcredit"><?php echo $hp['credit']; // phpcs:ignore -- kaçışlı ?></p><?php endif; ?>
</section>

<main id="main" class="main home">
  <?php $cc = ae_city_counts(); ?>
  <h2 class="sec"><?php ae_e('cities_title'); ?></h2>
  <div class="citygrid">
    <?php foreach (ec_cities() as $ck => $c) : $img = ae_city_photo($ck); $n = $cc[$ck] ?? []; ?>
      <a class="citycard<?php echo $img ? '' : ' noimg c-' . esc_attr($ck); ?>" href="<?php echo esc_url(ec_url_section('sale', $lang, $ck)); ?>">
        <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
        <span class="cc-in">
          <b><?php echo esc_html($c['name'][$lang]); ?></b>
          <span class="cc-n">
            <span data-href="<?php echo esc_url(ec_url_section('sale', $lang, $ck)); ?>"><?php ae_e('city_sale_n', ae_plus((int) ($n['sale'] ?? 0))); ?></span>
            <span data-href="<?php echo esc_url(ec_url_section('rent', $lang, $ck)); ?>"><?php ae_e('city_rent_n', ae_plus((int) ($n['rent'] ?? 0))); ?></span>
          </span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php
  // Özgür lisanslı fotoğraflar için zorunlu atıf: medya açıklamasından (Foto: Ad · Lisans · Wikimedia Commons (adres))
  $credits = [];
  foreach ((array) get_option('ec_city_photos', []) as $ck => $v) {
      if (is_int($v) || ctype_digit((string) $v)) {
          $cap = (string) wp_get_attachment_caption((int) $v);
          if (preg_match('~^Foto:\s*(.+?)\s*·\s*(.+?)\s*·.*?\((https?://[^)\s]+)\)~u', $cap, $m)) {
              $credits[] = '<a href="' . esc_url($m[3]) . '" rel="noopener" target="_blank">' . esc_html(ec_city_name($ck, $lang)) . '</a>: ' . esc_html($m[1] . ', ' . $m[2]);
          }
      }
  }
  if ($credits) : ?><details class="credits"><summary>ⓘ <?php ae_e('photo_sources'); ?></summary><p><?php echo implode(' · ', $credits); // phpcs:ignore -- kaçışlı ?> (Wikimedia Commons)</p></details><?php endif; ?>

  <?php $vitrin = ec_listing_showcase('home', $lang, [], 12); if ($vitrin) : ?>
    <div class="sechead"><h2 class="sec">✦ <?php ae_e('showcase'); ?></h2></div>
    <div class="grid vitrin"><?php foreach ($vitrin as $a) {
        echo ae_card($a);
    } ?></div>
  <?php endif; ?>

  <?php if ($featured) : ?>
    <h2 class="sec"><?php ae_e('featured'); ?></h2>
    <div class="feat"><?php foreach ($featured as $a) {
        echo ae_card($a);
    } ?></div>
  <?php endif; ?>

  <?php echo ae_ad('top'); ?>

  <div class="sechead"><h2 class="sec"><?php ae_e('latest'); ?></h2><a href="<?php echo esc_url(ec_url_section('sale')); ?>"><?php ae_e('see_all'); ?> →</a></div>
  <div class="grid">
    <?php foreach ($latest as $i => $a) {
        echo ae_card($a);
        if (($i + 1) % 6 === 0) {
            echo ae_ad_card();
        }
    } ?>
    <?php if (!$latest) : ?><div class="empty"><?php ae_e('no_results'); ?></div><?php endif; ?>
  </div>

  <?php if ($projects) : ?>
    <div class="sechead"><h2 class="sec"><?php ae_e('new_projects'); ?></h2><a href="<?php echo esc_url(ec_url_section('project')); ?>"><?php ae_e('see_all'); ?> →</a></div>
    <div class="grid"><?php foreach ($projects as $p) {
        echo ae_project_card($p);
    } ?></div>
  <?php endif; ?>

  <?php if ($idx) : ?>
    <div class="sechead"><h2 class="sec"><?php ae_e('price_index'); ?></h2><a href="<?php echo esc_url(ec_url_section('index')); ?>"><?php ae_e('idx_see'); ?> →</a></div>
    <div class="idxstrip">
      <?php foreach ($idx as $r) : ?>
        <a class="idxcard<?php echo $r['city'] === EC_INDEX_ALL ? ' total' : ''; ?>" href="<?php echo esc_url(add_query_arg($r['city'] === EC_INDEX_ALL ? [] : ['sehir' => $r['city']], ec_url_section('index'))); ?>">
          <span class="sub"><?php echo esc_html($r['city'] === EC_INDEX_ALL ? ae_t('idx_all') : ec_city_name($r['city'], $lang)); ?></span>
          <b class="price"><?php echo ae_money($r['m2']); ?></b><span class="sub"><?php ae_e('per_m2'); ?></span>
          <span class="sub"><?php ae_e('idx_chg12'); ?>: <?php echo ae_pct($r['chg12']); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php echo ae_ad('mid'); ?>

  <?php if ($agents) : ?>
    <div class="sechead"><h2 class="sec"><?php ae_e('agents'); ?></h2><a href="<?php echo esc_url(ec_url_section('offices')); ?>"><?php ae_e('firms_all'); ?> →</a></div>
    <div class="adv">
      <?php foreach ($agents as $ag) : ?>
        <a class="ag" href="<?php echo esc_url($ag['url'] ?: ec_url_section('sale', $lang, null, ['agent' => $ag['id']])); ?>">
          <?php echo $ag['logo'] ? '<img class="aglogo" src="' . esc_url($ag['logo']['small']) . '" alt="" loading="lazy">' : '<div class="av">' . esc_html(mb_substr($ag['name'], 0, 1)) . '</div>'; ?>
          <b><?php echo esc_html($ag['name']); ?></b>
          <div class="sub"><?php echo esc_html(implode(' · ', array_filter([ec_member_kinds()[$ag['kind']]['name'][$lang] ?? '', $ag['city'] ? ec_city_name($ag['city'], $lang) : '']))); ?></div>
          <div class="sub"><?php ae_e('agent_listings', $ag['listing_count']); ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($posts) : ?>
    <div class="sechead"><h2 class="sec"><?php ae_e('from_blog'); ?></h2><a href="<?php echo esc_url(ae_blog_url()); ?>"><?php ae_e('all_posts'); ?> →</a></div>
    <div class="grid posts"><?php foreach ($posts as $r) : ?>
      <article class="card post"><a class="cardlink" href="<?php echo esc_url(get_permalink($r)); ?>" aria-label="<?php echo esc_attr(get_the_title($r)); ?>"></a>
        <div class="img"><?php echo get_the_post_thumbnail($r, 'medium_large', ['loading' => 'lazy']) ?: AE_ICON; ?></div>
        <div class="body"><div class="sub"><?php echo esc_html(ae_date((int) get_post_timestamp($r))); ?></div><h3 class="ttl"><?php echo esc_html(get_the_title($r)); ?></h3><p class="sub"><?php echo esc_html(get_the_excerpt($r)); ?></p></div>
      </article>
    <?php endforeach; ?></div>
  <?php endif; ?>
</main>
<?php
get_footer();
