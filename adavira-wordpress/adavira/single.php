<?php defined('ABSPATH') || exit; get_header(); the_post(); $p = get_post();
$isb = $p->post_type === 'blog'; $nm = $isb ? 'Blog' : 'Bizden Haberler'; $base = $isb ? '/blog' : '/bizden-haberler';
?>
<article class="art">
	<header class="w art-h">
		<?php echo adv_crumbs([[$nm, $base], [get_the_title(), '']]); ?>
		<p class="pc-m"><span><?php echo esc_html($nm); ?></span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time><?php if (get_the_modified_date('Ymd') > get_the_date('Ymd')) echo '<span class="upd">Güncellendi: ' . esc_html(get_the_modified_date('d.m.Y')) . '</span>'; ?></p>
		<h1><?php the_title(); ?></h1>
		<?php if (has_excerpt()) echo '<p class="lead">' . esc_html(get_the_excerpt()) . '</p>'; ?>
	</header>
	<div class="w art-b">
		<?php echo adv_cover($p); ?>
		<div class="prose"><?php the_content(); ?></div>
		<?php $tl = get_the_term_list($p->ID, 'konu', '<p class="tags">', '', '</p>'); if ($tl && !is_wp_error($tl)) echo $tl; ?>
		<aside class="art-cta"><div><strong>Bu konuda destek mi istiyorsunuz?</strong><p>Ücretsiz ön analiz için numaranızı bırakın, sizi arayalım.</p></div><a class="btn" href="<?php echo esc_url(home_url('/iletisim')); ?>">Bize ulaşın <?php echo adv_icon('arrow'); ?></a></aside>
	</div>
</article>
<?php $rel = get_posts(['post_type' => $p->post_type, 'numberposts' => 3, 'post__not_in' => [$p->ID]]);
if ($rel): ?>
<section class="sec alt"><div class="w"><div class="sh"><div><h2>Diğer yazılar</h2></div><a class="more" href="<?php echo esc_url(home_url($base)); ?>">Tümü <?php echo adv_icon('arrow'); ?></a></div><?php echo adv_post_grid($rel); ?></div></section>
<?php endif; get_footer();
