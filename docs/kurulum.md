# Kurulum (Plesk VPS)

> **1.0.3 (PWA):** site telefona ve bilgisayara uygulama olarak yüklenebilir (Android/Chrome'da "Uygulamayı yükle", iPhone'da Paylaş → Ana Ekrana Ekle). Açılan sayfalar internet yokken de görüntülenir. Service worker `/?ec_pwa=sw`, manifest `/?ec_pwa=manifest&lang=tr` adresinden sunulur; sunucuda ek ayar gerekmez. Şehir fotoğrafları ve giriş görseli ayarları REST'e açıldı (`/wp-json/wp/v2/settings` → `ec_city_photos`, `ec_hero_photo`).
>
> **1.0.2 (görünüm):** fotoğraflı ana sayfa girişi ve üzerinde arama kutusu (Ara düğmesi sabit), telefonda ☰ menü, ilan sayfasında telefonda alt iletişim çubuğu, "AdaCasa" logo yazısı, AdSense kodu girilmediyse ziyaretçiye boş reklam kutusu gösterilmez. Giriş fotoğrafı **Emlak → Ayarlar → Ana sayfa giriş görseli** bölümünden değiştirilebilir; şehir kartları için gerçek fotoğraflar **Ana sayfa şehir kartları** bölümüne girilir. Güncelleme: eklenti ve tema zip'leri **Mevcut sürümü değiştir** ile yüklenir, ardından Cloudflare önbelleği temizlenir.
>
> **Marka değişti (1.0.1): AdaCasa.** Eklenti `emlak-core` → `adacasa-core`, tema `emlak-tema` → `adacasa-theme` oldu. WordPress eklentiyi ve temayı klasör adından tanıdığı için ikisi de **yeni** olarak kurulur. Veri tabloları (`wp_ec_*`) ve ayarlar aynen kalır, hiçbir şey kaybolmaz.
> 1. **Eklentiler → Yeni ekle → Eklenti yükle** → `adacasa-core-1.0.1.zip` → **Şimdi kur** → **Etkinleştir**. Eski "Emlak Core" bu sırada kendiliğinden devre dışı bırakılır; ikisi aynı anda çalışmaz. (Elle yapmak isterseniz: önce Emlak Core'u devre dışı bırakın, sonra AdaCasa Core'u etkinleştirin.)
> 2. **Eklentiler** → **Emlak Core → Sil**. ("Verileri sil" gibi bir soru çıkmaz; tablolar silinmez.)
> 3. **Görünüm → Temalar → Yeni ekle → Tema yükle** → `adacasa-theme-1.0.1.zip` → **Etkinleştir**. Logo, menü ve Özelleştir ayarları eski temadan kendiliğinden taşınır. Sonra **Emlak Tema → Sil**.
> 4. **Ayarlar → Genel → Site başlığı** = `AdaCasa`. Bu ad tam olarak "AdaCasa" ise logoda "ada**ca**sa" yazısı ve "GAYRİMENKUL & YATIRIM" alt yazısı çıkar.
> 5. **Emlak → Ayarlar → Araçlar → Benzer ilanları şimdi tara.**
> 6. **Emlak → Hız → Cloudflare önbelleğini şimdi tamamen temizle.**
>
> Bundan sonraki güncellemeler yine **Mevcut sürümü değiştir** ile yapılır. Zip adları sürüm numarası taşır (ör. `adacasa-core-1.0.1.zip`). Zip içindeki klasör adı sabittir, bu yüzden WordPress güncellemeyi doğru tanır.
>
> **0.5 yenilikleri:** Paketler (Emlak → Paketler), Facebook ve Instagram paylaşımı ([sosyal-medya-kurulum.md](sosyal-medya-kurulum.md), çizimli rehber: [sosyal-medya-rehberi.html](sosyal-medya-rehberi.html)), hız ayarları ([hiz.md](hiz.md)). Güncelleme sırası aynı: önce eklenti, sonra tema.
>
> **0.3'ten 0.4'e güncelleme:** Önce eklentiyi (`emlak-core-SÜRÜM.zip`), sonra temayı (`emlak-tema-SÜRÜM.zip`) **Eklentiler / Temalar → Yeni ekle → Yükle → Mevcut sürümü değiştir** ile yükleyin. Yeni tablolar ve adresler kendiliğinden kurulur. Sonra **Emlak → Canlıya çıkış** sayfasını açın, eksikleri sırayla tamamlayın. Plesk zamanlanmış görevini **5 dakikaya** düşürmeyi unutmayın.

## Gereksinimler

- PHP 8.1 veya üstü, **GD eklentisi WebP desteğiyle** (Plesk'te varsayılan olarak açık)
- MySQL veya MariaDB
- WordPress 6.4 veya üstü (6.9 ile test edildi)

## 1. Alan adı ve WordPress

1. Plesk'te **Web Siteleri ve Alan Adları → Alan Adı Ekle** ile yeni siteyi ekleyin.
   Başka bir site (ör. adavira.com) aynı sunucudaysa ayrı bir alan adı olarak eklemek yeterlidir.
2. **SSL/TLS Sertifikaları → Let's Encrypt** ile ücretsiz SSL alın.
3. **WordPress → Kur** ile WordPress'i kurun. Dil: Türkçe.
4. WordPress yönetiminde **Ayarlar → Kalıcı bağlantılar → "Yazı adı"** seçip kaydedin.
   Bu adım zorunludur: `/tr/satilik/` gibi linkler ancak böyle çalışır.

## 2. Eklenti ve tema

```bash
bash scripts/paketle.sh
```

1. **Eklentiler → Yeni ekle → Eklenti yükle** → `dist/adacasa-core-SÜRÜM.zip` → Etkinleştir.
   Etkinleştirme sırasında veri tabloları (`wp_ec_*`) otomatik oluşturulur.
2. **Görünüm → Temalar → Yeni ekle → Tema yükle** → `dist/adacasa-theme-SÜRÜM.zip` → Etkinleştir.
3. **Ayarlar → Genel → Site başlığı** alanına sitenin adını yazın. Bu ad logoda görünür.
4. **Emlak → Ayarlar** bölümünden döviz kurlarını, WhatsApp numarasını ve (varsa) AdSense kodlarını girin.

SSH ile güncelleme yapmak isterseniz iki klasörü sunucudaki `wp-content/plugins/` ve `wp-content/themes/` altına kopyalamak yeterlidir:

```bash
rsync -av --delete wordpress/wp-content/plugins/adacasa-core/ KULLANICI@SUNUCU:httpdocs/wp-content/plugins/adacasa-core/
```

```bash
rsync -av --delete wordpress/wp-content/themes/adacasa-theme/ KULLANICI@SUNUCU:httpdocs/wp-content/themes/adacasa-theme/
```

## 3. Demo veriler (isteğe bağlı)

**Emlak → Ayarlar → Demo verisi ekle** düğmesiyle eklenir. Şunları içerir:

- 62 kurgusal ilan
- 6 danışman
- 6 proje
- 3 blog yazısı
- 12 aylık örnek fiyat endeksi geçmişi

Canlıya çıkmadan önce aynı ekrandaki **Demo verisini sil** düğmesiyle silin. Düzenlediğiniz demo blog yazıları silinmez.
WP-CLI ile de yapılabilir:

```bash
wp emlak seed
```

```bash
wp emlak unseed
```

Blog yazıları genel bilgilendirme amaçlı taslaklardır. Yayında tutacaksanız içeriği bir avukata kontrol ettirin.

## Otomatik kur ve fiyat endeksi

- **Kurlar** saatte bir kontrol edilir. Kaynak sırası: **TCMB** (`tcmb.gov.tr/kurlar/today.xml`, her iş günü 15:30'da yayımlanır) → KKTC Merkez Bankası → Avrupa Merkez Bankası (api.frankfurter.dev) → open.er-api.com. **Kur türü** Ayarlar'dan seçilir: ortalama (varsayılan), döviz alış ya da döviz satış. Efektif (nakit) kurları kullanılmaz. Ayarlar ekranında kaynak, **kur tarihi** ve son kontrol zamanı görünür. TL bazlı bir kaynak okunamazsa cevabın başı gösterilir; o metni geliştiriciye gönderin. Makul aralık dışındaki değerler kaydedilmez.
- **Döviz çevirici:** Fiyatın yanındaki parantezli tutara (ör. ₺119.038,39), ilan detayındaki "⇄ Döviz çevirici" düğmesine ya da alt bilgideki linke tıklanınca açılır. Kurlar sitedeki kurların aynısıdır; kaynak ve kur tarihi pencerede yazar.
- **Fiyat endeksi** (`/tr/fiyat-endeksi/`) her gün yayındaki konut ilanlarından hesaplanır: şehir bazında m² başına istenen fiyatın medyanı, en az 3 ilan. Her ayın satırı ay bitince sabit kalır, böylece geçmiş zamanla birikir.
- Bu görevler WordPress'in zamanlayıcısı (WP-Cron) ile çalışır. WP-Cron sitenin ziyaret edilmesiyle tetiklendiği için Plesk'te **zamanlanmış görev şarttır** (gün içi kur ve Telegram'ın "otomatik hazırla" özelliği buna bağlı):
  1. Plesk → Web Siteleri ve Alan Adları → alan adı → **Zamanlanmış Görevler** → **Görev Ekle**.
  2. Görev türü: **URL getir**. URL: `https://SITE/wp-cron.php?doing_wp_cron`
  3. Çalıştır: **Cron tarzı** → `*/5 * * * *` (her 5 dakikada bir). Bildirim: "Bildirim gönderme".
  4. **Emlak → Canlıya çıkış** sayfası görevin çalıştığını 20 dakika içinde yeşil gösterir.

## Gün içi (piyasa) kuru

Türkiye'de kur gün içinde hızlı değiştiği için TL fiyatları TCMB'nin günlük kuru yerine piyasa kuruyla hesaplanabilir.

1. (İsteğe bağlı, önerilir) [twelvedata.com](https://twelvedata.com) → **Sign up** (ücretsiz) → **API Keys** → anahtarı kopyala.
2. **Emlak → Ayarlar → Gün içi (piyasa) kuru:** kutuyu işaretle, anahtarı yapıştır, sıklık **5 dakika**, en çok sapma **%5**. Kaydet.
3. **Araçlar → Gün içi kuru şimdi çek** ile dene. Anahtar yoksa ya da Twelve Data hata verirse Yahoo Finance kullanılır.

Nasıl çalışır:
- Sadece TL kuru gün içinde güncellenir (Twelve Data'da her çekim 1 istek; günde 288 istek, ücretsiz sınır 800). EUR ve USD resmi kurdan gelir.
- Her değer TCMB kuruyla karşılaştırılır. %5'ten fazla sapan değer veri hatası sayılır ve kullanılmaz.
- Değer 30 dakikadan eskiyse (ör. zamanlanmış görev durduysa) site kendiliğinden resmi TCMB kuruna döner.
- Sitede fiyatın yanında **"Piyasa kuru · 14:35"** yazar. Döviz çeviricide ayrıca **TCMB resmi kuru** görünür.

## 360° fotoğraflar (0.6)

- **Emlakçı:** Panelim → İlan → Fotoğraflar adımında **360° fotoğraf ekle**. Her fotoğrafa oda adı yazılır (Salon, Mutfak…). İlan başına en çok 15.
  Genişliği yüksekliğinin 2 katı olan (küresel) bir fotoğraf normal fotoğraf yükleyicisine verilse bile site onu tanır ve 360° bölümüne koyar.
- **Telegram:** 360° fotoğraf **📎 → Dosya** olarak gönderilmeli. Fotoğraf olarak gönderilirse Telegram küçültür ve görüntü bulanık olur; bot bunu emlakçıya söyler.
- **İlan sayfası:** "360° Tur" bölümü. Ziyaretçi **360° turu başlat**'a basınca görüntüleyici yüklenir, bu yüzden sayfa yavaşlamaz.
  - Fareyle, parmakla ya da telefonu döndürerek bakılır. Yakınlaştırma, tam ekran ve oda oda geçiş var.
- **Sanal tur linki:** Matterport, Kuula, Momento360, CloudPano, Roundme ve Panoee linkleri ilan sayfasının içinde açılır.
- **Arama:** **🌐 360°** kutusu, sadece 360° fotoğraflı ilanları listeler. Kartlarda "360°" rozeti çıkar.
- **Sosyal medya:** Facebook sayfasında ilanın ilk 360° fotoğrafı ayrıca çevrilebilir 360° gönderi olarak paylaşılır. Instagram 360° göstermediği için fotoğrafın ortasından düz bir kesit gönderilir.
- **Sunucu (bir kez):** Plesk → alan adı → PHP ayarları:
  - `upload_max_filesize` = **32M**
  - `post_max_size` = **32M**
  - `memory_limit` = **256M**

  **Emlak → Canlıya çıkış** bunu kontrol eder.

## Benzer / kopya ilan denetimi (0.7)
- Her fotoğrafın görsel parmak izi ve her açıklamanın özeti saklanır.
- Bir ilan yayındaki başka bir ilanla **birebir** aynı fotoğrafı ya da açıklamayı taşıyorsa, ekleyen üyeye **"Buna benzer ilan zaten yayında"** uyarısı gider. Uyarı Panelim'de, İlanlarım listesinde ve Telegram önizlemesinde görünür.
- Eşleşme başka bir üyenin ilanıyla ise onay panelinde (`/tr/onay/`) otomatik **"kopya ilan"** bildirimi açılır ve sana e-posta gider.
- Her gün tüm ilanlar yeniden taranır. Ziyaretçi "Hatalı ilan bildir → aynı ilan / fotoğraflar başka mülke ait" seçerse kontrol hemen yapılır, sonucu bildirimin altına yazılır.
- **Emlak → Ayarlar → Benzer / kopya ilan denetimi:** "Başka üyenin ilanıyla eşleşen ilan yayına girmesin, onaya düşsün" kutusu.
- **Araçlar → Benzer ilanları şimdi tara:** güncellemeden sonra bir kez bas.
- Yönetici de **Emlak → İlanlar → Fotoğraflar / 360°** ekranından fotoğraf ve 360° fotoğraf ekleyebilir.

## Tapu türü sadece satılıkta (0.6)

Kiralık ve günlük kiralık ilanlarda tapu türü sorulmaz, saklanmaz ve gösterilmez. Arama kutusunda kiralık sekmesinde tapu filtresi yoktur. Eski kiralık ilanlardaki tapu bilgileri güncellemede kendiliğinden silinir.

## Ana sayfa şehir kartları

**Emlak → Ayarlar → Ana sayfa şehir kartları:** her şehir için Medya kütüphanesindeki bir görselin numarasını ya da adresini girin. Kartlarda satılık ve kiralık ilan sayıları kendiliğinden yazar. Fotoğraf girilmeyen şehirde renkli arka plan görünür.

## Canlıya çıkış kontrolü

**Emlak → Canlıya çıkış** sayfası şunları otomatik kontrol eder ve eksikleri nasıl düzelteceğini yazar:
- HTTPS ve kalıcı bağlantılar
- Demo verisi ve Turnstile anahtarları
- Zamanlanmış görev
- SPF, DKIM, DMARC ve yıldızlı DNS kaydı
- Telegram, webhook ve Claude anahtarı
- Sabit sayfalar ve şehir fotoğrafları

Aynı kontrol SSH'den de çalışır:

```bash
wp emlak check
```

## Logo ve site ikonu

Tema kendi logosu ve ikonlarıyla gelir: sekme ikonu, telefon ana ekran ikonu ve paylaşım görseli.
Kendi logonuzu kullanmak isterseniz **Görünüm → Özelleştir → Site kimliği** bölümünden logo ve site simgesi yükleyin; tema otomatik olarak onları kullanır.

## Üyelik ve onay kuralları

| Hesap türü | Hesap onayı | İlanlar |
|---|---|---|
| Emlak ofisi | Bir kez yönetici onayı | Hesap onaylandıktan sonra **anında yayınlanır** |
| İnşaat firması | Bir kez yönetici onayı | Anında yayınlanır, proje ekleyebilir |
| Bireysel danışman | Gerekmez | **Her ilan yönetici onayından geçer** |
| Mülk sahibi (sahibinden) | Gerekmez | Her ilan yönetici onayından geçer |

- Her üye e-postasını doğrulamadan giriş yapamaz.
- Onay bekleyen ilanlar ve hesaplar telefona uygun **onay paneli** sayfasında listelenir: `/tr/onay/`. Bu sayfayı telefonunun ana ekranına ekleyebilirsin.
- Yeni üye ve onay bekleyen ilan bildirimi e-postayla gelir. Telegram ayarlandıysa Telegram'dan da gelir.
- Satılık ilanlarda **tapu türü zorunludur**. Taslak kaydedilirken istenmez, yayına gönderirken istenir.

## Cloudflare Turnstile (kayıt ve giriş captcha'sı)

1. [dash.cloudflare.com](https://dash.cloudflare.com) → sol menüden **Turnstile** → **Add widget**.
2. **Widget name:** site adı. **Hostname:** alan adın, örneğin `adacasa.com`. Ofis alt alan adları da kullanılacaksa onları da ekleyebilirsin.
3. **Widget mode:** **Managed**. Ziyaretçilerin çoğu hiçbir şey çözmeden geçer.
4. **Create** → çıkan **Site Key** ve **Secret Key** değerlerini kopyala.
5. WordPress → **Emlak → Ayarlar → Cloudflare Turnstile** bölümüne iki anahtarı yapıştır ve kaydet.

Anahtar girilmediği sürece formlar captcha'sız çalışır. Canlıya çıkmadan önce mutlaka girilmeli.

## Ofislere alt alan adı (maviada.alanadi.com)

DNS Cloudflare'de olduğu için önerilen yol şu:

1. **Cloudflare → DNS → Add record:** Type `A`, Name `*`, IPv4 = sunucunun IP'si, **Proxy: açık (turuncu bulut)**.
   Cloudflare'in ücretsiz SSL'i `*.alanadi.com` alt alan adlarını kendiliğinden kapsar.
2. **Cloudflare → SSL/TLS → Origin Server → Create Certificate:** Hostnames `alanadi.com` ve `*.alanadi.com`, süre 15 yıl.
   Verilen sertifikayı ve özel anahtarı kopyala.
3. **Cloudflare → SSL/TLS → Overview:** **Full (strict)** seç.
4. **Plesk → alan adı → SSL/TLS Sertifikaları → Sertifika ekle:** 2. adımdaki sertifikayı ve anahtarı yapıştır ve alan adına ata.
   Let's Encrypt yerine bu sertifikayı kullan, çünkü Let's Encrypt yıldızlı sertifikayı DNS Cloudflare'deyken kendiliğinden yenileyemez.
5. **Plesk → Web Siteleri ve Alan Adları → Alt Alan Adı Ekle:** adı `*` yaz. Belge kök dizini ana sitenin klasörü (`httpdocs`) olsun.
   Plesk'in bu sürümü yıldızlı alt alan adına izin vermiyorsa: alan adı → **Hosting ve DNS → Web Sunucusu Ayarları**'na `ServerAlias *.alanadi.com` satırını ekle.
6. WordPress → **Emlak → Ayarlar → Ofis alt alan adları** kutusunu işaretle.

Kutu işaretli değilken vitrinler `/tr/ofis/ofis-adi/` adresinde açılır. Bu adres her durumda yedek olarak çalışır.

## Telegram botu (ilan alma ve onay)

Tek merkezi bot vardır. Üyeler hesaplarını bota bağlar, fotoğraf ve metin gönderir, yapay zekâ ilanı hazırlar.

1. Telegram'da **@BotFather** → `/newbot`. Bota bir ad ve `...Bot` ile biten bir kullanıcı adı ver (ör. `AdaCasaBot`). Gelen **token**'ı kopyala.
2. Kendi botuna bir mesaj gönder. Sonra **@userinfobot**'a yazarak kendi sohbet numaranı (chat id) öğren.
3. **Emlak → Ayarlar → Telegram:** bot kullanıcı adı, token ve sohbet numaranı gir, kaydet.
4. **Emlak → Ayarlar → Araçlar → Telegram webhook'unu kur.** "Webhook kuruldu" yazmalı. (Site HTTPS olmalı; Telegram http adrese mesaj göndermez.)
5. Bota `/start` yaz: karşılama mesajı gelirse bot çalışıyor.

Üye tarafı: **Panelim → Telegram → Telegram'ı bağla.** Açılan sohbette **Başlat**'a basınca hesap bağlanır (kod 10 dakika geçerli).

Akış:
- Üye fotoğrafları ve metni arka arkaya gönderir. Bot tek bir durum mesajını günceller: "4 fotoğraf, 1 metin alındı · **İlanı hazırla**".
- **İlanı hazırla** (ya da 45 sn sessizlik): yapay zekâ alanları çıkarır, TR/EN/RU başlık ve açıklama yazar, sitede taslak ilan açılır, fotoğraflar eklenir, önizleme gelir: **✅ Yayınla · ✏️ Düzelt · 🗑 İptal · Panelde aç**.
- **Düzelt:** üye doğal dille yazar ("fiyat 165.000 sterlin, tapu eşdeğer"), yeni önizleme gelir. Fotoğraf göndermek fotoğraf ekler.
- **Yayınla:** ofis ve inşaat firması → anında yayında. Bireysel danışman / mülk sahibi → onaya düşer, yöneticiye Telegram'dan **Onayla / Reddet** düğmeli kart gelir. Reddet'e basınca bot sebebi sorar ve üyeye iletir.
- Komutlar: `/yeni`, `/iptal`, `/ilanlarim`, `/yardim`. Yönetici: `/bekleyen`.

## Yapay zekâ (Claude API)

1. [console.anthropic.com](https://console.anthropic.com) → hesap aç → **Billing**'den kredi yükle → **API Keys → Create Key**.
2. **Emlak → Ayarlar → Yapay zekâ:** anahtarı yapıştır. Model `claude-opus-5-5`, çaba **Orta** (önerilen).
3. **Araçlar → Yapay zekâyı dene:** örnek metinden çıkarılan başlık ve fiyat görünmelidir.

Bir ilan genellikle birkaç sentlik maliyet tutar. Maliyeti azaltmak isterseniz çabayı **Düşük** yapabilirsiniz.

## Chrome eklentisi (101evler ve hangiev)

`dist/ilan-aktarici-SÜRÜM.zip` dosyası emlakçıya verilir. Kurulum (Chrome Web Store'a yüklenene kadar):

1. Zip'i bir klasöre aç.
2. Chrome → adres çubuğuna `chrome://extensions` → sağ üstte **Geliştirici modu**'nu aç → **Paketlenmemiş öğe yükle** → açılan klasörü seç.
3. Eklenti simgesi → ⚙ **Ayarlar:** site adresi (`https://adacasa.com`) ve API anahtarı (**Emlak → Danışmanlar → API anahtarı üret**). **Kaydet ve dene** → "Bağlandı" yazmalı.
4. 101evler ve hangiev'de kendi hesabınla giriş yap.

Kullanım: eklenti simgesi → ilanı bul → **101evler'e aktar**. Yeni sekmede ilan formu açılır, eklenti alanları ve fotoğrafları doldurur, sağ altta ne yaptığını yazar.
- **"Yayınla" düğmesine her zaman emlakçı basar.** Captcha çıkarsa emlakçı çözer.
- SMS doğrulama kodu istenirse panelde **SMS kodunu Telegram'dan iste** düğmesi çıkar. Bot kodu sorar, emlakçı bota yazar, eklenti kodu kutuya yazar.
- Yayınladıktan sonra panelde **Yayınladım ✓**'e basılır. İlan Panelim → İlanlarım'da "101evler ✓" rozetiyle görünür.

Siteler formlarını değiştirirse eklentiyi yeniden kurmak gerekmez. **Emlak → Ayarlar → Chrome eklentisi** kutusundaki JSON (alan seçicileri ve etiketleri) güncellenir. Eklentinin varsayılan ayarları gerçek 101evler / hangiev formları görülmeden yazıldı. İlk gerçek denemede bulunamayan alanlar paneldeki listede görünür; o sayfaların kaynak kodu gönderilirse seçiciler kesinleştirilir.

## Cloudflare görsel üretimi (temsili görseller)

1. **dash.cloudflare.com** → sağ üstte profil → **My Profile → API Tokens → Create Token** → **Workers AI** şablonu → **Continue to summary → Create Token**.
2. Hesap ana sayfasının sağ tarafındaki **Account ID**'yi kopyala.
3. **Emlak → Ayarlar → Cloudflare Workers AI** bölümüne iki değeri gir.
4. **Emlak → Görsel üret:** tarifi yaz. Görseli medya kütüphanesine kaydet, bir projeye **temsili kapak** yap ya da bir blog yazısına öne çıkan görsel olarak ata.

Yapay zekâ ile üretilmiş görseller **ilan fotoğrafı olarak kullanılmaz**. Proje kapağında "Temsili görsel" etiketiyle gösterilir.

## E-posta teslimi

Doğrulama ve onay e-postaları sunucudan gönderilir. Spam klasörüne düşmemeleri için Plesk'te alan adı için **SPF, DKIM ve DMARC** kayıtlarını aç. DNS Cloudflare'deyse bu kayıtları Cloudflare DNS'e de ekle.
Gerekirse bir SMTP eklentisiyle (örneğin WP Mail SMTP) Brevo veya Mailgun gibi bir servis kullanılabilir.

## 4. Sabit sayfalar

Alt bilgideki linkler, aşağıdaki kısa adlara (slug) sahip WordPress sayfalarına bağlanır.
**Emlak → Ayarlar → Araçlar → Sabit sayfaları taslak olarak oluştur** dört sayfayı taslak olarak açar. Düzenleyip yayınlayın; gizlilik ve kullanım şartlarını bir avukata kontrol ettirin.

| Sayfa | Kısa ad |
|---|---|
| Hakkımızda | `hakkimizda` |
| İletişim | `iletisim` |
| Gizlilik Politikası | `gizlilik-politikasi` |
| Kullanım Şartları | `kullanim-sartlari` |

## 5. Danışman ve API anahtarı

1. Sitedeki **Kayıt Ol** formundan gelen başvurular **Emlak → Danışmanlar** ekranına "Bekliyor" olarak düşer.
2. **Onayla** deyin, ardından **API anahtarı üret** ile anahtarı alın.
   Anahtar yalnızca bir kez gösterilir. Telegram botu ve Chrome eklentisi bu anahtarla çalışır.

## Adresler

| Sayfa | Türkçe | English | Русский |
|---|---|---|---|
| Ana sayfa | `/tr/` | `/en/` | `/ru/` |
| Satılık | `/tr/satilik/` | `/en/for-sale/` | `/ru/prodazha/` |
| Kiralık | `/tr/kiralik/` | `/en/for-rent/` | `/ru/arenda/` |
| Günlük | `/tr/gunluk-kiralik/` | `/en/holiday-rentals/` | `/ru/posutochno/` |
| Şehir | `/tr/satilik/girne/` | `/en/for-sale/girne/` | `/ru/prodazha/girne/` |
| İlan | `/tr/ilan/{slug}-{no}/` | `/en/property/{slug}-{no}/` | `/ru/property/{slug}-{no}/` |
| Projeler | `/tr/projeler/` | `/en/projects/` | `/ru/proekty/` |
| Proje | `/tr/proje/{slug}-{no}/` | `/en/project/{slug}-{no}/` | `/ru/proekt/{slug}-{no}/` |
| Fiyat endeksi | `/tr/fiyat-endeksi/` | `/en/price-index/` | `/ru/indeks-tsen/` |
| Blog | `/blog/` | | |
| İlan ver | `/tr/ilan-ver/` | `/en/list-property/` | `/ru/razmestit/` |
| Giriş | `/tr/giris/` | `/en/login/` | `/ru/vhod/` |
| Kayıt | `/tr/kayit/` | `/en/register/` | `/ru/registratsiya/` |
| Panelim | `/tr/panelim/` | `/en/my-account/` | `/ru/kabinet/` |
| Onay paneli | `/tr/onay/` | `/en/review/` | `/ru/moderatsiya/` |
| Ofis vitrini | `/tr/ofis/{kisa-ad}/` | `/en/agent/{kisa-ad}/` | `/ru/agent/{kisa-ad}/` |
| Firmalar dizini | `/tr/firmalar/` | `/en/agencies/` | `/ru/agentstva/` |
| Favoriler | `/tr/favoriler/` | `/en/favourites/` | `/ru/izbrannoe/` |

Kök adres (`/`) ziyaretçinin tarayıcı diline göre `/tr/`, `/en/` veya `/ru/` adresine yönlenir.
