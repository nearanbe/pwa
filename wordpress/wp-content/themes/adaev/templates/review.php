<?php
/** Onay paneli (sadece yönetici, telefona uygun): /tr/onay/ */
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$lang = ec_lang();
ae_seo(['title' => ae_t('rv_title')]);
add_action('wp_head', fn() => print('<meta name="robots" content="noindex">' . "\n"));

$listings = ec_listings_present($wpdb->get_results('SELECT * FROM ' . ec_table('listings') . " WHERE status = 'pending' ORDER BY updated_at ASC LIMIT 100", ARRAY_A), $lang, true);
$accounts = $wpdb->get_results('SELECT * FROM ' . ec_table('agents') . " WHERE status = 'pending' ORDER BY id ASC LIMIT 100", ARRAY_A);
$projects = array_map(fn($p) => ec_project_present($p, $lang, true), $wpdb->get_results('SELECT * FROM ' . ec_table('projects') . " WHERE status = 'pending' ORDER BY id ASC LIMIT 100", ARRAY_A));
$agents = ec_agents_by_ids(array_filter(array_merge(array_column($listings, 'agent_id'), array_column($projects, 'agent_id'))));
$kinds = ec_member_kinds();
$wa = fn($phone) => 'https://wa.me/' . preg_replace('/\D/', '', (string) $phone);
get_header();
?>
<main id="main" class="main review" data-api="<?php echo esc_url(home_url('/api/v1/')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
  <h1 class="sec"><?php ae_e('rv_title'); ?></h1>

  <h2 class="rvh"><?php ae_e('rv_listings'); ?> <span class="count"><?php echo count($listings); ?></span></h2>
  <?php foreach ($listings as $a) : $ag = $agents[$a['agent_id']] ?? null; ?>
    <article class="rvcard" data-type="listing" data-id="<?php echo (int) $a['id']; ?>">
      <div class="rvimgs"><?php foreach (array_slice($a['photos'], 0, 4) as $ph) : ?><img src="<?php echo esc_url($ph['small']); ?>" alt="" loading="lazy"><?php endforeach; ?><?php if (!$a['photos']) : ?><div class="img"><?php echo AE_ICON; ?></div><?php endif; ?></div>
      <div class="rvbody">
        <div class="price"><?php echo ae_price($a['price_gbp'], $a['mode'], false); ?></div>
        <h3><a href="<?php echo esc_url($a['url']); ?>" target="_blank">#<?php echo (int) $a['id']; ?> <?php echo esc_html($a['title']); ?></a></h3>
        <div class="sub"><?php echo esc_html(ae_t($a['mode']) . ' · ' . $a['subtype_name'] . ' · ' . trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name'])); ?></div>
        <div class="sub"><?php echo esc_html(implode(' · ', array_filter([$a['area_m2'] ? $a['area_m2'] . ' m²' : '', $a['rooms'], $a['deed_name'] ?: ae_t('deed_unknown')]))); ?></div>
        <?php if ($a['description']) : ?><p class="rvdesc"><?php echo esc_html(wp_trim_words($a['description'], 40)); ?></p><?php endif; ?>
        <?php if ($ag) : ?>
          <div class="rvowner"><b><?php echo esc_html($ag['name']); ?></b> · <?php echo esc_html($kinds[$ag['kind']]['name']['tr'] ?? $ag['kind']); ?>
            <?php if ($ag['phone']) : ?> · <a href="tel:<?php echo esc_attr($ag['phone']); ?>"><?php echo esc_html($ag['phone']); ?></a> · <a href="<?php echo esc_url($wa($ag['whatsapp'] ?: $ag['phone'])); ?>" target="_blank">WhatsApp</a><?php endif; ?></div>
        <?php endif; ?>
      </div>
      <div class="rvact"><button class="btn" data-act="approve">✅ <?php ae_e('rv_approve'); ?></button><button class="ghost" data-act="reject">❌ <?php ae_e('rv_reject'); ?></button></div>
    </article>
  <?php endforeach; ?>
  <?php if (!$listings) : ?><p class="empty"><?php ae_e('rv_empty'); ?></p><?php endif; ?>

  <h2 class="rvh"><?php ae_e('rv_accounts'); ?> <span class="count"><?php echo count($accounts); ?></span></h2>
  <?php foreach ($accounts as $ac) : ?>
    <article class="rvcard" data-type="agent" data-id="<?php echo (int) $ac['id']; ?>">
      <div class="rvbody">
        <h3><?php echo esc_html($ac['name']); ?> <span class="sub">· <?php echo esc_html($kinds[$ac['kind']]['name']['tr'] ?? $ac['kind']); ?></span></h3>
        <div class="sub"><?php echo esc_html(implode(' · ', array_filter([$ac['contact_person'], $ac['email'], $ac['phone'], $ac['city'] ? ec_city_name($ac['city'], 'tr') : '', $ac['license_no'] ? 'Ruhsat: ' . $ac['license_no'] : '']))); ?></div>
        <div class="sub"><?php echo $ac['email_verified'] ? '✔ e-posta doğrulandı' : '✉ e-posta henüz doğrulanmadı'; ?> · <?php echo esc_html(get_date_from_gmt($ac['created_at'], 'd.m.Y H:i')); ?></div>
        <?php if ($ac['phone']) : ?><div class="rvowner"><a href="tel:<?php echo esc_attr($ac['phone']); ?>">📞 <?php echo esc_html($ac['phone']); ?></a> · <a href="<?php echo esc_url($wa($ac['whatsapp'] ?: $ac['phone'])); ?>" target="_blank">WhatsApp</a></div><?php endif; ?>
      </div>
      <div class="rvact"><button class="btn" data-act="approve">✅ <?php ae_e('rv_approve'); ?></button><button class="ghost" data-act="suspend">⛔ <?php ae_e('rv_reject'); ?></button></div>
    </article>
  <?php endforeach; ?>
  <?php if (!$accounts) : ?><p class="empty"><?php ae_e('rv_empty'); ?></p><?php endif; ?>

  <?php if ($projects) : ?>
    <h2 class="rvh"><?php ae_e('rv_projects'); ?> <span class="count"><?php echo count($projects); ?></span></h2>
    <?php foreach ($projects as $p) : $ag = $agents[$p['agent_id']] ?? null; ?>
      <article class="rvcard" data-type="project" data-id="<?php echo (int) $p['id']; ?>">
        <div class="rvbody">
          <h3><a href="<?php echo esc_url($p['url']); ?>" target="_blank"><?php echo esc_html($p['name']); ?></a></h3>
          <div class="sub"><?php echo esc_html($p['city_name'] . ($p['district'] ? ' / ' . $p['district'] : '') . ($ag ? ' · ' . $ag['name'] : '')); ?></div>
        </div>
        <div class="rvact"><button class="btn" data-act="approve">✅ <?php ae_e('rv_approve'); ?></button><button class="ghost" data-act="reject">❌ <?php ae_e('rv_reject'); ?></button></div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php $reports = ec_reports_open(); if ($reports) : $rr = ec_report_reasons(); ?>
    <h2 class="rvh"><?php ae_e('rv_reports'); ?> <span class="count"><?php echo count($reports); ?></span></h2>
    <?php foreach ($reports as $r) : $lr = ec_listing_row((int) $r['listing_id']); $li = $lr ? ec_listings_present([$lr], $lang)[0] : null; ?>
      <article class="rvcard" data-type="report" data-id="<?php echo (int) $r['id']; ?>">
        <div class="rvbody">
          <h3><?php if ($li) : ?><a href="<?php echo esc_url($li['url']); ?>" target="_blank">#<?php echo (int) $r['listing_id']; ?> <?php echo esc_html($li['title']); ?></a><?php else : ?>#<?php echo (int) $r['listing_id']; ?><?php endif; ?></h3>
          <div class="sub">⚠️ <b><?php echo esc_html($rr[$r['reason']]['tr'] ?? $r['reason']); ?></b><?php if ((int) $r['n'] > 1) : ?> · <?php echo (int) $r['n']; ?> <?php ae_e('rv_reports_n'); ?><?php endif; ?> · <?php echo esc_html(get_date_from_gmt($r['created_at'], 'd.m.Y H:i')); ?><?php if ($r['email']) : ?> · <?php echo esc_html($r['email']); ?><?php endif; ?></div>
          <?php if ($r['note']) : ?><p class="rvdesc"><?php echo esc_html($r['note']); ?></p><?php endif; ?>
        </div>
        <div class="rvact"><button class="ghost" data-act="close">✓ <?php ae_e('rv_close_report'); ?></button><?php if ($lr && $lr['status'] === 'published') : ?><button class="ghost" data-act="passive">⏸ <?php ae_e('act_passive'); ?></button><?php endif; ?></div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</main>
<script>
(function () {
  var main = document.querySelector('main.review');
  main.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-act]'); if (!b) return;
    var card = b.closest('.rvcard'), act = b.dataset.act, reason = '';
    if (act === 'reject') { reason = prompt(<?php echo wp_json_encode(ae_t('rv_reason_prompt')); ?>); if (reason === null) return; }
    if (act === 'suspend' && !confirm(<?php echo wp_json_encode(ae_t('act_delete_confirm')); ?>)) return;
    card.querySelectorAll('button').forEach(function (x) { x.disabled = true; });
    var isRep = card.dataset.type === 'report';
    var lid = isRep ? (card.querySelector('h3').textContent.match(/#(\d+)/) || [])[1] : null;
    var url = isRep ? (act === 'passive' ? 'listings/' + lid : 'reports/' + card.dataset.id) : 'review/' + card.dataset.type + '/' + card.dataset.id;
    var body = isRep ? (act === 'passive' ? { status: 'passive' } : { status: 'closed' }) : { action: act, reason: reason };
    fetch(main.dataset.api + url, {
      method: isRep ? 'PATCH' : 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': main.dataset.nonce },
      body: JSON.stringify(body)
    }).then(function (r) {
      if (!r.ok) throw new Error(r.status);
      if (isRep && act === 'passive') fetch(main.dataset.api + 'reports/' + card.dataset.id, { method: 'PATCH', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': main.dataset.nonce }, body: '{"status":"closed"}' });
      card.classList.add('done'); card.querySelector('.rvact').textContent = <?php echo wp_json_encode('✓ ' . ae_t('rv_done')); ?>; })
      .catch(function (err) { alert('Hata: ' + err.message); card.querySelectorAll('button').forEach(function (x) { x.disabled = false; }); });
  });
})();
</script>
<?php
get_footer();
