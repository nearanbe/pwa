<?php
/** Panelim → Proje ekle / düzenle. panel.js doldurur ve kaydeder; görseller kayıttan sonra yüklenir. */
if (!defined('ABSPATH')) {
    exit;
}
$P = $editProject ?? null;
?>
<form class="pform" id="pform" novalidate data-id="<?php echo (int) ($P['id'] ?? 0); ?>">
  <section class="panel">
    <h2><?php ae_e($P ? 'p_edit_project' : 'p_new_project'); ?></h2>
    <div class="fg two">
      <label><?php ae_e('pf_name'); ?> *<input name="name" required></label>
      <label><?php ae_e('developer'); ?><input name="developer"></label>
      <label><?php ae_e('city'); ?> *<select name="city" required></select></label>
      <label><?php ae_e('district'); ?><input name="district" list="pdistlist" autocomplete="off"><datalist id="pdistlist"></datalist></label>
      <label><?php ae_e('delivery_label'); ?><input name="delivery_date" type="month"></label>
      <label><?php ae_e('units_label'); ?><input name="units" inputmode="numeric"></label>
      <label><?php ae_e('pj_type'); ?><select name="project_type"><option value=""></option><?php foreach (ec_project_types() as $k => $n) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($n[ec_lang()]); ?></option><?php endforeach; ?></select></label>
      <label><?php ae_e('pj_status'); ?><select name="build_status"><option value=""><?php ae_e('pj_status_auto'); ?></option><?php foreach (ec_build_statuses() as $k => $n) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($n[ec_lang()]); ?></option><?php endforeach; ?></select></label>
      <label><?php ae_e('pj_land'); ?> (m²)<input name="land_m2" inputmode="numeric" placeholder="12000"></label>
      <label><?php ae_e('price_range'); ?> (£) – min<input name="price_min_gbp" inputmode="numeric"></label>
      <label><?php ae_e('price_range'); ?> (£) – max<input name="price_max_gbp" inputmode="numeric"></label>
    </div>
  </section>

  <section class="panel">
    <h2><?php ae_e('pf_units_table'); ?></h2>
    <div class="tablewrap"><table class="unittable" id="units">
      <thead><tr><th><?php ae_e('pf_type'); ?></th><th><?php ae_e('pf_m2'); ?></th><th><?php ae_e('pf_price_from'); ?></th><th><?php ae_e('pf_available'); ?></th><th></th></tr></thead>
      <tbody></tbody>
    </table></div>
    <button class="ghost sm" type="button" id="addunit">＋ <?php ae_e('pf_add_row'); ?></button>
  </section>

  <section class="panel">
    <h2><?php ae_e('payment_plan'); ?></h2>
    <div class="fg">
      <label><?php ae_e('pf_down'); ?><input name="down_payment_pct" inputmode="numeric" placeholder="35"></label>
      <label><?php ae_e('pf_installments'); ?><input name="installments" inputmode="numeric" placeholder="36"></label>
      <label class="wide"><?php ae_e('payment_plan'); ?> (metin)<input name="payment_plan" placeholder="%35 peşin, 36 ay faizsiz taksit"></label>
    </div>
    <p class="live" id="calc"></p>
    <label><?php ae_e('pf_progress'); ?>: <b id="progv">0%</b><input name="progress" type="range" min="0" max="100" step="5" value="0"></label>
  </section>

  <section class="panel">
    <h2><?php ae_e('pf_amenities'); ?></h2>
    <div class="checks">
      <?php foreach (ec_amenities() as $ak => $an) : ?><label class="chip"><input type="checkbox" name="amenities" value="<?php echo esc_attr($ak); ?>"> <?php echo esc_html($an[ec_lang()]); ?></label><?php endforeach; ?>
    </div>
  </section>

  <section class="panel">
    <h2><?php ae_e('description'); ?></h2>
    <div class="ltabs" role="tablist">
      <?php foreach (['tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский'] as $l => $n) : ?><button type="button" data-l="<?php echo $l; ?>" class="<?php echo $l === 'tr' ? 'on' : ''; ?>"><?php echo $n; ?></button><?php endforeach; ?>
    </div>
    <?php foreach (['tr', 'en', 'ru'] as $l) : ?>
      <div class="lpane" data-l="<?php echo $l; ?>" <?php echo $l === 'tr' ? '' : 'hidden'; ?>><textarea name="desc_<?php echo $l; ?>" rows="7"></textarea></div>
    <?php endforeach; ?>
    <label><?php ae_e('f_video'); ?><input name="video_url" type="url" placeholder="https://youtube.com/watch?v=…"></label>
    <div class="vidup">
      <label class="ghost sm filebtn">🎬 <?php ae_e('f_video_up'); ?><input type="file" id="pvidfile" accept="video/mp4,video/webm,video/quicktime,.mp4,.m4v,.webm,.mov" hidden></label>
      <button type="button" class="ghost sm" id="pviddel" hidden><?php ae_e('f_video_del'); ?></button>
      <span class="sub" id="pvidmsg"><?php ae_e('f_video_hint', size_format(wp_max_upload_size())); ?></span>
      <progress id="pvidprog" max="100" value="0" hidden></progress>
    </div>
    <p class="sub"><?php ae_e('f_map'); ?></p>
    <div id="map" class="map"></div>
    <input type="hidden" name="lat"><input type="hidden" name="lng">
  </section>

  <section class="panel" id="pmedia">
    <h2><?php ae_e('pf_cover'); ?> · <?php ae_e('pf_gallery'); ?> · <?php ae_e('pf_plans'); ?> · <?php ae_e('pf_brochure'); ?></h2>
    <p class="note" id="pmedia-wait"><?php ae_e('pf_save_first'); ?></p>
    <div id="pmedia-body" hidden>
      <div class="mediarow">
        <div><h3><?php ae_e('pf_cover'); ?></h3><div class="logoprev wide" id="coverprev"></div><label class="ghost filebtn sm">＋<input type="file" data-kind="cover" accept="image/jpeg,image/png,image/webp" hidden></label></div>
      </div>
      <h3><?php ae_e('pf_gallery'); ?></h3><ul class="photos" data-list="photo"></ul><label class="ghost filebtn sm">＋ <?php ae_e('f_choose_photos'); ?><input type="file" data-kind="photo" accept="image/jpeg,image/png,image/webp" multiple hidden></label>
      <h3><?php ae_e('pf_plans'); ?></h3><p class="sub"><?php ae_e('pf_plan_hint'); ?></p><ul class="photos" data-list="plan"></ul><label class="ghost filebtn sm">＋<input type="file" data-kind="plan" accept="image/jpeg,image/png,image/webp" multiple hidden></label>
      <h3><?php ae_e('pf_brochure'); ?></h3><ul class="files" data-list="brochure"></ul><label class="ghost filebtn sm">＋ PDF<input type="file" data-kind="brochure" accept="application/pdf" hidden></label>
    </div>
  </section>

  <p class="formmsg" role="status" id="pmsg"></p>
  <div class="wnav">
    <button class="ghost" type="button" data-save="draft"><?php ae_e('f_save_draft'); ?></button>
    <button class="btn" type="button" data-save="published"><?php ae_e('f_publish'); ?></button>
  </div>
</form>
