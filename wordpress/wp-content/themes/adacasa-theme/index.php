<?php
/** Blog listesi (ve diğer arşivler) */
if (!defined('ABSPATH')) {
    exit;
}
$title = is_home() ? ae_t('blog_title') : wp_strip_all_tags(get_the_archive_title());
ae_seo(['title' => $title, 'description' => ae_t('blog_sub'), 'canonical' => is_home() ? ae_blog_url() : '']);
get_header();
?>
<div class="pagehead"><div class="in"><h1><?php echo esc_html($title); ?></h1><?php if (is_home()) : ?><p><?php ae_e('blog_sub'); ?></p><?php endif; ?></div></div>
<main id="main" class="main">
  <?php if (have_posts()) : ?>
    <div class="grid posts" style="margin-top:28px">
      <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('card post'); ?>>
          <a class="cardlink" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>"></a>
          <div class="img"><?php if (has_post_thumbnail()) {
              the_post_thumbnail('medium_large', ['loading' => 'lazy']);
          } else {
              echo AE_ICON;
          } ?></div>
          <div class="body">
            <div class="sub"><?php echo esc_html(ae_date((int) get_post_timestamp())); ?></div>
            <h2 class="ttl"><?php the_title(); ?></h2>
            <p class="sub"><?php echo esc_html(get_the_excerpt()); ?></p>
            <span class="more"><?php ae_e('read_more'); ?> →</span>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
    <div class="pager"><?php the_posts_pagination(['prev_text' => '‹', 'next_text' => '›']); ?></div>
  <?php else : ?>
    <p class="empty"><?php ae_e('not_found'); ?></p>
  <?php endif; ?>
  <?php if (ec_lang() !== 'tr') : ?><p class="note"><?php ae_e('blog_lang_note'); ?></p><?php endif; ?>
</main>
<?php
get_footer();
