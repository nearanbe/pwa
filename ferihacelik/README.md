# ferihacelik.com — WordPress tema + randevu eklentisi

| Klasör | Ne? |
|---|---|
| `feriha-celik/` | Özel blok teması (FSE). Pastel yeşil tasarım, anasayfa (hero, 4 grup hizmet, randevu adımları, son yazılar), hizmet sayfası şablonu, sabit WhatsApp butonu, schema.org (MedicalBusiness + Person + BlogPosting + Breadcrumb), Yoast/Rank Math yoksa meta/OG. |
| `feriha-randevu/` | Ücretsiz özel randevu eklentisi. Müsait gün/saat yönetimi, `[feriha_randevu]` formu, Apple Takvim için iCal abonelik adresi, `.ics` ekli e-posta bildirimleri, onay/iptal akışı. |
| `dist/` | WordPress'e yüklenecek `.zip` dosyaları. |
| `KULLANIM-KILAVUZU.md` | Feriha Hanım için 1 sayfalık kullanım kılavuzu. |

## Kurulum

1. WordPress'i kurun (dil: Türkçe). *Ayarlar › Genel › Saat dilimi:* İstanbul.
2. **Görünüm › Temalar › Yeni ekle › Tema yükle** → `dist/feriha-celik.zip` → Etkinleştir.
   Etkinleştirince tüm sayfalar (Hakkımda, Hizmetler + 17 hizmet sayfası, Testler ve Ölçekler, Online Terapi, Blog, Randevu Al, İletişim, KVKK) örnek metinleriyle otomatik oluşur; kalıcı bağlantılar `/%postname%/` yapılır, yorumlar kapatılır.
3. **Eklentiler › Yeni ekle › Eklenti yükle** → `dist/feriha-randevu.zip` → Etkinleştir.
4. **Site Bilgileri** ekranından telefon/WhatsApp/e-posta/adres/fotoğrafı girin.
5. **Görünüm › Düzenleyici** → üstteki logo alanından logoyu yükleyin.
6. **Randevular › Müsait günlerim** ile çalışma düzenini ayarlayın; **Ayarlar ve Apple Takvim**'den iPhone'a abone olun.
7. Önerilen ücretsiz eklentiler: **WP Mail SMTP** (e-postaların spam'e düşmemesi için), **Yoast SEO** veya **Rank Math** (site haritası + meta; tema otomatik uyum sağlar). Google Search Console'a site haritasını ekleyin.

## Notlar

- Saatler `Europe/Istanbul` ile hesaplanır; iCal'de UTC olarak verilir.
- Randevu formu önbellek eklentileriyle uyumludur (nonce yerine bal küpü + IP başına saatlik sınır + aynı saat için kilit).
- iCal tek yönlüdür (site → Apple Takvim). İki yönlü CalDAV senkronu bilinçli olarak yapılmadı (iCloud uygulamaya özel şifre gerektirir).
- Paket oluşturmak: `./build.sh`
