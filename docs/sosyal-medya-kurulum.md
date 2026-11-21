# Sosyal medya paylaşımı: kurulum ve sorun çözme

Bu belge **site yöneticisi** (sen) içindir. Müşteri (emlakçı) sadece 2 küçük iş yapar, gerisini sen yaparsın ve bunun için ücret alırsın.
Resimli anlatım: `docs/sosyal-medya-rehberi.html` (tarayıcıda aç).

## Nasıl çalışıyor? (bir cümlede)

Senin Facebook hesabın, müşterinin Facebook sayfasında "yönetici" olur. Senin sitene kurduğun bağlantı da sayfayı senin hesabın üzerinden görür ve ilanları o sayfaya ve sayfaya bağlı Instagram'a otomatik gönderir.
Müşterinin şifresini hiç bilmezsin. Müşteri ödemeyi keserse paketini bitirirsin ya da müşteri senin erişimini kaldırır, paylaşım durur.

- Facebook **gruplarına** paylaşım yoktur (Meta 2024'te kapattı; zorlamak hesap kapattırır).
- Instagram **kişisel** hesaba paylaşım yapılamaz; hesap "İşletme" ya da "İçerik üretici" olmalı.

---

## A. Bir kerelik kurulum (sadece sen, ~15 dakika)

### A1. Meta geliştirici hesabı ve uygulama
1. Bilgisayardan **developers.facebook.com** adresine git. Kendi Facebook hesabınla giriş yap.
2. İlk kez giriyorsan **"Başlayın / Get Started"**. Telefon ve e-posta doğrulamasını yap.
3. Sağ üstte **"Uygulamalarım / My Apps"** → **"Uygulama oluştur / Create App"**.
4. Uygulama adı: `Emlak392 Paylaşım` (istediğin bir ad). İletişim e-postası: kendi e-postan. **İleri**.
5. **Kullanım durumu (Use case)** ekranında şu ikisini seç:
   - **"Sayfanızdaki her şeyi yönetin" / "Manage everything on your Page"**
   - **"Instagram'da mesajları ve içeriği yönetin" / "Manage messaging & content on Instagram"**

   İleri.
6. "İşletme portföyü (Business portfolio)" sorulursa **"Şimdilik bağlama"** seçebilirsin. **İleri** → **Uygulama oluştur**. Facebook şifreni tekrar sorabilir.

### A2. Uygulama numarası ve gizli anahtar
1. Uygulamanın sol menüsünde **Uygulama ayarları / App settings** → **Temel / Basic**.
2. **Uygulama No (App ID)** → kopyala.
3. **Uygulama Gizli Anahtarı (App Secret)** → **Göster / Show** → şifreni yaz → kopyala.
4. Aynı sayfada şunları doldur. Bunlar uygulamayı "Yayında" moduna alabilmek için gerekir:
   - **Gizlilik politikası URL'si:** `https://SITEN/gizlilik-politikasi/`
   - **Kullanıcı verilerini silme:** "Veri silme talimatları URL'si" → aynı adres
   - **Kategori:** İş ve Sayfalar (Business and Pages)
   - **Uygulama simgesi:** sitenin logosu (1024×1024)

   **Değişiklikleri kaydet.**
5. Siteye dön: **WordPress → Emlak → Sosyal medya → 1. Meta uygulaması** → App ID ve App Secret'ı yapıştır → **Kaydet**.

### A3. Uygulamayı "Yayında" moduna al
Sol üstte **"Uygulama modu: Geliştirme"** anahtarını **"Yayında / Live"** yap.
Geliştirme modunda yapılan paylaşımları bazen sadece sen görürsün; yayında modunda herkes görür. Uygulama incelemesi (App Review) **gerekmez**, çünkü uygulamayı sadece sen kullanıyorsun.

### A4. Kendi sitenin sayfasını bağla
Sitenin kendi Facebook sayfası ve Instagram'ı için B2 adımını kendin için yap. Sonra sayfayı listede **"★ Site"** olarak ata.

---

## B. Her yeni müşteri için

### B1. Müşteriden istenecek 2 şey (müşteri yapar, 5 dakika)
Bunu müşteriye WhatsApp'tan gönderebilirsin:

> **1) Instagram'ı işletme hesabına çevir:** Instagram → Profil → sağ üstte ☰ → **Hesap türü ve araçlar** → **Profesyonel hesaba geç** → **İşletme**.
>
> **2) Instagram'ı Facebook sayfana bağla ve bana sayfa erişimi ver:**
> - Bilgisayardan **business.facebook.com** (Meta Business Suite) → sol altta **Ayarlar**
> - **Hesaplar → Instagram hesapları → Bağla**
> - Sonra **Kişiler / Sayfa erişimi → Kişi ekle** → benim adım / e-postam → **"Tam kontrol" (Facebook erişimi + görev erişimi)** → **Ver**
>
> Telefondan: Facebook uygulaması → sayfana geç → **Ayarlar** → **Sayfa erişimi** → **Yeni ekle** → beni ara → **Tam kontrol**.

Sana bildirim gelir. Facebook'ta **"Sayfa erişim davetini kabul et"**e bas (bildirimlerde ya da business.facebook.com'da).

### B2. Bağlantıyı yenile (sen, 2 dakika)
**Yol 1: "Facebook ile bağlan" düğmesi** (ilk seferde A5 ayarı gerekir, aşağıya bak)
1. WordPress → **Emlak → Sosyal medya → 2. Facebook ile bağlan**.
2. Facebook penceresinde **"… olarak devam et"** → sayfa seçme ekranında **"Tüm mevcut ve gelecekteki sayfalar"**ı seç (ya da müşterinin sayfasını işaretle) → Instagram hesapları için de aynısı → tüm izinlere **Evet** → **Kaydet**.
3. Sitede "X sayfa bulundu" yazar.

**Yol 2: düğme çalışmazsa (yedek, her zaman çalışır)**
1. **developers.facebook.com/tools/explorer** (Graph API Gezgini).
2. Sağda **Meta Uygulaması:** kendi uygulamanı seç. **Kullanıcı ya da Sayfa:** **Kullanıcı Erişim Anahtarı (User Token)**.
3. **İzinler (Permissions)** kutusuna tek tek ekle:
   `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `instagram_basic`, `instagram_content_publish`, `business_management`
4. **Generate Access Token** → açılan pencerede tüm sayfaları ve izinleri onayla.
5. Çıkan uzun anahtarı (EAA… ile başlar) kopyala.
6. WordPress → **Emlak → Sosyal medya → "Yedek yol: anahtarı elle yapıştır"** → yapıştır → **Ekle**. Site bu anahtarı süresi dolmayan sayfa anahtarlarına kendisi çevirir.

### B3. Sayfayı müşteriye ata ve paketi ver
1. **Emlak → Sosyal medya → 3. Sayfalar:** müşterinin sayfasının yanındaki listeden **müşteriyi** seç. **Facebook** ve **Instagram** kutularını işaretle → **Kaydet**.
2. **"Şimdi paylaş"** ile bir ilanı dene → "paylaşıldı · aç ↗" yazmalı. Linke tıklayıp kontrol et.
3. **Emlak → Paketler → Üyeye paket ver:** müşteri, paket, süre → **Paketi ver**. Paketi olmayan müşterinin sayfasına hiçbir şey gönderilmez.

Müşteri kendi **Panelim → Sosyal medya** sekmesinden şunları seçer:
- otomatik paylaşım açık/kapalı
- gönderi dili (TR, EN, RU, TR+EN ya da üçü)
- imza satırı (ad, telefon)

Her ilanda ayrıca **📣 Paylaş** düğmesi vardır.

### A5. "Facebook ile bağlan" düğmesi için (isteğe bağlı, bir kez)
Uygulamada sol menü → **Ürün ekle** → **Facebook Girişi (Facebook Login for Business)** → **Ayarlar**. **Geçerli OAuth Yönlendirme URI'leri** kutusuna, **Emlak → Sosyal medya** sayfasında yazan adresi yapıştır:
`https://SITEN/wp-admin/admin-post.php?action=ec_fb_callback` → **Kaydet**.
Düğme çalışmazsa B2'deki Yol 2'yi kullan; sonuç aynıdır.

---

## C. Çıkabilecek sorunlar ve çözümleri

| Sorun (sitede görünen) | Sebep | Çözüm |
|---|---|---|
| "X sayfa bulundu" ama müşterinin sayfası yok | Müşteri erişim vermedi, sen daveti kabul etmedin ya da anahtar alırken o sayfayı seçmedin | Daveti kabul et (Facebook bildirimleri). B2'yi tekrarla ve sayfa seçerken **tüm sayfalar**ı işaretle. Sonra **Sayfa listesini yenile**. |
| Instagram sütunu "bağlı değil" | Instagram kişisel hesap ya da sayfaya bağlı değil | Müşteri B1-1 ve B1-2'yi yapsın. Sonra **Sayfa listesini yenile**. |
| "İzin eksik … (#10)" ya da "(#200)" | Anahtar alırken bir izin onaylanmadı ya da erişim "tam kontrol" değil | B2'yi tekrarla, 6 iznin hepsini ekle ve onayla. Müşteri erişimi "tam kontrol" olarak versin. |
| "Facebook bağlantısının süresi doldu (#190)" | Facebook şifreni değiştirdin, oturumların kapandı ya da müşteri erişimini kaldırdı | B2'yi tekrarla. Müşteri erişimi kaldırdıysa yeniden vermesi gerekir. Sana e-posta/Telegram ile otomatik uyarı gelir. |
| "Instagram fotoğrafı indiremedi (#9004)" | Meta site fotoğraflarına ulaşamıyor | Site **https** ve herkese açık olmalı. Cloudflare'de **Bot Fight Mode kapalı** olmalı (Güvenlik → Botlar). Fotoğraf linkini gizli pencerede açıp dene. |
| "Instagram fotoğraf oranını kabul etmedi (#36003)" | Çok dar ya da çok uzun fotoğraf | Site fotoğrafı kendisi kırpar. Yine de olursa o fotoğrafı ilan sırasında sona al. |
| "Instagram'ın günlük paylaşım sınırı doldu" | Bir hesaptan 24 saatte en çok 50 gönderi | Bekler, ertesi gün kendiliğinden devam eder. |
| "Facebook bu paylaşımı spam şüphesiyle engelledi (#368)" | Çok sık, birbirine çok benzeyen gönderi | Günlük paylaşımı azalt. Şablonu ilanlara göre değişen metinlerle zenginleştir. Birkaç saat bekle. |
| Gönderi "🕓 sırada" kalıyor | Zamanlanmış görev çalışmıyor | Plesk zamanlanmış görevi (5 dakikada bir) çalışıyor mu? **Emlak → Canlıya çıkış** sayfasına bak. |
| Paylaşım sadece bana görünüyor | Uygulama "Geliştirme" modunda | A3: uygulamayı **Yayında** moduna al. |
| Müşterinin sayfasına hiç gitmiyor | Paket yok, süresi dolmuş, kota dolmuş ya da müşteri otomatik paylaşımı kapatmış | **Emlak → Paketler** ve **Son paylaşımlar** listesindeki "atlandı" sebebine bak. |
| Aynı ilan iki kez paylaşılsın istiyorum | Sistem aynı ilanı aynı hesaba bir kez gönderir | **Son paylaşımlar**da **Tekrar dene**, ya da yeni ilan aç. |
| "Facebook ile bağlan"da "URL engellendi / redirect_uri" hatası | Yönlendirme adresi Meta'ya girilmedi | A5. Ya da B2'deki Yol 2'yi kullan. |
| Müşteri ödeme yapmadı | — | **Paketler → İptal** (anında durur) ya da sayfayı listede "atanmadı" yap. |

## D. Ücretlendirme önerisi
- Kurulum ücreti (B1–B3 için bir kez) + aylık paket (**Emlak → Paketler**).
- Paket bitmeden 3 gün önce hem sana hem müşteriye otomatik hatırlatma gider. Bitince paylaşım durur.
