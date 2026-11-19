# Otomatik Haber Sitesi

Gündemi RSS kaynaklarından takip eden, Claude ile özgün haber metni yazan, riskli haberleri onaya düşüren ve yayınlananları sosyal medyada paylaşan haber sitesi.

## Nasıl çalışır?

```
[n8n · her 15 dk]  RSS kaynakları (AA, TRT, NTV, CNN Türk, Hürriyet, BBC, DW)
        │
        ▼
[Site · /api/ingest]
   1. Daha önce görülen linkleri ayıklar
   2. Claude (editör): haber değeri olanları seçer, aynı olayı tek haberde birleştirir
   3. Kaynak sayfaları okur, Claude (muhabir) özgün metni yazar ve risk puanı verir
   4. Yayın kuralı:
        yüksek risk              → TASLAK (sen onaylamadan yayınlanmaz)
        son dakika + düşük risk  → HEMEN yayın
        düşük risk               → 5 dk sonra otomatik yayın (bu sürede durdurabilirsin)
        │
        ▼
[n8n] Telegram'a bildirim: "Önizle / Onayla / Durdur" linki
[n8n · her 5 dk] Yayına giren haberleri Telegram kanalında (ve istersen Facebook'ta) paylaşır
```

**Yüksek risk sayılanlar:** adı verilen kişiye suç isnadı, çocuklar ve mağdurlar, siyasi açıdan hassas konular, terör, sağlık iddiaları, netleşmemiş ölü/yaralı sayıları ve tek kaynağa dayanan bilgiler. Bu haberler sen onaylamadan yayınlanmaz. Bunun sebebi, yanlış bir haberin hukuki sorumluluğunun künyedeki kişiye ait olması.

## Sayfalar

| Adres | Açıklama |
|---|---|
| `/` | Ana sayfa (son dakika bandı, manşet, kategoriler) |
| `/haber/<slug>` | Haber detayı (NewsArticle şeması, otomatik kapak görseli) |
| `/kategori/<slug>` | Kategori sayfaları |
| `/yonetim` | Yönetim paneli: taslaklar, planlananlar, yayından kaldırma, düzenleme |
| `/onay/<id>?t=...` | Telegram'dan gelen tek tık onay sayfası (24 saat geçerli) |
| `/sitemap.xml`, `/news-sitemap.xml`, `/rss.xml`, `/robots.txt`, `/ads.txt` | SEO, Google Haberler ve AdSense dosyaları |
| `/sayfa/kunye`, `/sayfa/gizlilik`, `/sayfa/cerez-politikasi` ... | Yasal sayfalar |

## Kurulum (kendi sunucunda)

**Gerekenler:** Ubuntu bir VPS (2 GB RAM yeterli), alan adı, Docker.

1. Alan adının DNS'inde iki **A kaydı** oluştur: `ornekhaber.com` ve `n8n.ornekhaber.com` → sunucunun IP'si.
2. Sunucuda:
   ```bash
   git clone <bu-repo> haber && cd haber
   cp .env.example .env
   nano .env          # tüm alanları doldur
   cd deploy
   docker compose --env-file ../.env up -d --build
   ```
   Caddy HTTPS sertifikasını otomatik alır.
3. **Telegram:** [@BotFather](https://t.me/BotFather) ile bir bot oluştur ve token'ı `TELEGRAM_BOT_TOKEN`'a yaz. Bota bir mesaj at, ardından `https://api.telegram.org/bot<TOKEN>/getUpdates` adresinden sohbet ID'ni alıp `TELEGRAM_ADMIN_CHAT_ID`'ye yaz. Paylaşım kanalı açacaksan botu kanala yönetici olarak ekle.
4. **n8n:** `https://n8n.ornekhaber.com` adresine gir ve hesabını oluştur. *Workflows → Import from File* ile `n8n/1-haber-toplama.json` ve `n8n/2-sosyal-medya.json` dosyalarını içe aktar. Önce **Test workflow** ile bir kez elle çalıştır. "RSS kaynaklarını oku" düğümünün çıktısındaki `errors` alanında çalışmayan kaynak varsa düğümdeki listeden çıkar. Sonra iki akışı da **Active** yap.
5. `.env` dosyasını değiştirdiğinde değişikliğin geçerli olması için: `docker compose --env-file ../.env up -d`

### AlmaLinux / Rocky / RHEL üzerinde

AlmaLinux'ta varsayılan olarak Podman gelir; bu kurulum **Docker CE** ile test edildi. Kurulum (AlmaLinux 8 ve 9):

```bash
sudo dnf -y install dnf-plugins-core git
sudo dnf config-manager --add-repo https://download.docker.com/linux/rhel/docker-ce.repo
sudo dnf -y remove podman buildah runc 2>/dev/null || true
sudo dnf -y install docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo systemctl enable --now docker
```

```bash
sudo firewall-cmd --permanent --add-service=http --add-service=https
```

```bash
sudo firewall-cmd --reload
```

- **SELinux** açık kalabilir; `docker-compose.yml` içindeki `:Z` etiketi gerekli izni veriyor.
- Sunucuda cPanel, Plesk veya Apache/Nginx zaten 80/443 portlarını kullanıyorsa Caddy başlayamaz. Bu durumda ya o servisi kapat ya da bana yaz; mevcut web sunucusunun arkasına kuracak şekilde ayarlarım.

Bundan sonra yukarıdaki kurulum adımları aynen geçerli.

## Google AdSense

1. Sitede en az 20-30 kaliteli haber ve doldurulmuş yasal sayfalar (künye, gizlilik, iletişim) olduktan sonra başvur.
2. Onay gelince `ADSENSE_CLIENT=ca-pub-...` değerini gir. `ads.txt` otomatik oluşur.
3. AdSense panelinde **Otomatik reklamları** aç. İstersen manuel reklam birimleri oluşturup slot ID'lerini `ADSENSE_SLOT_*` alanlarına yazabilirsin; üst banner, kenar çubuğu ve haber içi alanlar hazır.
4. AdSense → **Gizlilik ve mesajlaşma** bölümünden Google'ın onaylı çerez onay penceresini (CMP) aç. AB/İngiltere trafiği için zorunlu, KVKK için de önerilir.

## Maliyet (yaklaşık)

- Claude (varsayılan `claude-opus-5-5`): her 15 dakikalık seçim turu yaklaşık 0,02-0,04 $, yazılan her haber yaklaşık 0,05-0,08 $. Günde 50 haberle **aylık yaklaşık 150-250 $** tutar. Maliyeti düşürmek için `CLAUDE_MODEL=claude-sonnet-5-5` yapabilirsin (yaklaşık yarı fiyat) ya da `MAX_ARTICLES_PER_RUN` değerini azaltabilirsin.
- VPS: aylık 5-10 €.

## Yerel geliştirme

```bash
npm install
cp .env.example .env.local   # ANTHROPIC_API_KEY, ADMIN_SECRET vb.
npm run dev
```

Veritabanı `data/haber.db` dosyasındaki SQLite'tır. Yedeklemek için bu dosyayı kopyalamak yeterli (Docker'da `site-data` volume'u).
