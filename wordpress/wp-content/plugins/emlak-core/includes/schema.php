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
  api_key_hash char(64) DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY api_key_hash (api_key_hash),
  KEY email (email),
  KEY slug (slug),
  KEY wp_user_id (wp_user_id),
  KEY telegram_chat_id (telegram_chat_id)
) $c;");

    dbDelta("CREATE TABLE $listings (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  agent_id bigint(20) unsigned DEFAULT NULL,
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
  source varchar(20) NOT NULL DEFAULT 'panel',
  views int(10) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  published_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY search (status,mode,ptype,city),
  KEY price_gbp (price_gbp),
  KEY agent_id (agent_id),
  KEY featured_until (featured_until)
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
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY listing_id (listing_id,sort)
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
  reject_reason varchar(255) DEFAULT NULL,
  source varchar(20) NOT NULL DEFAULT 'panel',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY city (city)
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

    update_option('ec_db_version', EC_DB_VERSION);
}
