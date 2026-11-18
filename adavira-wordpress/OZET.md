# AdaVira — Proje Özeti (18 Kasım 2026)

**Alan adı:** adavira.com  **Marka:** AdaVira Dijital Ajans  **Konum:** Ozanköy / Girne - KKTC
**İletişim:** bilgi@adavira.com · +90 548 857 01 07
**Altyapı:** WordPress 6.9 + özel **AdaVira teması 1.2.3** · AlmaLinux 8 + Plesk · nginx + Apache · PHP 8.4
**Durum:** Yayında ✅

---

## 1. Ne yaptık? (özet)

| Alan | Yapılan |
|---|---|
| **Geçiş** | Özel PHP site → WordPress. Eski site tamamen yedeklendi, WordPress sıfırdan kuruldu. |
| **Tema** | AdaVira için özel tema yazıldı. Lacivert/altın, serif başlıklar, mobil öncelikli, ajans görünümü. |
| **İçerik** | Eski 6 yazı aynı adreslerle taşındı. Hakkımızda yeniden yazıldı. 3 yeni blog yazısı eklendi. Gizlilik Politikası eklendi. |
| **Marka** | Yeni logo (saydamlaştırıldı), site simgesi, paylaşım görseli, uygulama simgesi. |
| **SEO** | Meta etiketleri, canonical, Open Graph, schema.org, site haritaları, Google News haritası, robots.txt, 301 yönlendirmeleri. |
| **Haber altyapısı** | Konular, yazar sayfaları, video (YouTube), röportaj, manşet, son haberler şeridi. |
| **Form ve e-posta** | Telefonlu iletişim formu, bot koruması, SMTP (mail.adavira.com:465/ssl). Test edildi ✅ |
| **Hız** | Cache Enabler sayfa önbelleği (gzip + HTML küçültme). Test edildi ✅ |
| **Güvenlik** | Kullanıcı adı sızması kapatıldı. www → adavira.com yönlendirmesi. wp-config güvenlik anahtarları yenilendi. |
| **Sosyal medya** | n8n ile yeni yazılar Facebook sayfasına ve Instagram'a otomatik paylaşılıyor. Test edildi ✅ |

---

## 2. Site yapısı ve adresler

| Bölüm | Adres | Not |
|---|---|---|
| Ana sayfa | `/` | Son haberler şeridi, Gündem (manşet), hizmetler, sektör sekmeleri, süreç, blog |
| Hizmetler | `/hizmetler`, `/hizmet/{slug}` | 6 hizmet (tema içinde tanımlı) |
| Sektörler | `/sektorler`, `/sektor/{slug}` | 8 sektör (tema içinde tanımlı) |
| Blog | `/blog`, `/blog/{slug}` | 7 yazı |
| Bizden Haberler | `/bizden-haberler`, `/bizden-haberler/{slug}` | 2 haber · konular: Sektör Haberleri, Röportaj, Video, Ajans Duyuruları |
| Konu sayfaları | `/konu/{slug}` | |
| Yazar sayfaları | `/yazar/{ad-soyad}` | Kullanıcı adını göstermez |
| Hakkımızda | `/hakkimizda` | |
| İletişim | `/iletisim` | Önbelleğe alınmaz |
| Gizlilik Politikası | `/gizlilik-politikasi` | Meta uygulaması için de kullanılıyor |
| Site haritası | `/sitemap.xml` | Search Console'a gönderildi |
| Google News haritası | `/news-sitemap.xml` | Son 2 günün yazıları |
| RSS | `/feed` | Blog + haberler, Instagram için JPEG paylaşım görseli ile |

**Yayındaki yazılar**
- Blog: Oto galeriler dijital vitrin rehberi · Klinik/diş hekimi dijital rehber · Milyonluk villalar · Girne Google SEO rehberi · **Emlak yapay zekâ asistanı (yeni)** · **Girne otelleri doğrudan rezervasyon (yeni)** · **Güzellik merkezleri Instagram + Google Haritalar (yeni)**
- Haber: Dijitalde yeni dönem · AdaVira yayında

---

## 3. Tema sürüm geçmişi

| Sürüm | Değişiklik |
|---|---|
| 1.0.0 | İlk tema: tasarım, sayfalar, SEO, iletişim formu, eski siteden aktarım aracı |
| 1.0.1 | Eski yazı adresleri için yönlendirme, kullanıcı adı koruması |
| 1.0.2 | AdaVira Ayarları REST API'de (şifreler hariç) |
| 1.1.0 | Haber/medya: yazar sayfaları, video, röportaj, Google News haritası, manşet, paylaşım butonları |
| 1.1.1 | www → adavira.com 301, iletişim sayfası önbellek dışı |
| 1.2.0 | Yeni logo, site simgesi, paylaşım görseli |
| 1.2.1 | Jetpack Social desteği (sonra kaldırıldı), çift Open Graph engeli |
| 1.2.2 | Instagram için JPEG paylaşım görseli + RSS enclosure |
| **1.2.3** | **Footer ve site haritasında Gizlilik Politikası** (güncel) |

Yeni sürüm üretmek: `./paketle.sh 1.2.4` → `adavira-tema-1.2.4.zip`.
Yükleme: Görünüm → Temalar → Yeni ekle → Tema yükle → **"Yüklenenle değiştir"**.

---

## 4. WordPress ayarları (şifre içermez)

- **Site adı:** AdaVira · **Slogan:** Girne Dijital Ajans · **Saat dilimi:** Europe/Istanbul · **Tarih:** d.m.Y
- **Kalıcı bağlantılar:** `/%postname%` · **Yorumlar:** kapalı
- **AdaVira Ayarları:** WhatsApp 905488570107 · adres Ozanköy / Girne - KKTC · bilgi@adavira.com · SMTP mail.adavira.com:465 ssl · Facebook/Instagram: adavira.social
- **Eklentiler:** Cache Enabler (etkin). Akismet ve Hello Dolly pasif, silinebilir.
- **Cache Enabler:** 24 saat süre · yayınlanınca tüm önbelleği temizle · gzip · HTML küçültme · `/iletisim` hariç
- **wp-config.php:** `WP_CACHE = true`

---

## 5. Sosyal medya otomasyonu (n8n)

- **n8n:** n8n.stetosgop.com → akış: *"AdaVira – Yeni yazıyı Facebook ve Instagram'da paylaş (v2)"*
- **Çalışma şekli:** Her 15 dakikada `/feed` okunur. Yeni yazı varsa:
  - Facebook sayfasında başlık, özet ve bağlantı paylaşılır.
  - Instagram'da JPEG kapak, özet ve hashtag'lerle gönderi yayınlanır (60 sn bekleme, otomatik yeniden deneme).
- **Meta:** Uygulama "Adavira" (Geliştirme modu) · Graph API v27.0
- **Facebook Sayfa ID:** 927984147053985 · **Instagram işletme ID:** 17841477921864269 (@adavira.social)
- **İlk otomatik paylaşım:** Güzellik merkezleri yazısı, Instagram'da 18.11.2026 18:18 ✅
- Sayfa anahtarı yalnızca n8n credential'ında ("Query Auth", name `access_token`) duruyor.

---

## 6. Günlük kullanım

- **Blog yazısı:** Blog → Yeni yazı. Başlık, metin, **Özet** (Google açıklaması olur, 155 karakter), **Öne çıkan görsel** (en az 1200 px, alt metin doldurulmalı).
- **Haber:** Bizden Haberler → Yeni haber → sağda **Konular** seçin.
- **Video haber:** Sağdaki "Haber / Röportaj / Video" kutusuna YouTube adresini yapıştırın.
- **Röportaj:** Aynı kutuya konuk adını ve unvanını yazın.
- **Manşet:** "Ana sayfada manşette göster" kutusunu işaretleyin.
- **Yazarlar:** Kullanıcılar → Yeni ekle → rol **Yazar** → profilde Görünen ad, Biyografi, Unvan, Fotoğraf.
- Yayınlanan her yazı önbelleği temizler, site haritalarına girer ve 15 dk içinde Facebook ile Instagram'da paylaşılır.

---

## 7. Açık işler / yapılacaklar

- [ ] **Mail-tester puanı 7,9:** DKIM'i açın (Plesk → Mail Settings) ve DMARC TXT kaydı ekleyin (`_dmarc`: `v=DMARC1; p=none; rua=mailto:bilgi@adavira.com`), sonra testi tekrarlayın.
- [ ] **Veritabanı şifresi:** Plesk'te yenileyip wp-config'e yazın (sohbette paylaşılmıştı).
- [ ] **Bu çalışma ortamındaki Meta anahtarı:** claude.ai ortam ayarları → ağ sırları → `graph.facebook.com` kaydını silin. n8n'deki sayfa anahtarı ayrı, o çalışmaya devam eder.
- [ ] **WordPress uygulama parolası "Claude":** İş bittiğinde Kullanıcılar → Profil → Uygulama Parolaları'ndan iptal edilebilir.
- [ ] **Search Console:** Site haritası durumunu kontrol edin, yeni yazılar için "Dizine eklenmesini iste".
- [ ] Görünen ad, biyografi, yazar fotoğrafı (Kullanıcılar → Profil).
- [ ] Emlak ve otel yazılarını Facebook/Instagram'da bir kez elle paylaşın (otomasyondan önce yayınlandılar).
- [ ] Statik dosya tarayıcı önbelleği için nginx `expires` yönergesi (isteğe bağlı).
- [ ] Açık zemin için ikinci logo sürümü ("Vira" lacivert), isteğe bağlı.
- [ ] Plesk'te düzenli otomatik yedekleme (Backup Manager / WP Toolkit).

---

## 9. Görsel üretimi (Cloudflare Workers AI)

- **Servis:** Cloudflare Workers AI, ücretsiz plan (günlük 10.000 neuron). Hesap ID: `b30a1eee1d8de12b206e7a49257b419a`
- **Model:** FLUX.2 [dev], doğrudan 1600×896 üretim. Yedek model: FLUX.1 [schnell] (Apache 2.0).
- **Araç:** `araclar/gorsel.py`. Sahne tarifinden görsel üretir, 1600×900 JPEG yapar, başlık, alt metin ve açıklamalarla
  adavira.com'a yükler, istenirse yazının kapağı yapar. Haberlerde "(Temsili görsel)" notu ekler.
- **Anahtar:** Cloudflare API anahtarı yalnızca çalışma ortamının ağ sırlarında durur, dosyalarda yoktur.
- **Günlük hak dolarsa:** O gün yeni görsel üretilmez, ertesi gün devam edilir. Ücret kesilmez.

---

## 8. Bu paketin içeriği

```
AdaVira-yedek-2026-11-18/
├── OZET.md                     ← bu dosya
├── tema/                       adavira-tema-1.2.3.zip, KURULUM.md, paketle.sh
├── n8n/                        adavira-sosyal-paylasim.json (v2), KURULUM-sosyal-paylasim.md
├── marka/                      saydam logo ve simge, 512/1024 simgeler, 1200x630 paylaşım görseli
├── icerik/                     3 yeni blog yazısının metinleri (JSON) ve kapak fotoğrafları
├── site-yedegi/                18.11.2026 canlı site yedeği:
│   ├── site-icerik.json          tüm yazılar, sayfalar, konular, görsel alt metinleri, ayarlar (şifresiz)
│   ├── *.html                    her yazı/sayfanın okunabilir kopyası
│   └── medya/                    sitedeki 12 görselin orijinalleri
└── eski-site/
    ├── icerik-yedegi/            eski PHP sitenin yazıları (JSON, SQL, HTML, görseller)
    └── php-kodu/                 eski PHP sitenin kaynak kodu (.env şifre dosyası çıkarıldı)
```

> Bu paket içerik yedeğidir. Veritabanının tam yedeği için Plesk → **WordPress → AdaVira → Back up** ya da
> **Backup Manager** kullanın.

**Kaynak kod:** GitHub `nearanbe/pwa` deposu, `claude/vigilant-babbage-iwz9wu` dalı → `adavira-wordpress/`
