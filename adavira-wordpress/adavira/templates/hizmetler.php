<?php defined('ABSPATH') || exit; get_header();
echo adv_page_head('Hizmetlerimiz', 'Web tasarım, sosyal medya, SEO, reklam, marka kimliği ve video prodüksiyon. Tek tek veya paket olarak.', [['Hizmetler', '']], 'Hizmetler');
?>
<section class="sec"><div class="w"><?php echo adv_svc_grid(); ?></div></section>
<section class="sec alt"><div class="w"><div class="sh"><div><p class="eyebrow">Süreç</p><h2>Çalışma şeklimiz</h2></div></div><?php echo adv_steps(); ?></div></section>
<?php echo adv_cta_band(); get_footer();
