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
$packages = [
    ['pkg_single', 2, 1, false],
    ['pkg_weekly', 20, 30, false],
    ['pkg_monthly', 50, 100, true],
];
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php ae_e('add_title'); ?></h1><p><?php ae_e('add_sub'); ?></p></div></div>
<main id="main" class="main">
  <ol class="steps">
    <li><b>1</b><span><?php ae_e('add_step1'); ?></span><button class="btn" type="button" data-open="regov"><?php ae_e('register'); ?></button></li>
    <li><b>2</b><span><?php ae_e('add_step2'); ?></span>
      <?php if ($bot) : ?><a class="btn" href="https://t.me/<?php echo esc_attr($bot); ?>" target="_blank" rel="noopener">Telegram</a><?php else : ?><span class="pill"><?php ae_e('soon'); ?></span><?php endif; ?>
    </li>
    <li><b>3</b><span><?php ae_e('add_step3'); ?></span></li>
  </ol>

  <h2 class="sec" id="paketler"><?php ae_e('packages'); ?></h2>
  <p class="lead"><?php ae_e('packages_sub'); ?></p>
  <div class="pkgs">
    <?php foreach ($packages as [$k, $price, $n, $hl]) : ?>
      <div class="pkg<?php echo $hl ? ' hl' : ''; ?>">
        <h3><?php ae_e($k); ?></h3>
        <div class="price big">€<?php echo (int) $price; ?></div>
        <div class="sub"><?php ae_e('pkg_n', $n); ?></div>
        <span class="pill"><?php ae_e('soon'); ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($wa) : ?><p style="text-align:center;margin-top:24px"><a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener"><?php ae_e('contact_us'); ?> (WhatsApp)</a></p><?php endif; ?>
</main>
<?php
get_footer();
