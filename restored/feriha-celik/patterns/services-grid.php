<?php
/**
 * Title: Hizmetler (4 grup kart)
 * Slug: feriha-celik/services-grid
 * Categories: feriha-celik
 * Description: Bireysel, Çift ve Aile, Çocuk ve Ergen, Testler ve Ölçekler kartları.
 *
 * @package feriha-celik
 */

?>
<!-- wp:group {"tagName":"section","align":"wide","className":"fc-section fc-services","layout":{"type":"default"}} -->
<section class="wp-block-group alignwide fc-section fc-services"><!-- wp:heading {"textAlign":"center","className":"fc-section__title"} -->
<h2 class="wp-block-heading has-text-align-center fc-section__title">Size nasıl yardımcı olabilirim?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"fc-section__lead"} -->
<p class="has-text-align-center fc-section__lead">Kuzey Kıbrıs'ta yüz yüze ve online; yetişkinler, çocuklar, ergenler, çiftler ve aileler için psikolojik destek.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"fc-services__grid","layout":{"type":"grid","minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group fc-services__grid"><?php foreach ( fc_service_groups() as $fc_key => $fc_group ) : ?><!-- wp:group {"anchor":"<?php echo esc_attr( $fc_key ); ?>","className":"fc-service-card","layout":{"type":"default"}} -->
<div id="<?php echo esc_attr( $fc_key ); ?>" class="wp-block-group fc-service-card"><!-- wp:html -->
<span class="fc-service-card__icon"><?php echo fc_icon( $fc_group['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
<!-- /wp:html -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $fc_group['title'] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"fc-service-card__intro"} -->
<p class="fc-service-card__intro"><?php echo esc_html( $fc_group['intro'] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"fc-service-card__list"} -->
<ul class="wp-block-list fc-service-card__list"><?php foreach ( $fc_group['items'] as $fc_item ) : ?><!-- wp:list-item -->
<li><a href="<?php echo esc_url( fc_service_url( $fc_item['slug'] ) ); ?>"><?php echo esc_html( $fc_item['title'] ); ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list --></div>
<!-- /wp:group --><?php endforeach; ?></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
