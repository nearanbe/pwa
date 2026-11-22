# AdaCasa: devir notu (22.11.2026)

Yeni oturumda **önce bu dosyayı oku**, sonra gerekirse `README.md`, `docs/kurulum.md`, `docs/api.md` ve `docs/veri-modeli.md`.

- **Dal:** `claude/jolly-carson-l927uv`. Her şey commit edildi ve gönderildi.
- **Paketleme:** `bash scripts/paketle.sh` → `dist/adacasa-core-X.zip`, `dist/adacasa-theme-X.zip`, `dist/ilan-aktarici-X.zip`. Sürüm numaraları dosya başlıklarından okunur.
- Önceki ayrıntılı sürüm notları git geçmişinde: `git log`, `git show <commit>:docs/devam-notu.md`.

## 0. 22.11 oturumu (tüm projeler tek oturumdan)
- Bu ortamda erişim: adacasa.com, adavira.com, stetosgop.com, ferihacelik.com.tr, mavicinar.com (WP REST, proxy ekler). Depolar: nearanbe/pwa ve nearanbe/stetosgop (dal `claude/jolly-carson-l927uv`).
- **Cloudflare anahtarı yenilendi:** Workers AI yalnızca ücretli hesapta (`f8f895b79ad9f366a28517dd3fe89e52`) çalışıyor; `b30a…` hesabında AI yetkisi yok. Zone okuma ve **önbellek temizleme artık çalışıyor** (adacasa a920477193d181a481d0e36b7a51955d, adavira 3bd7d938daeab16c4407b45c643178ff, stetosgop 7a196db28933088a585ba4041940ff12). Yalnızca `files` listesiyle temizle.
- **Zamanlanmış görevler:** AdaVira günlük (09:40, ücretli hesap, doğal belgesel fotoğraf), Stetosgop gün aşırı (07:43, Default ortamında), **AdaCasa gün aşırı (11:17, yeni, `trig_01FkKdijSnPwgi3ap5gKAsqQ`)**. Üçü de uzun kuyruk anahtar kelime + doğal fotoğraf kuralıyla.
- **AdaCasa blog:** kategoriler 3–7 (Bölge, Satın Alma, Kiralama, Tapu ve Yasal, Yatırım ve Projeler). Yazılar 27, 29, 31 genişletildi (artık demo değil, unseed silmez), 47 yeni. Yazı içi görseller ilgili arama sayfasına bağlı.
- **1.1.6 (SEO):** bkz. kurulum.md. Yerelde: site haritası, robots, JSON-LD doğrulandı; mobile-audit 156 görünüm temiz; testler 29/30/25/28.
- **Plesk erişimi (22.11):** REST API `https://mail.stetosgop.com/api/v2/` (port **443**; 8443 bu ortamın proxy'sinden geçmez). Yönetici girişi ağ sırrı olarak kayıtlı (Basic). `cli/{komut}/call` ile Plesk komutları çalışır. Okuma serbest; değişiklikler kullanıcı açıkça onaylarsa yapılabiliyor (cron ekleme yapıldı). Sunucuya dosya koymayı gerektiren işler (PHP ayarı) kullanıcıda. Durum: günlük 04:00 yedek (artımlı, haftalık tam, 3 kopya, sunucu diski); wp-cron görevleri adacasa (71) ve adavira (72) 5 dk, stetosgop 15 dk; PHP: adacasa 128M/128M, adavira ve stetosgop 64M/64M, bellek 256M.
- Kullanıcının isteği: **her şeyi Claude yapar**; kullanıcıdan yalnızca Claude'un yapamadığı işler istenir (zip kurulumu, panel ya da hesap erişimi gerektirenler). **Önbellek temizleme Claude'da:** zip kurulduktan sonra üç zone için `purge_everything`, yazı yayınlarında `files`; AdaVira Cache Enabler için yayındaki bir yazıyı yeniden kaydet. Kararları makul varsayımla ver ve söyle.

---

## 1. Durum özeti

| Parça | Daldaki sürüm | Kullanıcıya verilen | Canlıda kurulu (son kontrol) |
|---|---|---|---|
| Eklenti `adacasa-core` | **1.1.6** | 1.1.6 (22.11) | 1.1.5 (22.11 doğrulandı) |
| Tema `adacasa-theme` | **1.1.6** | 1.1.6 (22.11) | 1.1.5 (22.11 doğrulandı) |
| Chrome eklentisi "AdaCasa İlan Aktarıcı" | 0.4.1 | 0.4.1 | — |
| `EC_DB_VERSION` | 11 | | |

**Kullanıcıdan beklenen ilk geri bildirim:** 1.1.5'i kurdu mu, **Emlak → Ayarlar → Şirket ve yasal bilgiler** bölümünü doldurdu mu? Kurduysa canlıda kontrol edilecekler:
- `/tr/yasal/gizlilik/` ve EN/RU sürümleri,
- çerez kutusu,
- eski `/hakkimizda/` adresinin 301 ile yönlenmesi,
- alt bilgi bağlantıları.

**Canlı site:** https://adacasa.com
- Plesk VPS üzerinde WordPress (PHP 8.4), Cloudflare arkasında.
- Sayfa önbelleği açık ve çalışıyor (HIT doğrulandı). Cloudflare önbellek kuralı kurulu.
- Kalıcı bağlantılar "Yazı adı".
- Demo verisi yüklü: 62 ilan, 6 firma, 6 proje. Fotoğraflar yapay zekâ ile üretildi; 4 ilanda 360° var. **Canlıya geçmeden önce silinmeli.**
- Şehir kartı fotoğrafları medya no 18–23, Wikimedia Commons'tan, atıflı.
- Giriş görseli: tema içindeki Girne limanı fotoğrafı (A.Savin, FAL lisansı).

## 2. Kullanıcıyla çalışma şekli

- **Dil:** Türkçe konuşulur. Kullanıcı "bekle" derse hiçbir şey yapılmaz.
- **İş akışı:** büyük işlerden önce plan yazılır. Kullanıcı "hepsini yap" derse makul varsayımlarla ilerlenir ve varsayımlar söylenir.
- **Teslim:**
  - Zip'ler **sürüm numaralı** gönderilir (`SendUserFile`); kurulum sırası her seferinde hatırlatılır.
  - Değişmeyen parça tekrar gönderilmez; sadece tema değiştiyse sadece tema verilir.
  - Kurulum: Eklentiler/Temalar → Yükle → **Mevcut sürümü değiştir** → **Emlak → Hız → Cloudflare önbelleğini temizle**.
- **Kullanıcının beklentileri:**
  - Ekran görüntüsü gönderir ve hep telefondan bakar. **Her değişiklikten sonra telefon kontrolü şart:** `node tests/mobile-audit.js http://localhost:8080 admin admin <klasör>`.
  - Cloudflare paneli için adım adım, ekrandaki konuma göre tarif ister.

## 3. Bu ortam (bulut oturumu) ve erişimler

- **İzinli adresler:** adacasa.com ve www.adacasa.com. Ayrıca commons.wikimedia.org ve upload.wikimedia.org izinli; thumb.wikimedia.org izinli değil, onun yerine upload.wikimedia.org kullanılır.
- **Ağ sırrı olarak kayıtlı (proxy kendisi ekler):**
  - **adacasa.com:** WP uygulama parolası (Basic).
    - REST ile okuma ve yazma çalışıyor: medya yükleme, ayarlar, `/emlak/v1/*`.
    - Silme ve çöpe taşıma işlemleri otomatik izin denetimine takıldı; silme işlerini kullanıcı yapar.
    - wp-admin sayfalarına giriş yapılamaz.
    - `/wp/v2/users/me` Plesk tarafından 403 ile engelleniyor; yetkiyi `/wp/v2/settings` ile kontrol et.
  - **api.cloudflare.com:** hesap b30a1eee1d8de12b206e7a49257b419a. Workers AI ile görsel üretimi çalışıyor (`@cf/leonardo/lucid-origin`, `flux-1-schnell`).
    - Önbellek temizleme ve zone okuma yetkisi **yok**; önbelleği kullanıcı panelden temizler.
    - Zone ID: a920477193d181a481d0e36b7a51955d.
- **Erişilemeyenler:** api.telegram.org, 101evler, hangiev (Cloudflare bot koruması), Overpass, harita karoları, Unsplash.
- **Otomatik izin denetimine takılanlar:** `/usr/local/bin` içine phar indirmek, `0.0.0.0` üzerinde sunucu açmak, canlıda DELETE istekleri.

### Yerel test ortamı (oturum değişince yeniden kurulur)
```bash
apt-get update && apt-get install -y mariadb-server php8.3-fpm php8.3-mysql php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip
```
```bash
service mariadb start && mysql -e "CREATE DATABASE IF NOT EXISTS adaev; CREATE USER IF NOT EXISTS 'adaev'@'localhost' IDENTIFIED BY 'adaev'; GRANT ALL ON adaev.* TO 'adaev'@'localhost';"
```
```bash
mkdir -p /tmp/wpdev && cd /tmp/wpdev && composer require -q johnpbloch/wordpress-core:^6 wp-cli/wp-cli-bundle && cp -r vendor/johnpbloch/wordpress-core site
```
- **wp-cli:** `/tmp/wpdev/vendor/bin/wp --allow-root --path=/tmp/wpdev/site`. Kısayol için `/tmp/wpdev/w` sarmalayıcısı yazılabilir.
- **Kurulum:** `config create` → `core install` (admin/admin, başlık AdaCasa) → `rewrite structure '/%postname%/'`. Eklenti ve temayı depodan sembolik link ile bağla, etkinleştir, `wp emlak seed` çalıştır.
- **Sunucu:** `php -S 127.0.0.1:8080 -t site`. Arka plan görevi olarak çalıştır, timeout 7200000. İki saatte kapanır, gerekirse yeniden başlat.
- **Yerelde uygulama parolası:** HTTP üzerinde sadece `wp config set WP_ENVIRONMENT_TYPE local` ile çalışır.
- **Testler:** her biri taze demo verisiyle çalıştırılır. Sıfırlama: `UPDATE wp_ec_listings SET source='demo' WHERE source<>'demo'` → `emlak unseed` → `emlak seed` → `transient delete --all`.
  - `tests/backend-test.php` 29 · `social-test.php` 30 · `promo-test.php` 25 · `team-test.php` 28 → **hepsi geçiyor**.
  - Dikkat: yönetici ofis profili (wp_user_id=1) varken ilk üyeyi seçen testler bozulur; önce o kaydı sil.
- **Tarayıcı testleri:** `tests/mobile-audit.js` (36+ sayfa, 360/390 px, açık/koyu), `tests/shot.js`, `tests/interact.js`.

## 4. Mimari (kısa)

- **Eklenti `adacasa-core`:** iş mantığı, kendi tabloları (`wp_ec_*`), TR/EN/RU adresler, `/api/v1` (= `emlak/v1`), yönetim ekranları.
  - İç önekler `ec_` (eklenti) ve `ae_` (tema) **değişmez**.
  - Eski `emlak-core` etkinse kendini yüklemez, kullanıcıyı uyarır.
- **Tema `adacasa-theme`:** sadece görünüm. Metin çevirileri `inc/i18n*.php` (`ae_t`).
- **Adresler:** `ec_sections()` (data.php), kurallar routing.php'de. Tema şablon eşlemesi `functions.php` → `template_include`.
- **Chrome eklentisi:** `extension/`. Seçiciler sunucudan JSON olarak gelir; 101evler/hangiev seçicileri hâlâ tahmini.

### Özellik haritası
| Özellik | Dosyalar |
|---|---|
| İlanlar, arama, sıralama (öne çıkan → `GREATEST(bumped_at, published_at)`) | includes/listings.php |
| Fotoğraf, 360° (Pannellum), video (bağlantı ya da yükleme; ilan ve proje) | photos.php, panos.php, videos.php |
| Galeri penceresi (101evler tarzı: mozaik, Video, 360, Konum) | tema inc/helpers-detail.php `ae_gallery_view`, assets/js/detail.js |
| Paketler ve kota | packages.php, admin-packages.php |
| Vitrin: featured, home, cat, urgent, bump; paket kredileri | promos.php (`ec_promo_*`), ana sayfa "Vitrin", aramada kategori vitrini |
| Siparişler: havale (dekont, `uploads/ec-ozel`) ve kart (NestPay/Cardplus 3D Pay Hosting, hash ver3) | orders.php; araştırma: docs/odeme-kktc.md |
| Ofis ekibi: davet/kabul/çıkar/ayrıl | team.php; `agents.parent_id`, `team_invite` |
| Ekipte paket paylaşımı: varsayılan kapalı; danışman başına aç/kapa, aylık ilan ve vitrin sınırı; önce kendi paketi | team.php `ec_team_left`, `ec_team_month_usage`; packages.php, promos.php |
| Yönetici ofis profili | members.php `ec_admin_self_profile` |
| Telegram botu ve Claude ile ilan hazırlama | telegram.php, ai-text.php |
| Sosyal medya (Meta) | social.php |
| Kopya ilan denetimi | duplicates.php |
| Hız ve Cloudflare önbelleği temizleme | speed.php, docs/hiz.md |
| PWA (manifest ve service worker `/?ec_pwa=…`; telefonda 15 sn sonra yükleme kutusu) | tema inc/pwa.php, assets/js/main.js |
| Yasal sayfalar TR/EN/RU (`/tr/yasal/…`, `/en/legal/…`, `/ru/pravo/…`); şirket bilgisi `ec_company` | legal.php, legal-texts.php, tema templates/legal.php |
| Çerez onayı (GA4 ve Pixel sadece izinle, `ac_consent` çerezi); GSC ve Bing doğrulama | tracking.php |
| Giriş görseli, şehir kartları ve fotoğraf atfı | `ec_hero_photo`, `ec_city_photos` (REST ayarı); atıf medya açıklamasındaki "Foto: …" satırından |
| Turnstile (kayıt, giriş, mesaj formu) | members.php; **anahtarlar girilmedi** |

## 5. Bekleyen işler

### Kullanıcıda (anahtarlar ve ayarlar)
1. 1.1.5'i kurmak, şirket ve yasal bilgileri doldurmak, yasal metinleri avukata kontrol ettirmek.
2. **Turnstile anahtarları:** tarif verildi (Cloudflare → Turnstile → Add widget → Managed).
3. **Telegram:** BotFather token'ı ve chat id, ardından webhook kurulumu. Claude API anahtarı. Test, kullanıcının telefonundan yapılacak.
4. **Ödeme:** havale için IBAN girilecek. Kart için KKTC bankasından NestPay/Cardplus 3D Pay Hosting bilgileri istenecek (docs/odeme-kktc.md). Gerçek bankayla hiç denenmedi.
5. **Vitrin paketleri:** fiyatlar örnek (5–60 €); kullanıcı belirleyecek.
6. **Diğer anahtarlar ve kodlar:** Meta App ID ve Secret; GA4 ve Search Console kodları.
7. **Sunucu:** 5 dakikalık wp-cron görevi; PHP yükleme sınırı 128M (video için); OPcache; yedekleme; UptimeRobot.
8. Saat dilimi İstanbul; sloganın başındaki boşluk; Sample Page ve örnek yorum silinecek (kullanıcıya söylendi).
9. Canlıya geçerken demo verisi silinecek. **Emlak → Canlıya çıkış** sayfası eksikleri listeler.

### Kod tarafında açık kalanlar ve öneriler
- **101evler/hangiev seçicileri:** giriş yapılmış "ilan ver" sayfalarının kaynak kodu gelince kesinleştirilecek.
- Ofis için ekip büyüklüğü sınırı yok; istenirse pakete "en fazla N danışman" eklenir.
- Yasal sayfalar WP sitemap'inde yok; özel sitemap sağlayıcısı eklenebilir.
- Chrome Web Store yayını; ileride Next.js + Supabase geçişi (docs/veri-modeli.md).
- Kullanıcı kendi şehir ve giriş fotoğraflarını yüklerse atıf satırı kendiliğinden kalkar.

### Bilerek yapılmayanlar
- Gerçek emlak fotoğrafları (telif) ve yapay zekâ ile "gerçek yer" görseli.
- Facebook gruplarına paylaşım.
- iyzico, PayTR ve Stripe: KKTC şirketini kabul etmiyorlar.

## 6. Belgeler

| Belge | İçerik |
|---|---|
| `docs/kurulum.md` | Kurulum; en üstte sürüm notları (1.0.1 → 1.1.5) |
| `docs/api.md` | REST API (vitrin, sipariş, ekip ve video uçları dâhil) |
| `docs/hiz.md` | Hız ve Cloudflare önbellek kuralı |
| `docs/odeme-kktc.md` | KKTC ödeme seçenekleri raporu (kaynaklı) |
| `docs/sosyal-medya-kurulum.md`, `sosyal-medya-rehberi.html` | Sosyal medya kurulumu |
| `docs/neden-biz.html` ve `.pdf` | Emlakçı rehberi. Yayımlanmış hali: https://claude.ai/artifact/YL2sUFCN2h12Nt6Te49o2d. Paket fiyatları sabit yazılı; vitrin paketleri ve ekip özelliği henüz rehberde yok, güncellenebilir. |
| `docs/marka/` | Logo PNG ve WebP, `adacasa-mark.svg` |
