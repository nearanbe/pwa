# AdaVira — Devir Özeti (22 Kasım 2026)

Bu belge, adavira.com yönetimini devralacak kişi ya da yeni bir Claude oturumu içindir. Ayrıntılı proje
özeti `OZET.md`, kurulum `KURULUM.md`, sosyal medya kurulumu `n8n/KURULUM-sosyal-paylasim.md` dosyalarında.

---

## 1. Şu anki durum (22.11.2026 05:30 UTC)

| Alan | Durum |
|---|---|
| Site | adavira.com yayında · WordPress 6.9 · **AdaVira teması 1.2.5** (canlıda doğrulandı) |
| İçerik | 10 blog yazısı yayında + **2 kapaksız taslak** (ID 64, 71) · 2 haber · Hakkımızda, İletişim, Gizlilik Politikası |
| Manşet | Her zaman en son yayınlanan yazı (şu an: "KKTC'de Oto Galeri Reklamı…", 20.11) |
| Günlük yazı görevi | Etkin · her gün 09:40 (İstanbul) · sonraki çalışma bugün 06:40 UTC |
| Sosyal medya | n8n → Facebook + Instagram otomatik paylaşım çalışıyor |
| E-posta | İletişim formu SMTP ile çalışıyor (mail.adavira.com:465/ssl) · mail-tester 7,9 |
| Önbellek | Cache Enabler etkin |
| Eklentiler | Cache Enabler (etkin) · **WP Mail SMTP (pasif)**: site sahibi kurmuş olabilir, tema SMTP'yi zaten yapıyor; etkinleştirilirse çakışır |

---

## 2. ⚠️ Acil / açık işler (öncelik sırasıyla)

1. **Cloudflare günlük kotası doluyor → yazılar taslakta kalıyor.**
   - 19.11 sabah (ID 64 "oto-galeri-arac-tanitim-videosu-rehberi") ve 21.11 (ID 71 "girne-emlak-ofisi-yerel-seo")
     görsel üretilemediği için **kapaksız taslak** kaldı. 21.11 raporu: FLUX.2 [dev] 429, flux-1-schnell 4006
     (günlük ücretsiz 10.000 neuron dolmuş). Kota 00:00 UTC'de sıfırlanır.
   - Önceki tahmin (görsel başına 50–100 neuron) **yanlış çıktı**; FLUX.2 [dev] 1600×896, 28 adım çok daha
     pahalı olmalı. Cloudflare panelinde AI → Workers AI kullanım grafiğinden gerçek tüketim kontrol edilmeli.
   - Öneri: (a) görev talimatında FLUX.2 adımını ~20'ye ve yeniden deneme sayısını azaltmak ya da önce
     `flux-2-klein-4b` / `flux-1-schnell` denemek; (b) iki taslak (64, 71) için kota sıfırlanınca görsel üretip
     yayınlamak (21.11 oturumunda sahne tarifleri hazırdı, ama o oturumun /tmp dosyaları kalıcı değil).
2. **Meta anahtarı ortamda kaldı:** claude.ai ortam ayarları → ağ sırları → `graph.facebook.com` kaydı silinmeli
   (artık gerekmiyor; n8n'deki sayfa anahtarı ayrı).
3. **Veritabanı şifresi** sohbette paylaşıldı → Plesk'te değiştirilip wp-config.php'ye yazılmalı.
4. **DKIM + DMARC** (mail-tester 7,9): Plesk → Mail Settings → DKIM açık; DNS'e `_dmarc` TXT
   `v=DMARC1; p=none; rua=mailto:bilgi@adavira.com`.
5. **Cloudflare DNS geçişi (yarım):** Tema 1.2.4+ gerçek IP desteği hazır, ama NS değişmedi. Yapılacaksa önce
   Cloudflare'in taradığı DNS listesi kontrol edilmeli (mail kayıtları gri bulut, SSL Full (strict), Bot Fight Mode
   kapalı).
6. Search Console: site haritası durumu, yeni yazılar için "Dizine eklenmesini iste".
7. Profil: Görünen ad, biyografi, yazar fotoğrafı (şu an yazarlar "AdaVira Editör" görünüyor).

---

## 3. Erişim ve kimlik bilgileri (değerler yok, nerede durdukları)

| Ne | Nerede | Not |
|---|---|---|
| WordPress REST (yazı/medya/ayar) | Claude ortamı ağ sırları → `adavira.com` | Uygulama parolası "Claude", kullanıcı adavira_adm. Profilden iptal edilebilir. `/wp-json/wp/v2/users/me` sunucuda 403 (normal). |
| Cloudflare Workers AI | Claude ortamı ağ sırları → `api.cloudflare.com` | Yalnızca Workers AI yetkili token. Hesap ID: `b30a1eee1d8de12b206e7a49257b419a` |
| Meta (geçici) | Claude ortamı ağ sırları → `graph.facebook.com` | **Silinmeli** (bkz. 2.2) |
| Facebook sayfa anahtarı | Yalnızca n8n credential'ında ("Query Auth", name `access_token`) | Süresiz |
| SMTP şifresi | WordPress → Görünüm → AdaVira Ayarları (REST'te görünmez) | |
| Plesk, alan adı paneli, n8n, Meta, Cloudflare hesapları | Site sahibinde | |
| Claude ortamı | "pwa (adavira.com erişimi)" · izinli host: adavira.com, www.adavira.com | |

**Kurallar:** Site sahibinden şifre/anahtar sohbete yazmasını isteme. Gerekirse `request_credential` formu kullan.
Gizli anahtarları dosyaya yazma (güvenlik sistemi engelliyor ve doğru olan bu).

---

## 4. Önemli kimlikler

- Facebook sayfası "Ada Vira": `927984147053985` · Instagram @adavira.social: `17841477921864269`
- Meta uygulaması "Adavira" (Geliştirme modu), Graph API v27.0
- Günlük görev: `trig_01TEPvvUSCrANBpDtwWKVS7w` ("AdaVira günlük makale", `CRON_TZ=Europe/Istanbul 40 9 * * *`,
  her çalışmada yeni oturum, push bildirimi açık)
- Blog kategorileri (`konu`): Web Tasarım 7 · SEO 8 · Sosyal Medya 9 · Dijital Reklam 10 · Video ve Drone 11 ·
  Yapay Zekâ ve Otomasyon 12. Haber kategorileri: Sektör Haberleri 2 · Röportaj 3 · Video 4 · Ajans Duyuruları 5
- Sayfalar: Hakkımızda 7 · İletişim 8 · Gizlilik Politikası (slug `gizlilik-politikasi`)

---

## 5. Nasıl çalışılır

**Tema değişikliği**
1. Kod: depo `nearanbe/pwa`, dal `claude/vigilant-babbage-iwz9wu`, klasör `adavira-wordpress/adavira/`.
2. Paket: `cd adavira-wordpress && ./paketle.sh 1.2.6` → `adavira-tema-1.2.6.zip` (sürüm numarası zorunlu,
   site sahibinin isteği). Zip içindeki klasör adı her zaman `adavira`.
3. Tema REST ile yüklenemez: zip site sahibine gönderilir, o **Görünüm → Temalar → Yükle → "Yüklenenle değiştir"**
   yapar ve **Clear Site Cache**'e basar. Sonra canlıda `wp/v2/themes?status=active` ile sürüm doğrulanır.
4. Yerel test için WordPress kurulumu oturuma özel geçici klasördeydi (kalıcı değil); gerekirse Composer
   `johnpbloch/wordpress-core` + MariaDB ile yeniden kurulur (wordpress.org bu ortamdan engelli).

**Yazı yayınlama** (REST, `blog` ya da `haber` yazı türü)
- Görsel: `adavira-wordpress/araclar/gorsel.py` (FLUX.2 [dev] → 1600×900 JPEG → yükle + alt metin/açıklamalar).
  Önce `"sadece_uret": true` ile üret, görseli **açıp incele**, bozuksa yeniden üret.
- Haberlerde yapay zekâ görseline "(Temsili görsel)" notu.
- Yayınlanınca: önbellek otomatik temizlenir, RSS'e Instagram için JPEG (`/wp-content/uploads/sosyal/`) eklenir,
  n8n 15 dk içinde Facebook/Instagram'da paylaşır.
- Site sahibi yeni yazıların **onaysız yayınlanmasını** istedi.

**İçerik kuralları (site sahibinin tercihleri)**
- Doğal ("native") Türkçe, SEO anahtar kelimeleri, Girne/KKTC yerel bağlamı, 800–1200 kelime, SSS bölümü,
  en az 4 iç bağlantı.
- Uydurma istatistik, fiyat, müşteri adı, yorum, referans **yok**. Haber/röportaj gerçek olay ve gerçek konuk
  olmadan yazılmaz.
- Grafik/yazılı kapak sevilmedi; gerçekçi sahne fotoğrafı (ekip çekim yaparken vb.) tercih ediliyor.

---

## 6. Bilinen tuhaflıklar

- adavira.com zaman zaman tek seferlik **502** veriyor; birkaç saniye sonra düzeliyor (araçta yeniden deneme var).
- Bu Claude ortamında wordpress.org, Google Fonts dışındaki birçok dış adres ve DNS araçları kapalı.
- `fonts.googleapis.com` ve `raw.githubusercontent.com` erişilebilir.
- Sunucu: Plesk, nginx + Apache, PHP 8.4, Imunify360; yüklenen JPG/PNG tema tarafından WebP'ye çevrilir
  (iPhone simgesi bu yüzden temadan PNG veriliyor; Instagram için ayrıca JPEG üretiliyor).
- Logo beyaz "Vira" içerdiği için yalnızca koyu zeminde düzgün görünür.

---

## 7. Dosyalar

- `OZET.md` proje özeti · `KURULUM.md` tema kurulumu · `DEVIR.md` bu belge
- `adavira/` tema kaynağı · `adavira-tema-1.2.5.zip` güncel paket · `paketle.sh`
- `araclar/gorsel.py` görsel aracı · `n8n/` sosyal medya akışı (v2) ve rehberi
- `icerik/` elle yazılan yazıların metinleri ve görselleri · `yedek/` eski site içerik yedeği
- `AdaVira-yedek-2026-11-18.zip` tam içerik yedeği (şifre içermez)
