# AdaVira: yeni yazıları Facebook ve Instagram'da otomatik paylaşma (n8n)

**Nasıl çalışır:** n8n her 15 dakikada bir `https://adavira.com/feed` adresini okur. Yeni bir Blog ya da Haber
yazısı görünce:
- **Facebook sayfasında** başlık, özet ve yazının bağlantısıyla paylaşır. Facebook bağlantıdan kapak görselli kartı kendisi oluşturur.
- **Instagram'da** kapak görseli, başlık, özet ve hashtag'lerle gönderi yayınlar.
  Görsel tema tarafından Instagram'ın kabul ettiği JPEG formatında hazırlanır (tema 1.2.2 ve üstü).

Gereken: tema **1.2.2+**, n8n, Instagram **İşletme/İçerik üreticisi** hesabı (Facebook sayfasına bağlı).

---

## 1. Instagram'ı Facebook sayfasına bağlayın
1. Instagram uygulaması → Profil → ☰ → **Hesap türü ve araçlar** → **Profesyonel hesaba geç** → **İşletme**.
2. Facebook sayfanız → **Ayarlar → Bağlı hesaplar → Instagram** → hesabınızı bağlayın.

## 2. Meta geliştirici uygulaması oluşturun (bir kez)
1. **developers.facebook.com** → giriş yapın → **Uygulamalarım → Uygulama oluştur**.
2. Kullanım amacı olarak **"Diğer"**, tür olarak **"İşletme" (Business)** seçin. Ad: `AdaVira Paylaşım`.
3. Uygulama **Geliştirme modunda** kalabilir. Uygulamanın yöneticisi siz olduğunuz için kendi sayfanıza
   paylaşım yapmak için Meta incelemesi gerekmez.
4. **Uygulama ayarları → Temel** sayfasından **Uygulama No** (App ID) ve **Uygulama Gizli Anahtarı**nı
   (App Secret) bir kenara not edin. Bunları kimseyle paylaşmayın.

## 3. Süresi dolmayan sayfa erişim anahtarını alın
1. **developers.facebook.com/tools/explorer** (Graph API Explorer) adresini açın.
2. Sağda **Meta App:** `AdaVira Paylaşım` seçin. **User or Page:** "User Token".
3. **Permissions** listesine şunları ekleyin:
   `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `instagram_basic`,
   `instagram_content_publish`, `business_management`
4. **Generate Access Token** → Facebook izin penceresinde AdaVira sayfasını ve Instagram hesabını seçip onaylayın.
5. Çıkan kısa süreli anahtarı uzun süreliye çevirin. Tarayıcıda şu adresi açın (köşeli parantezleri kendi
   değerlerinizle değiştirin):
   ```
   https://graph.facebook.com/v27.0/oauth/access_token?grant_type=fb_exchange_token&client_id=[APP_ID]&client_secret=[APP_SECRET]&fb_exchange_token=[KISA_ANAHTAR]
   ```
   Yanıttaki `access_token` değeri **uzun süreli kullanıcı anahtarı**dır.
6. Explorer'da bu uzun süreli anahtarı kutuya yapıştırın ve şu sorguyu çalıştırın: `me/accounts`
   Listede AdaVira sayfasının **`id`** değerini (= **Sayfa ID**) ve **`access_token`** değerini
   (= **süresi dolmayan sayfa anahtarı**) not edin.
7. Explorer'da şu sorguyu çalıştırın: `[SAYFA_ID]?fields=instagram_business_account`
   Çıkan `instagram_business_account.id` değeri **Instagram işletme ID**'nizdir.

## 4. n8n'de akışı kurun
1. n8n → **Workflows → Add workflow → ⋯ → Import from File** → `adavira-sosyal-paylasim.json`.
2. **Credentials → Add credential → "Query Auth"** (HTTP Query Auth):
   - Name: `access_token`
   - Value: 3.6'daki **sayfa anahtarı**
   - Credential adı: `Meta sayfa erişim anahtarı`
3. Akıştaki üç HTTP düğümünde (Facebook, Instagram hazırla, Instagram yayınla) bu credential'ı seçin.
4. **Ayarlar ve metinler** düğümünde:
   - `pageId` → Sayfa ID
   - `igUserId` → Instagram işletme ID
   - `graphVersion` → `v27.0` (Meta yeni sürüm çıkardıkça güncellenebilir)
5. Sağ üstten akışı **Active** yapın.

> İlk etkinleştirmede n8n, akıştaki mevcut yazıları "eski" sayar ve paylaşmaz. Yalnızca bundan sonra
> yayınlanan yazılar paylaşılır.

## 5. Deneme
- Panelde bir yazı yayınlayın. En geç 15 dakika içinde Facebook ve Instagram'da görünür.
- Hemen denemek için: akışı açın → **Yeni yazı (RSS)** düğümü → **Fetch Test Event** → **Execute workflow**.
  (Bu, akıştaki en son yazıyı gerçekten paylaşır.)

## Sorun giderme
| Hata | Neden / çözüm |
|---|---|
| `(#200) ... permission` | 3.3'teki izinlerden biri eksik. Anahtarı izinlerle yeniden üretin. |
| `Invalid OAuth access token` | Kullanıcı anahtarı kullanılmış. 3.6'daki **sayfa** anahtarını kullanın. |
| Instagram `Only photo or video can be accepted` / `image_url` hatası | Tema 1.2.2 yüklü değil ya da yazının kapak görseli yok. |
| Instagram `Media ID is not available` | Instagram görseli henüz işlememiş. Bekleme süresini 60 saniyeye çıkarın. |
| Facebook şifresi değiştirildi | Sayfa anahtarı geçersiz olabilir. 3. adımı tekrarlayın. |
