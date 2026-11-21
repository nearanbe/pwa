# Kurulum (Plesk VPS)

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

1. **Eklentiler → Yeni ekle → Eklenti yükle** → `dist/emlak-core.zip` → Etkinleştir.
   Etkinleştirme sırasında veri tabloları (`wp_ec_*`) otomatik oluşturulur.
2. **Görünüm → Temalar → Yeni ekle → Tema yükle** → `dist/adaev.zip` → Etkinleştir.
3. **Ayarlar → Genel → Site başlığı** alanına sitenin adını yazın. Bu ad logoda görünür.
4. **Emlak → Ayarlar** bölümünden döviz kurlarını, WhatsApp numarasını ve (varsa) AdSense kodlarını girin.

SSH ile güncelleme yapmak isterseniz iki klasörü sunucudaki `wp-content/plugins/` ve `wp-content/themes/` altına kopyalamak yeterlidir:

```bash
rsync -av --delete wordpress/wp-content/plugins/emlak-core/ KULLANICI@SUNUCU:httpdocs/wp-content/plugins/emlak-core/
```

```bash
rsync -av --delete wordpress/wp-content/themes/adaev/ KULLANICI@SUNUCU:httpdocs/wp-content/themes/adaev/
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

- **Kurlar** her gün otomatik güncellenir. Önce **KKTC Merkez Bankası** günlük kur XML'i (`mb.gov.ct.tr/kur/gunluk.xml`) okunur, ulaşılamazsa Avrupa Merkez Bankası (api.frankfurter.dev), o da olmazsa open.er-api.com kullanılır. Merkez Bankası cevabı okunamazsa Ayarlar ekranında cevabın başı gösterilir; o metni geliştiriciye gönderin. **Emlak → Ayarlar** ekranında son güncelleme zamanı görünür, ayrıca **Kurları şimdi güncelle** düğmesi vardır. Makul aralık dışındaki değerler kaydedilmez.
- **Fiyat endeksi** (`/tr/fiyat-endeksi/`) her gün yayındaki konut ilanlarından hesaplanır: şehir bazında m² başına istenen fiyatın medyanı, en az 3 ilan. Her ayın satırı ay bitince sabit kalır, böylece geçmiş zamanla birikir.
- Bu iki görev WordPress'in zamanlayıcısı (WP-Cron) ile çalışır. WP-Cron sitenin ziyaret edilmesiyle tetiklendiği için, trafik azken Plesk'te **Zamanlanmış Görevler** bölümüne şu görevi eklemek daha güvenilirdir (her 15 dakikada bir):

  ```bash
  wget -q -O - https://SITE/wp-cron.php?doing_wp_cron >/dev/null 2>&1
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
2. **Widget name:** site adı. **Hostname:** alan adın, örneğin `emlak392.com`. Ofis alt alan adları da kullanılacaksa onları da ekleyebilirsin.
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

## Telegram bildirimleri

1. Telegram'da **@BotFather** → `/newbot` komutunu gönder. Bota bir ad ve `...Bot` ile biten bir kullanıcı adı ver. Gelen **token**'ı kopyala.
2. Kendi botuna bir mesaj gönder. Sonra **@userinfobot**'a yazarak kendi sohbet numaranı (chat id) öğren.
3. **Emlak → Ayarlar → Telegram:** bot kullanıcı adı, token ve sohbet numaranı gir.

Telegram'dan ilan ekleme (yapay zekâ ile metinden ilan hazırlama ve "Onayla" düğmeleri) sürüm 0.4'te eklenecek. Üyelerin Panelim'deki "Telegram'ı bağla" düğmesi bot ayarlanınca görünür.

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
**Sayfalar → Yeni ekle** ile oluşturun:

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
| Favoriler | `/tr/favoriler/` | `/en/favourites/` | `/ru/izbrannoe/` |

Kök adres (`/`) ziyaretçinin tarayıcı diline göre `/tr/`, `/en/` veya `/ru/` adresine yönlenir.
