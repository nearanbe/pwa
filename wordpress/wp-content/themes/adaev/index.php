<?php
/** Standart WordPress içerikleri (Hakkımızda, blog yazıları vb.) */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<main id="main" class="main prose">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article <?php post_class('panel'); ?>>
      <h1><?php the_title(); ?></h1>
      <?php if (!is_page()) : ?><div class="sub"><?php echo esc_html(get_the_date()); ?></div><?php endif; ?>
      <div class="desc"><?php is_singular() ? the_content() : the_excerpt(); ?></div>
      <?php if (!is_singular()) : ?><a href="<?php the_permalink(); ?>">→</a><?php endif; ?>
    </article>
  <?php endwhile; the_posts_pagination(); else : ?>
    <p class="empty"><?php ae_e('not_found'); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
