<?php
/** E-posta doğrulama: /tr/dogrula/?u=..&k=.. */
if (!defined('ABSPATH')) {
    exit;
}
$res = ec_member_verify((int) ($_GET['u'] ?? 0), (string) ($_GET['k'] ?? ''));
$ok = !is_wp_error($res);
$review = $ok && $res['status'] === 'pending';
ae_seo(['title' => ae_t('login_title')]);
add_action('wp_head', fn() => print('<meta name="robots" content="noindex">' . "\n"));
get_header();
?>
<main id="main" class="main auth">
  <div class="panel authbox">
    <h1><?php echo $ok ? '✅' : '⚠️'; ?></h1>
    <p class="<?php echo $ok ? 'okmsg' : 'errmsg'; ?>"><?php ae_e($ok ? ($review ? 'verify_ok_review' : 'verify_ok') : 'verify_bad'); ?></p>
    <p><a class="btn wide" href="<?php echo esc_url(ec_url_section('login')); ?>"><?php ae_e('login'); ?></a></p>
  </div>
</main>
<?php
get_footer();
