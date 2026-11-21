<?php
/** Firmalar dizini: /tr/firmalar/ — emlak ofisleri, inşaat firmaları ve danışmanlar */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$q = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
$kind = sanitize_key($_GET['tur'] ?? '');
$city = sanitize_key($_GET['sehir'] ?? '');
$pg = max(1, (int) ($_GET['sayfa'] ?? 1));
$res = ec_agents_directory($q, $kind, $city, $pg);
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('offices', $l);
}
ae_seo([
    'title' => ae_t('firms_title'),
    'description' => ae_t('firms_sub'),
    'canonical' => ec_url_section('offices'),
    'alternates' => $alts,
]);
if ($q || $pg > 1) {
    add_action('wp_head', fn() => print('<meta name="robots" content="noindex, follow">' . "\n"));
}
get_header();
?>
<main id="main" class="main firms">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <span><?php ae_e('firms'); ?></span></nav>
  <h1><?php ae_e('firms_title'); ?></h1>
  <p class="sub"><?php ae_e('firms_sub'); ?></p>
  <form class="panel firmsearch" method="get" role="search">
    <label class="vh" for="fq"><?php ae_e('firms_search'); ?></label>
    <input id="fq" name="q" value="<?php echo esc_attr($q); ?>" placeholder="<?php ae_e('firms_search'); ?>">
    <select name="tur" aria-label="<?php ae_e('firms_kind'); ?>"><option value=""><?php ae_e('firms_kind'); ?></option>
      <?php foreach (ec_member_kinds() as $k => $m) : if ($k === 'sahibi') { continue; } ?><option value="<?php echo esc_attr($k); ?>" <?php selected($kind, $k); ?>><?php echo esc_html($m['name'][$lang]); ?></option><?php endforeach; ?>
    </select>
    <select name="sehir" aria-label="<?php ae_e('city'); ?>"><option value=""><?php ae_e('city'); ?></option>
      <?php foreach (ec_cities() as $k => $c) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($city, $k); ?>><?php echo esc_html($c['name'][$lang]); ?></option><?php endforeach; ?>
    </select>
    <button class="btn" type="submit"><?php ae_e('search'); ?></button>
  </form>
  <p class="sub"><?php ae_e('firms_count', $res['total']); ?></p>
  <div class="firmgrid">
    <?php foreach ($res['items'] as $f) : ?>
      <article class="firm panel">
        <a class="cardlink" href="<?php echo esc_url($f['url'] ?: ec_url_section('sale', $lang, null, ['agent' => $f['id']])); ?>" aria-label="<?php echo esc_attr($f['name']); ?>"></a>
        <div class="flogo-box"><?php echo $f['logo'] ? '<img src="' . esc_url($f['logo']['small']) . '" alt="" loading="lazy">' : '<span class="av">' . esc_html(mb_substr($f['name'], 0, 1)) . '</span>'; ?></div>
        <div>
          <h2><?php echo esc_html($f['name']); ?><?php if ($f['verified']) : ?> <span class="vbadge sm" title="<?php ae_e('of_verified'); ?>">✔</span><?php endif; ?></h2>
          <div class="sub"><?php echo esc_html(ec_member_kinds()[$f['kind']]['name'][$lang] ?? ''); ?><?php if ($f['city']) : ?> · <?php echo esc_html(ec_city_name($f['city'], $lang)); ?><?php endif; ?> <?php echo ae_member_year($f['created_at']); ?></div>
          <div class="pills"><span><?php ae_e('agent_listings', $f['listing_count']); ?></span><?php if ($f['project_count']) : ?><span><?php ae_e('firm_projects', $f['project_count']); ?></span><?php endif; ?></div>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$res['items']) : ?><p class="empty"><?php ae_e('no_results'); ?></p><?php endif; ?>
  </div>
  <?php if ($res['pages'] > 1) : ?>
    <nav class="pager" aria-label="<?php ae_e('page'); ?>"><?php for ($i = 1; $i <= $res['pages']; $i++) : ?><a class="<?php echo $i === $pg ? 'on' : ''; ?>" href="<?php echo esc_url(add_query_arg(array_filter(['q' => $q, 'tur' => $kind, 'sehir' => $city, 'sayfa' => $i > 1 ? $i : null]), ec_url_section('offices'))); ?>"><?php echo $i; ?></a><?php endfor; ?></nav>
  <?php endif; ?>
  <p class="center"><a class="btn gold" href="<?php echo esc_url(add_query_arg('tur', 'ofis', ec_url_section('register'))); ?>"><?php ae_e('firms_join'); ?></a></p>
</main>
<?php
get_footer();
