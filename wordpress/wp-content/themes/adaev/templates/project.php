<?php
/** Proje detayı: /tr/proje/{slug}-{no}/ */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$p = $GLOBALS['ec_current_project'];
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_project($p['id'], ec_slugify($p['name']), $l);
}
$loc = $p['city_name'] . ($p['district'] ? ' / ' . $p['district'] : '');
ae_seo([
    'title' => $p['name'] . ' – ' . $loc,
    'description' => $p['description'] ?: $p['name'],
    'canonical' => $p['url'],
    'alternates' => $alts,
    'image' => $p['cover']['large'] ?? '',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'ApartmentComplex',
        'name' => $p['name'],
        'url' => $p['url'],
        'image' => $p['cover']['large'] ?? null,
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $loc, 'addressCountry' => 'CY'],
        'numberOfAccommodationUnits' => $p['units'],
    ],
]);
$agentRow = $p['agent_id'] ? ec_agent($p['agent_id']) : null;
$tel = (string) ($agentRow['phone'] ?? '');
$wa = $agentRow ? preg_replace('/\D/', '', (string) ($agentRow['whatsapp'] ?: $agentRow['phone'])) : preg_replace('/\D/', '', (string) (get_option('ec_contact')['whatsapp'] ?? ''));
$waLink = $wa ? 'https://wa.me/' . $wa . '?text=' . rawurlencode(ae_t('wa_project', $p['name'], $p['url'])) : '';
$byFirm = $p['agent_id'] ? ec_projects_by_agent($p['agent_id'], $p['id'], $lang, 4) : [];
$others = $byFirm ?: array_values(array_filter(ec_projects($lang, 8), fn($x) => $x['id'] !== $p['id']));
$media = $p['media'];
$photos = array_merge($p['cover'] ? [$p['cover'] + ['cover' => true]] : [], $media['photo']);
$n = count($photos);
$coords = $p['lat'] && $p['lng'] ? [$p['lat'], $p['lng'], false] : (isset(ec_city_coords()[$p['city']]) ? [ec_city_coords()[$p['city']][0], ec_city_coords()[$p['city']][1], true] : null);
$units = $p['unit_types'];
$planFor = function (string $type) use ($media) {
    foreach ($media['plan'] as $pl) {
        if ($pl['title'] && mb_stripos($pl['title'], $type) !== false) {
            return $pl;
        }
    }
    return null;
};
$yt = $p['video_url'] && preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $p['video_url'], $ym) ? $ym[1] : null;
$priceFrom = $p['price_min_gbp'] ?: (array_filter(array_column($units, 'price_from_gbp')) ? min(array_filter(array_column($units, 'price_from_gbp'))) : null);
$sections = ['ozet' => ae_t('sec_overview')];
if ($units) {
    $sections['daireler'] = ae_t('pj_units');
}
if ($p['down_payment_pct'] !== null || $p['payment_plan']) {
    $sections['odeme'] = ae_t('payment_plan');
}
if ($media['plan']) {
    $sections['planlar'] = ae_t('pf_plans');
}
if ($coords) {
    $sections['konum'] = ae_t('location');
}
$sections['iletisim'] = ae_t('sec_contact');
ae_enqueue_leaflet();
get_header();
$priceShort = $priceFrom ? ae_t('price_from_x', ae_money($priceFrom)) : '';
echo ae_sticky_head(['logo' => !empty($agentRow['logo_path']) ? ec_image_urls($agentRow['logo_path'])['small'] : '', 'title' => $p['name'], 'price' => $priceShort, 'tel' => $tel, 'wa' => $waLink, 'sections' => $sections]);
?>
<main id="main" class="main detail project-detail">
  <?php if (!empty($GLOBALS['ec_preview'])) : ?><p class="note warn">👁 <?php ae_e('st_' . $p['status']); ?></p><?php endif; ?>
  <nav class="crumbs" aria-label="breadcrumb">
    <a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> ›
    <a href="<?php echo esc_url(ec_url_section('project')); ?>"><?php ae_e('projects'); ?></a> ›
    <span><?php echo esc_html($p['name']); ?></span>
  </nav>

  <header class="dhead" id="dhead">
    <div>
      <h1 class="dtitle"><?php echo esc_html($p['name']); ?></h1>
      <div class="sub"><?php echo ae_icon('pin', 16); ?> <?php echo esc_html($loc); ?><?php if ($p['developer']) : ?> · <?php echo esc_html($p['developer']); ?><?php endif; ?><?php if ($coords) : ?> · <a href="#konum" class="link"><?php ae_e('see_on_map'); ?></a><?php endif; ?></div>
    </div>
    <?php if ($priceFrom) : ?>
      <div class="dprice"><div class="sub"><?php ae_e('price_range'); ?></div><div class="price big"><?php echo ae_money($priceFrom); ?><?php if ($p['price_max_gbp']) : ?> – <?php echo ae_money($p['price_max_gbp']); ?><?php endif; ?></div></div>
    <?php endif; ?>
  </header>

  <div class="dgrid">
    <div class="dgal">
      <div class="gallery" id="gallery">
        <?php if ($n) : ?>
          <div class="gtrack">
            <?php foreach ($photos as $i => $ph) : ?>
              <figure class="gslide<?php echo $i === 0 ? ' on' : ''; ?>"><img src="<?php echo esc_url($ph['large']); ?>" alt="<?php echo esc_attr($p['name'] . ' – ' . ($i + 1)); ?>" loading="<?php echo $i ? 'lazy' : 'eager'; ?>" decoding="async"></figure>
            <?php endforeach; ?>
          </div>
          <?php if ($n > 1) : ?><button class="sarrow prev" type="button" aria-label="<?php ae_e('prev'); ?>">‹</button><button class="sarrow next" type="button" aria-label="<?php ae_e('next'); ?>">›</button><?php endif; ?>
          <span class="gcount"><span class="gi">1</span> / <?php echo $n; ?></span>
          <button type="button" class="gfull" data-lightbox="0"><?php echo ae_icon('expand', 16); ?> <?php ae_e('all_photos', $n); ?></button>
          <?php if ($p['cover_ai']) : ?><span class="aitag big"><?php ae_e('temsili'); ?></span><?php endif; ?>
        <?php else : ?>
          <div class="img noimg"><?php echo AE_ICON; ?></div>
        <?php endif; ?>
      </div>
      <?php if ($n > 1) : ?>
        <div class="thumbs" id="thumbs"><?php foreach ($photos as $i => $ph) : ?><button type="button" class="<?php echo $i ? '' : 'on'; ?>" data-go="<?php echo $i; ?>" aria-label="<?php echo esc_attr(ae_t('gallery', $i + 1, $n)); ?>"><img src="<?php echo esc_url($ph['small']); ?>" alt="" loading="lazy"></button><?php endforeach; ?></div>
      <?php endif; ?>
      <?php if ($n) : ?><script type="application/json" id="lb-data"><?php echo wp_json_encode(array_map(fn($x) => ['l' => $x['large'], 's' => $x['small'], 'w' => $x['width'] ?? null, 'h' => $x['height'] ?? null], $photos), JSON_UNESCAPED_SLASHES); ?></script><?php endif; ?>
    </div>

    <div class="ddesc">
      <section class="panel" id="ozet">
        <ul class="tiles">
          <?php foreach ([
              ['home', ae_t('pj_type'), $p['project_type_name']],
              ['crane', ae_t('pj_status'), $p['build_status_name']],
              ['units', ae_t('units_label'), $p['units']],
              ['calendar', ae_t('delivery_label'), $p['delivery_date'] ? ae_month_label($p['delivery_date']) : null],
              ['area', ae_t('pj_land'), $p['land_m2'] ? number_format_i18n($p['land_m2']) . ' m²' : null],
          ] as [$ic, $lb, $v]) : if ($v === null || $v === '') { continue; } ?>
            <li><?php echo ae_icon($ic, 24); ?><small><?php echo esc_html($lb); ?></small><b><?php echo esc_html((string) $v); ?></b></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($p['progress'] !== null) : ?>
          <div class="progress" role="progressbar" aria-valuenow="<?php echo (int) $p['progress']; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php ae_e('pf_progress'); ?>">
            <div class="pbar"><span style="width:<?php echo (int) $p['progress']; ?>%"></span></div>
            <div class="sub"><?php ae_e('pf_progress'); ?>: <b><?php echo (int) $p['progress']; ?>%</b></div>
          </div>
        <?php endif; ?>
        <?php if ($p['description']) : ?><h2><?php ae_e('description'); ?></h2><div class="desc"><?php echo wpautop(esc_html($p['description'])); ?></div><?php endif; ?>
      </section>

      <?php if ($units) : ?>
        <section class="panel" id="daireler"><h2><?php ae_e('pj_units'); ?></h2>
          <div class="unitcards">
            <?php foreach ($units as $u) : $pl = $planFor((string) $u['type']); ?>
              <article class="unitcard">
                <?php if ($pl) : ?><button type="button" class="uplan" data-plan="<?php echo esc_url($pl['large']); ?>" aria-label="<?php echo esc_attr(ae_t('pf_plans') . ' ' . $u['type']); ?>"><img src="<?php echo esc_url($pl['small']); ?>" alt="" loading="lazy"></button><?php endif; ?>
                <div><h3><?php echo esc_html((string) $u['type']); ?></h3>
                  <?php if ($u['m2_min']) : ?><p class="sub"><?php echo (int) $u['m2_min']; ?><?php if ($u['m2_max'] && $u['m2_max'] != $u['m2_min']) : ?>–<?php echo (int) $u['m2_max']; ?><?php endif; ?> m²</p><?php endif; ?>
                  <?php if ($u['price_from_gbp']) : ?><p class="price"><?php echo wp_kses_post(ae_t('price_from_x', ae_money((float) $u['price_from_gbp']))); ?></p><?php endif; ?>
                  <?php if ($u['available'] !== null) : ?><p class="sub"><?php echo $u['available'] > 0 ? esc_html(ae_t('pj_available', $u['available'])) : '<b>' . esc_html(ae_t('sold')) . '</b>'; ?></p><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($p['amenity_names']) : ?>
        <section class="panel"><h2><?php ae_e('pf_amenities'); ?></h2><ul class="featlist"><?php foreach ($p['amenity_names'] as $an) : ?><li><?php echo esc_html($an); ?></li><?php endforeach; ?></ul></section>
      <?php endif; ?>

      <?php if ($p['down_payment_pct'] !== null || $p['payment_plan']) : ?>
        <section class="panel" id="odeme"><h2><?php ae_e('payment_plan'); ?></h2>
          <?php if ($p['payment_plan']) : ?><p><b><?php echo esc_html($p['payment_plan']); ?></b></p><?php endif; ?>
          <?php if ($priceFrom) : ?>
            <form class="calcbox" id="instcalc" data-rates="<?php echo esc_attr(wp_json_encode(ec_rates())); ?>">
              <div class="fg">
                <label><?php ae_e('pj_calc_price'); ?>
                  <select name="price">
                    <?php foreach ($units as $u) : if (!$u['price_from_gbp']) { continue; } ?><option value="<?php echo esc_attr((string) $u['price_from_gbp']); ?>"><?php echo esc_html($u['type'] . ' – ' . ae_format_money((float) $u['price_from_gbp'])); ?></option><?php endforeach; ?>
                    <?php if (!array_filter(array_column($units, 'price_from_gbp'))) : ?><option value="<?php echo esc_attr((string) $priceFrom); ?>"><?php echo esc_html(ae_format_money($priceFrom)); ?></option><?php endif; ?>
                  </select></label>
                <label><?php ae_e('pf_down'); ?><input name="down" inputmode="numeric" value="<?php echo (int) ($p['down_payment_pct'] ?? 35); ?>"></label>
                <label><?php ae_e('pf_installments'); ?><input name="months" inputmode="numeric" value="<?php echo (int) ($p['installments'] ?: 36); ?>"></label>
              </div>
              <div class="calcout" aria-live="polite">
                <div><small><?php ae_e('pj_calc_down'); ?></small><b data-o="down">—</b></div>
                <div><small><?php ae_e('pj_calc_monthly'); ?></small><b data-o="monthly">—</b></div>
                <div><small><?php ae_e('pj_calc_total'); ?></small><b data-o="total">—</b></div>
              </div>
              <p class="sub"><?php ae_e('pj_calc_note'); ?></p>
            </form>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($media['plan']) : ?>
        <section class="panel" id="planlar"><h2><?php ae_e('pf_plans'); ?></h2>
          <div class="plangrid"><?php foreach ($media['plan'] as $pl) : ?><figure><button type="button" data-plan="<?php echo esc_url($pl['large']); ?>"><img src="<?php echo esc_url($pl['small']); ?>" alt="<?php echo esc_attr((string) $pl['title']); ?>" loading="lazy"></button><?php if ($pl['title']) : ?><figcaption class="sub"><?php echo esc_html($pl['title']); ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div>
        </section>
      <?php endif; ?>

      <?php if ($media['brochure'] || $yt || $p['video_url']) : ?>
        <section class="panel"><h2><?php ae_e($media['brochure'] ? 'pj_docs' : 'video'); ?></h2>
          <?php foreach ($media['brochure'] as $b) : ?><p><a class="btn" href="<?php echo esc_url($b['url']); ?>" target="_blank" rel="noopener"><?php echo ae_icon('pdf', 18); ?> <?php echo esc_html($b['title'] ?: ae_t('pf_brochure')); ?> (PDF)</a></p><?php endforeach; ?>
          <?php if ($yt) : ?><div class="video"><iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($yt); ?>" title="<?php echo esc_attr($p['name']); ?>" loading="lazy" allowfullscreen></iframe></div>
          <?php elseif ($p['video_url']) : ?><p><a class="ghost" href="<?php echo esc_url($p['video_url']); ?>" target="_blank" rel="noopener nofollow">▶ <?php ae_e('video'); ?></a></p><?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($coords) : ?>
        <section class="panel" id="konum"><h2><?php ae_e('location'); ?> · <?php echo esc_html($loc); ?></h2><?php echo ae_map_box((float) $coords[0], (float) $coords[1], $coords[2], $p['name']); ?></section>
      <?php endif; ?>
    </div>

    <aside class="dside" id="iletisim">
      <div class="panel sticky">
        <?php if ($priceFrom) : ?><div class="sub"><?php ae_e('price_range'); ?></div><div class="price big"><?php echo ae_money($priceFrom); ?><?php if ($p['price_max_gbp']) : ?> – <?php echo ae_money($p['price_max_gbp']); ?><?php endif; ?></div><?php endif; ?>
        <?php echo ae_agent_card($agentRow, ['count' => null]); ?>
        <div class="row">
          <?php if ($tel) : ?><button class="btn" type="button" id="telbtn" data-tel="<?php echo esc_attr($tel); ?>"><?php echo ae_icon('phone', 16); ?> <?php ae_e('show_phone'); ?></button><?php endif; ?>
          <?php if ($waLink) : ?><a class="wa" href="<?php echo esc_url($waLink); ?>" target="_blank" rel="noopener"><?php ae_e('ask_project'); ?></a><?php endif; ?>
        </div>
        <?php if ($p['status'] === 'published') {
            echo ae_lead_form('project', $p['id'], $p['name']);
        } ?>
        <?php echo ae_share_buttons($p['url'], $p['name'], $p['cover']['large'] ?? ''); ?>
        <p class="note"><?php ae_e('deed_warning'); ?></p>
      </div>
    </aside>
  </div>
  <?php if ($others) : ?>
    <h2 class="sec"><?php ae_e($byFirm ? 'pj_firm_other' : 'new_projects'); ?></h2>
    <div class="grid"><?php foreach (array_slice($others, 0, 4) as $o) {
        echo ae_project_card($o);
    } ?></div>
  <?php endif; ?>
</main>
<?php
get_footer();
