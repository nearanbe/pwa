<?php
/** İlan detayı: /tr/ilan/{slug}-{no}/ */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$a = $GLOBALS['ec_current_listing'];
ec_listing_add_view($a['id']);
$loc = trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name']);
$alts = [];
foreach ($a['translations'] as $l => $tr) {
    $alts[$l] = $tr['url'];
}
foreach (EC_LANGS as $l) {
    // Çevirisi olmayan dilde de sayfa açılır (TR metinle); link yapısı aynı kalsın.
    $alts[$l] = $alts[$l] ?? ec_url_listing($a['id'], ec_slugify($a['title']), $l);
}
ae_seo([
    'title' => $a['title'] . ' – ' . $loc,
    'description' => $a['description'] ?: $a['title'],
    'canonical' => $a['url'],
    'alternates' => $alts,
    'image' => $a['cover']['large'] ?? '',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $a['title'],
        'url' => $a['url'],
        'image' => array_map(fn($p) => $p['large'], $a['photos']),
        'datePosted' => $a['published_at'] ? gmdate('c', strtotime($a['published_at'] . ' UTC')) : null,
        'offers' => ['@type' => 'Offer', 'price' => $a['price'], 'priceCurrency' => $a['currency']],
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $loc, 'addressCountry' => 'CY'],
    ],
]);
$ag = $a['agent'];
$tel = $ag['phone'] ?? '';
$wa = $ag ? preg_replace('/\D/', '', (string) $ag['whatsapp']) : preg_replace('/\D/', '', (string) (get_option('ec_contact')['whatsapp'] ?? ''));
$waText = rawurlencode(ae_t('wa_msg', $a['id'], $a['url']));
$similar = array_values(array_filter(
    ec_listing_search(['mode' => $a['mode'], 'city' => $a['city'], 'ptype' => $a['ptype']], $lang, 1, 5)['items'],
    fn($x) => $x['id'] !== $a['id']
));
$similar = array_slice($similar, 0, 4);
$n = count($a['photos']);
get_header();
?>
<main id="main" class="main detail">
  <nav class="crumbs" aria-label="breadcrumb">
    <a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> ›
    <a href="<?php echo esc_url(ec_url_section($a['mode'])); ?>"><?php ae_e($a['mode']); ?></a> ›
    <a href="<?php echo esc_url(ec_url_section($a['mode'], $lang, $a['city'])); ?>"><?php echo esc_html($a['city_name']); ?></a> ›
    <span>#<?php echo (int) $a['id']; ?></span>
  </nav>

  <div class="dgrid">
    <div class="dgal">
      <div class="gallery" id="gallery" aria-roledescription="carousel">
        <?php if ($n) : ?>
          <div class="gtrack">
            <?php foreach ($a['photos'] as $i => $p) : ?>
              <figure class="gslide<?php echo $i === 0 ? ' on' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(ae_t('gallery', $i + 1, $n)); ?>">
                <img src="<?php echo esc_url($p['large']); ?>" alt="<?php echo esc_attr($a['title'] . ' – ' . ($i + 1)); ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" decoding="async" <?php if ($p['width']) : ?>width="<?php echo (int) $p['width']; ?>" height="<?php echo (int) $p['height']; ?>"<?php endif; ?>>
              </figure>
            <?php endforeach; ?>
          </div>
          <?php if ($n > 1) : ?>
            <button class="sarrow prev" type="button" aria-label="<?php ae_e('prev'); ?>">‹</button>
            <button class="sarrow next" type="button" aria-label="<?php ae_e('next'); ?>">›</button>
            <span class="gcount"><span class="gi">1</span> / <?php echo $n; ?></span>
          <?php endif; ?>
          <?php if ($a['status'] === 'sold') : ?><span class="sbadge big"><?php ae_e('sold'); ?></span><?php endif; ?>
        <?php else : ?>
          <div class="img noimg"><?php echo AE_ICON; ?><span><?php ae_e('no_photo'); ?></span></div>
        <?php endif; ?>
      </div>
      <?php if ($n > 1) : ?>
        <div class="thumbs" id="thumbs">
          <?php foreach ($a['photos'] as $i => $p) : ?>
            <button type="button" class="<?php echo $i === 0 ? 'on' : ''; ?>" data-go="<?php echo $i; ?>" aria-label="<?php echo esc_attr(ae_t('gallery', $i + 1, $n)); ?>"><img src="<?php echo esc_url($p['small']); ?>" alt="" loading="lazy"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="ddesc">
      <section class="panel">
        <h2><?php ae_e('description'); ?></h2>
        <div class="desc"><?php echo wpautop(esc_html($a['description'])); ?></div>
      </section>
      <?php echo ae_ad('detail'); ?>
    </div>

    <aside class="dside">
      <div class="panel sticky">
        <div class="price big"><?php echo ae_money($a['price_gbp']) . esc_html(ae_price_suffix($a['mode'])); ?></div>
        <?php if ($a['currency'] !== ae_currency()) : ?>
          <div class="sub"><?php ae_e('original_price'); ?>: <?php echo esc_html(AE_SYM[$a['currency']] . number_format($a['price'], 0, ',', '.')); ?></div>
        <?php endif; ?>
        <h1 class="dtitle"><?php echo esc_html($a['title']); ?></h1>
        <div class="sub"><?php echo esc_html($loc); ?> · <?php ae_e('ref'); ?> #<?php echo (int) $a['id']; ?></div>
        <table class="facts">
          <tr><td><?php ae_e('listing_type'); ?></td><td><?php ae_e($a['mode']); ?></td></tr>
          <tr><td><?php ae_e('property_type'); ?></td><td><?php echo esc_html($a['subtype_name']); ?></td></tr>
          <?php if ($a['rooms']) : ?><tr><td><?php ae_e('bedrooms'); ?></td><td><?php echo esc_html($a['rooms']); ?></td></tr><?php endif; ?>
          <?php if ($a['baths']) : ?><tr><td><?php ae_e('baths'); ?></td><td><?php echo (int) $a['baths']; ?></td></tr><?php endif; ?>
          <?php if ($a['area_m2']) : ?><tr><td><?php ae_e('area'); ?></td><td><?php echo (int) $a['area_m2']; ?> m²</td></tr><?php endif; ?>
          <?php if ($a['deed_name']) : ?><tr><td><?php ae_e('deed'); ?></td><td><b><?php echo esc_html($a['deed_name']); ?></b></td></tr><?php endif; ?>
          <?php if ($a['furnished'] !== null && $a['ptype'] === 'konut') : ?><tr><td><?php ae_e('furnished'); ?></td><td><?php ae_e($a['furnished'] ? 'furnished_yes' : 'furnished_no'); ?></td></tr><?php endif; ?>
          <tr><td><?php ae_e('location'); ?></td><td><?php echo esc_html($loc); ?></td></tr>
        </table>
        <?php if ($ag) : ?>
          <div class="agent">
            <div class="av"><?php echo esc_html(mb_substr($ag['name'], 0, 1)); ?></div>
            <div><b><?php echo esc_html($ag['name']); ?></b><div class="sub"><?php echo esc_html((string) $ag['office']); ?></div></div>
          </div>
        <?php endif; ?>
        <div class="row">
          <button class="btn" type="button" id="telbtn" data-tel="<?php echo esc_attr($tel); ?>"><?php ae_e('show_phone'); ?></button>
          <?php if ($wa) : ?><a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>?text=<?php echo esc_attr($waText); ?>" target="_blank" rel="noopener"><?php ae_e('whatsapp'); ?></a><?php endif; ?>
        </div>
        <div class="row">
          <button class="ghost heart-btn<?php echo in_array($a['id'], ae_favs(), true) ? ' on' : ''; ?>" type="button" data-fav="<?php echo (int) $a['id']; ?>">♥ <?php ae_e('favourites'); ?></button>
          <button class="ghost" type="button" id="sharebtn" data-url="<?php echo esc_url($a['url']); ?>" data-title="<?php echo esc_attr($a['title']); ?>"><?php ae_e('share'); ?></button>
        </div>
        <?php $cmp = ec_index_compare($a); if ($cmp) :
            $pct = number_format(abs($cmp['diff']), 0) . '%';
            $key = abs($cmp['diff']) < 3 ? 'idx_compare_equal' : ($cmp['diff'] < 0 ? 'idx_compare_below' : 'idx_compare_above'); ?>
          <a class="cmp <?php echo $cmp['diff'] < -3 ? 'below' : ($cmp['diff'] > 3 ? 'above' : ''); ?>" href="<?php echo esc_url(add_query_arg(array_filter(['sehir' => $a['city'], 'tur' => $a['mode'] === 'rent' ? 'rent' : '']), ec_url_section('index'))); ?>">
            <b><?php echo esc_html(ae_t($key, $a['city_name'], $pct)); ?></b>
            <span class="sub"><?php echo ae_money($cmp['m2']); ?> <?php ae_e('per_m2'); ?> · <?php ae_e('price_index'); ?>: <?php echo ae_money($cmp['median']); ?> <?php ae_e('per_m2'); ?></span>
          </a>
        <?php endif; ?>
        <?php if ($a['mode'] === 'sale') : ?><p class="note"><?php ae_e('deed_warning'); ?></p><?php endif; ?>
      </div>
    </aside>
  </div>

  <?php if ($similar) : ?>
    <h2 class="sec"><?php ae_e('similar'); ?></h2>
    <div class="grid"><?php foreach ($similar as $s) {
        echo ae_card($s);
    } ?></div>
  <?php endif; ?>
</main>
<?php
get_footer();
