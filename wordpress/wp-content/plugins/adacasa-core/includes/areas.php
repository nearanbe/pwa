<?php
/**
 * Bölge rehberleri (1.6): /tr/bolge/ (liste) ve /tr/bolge/{slug}/ (rehber). EN: /en/area/, RU: /ru/rayon/.
 *
 * Metinler `ec_areas` seçeneğinde (autoload kapalı): slug => [tr|en|ru => [title, desc, body(html)], generated_at, reviewed].
 * Metinleri yapay zekâ taslak olarak yazar (ec_area_generate, WP-CLI: wp eval 'ec_area_generate("alsancak");'):
 * yalnızca genel bilinen özellikler; fiyat, mesafe, nüfus gibi rakam YAZMAZ. Rakamlar (ilan sayısı, m² fiyatı)
 * sayfada canlı veriden gösterilir. Yayında görünmesi için metin bulunmalı; yönetici WP Admin → Emlak → Bölge
 * rehberleri ekranından metni düzeltebilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** slug => [şehir, ilçe|null] */
function ec_area_list(): array
{
    return [
        'girne' => ['girne', null], 'lefkosa' => ['lefkosa', null], 'gazimagusa' => ['gazimagusa', null],
        'iskele' => ['iskele', null], 'guzelyurt' => ['guzelyurt', null], 'lefke' => ['lefke', null],
        'alsancak' => ['girne', 'Alsancak'], 'lapta' => ['girne', 'Lapta'], 'karsiyaka' => ['girne', 'Karşıyaka'],
        'catalkoy' => ['girne', 'Çatalköy'], 'ozankoy' => ['girne', 'Ozanköy'], 'edremit' => ['girne', 'Edremit'],
        'karaoglanoglu' => ['girne', 'Karaoğlanoğlu'], 'bellapais' => ['girne', 'Bellapais'], 'esentepe' => ['girne', 'Esentepe'],
        'kasgar' => ['girne', 'Kaşgar'], 'dogankoy' => ['girne', 'Doğanköy'],
        'gonyeli' => ['lefkosa', 'Gönyeli'], 'hamitkoy' => ['lefkosa', 'Hamitköy'], 'yenikent' => ['lefkosa', 'Yenikent'],
        'kosklucftlik' => ['lefkosa', 'Köşklüçiftlik'], 'ortakoy' => ['lefkosa', 'Ortaköy'],
        'tuzla' => ['gazimagusa', 'Tuzla'], 'yenibogazici' => ['gazimagusa', 'Yeniboğaziçi'], 'canakkale' => ['gazimagusa', 'Çanakkale'],
        'long-beach' => ['iskele', 'Long Beach'], 'bogaz' => ['iskele', 'Boğaz'], 'bafra' => ['iskele', 'Bafra'],
        'gemikonagi' => ['lefke', 'Gemikonağı'],
    ];
}

function ec_areas_all(): array
{
    $a = get_option('ec_areas', []);
    return is_array($a) ? $a : [];
}

function ec_area(string $slug): ?array
{
    $list = ec_area_list();
    if (!isset($list[$slug])) {
        return null;
    }
    $t = ec_areas_all()[$slug] ?? null;
    return ['slug' => $slug, 'city' => $list[$slug][0], 'district' => $list[$slug][1], 'texts' => $t];
}

function ec_url_area(?string $slug = null, ?string $lang = null): string
{
    return ec_url_section('area', $lang) . ($slug ? $slug . '/' : '');
}

/** İlçe adından rehber (arama sayfasındaki "Bölge rehberi" bağlantısı) */
function ec_area_for(string $city, ?string $district): ?string
{
    foreach (ec_area_list() as $slug => [$c, $d]) {
        if ($c === $city && $d === ($district ?: null) && !empty(ec_areas_all()[$slug])) {
            return $slug;
        }
    }
    return null;
}

/* ---- Taslak metin üretimi (Claude) ---- */

function ec_area_generate(string $slug, bool $force = false)
{
    $a = ec_area($slug);
    if (!$a) {
        return new WP_Error('ec_invalid', 'Bilinmeyen bölge');
    }
    if ($a['texts'] && !$force) {
        return $a['texts'];
    }
    $s = ec_ai_settings();
    if (!$s['key']) {
        return new WP_Error('ec_ai_key', 'Claude API anahtarı yok');
    }
    $cityName = ec_cities()[$a['city']]['name']['tr'];
    $place = $a['district'] ? $a['district'] . ' (' . $cityName . ')' : $cityName . ' (şehir / ilçe geneli)';
    $text = ['type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'desc', 'body'], 'properties' => ['title' => ['type' => 'string'], 'desc' => ['type' => 'string'], 'body' => ['type' => 'string']]];
    $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['tr', 'en', 'ru'], 'properties' => ['tr' => $text, 'en' => $text, 'ru' => $text]];
    $system = "You write neighbourhood guides for AdaCasa, a property portal in Northern Cyprus (TRNC). Audience: people looking to buy or rent a home there (locals, Turkish citizens, British and Russian buyers, students' families).\n"
        . "STRICT accuracy rules:\n"
        . "- Only write widely known, stable, general facts: geography (coast/mountain/plain), general character (quiet/lively, village/urban, holiday/residential), typical housing types, who the area tends to suit. If you are not sure about something, leave it out.\n"
        . "- Do NOT write any numbers: no prices, rents, distances in km/minutes, population, years, percentages. Use relative phrases instead (\"close to the town centre\", \"a short drive\").\n"
        . "- Do NOT name businesses, schools, hospitals, developers, restaurants or projects. You may name only very well-known public landmarks if certain (e.g. Kyrenia harbour, Bellapais Abbey, the Kyrenia mountains, the Karpaz peninsula).\n"
        . "- No promises, no investment advice, no superlatives like 'best'. Neutral, helpful, natural tone; not salesy.\n"
        . "- Mention practical considerations honestly (e.g. hillside roads, traffic towards the centre, seasonal quietness) only if they are generally known.\n"
        . "Format for each language: title (50–65 chars, includes the place name + 'living / buying a home' angle), desc (140–155 chars meta description), body (HTML with <p>, <h2>, <ul><li>; 300–420 words; sections: overview, who it suits, homes you typically find, things to consider; no <h1>, no links).\n"
        . "Turkish (tr) is the main text; en and ru are natural translations adapted for native readers.";
    $res = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 180,
        'headers' => ['content-type' => 'application/json', 'x-api-key' => $s['key'], 'anthropic-version' => '2026-10-21'],
        'body' => wp_json_encode([
            'model' => $s['model'],
            'max_tokens' => 16000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => 'Write the guide for: ' . $place . '. Turkish place name: ' . ($a['district'] ?: $cityName) . '.']],
            'output_config' => ['effort' => $s['effort'], 'format' => ['type' => 'json_schema', 'schema' => $schema]],
        ]),
    ]);
    if (is_wp_error($res)) {
        return $res;
    }
    $j = json_decode((string) wp_remote_retrieve_body($res), true);
    if ((int) wp_remote_retrieve_response_code($res) !== 200) {
        return new WP_Error('ec_ai_api', substr((string) wp_remote_retrieve_body($res), 0, 300));
    }
    $out = '';
    foreach (($j['content'] ?? []) as $b) {
        if (($b['type'] ?? '') === 'text') {
            $out .= $b['text'];
        }
    }
    $d = json_decode($out, true);
    if (!is_array($d) || empty($d['tr']['body'])) {
        return new WP_Error('ec_ai_json', 'Cevap okunamadı');
    }
    foreach (['tr', 'en', 'ru'] as $l) {
        $d[$l] = ['title' => sanitize_text_field($d[$l]['title']), 'desc' => sanitize_text_field($d[$l]['desc']), 'body' => wp_kses($d[$l]['body'], ['p' => [], 'h2' => [], 'h3' => [], 'ul' => [], 'li' => [], 'strong' => [], 'em' => []])];
    }
    $d['generated_at'] = ec_now();
    $all = ec_areas_all();
    $all[$slug] = $d;
    update_option('ec_areas', $all, false);
    return $d;
}

/* ---- Yönetici ekranı: metinleri gözden geçirme / düzeltme ---- */

if (is_admin()) {
    add_action('admin_menu', function () {
        add_submenu_page('ec-listings', 'Bölge rehberleri', 'Bölge rehberleri', 'manage_options', 'ec-areas', 'ec_admin_areas');
    }, 23);
}

add_action('admin_post_ec_area_save', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkisiz');
    }
    check_admin_referer('ec_area_save');
    $slug = sanitize_key($_POST['slug'] ?? '');
    $all = ec_areas_all();
    if (isset(ec_area_list()[$slug])) {
        foreach (['tr', 'en', 'ru'] as $l) {
            $all[$slug][$l] = [
                'title' => sanitize_text_field(wp_unslash($_POST[$l . '_title'] ?? '')),
                'desc' => sanitize_text_field(wp_unslash($_POST[$l . '_desc'] ?? '')),
                'body' => wp_kses(wp_unslash($_POST[$l . '_body'] ?? ''), ['p' => [], 'h2' => [], 'h3' => [], 'ul' => [], 'li' => [], 'strong' => [], 'em' => []]),
            ];
        }
        $all[$slug]['reviewed'] = ec_now();
        update_option('ec_areas', $all, false);
    }
    wp_safe_redirect(admin_url('admin.php?page=ec-areas&slug=' . $slug . '&ok=1'));
    exit;
});

function ec_admin_areas(): void
{
    $all = ec_areas_all();
    $slug = sanitize_key($_GET['slug'] ?? '');
    echo '<div class="wrap"><h1>Bölge rehberleri</h1>';
    if (!empty($_GET['ok'])) {
        echo '<div class="notice notice-success"><p>Kaydedildi.</p></div>';
    }
    if (!$slug || !isset(ec_area_list()[$slug])) {
        echo '<p>Metinler yapay zekâ ile taslak olarak yazıldı; rakam ve işletme adı içermez. Okuyup gerekirse düzeltin.</p><ul>';
        foreach (ec_area_list() as $s => [$c, $d]) {
            echo '<li><a href="' . esc_url(admin_url('admin.php?page=ec-areas&slug=' . $s)) . '">' . esc_html($d ? "$d (" . ec_cities()[$c]['name']['tr'] . ')' : ec_cities()[$c]['name']['tr']) . '</a> — ' . (empty($all[$s]) ? '<em>metin yok</em>' : (!empty($all[$s]['reviewed']) ? '✔ gözden geçirildi' : 'taslak')) . ' · <a href="' . esc_url(ec_url_area($s, 'tr')) . '" target="_blank">sayfa</a></li>';
        }
        echo '</ul></div>';
        return;
    }
    $t = $all[$slug] ?? [];
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=ec-areas')) . '">← Liste</a></p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('ec_area_save');
    echo '<input type="hidden" name="action" value="ec_area_save"><input type="hidden" name="slug" value="' . esc_attr($slug) . '">';
    foreach (['tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский'] as $l => $ln) {
        echo '<h2>' . $ln . '</h2><p><input class="large-text" name="' . $l . '_title" value="' . esc_attr($t[$l]['title'] ?? '') . '" placeholder="Başlık"></p>';
        echo '<p><input class="large-text" name="' . $l . '_desc" value="' . esc_attr($t[$l]['desc'] ?? '') . '" placeholder="Açıklama"></p>';
        echo '<p><textarea class="large-text" rows="14" name="' . $l . '_body">' . esc_textarea($t[$l]['body'] ?? '') . '</textarea></p>';
    }
    echo '<p><button class="button button-primary">Kaydet</button></p></form></div>';
}
