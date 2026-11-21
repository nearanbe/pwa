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
    add_submenu_page('ec-listings', 'Projeler', 'Projeler', 'manage_options', 'ec-projects', 'ec_admin_projects');
    add_submenu_page('ec-listings', 'Danışmanlar', 'Danışmanlar', 'manage_options', 'ec-agents', 'ec_admin_agents');
    add_submenu_page('ec-listings', 'Görsel üret', 'Görsel üret', 'manage_options', 'ec-ai', 'ec_admin_ai');
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
    $labels = ['pending' => 'Onay bekliyor', 'draft' => 'Taslak', 'published' => 'Yayında', 'rejected' => 'Reddedildi', 'sold' => 'Satıldı', 'passive' => 'Pasif'];
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
            $links[] = $act('publish', $r['status'] === 'pending' ? 'Onayla' : 'Yayınla');
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
            ec_review_listing($id, 'approve');
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
    $labels = ['pending' => 'Onay bekliyor', 'active' => 'Aktif', 'suspended' => 'Askıda'];
    foreach ($rows as $r) {
        $act = function (string $do, string $label) use ($r) {
            $u = wp_nonce_url(admin_url('admin-post.php?action=ec_agent&do=' . $do . '&id=' . $r['id']), 'ec_agent_' . $r['id']);
            return '<a href="' . esc_url($u) . '">' . esc_html($label) . '</a>';
        };
        $links = $r['status'] === 'active' ? [$act('key', $r['api_key_hash'] ? 'Yeni anahtar üret' : 'API anahtarı üret'), $act($r['verified'] ? 'unverify' : 'verify', $r['verified'] ? 'Doğrulanmış rozetini kaldır' : 'Doğrulanmış rozeti ver'), $act('suspend', 'Askıya al')] : [$act('approve', 'Onayla')];
        $st = ($labels[$r['status']] ?? $r['status']) . (!$r['email_verified'] && $r['wp_user_id'] ? ' (e-posta doğrulanmadı)' : '');
        $kn = (ec_member_kinds()[$r['kind']]['name']['tr'] ?? $r['kind']) . ($r['verified'] ? ' ✔' : '');
        echo '<tr><td>' . (int) $r['id'] . '</td><td>' . esc_html($r['name']) . '</td><td>' . esc_html($kn) . '</td><td>' . esc_html($r['email']) . '</td><td>' . esc_html((string) $r['phone']) . '</td><td>' . esc_html($r['city'] ? ec_city_name($r['city'], 'tr') : '—') . '</td><td>' . esc_html($st) . '</td><td>' . implode(' · ', $links) . '</td></tr>';
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
    if (in_array($do, ['approve', 'suspend', 'verify', 'unverify'], true)) {
        ec_review_agent($id, $do);
        ec_admin_back('ec-agents', 'Üye #' . $id . ' güncellendi.');
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
    $meta = (array) get_option('ec_rates_meta', []);
    echo '<tr><th>Otomatik güncelleme</th><td><label><input type="checkbox" name="rates_auto" value="1"' . checked((int) get_option('ec_rates_auto', 1), 1, false) . '> Kurları otomatik güncelle (saatte bir kontrol)</label>';
    if (!empty($meta['updated_at'])) {
        echo '<p class="description">Kaynak: <b>' . esc_html($meta['source'] ?? '') . '</b> · Kur tarihi: <b>' . esc_html(!empty($meta['rate_date']) ? date_i18n('d.m.Y', strtotime($meta['rate_date'])) : '—') . '</b> · Son kontrol: ' . esc_html(get_date_from_gmt($meta['updated_at'], 'd.m.Y H:i')) . ' (saatte bir kontrol edilir)</p>';
    }
    if (!empty($meta['error'])) {
        echo '<p class="description" style="color:#b32d2e">Son deneme başarısız: ' . esc_html($meta['error']) . '</p>';
    }
    if (!empty($meta['sample'])) {
        echo '<p class="description">KKTC Merkez Bankası cevabının başı (okunamadı; bu metni geliştiriciye gönderin):</p><textarea readonly rows="4" class="large-text code">' . esc_textarea($meta['sample']) . '</textarea>';
    }
    echo '</td></tr>';
    $side = get_option('ec_rates_side', 'mid');
    echo '<tr><th>Kur türü (TCMB / KKTC MB)</th><td><select name="rates_side"><option value="mid"' . selected($side, 'mid', false) . '>Ortalama (döviz alış + satış) / 2</option><option value="buy"' . selected($side, 'buy', false) . '>Döviz alış</option><option value="sell"' . selected($side, 'sell', false) . '>Döviz satış</option></select><p class="description">Kaynak sırası: TCMB → KKTC Merkez Bankası → Avrupa Merkez Bankası → open.er-api.com. TCMB her iş günü 15:30’da yayımlar.</p></td></tr>';
    echo '</table><p class="description">İlanlar kendi para birimiyle saklanır; arama ve sıralama için GBP karşılığı bu kurlarla hesaplanır. Otomatik güncelleme açıkken elle girilen değerlerin üzerine her gün yazılır.</p>';
    echo '<h2>Reklam (Google AdSense)</h2><table class="form-table">';
    foreach (['client' => 'Yayıncı kimliği (ca-pub-...)', 'top' => 'Üst alan slot', 'feed' => 'İlan arası slot', 'mid' => 'Orta alan slot', 'detail' => 'İlan detay slot'] as $k => $n) {
        echo '<tr><th>' . esc_html($n) . '</th><td><input name="ads_' . $k . '" class="regular-text" value="' . esc_attr($ads[$k] ?? '') . '"></td></tr>';
    }
    echo '</table><p class="description">Boş bırakılırsa sitede sadece "Reklam alanı" yer tutucusu görünür.</p>';
    echo '<h2>İletişim</h2><table class="form-table">';
    foreach (['whatsapp' => 'Site WhatsApp numarası (90533...)', 'email' => 'Site e-postası'] as $k => $n) {
        echo '<tr><th>' . esc_html($n) . '</th><td><input name="contact_' . $k . '" class="regular-text" value="' . esc_attr($contact[$k] ?? '') . '"></td></tr>';
    }
    echo '</table>';

    $social = (array) get_option('ec_social', []);
    echo '<h2>Sitenin sosyal medya hesapları</h2><p class="description">Link girilen hesapların simgesi sitenin üst ve alt bölümünde görünür; boş bırakılanlar gizlenir.</p><table class="form-table">';
    foreach (ec_social_networks() as $k => [$n, $ph]) {
        echo '<tr><th>' . esc_html($n) . '</th><td><input name="social[' . esc_attr($k) . ']" class="regular-text" placeholder="' . esc_attr($ph) . '" value="' . esc_attr($social[$k] ?? '') . '"></td></tr>';
    }
    echo '</table>';

    $secret = fn(string $name, string $val) => '<input name="' . esc_attr($name) . '" type="password" class="regular-text" autocomplete="new-password" placeholder="' . ($val ? '•••••••• (kayıtlı, değiştirmek için yazın)' : '') . '">';
    $ts = ec_turnstile_keys();
    echo '<h2>Cloudflare Turnstile (kayıt ve giriş captcha)</h2><p class="description">Boş bırakılırsa formlar captcha’sız çalışır. Anahtarları Cloudflare → Turnstile → Add widget ekranından alın.</p><table class="form-table">';
    echo '<tr><th>Site anahtarı (Site Key)</th><td><input name="ts_site" class="regular-text" value="' . esc_attr($ts['site']) . '"></td></tr>';
    echo '<tr><th>Gizli anahtar (Secret Key)</th><td>' . $secret('ts_secret', $ts['secret']) . '</td></tr></table>';

    $tg = (array) get_option('ec_telegram', []);
    echo '<h2>Telegram</h2><p class="description">Bot anahtarını Telegram’da @BotFather’dan alın. Sohbet numaranızı öğrenmek için bota bir mesaj yazıp @userinfobot’a sorabilirsiniz. Yeni üye ve onay bekleyen ilan bildirimleri bu sohbete gelir.</p><table class="form-table">';
    echo '<tr><th>Bot kullanıcı adı (@ olmadan)</th><td><input name="tg_bot" class="regular-text" value="' . esc_attr($tg['bot'] ?? ($contact['telegram_bot'] ?? '')) . '"></td></tr>';
    echo '<tr><th>Bot anahtarı (token)</th><td>' . $secret('tg_token', (string) ($tg['token'] ?? '')) . '</td></tr>';
    echo '<tr><th>Yönetici sohbet numarası (chat id)</th><td><input name="tg_admin_chat" class="regular-text" value="' . esc_attr($tg['admin_chat'] ?? '') . '"></td></tr></table>';

    $host = preg_replace('/^www\./', '', (string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    echo '<h2>Ofis alt alan adları</h2><table class="form-table"><tr><th>Alt alan adları</th><td><label><input type="checkbox" name="subdomains" value="1"' . checked((int) get_option('ec_subdomains'), 1, false) . '> Ofis vitrinleri <code>ofisadi.' . esc_html($host) . '</code> adresinde açılsın</label><p class="description">Açmadan önce DNS’te <code>*</code> kaydı ve sunucuda yıldızlı (wildcard) alt alan adı + SSL kurulmuş olmalı (bkz. kurulum belgesi). Kapalıyken vitrinler <code>/tr/ofis/ofisadi/</code> adresinde açılır.</p></td></tr></table>';

    $cf = (array) get_option('ec_cloudflare', []);
    echo '<h2>Cloudflare Workers AI (temsili görsel üretimi)</h2><p class="description">Sadece blog, slider ve fotoğrafı olmayan projeler için “temsili görsel” üretir. İlan fotoğrafı olarak kullanılmaz.</p><table class="form-table">';
    echo '<tr><th>Account ID</th><td><input name="cf_account" class="regular-text" value="' . esc_attr($cf['account'] ?? '') . '"></td></tr>';
    echo '<tr><th>API Token</th><td>' . $secret('cf_token', (string) ($cf['token'] ?? '')) . '</td></tr></table>';
    submit_button('Kaydet');
    echo '</form>';

    $tool = function (string $do, string $label, string $cls = 'button', string $confirm = '') {
        $u = wp_nonce_url(admin_url('admin-post.php?action=ec_tool&do=' . $do), 'ec_tool_' . $do);
        return '<a class="' . $cls . '" href="' . esc_url($u) . '"' . ($confirm ? ' onclick="return confirm(\'' . esc_js($confirm) . '\')"' : '') . '>' . esc_html($label) . '</a> ';
    };
    echo '<hr><h2>Araçlar</h2><p>' . $tool('rates', 'Kurları şimdi güncelle') . $tool('index', 'Fiyat endeksini şimdi hesapla') . '</p>';
    echo '<p class="description">Fiyat endeksi her gün otomatik hesaplanır: yayındaki konut ilanlarının m² başına istenen fiyatlarının medyanı (şehir bazında, en az ' . EC_INDEX_MIN_N . ' ilan).</p>';
    echo '<h2>Demo verisi</h2>';
    if (ec_demo_active()) {
        echo '<p>Demo verisi <b>yüklü</b>. Canlıya çıkmadan önce silin. Düzenlediğiniz demo blog yazıları silinmez.</p><p>' . $tool('unseed', 'Demo verisini sil', 'button button-link-delete', 'Tüm demo ilan, proje, danışman ve demo blog yazıları silinsin mi?') . '</p>';
    } else {
        echo '<p>Siteyi denemek için kurgusal ilanlar, danışmanlar, projeler, 3 blog yazısı ve 12 aylık örnek fiyat endeksi ekler.</p><p>' . $tool('seed', 'Demo verisi ekle', 'button button-primary') . '</p>';
    }
    echo '</div>';
}

add_action('admin_post_ec_tool', function () {
    $do = sanitize_key($_GET['do'] ?? '');
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_tool_' . $do)) {
        wp_die('Yetkisiz işlem');
    }
    switch ($do) {
        case 'rates':
            $r = ec_rates_update();
            $msg = is_wp_error($r) ? esc_html($r->get_error_message()) : 'Kurlar güncellendi: 1 GBP = ' . esc_html($r['EUR'] . ' EUR, ' . $r['USD'] . ' USD, ' . $r['TRY'] . ' TRY');
            break;
        case 'index':
            $msg = ec_index_compute() . ' endeks satırı hesaplandı.';
            break;
        case 'seed':
            $r = ec_demo_seed();
            $msg = is_wp_error($r) ? esc_html($r->get_error_message()) : esc_html($r);
            break;
        case 'unseed':
            $msg = esc_html(ec_demo_unseed());
            break;
        default:
            $msg = 'İşlem yok.';
    }
    ec_admin_back('ec-settings', $msg);
});

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
    update_option('ec_rates_auto', !empty($p['rates_auto']) ? 1 : 0);
    $newSide = in_array($p['rates_side'] ?? 'mid', ['mid', 'buy', 'sell'], true) ? $p['rates_side'] : 'mid';
    $sideChanged = $newSide !== get_option('ec_rates_side', 'mid');
    update_option('ec_rates_side', $newSide);
    $ads = [];
    foreach (['client', 'top', 'feed', 'mid', 'detail'] as $k) {
        $ads[$k] = preg_replace('/[^a-zA-Z0-9-]/', '', (string) ($p['ads_' . $k] ?? ''));
    }
    update_option('ec_ads', $ads);
    $bot = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($p['tg_bot'] ?? ''));
    update_option('ec_contact', [
        'whatsapp' => preg_replace('/\D/', '', (string) ($p['contact_whatsapp'] ?? '')),
        'email' => sanitize_email($p['contact_email'] ?? ''),
        'telegram_bot' => $bot,
    ]);
    update_option('ec_social', ec_clean_socials((array) ($p['social'] ?? [])));
    $keep = fn(array $old, string $k, string $new) => $new !== '' ? $new : ($old[$k] ?? '');
    $ts = ec_turnstile_keys();
    update_option('ec_turnstile', ['site' => sanitize_text_field($p['ts_site'] ?? ''), 'secret' => $keep($ts, 'secret', sanitize_text_field($p['ts_secret'] ?? ''))]);
    $tg = (array) get_option('ec_telegram', []);
    update_option('ec_telegram', [
        'bot' => $bot,
        'token' => $keep($tg, 'token', preg_replace('/[^0-9A-Za-z:_-]/', '', (string) ($p['tg_token'] ?? ''))),
        'admin_chat' => preg_replace('/[^0-9-]/', '', (string) ($p['tg_admin_chat'] ?? '')),
    ]);
    update_option('ec_subdomains', !empty($p['subdomains']) ? 1 : 0);
    $cf = (array) get_option('ec_cloudflare', []);
    update_option('ec_cloudflare', [
        'account' => preg_replace('/[^a-f0-9]/', '', strtolower((string) ($p['cf_account'] ?? ''))),
        'token' => $keep($cf, 'token', sanitize_text_field($p['cf_token'] ?? '')),
    ]);
    ec_recompute_price_gbp();
    if ($sideChanged && get_option('ec_rates_auto', 1)) {
        ec_rates_maybe_update(true);
    }
    ec_admin_back('ec-settings', 'Ayarlar kaydedildi.');
});

function ec_admin_projects(): void
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('projects') . ' ORDER BY id DESC', ARRAY_A);
    echo '<div class="wrap"><h1>Projeler</h1>';
    ec_admin_notice();
    echo '<table class="widefat striped"><thead><tr><th>No</th><th>Proje</th><th>Firma</th><th>Konum</th><th>Konut</th><th>Teslim</th><th>Fiyat aralığı (GBP)</th><th></th></tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="8">Henüz proje yok.</td></tr>';
    }
    foreach ($rows as $r) {
        $p = ec_project_present($r, 'tr');
        $del = wp_nonce_url(admin_url('admin-post.php?action=ec_project_del&id=' . $r['id']), 'ec_project_del_' . $r['id']);
        echo '<tr><td>' . (int) $r['id'] . '</td><td><a href="' . esc_url($p['url']) . '" target="_blank">' . esc_html($r['name']) . '</a>' . ($r['cover_path'] ? '' : ' <em>(görsel yok)</em>') . '</td><td>' . esc_html((string) $r['developer']) . '</td><td>' . esc_html($p['city_name'] . ($r['district'] ? ' / ' . $r['district'] : '')) . '</td><td>' . esc_html((string) $r['units']) . '</td><td>' . esc_html((string) $r['delivery_date']) . '</td><td>' . esc_html(number_format((float) $r['price_min_gbp'], 0, ',', '.') . ' – ' . number_format((float) $r['price_max_gbp'], 0, ',', '.')) . '</td><td><a class="button-link-delete" href="' . esc_url($del) . '" onclick="return confirm(\'Proje silinsin mi?\')">Sil</a></td></tr>';
    }
    echo '</tbody></table>';

    $cities = array_map(fn($c) => $c['name']['tr'], ec_cities());
    echo '<h2 style="margin-top:28px">Yeni proje</h2><form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('ec_project_new');
    echo '<input type="hidden" name="action" value="ec_project_new"><table class="form-table">';
    echo '<tr><th>Proje adı</th><td><input name="name" class="regular-text" required></td></tr>';
    echo '<tr><th>İnşaat firması</th><td><input name="developer" class="regular-text"></td></tr>';
    echo '<tr><th>Şehir / Bölge</th><td>' . ec_admin_select('city', $cities) . ' <input name="district" placeholder="Bölge"></td></tr>';
    echo '<tr><th>Konut sayısı / Teslim</th><td><input name="units" type="number" min="0" style="width:90px"> <input name="delivery_date" type="month"></td></tr>';
    echo '<tr><th>Fiyat aralığı (GBP)</th><td><input name="price_min_gbp" type="number" min="0" placeholder="En az"> – <input name="price_max_gbp" type="number" min="0" placeholder="En çok"></td></tr>';
    echo '<tr><th>Ödeme planı</th><td><input name="payment_plan" class="regular-text" placeholder="%35 peşin, 36 ay taksit"></td></tr>';
    foreach (['tr' => 'Türkçe', 'en' => 'İngilizce', 'ru' => 'Rusça'] as $l => $n) {
        echo '<tr><th>Açıklama (' . $n . ')</th><td><textarea name="desc_' . $l . '" rows="3" class="large-text"></textarea></td></tr>';
    }
    echo '<tr><th>Kapak görseli</th><td><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></td></tr>';
    echo '</table>';
    submit_button('Projeyi kaydet');
    echo '</form></div>';
}

add_action('admin_post_ec_project_new', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_project_new')) {
        wp_die('Yetkisiz işlem');
    }
    $id = ec_project_create(wp_unslash($_POST), 'panel');
    if (is_wp_error($id)) {
        wp_die(esc_html($id->get_error_message()));
    }
    $note = '';
    if (!empty($_FILES['cover']['tmp_name']) && ($_FILES['cover']['error'] ?? 1) === UPLOAD_ERR_OK && is_uploaded_file($_FILES['cover']['tmp_name'])) {
        $r = ec_project_set_cover($id, $_FILES['cover']['tmp_name']);
        $note = is_wp_error($r) ? ' Görsel yüklenemedi: ' . esc_html($r->get_error_message()) : '';
    }
    ec_admin_back('ec-projects', 'Proje #' . $id . ' kaydedildi.' . $note);
});

add_action('admin_post_ec_project_del', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_project_del_' . $id)) {
        wp_die('Yetkisiz işlem');
    }
    ec_project_delete($id);
    ec_admin_back('ec-projects', 'Proje #' . $id . ' silindi.');
});

function ec_admin_ai(): void
{
    global $wpdb;
    $cf = (array) get_option('ec_cloudflare', []);
    echo '<div class="wrap"><h1>Temsili görsel üret (Cloudflare Workers AI)</h1>';
    ec_admin_notice();
    if (empty($cf['account']) || empty($cf['token'])) {
        echo '<div class="notice notice-warning"><p>Önce <a href="' . esc_url(admin_url('admin.php?page=ec-settings')) . '">Ayarlar</a> sayfasında Cloudflare Account ID ve API Token girin.</p></div>';
    }
    echo '<p><b>Dikkat:</b> Yapay zekâ ile üretilen görseller ilan fotoğrafı olarak kullanılmamalıdır. Blog, slider ve fotoğrafı henüz olmayan projeler için “temsili görsel” etiketiyle kullanın.</p>';
    $projects = $wpdb->get_results('SELECT id, name FROM ' . ec_table('projects') . ' ORDER BY id DESC LIMIT 100', ARRAY_A);
    $posts = get_posts(['numberposts' => 50, 'post_status' => ['publish', 'draft']]);
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('ec_ai');
    echo '<input type="hidden" name="action" value="ec_ai"><table class="form-table">';
    echo '<tr><th>Görsel tarifi (İngilizce daha iyi sonuç verir)</th><td><textarea name="prompt" rows="4" class="large-text" required placeholder="Modern apartment complex with pool on the Mediterranean coast of North Cyprus, sunset, architectural photography, no people, no text"></textarea>';
    echo '<p class="description">Hazır örnekler: <a href="#" class="ec-p">Modern villa with infinity pool overlooking the sea in Kyrenia, golden hour, architectural photography, no text</a> · <a href="#" class="ec-p">Aerial view of a new residential development near Long Beach Iskele, Mediterranean, daylight, no text</a> · <a href="#" class="ec-p">Cozy furnished living room with sea view balcony, bright, interior photography, no text</a></p></td></tr>';
    echo '<tr><th>Nereye kaydedilsin?</th><td><select name="target"><option value="media">Sadece medya kütüphanesi</option>';
    foreach ($projects as $p) {
        echo '<option value="project:' . (int) $p['id'] . '">Proje kapağı: ' . esc_html($p['name']) . '</option>';
    }
    foreach ($posts as $p) {
        echo '<option value="post:' . (int) $p->ID . '">Blog öne çıkan görsel: ' . esc_html(get_the_title($p)) . '</option>';
    }
    echo '</select></td></tr></table>';
    submit_button('Görsel üret');
    echo '</form><script>document.querySelectorAll(".ec-p").forEach(function(a){a.onclick=function(e){e.preventDefault();document.querySelector("textarea[name=prompt]").value=a.textContent;};});</script>';
    $recent = get_posts(['post_type' => 'attachment', 'numberposts' => 8, 'meta_key' => '_ec_ai_image', 'meta_value' => 1]);
    if ($recent) {
        echo '<h2>Son üretilenler</h2><div style="display:flex;gap:10px;flex-wrap:wrap">';
        foreach ($recent as $r) {
            echo '<a href="' . esc_url(get_edit_post_link($r->ID)) . '">' . wp_get_attachment_image($r->ID, [200, 130]) . '</a>';
        }
        echo '</div>';
    }
    echo '</div>';
}

add_action('admin_post_ec_ai', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_ai')) {
        wp_die('Yetkisiz işlem');
    }
    @set_time_limit(120);
    $prompt = (string) wp_unslash($_POST['prompt'] ?? '');
    [$kind, $tid] = array_pad(explode(':', sanitize_text_field($_POST['target'] ?? 'media')), 2, 0);
    $file = ec_cf_generate_image($prompt);
    if (is_wp_error($file)) {
        ec_admin_back('ec-ai', esc_html($file->get_error_message()));
    }
    if ($kind === 'project' && (int) $tid) {
        $r = ec_project_set_cover((int) $tid, $file, true);
        @unlink($file);
        ec_admin_back('ec-ai', is_wp_error($r) ? esc_html($r->get_error_message()) : 'Görsel üretildi ve projenin kapağı yapıldı (“Temsili görsel” etiketiyle gösterilir).');
    }
    $id = ec_cf_to_media($file, wp_trim_words($prompt, 6, ''), $kind === 'post' ? (int) $tid : 0);
    if (is_wp_error($id)) {
        ec_admin_back('ec-ai', esc_html($id->get_error_message()));
    }
    if ($kind === 'post' && (int) $tid) {
        set_post_thumbnail((int) $tid, $id);
    }
    ec_admin_back('ec-ai', 'Görsel üretildi ve medya kütüphanesine eklendi.' . ($kind === 'post' ? ' Blog yazısının öne çıkan görseli yapıldı.' : ''));
});
