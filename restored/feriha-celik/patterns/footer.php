<?php
/**
 * Title: Alt bölüm
 * Slug: feriha-celik/footer
 * Categories: feriha-celik
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package feriha-celik
 */

$fc_phone = fc_opt( 'phone' );
$fc_mail  = fc_opt( 'email' );
$fc_addr  = fc_opt( 'address' );
$fc_wa    = fc_whatsapp_url();
$fc_maps  = fc_opt( 'maps_url' );
?>
<!-- wp:group {"tagName":"div","align":"full","className":"fc-footer","backgroundColor":"primary-dark","textColor":"base","layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull fc-footer has-base-color has-primary-dark-background-color has-text-color has-background"><!-- wp:columns {"align":"wide","className":"fc-footer__cols"} -->
<div class="wp-block-columns alignwide fc-footer__cols"><!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%"><!-- wp:site-title {"level":0,"className":"fc-footer__brand"} /-->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php echo esc_html( fc_opt( 'title' ) . ' ' . fc_opt( 'person_name' ) ); ?> — Kuzey Kıbrıs genelinde yetişkin, çocuk, ergen, çift ve aile terapisi. Yüz yüze ve online görüşme.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"fc-footer__area","fontSize":"small"} -->
<p class="fc-footer__area has-small-font-size">Hizmet bölgesi: Lefkoşa, Girne, Gazimağusa, Güzelyurt, İskele, Lefke ve tüm Kuzey Kıbrıs · Online: her yerden</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:heading {"level":2,"className":"fc-footer__h"} -->
<h2 class="wp-block-heading fc-footer__h">Hizmetler</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"fc-footer__links"} -->
<ul class="wp-block-list fc-footer__links"><?php foreach ( fc_service_groups() as $fc_key => $fc_group ) : ?><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/hizmetler/#' . $fc_key ) ); ?>"><?php echo esc_html( $fc_group['title'] ); ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/online-terapi/' ) ); ?>">Online Terapi</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"18%"} -->
<div class="wp-block-column" style="flex-basis:18%"><!-- wp:heading {"level":2,"className":"fc-footer__h"} -->
<h2 class="wp-block-heading fc-footer__h">Sayfalar</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"fc-footer__links"} -->
<ul class="wp-block-list fc-footer__links"><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/hakkimda/' ) ); ?>">Hakkımda</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( fc_booking_url() ); ?>">Randevu Al</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/iletisim/' ) ); ?>">İletişim</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/kvkk-gizlilik-politikasi/' ) ); ?>">KVKK ve Gizlilik</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"26%"} -->
<div class="wp-block-column" style="flex-basis:26%"><!-- wp:heading {"level":2,"className":"fc-footer__h"} -->
<h2 class="wp-block-heading fc-footer__h">İletişim</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<ul class="fc-footer__contact">
<?php if ( $fc_phone ) : ?><li><?php echo fc_icon( 'phone' ); // phpcs:ignore ?><a href="tel:+<?php echo esc_attr( fc_digits( $fc_phone ) ); ?>"><?php echo esc_html( $fc_phone ); ?></a></li><?php endif; ?>
<?php if ( $fc_wa ) : ?><li><?php echo fc_icon( 'chat' ); // phpcs:ignore ?><a href="<?php echo esc_url( $fc_wa ); ?>" target="_blank" rel="noopener">WhatsApp ile yazın</a></li><?php endif; ?>
<?php if ( $fc_mail ) : ?><li><?php echo fc_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( antispambot( $fc_mail ) ); ?>"><?php echo esc_html( antispambot( $fc_mail ) ); ?></a></li><?php endif; ?>
<li><?php echo fc_icon( 'pin' ); // phpcs:ignore ?><span><?php echo $fc_addr ? nl2br( esc_html( $fc_addr ) ) : esc_html( fc_opt( 'city' ) . ', Kuzey Kıbrıs' ); ?><?php if ( $fc_maps ) : ?><br><a href="<?php echo esc_url( $fc_maps ); ?>" target="_blank" rel="noopener">Haritada göster</a><?php endif; ?></span></li>
<li><?php echo fc_icon( 'calendar' ); // phpcs:ignore ?><span><?php echo esc_html( fc_opt( 'hours' ) ); ?></span></li>
</ul>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:separator {"align":"wide","className":"fc-footer__sep is-style-wide"} -->
<hr class="wp-block-separator alignwide has-alpha-channel-opacity fc-footer__sep is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"align":"wide","className":"fc-footer__bottom","fontSize":"small"} -->
<p class="alignwide fc-footer__bottom has-small-font-size">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. Bu sitedeki içerikler bilgilendirme amaçlıdır; tanı ve tedavi yerine geçmez. Acil durumlarda 112'yi arayın.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
