<?php
/**
 * Sıkça sorulan sorular (0.7): /tr/sss/ · /en/faq/ · /ru/voprosy/
 * Üç grup: ev arayanlar, emlakçılar ve ilan sahipleri, paketler ve otomasyon.
 * Hukuki konular genel bilgidir; kesin rakam verilmez, avukata yönlendirilir.
 * Cevaplarda %site% site adıyla değiştirilir.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ae_faq(): array
{
    return [
        'buyers' => [
            'title' => ['Ev arayanlar', 'Home seekers', 'Покупателям и арендаторам'],
            'items' => [
                [
                    ['%site% üzerinden ev almak ya da kiralamak ücretli mi?', 'Do I pay %site% to buy or rent a home?', 'Нужно ли платить %site% за покупку или аренду?'],
                    ['Hayır. İlanlara bakmak, favorilere eklemek ve ilan sahibine mesaj göndermek ücretsizdir. %site% alım-satıma taraf değildir; anlaşmayı doğrudan ilan sahibiyle (emlak ofisi, inşaat firması, danışman ya da mülk sahibi) yaparsınız. Komisyon olup olmadığını ilan sahibine sorun.',
                     'No. Browsing, saving favourites and messaging advertisers are free. %site% is not a party to the sale or rental; you deal directly with the advertiser (agency, developer, agent or owner). Ask them whether a commission applies.',
                     'Нет. Просмотр, избранное и сообщения бесплатны. %site% не является стороной сделки — вы договариваетесь напрямую с автором объявления (агентство, застройщик, агент или собственник). О комиссии спрашивайте у него.'],
                ],
                [
                    ['Tapu türleri ne anlama geliyor?', 'What do the title deed types mean?', 'Что означают типы титулов?'],
                    ['Kuzey Kıbrıs’ta satılık her ilanda tapu türü yazar: Türk koçanı, eşdeğer, tahsis, vakıf (evkaf), projeden (sözleşmeli) ya da diğer. Her türün kısa açıklaması ilan sayfasındaki “Tapu türü” bölümündedir. Tapu türü satın alma riskini doğrudan etkiler; satın almadan önce tapuyu ve belgeleri mutlaka bağımsız bir avukata kontrol ettirin.',
                     'Every sale listing in North Cyprus shows the title deed type: Turkish title, exchange (eşdeğer), allocation (tahsis), Evkaf (foundation), off-plan (contract) or other. A short explanation of each is in the “Title deed” section of the listing. The deed type directly affects your risk; always have the deed and documents checked by an independent lawyer before buying.',
                     'В каждом объявлении о продаже указан тип титула: турецкий, эквивалентный (eşdeğer), по распределению (tahsis), Эвкаф, строящийся (по договору) или другой. Краткое описание — в разделе «Титул» объявления. Тип титула влияет на риск сделки; перед покупкой обязательно проверьте документы у независимого юриста.'],
                ],
                [
                    ['Yabancılar Kuzey Kıbrıs’ta mülk alabilir mi?', 'Can foreigners buy property in North Cyprus?', 'Могут ли иностранцы покупать недвижимость на Северном Кипре?'],
                    ['Evet. Yabancı alıcıların tapuyu kendi adlarına geçirebilmesi için Bakanlar Kurulu’ndan izin almaları gerekir. Arazi büyüklüğü, süreler ve vergiler gibi koşullar zaman zaman değişir. Sözleşmeyi imzalamadan önce güncel kuralları bir avukata danışın; avukatınız izin başvurusunu ve sözleşmenin Tapu Dairesi’ne kaydını da takip eder.',
                     'Yes. Foreign buyers need permission from the Council of Ministers before the title can be transferred into their name. Conditions such as land size, timings and taxes change from time to time. Check the current rules with a lawyer before signing; your lawyer can also handle the permission application and register the contract at the Land Registry.',
                     'Да. Иностранцу для переоформления титула нужно разрешение Совета министров. Условия (площадь участка, сроки, налоги) периодически меняются. До подписания договора уточните актуальные правила у юриста — он же подаст заявление на разрешение и зарегистрирует договор в земельном реестре.'],
                ],
                [
                    ['Fiyatlar neden farklı para birimlerinde görünüyor?', 'Why are prices shown in different currencies?', 'Почему цены в разных валютах?'],
                    ['İlan sahibi fiyatı kendi para biriminde (çoğunlukla sterlin) girer. Sağ üstteki para birimi seçimiyle tüm fiyatları £, €, $ ya da ₺ olarak görebilirsiniz; parantez içindeki tutar ikinci para birimidir. Kurlar TCMB’den ve (açıksa) gün içi piyasadan otomatik alınır, kaynak ve saat “Döviz çevirici”de yazar. Kesin fiyat her zaman ilan sahibinin girdiği tutardır.',
                     'Advertisers enter the price in their own currency (usually sterling). Use the currency selector at the top to see all prices in £, €, $ or ₺; the amount in brackets is a second currency. Rates are updated automatically (central bank and, when enabled, intraday market rates); the source and time are shown in the currency converter. The binding price is always the advertiser’s original amount.',
                     'Цена указывается в валюте автора объявления (обычно фунты). Переключатель валют вверху показывает цены в £, €, $ или ₺; сумма в скобках — во второй валюте. Курсы обновляются автоматически; источник и время указаны в конвертере. Окончательная цена — сумма, указанная автором.'],
                ],
                [
                    ['İlan sahibiyle nasıl iletişime geçerim?', 'How do I contact the advertiser?', 'Как связаться с автором объявления?'],
                    ['İlan sayfasında “Telefonu göster”, WhatsApp ve “Mesaj gönder” formu vardır. Formdan gönderdiğiniz mesaj ilan sahibine e-posta ve Telegram ile anında ulaşır. Mesajınızda ilan numarasını (#10023 gibi) belirtmeniz işi hızlandırır.',
                     'Each listing has “Show phone”, WhatsApp and a “Send a message” form. Messages from the form reach the advertiser instantly by email and Telegram. Mentioning the reference number (e.g. #10023) speeds things up.',
                     'На странице объявления есть кнопка «Показать телефон», WhatsApp и форма «Написать». Сообщение мгновенно приходит автору по e-mail и в Telegram. Укажите номер объявления (например, #10023).'],
                ],
                [
                    ['İlan güncel mi, satılmış olabilir mi?', 'Is the listing still available?', 'Объявление актуально?'],
                    ['İlan sahipleri satılan ya da kiralanan ilanı “Satıldı” ya da “Pasif” yapar. Yine de yayında olduğu halde satılmış bir ilan görürseniz ilan sayfasındaki “Hatalı ilan bildir”i kullanın; ekibimiz inceler.',
                     'Advertisers mark sold or let properties as “Sold” or “Inactive”. If you still find an unavailable property, use “Report this listing” on the page and our team will check it.',
                     'Авторы отмечают проданные объекты как «Продано» или «Неактивно». Если вы нашли неактуальное объявление, нажмите «Сообщить об ошибке» — мы проверим.'],
                ],
                [
                    ['Kopya ya da yanıltıcı ilanlara karşı ne yapıyorsunuz?', 'What do you do about duplicate or misleading listings?', 'Как вы боретесь с дубликатами и обманом?'],
                    ['Her fotoğrafın görsel parmak izi ve her açıklamanın özeti otomatik karşılaştırılır. Aynı fotoğraf ya da metin başka bir ilanda yayındaysa ilanı ekleyen uyarılır, ekibimize bildirim düşer. Ziyaretçi bildirimleri de onay panelinde incelenir. Kapora ya da ödeme isteyen, mülkü göstermeden para talep eden ilanları lütfen bildirin; mülkü görmeden ve avukatınız kontrol etmeden ödeme yapmayın.',
                     'Every photo gets a visual fingerprint and every description a checksum, compared automatically. If the same photo or text is already live elsewhere, the advertiser is warned and our team is notified. Visitor reports are reviewed too. Please report anyone asking for a deposit before showing the property; never pay before viewing and before your lawyer has checked.',
                     'Все фото и описания автоматически сравниваются. При совпадении автор получает предупреждение, а мы — уведомление. Жалобы посетителей проверяются. Сообщайте о просьбах внести задаток до показа объекта; не платите, не увидев объект и без проверки юристом.'],
                ],
                [
                    ['360° tur nedir?', 'What is a 360° tour?', 'Что такое 360° тур?'],
                    ['Bazı ilanlarda odaların 360° fotoğrafı vardır. “360° turu başlat”a basıp fareyle, parmağınızla ya da telefonunuzu döndürerek etrafa bakabilir, odalar arasında geçebilirsiniz. Aramada “🌐 360°” kutusunu işaretleyerek sadece bu ilanları görebilirsiniz.',
                     'Some listings include 360° photos of each room. Press “Start 360° tour” and look around by dragging or turning your phone, switching between rooms. Tick “🌐 360°” in the search to see only these listings.',
                     'В некоторых объявлениях есть 360° фото комнат. Нажмите «Начать 360° тур» и осматривайтесь, перемещая палец или поворачивая телефон. Отметьте «🌐 360°» в поиске, чтобы видеть только такие объявления.'],
                ],
                [
                    ['Fiyat endeksi nasıl hesaplanıyor?', 'How is the price index calculated?', 'Как считается индекс цен?'],
                    ['Fiyat endeksi, sitede yayında olan konut ilanlarının metrekare başına istenen fiyatlarının medyanıdır (şehir bazında, her ay). Gerçekleşen satış fiyatı değil, ilan fiyatıdır; piyasanın yönünü görmek içindir. İlan sayfasındaki “bölge ortalamasının %x altında/üstünde” bilgisi de buna göre hesaplanır.',
                     'The price index is the monthly median asking price per m² of live residential listings, by town. It reflects asking prices, not completed sales, and shows the market direction. The “x% below/above the area median” note on listings uses the same figure.',
                     'Индекс — медиана цены предложения за м² по жилым объявлениям, по городам и месяцам. Это цены предложения, а не сделок. На ней же основана отметка «на x% ниже/выше медианы района».'],
                ],
            ],
        ],
        'agents' => [
            'title' => ['Emlakçılar ve ilan sahipleri', 'Agents and owners', 'Агентам и собственникам'],
            'items' => [
                [
                    ['İlan vermek ücretli mi?', 'Is listing a property free?', 'Размещение платное?'],
                    ['Hayır, ilan vermek ücretsizdir ve öyle kalacak. Ücretli olan, isteğe bağlı otomasyon paketleridir: ilanlarınızın 101evler ve hangiev’e aktarılması ve Facebook / Instagram hesaplarınızda otomatik paylaşılması.',
                     'No, listing is free and will stay free. Only the optional automation packages are paid: posting your listings to 101evler and hangiev, and sharing them automatically on your Facebook / Instagram accounts.',
                     'Нет, размещение бесплатно. Платные только дополнительные пакеты автоматизации: публикация на 101evler и hangiev и автопостинг в Facebook / Instagram.'],
                ],
                [
                    ['Nasıl üye olurum, ne kadar sürer?', 'How do I sign up and how long does it take?', 'Как зарегистрироваться и сколько это занимает?'],
                    ['“Kayıt ol”dan hesap türünüzü seçin: emlak ofisi, inşaat firması, bireysel danışman ya da mülk sahibi. E-postanızı doğruladıktan sonra panelinize girersiniz. Emlak ofisi ve inşaat firması hesapları bir kez onaylanır; onaydan sonra ilanlarınız anında yayına girer. Bireysel danışman ve mülk sahiplerinin her ilanı kısa bir kontrolden sonra yayınlanır.',
                     'Choose your account type under “Register”: agency, developer, independent agent or owner. After confirming your email you can use your panel. Agency and developer accounts are approved once, after which listings go live instantly. Listings by independent agents and owners are published after a short check.',
                     'Выберите тип аккаунта: агентство, застройщик, частный агент или собственник. После подтверждения e-mail откроется кабинет. Агентства и застройщики проходят разовую проверку, после чего объявления публикуются сразу. Объявления частных агентов и собственников публикуются после короткой проверки.'],
                ],
                [
                    ['İlanımı nasıl eklerim?', 'How do I add a listing?', 'Как добавить объявление?'],
                    ['İki yolu var. Panelim → “İlan ekle”: adım adım form, haritada konum, sürükle-bırak fotoğraf, 360° fotoğraflar. Ya da Telegram: fotoğrafları ve ilan notunuzu botumuza gönderin, “İlanı hazırla”ya basın; yapay zekâ bilgileri çıkarır, Türkçe, İngilizce ve Rusça metni yazar, siz önizlemeyi kontrol edip “Yayınla” dersiniz.',
                     'Two ways. My panel → “Add listing”: a step-by-step form with map location, drag-and-drop photos and 360° photos. Or Telegram: send the photos and your notes to our bot and press “Prepare listing”; AI extracts the details and writes the Turkish, English and Russian text, you check the preview and press “Publish”.',
                     'Два способа. Кабинет → «Добавить объявление»: пошаговая форма, карта, фото и 360° фото. Или Telegram: отправьте боту фото и описание и нажмите «Подготовить»; ИИ заполнит поля и напишет текст на трёх языках, вы проверяете и публикуете.'],
                ],
                [
                    ['İlanım üç dilde mi yayınlanıyor?', 'Is my listing published in three languages?', 'Объявление публикуется на трёх языках?'],
                    ['Evet. Site Türkçe, İngilizce ve Rusça yayın yapar. Telegram’dan gelen ilanlarda üç dildeki başlık ve açıklama otomatik yazılır. Panelden girdiğiniz ilanlarda her dili kendiniz düzenleyebilirsiniz; bir dil boşsa o dilde Türkçe metin gösterilir.',
                     'Yes. The site runs in Turkish, English and Russian. For listings sent via Telegram, titles and descriptions are written automatically in all three. In the panel you can edit each language; if one is empty, the Turkish text is shown.',
                     'Да. Сайт работает на турецком, английском и русском. Для объявлений из Telegram тексты пишутся автоматически на всех трёх языках; в кабинете каждый язык можно редактировать.'],
                ],
                [
                    ['Kaç fotoğraf ekleyebilirim? 360° ekleyebilir miyim?', 'How many photos can I add? Can I add 360°?', 'Сколько фото можно добавить? Можно ли 360°?'],
                    ['Her ilana 30 fotoğraf ve 15 adet 360° fotoğraf ekleyebilirsiniz. Fotoğraflar otomatik küçültülür ve hızlı açılan biçime çevrilir. 360° fotoğraf 2:1 oranında olmalıdır (360° kamera ya da telefonun “Fotoğraf küresi” modu). Telegram’dan 360° gönderirken “Dosya” olarak gönderin.',
                     'Up to 30 photos and 15 360° photos per listing. Photos are resized and converted automatically for speed. 360° photos must be 2:1 (360° camera or the phone’s “Photo Sphere” mode). Send 360° photos to Telegram as a “File”.',
                     'До 30 фото и 15 фото 360° на объявление. Фото автоматически оптимизируются. 360° фото должны быть 2:1; в Telegram отправляйте их как «Файл».'],
                ],
                [
                    ['Neden “Buna benzer ilan zaten yayında” uyarısı aldım?', 'Why did I get “A similar listing is already live”?', 'Почему я получил предупреждение «Похожее объявление уже опубликовано»?'],
                    ['Fotoğraflarınızdan biri ya da açıklamanız, yayındaki başka bir ilanla birebir aynı çıktı. Aynı mülkü iki kez yayınladıysanız eskisini güncelleyin. Fotoğraf ve metin size ait olmalıdır; başka ilanlardan kopyalanan içerik bildirim olarak ekibimize düşer.',
                     'One of your photos or your description is identical to another live listing. If you listed the same property twice, update the older one instead. Photos and text must be your own; copied content is reported to our team.',
                     'Одно из ваших фото или описание совпадает с другим опубликованным объявлением. Если это тот же объект, обновите прежнее объявление. Скопированный контент передаётся на проверку.'],
                ],
                [
                    ['Ofisten ayrılırsam ilanlarıma ne olur?', 'What happens to my listings if I leave an agency?', 'Что будет с моими объявлениями, если я уйду из агентства?'],
                    ['Bir ofisin ekibindeyken açtığınız ilanlar o ofisin portföyüne aittir. Ekipten ayrılırsanız, çıkarılırsanız ya da başka bir ofise geçerseniz bu ilanlar taslağa alınır, yayından kalkar ve kilitlenir. Ofisin başvurusu üzerine ofisin bildirdiği kişiye aktarılır; ofis istemezse kilit kaldırılır ve ilanlar sizde kalır. Ofise katılmadan önce açtığınız ilanlar her zaman sizindir.',
                     'Listings you create while in an agency’s team belong to that agency’s portfolio. If you leave, are removed or move to another agency, they are moved to draft, taken offline and locked. At the agency’s request they are transferred to the person the agency names; if the agency does not want them, they are unlocked and stay with you. Listings you created before joining are always yours.',
                     'Объявления, созданные в команде агентства, принадлежат его портфелю. Если вы уходите, вас исключают или вы переходите в другое агентство, они становятся черновиками, снимаются с публикации и блокируются. По запросу агентства их передают указанному агентством человеку; если агентство от них отказывается, блокировка снимается и они остаются у вас. Объявления, созданные до вступления в агентство, всегда ваши.'],
                ],
                [
                    ['Talepleri (müşteri mesajlarını) nereden görürüm?', 'Where do I see enquiries?', 'Где смотреть заявки?'],
                    ['İlan sayfanızdan gelen her mesaj anında e-postanıza ve bağlıysa Telegram’ınıza gelir. Hepsi Panelim → “Talepler” sekmesinde listelenir; okundu, tamamlandı ya da spam olarak işaretleyebilirsiniz.',
                     'Every message from your listing pages arrives instantly by email and, if connected, Telegram. All are listed in My panel → “Enquiries”, where you can mark them read, done or spam.',
                     'Каждое сообщение мгновенно приходит на e-mail и в Telegram. Все заявки — в кабинете на вкладке «Заявки».'],
                ],
                [
                    ['Ofisimin kendi sayfası olabilir mi?', 'Can my agency have its own page?', 'Может ли у агентства быть своя страница?'],
                    ['Evet. Her üyenin logo, iletişim bilgileri, sosyal medya hesapları ve tüm ilanlarıyla bir vitrin sayfası vardır (ör. /tr/ofis/ofisinizin-adi/). Yönetim açarsa vitrin kendi alt alan adınızda da açılır (ofisiniz.alanadi.com). Firmalar dizininde de listelenirsiniz.',
                     'Yes. Every member has a showcase page with logo, contact details, social accounts and all listings (e.g. /en/agent/your-agency/). It can also run on its own subdomain (youragency.domain.com). You are listed in the agencies directory too.',
                     'Да. У каждого участника есть витрина с логотипом, контактами, соцсетями и всеми объявлениями; возможен и собственный поддомен. Вы также попадаете в каталог агентств.'],
                ],
            ],
        ],
        'packages' => [
            'title' => ['Paketler ve otomasyon', 'Packages and automation', 'Пакеты и автоматизация'],
            'items' => [
                [
                    ['Otomasyon paketleri ne işe yarar?', 'What do the automation packages do?', 'Что дают пакеты автоматизации?'],
                    ['İlanınızı bir kez girersiniz; paketle aynı ilan kendi tarayıcınızdaki “İlan Aktarıcı” eklentisiyle 101evler ve hangiev formlarına doldurulur, ayrıca Facebook sayfanızda ve Instagram işletme hesabınızda otomatik paylaşılır. Aynı ilanı üç ayrı sitede tekrar tekrar yazmazsınız.',
                     'You enter your listing once; with a package, the “Listing Transfer” browser extension fills it into 101evler and hangiev, and it is shared automatically on your Facebook Page and Instagram business account. No more typing the same listing on three sites.',
                     'Вы вводите объявление один раз; с пакетом расширение заполняет его на 101evler и hangiev, а также публикует в Facebook и Instagram.'],
                ],
                [
                    ['101evler ve hangiev’e aktarım nasıl çalışıyor? Hesabım riske girer mi?', 'How does posting to 101evler and hangiev work? Is my account at risk?', 'Как работает публикация на 101evler и hangiev? Не заблокируют ли аккаунт?'],
                    ['Eklenti sizin bilgisayarınızda, sizin hesabınızla çalışır; formu doldurur, fotoğrafları ekler. “Yayınla” düğmesine her zaman siz basarsınız, doğrulama (captcha) çıkarsa siz çözersiniz, SMS kodu istenirse Telegram’dan sorulur. Bot gibi davranmadığı için normal ilan girişinizden farkı yoktur.',
                     'The extension runs on your own computer with your own account; it fills the form and attaches the photos. You always press “Publish” yourself and solve any captcha; if an SMS code is needed it is requested via Telegram. It does not behave like a bot, so it is no different from posting manually.',
                     'Расширение работает на вашем компьютере в вашем аккаунте: заполняет форму и добавляет фото. Кнопку «Опубликовать» всегда нажимаете вы, капчу решаете вы, SMS-код запрашивается через Telegram.'],
                ],
                [
                    ['Paket fiyatları ve kota nasıl işliyor?', 'How do prices and quotas work?', 'Как работают цены и лимиты?'],
                    ['Paketler süre ve ilan sayısına göredir (ör. aylık 100 ilan, haftalık 30 ilan, tek ilan). Bir ilan hem 101evler’e, hem hangiev’e, hem sosyal medyaya gitse de kotanızdan 1 düşer. Kalan kotanızı ve bitiş tarihini Panelim → “Paketim”de görürsünüz; bitmeden 3 gün önce hatırlatma gelir.',
                     'Packages are by duration and number of listings (e.g. 100 a month, 30 a week, single listing). A listing counts once even if it goes to 101evler, hangiev and social media. See your remaining quota and end date under My panel → “My package”; you get a reminder 3 days before it ends.',
                     'Пакеты — по сроку и числу объявлений. Одно объявление считается один раз, даже если публикуется на всех площадках. Остаток и срок — в кабинете, напоминание приходит за 3 дня.'],
                ],
                [
                    ['Facebook ve Instagram paylaşımı için ne gerekiyor?', 'What do I need for Facebook and Instagram posting?', 'Что нужно для публикации в Facebook и Instagram?'],
                    ['Bir Facebook sayfanız ve ona bağlı bir Instagram “İşletme” ya da “İçerik üretici” hesabınız olmalı. Sayfanızda yönetimimize erişim verirsiniz; şifrenizi paylaşmazsınız, erişimi istediğiniz an kaldırabilirsiniz. Bağlantıyı biz kurarız. Facebook gruplarına otomatik paylaşım yapılmaz; Meta buna izin vermiyor ve hesapların kapanmasına yol açabiliyor.',
                     'You need a Facebook Page and an Instagram Business or Creator account linked to it. You give our team access to your Page; you never share your password and can remove access at any time. We set up the connection. We do not post to Facebook groups automatically: Meta does not allow it and it can get accounts closed.',
                     'Нужна страница Facebook и связанный бизнес-аккаунт Instagram. Вы даёте нам доступ к странице без передачи пароля и можете отозвать его в любой момент. В группы Facebook автоматически не публикуем — это запрещено Meta.'],
                ],
                [
                    ['Paketi nasıl alırım, nasıl öderim?', 'How do I buy a package and pay?', 'Как купить пакет и оплатить?'],
                    ['Panelim → “Paketim”deki “Paket almak için yazın” düğmesiyle bize ulaşın. Ödemeden sonra paketiniz aynı gün tanımlanır ve e-postayla bildirilir.',
                     'Contact us with the “Message us to buy” button under My panel → “My package”. Your package is activated the same day after payment and confirmed by email.',
                     'Напишите нам через кнопку в кабинете → «Мой пакет». После оплаты пакет активируется в тот же день.'],
                ],
            ],
        ],
    ];
}

/** Metin: %site% → site adı */
function ae_faq_text(string $s): string
{
    return str_replace('%site%', ae_brand(), $s);
}
