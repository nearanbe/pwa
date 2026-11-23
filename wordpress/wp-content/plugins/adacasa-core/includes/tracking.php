<?php
/**
 * Ölçüm, doğrulama ve çerez onayı (1.1 / 1.1.5)
 *  - Search Console ve Bing doğrulama etiketleri her zaman eklenir (çerez kullanmaz).
 *  - Google Analytics 4 (analiz) ve Meta Pixel (pazarlama) SADECE ziyaretçi izin verirse yüklenir.
 *  - Tercih "ac_consent" çerezinde (6 ay): "a1m0" = analiz açık, pazarlama kapalı.
 *  - Sayfalar önbellekten gelse de çalışır: karar tarayıcıda verilir.
 *  - Yöneticiler ve editörler sayılmaz.
 *  - window.ecTrack('view_item'|'lead', {...}) temanın olayları; izin yoksa hiçbir şey göndermez.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_tracking(): array
{
    return array_merge(['ga4' => '', 'gsc' => '', 'pixel' => '', 'bing' => ''], (array) get_option('ec_tracking', []));
}

function ec_consent_texts(string $lang): array
{
    $t = [
        'tr' => ['title' => 'Çerez tercihleriniz', 'text' => 'Siteyi çalıştırmak için zorunlu çerezler kullanıyoruz. İzin verirseniz ziyaretleri ölçmek (analiz) ve reklamlarımızın etkisini görmek (pazarlama) için ek çerezler de kullanırız.', 'more' => 'Çerez Politikası', 'accept' => 'Tümünü kabul et', 'reject' => 'Sadece zorunlu', 'settings' => 'Ayarlar', 'save' => 'Seçimi kaydet', 'nec' => 'Zorunlu (her zaman açık)', 'an' => 'Analiz (Google Analytics)', 'mk' => 'Pazarlama ve reklam (Meta Pixel, Google AdSense)', 'open' => 'Çerez ayarları'],
        'en' => ['title' => 'Your cookie choices', 'text' => 'We use essential cookies to run the site. With your consent we also use cookies to measure visits (analytics) and the effect of our ads (marketing).', 'more' => 'Cookie Policy', 'accept' => 'Accept all', 'reject' => 'Essential only', 'settings' => 'Settings', 'save' => 'Save choices', 'nec' => 'Essential (always on)', 'an' => 'Analytics (Google Analytics)', 'mk' => 'Marketing and ads (Meta Pixel, Google AdSense)', 'open' => 'Cookie settings'],
        'ru' => ['title' => 'Настройки cookie', 'text' => 'Мы используем необходимые cookie для работы сайта. С вашего согласия мы также используем cookie для статистики посещений (аналитика) и оценки рекламы (маркетинг).', 'more' => 'Политика cookie', 'accept' => 'Принять все', 'reject' => 'Только необходимые', 'settings' => 'Настройки', 'save' => 'Сохранить', 'nec' => 'Необходимые (всегда включены)', 'an' => 'Аналитика (Google Analytics)', 'mk' => 'Маркетинг и реклама (Meta Pixel, Google AdSense)', 'open' => 'Настройки cookie'],
    ];
    return $t[$lang] ?? $t['tr'];
}

add_action('wp_head', function () {
    $t = ec_tracking();
    if ($t['gsc']) {
        echo '<meta name="google-site-verification" content="' . esc_attr($t['gsc']) . '">' . "\n";
    }
    if ($t['bing']) {
        echo '<meta name="msvalidate.01" content="' . esc_attr($t['bing']) . '">' . "\n";
    }
    $skip = current_user_can('edit_posts') || !empty($GLOBALS['ec_preview']);
    $ads = (array) get_option('ec_ads', []);
    $cfg = ['ga4' => $skip ? '' : $t['ga4'], 'pixel' => $skip ? '' : $t['pixel'], 'ads' => $skip ? '' : (string) ($ads['client'] ?? '')];
    // Onay okuyucu ve yükleyici (izin gelince GA4 / Pixel yüklenir)
    echo '<script>window.EC_TRK=' . wp_json_encode($cfg) . ";(function(){"
        . "function c(){var m=document.cookie.match(/(?:^|; )ac_consent=([^;]+)/);return m?m[1]:'';}"
        . "window.ecConsent=function(){var v=c();return{set:!!v,a:v.indexOf('a1')>-1,m:v.indexOf('m1')>-1};};"
        . "var L={};window.ecLoadTrackers=function(){var k=ecConsent(),T=window.EC_TRK;"
        . "if(k.a&&T.ga4&&!L.ga){L.ga=1;var s=document.createElement('script');s.async=1;s.src='https://www.googletagmanager.com/gtag/js?id='+T.ga4;document.head.appendChild(s);window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments);};gtag('js',new Date());gtag('config',T.ga4,{anonymize_ip:true});}"
        . "if(k.m&&T.pixel&&!L.px){L.px=1;!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',T.pixel);fbq('track','PageView');}"
        // Google AdSense: yalnızca pazarlama izniyle; izin yoksa reklam alanında kendi "reklam verebilirsiniz" görselimiz kalır
        . "if(k.m&&T.ads&&!L.ad){L.ad=1;document.documentElement.classList.add('ec-ads-on');var a=document.createElement('script');a.async=1;a.crossOrigin='anonymous';a.src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='+encodeURIComponent(T.ads);document.head.appendChild(a);}};"
        . "window.ecTrack=function(ev,p){try{p=p||{};if(window.gtag&&L.ga)gtag('event',ev==='lead'?'generate_lead':ev,p);if(window.fbq&&L.px)fbq('track',ev==='lead'?'Lead':(ev==='view_item'?'ViewContent':ev),p);}catch(e){}};"
        . "ecLoadTrackers();})();</script>\n";
}, 3);

/** Çerez onay kutusu (tüm sayfalar) */
add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    $lang = function_exists('ec_lang') ? ec_lang() : 'tr';
    $x = ec_consent_texts($lang);
    $more = function_exists('ec_url_legal') ? ec_url_legal('cookies', $lang) : '#';
    ?>
<div class="cconsent" id="cconsent" hidden role="dialog" aria-live="polite" aria-labelledby="cc-h">
  <b id="cc-h"><?php echo esc_html($x['title']); ?></b>
  <p><?php echo esc_html($x['text']); ?> <a href="<?php echo esc_url($more); ?>"><?php echo esc_html($x['more']); ?></a></p>
  <div class="cc-opts" hidden>
    <label><input type="checkbox" checked disabled> <?php echo esc_html($x['nec']); ?></label>
    <label><input type="checkbox" id="cc-a"> <?php echo esc_html($x['an']); ?></label>
    <label><input type="checkbox" id="cc-m"> <?php echo esc_html($x['mk']); ?></label>
  </div>
  <div class="cc-btns">
    <button type="button" class="ghost sm" data-cc="settings"><?php echo esc_html($x['settings']); ?></button>
    <button type="button" class="ghost sm" data-cc="reject"><?php echo esc_html($x['reject']); ?></button>
    <button type="button" class="btn sm" data-cc="accept"><?php echo esc_html($x['accept']); ?></button>
    <button type="button" class="btn sm" data-cc="save" hidden><?php echo esc_html($x['save']); ?></button>
  </div>
</div>
<script>(function(){var b=document.getElementById('cconsent');if(!b)return;
function save(a,m){var d=new Date();d.setTime(d.getTime()+182*864e5);document.cookie='ac_consent=a'+(a?1:0)+'m'+(m?1:0)+';expires='+d.toUTCString()+';path=/;SameSite=Lax'+(location.protocol==='https:'?';Secure':'');b.hidden=true;document.body.classList.remove('has-cc');if(window.ecLoadTrackers)ecLoadTrackers();if((!a||!m)&&(window.gtag||window.fbq))location.reload();}
function open(full){var k=window.ecConsent?ecConsent():{a:0,m:0};document.getElementById('cc-a').checked=!!k.a;document.getElementById('cc-m').checked=!!k.m;var o=b.querySelector('.cc-opts');o.hidden=!full;b.querySelector('[data-cc=save]').hidden=!full;b.querySelector('[data-cc=accept]').hidden=!!full;b.querySelector('[data-cc=settings]').hidden=!!full;b.hidden=false;document.body.classList.add('has-cc');}
b.addEventListener('click',function(e){var t=e.target.closest('[data-cc]');if(!t)return;var a=t.dataset.cc;
if(a==='accept')save(1,1);else if(a==='reject')save(0,0);else if(a==='settings')open(true);else if(a==='save')save(document.getElementById('cc-a').checked,document.getElementById('cc-m').checked);});
document.addEventListener('click',function(e){if(e.target.closest('[data-cookie-settings]')){e.preventDefault();open(true);}});
if(!(window.ecConsent&&ecConsent().set))setTimeout(function(){open(false);},600);})();</script>
<?php
}, 5);
