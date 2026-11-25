<?php
/**
 * Veri tabloları. WordPress'in posts/postmeta yapısı KULLANILMAZ:
 * tablolar ileride PostgreSQL'e (Supabase) birebir taşınacak şekilde tasarlandı.
 * Alan adları docs/veri-modeli.md ile aynıdır; değişiklik yaparken ikisini birlikte güncelle.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_table(string $name): string
{
    global $wpdb;
    return $wpdb->prefix . 'ec_' . $name;
}

function ec_install_schema(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $c = $wpdb->get_charset_collate();

    $agents = ec_table('agents');
    $listings = ec_table('listings');
    $i18n = ec_table('listing_i18n');
    $photos = ec_table('photos');
    $projects = ec_table('projects');

    dbDelta("CREATE TABLE $agents (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  wp_user_id bigint(20) unsigned DEFAULT NULL,
  kind varchar(20) NOT NULL DEFAULT 'danisman',
  company_type varchar(40) DEFAULT NULL,
  name varchar(190) NOT NULL,
  slug varchar(60) DEFAULT NULL,
  logo_path varchar(255) DEFAULT NULL,
  bio text,
  license_no varchar(60) DEFAULT NULL,
  parent_id bigint(20) unsigned DEFAULT NULL,
  team_invite bigint(20) unsigned DEFAULT NULL,
  team_share tinyint(1) NOT NULL DEFAULT 0,
  team_lim_listings int(10) unsigned NOT NULL DEFAULT 0,
  team_lim_promos int(10) unsigned NOT NULL DEFAULT 0,
  verified tinyint(1) NOT NULL DEFAULT 0,
  address varchar(255) DEFAULT NULL,
  website varchar(190) DEFAULT NULL,
  socials text,
  email_verified tinyint(1) NOT NULL DEFAULT 0,
  verify_token char(40) DEFAULT NULL,
  consent_at datetime DEFAULT NULL,
  telegram_opt_in tinyint(1) NOT NULL DEFAULT 0,
  telegram_username varchar(64) DEFAULT NULL,
  telegram_link_code char(12) DEFAULT NULL,
  telegram_link_expires datetime DEFAULT NULL,
  office varchar(190) DEFAULT NULL,
  contact_person varchar(190) DEFAULT NULL,
  email varchar(190) NOT NULL,
  phone varchar(40) DEFAULT NULL,
  whatsapp varchar(40) DEFAULT NULL,
  city varchar(40) DEFAULT NULL,
  telegram_chat_id varchar(40) DEFAULT NULL,
  social_auto tinyint(1) NOT NULL DEFAULT 1,
  social_lang varchar(10) NOT NULL DEFAULT 'tr+en',
  social_sign varchar(255) DEFAULT NULL,
  api_key_hash char(64) DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY api_key_hash (api_key_hash),
  KEY email (email),
  KEY slug (slug),
  KEY parent_id (parent_id),
  KEY wp_user_id (wp_user_id),
  KEY telegram_chat_id (telegram_chat_id)
) $c;");

    dbDelta("CREATE TABLE $listings (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) unsigned DEFAULT NULL,
  office_id bigint(20) unsigned DEFAULT NULL,
  hold_office_id bigint(20) unsigned DEFAULT NULL,
  hold_prev_status varchar(20) DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'draft',
  mode varchar(10) NOT NULL,
  ptype varchar(20) NOT NULL,
  subtype varchar(40) DEFAULT NULL,
  city varchar(40) NOT NULL,
  district varchar(80) DEFAULT NULL,
  price decimal(14,2) NOT NULL DEFAULT 0,
  currency char(3) NOT NULL DEFAULT 'GBP',
  price_gbp decimal(14,2) NOT NULL DEFAULT 0,
  area_m2 int(10) unsigned DEFAULT NULL,
  rooms varchar(10) DEFAULT NULL,
  baths tinyint(3) unsigned DEFAULT NULL,
  deed_type varchar(30) DEFAULT NULL,
  furnished tinyint(1) DEFAULT NULL,
  features text,
  vat_paid tinyint(1) DEFAULT NULL,
  transformer_paid tinyint(1) DEFAULT NULL,
  sea_distance_m int(10) unsigned DEFAULT NULL,
  floor varchar(10) DEFAULT NULL,
  total_floors tinyint(3) unsigned DEFAULT NULL,
  year_built smallint(5) unsigned DEFAULT NULL,
  payment_plan varchar(190) DEFAULT NULL,
  video_url varchar(255) DEFAULT NULL,
  tour_url varchar(255) DEFAULT NULL,
  project_id bigint(20) unsigned DEFAULT NULL,
  reject_reason varchar(255) DEFAULT NULL,
  lat decimal(9,6) DEFAULT NULL,
  lng decimal(9,6) DEFAULT NULL,
  featured_until datetime DEFAULT NULL,
  home_until datetime DEFAULT NULL,
  cat_until datetime DEFAULT NULL,
  urgent_until datetime DEFAULT NULL,
  bumped_at datetime DEFAULT NULL,
  source varchar(20) NOT NULL DEFAULT 'panel',
  views int(10) unsigned NOT NULL DEFAULT 0,
  text_md5 char(32) DEFAULT NULL,
  text_hash bigint(20) DEFAULT NULL,
  dup_of bigint(20) unsigned DEFAULT NULL,
  dup_checked datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  published_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY text_md5 (text_md5),
  KEY search (status,mode,ptype,city),
  KEY price_gbp (price_gbp),
  KEY agent_id (agent_id),
  KEY office_id (office_id),
  KEY hold_office_id (hold_office_id),
  KEY featured_until (featured_until),
  KEY home_until (home_until),
  KEY cat_until (cat_until)
) $c;");

    dbDelta("CREATE TABLE $i18n (
  listing_id bigint(20) unsigned NOT NULL,
  lang char(2) NOT NULL,
  title varchar(200) NOT NULL,
  slug varchar(200) NOT NULL,
  description text,
  PRIMARY KEY  (listing_id,lang)
) $c;");

    dbDelta("CREATE TABLE $photos (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  listing_id bigint(20) unsigned NOT NULL,
  sort smallint(5) unsigned NOT NULL DEFAULT 0,
  path varchar(255) NOT NULL,
  width int(10) unsigned DEFAULT NULL,
  height int(10) unsigned DEFAULT NULL,
  phash bigint(20) DEFAULT NULL,
  phash2 bigint(20) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY listing_id (listing_id,sort),
  KEY phash (phash)
) $c;");

    dbDelta("CREATE TABLE $projects (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) unsigned DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'published',
  name varchar(190) NOT NULL,
  slug varchar(190) NOT NULL DEFAULT '',
  developer varchar(190) DEFAULT NULL,
  city varchar(40) NOT NULL,
  district varchar(80) DEFAULT NULL,
  units int(10) unsigned DEFAULT NULL,
  delivery_date date DEFAULT NULL,
  price_min_gbp decimal(14,2) DEFAULT NULL,
  price_max_gbp decimal(14,2) DEFAULT NULL,
  payment_plan varchar(190) DEFAULT NULL,
  desc_tr text,
  desc_en text,
  desc_ru text,
  cover_path varchar(255) DEFAULT NULL,
  cover_ai tinyint(1) NOT NULL DEFAULT 0,
  unit_types text,
  amenities text,
  progress tinyint(3) unsigned DEFAULT NULL,
  down_payment_pct tinyint(3) unsigned DEFAULT NULL,
  installments smallint(5) unsigned DEFAULT NULL,
  video_url varchar(255) DEFAULT NULL,
  lat decimal(9,6) DEFAULT NULL,
  lng decimal(9,6) DEFAULT NULL,
  project_type varchar(20) DEFAULT NULL,
  build_status varchar(20) DEFAULT NULL,
  land_m2 int(10) unsigned DEFAULT NULL,
  reject_reason varchar(255) DEFAULT NULL,
  source varchar(20) NOT NULL DEFAULT 'panel',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY city (city),
  KEY agent_id (agent_id)
) $c;");

    // Fiyat endeksi: her ay, şehir, ilan türü ve emlak tipi için m² medyanı (ilan fiyatlarından)
    $index = ec_table('price_index');
    dbDelta("CREATE TABLE $index (
  period char(7) NOT NULL,
  city varchar(40) NOT NULL,
  mode varchar(10) NOT NULL,
  ptype varchar(20) NOT NULL,
  median_m2_gbp decimal(12,2) NOT NULL DEFAULT 0,
  avg_price_gbp decimal(14,2) NOT NULL DEFAULT 0,
  n int(10) unsigned NOT NULL DEFAULT 0,
  is_demo tinyint(1) NOT NULL DEFAULT 0,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (period,city,mode,ptype)
) $c;");

    // Proje medyası: galeri fotoğrafları, kat planları, broşür (PDF)
    $media = ec_table('project_media');
    dbDelta("CREATE TABLE $media (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  project_id bigint(20) unsigned NOT NULL,
  kind varchar(12) NOT NULL DEFAULT 'photo',
  path varchar(255) NOT NULL,
  title varchar(190) DEFAULT NULL,
  width int(10) unsigned DEFAULT NULL,
  height int(10) unsigned DEFAULT NULL,
  sort smallint(5) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY project_id (project_id,kind,sort)
) $c;");

    // Talepler: ilan / proje sayfasındaki "Mesaj gönder" formu
    $leads = ec_table('leads');
    dbDelta("CREATE TABLE $leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) unsigned DEFAULT NULL,
  listing_id bigint(20) unsigned DEFAULT NULL,
  project_id bigint(20) unsigned DEFAULT NULL,
  name varchar(120) NOT NULL,
  email varchar(190) DEFAULT NULL,
  phone varchar(40) DEFAULT NULL,
  message text,
  lang char(2) NOT NULL DEFAULT 'tr',
  status varchar(12) NOT NULL DEFAULT 'new',
  ip_hash char(40) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY agent_id (agent_id,status),
  KEY listing_id (listing_id)
) $c;");

    // Hatalı ilan bildirimleri
    $reports = ec_table('reports');
    dbDelta("CREATE TABLE $reports (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  listing_id bigint(20) unsigned NOT NULL,
  reason varchar(30) NOT NULL,
  note text,
  email varchar(190) DEFAULT NULL,
  status varchar(12) NOT NULL DEFAULT 'open',
  ip_hash char(40) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY listing_id (listing_id),
  KEY status (status)
) $c;");

    // Telegram botu: sohbet başına açık taslak (gelen fotoğraf ve metinler tek ilanda toplanır)
    $tg = ec_table('tg_drafts');
    dbDelta("CREATE TABLE $tg (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  chat_id varchar(40) NOT NULL,
  agent_id bigint(20) unsigned DEFAULT NULL,
  state varchar(16) NOT NULL DEFAULT 'collecting',
  texts mediumtext,
  photos text,
  data mediumtext,
  listing_id bigint(20) unsigned DEFAULT NULL,
  preview_msg_id bigint(20) DEFAULT NULL,
  error varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY chat_state (chat_id,state)
) $c;");

    // Diğer sitelere (101evler, hangiev) girilen ilanlar: Chrome eklentisi kaydeder
    $syn = ec_table('syndication');
    dbDelta("CREATE TABLE $syn (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  listing_id bigint(20) unsigned NOT NULL,
  agent_id bigint(20) unsigned DEFAULT NULL,
  site varchar(20) NOT NULL,
  status varchar(16) NOT NULL DEFAULT 'filled',
  remote_url varchar(255) DEFAULT NULL,
  remote_id varchar(60) DEFAULT NULL,
  note varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY listing_site (listing_id,site),
  KEY agent_id (agent_id)
) $c;");

    // Paketler (0.5): tanımlar, üyeye verilen paketler, kullanım (kota)
    $pk = ec_table('packages');
    dbDelta("CREATE TABLE $pk (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(120) NOT NULL,
  days smallint(5) unsigned NOT NULL DEFAULT 30,
  quota int(10) unsigned NOT NULL DEFAULT 0,
  features varchar(255) NOT NULL DEFAULT '',
  price varchar(40) DEFAULT NULL,
  amount decimal(10,2) DEFAULT NULL,
  currency char(3) NOT NULL DEFAULT 'EUR',
  promos varchar(255) NOT NULL DEFAULT '',
  promo_days smallint(5) unsigned NOT NULL DEFAULT 7,
  active tinyint(1) NOT NULL DEFAULT 1,
  sort smallint(5) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id)
) $c;");
    $sub = ec_table('subscriptions');
    dbDelta("CREATE TABLE $sub (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) unsigned NOT NULL,
  package_id bigint(20) unsigned DEFAULT NULL,
  name varchar(120) NOT NULL,
  features varchar(255) NOT NULL DEFAULT '',
  quota int(10) unsigned NOT NULL DEFAULT 0,
  promos varchar(255) NOT NULL DEFAULT '',
  promo_days smallint(5) unsigned NOT NULL DEFAULT 7,
  starts_at datetime NOT NULL,
  ends_at datetime NOT NULL,
  status varchar(12) NOT NULL DEFAULT 'active',
  note varchar(255) DEFAULT NULL,
  warned tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY agent (agent_id,status,ends_at)
) $c;");
    $use = ec_table('usage');
    dbDelta("CREATE TABLE $use (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  subscription_id bigint(20) unsigned NOT NULL,
  agent_id bigint(20) unsigned NOT NULL,
  listing_id bigint(20) unsigned NOT NULL,
  feature varchar(20) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY sub_listing (subscription_id,listing_id)
) $c;");

    // Vitrin (1.1): paketteki vitrin kredilerinin kullanımı
    $pu = ec_table('promo_usage');
    dbDelta("CREATE TABLE $pu (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  subscription_id bigint(20) unsigned DEFAULT NULL,
  agent_id bigint(20) unsigned DEFAULT NULL,
  listing_id bigint(20) unsigned NOT NULL,
  kind varchar(12) NOT NULL,
  until datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY sub_kind (subscription_id,kind),
  KEY listing_id (listing_id)
) $c;");

    // Siparişler (1.1): paket satın alma — havale/EFT ya da kartla (sanal POS)
    $or = ec_table('orders');
    dbDelta("CREATE TABLE $or (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) unsigned NOT NULL,
  package_id bigint(20) unsigned NOT NULL,
  amount decimal(10,2) NOT NULL,
  currency char(3) NOT NULL,
  method varchar(12) NOT NULL,
  status varchar(12) NOT NULL DEFAULT 'pending',
  ref varchar(40) NOT NULL,
  receipt_path varchar(255) DEFAULT NULL,
  bank_ref varchar(80) DEFAULT NULL,
  note varchar(255) DEFAULT NULL,
  subscription_id bigint(20) unsigned DEFAULT NULL,
  created_at datetime NOT NULL,
  paid_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY ref (ref),
  KEY agent (agent_id,status)
) $c;");

    // Sosyal medya hesapları (agent_id 0 = sitenin kendi hesabı) ve paylaşım kayıtları
    $so = ec_table('social_accounts');
    dbDelta("CREATE TABLE $so (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) DEFAULT NULL,
  page_id varchar(40) NOT NULL,
  page_name varchar(190) DEFAULT NULL,
  ig_user_id varchar(40) DEFAULT NULL,
  ig_username varchar(100) DEFAULT NULL,
  token_enc text,
  fb_on tinyint(1) NOT NULL DEFAULT 1,
  ig_on tinyint(1) NOT NULL DEFAULT 1,
  status varchar(12) NOT NULL DEFAULT 'ok',
  last_error varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY page_id (page_id),
  KEY agent_id (agent_id)
) $c;");
    $sp = ec_table('social_posts');
    dbDelta("CREATE TABLE $sp (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  listing_id bigint(20) unsigned NOT NULL,
  account_id bigint(20) unsigned NOT NULL,
  network varchar(10) NOT NULL,
  status varchar(12) NOT NULL DEFAULT 'queued',
  attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
  remote_id varchar(80) DEFAULT NULL,
  permalink varchar(255) DEFAULT NULL,
  error varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY one_post (listing_id,account_id,network),
  KEY status (status)
) $c;");

    // 360° fotoğraflar (0.6)
    $pn = ec_table('panos');
    dbDelta("CREATE TABLE $pn (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  listing_id bigint(20) unsigned NOT NULL,
  title varchar(60) DEFAULT NULL,
  path varchar(255) NOT NULL,
  width int(10) unsigned DEFAULT NULL,
  height int(10) unsigned DEFAULT NULL,
  sort smallint(5) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY listing_id (listing_id,sort)
) $c;");

    // 0.6: tapu türü sadece satılık ilanlarda (kiracıyı ilgilendirmez)
    $wpdb->query("UPDATE $listings SET deed_type = NULL WHERE mode <> 'sale' AND deed_type IS NOT NULL");

    // v1 → v3: eski hesap türlerini yeni adlara çevir
    $wpdb->query("UPDATE $agents SET kind = 'ofis' WHERE kind = 'kurumsal'");
    $wpdb->query("UPDATE $agents SET kind = 'danisman' WHERE kind = 'bireysel'");
    $wpdb->query("UPDATE $agents SET email_verified = 1 WHERE status = 'active' AND email_verified = 0 AND wp_user_id IS NULL");
    // Vitrin adresi olmayan eski üyelere kısa ad ver
    if (function_exists('ec_agent_unique_slug')) {
        foreach ($wpdb->get_results("SELECT id, name FROM $agents WHERE slug IS NULL OR slug = ''", ARRAY_A) as $a) {
            $wpdb->update($agents, ['slug' => ec_agent_unique_slug($a['name'], (int) $a['id'])], ['id' => $a['id']]);
        }
    }

    // İlan numaraları 10001'den başlasın (ilk ilanlar "#1" gibi görünmesin).
    $max = (int) $wpdb->get_var("SELECT IFNULL(MAX(id),0) FROM $listings");
    if ($max < 10000) {
        $wpdb->query("ALTER TABLE $listings AUTO_INCREMENT = 10001");
    }

    // 1.3 (db 12): ofis portföyü. Ekipteki danışmanın mevcut ilanları şu anki ofisine yazılır.
    $wpdb->query("UPDATE $listings l JOIN $agents a ON a.id = l.agent_id SET l.office_id = a.parent_id WHERE a.parent_id IS NOT NULL AND l.office_id IS NULL");

    // 0.7: eski fotoğrafların görsel parmak izlerini arka planda hesapla
    if (!wp_next_scheduled('ec_dup_backfill')) {
        wp_schedule_single_event(time() + 30, 'ec_dup_backfill');
    }

    update_option('ec_db_version', EC_DB_VERSION);
}
