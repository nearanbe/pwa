# Siteyi en hızlı hale getirme (ücretsiz)

Hedef: Ziyaretçi sayfaları Cloudflare'in yakın sunucusundan (İstanbul / Frankfurt) gelsin. Sunucuya hiç ulaşmasın; ulaşırsa da nginx önbelleğinden 1 ms'de dönsün.
Test ortamındaki ölçüm (mobil Lighthouse, yavaş 4G):
- **Önce:** 82–85 puan.
- **Sonra:** 96–97 puan.
- **Önbellekten sunucu cevabı:** 1 ms'nin altında.
- **Önbelleksiz sunucu cevabı:** ~25 ms.

Sıra önemli değil, ama hepsi yapılmalı. Toplam ~30 dakika. SSH gerekmez; isteğe bağlı SSH adımları en sonda.

---

## 1. WordPress (2 dakika)
1. **Emlak → Hız → "Ziyaretçi sayfaları önbelleğe alınabilsin"** ✔ → Kaydet.
2. Aynı sayfada **Cloudflare Zone ID** ve **API anahtarı**: 3.5'te alınır. Bunlar girilince yeni ilan eklendiğinde ilgili sayfalar Cloudflare'de anında yenilenir.
3. Sayfanın altındaki **Ölçüm** satırı sunucunun ana sayfayı kaç ms'de ürettiğini ve OPcache'in açık olup olmadığını gösterir.

## 2. Plesk (10 dakika)
**Web Siteleri ve Alan Adları → alan adın** altında:

### 2.1 PHP
**PHP** (PHP Ayarları):
- **PHP sürümü:** en yeni 8.3 ya da 8.4, **"FPM application served by Apache"** (ya da nginx).
- Aşağıdaki ayarları bul ve değiştir:

  | Ayar | Değer |
  |---|---|
  | `opcache.enable` | **on** |
  | `opcache.memory_consumption` | **192** |
  | `opcache.max_accelerated_files` | **20000** |
  | `opcache.revalidate_freq` | **60** |
  | `memory_limit` | **256M** |
- **Tamam.**

### 2.2 Apache ve nginx
**Hosting ve DNS → Apache & nginx Ayarları**:
- **nginx proxy modu:** açık (varsayılan).
- **Statik dosyaları doğrudan nginx ile sun:** ✔
- **Statik dosyalar için süre sonu (Expires):** **365 gün** (`31536000`). Tema dosyaları sürüm numarasıyla yüklendiği için güvenli.
- **nginx önbelleğe almayı etkinleştir (Enable nginx caching):** ✔
  - Önbellek boyutu: **256 MB**
  - Önbellek anahtarı: varsayılan
  - Zaman aşımı: **2 dakika** (site zaten başlıkla söyler, bu yedektir)
  - **Önbelleği atlayacak çerezler (Bypass cache for cookies):** `wordpress_logged_in_`, `wp-postpass_`, `comment_author_`
  - **Önbelleğe alınmayacak konumlar (Don't cache locations):** `/wp-admin`, `/wp-login.php`, `/api/`, `/wp-json/`, `/wp-cron.php`
- **Ek nginx yönergeleri (Additional nginx directives)** kutusuna yapıştır:

```nginx
# Metin dosyalarını sıkıştır (Cloudflare ayrıca Brotli ile sıkıştırır)
gzip on;
gzip_comp_level 5;
gzip_min_length 512;
gzip_vary on;
gzip_proxied any;
gzip_types text/css application/javascript application/json image/svg+xml application/xml text/xml application/manifest+json;
```

- **Tamam.** Plesk ayarı kontrol eder. Hata verirse sadece o yönergeyi sil; Plesk'in kendisi zaten gzip açık geliyor olabilir.

### 2.3 Zamanlanmış görev ve wp-cron
Zamanlanmış görev 5 dakikada bir çalışıyorsa (kurulum belgesi), ziyaretçilerin cron tetiklemesini kapat. Bu her ziyareti biraz hızlandırır:
- **Dosya Yöneticisi → httpdocs → wp-config.php → düzenle.** `/* That's all, stop editing! */` satırının **üstüne** ekle:

```php
define('DISABLE_WP_CRON', true);
```

### 2.4 HTTP/3 (isteğe bağlı)
**Apache & nginx Ayarları → HTTP/3 (QUIC)** varsa ✔. Cloudflare zaten ziyaretçiye HTTP/3 ile bağlanır; bu sadece Cloudflare ile sunucu arasını etkiler.

## 3. Cloudflare (15 dakika, ücretsiz plan)
**dash.cloudflare.com → alan adın**:

### 3.1 SSL ve ağ
- **SSL/TLS → Genel bakış:** **Full (strict)** (kurulum belgesindeki Origin Certificate ile).
- **SSL/TLS → Edge Certificates:** **Always Use HTTPS** ✔, **TLS 1.3** ✔, **Automatic HTTPS Rewrites** ✔.
- **Network (Ağ):** **HTTP/3 (with QUIC)** ✔, **0-RTT** ✔, **WebSockets** açık kalabilir.

### 3.2 Hız
- **Speed → Optimization → Content Optimization:** **Early Hints** ✔. **Rocket Loader ✘ KAPALI**, sitenin betiklerini bozar.
- **Caching → Tiered Cache:** **Smart Tiered Caching** ✔.
- **Caching → Configuration:** **Browser Cache TTL: "Respect Existing Headers"**.

### 3.3 Sayfa önbelleği kuralı (en önemli adım)
**Caching → Cache Rules → Create rule**:
- Ad: `Sayfa önbelleği`
- **When incoming requests match… → Edit expression** ve yapıştır (alan adını kendi adınla değiştir):

```
(http.host eq "emlak392.com" or http.host eq "www.emlak392.com")
and not starts_with(http.request.uri.path, "/wp-admin")
and not starts_with(http.request.uri.path, "/wp-login.php")
and not starts_with(http.request.uri.path, "/wp-json")
and not starts_with(http.request.uri.path, "/api/")
and not starts_with(http.request.uri.path, "/wp-cron.php")
and not http.cookie contains "wordpress_logged_in"
and not http.cookie contains "wp-postpass"
```

- **Then → Cache eligibility: Eligible for cache**
- **Edge TTL:** **Use cache-control header if present, bypass cache if not**
- **Browser TTL:** **Respect origin**
- **Deploy.**

Site kişisel sayfaları (panel, giriş, favoriler) zaten "önbelleğe alma" başlığıyla gönderir. Kural onları önbelleğe almaz.
Ofis alt alan adları (`ofis.emlak392.com`) da hızlansın istersen ilk satırı şöyle yap: `(http.host eq "emlak392.com" or ends_with(http.host, ".emlak392.com"))`.

### 3.4 Güvenlik (hız ve botlar)
- **Security → Bots → Bot Fight Mode: ✘ KAPALI.** Açıkken Telegram'ın webhook'unu ve Instagram'ın fotoğraf indirmesini engelleyebilir; ücretsiz planda bunun için istisna tanımlanamaz.
- Saldırı olursa geçici olarak **Security → Settings → Security Level: High** yeterli.

### 3.5 Önbellek temizleme anahtarı (sitedeki "Hız" sayfası için)
- **Overview** sayfasının sağ altında **Zone ID** → kopyala.
- Sağ üst profil → **My Profile → API Tokens → Create Token → Create Custom Token**:
  - **Permissions:** `Zone` · `Cache Purge` · `Purge`
  - **Zone Resources:** `Include` · `Specific zone` · alan adın
  - **Continue → Create Token** → kopyala.
- WordPress → **Emlak → Hız** → Zone ID ve anahtarı yapıştır → **Kaydet** → "Cloudflare bağlantısı çalışıyor ✅" yazmalı.

## 4. Kontrol
1. Gizli pencerede siteyi aç. **F12 → Network** → sayfayı yenile → ilk satır → **Response Headers**:
   - `cf-cache-status: HIT` (ikinci yenilemede) → Cloudflare'den geliyor ✅
   - `cache-control: public, max-age=0, s-maxage=600` → site doğru başlığı gönderiyor ✅
2. **pagespeed.web.dev** → site adresi → mobil puanı 90'ın üstünde olmalı.
3. Giriş yap → panel sayfasında `cf-cache-status: BYPASS` ya da `DYNAMIC` olmalı (kişisel sayfa önbelleğe alınmıyor ✅).

## 5. İsteğe bağlı (SSH ile, daha da hızlı)

### 5.1 Redis nesne önbelleği (yönetim paneli ve API hızlanır)
```bash
sudo apt-get update && sudo apt-get install -y redis-server
```

```bash
sudo systemctl enable --now redis-server
```

Sonra WordPress → Eklentiler → Yeni ekle → **"Redis Object Cache"** (Till Krüss) → Etkinleştir → **Ayarlar → Redis → Enable Object Cache**.
**Emlak → Hız** sayfası "Kalıcı nesne önbelleği: açık ✅" gösterir.

### 5.2 PHP'yi nginx ile çalıştırıyorsan: nginx FastCGI önbelleği
Plesk'te "FPM application served by **nginx**" seçtiysen ve 2.2'deki nginx önbelleği kutusu yoksa bu ayar aynı işi görür. Test ortamında denendi: ziyaretçi sayfaları `HIT`, panel ve giriş yapanlar `BYPASS`, sıkıştırma açık.

```bash
sudo tee /etc/nginx/conf.d/emlak-cache.conf >/dev/null <<'EOF'
fastcgi_cache_path /var/cache/nginx/emlak levels=1:2 keys_zone=EMLAK:50m max_size=512m inactive=60m use_temp_path=off;
fastcgi_cache_key "$scheme$request_method$host$request_uri";
map $http_cookie $emlak_skip_cookie { default 0; ~wordpress_logged_in 1; ~wp-postpass 1; }
EOF
```

```bash
sudo mkdir -p /var/cache/nginx/emlak && sudo nginx -t && sudo systemctl reload nginx
```

Plesk → alan adı → **Apache & nginx → Ek nginx yönergeleri:**

```nginx
set $emlak_skip $emlak_skip_cookie;
if ($request_method = POST) { set $emlak_skip 1; }
if ($request_uri ~* "^/(wp-admin|wp-login\.php|wp-json|api/|wp-cron\.php)") { set $emlak_skip 1; }
if ($http_authorization) { set $emlak_skip 1; }
fastcgi_cache EMLAK;
fastcgi_cache_bypass $emlak_skip;
fastcgi_no_cache $emlak_skip;
fastcgi_cache_use_stale error timeout updating http_500 http_503;
fastcgi_cache_lock on;
add_header X-Cache $upstream_cache_status;
```

Kontrol (SSH):

```bash
curl -sI https://emlak392.com/tr/ | grep -iE "x-cache|cache-control"
```

İkinci çalıştırmada `X-Cache: HIT` görünmeli.

## Nasıl çalışıyor?
- Site, ziyaretçi sayfasını `Cache-Control: public, s-maxage=600` (Cloudflare 10 dk saklar) ve `X-Accel-Expires: 120` (nginx 2 dk saklar) başlıklarıyla gönderir.
- İlan eklenince, değişince ya da satılınca o ilanın 3 dildeki sayfası, ana sayfalar, ilgili şehir/liste sayfaları ve ofis vitrini Cloudflare'den anında silinir.
- Kur günde bir değişince tüm önbellek bir kez temizlenir.
- Önbellekli sayfa herkese aynı gider. Ziyaretçinin seçtiği para birimi ve favori kalpleri tarayıcıda uygulanır.
- Yazı tipleri artık Google'dan değil sitenin kendi sunucusundan gelir. Harita kütüphanesi (160 KB) sadece harita ekrana gelince yüklenir.
