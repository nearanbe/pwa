<?php
/** Blog yazısı */
if (!defined('ABSPATH')) {
    exit;
}
the_post();
ae_seo([
    'title' => get_the_title(),
    'description' => wp_strip_all_tags(get_the_excerpt()),
    'canonical' => get_permalink(),
    'image' => get_the_post_thumbnail_url(null, 'large') ?: '',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'image' => get_the_post_thumbnail_url(null, 'large') ?: null,
        'publisher' => ['@type' => 'Organization', 'name' => ae_brand()],
    ],
]);
$recent = get_posts(['numberposts' => 3, 'post__not_in' => [get_the_ID()]]);
get_header();
?>
<main id="main" class="main article">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?php echo esc_url(ec_url_home()); ?>"><?php ae_e('home'); ?></a> › <a href="<?php echo esc_url(ae_blog_url()); ?>"><?php ae_e('blog'); ?></a></nav>
  <article <?php post_class('panel prose'); ?>>
    <h1><?php the_title(); ?></h1>
    <div class="sub"><?php echo esc_html(get_the_date()); ?></div>
    <?php if (has_post_thumbnail()) : ?><figure class="hero-img"><?php the_post_thumbnail('large', ['alt' => '']); ?></figure><?php endif; ?>
    <div class="entry"><?php the_content(); ?></div>
  </article>
  <?php if ($recent) : ?>
    <div class="sechead"><h2 class="sec"><?php ae_e('from_blog'); ?></h2><a href="<?php echo esc_url(ae_blog_url()); ?>"><?php ae_e('all_posts'); ?> →</a></div>
    <div class="grid posts"><?php foreach ($recent as $r) : ?>
      <article class="card post"><a class="cardlink" href="<?php echo esc_url(get_permalink($r)); ?>" aria-label="<?php echo esc_attr(get_the_title($r)); ?>"></a>
        <div class="img"><?php echo get_the_post_thumbnail($r, 'medium_large', ['loading' => 'lazy', 'alt' => '']) ?: AE_ICON; ?></div>
        <div class="body"><div class="sub"><?php echo esc_html(get_the_date('', $r)); ?></div><h3 class="ttl"><?php echo esc_html(get_the_title($r)); ?></h3></div>
      </article>
    <?php endforeach; ?></div>
  <?php endif; ?>
</main>
<?php
get_footer();
