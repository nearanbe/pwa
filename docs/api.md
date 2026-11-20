# API sözleşmesi – v1

Telegram botu ve Chrome eklentisi siteyle **yalnızca** bu API üzerinden konuşur.
Next.js'e geçişte aynı uçlar aynı cevaplarla yeniden yazılır, bot ve eklentide hiçbir şey değişmez.

- **Temel adres:** `https://SITE/api/v1/` (WordPress karşılığı: `/wp-json/emlak/v1/`)
- **Biçim:** JSON. Fotoğraf yüklemede `multipart/form-data`.
- **Dil:** Metin döndüren uçlarda `?lang=tr|en|ru` kullanılır. Varsayılan `tr`.
- **Kimlik doğrulama:** `Authorization: Bearer ec_...` (veya `X-Emlak-Key: ec_...`). Anahtar **Emlak → Danışmanlar** ekranından üretilir.
- **Hatalar:** `{"code": "...", "message": "...", "data": {"status": 400, "fields": {"price": "zorunlu"}}}`

## Herkese açık uçlar

### `GET /meta`
Form doldurmak için gereken sözlükler: şehirler ve bölgeler, emlak tipleri, oda seçenekleri, tapu türleri, para birimleri ve kurlar.
Bot, yapay zekânın çıkardığı alanları bu listeyle eşleştirir.

### `GET /listings`
Yalnızca yayındaki ilanlarda arama yapar.

| Parametre | Örnek | Açıklama |
|---|---|---|
| `mode` | `sale` / `rent` / `daily` | İlan türü |
| `ptype`, `subtype` | `konut`, `villa` | Emlak tipi |
| `city`, `district` | `girne`, `Alsancak` | Konum |
| `rooms` | `2+1` | Oda |
| `min`, `max`, `currency` | `100000`, `200000`, `GBP` | Fiyat aralığı (seçili para biriminde) |
| `ids` | `10001,10004` | Belirli ilanlar |
| `agent_id` | `3` | Danışmanın ilanları |
| `sort` | `new` / `price_asc` / `price_desc` | Sıralama |
| `page`, `per_page` | `1`, `24` | Sayfalama (en çok 60) |

Cevap: `{"items": [İlan], "total": 42, "page": 1, "pages": 2}`

### `GET /listings/{id}`
İlan detayı (yayında veya satıldı durumunda). Sahibi anahtarıyla taslak ilanını da görebilir.

### `POST /agents/apply`
Sitedeki kayıt formu: `{"name", "email", "phone", "kind": "bireysel|kurumsal", "company_type", "contact_person", "city"}`.
Aynı IP'den saatte en çok 5 başvuru kabul edilir.

## Anahtar gerektiren uçlar

### `POST /listings`
Yeni ilan oluşturur, cevap olarak `201` ve tam ilanı döndürür.

```json
{
  "mode": "sale",
  "ptype": "konut",
  "subtype": "daire",
  "city": "girne",
  "district": "Alsancak",
  "price": 145000,
  "currency": "GBP",
  "area_m2": 95,
  "rooms": "2+1",
  "baths": 1,
  "deed_type": "turk-kocani",
  "furnished": false,
  "status": "draft",
  "source": "telegram",
  "i18n": {
    "tr": {"title": "Alsancak deniz manzaralı 2+1", "description": "..."},
    "en": {"title": "Sea view 2-bed in Alsancak", "description": "..."},
    "ru": {"title": "Квартира 2+1 с видом на море", "description": "..."}
  }
}
```

Zorunlu alanlar: `mode`, `ptype`, `city`, `price` ve en az bir dilde `i18n.*.title`.
`status`: `draft` (varsayılan), `published`, `sold`, `passive`.

### `PATCH /listings/{id}`
Gönderilen alanları günceller. Örnekler: `{"price": 139000}`, `{"status": "sold"}`, `{"i18n": {"en": {...}}}`.

### `DELETE /listings/{id}`
İlanı pasife alır (silmez).

### `POST /listings/{id}/photos`
`multipart/form-data`, alan adı `photos[]`, birden fazla dosya olabilir. JPG, PNG veya WebP kabul edilir, en çok 30 fotoğraf.
Her fotoğraf 1600 px (`large`) ve 640 px (`small`) boyutunda WebP'ye çevrilir.
Cevap: `{"added": [Fotoğraf], "errors": []}`

### `PUT /listings/{id}/photos/order`
`{"order": [43, 41, 42]}`. İlk sıradaki fotoğraf kapak olur.

### `DELETE /listings/{id}/photos/{photoId}`

### `GET /me` · `GET /me/listings`
Anahtarın sahibi danışman, ilan sayıları ve tüm durumlardaki ilanları.

## İlan nesnesi

```json
{
  "id": 10002,
  "status": "published",
  "mode": "sale",
  "ptype": "konut", "ptype_name": "Konut",
  "subtype": "villa", "subtype_name": "Villa",
  "city": "girne", "city_name": "Girne", "district": "Lapta",
  "price": 385000, "currency": "GBP", "price_gbp": 385000,
  "area_m2": 240, "rooms": "4+1", "baths": 3,
  "deed_type": "turk-kocani", "deed_name": "Türk Koçanı",
  "furnished": false,
  "featured": true,
  "title": "Havuzlu müstakil villa",
  "url": "https://SITE/tr/ilan/havuzlu-mustakil-villa-10002/",
  "cover": {"id": 4, "large": "...-l.webp", "small": "...-s.webp", "width": 1600, "height": 1000},
  "agent": {"id": 2, "name": "Kerem Aksoy", "office": "Zeytin Property", "phone": "+90533...", "whatsapp": "90533..."},
  "published_at": "2026-11-20 20:10:00",

  "description": "… (yalnızca detayda)",
  "photos": ["… (yalnızca detayda)"],
  "translations": {"tr": {"title": "...", "url": "..."}, "en": {}, "ru": {}}
}
```
