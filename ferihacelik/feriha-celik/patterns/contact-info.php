<?php
/**
 * Title: İletişim bilgileri (kartlar)
 * Slug: feriha-celik/contact-info
 * Categories: feriha-celik
 * Description: Telefon, WhatsApp, e-posta, adres ve çalışma saatleri.
 *
 * @package feriha-celik
 */

$fc_phone = fc_opt( 'phone' );
$fc_mail  = fc_opt( 'email' );
$fc_wa    = fc_whatsapp_url();
$fc_maps  = fc_opt( 'maps_url' );
$fc_addr  = fc_opt( 'address' );
?>
<!-- wp:paragraph -->
<p>Randevu ve sorularınız için aşağıdaki yollardan bana ulaşabilirsiniz. Görüşmeler Kuzey Kıbrıs'ta yüz yüze ya da her yerden online yapılabilir.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="fc-contact-grid">
<?php if ( $fc_wa ) : ?>
<a class="fc-contact-card" href="<?php echo esc_url( $fc_wa ); ?>" target="_blank" rel="noopener"><?php echo fc_icon( 'chat' ); // phpcs:ignore ?><strong>WhatsApp</strong><span>Hemen yazın, kısa sürede dönüş yapılır.</span></a>
<?php endif; ?>
<?php if ( $fc_phone ) : ?>
<a class="fc-contact-card" href="tel:+<?php echo esc_attr( fc_digits( $fc_phone ) ); ?>"><?php echo fc_icon( 'phone' ); // phpcs:ignore ?><strong>Telefon</strong><span><?php echo esc_html( $fc_phone ); ?></span></a>
<?php endif; ?>
<?php if ( $fc_mail ) : ?>
<a class="fc-contact-card" href="mailto:<?php echo esc_attr( antispambot( $fc_mail ) ); ?>"><?php echo fc_icon( 'mail' ); // phpcs:ignore ?><strong>E-posta</strong><span><?php echo esc_html( antispambot( $fc_mail ) ); ?></span></a>
<?php endif; ?>
<a class="fc-contact-card" href="<?php echo esc_url( fc_booking_url() ); ?>"><?php echo fc_icon( 'calendar' ); // phpcs:ignore ?><strong>Online randevu</strong><span>Müsait gün ve saati seçin.</span></a>
<div class="fc-contact-card fc-contact-card--wide"><?php echo fc_icon( 'pin' ); // phpcs:ignore ?><strong>Muayenehane</strong><span><?php echo $fc_addr ? nl2br( esc_html( $fc_addr ) ) : esc_html( fc_opt( 'city' ) . ', Kuzey Kıbrıs' ); ?></span><span><?php echo esc_html( fc_opt( 'hours' ) ); ?></span><?php if ( $fc_maps ) : ?><a class="fc-contact-card__link" href="<?php echo esc_url( $fc_maps ); ?>" target="_blank" rel="noopener">Google Haritalar'da aç →</a><?php endif; ?></div>
</div>
<!-- /wp:html -->
