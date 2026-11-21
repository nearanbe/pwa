# API sözleşmesi – v1

Telegram botu ve Chrome eklentisi siteyle **yalnızca** bu API üzerinden konuşur.
Next.js'e geçişte aynı uçlar aynı cevaplarla yeniden yazılır, bot ve eklentide hiçbir şey değişmez.

- **Temel adres:** `https://SITE/api/v1/` (WordPress karşılığı: `/wp-json/emlak/v1/`)
- **Biçim:** JSON. Fotoğraf yüklemede `multipart/form-data`.
- **Dil:** Metin döndüren uçlarda `?lang=tr|en|ru` kullanılır. Varsayılan `tr`.
- **Kimlik doğrulama:** `Authorization: Bearer ec_...` (veya `X-Emlak-Key: ec_...`). Anahtar **Emlak → Danışmanlar** ekranından üretilir.
  Sitedeki Panelim sayfası aynı uçları WordPress oturumu ve `X-WP-Nonce` başlığıyla kullanır.
- **Onay kuralı:** Üye `status: "published"` gönderdiğinde ofis ve inşaat firması (onaylı hesap) için ilan yayına çıkar. Bireysel danışman ve mülk sahibi için ilan `pending` olur ve yönetici onayını bekler.
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

### `GET /index/compare?city=girne&mode=sale&area_m2=120&price=180000&currency=GBP`
İlanın m² fiyatının şehir medyanına göre farkı: `{"m2": 1500, "median": 1929, "diff": -22.2}`.

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
**Satılık ilanda yayına gönderirken `deed_type` zorunludur:** `turk-kocani`, `esdeger`, `tahsis`, `vakif`, `proje`, `diger`.
`status`: `draft` (varsayılan), `published`, `sold`, `passive`. Sunucu tarafından atananlar: `pending`, `rejected`.
Ek alanlar: `features` (dizi, `GET /meta` listesi), `vat_paid`, `transformer_paid` (true/false/null), `sea_distance_m`, `floor`, `total_floors`, `year_built`, `payment_plan`, `video_url`, `tour_url`, `lat`, `lng`.

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

## Panel ve proje uçları

| Uç | Açıklama |
|---|---|
| `PATCH /me` | Profil: `name`, `contact_person`, `phone`, `whatsapp`, `city`, `address`, `website`, `bio`, `license_no`, `slug`, `socials{instagram,facebook,tiktok,youtube,x,linkedin,telegram,whatsapp}`, `telegram_opt_in` |
| `POST /me/logo` | `multipart`, alan `logo` |
| `POST /me/telegram-code` | 10 dakika geçerli eşleştirme kodu ve `t.me/<bot>?start=<kod>` linki |
| `GET /me/projects` | Üyenin projeleri |
| `POST /projects` · `PATCH/DELETE /projects/{id}` | Proje: `name`, `city`, `district`, `developer`, `units`, `delivery_date` (YYYY-MM), `price_min_gbp`, `price_max_gbp`, `payment_plan`, `down_payment_pct`, `installments`, `progress`, `amenities[]`, `unit_types[{type,m2_min,m2_max,price_from_gbp,available}]`, `desc_tr/en/ru`, `video_url`, `lat`, `lng`, `status` |
| `POST /projects/{id}/cover` | `multipart`, alan `cover` |
| `POST /projects/{id}/media` | `multipart`: `file`, `kind` = `photo` / `plan` / `brochure` (PDF), `title` |
| `DELETE /projects/{id}/media/{mid}` | Dosyayı siler |
| `POST /review/{listing|agent|project}/{id}` | Yönetici: `{"action": "approve" | "reject" | "suspend" | "verify", "reason": "..."}` |

## 0.4 uçları

### Talepler ve bildirimler (herkese açık, hız sınırlı)
| Uç | Gövde | Açıklama |
|---|---|---|
| `POST /listings/{id}/lead` | `name`, `phone` veya `email`, `message`, `consent: 1` (+ Turnstile açıksa `cf-turnstile-response`) | İlan sahibine e-posta (bağlıysa Telegram), yöneticiye kopya. IP başına saatte 6. |
| `POST /projects/{id}/lead` | aynı | Proje sahibine |
| `POST /listings/{id}/report` | `reason` (`sold`, `price`, `photos`, `location`, `duplicate`, `fraud`, `other`), `note`, `email` | Yöneticiye bildirim, onay panelinde listelenir |
| `GET /nearby?lat=&lng=&lang=` | | Yakındaki yerler (OpenStreetMap Overpass, 2 km, 30 gün önbellek): `{"radius":2000,"categories":[{"key":"health","name":"Sağlık","icon":"✚","items":[{"name":"…","dist":"350 m","m":350,"lat":..,"lng":..}]}]}` |

### Üye (anahtar ya da oturum)
| Uç | Açıklama |
|---|---|
| `GET /me/leads` | `{"items":[{id,name,email,phone,message,lang,status,created_at,about:{type,id,title,url}}],"unread":2}` |
| `PATCH /me/leads/{id}` | `{"status":"read"\|"done"\|"spam"}` |

### Yönetici
| Uç | Açıklama |
|---|---|
| `GET /reports` · `PATCH /reports/{id}` | Açık hatalı ilan bildirimleri · `{"status":"closed"}` |

### Telegram
`POST /telegram/webhook` — yalnızca Telegram çağırır; `X-Telegram-Bot-Api-Secret-Token` başlığı Ayarlar'daki gizli değerle eşleşmeli. Webhook **Emlak → Ayarlar → Araçlar → Telegram webhook'unu kur** ile kaydedilir.

### Chrome eklentisi (üye API anahtarıyla)
| Uç | Açıklama |
|---|---|
| `GET /ext/config` | Site modülleri: `sites.{101evler,hangiev}.{name,new_url,fields{alan:{selectors[],labels[],map{},after,wait}},sms,success}` |
| `GET /ext/listing/{id}` | Tam ilan + `i18n` (3 dil) + `feature_labels` + `syndication` |
| `GET /ext/syndication` · `POST /ext/syndication` | `{"listing_id","site":"101evler"\|"hangiev","status":"filled"\|"published"\|"failed"\|"removed","remote_url","remote_id"}` |
| `POST /ext/sms-request` | `{"site":"101evler"}` → bot üyeye kodu sorar (Telegram bağlı olmalı; değilse 409) |
| `GET /ext/sms-code` | `{"code":"482913","site":"101evler"}` bir kez döner; yoksa `{"code":null,"waiting":true}` |

### Proje alanları (0.4)
`project_type` (`konut`, `villa`, `karma`, `ticari`, `tatil`), `build_status` (`planned`, `ongoing`, `completed`; boşsa ilerlemeden hesaplanır), `land_m2`. Kat planı dosyasının başlığı daire tipi adıyla (ör. `2+1`) eşleşirse proje sayfasında o daire kartında gösterilir.

## 0.5 uçları

| Uç | Açıklama |
|---|---|
| `GET /me/packages` | Üyenin geçerli paketleri: `{"items":[{id,name,features[],quota,used,left,ends_at,days_left}],"can":{"syndication":true,"social":false,"telegram":true}}` |
| `GET /me/social` · `PATCH /me/social` | Sosyal medya: bağlı hesaplar, son paylaşımlar; ayar: `{"auto":true,"lang":"tr"\|"en"\|"ru"\|"tr+en"\|"all","sign":"Ad · telefon"}` |
| `POST /listings/{id}/share` | İlanı üyenin (yöneticiyse tüm) bağlı hesaplarında paylaşım sırasına alır. Paket yoksa `402`. |

Paket ve kota kuralları:
- `GET /ext/listing/{id}` (Chrome eklentisi) çağrısı kotadan **1 ilan** düşer. Aynı ilan aynı pakette bir kez sayılır; kota dolunca `402 ec_no_package` döner.
- Telegram botu, Ayarlar'da "paket gereksin" açıksa paketsiz üyeye ilan hazırlamaz.
- Sosyal medya paylaşımı her zaman paket ister. Sitenin kendi hesabına paylaşım ücretsizdir.

## 0.6 uçları

| Uç | Açıklama |
|---|---|
| `GET /listings/{id}/panos` | 360° fotoğraflar: `[{"id","title","url","thumb","width","height"}]` (`url` = en çok 6000 px WebP) |
| `POST /listings/{id}/panos` | `multipart`: `pano` (dosya, genişlik = 2 × yükseklik, en az 1600 px), `title` (oda adı). İlan başına en çok 15. Oran tutmazsa `400 ec_pano_ratio`. |
| `PATCH /listings/{id}/panos/{pid}` · `PUT /listings/{id}/panos/order` · `DELETE /listings/{id}/panos/{pid}` | Oda adı, sıra, silme |
| `GET /listings?pano=1` | Sadece 360° fotoğraflı ilanlar |

İlan nesnesine eklenenler: `pano_count` (her yerde) ve `panos` (sadece detayda).

**Tapu türü kuralı (0.6):** `deed_type` sadece `mode: "sale"` ilanlarda saklanır ve döner. Kiralık ve günlük ilanda gönderilen değer yok sayılır, `deed_type` / `deed_name` her zaman `null` döner. Aramada kiralık için `deed_type` filtresi uygulanmaz.

## 0.7 uçları

| Uç | Açıklama |
|---|---|
| `GET /listings/{id}/duplicates` | (ilan sahibi) `{"items":[{"id","photos":2,"text":true,"same_agent":false,"title","url","status","agent"}],"message":"Buna benzer bir ilan zaten yayında: …"}` |

`GET /listings` yeni filtreler:
- `rooms[]` (çoklu), `amin` / `amax` (m²), `baths` (en az), `furnished` (`0` / `1`)
- `feat[]` (özelliklerin hepsi), `kind[]` (`ofis`, `insaat`, `danisman`, `sahibi`), `days` (`1`, `3`, `7`, `30`)
- `q` (başlık/açıklama/bölge/ilan no), `sort=m2_asc|area_desc`

İlan nesnesine eklenenler: `photo_count`, `lat`, `lng` (liste cevabında da).


## İlan videosu (1.0.4)

| Yöntem | Adres | Açıklama |
|---|---|---|
| POST | `/api/v1/listings/{id}/video` | `video` alanında MP4/WebM/MOV dosyası. İlanın klasörüne kaydedilir, `video_url` bu dosyanın adresi olur. Yanıt: `{url, info}` |
| DELETE | `/api/v1/listings/{id}/video` | `video_url` boşaltılır; yüklenmiş dosya silinir |

Bağlantı eklemek için ilanın `video_url` alanı PATCH ile güncellenir. YouTube ve Vimeo sayfada gömülü oynatılır, `.mp4`/`.webm`/`.mov` adresleri `<video>` ile oynatılır, diğer bağlantılar düğme olarak gösterilir.


## Vitrin (1.1)

| Yöntem | Adres | Açıklama |
|---|---|---|
| POST | `/api/v1/listings/{id}/promote` | `{kind}`: `featured`, `home`, `cat`, `urgent` ya da `bump`. Üye kendi ilanına uygular, 1 kredi düşer. Yönetici kredisiz uygular (`days` verilebilir). |
| GET | `/api/v1/me/promos` | Kalan krediler ve tür adları |
| GET | `/api/v1/listings?…` | Yanıttaki her ilanda `urgent`, `showcase_home` ve `showcase_cat` alanları var |

## Paket satın alma (1.1)

| Yöntem | Adres | Açıklama |
|---|---|---|
| GET | `/api/v1/me/orders` | Siparişler, kullanılabilir ödeme yöntemleri, banka bilgisi |
| POST | `/api/v1/me/orders` | `{package_id, method: bank\|card}`. `card` ise yanıtta `pay.action` (banka adresi) ve `pay.fields` (imzalı form alanları) gelir; tarayıcı bu formu bankaya POST eder |
| POST | `/api/v1/me/orders/{id}/receipt` | `receipt` alanında JPG, PNG ya da PDF dekont |
| POST | `/api/v1/pay/nestpay` | Bankanın dönüş adresi. İmza (hash ver3, SHA-512), `mdStatus`, `ProcReturnCode=00` ve tutar doğrulanır; doğruysa paket tanımlanır |

## Ofis ekibi (1.1)

| Yöntem | Adres | Açıklama |
|---|---|---|
| GET | `/api/v1/me/team` | Ofis için ekip üyeleri ve bekleyen davetler; danışman için gelen davet ve bağlı olduğu ofis |
| POST | `/api/v1/me/team/invite` | `{email}` (ofis) |
| POST | `/api/v1/me/team/accept` · `decline` · `leave` | Danışman |
| POST | `/api/v1/me/team/remove` · `cancel` | `{member}` (ofis) |

## Proje videosu (1.1)

`POST` ve `DELETE` `/api/v1/projects/{id}/video`: ilan videosuyla aynı kurallar.
