<?php
/** Sabit sayfalar (Hakkımızda, İletişim, Gizlilik ...) */
if (!defined('ABSPATH')) {
    exit;
}
the_post();
ae_seo(['title' => get_the_title(), 'canonical' => get_permalink()]);
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php the_title(); ?></h1></div></div>
<main id="main" class="main article">
  <article <?php post_class('panel prose'); ?>><div class="entry"><?php the_content(); ?></div></article>
</main>
<?php
get_footer();
