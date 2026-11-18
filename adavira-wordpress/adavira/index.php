<?php defined('ABSPATH') || exit; get_header();
echo adv_page_head(is_search() ? 'Arama: ' . get_search_query() : wp_get_document_title());
?>
<section class="sec"><div class="w"><?php global $wp_query; echo adv_post_grid($wp_query->posts); the_posts_pagination(); ?></div></section>
<?php get_footer();
