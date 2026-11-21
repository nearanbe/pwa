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
    echo '<main id="main" class="main auth"><div class="panel authbox"><p>' . (current_user_can('manage_options') ? '<a class="btn wide" href="' . esc_url(ec_url_section('review')) . '">' . esc_html(ae_t('rv_title')) . '</a>' : esc_html(ae_t('not_found'))) . '</p></div></main>';
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
$tabs += ['profil' => 'p_profile', 'telegram' => 'p_telegram'];
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
        'pendingAfter' => ae_t('f_status_pending_after'), 'liveAfter' => ae_t('f_status_live_after'), 'uploading' => ae_t('f_uploading'),
        'below' => ae_t('idx_compare_below', '%c', '%p'), 'above' => ae_t('idx_compare_above', '%c', '%p'), 'equal' => ae_t('idx_compare_equal', '%c'),
        'perMonth' => ae_t('per_month'), 'perNight' => ae_t('per_night'), 'confirm' => ae_t('act_delete_confirm'),
        'calc' => ae_t('pf_calc', '%1', '%2', '%3'), 'saveFirst' => ae_t('pf_save_first'), 'tgSoon' => ae_t('tg_bot_soon'), 'tgHint' => ae_t('tg_code_hint'),
        'mode' => ['sale' => ae_t('sale'), 'rent' => ae_t('rent'), 'daily' => ae_t('daily')],
        'cover' => ae_t('pf_cover'),
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
      <a href="<?php echo $url($t); ?>" class="<?php echo $t === $tab ? 'on' : ''; ?>"><?php ae_e($t === 'ilan' && $editListing ? 'p_edit_listing' : ($t === 'proje' && $editProject ? 'p_edit_project' : $label)); ?></a>
    <?php endforeach; ?>
  </nav>

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
      $items = ec_listings_present($rows, $lang, true); ?>
    <div class="mylist">
      <?php foreach ($items as $a) : ?>
        <article class="myrow" data-id="<?php echo (int) $a['id']; ?>">
          <div class="mythumb"><?php echo $a['cover'] ? '<img src="' . esc_url($a['cover']['small']) . '" alt="" loading="lazy">' : AE_ICON; ?></div>
          <div class="myinfo">
            <div><?php echo $statusLabel($a['status']); ?> <span class="sub">#<?php echo (int) $a['id']; ?> · <?php echo (int) $a['views']; ?> <?php ae_e('views'); ?></span></div>
            <h3><?php echo esc_html($a['title']); ?></h3>
            <div class="sub"><?php echo esc_html($a['subtype_name'] . ' · ' . trim(($a['district'] ? $a['district'] . ', ' : '') . $a['city_name'])); ?> · <b><?php echo ae_price($a['price_gbp'], $a['mode'], false); ?></b></div>
            <?php if ($a['status'] === 'rejected' && $a['reject_reason']) : ?><p class="ferr"><?php ae_e('reject_reason'); ?>: <?php echo esc_html($a['reject_reason']); ?></p><?php endif; ?>
          </div>
          <div class="myact">
            <a class="ghost sm" href="<?php echo $url('ilan', ['id' => $a['id']]); ?>"><?php ae_e('act_edit'); ?></a>
            <a class="ghost sm" href="<?php echo esc_url($a['url']); ?>" target="_blank"><?php ae_e('act_view'); ?></a>
            <?php if ($a['status'] === 'published') : ?>
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
