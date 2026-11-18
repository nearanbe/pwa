# AdaVira yazı yedeği

Canlı siteden (adavira.com) alındı: 4 blog yazısı, 2 Bizden Haberler yazısı, Hakkımızda sayfası ve 28 görsel.

- `yazilar.json`: Her yazının başlığı, adresi (slug), özeti, yayın ve güncelleme tarihi, kapak görseli,
  alt metni ve tam HTML içeriği.
- `yazilar.sql`: Eski sitenin `posts` ve `pages` tablolarına yüklenebilir SQL. Gerekirse eski siteyi
  bu dosyayla geri doldurabilirsiniz: phpMyAdmin → İçe aktar.
- `yazilar/`: Her yazının tarayıcıda açılabilen HTML kopyası.
- `uploads/`: Kapak ve yazı içi görseller (400/800/1024 px WebP + paylaşım JPG).

Not: Form mesajları ve yönetici hesapları sitede herkese açık olmadığı için bu yedekte yoktur.
Onlar için Plesk → Databases → Export Dump kullanın.
