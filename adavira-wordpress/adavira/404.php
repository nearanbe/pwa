<?php defined('ABSPATH') || exit; get_header(); ?>
<section class="sec nf"><div class="w"><p class="eyebrow">404</p><h1>Aradığınız sayfa bulunamadı</h1><p class="lead">Sayfa taşınmış ya da kaldırılmış olabilir.</p>
<div class="actions"><a class="btn" href="<?php echo esc_url(home_url('/')); ?>">Ana sayfaya dön</a><a class="btn ghost" href="<?php echo esc_url(home_url('/iletisim')); ?>">Bize ulaşın</a></div></div></section>
<?php get_footer();
