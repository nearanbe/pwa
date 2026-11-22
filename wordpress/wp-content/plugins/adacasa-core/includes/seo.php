<?php
/**
 * Arama motoru (SEO) yardımcıları (1.1.6):
 *  - WordPress site haritasına (/wp-sitemap.xml) AdaCasa sağlayıcısı: sabit sayfalar (ana sayfa, şehir bazlı
 *    satılık/kiralık/günlük arama sayfaları, projeler, firmalar, fiyat endeksi, SSS, yasal sayfalar), ilanlar,
 *    projeler ve firma vitrinleri; her dil için ayrı adres.
 *  - Demo kayıtlar (source = 'demo', ec_demo_agents) site haritasına girmez ve sayfalarında noindex olur.
 */
if (!defined('ABSPATH')) {
    exit;
}

/** Demo firma kimlikleri */
function ec_seo_demo_agents(): array
{
    return array_map('intval', (array) get_option('ec_demo_agents', []));
}

/** Kayıt demo mu? $type: listing | project | office */
function ec_seo_is_demo(string $type, int $id): bool
{
    global $wpdb;
    if ($id <= 0) {
        return false;
    }
    if ($type === 'office') {
        return in_array($id, ec_seo_demo_agents(), true);
    }
    $table = $type === 'project' ? ec_table('projects') : ec_table('listings');
    return $wpdb->get_var($wpdb->prepare("SELECT source FROM $table WHERE id = %d", $id)) === 'demo';
}

/** Demo ilan, proje ve firma sayfaları arama motorlarına kapalı (siteyi gezen ziyaretçi görmeye devam eder) */
add_filter('wp_robots', function (array $robots) {
    $checks = [
        'listing' => $GLOBALS['ec_current_listing']['id'] ?? 0,
        'project' => $GLOBALS['ec_current_project']['id'] ?? 0,
        'office' => $GLOBALS['ec_current_office']['id'] ?? 0,
    ];
    foreach ($checks as $type => $id) {
        if ($id && ec_seo_is_demo($type, (int) $id)) {
            $robots['noindex'] = true;
            $robots['follow'] = true;
            unset($robots['max-image-preview']);
            break;
        }
    }
    return $robots;
});

if (class_exists('WP_Sitemaps_Provider')) {
    class EC_Sitemaps_Provider extends WP_Sitemaps_Provider
    {
        const PER_PAGE = 1500;

        public function __construct()
        {
            $this->name = 'adacasa';
            $this->object_type = 'adacasa';
        }

        public function get_object_subtypes()
        {
            return [
                'sayfalar' => (object) ['name' => 'sayfalar'],
                'ilanlar' => (object) ['name' => 'ilanlar'],
                'projeler' => (object) ['name' => 'projeler'],
                'firmalar' => (object) ['name' => 'firmalar'],
            ];
        }

        private function total(string $sub): int
        {
            global $wpdb;
            switch ($sub) {
                case 'ilanlar':
                    return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('listings') . ' l JOIN ' . ec_table('listing_i18n') . " i ON i.listing_id = l.id WHERE l.status = 'published' AND l.source <> 'demo'");
                case 'projeler':
                    return count(EC_LANGS) * (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('projects') . " WHERE status = 'published' AND source <> 'demo'");
                case 'firmalar':
                    return count(EC_LANGS) * count($this->offices());
                default:
                    return count($this->pages());
            }
        }

        public function get_max_num_pages($object_subtype = '')
        {
            return (int) ceil($this->total((string) $object_subtype) / self::PER_PAGE);
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            global $wpdb;
            $sub = (string) $object_subtype;
            $off = max(0, ((int) $page_num - 1) * self::PER_PAGE);
            $out = [];
            if ($sub === 'ilanlar') {
                $rows = $wpdb->get_results($wpdb->prepare(
                    'SELECT l.id, i.lang, i.slug, l.updated_at FROM ' . ec_table('listings') . ' l JOIN ' . ec_table('listing_i18n') .
                    " i ON i.listing_id = l.id WHERE l.status = 'published' AND l.source <> 'demo' ORDER BY l.id DESC, i.lang ASC LIMIT %d OFFSET %d",
                    self::PER_PAGE,
                    $off
                ), ARRAY_A);
                foreach ($rows as $r) {
                    if (in_array($r['lang'], EC_LANGS, true)) {
                        $out[] = ['loc' => ec_url_listing((int) $r['id'], $r['slug'], $r['lang']), 'lastmod' => gmdate('c', strtotime($r['updated_at'] . ' UTC'))];
                    }
                }
                return $out;
            }
            if ($sub === 'projeler') {
                $rows = $wpdb->get_results('SELECT id, name, slug, updated_at FROM ' . ec_table('projects') . " WHERE status = 'published' AND source <> 'demo' ORDER BY id DESC", ARRAY_A);
                foreach ($rows as $r) {
                    foreach (EC_LANGS as $l) {
                        $out[] = ['loc' => ec_url_project((int) $r['id'], $r['slug'] ?: ec_slugify($r['name']), $l), 'lastmod' => gmdate('c', strtotime($r['updated_at'] . ' UTC'))];
                    }
                }
                return array_slice($out, $off, self::PER_PAGE);
            }
            if ($sub === 'firmalar') {
                foreach ($this->offices() as $slug) {
                    foreach (EC_LANGS as $l) {
                        $out[] = ['loc' => ec_url_office($slug, $l)];
                    }
                }
                return array_slice($out, $off, self::PER_PAGE);
            }
            return array_slice($this->pages(), $off, self::PER_PAGE);
        }

        /** Vitrini olan, demo olmayan, etkin firmalar */
        private function offices(): array
        {
            global $wpdb;
            $rows = $wpdb->get_results('SELECT id, slug FROM ' . ec_table('agents') . " WHERE status = 'active' AND kind <> 'sahibi' AND slug IS NOT NULL AND slug <> '' ORDER BY id ASC", ARRAY_A);
            $demo = ec_seo_demo_agents();
            return array_values(array_map(fn($r) => $r['slug'], array_filter($rows, fn($r) => !in_array((int) $r['id'], $demo, true))));
        }

        /** Sabit sayfalar ve ilan bulunan şehir arama sayfaları */
        private function pages(): array
        {
            global $wpdb;
            $out = [];
            // İlan bulunan (demo hariç) mod + şehir çiftleri; boş arama sayfası haritaya girmez
            $pairs = $wpdb->get_results('SELECT mode, city, MAX(updated_at) AS m FROM ' . ec_table('listings') . " WHERE status = 'published' AND source <> 'demo' GROUP BY mode, city", ARRAY_A);
            $modes = [];
            foreach ($pairs as $p) {
                $modes[$p['mode']] = max($modes[$p['mode']] ?? '', $p['m']);
            }
            foreach (EC_LANGS as $l) {
                $out[] = ['loc' => ec_url_home($l)];
                foreach (ec_modes() as $m) {
                    $out[] = ['loc' => ec_url_section($m, $l)] + (!empty($modes[$m]) ? ['lastmod' => gmdate('c', strtotime($modes[$m] . ' UTC'))] : []);
                }
                foreach ($pairs as $p) {
                    if (isset(ec_cities()[$p['city']]) && in_array($p['mode'], ec_modes(), true)) {
                        $out[] = ['loc' => ec_url_section($p['mode'], $l, $p['city']), 'lastmod' => gmdate('c', strtotime($p['m'] . ' UTC'))];
                    }
                }
                foreach (['project', 'offices', 'index', 'faq'] as $s) {
                    $out[] = ['loc' => ec_url_section($s, $l)];
                }
                if (function_exists('ec_legal_docs')) {
                    foreach (array_keys(ec_legal_docs()) as $doc) {
                        $out[] = ['loc' => ec_url_legal($doc, $l)];
                    }
                }
            }
            return $out;
        }
    }

    add_action('init', function () {
        wp_register_sitemap_provider('adacasa', new EC_Sitemaps_Provider());
    });
}

/** WordPress'in kullanıcı (yazar) site haritası gereksiz: yazarlar ayrı sayfa olarak yayınlanmıyor */
add_filter('wp_sitemaps_add_provider', fn($provider, $name) => $name === 'users' ? false : $provider, 10, 2);
// Kök adres (/) /tr/'ye yönlendirdiği için haritada yönlendiren adres olmasın: dil ana sayfası kullanılır (1.2.4)
add_filter('wp_sitemaps_posts_show_on_front_entry', fn($e) => array_merge((array) $e, ['loc' => ec_url_home('tr')]));
add_filter('wp_sitemaps_posts_entry', fn($e) => (isset($e['loc']) && untrailingslashit($e['loc']) === untrailingslashit(home_url('/'))) ? array_merge($e, ['loc' => ec_url_home('tr')]) : $e);
