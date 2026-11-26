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
    'jsonld' => ['@context' => 'https://schema.org', '@graph' => [
        ae_breadcrumb_ld(array_values(array_filter([
            [ae_t('home'), ec_url_home()],
            [ae_t($a['mode']), ec_url_section($a['mode'])],
            isset(ec_cities()[$a['city']]) ? [$a['city_name'], ec_url_section($a['mode'], $lang, $a['city'])] : null,
            [$a['title'], $a['url']],
        ]))),
        [
        '@type' => 'RealEstateListing',
        'name' => $a['title'],
        'url' => $a['url'],
        'image' => array_map(fn($p) => $p['large'], $a['photos']),
        'datePosted' => $a['published_at'] ? gmdate('c', strtotime($a['published_at'] . ' UTC')) : null,
        'offers' => ['@type' => 'Offer', 'price' => $a['price'], 'priceCurrency' => $a['currency']],
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $loc, 'addressCountry' => 'CY'],
        ],
    ]],
]);
$ag = $a['agent'];
$tel = $ag['phone'] ?? '';
$wa = $ag ? preg_replace('/\D/', '', (string) $ag['whatsapp']) : preg_replace('/\D/', '', (string) (get_option('ec_contact')['whatsapp'] ?? ''));
$waText = rawurlencode(ae_t('wa_msg', $a['id'], $a['url'], $a['title']));
$similar = array_values(array_filter(
    ec_listing_search(['mode' => $a['mode'], 'city' => $a['city'], 'ptype' => $a['ptype']], $lang, 1, 5)['items'],
    fn($x) => $x['id'] !== $a['id']
));
$similar = array_slice($similar, 0, 4);
$n = count($a['photos']);
$agentRow = $a['agent_id'] ? ec_agent($a['agent_id']) : null;
$agentCount = $agentRow ? ec_listing_search(['agent_id' => $a['agent_id']], $lang, 1, 1)['total'] : 0;
$coords = $a['lat'] && $a['lng'] ? [$a['lat'], $a['lng'], false] : (isset(ec_city_coords()[$a['city']]) ? [ec_city_coords()[$a['city']][0], ec_city_coords()[$a['city']][1], true] : null);
$series = in_array($a['mode'], ['sale', 'rent'], true) ? ec_index_series($a['city'], $a['mode'], $a['ptype'] === 'arsa' ? 'arsa' : 'konut', 24) : [];
if (count($series) < 2) {
    $series = in_array($a['mode'], ['sale', 'rent'], true) ? ec_index_series(EC_INDEX_ALL, $a['mode'], 'konut', 24) : [];
    $seriesCity = ae_t('idx_all');
} else {
    $seriesCity = $a['city_name'];
}
$feat = $a['features'];
$groups = [];
foreach (ec_feature_groups() as $gk => $g) {
    $names = [];
    foreach ($g['keys'] as $fk) {
        if (in_array($fk, $feat, true)) {
            $names[] = ec_features()[$fk][$lang];
        }
    }
    if ($names) {
        $groups[$gk] = ['name' => $g['name'][$lang], 'items' => $names];
    }
}
$age = $a['year_built'] ? max(0, (int) gmdate('Y') - $a['year_built']) : null;
$yn = fn($v) => $v === null ? null : ae_t($v ? 'yes' : 'no');
$details = [
    'dg_general' => [
        [ae_t('ref'), '#' . $a['id']],
        [ae_t('listing_type'), ae_t($a['mode'])],
        [ae_t('property_type'), $a['subtype_name']],
        [ae_t('bedrooms'), $a['rooms']],
        [ae_t('baths'), $a['baths']],
        [ae_t('area'), $a['area_m2'] ? $a['area_m2'] . ' m²' : null],
        [ae_t('furnished'), $a['furnished'] !== null && $a['ptype'] === 'konut' ? ae_t($a['furnished'] ? 'furnished_yes' : 'furnished_no') : null],
    ],
    'dg_building' => [
        [ae_t('f_floor'), $a['floor']],
        [ae_t('dt_total_floors'), $a['total_floors']],
        [ae_t('year_built'), $a['year_built'] ? $a['year_built'] . ($age !== null ? ' (' . ae_t('dt_age', $age) . ')' : '') : null],
        [ae_t('dt_gated'), in_array('site_icinde', $feat, true) ? ae_t('yes') : null],
        [ae_t('dt_pool'), in_array('ozel_havuz', $feat, true) ? ec_features()['ozel_havuz'][$lang] : (in_array('ortak_havuz', $feat, true) ? ec_features()['ortak_havuz'][$lang] : null)],
        [ae_t('dt_lift'), in_array('asansor', $feat, true) ? ae_t('yes') : null],
        [ae_t('dt_parking'), in_array('kapali_otopark', $feat, true) ? ec_features()['kapali_otopark'][$lang] : (in_array('otopark', $feat, true) ? ae_t('yes') : null)],
    ],
    'dg_sale' => [
        [ae_t('deed'), $a['mode'] === 'sale' ? ($a['deed_name'] ?: ae_t('deed_unknown')) : null],
        [ae_t('f_vat'), $a['mode'] === 'sale' && $a['vat_paid'] !== null ? ae_t($a['vat_paid'] ? 'paid' : 'not_paid') : null],
        [ae_t('f_transformer'), $a['mode'] === 'sale' && $a['transformer_paid'] !== null ? ae_t($a['transformer_paid'] ? 'paid' : 'not_paid') : null],
        [ae_t('payment_plan'), $a['payment_plan']],
    ],
    'dg_location' => [
        [ae_t('city'), $a['city_name']],
        [ae_t('district'), $a['district']],
        [ae_t('f_sea'), $a['sea_distance_m'] ? ae_t('sea_m', number_format_i18n($a['sea_distance_m'])) : null],
    ],
];
$panos = $a['panos'];
$video = function_exists('ec_video_info') ? ec_video_info($a['video_url']) : null;
$tourEmbed = function_exists('ec_tour_embed') ? ec_tour_embed($a['tour_url']) : null;
$sections = ['ozet' => ae_t('sec_overview')];
if ($panos || $tourEmbed) {
    $sections['tur'] = ae_t('pano_tour');
}
$sections['detaylar'] = ae_t('sec_details');
if ($groups) {
    $sections['ozellikler'] = ae_t('f_features');
}
if ($video) {
    $sections['video'] = ae_t('video');
}
if ($coords) {
    $sections['konum'] = ae_t('location');
}
if (count($series) >= 2) {
    $sections['trend'] = ae_t('sec_trend');
}
$sections['iletisim'] = ae_t('sec_contact');
$waLink = $wa ? 'https://wa.me/' . $wa . '?text=' . $waText : '';
$priceHist = function_exists('ec_price_history') ? ec_price_history($a['id']) : [];
$cfDays = $a['confirmed_at'] ? max(0, (int) floor((time() - strtotime($a['confirmed_at'] . ' UTC')) / DAY_IN_SECONDS)) : null;
get_header();
$priceHtml = ae_price($a['price_gbp'], $a['mode']);
echo ae_sticky_head(['logo' => $a['agent']['logo']['small'] ?? '', 'title' => $a['title'], 'price' => ae_money($a['price_gbp']) . esc_html(ae_price_suffix($a['mode'])), 'tel' => $tel, 'wa' => $waLink, 'sections' => $sections]);
?>
<main id="main" class="main detail" data-listing="<?php echo (int) $a['id']; ?>">
  <?php if (!empty($GLOBALS['ec_preview'])) : ?><p class="note warn">👁 <?php ae_e('st_' . $a['status']); ?><?php if ($a['reject_reason']) : ?> — <?php echo esc_html($a['reject_reason']); ?><?php endif; ?></p><?php endif; ?>
  <nav class="crumbs" aria-label="breadcrumb">
    <a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> ›
    <a href="<?php echo esc_url(ec_url_section($a['mode'])); ?>"><?php ae_e($a['mode']); ?></a> ›
    <a href="<?php echo esc_url(ec_url_section($a['mode'], $lang, $a['city'])); ?>"><?php echo esc_html($a['city_name']); ?></a> ›
    <span>#<?php echo (int) $a['id']; ?></span>
  </nav>

  <header class="dhead" id="dhead">
    <div>
      <h1 class="dtitle"><?php echo esc_html($a['title']); ?></h1>
      <div class="sub"><?php echo ae_icon('pin', 16); ?> <?php echo esc_html($loc); ?> · <?php ae_e('ref'); ?> #<?php echo (int) $a['id']; ?><?php if ($coords) : ?> · <a href="#konum" class="link"><?php ae_e('see_on_map'); ?></a><?php endif; ?></div>
      <div class="dtrust">
        <?php if (!empty($a['verified'])) : ?><span class="vfbadge" title="<?php ae_e('vf_desc'); ?>">✔ <?php ae_e('vf_badge'); ?></span><?php endif; ?>
        <?php if ($cfDays !== null && $a['status'] === 'published' && $cfDays <= 45) : ?><span class="cfline">🟢 <?php ae_e('cf_line', $cfDays === 0 ? ae_t('cf_today') : ae_t('cf_days', $cfDays)); ?></span><?php endif; ?>
      </div>
    </div>
    <div class="dprice">
      <div class="price big"><?php echo $priceHtml; ?></div>
      <?php if (!empty($a['price_drop'])) : ?><div class="dropline"><span class="dbadge">🔻 <?php ae_e('ph_drop_badge'); ?></span> <s class="sub"><?php echo ae_money($a['price_drop']['from']); ?></s></div><?php endif; ?>
      <?php if ($a['currency'] !== ae_currency()) : ?><div class="sub"><?php ae_e('original_price'); ?>: <?php echo esc_html(AE_SYM[$a['currency']] . number_format($a['price'], 0, ',', '.')); ?></div><?php endif; ?>
      <?php if ($rl = ae_rate_line()) : ?><div class="sub rateline"><button type="button" class="linkbtn" data-conv="<?php echo esc_attr((string) $a['price_gbp']); ?>">⇄ <?php ae_e('conv_title'); ?></button> · <?php echo esc_html($rl); ?></div><?php endif; ?>
    </div>
  </header>

  <div class="dgrid">
    <div class="dgal">
      <div class="gallery" id="gallery" aria-roledescription="carousel">
        <?php if ($n) : ?>
          <div class="gtrack">
            <?php foreach ($a['photos'] as $i => $p) : ?>
              <figure class="gslide<?php echo $i === 0 ? ' on' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(ae_t('gallery', $i + 1, $n)); ?>">
                <img src="<?php echo esc_url($p['large']); ?>" alt="<?php echo esc_attr($a['title'] . ' – ' . ($i + 1)); ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" <?php echo $i === 0 ? 'fetchpriority="high"' : ''; ?> decoding="async" <?php if ($p['width']) : ?>width="<?php echo (int) $p['width']; ?>" height="<?php echo (int) $p['height']; ?>"<?php endif; ?>>
              </figure>
            <?php endforeach; ?>
          </div>
          <?php if ($n > 1) : ?>
            <button class="sarrow prev" type="button" aria-label="<?php ae_e('prev'); ?>">‹</button>
            <button class="sarrow next" type="button" aria-label="<?php ae_e('next'); ?>">›</button>
          <?php endif; ?>
          <span class="gcount"><span class="gi">1</span> / <?php echo $n; ?></span>
          <span class="gbtns">
            <button type="button" class="gfull" data-gv="g"><?php echo ae_icon('expand', 16); ?> <?php ae_e('all_photos', $n); ?></button>
            <?php if ($video) : ?><button type="button" class="gfull" data-gv="v">▶ <?php ae_e('video'); ?></button><?php endif; ?>
            <?php if ($panos || $tourEmbed) : ?><button type="button" class="gfull" data-gv="p">🌐 <?php echo $panos ? esc_html(ae_t('pano_tour_n', count($panos))) : esc_html(ae_t('tour')); ?></button><?php endif; ?>
          </span>
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
      <?php if ($n) : ?>
        <script type="application/json" id="lb-data"><?php echo wp_json_encode(array_map(fn($p) => ['l' => $p['large'], 's' => $p['small'], 'w' => $p['width'], 'h' => $p['height']], $a['photos']), JSON_UNESCAPED_SLASHES); ?></script>
      <?php endif; ?>
    </div>

    <div class="ddesc">
      <section class="panel" id="ozet">
        <?php echo ae_info_strip([
            ['home', ae_t('property_type'), $a['subtype_name']],
            ['bed', ae_t('bedrooms'), $a['rooms']],
            ['bath', ae_t('baths'), $a['baths']],
            ['area', ae_t('area'), $a['area_m2'] ? $a['area_m2'] . ' m²' : null],
            ['deed', ae_t('deed'), $a['deed_name'] ? $a['deed_name'] : ($a['mode'] === 'sale' ? ae_t('deed_unknown') : null)],
        ]); ?>
        <h2><?php ae_e('description'); ?></h2>
        <div class="desc"><?php echo wpautop(esc_html($a['description'])); ?></div>
      </section>

      <?php if ($panos || $tourEmbed) : ?>
        <section class="panel" id="tur"><h2>🌐 <?php ae_e('pano_tour'); ?></h2>
          <?php if ($panos) : ?>
            <div class="panobox" id="panobox" data-panos="<?php echo esc_attr(wp_json_encode(array_map(fn($p) => ['u' => $p['url'], 't' => $p['title'] ?: ae_t('pano_room', 1), 'th' => $p['thumb']], $panos), JSON_UNESCAPED_SLASHES)); ?>">
              <div class="panoview" id="panoview">
                <button type="button" class="panostart" id="panostart" style="background-image:url('<?php echo esc_url($panos[0]['thumb']); ?>')"><span>▶ <?php ae_e('pano_start'); ?></span><small><?php ae_e('pano_hint'); ?></small></button>
              </div>
              <?php if (count($panos) > 1) : ?>
                <div class="panorooms" role="tablist" aria-label="<?php ae_e('pano_rooms'); ?>">
                  <?php foreach ($panos as $i => $p) : ?><button type="button" role="tab" data-i="<?php echo $i; ?>" class="<?php echo $i ? '' : 'on'; ?>"><img src="<?php echo esc_url($p['thumb']); ?>" alt="" loading="lazy"><span><?php echo esc_html($p['title'] ?: ae_t('pano_room', $i + 1)); ?></span></button><?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($tourEmbed) : ?>
            <div class="tourframe"><iframe src="<?php echo esc_url($tourEmbed); ?>" title="<?php ae_e('tour'); ?>" loading="lazy" allow="fullscreen; xr-spatial-tracking; gyroscope; accelerometer" allowfullscreen></iframe></div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <section class="panel" id="detaylar">
        <h2><?php ae_e($a['ptype'] === 'konut' ? 'sec_details_home' : 'sec_details'); ?></h2>
        <div class="dgroups">
          <?php foreach ($details as $gk => $rows) : $rows = array_filter($rows, fn($r) => $r[1] !== null && $r[1] !== ''); if (!$rows) { continue; } ?>
            <div class="dgroup"><h3><?php ae_e($gk); ?></h3>
              <dl><?php foreach ($rows as [$k, $v]) : ?><div><dt><?php echo esc_html($k); ?></dt><dd><?php echo esc_html((string) $v); ?></dd></div><?php endforeach; ?></dl>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if ($a['mode'] === 'sale') : ?>
      <section class="panel deedpanel">
        <h2><?php ae_e('deed'); ?></h2>
        <?php if ($a['deed_type']) : ?>
          <p class="deedname"><b><?php echo esc_html($a['deed_name']); ?></b></p>
          <p class="sub"><?php echo esc_html($a['deed_desc']); ?></p>
        <?php else : ?>
          <p class="deedname unknown"><b><?php ae_e('deed_unknown'); ?></b></p>
        <?php endif; ?>
        <p class="note"><?php ae_e('deed_warning'); ?></p>
      </section>
      <?php endif; ?>

      <?php if ($groups) : ?>
        <section class="panel" id="ozellikler"><h2><?php ae_e('f_features'); ?></h2>
          <div class="fgroups"><?php foreach ($groups as $g) : ?><div><h3><?php echo esc_html($g['name']); ?></h3><ul class="featlist"><?php foreach ($g['items'] as $fn) : ?><li><?php echo esc_html($fn); ?></li><?php endforeach; ?></ul></div><?php endforeach; ?></div>
        </section>
      <?php endif; ?>

      <?php if ($video || ($a['tour_url'] && !$tourEmbed)) : ?>
        <section class="panel" id="video"><h2><?php ae_e('video'); ?></h2>
          <?php echo ae_video_html($video, $a['title']); ?>
          <?php if ($a['tour_url'] && !$tourEmbed) : ?><p><a class="btn" href="<?php echo esc_url($a['tour_url']); ?>" target="_blank" rel="noopener nofollow">⟳ <?php ae_e('tour'); ?></a></p><?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($coords) : ?>
        <section class="panel" id="konum"><h2><?php ae_e('location'); ?> · <?php echo esc_html($loc); ?></h2>
          <?php echo ae_map_box((float) $coords[0], (float) $coords[1], $coords[2], $a['title']); ?>
        </section>
      <?php endif; ?>

      <?php if (count($series) >= 2) : $cmp = ec_index_compare($a); ?>
        <?php if (count($priceHist) >= 2) : ?>
        <section class="panel" id="fiyatgecmisi"><h2><?php ae_e('ph_title'); ?></h2>
          <ol class="phlist">
            <?php foreach (array_reverse($priceHist) as $i => $h) : $prevH = $priceHist[count($priceHist) - 2 - $i] ?? null; $diff = $prevH ? (float) $h['price_gbp'] - (float) $prevH['price_gbp'] : 0; ?>
              <li><span class="sub"><?php echo esc_html(wp_date('d.m.Y', strtotime($h['created_at'] . ' UTC'))); ?></span> <b><?php echo esc_html(AE_SYM[$h['currency']] . number_format((float) $h['price'], 0, ',', '.')); ?></b>
                <?php if ($prevH && abs($diff) >= 1) : ?><span class="<?php echo $diff < 0 ? 'down' : 'up'; ?>"><?php echo $diff < 0 ? '▼' : '▲'; ?> <?php echo esc_html(abs((int) round(100 * $diff / max(1, (float) $prevH['price_gbp'])))); ?>%</span><?php elseif (!$prevH) : ?><span class="sub"><?php ae_e('ph_first'); ?></span><?php endif; ?></li>
            <?php endforeach; ?>
          </ol>
        </section>
        <?php endif; ?>
        <section class="panel" id="trend"><h2><?php ae_e('trend_title', $seriesCity); ?></h2>
          <p class="sub"><?php ae_e($a['mode'] === 'rent' ? 'trend_sub_rent' : 'trend_sub'); ?><?php if (!empty(end($series)['demo'])) : ?> · <b><?php ae_e('demo_data'); ?></b><?php endif; ?></p>
          <?php echo ae_chart($series, ae_t('trend_title', $seriesCity)); ?>
          <?php if ($cmp) :
              $pct = ae_pct_label($cmp['diff']);
              $key = abs($cmp['diff']) < 3 ? 'idx_compare_equal' : ($cmp['diff'] < 0 ? 'idx_compare_below' : 'idx_compare_above'); ?>
            <p class="cmp <?php echo $cmp['diff'] < -3 ? 'below' : ($cmp['diff'] > 3 ? 'above' : ''); ?>"><b><?php echo esc_html(ae_t($key, $a['city_name'], $pct)); ?></b><span class="sub"><?php echo ae_money($cmp['m2']); ?> <?php ae_e('per_m2'); ?> · <?php ae_e('price_index'); ?>: <?php echo ae_money($cmp['median']); ?> <?php ae_e('per_m2'); ?></span></p>
          <?php endif; ?>
          <p><a class="link" href="<?php echo esc_url(add_query_arg(array_filter(['sehir' => $a['city'], 'tur' => $a['mode'] === 'rent' ? 'rent' : '']), ec_url_section('index'))); ?>"><?php ae_e('idx_see'); ?> →</a></p>
        </section>
      <?php endif; ?>
      <?php echo ae_ad('detail'); ?>
    </div>

    <aside class="dside" id="iletisim">
      <div class="panel sticky">
        <div class="price big"><?php echo $priceHtml; ?></div>
        <?php echo ae_agent_card($agentRow, ['count' => $agentCount > 1 ? $agentCount : null]); ?>
        <div class="row">
          <button class="btn" type="button" id="telbtn" data-tel="<?php echo esc_attr($tel); ?>"><?php echo ae_icon('phone', 16); ?> <?php ae_e('show_phone'); ?></button>
          <?php if ($wa) : ?><a class="wa" href="<?php echo esc_url($waLink); ?>" target="_blank" rel="noopener"><?php ae_e('whatsapp'); ?></a><?php endif; ?>
        </div>
        <?php if ($a['status'] === 'published' && $a['mode'] !== 'daily') : ?>
          <button type="button" class="ghost alertbtn" data-alert-price="<?php echo (int) $a['id']; ?>" aria-pressed="false">🔔 <span class="allbl"><?php ae_e('al_price_btn'); ?></span></button>
        <?php endif; ?>
        <?php if (in_array($a['status'], ['published', 'sold'], true)) {
            echo ae_lead_form('listing', $a['id'], '#' . $a['id'] . ' ' . $a['title']);
        } ?>
        <div class="row tools">
          <button class="ghost sm heart-btn<?php echo in_array($a['id'], ae_favs(), true) ? ' on' : ''; ?>" type="button" data-fav="<?php echo (int) $a['id']; ?>">♥ <?php ae_e('favourites'); ?></button>
          <button class="ghost sm" type="button" data-note="<?php echo (int) $a['id']; ?>"><?php echo ae_icon('note', 16); ?> <?php ae_e('my_note'); ?></button>
          <button class="ghost sm" type="button" onclick="window.print()"><?php echo ae_icon('print', 16); ?> <?php ae_e('print'); ?></button>
        </div>
        <div class="mynote" id="mynote" hidden>
          <label class="vh" for="note-in"><?php ae_e('my_note'); ?></label>
          <textarea id="note-in" rows="3" placeholder="<?php ae_e('note_ph'); ?>"></textarea>
          <p class="sub"><?php ae_e('note_hint'); ?></p>
        </div>
        <?php echo ae_share_buttons($a['url'], $a['title'], $a['cover']['large'] ?? ''); ?>
        <p class="sub reportline"><button type="button" class="linkbtn" data-open="reportov"><?php echo ae_icon('flag', 14); ?> <?php ae_e('report_title'); ?></button></p>
      </div>
    </aside>
  </div>

  <?php if ($similar) : ?>
    <h2 class="sec"><?php ae_e('similar'); ?></h2>
    <div class="grid"><?php foreach ($similar as $s) {
        echo ae_card($s);
    } ?></div>
  <?php endif; ?>
  <?php if ($agentRow && $agentCount > 1 && !empty($a['agent']['url'])) : ?>
    <p class="center"><a class="ghost" href="<?php echo esc_url($a['agent']['url']); ?>"><?php ae_e('agent_other', $agentCount); ?> →</a></p>
  <?php endif; ?>

  <div class="printonly printfoot"><?php echo esc_html($a['url']); ?> · <?php echo esc_html(ae_brand()); ?><?php if ($ag) : ?> · <?php echo esc_html($ag['name'] . ' ' . $tel); ?><?php endif; ?></div>
</main>

<?php
$gvChips = array_values(array_filter([$a['area_m2'] ? $a['area_m2'] . ' m²' : '', $a['rooms'] ?: '', $a['subtype_name'] ?? '']));
echo ae_gallery_view([
    'photos' => $a['photos'], 'panos' => $panos, 'tourEmbed' => $tourEmbed, 'video' => $video, 'coords' => $coords,
    'title' => $a['title'], 'priceHtml' => $priceHtml, 'loc' => $loc, 'chips' => $gvChips,
    'agentHtml' => ae_agent_card($agentRow, ['count' => $agentCount > 1 ? $agentCount : null]), 'tel' => $tel, 'wa' => $waLink,
]);
?>
<div class="ov" id="reportov" hidden role="dialog" aria-modal="true" aria-labelledby="report-h">
  <div class="sheet">
    <button class="ovx" type="button" data-close aria-label="<?php ae_e('close'); ?>">✕</button>
    <h2 id="report-h"><?php ae_e('report_title'); ?></h2>
    <form class="reportform" data-report="<?php echo (int) $a['id']; ?>">
      <fieldset><legend class="sub"><?php ae_e('report_reason'); ?></legend>
        <?php foreach (ec_report_reasons() as $rk => $rn) : ?><label class="chk"><input type="radio" name="reason" value="<?php echo esc_attr($rk); ?>" required> <?php echo esc_html($rn[$lang]); ?></label><?php endforeach; ?>
      </fieldset>
      <label><?php ae_e('report_note'); ?><textarea name="note" rows="3"></textarea></label>
      <label><?php ae_e('email'); ?> (<?php ae_e('optional'); ?>)<input type="email" name="email"></label>
      <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
      <p class="formmsg" role="status"></p>
      <button class="btn wide" type="submit"><?php ae_e('report_send'); ?></button>
    </form>
  </div>
</div>
<?php
get_footer();
