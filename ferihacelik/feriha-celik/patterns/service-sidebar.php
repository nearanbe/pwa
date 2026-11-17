<?php
/**
 * Title: Hizmet sayfası yan kutu
 * Slug: feriha-celik/service-sidebar
 * Categories: feriha-celik
 * Inserter: no
 *
 * @package feriha-celik
 */

$fc_wa    = fc_whatsapp_url();
$fc_phone = fc_opt( 'phone' );
?>
<!-- wp:group {"className":"fc-aside","layout":{"type":"default"}} -->
<div class="wp-block-group fc-aside"><!-- wp:heading {"level":2,"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size">Randevu alın</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"fc-checks"} -->
<ul class="wp-block-list fc-checks"><!-- wp:list-item -->
<li>Online veya yüz yüze</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>Seanslar yaklaşık 50 dakika</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>Gizlilik ve etik ilkeler</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>Kuzey Kıbrıs geneli</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:buttons {"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-buttons"><!-- wp:button {"width":100} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fc_booking_url() ); ?>">Randevu Al</a></div>
<!-- /wp:button --><?php if ( $fc_wa ) : ?>

<!-- wp:button {"width":100,"className":"is-style-whatsapp"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-whatsapp"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $fc_wa ); ?>" target="_blank" rel="noreferrer noopener">WhatsApp ile yazın</a></div>
<!-- /wp:button --><?php endif; ?><?php if ( $fc_phone ) : ?>

<!-- wp:button {"width":100,"className":"is-style-outline"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline"><a class="wp-block-button__link wp-element-button" href="tel:+<?php echo esc_attr( fc_digits( $fc_phone ) ); ?>">Ara: <?php echo esc_html( $fc_phone ); ?></a></div>
<!-- /wp:button --><?php endif; ?></div>
<!-- /wp:buttons -->

<!-- wp:shortcode -->
[fc_ilgili_hizmetler]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
