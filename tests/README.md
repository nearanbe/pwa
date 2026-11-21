# Testler (yerel test ortamında)

Kurulum için `docs/devam-notu.md` → "Yerel test ortamı". Dış servisler bu ortamdan erişilemediği için taklit edilir.

| Dosya | Ne yapar | Çalıştırma |
|---|---|---|
| `backend-test.php` | Telegram akışı (eşleştirme, toplama, hazırla, düzelt, yayınla, yönetici onayı), Claude isteği, SMS kodu, eklenti uçları, yakın yerler, gün içi kur. Telegram/Claude/Overpass/Yahoo `pre_http_request` ile taklit edilir. | `wp transient delete --all && wp eval-file tests/backend-test.php` (taze demo verisiyle) |
| `interact.js` | İlan ve proje sayfasında galeri, sabit başlık, not, mesaj formu, hatalı ilan bildirimi, taksit hesabı, yazdırma | `node tests/interact.js <ilan-url> <proje-url>` |
| `exttest.js` + `mock101.html` | Chrome eklentisini gerçek Chromium'a yükleyip sahte 101evler formunu doldurur | eklentinin bir kopyasına `http://localhost:8080/*` izni ekleyin, `mock101.html` dosyasını site köküne koyun, `ec_ext_config` içinde `new_url`'yi o adrese çevirin, sonra `node tests/exttest.js <eklenti-kopyası> <ilan-no> <api-anahtarı>` |
| `shot.js` | Sayfaların ekran görüntüsü ve konsol hataları | `node tests/shot.js <klasör> '[["ad","url",genişlik]]'` |
