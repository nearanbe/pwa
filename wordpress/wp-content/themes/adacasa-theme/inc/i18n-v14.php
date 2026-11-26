<?php
/**
 * 1.3 arayüz metinleri (tema 1.3.0): fiyat uyarısı, kayıtlı arama, fiyat geçmişi, ilan teyidi,
 * doğrulanmış ilan, maliyet hesaplayıcı, görüntülü gezi, doğal dille arama.
 * Sıra: tr, en, ru (de/ar eklenince 4. ve 5. eleman).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('emlak_tema_strings_extra', function (array $s): array {
    return array_merge($s, [
        // Uyarılar
        'al_price_btn' => ['Fiyat düşerse haber ver', 'Alert me if the price drops', 'Сообщить о снижении цены'],
        'al_price_on' => ['Fiyat uyarısı açık ✓', 'Price alert on ✓', 'Уведомление о цене включено ✓'],
        'al_search_btn' => ['Bu aramayı kaydet', 'Save this search', 'Сохранить поиск'],
        'al_search_on' => ['Arama kayıtlı ✓', 'Search saved ✓', 'Поиск сохранён ✓'],
        'al_price_h' => ['Fiyat düşünce haber verelim', 'We will tell you when the price drops', 'Сообщим, когда цена снизится'],
        'al_price_p' => ['Bu ilanın fiyatı düşerse size hemen bildirim gönderelim.', 'If this property’s price drops, we will notify you right away.', 'Если цена на этот объект снизится, мы сразу сообщим.'],
        'al_search_h' => ['Yeni ilanları kaçırmayın', 'Don’t miss new listings', 'Не пропустите новые объявления'],
        'al_search_p' => ['Bu kriterlere uyan yeni ilan eklendiğinde günde en fazla bir kez haber verelim.', 'We will let you know (at most once a day) when new listings match these filters.', 'Мы сообщим (не чаще раза в день) о новых объявлениях по этим параметрам.'],
        'al_this_device' => ['Bu cihaza bildirim gönder', 'Notify this device', 'Уведомлять на это устройство'],
        'al_by_email' => ['E-postayla', 'By email', 'По e-mail'],
        'al_email_ph' => ['ya da e-posta adresiniz', 'or your email address', 'или ваш e-mail'],
        'al_privacy' => ['Adresinizi yalnızca bu bildirim için kullanırız. Her bildirimde kapatma bağlantısı bulunur.', 'We use your address only for this alert. Every message has an unsubscribe link.', 'Адрес используется только для этого уведомления. В каждом письме есть ссылка для отписки.'],
        'al_ios' => ['iPhone’da bildirim almak için önce siteyi ana ekrana ekleyin (Paylaş → Ana Ekrana Ekle) ya da e-postayı seçin.', 'On iPhone, add the site to your Home Screen first (Share → Add to Home Screen) or use email.', 'На iPhone сначала добавьте сайт на экран «Домой» (Поделиться → На экран «Домой») или выберите e-mail.'],
        'al_no_push' => ['Bu tarayıcı bildirimleri desteklemiyor; e-postayı kullanın.', 'This browser does not support notifications; please use email.', 'Браузер не поддерживает уведомления; используйте e-mail.'],
        'al_denied' => ['Bildirim izni verilmedi. Tarayıcı ayarlarından izin verebilir ya da e-postayı seçebilirsiniz.', 'Notification permission was not granted. Allow it in browser settings or use email.', 'Разрешение не получено. Разрешите в настройках браузера или выберите e-mail.'],
        'al_ok_push' => ['Tamam! Bu cihaza bildirim gelecek.', 'Done! This device will be notified.', 'Готово! Уведомления придут на это устройство.'],
        'al_check_mail' => ['E-postanıza gelen bağlantıya tıklayarak onaylayın.', 'Please confirm via the link we emailed you.', 'Подтвердите по ссылке из письма.'],
        'al_err' => ['Bir sorun oluştu, lütfen tekrar deneyin.', 'Something went wrong, please try again.', 'Произошла ошибка, попробуйте ещё раз.'],
        'al_off_q' => ['Bu bildirim kapatılsın mı?', 'Turn off this alert?', 'Отключить это уведомление?'],
        'al_off_done' => ['Bildirim kapatıldı.', 'Alert turned off.', 'Уведомление отключено.'],
        'al_fav_q' => ['Fiyatı düşerse haber verelim mi?', 'Shall we tell you if the price drops?', 'Сообщить, если цена снизится?'],
        'al_yes' => ['Evet', 'Yes', 'Да'],
        // Fiyat geçmişi
        'ph_title' => ['Fiyat geçmişi', 'Price history', 'История цены'],
        'ph_first' => ['İlk fiyat', 'Listed at', 'Первая цена'],
        'ph_drop_badge' => ['Fiyat düştü', 'Price reduced', 'Цена снижена'],
        'ph_drop_badge_pct' => ['%%%d indirim', '%d%% off', '−%d%%'],
        'ph_was' => ['Önceki fiyat: %s', 'Was: %s', 'Было: %s'],
        // Teyit
        'cf_line' => ['İlan sahibi %s bu ilanın güncel olduğunu teyit etti', 'Advertiser confirmed this listing is still available %s', 'Продавец подтвердил актуальность %s'],
        'cf_today' => ['bugün', 'today', 'сегодня'],
        'cf_days' => ['%d gün önce', '%d days ago', '%d дн. назад'],
        'p_still' => ['Hâlâ güncel', 'Still available', 'Актуально'],
        'p_verify' => ['Doğrulama belgesi', 'Verification document', 'Документ для проверки'],
        // Maliyet hesaplayıcı
        'co_title' => ['Toplam maliyet (yaklaşık)', 'Total purchase cost (approx.)', 'Полная стоимость покупки (ориентир.)'],
        'co_sub' => ['Fiyata eklenen vergi, harç ve masrafları görün. Bilgilerinizi seçin:', 'See the taxes, fees and costs on top of the price. Choose your details:', 'Налоги, сборы и расходы сверх цены. Укажите данные:'],
        'co_who' => ['Alıcı', 'Buyer', 'Покупатель'],
        'co_who_foreign' => ['Yabancı (TC ve KKTC dışı)', 'Foreign national (not TR/TRNC)', 'Иностранец (не ТР/ТРСК)'],
        'co_who_tr' => ['Türkiye Cumhuriyeti vatandaşı', 'Turkish citizen', 'Гражданин Турции'],
        'co_who_kktc' => ['KKTC vatandaşı', 'TRNC citizen', 'Гражданин ТРСК'],
        'co_nth' => ['Kaçıncı taşınmazınız?', 'Which property is this for you?', 'Какой это объект для вас?'],
        'co_nth_1' => ['İlk (1.)', 'First', 'Первый'],
        'co_seller' => ['Satıcı', 'Seller', 'Продавец'],
        'co_seller_new' => ['Müteahhit / şirket (yeni konut)', 'Developer / company (new build)', 'Застройщик / компания (новостройка)'],
        'co_seller_resale' => ['Bireysel satıcı (ikinci el)', 'Private seller (resale)', 'Частный продавец (вторичка)'],
        'co_deed' => ['Tapu devir harcı', 'Title deed transfer fee', 'Сбор за переоформление титула'],
        'co_deed_note' => ['Matrah: satış bedeli ile Tapu’nun belirlediği rayiç değerden yüksek olanı.', 'Based on the higher of the price and the Land Registry valuation.', 'Считается от большей из цены и оценки Земельного департамента.'],
        'co_promo_note' => ['İndirimli oran %s tarihine kadar geçerli (Resmî Gazete, Ekim 2026).', 'Reduced rate valid until %s (Official Gazette, Oct 2026).', 'Льготная ставка действует до %s (Офиц. газета, окт. 2026).'],
        'co_vat' => ['KDV', 'VAT', 'НДС'],
        'co_vat_note' => ['Yalnız müteahhit/şirketten alımda. 300 m² ve üzeri konutta %10.', 'Only when buying from a developer/company. 10% for homes of 300 m² or more.', 'Только при покупке у застройщика/компании. 10% для жилья от 300 м².'],
        'co_stamp' => ['Sözleşme damga pulu', 'Contract stamp duty', 'Гербовый сбор по договору'],
        'co_stamp_note' => ['İmzadan sonraki 1 ay içinde ödenmezse ceza eklenir.', 'A penalty applies if not paid within 1 month of signing.', 'Штраф, если не оплачено в течение 1 месяца после подписания.'],
        'co_lawyer' => ['Avukat ücreti (+KDV)', 'Lawyer fee (+VAT)', 'Юрист (+НДС)'],
        'co_lawyer_note' => ['Piyasa aralığı; avukata göre değişir.', 'Typical market range; varies by lawyer.', 'Рыночный диапазон; зависит от юриста.'],
        'co_trafo' => ['Trafo payı', 'Electricity transformer fee', 'Плата за трансформатор'],
        'co_trafo_note' => ['Yeni projelerde; bazı projelerde fiyata dahildir, satıcıya sorun.', 'New developments; sometimes included in the price, ask the seller.', 'В новостройках; иногда включено в цену, уточните.'],
        'co_util' => ['Su ve elektrik aboneliği', 'Water & electricity connection', 'Подключение воды и электричества'],
        'co_permit' => ['Satın alma izni başvuru harcı', 'Purchase permit application fee', 'Сбор за разрешение на покупку'],
        'co_permit_note' => ['Yabancılar için Bakanlar Kurulu izni (yaklaşık tutar).', 'Council of Ministers permit for foreigners (approx.).', 'Разрешение Совета министров для иностранцев (ориентир.).'],
        'co_extra' => ['Fiyata ek masraflar', 'Costs on top of the price', 'Расходы сверх цены'],
        'co_total' => ['Yaklaşık toplam', 'Approximate total', 'Итого примерно'],
        'co_disclaimer' => ['Yaklaşık hesaptır, hukuki ya da mali tavsiye değildir. Oranlar %s tarihli resmi metinlere göredir ve sık değişir; kesin tutarı Tapu Dairesi ve avukatınız belirler. Emlakçı komisyonu ve yıllık emlak vergisi dahil değildir.', 'Approximate only; not legal or financial advice. Rates follow official texts as of %s and change often; the Land Registry and your lawyer confirm exact amounts. Agent commission and annual property tax are not included.', 'Ориентировочный расчёт, не юридическая консультация. Ставки по официальным текстам на %s и часто меняются; точную сумму определяют Земельный департамент и ваш юрист. Комиссия агента и ежегодный налог не включены.'],
        // Görüntülü gezi
        'vt_btn' => ['Görüntülü gezi iste', 'Request a video tour', 'Запросить видеопоказ'],
        'vt_title' => ['Görüntülü gezi', 'Video tour', 'Видеопоказ'],
        'vt_sub' => ['Evi bulunduğunuz yerden, canlı görüntülü görüşmeyle gezin. Uygun zamanı seçin; ilan sahibi sizi arayarak teyit eder.', 'Tour the property live over a video call from wherever you are. Pick a time; the advertiser will call to confirm.', 'Осмотрите объект по видеосвязи, где бы вы ни были. Выберите время — продавец перезвонит для подтверждения.'],
        'vt_phone' => ['Telefon / WhatsApp', 'Phone / WhatsApp', 'Телефон / WhatsApp'],
        'vt_date' => ['Tarih', 'Date', 'Дата'],
        'vt_time' => ['Saat aralığı', 'Time', 'Время'],
        'vt_app' => ['Uygulama', 'App', 'Приложение'],
        'vt_send' => ['Görüntülü gezi talep et', 'Request video tour', 'Отправить запрос'],
        'vt_msg' => ['Görüntülü gezi talebi', 'Video tour request', 'Запрос видеопоказа'],
        // Müşteri takibi
        'crm_push' => ['Hatırlatmaları bu cihazda aç', 'Get reminders on this device', 'Напоминания на это устройство'],
        'crm_add' => ['Müşteri ekle', 'Add client', 'Добавить клиента'],
        'crm_follow' => ['Hatırlatma (tarih ve saat)', 'Reminder (date & time)', 'Напоминание (дата и время)'],
        'crm_notes' => ['Notlar', 'Notes', 'Заметки'],
        'crm_stage' => ['Durum', 'Stage', 'Этап'],
        'crm_hint' => ['Her müşteri için durum, not ve hatırlatma girin. Hatırlatma 1 gün, 1 saat ve 5 dakika önce telefonunuza bildirim olarak gelir (bildirimi açmadıysanız Telegram’dan).', 'Add a stage, notes and a reminder for each client. Reminders arrive 1 day, 1 hour and 5 minutes before as phone notifications (or via Telegram).', 'Укажите этап, заметки и напоминание. Уведомления приходят за 1 день, 1 час и 5 минут (или в Telegram).'],
        'save' => ['Kaydet', 'Save', 'Сохранить'],
        // Doğrulanmış ilan
        'vf_badge' => ['Doğrulanmış ilan', 'Verified listing', 'Проверенное объявление'],
        'vf_desc' => ['İlan sahibinin tapu ya da mal sahibi yetki belgesi AdaCasa tarafından kontrol edildi.', 'The advertiser’s title deed or owner authorisation was checked by AdaCasa.', 'AdaCasa проверила титул или доверенность владельца.'],
    ]);
});
