<?php
/**
 * Title: Son blog yazıları (3 kart)
 * Slug: feriha-celik/latest-posts
 * Categories: feriha-celik
 * Description: Blogdan en son 3 yazı.
 *
 * @package feriha-celik
 */

$fc_blog = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/blog/' );
?>
<!-- wp:group {"tagName":"section","align":"wide","className":"fc-section fc-latest","layout":{"type":"default"}} -->
<section class="wp-block-group alignwide fc-section fc-latest"><!-- wp:heading {"textAlign":"center","className":"fc-section__title"} -->
<h2 class="wp-block-heading has-text-align-center fc-section__title">Blogdan son yazılar</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"fc-section__lead"} -->
<p class="has-text-align-center fc-section__lead">Ruh sağlığı, ilişkiler ve çocuk gelişimi üzerine yazılar.</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":11,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"className":"fc-cards","layout":{"type":"grid","minimumColumnWidth":"17rem"}} -->
<!-- wp:group {"className":"fc-card","layout":{"type":"default"}} -->
<div class="wp-block-group fc-card"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","sizeSlug":"fc-card"} /-->

<!-- wp:group {"className":"fc-card__body","layout":{"type":"default"}} -->
<div class="wp-block-group fc-card__body"><!-- wp:post-date /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-excerpt {"moreText":"Devamını oku","excerptLength":22} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Yakında burada yazılar yer alacak.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $fc_blog ); ?>">Tüm yazılar</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></section>
<!-- /wp:group -->
