<?php
/**
 * Emlak → Canlıya çıkış: yayına almadan önce yapılması gerekenlerin otomatik kontrolü.
 * Her satır yeşil (tamam), sarı (önerilir) ya da kırmızı (gerekli) görünür; nasıl düzeltileceği yanında yazar.
 * WP-CLI: wp emlak check
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_submenu_page('ec-listings', 'Canlıya çıkış', 'Canlıya çıkış', 'manage_options', 'ec-launch', 'ec_admin_launch');
}, 20);

/** @return array<int, array{0: string, 1: string, 2: string}> [durum ok|warn|fail, başlık, açıklama] */
function ec_launch_checks(): array
{
    global $wpdb;
    $out = [];
    $add = function (string $st, string $t, string $d = '') use (&$out) {
        $out[] = [$st, $t, $d];
    };
    $host = preg_replace('/^www\./', '', (string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $isLocal = in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.test');

    $add(str_starts_with(home_url('/'), 'https://') ? 'ok' : 'fail', 'Site HTTPS ile açılıyor', 'Ayarlar → Genel → WordPress ve site adresi https:// ile başlamalı; Plesk’te SSL sertifikası kurulu olmalı.');
    $add(get_option('permalink_structure') === '/%postname%/' ? 'ok' : 'fail', 'Kalıcı bağlantılar “Yazı adı”', 'Ayarlar → Kalıcı bağlantılar → “Yazı adı” seçip kaydedin.');
    $add(!get_option('blog_public') ? 'fail' : 'ok', 'Arama motorları siteyi görebiliyor', 'Ayarlar → Okuma → “Arama motorlarının bu siteyi indekslemesini engelle” işaretli olmamalı.');
    $add(ec_demo_active() ? 'fail' : 'ok', 'Demo verisi silindi', 'Emlak → Ayarlar → Demo verisini sil.');
    $ts = ec_turnstile_keys();
    $add($ts['site'] && $ts['secret'] ? 'ok' : 'fail', 'Cloudflare Turnstile anahtarları', 'Kayıt, giriş ve mesaj formlarını botlardan korur. Kurulum belgesi → Turnstile.');

    $last = (int) get_option('ec_cron_last', 0);
    $cronOk = $last && time() - $last < 20 * MINUTE_IN_SECONDS;
    $add($cronOk ? 'ok' : 'warn', 'Zamanlanmış görev (wp-cron) düzenli çalışıyor', ($last ? 'Son çalışma: ' . wp_date('d.m.Y H:i', $last) . '. ' : 'Henüz çalıştığı görülmedi. ') . 'Plesk → Zamanlanmış Görevler’e her 5 dakikada bir çalışan görevi ekleyin (kurulum belgesi). Kurlar, endeks ve Telegram “otomatik hazırla” buna bağlı.');

    if (!$isLocal && function_exists('dns_get_record')) {
        $txt = @dns_get_record($host, DNS_TXT) ?: [];
        $spf = array_filter($txt, fn($r) => str_starts_with(strtolower($r['txt'] ?? ''), 'v=spf1'));
        $add($spf ? 'ok' : 'fail', 'SPF kaydı', 'DNS’e TXT kaydı: <code>v=spf1 a mx ip4:SUNUCU_IP ~all</code> (Plesk → Alan adı → DNS’te hazır gelir; DNS Cloudflare’deyse oraya da ekleyin).');
        $dmarc = @dns_get_record('_dmarc.' . $host, DNS_TXT) ?: [];
        $add($dmarc ? 'ok' : 'warn', 'DMARC kaydı', 'TXT <code>_dmarc</code> = <code>v=DMARC1; p=none; rua=mailto:' . esc_html(get_option('admin_email')) . '</code>');
        $dkim = @dns_get_record('default._domainkey.' . $host, DNS_TXT) ?: [];
        $add($dkim ? 'ok' : 'warn', 'DKIM kaydı (Plesk “default” seçicisi)', 'Plesk → Alan adı → Posta → Posta ayarları → “DKIM ile imzala”yı açın; çıkan <code>default._domainkey</code> TXT kaydını Cloudflare DNS’e ekleyin.');
        if (get_option('ec_subdomains')) {
            $probe = 'ec-dns-test-' . substr(md5($host), 0, 6) . '.' . $host;
            $add(gethostbyname($probe) !== $probe ? 'ok' : 'fail', 'Yıldızlı alt alan adı (*) DNS kaydı', 'Cloudflare → DNS → A kaydı, ad <code>*</code>, turuncu bulut açık. Ardından Origin Certificate (kurulum belgesi).');
        }
    } else {
        $add('warn', 'E-posta DNS kayıtları (SPF / DKIM / DMARC)', 'Yerel ortamda kontrol edilemez; canlı alan adında bu sayfayı tekrar açın.');
    }

    $tg = ec_tg_settings();
    $add($tg['token'] && $tg['admin_chat'] ? 'ok' : 'warn', 'Telegram bot anahtarı ve yönetici sohbeti', 'Emlak → Ayarlar → Telegram.');
    $add(!empty($tg['webhook_ok']) ? 'ok' : 'warn', 'Telegram webhook kurulu', 'Emlak → Ayarlar → Araçlar → “Telegram webhook’unu kur”.');
    $add(ec_ai_settings()['key'] ? 'ok' : 'warn', 'Claude API anahtarı', 'Telegram’dan ilan için gerekli. Emlak → Ayarlar → Yapay zekâ.');
    $meta = (array) get_option('ec_rates_meta', []);
    $add(!empty($meta['updated_at']) && time() - strtotime($meta['updated_at'] . ' UTC') < 2 * DAY_IN_SECONDS ? 'ok' : 'warn', 'Resmi kurlar güncel', 'Son güncelleme: ' . (!empty($meta['updated_at']) ? get_date_from_gmt($meta['updated_at'], 'd.m.Y H:i') : '—'));

    $up = wp_max_upload_size();
    $add($up >= 24 * MB_IN_BYTES ? 'ok' : 'warn', 'Yükleme sınırı (360° fotoğraflar için)', 'Şu an ' . size_format($up) . '. Plesk → PHP ayarları → upload_max_filesize ve post_max_size = 32M, memory_limit = 256M.');
    $sp = ec_speed_settings();
    $add($sp['cache'] ? 'ok' : 'warn', 'Sayfa önbelleği (hız)', 'Emlak → Hız → “Ziyaretçi sayfaları önbelleğe alınabilsin”. Tarif: docs/hiz.md');
    $add($sp['cf_token'] && $sp['cf_zone'] ? 'ok' : 'warn', 'Cloudflare önbellek temizleme anahtarı', 'Emlak → Hız. İlan eklenince sayfalar anında yenilenir.');
    // Plesk opcache_get_status()'u kapatabiliyor (disable_functions); o durumda ini ayarına bakılır
    $opc = is_callable('opcache_get_status') ? !empty(@opcache_get_status(false)['opcache_enabled']) : (extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN));
    $add($opc ? 'ok' : 'warn', 'PHP OPcache', 'Plesk → PHP ayarları → opcache.enable = on');
    $siteAcc = array_filter(ec_social_accounts(0), fn($a) => $a['status'] === 'ok');
    $add($siteAcc ? 'ok' : 'warn', 'Sitenin Facebook / Instagram hesabı bağlı', 'Emlak → Sosyal medya (docs/sosyal-medya-kurulum.md).');
    $broken = array_filter(ec_social_accounts(), fn($a) => $a['status'] !== 'ok' && $a['agent_id'] !== null);
    if ($broken) {
        $add('fail', 'Kopan sosyal medya bağlantısı', count($broken) . ' sayfa: ' . esc_html(implode(', ', array_column($broken, 'page_name'))) . ' — Emlak → Sosyal medya → bağlantıyı yenile.');
    }
    $co = (array) get_option('ec_company', []);
    $add(!empty($co['name']) && !empty($co['address']) && !empty($co['email']) ? 'ok' : 'warn', 'Şirket ve yasal bilgiler (yasal sayfalar)', 'Emlak → Ayarlar → Şirket ve yasal bilgiler: unvan, adres, yasal iletişim e-postası. Yasal sayfalar TR/EN/RU olarak hazır; bir avukata kontrol ettirin.');
    $add(get_option('admin_email') && !str_ends_with((string) get_option('admin_email'), '@example.com') ? 'ok' : 'fail', 'Yönetici e-postası', 'Ayarlar → Genel → Yönetim e-posta adresi; bildirimler buraya gelir.');
    $add(get_bloginfo('name') && get_bloginfo('name') !== 'AdaEv' && get_bloginfo('name') !== 'Emlak392' ? 'ok' : 'warn', 'Site başlığı (marka)', 'Ayarlar → Genel → Site başlığı. Şu an: ' . esc_html(get_bloginfo('name')));
    $cp = (array) get_option('ec_city_photos', []);
    $add(count($cp) >= 4 ? 'ok' : 'warn', 'Ana sayfa şehir fotoğrafları', count($cp) . ' / ' . count(ec_cities()) . ' şehir. Emlak → Ayarlar → Şehir kartları.');
    $open = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('reports') . " WHERE status = 'open'");
    if ($open) {
        $add('warn', 'Açık hatalı ilan bildirimi', $open . ' bildirim onay panelinde bekliyor.');
    }
    return $out;
}

function ec_admin_launch(): void
{
    $icons = ['ok' => '✅', 'warn' => '🟡', 'fail' => '⛔'];
    $rows = ec_launch_checks();
    $fails = count(array_filter($rows, fn($r) => $r[0] === 'fail'));
    echo '<div class="wrap"><h1>Canlıya çıkış kontrolü</h1>';
    echo '<p>' . ($fails ? "<b>$fails</b> zorunlu madde eksik." : '<b>Zorunlu maddelerin hepsi tamam.</b>') . ' Sarı maddeler önerilir. Ayrıntılı tarifler: <code>docs/kurulum.md</code>.</p>';
    echo '<table class="widefat striped"><tbody>';
    foreach ($rows as [$st, $t, $d]) {
        echo '<tr><td style="width:28px">' . $icons[$st] . '</td><td style="width:32%"><b>' . esc_html($t) . '</b></td><td>' . wp_kses_post($d) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<h2>Plesk zamanlanmış görevi</h2><p>Plesk → Web Siteleri ve Alan Adları → <b>Zamanlanmış Görevler</b> → Görev ekle → “URL getir” → her 5 dakikada bir:</p><p><code>' . esc_html(site_url('/wp-cron.php?doing_wp_cron')) . '</code></p>';
    echo '</div>';
}
