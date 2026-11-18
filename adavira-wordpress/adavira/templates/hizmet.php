<?php defined('ABSPATH') || exit; $s = get_query_var('adv_slug'); $v = $GLOBALS['ADV_SERVICES'][$s]; get_header();
echo adv_page_head($v[0], $v[2], [['Hizmetler', '/hizmetler'], [$v[0], '']], $v[1]);
?>
<section class="sec"><div class="w">
	<div class="sh"><div><p class="eyebrow">Sektörler</p><h2>Hangi sektörlere hizmet veriyoruz?</h2></div></div>
	<div class="cards"><?php foreach ($GLOBALS['ADV_SECTORS'] as $k => $x) echo '<a class="card" href="' . esc_url(home_url('/sektor/' . $k)) . '"><h3>' . esc_html($x[0]) . '</h3><p>' . esc_html($x[2]) . '</p><span class="svc-go">' . esc_html($x[1]) . ' ' . adv_icon('arrow') . '</span></a>'; ?></div>
</div></section>
<section class="sec alt"><div class="w"><div class="sh"><div><p class="eyebrow">Diğer hizmetler</p><h2>Birlikte daha güçlü</h2></div></div>
	<div class="chips"><?php foreach ($GLOBALS['ADV_SERVICES'] as $k => $x) if ($k !== $s) echo '<a href="' . esc_url(home_url('/hizmet/' . $k)) . '">' . adv_service_icon($k) . esc_html($x[0]) . '</a>'; ?></div>
</div></section>
<?php echo adv_cta_band($v[0] . ' için teklif alın'); get_footer();
