<?php defined('ABSPATH') || exit; $uid = adv_author_by_slug(get_query_var('adv_slug')); get_header();
$n = adv_author_name($uid); $bio = trim((string) get_the_author_meta('description', $uid)); $t = get_user_meta($uid, 'adv_title', true);
$paged = max(1, (int) get_query_var('paged'));
$q = new WP_Query(['post_type' => ['haber', 'blog'], 'author' => $uid, 'posts_per_page' => 12, 'paged' => $paged]);
?>
<section class="phead"><div class="w">
	<?php echo adv_crumbs([['Yazarlar', ''], [$n, '']]); ?>
	<div class="ahead"><?php echo adv_author_photo($uid, 96); ?><div><p class="eyebrow"><?php echo esc_html($t ?: 'Yazar'); ?></p><h1><?php echo esc_html($n); ?></h1><?php if ($bio) echo '<p class="lead">' . esc_html($bio) . '</p>'; ?>
	<p class="alinks"><?php foreach (['adv_linkedin' => 'LinkedIn', 'adv_x' => 'X'] as $k => $l) { $u = get_user_meta($uid, $k, true); if ($u) echo '<a href="' . esc_url($u) . '" target="_blank" rel="noopener me">' . $l . '</a>'; } ?></p></div></div>
</div></section>
<section class="sec"><div class="w">
	<div class="sh"><div><h2>Yazıları</h2></div><p class="muted"><?php echo (int) $q->found_posts; ?> içerik</p></div>
	<?php echo adv_post_grid($q->posts);
	if ($q->max_num_pages > 1) { echo '<nav class="pagination"><div class="nav-links">' . paginate_links(['base' => esc_url(adv_author_url($uid)) . '/page/%#%', 'format' => '', 'current' => $paged, 'total' => $q->max_num_pages, 'prev_text' => '‹', 'next_text' => '›']) . '</div></nav>'; } ?>
</div></section>
<?php get_footer();
