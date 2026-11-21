# AdaEv – Kıbrıs Emlak İlan Portalı

Türkçe, İngilizce ve Rusça yayın yapan emlak ilan sitesi. İlanlar emlakçılar için ücretsiz.
Gelir, Telegram botu ve tarayıcı otomasyonuyla 101evler ve hangiev'e ilan girme paketlerinden gelecek.

Site WordPress üzerinde çalışır. Ancak ileride Next.js altyapısına **bir gecelik bakımla** taşınabilecek şekilde kuruldu:

| Parça | Klasör | Görevi |
|---|---|---|
| **Emlak Core** eklentisi | `wordpress/wp-content/plugins/emlak-core/` | Kendi veri tabloları, TR/EN/RU link yapısı, `/api/v1` REST API, yönetim ekranları |
| **AdaEv** teması | `wordpress/wp-content/themes/adaev/` | Görünüm: slider'lı ana sayfa, arama, ilan detayı, projeler, ilan ver, favoriler |
| Chrome eklentisi | `extension/` | 101evler ve hangiev formlarını emlakçının tarayıcısında doldurur |
| Belgeler | `docs/` | [Kurulum](docs/kurulum.md) · [Hız](docs/hiz.md) · [Sosyal medya](docs/sosyal-medya-kurulum.md) · [API sözleşmesi](docs/api.md) · [Veri modeli ve geçiş planı](docs/veri-modeli.md) |

## Durum

- [x] 1a. Portal: veri tabloları, çok dilli linkler, arama, ilan detayı, slider, favoriler, kayıt başvurusu, reklam alanları, yönetim paneli, REST API
- [x] 1a+. Otomatik kur, fiyat endeksi, projeler (liste, detay, yönetim), blog, logo ve site ikonları, panelden demo verisi
- [x] 0.3. Üyelik (4 hesap türü, e-posta doğrulama, Turnstile), Panelim (adım adım ilan formu, proje formu, profil, sosyal hesaplar), onay paneli, ofis vitrinleri ve alt alan adları, KKTC Merkez Bankası kurları, zorunlu tapu türü, Cloudflare temsili görsel üretimi
- [x] 0.4. Telegram botu ve yapay zekâ motoru (metinden alanları çıkarma, 3 dile çeviri, önizleme, Telegram'dan onay), 101evler'e göre eksikler (tam ekran galeri, harita, yakın yerler, fiyat trendi, mesaj formu, paylaş, hatalı ilan bildir, not, yazdır, proje sayfası, şehir kartları, firmalar dizini), gün içi kur, canlıya çıkış kontrolü
- [x] 2. Chrome eklentisi: 101evler ve hangiev modülleri (`extension/`; seçiciler gerçek formla kesinleştirilecek)
- [x] 0.5. Paketler ve kota (yönetici kontrolünde), Facebook sayfası ve Instagram'da otomatik paylaşım (sitenin ve üyelerin hesapları), hız (sayfa önbelleği, Cloudflare temizleme, yerel yazı tipleri; mobil Lighthouse 96–97)
- [ ] 3. Online ödeme (şimdilik paketler elle verilir)

## Hızlı başlangıç

Plesk sunucusuna kurulum için [docs/kurulum.md](docs/kurulum.md) dosyasına bakın. Kısaca:

```bash
bash scripts/paketle.sh
```

Bu komut `dist/emlak-core.zip`, `dist/adaev.zip` ve `dist/ilan-aktarici.zip` (Chrome eklentisi) dosyalarını üretir. WordPress yönetiminden önce eklentiyi, sonra temayı yükleyip etkinleştirin.
