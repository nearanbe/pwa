<?php
/**
 * Paket satın alma ve ödeme (1.1).
 *
 * Yöntemler:
 *   bank  Havale / EFT: üye siparişi açar, IBAN + sipariş kodu görür, dekont yükler; yönetici onaylayınca paket tanımlanır.
 *   card  Kartla ödeme: KKTC bankalarının NestPay / Cardplus (Payten) sanal POS'u, "3D Pay Hosting" modeli, hash ver3 (SHA-512).
 *         Kart bilgisi bu siteye hiç gelmez; üye bankanın 3D Secure sayfasında öder. Banka sonucu imzalı döndürür,
 *         imza ve onay kodu doğrulanınca paket kendiliğinden tanımlanır.
 *
 * Ayarlar: ec_payment = [bank_name, holder, iban, bank_note, pos_on, pos_client, pos_key(şifreli), pos_url, pos_test]
 * Sipariş durumu: pending (yeni) → review (dekont yüklendi) → paid (ödendi, paket verildi) | cancelled | failed
 *
 * REST:
 *   GET  /api/v1/me/orders                     üyenin siparişleri + satın alınabilir paketler + ödeme yöntemleri
 *   POST /api/v1/me/orders {package_id, method} sipariş açar; card ise banka formunun alanlarını döner
 *   POST /api/v1/me/orders/{id}/receipt        dekont (jpg/png/pdf)
 *   POST /api/v1/pay/nestpay                   bankanın dönüş adresi (okUrl / failUrl)
 */

if (!defined('ABSPATH')) {
    exit;
}

const EC_CUR_ISO = ['TRY' => '949', 'USD' => '840', 'EUR' => '978', 'GBP' => '826'];

function ec_payment_settings(): array
{
    return array_merge(['bank_name' => '', 'holder' => '', 'iban' => '', 'bank_note' => '', 'pos_on' => 0, 'pos_client' => '', 'pos_key' => '', 'pos_url' => '', 'pos_test' => 1],
        (array) get_option('ec_payment', []));
}

function ec_payment_methods(): array
{
    $s = ec_payment_settings();
    $m = [];
    if ($s['iban']) {
        $m[] = 'bank';
    }
    if ($s['pos_on'] && $s['pos_client'] && $s['pos_key'] && $s['pos_url']) {
        $m[] = 'card';
    }
    return $m;
}

function ec_order_row(int $id): ?array
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('orders') . ' WHERE id = %d', $id), ARRAY_A) ?: null;
}

function ec_order_by_ref(string $ref): ?array
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('orders') . ' WHERE ref = %s', $ref), ARRAY_A) ?: null;
}

/** @return array|WP_Error */
function ec_order_create(array $agent, int $package_id, string $method)
{
    global $wpdb;
    $pkg = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('packages') . ' WHERE id = %d AND active = 1', $package_id), ARRAY_A);
    if (!$pkg || $pkg['amount'] === null || (float) $pkg['amount'] <= 0) {
        return new WP_Error('ec_invalid', 'Bu paket online satın alınamıyor.', ['status' => 400]);
    }
    if (!in_array($method, ec_payment_methods(), true)) {
        return new WP_Error('ec_invalid', 'Bu ödeme yöntemi şu an kullanılamıyor.', ['status' => 400]);
    }
    $ref = 'AC' . gmdate('ymd') . strtoupper(wp_generate_password(6, false, false));
    $wpdb->insert(ec_table('orders'), [
        'agent_id' => (int) $agent['id'], 'package_id' => $package_id, 'amount' => (float) $pkg['amount'], 'currency' => $pkg['currency'] ?: 'EUR',
        'method' => $method, 'status' => 'pending', 'ref' => $ref, 'created_at' => ec_now(),
    ]);
    $o = ec_order_row((int) $wpdb->insert_id);
    if ($method === 'bank') {
        ec_notify_admin(sprintf("🧾 Yeni havale siparişi %s: %s — %s (%s %s)", $ref, $agent['name'], $pkg['name'], number_format((float) $pkg['amount'], 2, ',', '.'), $o['currency']), admin_url('admin.php?page=ec-packages#siparisler'), false);
    }
    return $o;
}

/** Siparişi ödenmiş yapar ve paketi tanımlar (tek sefer) */
function ec_order_mark_paid(array $o, string $bankRef = '', string $note = ''): void
{
    global $wpdb;
    if ($o['status'] === 'paid') {
        return;
    }
    $sub = ec_subscription_grant((int) $o['agent_id'], ['package_id' => (int) $o['package_id'], 'note' => 'Sipariş ' . $o['ref']]);
    $wpdb->update(ec_table('orders'), [
        'status' => 'paid', 'paid_at' => ec_now(), 'bank_ref' => $bankRef ?: $o['bank_ref'], 'note' => $note ?: $o['note'],
        'subscription_id' => is_wp_error($sub) ? null : (int) $sub,
    ], ['id' => $o['id']]);
    $a = ec_agent((int) $o['agent_id']);
    ec_notify_admin(sprintf("✅ Ödeme alındı %s: %s — %s %s (%s)", $o['ref'], $a['name'] ?? '?', number_format((float) $o['amount'], 2, ',', '.'), $o['currency'], $o['method'] === 'card' ? 'kart' : 'havale'), admin_url('admin.php?page=ec-packages#siparisler'), true);
}

/* ---------------- NestPay / Cardplus 3D Pay Hosting (hash ver3) ---------------- */

function ec_nestpay_hash(array $params, string $storeKey): string
{
    foreach (array_keys($params) as $k) {
        if (in_array(strtolower((string) $k), ['hash', 'encoding'], true)) {
            unset($params[$k]);
        }
    }
    $keys = array_keys($params);
    natcasesort($keys);
    $esc = fn($v) => str_replace(['\\', '|'], ['\\\\', '\\|'], (string) $v);
    $plain = '';
    foreach ($keys as $k) {
        $plain .= $esc($params[$k]) . '|';
    }
    $plain .= $esc($storeKey);
    return base64_encode(hash('sha512', $plain, true));
}

/** Banka ödeme sayfasına gidecek form: ['action' => url, 'fields' => [...]] */
function ec_nestpay_form(array $o, string $lang): array
{
    $s = ec_payment_settings();
    $ret = home_url('/api/v1/pay/nestpay');
    $f = [
        'clientid' => $s['pos_client'],
        'storetype' => '3d_pay_hosting',
        'hashAlgorithm' => 'ver3',
        'TranType' => 'Auth',
        'amount' => number_format((float) $o['amount'], 2, '.', ''),
        'currency' => EC_CUR_ISO[$o['currency']] ?? '978',
        'oid' => $o['ref'],
        'okUrl' => $ret,
        'failUrl' => $ret,
        'callbackUrl' => $ret,
        'lang' => $lang === 'tr' ? 'tr' : 'en',
        'rnd' => wp_generate_password(20, false, false),
        'Instalment' => '',
        'refreshtime' => '5',
    ];
    $f['hash'] = ec_nestpay_hash($f, ec_decrypt($s['pos_key']));
    $f['encoding'] = 'utf-8';
    return ['action' => $s['pos_url'], 'fields' => $f];
}

/** Bankanın dönüşünü doğrular. @return array [ok(bool), order|null, mesaj] */
function ec_nestpay_verify(array $post): array
{
    $s = ec_payment_settings();
    $o = ec_order_by_ref((string) ($post['oid'] ?? ''));
    if (!$o) {
        return [false, null, 'Sipariş bulunamadı'];
    }
    $given = (string) ($post['HASH'] ?? $post['hash'] ?? '');
    if (!$given || !hash_equals(ec_nestpay_hash($post, ec_decrypt($s['pos_key'])), $given)) {
        return [false, $o, 'Banka yanıtının imzası doğrulanamadı'];
    }
    $md = (string) ($post['mdStatus'] ?? '');
    $okAuth = in_array($md, ['1', '2', '3', '4'], true);
    $approved = ($post['Response'] ?? '') === 'Approved' && ($post['ProcReturnCode'] ?? '') === '00';
    $amountOk = abs((float) ($post['amount'] ?? 0) - (float) $o['amount']) < 0.01;
    if ($okAuth && $approved && $amountOk) {
        return [true, $o, ''];
    }
    return [false, $o, (string) ($post['ErrMsg'] ?? $post['mdErrorMsg'] ?? 'Ödeme onaylanmadı')];
}

/* ---------------- Dekont (özel klasör) ---------------- */

function ec_receipt_dir(): string
{
    $up = wp_upload_dir();
    $dir = $up['basedir'] . '/ec-ozel';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
        @file_put_contents($dir . '/index.php', "<?php // sessizlik\n");
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    return $dir;
}

/* ---------------- REST ---------------- */

function ec_orders_for(int $agent_id): array
{
    global $wpdb;
    return array_map(fn($o) => [
        'id' => (int) $o['id'], 'ref' => $o['ref'], 'package' => $o['pkg_name'], 'amount' => (float) $o['amount'], 'currency' => $o['currency'],
        'method' => $o['method'], 'status' => $o['status'], 'receipt' => (bool) $o['receipt_path'], 'created_at' => $o['created_at'],
    ], $wpdb->get_results($wpdb->prepare(
        'SELECT o.*, p.name pkg_name FROM ' . ec_table('orders') . ' o LEFT JOIN ' . ec_table('packages') . ' p ON p.id = o.package_id WHERE o.agent_id = %d ORDER BY o.id DESC LIMIT 30',
        $agent_id
    ), ARRAY_A) ?: []);
}

function ec_bank_info(): array
{
    $s = ec_payment_settings();
    return ['bank' => $s['bank_name'], 'holder' => $s['holder'], 'iban' => $s['iban'], 'note' => $s['bank_note']];
}

add_action('rest_api_init', function () {
    register_rest_route(EC_NS, '/me/orders', [
        [
            'methods' => 'GET',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                $a = ec_rest_agent($r);
                return ['orders' => $a ? ec_orders_for((int) $a['id']) : [], 'methods' => ec_payment_methods(), 'bank' => ec_bank_info()];
            },
        ],
        [
            'methods' => 'POST',
            'permission_callback' => 'ec_rest_require_agent',
            'callback' => function (WP_REST_Request $r) {
                $a = ec_rest_agent($r);
                if (!$a) {
                    return new WP_Error('ec_no_agent', 'Üye hesabı gerekli', ['status' => 400]);
                }
                $o = ec_order_create($a, (int) $r->get_param('package_id'), sanitize_key((string) $r->get_param('method')));
                if (is_wp_error($o)) {
                    return $o;
                }
                $out = ['id' => (int) $o['id'], 'ref' => $o['ref'], 'amount' => (float) $o['amount'], 'currency' => $o['currency'], 'method' => $o['method']];
                if ($o['method'] === 'card') {
                    $out['pay'] = ec_nestpay_form($o, ec_rest_lang($r));
                } else {
                    $out['bank'] = ec_bank_info();
                }
                return $out;
            },
        ],
    ]);

    register_rest_route(EC_NS, '/me/orders/(?P<id>\d+)/receipt', [
        'methods' => 'POST',
        'permission_callback' => 'ec_rest_require_agent',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $a = ec_rest_agent($r);
            $o = ec_order_row((int) $r['id']);
            if (!$a || !$o || (int) $o['agent_id'] !== (int) $a['id'] || !in_array($o['status'], ['pending', 'review'], true)) {
                return new WP_Error('ec_not_found', 'Sipariş bulunamadı', ['status' => 404]);
            }
            $f = $r->get_file_params()['receipt'] ?? null;
            if (!$f || is_array($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                return new WP_Error('ec_no_file', 'Dekont yüklenemedi', ['status' => 400]);
            }
            $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true) || $f['size'] > 10 * MB_IN_BYTES) {
                return new WP_Error('ec_bad_type', 'Dekont JPG, PNG ya da PDF olmalı (en çok 10 MB).', ['status' => 400]);
            }
            $name = 'dekont-' . $o['ref'] . '-' . strtolower(wp_generate_password(16, false, false)) . '.' . $ext;
            if (!@move_uploaded_file($f['tmp_name'], ec_receipt_dir() . '/' . $name)) {
                return new WP_Error('ec_io', 'Dekont kaydedilemedi', ['status' => 500]);
            }
            $wpdb->update(ec_table('orders'), ['receipt_path' => $name, 'status' => 'review'], ['id' => $o['id']]);
            ec_notify_admin("🧾 Dekont yüklendi {$o['ref']}: {$a['name']} — onay bekliyor", admin_url('admin.php?page=ec-packages#siparisler'), true);
            return ['ok' => true, 'status' => 'review'];
        },
    ]);

    // Bankanın dönüşü (okUrl/failUrl/callbackUrl): tarayıcı POST eder; doğrulanınca panele yönlendirir
    register_rest_route(EC_NS, '/pay/nestpay', [
        'methods' => ['POST', 'GET'],
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $r) {
            global $wpdb;
            $post = $r->get_body_params() ?: $r->get_params();
            [$ok, $o, $msg] = ec_nestpay_verify((array) $post);
            if ($o && $ok) {
                ec_order_mark_paid($o, (string) ($post['AuthCode'] ?? $post['TransId'] ?? ''), 'Kart: ' . substr((string) ($post['MaskedPan'] ?? ''), -4));
            } elseif ($o && $o['status'] === 'pending') {
                $wpdb->update(ec_table('orders'), ['status' => 'failed', 'note' => mb_substr($msg, 0, 250)], ['id' => $o['id']]);
            }
            $a = $o ? ec_agent((int) $o['agent_id']) : null;
            $lang = $a ? ec_member_lang((int) $a['wp_user_id']) : 'tr';
            $to = add_query_arg(['bolum' => 'paket', 'odeme' => $ok ? 'ok' : 'hata', 'sip' => $o['ref'] ?? ''], ec_url_section('panel', $lang));
            if (!$ok && $msg) {
                $to = add_query_arg('mesaj', rawurlencode(mb_substr($msg, 0, 120)), $to);
            }
            // Bankanın sunucudan sunucuya çağrısına düz yanıt, tarayıcıya yönlendirme
            if (($post['callbackCall'] ?? '') === 'true') {
                return new WP_REST_Response($ok ? 'Approved' : 'Declined', 200);
            }
            $res = new WP_REST_Response(null, 303);
            $res->header('Location', $to);
            return $res;
        },
    ]);
});

/* ---------------- Yönetici: siparişler ve ödeme ayarları (Paketler sayfasında) ---------------- */

function ec_admin_orders_html(): string
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT o.*, a.name agent_name, p.name pkg_name FROM ' . ec_table('orders') . ' o LEFT JOIN ' . ec_table('agents') . ' a ON a.id = o.agent_id LEFT JOIN '
        . ec_table('packages') . ' p ON p.id = o.package_id ORDER BY (o.status = \'review\') DESC, o.id DESC LIMIT 100', ARRAY_A) ?: [];
    $lbl = ['pending' => 'Ödeme bekleniyor', 'review' => '🧾 Dekont geldi — onay bekliyor', 'paid' => '✅ Ödendi', 'cancelled' => 'İptal', 'failed' => '✗ Başarısız'];
    $h = '<h2 id="siparisler">Siparişler</h2><table class="widefat striped"><thead><tr><th>Kod</th><th>Üye</th><th>Paket</th><th>Tutar</th><th>Yöntem</th><th>Durum</th><th>Tarih</th><th></th></tr></thead><tbody>';
    if (!$rows) {
        $h .= '<tr><td colspan="8">Henüz sipariş yok.</td></tr>';
    }
    foreach ($rows as $o) {
        $acts = [];
        if (in_array($o['status'], ['pending', 'review', 'failed'], true)) {
            $acts[] = ec_admin_form('ec_order_paid', '<input type="hidden" name="id" value="' . (int) $o['id'] . '">', 'Ödendi — paketi ver', 'button button-primary');
            $acts[] = ec_admin_form('ec_order_cancel', '<input type="hidden" name="id" value="' . (int) $o['id'] . '">', 'İptal', 'button');
        }
        if ($o['receipt_path']) {
            $acts[] = '<a class="button" target="_blank" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=ec_receipt&id=' . (int) $o['id']), 'ec_receipt_' . $o['id'])) . '">Dekontu aç</a>';
        }
        $h .= '<tr><td><code>' . esc_html($o['ref']) . '</code></td><td>' . esc_html((string) $o['agent_name']) . '</td><td>' . esc_html((string) $o['pkg_name']) . '</td><td>'
            . esc_html(number_format((float) $o['amount'], 2, ',', '.') . ' ' . $o['currency']) . '</td><td>' . ($o['method'] === 'card' ? 'Kart' : 'Havale/EFT') . '</td><td>'
            . esc_html($lbl[$o['status']] ?? $o['status']) . ($o['note'] ? '<br><small>' . esc_html($o['note']) . '</small>' : '') . '</td><td>' . esc_html(get_date_from_gmt($o['created_at'], 'd.m.Y H:i')) . '</td><td>' . implode(' ', $acts) . '</td></tr>';
    }
    $h .= '</tbody></table>';

    $s = ec_payment_settings();
    $h .= '<h2>Ödeme ayarları</h2>' . ec_admin_form('ec_payment_save',
        '<table class="form-table">'
        . '<tr><th colspan="2"><h3 style="margin:0">Havale / EFT</h3></th></tr>'
        . '<tr><th>Banka</th><td><input name="bank_name" class="regular-text" value="' . esc_attr($s['bank_name']) . '" placeholder="ör. Near East Bank"></td></tr>'
        . '<tr><th>Hesap sahibi</th><td><input name="holder" class="regular-text" value="' . esc_attr($s['holder']) . '"></td></tr>'
        . '<tr><th>IBAN</th><td><input name="iban" class="regular-text" value="' . esc_attr($s['iban']) . '" placeholder="TR.. ya da KKTC hesap no"><p class="description">Doluysa üyeler Panelim → Paketim’den havaleyle paket satın alabilir.</p></td></tr>'
        . '<tr><th>Not</th><td><input name="bank_note" class="large-text" value="' . esc_attr($s['bank_note']) . '" placeholder="ör. Açıklamaya sipariş kodunu yazın. EUR hesabı."></td></tr>'
        . '<tr><th colspan="2"><h3 style="margin:0">Kartla ödeme — sanal POS (NestPay / Cardplus, 3D Pay Hosting)</h3><p class="description" style="font-weight:normal">KKTC bankalarının (ör. Near East Bank, Creditwest, Koopbank) sanal POS’u. Bankadan: mağaza numarası (Client ID), mağaza anahtarı (Store Key) ve 3D ödeme adresi (ör. https://…/fim/est3Dgate). Bankaya dönüş adresi olarak <code>' . esc_html(home_url('/api/v1/pay/nestpay')) . '</code> verin.</p></th></tr>'
        . '<tr><th>Kartla ödeme</th><td><label><input type="checkbox" name="pos_on" value="1"' . checked((int) $s['pos_on'], 1, false) . '> Açık</label></td></tr>'
        . '<tr><th>Client ID</th><td><input name="pos_client" class="regular-text" value="' . esc_attr($s['pos_client']) . '"></td></tr>'
        . '<tr><th>Store Key</th><td><input name="pos_key" type="password" class="regular-text" autocomplete="new-password" placeholder="' . ($s['pos_key'] ? '•••••••• (kayıtlı)' : '') . '"></td></tr>'
        . '<tr><th>3D ödeme adresi</th><td><input name="pos_url" class="large-text" value="' . esc_attr($s['pos_url']) . '" placeholder="https://…/fim/est3Dgate"></td></tr>'
        . '</table>');
    return $h;
}

add_action('admin_post_ec_payment_save', function () {
    $p = ec_admin_check('ec_payment_save');
    $s = ec_payment_settings();
    $url = esc_url_raw(trim((string) ($p['pos_url'] ?? '')), ['https']);
    update_option('ec_payment', [
        'bank_name' => sanitize_text_field($p['bank_name'] ?? ''), 'holder' => sanitize_text_field($p['holder'] ?? ''),
        'iban' => strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', (string) ($p['iban'] ?? ''))), 'bank_note' => sanitize_text_field($p['bank_note'] ?? ''),
        'pos_on' => !empty($p['pos_on']) ? 1 : 0, 'pos_client' => sanitize_text_field($p['pos_client'] ?? ''),
        'pos_key' => trim((string) ($p['pos_key'] ?? '')) !== '' ? ec_encrypt(trim((string) $p['pos_key'])) : $s['pos_key'],
        'pos_url' => $url, 'pos_test' => 0,
    ]);
    ec_admin_back('ec-packages', 'Ödeme ayarları kaydedildi.');
});

add_action('admin_post_ec_order_paid', function () {
    $p = ec_admin_check('ec_order_paid');
    $o = ec_order_row((int) $p['id']);
    if ($o) {
        ec_order_mark_paid($o, '', 'Yönetici onayı');
    }
    ec_admin_back('ec-packages', 'Sipariş ödendi olarak işaretlendi; paket üyeye tanımlandı.');
});

add_action('admin_post_ec_order_cancel', function () {
    global $wpdb;
    $p = ec_admin_check('ec_order_cancel');
    $wpdb->update(ec_table('orders'), ['status' => 'cancelled'], ['id' => (int) $p['id']]);
    ec_admin_back('ec-packages', 'Sipariş iptal edildi.');
});

add_action('admin_post_ec_receipt', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_receipt_' . $id)) {
        wp_die('Yetkisiz');
    }
    $o = ec_order_row($id);
    $file = $o && $o['receipt_path'] ? ec_receipt_dir() . '/' . basename($o['receipt_path']) : '';
    if (!$file || !is_file($file)) {
        wp_die('Dekont bulunamadı');
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . (['pdf' => 'application/pdf', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'image/jpeg'));
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
    header('X-Robots-Tag: noindex');
    readfile($file);
    exit;
});
