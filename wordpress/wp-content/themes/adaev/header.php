<?php
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$cur = ae_currency();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#16343b">
<?php wp_head(); ?>
</head>
<body <?php body_class('lang-' . $lang); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main"><?php echo esc_html(ae_t('listings')); ?></a>
<header class="site-header"><div class="bar">
  <a class="logo" href="<?php echo esc_url(ec_url_home()); ?>" aria-label="<?php echo esc_attr(ae_brand()); ?>">
    <svg width="30" height="30" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 28V15a11 11 0 0 1 22 0v13"/><path d="M10 24q3-3 6 0t6 0" stroke="#b48a45"/></svg><?php echo esc_html(ae_brand()); ?>
  </a>
  <form class="ino" id="ino" role="search">
    <label class="vh" for="ino-in"><?php ae_e('listing_no'); ?></label>
    <input id="ino-in" placeholder="<?php ae_e('listing_no'); ?>" inputmode="numeric" autocomplete="off">
  </form>
  <label class="vh" for="cur"><?php echo esc_html('GBP / EUR / USD / TRY'); ?></label>
  <select id="cur" class="mini">
    <?php foreach (EC_CURRENCIES as $c) : ?><option <?php selected($cur, $c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?>
  </select>
  <nav class="langs" aria-label="Language">
    <?php foreach (ae_lang_links() as $l => $u) : ?>
      <a href="<?php echo esc_url($u); ?>" hreflang="<?php echo esc_attr($l); ?>" class="<?php echo $l === $lang ? 'on' : ''; ?>" <?php echo $l === $lang ? 'aria-current="true"' : ''; ?>><?php echo esc_html(strtoupper($l)); ?></a>
    <?php endforeach; ?>
  </nav>
  <a class="ghost" href="<?php echo esc_url(ec_url_section('favs')); ?>">♥ <span class="hide-s"><?php ae_e('favourites'); ?></span></a>
  <button class="ghost" type="button" data-open="regov"><?php ae_e('register'); ?></button>
  <a class="btn" href="<?php echo esc_url(ec_url_section('add')); ?>"><?php ae_e('add_listing'); ?></a>
</div></header>
