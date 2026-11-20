<?php
/**
 * Yönetim ekranları: İlanlar, Yeni ilan, Danışmanlar, Ayarlar.
 * Bilerek sade tutuldu; asıl ilan girişi Telegram botu ve API üzerinden olacak.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page('Emlak', 'Emlak', 'manage_options', 'ec-listings', 'ec_admin_listings', 'dashicons-admin-home', 3);
    add_submenu_page('ec-listings', 'İlanlar', 'İlanlar', 'manage_options', 'ec-listings', 'ec_admin_listings');
    add_submenu_page('ec-listings', 'Yeni ilan', 'Yeni ilan', 'manage_options', 'ec-new', 'ec_admin_new');
    add_submenu_page('ec-listings', 'Danışmanlar', 'Danışmanlar', 'manage_options', 'ec-agents', 'ec_admin_agents');
    add_submenu_page('ec-listings', 'Ayarlar', 'Ayarlar', 'manage_options', 'ec-settings', 'ec_admin_settings');
});

function ec_admin_notice(): void
{
    $m = get_transient('ec_admin_msg_' . get_current_user_id());
    if ($m) {
        delete_transient('ec_admin_msg_' . get_current_user_id());
        echo '<div class="notice notice-success"><p>' . wp_kses_post($m) . '</p></div>';
    }
}

function ec_admin_back(string $page, string $msg): void
{
    set_transient('ec_admin_msg_' . get_current_user_id(), $msg, 60);
    wp_safe_redirect(admin_url('admin.php?page=' . $page));
    exit;
}

function ec_admin_listings(): void
{
    global $wpdb;
    $status = sanitize_key($_GET['status'] ?? '');
    $where = $status ? $wpdb->prepare('WHERE l.status = %s', $status) : '';
    $l = ec_table('listings');
    $t = ec_table('listing_i18n');
    $a = ec_table('agents');
    $rows = $wpdb->get_results("SELECT l.*, COALESCE(tr.title, en.title, ru.title) title, ag.name agent_name
        FROM $l l
        LEFT JOIN $t tr ON tr.listing_id = l.id AND tr.lang = 'tr'
        LEFT JOIN $t en ON en.listing_id = l.id AND en.lang = 'en'
        LEFT JOIN $t ru ON ru.listing_id = l.id AND ru.lang = 'ru'
        LEFT JOIN $a ag ON ag.id = l.agent_id
        $where ORDER BY l.id DESC LIMIT 200", ARRAY_A);
    $labels = ['draft' => 'Taslak', 'published' => 'Yayında', 'sold' => 'Satıldı', 'passive' => 'Pasif'];
    echo '<div class="wrap"><h1 class="wp-heading-inline">İlanlar</h1> <a class="page-title-action" href="' . esc_url(admin_url('admin.php?page=ec-new')) . '">Yeni ilan</a>';
    ec_admin_notice();
    echo '<ul class="subsubsub"><li><a href="?page=ec-listings">Tümü</a></li>';
    foreach ($labels as $k => $n) {
        echo ' | <li><a href="?page=ec-listings&status=' . $k . '">' . $n . '</a></li>';
    }
    echo '</ul><table class="widefat striped"><thead><tr><th>No</th><th>Başlık</th><th>Tür</th><th>Şehir</th><th>Fiyat</th><th>Danışman</th><th>Durum</th><th>İşlemler</th></tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="8">Henüz ilan yok.</td></tr>';
    }
    foreach ($rows as $r) {
        $featured = $r['featured_until'] && strtotime($r['featured_until'] . ' UTC') > time();
        $act = function (string $do, string $label) use ($r) {
            $u = wp_nonce_url(admin_url('admin-post.php?action=ec_listing&do=' . $do . '&id=' . $r['id']), 'ec_listing_' . $r['id']);
            return '<a href="' . esc_url($u) . '">' . esc_html($label) . '</a>';
        };
        $links = [];
        if ($r['status'] !== 'published') {
            $links[] = $act('publish', 'Yayınla');
        }
        if ($r['status'] === 'published') {
            $links[] = $act('sold', 'Satıldı');
            $links[] = $act('passive', 'Pasife al');
        }
        $links[] = $featured ? $act('unfeature', 'Öne çıkarmayı kaldır') : $act('feature', 'Öne çıkar (30 gün)');
        $view = ec_listings_present([$r], 'tr')[0]['url'];
        echo '<tr><td>#' . (int) $r['id'] . '</td><td><a href="' . esc_url($view) . '" target="_blank">' . esc_html($r['title']) . '</a>' . ($featured ? ' <span class="dashicons dashicons-star-filled" title="Öne çıkan"></span>' : '') . '</td>'
            . '<td>' . esc_html(ec_subtype_name($r['ptype'], (string) $r['subtype'], 'tr')) . ' / ' . esc_html($r['mode']) . '</td>'
            . '<td>' . esc_html(ec_city_name($r['city'], 'tr') . ($r['district'] ? ' / ' . $r['district'] : '')) . '</td>'
            . '<td>' . esc_html(number_format((float) $r['price'], 0, ',', '.') . ' ' . $r['currency']) . '</td>'
            . '<td>' . esc_html($r['agent_name'] ?: '—') . '</td>'
            . '<td>' . esc_html($labels[$r['status']] ?? $r['status']) . '</td>'
            . '<td>' . implode(' · ', $links) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

add_action('admin_post_ec_listing', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_listing_' . $id)) {
        wp_die('Yetkisiz işlem');
    }
    global $wpdb;
    switch ($_GET['do'] ?? '') {
        case 'publish':
            ec_listing_update($id, ['status' => 'published']);
            break;
        case 'sold':
            ec_listing_update($id, ['status' => 'sold']);
            break;
        case 'passive':
            ec_listing_update($id, ['status' => 'passive']);
            break;
        case 'feature':
            $wpdb->update(ec_table('listings'), ['featured_until' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS)], ['id' => $id]);
            break;
        case 'unfeature':
            $wpdb->update(ec_table('listings'), ['featured_until' => null], ['id' => $id]);
            break;
    }
    ec_admin_back('ec-listings', 'İlan #' . $id . ' güncellendi.');
});

function ec_admin_select(string $name, array $opts, string $sel = '', string $empty = ''): string
{
    $h = '<select name="' . esc_attr($name) . '">' . ($empty !== '' ? '<option value="">' . esc_html($empty) . '</option>' : '');
    foreach ($opts as $k => $v) {
        $h .= '<option value="' . esc_attr($k) . '"' . selected($sel, $k, false) . '>' . esc_html($v) . '</option>';
    }
    return $h . '</select>';
}

function ec_admin_new(): void
{
    global $wpdb;
    $types = [];
    foreach (ec_property_types() as $k => $t) {
        foreach ($t['sub'] as $sk => $sn) {
            $types[$k . '|' . $sk] = $t['name']['tr'] . ' › ' . $sn['tr'];
        }
    }
    $cities = array_map(fn($c) => $c['name']['tr'], ec_cities());
    $agents = $wpdb->get_results('SELECT id, name FROM ' . ec_table('agents') . " WHERE status = 'active' ORDER BY name", ARRAY_A);
    $agentOpts = array_column($agents, 'name', 'id');
    $deeds = array_map(fn($d) => $d['tr'], ec_deed_types());

    echo '<div class="wrap"><h1>Yeni ilan</h1>';
    ec_admin_notice();
    echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('ec_new');
    echo '<input type="hidden" name="action" value="ec_new"><table class="form-table">';
    echo '<tr><th>İlan türü</th><td>' . ec_admin_select('mode', ['sale' => 'Satılık', 'rent' => 'Kiralık', 'daily' => 'Günlük kiralık']) . '</td></tr>';
    echo '<tr><th>Emlak tipi</th><td>' . ec_admin_select('type', $types) . '</td></tr>';
    echo '<tr><th>Şehir / Bölge</th><td>' . ec_admin_select('city', $cities) . ' <input name="district" placeholder="Bölge (ör. Alsancak)"></td></tr>';
    echo '<tr><th>Fiyat</th><td><input name="price" type="number" min="0" step="1" required> ' . ec_admin_select('currency', array_combine(EC_CURRENCIES, EC_CURRENCIES)) . '</td></tr>';
    echo '<tr><th>m² / Oda / Banyo</th><td><input name="area_m2" type="number" min="0" style="width:90px" placeholder="m²"> ' . ec_admin_select('rooms', array_combine(ec_room_options(), ec_room_options()), '', 'Oda') . ' <input name="baths" type="number" min="0" style="width:70px" placeholder="Banyo"></td></tr>';
    echo '<tr><th>Tapu türü</th><td>' . ec_admin_select('deed_type', $deeds, '', '—') . ' <label><input type="checkbox" name="furnished" value="1"> Eşyalı</label></td></tr>';
    echo '<tr><th>Danışman</th><td>' . ec_admin_select('agent_id', $agentOpts, '', '—') . '</td></tr>';
    foreach (['tr' => 'Türkçe', 'en' => 'İngilizce', 'ru' => 'Rusça'] as $l => $n) {
        echo '<tr><th>Başlık / Açıklama (' . $n . ')</th><td><input name="title_' . $l . '" class="large-text"' . ($l === 'tr' ? ' required' : '') . '><br><textarea name="desc_' . $l . '" rows="4" class="large-text"></textarea></td></tr>';
    }
    echo '<tr><th>Fotoğraflar</th><td><input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"><p class="description">İlk fotoğraf kapak olur. JPG/PNG/WebP, en çok ' . EC_PHOTO_MAX . ' adet.</p></td></tr>';
    echo '<tr><th>Durum</th><td>' . ec_admin_select('status', ['published' => 'Hemen yayınla', 'draft' => 'Taslak']) . '</td></tr>';
    echo '</table>';
    submit_button('İlanı kaydet');
    echo '</form></div>';
}

add_action('admin_post_ec_new', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_new')) {
        wp_die('Yetkisiz işlem');
    }
    $p = wp_unslash($_POST);
    [$ptype, $sub] = array_pad(explode('|', (string) ($p['type'] ?? '')), 2, '');
    $i18n = [];
    foreach (EC_LANGS as $l) {
        if (!empty($p['title_' . $l])) {
            $i18n[$l] = ['title' => $p['title_' . $l], 'description' => $p['desc_' . $l] ?? ''];
        }
    }
    $id = ec_listing_create([
        'mode' => $p['mode'] ?? '',
        'ptype' => $ptype,
        'subtype' => $sub,
        'city' => $p['city'] ?? '',
        'district' => $p['district'] ?? '',
        'price' => $p['price'] ?? '',
        'currency' => $p['currency'] ?? 'GBP',
        'area_m2' => $p['area_m2'] ?? null,
        'rooms' => $p['rooms'] ?? null,
        'baths' => $p['baths'] ?? null,
        'deed_type' => $p['deed_type'] ?? null,
        'furnished' => !empty($p['furnished']) ? 1 : 0,
        'status' => $p['status'] ?? 'draft',
        'i18n' => $i18n,
    ], !empty($p['agent_id']) ? (int) $p['agent_id'] : null, 'panel');
    if (is_wp_error($id)) {
        wp_die('İlan kaydedilemedi: ' . esc_html(wp_json_encode($id->get_error_data())));
    }
    $n = 0;
    if (!empty($_FILES['photos']['tmp_name'])) {
        foreach ((array) $_FILES['photos']['tmp_name'] as $i => $tmp) {
            if (($_FILES['photos']['error'][$i] ?? 1) === UPLOAD_ERR_OK && is_uploaded_file($tmp) && !is_wp_error(ec_photo_add($id, $tmp))) {
                $n++;
            }
        }
    }
    ec_admin_back('ec-listings', "İlan #$id kaydedildi ($n fotoğraf).");
});

function ec_admin_agents(): void
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('agents') . ' ORDER BY status = "pending" DESC, id DESC', ARRAY_A);
    echo '<div class="wrap"><h1>Danışmanlar</h1>';
    ec_admin_notice();
    echo '<p>Siteden gelen başvurular "Bekliyor" olarak görünür. Onaylanan danışmana API anahtarı üretip Telegram botu ve Chrome eklentisi kurulumunda kullanırsınız.</p>';
    echo '<table class="widefat striped"><thead><tr><th>No</th><th>İsim / Firma</th><th>Tür</th><th>E-posta</th><th>Telefon</th><th>Şehir</th><th>Durum</th><th>İşlemler</th></tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="8">Henüz danışman yok.</td></tr>';
    }
    $labels = ['pending' => 'Bekliyor', 'active' => 'Aktif', 'suspended' => 'Askıda'];
    foreach ($rows as $r) {
        $act = function (string $do, string $label) use ($r) {
            $u = wp_nonce_url(admin_url('admin-post.php?action=ec_agent&do=' . $do . '&id=' . $r['id']), 'ec_agent_' . $r['id']);
            return '<a href="' . esc_url($u) . '">' . esc_html($label) . '</a>';
        };
        $links = $r['status'] === 'active' ? [$act('key', $r['api_key_hash'] ? 'Yeni anahtar üret' : 'API anahtarı üret'), $act('suspend', 'Askıya al')] : [$act('approve', 'Onayla')];
        echo '<tr><td>' . (int) $r['id'] . '</td><td>' . esc_html($r['name']) . '</td><td>' . esc_html($r['kind'] . ($r['company_type'] ? ' / ' . $r['company_type'] : '')) . '</td><td>' . esc_html($r['email']) . '</td><td>' . esc_html((string) $r['phone']) . '</td><td>' . esc_html($r['city'] ? ec_city_name($r['city'], 'tr') : '—') . '</td><td>' . esc_html($labels[$r['status']] ?? $r['status']) . '</td><td>' . implode(' · ', $links) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

add_action('admin_post_ec_agent', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_agent_' . $id)) {
        wp_die('Yetkisiz işlem');
    }
    global $wpdb;
    $do = $_GET['do'] ?? '';
    if ($do === 'approve' || $do === 'suspend') {
        $wpdb->update(ec_table('agents'), ['status' => $do === 'approve' ? 'active' : 'suspended', 'updated_at' => ec_now()], ['id' => $id]);
        ec_admin_back('ec-agents', 'Danışman #' . $id . ' güncellendi.');
    }
    if ($do === 'key') {
        $key = ec_agent_issue_key($id);
        ec_admin_back('ec-agents', 'Danışman #' . $id . ' için yeni API anahtarı (sadece şimdi görünür, güvenli bir yere kaydedin):<br><code style="font-size:14px">' . esc_html($key) . '</code>');
    }
    ec_admin_back('ec-agents', 'İşlem yok.');
});

function ec_admin_settings(): void
{
    $rates = ec_rates();
    $ads = get_option('ec_ads', ['client' => '', 'top' => '', 'feed' => '', 'mid' => '', 'detail' => '']);
    $contact = get_option('ec_contact', ['whatsapp' => '', 'email' => '', 'telegram_bot' => '']);
    echo '<div class="wrap"><h1>Emlak ayarları</h1>';
    ec_admin_notice();
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('ec_settings');
    echo '<input type="hidden" name="action" value="ec_settings"><h2>Döviz kurları (1 GBP = ?)</h2><table class="form-table">';
    foreach (EC_CURRENCIES as $c) {
        if ($c === 'GBP') {
            continue;
        }
        echo '<tr><th>' . $c . '</th><td><input name="rate_' . $c . '" type="number" step="0.0001" min="0" value="' . esc_attr($rates[$c]) . '"></td></tr>';
    }
    echo '</table><p class="description">İlanlar kendi para birimiyle saklanır; arama ve sıralama için GBP karşılığı bu kurlarla hesaplanır. İleride KKTC Merkez Bankası kurlarından otomatik çekilecek.</p>';
    echo '<h2>Reklam (Google AdSense)</h2><table class="form-table">';
    foreach (['client' => 'Yayıncı kimliği (ca-pub-...)', 'top' => 'Üst alan slot', 'feed' => 'İlan arası slot', 'mid' => 'Orta alan slot', 'detail' => 'İlan detay slot'] as $k => $n) {
        echo '<tr><th>' . esc_html($n) . '</th><td><input name="ads_' . $k . '" class="regular-text" value="' . esc_attr($ads[$k] ?? '') . '"></td></tr>';
    }
    echo '</table><p class="description">Boş bırakılırsa sitede sadece "Reklam alanı" yer tutucusu görünür.</p>';
    echo '<h2>İletişim</h2><table class="form-table">';
    foreach (['whatsapp' => 'Site WhatsApp numarası (90533...)', 'email' => 'Site e-postası', 'telegram_bot' => 'Telegram bot kullanıcı adı (@ olmadan)'] as $k => $n) {
        echo '<tr><th>' . esc_html($n) . '</th><td><input name="contact_' . $k . '" class="regular-text" value="' . esc_attr($contact[$k] ?? '') . '"></td></tr>';
    }
    echo '</table>';
    submit_button('Kaydet');
    echo '</form></div>';
}

add_action('admin_post_ec_settings', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_settings')) {
        wp_die('Yetkisiz işlem');
    }
    $p = wp_unslash($_POST);
    $rates = ['GBP' => 1];
    foreach (EC_CURRENCIES as $c) {
        if ($c !== 'GBP' && isset($p['rate_' . $c]) && (float) $p['rate_' . $c] > 0) {
            $rates[$c] = (float) $p['rate_' . $c];
        }
    }
    update_option('ec_rates', $rates);
    $ads = [];
    foreach (['client', 'top', 'feed', 'mid', 'detail'] as $k) {
        $ads[$k] = preg_replace('/[^a-zA-Z0-9-]/', '', (string) ($p['ads_' . $k] ?? ''));
    }
    update_option('ec_ads', $ads);
    update_option('ec_contact', [
        'whatsapp' => preg_replace('/\D/', '', (string) ($p['contact_whatsapp'] ?? '')),
        'email' => sanitize_email($p['contact_email'] ?? ''),
        'telegram_bot' => preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($p['contact_telegram_bot'] ?? '')),
    ]);
    // Kur değişince tüm ilanların GBP karşılığını yeniden hesapla.
    global $wpdb;
    $t = ec_table('listings');
    foreach ($rates as $c => $r) {
        $wpdb->query($wpdb->prepare("UPDATE $t SET price_gbp = ROUND(price / %f, 2) WHERE currency = %s", $r, $c));
    }
    ec_admin_back('ec-settings', 'Ayarlar kaydedildi.');
});
