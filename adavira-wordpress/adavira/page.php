<?php defined('ABSPATH') || exit; get_header(); the_post(); $p = get_post();
echo adv_page_head(get_the_title(), has_excerpt() ? get_the_excerpt() : '', [[get_the_title(), '']], $p->post_name === 'hakkimizda' ? 'AdaVira' : '');
?>
<section class="sec"><div class="w art-b">
	<?php echo adv_cover($p); ?>
	<div class="prose"><?php the_content(); ?></div>
</div></section>
<?php if ($p->post_name === 'hakkimizda'): ?>
<section class="sec alt"><div class="w"><div class="sh"><div><p class="eyebrow">Neler yapıyoruz?</p><h2>Hizmetlerimiz</h2></div></div><?php echo adv_svc_grid(); ?></div></section>
<?php endif; echo adv_cta_band(); get_footer();
