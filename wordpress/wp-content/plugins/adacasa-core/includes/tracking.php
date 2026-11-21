<?php
/**
 * Ölçüm ve doğrulama etiketleri (1.1): GA4, Search Console, Meta Pixel, Bing.
 * Ayarlar → Ölçüm ve doğrulama. Boş olan eklenmez; yöneticiler ve önizlemeler sayılmaz.
 * İlan sayfasında "ilan görüntüleme", mesaj formunda "lead" olayları gönderilir (theme: window.ecTrack).
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_tracking(): array
{
    return array_merge(['ga4' => '', 'gsc' => '', 'pixel' => '', 'bing' => ''], (array) get_option('ec_tracking', []));
}

add_action('wp_head', function () {
    $t = ec_tracking();
    if ($t['gsc']) {
        echo '<meta name="google-site-verification" content="' . esc_attr($t['gsc']) . '">' . "\n";
    }
    if ($t['bing']) {
        echo '<meta name="msvalidate.01" content="' . esc_attr($t['bing']) . '">' . "\n";
    }
    if (current_user_can('edit_posts') || !empty($GLOBALS['ec_preview'])) {
        return; // yönetici / editör ziyaretleri sayılmasın
    }
    if ($t['ga4']) {
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($t['ga4']) . '"></script>'
            . "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config'," . wp_json_encode($t['ga4']) . ",{anonymize_ip:true});</script>\n";
    }
    if ($t['pixel']) {
        echo "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init'," . wp_json_encode($t['pixel']) . ");fbq('track','PageView');</script>\n";
    }
    // Tema olayları için ortak çağrı: ecTrack('lead', {...})
    echo "<script>window.ecTrack=function(ev,p){try{p=p||{};if(window.gtag)gtag('event',ev==='lead'?'generate_lead':ev,p);if(window.fbq)fbq('track',ev==='lead'?'Lead':(ev==='view_item'?'ViewContent':ev),p);}catch(e){}};</script>\n";
}, 3);
