<?php defined('ABSPATH') || exit; $wa = adv_wa(); $soc = adv_social_html(); ?>
</main>
<footer class="ftr">
	<div class="w ftop">
		<div class="fbrand">
			<a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><svg class="logo-m" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="14" fill="#C9A86A"/><text x="32" y="43" text-anchor="middle" font-family="Georgia,'Times New Roman',serif" font-size="30" font-weight="700" fill="#0F2730">VA</text></svg><span>AdaVira</span></a>
			<p>Girne merkezli dijital ajans. Web tasarım, SEO, sosyal medya ve video prodüksiyon tek ekipte.</p>
			<ul class="fcontact">
				<li><?php echo adv_icon('mail'); ?><a href="mailto:<?php echo esc_attr(adv_email()); ?>"><?php echo esc_html(adv_email()); ?></a></li>
				<li><?php echo adv_icon('phone'); ?><a href="tel:+<?php echo esc_attr($wa); ?>"><?php echo esc_html(adv_phone_fmt($wa)); ?></a></li>
				<li><?php echo adv_icon('pin'); ?><span><?php echo esc_html(adv_opt('address', 'Ozanköy - Girne / KKTC')); ?></span></li>
			</ul>
			<?php if ($soc): ?><div class="soc"><?php echo $soc; ?></div><?php endif; ?>
		</div>
		<div><h2 class="fh">Hizmetler</h2><?php foreach ($GLOBALS['ADV_SERVICES'] as $s => $v) echo '<a href="' . esc_url(home_url('/hizmet/' . $s)) . '">' . esc_html($v[1]) . '</a>'; ?></div>
		<div><h2 class="fh">Sektörlere özel</h2><?php foreach ($GLOBALS['ADV_SECTORS'] as $s => $v) echo '<a href="' . esc_url(home_url('/sektor/' . $s)) . '">' . esc_html($v[1]) . '</a>'; ?></div>
		<div><h2 class="fh">Kurumsal</h2>
			<a href="<?php echo esc_url(home_url('/hakkimizda')); ?>">Hakkımızda</a>
			<a href="<?php echo esc_url(home_url('/blog')); ?>">Blog</a>
			<a href="<?php echo esc_url(home_url('/bizden-haberler')); ?>">Bizden Haberler</a>
			<a href="<?php echo esc_url(home_url('/iletisim')); ?>">İletişim</a>
			<a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>">Site Haritası</a>
		</div>
	</div>
	<div class="w fbot">
		<p>© <?php echo esc_html(date_i18n('Y')); ?> AdaVira Dijital Ajans · Girne, Kuzey Kıbrıs</p>
		<p><?php echo esc_html(adv_opt('hours', 'Pazartesi – Cumartesi: 09:00 – 18:00')); ?></p>
	</div>
</footer>
<a class="wa" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener" aria-label="WhatsApp ile yazın"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.3-.4.8-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2c0 1.3 1 2.6 1.1 2.8.1.2 1.9 2.9 4.6 4 1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.5-.2z"/></svg><span>WhatsApp</span></a>
<div class="mbar"><a href="tel:+<?php echo esc_attr($wa); ?>"><?php echo adv_icon('phone'); ?>Ara</a><a href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener">WhatsApp</a></div>
<?php wp_footer(); ?>
</body>
</html>
