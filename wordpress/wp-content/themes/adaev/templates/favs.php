<?php
/** Favoriler: ziyaretçinin ♥ ile işaretlediği ilanlar (çerezde saklanır, hesap gerekmez) */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('favs', $l);
}
ae_seo(['title' => ae_t('favourites'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
$ids = ae_favs();
$items = $ids ? ec_listing_search(['ids' => $ids], $lang, 1, 60)['items'] : [];
get_header();
?>
<div class="pagehead"><div class="in"><h1>♥ <?php ae_e('favourites'); ?></h1></div></div>
<main id="main" class="main">
  <div class="grid" style="margin-top:24px">
    <?php foreach ($items as $a) {
        echo ae_card($a);
    } ?>
    <?php if (!$items) : ?><div class="empty"><?php ae_e('fav_empty'); ?></div><?php endif; ?>
  </div>
</main>
<?php
get_footer();
