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
<meta name="google" content="notranslate">
<meta name="theme-color" content="#14284b">
<?php wp_head(); ?>
</head>
<body <?php body_class('lang-' . $lang); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main"><?php echo esc_html(ae_t('listings')); ?></a>
<header class="site-header"><div class="bar">
  <?php ae_logo(); ?>
  <div class="tools" id="tools">
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
    <?php if (is_user_logged_in()) : ?>
      <a class="ghost" href="<?php echo esc_url(current_user_can('manage_options') && !ec_current_agent() ? ec_url_section('review') : ec_url_section('panel')); ?>"><?php ae_e(current_user_can('manage_options') && !ec_current_agent() ? 'rv_title' : 'my_panel'); ?></a>
      <?php if (current_user_can('manage_options') && ec_current_agent()) : ?><a class="ghost" href="<?php echo esc_url(ec_url_section('review')); ?>"><?php ae_e('rv_title'); ?></a><?php endif; ?>
    <?php else : ?>
      <a class="ghost" href="<?php echo esc_url(ec_url_section('login')); ?>"><?php ae_e('login'); ?></a>
    <?php endif; ?>
    <button class="ghost pwa-btn" type="button" id="pwa-install" hidden data-ios="<?php ae_e('pwa_ios'); ?>">⬇ <?php ae_e('pwa_install'); ?></button>
  </div>
  <a class="btn addbtn" href="<?php echo esc_url(ec_current_agent() ? add_query_arg('bolum', 'ilan', ec_url_section('panel')) : ec_url_section('add')); ?>"><?php ae_e('add_listing'); ?></a>
  <button class="burger" type="button" aria-controls="tools" aria-expanded="false" aria-label="<?php ae_e('open_menu'); ?>"><span></span><span></span><span></span></button>
</div>
<nav class="mainnav" aria-label="<?php ae_e('menu'); ?>"><div class="navin">
  <?php foreach (ae_nav_items() as [$url, $label, $on]) : ?>
    <a href="<?php echo esc_url($url); ?>" class="<?php echo $on ? 'on' : ''; ?>" <?php echo $on ? 'aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
  <?php endforeach; ?>
  <?php echo ae_social_icons((array) get_option('ec_social', []), 'socials navsocial'); ?>
</div></nav>
</header>
