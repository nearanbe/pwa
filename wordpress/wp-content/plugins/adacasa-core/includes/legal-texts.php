<?php
/**
 * Yasal metinler (1.1.5) — TR / EN / RU.
 * Yer tutucular ec_legal_render() ile doldurulur:
 *   {brand} {company} {address} {email} {phone} {taxno} {site} {date}
 *   {privacy_url} {cookies_url} {terms_url} {sales_url} {contact_url} {faq_url}
 * Hukuki dayanak: KKTC Kişisel Verilerin Korunması Yasası (89/2007); AB'de yerleşik ziyaretçiler için
 * uygulanabildiği ölçüde GDPR (AB 2016/679); Türkiye'de yerleşik ziyaretçiler için uygulanabildiği ölçüde 6698 sayılı KVKK.
 * Metinler bir avukata kontrol ettirilmelidir.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_legal_docs(): array
{
    return [
        'about' => [
            'slug' => ['tr' => 'hakkimizda', 'en' => 'about', 'ru' => 'o-nas'],
            'title' => ['tr' => 'Hakkımızda', 'en' => 'About us', 'ru' => 'О нас'],
            'html' => [
                'tr' => <<<'H'
<p><b>{brand}</b>, Kuzey Kıbrıs’ta satılık, kiralık ve günlük kiralık konutları, yeni projeleri ve emlak ofislerini tek yerde buluşturan bir emlak ilan platformudur. Site Türkçe, İngilizce ve Rusça yayın yapar.</p>
<h2>Ne yapıyoruz?</h2>
<ul>
<li><b>İlan vermek ücretsiz.</b> Emlak ofisleri, inşaat firmaları, danışmanlar ve mülk sahipleri ilanlarını ücretsiz yayınlar.</li>
<li><b>Üç dil.</b> İlanlar Türkçe, İngilizce ve Rusça olarak, her dilin kendi adresinde yayınlanır.</li>
<li><b>Şeffaf bilgi.</b> Satılık her ilanda tapu türü, bölge fiyat trendi, konum ve yakındaki önemli yerler gösterilir.</li>
<li><b>Zaman kazandıran araçlar.</b> Telegram ile ilan verme, yapay zekâ ile çeviri, başka ilan sitelerine aktarım ve sosyal medya paylaşımı gibi isteğe bağlı ücretli paketler sunulur.</li>
</ul>
<h2>İlkelerimiz</h2>
<p>İlanların doğruluğu bizim için önemlidir: kopya ilanlar otomatik denetlenir, hatalı ilanlar tek tıkla bildirilebilir. {brand} bir ilan platformudur; mülklerin satıcısı ya da kiraya vereni değildir. Alım-satım ve kiralama işlemleri ilan sahibi ile alıcı arasında gerçekleşir.</p>
<h2>Şirket bilgileri</h2>
<p>{company}<br>{address}<br>E-posta: {email}<br>{phone}</p>
H,
                'en' => <<<'H'
<p><b>{brand}</b> is a property listing platform for North Cyprus that brings together homes for sale, long-term and holiday rentals, new developments and estate agencies in one place. The site is published in Turkish, English and Russian.</p>
<h2>What we do</h2>
<ul>
<li><b>Listing is free.</b> Agencies, developers, agents and owners publish their listings free of charge.</li>
<li><b>Three languages.</b> Every listing is published in Turkish, English and Russian, each with its own address.</li>
<li><b>Transparent information.</b> Every property for sale shows its title deed type, area price trend, location and nearby places.</li>
<li><b>Time-saving tools.</b> Optional paid packages offer listing via Telegram, AI translation, transfer to other portals and social media sharing.</li>
</ul>
<h2>Our principles</h2>
<p>Accurate listings matter to us: duplicate listings are checked automatically and incorrect listings can be reported with one click. {brand} is a listing platform; it is not the seller or landlord of any property. Sales and rentals are concluded between the advertiser and the buyer or tenant.</p>
<h2>Company details</h2>
<p>{company}<br>{address}<br>Email: {email}<br>{phone}</p>
H,
                'ru' => <<<'H'
<p><b>{brand}</b> — платформа объявлений о недвижимости Северного Кипра: продажа, долгосрочная и посуточная аренда, новые проекты и агентства недвижимости в одном месте. Сайт работает на турецком, английском и русском языках.</p>
<h2>Что мы делаем</h2>
<ul>
<li><b>Размещение бесплатно.</b> Агентства, застройщики, агенты и собственники публикуют объявления бесплатно.</li>
<li><b>Три языка.</b> Каждое объявление публикуется на турецком, английском и русском, у каждого языка свой адрес.</li>
<li><b>Прозрачная информация.</b> Для объектов на продажу указываются тип документа о собственности (тапу), динамика цен в районе, расположение и ближайшие объекты.</li>
<li><b>Инструменты, экономящие время.</b> Платные пакеты по желанию: размещение через Telegram, перевод с помощью ИИ, перенос на другие порталы и публикация в соцсетях.</li>
</ul>
<h2>Наши принципы</h2>
<p>Для нас важна достоверность объявлений: дубликаты проверяются автоматически, о неверном объявлении можно сообщить одним нажатием. {brand} — площадка объявлений, а не продавец или арендодатель объектов. Сделки заключаются между автором объявления и покупателем или арендатором.</p>
<h2>Реквизиты</h2>
<p>{company}<br>{address}<br>E-mail: {email}<br>{phone}</p>
H,
            ],
        ],

        'contact' => [
            'slug' => ['tr' => 'iletisim', 'en' => 'contact', 'ru' => 'kontakty'],
            'title' => ['tr' => 'İletişim', 'en' => 'Contact', 'ru' => 'Контакты'],
            'html' => [
                'tr' => <<<'H'
<p>Sorularınız, ilan bildirimleriniz ve iş birliği talepleriniz için bize ulaşın.</p>
<p><b>{company}</b><br>{address}</p>
<p>E-posta: <a href="mailto:{email}">{email}</a><br>{phone}</p>
<p>{whatsapp}</p>
<p>Bir ilanla ilgili sorunuz varsa ilan sayfasındaki <b>“Mesaj gönder”</b> formunu kullanın; mesajınız doğrudan ilan sahibine iletilir. Hatalı ya da yanıltıcı bir ilan gördüyseniz ilan sayfasındaki <b>“Hatalı ilan bildir”</b> bağlantısını kullanın.</p>
<p>Kişisel verilerinizle ilgili başvurularınız için: <a href="{privacy_url}">Gizlilik ve Kişisel Veriler</a>. Sık sorulan sorular: <a href="{faq_url}">SSS</a>.</p>
H,
                'en' => <<<'H'
<p>Contact us with questions, listing reports or partnership requests.</p>
<p><b>{company}</b><br>{address}</p>
<p>Email: <a href="mailto:{email}">{email}</a><br>{phone}</p>
<p>{whatsapp}</p>
<p>For questions about a specific property, please use the <b>“Send a message”</b> form on the listing page; your message goes directly to the advertiser. If you see an incorrect or misleading listing, use the <b>“Report listing”</b> link on that page.</p>
<p>Requests about your personal data: <a href="{privacy_url}">Privacy &amp; Personal Data</a>. Frequently asked questions: <a href="{faq_url}">FAQ</a>.</p>
H,
                'ru' => <<<'H'
<p>Свяжитесь с нами по вопросам, жалобам на объявления или сотрудничеству.</p>
<p><b>{company}</b><br>{address}</p>
<p>E-mail: <a href="mailto:{email}">{email}</a><br>{phone}</p>
<p>{whatsapp}</p>
<p>По вопросам о конкретном объекте используйте форму <b>«Написать сообщение»</b> на странице объявления — сообщение получит автор объявления. Если вы заметили неверное или вводящее в заблуждение объявление, нажмите <b>«Сообщить об ошибке»</b> на его странице.</p>
<p>Запросы о ваших персональных данных: <a href="{privacy_url}">Конфиденциальность</a>. Частые вопросы: <a href="{faq_url}">FAQ</a>.</p>
H,
            ],
        ],

        'privacy' => [
            'slug' => ['tr' => 'gizlilik', 'en' => 'privacy', 'ru' => 'konfidentsialnost'],
            'title' => ['tr' => 'Gizlilik ve Kişisel Verilerin Korunması (Aydınlatma Metni)', 'en' => 'Privacy Policy & Personal Data Notice', 'ru' => 'Политика конфиденциальности и обработки персональных данных'],
            'html' => [
                'tr' => <<<'H'
<p class="sub">Son güncelleme: {date}</p>
<p>Bu metin, <b>{site}</b> adresindeki {brand} platformunu kullanırken kişisel verilerinizin nasıl işlendiğini açıklar. Hazırlanırken KKTC <b>Kişisel Verilerin Korunması Yasası (89/2007)</b>; Avrupa Birliği’nde bulunan kullanıcılar için uygulanabildiği ölçüde <b>Genel Veri Koruma Tüzüğü (GDPR)</b>; Türkiye’de bulunan kullanıcılar için uygulanabildiği ölçüde <b>6698 sayılı KVKK</b> esas alınmıştır.</p>

<h2>1. Veri sorumlusu</h2>
<p>{company}, {address} · E-posta: {email}</p>

<h2>2. Hangi verileri işliyoruz?</h2>
<ul>
<li><b>Üyelik bilgileri:</b> ad soyad ya da firma adı, yetkili kişi, e-posta, cep telefonu, şehir, hesap türü, şifre (şifrelenmiş olarak), onay ve doğrulama kayıtları.</li>
<li><b>Profil ve vitrin bilgileri:</b> logo, adres, web sitesi, ruhsat numarası, sosyal medya hesapları, tanıtım yazısı.</li>
<li><b>İlan bilgileri:</b> ilan metinleri, fiyat, konum (harita işareti), fotoğraflar, 360° fotoğraflar ve videolar.</li>
<li><b>İletişim talepleri:</b> ilan sahibine “Mesaj gönder” formuyla ilettiğiniz ad, telefon, e-posta ve mesaj; hatalı ilan bildirimleri.</li>
<li><b>Telegram kullanımı:</b> Telegram sohbet numarası, bota gönderilen fotoğraflar, metinler ve doğrulama kodları.</li>
<li><b>Google veya Facebook ile giriş:</b> bu yöntemi seçerseniz ilgili hizmetin bize ilettiği ad soyad, e-posta adresi ve hesap numarası. Google veya Facebook şifreniz bize iletilmez.</li>
<li><b>Sosyal medya bağlantısı:</b> paylaşım yapılacak Facebook sayfasının ve Instagram işletme hesabının adı, numarası ve erişim anahtarı (şifrelenmiş olarak). Şifrenizi asla istemeyiz.</li>
<li><b>Ödeme ve sipariş bilgileri:</b> sipariş kodu, paket, tutar, havale dekontu. Kartla ödemede kart bilgileri bankanın güvenli ödeme sayfasında girilir; <b>kart numaranız bize ulaşmaz ve saklanmaz</b>, sadece bankanın onay kodu ve kartın son dört hanesi kaydedilir.</li>
<li><b>Kullanım verileri:</b> IP adresi, tarayıcı ve cihaz bilgisi, ziyaret edilen sayfalar, ilan görüntülenme sayıları, güvenlik kayıtları; izin vermeniz hâlinde analiz ve pazarlama çerezleri (bkz. <a href="{cookies_url}">Çerez Politikası</a>).</li>
<li><b>Tarayıcınızda saklananlar:</b> seçtiğiniz para birimi, favorileriniz, ilanlara aldığınız kişisel notlar ve son aramalarınız. Notlarınız sadece sizin cihazınızda tutulur, bize gönderilmez.</li>
</ul>

<h2>3. Hangi amaçlarla ve hangi hukuki sebeplerle?</h2>
<ul>
<li>Üyeliğin oluşturulması, ilanların yayınlanması ve platform hizmetlerinin sunulması (sözleşmenin kurulması ve ifası).</li>
<li>İlanlarla ilgili mesajların ilan sahibine iletilmesi (açık rızanız ve talebiniz).</li>
<li>Paketlerin satışı, faturalandırılması ve muhasebe kayıtları (sözleşme ve yasal yükümlülük).</li>
<li>Sahte, kopya ve yanıltıcı ilanların önlenmesi; spam, bot ve kötüye kullanımın engellenmesi; güvenlik (meşru menfaat).</li>
<li>Ziyaret istatistikleri ve hizmetin iyileştirilmesi; reklam ölçümü (sadece açık rızanızla, çerez tercihlerinize göre).</li>
<li>Yetkili kurumların yasal talepleri (yasal yükümlülük).</li>
</ul>

<h2>4. Kimlerle paylaşıyoruz?</h2>
<ul>
<li><b>İlan sahipleri:</b> “Mesaj gönder” formuyla ilettiğiniz bilgiler, mesaj verdiğiniz ilanın sahibine (emlak ofisi, danışman ya da mülk sahibi) iletilir.</li>
<li><b>Herkese açık ilan ve vitrin bilgileri:</b> ilan sahibinin yayınladığı ilanlar, firma adı, logosu, telefon ve WhatsApp numarası ziyaretçilere gösterilir.</li>
<li><b>Hizmet sağlayıcılarımız</b> (sadece hizmetin gerektirdiği kadar):
  <ul>
  <li>Barındırma sağlayıcısı (sunucu),</li>
  <li>Cloudflare (içerik dağıtımı, güvenlik, Turnstile bot koruması),</li>
  <li>Telegram (bot mesajları),</li>
  <li>Anthropic (Claude yapay zekâ: Telegram’dan gönderilen ilan metinlerinden alan çıkarma ve çeviri; bu veriler model eğitiminde kullanılmaz),</li>
  <li>Meta Platforms (Facebook ve Instagram paylaşımı; çerez izni verdiyseniz Meta Pixel),</li>
  <li>Google (çerez izni verdiyseniz Google Analytics ve Google AdSense reklamları),</li>
  <li>OpenStreetMap ve harita karo sağlayıcıları (harita gösterimi),</li>
  <li>YouTube ve Vimeo (ilan sahibi video eklediyse),</li>
  <li>Kartla ödemede ilgili banka ve ödeme altyapısı,</li>
  <li>E-posta gönderim hizmeti.</li>
  </ul></li>
<li><b>Paket kapsamında, üyenin isteğiyle:</b> 101evler ve hangiev gibi başka ilan sitelerine aktarım, üyenin kendi tarayıcısından ve kendi hesabıyla yapılır.</li>
<li><b>Yetkili kamu kurumları:</b> yasal zorunluluk hâlinde.</li>
</ul>
<p>Bazı hizmet sağlayıcıların sunucuları KKTC dışında (AB, ABD ve başka ülkelerde) bulunabilir. Bu aktarımlar hizmetin sunulması için gereklidir ve sağlayıcıların veri koruma taahhütlerine dayanır.</p>

<p><b>Google reklamları:</b> Sitemizde Google AdSense reklamları gösterilebilir. Google dahil üçüncü taraf sağlayıcılar, bu siteye ve diğer sitelere yaptığınız önceki ziyaretlere dayalı reklamlar sunmak için çerezler kullanır. Google’ın reklam çerezlerini kullanması, Google’ın ve iş ortaklarının size bu siteye ve/veya internetteki diğer sitelere yaptığınız ziyaretlere göre reklam göstermesini sağlar. Kişiselleştirilmiş reklamları <a href="https://adssettings.google.com" target="_blank" rel="noopener">Google Reklam Ayarları</a> sayfasından kapatabilir, üçüncü taraf sağlayıcıların çerezlerini <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">www.aboutads.info</a> üzerinden reddedebilirsiniz. Bu çerezler yalnızca sitemizdeki çerez tercihlerinde “Pazarlama ve reklam” iznini verdiğinizde yüklenir. Google’ın verileri nasıl kullandığı: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>.</p>
<h2>5. Ne kadar süre saklıyoruz?</h2>
<ul>
<li>Üyelik ve ilan bilgileri: hesabınız açık olduğu sürece; hesabın kapatılmasından sonra en fazla 1 yıl (uyuşmazlık ve güvenlik için).</li>
<li>Mesaj (talep) kayıtları: 2 yıl.</li>
<li>Sipariş, ödeme ve fatura kayıtları: yasal saklama süresi boyunca.</li>
<li>Güvenlik ve sunucu kayıtları: en fazla 1 yıl.</li>
<li>Telegram taslakları: ilan yayınlanınca ya da iptal edilince silinir.</li>
</ul>

<h2>6. Haklarınız</h2>
<p>Kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi ve kopya isteme, düzeltilmesini, silinmesini ya da işlenmesinin kısıtlanmasını isteme, verilerinizi taşınabilir biçimde alma, işlemeye itiraz etme ve verdiğiniz rızayı her zaman geri alma haklarına sahipsiniz. Çerez iznini sayfanın altındaki <b>“Çerez ayarları”</b> bağlantısından değiştirebilirsiniz.</p>
<p>Başvurunuzu <a href="mailto:{email}">{email}</a> adresine, üyeliğinizde kayıtlı e-postadan gönderin. Başvurunuz en geç <b>30 gün</b> içinde yanıtlanır. Sonuçtan memnun kalmazsanız KKTC Kişisel Verileri Koruma Kurulu’na (AB’de yaşıyorsanız ülkenizin veri koruma otoritesine) başvurabilirsiniz.</p>

<p id="veri-silme"><b>Google / Facebook verilerinin silinmesi:</b> Hesabınızı ve Google veya Facebook ile girişte aldığımız bilgileri sildirmek için {email} adresine “hesabımı sil” yazmanız yeterlidir; 30 gün içinde silinir. Bağlantıyı Google veya Facebook hesap ayarlarınızdaki “Uygulamalar” bölümünden de kaldırabilirsiniz.</p>

<h2>7. Güvenlik</h2>
<p>Site HTTPS ile şifrelenir. Şifreler ve erişim anahtarları şifrelenmiş olarak saklanır. Erişim yetkileri sınırlıdır. Hiçbir sistem %100 güvenli değildir; bir ihlal fark edersek yasal sürelerde bildiririz.</p>

<h2>8. Çocuklar</h2>
<p>Platform 18 yaşından küçükler için tasarlanmamıştır; 18 yaşından küçüklerin üye olmaması gerekir.</p>

<h2>9. Değişiklikler</h2>
<p>Bu metni güncelleyebiliriz. Önemli değişiklikleri sitede ve üyelerimize e-postayla duyururuz.</p>
H,
                'en' => <<<'H'
<p class="sub">Last updated: {date}</p>
<p>This notice explains how your personal data is processed when you use the {brand} platform at <b>{site}</b>. It is based on the TRNC <b>Personal Data Protection Law (89/2007)</b>, and, to the extent applicable, the EU <b>General Data Protection Regulation (GDPR)</b> for users in the European Union and Turkish Law No. <b>6698 (KVKK)</b> for users in Turkey.</p>

<h2>1. Data controller</h2>
<p>{company}, {address} · Email: {email}</p>

<h2>2. What data do we process?</h2>
<ul>
<li><b>Account data:</b> name or company name, contact person, email, mobile phone, city, account type, password (hashed), consent and verification records.</li>
<li><b>Profile and showcase data:</b> logo, address, website, licence number, social media accounts, description.</li>
<li><b>Listing data:</b> texts, price, location (map pin), photos, 360° photos and videos.</li>
<li><b>Enquiries:</b> the name, phone, email and message you send to an advertiser via “Send a message”; listing reports.</li>
<li><b>Telegram:</b> chat ID, photos, texts and verification codes sent to the bot.</li>
<li><b>Sign-in with Google or Facebook:</b> if you choose this, the name, email address and account ID that the service sends us. Your Google or Facebook password is never shared with us.</li>
<li><b>Social media connection:</b> name, ID and access token (encrypted) of the Facebook page and Instagram business account used for sharing. We never ask for your password.</li>
<li><b>Orders and payments:</b> order code, package, amount, bank transfer receipt. For card payments, card details are entered on the bank’s secure payment page; <b>your card number never reaches or is stored by us</b> — only the bank’s approval code and the last four digits are recorded.</li>
<li><b>Usage data:</b> IP address, browser and device information, pages visited, listing view counts, security logs; analytics and marketing cookies only with your consent (see the <a href="{cookies_url}">Cookie Policy</a>).</li>
<li><b>Stored in your browser:</b> chosen currency, favourites, your private notes on listings and recent searches. Your notes stay on your device and are not sent to us.</li>
</ul>

<h2>3. Purposes and legal bases</h2>
<ul>
<li>Creating your account, publishing listings and providing the platform (performance of a contract).</li>
<li>Forwarding your enquiries to advertisers (your consent and request).</li>
<li>Selling packages, invoicing and accounting (contract and legal obligation).</li>
<li>Preventing fake, duplicate and misleading listings, spam, bots and abuse; security (legitimate interests).</li>
<li>Visitor statistics, service improvement and advertising measurement (only with your consent, according to your cookie choices).</li>
<li>Lawful requests from competent authorities (legal obligation).</li>
</ul>

<h2>4. Who do we share data with?</h2>
<ul>
<li><b>Advertisers:</b> details you send via “Send a message” are forwarded to the owner of that listing (agency, agent or owner).</li>
<li><b>Public listing and showcase data:</b> listings, company name, logo, phone and WhatsApp number published by the advertiser are shown to visitors.</li>
<li><b>Our service providers</b> (only as needed to provide the service):
  <ul>
  <li>hosting provider,</li>
  <li>Cloudflare (content delivery, security, Turnstile bot protection),</li>
  <li>Telegram (bot messages),</li>
  <li>Anthropic (Claude AI: extracting fields from and translating listing texts sent via Telegram; not used for model training),</li>
  <li>Meta Platforms (Facebook and Instagram sharing; Meta Pixel if you consent),</li>
  <li>Google (Google Analytics and Google AdSense ads if you consent),</li>
  <li>OpenStreetMap and map tile providers,</li>
  <li>YouTube and Vimeo (if the advertiser adds a video),</li>
  <li>the bank and payment infrastructure for card payments,</li>
  <li>email delivery service.</li>
  </ul></li>
<li><b>At the member’s request, within a package:</b> transfer to other portals such as 101evler and hangiev is performed from the member’s own browser and account.</li>
<li><b>Public authorities:</b> where required by law.</li>
</ul>
<p>Some providers’ servers may be located outside the TRNC (EU, USA and other countries). These transfers are necessary to provide the service and rely on the providers’ data protection commitments.</p>

<p><b>Google ads:</b> Google AdSense ads may be shown on this site. Third-party vendors, including Google, use cookies to serve ads based on your prior visits to this website or other websites. Google’s use of advertising cookies enables it and its partners to serve ads to you based on your visits to this site and/or other sites on the Internet. You may opt out of personalised advertising by visiting <a href="https://adssettings.google.com" target="_blank" rel="noopener">Google Ads Settings</a>, and opt out of third-party vendors’ cookies at <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">www.aboutads.info</a>. These cookies are only loaded if you allow “Marketing and ads” in the cookie settings on our site. How Google uses data: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>.</p>
<h2>5. How long do we keep data?</h2>
<ul>
<li>Account and listing data: while your account is open; up to 1 year after closure (disputes and security).</li>
<li>Enquiry records: 2 years.</li>
<li>Order, payment and invoice records: for the statutory retention period.</li>
<li>Security and server logs: up to 1 year.</li>
<li>Telegram drafts: deleted when the listing is published or cancelled.</li>
</ul>

<h2>6. Your rights</h2>
<p>You have the right to know whether your data is processed, to request information and a copy, rectification, erasure or restriction, to receive your data in a portable format, to object, and to withdraw your consent at any time. You can change your cookie consent via <b>“Cookie settings”</b> at the bottom of every page.</p>
<p>Send your request to <a href="mailto:{email}">{email}</a> from the email address registered to your account. We reply within <b>30 days</b>. If you are not satisfied, you may complain to the TRNC Personal Data Protection Board (or, if you live in the EU, to your national data protection authority).</p>

<p id="veri-silme"><b>Deleting Google / Facebook data:</b> to delete your account and the information we received through Google or Facebook sign-in, email {email} with “delete my account”; it is deleted within 30 days. You can also remove the connection under “Apps” in your Google or Facebook account settings.</p>

<h2>7. Security</h2>
<p>The site is encrypted with HTTPS. Passwords and access tokens are stored encrypted, and access is restricted. No system is 100% secure; if we become aware of a breach we will notify it within the legal time limits.</p>

<h2>8. Children</h2>
<p>The platform is not intended for persons under 18, who should not register.</p>

<h2>9. Changes</h2>
<p>We may update this notice. Significant changes will be announced on the site and emailed to members.</p>
H,
                'ru' => <<<'H'
<p class="sub">Последнее обновление: {date}</p>
<p>Настоящий документ объясняет, как обрабатываются ваши персональные данные при использовании платформы {brand} по адресу <b>{site}</b>. Он основан на <b>Законе ТРСК о защите персональных данных (89/2007)</b>, а также, в применимой мере, на <b>Общем регламенте ЕС по защите данных (GDPR)</b> для пользователей из ЕС и <b>Законе Турции № 6698 (KVKK)</b> для пользователей из Турции.</p>

<h2>1. Оператор данных</h2>
<p>{company}, {address} · E-mail: {email}</p>

<h2>2. Какие данные мы обрабатываем</h2>
<ul>
<li><b>Данные аккаунта:</b> имя или название компании, контактное лицо, e-mail, мобильный телефон, город, тип аккаунта, пароль (в зашифрованном виде), записи о согласии и подтверждении.</li>
<li><b>Профиль и витрина:</b> логотип, адрес, сайт, номер лицензии, соцсети, описание.</li>
<li><b>Объявления:</b> тексты, цена, расположение (метка на карте), фото, фото 360° и видео.</li>
<li><b>Запросы:</b> имя, телефон, e-mail и сообщение, отправленные автору объявления через форму «Написать сообщение»; жалобы на объявления.</li>
<li><b>Telegram:</b> ID чата, отправленные боту фото, тексты и коды подтверждения.</li>
<li><b>Вход через Google или Facebook:</b> если вы выбираете этот способ — имя, e-mail и идентификатор аккаунта, которые передаёт сервис. Ваш пароль Google или Facebook нам не передаётся.</li>
<li><b>Подключение соцсетей:</b> название, ID и ключ доступа (зашифрован) страницы Facebook и бизнес-аккаунта Instagram. Мы никогда не запрашиваем ваш пароль.</li>
<li><b>Заказы и платежи:</b> код заказа, пакет, сумма, квитанция банковского перевода. При оплате картой данные карты вводятся на защищённой странице банка; <b>номер карты к нам не поступает и не хранится</b> — сохраняются только код подтверждения банка и последние четыре цифры.</li>
<li><b>Данные об использовании:</b> IP-адрес, сведения о браузере и устройстве, посещённые страницы, число просмотров объявлений, журналы безопасности; аналитические и маркетинговые cookie — только с вашего согласия (см. <a href="{cookies_url}">Политику cookie</a>).</li>
<li><b>Хранится в вашем браузере:</b> выбранная валюта, избранное, личные заметки к объявлениям и недавние поиски. Заметки остаются на вашем устройстве и нам не передаются.</li>
</ul>

<h2>3. Цели и правовые основания</h2>
<ul>
<li>Создание аккаунта, публикация объявлений и работа платформы (исполнение договора).</li>
<li>Передача ваших запросов авторам объявлений (ваше согласие и запрос).</li>
<li>Продажа пакетов, выставление счетов и бухгалтерия (договор и требования закона).</li>
<li>Предотвращение фейковых, дублирующих и вводящих в заблуждение объявлений, спама, ботов и злоупотреблений; безопасность (законный интерес).</li>
<li>Статистика посещений, улучшение сервиса и измерение рекламы (только с вашего согласия, согласно настройкам cookie).</li>
<li>Законные запросы уполномоченных органов (требование закона).</li>
</ul>

<h2>4. Кому мы передаём данные</h2>
<ul>
<li><b>Авторам объявлений:</b> данные из формы «Написать сообщение» передаются владельцу этого объявления (агентству, агенту или собственнику).</li>
<li><b>Публичные данные:</b> объявления, название компании, логотип, телефон и WhatsApp, опубликованные автором, видны посетителям.</li>
<li><b>Наши поставщики услуг</b> (только в необходимом объёме):
  <ul>
  <li>хостинг-провайдер,</li>
  <li>Cloudflare (доставка контента, безопасность, защита от ботов Turnstile),</li>
  <li>Telegram (сообщения бота),</li>
  <li>Anthropic (ИИ Claude: извлечение данных из текстов объявлений, присланных через Telegram, и перевод; данные не используются для обучения моделей),</li>
  <li>Meta Platforms (публикации в Facebook и Instagram; Meta Pixel — при вашем согласии),</li>
  <li>Google (Google Analytics и реклама Google AdSense — при вашем согласии),</li>
  <li>OpenStreetMap и поставщики картографических данных,</li>
  <li>YouTube и Vimeo (если автор добавил видео),</li>
  <li>банк и платёжная инфраструктура при оплате картой,</li>
  <li>сервис отправки e-mail.</li>
  </ul></li>
<li><b>По желанию участника в рамках пакета:</b> перенос объявлений на другие порталы (например, 101evler и hangiev) выполняется из браузера и аккаунта самого участника.</li>
<li><b>Государственным органам:</b> если это требуется по закону.</li>
</ul>
<p>Серверы некоторых поставщиков могут находиться за пределами ТРСК (в ЕС, США и других странах). Такая передача необходима для оказания услуг и основана на обязательствах поставщиков по защите данных.</p>

<p><b>Реклама Google:</b> на сайте может показываться реклама Google AdSense. Сторонние поставщики, в том числе Google, используют файлы cookie для показа рекламы с учётом ваших предыдущих посещений этого и других сайтов. Рекламные cookie позволяют Google и его партнёрам показывать вам рекламу на основе посещений этого сайта и/или других сайтов в интернете. Отключить персонализированную рекламу можно в <a href="https://adssettings.google.com" target="_blank" rel="noopener">настройках рекламы Google</a>, а cookie сторонних поставщиков — на <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">www.aboutads.info</a>. Эти cookie загружаются только если вы разрешили «Маркетинг и реклама» в настройках cookie на нашем сайте. Как Google использует данные: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>.</p>
<h2>5. Сроки хранения</h2>
<ul>
<li>Данные аккаунта и объявлений: пока аккаунт открыт; до 1 года после закрытия (споры и безопасность).</li>
<li>Запросы: 2 года.</li>
<li>Заказы, платежи и счета: в течение установленного законом срока.</li>
<li>Журналы безопасности и сервера: до 1 года.</li>
<li>Черновики Telegram: удаляются после публикации или отмены.</li>
</ul>

<h2>6. Ваши права</h2>
<p>Вы вправе узнать, обрабатываются ли ваши данные, запросить сведения и копию, исправление, удаление или ограничение обработки, получить данные в переносимом формате, возразить против обработки и в любой момент отозвать согласие. Согласие на cookie можно изменить по ссылке <b>«Настройки cookie»</b> внизу каждой страницы.</p>
<p>Направляйте запросы на <a href="mailto:{email}">{email}</a> с адреса, указанного в аккаунте. Мы отвечаем в течение <b>30 дней</b>. Если ответ вас не устроит, вы можете обратиться в Совет по защите персональных данных ТРСК (а при проживании в ЕС — в национальный надзорный орган).</p>

<p id="veri-silme"><b>Удаление данных Google / Facebook:</b> чтобы удалить аккаунт и данные, полученные при входе через Google или Facebook, напишите на {email} «удалите мой аккаунт» — удаление в течение 30 дней. Связь можно также отключить в разделе «Приложения» настроек аккаунта Google или Facebook.</p>

<h2>7. Безопасность</h2>
<p>Сайт защищён HTTPS. Пароли и ключи доступа хранятся в зашифрованном виде, доступ ограничен. Ни одна система не защищена на 100%; об утечке мы сообщим в установленные законом сроки.</p>

<h2>8. Дети</h2>
<p>Платформа не предназначена для лиц младше 18 лет; им не следует регистрироваться.</p>

<h2>9. Изменения</h2>
<p>Мы можем обновлять этот документ. О существенных изменениях сообщим на сайте и по e-mail участникам.</p>
H,
            ],
        ],

        'cookies' => [
            'slug' => ['tr' => 'cerez-politikasi', 'en' => 'cookies', 'ru' => 'cookies'],
            'title' => ['tr' => 'Çerez Politikası', 'en' => 'Cookie Policy', 'ru' => 'Политика использования cookie'],
            'html' => [
                'tr' => <<<'H'
<p class="sub">Son güncelleme: {date}</p>
<p>Çerezler, siteyi ziyaret ettiğinizde tarayıcınıza kaydedilen küçük dosyalardır. Benzer amaçla tarayıcının yerel depolama alanını da kullanırız. Zorunlu çerezler sitenin çalışması için gereklidir. Analiz ve pazarlama çerezleri ise <b>sadece izin verirseniz</b> kullanılır. Tercihinizi her sayfanın altındaki <b>“Çerez ayarları”</b> bağlantısından istediğiniz zaman değiştirebilirsiniz.</p>
<h2>Zorunlu (her zaman açık)</h2>
<table class="ltable"><tr><th>Ad</th><th>Amaç</th><th>Süre</th></tr>
<tr><td>wordpress_logged_in_*, wordpress_sec_*, wordpress_test_cookie</td><td>Giriş yapmış üyelerin oturumu ve güvenliği</td><td>Oturum / 14 gün</td></tr>
<tr><td>ec_cur</td><td>Seçtiğiniz para birimi</td><td>1 yıl</td></tr>
<tr><td>ec_favs</td><td>Favori ilanlarınız</td><td>1 yıl</td></tr>
<tr><td>ac_consent</td><td>Çerez tercihiniz</td><td>6 ay</td></tr>
<tr><td>__cf_bm, cf_clearance (Cloudflare)</td><td>Bot ve saldırı koruması; Turnstile doğrulaması</td><td>30 dk – 1 yıl</td></tr>
<tr><td>Yerel depolama: notlar, son aramalar, uygulama yükleme hatırlatması</td><td>İlanlara aldığınız kişisel notlar ve son aramalarınız (sadece cihazınızda)</td><td>Siz silene kadar</td></tr>
</table>
<h2>Analiz (izninizle)</h2>
<table class="ltable"><tr><th>Ad</th><th>Sağlayıcı</th><th>Amaç</th><th>Süre</th></tr>
<tr><td>_ga, _ga_*</td><td>Google Analytics</td><td>Ziyaret istatistikleri; IP adresi kısaltılır</td><td>2 yıl</td></tr>
</table>
<h2>Pazarlama (izninizle)</h2>
<table class="ltable"><tr><th>Ad</th><th>Sağlayıcı</th><th>Amaç</th><th>Süre</th></tr>
<tr><td>_fbp, fr</td><td>Meta (Facebook / Instagram)</td><td>Reklamların etkisini ölçme ve ilgili reklam gösterme</td><td>3 ay</td></tr>
<tr><td>__gads, __gpi, IDE</td><td>Google AdSense (sitede Google reklamı gösteriliyorsa)</td><td>Reklam gösterme, sıklık sınırı ve reklam ölçümü</td><td>13 aya kadar</td></tr>
</table>
<p><b>Google reklamları:</b> Sitemizde Google AdSense reklamları gösterilebilir. Google dahil üçüncü taraf sağlayıcılar, bu siteye ve diğer sitelere yaptığınız önceki ziyaretlere dayalı reklamlar sunmak için çerezler kullanır. Google’ın reklam çerezlerini kullanması, Google’ın ve iş ortaklarının size bu siteye ve/veya internetteki diğer sitelere yaptığınız ziyaretlere göre reklam göstermesini sağlar. Kişiselleştirilmiş reklamları <a href="https://adssettings.google.com" target="_blank" rel="noopener">Google Reklam Ayarları</a> sayfasından kapatabilir, üçüncü taraf sağlayıcıların çerezlerini <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">www.aboutads.info</a> üzerinden reddedebilirsiniz. Bu çerezler yalnızca sitemizdeki çerez tercihlerinde “Pazarlama ve reklam” iznini verdiğinizde yüklenir. Google’ın verileri nasıl kullandığı: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>.</p>
<h2>Üçüncü taraf içerikler</h2>
<p>İlan sayfasında video (YouTube gizlilik modu, Vimeo) ya da sanal tur (Matterport, Kuula vb.) varsa, bu içerikler oynatıldığında ilgili sağlayıcı kendi çerezlerini kullanabilir. Harita gösterimi OpenStreetMap ile yapılır ve çerez kullanmaz.</p>
<h2>Çerezleri tarayıcıdan yönetme</h2>
<p>Tarayıcınızın ayarlarından çerezleri silebilir ya da engelleyebilirsiniz. Zorunlu çerezleri engellerseniz giriş yapma ve favoriler gibi özellikler çalışmayabilir. Ayrıntılar için: <a href="{privacy_url}">Gizlilik ve Kişisel Veriler</a>.</p>
H,
                'en' => <<<'H'
<p class="sub">Last updated: {date}</p>
<p>Cookies are small files saved in your browser when you visit a website; for similar purposes we also use your browser’s local storage. Essential cookies are needed for the site to work. Analytics and marketing cookies are used <b>only if you consent</b>. You can change your choice at any time via <b>“Cookie settings”</b> at the bottom of every page.</p>
<h2>Essential (always on)</h2>
<table class="ltable"><tr><th>Name</th><th>Purpose</th><th>Duration</th></tr>
<tr><td>wordpress_logged_in_*, wordpress_sec_*, wordpress_test_cookie</td><td>Session and security for logged-in members</td><td>Session / 14 days</td></tr>
<tr><td>ec_cur</td><td>Your chosen currency</td><td>1 year</td></tr>
<tr><td>ec_favs</td><td>Your favourite listings</td><td>1 year</td></tr>
<tr><td>ac_consent</td><td>Your cookie choice</td><td>6 months</td></tr>
<tr><td>__cf_bm, cf_clearance (Cloudflare)</td><td>Bot and attack protection; Turnstile verification</td><td>30 min – 1 year</td></tr>
<tr><td>Local storage: notes, recent searches, app install reminder</td><td>Your private listing notes and recent searches (on your device only)</td><td>Until you delete them</td></tr>
</table>
<h2>Analytics (with your consent)</h2>
<table class="ltable"><tr><th>Name</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr>
<tr><td>_ga, _ga_*</td><td>Google Analytics</td><td>Visitor statistics; IP addresses are anonymised</td><td>2 years</td></tr>
</table>
<h2>Marketing (with your consent)</h2>
<table class="ltable"><tr><th>Name</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr>
<tr><td>_fbp, fr</td><td>Meta (Facebook / Instagram)</td><td>Measuring ad performance and showing relevant ads</td><td>3 months</td></tr>
<tr><td>__gads, __gpi, IDE</td><td>Google AdSense (when Google ads are shown on the site)</td><td>Showing ads, frequency capping and ad measurement</td><td>up to 13 months</td></tr>
</table>
<p><b>Google ads:</b> Google AdSense ads may be shown on this site. Third-party vendors, including Google, use cookies to serve ads based on your prior visits to this website or other websites. Google’s use of advertising cookies enables it and its partners to serve ads to you based on your visits to this site and/or other sites on the Internet. You may opt out of personalised advertising by visiting <a href="https://adssettings.google.com" target="_blank" rel="noopener">Google Ads Settings</a>, and opt out of third-party vendors’ cookies at <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">www.aboutads.info</a>. These cookies are only loaded if you allow “Marketing and ads” in the cookie settings on our site. How Google uses data: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>.</p>
<h2>Third-party content</h2>
<p>If a listing contains a video (YouTube privacy-enhanced mode, Vimeo) or a virtual tour (Matterport, Kuula etc.), the provider may set its own cookies when the content is played. Maps are shown with OpenStreetMap and set no cookies.</p>
<h2>Managing cookies in your browser</h2>
<p>You can delete or block cookies in your browser settings. If you block essential cookies, features such as logging in and favourites may not work. More details: <a href="{privacy_url}">Privacy &amp; Personal Data</a>.</p>
H,
                'ru' => <<<'H'
<p class="sub">Последнее обновление: {date}</p>
<p>Cookie — небольшие файлы, которые сохраняются в браузере при посещении сайта; с той же целью мы используем локальное хранилище браузера. Необходимые cookie нужны для работы сайта. Аналитические и маркетинговые cookie используются <b>только с вашего согласия</b>. Изменить выбор можно в любой момент по ссылке <b>«Настройки cookie»</b> внизу каждой страницы.</p>
<h2>Необходимые (всегда включены)</h2>
<table class="ltable"><tr><th>Название</th><th>Назначение</th><th>Срок</th></tr>
<tr><td>wordpress_logged_in_*, wordpress_sec_*, wordpress_test_cookie</td><td>Сессия и безопасность авторизованных участников</td><td>Сессия / 14 дней</td></tr>
<tr><td>ec_cur</td><td>Выбранная валюта</td><td>1 год</td></tr>
<tr><td>ec_favs</td><td>Избранные объявления</td><td>1 год</td></tr>
<tr><td>ac_consent</td><td>Ваш выбор по cookie</td><td>6 месяцев</td></tr>
<tr><td>__cf_bm, cf_clearance (Cloudflare)</td><td>Защита от ботов и атак; проверка Turnstile</td><td>30 мин – 1 год</td></tr>
<tr><td>Локальное хранилище: заметки, недавние поиски, напоминание об установке приложения</td><td>Ваши личные заметки и недавние поиски (только на вашем устройстве)</td><td>Пока вы их не удалите</td></tr>
</table>
<h2>Аналитические (с вашего согласия)</h2>
<table class="ltable"><tr><th>Название</th><th>Поставщик</th><th>Назначение</th><th>Срок</th></tr>
<tr><td>_ga, _ga_*</td><td>Google Analytics</td><td>Статистика посещений; IP-адреса анонимизируются</td><td>2 года</td></tr>
</table>
<h2>Маркетинговые (с вашего согласия)</h2>
<table class="ltable"><tr><th>Название</th><th>Поставщик</th><th>Назначение</th><th>Срок</th></tr>
<tr><td>_fbp, fr</td><td>Meta (Facebook / Instagram)</td><td>Оценка эффективности и показ релевантной рекламы</td><td>3 месяца</td></tr>
<tr><td>__gads, __gpi, IDE</td><td>Google AdSense (если на сайте показывается реклама Google)</td><td>Показ рекламы, ограничение частоты и измерение эффективности</td><td>до 13 месяцев</td></tr>
</table>
<p><b>Реклама Google:</b> на сайте может показываться реклама Google AdSense. Сторонние поставщики, в том числе Google, используют файлы cookie для показа рекламы с учётом ваших предыдущих посещений этого и других сайтов. Рекламные cookie позволяют Google и его партнёрам показывать вам рекламу на основе посещений этого сайта и/или других сайтов в интернете. Отключить персонализированную рекламу можно в <a href="https://adssettings.google.com" target="_blank" rel="noopener">настройках рекламы Google</a>, а cookie сторонних поставщиков — на <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">www.aboutads.info</a>. Эти cookie загружаются только если вы разрешили «Маркетинг и реклама» в настройках cookie на нашем сайте. Как Google использует данные: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>.</p>
<h2>Сторонний контент</h2>
<p>Если в объявлении есть видео (YouTube в режиме повышенной конфиденциальности, Vimeo) или виртуальный тур (Matterport, Kuula и др.), при воспроизведении поставщик может устанавливать свои cookie. Карты показываются через OpenStreetMap и cookie не устанавливают.</p>
<h2>Управление cookie в браузере</h2>
<p>Вы можете удалить или заблокировать cookie в настройках браузера. При блокировке необходимых cookie вход в аккаунт и избранное могут не работать. Подробнее: <a href="{privacy_url}">Конфиденциальность</a>.</p>
H,
            ],
        ],

        'terms' => [
            'slug' => ['tr' => 'kullanim-sartlari', 'en' => 'terms', 'ru' => 'usloviya'],
            'title' => ['tr' => 'Kullanım ve Üyelik Şartları', 'en' => 'Terms of Use & Membership', 'ru' => 'Условия использования и членства'],
            'html' => [
                'tr' => <<<'H'
<p class="sub">Son güncelleme: {date}</p>
<p>Bu şartlar <b>{site}</b> adresindeki {brand} platformunun ziyaretçiler ve üyeler tarafından kullanımını düzenler. Siteyi kullanarak ya da üye olarak bu şartları kabul etmiş olursunuz. Hizmet sağlayıcı: {company}, {address}.</p>

<h2>1. Platformun niteliği</h2>
<p>{brand} bir <b>ilan platformudur</b>. İlanları üyeler (emlak ofisleri, inşaat firmaları, danışmanlar ve mülk sahipleri) yayınlar. {brand} ilan konusu mülklerin sahibi, satıcısı, kiraya vereni ya da aracısı değildir; alım-satım, kiralama ve ödeme işlemlerinin tarafı olmaz ve bunlardan sorumlu tutulamaz. Fiyat endeksi, bölge karşılaştırmaları ve döviz kurları bilgi amaçlıdır; yatırım tavsiyesi değildir.</p>

<h2>2. Üyelik</h2>
<ul>
<li>Üye olmak için 18 yaşını doldurmuş olmanız ve doğru, güncel bilgi vermeniz gerekir. Firma hesapları firmayı temsile yetkili kişi tarafından açılmalıdır.</li>
<li>Emlak ofisi ve inşaat firması hesapları onaydan sonra kullanılabilir. Bireysel danışman ve mülk sahibi ilanları kontrol edildikten sonra yayınlanabilir.</li>
<li>Hesap güvenliğinden, şifrenizden ve API anahtarlarınızdan siz sorumlusunuz. Yetkisiz kullanımı fark ederseniz hemen bize bildirin.</li>
<li>Ofis hesapları, kayıtlı danışmanları ekiplerine ekleyebilir. Ofis, paketini ekip üyeleriyle paylaşıp paylaşmamaya ve kullanım sınırlarına kendisi karar verir. Ekip üyesinin ilanları kendi sorumluluğundadır; ofis de ekibinin ilanlarının doğruluğunu gözetmekle yükümlüdür.</li>
<li><b>Ofis portföyü:</b> Danışmanın bir ofisin ekibindeyken açtığı ilanlar o ofisin portföyüne aittir. Danışman ekipten ayrılır, çıkarılır ya da başka bir ofise geçerse bu ilanlar taslağa alınır, yayından kalkar ve kilitlenir; danışman bunları değiştiremez, yayınlayamaz ve silemez. Ofisin başvurusu üzerine {brand}, ilanları ofisin bildirdiği kullanıcıya (ofis hesabı ya da ofisin ekibindeki bir danışman) aktarır ya da ofis istemezse kilidi kaldırıp danışmana bırakır. Danışmanın ofise katılmadan önce açtığı ilanlar bu kuraldan etkilenmez.</li>
</ul>

<h2>3. İlan kuralları</h2>
<ul>
<li>İlan gerçek, güncel ve satılık/kiralık olarak <b>gerçekten mevcut</b> bir mülke ait olmalıdır. Satılan ya da kiralanan mülkün ilanı en geç 3 gün içinde “satıldı” işaretlenmeli ya da yayından kaldırılmalıdır.</li>
<li>Fiyat, metrekare, oda sayısı, konum ve satılıkta <b>tapu türü</b> doğru belirtilmelidir. Yanıltıcı fiyat (ör. “1 £”) ve yanıltıcı konum yasaktır.</li>
<li>Fotoğraf, video ve metinler ilan verene ait olmalı ya da kullanma izni bulunmalıdır. Başkasının fotoğrafını ya da ilanını kopyalamak yasaktır. Kopya ilanlar otomatik denetlenir.</li>
<li>Yapay zekâ ile üretilmiş görseller mülkün gerçek fotoğrafı gibi sunulamaz.</li>
<li>Aynı mülk için birden fazla ilan açılamaz. İlan, mülk dışında ürün ya da hizmet reklamı içeremez.</li>
<li>Yasa dışı, ayrımcı, hakaret içeren, kişisel verileri izinsiz paylaşan ya da üçüncü kişilerin haklarını ihlal eden içerik yasaktır.</li>
<li>İlan sahibi, ilanın ve içeriğinin yürürlükteki mevzuata (emlak, imar, tapu ve yabancıların mülk edinimi kuralları dâhil) uygunluğundan tek başına sorumludur.</li>
</ul>

<h2>4. Moderasyon</h2>
<p>{brand}, bu şartlara aykırı gördüğü ilanı bildirimde bulunarak ya da bulunmadan düzenletme, yayından kaldırma, reddetme hakkına; kuralları tekrar tekrar ihlal eden hesabı askıya alma ya da kapatma hakkına sahiptir. Ziyaretçiler hatalı ilanı ilan sayfasından bildirebilir.</p>

<h2>5. İçerik lisansı</h2>
<p>Üye, yüklediği içeriklerin (metin, fotoğraf, 360° fotoğraf, video, logo) platformda, sitenin TR/EN/RU sürümlerinde, arama motorlarında, sitenin sosyal medya hesaplarında ve tanıtımlarda gösterilmesi, boyutlandırılması, çevrilmesi ve filigran olmadan yayınlanması için {brand}’a ücretsiz, münhasır olmayan bir kullanım izni verir. İzin, içerik silinince sona erer; önceden yapılmış paylaşımların kaldırılması makul süre alabilir.</p>

<h2>6. Ücretli hizmetler</h2>
<p>İlan vermek ücretsizdir. Otomasyon (başka sitelere aktarım, sosyal medya paylaşımı, Telegram) ve vitrin hizmetleri ücretli paketlerle sunulur. Satın alma, ödeme ve iade koşulları <a href="{sales_url}">Paket Satış ve İade Koşulları</a>’nda yer alır. Başka ilan sitelerine aktarım, üyenin kendi hesabıyla ve kendi tarayıcısında yapılır. O sitelerin kurallarına uymak üyenin sorumluluğundadır.</p>

<h2>7. Yasak kullanımlar</h2>
<p>Siteden otomatik araçlarla veri toplamak (scraping), ilanları izinsiz başka yerlerde yayınlamak, sistemin güvenliğini aşmaya çalışmak, sahte hesap açmak, spam ya da toplu mesaj göndermek ve iletişim formlarını reklam amaçlı kullanmak yasaktır.</p>

<h2>8. Sorumluluğun sınırlandırılması</h2>
<p>Site “olduğu gibi” sunulur. Kesintisiz ya da hatasız çalışacağı garanti edilmez. {brand}; ilanların doğruluğundan, kullanıcılar arasındaki görüşme ve işlemlerden, üçüncü taraf hizmetlerin (Telegram, Meta, bankalar, diğer ilan siteleri) kesinti ya da kurallarından doğan zararlardan, yasaların izin verdiği ölçüde sorumlu değildir. Alıcıların satın almadan önce tapuyu ve belgeleri bağımsız bir avukata kontrol ettirmesi önerilir.</p>

<h2>9. Fikrî mülkiyet</h2>
<p>Sitenin tasarımı, yazılımı, logosu ve {brand} markası üzerindeki tüm haklar {company} tarafından saklıdır; izinsiz kullanılamaz.</p>

<h2>10. Hesabın kapatılması</h2>
<p>Üyeliğinizi istediğiniz zaman {email} adresine yazarak kapatabilirsiniz. Ücretli paketlerin iadesi için <a href="{sales_url}">Paket Satış ve İade Koşulları</a> geçerlidir.</p>

<h2>11. Uygulanacak hukuk</h2>
<p>Bu şartlara KKTC hukuku uygulanır. Uyuşmazlıklarda KKTC mahkemeleri yetkilidir. Tüketici sıfatıyla işlem yapan kullanıcıların yasal hakları saklıdır.</p>

<h2>12. Değişiklikler</h2>
<p>Bu şartları güncelleyebiliriz. Önemli değişiklikleri yürürlükten en az 7 gün önce sitede ve e-postayla duyururuz.</p>
H,
                'en' => <<<'H'
<p class="sub">Last updated: {date}</p>
<p>These terms govern the use of the {brand} platform at <b>{site}</b> by visitors and members. By using the site or registering you accept these terms. Service provider: {company}, {address}.</p>

<h2>1. Nature of the platform</h2>
<p>{brand} is a <b>listing platform</b>. Listings are published by members (estate agencies, developers, agents and owners). {brand} is not the owner, seller, landlord or broker of the advertised properties, is not a party to sales, rentals or payments between users, and cannot be held liable for them. The price index, area comparisons and exchange rates are for information only and are not investment advice.</p>

<h2>2. Membership</h2>
<ul>
<li>You must be at least 18 and provide accurate, up-to-date information. Company accounts must be opened by a person authorised to represent the company.</li>
<li>Agency and developer accounts can be used after approval; listings from independent agents and owners may be reviewed before publication.</li>
<li>You are responsible for the security of your account, password and API keys. Report any unauthorised use immediately.</li>
<li>Agency accounts may add registered agents to their team. The agency decides whether to share its package with team members and sets usage limits. Team members are responsible for their own listings; the agency also oversees the accuracy of its team’s listings.</li>
<li><b>Agency portfolio:</b> Listings an agent creates while in an agency’s team belong to that agency’s portfolio. If the agent leaves the team, is removed or moves to another agency, these listings are moved to draft, taken offline and locked; the agent cannot edit, publish or delete them. At the agency’s request, {brand} transfers them to the user the agency names (the agency account or an agent in its team), or, if the agency does not want them, unlocks them and leaves them with the agent. Listings the agent created before joining the agency are not affected.</li>
</ul>

<h2>3. Listing rules</h2>
<ul>
<li>A listing must relate to a <b>real and currently available</b> property. Sold or let properties must be marked “sold” or removed within 3 days.</li>
<li>Price, area, rooms, location and, for sales, the <b>title deed type</b> must be accurate. Misleading prices (e.g. “£1”) or locations are prohibited.</li>
<li>Photos, videos and texts must belong to the advertiser or be used with permission. Copying others’ photos or listings is prohibited; duplicates are detected automatically.</li>
<li>AI-generated images may not be presented as real photos of the property.</li>
<li>Only one listing per property. Listings may not advertise anything other than the property.</li>
<li>Illegal, discriminatory or defamatory content, unauthorised sharing of personal data and infringement of third-party rights are prohibited.</li>
<li>The advertiser is solely responsible for compliance of the listing with applicable law (including real estate, zoning, title deed and foreign ownership rules).</li>
</ul>

<h2>4. Moderation</h2>
<p>{brand} may, with or without notice, require changes to, remove or reject any listing it considers in breach of these terms, and may suspend or close accounts that repeatedly breach them. Visitors can report incorrect listings from the listing page.</p>

<h2>5. Content licence</h2>
<p>The member grants {brand} a free, non-exclusive licence to display, resize, translate and publish the content they upload (text, photos, 360° photos, videos, logo) on the platform, its TR/EN/RU versions, search engines, the site’s social media accounts and promotions. The licence ends when the content is deleted; removing earlier shares may take reasonable time.</p>

<h2>6. Paid services</h2>
<p>Listing is free. Automation (transfer to other portals, social media sharing, Telegram) and showcase services are offered in paid packages; purchase, payment and refund terms are set out in the <a href="{sales_url}">Package Sales &amp; Refund Terms</a>. Transfers to other portals are made with the member’s own account and browser; complying with those sites’ rules is the member’s responsibility.</p>

<h2>7. Prohibited use</h2>
<p>Automated data collection (scraping), republishing listings without permission, attempting to bypass security, fake accounts, spam or bulk messaging, and using contact forms for advertising are prohibited.</p>

<h2>8. Limitation of liability</h2>
<p>The site is provided “as is”; uninterrupted or error-free operation is not guaranteed. To the extent permitted by law, {brand} is not liable for the accuracy of listings, for negotiations and transactions between users, or for losses caused by outages or rules of third-party services (Telegram, Meta, banks, other portals). Buyers are advised to have the title deed and documents checked by an independent lawyer before purchase.</p>

<h2>9. Intellectual property</h2>
<p>The design, software, logo and the {brand} brand belong to {company} and may not be used without permission.</p>

<h2>10. Closing your account</h2>
<p>You can close your account at any time by writing to {email}. Refunds of paid packages are governed by the <a href="{sales_url}">Package Sales &amp; Refund Terms</a>.</p>

<h2>11. Governing law</h2>
<p>These terms are governed by TRNC law and TRNC courts have jurisdiction. Statutory rights of users acting as consumers are not affected.</p>

<h2>12. Changes</h2>
<p>We may update these terms. Significant changes will be announced on the site and by email at least 7 days before they take effect.</p>
H,
                'ru' => <<<'H'
<p class="sub">Последнее обновление: {date}</p>
<p>Настоящие условия регулируют использование платформы {brand} по адресу <b>{site}</b> посетителями и участниками. Пользуясь сайтом или регистрируясь, вы принимаете эти условия. Поставщик услуг: {company}, {address}.</p>

<h2>1. Характер платформы</h2>
<p>{brand} — <b>площадка объявлений</b>. Объявления публикуют участники (агентства, застройщики, агенты и собственники). {brand} не является владельцем, продавцом, арендодателем или посредником по объектам, не участвует в сделках, аренде и расчётах между пользователями и не несёт за них ответственности. Индекс цен, сравнения по районам и курсы валют носят информационный характер и не являются инвестиционной рекомендацией.</p>

<h2>2. Членство</h2>
<ul>
<li>Участнику должно быть не менее 18 лет; он обязан указывать достоверные и актуальные данные. Аккаунт компании открывает уполномоченное лицо.</li>
<li>Аккаунты агентств и застройщиков используются после одобрения; объявления частных агентов и собственников могут проверяться перед публикацией.</li>
<li>Вы отвечаете за безопасность аккаунта, пароля и API-ключей. Сообщайте о несанкционированном доступе немедленно.</li>
<li>Агентства могут добавлять зарегистрированных агентов в свою команду. Агентство само решает, делиться ли пакетом с участниками команды, и устанавливает лимиты. Участник команды отвечает за свои объявления; агентство также контролирует достоверность объявлений своей команды.</li>
<li><b>Портфель агентства:</b> объявления, созданные агентом в составе команды агентства, принадлежат портфелю этого агентства. Если агент покидает команду, исключается из неё или переходит в другое агентство, такие объявления переводятся в черновики, снимаются с публикации и блокируются; агент не может их изменять, публиковать или удалять. По запросу агентства {brand} передаёт их указанному агентством пользователю (аккаунту агентства или агенту его команды) либо, если агентство от них отказывается, снимает блокировку и оставляет их агенту. Объявления, созданные агентом до вступления в агентство, это правило не затрагивает.</li>
</ul>

<h2>3. Правила объявлений</h2>
<ul>
<li>Объявление должно относиться к <b>реально существующему и доступному</b> объекту. Проданный или сданный объект нужно отметить «продано» или снять в течение 3 дней.</li>
<li>Цена, площадь, комнаты, расположение и для продажи — <b>тип документа о собственности (тапу)</b> должны быть точными. Вводящие в заблуждение цены (например «1 £») и адреса запрещены.</li>
<li>Фото, видео и тексты должны принадлежать автору или использоваться с разрешения. Копирование чужих фото и объявлений запрещено; дубликаты выявляются автоматически.</li>
<li>Изображения, созданные ИИ, нельзя выдавать за реальные фото объекта.</li>
<li>Один объект — одно объявление. Нельзя рекламировать что-либо кроме объекта.</li>
<li>Запрещены незаконный, дискриминационный или оскорбительный контент, распространение чужих персональных данных без согласия и нарушение прав третьих лиц.</li>
<li>Автор объявления единолично отвечает за соответствие объявления законодательству (включая правила о недвижимости, застройке, тапу и приобретении недвижимости иностранцами).</li>
</ul>

<h2>4. Модерация</h2>
<p>{brand} вправе с уведомлением или без него требовать исправления, снимать или отклонять объявления, нарушающие условия, а также приостанавливать или закрывать аккаунты при повторных нарушениях. Посетители могут сообщить о неверном объявлении со страницы объявления.</p>

<h2>5. Лицензия на контент</h2>
<p>Участник предоставляет {brand} бесплатную неисключительную лицензию показывать, масштабировать, переводить и публиковать загруженный контент (тексты, фото, фото 360°, видео, логотип) на платформе, в её версиях TR/EN/RU, в поисковых системах, в соцсетях сайта и в рекламе. Лицензия прекращается при удалении контента; удаление ранее сделанных публикаций может занять разумное время.</p>

<h2>6. Платные услуги</h2>
<p>Размещение объявлений бесплатно. Автоматизация (перенос на другие порталы, публикация в соцсетях, Telegram) и витрина предоставляются в платных пакетах; условия покупки, оплаты и возврата — в документе <a href="{sales_url}">Условия продажи пакетов и возврата</a>. Перенос на другие порталы выполняется с аккаунта и в браузере участника; соблюдение правил этих сайтов — обязанность участника.</p>

<h2>7. Запрещённое использование</h2>
<p>Запрещены автоматический сбор данных (скрейпинг), публикация объявлений в других местах без разрешения, попытки обойти защиту, фейковые аккаунты, спам и массовые рассылки, использование форм связи в рекламных целях.</p>

<h2>8. Ограничение ответственности</h2>
<p>Сайт предоставляется «как есть»; бесперебойная и безошибочная работа не гарантируется. В пределах, допускаемых законом, {brand} не отвечает за достоверность объявлений, переговоры и сделки между пользователями, а также за убытки из-за сбоев или правил сторонних сервисов (Telegram, Meta, банки, другие порталы). Покупателям рекомендуется до покупки проверить тапу и документы у независимого юриста.</p>

<h2>9. Интеллектуальная собственность</h2>
<p>Дизайн, программное обеспечение, логотип и бренд {brand} принадлежат {company} и не могут использоваться без разрешения.</p>

<h2>10. Закрытие аккаунта</h2>
<p>Вы можете закрыть аккаунт в любое время, написав на {email}. Возврат за платные пакеты регулируется <a href="{sales_url}">Условиями продажи пакетов и возврата</a>.</p>

<h2>11. Применимое право</h2>
<p>К условиям применяется право ТРСК; споры рассматривают суды ТРСК. Законные права пользователей-потребителей сохраняются.</p>

<h2>12. Изменения</h2>
<p>Мы можем обновлять условия. О существенных изменениях сообщим на сайте и по e-mail не менее чем за 7 дней до их вступления в силу.</p>
H,
            ],
        ],

        'sales' => [
            'slug' => ['tr' => 'paket-satis-kosullari', 'en' => 'package-terms', 'ru' => 'usloviya-paketov'],
            'title' => ['tr' => 'Paket Satış, Ödeme ve İade Koşulları', 'en' => 'Package Sales, Payment & Refund Terms', 'ru' => 'Условия продажи пакетов, оплаты и возврата'],
            'html' => [
                'tr' => <<<'H'
<p class="sub">Son güncelleme: {date}</p>
<p>Bu koşullar, {brand} üyelerinin satın aldığı otomasyon ve vitrin paketlerinin satışını düzenler. Satıcı: {company}, {address} · {email}{taxno_line}</p>

<h2>1. Hizmetin konusu</h2>
<ul>
<li><b>Otomasyon paketleri:</b> belirli bir süre ve ilan kotası içinde 101evler / hangiev formlarının üyenin tarayıcısında doldurulması (Chrome eklentisi), Facebook ve Instagram’da otomatik paylaşım, Telegram ile ilan verme.</li>
<li><b>Vitrin hizmetleri:</b> öne çıkarma, ana sayfa vitrini, kategori vitrini, acil etiketi (her biri paketinde belirtilen gün sayısınca) ve yukarı taşıma.</li>
</ul>
<p>Paketlerin içeriği, süresi, kotası ve fiyatı satın alma ekranında gösterilir. Fiyatlara aksi belirtilmedikçe vergiler dâhildir.</p>

<h2>2. Sipariş ve ödeme</h2>
<ul>
<li><b>Havale / EFT:</b> Siparişten sonra gösterilen hesaba, açıklamaya sipariş kodunu yazarak ödeme yapılır ve dekont Panelim → Paketim bölümüne yüklenir. Ödeme doğrulandığında paket tanımlanır (genellikle 1 iş günü içinde).</li>
<li><b>Kart:</b> Ödeme, bankanın 3D Secure güvenli ödeme sayfasında yapılır. Onay geldiği anda paket otomatik tanımlanır. Kart bilgileri {brand} tarafından görülmez ve saklanmaz.</li>
<li>Paket süresi tanımlandığı andan başlar. Bitmeden 3 gün önce e-posta ve Telegram ile hatırlatma yapılır. Paketler kendiliğinden yenilenmez.</li>
</ul>

<h2>3. Kota ve kullanım</h2>
<ul>
<li>Otomasyon kotası, paket süresince işlenen <b>farklı ilan</b> sayısıdır. Bir ilan hem 101evler’e, hem hangiev’e, hem sosyal medyaya gönderilse de kotadan 1 düşer.</li>
<li>Kullanılmayan kota ve vitrin kredileri paket süresi bitince sona erer, sonraki döneme devretmez.</li>
<li>Vitrin yalnızca yayındaki ilana uygulanır. Uygulanmış bir vitrin başka bir ilana aktarılamaz.</li>
<li>Ofis paketleri, ofisin izin verdiği ve belirlediği sınırlar içinde ekip üyelerince kullanılabilir.</li>
</ul>

<h2>4. Cayma ve iade</h2>
<ul>
<li>Paketler dijital hizmettir ve tanımlandığı anda ifasına başlanır. Hiç kullanılmamış bir paket için, tanımlandığı günden itibaren <b>14 gün içinde</b> {email} adresine yazarak cayma hakkınızı kullanabilirsiniz. Ödeme 14 gün içinde aynı yöntemle iade edilir.</li>
<li>Kullanılmaya başlanmış paketlerde (en az bir ilan işlenmiş ya da bir vitrin uygulanmışsa) bedel iadesi yapılmaz. Ancak hizmet bizim kaynaklı bir sebeple sunulamadıysa kullanılmayan kısım için orantılı iade ya da süre uzatımı yapılır.</li>
<li>101evler, hangiev, Meta (Facebook/Instagram) gibi üçüncü taraf sitelerin kural, erişim ya da teknik değişikliklerinden kaynaklanan kesintilerde süre uzatımı yapılır; bu durum tek başına bedel iadesi gerektirmez.</li>
<li>Kurallara aykırılık nedeniyle askıya alınan ya da kapatılan hesaplarda kalan süre için iade yapılmaz.</li>
</ul>

<h2>5. Fatura</h2>
<p>Ödeme alınan her sipariş için talep hâlinde fatura düzenlenir. Fatura bilgilerinizi {email} adresine iletin.</p>

<h2>6. Şikâyet ve uyuşmazlık</h2>
<p>Şikâyetlerinizi {email} adresine iletebilirsiniz. Uyuşmazlıklarda KKTC hukuku uygulanır ve KKTC mahkemeleri yetkilidir. Tüketicinin yasal hakları saklıdır.</p>
H,
                'en' => <<<'H'
<p class="sub">Last updated: {date}</p>
<p>These terms govern the sale of automation and showcase packages purchased by {brand} members. Seller: {company}, {address} · {email}{taxno_line}</p>

<h2>1. Services</h2>
<ul>
<li><b>Automation packages:</b> within a set period and listing quota, filling 101evler / hangiev forms in the member’s own browser (Chrome extension), automatic sharing on Facebook and Instagram, and listing via Telegram.</li>
<li><b>Showcase services:</b> featured, homepage showcase, category showcase, urgent label (each for the number of days stated in the package) and bump to top.</li>
</ul>
<p>Package contents, duration, quota and price are shown on the purchase screen. Prices include taxes unless stated otherwise.</p>

<h2>2. Order and payment</h2>
<ul>
<li><b>Bank transfer:</b> pay to the account shown after ordering, quoting the order code, and upload the receipt in My account → My package. The package is activated once payment is confirmed (usually within 1 business day).</li>
<li><b>Card:</b> payment is made on the bank’s 3D Secure page; the package is activated automatically on approval. Card details are never seen or stored by {brand}.</li>
<li>The package period starts on activation. A reminder is sent by email and Telegram 3 days before expiry. Packages do not renew automatically.</li>
</ul>

<h2>3. Quota and usage</h2>
<ul>
<li>The automation quota is the number of <b>distinct listings</b> processed during the package period; a listing sent to 101evler, hangiev and social media counts once.</li>
<li>Unused quota and showcase credits expire at the end of the package period and do not carry over.</li>
<li>Showcases apply only to published listings and cannot be transferred to another listing once applied.</li>
<li>Agency packages may be used by team members within the permissions and limits set by the agency.</li>
</ul>

<h2>4. Cancellation and refunds</h2>
<ul>
<li>Packages are digital services whose performance starts on activation. For a <b>completely unused</b> package you may cancel within <b>14 days</b> of activation by writing to {email}; the payment is refunded within 14 days using the same method.</li>
<li>Once a package has been used (at least one listing processed or one showcase applied) no refund is given; however, if the service could not be provided for reasons attributable to us, the unused part is refunded pro rata or the period is extended.</li>
<li>For interruptions caused by changes in the rules, access or technology of third-party sites (such as 101evler, hangiev, Meta), the period is extended; this alone does not entitle a refund.</li>
<li>No refund is given for the remaining period of accounts suspended or closed for breach of the rules.</li>
</ul>

<h2>5. Invoices</h2>
<p>An invoice is issued on request for every paid order; send your invoice details to {email}.</p>

<h2>6. Complaints and disputes</h2>
<p>Send complaints to {email}. TRNC law applies and TRNC courts have jurisdiction. Consumers’ statutory rights are not affected.</p>
H,
                'ru' => <<<'H'
<p class="sub">Последнее обновление: {date}</p>
<p>Настоящие условия регулируют продажу пакетов автоматизации и витрины участникам {brand}. Продавец: {company}, {address} · {email}{taxno_line}</p>

<h2>1. Предмет услуг</h2>
<ul>
<li><b>Пакеты автоматизации:</b> в течение срока и в пределах квоты объявлений — заполнение форм 101evler / hangiev в браузере участника (расширение Chrome), автоматическая публикация в Facebook и Instagram, размещение через Telegram.</li>
<li><b>Витрина:</b> выделение, витрина на главной, витрина категории, метка «срочно» (каждая — на указанное в пакете число дней) и поднятие объявления.</li>
</ul>
<p>Состав, срок, квота и цена пакета указываются на экране покупки. Цены включают налоги, если не указано иное.</p>

<h2>2. Заказ и оплата</h2>
<ul>
<li><b>Банковский перевод:</b> оплатите на указанный после заказа счёт, указав код заказа, и загрузите квитанцию в Кабинет → Мой пакет. Пакет активируется после подтверждения оплаты (обычно в течение 1 рабочего дня).</li>
<li><b>Карта:</b> оплата производится на защищённой странице банка 3D Secure; пакет активируется автоматически после одобрения. Данные карты {brand} не видит и не хранит.</li>
<li>Срок пакета начинается с момента активации. За 3 дня до окончания приходит напоминание по e-mail и в Telegram. Пакеты не продлеваются автоматически.</li>
</ul>

<h2>3. Квота и использование</h2>
<ul>
<li>Квота автоматизации — число <b>разных объявлений</b>, обработанных за срок пакета; объявление, отправленное на 101evler, hangiev и в соцсети, считается один раз.</li>
<li>Неиспользованная квота и кредиты витрины сгорают по окончании срока и не переносятся.</li>
<li>Витрина применяется только к опубликованным объявлениям и не переносится на другое объявление после применения.</li>
<li>Пакеты агентства могут использовать участники команды в пределах разрешений и лимитов, установленных агентством.</li>
</ul>

<h2>4. Отказ и возврат</h2>
<ul>
<li>Пакеты — цифровые услуги, исполнение которых начинается с момента активации. От <b>полностью неиспользованного</b> пакета можно отказаться в течение <b>14 дней</b> с даты активации, написав на {email}; деньги возвращаются в течение 14 дней тем же способом.</li>
<li>После начала использования (обработано хотя бы одно объявление или применена витрина) возврат не производится; если услуга не была оказана по нашей вине, неиспользованная часть возвращается пропорционально или срок продлевается.</li>
<li>При перебоях из-за изменения правил, доступа или технологий сторонних сайтов (101evler, hangiev, Meta и др.) срок продлевается; само по себе это не даёт права на возврат.</li>
<li>За оставшийся срок аккаунтов, приостановленных или закрытых за нарушение правил, возврат не производится.</li>
</ul>

<h2>5. Счета</h2>
<p>По запросу на каждый оплаченный заказ выставляется счёт; реквизиты отправьте на {email}.</p>

<h2>6. Жалобы и споры</h2>
<p>Жалобы направляйте на {email}. Применяется право ТРСК, споры рассматривают суды ТРСК. Законные права потребителей сохраняются.</p>
H,
            ],
        ],
    ];
}
