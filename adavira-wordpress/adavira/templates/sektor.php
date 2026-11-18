<?php defined('ABSPATH') || exit; $s = get_query_var('adv_slug'); $v = $GLOBALS['ADV_SECTORS'][$s]; $L = $GLOBALS['ADV_SLOG'][$s]; get_header();
echo adv_page_head($v[1], $v[2], [['Sektörler', '/sektorler'], [$v[0], '']], $v[0]);
?>
<section class="sec"><div class="w two">
	<div><p class="eyebrow">Yaklaşımımız</p><h2><?php echo esc_html($L[0]); ?></h2><p class="lead"><?php echo esc_html($L[1]); ?></p>
		<a class="btn" href="<?php echo esc_url(home_url('/iletisim')); ?>">Bu sektör için teklif alın <?php echo adv_icon('arrow'); ?></a></div>
	<div class="boxed"><h3>Sunduğumuz hizmetler</h3><ul class="ck"><?php foreach ($v[3] as $m) echo '<li>' . adv_icon('check') . esc_html($m) . '</li>'; ?></ul></div>
</div></section>
<section class="sec alt"><div class="w"><div class="sh"><div><p class="eyebrow">Diğer sektörler</p><h2>Başka sektörlerde de yanınızdayız</h2></div></div>
	<div class="chips"><?php foreach ($GLOBALS['ADV_SECTORS'] as $k => $x) if ($k !== $s) echo '<a href="' . esc_url(home_url('/sektor/' . $k)) . '">' . esc_html(explode(':', $x[0])[0]) . '</a>'; ?></div>
</div></section>
<?php echo adv_cta_band(); get_footer();
