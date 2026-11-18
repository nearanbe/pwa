<?php defined('ABSPATH') || exit; $wa = adv_wa(); $cur = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$nav = ['/sektorler' => 'Sektörler', '/hizmetler' => 'Hizmetler', '/blog' => 'Blog', '/bizden-haberler' => 'Bizden Haberler', '/hakkimizda' => 'Hakkımızda'];
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">İçeriğe geç</a>
<header class="hdr">
	<div class="w nav">
		<a class="logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="AdaVira ana sayfa">
			<svg class="logo-m" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="14" fill="#C9A86A"/><text x="32" y="43" text-anchor="middle" font-family="Georgia,'Times New Roman',serif" font-size="30" font-weight="700" fill="#0F2730">VA</text></svg>
			<span>AdaVira</span>
		</a>
		<button id="menu" class="burger" aria-label="Menüyü aç" aria-expanded="false" aria-controls="nav"><span></span></button>
		<nav id="nav" aria-label="Ana menü">
			<?php foreach ($nav as $u => $t): $on = $cur === $u || strpos($cur, $u . '/') === 0 || ($u === '/sektorler' && strpos($cur, '/sektor/') === 0) || ($u === '/hizmetler' && strpos($cur, '/hizmet/') === 0); ?>
			<a href="<?php echo esc_url(home_url($u)); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo esc_html($t); ?></a>
			<?php endforeach; ?>
			<a class="btn sm" href="<?php echo esc_url(home_url('/iletisim')); ?>">Teklif alın</a>
		</nav>
	</div>
</header>
<main id="main">
