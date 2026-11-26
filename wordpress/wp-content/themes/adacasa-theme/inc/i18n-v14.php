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
        // Doğrulanmış ilan
        'vf_badge' => ['Doğrulanmış ilan', 'Verified listing', 'Проверенное объявление'],
        'vf_desc' => ['İlan sahibinin tapu ya da mal sahibi yetki belgesi AdaCasa tarafından kontrol edildi.', 'The advertiser’s title deed or owner authorisation was checked by AdaCasa.', 'AdaCasa проверила титул или доверенность владельца.'],
    ]);
});
