<?php
/**
 * Title: Randevu adımları
 * Slug: feriha-celik/appointment-steps
 * Categories: feriha-celik
 * Description: Nasıl randevu alınır — 4 adım.
 *
 * @package feriha-celik
 */

$fc_steps = array(
	array( 'Hizmeti seçin', 'Size uygun hizmeti ve görüşme türünü (online ya da yüz yüze) belirleyin.' ),
	array( 'Gün ve saat seçin', 'Takvimde müsait günlerden birini, ardından size uyan saati seçin.' ),
	array( 'Bilgilerinizi bırakın', 'Adınızı ve telefon numaranızı yazın. Sadece randevu için gereken bilgiler istenir.' ),
	array( 'Onayı alın', 'Randevunuz onaylandığında size e-posta veya telefonla bilgi verilir.' ),
);
$fc_wa    = fc_whatsapp_url();
?>
<!-- wp:group {"tagName":"section","align":"full","className":"fc-section fc-steps","backgroundColor":"cream","layout":{"type":"constrained","contentSize":"1180px"}} -->
<section class="wp-block-group alignfull fc-section fc-steps has-cream-background-color has-background"><!-- wp:heading {"textAlign":"center","className":"fc-section__title"} -->
<h2 class="wp-block-heading has-text-align-center fc-section__title">Nasıl randevu alırım?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"fc-section__lead"} -->
<p class="has-text-align-center fc-section__lead">Birkaç dakikada, 4 kolay adımda randevunuzu oluşturabilirsiniz.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","className":"fc-steps__grid","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group alignwide fc-steps__grid"><?php foreach ( $fc_steps as $fc_i => $fc_step ) : ?><!-- wp:group {"className":"fc-step","layout":{"type":"default"}} -->
<div class="wp-block-group fc-step"><!-- wp:paragraph {"className":"fc-step__num"} -->
<p class="fc-step__num"><?php echo (int) $fc_i + 1; ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $fc_step[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $fc_step[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --><?php endforeach; ?></div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fc_booking_url() ); ?>">Hemen randevu al</a></div>
<!-- /wp:button --><?php if ( $fc_wa ) : ?>

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $fc_wa ); ?>" target="_blank" rel="noreferrer noopener">WhatsApp'tan sorun</a></div>
<!-- /wp:button --><?php endif; ?></div>
<!-- /wp:buttons --></section>
<!-- /wp:group -->
