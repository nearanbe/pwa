# AdaVira WordPress teması: kurulum (Plesk)

`adavira-tema.zip` içinde WordPress teması ve eski PHP siteden içerik aktarım aracı bulunur.
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

## 1. WordPress'i deneme adresine kurun
1. Plesk → **Websites & Domains → Add Subdomain** → `yeni.adavira.com`.
2. Plesk → **WordPress → Install** → alan olarak `yeni.adavira.com` seçin. Dil: Türkçe.
3. WordPress paneli → **Görünüm → Temalar → Yeni ekle → Tema yükle** → `adavira-tema.zip` → **Etkinleştir**.
   Tema etkinleşince kalıcı bağlantılar ayarlanır, Hakkımızda ve İletişim sayfaları oluşturulur.

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

## 4. Yayına alın (alan adını çevirin)
1. Plesk → `adavira.com` → **Hosting Settings → Document root** değerini WordPress'in kurulu olduğu
   klasöre çevirin. Bunun yerine WP Toolkit'in **Clone** özelliğiyle `yeni.adavira.com`'u `adavira.com`'a
   da kopyalayabilirsiniz.
2. WordPress → **Ayarlar → Genel**: WordPress adresi ve Site adresi `https://adavira.com` olmalı.
   WP Toolkit'te klonladıysanız bu adım kendiliğinden yapılır.
3. Plesk → `adavira.com` → **Apache & nginx Settings → Additional nginx directives** alanına şunu ekleyin
   (eski görsel adresleri için):
   ```
   location ^~ /uploads/ { return 301 /wp-content$request_uri; }
   ```
4. Google Search Console → **Site haritaları** → `https://adavira.com/sitemap.xml` adresini yeniden gönderin.
5. Deneme alt alanını (`yeni.adavira.com`) silin ya da arama motorlarına kapatın.

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
