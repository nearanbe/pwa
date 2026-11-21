# AdaCasa: devam notu (yeni sohbet için)

Yeni oturumda **önce bu dosyayı oku**, sonra `README.md`, `docs/kurulum.md`, `docs/api.md`, `docs/veri-modeli.md` dosyalarına bak.
Dal: `claude/jolly-carson-l927uv`. Kod buradadır, her şey commit edilip gönderildi.

---

## 1. Marka değişikliği: AdaCasa 1.0.1 (yapıldı, kullanıcıya teslim edildi)

- **Marka:** AdaCasa ("GAYRİMENKUL & YATIRIM"), alan adı **adacasa.com**. Orijinal logo: `docs/marka/adacasa-logo.png` / `.webp`.
- **SVG sembol:** `docs/marka/adacasa-mark.svg` (orijinalden elle yeniden çizildi, birebir değil). Temada `ae_logo_mark()` + `ae_logo_word()`; site başlığı tam "AdaCasa" ise "ada**ca**sa" yazısı ve dile göre alt yazı çıkar.
  - Lacivert kısımlar CSS değişkeni `--lgn` ile boyanır; koyu temada ve alt bilgide açık renk olur.
- **Palet (logoya uyduruldu):** `--acc` lacivert `#14284b`, `--gold` `#b8893f`, `--blue` `#4a8fc7`, `--blue2` `#7db3df`, zemin `#f8f9fb`. Koyu tema lacivert tonlarında.
- **Klasörler:** `plugins/adacasa-core/adacasa-core.php` ("AdaCasa Core"), `themes/adacasa-theme` ("AdaCasa").
  - İç önekler (`ec_`, `ae_`), tablolar (`wp_ec_*`) ve REST yolları değişmedi.
- **Geçiş koruması:**
  - Eski `emlak-core` etkinken AdaCasa Core etkinleştirilirse eskiyi sessizce devre dışı bırakır ve bir kez başarı uyarısı gösterir.
  - İkisi aynı anda etkin kalırsa yenisi hiçbir şey yüklemez, sadece uyarı gösterir; çökme olmaz.
- **Tema ayarları:** `after_switch_theme` ile `theme_mods_emlak-tema`, yoksa `theme_mods_adaev`, yeni temaya kopyalanır.
- **Görseller ve diğer parçalar:**
  - Favicon, PNG ikonlar, apple-touch-icon, og-default.jpg (orijinal PNG logo ve lacivert bant) ve manifest yenilendi.
  - Chrome eklentisi "AdaCasa İlan Aktarıcı" adını aldı, sürümü 0.4.1; ikonları ve renkleri yenilendi.
- **Rehber:** `docs/neden-biz.html` ve `.pdf` AdaCasa adı, logosu ve paletiyle yeniden üretildi; aynı adresten yeniden yayımlandı.
- **Testler:** `tests/social-test.php` artık `/tmp/x/pano1.jpg` dosyasını yoksa kendisi üretiyor. Eski 0.7'den 1.0.1'e geçiş yerelde denendi (wp-cli).

## 1b. Sürüm 1.0.2: profesyonel görünüm (teslim edildi)

- Canlı site: adacasa.com, Plesk ve WordPress üzerinde, Cloudflare arkasında. Bu ortamda `adacasa.com` izinli adres listesinde; WP uygulama parolası ortamın ağ sırrı olarak kayıtlı (Basic auth). Uygulama parolası sadece REST API'de geçerli, wp-admin sayfalarına giriş yapmaz.
  - `/wp/v2/users/me` adresi Plesk tarafından 403 ile engelleniyor; yetki kontrolü için `/wp/v2/settings` kullanılır.
- **Ana sayfa:** fotoğraflı giriş, `ae_hero_photo()`. Seçenek `ec_hero_photo`, yoksa `assets/img/hero-*.webp`. Bu görseller Cloudflare `@cf/leonardo/lucid-origin` ile üretildi; belirli bir yeri göstermeyen genel bir Akdeniz kıyısı.
  - İlan slaytları kaldırıldı; öne çıkan ilanlar zaten aşağıda ayrı bölümde.
- **Arama kutusu:** `.sf-main` (Konut, Şehir, Bölge ve sabit Ara) ile `.sf-more` (diğer filtreler). Telefonda "Detaylı arama" ile açılır, Ara düğmesi hep en altta kalır.
- **Üst bar:** `.bar>.tools` telefonda ☰ ile açılan panel. Dikkat: ilan sayfası yan kutusunda da `.tools` sınıfı var; seçiciler `.bar>` ile sınırlı.
- **Diğer:**
  - `ae_ad()` reklam kodu yoksa ziyaretçiye boş döner.
  - `ae_date()` tarihi sayfanın diline göre yazar.
  - Danışman kartlarında ad tekrarı kaldırıldı; yerine "Emlak Ofisi · Şehir" yazıyor.
  - Alt bilgide marka, WhatsApp ve e-posta solda.
  - Telefonda ilan ve proje sayfalarında `.mbar` alt iletişim çubuğu var; sıralama galeri → bilgiler → iletişim.
- **Kullanıcıdan beklenen:** şehir kartları için gerçek fotoğraflar. Unsplash bot korumalı, buradan alınamadı.

## 1c. Sürüm 1.0.3: PWA ve şehir fotoğrafları

- **PWA:** `themes/adacasa-theme/inc/pwa.php`.
  - Service worker kök adresten `/?ec_pwa=sw` ile sunulur. `/sw.js` kullanılmadı, çünkü uzantılı adresler PHP yerleşik sunucusunda ve bazı nginx ayarlarında WordPress'e ulaşmıyor.
  - Manifest dile göre `/?ec_pwa=manifest&lang=xx` adresinden gelir.
  - Sayfalar: önce ağ, sonra saklanan sayfa, sonra çevrimdışı sayfası. Sadece private/no-store olmayan sayfalar saklanır; sayfa önbelleği kapalıyken de kişisel sayfalar ve giriş yapmış kullanıcının sayfaları private işaretlenir.
  - Statik dosyalar: önbellekten verilir, arka planda tazelenir.
  - Telefon menüsünde "Uygulamayı yükle" düğmesi var; iPhone'da talimat gösterir.
  - Manifest ekran görüntüleri: `assets/img/screen-*.webp`.
  - Chrome denetimi (`Page.getInstallabilityErrors`) temiz.
- **Şehir fotoğrafları:** Wikimedia Commons'tan alındı, özgür lisanslı ve atıf zorunlu. Atıf, medya açıklamasında "Foto: Ad · Lisans · Wikimedia Commons (adres)" biçiminde tutulur; ana sayfada kartların altında otomatik yazılır.
  - Canlı sitede medya no 18–23: lefkosa 18, girne 19, gazimagusa 20, iskele 21, guzelyurt 22, lefke 23.
  - `ec_city_photos` 1.0.3 kurulunca REST ile ayarlanacak.
- **Ortam izinleri:** commons.wikimedia.org ve upload.wikimedia.org izinli. thumb.wikimedia.org izinli değil; küçük görsel adreslerinde alan adı upload.wikimedia.org ile değiştirilir. Wikimedia sık istekte 429 verir; istekler arasında beklenir.
- **Örnek içerikler** ("Hello world!" yazısı 1, "Sample Page" sayfası 2, yorum 1): silme ve çöpe taşıma bu oturumda otomatik izin denetimine takıldı. Kullanıcı kendisi siler.

## 1d. Canlı demo verisi (21.11.2026)

- **Demo verisi:** kullanıcı canlı sitede yükledi (Emlak → Ayarlar → Araçlar): 62 ilan, 6 firma, 6 proje.
- **Fotoğraflar:** çizim görseller REST ile Cloudflare yapay zekâsı (`@cf/leonardo/lucid-origin`) ile üretilmiş gerçekçi fotoğraflarla değiştirildi.
  - 20 fotoğraf: 11 dış cephe ve 9 iç mekân. Toplam 230 yükleme; 138 eski çizim silindi.
  - 360° panoramalar 2048×1024: 10002 Lapta villa (Teras, Salon), 10007 Long Beach 1+1, 10003 Lefkoşa penthouse, 10010 Long Beach kiralık villa.
  - Betikler oturumun geçici klasöründeydi (`upload.py`, `delold.py`). Mantığı:
    1. `/emlak/v1/listings` ile ilanları listele.
    2. `POST /listings/{id}/photos` ile yeni fotoğrafları yükle.
    3. `PUT …/photos/order` ile yenileri başa al.
    4. `DELETE …/photos/{pid}` ile eski çizimleri sil.
    5. `POST …/panos` ile 360° ekle.
- **Gerçek ilan fotoğrafı kullanılmadı:** telifli ve yanıltıcı olurdu. Demo, Ayarlar → Araçlar → Demo verisini sil ile fotoğraflarıyla birlikte silinir.
- **Bu ortamdan yapılamayanlar:**
  - Cloudflare önbelleğini temizlemek: ortamdaki api.cloudflare.com anahtarının zone yetkisi yok.
  - Telegram'a bağlanmak (api.telegram.org kapalı); Telegram testini kullanıcı telefonundan yapar.
- **Yerel test:** uygulama parolası HTTP üzerinde sadece `WP_ENVIRONMENT_TYPE=local` iken çalışır.

## 1e. Sürüm 1.0.4

- **Galeri penceresi:** `ae_gallery_view()` (helpers-detail.php), `#gv`, detail.js "Galeri penceresi" bölümü.
  - Mozaik düzeni: bir satırda 3, bir satırda 2 fotoğraf; telefonda 2 sütun.
  - Fotoğrafa tıklanınca mevcut büyük görüntü penceresi (`lbOpen`) açılır.
  - 360° görüntüleyici pencere içinde ayrı açılır (`#gvpano`).
  - Video, sekmesi açılınca yüklenir ve pencere kapanınca durur.
- **Video:** `includes/videos.php`.
  - `ec_video_info()` bağlantıyı tanır: youtube, vimeo, dosya ya da düz bağlantı.
  - Uçlar: POST/DELETE `/listings/{id}/video`.
  - Dosya `ilan-foto/{id}/video-*.ext` olarak kaydedilir; demo silinirken `ec_video_cleanup()` ile temizlenir.
  - Panel formu: `#vidfile`; yükleme ilerlemesi XHR ile gösterilir.
- **Genişlik:** `--w:1320px`, kenar boşluğu 24 px; telefonda 12 px. İlan yan kutusu 380 px.
- **Giriş görseli:** Girne limanı (A.Savin, FAL); atıf `assets/hero-credit.json` dosyasından okunup `.hcredit` olarak gösterilir.
- **Bilinen:** proje sayfasının videosu hâlâ eski kodla (sadece YouTube) gösteriliyor; ilan galerisindeki yeni pencere de proje sayfasında yok.

## 2. Proje özeti

- **Amaç:** KKTC (Kuzey Kıbrıs) emlak ilan portalı. Rakipler: 101evler.com ve hangiev.com. İkisi de Cloudflare bot korumalı; bu ortamdan açılamaz.
- **Gelir:**
  - İlan vermek ücretsiz.
  - Para otomasyon paketlerinden gelir: 101evler/hangiev'e Chrome eklentisiyle aktarım ve Facebook/Instagram paylaşımı.
  - Örnek fiyatlar: aylık 100 ilan 50 €, haftalık 30 ilan 20 €, tek ilan 2 €.
  - Paketleri yönetici elle verir; online ödeme yok.
- **Altyapı:**
  - Kullanıcının Plesk VPS'i, WordPress (PHP 8.1+), DNS Cloudflare'de.
  - Kendi tablolar, sabit linkler ve `/api/v1` API'si sayesinde ileride Next.js + Supabase'e "bir gecelik bakım" ile geçilebilir. Ölçütler `docs/veri-modeli.md` dosyasında; KKTC ölçeğinde uzun süre gerek yok.
- **Dil:** TR / EN / RU (`/tr/…`, `/en/…`, `/ru/…`).
- **Kod:**
  - `wordpress/wp-content/plugins/adacasa-core/`: iş mantığı, tablolar, REST, yönetim.
  - `wordpress/wp-content/themes/adacasa-theme/`: görünüm.
  - `extension/`: Chrome eklentisi.
  - `tests/`: testler.
  - `docs/`: belgeler.
- **Paketleme:** `bash scripts/paketle.sh`. Sürüm numaraları başlıklardan okunur. Çıktılar: `dist/adacasa-core-X.zip`, `dist/adacasa-theme-X.zip`, `dist/ilan-aktarici-X.zip`.

## 3. Sürüm durumu

| Parça | Kullanıcıya verilen son sürüm | Daldaki sürüm (teslim bekliyor) |
|---|---|---|
| Eklenti (adacasa-core, eski emlak-core) | **1.0.4** | 1.0.4 |
| Tema (adacasa-theme, eski emlak-tema) | **1.0.4** | 1.0.4 |
| Chrome eklentisi | **0.4.1** | 0.4.1 |

Kullanıcının kuralı: "Her şey tamam olana kadar yeni sürüm verme." 0.7 içeriği marka değişikliğiyle birlikte **AdaCasa 1.0.1** olarak teslim edildi.

### Bitmiş özellikler (0.1 – 0.7)

**Portal**
- Slider'lı ana sayfa ve şehir kartları (fotoğraflar Ayarlar'dan; satılık ve kiralık sayıları).
- Arama, 101evler düzeninde (0.7):
  - Solda filtre paneli; telefonda alttan açılan çekmece.
  - Filtreler: kelime/ilan no, çoklu oda, m², banyo, eşya, özellikler, kimden, tarih, 360°.
  - Izgara / Liste / Harita görünümü; harita fiyat etiketleriyle, yakın ilanlar gruplanır.
  - Numaralı sayfalar. Kartlarda fotoğraf sayısı ve oda/banyo/m² simgeleri.
- İlan detayı:
  - Tam ekran galeri, bilgi şeridi, sabit başlık ve bölüm menüsü, gruplanmış detaylar ve özellikler.
  - Leaflet harita (görününce yüklenir) ve yakın yerler (Overpass, 30 gün önbellek).
  - Bölge fiyat trendi, paylaş düğmeleri.
  - Mesaj gönder (talepler), hatalı ilan bildir, notum, yazdır, ofis yıl rozeti.
  - 360° tur (Pannellum, tıklayınca yüklenir); Matterport/Kuula gibi sanal turlar sayfa içinde açılır.
- Proje detayı: kutucuklar (tip, durum, alan), daire tipleri, kat planları, taksit hesabı, broşür, harita.
- Firmalar dizini `/tr/firmalar/`, ofis vitrini (`/tr/ofis/x/` ya da alt alan adı), fiyat endeksi, blog, favoriler.
- SSS `/tr/sss/` (22 soru, FAQPage işaretlemesi, içerik `inc/faq.php`).
- **Tapu türü sadece satılıkta** (0.6); kiralıkta hiçbir yerde yok.
- Kurlar:
  - Resmi kur kaynak sırası: TCMB → KKTC MB → ECB → er-api.
  - Gün içi piyasa kuru: Twelve Data, olmazsa Yahoo; TCMB'den %5'ten fazla sapan değer reddedilir.
  - Döviz çevirici.

**Üyelik ve panel**
- Üyelik: 4 tür, e-posta doğrulama, Turnstile, onay kuralları.
- Panelim sekmeleri: İlan formu (360° dahil), Projeler, Talepler, **Paketim**, **Sosyal medya**, Profil/vitrin, Telegram.
- Onay paneli `/tr/onay/`: ilanlar, hesaplar, projeler, hatalı ilan ve kopya ilan bildirimleri.

**Otomasyon**
- **Telegram botu:**
  - Eşleştirme, fotoğraf ve metin toplama, Claude ile alan çıkarma ve 3 dile çeviri.
  - Önizleme ve Yayınla/Düzelt; yöneticiye Onayla/Reddet.
  - SMS kodu aktarımı; 360° fotoğraf (dosya olarak gönderilirse).
- **Chrome "İlan Aktarıcı":**
  - 101evler/hangiev formlarını doldurur, fotoğrafları ekler; son tık insanda.
  - Seçiciler sunucudan JSON olarak gelir. Gerçek sayfa kaynakları henüz gelmedi; seçiciler tahmini.
- **Paketler** (Emlak → Paketler): tanım, verme, uzatma, iptal, kota; bitime 3 gün kala uyarı.
- **Sosyal medya** (Emlak → Sosyal medya):
  - Yöneticinin Meta uygulaması; müşteri sayfasında yöneticiye erişim verir.
  - Facebook çok fotoğraflı gönderi ve ayrıca 360° gönderi; Instagram kaydırmalı gönderi (JPEG'e çevrilir).
  - Sitenin hesabı her zaman paylaşır; gruplara paylaşım bilerek yok.
  - Rehberler: `docs/sosyal-medya-kurulum.md` ve `docs/sosyal-medya-rehberi.html` (yayımlandı: https://claude.ai/artifact/FLKHamnsMnGtkd2GU1Svrr).
- **Kopya ilan denetimi** (0.7):
  - Fotoğraf parmak izi (yatay + dikey dHash ≤4 bit) ve normalleştirilmiş açıklama md5'i.
  - Ekleyene uyarı; başka üyeyle eşleşmede otomatik bildirim; günlük tarama; isteğe bağlı onaya düşürme.

**Yönetim**
- Ayarlar, Paketler, Sosyal medya, Hız, Canlıya çıkış kontrolü (`wp emlak check`), Görsel üret (Cloudflare AI).
- Yönetici fotoğraf ve 360° ekranı: İlanlar → "Fotoğraflar / 360°".

**Hız** (`docs/hiz.md`)
- Sayfa önbelleği başlıkları (`s-maxage`, `X-Accel-Expires`); ilan değişince Cloudflare'den ilgili adresler temizlenir.
- Yerel yazı tipleri. Mobil Lighthouse 96–97.

**Belgeler**
- `kurulum.md`, `hiz.md`, `sosyal-medya-kurulum.md`, `api.md`, `veri-modeli.md`.
- `neden-biz.html` ve `.pdf` (emlakçı rehberi, 11 sayfa).

## 4. Bekleyen işler ve kullanıcıdan beklenenler

1. Kullanıcının 1.0.1 kurulumunu yapıp geri bildirim vermesi (logo SVG çizimi beğenilmezse PNG kullanılabilir).
2. **Anahtarlar (kullanıcıda):**
   - BotFather token ve yönetici chat id
   - Claude API anahtarı
   - Turnstile anahtarları
   - Meta App ID/Secret
   - Cloudflare Zone ID ve önbellek temizleme anahtarı
   - (isteğe bağlı) Twelve Data anahtarı
3. **Kesinleştirilecekler:**
   - 101evler/hangiev "ilan ver" sayfalarının kaynak kodu (giriş yapılmış hali) gelince eklenti seçicileri.
   - Meta uygulamasıyla ilk gerçek paylaşım: bu ortamdaki Facebook bağlantısının süresi dolmuştu; Graph API taklitle test edildi.
4. **Sunucu:**
   - PHP ayarları: `upload_max_filesize` / `post_max_size` 32M, `memory_limit` 256M.
   - Plesk'te 5 dakikalık zamanlanmış görev (wp-cron).
   - Bot Fight Mode kapalı.
   - Yedekleme ve izleme (UptimeRobot).
   - İleride fotoğraf deposu: Cloudflare R2.
5. **Rehber:** paket fiyatları rehberde sabit; değişirse rehber yeniden üretilmeli. İletişim numarası rehbere eklenmedi; kullanıcı verecek.
6. **Online ödeme** (sürüm 3) ve Chrome Web Store yayını.

Bilerek yapılmayanlar: Facebook gruplarına paylaşım, demografik veri (güvenilir kaynak yok).

## 5. Yerel test ortamı (oturum değişince yeniden kurulur)

```bash
apt-get update && apt-get install -y mariadb-server nginx php8.3-fpm
```

```bash
mkdir -p /tmp/wpdev && cd /tmp/wpdev && composer require -q johnpbloch/wordpress-core:^6 && cp -r vendor/johnpbloch/wordpress-core site
```

```bash
cd /tmp/wpdev && composer require -q wp-cli/wp-cli-bundle
```

wp-cli: `/tmp/wpdev/vendor/bin/wp --allow-root --path=/tmp/wpdev/site`. Sistem klasörüne phar indirmek ve `0.0.0.0` üzerinde sunucu açmak otomatik izin denetimine takıldı; sunucu `php -S 127.0.0.1:8080 -t site` ile arka plan görevi olarak çalıştırılır.

Sonra:

1. Veritabanı ve kullanıcı: `adaev` / `adaev`.
2. `wp core install` (admin/admin). Kalıcı bağlantılar: `/%postname%/`.
3. Eklenti ve temayı depodan sembolik link olarak bağla, ikisini de etkinleştir.
4. `php -S 127.0.0.1:8080 -t site` (yönlendirici betik verme).
5. `wp emlak seed`.

Notlar:
- Container ara sıra yeniden başlar; MariaDB düşerse `service mariadb start`.
- nginx önbellek testi için 8090 portunda yapılandırma: `docs/hiz.md` → 5.2.
- Testler:
  - `tests/backend-test.php` (29) ve `tests/social-test.php` (30): `wp transient delete --all` ile, taze demo verisiyle çalıştırılır. Dış servisler `pre_http_request` ile taklit edilir.
  - Tarayıcı testleri: `tests/*.js`.
  - Eklenti testi: Playwright `launchPersistentContext`, `channel: 'chromium'`.
- Bu ortamdan erişilemeyenler: wordpress.org, Overpass, Telegram, Yahoo, Twelve Data, harita karoları. Google Fonts ve npm erişilebilir.
- Test sonrası demo kirlenirse: `UPDATE wp_ec_listings SET source='demo' WHERE source<>'demo'`, ardından `wp emlak unseed && wp emlak seed`.

## 6. Kullanıcıyla çalışma şekli

- Türkçe konuşulur. Kullanıcı "bekle" derse hiçbir işlem yapılmaz.
- Büyük işlerden önce plan ve sorular yazılır. Kullanıcı "hepsini yap" derse makul varsayımlarla ilerlenir, varsayımlar söylenir.
- Teslimlerde zip'ler **sürüm numaralı** gönderilir ve kurulum sırası hatırlatılır. Değişmeyen parça tekrar gönderilmez.
- "Her şey tamam olana kadar sürüm verme" kuralı: değişiklikler dalda birikir ve bu notta "teslim bekleyen" olarak yazılır.
- Kullanıcı Plesk erişimi ya da kendi bilgisayarına bağlanma önerdi; şimdiye kadar gerekmedi.
