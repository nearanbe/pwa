<?php defined('ABSPATH') || exit; get_header(); $wa = adv_wa(); ?>
<?php echo adv_news_bar(); ?>
<section class="hero">
	<div class="w hero-in">
		<div class="hero-tx">
			<p class="eyebrow">Girne · Kuzey Kıbrıs dijital ajans</p>
			<h1>Kıbrıs Dijital Ajans: <em>Web Tasarım, SEO</em> ve Video Prodüksiyon</h1>
			<p class="lead">Markanızın dijital rotasını çiziyoruz. Web sitesi, SEO, sosyal medya, reklam ve profesyonel video çekimi tek ekipte.</p>
			<div class="actions">
				<a class="btn" href="<?php echo esc_url(home_url('/iletisim')); ?>">Ücretsiz analiz isteyin <?php echo adv_icon('arrow'); ?></a>
				<a class="btn ghost" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener">WhatsApp'tan yazın</a>
			</div>
		</div>
		<aside class="hero-card" aria-label="Öne çıkan hizmetler">
			<p class="hc-t">Tek ekipte</p>
			<ul>
				<li><?php echo adv_icon('web'); ?><div><strong>Web tasarım ve yazılım</strong><span>Hızlı, mobil uyumlu, Google dostu</span></div></li>
				<li><?php echo adv_icon('drone'); ?><div><strong>Drone 4K ve video</strong><span>İç ve dış mekân çekim, kurgu</span></div></li>
				<li><?php echo adv_icon('seo'); ?><div><strong>SEO ve dijital reklam</strong><span>Haritalar ve aramada üst sıralar</span></div></li>
				<li><?php echo adv_icon('ai'); ?><div><strong>Akıllı otomasyonlar</strong><span>Emlak için yapay zekâ asistanı</span></div></li>
			</ul>
		</aside>
	</div>
	<div class="ticker" aria-hidden="true"><div class="ticker-in"><?php $tk = ['Web Tasarım', 'SEO', 'Sosyal Medya', 'Google ve Meta Reklam', 'Marka Kimliği', 'Drone 4K', 'Video Kurgu', 'AI Asistan']; for ($i = 0; $i < 2; $i++) foreach ($tk as $t) echo '<span>' . esc_html($t) . '</span>'; ?></div></div>
</section>

<?php echo adv_news_block(); ?>

<section class="sec" id="hizmetler">
	<div class="w">
		<div class="sh"><div><p class="eyebrow">Hizmetler</p><h2>Neler yapıyoruz?</h2></div><p class="lead">İhtiyacınız olanı tek tek veya paket olarak alabilirsiniz.</p></div>
		<?php echo adv_svc_grid(); ?>
	</div>
</section>

<section class="band" id="sektorler">
	<div class="w">
		<div class="sh"><div><p class="eyebrow">Sektörler</p><h2>Sektörünüzü tanıyan bir ekip</h2></div><p class="lead">Her sektörün müşterisi farklı karar verir. Çalışmamızı buna göre kurarız.</p></div>
		<?php echo adv_sector_tabs(); ?>
	</div>
</section>

<section class="sec">
	<div class="w spot">
		<div class="spot-art" aria-hidden="true">
			<div class="frame"><span class="rec">● REC</span><span class="res">4K · 60fps</span><svg viewBox="0 0 200 120"><path d="M0 95 L40 60 L70 80 L110 35 L150 70 L200 40 L200 120 L0 120 Z" fill="currentColor" opacity=".18"/><path d="M0 105 L50 78 L90 95 L140 60 L200 85 L200 120 L0 120 Z" fill="currentColor" opacity=".3"/><circle cx="160" cy="28" r="10" fill="currentColor" opacity=".5"/></svg><span class="cross"></span></div>
		</div>
		<div>
			<p class="eyebrow">Emlak ve oto galeri için</p>
			<h2>Portföyünüz, doğru görüntüyle hızlı satılır.</h2>
			<p class="lead">Alıcı ilanı açmadan önce fotoğrafa ve videoya bakar. Yerinde iç ve dış mekân çekimi, drone 4K görüntü ve ilan odaklı web siteniz tek ekipten çıkar.</p>
			<ul class="ck">
				<li><?php echo adv_icon('check'); ?>Yerinde iç/dış mekân profesyonel çekim</li>
				<li><?php echo adv_icon('check'); ?>Drone 4K çekim ve video kurgu</li>
				<li><?php echo adv_icon('check'); ?>Müşteri sorularını yanıtlayan yapay zekâ asistanı</li>
				<li><?php echo adv_icon('check'); ?>İlan ve stok sayfaları için Google SEO</li>
			</ul>
			<div class="actions"><a class="btn" href="<?php echo esc_url(home_url('/sektor/emlak')); ?>">Emlak çözümleri <?php echo adv_icon('arrow'); ?></a><a class="btn ghost" href="<?php echo esc_url(home_url('/sektor/oto-galeri')); ?>">Oto galeri çözümleri</a></div>
		</div>
	</div>
</section>

<section class="sec alt">
	<div class="w">
		<div class="sh"><div><p class="eyebrow">Süreç</p><h2>Çalışma şeklimiz</h2></div><p class="lead">Dört adımda, net bir takvimle ilerleriz.</p></div>
		<?php echo adv_steps(); ?>
	</div>
</section>

<section class="sec">
	<div class="w">
		<div class="sh"><div><p class="eyebrow">İçerikler</p><h2>Blog</h2></div><a class="more" href="<?php echo esc_url(home_url('/blog')); ?>">Tüm yazılar <?php echo adv_icon('arrow'); ?></a></div>
		<?php echo adv_post_grid(adv_posts('blog', 3)); ?>
	</div>
</section>

<?php echo adv_cta_band('Birlikte çalışalım'); get_footer();
