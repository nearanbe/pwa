<?php
/** Emlak talep formu: yalnızca yapay zekâ aramasının sonuç sayfasında. $args: filters, heading, url, empty */
defined('ABSPATH') || exit;
if (!function_exists('ec_request_create')) {
    return;
}
$lang = ec_lang();
$rf = $args['filters'] ?? [];
$crit = function_exists('ec_request_filters_text') ? $args['heading'] : '';
?>
<div class="reqbox<?php echo !empty($args['empty']) ? ' big' : ''; ?>">
  <div><b><?php ae_e('rq_ask'); ?></b><p class="sub"><?php ae_e('rq_ask_p'); ?></p></div>
  <button type="button" class="btn" data-req-open>🏠 <?php ae_e('rq_btn'); ?></button>
</div>
<?php if (empty($GLOBALS['ae_req_form'])) : $GLOBALS['ae_req_form'] = 1; ?>
<div class="ov reqov" id="reqov" hidden role="dialog" aria-modal="true" aria-labelledby="rq-h">
  <form class="sheet reqf" novalidate data-filters="<?php echo esc_attr(wp_json_encode($rf, JSON_UNESCAPED_UNICODE)); ?>" data-url="<?php echo esc_attr($args['url'] ?? ''); ?>">
    <button type="button" class="ovx" aria-label="×">✕</button>
    <h2 id="rq-h"><?php ae_e('rq_h'); ?></h2>
    <?php if ($crit) : ?><p class="sub"><?php ae_e('rq_crit'); ?>: <b><?php echo esc_html($crit); ?></b></p><?php endif; ?>
    <label><?php ae_e('rq_msg'); ?><textarea name="message" rows="3" maxlength="1500" required></textarea></label>
    <div class="row2">
      <label><?php ae_e('rq_name'); ?><input name="name" required autocomplete="name" maxlength="120"></label>
      <label><?php ae_e('rq_phone'); ?><input name="phone" type="tel" required autocomplete="tel" inputmode="tel" maxlength="40"></label>
    </div>
    <div class="row2">
      <label><?php ae_e('rq_email'); ?><input name="email" type="email" autocomplete="email" maxlength="190"></label>
      <label><?php ae_e('rq_when'); ?><select name="timing"><option value=""></option><?php foreach (ec_request_timings() as $k => $t) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($t[$lang] ?? $t['en']); ?></option><?php endforeach; ?></select></label>
    </div>
    <fieldset class="sf-seg"><legend class="sub"><?php ae_e('rq_contact'); ?></legend>
      <label><input type="radio" name="contact" value="phone" checked><span><?php ae_e('rq_c_phone'); ?></span></label>
      <label><input type="radio" name="contact" value="whatsapp"><span>WhatsApp</span></label>
      <label><input type="radio" name="contact" value="email"><span><?php ae_e('rq_c_email'); ?></span></label>
    </fieldset>
    <label class="chk"><input type="checkbox" name="consent" value="1" required> <span><?php ae_e('rq_consent'); ?> <a href="<?php echo esc_url(ec_url_legal('privacy', $lang)); ?>" target="_blank"><?php ae_e('rq_privacy'); ?></a></span></label>
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
    <?php echo ae_turnstile(); ?>
    <p class="rqmsg" role="status"></p>
    <button class="btn" type="submit"><?php ae_e('rq_send'); ?></button>
  </form>
</div>
<?php endif;
