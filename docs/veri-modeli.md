# Veri modeli ve Next.js geçiş planı

WordPress'in `posts` ve `postmeta` tabloları **kullanılmaz**. İlanlar kendi tablolarımızdadır.
Tablo ve alan adları, ileride PostgreSQL'e (Supabase) birebir taşınacak şekilde seçildi.
Tablo adlarının önündeki `wp_` öneki WordPress'e aittir, geçişte kaldırılır.

## Tablolar

### `wp_ec_listings` – ilanlar
| Alan | Tür | Not |
|---|---|---|
| id | bigint | İlan numarası, 10001'den başlar |
| agent_id | bigint | → `ec_agents.id` |
| status | varchar | `draft`, `published`, `sold`, `passive` |
| mode | varchar | `sale`, `rent`, `daily` |
| ptype / subtype | varchar | Anahtarlar `data.php`'de (`konut` / `villa` …) |
| city / district | varchar | Şehir anahtarı (`girne`) ve bölge adı (`Alsancak`) |
| price / currency | decimal / char(3) | Emlakçının girdiği fiyat ve para birimi |
| price_gbp | decimal | Arama ve sıralama için GBP karşılığı (kur değişince yeniden hesaplanır) |
| area_m2, rooms, baths | | |
| deed_type | varchar | `turk-kocani`, `esdeger`, `tahsis`, `proje` |
| furnished | tinyint | 1 / 0 / boş |
| lat / lng | decimal | Harita için (ileride) |
| featured_until | datetime | Ücretli öne çıkarma bitiş tarihi (UTC). Slider bu ilanları gösterir |
| source | varchar | `panel`, `api`, `telegram`, `import`, `demo` |
| views | int | Görüntülenme sayısı |
| created_at / updated_at / published_at | datetime | UTC |

### `wp_ec_listing_i18n` – ilan metinleri
`(listing_id, lang)` birincil anahtar · `title`, `slug`, `description`.
Bir dilde metin yoksa site Türkçeye, o da yoksa ilk bulunan dile düşer.

### `wp_ec_photos` – fotoğraflar
`listing_id`, `sort` (0 = kapak), `path` (`10002/abcd1234`), `width`, `height`.
Dosyalar `wp-content/uploads/ilan-foto/{path}-l.webp` ve `-s.webp` olarak durur.

### `wp_ec_agents` – danışmanlar ve ofisler
`kind` (bireysel / kurumsal), `name`, `office`, `email`, `phone`, `whatsapp`, `city`,
`telegram_chat_id` (bot için), `api_key_hash` (anahtarın SHA-256 özeti), `status` (`pending` / `active` / `suspended`).

### `wp_ec_projects` – projeler
`name`, `slug`, `developer`, `city`, `district`, `units`, `delivery_date`, `price_min_gbp`, `price_max_gbp`, `payment_plan`, `desc_tr` / `desc_en` / `desc_ru`, `cover_path` (`proje-{no}/...`), `source`.

### `wp_ec_price_index` – fiyat endeksi
`(period, city, mode, ptype)` birincil anahtar · `median_m2_gbp`, `avg_price_gbp`, `n` (ilan sayısı), `is_demo`.
`period` = `2026-11`, `city` = şehir anahtarı veya `all`. Her gün bu ayın satırı yeniden hesaplanır; geçmiş aylar sabit kalır.

## Geçiş gecesi (WordPress → Next.js + Supabase)

Geçiş için ölçüt ilan sayısı değil. Şu işaretlerden biri görüldüğünde düşünülmeli:

- Site yavaşlıyor.
- Sunucu bot ve eklentiden gelen isteklere yetişemiyor.
- WordPress'te yapılması zor bir özellik gerekiyor.
- Gelir, Vercel ve Supabase giderlerini rahatça karşılıyor.

Adımlar:

1. Next.js sitesi önceden hazırlanır ve aynı link yapısını ve aynı `/api/v1` uçlarını sunar.
2. Bakım modu açılır.
3. Altı `wp_ec_*` tablosu dışa aktarılır (`wp db export --tables=...`) ve önek olmadan PostgreSQL'e yüklenir.
4. `uploads/ilan-foto/` klasörü olduğu gibi Supabase Storage'a kopyalanır.
5. DNS kaydı yeni sisteme çevrilir.

Linkler aynı kaldığı için 301 yönlendirmesi gerekmez ve Google sıralaması korunur.
Bot ve eklenti aynı API'yi gördüğü için onlarda değişiklik yapılmaz.

WordPress sabit sayfaları (Hakkımızda vb.) ve blog yazıları, istenirse `blog.` alt alan adında WordPress'te kalabilir.
