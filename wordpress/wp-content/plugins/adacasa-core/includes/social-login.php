<?php
/**
 * Google ve Facebook ile giriş / kayıt (1.2.0).
 *
 * Başlat:  /?ec_oauth=google&start=1&intent=login|register&lang=tr
 * Dönüş:   /?ec_oauth=google&code=...&state=...   (Google Cloud / Meta'ya bu adres yazılır)
 *  - Bağlı (ec_social_{sağlayıcı} metası) ya da aynı e-postalı üye varsa giriş yapılır. Sağlayıcı e-postayı
 *    doğruladığı için e-postası doğrulanmamış üye doğrulanmış sayılır.
 *  - Üye yoksa kayıt formu ad + e-posta dolu, şifresiz açılır; hesap türü, telefon ve onay yine istenir.
 *  - Yöneticiler sosyal girişle oturum açamaz.
 * Ayar: Ayarlar > AdaCasa Sosyal Giriş (ec_social_login; gizli anahtarlar şifreli saklanır).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_sl_settings(): array
{
    return array_merge(['google_id' => '', 'google_secret' => '', 'fb_id' => '', 'fb_secret' => '', 'fb_ver' => 'v27.0'], (array) get_option('ec_social_login', []));
}

function ec_sl_secret(string $k): string
{
    return ec_decrypt(ec_sl_settings()[$k] ?? '');
}

/** @return array<string,string> açık sağlayıcılar */
function ec_social_login_providers(): array
{
    $s = ec_sl_settings();
    $out = [];
    if ($s['google_id'] !== '' && $s['google_secret'] !== '') {
        $out['google'] = 'Google';
    }
    if ($s['fb_id'] !== '' && $s['fb_secret'] !== '') {
        $out['facebook'] = 'Facebook';
    }
    return $out;
}

function ec_sl_redirect_uri(string $p): string
{
    return home_url('/?ec_oauth=' . $p);
}

function ec_sl_start_url(string $p, string $intent, ?string $lang = null): string
{
    return add_query_arg(['ec_oauth' => $p, 'start' => 1, 'intent' => $intent === 'register' ? 'register' : 'login', 'lang' => $lang ?? ec_lang()], home_url('/'));
}

/** Hata/iptal: giriş ya da kayıt sayfasına ?sosyal=<kod> ile dön */
function ec_sl_fail(string $code, string $intent, string $lang): void
{
    wp_safe_redirect(add_query_arg('sosyal', $code, ec_url_section($intent === 'register' ? 'register' : 'login', $lang)));
    exit;
}

add_action('init', function () {
    if (empty($_GET['ec_oauth'])) {
        return;
    }
    $p = sanitize_key(wp_unslash($_GET['ec_oauth']));
    if (!isset(ec_social_login_providers()[$p])) {
        return;
    }
    nocache_headers();
    if (!empty($_GET['start'])) {
        ec_sl_start($p);
    }
    ec_sl_callback($p);
}, 6);

function ec_sl_start(string $p): void
{
    $lang = in_array($_GET['lang'] ?? '', EC_LANGS, true) ? (string) $_GET['lang'] : EC_DEFAULT_LANG;
    $intent = ($_GET['intent'] ?? '') === 'register' ? 'register' : 'login';
    if (is_user_logged_in()) {
        wp_safe_redirect(ec_url_section('panel', $lang));
        exit;
    }
    $state = wp_generate_password(32, false, false);
    set_transient('ec_sl_' . $state, ['p' => $p, 'i' => $intent, 'l' => $lang], 10 * MINUTE_IN_SECONDS);
    setcookie('ec_sl', hash_hmac('sha256', $state, wp_salt('auth')), ['expires' => time() + 600, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax']);
    $s = ec_sl_settings();
    if ($p === 'google') {
        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $s['google_id'], 'redirect_uri' => ec_sl_redirect_uri('google'), 'response_type' => 'code',
            'scope' => 'openid email profile', 'state' => $state, 'prompt' => 'select_account', 'hl' => $lang,
        ]);
    } else {
        $url = 'https://www.facebook.com/' . $s['fb_ver'] . '/dialog/oauth?' . http_build_query([
            'client_id' => $s['fb_id'], 'redirect_uri' => ec_sl_redirect_uri('facebook'), 'response_type' => 'code',
            'scope' => 'email,public_profile', 'state' => $state,
        ]);
    }
    wp_redirect($url); // dış sağlayıcı
    exit;
}

/** @return array{id:string,email:string,name:string}|WP_Error */
function ec_sl_profile(string $p, string $code)
{
    $s = ec_sl_settings();
    if ($p === 'google') {
        $r = wp_remote_post('https://oauth2.googleapis.com/token', ['timeout' => 15, 'body' => [
            'code' => $code, 'client_id' => $s['google_id'], 'client_secret' => ec_sl_secret('google_secret'),
            'redirect_uri' => ec_sl_redirect_uri('google'), 'grant_type' => 'authorization_code',
        ]]);
        $j = is_wp_error($r) ? [] : (array) json_decode(wp_remote_retrieve_body($r), true);
        if (empty($j['access_token'])) {
            return new WP_Error('ec_sl', 'Google token: ' . (is_wp_error($r) ? $r->get_error_message() : wp_remote_retrieve_body($r)));
        }
        $r = wp_remote_get('https://openidconnect.googleapis.com/v1/userinfo', ['timeout' => 15, 'headers' => ['Authorization' => 'Bearer ' . $j['access_token']]]);
        $u = is_wp_error($r) ? [] : (array) json_decode(wp_remote_retrieve_body($r), true);
        if (empty($u['sub'])) {
            return new WP_Error('ec_sl', 'Google profil okunamadı');
        }
        return ['id' => (string) $u['sub'], 'email' => !empty($u['email_verified']) ? sanitize_email((string) ($u['email'] ?? '')) : '', 'name' => sanitize_text_field((string) ($u['name'] ?? ''))];
    }
    $g = 'https://graph.facebook.com/' . $s['fb_ver'] . '/';
    $secret = ec_sl_secret('fb_secret');
    $r = wp_remote_get($g . 'oauth/access_token?' . http_build_query(['client_id' => $s['fb_id'], 'client_secret' => $secret, 'redirect_uri' => ec_sl_redirect_uri('facebook'), 'code' => $code]), ['timeout' => 15]);
    $j = is_wp_error($r) ? [] : (array) json_decode(wp_remote_retrieve_body($r), true);
    if (empty($j['access_token'])) {
        return new WP_Error('ec_sl', 'Facebook token: ' . (is_wp_error($r) ? $r->get_error_message() : wp_remote_retrieve_body($r)));
    }
    $r = wp_remote_get($g . 'me?' . http_build_query(['fields' => 'id,name,email', 'access_token' => $j['access_token'], 'appsecret_proof' => hash_hmac('sha256', $j['access_token'], $secret)]), ['timeout' => 15]);
    $u = is_wp_error($r) ? [] : (array) json_decode(wp_remote_retrieve_body($r), true);
    if (empty($u['id'])) {
        return new WP_Error('ec_sl', 'Facebook profil okunamadı');
    }
    return ['id' => (string) $u['id'], 'email' => sanitize_email((string) ($u['email'] ?? '')), 'name' => sanitize_text_field((string) ($u['name'] ?? ''))];
}

function ec_sl_callback(string $p): void
{
    global $wpdb;
    $state = sanitize_text_field(wp_unslash($_GET['state'] ?? ''));
    $st = $state ? get_transient('ec_sl_' . $state) : false;
    $lang = is_array($st) ? $st['l'] : EC_DEFAULT_LANG;
    $intent = is_array($st) ? $st['i'] : 'login';
    $cookie = (string) ($_COOKIE['ec_sl'] ?? '');
    if (!is_array($st) || $st['p'] !== $p || !hash_equals(hash_hmac('sha256', $state, wp_salt('auth')), $cookie)) {
        ec_sl_fail('hata', 'login', $lang);
    }
    delete_transient('ec_sl_' . $state);
    setcookie('ec_sl', '', time() - 3600, '/');
    if (!empty($_GET['error']) || empty($_GET['code'])) {
        ec_sl_fail('iptal', $intent, $lang);
    }
    $prof = ec_sl_profile($p, sanitize_text_field(wp_unslash($_GET['code'])));
    if (is_wp_error($prof)) {
        error_log('AdaCasa sosyal giriş: ' . $prof->get_error_message());
        ec_sl_fail('hata', $intent, $lang);
    }

    $meta = 'ec_social_' . $p;
    $found = get_users(['meta_key' => $meta, 'meta_value' => $prof['id'], 'number' => 1]);
    $user = $found ? $found[0] : null;
    if (!$user && $prof['email']) {
        $user = get_user_by('email', $prof['email']) ?: null;
        if (!$user) {
            $uid = (int) $wpdb->get_var($wpdb->prepare('SELECT wp_user_id FROM ' . ec_table('agents') . ' WHERE email = %s', $prof['email']));
            $user = $uid ? (get_user_by('id', $uid) ?: null) : null;
        }
    }

    if ($user) {
        if (user_can($user, 'manage_options')) {
            ec_sl_fail('yonetici', 'login', $lang);
        }
        $a = ec_agent_by_user($user->ID);
        if ($a && $a['status'] === 'suspended') {
            ec_sl_fail('askida', 'login', $lang);
        }
        // Sağlayıcı bu e-postayı doğruladı: doğrulanmamış üyeyi doğrulanmış say
        if ($a && !$a['email_verified'] && $prof['email'] && strcasecmp($prof['email'], (string) $a['email']) === 0) {
            $wpdb->update(ec_table('agents'), ['email_verified' => 1, 'verify_token' => null, 'updated_at' => ec_now()], ['id' => $a['id']]);
        }
        update_user_meta($user->ID, $meta, $prof['id']);
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);
        wp_safe_redirect(ec_url_section('panel', ec_member_lang($user->ID) ?: $lang));
        exit;
    }

    if (!$prof['email']) {
        ec_sl_fail('eposta', $intent, $lang);
    }
    $token = wp_generate_password(32, false, false);
    set_transient('ec_slr_' . $token, ['p' => $p, 'id' => $prof['id'], 'email' => $prof['email'], 'name' => $prof['name']], 30 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg('sosyal_kayit', $token, ec_url_section('register', $lang)));
    exit;
}

/** Kayıt formundaki sosyal jeton → bekleyen profil */
function ec_sl_pending(string $token): ?array
{
    $token = preg_replace('/[^A-Za-z0-9]/', '', $token);
    if (strlen($token) < 20) {
        return null;
    }
    $v = get_transient('ec_slr_' . $token);
    return is_array($v) ? $v + ['token' => $token] : null;
}

function ec_sl_pending_done(array $sg, int $uid): void
{
    update_user_meta($uid, 'ec_social_' . $sg['p'], $sg['id']);
    delete_transient('ec_slr_' . $sg['token']);
}

/* ---------------- Ayar sayfası ---------------- */

add_action('admin_menu', function () {
    add_options_page('AdaCasa Sosyal Giriş', 'AdaCasa Sosyal Giriş', 'manage_options', 'adacasa-sosyal-giris', 'ec_sl_admin_page');
});

add_action('admin_post_ec_sl_save', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_sl_save')) {
        wp_die('Yetkiniz yok');
    }
    $old = ec_sl_settings();
    $g = fn($k) => trim(sanitize_text_field(wp_unslash($_POST[$k] ?? '')));
    update_option('ec_social_login', [
        'google_id' => $g('google_id'),
        'google_secret' => $g('google_secret') !== '' ? ec_encrypt($g('google_secret')) : $old['google_secret'],
        'fb_id' => preg_replace('/\D/', '', $g('fb_id')),
        'fb_secret' => $g('fb_secret') !== '' ? ec_encrypt($g('fb_secret')) : $old['fb_secret'],
        'fb_ver' => preg_match('/^v\d+\.\d$/', $g('fb_ver')) ? $g('fb_ver') : 'v27.0',
    ], false);
    wp_safe_redirect(add_query_arg('kaydedildi', 1, admin_url('options-general.php?page=adacasa-sosyal-giris')));
    exit;
});

function ec_sl_admin_page(): void
{
    $s = ec_sl_settings();
    $on = ec_social_login_providers();
    ?>
    <div class="wrap">
      <h1>AdaCasa Sosyal Giriş</h1>
      <?php if (!empty($_GET['kaydedildi'])) : ?><div class="notice notice-success"><p>Kaydedildi.</p></div><?php endif; ?>
      <p>Üyelerin Google ve Facebook hesaplarıyla giriş ve kayıt olabilmesi için. Bir sağlayıcının iki alanı da doluysa düğmesi giriş ve kayıt sayfalarında görünür. Yönetici hesapları sosyal girişle açılamaz. Gizli anahtarlar şifreli saklanır; boş bırakılırsa eskisi korunur.</p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="ec_sl_save"><?php wp_nonce_field('ec_sl_save'); ?>
        <h2>Google</h2>
        <table class="form-table">
          <tr><th>Yetkili yönlendirme URI'si</th><td><input readonly class="large-text code" value="<?php echo esc_attr(ec_sl_redirect_uri('google')); ?>" onclick="this.select()"><p class="description">Google Cloud Console → API'ler ve Hizmetler → Kimlik bilgileri → OAuth istemcisi → <b>Yetkili yönlendirme URI'leri</b>.</p></td></tr>
          <tr><th>İstemci kimliği (Client ID)</th><td><input class="large-text code" name="google_id" value="<?php echo esc_attr($s['google_id']); ?>" placeholder="....apps.googleusercontent.com"></td></tr>
          <tr><th>İstemci gizli anahtarı</th><td><input type="password" class="regular-text" name="google_secret" autocomplete="new-password" placeholder="<?php echo $s['google_secret'] ? 'Kayıtlı (değiştirmek için yazın)' : 'GOCSPX-...'; ?>"></td></tr>
        </table>
        <h2>Facebook</h2>
        <table class="form-table">
          <tr><th>Geçerli OAuth yönlendirme URI'si</th><td><input readonly class="large-text code" value="<?php echo esc_attr(ec_sl_redirect_uri('facebook')); ?>" onclick="this.select()"><p class="description">developers.facebook.com → uygulama → Facebook Girişi → Ayarlar → <b>Geçerli OAuth Yönlendirme URI'leri</b>.</p></td></tr>
          <tr><th>Uygulama Kimliği (App ID)</th><td><input class="regular-text code" name="fb_id" value="<?php echo esc_attr($s['fb_id']); ?>"></td></tr>
          <tr><th>Uygulama Gizli Anahtarı</th><td><input type="password" class="regular-text" name="fb_secret" autocomplete="new-password" placeholder="<?php echo $s['fb_secret'] ? 'Kayıtlı (değiştirmek için yazın)' : ''; ?>"></td></tr>
          <tr><th>Graph sürümü</th><td><input class="small-text code" name="fb_ver" value="<?php echo esc_attr($s['fb_ver']); ?>"></td></tr>
        </table>
        <?php submit_button('Kaydet'); ?>
      </form>
      <p>Durum: <?php echo $on ? esc_html(implode(', ', $on)) . ' açık.' : 'Henüz hiçbiri açık değil.'; ?></p>
    </div>
    <?php
}
