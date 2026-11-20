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

WP-CLI varsa (Plesk'te **WordPress → WP-CLI** veya SSH ile):

```bash
wp emlak seed
```

Bu komut 14 kurgusal ilan, 4 danışman, 4 proje ve örnek görseller ekler. Canlıya çıkmadan önce silin:

```bash
wp emlak unseed
```

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
| İlan ver | `/tr/ilan-ver/` | `/en/list-property/` | `/ru/razmestit/` |
| Favoriler | `/tr/favoriler/` | `/en/favourites/` | `/ru/izbrannoe/` |

Kök adres (`/`) ziyaretçinin tarayıcı diline göre `/tr/`, `/en/` veya `/ru/` adresine yönlenir.
