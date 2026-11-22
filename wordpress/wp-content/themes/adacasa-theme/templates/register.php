<?php
/** Kayıt: /tr/kayit/  (?tur=ofis|insaat|danisman|sahibi) */
if (!defined('ABSPATH')) {
    exit;
}
$lang = ec_lang();
$st = ec_form_state();
$e = $st['errors'];
$old = $st['old'];
$kinds = ec_member_kinds();
$kind = $old['kind'] ?? (isset($kinds[$_GET['tur'] ?? '']) ? $_GET['tur'] : 'ofis');
$alts = [];
foreach (EC_LANGS as $l) {
    $alts[$l] = ec_url_section('register', $l);
}
ae_seo(['title' => ae_t('register_title'), 'description' => ae_t('register_sub'), 'canonical' => $alts[$lang], 'alternates' => $alts]);
$sgTok = (string) ($old['sosyal_kayit'] ?? ($_GET['sosyal_kayit'] ?? ''));
$sg = $sgTok !== '' && function_exists('ec_sl_pending') ? ec_sl_pending($sgTok) : null;
if ($sg && empty($old['name'])) {
    $old['name'] = $sg['name'];
}
$v = fn(string $k) => esc_attr((string) ($old[$k] ?? ''));
get_header();
?>
<main id="main" class="main auth">
  <div class="panel authbox wide">
    <h1><?php ae_e('register_title'); ?></h1>
    <p class="sub"><?php ae_e('register_sub'); ?></p>
    <?php echo ae_social_error(); ?>
    <?php if ($e) : ?><p class="errmsg" role="alert"><?php ae_e(isset($e['form']) && in_array($e['form'], ['expired', 'social_expired'], true) ? ($e['form'] === 'expired' ? 'err_expired' : 'err_social_expired') : 'err_form'); ?></p><?php endif; ?>
    <?php if ($sg) : ?><p class="okmsg" role="status"><?php echo esc_html(ae_t('social_reg_note', $sg['p'] === 'google' ? 'Google' : 'Facebook')); ?></p><?php else : echo ae_social_buttons('register'); endif; ?>
    <form method="post" class="form" id="regform" novalidate>
      <?php wp_nonce_field('ec_register', '_ecnonce'); ?>
      <?php if ($sg) : ?><input type="hidden" name="sosyal_kayit" value="<?php echo esc_attr($sg['token']); ?>"><?php endif; ?>
      <fieldset class="kinds">
        <legend><?php ae_e('account_type'); ?></legend>
        <?php foreach ($kinds as $k => $kd) : ?>
          <label class="kind<?php echo $k === $kind ? ' on' : ''; ?>">
            <input type="radio" name="kind" value="<?php echo esc_attr($k); ?>" <?php checked($kind, $k); ?>>
            <b><?php echo esc_html($kd['name'][$lang]); ?></b>
            <span class="sub"><?php ae_e('kind_hint_' . $k); ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>
      <?php echo ae_field_error($e, 'kind'); ?>
      <div class="fg two">
        <label><span class="lbl-company"<?php echo in_array($kind, ['ofis', 'insaat'], true) ? '' : ' hidden'; ?>><?php ae_e('company_name'); ?></span><span class="lbl-person"<?php echo in_array($kind, ['ofis', 'insaat'], true) ? ' hidden' : ''; ?>><?php ae_e('full_name'); ?></span>
          <input name="name" required autocomplete="organization" value="<?php echo $v('name'); ?>"><?php echo ae_field_error($e, 'name'); ?></label>
        <label class="corp-only"<?php echo in_array($kind, ['ofis', 'insaat'], true) ? '' : ' hidden'; ?>><?php ae_e('reg_contact'); ?>
          <input name="contact_person" autocomplete="name" value="<?php echo $v('contact_person'); ?>"></label>
        <?php if ($sg) : ?>
        <label><?php ae_e('reg_email'); ?><input type="email" value="<?php echo esc_attr($sg['email']); ?>" readonly><?php echo ae_field_error($e, 'email'); ?></label>
        <?php else : ?>
        <label><?php ae_e('reg_email'); ?><input name="email" type="email" required autocomplete="email" value="<?php echo $v('email'); ?>"><?php echo ae_field_error($e, 'email'); ?></label>
        <?php endif; ?>
        <label><?php ae_e('reg_phone'); ?><input name="phone" type="tel" required autocomplete="tel" placeholder="+90 533 …" value="<?php echo $v('phone'); ?>"><?php echo ae_field_error($e, 'phone'); ?></label>
        <label><?php ae_e('city'); ?>
          <select name="city"><option value=""><?php ae_e('city'); ?></option>
            <?php foreach (ec_cities() as $ck => $c) : ?><option value="<?php echo esc_attr($ck); ?>" <?php selected($old['city'] ?? '', $ck); ?>><?php echo esc_html($c['name'][$lang]); ?></option><?php endforeach; ?>
          </select></label>
        <?php if (!$sg) : ?>
        <label><?php ae_e('password'); ?> <span class="sub">(<?php ae_e('password_hint'); ?>)</span><input name="password" type="password" required minlength="8" autocomplete="new-password"><?php echo ae_field_error($e, 'password'); ?></label>
        <?php endif; ?>
      </div>
      <fieldset class="tgq">
        <legend><?php ae_e('tg_question'); ?></legend>
        <p class="sub"><?php ae_e('tg_question_hint'); ?></p>
        <label class="chk"><input type="radio" name="telegram_opt_in" value="1" <?php checked($old['telegram_opt_in'] ?? '', '1'); ?>> <?php ae_e('yes'); ?></label>
        <label class="chk"><input type="radio" name="telegram_opt_in" value="0" <?php checked($old['telegram_opt_in'] ?? '0', '0'); ?>> <?php ae_e('no'); ?></label>
      </fieldset>
      <label class="chk"><input type="checkbox" name="consent" value="1" <?php checked(!empty($old['consent'])); ?>> <?php printf(esc_html(ae_t('consent', '%1$s', '%2$s')), '<a href="' . esc_url(ec_url_legal('privacy')) . '" target="_blank">' . esc_html(ae_t('privacy')) . '</a>', '<a href="' . esc_url(ec_url_legal('terms')) . '" target="_blank">' . esc_html(ae_t('terms')) . '</a>'); ?></label>
      <?php echo ae_field_error($e, 'consent'); ?>
      <?php echo ae_turnstile(); ?><?php echo ae_field_error($e, 'captcha'); ?>
      <button class="btn wide" type="submit"><?php ae_e('create_account'); ?></button>
    </form>
    <hr><p class="authlinks"><?php ae_e('have_account'); ?> <a href="<?php echo esc_url(ec_url_section('login')); ?>"><b><?php ae_e('login'); ?></b></a></p>
  </div>
</main>
<script>
document.querySelectorAll('#regform input[name=kind]').forEach(function (r) {
  r.addEventListener('change', function () {
    var corp = r.value === 'ofis' || r.value === 'insaat';
    document.querySelectorAll('#regform .kind').forEach(function (k) { k.classList.toggle('on', k.contains(r)); });
    document.querySelectorAll('#regform .corp-only, #regform .lbl-company').forEach(function (el) { el.hidden = !corp; });
    document.querySelectorAll('#regform .lbl-person').forEach(function (el) { el.hidden = corp; });
  });
});
</script>
<?php
get_footer();
