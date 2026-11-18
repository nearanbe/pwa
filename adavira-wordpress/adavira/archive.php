<?php defined('ABSPATH') || exit; get_header();
if (is_tax('konu')) { $t = get_queried_object(); $nm = $t->name; $lead = $t->description; $cr = [[$nm, '']]; }
else { $isb = is_post_type_archive('blog'); $nm = $isb ? 'Blog' : 'Bizden Haberler'; $lead = $isb ? 'Dijital pazarlama, SEO, web tasarım ve video üzerine rehberler.' : 'AdaVira\'dan duyurular, projeler ve sektör haberleri.'; $cr = [[$nm, '']]; }
echo adv_page_head($nm, $lead, $cr);
?>
<section class="sec"><div class="w">
<?php
$terms = get_terms(['taxonomy' => 'konu', 'hide_empty' => true]);
if ($terms && !is_wp_error($terms)) { echo '<div class="chips sm">'; foreach ($terms as $t) echo '<a href="' . esc_url(get_term_link($t)) . '">' . esc_html($t->name) . '</a>'; echo '</div>'; }
global $wp_query; echo adv_post_grid($wp_query->posts);
the_posts_pagination(['mid_size' => 1, 'prev_text' => '‹ Önceki', 'next_text' => 'Sonraki ›', 'screen_reader_text' => 'Sayfalar']);
?>
</div></section>
<?php echo adv_cta_band(); get_footer();
