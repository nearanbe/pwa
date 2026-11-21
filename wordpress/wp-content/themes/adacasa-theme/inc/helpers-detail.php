<?php
/**
 * İlan ve proje detay sayfalarının ortak parçaları: simgeler, bilgi şeridi, paylaş düğmeleri,
 * mesaj formu, sabit başlık, harita, ofis kartı. (0.4)
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Küçük çizgi simgeler (24x24) */
function ae_icon(string $name, int $size = 20): string
{
    $p = [
        'home' => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'bed' => '<path d="M3 18V8"/><path d="M3 14h18v4"/><path d="M21 14v-2a3 3 0 0 0-3-3h-7v5"/><circle cx="7" cy="11" r="2"/>',
        'bath' => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M6 12V6a2 2 0 0 1 4 0"/><path d="M7 19l-1 2M17 19l1 2"/>',
        'area' => '<rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 9h3M4 14h2M9 4v3M14 4v2"/>',
        'deed' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 13h7M9 17h5"/>',
        'floor' => '<path d="M4 20h16M6 20V9l6-5 6 5v11"/><path d="M10 20v-4h4v4M9 12h6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'units' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2"/>',
        'crane' => '<path d="M4 21V5h14"/><path d="M4 5l6 0 0 6"/><path d="M18 5v5"/><path d="M16 10h4v3h-4z"/><path d="M2 21h8"/>',
        'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
        'print' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
        'flag' => '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
        'note' => '<path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6M9 17h3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone' => '<path d="M5 4h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'play' => '<path d="M7 4l13 8-13 8z"/>',
        'pause' => '<path d="M7 4v16M17 4v16"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/>',
        'close' => '<path d="M5 5l14 14M19 5 5 19"/>',
        'pdf' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 15h6M9 18h4"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/** Fiyatın altındaki simgeli bilgi şeridi */
function ae_info_strip(array $items): string
{
    $h = '';
    foreach ($items as [$icon, $label, $value]) {
        if ($value === null || $value === '' || $value === false) {
            continue;
        }
        $h .= '<li>' . ae_icon($icon, 22) . '<span><small>' . esc_html($label) . '</small><b>' . esc_html((string) $value) . '</b></span></li>';
    }
    return $h ? '<ul class="istrip">' . $h . '</ul>' : '';
}

/** Paylaş düğmeleri: Facebook, X, e-posta, LinkedIn, Pinterest, WhatsApp, Viber, kopyala */
function ae_share_buttons(string $url, string $title, string $image = ''): string
{
    $u = rawurlencode($url);
    $t = rawurlencode($title);
    $links = [
        'facebook' => ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u, '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>'],
        'x' => ['X', 'https://x.com/intent/post?url=' . $u . '&text=' . $t, '<path d="M4 4l16 16M20 4L4 20"/>'],
        'whatsapp' => ['WhatsApp', 'https://wa.me/?text=' . $t . '%20' . $u, '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/>'],
        'viber' => ['Viber', 'viber://forward?text=' . $t . '%20' . $u, '<path d="M12 3c5 0 8 2.5 8 7.5S17 18 12 18l-4 3v-3.5C5 16.5 4 14 4 10.5 4 5.5 7 3 12 3z"/><path d="M10 8c0 2.5 1.5 4 4 4.5"/>'],
        'telegram' => ['Telegram', 'https://t.me/share/url?url=' . $u . '&text=' . $t, '<path d="M21 4L3 11l6 2 2 6 3-4 5 4z"/>'],
        'linkedin' => ['LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u, '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>'],
        'pinterest' => ['Pinterest', 'https://pinterest.com/pin/create/button/?url=' . $u . '&description=' . $t . ($image ? '&media=' . rawurlencode($image) : ''), '<circle cx="12" cy="12" r="9"/><path d="M11 8c3-1 5 1 4 4s-3 3-4 2M11 12l-2 8"/>'],
        'email' => [ae_t('share_email'), 'mailto:?subject=' . $t . '&body=' . $t . '%0A' . $u, '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'],
    ];
    $h = '<div class="sharebar" role="group" aria-label="' . esc_attr(ae_t('share')) . '">';
    foreach ($links as $k => [$name, $href, $svg]) {
        $h .= '<a class="sh sh-' . $k . '" href="' . esc_url($href, ['https', 'http', 'mailto', 'viber']) . '" target="_blank" rel="noopener nofollow" title="' . esc_attr($name) . '" aria-label="' . esc_attr($name) . '"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $svg . '</svg></a>';
    }
    $h .= '<button type="button" class="sh sh-copy" data-copy="' . esc_attr($url) . '" title="' . esc_attr(ae_t('copy_link')) . '" aria-label="' . esc_attr(ae_t('copy_link')) . '"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 6 0l3-3a4 4 0 0 0-6-6l-1 1"/><path d="M14 10a4 4 0 0 0-6 0l-3 3a4 4 0 0 0 6 6l1-1"/></svg></button>';
    return $h . '</div>';
}

/** "Mesaj gönder" formu. $kind = listing|project */
function ae_lead_form(string $kind, int $id, string $title): string
{
    ob_start(); ?>
<form class="leadform" data-lead="<?php echo esc_attr($kind . 's/' . $id); ?>" novalidate>
  <h3><?php echo ae_icon('mail'); ?> <?php ae_e('lead_title'); ?></h3>
  <div class="fg">
    <label><span class="vh"><?php ae_e('lead_name'); ?></span><input name="name" autocomplete="name" placeholder="<?php ae_e('lead_name'); ?> *" required></label>
    <label><span class="vh"><?php ae_e('reg_phone'); ?></span><input name="phone" type="tel" autocomplete="tel" placeholder="<?php ae_e('reg_phone'); ?>"></label>
    <label><span class="vh"><?php ae_e('email'); ?></span><input name="email" type="email" autocomplete="email" placeholder="<?php ae_e('email'); ?>"></label>
    <label class="wide"><span class="vh"><?php ae_e('lead_message'); ?></span><textarea name="message" rows="3"><?php echo esc_textarea(ae_t($kind === 'project' ? 'lead_default_project' : 'lead_default', $title)); ?></textarea></label>
  </div>
  <input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
  <label class="chk sub"><input type="checkbox" name="consent" value="1" required> <?php ae_e('lead_consent'); ?></label>
  <?php echo ae_turnstile(); ?>
  <p class="formmsg" role="status"></p>
  <button class="btn wide" type="submit"><?php ae_e('lead_send'); ?></button>
</form>
<?php
    return ob_get_clean();
}

/** Üyelik yılı rozeti: "3. yıl" */
function ae_member_year(?string $created): string
{
    if (!$created) {
        return '';
    }
    $y = (int) floor((time() - strtotime($created . ' UTC')) / (365.25 * DAY_IN_SECONDS)) + 1;
    return '<span class="yearbadge" title="' . esc_attr(ae_t('member_since', wp_date('Y', strtotime($created . ' UTC')))) . '">' . esc_html(ae_t('member_year', $y)) . '</span>';
}

/** Ofis / firma kartı (ilan ve proje yan sütunu) */
function ae_agent_card(?array $agentRow, array $opt = []): string
{
    if (!$agentRow) {
        return '';
    }
    $ag = ec_agent_public($agentRow);
    $count = $opt['count'] ?? null;
    ob_start(); ?>
<div class="agent">
  <?php echo $ag['logo'] ? '<img class="alogo" src="' . esc_url($ag['logo']['small']) . '" alt="" loading="lazy">' : '<div class="av">' . esc_html(mb_substr($ag['name'], 0, 1)) . '</div>'; ?>
  <div>
    <b><?php echo esc_html($ag['name']); ?></b><?php if ($ag['verified']) : ?> <span class="vbadge sm" title="<?php ae_e('of_verified'); ?>">✔</span><?php endif; ?>
    <div class="sub"><?php echo esc_html(ec_member_kinds()[$ag['kind']]['name'][ec_lang()] ?? ''); ?> <?php echo ae_member_year($agentRow['created_at'] ?? null); ?></div>
    <?php if ($ag['url']) : ?><a class="sub link" href="<?php echo esc_url($ag['url']); ?>"><?php echo esc_html($count ? ae_t('agent_other', $count) : ae_t('agent_profile')); ?> →</a><?php endif; ?>
  </div>
</div>
<?php echo ae_social_icons(array_merge($ag['socials'], $ag['website'] ? ['website' => $ag['website']] : []));
    return ob_get_clean();
}

/** Harita kutusu (Leaflet, kaydırınca yüklenir). $approx: tam konum yoksa şehir merkezi */
function ae_map_box(float $lat, float $lng, bool $approx, string $label, bool $nearby = true): string
{
    // Leaflet (≈160 KB) burada yüklenmez: harita ekrana gelince detail.js yükler
    $gm = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
    $osm = 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=15/' . $lat . '/' . $lng;
    ob_start(); ?>
<div class="mapwrap">
  <div class="dmap" data-lat="<?php echo esc_attr((string) $lat); ?>" data-lng="<?php echo esc_attr((string) $lng); ?>" data-approx="<?php echo $approx ? 1 : 0; ?>" data-label="<?php echo esc_attr($label); ?>" role="img" aria-label="<?php echo esc_attr(ae_t('map_of', $label)); ?>"></div>
  <p class="sub maplinks"><?php if ($approx) : ?><span>ⓘ <?php ae_e('map_approx'); ?></span> · <?php endif; ?><a href="<?php echo esc_url($gm); ?>" target="_blank" rel="noopener"><?php ae_e('open_gmaps'); ?> ↗</a> · <a href="<?php echo esc_url($osm); ?>" target="_blank" rel="noopener">OpenStreetMap ↗</a></p>
</div>
<?php if ($nearby && !$approx) : ?>
  <div class="nearby" data-lat="<?php echo esc_attr((string) $lat); ?>" data-lng="<?php echo esc_attr((string) $lng); ?>">
    <h3><?php ae_e('nearby_title'); ?></h3>
    <p class="sub nb-wait"><?php ae_e('nearby_loading'); ?></p>
  </div>
<?php endif;
    return ob_get_clean();
}

function ae_enqueue_leaflet(): void
{
    wp_enqueue_style('leaflet', get_theme_file_uri('assets/vendor/leaflet/leaflet.css'), [], '1.9.4');
    wp_enqueue_script('leaflet', get_theme_file_uri('assets/vendor/leaflet/leaflet.js'), [], '1.9.4', ['in_footer' => true]);
}

/** Bölüm menüsü + kaydırınca beliren sabit başlık */
function ae_sticky_head(array $o): string
{
    ob_start(); ?>
<div class="stickhead" id="stickhead" hidden>
  <div class="sh-in">
    <?php if (!empty($o['logo'])) : ?><img class="sh-logo" src="<?php echo esc_url($o['logo']); ?>" alt=""><?php endif; ?>
    <div class="sh-t"><b><?php echo esc_html($o['title']); ?></b><span class="sub"><?php echo $o['price']; ?></span></div>
    <?php if (!empty($o['tel'])) : ?><a class="btn sm" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $o['tel'])); ?>"><?php echo ae_icon('phone', 16); ?> <span class="hide-s"><?php ae_e('call'); ?></span></a><?php endif; ?>
    <?php if (!empty($o['wa'])) : ?><a class="wa sm" href="<?php echo esc_url($o['wa']); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
  </div>
  <nav class="secnav" aria-label="<?php ae_e('sections'); ?>">
    <?php foreach ($o['sections'] as $id => $label) : ?><a href="#<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?>
  </nav>
</div>
<?php
    return ob_get_clean();
}

/** Şehir kartı fotoğrafı (Ayarlar → Şehir kartları) */
function ae_city_photo(string $city): string
{
    $v = ((array) get_option('ec_city_photos', []))[$city] ?? '';
    if (is_int($v) || ctype_digit((string) $v)) {
        return (string) wp_get_attachment_image_url((int) $v, 'medium_large');
    }
    return (string) $v;
}

/** Şehir başına yayındaki ilan sayıları: ['girne' => ['sale' => 120, 'rent' => 40, 'daily' => 3]] */
function ae_city_counts(): array
{
    global $wpdb;
    $c = wp_cache_get('ae_city_counts', 'adacasa-theme');
    if (is_array($c)) {
        return $c;
    }
    $c = get_transient('ae_city_counts');
    if (!is_array($c)) {
        $c = [];
        foreach ($wpdb->get_results('SELECT city, mode, COUNT(*) n FROM ' . ec_table('listings') . " WHERE status = 'published' GROUP BY city, mode", ARRAY_A) as $r) {
            $c[$r['city']][$r['mode']] = (int) $r['n'];
        }
        set_transient('ae_city_counts', $c, 10 * MINUTE_IN_SECONDS);
    }
    wp_cache_set('ae_city_counts', $c, 'adacasa-theme');
    return $c;
}

/** "120+" biçimi (10'un katına yuvarlanır) */
function ae_plus(int $n): string
{
    return $n >= 20 ? (floor($n / 10) * 10) . '+' : (string) $n;
}
