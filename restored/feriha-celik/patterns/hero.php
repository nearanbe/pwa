<?php
/**
 * Title: Anasayfa giriş (fotoğraf + başlık + butonlar)
 * Slug: feriha-celik/hero
 * Categories: feriha-celik
 * Description: Fotoğraf, ana başlık, Randevu Al ve WhatsApp butonları.
 *
 * @package feriha-celik
 */

$fc_wa     = fc_whatsapp_url();
$fc_img_id = (int) fc_opt( 'hero_image' );
$fc_img    = fc_hero_image_url( 'large' );
$fc_alt    = fc_opt( 'title' ) . ' ' . fc_opt( 'person_name' );
?>
<!-- wp:group {"tagName":"section","align":"full","className":"fc-hero","backgroundColor":"surface","layout":{"type":"constrained","contentSize":"1180px"}} -->
<section class="wp-block-group alignfull fc-hero has-surface-background-color has-background"><!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"fc-hero__cols"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center fc-hero__cols"><!-- wp:column {"verticalAlignment":"center","width":"56%","className":"fc-hero__text"} -->
<div class="wp-block-column is-vertically-aligned-center fc-hero__text" style="flex-basis:56%"><!-- wp:paragraph {"className":"fc-eyebrow"} -->
<p class="fc-eyebrow"><?php echo esc_html( fc_opt( 'title' ) ); ?> · Kuzey Kıbrıs</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"fc-hero__title"} -->
<h1 class="wp-block-heading fc-hero__title"><?php echo esc_html( fc_opt( 'hero_title' ) ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"fc-hero__lead","fontSize":"large"} -->
<p class="fc-hero__lead has-large-font-size"><?php echo esc_html( fc_opt( 'hero_text' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"fc-hero__buttons"} -->
<div class="wp-block-buttons fc-hero__buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fc_booking_url() ); ?>">Randevu Al</a></div>
<!-- /wp:button --><?php if ( $fc_wa ) : ?>

<!-- wp:button {"className":"is-style-whatsapp"} -->
<div class="wp-block-button is-style-whatsapp"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $fc_wa ); ?>" target="_blank" rel="noreferrer noopener">WhatsApp ile yazın</a></div>
<!-- /wp:button --><?php endif; ?></div>
<!-- /wp:buttons -->

<!-- wp:list {"className":"fc-badges"} -->
<ul class="wp-block-list fc-badges"><!-- wp:list-item -->
<li>Online terapi</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Yüz yüze görüşme</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Gizlilik ve güven</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"44%","className":"fc-hero__media"} -->
<div class="wp-block-column is-vertically-aligned-center fc-hero__media" style="flex-basis:44%"><!-- wp:image {<?php echo $fc_img_id ? '"id":' . (int) $fc_img_id . ',' : ''; ?>"sizeSlug":"large","linkDestination":"none","className":"fc-hero__photo"} -->
<figure class="wp-block-image size-large fc-hero__photo"><img src="<?php echo esc_url( $fc_img ); ?>" alt="<?php echo esc_attr( $fc_alt ); ?>"<?php echo $fc_img_id ? ' class="wp-image-' . (int) $fc_img_id . '"' : ' width="600" height="720"'; ?>/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
