<?php defined('ABSPATH') || exit; get_header(); the_post(); $p = get_post();
$isb = $p->post_type === 'blog'; $nm = $isb ? 'Blog' : 'Bizden Haberler'; $base = $isb ? '/blog' : '/bizden-haberler';
$uid = (int) $p->post_author; $video = adv_video_html($p);
?>
<article class="art">
	<header class="w art-h">
		<?php echo adv_crumbs([[$nm, $base], [get_the_title(), '']]); ?>
		<p class="pc-m"><span><?php echo esc_html(adv_kind($p)); ?></span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time><?php if (get_the_modified_date('YmdHi') > get_the_date('YmdHi')) echo '<span class="upd">Güncellendi: <time datetime="' . esc_attr(get_the_modified_date('c')) . '">' . esc_html(get_the_modified_date('d.m.Y')) . '</time></span>'; ?><span class="rt"><?php echo (int) adv_read_time($p); ?> dk okuma</span></p>
		<h1><?php the_title(); ?></h1>
		<?php if (has_excerpt()) echo '<p class="lead">' . esc_html(get_the_excerpt()) . '</p>'; ?>
		<div class="byline"><?php echo adv_author_photo($uid, 44); ?><div><a href="<?php echo esc_url(adv_author_url($uid)); ?>" rel="author"><?php echo esc_html(adv_author_name($uid)); ?></a><?php $t = get_user_meta($uid, 'adv_title', true); if ($t) echo '<span>' . esc_html($t) . '</span>'; ?></div></div>
	</header>
	<div class="w art-b">
		<?php echo $video ?: adv_cover($p); echo adv_guest_html($p); ?>
		<div class="prose"><?php the_content(); ?></div>
		<?php $tl = get_the_term_list($p->ID, 'konu', '<p class="tags">', '', '</p>'); if ($tl && !is_wp_error($tl)) echo $tl; ?>
		<?php echo adv_share($p); echo adv_author_box($uid); ?>
		<aside class="art-cta"><div><strong>Bu konuda destek mi istiyorsunuz?</strong><p>Ücretsiz ön analiz için numaranızı bırakın, sizi arayalım.</p></div><a class="btn" href="<?php echo esc_url(home_url('/iletisim')); ?>">Bize ulaşın <?php echo adv_icon('arrow'); ?></a></aside>
	</div>
</article>
<?php
$tids = wp_get_post_terms($p->ID, 'konu', ['fields' => 'ids']);
$args = ['post_type' => $p->post_type, 'numberposts' => 3, 'post__not_in' => [$p->ID]];
if ($tids && !is_wp_error($tids)) $args['tax_query'] = [['taxonomy' => 'konu', 'terms' => $tids]];
$rel = get_posts($args);
if (count($rel) < 3) $rel = array_merge($rel, get_posts(['post_type' => $p->post_type, 'numberposts' => 3 - count($rel), 'post__not_in' => array_merge([$p->ID], wp_list_pluck($rel, 'ID'))]));
if ($rel): ?>
<section class="sec alt"><div class="w"><div class="sh"><div><h2>İlgili içerikler</h2></div><a class="more" href="<?php echo esc_url(home_url($base)); ?>">Tümü <?php echo adv_icon('arrow'); ?></a></div><?php echo adv_post_grid($rel); ?></div></section>
<?php endif; get_footer();
