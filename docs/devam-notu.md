# AdaCasa: devam notu (yeni sohbet için)

Yeni oturumda **önce bu dosyayı oku**, sonra `README.md`, `docs/kurulum.md`, `docs/api.md`, `docs/veri-modeli.md` dosyalarına bak.
Dal: `claude/jolly-carson-l927uv`. Kod buradadır, her şey commit edilip gönderildi.

---

## 1. Yeni oturumda ilk iş: marka değişikliği (henüz yapılmadı)

Kullanıcının kararı:

- **Marka:** **AdaCasa**. Logodaki alt yazı: "GAYRİMENKUL & YATIRIM".
- **Alan adı:** **adacasa.com**.
- **Logo:** `docs/marka/adacasa-logo.png`, aynısının `.webp` hali de var (1024×572, beyaz zemin).
  - Sol tarafta sembol: ev çatısı, mavi dalgalar, altın kıvrım.
  - Sağ tarafta yazı: "adacasa"; "ca" altın, gerisi lacivert.
- **Logo renkleri (yaklaşık):**
  - Lacivert `#14284B`
  - Mavi `#4A8FC7` / `#7DB3DF`
  - Altın `#B8893F`

  Sitenin şimdiki paleti koyu turkuaz (`--acc:#16343b`) ve altın (`--gold:#b48a45`). Yeni oturumda paletin logoya uyumu kullanıcıya sorulmalı.
- **Paket adları:** kullanıcı yeni sohbette paketleri şu adlarla isteyecek:
  - `adacasa-them1.01` → büyük olasılıkla **tema**: `adacasa-theme-1.0.1.zip`
  - `adacasa-core1.0.1` → **eklenti**: `adacasa-core-1.0.1.zip`

  "them" kelimesini ve 1.01 / 1.0.1 farkını kullanıcıya bir kez sor; önerin tema `adacasa-theme`, eklenti `adacasa-core`, ikisi de **1.0.1**.

Yapılacaklar listesi (öneri; büyük iş olduğu için önce kullanıcıya planı göster):

1. **Klasör ve ad değişikliği.**
   - `plugins/emlak-core` → `plugins/adacasa-core` (ana dosya `adacasa-core.php`, Plugin Name: "AdaCasa Core").
   - `themes/emlak-tema` → `themes/adacasa-theme` (Theme Name: "AdaCasa").
   - `scripts/paketle.sh` yeni adlara göre güncellenecek.
2. **Geçişte dikkat edilecekler.**
   - WordPress eklentiyi klasör/dosya adından tanır. Klasör adı değişirse eski eklenti devre dışı kalır, yenisi ayrı eklenti olarak kurulur.
   - Veri tabloları (`wp_ec_*`) ve ayarlar (`ec_*` seçenekleri) aynı kalır, kayıp olmaz. Yeni eklenti etkinleştirilince şema kontrolü kendiliğinden çalışır.
   - Sıra: yeni eklentiyi yükle → eski "Emlak Core"u devre dışı bırak → yeniyi etkinleştir → eskiyi sil.
   - Aynı anda ikisi etkin olursa fonksiyon adları çakışır (fatal error). Yeni eklentiye "eski eklenti etkinse etkinleşme, uyarı göster" koruması eklenmeli.
   - Tema: `after_switch_theme` içindeki ayar taşıma, `theme_mods_emlak-tema` (ve eski `theme_mods_adaev`) → yeni temaya kopyalayacak şekilde genişletilecek.
   - İç fonksiyon önekleri (`ec_`, `ae_`), tablo adları (`wp_ec_*`) ve REST yolu (`/api/v1`, `emlak/v1`) **değişmesin**. Bot, Chrome eklentisi ve gelecekteki Next.js geçişi bunlara bağlı.
3. **Logo.** Temada `ae_logo()` şu an SVG sembol ve site adını gösteriyor.
   - AdaCasa logosu SVG'ye çevrilebilirse en iyisi. Çevrilemezse PNG/WebP ile `assets/img/` altına konulur, şeffaf zemin gerekebilir.
   - Favicon, `apple-touch-icon`, `og-default.jpg` ve `site.webmanifest` adları/renkleri güncellenecek.
4. **Marka metinleri.**
   - Fallback "AdaEv" geçen yerler: `ae_brand()`, demo e-postaları, rehber, SSS.
   - Site adı WordPress'in "Site başlığı" ayarından gelir; canlıda "AdaCasa" yazılacak.
5. **Belgeler.**
   - `docs/neden-biz.html` ve `.pdf` rehberi AdaCasa adı, logosu ve renkleriyle yeniden üretilecek. PDF, Playwright ile `page.pdf` kullanılarak üretilir. Yayımlanmış hali: https://claude.ai/artifact/YL2sUFCN2h12Nt6Te49o2d; aynı dosya yolundan yeniden yayımlanırsa adres aynı kalır.
   - Kurulum belgelerinde `emlak392.com` örnekleri → `adacasa.com`.
6. **Chrome eklentisi.** Adı "İlan Aktarıcı" kalabilir ya da "AdaCasa İlan Aktarıcı" olur; manifest sürümü artırılır.
7. **Teslim.** `emlak-core` 0.7 içeriği yeni adlarla **1.0.1** olarak verilecek (bkz. 3. bölüm).
   - Kurulum sırası kullanıcıya hatırlatılacak:
     1. Yeni eklentiyi yükle → eskisini devre dışı bırak → yeniyi etkinleştir → eskisini sil.
     2. Yeni temayı yükle ve etkinleştir → eski temayı sil.
     3. Ayarlar → Site başlığı = "AdaCasa".
     4. Emlak → Ayarlar → Araçlar → "Benzer ilanları şimdi tara".
     5. Emlak → Hız → Cloudflare önbelleğini temizle.

---

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
  - `wordpress/wp-content/plugins/emlak-core/`: iş mantığı, tablolar, REST, yönetim.
  - `wordpress/wp-content/themes/emlak-tema/`: görünüm.
  - `extension/`: Chrome eklentisi.
  - `tests/`: testler.
  - `docs/`: belgeler.
- **Paketleme:** `bash scripts/paketle.sh`. Sürüm numaraları başlıklardan okunur. Çıktılar: `dist/emlak-core-X.zip`, `dist/emlak-tema-X.zip`, `dist/ilan-aktarici-X.zip`.

## 3. Sürüm durumu

| Parça | Kullanıcıya verilen son sürüm | Daldaki sürüm (teslim bekliyor) |
|---|---|---|
| Eklenti (emlak-core) | 0.6.0 | **0.7.0** |
| Tema (emlak-tema) | 0.6.1 | **0.7.0** |
| Chrome eklentisi | 0.4.0 | 0.4.0 |

Kullanıcının kuralı: "Her şey tamam olana kadar yeni sürüm verme." 0.7 değişiklikleri teslim edilmedi. Marka değişikliğiyle birlikte **AdaCasa 1.0.1** olarak verilecek.

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

1. **Marka değişikliği ve 1.0.1 teslimi** (1. bölüm).
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
curl -sSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x /usr/local/bin/wp
```

Sonra:

1. Veritabanı ve kullanıcı: `adaev` / `adaev`.
2. `wp core install` (admin/admin). Kalıcı bağlantılar: `/%postname%/`.
3. Eklenti ve temayı depodan sembolik link olarak bağla, ikisini de etkinleştir.
4. `php -S 0.0.0.0:8080 -t site` (yönlendirici betik verme).
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
