<?php
/**
 * Yönetim: Emlak → Paketler ve Emlak → Sosyal medya (0.5)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_submenu_page('ec-listings', 'Paketler', 'Paketler', 'manage_options', 'ec-packages', 'ec_admin_packages');
    add_submenu_page('ec-listings', 'Sosyal medya', 'Sosyal medya', 'manage_options', 'ec-social', 'ec_admin_social');
}, 15);

function ec_admin_check(string $nonce): array
{
    if (!current_user_can('manage_options') || !check_admin_referer($nonce)) {
        wp_die('Yetkisiz işlem');
    }
    return wp_unslash($_POST);
}

function ec_admin_form(string $action, string $inner, string $button = 'Kaydet', string $cls = 'button button-primary'): string
{
    return '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline">' . wp_nonce_field($action, '_wpnonce', true, false)
        . '<input type="hidden" name="action" value="' . esc_attr($action) . '">' . $inner . ' <button class="' . esc_attr($cls) . '">' . esc_html($button) . '</button></form>';
}

/* ================= Paketler ================= */

function ec_admin_packages(): void
{
    global $wpdb;
    $feats = ec_package_features();
    $pkgs = ec_packages();
    $agents = $wpdb->get_results('SELECT id, name, kind FROM ' . ec_table('agents') . " WHERE status <> 'suspended' ORDER BY name", ARRAY_A);
    $subs = $wpdb->get_results('SELECT s.*, a.name AS agent_name FROM ' . ec_table('subscriptions') . ' s JOIN ' . ec_table('agents') . " a ON a.id = s.agent_id WHERE s.status = 'active' ORDER BY s.ends_at ASC", ARRAY_A);
    $old = $wpdb->get_results('SELECT s.*, a.name AS agent_name FROM ' . ec_table('subscriptions') . ' s JOIN ' . ec_table('agents') . " a ON a.id = s.agent_id WHERE s.status <> 'active' ORDER BY s.ends_at DESC LIMIT 30", ARRAY_A);
    $fl = fn($csv) => esc_html(implode(', ', array_map(fn($f) => ['syndication' => '101evler/hangiev', 'social' => 'Sosyal medya', 'telegram' => 'Telegram'][$f] ?? $f, array_filter(explode(',', $csv)))));

    echo '<div class="wrap"><h1>Paketler</h1>';
    ec_admin_notice();
    echo '<p>Paketleri sadece siz verirsiniz. İstediğiniz üyeye istediğiniz süreyle ve kotayla paket tanımlayın, uzatın ya da iptal edin. Ödeme şimdilik site dışında (havale, elden) alınır. <b>Kota:</b> paket süresince otomasyonla işlenen farklı ilan sayısıdır (0 = sınırsız). Bir ilan hem 101evler’e, hem hangiev’e, hem sosyal medyaya gitse de 1 sayılır.</p>';

    // Üyeye paket ver
    $agentOpts = '';
    foreach ($agents as $a) {
        $agentOpts .= '<option value="' . (int) $a['id'] . '">' . esc_html($a['name'] . ' (' . (ec_member_kinds()[$a['kind']]['name']['tr'] ?? $a['kind']) . ')') . '</option>';
    }
    $pkgOpts = '';
    foreach ($pkgs as $p) {
        if ($p['active']) {
            $pkgOpts .= '<option value="' . (int) $p['id'] . '" data-days="' . (int) $p['days'] . '" data-quota="' . (int) $p['quota'] . '">' . esc_html($p['name'] . ' — ' . $p['days'] . ' gün, ' . ($p['quota'] ? $p['quota'] . ' ilan' : 'sınırsız') . ($p['price'] ? ' · ' . $p['price'] : '')) . '</option>';
        }
    }
    echo '<h2>Üyeye paket ver</h2><div class="card" style="max-width:900px">';
    echo ec_admin_form('ec_pkg_grant', '<table class="form-table">'
        . '<tr><th>Üye</th><td><select name="agent_id" required style="min-width:320px"><option value="">— seçin —</option>' . $agentOpts . '</select></td></tr>'
        . '<tr><th>Paket</th><td><select name="package_id" required>' . $pkgOpts . '</select></td></tr>'
        . '<tr><th>Başlangıç</th><td><input type="date" name="starts" value="' . esc_attr(wp_date('Y-m-d')) . '"></td></tr>'
        . '<tr><th>Süre</th><td><input type="number" name="days" min="1" max="3650" placeholder="paketteki gün"> gün <b>ya da</b> bitiş tarihi <input type="date" name="ends"><p class="description">Boş bırakırsanız paketin süresi kullanılır. İstediğiniz süreyi yazabilirsiniz (ör. 45 gün ya da 31.12.2026).</p></td></tr>'
        . '<tr><th>Kota (ilan)</th><td><input type="number" name="quota" min="0" placeholder="paketteki kota"><p class="description">Boşsa paketteki kota. 0 = sınırsız.</p></td></tr>'
        . '<tr><th>Not</th><td><input name="note" class="regular-text" placeholder="ör. 50 € havale, 21.11 alındı"></td></tr>'
        . '</table>', 'Paketi ver');
    echo '</div>';

    // Aktif paketler
    echo '<h2>Aktif paketler</h2><table class="widefat striped"><thead><tr><th>Üye</th><th>Paket</th><th>Özellikler</th><th>Kullanım</th><th>Bitiş</th><th>Not</th><th></th></tr></thead><tbody>';
    foreach ($subs as $s) {
        $used = ec_subscription_used((int) $s['id']);
        $left = (int) ceil((strtotime($s['ends_at'] . ' UTC') - time()) / DAY_IN_SECONDS);
        echo '<tr><td><b>' . esc_html($s['agent_name']) . '</b></td><td>' . esc_html($s['name']) . '</td><td>' . $fl($s['features']) . '</td>'
            . '<td>' . $used . ' / ' . ((int) $s['quota'] ?: '∞') . '</td>'
            . '<td' . ($left <= 3 ? ' style="color:#b32d2e"' : '') . '>' . esc_html(get_date_from_gmt($s['ends_at'], 'd.m.Y')) . ' (' . max(0, $left) . ' gün)</td><td>' . esc_html((string) $s['note']) . '</td><td>'
            . ec_admin_form('ec_pkg_extend', '<input type="hidden" name="sub" value="' . (int) $s['id'] . '"><input type="number" name="days" value="30" min="1" style="width:70px"> gün', 'Uzat', 'button')
            . ' ' . ec_admin_form('ec_pkg_cancel', '<input type="hidden" name="sub" value="' . (int) $s['id'] . '">', 'İptal', 'button button-link-delete') . '</td></tr>';
    }
    if (!$subs) {
        echo '<tr><td colspan="7">Aktif paket yok.</td></tr>';
    }
    echo '</tbody></table>';

    // Paket tanımları
    echo '<h2>Paket tanımları</h2><p class="description">Ad · gün · kota (0 = sınırsız) · otomasyon özellikleri · fiyat yazısı · satışta mı · vitrin kredileri · satış fiyatı. Son satır yeni paket ekler. Vitrin paketi için kotayı 0, özellikleri boş bırakıp sadece vitrin kredisi girin.</p>';
    foreach (array_merge($pkgs, [['id' => 0, 'name' => '', 'days' => 30, 'quota' => 0, 'features' => 'syndication,social,telegram', 'price' => '', 'active' => 1, 'promos' => '', 'promo_days' => 7, 'amount' => '', 'currency' => 'EUR']]) as $p) {
        $fc = '';
        foreach ($feats as $k => $n) {
            $fc .= '<label style="margin-right:10px"><input type="checkbox" name="features[]" value="' . $k . '"' . checked(in_array($k, explode(',', $p['features']), true), true, false) . '> ' . esc_html(['syndication' => '101evler/hangiev', 'social' => 'Sosyal medya', 'telegram' => 'Telegram'][$k]) . '</label>';
        }
        $pr = ec_promos_parse($p['promos'] ?? '');
        $promoFields = '<br><span style="display:inline-block;margin-top:6px"><b>Vitrin kredileri:</b> ';
        foreach (ec_promo_kinds() as $pk => $pd) {
            $promoFields .= '<label style="margin-right:8px">' . esc_html($pd['icon'] . ' ' . $pd['name']['tr']) . ' <input type="number" name="promos[' . $pk . ']" value="' . (int) ($pr[$pk] ?? 0) . '" min="0" style="width:56px"></label>';
        }
        $promoFields .= ' · her vitrin <input type="number" name="promo_days" value="' . (int) ($p['promo_days'] ?? 7) . '" min="1" max="365" style="width:56px"> gün</span>'
            . '<br><span style="display:inline-block;margin-top:6px"><b>Satış fiyatı:</b> <input type="number" step="0.01" min="0" name="amount" value="' . esc_attr((string) ($p['amount'] ?? '')) . '" style="width:90px"> '
            . ec_admin_select('currency', ['EUR' => 'EUR', 'GBP' => 'GBP', 'USD' => 'USD', 'TRY' => 'TRY'], (string) ($p['currency'] ?? 'EUR')) . ' <span class="description">(online satın alma için; boşsa üye satın alamaz, sadece siz verirsiniz)</span></span>';
        echo '<div class="card" style="max-width:none;margin:6px 0;padding:8px 12px">' . ec_admin_form('ec_pkg_save', '<input type="hidden" name="id" value="' . (int) $p['id'] . '">'
            . '<input name="name" placeholder="Yeni paket adı" value="' . esc_attr($p['name']) . '" style="width:220px"> '
            . '<input type="number" name="days" value="' . (int) $p['days'] . '" min="1" style="width:70px" title="gün"> gün '
            . '<input type="number" name="quota" value="' . (int) $p['quota'] . '" min="0" style="width:70px" title="kota"> ilan &nbsp; ' . $fc
            . '<input name="price" value="' . esc_attr((string) $p['price']) . '" placeholder="fiyat yazısı" style="width:80px"> '
            . '<label><input type="checkbox" name="active" value="1"' . checked((int) $p['active'], 1, false) . '> satışta</label>' . $promoFields, $p['id'] ? 'Kaydet' : 'Ekle', 'button') . '</div>';
    }

    echo ec_admin_orders_html();

    $req = array_merge(['syndication' => 1, 'telegram' => 0], (array) get_option('ec_pkg_required', []));
    echo '<h2>Hangi özellikler paket gerektirsin?</h2>' . ec_admin_form('ec_pkg_required',
        '<p><label><input type="checkbox" checked disabled> Sosyal medya paylaşımı (her zaman ücretli)</label><br>'
        . '<label><input type="checkbox" name="syndication" value="1"' . checked((int) $req['syndication'], 1, false) . '> 101evler / hangiev aktarımı (Chrome eklentisi)</label><br>'
        . '<label><input type="checkbox" name="telegram" value="1"' . checked((int) $req['telegram'], 1, false) . '> Telegram’dan ilan verme</label></p>');

    if ($old) {
        echo '<h2>Geçmiş paketler</h2><table class="widefat striped"><tbody>';
        foreach ($old as $s) {
            echo '<tr><td>' . esc_html($s['agent_name']) . '</td><td>' . esc_html($s['name']) . '</td><td>' . ec_subscription_used((int) $s['id']) . ' / ' . ((int) $s['quota'] ?: '∞') . '</td><td>' . esc_html(get_date_from_gmt($s['ends_at'], 'd.m.Y')) . '</td><td>' . esc_html($s['status'] === 'cancelled' ? 'iptal' : 'süresi doldu') . '</td><td>' . esc_html((string) $s['note']) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

add_action('admin_post_ec_pkg_grant', function () {
    $p = ec_admin_check('ec_pkg_grant');
    $r = ec_subscription_grant((int) $p['agent_id'], [
        'package_id' => (int) $p['package_id'], 'starts' => $p['starts'] ?? '', 'days' => $p['days'] !== '' ? (int) $p['days'] : null,
        'ends' => $p['ends'] ?? '', 'quota' => $p['quota'] ?? '', 'note' => $p['note'] ?? '',
    ]);
    ec_admin_back('ec-packages', is_wp_error($r) ? esc_html($r->get_error_message()) : 'Paket verildi; üyeye e-posta gönderildi.');
});
add_action('admin_post_ec_pkg_extend', function () {
    $p = ec_admin_check('ec_pkg_extend');
    ec_subscription_extend((int) $p['sub'], max(1, (int) $p['days']));
    ec_admin_back('ec-packages', 'Paket ' . (int) $p['days'] . ' gün uzatıldı.');
});
add_action('admin_post_ec_pkg_cancel', function () {
    $p = ec_admin_check('ec_pkg_cancel');
    ec_subscription_cancel((int) $p['sub']);
    ec_admin_back('ec-packages', 'Paket iptal edildi.');
});
add_action('admin_post_ec_pkg_save', function () {
    global $wpdb;
    $p = ec_admin_check('ec_pkg_save');
    $d = [
        'name' => mb_substr(sanitize_text_field($p['name'] ?? ''), 0, 120), 'days' => max(1, (int) $p['days']), 'quota' => max(0, (int) $p['quota']),
        'features' => implode(',', array_intersect((array) ($p['features'] ?? []), array_keys(ec_package_features()))),
        'price' => mb_substr(sanitize_text_field($p['price'] ?? ''), 0, 40), 'active' => !empty($p['active']) ? 1 : 0,
        'promos' => ec_promos_format(array_map('intval', (array) ($p['promos'] ?? []))), 'promo_days' => max(1, min(365, (int) ($p['promo_days'] ?? 7))),
        'amount' => ($p['amount'] ?? '') === '' ? null : round(max(0, (float) $p['amount']), 2),
        'currency' => in_array($p['currency'] ?? '', ['EUR', 'GBP', 'USD', 'TRY'], true) ? $p['currency'] : 'EUR',
    ];
    if ($d['name'] === '') {
        ec_admin_back('ec-packages', 'Paket adı boş olamaz.');
    }
    (int) $p['id'] ? $wpdb->update(ec_table('packages'), $d, ['id' => (int) $p['id']]) : $wpdb->insert(ec_table('packages'), $d + ['sort' => 99, 'created_at' => ec_now()]);
    ec_admin_back('ec-packages', 'Paket kaydedildi.');
});
add_action('admin_post_ec_pkg_required', function () {
    $p = ec_admin_check('ec_pkg_required');
    update_option('ec_pkg_required', ['syndication' => !empty($p['syndication']) ? 1 : 0, 'telegram' => !empty($p['telegram']) ? 1 : 0]);
    ec_admin_back('ec-packages', 'Kaydedildi.');
});

/* ================= Sosyal medya ================= */

function ec_admin_social(): void
{
    global $wpdb;
    $s = ec_social_settings();
    $agents = $wpdb->get_results('SELECT id, name FROM ' . ec_table('agents') . " WHERE status = 'active' ORDER BY name", ARRAY_A);
    $accs = ec_social_accounts();
    echo '<div class="wrap"><h1>Sosyal medya paylaşımı</h1>';
    ec_admin_notice();
    echo '<p>Yayına giren ilanlar <b>sitenin</b> Facebook sayfasında ve Instagram hesabında her zaman paylaşılır. <b>Üyelerin</b> sayfalarında ise paketi varsa ve üye otomatik paylaşımı açtıysa paylaşılır. Facebook gruplarına paylaşım yoktur. Kurulum tarifi: <code>docs/sosyal-medya-kurulum.md</code> (resimli rehber: <code>docs/sosyal-medya-rehberi.html</code>).</p>';

    // 1. Meta uygulaması
    echo '<h2>1. Meta uygulaması (bir kez)</h2>' . ec_admin_form('ec_social_app', '<table class="form-table">'
        . '<tr><th>Uygulama No (App ID)</th><td><input name="app_id" class="regular-text" value="' . esc_attr($s['app_id']) . '"></td></tr>'
        . '<tr><th>Uygulama Gizli Anahtarı (App Secret)</th><td><input name="app_secret" type="password" class="regular-text" autocomplete="new-password" placeholder="' . ($s['app_secret'] ? '•••••••• (kayıtlı)' : '') . '"></td></tr>'
        . '<tr><th>Bu adresi Meta’ya yapıştırın</th><td><input readonly class="large-text code" value="' . esc_attr(ec_fb_redirect_uri()) . '" onclick="this.select()"><p class="description">developers.facebook.com → uygulamanız → Facebook Girişi (Facebook Login for Business) → Ayarlar → <b>Geçerli OAuth Yönlendirme URI’leri</b>.</p></td></tr>'
        . '<tr><th>Graph API sürümü</th><td><input name="graph_ver" value="' . esc_attr($s['graph_ver']) . '" style="width:90px"></td></tr>'
        . '</table>');

    // 2. Bağlan
    echo '<h2>2. Facebook hesabınızla bağlanın</h2>';
    if (!$s['app_id'] || !$s['app_secret']) {
        echo '<p>Önce 1. adımı tamamlayın.</p>';
    } else {
        echo '<p>' . ($s['user_name'] ? '✅ Bağlı hesap: <b>' . esc_html($s['user_name']) . '</b> (' . esc_html(get_date_from_gmt($s['connected_at'], 'd.m.Y H:i')) . '). ' : '') . 'Bu hesabın yönettiği <b>tüm sayfalar</b> (müşterilerin size erişim verdiği sayfalar dahil) aşağıya gelir.</p>';
        echo '<p><a class="button button-primary button-hero" href="' . esc_url(ec_fb_login_url()) . '">Facebook ile bağlan</a> ';
        if ($s['user_token']) {
            echo ec_admin_form('ec_social_sync', '', 'Sayfa listesini yenile', 'button');
        }
        echo '</p><details><summary>Yedek yol: anahtarı elle yapıştır</summary><p>Graph API Explorer’da aldığınız <b>kullanıcı</b> ya da <b>sayfa</b> anahtarını yapıştırın.</p>'
            . ec_admin_form('ec_social_token', '<input name="token" class="large-text code" placeholder="EAAG…">', 'Ekle', 'button') . '</details>';
    }

    // 3. Sayfalar
    echo '<h2>3. Sayfalar ve sahipleri</h2>';
    if (!$accs) {
        echo '<p>Henüz sayfa yok.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>Facebook sayfası</th><th>Instagram</th><th>Kimin?</th><th>Paylaş</th><th>Durum</th><th></th></tr></thead><tbody>';
        foreach ($accs as $a) {
            $opts = '<option value="">— atanmadı (paylaşılmaz) —</option><option value="0"' . selected($a['agent_id'], '0', false) . '>★ Site (' . esc_html(get_bloginfo('name')) . ')</option>';
            foreach ($agents as $ag) {
                $opts .= '<option value="' . (int) $ag['id'] . '"' . selected((string) $a['agent_id'], (string) $ag['id'], false) . '>' . esc_html($ag['name']) . '</option>';
            }
            $st = $a['status'] === 'ok' ? '✅' : '⛔ ' . esc_html((string) $a['last_error']);
            echo '<tr><td><b>' . esc_html($a['page_name']) . '</b><br><span class="description">' . esc_html($a['page_id']) . '</span></td>'
                . '<td>' . ($a['ig_username'] ? '@' . esc_html($a['ig_username']) : '<span class="description">bağlı değil</span>') . '</td><td colspan="2">'
                . ec_admin_form('ec_social_assign', '<input type="hidden" name="id" value="' . (int) $a['id'] . '"><select name="agent_id">' . $opts . '</select> '
                    . '<label><input type="checkbox" name="fb_on" value="1"' . checked((int) $a['fb_on'], 1, false) . '> Facebook</label> '
                    . '<label><input type="checkbox" name="ig_on" value="1"' . checked((int) $a['ig_on'], 1, false) . ($a['ig_user_id'] ? '' : ' disabled') . '> Instagram</label>', 'Kaydet', 'button')
                . '</td><td>' . $st . '</td><td>'
                . ec_admin_form('ec_social_test', '<input type="hidden" name="id" value="' . (int) $a['id'] . '"><input name="listing" placeholder="İlan no" style="width:80px">', 'Şimdi paylaş', 'button')
                . ' ' . ec_admin_form('ec_social_del', '<input type="hidden" name="id" value="' . (int) $a['id'] . '">', 'Sil', 'button button-link-delete') . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    // 4. Ayarlar ve şablon
    $ph = '{baslik} {konum} {fiyat} {bilgi} {ozellikler} {aciklama} {imza} {link} {ilan_no} {etiketler}';
    $tplRows = '';
    foreach (['tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский'] as $l => $n) {
        $tplRows .= '<tr><th>' . $n . '</th><td><textarea name="tpl_' . $l . '" rows="6" class="large-text code" placeholder="' . esc_attr(ec_social_default_template($l)) . '">' . esc_textarea($s['tpl_' . $l]) . '</textarea></td></tr>';
    }
    echo '<h2>4. Ayarlar ve gönderi şablonu</h2>' . ec_admin_form('ec_social_opts', '<table class="form-table">'
        . '<tr><th>Sitenin hesapları</th><td><label><input type="checkbox" name="site_auto" value="1"' . checked((int) $s['site_auto'], 1, false) . '> Yayına giren her ilan sitenin sayfasında da paylaşılsın</label></td></tr>'
        . '<tr><th>En çok fotoğraf</th><td><input type="number" name="max_photos" min="1" max="10" value="' . (int) $s['max_photos'] . '"> (Instagram en çok 10)</td></tr>'
        . $tplRows . '<tr><th></th><td><p class="description">Boş bırakılırsa varsayılan şablon. Kullanılabilir alanlar: <code>' . esc_html($ph) . '</code>. Instagram’da {link} yerine “İlan no + site adı (profildeki link)” yazılır, çünkü Instagram açıklamadaki linkler tıklanmaz.</p></td></tr>'
        . '</table>');

    // Son paylaşımlar
    $recent = ec_social_recent(null, 40);
    echo '<h2>Son paylaşımlar</h2><table class="widefat striped"><thead><tr><th>İlan</th><th>Hesap</th><th>Ağ</th><th>Durum</th><th>Zaman</th><th></th></tr></thead><tbody>';
    foreach ($recent as $r) {
        $stl = ['done' => '✅ paylaşıldı', 'queued' => '🕓 sırada', 'failed' => '⛔ hata', 'skipped' => '⏭ atlandı'][$r['status']] ?? $r['status'];
        echo '<tr><td>#' . (int) $r['listing_id'] . '</td><td>' . esc_html($r['page_name']) . '</td><td>' . esc_html($r['network']) . '</td><td>' . esc_html($stl) . ($r['error'] ? '<br><span class="description">' . esc_html($r['error']) . '</span>' : '') . '</td>'
            . '<td>' . esc_html(get_date_from_gmt($r['updated_at'], 'd.m H:i')) . '</td><td>' . ($r['permalink'] ? '<a href="' . esc_url($r['permalink']) . '" target="_blank">aç ↗</a>' : '')
            . (in_array($r['status'], ['failed', 'skipped'], true) ? ' ' . ec_admin_form('ec_social_retry', '<input type="hidden" name="id" value="' . (int) $r['id'] . '">', 'Tekrar dene', 'button-link') : '') . '</td></tr>';
    }
    if (!$recent) {
        echo '<tr><td colspan="6">Henüz paylaşım yok.</td></tr>';
    }
    echo '</tbody></table></div>';
}

add_action('admin_post_ec_social_app', function () {
    $p = ec_admin_check('ec_social_app');
    $s = ec_social_settings();
    $s['app_id'] = preg_replace('/\D/', '', (string) $p['app_id']);
    if (trim((string) $p['app_secret']) !== '') {
        $s['app_secret'] = ec_encrypt(preg_replace('/[^a-f0-9]/i', '', (string) $p['app_secret']));
    }
    $s['graph_ver'] = preg_match('/^v\d{2}\.0$/', (string) $p['graph_ver']) ? $p['graph_ver'] : 'v24.0';
    ec_social_save($s);
    ec_admin_back('ec-social', 'Uygulama bilgileri kaydedildi. Şimdi “Facebook ile bağlan”a basın.');
});

add_action('admin_post_ec_fb_callback', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkisiz');
    }
    if (!wp_verify_nonce($_GET['state'] ?? '', 'ec_fb_state')) {
        ec_admin_back('ec-social', 'Güvenlik kontrolü başarısız; tekrar deneyin.');
    }
    if (!empty($_GET['error'])) {
        ec_admin_back('ec-social', 'Facebook izin vermedi: ' . esc_html(sanitize_text_field(wp_unslash($_GET['error_description'] ?? $_GET['error']))));
    }
    $s = ec_social_settings();
    $r = ec_graph('GET', 'oauth/access_token', ['client_id' => $s['app_id'], 'client_secret' => ec_decrypt($s['app_secret']), 'redirect_uri' => ec_fb_redirect_uri(), 'code' => sanitize_text_field(wp_unslash($_GET['code'] ?? ''))]);
    if (is_wp_error($r)) {
        ec_admin_back('ec-social', esc_html($r->get_error_message()));
    }
    $n = ec_fb_connect_user((string) $r['access_token']);
    ec_admin_back('ec-social', is_wp_error($n) ? esc_html($n->get_error_message()) : "Bağlandı. $n sayfa bulundu; aşağıda her sayfayı bir üyeye ya da siteye atayın.");
});

add_action('admin_post_ec_social_sync', function () {
    ec_admin_check('ec_social_sync');
    $n = ec_fb_sync_pages();
    ec_admin_back('ec-social', is_wp_error($n) ? esc_html($n->get_error_message()) : "$n sayfa güncellendi.");
});

add_action('admin_post_ec_social_token', function () {
    $p = ec_admin_check('ec_social_token');
    $tok = trim((string) $p['token']);
    $s = ec_social_settings();
    // Önce kullanıcı anahtarı gibi dene (uzun ömürlüye çevrilebilir), olmazsa sayfa anahtarı say
    $r = ($s['app_id'] && $s['app_secret']) ? ec_fb_connect_user($tok) : new WP_Error('x', '');
    if (is_wp_error($r) || $r === 0) {
        $r2 = ec_fb_add_page_token($tok);
        ec_admin_back('ec-social', is_wp_error($r2) ? esc_html($r2->get_error_message()) : 'Sayfa eklendi.');
    }
    ec_admin_back('ec-social', "$r sayfa bulundu.");
});

add_action('admin_post_ec_social_assign', function () {
    global $wpdb;
    $p = ec_admin_check('ec_social_assign');
    $wpdb->update(ec_table('social_accounts'), [
        'agent_id' => $p['agent_id'] === '' ? null : (int) $p['agent_id'],
        'fb_on' => !empty($p['fb_on']) ? 1 : 0, 'ig_on' => !empty($p['ig_on']) ? 1 : 0, 'updated_at' => ec_now(),
    ], ['id' => (int) $p['id']]);
    ec_admin_back('ec-social', 'Kaydedildi.');
});

add_action('admin_post_ec_social_del', function () {
    global $wpdb;
    $p = ec_admin_check('ec_social_del');
    $wpdb->delete(ec_table('social_accounts'), ['id' => (int) $p['id']]);
    ec_admin_back('ec-social', 'Sayfa kaldırıldı.');
});

add_action('admin_post_ec_social_test', function () {
    global $wpdb;
    $p = ec_admin_check('ec_social_test');
    $acc = ec_social_account((int) $p['id']);
    $lid = (int) $p['listing'] ?: (int) $wpdb->get_var('SELECT id FROM ' . ec_table('listings') . " WHERE status = 'published' ORDER BY published_at DESC LIMIT 1");
    if (!$acc || !$lid) {
        ec_admin_back('ec-social', 'Hesap ya da ilan bulunamadı.');
    }
    $out = [];
    foreach (['facebook' => $acc['fb_on'], 'instagram' => $acc['ig_on'] && $acc['ig_user_id']] as $net => $on) {
        if (!$on) {
            continue;
        }
        $wpdb->query($wpdb->prepare('INSERT INTO ' . ec_table('social_posts') . " (listing_id, account_id, network, status, created_at, updated_at) VALUES (%d, %d, %s, 'queued', %s, %s) ON DUPLICATE KEY UPDATE status = 'queued', attempts = 0, error = NULL", $lid, $acc['id'], $net, ec_now(), ec_now()));
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('social_posts') . ' WHERE listing_id = %d AND account_id = %d AND network = %s', $lid, $acc['id'], $net), ARRAY_A);
        $r = ec_social_process($row);
        $out[] = $net . ': ' . ($r['status'] === 'done' ? 'paylaşıldı ' . ($r['permalink'] ? '<a href="' . esc_url($r['permalink']) . '" target="_blank">aç ↗</a>' : '') : 'hata — ' . esc_html($r['error'] ?? $r['status']));
    }
    ec_admin_back('ec-social', '#' . $lid . ' → ' . implode(' · ', $out ?: ['açık ağ yok']));
});

add_action('admin_post_ec_social_retry', function () {
    global $wpdb;
    $p = ec_admin_check('ec_social_retry');
    $wpdb->update(ec_table('social_posts'), ['status' => 'queued', 'attempts' => 0, 'error' => null], ['id' => (int) $p['id']]);
    wp_schedule_single_event(time() + 5, 'ec_social_run');
    ec_admin_back('ec-social', 'Tekrar sıraya alındı.');
});

add_action('admin_post_ec_social_opts', function () {
    $p = ec_admin_check('ec_social_opts');
    $s = ec_social_settings();
    $s['site_auto'] = !empty($p['site_auto']) ? 1 : 0;
    $s['max_photos'] = max(1, min(10, (int) $p['max_photos']));
    foreach (['tr', 'en', 'ru'] as $l) {
        $s['tpl_' . $l] = ec_clean_textarea((string) ($p['tpl_' . $l] ?? ''));
    }
    ec_social_save($s);
    ec_admin_back('ec-social', 'Kaydedildi.');
});
