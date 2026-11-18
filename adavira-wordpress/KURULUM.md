# AdaVira WordPress teması: kurulum (Plesk)

`adavira-tema-<sürüm>.zip` (ör. `adavira-tema-1.0.1.zip`) içinde WordPress teması ve eski PHP siteden içerik aktarım aracı bulunur.
Eski siteniz bu süreçte **hiç değişmez**. Yeni site hazır olunca alan adı yeni siteye çevrilir.

## Neler korunuyor?
- Tüm adresler aynı: `/hizmetler`, `/hizmet/...`, `/sektorler`, `/sektor/...`, `/blog/...`,
  `/bizden-haberler/...`, `/hakkimizda`, `/iletisim`, `/sitemap.xml`. Google sıralamanız korunur.
- Eski görsel adresleri (`/uploads/2026/11/...`) yeni yerlerine 301 ile yönlenir.
- `/yonetim.php` adresi WordPress paneline (`/wp-admin`) yönlenir.
- SEO: title, description, canonical, Open Graph, Twitter kartı, schema.org (ProfessionalService,
  BreadcrumbList, BlogPosting/NewsArticle), görsel destekli site haritası, robots.txt.
- İletişim formu: ülke kodu ve (5xx) xxx xx xx telefon alanı, bot koruması (matematik sorusu veya
  Cloudflare Turnstile), SMTP ile e-posta. Mesajlar ayrıca panelde **Mesajlar** menüsünde saklanır.

## 1. WordPress'i adavira.com/yeni klasörüne kurun
Eski siteyi bozmamak için WordPress'i sitenin **ana klasörüne değil**, `yeni` adlı bir alt klasöre kurun.
1. Plesk → **WordPress → Install** (veya **Install → Custom**).
2. Alan adı: `adavira.com`. **Installation path** (kurulum yolu) alanına `yeni` yazın.
   Sonuç `https://adavira.com/yeni` olmalı. Bu alan boş kalırsa WordPress eski sitenin üzerine kurulur!
3. Veritabanı için **yeni** bir veritabanı oluşturulsun (eski sitenin veritabanını seçmeyin).
4. WordPress paneli (`https://adavira.com/yeni/wp-admin`) → **Görünüm → Temalar → Yeni ekle → Tema yükle**
   → `adavira-tema-<sürüm>.zip` (ör. `adavira-tema-1.0.1.zip`) → **Etkinleştir**. Tema etkinleşince kalıcı bağlantılar ayarlanır, Hakkımızda ve
   İletişim sayfaları oluşturulur.
5. **Ayarlar → Okuma** → "Arama motorlarının bu siteyi dizinlemesini engelle" kutusunu işaretleyin
   (deneme süresince).

## 2. Eski içerikleri aktarın
1. **Araçlar → AdaVira Aktarım** menüsünü açın.
2. "Eski site klasörü" alanına eski sitenin klasörünü yazın (içinde `.env` olan):
   `/var/www/vhosts/adavira.com/httpdocs`
3. **Aktarımı başlat** düğmesine basın. Aktarılanlar şunlar:
   - Blog ve Bizden Haberler yazıları (tarihleri, özetleri ve adresleriyle)
   - Kapak görselleri (alt metinleriyle) ve yazı içi görseller
   - Hakkımızda sayfası
   - İletişim formu mesajları
   - `.env` içindeki WhatsApp numarası, sosyal medya adresleri ve SMTP ayarları

Aracı tekrar çalıştırmak güvenlidir: kayıtlar çoğalmaz, yalnızca güncellenir.
WP-CLI kullanıyorsanız: `wp adavira import --old=/var/www/vhosts/adavira.com/httpdocs`

## 3. Ayarları kontrol edin
**Görünüm → AdaVira Ayarları** sayfasında şunları kontrol edin:
- WhatsApp ve telefon numarası, e-posta, adres, çalışma saatleri
- SMTP bilgileri: Plesk'teki `bilgi@adavira.com` kutusu. Sunucu `mail.adavira.com`, port 465, şifreleme ssl.
- Sosyal medya adresleri. Yalnızca alan adı olan adresler (ör. `https://x.com/`) sitede gösterilmez,
  profil adresinizi tam yazın.

**İletişim** sayfasından deneme mesajı gönderin, sonra hem e-postayı hem **Mesajlar** menüsünü kontrol edin.

## 4. Yayına alın (adavira.com'u WordPress'e çevirin)
1. Plesk → `adavira.com` → **Hosting & DNS → Hosting Settings → Document root**: şu anki değerin
   sonuna `/yeni` ekleyin (ör. `httpdocs/public` ise `httpdocs/public/yeni`). **Kaydet**.
2. WordPress → **Ayarlar → Genel**: "WordPress adresi" ve "Site adresi" alanlarının ikisini de
   `https://adavira.com` yapın. **Kaydet** (sizi yeniden giriş ekranına atabilir: `https://adavira.com/wp-admin`).
3. **Araçlar → AdaVira Aktarım** → aktarımı **bir kez daha** çalıştırın. Yazı içindeki görsel adresleri
   `/yeni` olmadan güncellenir. (Bu adımdan önce yazıları panelde düzenlemeyin, aktarım üzerine yazar.)
4. **Ayarlar → Kalıcı bağlantılar** → hiçbir şey değiştirmeden **Kaydet**.
5. **Ayarlar → Okuma** → dizinleme engeli kutusunun işaretini **kaldırın**.
6. Plesk → **Apache & nginx Settings → Additional nginx directives** alanına şunu ekleyin
   (eski görsel adresleri için):
   ```
   location ^~ /uploads/ { return 301 /wp-content$request_uri; }
   ```
7. Google Search Console → **Site haritaları** → `https://adavira.com/sitemap.xml` adresini yeniden gönderin.

Eski dosyalar `httpdocs` içinde yedek olarak kalır. Bir sorun olursa document root'u geri almanız yeterli.

## Notlar
- **Yeni görseller:** Panelden yüklenen JPG/PNG görseller otomatik WebP'ye çevrilir, 400/800/1600 px
  boyutları ve 1200×630 paylaşım görseli oluşturulur. Kapak görseli = yazının "Öne çıkan görsel"i.
  Alt metni (alternatif metin) mutlaka doldurun.
- **Konular:** Blog ve haberler için "Konular" kategorisi eklendi (ör. Röportaj, YouTube, Sektör Haberi).
  Sektör haberleri / röportaj platformuna geçişte bunu kullanabilirsiniz.
- **SEO eklentisi:** Rank Math veya Yoast kurarsanız tema başlık ve meta etiketlerini otomatik olarak
  eklentiye bırakır. İki kez çıktı oluşmaz.
- **Gmail'e mail ulaşmama** sorunu tema ile ilgili değildir: DNS'te SPF, DKIM ve DMARC kayıtları gerekir
  (Plesk → Mail Settings → DKIM'i açın, DNS'e `v=DMARC1; p=none;` kaydı ekleyin).
- Aktarım bitince güvenlik için eski sitenin `.env` dosyasındaki şifreleri (veritabanı, SMTP) değiştirin
  ve yeni şifreleri **AdaVira Ayarları** sayfasına girin.
