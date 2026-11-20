<?php
if (!defined('ABSPATH')) {
    exit;
}
status_header(404);
ae_seo(['title' => '404']);
get_header();
?>
<main id="main" class="main" style="text-align:center;padding:80px 16px">
  <h1 class="sec">404</h1>
  <p><?php ae_e('not_found'); ?></p>
  <p><a class="btn" href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a></p>
</main>
<?php
get_footer();
