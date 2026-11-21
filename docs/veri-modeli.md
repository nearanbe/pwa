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
| status | varchar | `draft`, `pending` (onay bekliyor), `published`, `rejected`, `sold`, `passive` |
| mode | varchar | `sale`, `rent`, `daily` |
| ptype / subtype | varchar | Anahtarlar `data.php`'de (`konut` / `villa` …) |
| city / district | varchar | Şehir anahtarı (`girne`) ve bölge adı (`Alsancak`) |
| price / currency | decimal / char(3) | Emlakçının girdiği fiyat ve para birimi |
| price_gbp | decimal | Arama ve sıralama için GBP karşılığı (kur değişince yeniden hesaplanır) |
| area_m2, rooms, baths | | |
| deed_type | varchar | `turk-kocani`, `esdeger`, `tahsis`, `vakif`, `proje`, `diger` (satılıkta yayın için zorunlu) |
| features | text | JSON dizi: `["deniz_manzarasi","ozel_havuz"]` |
| vat_paid / transformer_paid | tinyint | KDV / trafo ödendi mi (1/0/boş) |
| sea_distance_m, floor, total_floors, year_built | | |
| payment_plan, video_url, tour_url | varchar | |
| project_id | bigint | İlan bir projeye aitse |
| reject_reason | varchar | Yönetici red sebebi |
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

### `wp_ec_agents` – üyeler (ofis, inşaat firması, danışman, mülk sahibi)
`wp_user_id` (WordPress kullanıcısı, rol `ec_member`), `kind` (`ofis` / `insaat` / `danisman` / `sahibi`), `name`, `office`, `contact_person`,
`slug` (vitrin ve alt alan adı), `logo_path`, `bio`, `license_no`, `verified` (doğrulanmış rozeti), `address`, `website`, `socials` (JSON),
`email`, `phone`, `whatsapp`, `city`, `email_verified`, `verify_token`, `consent_at`,
`telegram_opt_in`, `telegram_chat_id`, `telegram_username`, `telegram_link_code`, `telegram_link_expires`,
`api_key_hash` (anahtarın SHA-256 özeti), `status` (`pending` / `active` / `suspended`).

### `wp_ec_projects` – projeler
`status`, `name`, `slug`, `developer`, `city`, `district`, `units`, `delivery_date`, `price_min_gbp`, `price_max_gbp`, `payment_plan`, `down_payment_pct`, `installments`, `progress`, `amenities` (JSON), `unit_types` (JSON), `video_url`, `lat`, `lng`, `desc_tr` / `desc_en` / `desc_ru`, `cover_path` (`proje-{no}/...`), `cover_ai` (temsili görsel), `reject_reason`, `source`.

Ek alanlar (0.4): `project_type`, `build_status` (`planned` / `ongoing` / `completed`), `land_m2` (proje alanı).

### `wp_ec_project_media` – proje galerisi, kat planları, broşür
`project_id`, `kind` (`photo` / `plan` / `brochure`), `path`, `title`, `width`, `height`, `sort`.

### `wp_ec_price_index` – fiyat endeksi
`(period, city, mode, ptype)` birincil anahtar · `median_m2_gbp`, `avg_price_gbp`, `n` (ilan sayısı), `is_demo`.
`period` = `2026-11`, `city` = şehir anahtarı veya `all`. Her gün bu ayın satırı yeniden hesaplanır; geçmiş aylar sabit kalır.

### `wp_ec_leads` – talepler (0.4)
`agent_id`, `listing_id` veya `project_id`, `name`, `email`, `phone`, `message`, `lang`, `status` (`new` / `read` / `done` / `spam`), `ip_hash`, `created_at`.

### `wp_ec_reports` – hatalı ilan bildirimleri (0.4)
`listing_id`, `reason`, `note`, `email`, `status` (`open` / `closed`), `ip_hash`, `created_at`.

### `wp_ec_tg_drafts` – Telegram taslakları (0.4)
Sohbet başına açık taslak: `chat_id`, `agent_id`, `state` (`collecting` / `processing` / `preview` / `editing` / `done` / `cancelled`), `texts` (JSON), `photos` (JSON: Telegram file_id), `data` (JSON: yapay zekâ çıktısı), `listing_id`, `preview_msg_id`, `error`.

### `wp_ec_syndication` – diğer sitelere aktarım (0.4)
`(listing_id, site)` benzersiz · `agent_id`, `site` (`101evler` / `hangiev`), `status` (`filled` / `published` / `failed` / `removed`), `remote_url`, `remote_id`, `note`. Paketlerde kota sayımı bu tablodan yapılacak.

### `wp_ec_packages` – paket tanımları (0.5)
`name`, `days`, `quota` (0 = sınırsız), `features` (`syndication,social,telegram`), `price` (bilgi amaçlı metin), `active`, `sort`.

### `wp_ec_subscriptions` – üyeye verilen paketler (0.5)
`agent_id`, `package_id`, `name`, `features`, `quota` (verilirken kopyalanır; tanım değişse de verilen paket değişmez), `starts_at`, `ends_at`, `status` (`active` / `expired` / `cancelled`), `note`, `warned` (3 gün kala uyarı gönderildi).

### `wp_ec_usage` – kota kullanımı (0.5)
`(subscription_id, listing_id)` benzersiz · `agent_id`, `feature`. Aynı ilan aynı pakette bir kez sayılır.

### `wp_ec_social_accounts` – Facebook sayfaları ve bağlı Instagram (0.5)
`page_id` benzersiz · `agent_id` (`NULL` = atanmadı, `0` = sitenin kendisi, diğerleri üye), `page_name`, `ig_user_id`, `ig_username`, `token_enc` (AES-256-GCM ile şifreli sayfa anahtarı), `fb_on`, `ig_on`, `status` (`ok` / `expired`), `last_error`.

### `wp_ec_social_posts` – paylaşımlar (0.5)
`(listing_id, account_id, network)` benzersiz · `network` (`facebook` / `instagram`), `status` (`queued` / `done` / `failed` / `skipped`), `attempts`, `remote_id`, `permalink`, `error`.

Üyeler tablosuna 0.5'te eklenenler: `social_auto`, `social_lang` (`tr+en` varsayılan), `social_sign`.

### `wp_ec_panos` – 360° fotoğraflar (0.6)
`listing_id`, `title` (oda adı), `path` (`{ilan}/360-xxxx` → `-p.webp` en çok 6000 px, `-t.webp` küçük resim; paylaşım için `-fb.jpg` ve `-ig.jpg` gerektiğinde üretilir), `width`, `height`, `sort`.

`wp_ec_listings.deed_type` 0.6'dan itibaren sadece `mode = 'sale'` ilanlarda doludur.

## Geçiş gecesi (WordPress → Next.js + Supabase)

Geçiş için ölçüt ilan sayısı değil. Şu işaretlerden biri görüldüğünde düşünülmeli:

- Site yavaşlıyor.
- Sunucu bot ve eklentiden gelen isteklere yetişemiyor.
- WordPress'te yapılması zor bir özellik gerekiyor.
- Gelir, Vercel ve Supabase giderlerini rahatça karşılıyor.

Adımlar:

1. Next.js sitesi önceden hazırlanır ve aynı link yapısını ve aynı `/api/v1` uçlarını sunar.
2. Bakım modu açılır.
3. On yedi `wp_ec_*` tablosu dışa aktarılır (`wp db export --tables=...`) ve önek olmadan PostgreSQL'e yüklenir.
4. `uploads/ilan-foto/` klasörü olduğu gibi Supabase Storage'a kopyalanır.
5. DNS kaydı yeni sisteme çevrilir.

Linkler aynı kaldığı için 301 yönlendirmesi gerekmez ve Google sıralaması korunur.
Bot ve eklenti aynı API'yi gördüğü için onlarda değişiklik yapılmaz.

WordPress sabit sayfaları (Hakkımızda vb.) ve blog yazıları, istenirse `blog.` alt alan adında WordPress'te kalabilir.
