<?php defined('ABSPATH') || exit; [$msg, $good, $old] = adv_contact_handle(); get_header(); the_post(); $wa = adv_wa();
echo adv_page_head('İletişim', 'Projenizi anlatın, size en kısa sürede dönüş yapalım.', [['İletişim', '']], 'Bize ulaşın');
?>
<section class="sec"><div class="w contact">
	<div class="cinfo">
		<div class="cbtns">
			<a class="cbtn" href="tel:+<?php echo esc_attr($wa); ?>"><?php echo adv_icon('phone'); ?><span><small>Hemen arayın</small><?php echo esc_html(adv_phone_fmt($wa)); ?></span></a>
			<a class="cbtn wa-b" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener"><?php echo adv_icon('social'); ?><span><small>WhatsApp</small>Mesaj gönderin</span></a>
			<a class="cbtn" href="mailto:<?php echo esc_attr(adv_email()); ?>"><?php echo adv_icon('mail'); ?><span><small>E-posta</small><?php echo esc_html(adv_email()); ?></span></a>
			<div class="cbtn"><?php echo adv_icon('pin'); ?><span><small>Adres</small><?php echo esc_html(adv_opt('address', 'Ozanköy - Girne / KKTC')); ?></span></div>
		</div>
		<p class="muted"><?php echo esc_html(adv_opt('hours', 'Pazartesi – Cumartesi: 09:00 – 18:00')); ?></p>
		<?php if (trim(get_the_content())) echo '<div class="prose">' . apply_filters('the_content', get_the_content()) . '</div>'; ?>
	</div>
	<?php echo adv_contact_form($msg, $good, $old); ?>
</div></section>
<?php get_footer();
