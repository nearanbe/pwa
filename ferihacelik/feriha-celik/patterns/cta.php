<?php
/**
 * Title: Randevu çağrısı (Randevu Al + WhatsApp)
 * Slug: feriha-celik/cta
 * Categories: feriha-celik
 * Description: Yazıların ve sayfaların sonuna eklenebilecek randevu kutusu.
 *
 * @package feriha-celik
 */

$fc_wa = fc_whatsapp_url();
?>
<!-- wp:group {"className":"fc-cta","backgroundColor":"surface","layout":{"type":"constrained"}} -->
<div class="wp-block-group fc-cta has-surface-background-color has-background"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Görüşmek ister misiniz?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Size uygun gün ve saati seçerek online ya da yüz yüze randevu oluşturabilir, aklınıza takılanları WhatsApp'tan sorabilirsiniz.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fc_booking_url() ); ?>">Randevu Al</a></div>
<!-- /wp:button --><?php if ( $fc_wa ) : ?>

<!-- wp:button {"className":"is-style-whatsapp"} -->
<div class="wp-block-button is-style-whatsapp"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $fc_wa ); ?>" target="_blank" rel="noreferrer noopener">WhatsApp ile yazın</a></div>
<!-- /wp:button --><?php endif; ?></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
