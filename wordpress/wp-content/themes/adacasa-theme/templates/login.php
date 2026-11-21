<?php
/** Giriş: /tr/giris/ */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$st = ec_form_state();
$err = $st['errors']['form'] ?? ($st['errors']['captcha'] ?? '');
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('login', $l);
}
ae_seo(['title' => ae_t('login_title'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
add_action('wp_head', fn() => print('<meta name="robots" content="noindex">' . "\n"));
get_header();
?>
<main id="main" class="main auth">
  <div class="panel authbox">
    <h1><?php ae_e('login_title'); ?></h1>
    <?php if (($_GET['kayit'] ?? '') === 'ok') : ?><p class="okmsg" role="status"><?php ae_e('reg_done'); ?></p><?php endif; ?>
    <?php if ($st['notice'] === 'resent') : ?><p class="okmsg" role="status"><?php ae_e('resent'); ?></p><?php endif; ?>
    <?php if ($err) : ?>
      <p class="errmsg" role="alert"><?php ae_e($err === 'captcha' ? 'err_captcha' : 'err_' . str_replace('ec_', '', $err)); ?></p>
    <?php endif; ?>
    <form method="post" class="form">
      <?php wp_nonce_field('ec_login', '_ecnonce'); ?>
      <label><?php ae_e('login_id'); ?><input name="email" type="text" inputmode="email" autocapitalize="none" spellcheck="false" autocomplete="username" required value="<?php echo esc_attr($st['old']['email'] ?? ''); ?>"></label>
      <label><?php ae_e('password'); ?><input name="password" type="password" autocomplete="current-password" required></label>
      <label class="chk"><input type="checkbox" name="remember" value="1" checked> <?php ae_e('remember'); ?></label>
      <?php echo ae_turnstile(); ?>
      <button class="btn wide" type="submit"><?php ae_e('login'); ?></button>
    </form>
    <p class="authlinks"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php ae_e('forgot'); ?></a></p>
    <?php if ($err === 'ec_unverified') : ?>
      <form method="post" class="form">
        <?php wp_nonce_field('ec_login', '_ecnonce'); ?>
        <input type="hidden" name="do" value="resend"><input type="hidden" name="email" value="<?php echo esc_attr($st['old']['email'] ?? ''); ?>">
        <button class="ghost wide" type="submit"><?php ae_e('resend'); ?></button>
      </form>
    <?php endif; ?>
    <hr><p class="authlinks"><?php ae_e('no_account'); ?> <a href="<?php echo esc_url(ec_url_section('register')); ?>"><b><?php ae_e('create_account'); ?></b></a></p>
  </div>
</main>
<?php
get_footer();
