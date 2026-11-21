<?php
/** Ana sayfa: slider + arama kutusu + öne çıkanlar + son ilanlar + danışmanlar + hızlı linkler */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_home($l);
}
ae_seo([
    'title' => ae_t('hero_title') . ' – ' . ae_t('tagline'),
    'description' => ae_t('hero_sub'),
    'canonical' => ec_url_home(),
    'alternates' => $alts,
]);
$GLOBALS['ae_quicklinks'] = true;

$featured = ec_listing_featured($lang, null, 8);
$slides = array_values(array_filter($featured, fn($a) => $a['cover']));
$slides = array_slice($slides, 0, 5);
$latest = ec_listing_search(['mode' => 'sale'], $lang, 1, 12)['items'];
$agents = ec_agents_active(12);
$projects = ec_projects($lang, 4);
$idx = array_slice(ec_index_table('sale'), 0, 7);
$posts = get_posts(['numberposts' => 3]);
get_header();
?>
<section class="hero" aria-roledescription="carousel" aria-label="<?php ae_e('featured'); ?>">
  <div class="slides" id="slides">
    <div class="slide on brand" data-i="0">
      <div class="scap">
        <h1><?php ae_e('hero_title'); ?></h1>
        <p><?php ae_e('hero_sub'); ?></p>
        <a class="btn gold" href="<?php echo esc_url(ec_url_section('sale')); ?>"><?php ae_e('hero_cta'); ?></a>
      </div>
    </div>
    <?php foreach ($slides as $i => $a) : ?>
      <div class="slide" data-i="<?php echo $i + 1; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(ae_t('slide', $i + 2)); ?>">
        <img class="sbg" src="<?php echo esc_url($a['cover']['large']); ?>" alt="" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" <?php echo $i === 0 ? 'fetchpriority="high"' : ''; ?> decoding="async">
        <div class="scap">
          <span class="stag"><?php echo esc_html($a['subtype_name'] . ' · ' . trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name'])); ?></span>
          <h2><?php echo esc_html($a['title']); ?></h2>
          <p class="sprice"><?php echo ae_price($a['price_gbp'], $a['mode']); ?></p>
          <a class="btn gold" href="<?php echo esc_url($a['url']); ?>"><?php ae_e('view_listing'); ?></a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($slides) : ?>
    <button class="sarrow prev" type="button" aria-label="<?php ae_e('prev'); ?>">‹</button>
    <button class="sarrow next" type="button" aria-label="<?php ae_e('next'); ?>">›</button>
    <div class="sdots" role="tablist">
      <?php for ($i = 0; $i <= count($slides); $i++) : ?>
        <button type="button" class="<?php echo $i === 0 ? 'on' : ''; ?>" data-go="<?php echo $i; ?>" aria-label="<?php echo esc_attr(ae_t('slide', $i + 1)); ?>"></button>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>

<main id="main" class="main home">
  <?php ae_search_box(['mode' => 'sale']); ?>

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
          <div class="sub"><?php echo esc_html((string) $ag['office']); ?></div>
          <div class="sub"><?php ae_e('agent_listings', $ag['listing_count']); ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($posts) : ?>
    <div class="sechead"><h2 class="sec"><?php ae_e('from_blog'); ?></h2><a href="<?php echo esc_url(ae_blog_url()); ?>"><?php ae_e('all_posts'); ?> →</a></div>
    <div class="grid posts"><?php foreach ($posts as $r) : ?>
      <article class="card post"><a class="cardlink" href="<?php echo esc_url(get_permalink($r)); ?>" aria-label="<?php echo esc_attr(get_the_title($r)); ?>"></a>
        <div class="img"><?php echo get_the_post_thumbnail($r, 'medium_large', ['loading' => 'lazy', 'alt' => '']) ?: AE_ICON; ?></div>
        <div class="body"><div class="sub"><?php echo esc_html(get_the_date('', $r)); ?></div><h3 class="ttl"><?php echo esc_html(get_the_title($r)); ?></h3><p class="sub"><?php echo esc_html(get_the_excerpt($r)); ?></p></div>
      </article>
    <?php endforeach; ?></div>
  <?php endif; ?>
</main>
<?php
get_footer();
