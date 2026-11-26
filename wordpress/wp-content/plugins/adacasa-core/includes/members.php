<?php
/**
 * Üyelik: kayıt, e-posta doğrulama, giriş, profil, logo, Telegram tercihi, Turnstile, bildirimler.
 * Her üye bir WordPress kullanıcısıdır (rol: ec_member) ve ec_agents tablosunda bir kayda bağlıdır.
 * Üyeler WordPress yönetim paneline giremez; her şeyi sitedeki "Panelim" sayfasından yapar.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_add_member_role(): void
{
    if (!get_role('ec_member')) {
        add_role('ec_member', 'Emlak Üyesi', ['read' => true]);
    }
}
add_action('init', 'ec_add_member_role', 5);

/** Üyeler yönetim paneline giremez, üst çubuğu görmez */
add_action('admin_init', function () {
    if (wp_doing_ajax() || !is_user_logged_in()) {
        return;
    }
    $u = wp_get_current_user();
    if (in_array('ec_member', (array) $u->roles, true) && !current_user_can('edit_posts')) {
        wp_safe_redirect(ec_url_section('panel', ec_member_lang()));
        exit;
    }
});
add_filter('show_admin_bar', function ($show) {
    $u = wp_get_current_user();
    return in_array('ec_member', (array) $u->roles, true) ? false : $show;
});

/** Üyenin tercih ettiği dil (kayıt sırasında kaydedilir) */
function ec_member_lang(int $uid = 0): string
{
    $l = get_user_meta($uid ?: get_current_user_id(), 'ec_lang', true);
    return in_array($l, EC_LANGS, true) ? $l : EC_DEFAULT_LANG;
}

/* ---------------- Turnstile (Cloudflare captcha) ---------------- */

function ec_turnstile_keys(): array
{
    $o = (array) get_option('ec_turnstile', []);
    return ['site' => (string) ($o['site'] ?? ''), 'secret' => (string) ($o['secret'] ?? '')];
}

/** Anahtar girilmemişse doğrulama atlanır (geliştirme ortamı). */
function ec_turnstile_verify(?string $token): bool
{
    $k = ec_turnstile_keys();
    if (!$k['secret']) {
        return true;
    }
    if (!$token) {
        return false;
    }
    $res = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'timeout' => 10,
        'body' => ['secret' => $k['secret'], 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''],
    ]);
    if (is_wp_error($res)) {
        return false;
    }
    $j = json_decode(wp_remote_retrieve_body($res), true);
    return !empty($j['success']);
}

/* ---------------- Kayıt ---------------- */

/**
 * Yeni üye. @return int|WP_Error danışman (agent) numarası
 * $in: kind, name, contact_person, email, phone, city, password, consent, telegram_opt_in, lang, cf-turnstile-response
 */
/** @param array|null $social yalnızca ec_sl_pending() sonucu (Google/Facebook ile doğrulanmış e-posta) */
function ec_member_register(array $in, ?array $social = null)
{
    global $wpdb;
    $err = [];
    $kinds = ec_member_kinds();
    $kind = (string) ($in['kind'] ?? '');
    $name = sanitize_text_field((string) ($in['name'] ?? ''));
    $email = sanitize_email((string) ($in['email'] ?? ''));
    $phone = preg_replace('/[^0-9+]/', '', (string) ($in['phone'] ?? ''));
    $pass = (string) ($in['password'] ?? '');
    $city = sanitize_key((string) ($in['city'] ?? ''));
    $lang = in_array($in['lang'] ?? '', EC_LANGS, true) ? $in['lang'] : EC_DEFAULT_LANG;
    $socialVerified = $social && !empty($social['email']); // sağlayıcı e-postayı verdi ve doğruladı
    if ($social) {
        if ($socialVerified) {
            $email = sanitize_email((string) $social['email']);
        }
        $pass = wp_generate_password(24);
    }

    if (!isset($kinds[$kind])) {
        $err['kind'] = 'kind';
    }
    if (mb_strlen($name) < 2) {
        $err['name'] = 'name';
    }
    if (!is_email($email)) {
        $err['email'] = 'email';
    } elseif (email_exists($email) || $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . ec_table('agents') . ' WHERE email = %s', $email))) {
        $err['email'] = 'email_exists';
    }
    if (strlen($phone) < 8) {
        $err['phone'] = 'phone';
    }
    if (strlen($pass) < 8) {
        $err['password'] = 'password';
    }
    if (empty($in['consent'])) {
        $err['consent'] = 'consent';
    }
    if (!ec_turnstile_verify($in['cf-turnstile-response'] ?? null)) {
        $err['captcha'] = 'captcha';
    }
    if ($err) {
        return new WP_Error('ec_register', 'Kayıt formunda hata var', ['fields' => $err]);
    }

    $login = substr(sanitize_user(current(explode('@', $email)), true), 0, 40) ?: 'uye';
    $base = $login;
    $i = 2;
    while (username_exists($login)) {
        $login = $base . $i++;
    }
    $uid = wp_insert_user([
        'user_login' => $login,
        'user_email' => $email,
        'user_pass' => $pass,
        'display_name' => $name,
        'role' => 'ec_member',
    ]);
    if (is_wp_error($uid)) {
        return $uid;
    }
    update_user_meta($uid, 'ec_lang', $lang);

    $company = in_array($kind, ['ofis', 'insaat'], true);
    $token = wp_generate_password(40, false, false);
    $wpdb->insert(ec_table('agents'), [
        'wp_user_id' => $uid,
        'kind' => $kind,
        'company_type' => $kinds[$kind]['name']['tr'],
        'name' => mb_substr($name, 0, 190),
        'office' => $company ? mb_substr($name, 0, 190) : null,
        'contact_person' => $company ? (mb_substr(sanitize_text_field((string) ($in['contact_person'] ?? '')), 0, 190) ?: null) : null,
        'email' => $email,
        'phone' => $phone,
        'whatsapp' => ltrim($phone, '+'),
        'city' => isset(ec_cities()[$city]) ? $city : null,
        'slug' => ec_agent_unique_slug($name),
        'status' => $kinds[$kind]['account_review'] ? 'pending' : 'active',
        'email_verified' => $socialVerified ? 1 : 0,
        'verify_token' => $socialVerified ? null : $token,
        'consent_at' => ec_now(),
        'telegram_opt_in' => !empty($in['telegram_opt_in']) ? 1 : 0,
        'created_at' => ec_now(),
        'updated_at' => ec_now(),
    ]);
    $id = (int) $wpdb->insert_id;
    if (!$id) {
        wp_delete_user($uid);
        return new WP_Error('ec_db', 'Kayıt oluşturulamadı');
    }
    if ($social) {
        ec_sl_pending_done($social, (int) $uid);
    }
    if (!$socialVerified) {
        ec_member_send_verification($id);
    }
    ec_notify_admin(
        sprintf("🆕 Yeni üye: %s (%s)\n%s · %s%s", $name, $kinds[$kind]['name']['tr'], $email, $phone, $company ? "\nHesap onayınızı bekliyor." : ''),
        ec_url_section('review', 'tr')
    );
    return $id;
}

function ec_verify_url(array $agent): string
{
    return add_query_arg(['u' => $agent['id'], 'k' => $agent['verify_token']], ec_url_section('verify', ec_member_lang((int) $agent['wp_user_id'])));
}

function ec_member_send_verification(int $id): bool
{
    $a = ec_agent($id);
    if (!$a || $a['email_verified'] || !$a['verify_token']) {
        return false;
    }
    $site = get_bloginfo('name');
    $lang = ec_member_lang((int) $a['wp_user_id']);
    $subj = ec_i18n_pick(['tr' => "$site: e-posta adresinizi doğrulayın", 'en' => "$site: please verify your email", 'ru' => "$site: подтвердите e-mail"], $lang);
    $body = ec_i18n_pick([
        'tr' => "Merhaba {$a['name']},\n\n$site üyeliğinizi tamamlamak için aşağıdaki bağlantıya tıklayın:\n\n%s\n\nBu işlemi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.",
        'en' => "Hello {$a['name']},\n\nPlease click the link below to complete your $site registration:\n\n%s\n\nIf you did not sign up, you can ignore this email.",
        'ru' => "Здравствуйте, {$a['name']}!\n\nЧтобы завершить регистрацию на $site, перейдите по ссылке:\n\n%s\n\nЕсли вы не регистрировались, просто проигнорируйте это письмо.",
    ], $lang);
    return wp_mail($a['email'], $subj, sprintf($body, ec_verify_url($a)));
}

/** @return array|WP_Error doğrulanan üye */
function ec_member_verify(int $id, string $token)
{
    global $wpdb;
    $a = ec_agent($id);
    if (!$a || !$a['verify_token'] || !hash_equals($a['verify_token'], $token)) {
        return $a && $a['email_verified'] ? $a : new WP_Error('ec_verify', 'Bağlantı geçersiz veya süresi dolmuş');
    }
    $wpdb->update(ec_table('agents'), ['email_verified' => 1, 'verify_token' => null, 'updated_at' => ec_now()], ['id' => $id]);
    return ec_agent($id);
}

/** Giriş: e-posta doğrulanmamış üyeler giremez */
add_filter('wp_authenticate_user', function ($user) {
    if ($user instanceof WP_User && in_array('ec_member', (array) $user->roles, true)) {
        $a = ec_agent_by_user($user->ID);
        if ($a && !$a['email_verified']) {
            return new WP_Error('ec_unverified', 'unverified');
        }
        if ($a && $a['status'] === 'suspended') {
            return new WP_Error('ec_suspended', 'suspended');
        }
    }
    return $user;
}, 20);

/* ---------------- Profil ---------------- */

/** @return true|WP_Error */
function ec_member_update_profile(int $id, array $in)
{
    global $wpdb;
    $a = ec_agent($id);
    if (!$a) {
        return new WP_Error('ec_not_found', 'Üye bulunamadı');
    }
    $d = [];
    if (isset($in['name']) && mb_strlen(trim($in['name'])) >= 2) {
        $d['name'] = mb_substr(sanitize_text_field($in['name']), 0, 190);
        if (in_array($a['kind'], ['ofis', 'insaat'], true)) {
            $d['office'] = $d['name'];
        }
    }
    foreach (['contact_person' => 190, 'license_no' => 60, 'address' => 255] as $k => $max) {
        if (array_key_exists($k, $in)) {
            $d[$k] = mb_substr(sanitize_text_field((string) $in[$k]), 0, $max) ?: null;
        }
    }
    foreach (['phone', 'whatsapp'] as $k) {
        if (array_key_exists($k, $in)) {
            $d[$k] = preg_replace('/[^0-9+]/', '', (string) $in[$k]) ?: null;
        }
    }
    if (array_key_exists('city', $in)) {
        $d['city'] = isset(ec_cities()[$in['city']]) ? $in['city'] : null;
    }
    if (array_key_exists('website', $in)) {
        $w = trim((string) $in['website']);
        $d['website'] = $w === '' ? null : (esc_url_raw(preg_match('#^https?://#', $w) ? $w : 'https://' . $w, ['http', 'https']) ?: null);
    }
    if (array_key_exists('bio', $in)) {
        $d['bio'] = mb_substr(sanitize_textarea_field((string) $in['bio']), 0, 2000);
    }
    if (array_key_exists('socials', $in)) {
        $d['socials'] = wp_json_encode(ec_clean_socials((array) $in['socials']));
    }
    if (array_key_exists('slug', $in) && trim((string) $in['slug']) !== '') {
        $d['slug'] = ec_agent_unique_slug((string) $in['slug'], $id);
    }
    if (array_key_exists('telegram_opt_in', $in)) {
        $d['telegram_opt_in'] = !empty($in['telegram_opt_in']) ? 1 : 0;
        if (!$d['telegram_opt_in']) {
            $d['telegram_chat_id'] = null;
            $d['telegram_username'] = null;
            $d['telegram_link_code'] = null;
        }
    }
    if ($d) {
        $d['updated_at'] = ec_now();
        $wpdb->update(ec_table('agents'), $d, ['id' => $id]);
        if (isset($d['name']) && $a['wp_user_id']) {
            wp_update_user(['ID' => (int) $a['wp_user_id'], 'display_name' => $d['name']]);
        }
    }
    return true;
}

/** @return true|WP_Error */
function ec_member_set_logo(int $id, string $file)
{
    global $wpdb;
    $a = ec_agent($id);
    $rel = 'uye-' . $id . '/logo-' . strtolower(wp_generate_password(8, false, false));
    $r = ec_image_variants($file, $rel);
    if (is_wp_error($r)) {
        return $r;
    }
    if (!empty($a['logo_path'])) {
        ec_image_delete($a['logo_path']);
    }
    $wpdb->update(ec_table('agents'), ['logo_path' => $rel, 'updated_at' => ec_now()], ['id' => $id]);
    return true;
}

/** Telegram eşleştirme kodu (10 dakika geçerli, tek kullanımlık). Bot 0.4 sürümünde bu kodu okur. */
function ec_member_telegram_code(int $id): string
{
    global $wpdb;
    $code = strtoupper(wp_generate_password(12, false, false));
    $wpdb->update(ec_table('agents'), ['telegram_link_code' => $code, 'telegram_link_expires' => gmdate('Y-m-d H:i:s', time() + 600)], ['id' => $id]);
    return $code;
}

/* ---------------- Bildirimler ---------------- */

/**
 * Yöneticiye bildirim: e-posta ve (ayarlıysa) Telegram.
 * Telegram için Ayarlar'da bot anahtarı ve yöneticinin sohbet numarası (chat id) girilmelidir.
 */
function ec_notify_admin(string $text, string $url = '', bool $telegram = true): void
{
    $msg = $text . ($url ? "\n\n" . $url : '');
    wp_mail(get_option('admin_email'), get_bloginfo('name') . ': ' . strtok($text, "\n"), $msg);
    $tg = (array) get_option('ec_telegram', []);
    if ($telegram && !empty($tg['token']) && !empty($tg['admin_chat'])) {
        wp_remote_post('https://api.telegram.org/bot' . $tg['token'] . '/sendMessage', [
            'timeout' => 8,
            'blocking' => false,
            'body' => ['chat_id' => $tg['admin_chat'], 'text' => $msg, 'disable_web_page_preview' => 'true'],
        ]);
    }
}

/** Üyeye e-posta (ilan onaylandı / reddedildi gibi) */
function ec_notify_member(array $agent, string $subject, string $text): void
{
    if (!empty($agent['email'])) {
        wp_mail($agent['email'], get_bloginfo('name') . ': ' . $subject, $text);
    }
}

/* ---------------- Form işleme (giriş, kayıt, doğrulama e-postasını yeniden gönder) ---------------- */

/** Şablonların okuyacağı form durumu: ['errors' => [...], 'old' => [...], 'notice' => '...'] */
function ec_form_state(): array
{
    return $GLOBALS['ec_form_state'] ?? ['errors' => [], 'old' => [], 'notice' => ''];
}

add_action('template_redirect', function () {
    $page = function_exists('ec_page') ? ec_page() : '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($page, ['login', 'register'], true)) {
        return;
    }
    $post = wp_unslash($_POST);
    if (!wp_verify_nonce($post['_ecnonce'] ?? '', 'ec_' . $page)) {
        $GLOBALS['ec_form_state'] = ['errors' => ['form' => 'expired'], 'old' => $post, 'notice' => ''];
        return;
    }
    $lang = ec_lang();

    if ($page === 'register') {
        $post['lang'] = $lang;
        $sg = !empty($post['sosyal_kayit']) && function_exists('ec_sl_pending') ? ec_sl_pending((string) $post['sosyal_kayit']) : null;
        if (!empty($post['sosyal_kayit']) && !$sg) {
            $GLOBALS['ec_form_state'] = ['errors' => ['form' => 'social_expired'], 'old' => $post, 'notice' => ''];
            return;
        }
        $id = ec_member_register($post, $sg);
        if (!is_wp_error($id) && $sg && !empty($sg['email'])) {
            // Google/Facebook ile kayıt: e-posta doğrulanmış sayılır, doğrudan oturum açılır
            $a = ec_agent((int) $id);
            if ($a) {
                wp_set_current_user((int) $a['wp_user_id']);
                wp_set_auth_cookie((int) $a['wp_user_id'], true, is_ssl());
            }
            wp_safe_redirect(add_query_arg('kayit', 'sosyal', ec_url_section('panel', $lang)));
            exit;
        }
        if (is_wp_error($id)) {
            unset($post['password']);
            $GLOBALS['ec_form_state'] = ['errors' => $id->get_error_data()['fields'] ?? ['form' => 'error'], 'old' => $post, 'notice' => ''];
            return;
        }
        wp_safe_redirect(add_query_arg('kayit', 'ok', ec_url_section('login', $lang)));
        exit;
    }

    // Doğrulama e-postasını yeniden gönder
    if (($post['do'] ?? '') === 'resend') {
        global $wpdb;
        $a = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ec_table('agents') . ' WHERE email = %s', sanitize_email($post['email'] ?? '')), ARRAY_A);
        if ($a && !$a['email_verified']) {
            $k = 'ec_resend_' . $a['id'];
            if (!get_transient($k)) {
                set_transient($k, 1, 5 * MINUTE_IN_SECONDS);
                ec_member_send_verification((int) $a['id']);
            }
        }
        $GLOBALS['ec_form_state'] = ['errors' => [], 'old' => $post, 'notice' => 'resent'];
        return;
    }

    // Giriş (5 hatalı denemeden sonra 15 dakika bekleme)
    $ipKey = 'ec_login_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $fails = (int) get_transient($ipKey);
    if ($fails >= 5) {
        $GLOBALS['ec_form_state'] = ['errors' => ['form' => 'locked'], 'old' => $post, 'notice' => ''];
        return;
    }
    if (!ec_turnstile_verify($post['cf-turnstile-response'] ?? null)) {
        $GLOBALS['ec_form_state'] = ['errors' => ['captcha' => 'captcha'], 'old' => $post, 'notice' => ''];
        return;
    }
    $user = wp_signon([
        'user_login' => sanitize_text_field($post['email'] ?? ''),
        'user_password' => (string) ($post['password'] ?? ''),
        'remember' => !empty($post['remember']),
    ], is_ssl());
    if (is_wp_error($user)) {
        $code = $user->get_error_code();
        if (!in_array($code, ['ec_unverified', 'ec_suspended'], true)) {
            set_transient($ipKey, $fails + 1, 15 * MINUTE_IN_SECONDS);
            $code = 'invalid';
        }
        unset($post['password']);
        $GLOBALS['ec_form_state'] = ['errors' => ['form' => $code], 'old' => $post, 'notice' => ''];
        return;
    }
    delete_transient($ipKey);
    $back = isset($_GET['geri']) ? wp_validate_redirect(home_url(rawurldecode((string) $_GET['geri'])), '') : '';
    $dest = $back ?: (user_can($user, 'manage_options') ? ec_url_section('review', $lang) : ec_url_section('panel', ec_member_lang($user->ID)));
    wp_safe_redirect($dest);
    exit;
});


/**
 * Yöneticinin kendi ofis profili (1.1): site sahibi de ilan verip vitrin/profil kullanabilsin.
 * Onaylı, e-postası doğrulanmış "Emlak Ofisi" hesabı açar ve yönetici kullanıcısına bağlar.
 * @return int|WP_Error danışman no
 */
function ec_admin_self_profile(int $uid, array $in)
{
    global $wpdb;
    if (!user_can($uid, 'manage_options')) {
        return new WP_Error('ec_forbidden', 'Sadece yönetici', ['status' => 403]);
    }
    if (ec_agent_by_user($uid)) {
        return (int) ec_agent_by_user($uid)['id'];
    }
    $u = get_userdata($uid);
    $name = mb_substr(sanitize_text_field($in['name'] ?? '') ?: get_bloginfo('name'), 0, 190);
    $phone = preg_replace('/[^0-9+]/', '', (string) ($in['phone'] ?? ''));
    $city = sanitize_key($in['city'] ?? '');
    $wpdb->insert(ec_table('agents'), [
        'wp_user_id' => $uid, 'kind' => 'ofis', 'company_type' => ec_member_kinds()['ofis']['name']['tr'],
        'name' => $name, 'office' => $name, 'email' => sanitize_email($in['email'] ?? '') ?: $u->user_email,
        'phone' => $phone ?: null, 'whatsapp' => $phone ? ltrim($phone, '+') : null,
        'city' => isset(ec_cities()[$city]) ? $city : null, 'slug' => ec_agent_unique_slug($name),
        'status' => 'active', 'verified' => 1, 'email_verified' => 1, 'consent_at' => ec_now(),
        'created_at' => ec_now(), 'updated_at' => ec_now(),
    ]);
    return (int) $wpdb->insert_id ?: new WP_Error('ec_db', 'Profil oluşturulamadı');
}

add_action('admin_post_ec_self_profile', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('ec_self_profile')) {
        wp_die('Yetkisiz');
    }
    $r = ec_admin_self_profile(get_current_user_id(), wp_unslash($_POST));
    $to = add_query_arg('bolum', 'profil', ec_url_section('panel'));
    wp_safe_redirect(is_wp_error($r) ? add_query_arg('hata', rawurlencode($r->get_error_message()), $to) : $to);
    exit;
});


/** E-postalarda gönderen adı "WordPress" yerine site adı (1.1.8) */
add_filter('wp_mail_from_name', fn($name) => ($name === '' || $name === 'WordPress') ? wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES) : $name);
