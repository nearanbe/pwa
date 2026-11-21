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
- **Kod:** `wordpress/wp-content/plugins/emlak-core/` (iş mantığı) ve `wordpress/wp-content/themes/adaev/` (görünüm).
- **Paketleme:** `bash scripts/paketle.sh` komutu `dist/emlak-core.zip` ve `dist/adaev.zip` dosyalarını üretir. Kullanıcı bunları yönetim panelinden "mevcut sürümü değiştir" seçeneğiyle yükler.
- **Dal:** `claude/jolly-carson-l927uv`.

## Sürüm durumu (0.3 + kur güncellemesi)

Bitmiş olanlar:

- 3 dil (TR/EN/RU): `/tr/…`, `/en/…`, `/ru/…`
- Slider'lı ana sayfa, arama, ilan ve proje detayı, projeler, fiyat endeksi, blog, favoriler
- Ofis vitrini: `/tr/ofis/{ad}/`, ayarla açılırsa `ofis.alanadi.com`
- **Üyelik:**
  - 4 tür: ofis, inşaat firması, bireysel danışman, mülk sahibi
  - E-posta doğrulama, Turnstile (anahtar girilince devreye girer), giriş kilidi, kişisel veri onayı
  - Kayıtta "Telegram ile otomatik ilan ister misiniz?" sorusu
- **Onay:**
  - Ofis ve inşaat firması hesabı bir kez onaylanır, ilanları anında yayınlanır.
  - Bireysel danışman ve mülk sahibinin her ilanı onaya düşer.
  - Telefona uygun onay paneli: `/tr/onay/`. Bildirim e-posta ve Telegram ile.
- **Panelim:**
  - Adım adım ilan formu: harita (Leaflet temaya gömülü), sürükle-bırak fotoğraf, 25 özellik, KDV/trafo, anlık kur karşılığı, bölge karşılaştırması
  - Proje formu: daire tipleri, ödeme planı hesaplayıcı, ilerleme, galeri, kat planı, PDF broşür
  - Profil, logo, sosyal hesaplar, Telegram sekmesi
- **Tapu türü:**
  - Satılıkta yayın için zorunlu, taslakta değil.
  - 6 tür ve açıklamaları: Türk koçanı, eşdeğer, tahsis, vakıf, projeden, diğer.
  - Detayda her zaman görünür, aramada filtre olarak var.
- **Kurlar:**
  - Kaynak sırası: **TCMB** `today.xml` → KKTC Merkez Bankası → ECB (api.frankfurter.dev) → open.er-api.com
  - Kur türü ayarı: ortalama, alış ya da satış
  - Saatlik kontrol (WP-Cron), kur tarihi saklanır ve gösterilir
- **Fiyatlar:**
  - Fiyatın yanında parantez içinde ikinci para birimi (₺ kuruşlu)
  - Tıklayınca açılan **döviz çevirici**: `assets/js/converter.js`
  - Türkçe sayı okuma: "250.000" = 250 bin
- Site ve emlakçı sosyal medya ikonları
- Cloudflare Workers AI ile **temsili** görsel üretimi (Emlak → Görsel üret). İlan fotoğrafı olarak kullanılmaz.
- Demo verisi panelden eklenir ve silinir: 62 ilan, 6 ofis, 6 proje, 3 blog yazısı, 12 aylık demo endeks.

## Bekleyen kararlar ve işler

1. **Gün içi (piyasa) kuru:** Kullanıcı Türkiye şartlarında günlük TCMB kurunu yetersiz buluyor. Önerilen, kullanıcı onayı bekleyen yapı:
   - Twelve Data (ücretsiz anahtarla) 5 dakikada bir çeker, anahtar yoksa Yahoo Finance `GBPTRY=X` yedek olur.
   - Her değer TCMB ile karşılaştırılır, %5'ten fazla sapma veri hatası sayılır ve reddedilir.
   - Plesk'te 5 dakikalık zamanlanmış görev kurulur.
   - Ayarlara sıklık ve kaynak seçimi eklenir.
   - Sitede "Piyasa kuru · saat" yazar, çeviricide ayrıca "TCMB resmi" gösterilir.
2. **101evler karşılaştırması** (kullanıcının ekran görüntülerinden). Eksiklerimiz aşağıda. Kullanıcı "eksik ne varsa yap ve ekle" dedi, gerekirse sayfa kaynak kodlarını da verecek.
3. **0.4: Telegram botu.** Tek merkezi bot. Eşleştirme Telegram kullanıcı numarası ve tek kullanımlık 10 dakikalık kodla yapılır (`telegram_link_code` alanı ve `/me/telegram-code` ucu hazır).
   - Botun mesajları alacağı adres (webhook)
   - Arka arkaya gelen fotoğraf ve metinleri tek ilanda gruplama
   - Claude API ile metinden alanları çıkarma ve TR/EN/RU çeviri
   - "✅ Yayınla / ✏️ Düzelt" önizlemesi
   - Yöneticiye Telegram'dan "Onayla/Reddet" düğmeleri
   - Gerekenler: Claude API anahtarı ve BotFather'dan bot anahtarı
4. **Chrome eklentisi:** 101evler ve hangiev modülleri. Kullanıcının tarayıcısında çalışacak, son "Yayınla" tıkı insanda kalacak. Captcha aşılmayacak. SMS kodu Telegram'dan istenecek.
5. **Canlıya çıkmadan önce:**
   - Turnstile anahtarları
   - Plesk'te `wp-cron` zamanlanmış görevi
   - Demo verisini silmek
   - SPF ve DKIM kayıtları
   - Alt alan adı için Cloudflare'de `*` kaydı ve Origin Certificate
   - Tarifleri `docs/kurulum.md` dosyasında

## 101evler'e göre eksiklerimiz (ekran görüntülerinden)

**İlan detayı**
- Tam ekran fotoğraf galerisi: yanda küçük resim ızgarası, sayaç, slayt gösterisi
- Fiyatın altında başlık hizasında simgeli bilgi şeridi: emlak tipi, oda, banyo, m²
- **"Haritada gör" ve konum haritası.** `lat`/`lng` kaydediliyor ama detayda harita yok.
- Kaydırınca üstte sabit kalan başlık (ofis logosu, başlık, fiyat, telefon) ve bölüm menüsü: hızlı bakış, detaylar, konum, fiyat trendi, özellikler
- Gruplanmış "Konut detayları" tablosu: kat sayısı, bulunduğu kat, bina yaşı, site içinde, havuz
- Özelliklerin **dış / iç / konum** diye gruplanması. Şu an düz bir liste.
- **Yakındaki önemli yerler:** sağlık, eğitim, banka, yemek, market, turizm, mesafeleriyle. OpenStreetMap Overpass ile ücretsiz yapılabilir, sonuç önbelleğe alınmalı.
- İlanın bölgesi için fiyat trendi grafiği. Endeks verisi var, ilan sayfasına grafik konmalı.
- Paylaş düğmeleri: Facebook, X, e-posta, LinkedIn, Pinterest, WhatsApp, Viber
- **"Mesaj gönder"** iletişim formu: talep (lead) olarak üyenin paneline ve e-postasına düşer
- "Hatalı ilan bildir", "Bana özel not" (favoriyle birlikte, tarayıcıda saklanır), "Yazdır" (yazdırma CSS'i)
- Ofis kartında üyelik yılı rozeti ("3. yıl"), "Diğer ilanlarını incele"

**Proje detayı**
- Bilgi kutucukları: proje tipi, inşaat durumu (tamamlandı/devam ediyor), toplam konut, teslim tarihi, **proje alanı (m²)**. Proje alanı ve inşaat durumu alanları yok.
- Proje sayfası 0.3'teki yeni alanları göstermiyor. Eklenmesi gerekenler: daire tipleri kartları (kat planı görseliyle), sosyal alanlar, galeri, kat planları, broşür, ilerleme çubuğu, ödeme planı ve taksit hesabı, harita, video.
- Sabit başlık ve bölüm menüsü, "Firmanın diğer projeleri" (şu an genel liste), mesaj gönder, paylaş.

**Ana sayfa ve genel**
- Vitrin ilan kartlarında ofis logosu ve adı
- Şehir kartları: fotoğraf ve "x+ kiralık / y+ satılık" sayıları (şu an sadece metin linkler var)
- **Firmalar dizini** sayfası (ofis listesi, aranabilir)
- Arama kutusunda para birimi seçimi (şu an üst bardaki genel seçim)

**Bilerek yapılmayacaklar:**
- Demografik bilgiler (eğitim, yaş, meslek grafikleri). Güvenilir bir kaynak yok, uydurma veri gösterilmeyecek. Ancak gerçek bir kaynak bulunursa eklenir.

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
4. `php -S 0.0.0.0:8080 -t site` ile sunucuyu başlat.
5. Demo veri için: `wp emlak seed`.

Notlar:
- wordpress.org bu ortamdan engelli, WordPress çekirdeği Composer ile iner.
- Tarayıcı testleri Playwright ile yapılır.
- 101evler ve hangiev Cloudflare bot korumasının arkasında, doğrudan açılamaz. Karşılaştırma kullanıcının ekran görüntüleri ve kaynak kodlarıyla yapılır.

## Kullanıcıyla çalışma şekli

- Türkçe konuşulur.
- Kullanıcı "bekle" derse hiçbir işlem yapılmaz.
- Büyük işlerden önce plan ve sorular yazılır, onay beklenir.
- Her teslimde zip paketleri gönderilir ve kurulum sırası hatırlatılır.
