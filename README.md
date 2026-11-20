# AdaEv – Kıbrıs Emlak İlan Portalı

Türkçe, İngilizce ve Rusça yayın yapan emlak ilan sitesi. İlanlar emlakçılar için ücretsiz.
Gelir, Telegram botu ve tarayıcı otomasyonuyla 101evler ve hangiev'e ilan girme paketlerinden gelecek.

Site WordPress üzerinde çalışır. Ancak ileride Next.js altyapısına **bir gecelik bakımla** taşınabilecek şekilde kuruldu:

| Parça | Klasör | Görevi |
|---|---|---|
| **Emlak Core** eklentisi | `wordpress/wp-content/plugins/emlak-core/` | Kendi veri tabloları, TR/EN/RU link yapısı, `/api/v1` REST API, yönetim ekranları |
| **AdaEv** teması | `wordpress/wp-content/themes/adaev/` | Görünüm: slider'lı ana sayfa, arama, ilan detayı, projeler, ilan ver, favoriler |
| Belgeler | `docs/` | [Kurulum](docs/kurulum.md) · [API sözleşmesi](docs/api.md) · [Veri modeli ve geçiş planı](docs/veri-modeli.md) |

## Durum

- [x] 1a. Portal: veri tabloları, çok dilli linkler, arama, ilan detayı, slider, favoriler, kayıt başvurusu, reklam alanları, yönetim paneli, REST API
- [ ] 1b. Telegram botu ve yapay zekâ motoru (metinden alanları çıkarma, 3 dile çeviri)
- [ ] 2. Chrome eklentisi: 101evler ve hangiev modülleri
- [ ] 3. Paketler, kota takibi, ödeme

## Hızlı başlangıç

Plesk sunucusuna kurulum için [docs/kurulum.md](docs/kurulum.md) dosyasına bakın. Kısaca:

```bash
bash scripts/paketle.sh
```

Bu komut `dist/emlak-core.zip` ve `dist/adaev.zip` dosyalarını üretir. WordPress yönetiminden önce eklentiyi, sonra temayı yükleyip etkinleştirin.
