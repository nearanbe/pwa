<?php
/**
 * PWA (1.0.3): uygulama olarak yüklenebilme ve çevrimdışı çalışma.
 *  /?ec_pwa=manifest&lang=tr  → dile göre ad, simgeler, kısayollar (Satılık, Kiralık, İlan Ver)
 *  /?ec_pwa=sw                 → service worker (kök kapsam "/")
 * Kök adres + sorgu kullanılır: uzantılı adresler (/sw.js) bazı sunucularda (Plesk nginx statik dosya ayarı)
 * WordPress'e hiç ulaşmadan 404 verebilir. /sw.js ve /manifest.webmanifest de desteklenir.
 * Önbellek kuralları (sw.js):
 *  - Sayfalar: önce ağ; ağ yoksa daha önce açılmış sayfa, o da yoksa çevrimdışı sayfası.
 *    Sadece herkese açık sayfalar saklanır (Cache-Control'da private/no-store olanlar saklanmaz: panel, giriş, favoriler).
 *  - Tema dosyaları, yüklenen fotoğraflar: önbellekten hemen, arka planda tazelenir.
 *  - Yönetim, giriş, REST/API, POST istekleri: hiç dokunulmaz.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** İstenen yol (site alt klasördeyse onu düşerek): "sw.js", "manifest.webmanifest" … */
function ae_pwa_path(): string
{
    $base = rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return ltrim($path, '/');
}

add_action('init', function () {
    $p = ae_pwa_path();
    $q = isset($_GET['ec_pwa']) ? (string) $_GET['ec_pwa'] : '';
    if ($p === '' && $q === 'sw') {
        $p = 'sw.js';
    } elseif ($p === '' && $q === 'manifest') {
        $p = 'manifest.webmanifest';
    }
    if ($p === 'sw.js') {
        ae_pwa_sw();
        exit;
    }
    if ($p === 'manifest.webmanifest') {
        ae_pwa_manifest();
        exit;
    }
}, 1);

function ae_pwa_texts(string $lang): array
{
    $t = [
        'tr' => ['name' => 'Kıbrıs Emlak İlanları', 'desc' => 'Kuzey Kıbrıs’ta satılık, kiralık ve günlük kiralık evler, projeler ve fiyat endeksi.', 'sale' => 'Satılık', 'rent' => 'Kiralık', 'add' => 'İlan Ver',
                 'off_t' => 'Çevrimdışısınız', 'off_p' => 'İnternet bağlantınız yok gibi görünüyor. Daha önce açtığınız ilanlar bağlantı olmadan da görüntülenebilir.', 'off_b' => 'Tekrar dene'],
        'en' => ['name' => 'North Cyprus Property', 'desc' => 'Homes for sale, long-term and holiday rental, new projects and price index in North Cyprus.', 'sale' => 'For sale', 'rent' => 'For rent', 'add' => 'List property',
                 'off_t' => 'You are offline', 'off_p' => 'There seems to be no internet connection. Properties you have already opened can still be viewed.', 'off_b' => 'Try again'],
        'ru' => ['name' => 'Недвижимость Северного Кипра', 'desc' => 'Продажа, аренда и посуточная аренда, новые проекты и индекс цен на Северном Кипре.', 'sale' => 'Продажа', 'rent' => 'Аренда', 'add' => 'Разместить',
                 'off_t' => 'Нет подключения', 'off_p' => 'Похоже, нет интернета. Ранее открытые объявления можно смотреть без подключения.', 'off_b' => 'Повторить'],
    ];
    return $t[$lang] ?? $t['tr'];
}

function ae_pwa_manifest(): void
{
    $lang = in_array($_GET['lang'] ?? '', EC_LANGS, true) ? $_GET['lang'] : 'tr';
    $t = ae_pwa_texts($lang);
    $brand = ae_brand();
    $img = fn($f) => get_theme_file_uri('assets/img/' . $f);
    $rel = fn($u) => wp_make_link_relative($u);
    $icon192 = ['src' => $img('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'];
    $m = [
        'id' => '/',
        'name' => $brand . ' – ' . $t['name'],
        'short_name' => $brand,
        'description' => $t['desc'],
        'lang' => $lang,
        'dir' => 'ltr',
        'start_url' => $rel(ec_url_home($lang)) . '?utm_source=pwa',
        'scope' => '/',
        'display' => 'standalone',
        'display_override' => ['standalone', 'minimal-ui'],
        'orientation' => 'any',
        'background_color' => '#f8f9fb',
        'theme_color' => '#14284b',
        'categories' => ['business', 'lifestyle', 'travel'],
        'icons' => [
            $icon192,
            ['src' => $img('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => $img('icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            ['name' => $t['sale'], 'url' => $rel(ec_url_section('sale', $lang)) . '?utm_source=pwa', 'icons' => [$icon192]],
            ['name' => $t['rent'], 'url' => $rel(ec_url_section('rent', $lang)) . '?utm_source=pwa', 'icons' => [$icon192]],
            ['name' => $t['add'], 'url' => $rel(ec_url_section('add', $lang)) . '?utm_source=pwa', 'icons' => [$icon192]],
        ],
    ];
    $shots = [['screen-narrow.webp', '780x1688', 'narrow'], ['screen-wide.webp', '1280x800', 'wide']];
    foreach ($shots as [$f, $size, $ff]) {
        if (file_exists(get_theme_file_path('assets/img/' . $f))) {
            $m['screenshots'][] = ['src' => $img($f), 'sizes' => $size, 'type' => 'image/webp', 'form_factor' => $ff, 'label' => $brand];
        }
    }
    status_header(200);
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    header('X-Robots-Tag: noindex');
    echo wp_json_encode($m, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function ae_pwa_sw(): void
{
    $u = fn($f) => wp_make_link_relative(get_theme_file_uri($f));
    $ver = AE_VER . '-' . substr(md5((string) @filemtime(get_theme_file_path('assets/css/main.css')) . (string) @filemtime(get_theme_file_path('assets/js/main.js'))), 0, 8);
    $pre = [
        $u('assets/css/main.css') . '?ver=' . AE_VER,
        $u('assets/js/main.js') . '?ver=' . AE_VER,
        $u('assets/img/favicon.svg'),
        $u('assets/img/icon-192.png'),
    ];
    $off = [];
    foreach (EC_LANGS as $l) {
        $off[$l] = ae_pwa_texts($l);
    }
    $cfg = [
        'v' => $ver,
        'pre' => $pre,
        'off' => $off,
        'brand' => ae_brand(),
        'icon' => $u('assets/img/favicon.svg'),
        'skip' => ['/wp-admin', '/wp-login.php', '/wp-json', '/api/', '/wp-cron.php', '/xmlrpc.php', '/sw.js', '/manifest.webmanifest', '/feed'],
        'skipq' => 'ec_pwa',
    ];
    status_header(200);
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-cache, max-age=0');
    header('Service-Worker-Allowed: /');
    header('X-Robots-Tag: noindex');
    echo '/* ' . esc_html(ae_brand()) . ' service worker */' . "\n" . 'const CFG = ' . wp_json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";\n";
    echo <<<'JS'
const STATIC = 'ac-static-' + CFG.v, PAGES = 'ac-pages-' + CFG.v;
const MAX_PAGES = 40, MAX_STATIC = 160;

self.addEventListener('install', e => {
  e.waitUntil(caches.open(STATIC).then(c => c.addAll(CFG.pre)).catch(() => {}).then(() => self.skipWaiting()));
});
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k.startsWith('ac-') && k !== STATIC && k !== PAGES).map(k => caches.delete(k))))
    .then(() => self.clients.claim()));
});

async function trim(name, max) {
  const c = await caches.open(name), ks = await c.keys();
  for (let i = 0; i < ks.length - max; i++) await c.delete(ks[i]);
}
function storable(res) {
  if (!res || !res.ok || res.type !== 'basic') return false;
  const cc = (res.headers.get('Cache-Control') || '').toLowerCase();
  return !/no-store|private/.test(cc);
}
function offlinePage(url) {
  const lang = (url.pathname.match(/^\/(tr|en|ru)\//) || [, 'tr'])[1], t = CFG.off[lang] || CFG.off.tr;
  const html = '<!doctype html><html lang="' + lang + '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    + '<meta name="theme-color" content="#14284b"><title>' + t.off_t + ' · ' + CFG.brand + '</title><style>'
    + 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8f9fb;color:#142036;font:16px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;text-align:center;padding:24px}'
    + '@media(prefers-color-scheme:dark){body{background:#0b1526;color:#eef2f7}}'
    + 'img{width:64px;height:64px;border-radius:14px}h1{font:600 30px/1.2 Georgia,serif;margin:18px 0 8px}p{max-width:420px;margin:0 auto 22px;opacity:.8}'
    + 'button{border:0;background:#14284b;color:#fff;font:600 16px system-ui;padding:12px 22px;border-radius:12px;cursor:pointer}</style></head>'
    + '<body><div><img src="' + CFG.icon + '" alt=""><h1>' + t.off_t + '</h1><p>' + t.off_p + '</p><button onclick="location.reload()">' + t.off_b + '</button></div></body></html>';
  return new Response(html, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' } });
}

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;
  if (CFG.skip.some(p => url.pathname.startsWith(p)) || url.searchParams.has(CFG.skipq) || url.searchParams.has('preview') || url.searchParams.has('ec_preview')) return;

  // Sayfalar: önce ağ, sonra saklanan sayfa, sonra çevrimdışı sayfası
  if (req.mode === 'navigate') {
    e.respondWith((async () => {
      try {
        const res = await fetch(req);
        if (storable(res)) {
          const copy = res.clone();
          caches.open(PAGES).then(c => c.put(req, copy)).then(() => trim(PAGES, MAX_PAGES));
        }
        return res;
      } catch (err) {
        const hit = await caches.match(req, { ignoreSearch: true });
        if (hit) return hit;
        const home = await caches.match('/' + ((url.pathname.match(/^\/(tr|en|ru)\//) || [, 'tr'])[1]) + '/');
        return url.pathname === '/' && home ? home : offlinePage(url);
      }
    })());
    return;
  }

  // Tema dosyaları, yazı tipleri, yüklenen fotoğraflar: önbellekten hemen, arka planda tazele
  if (/^\/wp-(content|includes)\//.test(url.pathname) && /\.(css|js|woff2?|png|jpe?g|webp|avif|svg|gif)$/i.test(url.pathname)) {
    e.respondWith((async () => {
      const c = await caches.open(STATIC);
      const hit = await c.match(req);
      const net = fetch(req).then(res => {
        if (res && res.ok && res.type === 'basic') { c.put(req, res.clone()).then(() => trim(STATIC, MAX_STATIC)); }
        return res;
      }).catch(() => hit);
      return hit || net;
    })());
  }
});
JS;
}

/** <head>: manifest (sayfa diline göre), iPhone ana ekran etiketleri */
add_action('wp_head', function () {
    $lang = function_exists('ec_lang') ? ec_lang() : 'tr';
    echo '<link rel="manifest" href="' . esc_url(home_url('/?ec_pwa=manifest&lang=' . $lang)) . '">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr(ae_brand()) . '">' . "\n";
}, 2);

/**
 * Kişisel sayfalar (panel, giriş, favoriler) ve giriş yapmış kullanıcıların sayfaları service worker'da saklanmasın:
 * sayfa önbelleği kapalıyken de "private" işaretlenir (açıkken bunu Emlak Core yapar).
 */
add_action('template_redirect', function () {
    if (headers_sent() || !function_exists('ec_page_cache_on') || ec_page_cache_on() || !function_exists('ec_request_cacheable')) {
        return;
    }
    if (!ec_request_cacheable()) {
        header('Cache-Control: private, no-store, max-age=0');
    }
}, 100);
