<?php defined('ABSPATH') || exit; get_header(); ?>
<section class="band phead-band"><div class="w">
	<?php echo adv_crumbs([['Sektörler', '']]); ?>
	<p class="eyebrow">Sektörler</p><h1>Sektörünüzü tanıyan bir ekip</h1>
	<p class="lead">Emlak, oto galeri, güzellik, spor, sağlık, e-ticaret, otel ve seyahat acenteleri için dijital çözümler.</p>
	<?php echo adv_sector_tabs(); ?>
</div></section>
<?php echo adv_cta_band(); get_footer();
