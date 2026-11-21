<?php
/**
 * Panelim: /tr/panelim/?bolum=ozet|ilanlar|ilan|projeler|proje|profil|telegram[&id=..]
 * Formlar assets/js/panel.js ile çalışır ve /api/v1 uçlarına (oturum + nonce) kaydeder.
 */
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$lang = ec_lang();
$agent = ec_current_agent();
ae_seo(['title' => ae_t('my_panel')]);
add_action('wp_head', fn() => print('<meta name="robots" content="noindex">' . "\n"));

if (!$agent) {
    // Yönetici ya da üye kaydı olmayan kullanıcı
    get_header();
    if (current_user_can('manage_options')) {
        $cu = wp_get_current_user();
        $cityOpts = '';
        foreach (ec_cities() as $ck => $c) {
            $cityOpts .= '<option value="' . esc_attr($ck) . '">' . esc_html($c['name'][$lang]) . '</option>';
        }
        echo '<main id="main" class="main auth"><div class="panel authbox"><h1>' . esc_html(ae_t('self_title')) . '</h1><p class="sub">' . esc_html(ae_t('self_sub')) . '</p>'
            . '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="fg two">' . wp_nonce_field('ec_self_profile', '_wpnonce', true, false)
            . '<input type="hidden" name="action" value="ec_self_profile">'
            . '<label class="wide">' . esc_html(ae_t('company_name')) . '<input name="name" required value="' . esc_attr(get_bloginfo('name')) . '"></label>'
            . '<label>' . esc_html(ae_t('email')) . '<input name="email" type="email" value="' . esc_attr($cu->user_email) . '"></label>'
            . '<label>' . esc_html(ae_t('reg_phone')) . '<input name="phone" inputmode="tel" placeholder="+90 533 …"></label>'
            . '<label class="wide">' . esc_html(ae_t('city')) . '<select name="city"><option value="">—</option>' . $cityOpts . '</select></label>'
            . '<button class="btn wide" type="submit">' . esc_html(ae_t('self_btn')) . '</button></form>'
            . '<p class="center"><a class="ghost" href="' . esc_url(ec_url_section('review')) . '">' . esc_html(ae_t('rv_title')) . ' →</a></p></div></main>';
    } else {
        echo '<main id="main" class="main auth"><div class="panel authbox"><p>' . esc_html(ae_t('not_found')) . '</p></div></main>';
    }
    get_footer();
    return;
}

$kind = ec_member_kinds()[$agent['kind']] ?? ec_member_kinds()['danisman'];
$canProjects = !empty($kind['projects']);
$tab = sanitize_key($_GET['bolum'] ?? 'ozet');
$id = (int) ($_GET['id'] ?? 0);
$base = ec_url_section('panel');
$url = fn(string $t, array $q = []) => esc_url(add_query_arg(['bolum' => $t] + $q, $base));

$tabs = ['ozet' => 'p_overview', 'ilanlar' => 'p_listings', 'ilan' => 'p_new_listing'];
if ($canProjects) {
    $tabs += ['projeler' => 'p_projects', 'proje' => 'p_new_project'];
}
$unread = ec_leads_unread((int) $agent['id']);
$canSocial = ec_member_can($agent, 'social');
$tabs += ['talepler' => 'p_leads', 'paket' => 'p_package', 'sosyal' => 'p_social', 'profil' => 'p_profile', 'telegram' => 'p_telegram'];
if (function_exists('ec_team_can_lead') && ec_team_can_lead($agent)) {
    $tabs = array_slice($tabs, 0, array_search('paket', array_keys($tabs), true), true) + ['ekip' => 'team_tab'] + array_slice($tabs, array_search('paket', array_keys($tabs), true), null, true);
}
if (!isset($tabs[$tab])) {
    $tab = 'ozet';
}

$counts = $wpdb->get_results($wpdb->prepare('SELECT status, COUNT(*) n, SUM(views) v FROM ' . ec_table('listings') . ' WHERE agent_id = %d GROUP BY status', $agent['id']), OBJECT_K);
$cnt = fn(string $s) => (int) ($counts[$s]->n ?? 0);
$views = array_sum(array_map(fn($c) => (int) $c->v, $counts));

/* Düzenlenen ilan / proje (sahiplik kontrolü) */
$editListing = null;
if ($tab === 'ilan' && $id) {
    $row = ec_listing_row($id);
    if ($row && (int) $row['agent_id'] === (int) $agent['id']) {
        $editListing = ec_listings_present([$row], $lang, true)[0];
        $editListing['i18n'] = [];
        foreach ($wpdb->get_results($wpdb->prepare('SELECT lang, title, description FROM ' . ec_table('listing_i18n') . ' WHERE listing_id = %d', $id), ARRAY_A) as $t) {
            $editListing['i18n'][$t['lang']] = ['title' => $t['title'], 'description' => $t['description']];
        }
        $editListing += ['price_raw' => (float) $row['price'], 'lat' => $row['lat'], 'lng' => $row['lng']];
    }
}
$editProject = null;
if ($tab === 'proje' && $id && $canProjects) {
    $prow = ec_project_row($id);
    if ($prow && (int) $prow['agent_id'] === (int) $agent['id']) {
        $editProject = ec_project_present($prow, $lang, true);
    }
}

/* panel.js için veri */
$cityCoords = ['lefkosa' => [35.185, 33.38], 'girne' => [35.335, 33.32], 'gazimagusa' => [35.125, 33.94], 'iskele' => [35.29, 33.89], 'guzelyurt' => [35.20, 32.99], 'lefke' => [35.11, 32.85]];
$types = [];
foreach (ec_property_types() as $k => $t) {
    $types[$k] = ['name' => $t['name'][$lang], 'sub' => array_map(fn($n) => $n[$lang], $t['sub'])];
}
$data = [
    'lang' => $lang,
    'tab' => $tab,
    'api' => home_url('/api/v1/'),
    'nonce' => wp_create_nonce('wp_rest'),
    'panel' => $base,
    'rates' => ec_rates(),
    'sym' => AE_SYM,
    'types' => $types,
    'cities' => array_map(fn($c) => ['name' => $c['name'][$lang], 'districts' => $c['districts']], ec_cities()),
    'coords' => $cityCoords,
    'deeds' => array_map(fn($k) => ['name' => ec_deed_types()[$k][$lang], 'desc' => ec_deed_desc($k, $lang)], array_combine(array_keys(ec_deed_types()), array_keys(ec_deed_types()))),
    'listing' => $editListing,
    'project' => $editProject,
    't' => [
        'required' => ae_t('f_required'), 'deedRequired' => ae_t('f_deed_required'), 'saved' => ae_t('f_saved'), 'errSave' => ae_t('f_err_save'),
        'pendingAfter' => ae_t('f_status_pending_after'), 'liveAfter' => ae_t('f_status_live_after'), 'uploading' => ae_t('f_uploading'), 'teamInvited' => ae_t('team_invited_ok', '%s'), 'prDone' => ae_t('pr_done'), 'payRef' => ae_t('pay_ref'), 'payAmount' => ae_t('pay_amount'), 'payIban' => ae_t('pay_iban'), 'payHolder' => ae_t('pay_holder'), 'payBankHint' => ae_t('pay_bank_hint'), 'payRedirect' => ae_t('pay_redirect'), 'payReceiptOk' => ae_t('pay_receipt_ok'), 'videoDone' => ae_t('f_video_done'), 'videoBig' => ae_t('f_video_big', size_format(wp_max_upload_size())), 'videoMax' => wp_max_upload_size(),
        'below' => ae_t('idx_compare_below', '%c', '%p'), 'above' => ae_t('idx_compare_above', '%c', '%p'), 'equal' => ae_t('idx_compare_equal', '%c'),
        'perMonth' => ae_t('per_month'), 'perNight' => ae_t('per_night'), 'confirm' => ae_t('act_delete_confirm'),
        'calc' => ae_t('pf_calc', '%1', '%2', '%3'), 'saveFirst' => ae_t('pf_save_first'), 'tgSoon' => ae_t('tg_bot_soon'), 'tgHint' => ae_t('tg_code_hint'),
        'mode' => ['sale' => ae_t('sale'), 'rent' => ae_t('rent'), 'daily' => ae_t('daily')],
        'cover' => ae_t('pf_cover'), 'panoRoom' => ae_t('pano_room', '%s'),
    ],
];

wp_enqueue_style('leaflet', get_theme_file_uri('assets/vendor/leaflet/leaflet.css'), [], '1.9.4');
wp_enqueue_script('leaflet', get_theme_file_uri('assets/vendor/leaflet/leaflet.js'), [], '1.9.4', ['in_footer' => true]);
wp_enqueue_script('ae-panel', get_theme_file_uri('assets/js/panel.js'), ['leaflet'], AE_VER, ['in_footer' => true]);
get_header();
$statusLabel = fn(string $s) => '<span class="st st-' . esc_attr($s) . '">' . esc_html(ae_t('st_' . $s)) . '</span>';
?>
<script type="application/json" id="ec-data"><?php echo wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
<main id="main" class="main panelpage">
  <div class="phead">
    <div class="pwho">
      <?php if (!empty($agent['logo_path'])) : ?><img class="plogo" src="<?php echo esc_url(ec_image_urls($agent['logo_path'])['small']); ?>" alt=""><?php else : ?><div class="av"><?php echo esc_html(mb_substr($agent['name'], 0, 1)); ?></div><?php endif; ?>
      <div><h1><?php ae_e('p_hello', $agent['name']); ?></h1><div class="sub"><?php echo esc_html($kind['name'][$lang]); ?><?php if ($agent['verified']) : ?> · ✔ <?php ae_e('of_verified'); ?><?php endif; ?></div></div>
    </div>
    <a class="ghost" href="<?php echo esc_url(wp_logout_url(ec_url_home())); ?>"><?php ae_e('logout'); ?></a>
  </div>
  <?php if ($agent['status'] === 'pending') : ?><p class="note warn"><?php ae_e('p_pending_account'); ?></p><?php elseif (!empty($kind['needs_review'])) : ?><p class="note"><?php ae_e('p_review_note'); ?></p><?php endif; ?>

  <nav class="ptabs" aria-label="<?php ae_e('my_panel'); ?>">
    <?php foreach ($tabs as $t => $label) : ?>
      <a href="<?php echo $url($t); ?>" class="<?php echo $t === $tab ? 'on' : ''; ?>"><?php ae_e($t === 'ilan' && $editListing ? 'p_edit_listing' : ($t === 'proje' && $editProject ? 'p_edit_project' : $label)); ?><?php if ($t === 'talepler' && $unread) : ?> <span class="count"><?php echo (int) $unread; ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if (function_exists('ec_team_parent')) :
      $tInv = !empty($agent['team_invite']) ? ec_agent((int) $agent['team_invite']) : null;
      $tPar = ec_team_parent($agent);
      if ($tInv) : ?>
        <div class="note teamnote">👥 <?php ae_e('team_invited', $tInv['name']); ?>
          <span class="row"><button type="button" class="btn sm" data-team="accept"><?php ae_e('team_accept'); ?></button><button type="button" class="ghost sm" data-team="decline"><?php ae_e('team_decline'); ?></button></span></div>
      <?php elseif ($tPar && $tab === 'ozet') : ?>
        <div class="note teamnote">👥 <?php ae_e('team_member_of', $tPar['name']); ?> <button type="button" class="linkbtn" data-team="leave" data-confirm="<?php echo esc_attr(ae_t('team_leave_q')); ?>"><?php ae_e('team_leave'); ?></button></div>
      <?php endif;
  endif; ?>

  <?php if ($tab === 'ozet') : ?>
    <div class="stats">
      <div class="stat"><b><?php echo $cnt('published'); ?></b><span><?php ae_e('p_stat_published'); ?></span></div>
      <div class="stat"><b><?php echo $cnt('pending'); ?></b><span><?php ae_e('p_stat_pending'); ?></span></div>
      <div class="stat"><b><?php echo $cnt('draft') + $cnt('rejected'); ?></b><span><?php ae_e('p_stat_draft'); ?></span></div>
      <div class="stat"><b><?php echo number_format_i18n($views); ?></b><span><?php ae_e('p_stat_views'); ?></span></div>
    </div>
    <div class="quick">
      <a class="btn" href="<?php echo $url('ilan'); ?>">＋ <?php ae_e('p_new_listing'); ?></a>
      <?php if ($canProjects) : ?><a class="ghost" href="<?php echo $url('proje'); ?>">＋ <?php ae_e('p_new_project'); ?></a><?php endif; ?>
      <?php if (!empty($agent['slug'])) : ?><a class="ghost" href="<?php echo esc_url(ec_url_office($agent['slug'])); ?>" target="_blank"><?php ae_e('pr_view_showcase'); ?> ↗</a><?php endif; ?>
      <a class="ghost" href="<?php echo $url('telegram'); ?>">✈ <?php ae_e('p_telegram'); ?></a>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'ilanlar') :
      $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('listings') . ' WHERE agent_id = %d ORDER BY FIELD(status,"rejected","pending","draft","published","sold","passive"), id DESC LIMIT 500', $agent['id']), ARRAY_A);
      $items = ec_listings_present($rows, $lang, true);
      $dupOf = array_column($rows, 'dup_of', 'id');
      $rowById = array_column($rows, null, 'id');
      $credits = function_exists('ec_promo_credits') ? ec_promo_credits((int) $agent['id']) : [];
      $kinds = function_exists('ec_promo_kinds') ? ec_promo_kinds() : []; ?>
    <?php if ($kinds) : ?>
      <div class="note promobar"><b><?php ae_e('pr_credits'); ?>:</b>
        <?php foreach ($kinds as $k => $d) : ?><span class="pcred" data-k="<?php echo esc_attr($k); ?>"><?php echo esc_html($d['icon'] . ' ' . $d['name'][$lang]); ?> <b><?php echo (int) ($credits[$k] ?? 0); ?></b></span><?php endforeach; ?>
        <a href="<?php echo $url('paket'); ?>"><?php ae_e('pr_buy'); ?> →</a>
      </div>
    <?php endif; ?>
    <div class="mylist">
      <?php foreach ($items as $a) : ?>
        <article class="myrow" data-id="<?php echo (int) $a['id']; ?>">
          <div class="mythumb"><?php echo $a['cover'] ? '<img src="' . esc_url($a['cover']['small']) . '" alt="" loading="lazy">' : AE_ICON; ?></div>
          <div class="myinfo">
            <div><?php echo $statusLabel($a['status']); ?> <span class="sub">#<?php echo (int) $a['id']; ?> · <?php echo (int) $a['views']; ?> <?php ae_e('views'); ?></span></div>
            <h3><?php echo esc_html($a['title']); ?></h3>
            <div class="sub"><?php echo esc_html($a['subtype_name'] . ' · ' . trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name'])); ?> · <b><?php echo ae_price($a['price_gbp'], $a['mode'], false); ?></b></div>
            <?php if ($a['status'] === 'rejected' && $a['reject_reason']) : ?><p class="ferr"><?php ae_e('reject_reason'); ?>: <?php echo esc_html($a['reject_reason']); ?></p><?php endif; ?>
            <?php if (!empty($dupOf[$a['id']]) && in_array($a['status'], ['published', 'pending'], true)) : ?><p class="ferr">⚠️ <?php ae_e('dup_badge', (int) $dupOf[$a['id']]); ?> <a href="<?php echo $url('ilan', ['id' => $a['id']]); ?>"><?php ae_e('act_edit'); ?> →</a></p><?php endif; ?>
            <?php $syn = ec_syndication_for((int) $a['id']); if ($syn) : ?><div class="synd"><?php foreach ($syn as $site => $sy) : ?><a class="st st-<?php echo $sy['status'] === 'published' ? 'published' : 'draft'; ?>" <?php echo $sy['remote_url'] ? 'href="' . esc_url($sy['remote_url']) . '" target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($site); ?> <?php echo $sy['status'] === 'published' ? '✓' : '…'; ?></a><?php endforeach; ?></div><?php endif; ?>
          </div>
          <div class="myact">
            <a class="ghost sm" href="<?php echo $url('ilan', ['id' => $a['id']]); ?>"><?php ae_e('act_edit'); ?></a>
            <a class="ghost sm" href="<?php echo esc_url($a['url']); ?>" target="_blank"><?php ae_e('act_view'); ?></a>
            <?php if ($a['status'] === 'published') : ?>
              <?php if ($canSocial) : ?><button class="ghost sm" data-share="<?php echo (int) $a['id']; ?>">📣 <?php ae_e('act_share'); ?></button><?php endif; ?>
              <?php if ($kinds) : $st = ec_promo_state($rowById[$a['id']] ?? []); ?>
                <details class="promo">
                  <summary class="btn sm gold">✦ <?php ae_e('pr_showcase'); ?><?php if ($st) : ?> <span class="count"><?php echo count($st); ?></span><?php endif; ?></summary>
                  <div class="promo-menu">
                    <?php foreach ($kinds as $k => $d) : $on = $st[$k] ?? null; ?>
                      <button type="button" class="promo-it" data-promo="<?php echo esc_attr($k); ?>" data-id="<?php echo (int) $a['id']; ?>" <?php disabled(empty($credits[$k])); ?>>
                        <span><?php echo esc_html($d['icon'] . ' ' . $d['name'][$lang]); ?></span>
                        <small><?php echo $on ? esc_html(ae_t('pr_until', wp_date('d.m.Y', strtotime($on . ' UTC')))) : esc_html(ae_t('pr_left', (int) ($credits[$k] ?? 0))); ?></small>
                      </button>
                    <?php endforeach; ?>
                    <a class="sub" href="<?php echo $url('paket'); ?>"><?php ae_e('pr_buy'); ?> →</a>
                  </div>
                </details>
              <?php endif; ?>
              <button class="ghost sm" data-status="sold"><?php ae_e('act_sold'); ?></button>
              <button class="ghost sm" data-status="passive"><?php ae_e('act_passive'); ?></button>
            <?php elseif (in_array($a['status'], ['draft', 'rejected', 'sold', 'passive'], true)) : ?>
              <button class="btn sm" data-status="published"><?php ae_e('act_republish'); ?></button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$items) : ?><p class="empty"><?php ae_e('p_empty'); ?> <a href="<?php echo $url('ilan'); ?>"><?php ae_e('p_new_listing'); ?> →</a></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'ilan') : include __DIR__ . '/parts/listing-form.php'; endif; ?>

  <?php if ($tab === 'talepler') : $leads = ec_leads_for_agent((int) $agent['id']); ?>
    <div class="mylist leads">
      <?php foreach ($leads as $ld) : ?>
        <article class="myrow lead st-<?php echo esc_attr($ld['status']); ?>" data-lead="<?php echo (int) $ld['id']; ?>">
          <div class="myinfo">
            <div><?php echo $ld['status'] === 'new' ? '<span class="st st-pending">' . esc_html(ae_t('lead_new')) . '</span>' : ''; ?> <span class="sub"><?php echo esc_html(get_date_from_gmt($ld['created_at'], 'd.m.Y H:i')); ?> · <?php echo esc_html(strtoupper($ld['lang'])); ?></span></div>
            <h3><?php echo esc_html($ld['name']); ?></h3>
            <div class="sub"><?php if ($ld['phone']) : ?><a href="tel:<?php echo esc_attr($ld['phone']); ?>">📞 <?php echo esc_html($ld['phone']); ?></a> · <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $ld['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?><?php if ($ld['email']) : ?> · <a href="mailto:<?php echo esc_attr($ld['email']); ?>">✉ <?php echo esc_html($ld['email']); ?></a><?php endif; ?></div>
            <?php if ($ld['about']) : ?><div class="sub"><a href="<?php echo esc_url($ld['about']['url']); ?>" target="_blank"><?php echo esc_html(($ld['about']['type'] === 'listing' ? '#' . $ld['about']['id'] . ' ' : '') . $ld['about']['title']); ?></a></div><?php endif; ?>
            <p class="rvdesc"><?php echo nl2br(esc_html((string) $ld['message'])); ?></p>
          </div>
          <div class="myact">
            <?php if ($ld['status'] === 'new') : ?><button class="ghost sm" data-lead-st="read"><?php ae_e('lead_mark_read'); ?></button><?php endif; ?>
            <?php if ($ld['status'] !== 'done') : ?><button class="ghost sm" data-lead-st="done">✓ <?php ae_e('lead_done'); ?></button><?php endif; ?>
            <button class="ghost sm" data-lead-st="spam"><?php ae_e('lead_spam'); ?></button>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$leads) : ?><p class="empty"><?php ae_e('lead_empty'); ?></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'projeler' && $canProjects) :
      $prows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ec_table('projects') . ' WHERE agent_id = %d ORDER BY id DESC', $agent['id']), ARRAY_A); ?>
    <div class="mylist">
      <?php foreach ($prows as $pr) : $p = ec_project_present($pr, $lang); ?>
        <article class="myrow">
          <div class="mythumb"><?php echo $p['cover'] ? '<img src="' . esc_url($p['cover']['small']) . '" alt="" loading="lazy">' : AE_ICON; ?></div>
          <div class="myinfo"><div><?php echo $statusLabel($p['status']); ?></div><h3><?php echo esc_html($p['name']); ?></h3><div class="sub"><?php echo esc_html($p['city_name'] . ($p['district'] ? ' / ' . $p['district'] : '')); ?></div></div>
          <div class="myact"><a class="ghost sm" href="<?php echo $url('proje', ['id' => $p['id']]); ?>"><?php ae_e('act_edit'); ?></a><a class="ghost sm" href="<?php echo esc_url($p['url']); ?>" target="_blank"><?php ae_e('act_view'); ?></a></div>
        </article>
      <?php endforeach; ?>
      <?php if (!$prows) : ?><p class="empty"><a href="<?php echo $url('proje'); ?>">＋ <?php ae_e('p_new_project'); ?></a></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'proje') : ?>
    <?php if ($canProjects) : include __DIR__ . '/parts/project-form.php'; else : ?><p class="note"><?php ae_e('pf_only_company'); ?></p><?php endif; ?>
  <?php endif; ?>

  <?php if ($tab === 'ekip' && function_exists('ec_team_can_lead') && ec_team_can_lead($agent)) : $tm = ec_team_members((int) $agent['id']); $tp = ec_team_pending((int) $agent['id']); ?>
    <div class="panel">
      <h2><?php ae_e('team_tab'); ?></h2>
      <p class="sub"><?php ae_e('team_sub'); ?></p>
      <form class="teaminv" id="teaminv">
        <label class="vh" for="tm-email"><?php ae_e('email'); ?></label>
        <input id="tm-email" type="email" required placeholder="<?php ae_e('team_email_ph'); ?>">
        <button class="btn" type="submit"><?php ae_e('team_invite'); ?></button>
      </form>
      <p class="formmsg" id="teammsg" role="status"></p>
      <?php if ($tm) : ?>
        <table class="ordtable"><tbody>
          <?php foreach ($tm as $m) : ?>
            <tr><td><b><?php echo esc_html($m['name']); ?></b><br><span class="sub"><?php echo esc_html($m['email']); ?></span></td><td><?php ae_e('team_live', (int) $m['live']); ?></td>
              <td><button type="button" class="ghost sm" data-team="remove" data-member="<?php echo (int) $m['id']; ?>" data-confirm="<?php echo esc_attr(ae_t('team_remove_q', $m['name'])); ?>"><?php ae_e('team_remove'); ?></button></td></tr>
          <?php endforeach; ?>
        </tbody></table>
      <?php else : ?><p class="note"><?php ae_e('team_empty'); ?></p><?php endif; ?>
      <?php if ($tp) : ?>
        <h3><?php ae_e('team_pending'); ?></h3>
        <ul class="pklist"><?php foreach ($tp as $m) : ?><li><?php echo esc_html($m['name'] . ' · ' . $m['email']); ?> <button type="button" class="linkbtn" data-team="cancel" data-member="<?php echo (int) $m['id']; ?>"><?php ae_e('team_cancel'); ?></button></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <p class="sub"><?php ae_e('team_note'); ?></p>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'paket') : $subs = ec_subscriptions_summary((int) $agent['id']); $fn = ['syndication' => ae_t('pk_f_syndication'), 'social' => ae_t('pk_f_social'), 'telegram' => ae_t('pk_f_telegram')]; ?>
    <div class="panel">
      <h2><?php ae_e('p_package'); ?></h2>
      <?php foreach ($subs as $s) : ?>
        <div class="pkcard">
          <h3><?php echo esc_html($s['name']); ?></h3>
          <p class="sub"><?php echo esc_html(implode(' · ', array_map(fn($f) => $fn[$f] ?? $f, $s['features']))); ?></p>
          <?php if ($s['quota']) : ?>
            <div class="pbar"><span style="width:<?php echo (int) min(100, $s['used'] / max(1, $s['quota']) * 100); ?>%"></span></div>
            <p><?php ae_e('pk_used', $s['used'], $s['quota']); ?></p>
          <?php else : ?><p><?php ae_e('pk_unlimited', $s['used']); ?></p><?php endif; ?>
          <?php $pl = array_filter($s['promos'] ?? [], fn($x) => $x['total'] > 0); if ($pl) : ?>
            <p class="sub">✦ <?php ae_e('pr_showcase'); ?> (<?php ae_e('pr_days', (int) $s['promo_days']); ?>): <?php echo esc_html(implode(' · ', array_map(fn($k, $x) => ec_promo_name($k, $lang) . ' ' . $x['left'] . '/' . $x['total'], array_keys($pl), $pl))); ?></p>
          <?php endif; ?>
          <p class="sub"><?php ae_e('pk_ends', wp_date('d.m.Y', strtotime($s['ends_at'] . ' UTC')), $s['days_left']); ?></p>
        </div>
      <?php endforeach; ?>
      <?php if (!$subs) : ?><p class="note"><?php ae_e('pk_none'); ?></p><?php endif; ?>
      <?php if (isset($_GET['odeme'])) : ?><p class="note <?php echo $_GET['odeme'] === 'ok' ? 'ok' : 'warn'; ?>"><?php echo $_GET['odeme'] === 'ok' ? esc_html(ae_t('pay_ok', sanitize_text_field($_GET['sip'] ?? ''))) : esc_html(ae_t('pay_fail') . (isset($_GET['mesaj']) ? ' (' . sanitize_text_field(wp_unslash($_GET['mesaj'])) . ')' : '')); ?></p><?php endif; ?>
      <?php $methods = function_exists('ec_payment_methods') ? ec_payment_methods() : []; $buyable = array_filter(ec_packages(true), fn($pk) => (float) ($pk['amount'] ?? 0) > 0); ?>
      <?php if ($methods && $buyable) : ?>
        <h3><?php ae_e('pay_title'); ?></h3>
        <div class="buygrid" id="buygrid" data-methods="<?php echo esc_attr(implode(',', $methods)); ?>">
          <?php foreach ($buyable as $pk) : $pr = ec_promos_parse($pk['promos'] ?? ''); ?>
            <div class="buycard">
              <b><?php echo esc_html($pk['name']); ?></b>
              <span class="sub"><?php echo (int) $pk['days']; ?> <?php ae_e('pk_days'); ?><?php if ($pk['quota']) : ?> · <?php echo (int) $pk['quota']; ?> <?php ae_e('pk_listings'); ?><?php endif; ?></span>
              <?php if ($pr) : ?><span class="sub">✦ <?php echo esc_html(implode(' · ', array_map(fn($k, $n) => $n . '× ' . ec_promo_name($k, $lang), array_keys($pr), $pr))); ?></span><?php endif; ?>
              <span class="price"><?php echo esc_html(number_format((float) $pk['amount'], 2, ',', '.') . ' ' . $pk['currency']); ?></span>
              <div class="row">
                <?php if (in_array('card', $methods, true)) : ?><button type="button" class="btn sm" data-buy="<?php echo (int) $pk['id']; ?>" data-method="card">💳 <?php ae_e('pay_card'); ?></button><?php endif; ?>
                <?php if (in_array('bank', $methods, true)) : ?><button type="button" class="ghost sm" data-buy="<?php echo (int) $pk['id']; ?>" data-method="bank">🏦 <?php ae_e('pay_bank'); ?></button><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="bankbox panel" id="bankbox" hidden></div>
        <?php $orders = ec_orders_for((int) $agent['id']); if ($orders) : ?>
          <h3><?php ae_e('pay_orders'); ?></h3>
          <table class="ordtable"><tbody>
            <?php foreach ($orders as $o) : ?>
              <tr><td><code><?php echo esc_html($o['ref']); ?></code></td><td><?php echo esc_html((string) $o['package']); ?></td><td><?php echo esc_html(number_format($o['amount'], 2, ',', '.') . ' ' . $o['currency']); ?></td><td><?php ae_e('pay_st_' . $o['status']); ?></td>
                <td><?php if ($o['method'] === 'bank' && in_array($o['status'], ['pending', 'review'], true)) : ?><label class="ghost sm filebtn">📎 <?php ae_e($o['receipt'] ? 'pay_receipt_again' : 'pay_receipt'); ?><input type="file" accept="image/*,application/pdf" data-receipt="<?php echo (int) $o['id']; ?>" hidden></label><?php endif; ?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!($methods && $buyable)) : ?>
      <h3><?php ae_e('pk_available'); ?></h3>
      <ul class="pklist"><?php foreach (ec_packages(true) as $pk) : ?><li><b><?php echo esc_html($pk['name']); ?></b> <span class="sub">· <?php echo (int) $pk['days']; ?> <?php ae_e('pk_days'); ?><?php if ($pk['quota']) : ?> · <?php echo (int) $pk['quota']; ?> <?php ae_e('pk_listings'); ?><?php endif; ?></span><?php if ($pk['price']) : ?> <b class="price"><?php echo esc_html($pk['price']); ?></b><?php endif; ?></li><?php endforeach; ?></ul>
      <?php $cw = preg_replace('/\D/', '', (string) (get_option('ec_contact')['whatsapp'] ?? '')); if ($cw) : ?><p><a class="wa" href="https://wa.me/<?php echo esc_attr($cw); ?>?text=<?php echo esc_attr(rawurlencode(ae_t('pk_wa', $agent['name']))); ?>" target="_blank" rel="noopener"><?php ae_e('pk_buy'); ?></a></p><?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'sosyal') : $accs = ec_social_accounts((int) $agent['id']); $recent = ec_social_recent((int) $agent['id'], 20); ?>
    <div class="panel form" id="socialbox">
      <h2><?php ae_e('p_social'); ?></h2>
      <?php if (!$canSocial) : ?>
        <p class="note"><?php ae_e('so_locked'); ?> <a href="<?php echo $url('paket'); ?>"><?php ae_e('p_package'); ?> →</a></p>
      <?php endif; ?>
      <h3><?php ae_e('so_accounts'); ?></h3>
      <?php if ($accs) : ?>
        <ul class="soacc"><?php foreach ($accs as $ac) : ?><li><?php echo $ac['status'] === 'ok' ? '✅' : '⛔'; ?> <b>Facebook:</b> <?php echo esc_html($ac['page_name']); ?><?php if ($ac['ig_username']) : ?> · <b>Instagram:</b> @<?php echo esc_html($ac['ig_username']); ?><?php endif; ?><?php if ($ac['status'] !== 'ok') : ?> <span class="sub">— <?php ae_e('so_broken'); ?></span><?php endif; ?></li><?php endforeach; ?></ul>
      <?php else : ?>
        <p class="sub"><?php ae_e('so_none'); ?></p>
        <ol class="tgsteps"><li><?php ae_e('so_step1'); ?></li><li><?php ae_e('so_step2'); ?></li><li><?php ae_e('so_step3'); ?></li></ol>
      <?php endif; ?>
      <h3><?php ae_e('so_settings'); ?></h3>
      <label class="chk"><input type="checkbox" name="auto" <?php checked((int) $agent['social_auto'], 1); ?>> <?php ae_e('so_auto'); ?></label>
      <label><?php ae_e('so_lang'); ?>
        <select name="lang"><?php foreach (['tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский', 'tr+en' => 'Türkçe + English', 'all' => 'TR + EN + RU'] as $k => $n) : ?><option value="<?php echo $k; ?>" <?php selected($agent['social_lang'], $k); ?>><?php echo esc_html($n); ?></option><?php endforeach; ?></select></label>
      <label><?php ae_e('so_sign'); ?><input name="sign" maxlength="200" value="<?php echo esc_attr((string) $agent['social_sign']); ?>" placeholder="<?php echo esc_attr($agent['name'] . ($agent['phone'] ? ' · 📞 ' . $agent['phone'] : '')); ?>"></label>
      <p class="formmsg" role="status"></p>
      <button class="btn" type="button" id="sosave"><?php ae_e('pr_save'); ?></button>
      <?php if ($recent) : ?>
        <h3><?php ae_e('so_recent'); ?></h3>
        <ul class="sorecent"><?php foreach ($recent as $r) : ?><li><span class="st st-<?php echo $r['status'] === 'done' ? 'published' : ($r['status'] === 'queued' ? 'pending' : 'rejected'); ?>"><?php echo esc_html(ae_t('so_st_' . $r['status'])); ?></span> #<?php echo (int) $r['listing_id']; ?> · <?php echo $r['network'] === 'instagram' ? 'Instagram' : 'Facebook'; ?> · <span class="sub"><?php echo esc_html(get_date_from_gmt($r['updated_at'], 'd.m H:i')); ?></span><?php if ($r['permalink']) : ?> · <a href="<?php echo esc_url($r['permalink']); ?>" target="_blank" rel="noopener"><?php ae_e('act_view'); ?> ↗</a><?php endif; ?><?php if ($r['error'] && $r['status'] !== 'done') : ?><div class="sub"><?php echo esc_html($r['error']); ?></div><?php endif; ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'profil') :
      $soc = $agent['socials'] ? (array) json_decode($agent['socials'], true) : []; ?>
    <form class="panel form" id="profileform">
      <div class="logorow">
        <div class="logoprev" id="logoprev"><?php echo !empty($agent['logo_path']) ? '<img src="' . esc_url(ec_image_urls($agent['logo_path'])['small']) . '" alt="">' : AE_ICON; ?></div>
        <label class="ghost filebtn"><?php ae_e('pr_logo'); ?><input type="file" id="logofile" accept="image/jpeg,image/png,image/webp" hidden></label>
      </div>
      <div class="fg two">
        <label><?php ae_e(in_array($agent['kind'], ['ofis', 'insaat'], true) ? 'company_name' : 'full_name'); ?><input name="name" required value="<?php echo esc_attr($agent['name']); ?>"></label>
        <?php if (in_array($agent['kind'], ['ofis', 'insaat'], true)) : ?><label><?php ae_e('reg_contact'); ?><input name="contact_person" value="<?php echo esc_attr((string) $agent['contact_person']); ?>"></label><?php endif; ?>
        <label><?php ae_e('reg_phone'); ?><input name="phone" type="tel" value="<?php echo esc_attr((string) $agent['phone']); ?>"></label>
        <label>WhatsApp<input name="whatsapp" type="tel" value="<?php echo esc_attr((string) $agent['whatsapp']); ?>"></label>
        <label><?php ae_e('city'); ?><select name="city"><option value=""></option><?php foreach (ec_cities() as $ck => $c) : ?><option value="<?php echo esc_attr($ck); ?>" <?php selected($agent['city'], $ck); ?>><?php echo esc_html($c['name'][$lang]); ?></option><?php endforeach; ?></select></label>
        <label><?php ae_e('pr_address'); ?><input name="address" value="<?php echo esc_attr((string) $agent['address']); ?>"></label>
        <label><?php ae_e('pr_website'); ?><input name="website" type="url" placeholder="https://" value="<?php echo esc_attr((string) $agent['website']); ?>"></label>
        <label><?php ae_e('pr_license'); ?><input name="license_no" value="<?php echo esc_attr((string) $agent['license_no']); ?>"></label>
        <label class="wide"><?php ae_e('pr_bio'); ?><textarea name="bio" rows="4"><?php echo esc_textarea((string) $agent['bio']); ?></textarea></label>
        <label class="wide"><?php ae_e('pr_slug'); ?>
          <span class="slugrow"><span class="sub"><?php echo esc_html(get_option('ec_subdomains') ? '' : trailingslashit(home_url('/' . $lang . '/' . ec_sections()['office'][$lang]))); ?></span><input name="slug" pattern="[a-z0-9-]{2,40}" value="<?php echo esc_attr((string) $agent['slug']); ?>"><span class="sub"><?php echo esc_html(get_option('ec_subdomains') ? '.' . preg_replace('/^www\./', '', (string) wp_parse_url(home_url('/'), PHP_URL_HOST)) : '/'); ?></span></span></label>
      </div>
      <h3><?php ae_e('pr_socials'); ?></h3>
      <div class="fg two">
        <?php foreach (ec_social_networks() as $k => [$n, $ph]) : ?>
          <label><?php echo esc_html($n); ?><input name="socials[<?php echo esc_attr($k); ?>]" placeholder="<?php echo esc_attr($ph); ?>" value="<?php echo esc_attr($soc[$k] ?? ''); ?>"></label>
        <?php endforeach; ?>
      </div>
      <p class="formmsg" role="status"></p>
      <div class="row"><button class="btn" type="submit"><?php ae_e('pr_save'); ?></button><?php if (!empty($agent['slug'])) : ?><a class="ghost" target="_blank" href="<?php echo esc_url(ec_url_office($agent['slug'])); ?>"><?php ae_e('pr_view_showcase'); ?> ↗</a><?php endif; ?></div>
    </form>
  <?php endif; ?>

  <?php if ($tab === 'telegram') :
      $bot = (string) (get_option('ec_telegram')['bot'] ?? ''); ?>
    <div class="panel form" id="tgbox" data-bot="<?php echo esc_attr($bot); ?>">
      <h2><?php ae_e('tg_title'); ?></h2>
      <p><?php ae_e('tg_how'); ?></p>
      <fieldset class="tgq">
        <legend><?php ae_e('tg_question'); ?></legend>
        <label class="chk"><input type="radio" name="telegram_opt_in" value="1" <?php checked((int) $agent['telegram_opt_in'], 1); ?>> <?php ae_e('yes'); ?></label>
        <label class="chk"><input type="radio" name="telegram_opt_in" value="0" <?php checked((int) $agent['telegram_opt_in'], 0); ?>> <?php ae_e('no'); ?></label>
      </fieldset>
      <div id="tgmore" <?php echo $agent['telegram_opt_in'] ? '' : 'hidden'; ?>>
        <?php if ($agent['telegram_chat_id']) : ?>
          <p class="okmsg"><?php ae_e('tg_connected', $agent['telegram_username'] ? '@' . $agent['telegram_username'] : '✔'); ?></p>
          <ol class="tgsteps"><li><?php ae_e('tg_step1'); ?></li><li><?php ae_e('tg_step2'); ?></li><li><?php ae_e('tg_step3'); ?></li></ol>
          <?php if ($bot) : ?><p><a class="btn" href="https://t.me/<?php echo esc_attr($bot); ?>" target="_blank" rel="noopener">✈ @<?php echo esc_html($bot); ?></a> <button class="ghost" type="button" id="tgrelink"><?php ae_e('tg_relink'); ?></button></p><p class="sub" id="tgout"></p><?php endif; ?>
        <?php elseif ($bot) : ?>
          <button class="btn" type="button" id="tgconnect">✈ <?php ae_e('tg_connect'); ?></button>
          <p class="sub" id="tgout"></p>
        <?php else : ?>
          <p class="note"><?php ae_e('tg_bot_soon'); ?></p>
        <?php endif; ?>
      </div>
      <p class="formmsg" role="status"></p>
    </div>
  <?php endif; ?>
</main>
<?php
get_footer();
