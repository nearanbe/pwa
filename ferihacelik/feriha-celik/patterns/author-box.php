<?php
/**
 * Title: Yazar kutusu
 * Slug: feriha-celik/author-box
 * Categories: feriha-celik
 * Description: Blog yazılarının altında yazar tanıtımı.
 *
 * @package feriha-celik
 */

$fc_img_id = (int) fc_opt( 'hero_image' );
?>
<!-- wp:group {"className":"fc-author","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group fc-author"><?php if ( $fc_img_id ) : ?><!-- wp:html -->
<?php echo wp_get_attachment_image( $fc_img_id, 'thumbnail', false, array( 'class' => 'fc-author__img', 'alt' => esc_attr( fc_opt( 'person_name' ) ) ) ); ?>
<!-- /wp:html --><?php endif; ?>

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"fc-author__name"} -->
<p class="fc-author__name"><strong><?php echo esc_html( fc_opt( 'person_name' ) ); ?></strong> · <?php echo esc_html( fc_opt( 'title' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Kuzey Kıbrıs'ta yetişkinler, çocuklar, ergenler, çiftler ve ailelerle yüz yüze ve online çalışıyor. <a href="<?php echo esc_url( home_url( '/hakkimda/' ) ); ?>">Hakkımda daha fazlası</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
