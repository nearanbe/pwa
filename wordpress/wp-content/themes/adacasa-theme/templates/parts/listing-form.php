<?php
/** Panelim → İlan ekle / düzenle (adım adım). Değerler panel.js tarafından doldurulur ve kaydedilir. */
if (!defined('ABSPATH')) {
    exit;
}
$L = $editListing ?? null;
$steps = ['basics' => 'f_step_basics', 'location' => 'f_step_location', 'details' => 'f_step_details', 'text' => 'f_step_text', 'photos' => 'f_step_photos', 'preview' => 'f_step_preview'];
?>
<form class="wizard" id="lform" novalidate data-id="<?php echo (int) ($L['id'] ?? 0); ?>">
  <ol class="wsteps">
    <?php $i = 0; foreach ($steps as $k => $label) : $i++; ?>
      <li data-go="<?php echo $k; ?>" class="<?php echo $i === 1 ? 'on' : ''; ?>"><span><?php echo $i; ?></span><?php ae_e($label); ?></li>
    <?php endforeach; ?>
  </ol>

  <section class="wstep panel on" data-step="basics">
    <h2><?php ae_e('f_step_basics'); ?></h2>
    <div class="segs" role="radiogroup">
      <?php foreach (['sale', 'rent', 'daily'] as $m) : ?>
        <label class="segbtn"><input type="radio" name="mode" value="<?php echo $m; ?>"> <?php ae_e($m); ?></label>
      <?php endforeach; ?>
    </div>
    <div class="fg">
      <label><?php ae_e('property_type'); ?><select name="ptype" required></select></label>
      <label><?php ae_e('f_subtype'); ?><select name="subtype"></select></label>
      <label><?php ae_e('f_price'); ?> *<input name="price" inputmode="decimal" required></label>
      <label><?php ae_e('f_currency'); ?><select name="currency"><?php foreach (EC_CURRENCIES as $c) : ?><option><?php echo $c; ?></option><?php endforeach; ?></select></label>
      <label><?php ae_e('f_area'); ?><input name="area_m2" inputmode="numeric"></label>
      <label><?php ae_e('bedrooms'); ?><select name="rooms"><option value=""></option><?php foreach (ec_room_options() as $r) : ?><option><?php echo esc_html($r); ?></option><?php endforeach; ?></select></label>
      <label><?php ae_e('baths'); ?><input name="baths" inputmode="numeric"></label>
    </div>
    <div class="live" id="live"><span class="sub"><?php ae_e('f_equivalents'); ?>:</span> <span id="equiv">—</span><div id="cmp" class="sub"></div></div>
  </section>

  <section class="wstep panel" data-step="location">
    <h2><?php ae_e('f_step_location'); ?></h2>
    <div class="fg two">
      <label><?php ae_e('city'); ?> *<select name="city" required></select></label>
      <label><?php ae_e('district'); ?><input name="district" list="distlist" autocomplete="off"><datalist id="distlist"></datalist></label>
    </div>
    <p class="sub"><?php ae_e('f_map'); ?> · <?php ae_e('f_map_hint'); ?></p>
    <div id="map" class="map"></div>
    <input type="hidden" name="lat"><input type="hidden" name="lng">
  </section>

  <section class="wstep panel" data-step="details">
    <h2><?php ae_e('f_step_details'); ?></h2>
    <fieldset class="deedbox sale-only">
      <legend><?php ae_e('deed'); ?> <span class="req-sale">*</span></legend>
      <p class="sub"><?php ae_e('f_deed_required'); ?></p>
      <div class="deeds">
        <?php foreach (ec_deed_types() as $dk => $dn) : ?>
          <label class="deed"><input type="radio" name="deed_type" value="<?php echo esc_attr($dk); ?>"> <b><?php echo esc_html($dn[ec_lang()]); ?></b><span class="sub"><?php echo esc_html(ec_deed_desc($dk, ec_lang())); ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <div class="fg">
      <label><?php ae_e('furnished'); ?><select name="furnished"><option value=""><?php ae_e('f_unknown'); ?></option><option value="1"><?php ae_e('furnished_yes'); ?></option><option value="0"><?php ae_e('furnished_no'); ?></option></select></label>
      <label class="sale-only"><?php ae_e('f_vat'); ?><select name="vat_paid"><option value=""><?php ae_e('f_unknown'); ?></option><option value="1"><?php ae_e('yes'); ?></option><option value="0"><?php ae_e('no'); ?></option></select></label>
      <label class="sale-only"><?php ae_e('f_transformer'); ?><select name="transformer_paid"><option value=""><?php ae_e('f_unknown'); ?></option><option value="1"><?php ae_e('yes'); ?></option><option value="0"><?php ae_e('no'); ?></option></select></label>
      <label><?php ae_e('f_floor'); ?><input name="floor" placeholder="2, Zemin, Bahçe"></label>
      <label><?php ae_e('f_total_floors'); ?><input name="total_floors" inputmode="numeric"></label>
      <label><?php ae_e('f_year'); ?><input name="year_built" inputmode="numeric" placeholder="2024"></label>
      <label><?php ae_e('f_sea'); ?><input name="sea_distance_m" inputmode="numeric"></label>
    </div>
    <label class="sale-only"><?php ae_e('f_payment_plan'); ?><input name="payment_plan" placeholder="%35 peşin, 36 ay taksit"></label>
    <h3><?php ae_e('f_features'); ?></h3>
    <div class="checks">
      <?php foreach (ec_features() as $fk => $fn) : ?><label class="chip"><input type="checkbox" name="features" value="<?php echo esc_attr($fk); ?>"> <?php echo esc_html($fn[ec_lang()]); ?></label><?php endforeach; ?>
    </div>
    <div class="fg two">
      <label><?php ae_e('f_video'); ?><input name="video_url" type="url" placeholder="https://youtube.com/watch?v=…"></label>
      <label><?php ae_e('f_tour'); ?><input name="tour_url" type="url" placeholder="https://"></label>
    </div>
    <div class="vidup">
      <label class="ghost sm filebtn">🎬 <?php ae_e('f_video_up'); ?><input type="file" id="vidfile" accept="video/mp4,video/webm,video/quicktime,.mp4,.m4v,.webm,.mov" hidden></label>
      <button type="button" class="ghost sm" id="viddel" hidden><?php ae_e('f_video_del'); ?></button>
      <span class="sub" id="vidmsg"><?php ae_e('f_video_hint', size_format(wp_max_upload_size())); ?></span>
      <progress id="vidprog" max="100" value="0" hidden></progress>
    </div>
  </section>

  <section class="wstep panel" data-step="text">
    <h2><?php ae_e('f_step_text'); ?></h2>
    <p class="sub"><?php ae_e('f_lang_hint'); ?> <em><?php ae_e('f_ai_soon'); ?></em></p>
    <div class="ltabs" role="tablist">
      <?php foreach (['tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский'] as $l => $n) : ?><button type="button" data-l="<?php echo $l; ?>" class="<?php echo $l === 'tr' ? 'on' : ''; ?>"><?php echo $n; ?></button><?php endforeach; ?>
    </div>
    <?php foreach (['tr', 'en', 'ru'] as $l) : ?>
      <div class="lpane" data-l="<?php echo $l; ?>" <?php echo $l === 'tr' ? '' : 'hidden'; ?>>
        <label><?php ae_e('f_title'); ?><?php echo $l === 'tr' ? ' *' : ''; ?><input name="title_<?php echo $l; ?>" maxlength="200"></label>
        <label><?php ae_e('f_desc'); ?><textarea name="desc_<?php echo $l; ?>" rows="8"></textarea></label>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="wstep panel" data-step="photos">
    <h2><?php ae_e('f_step_photos'); ?></h2>
    <div class="drop" id="drop">
      <p><?php ae_e('f_photos_hint'); ?></p>
      <label class="btn filebtn"><?php ae_e('f_choose_photos'); ?><input type="file" id="photofiles" accept="image/jpeg,image/png,image/webp" multiple hidden></label>
    </div>
    <ul class="photos" id="photos"></ul>
    <div class="panoup" id="panoup">
      <h3>🌐 <?php ae_e('pano_tour'); ?> <span class="sub">(<?php ae_e('optional'); ?>, <?php echo (int) EC_PANO_MAX; ?>)</span></h3>
      <p class="sub"><?php ae_e('pano_upload_hint', size_format(wp_max_upload_size())); ?></p>
      <label class="ghost filebtn sm">＋ <?php ae_e('pano_add'); ?><input type="file" id="panofiles" accept="image/jpeg,image/png,image/webp" multiple hidden></label>
      <ul class="panolist" id="panolist"></ul>
    </div>
  </section>

  <section class="wstep panel" data-step="preview">
    <h2><?php ae_e('f_step_preview'); ?></h2>
    <p class="sub"><?php ae_e('f_preview_note'); ?></p>
    <div id="pv" class="pv"></div>
  </section>

  <p class="note warn dupwarn" id="dupwarn" role="alert" hidden></p>
  <p class="formmsg" role="status" id="lmsg"></p>
  <div class="wnav">
    <button class="ghost" type="button" id="wprev">‹ <?php ae_e('f_back'); ?></button>
    <button class="ghost" type="button" id="wdraft"><?php ae_e('f_save_draft'); ?></button>
    <button class="btn" type="button" id="wnext"><?php ae_e('f_next'); ?> ›</button>
    <button class="btn" type="button" id="wpublish" hidden><?php ae_e('f_publish'); ?></button>
  </div>
</form>
