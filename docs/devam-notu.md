# Devam notu (yeni oturum için)

Bu dosya, projeyi yeni bir oturumda kaldığı yerden sürdürmek için yazıldı. Önce bunu, sonra `README.md`, `docs/kurulum.md`, `docs/api.md` ve `docs/veri-modeli.md` dosyalarını oku.

## Proje

- **Amaç:** KKTC (Kuzey Kıbrıs) emlak ilan portalı. Rakipler: 101evler.com ve hangiev.com.
- **Gelir modeli:**
  - İlan vermek ücretsiz.
  - Para, emlakçılara satılan **otomasyon paketlerinden** gelecek: Telegram'a atılan ilan, emlakçının kendi tarayıcısında çalışan bir Chrome eklentisiyle 101evler ve hangiev'e de girilecek.
  - Fiyatlar: aylık 100 ilan 50 €, haftalık 30 ilan 20 €, tek ilan 2 €.
- **Altyapı:** Kullanıcının Plesk VPS'inde WordPress (PHP 8.1+). İleride Next.js + Supabase'e "bir gecelik bakım" ile geçilecek şekilde tasarlandı: kendi tabloları, sabit linkler ve `/api/v1` API'si var.
- **Marka:** Henüz kesin değil. Şimdilik "AdaEv". Elde boşta `emlak392.com` alan adı var (392 KKTC'nin telefon kodu). Logo ve ikonlar yazısız sembol, site adı WordPress'in "Site başlığı" ayarından geliyor.
- **Kod:** `wordpress/wp-content/plugins/emlak-core/` (iş mantığı), `wordpress/wp-content/themes/emlak-tema/` (görünüm), `extension/` (Chrome eklentisi).
- **Paketleme:** `bash scripts/paketle.sh` komutu `dist/emlak-core-SÜRÜM.zip`, `dist/emlak-tema-SÜRÜM.zip` ve `dist/ilan-aktarici-SÜRÜM.zip` dosyalarını üretir. Kullanıcı bunları yönetim panelinden "mevcut sürümü değiştir" seçeneğiyle yükler.
- **Dal:** `claude/jolly-carson-l927uv`.

## Tema adı (0.6.1)
Tema klasörü `adaev` → `emlak-tema`, tema adı "Emlak Tema". Etkinleştirmede `theme_mods_adaev` yeni temaya taşınır (`functions.php`). İç fonksiyon önekleri (`ae_`) aynı kaldı. Marka (site adı) hâlâ "Site başlığı" ayarından gelir.

## Sürüm durumu (0.6)

- **360° fotoğraflar (`includes/panos.php`, tablo `wp_ec_panos`):**
  - 2:1 oran kontrolü; WebP en çok 6000 px + küçük resim; ilan başına 15; her fotoğrafın oda adı var.
  - Panelde ayrı bölüm. Normal yükleyiciye verilen 2:1 dosya da otomatik 360°'a gider.
  - Telegram'da "dosya olarak" gönderilen 360° fotoğraf tanınır.
  - İlan sayfasında Pannellum 2.5.6 (`assets/vendor/pannellum`, tıklayınca yüklenir), oda geçişi.
  - Matterport/Kuula vb. sanal tur linkleri sayfa içinde açılır (`ec_tour_embed`).
  - Arama `pano=1` filtresi, kartlarda "360°" rozeti.
  - Facebook'ta ilk 360° fotoğraf `allow_spherical_photo` ile ayrı gönderi (Ayarlar'da `fb_360`). Instagram'a ortadan düz kesit.
- **Tapu türü sadece satılıkta:**
  - Çekirdekte (`ec_listing_create` / `update`) kiralıkta `deed_type` boşaltılıyor. `ec_listings_present` kiralıkta tapu döndürmüyor.
  - Arama kutusunda kiralık sekmesinde gizli. Form, ilan sayfası, onay paneli, Telegram önizlemesi, yapay zekâ ve sosyal metin buna göre düzenlendi.
  - DB v6 eski kiralık tapularını sildi.
- Testler: social-test 30/30 (360° dahil), backend 29/29.

## Sürüm durumu (0.5)

0.5'te eklenenler:
- **Paketler (`includes/packages.php`, yönetim: Emlak → Paketler):**
  - Yönetici paket tanımlar ve istediği üyeye istediği süre ve kotayla verir; uzatır, iptal eder.
  - Özellikler: `syndication` (Chrome eklentisi), `social`, `telegram`.
  - Kota: farklı ilan sayısı; bir ilan bir pakette bir kez sayılır.
  - Bitişe 3 gün kala üyeye ve yöneticiye uyarı gider.
  - Panelim → Paketim sekmesi.
  - Ödeme şimdilik site dışında (elle).
- **Sosyal medya (`includes/social.php`, yönetim: Emlak → Sosyal medya):**
  - Yöneticinin tek Meta uygulaması; müşteri kendi sayfasında yöneticiye "tam kontrol" erişimi verir.
  - Bağlantı iki yoldan kurulur: "Facebook ile bağlan" ya da Graph API Gezgini'nden alınan anahtar. Sayfa anahtarlarının süresi dolmaz ve şifreli saklanır.
  - Her sayfa listede siteye ya da bir üyeye atanır.
  - İlan yayına girince (`ec_listing_published` olayı) gönderi sıraya alınır, zamanlanmış görev paylaşır.
  - Facebook'ta çoklu fotoğraflı gönderi; Instagram'da kaydırmalı gönderi (fotoğraflar JPEG'e çevrilir, `-ig.jpg`).
  - Metin şablonu 3 dilde. Üye gönderi dilini ve imzasını seçer (Panelim → Sosyal medya).
  - Facebook grupları **bilerek yok**.
  - Belgeler: `docs/sosyal-medya-kurulum.md` ve çizimli rehber `docs/sosyal-medya-rehberi.html` (Claude'da yayımlandı: https://claude.ai/artifact/FLKHamnsMnGtkd2GU1Svrr).
- **Hız (`includes/speed.php`, yönetim: Emlak → Hız):**
  - Ziyaretçi sayfalarına `s-maxage=600` ve `X-Accel-Expires`, kişisel sayfalara `no-store` başlığı.
  - İlan değişince Cloudflare'de ilgili adresler temizlenir.
  - Önbellek açıkken para birimi ve favoriler tarayıcıda uygulanır.
  - Yazı tipleri temada (`assets/fonts`). Leaflet sadece harita görününce yüklenir.
  - Mobil Lighthouse 82–85 → 96–97.
  - Plesk, Cloudflare ve nginx tarifi: `docs/hiz.md` (nginx yapılandırması burada test edildi).
- Testler: `tests/social-test.php` 28/28. Yerel nginx + php-fpm 8090 portunda (`/etc/nginx/sites-enabled/default`, `conf.d/ec-cache.conf`).

## Sürüm durumu (0.4)

0.3'te bitmiş olanlar (3 dil, slider, arama, ilan/proje detayı, fiyat endeksi, blog, favoriler, ofis vitrini, üyelik ve onay, Panelim formları, tapu türü, TCMB kurları, döviz çevirici, temsili görsel, demo verisi) aynen duruyor. 0.4'te eklenenler:

- **İlan detayı (101evler eksikleri):**
  - Tam ekran galeri: yanda küçük resimler, sayaç, slayt gösterisi, klavye ve kaydırma
  - Simgeli bilgi şeridi, kaydırınca beliren sabit başlık ve bölüm menüsü
  - Gruplanmış konut detayları, dış / iç / konum diye gruplanmış özellikler
  - Leaflet harita ve "Haritada gör"
  - Yakındaki yerler: Overpass, 2 km, 30 gün önbellek
  - Bölge fiyat trendi grafiği
  - Paylaş düğmeleri: Facebook, X, WhatsApp, Viber, Telegram, LinkedIn, Pinterest, e-posta, kopyala
  - "Mesaj gönder" formu: talepler Panelim → Talepler'e, e-postaya ve Telegram'a gider; yöneticiye kopya
  - "Hatalı ilan bildir" (onay panelinde listelenir), "Notum" (tarayıcıda saklanır), yazdırma CSS'i
  - Ofis kartında "N. yıl" rozeti ve "Diğer ilanlarını incele"
- **Proje detayı:**
  - Bilgi kutucukları; yeni alanlar `project_type`, `build_status`, `land_m2`
  - İlerleme çubuğu, daire tipi kartları (kat planı, dosya adı daire tipiyle eşleşir), sosyal alanlar
  - Taksit hesaplayıcı, kat planları, broşür, video, harita
  - Firmanın diğer projeleri, mesaj ve paylaş
- **Ana sayfa ve genel:**
  - Fotoğraflı ve sayılı şehir kartları (fotoğraflar Ayarlar'dan)
  - Kartlarda ofis logosu ve adı
  - Firmalar dizini `/tr/firmalar/` (aranabilir)
  - Arama kutusunda para birimi seçimi
- **Gün içi kur:**
  - Twelve Data → Yahoo yedeği
  - TCMB'ye göre %5 sapma kontrolü, 30 dakikadan eski değer kullanılmaz
  - Sitede "Piyasa kuru · saat", çeviricide ayrıca TCMB resmi kuru
  - Ayarlar'dan açılır
- **Telegram botu ve yapay zekâ:**
  - Webhook, eşleştirme, fotoğraf ve metin toplama (albüm desteği)
  - Claude ile alan çıkarma ve 3 dile çeviri (`claude-opus-5-5`, JSON şeması, sunucu tarafı yedek model)
  - Önizleme: Yayınla / Düzelt / İptal; yöneticiye Onayla / Reddet; komutlar
  - SMS kodu aktarımı
- **Chrome eklentisi (`extension/`):**
  - MV3 popup'ta ilan listesi ve "101evler'e / hangiev'e aktar"
  - Form doldurma motoru (`filler.js`): önce seçici, sonra etiket metni; bağımlı alanlar (şehir → bölge)
  - Özellik kutuları, fotoğraflar (WebP → JPEG)
  - Captcha uyarısı, SMS kodu, "Yayınladım" kaydı
  - Alan ayarları sunucudan JSON olarak güncellenir
- **Canlıya çıkış:** Emlak → Canlıya çıkış sayfası ve `wp emlak check`. Sabit sayfaları taslak oluşturma aracı.
- **Düzeltme:** `sanitize_text_field` "%40 peşin" metnindeki "%40"ı siliyordu. `ec_clean_text` eklendi.

Testler (bu oturumda geçti):
- Arka uç: 28/28, dış servisler taklit edildi.
- Tarayıcı etkileşimleri: Playwright.
- Eklenti: gerçek Chromium'da sahte 101evler formuna karşı uçtan uca.

## Bekleyen işler

0. **Sosyal medya canlı deneme:** kullanıcının Meta uygulaması ve bir test sayfasıyla. Bu oturumda Graph API taklitle test edildi. Oturuma bağlı Facebook bağlantısının süresi dolmuştu.
1. **Eklenti seçicilerini kesinleştirmek:** 101evler ve hangiev'in gerçek "ilan ver" sayfalarının kaynak kodu (giriş yapılmış hâli) gelince `ec_ext_default_config()` güncellenecek. O zamana kadar etiket metnine göre arama yapılıyor.
2. **Anahtarlar (kullanıcıda):** BotFather token, yönetici chat id, Claude API anahtarı, (isteğe bağlı) Twelve Data anahtarı, Turnstile anahtarları.
3. **Online ödeme (sürüm 3):** Paketler ve kota hazır; ödeme alındıktan sonra paketin otomatik verilmesi kaldı.
4. **Chrome Web Store'a yükleme:** Şimdilik "paketlenmemiş öğe yükle" ile kuruluyor.
5. **Canlıya çıkış:** Emlak → Canlıya çıkış sayfasındaki maddeler; tarifler `docs/kurulum.md` dosyasında.

Bilerek yapılmayanlar: demografik veri (güvenilir kaynak yok, uydurma veri gösterilmez).

## Yerel test ortamı (oturum değişince yeniden kurulur)

```bash
apt-get update && apt-get install -y mariadb-server
```

```bash
mkdir -p /tmp/wpdev && cd /tmp/wpdev && composer require -q johnpbloch/wordpress-core:^6 && cp -r vendor/johnpbloch/wordpress-core site
```

```bash
curl -sSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x /usr/local/bin/wp
```

Sonra:

1. Veritabanı ve kullanıcı: `adaev` / `adaev`.
2. `wp core install` ve kalıcı bağlantılar: `/%postname%/`.
3. Eklenti ve temayı depodan sembolik link olarak bağla.
4. `php -S 0.0.0.0:8080 -t site` ile sunucuyu başlat (yönlendirici betik verme; verilirse JS/CSS dosyaları da WordPress'e gider).
5. Demo veri için: `wp emlak seed`.

Notlar:
- wordpress.org bu ortamdan engelli, WordPress çekirdeği Composer ile iner.
- Tarayıcı testleri Playwright ile yapılır.
- Bu ortamdan Overpass, Telegram, Yahoo ve Twelve Data erişilemiyor; testler `pre_http_request` ile taklit ediliyor.
- Eklenti testi: Playwright `launchPersistentContext` ile `channel: 'chromium'`, sahte form yerel sunucudan, ayarda `new_url` geçici olarak yerel adrese çevrilir.
- 101evler ve hangiev Cloudflare bot korumasının arkasında, doğrudan açılamaz. Karşılaştırma kullanıcının ekran görüntüleri ve kaynak kodlarıyla yapılır.

## Kullanıcıyla çalışma şekli

- Türkçe konuşulur.
- Kullanıcı "bekle" derse hiçbir işlem yapılmaz.
- Büyük işlerden önce plan ve sorular yazılır, onay beklenir.
- Her teslimde zip paketleri **sürüm numaralı** gönderilir (`emlak-core-X.Y.Z.zip`, `emlak-tema-X.Y.Z.zip`, `ilan-aktarici-X.Y.Z.zip`; `scripts/paketle.sh` sürümü başlıklardan okur) ve kurulum sırası hatırlatılır. Bir parça değiştiyse onun sürümü artırılır.
