<?php
/** İlan ver: nasıl çalışır + otomasyon paketleri */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('add', $l);
}
ae_seo(['title' => ae_t('add_title'), 'description' => ae_t('add_sub'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
$contact = get_option('ec_contact', []);
$bot = $contact['telegram_bot'] ?? '';
$wa = $contact['whatsapp'] ?? '';
$packages = function_exists('ec_packages') ? ec_packages(true) : [];
$fn = ['syndication' => ae_t('pk_f_syndication'), 'social' => ae_t('pk_f_social'), 'telegram' => ae_t('pk_f_telegram')];
$best = $packages ? max(array_map(fn($p) => (int) $p['quota'], $packages)) : 0;
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php ae_e('add_title'); ?></h1><p><?php ae_e('add_sub'); ?></p></div></div>
<main id="main" class="main">
  <ol class="steps">
    <li><b>1</b><span><?php ae_e('add_step1'); ?></span><a class="btn" href="<?php echo esc_url(ec_url_section('register')); ?>"><?php ae_e('register'); ?></a></li>
    <li><b>2</b><span><?php ae_e('add_step2'); ?></span>
      <?php if ($bot) : ?><a class="btn" href="https://t.me/<?php echo esc_attr($bot); ?>" target="_blank" rel="noopener">✈ Telegram</a><?php else : ?><a class="ghost" href="<?php echo esc_url(ec_url_section('panel')); ?>"><?php ae_e('my_panel'); ?></a><?php endif; ?>
    </li>
    <li><b>3</b><span><?php ae_e('add_step3'); ?></span></li>
  </ol>

  <h2 class="sec" id="paketler"><?php ae_e('packages'); ?></h2>
  <p class="lead"><?php ae_e('packages_sub'); ?></p>
  <div class="pkgs">
    <?php foreach ($packages as $pk) : $feats = array_filter(explode(',', $pk['features'])); ?>
      <div class="pkg<?php echo (int) $pk['quota'] === $best && $best > 1 ? ' hl' : ''; ?>">
        <h3><?php echo esc_html($pk['name']); ?></h3>
        <?php if ($pk['price']) : ?><div class="price big"><?php echo esc_html($pk['price']); ?></div><?php endif; ?>
        <div class="sub"><?php echo (int) $pk['days']; ?> <?php ae_e('pk_days'); ?><?php echo (int) $pk['quota'] ? ' · ' . (int) $pk['quota'] . ' ' . esc_html(ae_t('pk_listings')) : ''; ?></div>
        <ul class="pkfeat"><?php foreach ($feats as $f) : ?><li><?php echo esc_html($fn[$f] ?? $f); ?></li><?php endforeach; ?></ul>
        <?php if ($wa) : ?><a class="ghost sm" href="https://wa.me/<?php echo esc_attr($wa); ?>?text=<?php echo esc_attr(rawurlencode(ae_t('pk_want', $pk['name']))); ?>" target="_blank" rel="noopener"><?php ae_e('pk_buy'); ?></a><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="center"><a class="ghost" href="<?php echo esc_url(ec_url_section('faq') . '#agents'); ?>"><?php ae_e('faq_title'); ?> →</a></p>
  <?php if ($wa) : ?><p style="text-align:center;margin-top:24px"><a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener"><?php ae_e('contact_us'); ?> (WhatsApp)</a></p><?php endif; ?>
</main>
<?php
get_footer();
