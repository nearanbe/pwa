<?php
/**
 * Yasal sayfalar ve çerez onayı (1.1.5)
 *   /tr/yasal/{belge}/ · /en/legal/{doc}/ · /ru/pravo/{doc}/   (metinler: legal-texts.php)
 *   Şirket bilgileri: Emlak → Ayarlar → Şirket ve yasal bilgiler (ec_company)
 *   Çerez onayı: analiz (GA4) ve pazarlama (Meta Pixel) sadece izinle yüklenir; tercih "ac_consent" çerezinde.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/legal-texts.php';

function ec_company(): array
{
    $c = array_merge(['name' => '', 'address' => '', 'taxno' => '', 'email' => '', 'phone' => '', 'updated' => ''], (array) get_option('ec_company', []));
    $contact = (array) get_option('ec_contact', []);
    $c['name'] = $c['name'] ?: get_bloginfo('name');
    $c['email'] = $c['email'] ?: ($contact['email'] ?? '') ?: get_option('admin_email');
    $c['phone'] = $c['phone'] ?: (!empty($contact['whatsapp']) ? '+' . ltrim($contact['whatsapp'], '+') : '');
    $c['address'] = $c['address'] ?: 'Kuzey Kıbrıs';
    $c['updated'] = $c['updated'] ?: '2026-11-21';
    return $c;
}

function ec_url_legal(string $doc, ?string $lang = null): string
{
    $lang = $lang ?? (function_exists('ec_lang') ? ec_lang() : 'tr');
    $d = ec_legal_docs()[$doc] ?? null;
    return $d ? home_url('/' . $lang . '/' . ec_sections()['legal'][$lang] . '/' . $d['slug'][$lang] . '/') : home_url('/');
}

/** Adres parçasından belge anahtarı: 'gizlilik' → 'privacy' */
function ec_legal_by_slug(string $slug, string $lang): ?string
{
    foreach (ec_legal_docs() as $k => $d) {
        if (($d['slug'][$lang] ?? '') === $slug) {
            return $k;
        }
    }
    return null;
}

function ec_legal_render(string $doc, string $lang): array
{
    $d = ec_legal_docs()[$doc];
    $c = ec_company();
    $months = ['tr' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'], 'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], 'ru' => ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря']];
    $ts = strtotime($c['updated']) ?: time();
    $wa = preg_replace('/\D/', '', (string) ((array) get_option('ec_contact', []))['whatsapp'] ?? '');
    $taxLbl = ['tr' => 'Vergi no', 'en' => 'Tax no', 'ru' => 'Налоговый номер'][$lang];
    $phoneLbl = ['tr' => 'Telefon', 'en' => 'Phone', 'ru' => 'Телефон'][$lang];
    $rep = [
        '{brand}' => esc_html(get_bloginfo('name')),
        '{company}' => esc_html($c['name']),
        '{address}' => nl2br(esc_html($c['address'])),
        '{email}' => esc_html($c['email']),
        '{phone}' => $c['phone'] ? esc_html($phoneLbl . ': ' . $c['phone']) : '',
        '{taxno_line}' => $c['taxno'] ? ' · ' . esc_html($taxLbl . ': ' . $c['taxno']) : '',
        '{whatsapp}' => $wa ? '<a class="wa" href="https://wa.me/' . esc_attr($wa) . '" target="_blank" rel="noopener">WhatsApp: +' . esc_html($wa) . '</a>' : '',
        '{site}' => esc_html(wp_parse_url(home_url(), PHP_URL_HOST)),
        '{date}' => esc_html(wp_date('j', $ts) . ' ' . $months[$lang][(int) wp_date('n', $ts) - 1] . ' ' . wp_date('Y', $ts)),
        '{privacy_url}' => esc_url(ec_url_legal('privacy', $lang)),
        '{cookies_url}' => esc_url(ec_url_legal('cookies', $lang)),
        '{terms_url}' => esc_url(ec_url_legal('terms', $lang)),
        '{sales_url}' => esc_url(ec_url_legal('sales', $lang)),
        '{contact_url}' => esc_url(ec_url_legal('contact', $lang)),
        '{faq_url}' => esc_url(ec_url_section('faq', $lang)),
    ];
    return ['title' => $d['title'][$lang], 'html' => strtr($d['html'][$lang], $rep)];
}

/* Eski WordPress sayfa adresleri (/hakkimizda/ vb.) yeni adreslere yönlensin */
add_action('template_redirect', function () {
    if (!is_404()) {
        return;
    }
    $map = ['hakkimizda' => 'about', 'iletisim' => 'contact', 'gizlilik-politikasi' => 'privacy', 'kullanim-sartlari' => 'terms', 'cerez-politikasi' => 'cookies', 'privacy-policy' => 'privacy'];
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if (isset($map[$path])) {
        wp_safe_redirect(ec_url_legal($map[$path], 'tr'), 301);
        exit;
    }
}, 1);

/* ---------------- Ayarlar: şirket bilgileri ---------------- */

function ec_company_settings_html(): string
{
    $c = array_merge(['name' => '', 'address' => '', 'taxno' => '', 'email' => '', 'phone' => '', 'updated' => ''], (array) get_option('ec_company', []));
    $links = [];
    foreach (ec_legal_docs() as $k => $d) {
        $links[] = '<a href="' . esc_url(ec_url_legal($k, 'tr')) . '" target="_blank">' . esc_html($d['title']['tr']) . '</a>';
    }
    return '<h2>Şirket ve yasal bilgiler</h2><p class="description">Yasal sayfalarda (TR / EN / RU) ve alt bilgide kullanılır: ' . implode(' · ', $links) . '. Metinler bir avukata kontrol ettirilmelidir.</p><table class="form-table">'
        . '<tr><th>Şirket unvanı</th><td><input name="co_name" class="regular-text" value="' . esc_attr($c['name']) . '" placeholder="ör. AdaCasa Gayrimenkul Ltd."></td></tr>'
        . '<tr><th>Adres</th><td><textarea name="co_address" rows="2" class="large-text" placeholder="Cadde, No, Şehir, KKTC">' . esc_textarea($c['address']) . '</textarea></td></tr>'
        . '<tr><th>Vergi / şirket no</th><td><input name="co_taxno" class="regular-text" value="' . esc_attr($c['taxno']) . '"></td></tr>'
        . '<tr><th>Yasal iletişim e-postası</th><td><input name="co_email" type="email" class="regular-text" value="' . esc_attr($c['email']) . '" placeholder="ör. info@adacasa.com"><p class="description">Kişisel veri başvuruları bu adrese gelir. Boşsa site e-postası kullanılır.</p></td></tr>'
        . '<tr><th>Telefon</th><td><input name="co_phone" class="regular-text" value="' . esc_attr($c['phone']) . '" placeholder="+90 533 …"></td></tr>'
        . '<tr><th>Metinlerin güncellenme tarihi</th><td><input name="co_updated" type="date" value="' . esc_attr($c['updated'] ?: '2026-11-21') . '"></td></tr></table>';
}

function ec_company_settings_save(array $p): void
{
    update_option('ec_company', [
        'name' => sanitize_text_field($p['co_name'] ?? ''), 'address' => sanitize_textarea_field($p['co_address'] ?? ''),
        'taxno' => sanitize_text_field($p['co_taxno'] ?? ''), 'email' => sanitize_email($p['co_email'] ?? ''),
        'phone' => sanitize_text_field($p['co_phone'] ?? ''), 'updated' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($p['co_updated'] ?? '')) ? $p['co_updated'] : '',
    ]);
}
