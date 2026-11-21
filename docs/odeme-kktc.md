# KKTC'de online ödeme seçenekleri (Kasım 2026)

AdaCasa 1.1'de iki ödeme yolu hazırdır. İkisi de **Emlak → Paketler → Ödeme ayarları** bölümünden açılır.

| Yol | Durum | Ne gerekir |
|---|---|---|
| **Havale / EFT** | Hemen kullanılabilir | Banka adı, hesap sahibi ve IBAN girilir. Üye siparişi açar, dekontu yükler; siz "Ödendi — paketi ver" dersiniz. |
| **Kartla ödeme (sanal POS)** | Kod hazır, banka sözleşmesi gerekir | KKTC bankasından **NestPay / Cardplus (Payten)** altyapılı sanal POS: Client ID, Store Key, 3D ödeme adresi. |

## 1. KKTC bankalarının sanal POS'u (önerilen)

- KKTC'deki bankalar, örneğin **Near East Bank (Yakın Doğu Bankası)**, **Creditwest**, **Koopbank** ve **Vakıflar Bankası**, internet tahsilatı için sanal POS veriyor.
- Bu POS'ların çoğu **Cardplus / NestPay (Payten, eski adı Asseco)** altyapısında çalışıyor.
  - Cardplus'ı İş Bankası, Akbank, Ziraat gibi NestPay tabanlı bankalarla aynı listede gösteren açık kaynak bir entegrasyon kütüphanesi var.
  - Near East Bank'ın sanal POS eklentisinin NestPay API ile yapıldığını ve TRY, USD, EUR ve GBP desteklediğini belirten kaynaklar var.
- AdaCasa'da **3D Pay Hosting** modeli kullanılır:
  - Kart bilgisi sitemize hiç gelmez; üye bankanın 3D Secure sayfasında öder.
  - Bankanın döndürdüğü yanıt **SHA-512 (hash ver3)** imzasıyla doğrulanır.
  - NestPay bankaları 15.03.2024'ten beri ver3 imza istiyor; eski imzayla ödeme alınamıyor.
- Para birimi ilan sitesinin fiyatına göre gönderilir: EUR 978, GBP 826, USD 840, TRY 949. Bankanın hangi para birimlerini açtığını sorun.

**Bankaya başvururken sorulacaklar:**
1. "E-ticaret sanal POS, **3D Pay Hosting** modeli, NestPay/Cardplus altyapısı" isteyin.
2. Test bilgileri: test mağaza numarası, mağaza anahtarı ve test ödeme adresi (`…/fim/est3Dgate`).
3. Komisyon oranı, kurulum ve aylık ücret, paranın hesaba geçme süresi. Kaynaklarda kurulum için yaklaşık 200 $ ve aylık 20 $ geçiyor, ancak eski bir sayfa; güncel fiyatı bankaya sorun.
4. Bankaya dönüş adresi olarak şunu verin: `https://adacasa.com/api/v1/pay/nestpay`
5. Hangi para birimleriyle tahsilat yapılabildiği.

Banka bilgileri geldikten sonra **Emlak → Paketler → Ödeme ayarları → Kartla ödeme** bölümüne girilir. Önce bankanın test kartıyla küçük bir ödeme denenir.

## 2. Türkiye ve uluslararası sağlayıcılar

| Sağlayıcı | KKTC şirketi için durum |
|---|---|
| iyzico, PayTR | Türkiye'de kurulu şirket ve vergi kaydı istiyor. Bir forum kaydında PayTR'nin KKTC'li müşteri kabul etmediği yazıyor. KKTC şirketiyle uygun değil. |
| Stripe | Şirketin kurulu olduğu ülkeye bağlı. Kıbrıs Cumhuriyeti (güney) destekleniyor, KKTC desteklenmiyor. Güney Kıbrıs ya da İngiltere'de şirket gerekir. |
| PayPal | KKTC için güvenilir bir para alma desteği bulunamadı. |
| MayPay / Hepi | KKTC'de yerel elektronik para kuruluşu olarak tanıtılıyor. İşyeri ve online tahsilat API'si olup olmadığı doğrulanamadı; doğrudan sorulabilir. |

## 3. Öneri

1. **Şimdi:** Havale/EFT'yi açın (IBAN girin). Paketler online satılmaya başlar, kod hazır.
2. **Paralel olarak:** Hesabınızın olduğu KKTC bankasından NestPay/Cardplus 3D Pay Hosting sanal POS'u isteyin. Bilgiler gelince "Kartla ödeme"yi açın.
3. İleride yurt dışı müşteri için Stripe gerekirse ayrı bir şirket yapısı değerlendirilebilir; kod tarafı eklenebilir.

## Kaynaklar

- [Koopbank — Sanal POS](https://www.koopbank.com/index.php?option=com_content&view=article&id=120&Itemid=672&lang=tr)
- [Vakıflar Bankası (KKTC) — Sanal POS](https://vakiflarbankasi.com/page-sanal-pos-79)
- [Card Plus — E-commerce](https://card-plus.net/en/services/e-commerce/)
- [Near East Bank WooCommerce Sanal POS (Cardplus)](https://www.wpmatik.com/urun/near-east-bank-woocommerce-sanal-pos)
- [Gri Teknoloji — Cardplus sanal POS modülü](https://www.gri.net/wordpress-eklentileri/cardplus-woocommerce-sanal-pos-modulu)
- [alegraio/omnipay-nestpay (NestPay bankaları ve Cardplus)](https://github.com/alegraio/omnipay-nestpay)
- [CP.VPOS — hash v3 notu](https://github.com/erdemaytek/CP.VPOS)
- [Kıbrıs Sanal POS — Figensoft](https://www.figensoft.com/blog/kibris-sanal-pos)
- [MayPay](https://maypay.net/)
- [R10.net — KKTC şirket kurulumu ve sanal POS](https://www.r10.net/proje-gelistirme-beyin-firtinasi-ortak-ariyorum/3483358-kktc-sirket-kurulumu-sanal-pos.html)
- [Stripe — Cyprus launch](https://stripe.com/newsroom/news/stripe-launches-in-five-more-european-countries)
- [iyzico vs PayTR vs Stripe](https://fastart.co/en/blog/iyzico-vs-paytr-vs-stripe-for-turkish-merchants)
