<?php
/**
 * Demo verisi: kurgusal danışmanlar, ilanlar, projeler, blog yazıları ve geçmiş fiyat endeksi.
 * Yönetim panelinden (Emlak → Ayarlar) veya "wp emlak seed / unseed" ile eklenir ve silinir.
 * Tüm demo kayıtları işaretlidir (source = 'demo', is_demo = 1, _ec_demo meta) ve tek seferde silinir.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_demo_active(): bool
{
    return (bool) get_option('ec_demo_agents');
}

/** Blog için "Blog" sayfasını ve sabit ana sayfayı oluşturur (yoksa) */
function ec_ensure_blog_page(): void
{
    if ((int) get_option('page_for_posts') && get_post((int) get_option('page_for_posts'))) {
        return;
    }
    $front = get_page_by_path('ana-sayfa');
    $frontId = $front ? $front->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Ana Sayfa', 'post_name' => 'ana-sayfa']);
    $blog = get_page_by_path('blog');
    $blogId = $blog ? $blog->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog']);
    update_option('show_on_front', 'page');
    update_option('page_on_front', (int) $frontId);
    update_option('page_for_posts', (int) $blogId);
}

/** @return string|WP_Error özet */
function ec_demo_seed()
{
    global $wpdb;
    if (ec_demo_active()) {
        return new WP_Error('ec_demo', 'Demo verisi zaten yüklü. Önce silin.');
    }
    @set_time_limit(300);
    mt_srand(2026);

    // --- Danışmanlar (numaralar bilerek geçersizdir) ---
    $agents = [
        ['Deniz Yıldız', 'Mavi Ada Emlak', 'girne'],
        ['Kerem Aksoy', 'Zeytin Property', 'lefkosa'],
        ['Selin Arda', 'Liman Gayrimenkul', 'gazimagusa'],
        ['Mert Kaya', 'Kuzey Yapı', 'iskele'],
        ['Elif Demir', 'Portakal Emlak', 'guzelyurt'],
        ['Can Özkan', 'Lefke Homes', 'lefke'],
    ];
    $agentIds = [];
    foreach ($agents as $i => $a) {
        $wpdb->insert(ec_table('agents'), [
            'kind' => 'ofis', 'company_type' => 'Emlak Ofisi', 'name' => $a[1], 'office' => $a[1], 'contact_person' => $a[0],
            'slug' => ec_agent_unique_slug($a[1]), 'email_verified' => 1, 'verified' => $i < 3 ? 1 : 0,
            'bio' => $a[1] . ', ' . ec_city_name($a[2], 'tr') . ' bölgesinde satılık ve kiralık konut, arsa ve proje danışmanlığı veren kurgusal bir demo ofisidir.',
            'email' => 'demo' . ($i + 1) . '@example.com', 'phone' => '+90000000000' . ($i + 1), 'whatsapp' => '90000000000' . ($i + 1),
            'city' => $a[2], 'status' => 'active', 'created_at' => ec_now(), 'updated_at' => ec_now(),
        ]);
        $agentIds[$a[2]] = (int) $wpdb->insert_id;
    }
    update_option('ec_demo_agents', array_values($agentIds));
    $agentFor = fn(string $city) => $agentIds[$city];

    // --- El ile yazılmış örnek ilanlar ---
    // mode, ptype, subtype, city, district, price, m2, rooms, baths, deed, furnished, featured, [tr, en, ru]
    $L = [
        ['sale', 'konut', 'daire', 'girne', 'Merkez', 139000, 115, '3+1', 2, 'turk-kocani', 0, 1, ['Merkezde deniz manzaralı 3+1 daire', 'Sea view 3-bedroom apartment in the centre', 'Квартира 3+1 с видом на море в центре']],
        ['sale', 'konut', 'villa', 'girne', 'Lapta', 385000, 240, '4+1', 3, 'turk-kocani', 0, 1, ['Havuzlu müstakil villa', 'Detached villa with private pool', 'Отдельная вилла с бассейном']],
        ['sale', 'konut', 'penthouse', 'lefkosa', 'Gönyeli', 138900, 150, '3+1', 2, 'esdeger', 0, 1, ['Şehir manzaralı penthouse', 'Penthouse with city views', 'Пентхаус с видом на город']],
        ['sale', 'arsa', 'konut-imarli', 'iskele', 'Bafra', 95000, 650, null, null, 'turk-kocani', null, 0, ['Denize yakın imarlı arsa', 'Building plot close to the sea', 'Участок под застройку рядом с морем']],
        ['sale', 'konut', 'daire', 'gazimagusa', 'Mormenekşe', 72000, 85, '2+1', 1, 'proje', 0, 0, ['Üniversiteye yakın yatırımlık daire', 'Investment flat near the university', 'Инвестиционная квартира у университета']],
        ['sale', 'ticari', 'dukkan', 'girne', 'Merkez', 210000, 90, null, 1, 'turk-kocani', null, 0, ['Ana cadde üzerinde dükkan', 'Shop on the main street', 'Магазин на главной улице']],
        ['sale', 'konut', 'daire', 'iskele', 'Long Beach', 119000, 78, '1+1', 1, 'proje', 1, 1, ['Long Beach’te sitede eşyalı 1+1', 'Furnished 1-bed in a Long Beach complex', 'Меблированная 1+1 в комплексе на Лонг-Бич']],
        ['sale', 'konut', 'ikiz', 'girne', 'Çatalköy', 255000, 190, '3+1', 3, 'turk-kocani', 0, 0, ['Dağ ve deniz manzaralı ikiz villa', 'Semi-detached villa with mountain and sea views', 'Таунхаус с видом на горы и море']],
        ['rent', 'konut', 'daire', 'girne', 'Kaşgar', 650, 90, '2+1', 1, null, 1, 0, ['Kaşgar’da eşyalı kiralık 2+1', 'Furnished 2-bed for rent in Kaşgar', 'Сдаётся меблированная 2+1 в Кашгаре']],
        ['rent', 'konut', 'villa', 'iskele', 'Long Beach', 1800, 200, '3+1', 2, null, 1, 1, ['Deniz kenarında kiralık villa', 'Beachfront villa for rent', 'Вилла у моря в аренду']],
        ['rent', 'konut', 'daire', 'lefkosa', 'Hamitköy', 520, 70, '1+1', 1, null, 1, 0, ['Eşyalı kiralık 1+1', 'Furnished 1-bed for rent', 'Сдаётся меблированная 1+1']],
        ['rent', 'konut', 'studyo', 'gazimagusa', 'Sakarya', 380, 45, '1+0', 1, null, 1, 0, ['Öğrenciye uygun stüdyo daire', 'Student-friendly studio', 'Студия для студентов']],
        ['daily', 'konut', 'bungalow', 'girne', 'Alsancak', 85, 60, '1+1', 1, null, 1, 1, ['Günlük kiralık bungalow', 'Bungalow for holiday rental', 'Бунгало посуточно']],
        ['daily', 'konut', 'villa', 'guzelyurt', 'Merkez', 160, 180, '3+1', 2, null, 1, 0, ['Portakal bahçesinde tatil villası', 'Holiday villa in an orange grove', 'Вилла для отдыха в апельсиновом саду']],
    ];

    // --- Otomatik üretilen ilanlar: her şehirde satılık ve kiralık konut (fiyat endeksi için) ---
    $m2Sale = ['girne' => 2100, 'lefkosa' => 1350, 'gazimagusa' => 1200, 'iskele' => 1750, 'guzelyurt' => 950, 'lefke' => 900];
    $m2Rent = ['girne' => 8.5, 'lefkosa' => 7, 'gazimagusa' => 6.5, 'iskele' => 8, 'guzelyurt' => 5, 'lefke' => 4.5];
    $sizes = ['1+1' => [55, 75], '2+1' => [80, 110], '3+1' => [115, 160], '4+1' => [170, 260]];
    $deeds = ['turk-kocani', 'turk-kocani', 'esdeger', 'proje', 'tahsis'];
    $tpl = [
        'tr' => '%1$s bölgesinde %2$s %3$s',
        'en' => '%2$s %3$s in %1$s',
        'ru' => '%3$s %2$s, район %1$s',
    ];
    foreach (ec_cities() as $city => $c) {
        foreach (['sale' => 5, 'rent' => 3] as $mode => $count) {
            for ($k = 0; $k < $count; $k++) {
                $rooms = array_rand($sizes);
                $sub = $mode === 'sale'
                    ? ($rooms === '4+1' ? 'villa' : (['daire', 'daire', 'ikiz', 'penthouse', 'mustakil'][mt_rand(0, 4)]))
                    : ($rooms === '4+1' ? 'villa' : 'daire');
                $area = mt_rand($sizes[$rooms][0], $sizes[$rooms][1]);
                $f = mt_rand(80, 125) / 100;
                $price = $mode === 'sale'
                    ? round($area * $m2Sale[$city] * $f / 500) * 500
                    : round($area * $m2Rent[$city] * $f / 10) * 10;
                $dist = $c['districts'][mt_rand(0, count($c['districts']) - 1)];
                $titles = [];
                foreach (['tr', 'en', 'ru'] as $l) {
                    $titles[] = sprintf($tpl[$l], $dist, $rooms, mb_strtolower(ec_subtype_name('konut', $sub, $l)));
                }
                $titles[0] = ($mode === 'rent' ? 'Kiralık: ' : '') . $titles[0];
                $titles[1] = ($mode === 'rent' ? 'For rent: ' : '') . ucfirst($titles[1]);
                $titles[2] = ($mode === 'rent' ? 'Аренда: ' : '') . mb_strtoupper(mb_substr($titles[2], 0, 1)) . mb_substr($titles[2], 1);
                $L[] = [$mode, 'konut', $sub, $city, $dist, $price, $area, $rooms, max(1, (int) $rooms[0] - 1), $mode === 'sale' ? $deeds[mt_rand(0, 4)] : null, mt_rand(0, 1), 0, $titles];
            }
        }
    }

    $desc = [
        'tr' => '%s. %s bölgesinde, %s m² kullanım alanı. Ulaşım, market ve sahile yakın. Detaylı bilgi ve randevu için danışmanımızla iletişime geçin.',
        'en' => '%s. Located in %s with %s m² of living space. Close to transport, shops and the beach. Contact our agent for details and viewings.',
        'ru' => '%s. Район %s, площадь %s м². Рядом транспорт, магазины и пляж. Свяжитесь с нашим агентом для подробностей и просмотра.',
    ];
    $tmp = get_temp_dir();
    $n = 0;
    foreach ($L as $i => $x) {
        $i18n = [];
        foreach (['tr', 'en', 'ru'] as $k => $lang) {
            $i18n[$lang] = ['title' => $x[12][$k], 'description' => sprintf($desc[$lang], $x[12][$k], $x[4], $x[6] ?? '—')];
        }
        $featKeys = array_keys(ec_features());
        shuffle($featKeys);
        $id = ec_listing_create([
            'mode' => $x[0], 'ptype' => $x[1], 'subtype' => $x[2], 'city' => $x[3], 'district' => $x[4],
            'price' => $x[5], 'currency' => 'GBP', 'area_m2' => $x[6], 'rooms' => $x[7], 'baths' => $x[8],
            'deed_type' => $x[9], 'furnished' => $x[10], 'status' => 'published', 'i18n' => $i18n,
            'features' => $x[1] === 'konut' ? array_slice($featKeys, 0, mt_rand(4, 9)) : [],
            'vat_paid' => $x[0] === 'sale' ? mt_rand(0, 1) : null,
            'transformer_paid' => $x[0] === 'sale' ? mt_rand(0, 1) : null,
            'sea_distance_m' => mt_rand(1, 40) * 100,
            'year_built' => $x[1] === 'konut' ? mt_rand(2008, 2026) : null,
            'floor' => in_array($x[2], ['daire', 'penthouse'], true) ? (string) mt_rand(1, 6) : null,
            'total_floors' => in_array($x[2], ['daire', 'penthouse'], true) ? 7 : null,
            // Demo konum: şehir merkezi çevresinde (yaklaşık 2-4 km)
            'lat' => ec_city_coords()[$x[3]][0] + mt_rand(-250, 250) / 10000,
            'lng' => ec_city_coords()[$x[3]][1] + mt_rand(-300, 300) / 10000,
        ], $agentFor($x[3]), 'demo');
        if (is_wp_error($id)) {
            continue;
        }
        // Yayın tarihlerini son 60 güne yay (sıralama doğal görünsün)
        $wpdb->update(ec_table('listings'), ['published_at' => gmdate('Y-m-d H:i:s', time() - mt_rand(0, 60) * DAY_IN_SECONDS - $i * 600)], ['id' => $id]);
        if ($x[11]) {
            $wpdb->update(ec_table('listings'), ['featured_until' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS)], ['id' => $id]);
        }
        $photos = $i < 14 ? 3 : 2;
        for ($p = 0; $p < $photos; $p++) {
            $f = $tmp . 'ec-demo-' . $id . '-' . $p . '.jpg';
            ec_demo_image($f, $i * 3 + $p, $x[1]);
            ec_photo_add($id, $f);
            @unlink($f);
        }
        $n++;
    }

    // --- Projeler ---
    $P = [
        ['Mavi Sahil Residence', 'Mavi Ada İnşaat', 'girne', 'Alsancak', 48, '2027-12-01', 120000, 320000, '%35 peşin, 36 ay taksit',
            ['Alsancak sahiline yürüme mesafesinde, havuzlu ve spor salonlu 48 konutluk site. 1+1’den 3+1’e daire ve penthouse seçenekleri.', 'A 48-unit complex within walking distance of Alsancak beach, with pool and gym. Apartments and penthouses from 1 to 3 bedrooms.', 'Комплекс на 48 квартир в шаговой доступности от пляжа Алсанджак, с бассейном и спортзалом. Квартиры и пентхаусы от 1 до 3 спален.']],
        ['Zeytin Park', 'Zeytin Yapı', 'gazimagusa', 'Mormenekşe', 32, '2027-06-01', 65000, 140000, '%30 peşin, 24 ay taksit',
            ['Üniversitelere yakın, kira getirisi odaklı 32 dairelik proje. Öğrenci ve akademisyen kiralamasına uygun.', 'A 32-apartment project near the universities, designed for rental income from students and staff.', 'Проект на 32 квартиры рядом с университетами, ориентирован на арендный доход.']],
        ['Liman Towers', 'Liman İnşaat', 'iskele', 'Long Beach', 164, '2028-03-01', 105000, 290000, '%40 peşin, 48 ay taksit',
            ['Long Beach’te denize sıfır, resepsiyon, kapalı havuz ve SPA’lı 164 konutluk kule projesi.', 'A 164-unit beachfront tower on Long Beach with reception, indoor pool and spa.', 'Башня на 164 квартиры на первой линии Лонг-Бич: ресепшн, крытый бассейн и спа.']],
        ['Kuzey Vadi Evleri', 'Kuzey Yapı', 'lefkosa', 'Gönyeli', 24, '2027-09-01', 98000, 175000, '%50 peşin, 18 ay taksit',
            ['Gönyeli’de bahçeli ikiz villalardan oluşan 24 konutluk sakin bir aile sitesi.', 'A quiet family community of 24 semi-detached villas with gardens in Gönyeli.', 'Тихий семейный комплекс из 24 таунхаусов с садами в Гёнйели.']],
        ['Portakal Bahçe Villaları', 'Portakal Yapı', 'guzelyurt', 'Merkez', 12, '2027-05-01', 145000, 210000, '%40 peşin, 24 ay taksit',
            ['Güzelyurt’un portakal bahçeleri arasında, geniş arsalı 12 müstakil villa.', '12 detached villas on large plots among the orange groves of Güzelyurt.', '12 отдельных вилл на больших участках среди апельсиновых садов Гюзельюрта.']],
        ['Bafra Bay Suites', 'Ada Developments', 'iskele', 'Bafra', 96, '2028-06-01', 89000, 260000, '%35 peşin, 60 ay taksit',
            ['Bafra turizm bölgesinde, otel konseptli 96 konutluk yatırım projesi.', 'A 96-unit hotel-concept investment project in the Bafra tourism zone.', 'Инвестиционный проект на 96 апартаментов в формате отеля в туристической зоне Бафра.']],
    ];
    foreach ($P as $k => $p) {
        $pid = ec_project_create([
            'agent_id' => $agentFor($p[2]), 'name' => $p[0], 'developer' => $p[1], 'city' => $p[2], 'district' => $p[3],
            'units' => $p[4], 'delivery_date' => $p[5], 'price_min_gbp' => $p[6], 'price_max_gbp' => $p[7], 'payment_plan' => $p[8],
            'desc_tr' => $p[9][0], 'desc_en' => $p[9][1], 'desc_ru' => $p[9][2],
            'progress' => [35, 70, 15, 55, 100, 10][$k],
            'project_type' => ['konut', 'konut', 'tatil', 'villa', 'villa', 'tatil'][$k],
            'build_status' => [null, null, null, null, 'completed', 'planned'][$k],
            'land_m2' => [8500, 4200, 21000, 9600, 14000, 18500][$k],
            'lat' => ec_city_coords()[$p[2]][0] + 0.01 * ($k - 2),
            'lng' => ec_city_coords()[$p[2]][1] - 0.012 * ($k - 2),
            'down_payment_pct' => (int) ltrim($p[8], '%'), 'installments' => (int) preg_replace('/.*?(\d+) ay.*/', '$1', $p[8]),
            'amenities' => array_slice(array_keys(ec_amenities()), $k, 6),
            'unit_types' => [
                ['type' => '1+1', 'm2_min' => 55, 'm2_max' => 70, 'price_from_gbp' => $p[6], 'available' => 6 + $k],
                ['type' => '2+1', 'm2_min' => 85, 'm2_max' => 105, 'price_from_gbp' => round(($p[6] + $p[7]) / 2 / 1000) * 1000, 'available' => 10 - $k],
                ['type' => '3+1', 'm2_min' => 130, 'm2_max' => 160, 'price_from_gbp' => $p[7], 'available' => 3],
            ],
        ], 'demo');
        if (!is_wp_error($pid)) {
            $f = $tmp . 'ec-demo-proje-' . $pid . '.jpg';
            ec_demo_image($f, 100 + $k, 'proje');
            ec_project_set_cover($pid, $f);
            @unlink($f);
            // İlk iki projeye galeri ve kat planı (daire tipi adıyla)
            if ($k < 2) {
                for ($g = 0; $g < 3; $g++) {
                    $f = $tmp . 'ec-demo-pg-' . $pid . '-' . $g . '.jpg';
                    ec_demo_image($f, 110 + $k * 5 + $g, 'konut');
                    ec_project_media_add($pid, $f, 'photo');
                    @unlink($f);
                }
                foreach (['1+1', '2+1'] as $g => $type) {
                    $f = $tmp . 'ec-demo-pp-' . $pid . '-' . $g . '.jpg';
                    ec_demo_image($f, 130 + $g, 'arsa');
                    ec_project_media_add($pid, $f, 'plan', $type);
                    @unlink($f);
                }
            }
        }
    }

    // --- Fiyat endeksi: bu ay gerçek hesap, geçmiş 12 ay demo ---
    ec_index_compute();
    ec_demo_index_history();

    // --- Blog ---
    ec_ensure_blog_page();
    // WordPress'in örnek "Hello world!" yazısı hiç düzenlenmemişse çöpe taşınır (geri alınabilir)
    $hello = get_page_by_path('hello-world', OBJECT, 'post');
    if ($hello && $hello->post_modified_gmt === $hello->post_date_gmt) {
        wp_trash_post($hello->ID);
    }
    $posts = ec_demo_posts();
    foreach ($posts as $k => $post) {
        $postId = wp_insert_post([
            'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $post[0], 'post_content' => $post[1],
            'post_excerpt' => $post[2], 'post_date' => gmdate('Y-m-d H:i:s', time() - ($k + 1) * 4 * DAY_IN_SECONDS),
        ]);
        if ($postId && !is_wp_error($postId)) {
            update_post_meta($postId, '_ec_demo', 1);
            ec_demo_featured_image($postId, 200 + $k);
        }
    }

    return sprintf('%d ilan, %d danışman, %d proje ve %d blog yazısı eklendi.', $n, count($agentIds), count($P), count($posts));
}

/** Geçmiş 12 ay için demo endeks satırları (bu ayın gerçek değerinden geriye doğru) */
function ec_demo_index_history(): void
{
    global $wpdb;
    $t = ec_table('price_index');
    $now = gmdate('Y-m');
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE period = %s", $now), ARRAY_A);
    foreach ($rows as $r) {
        $growth = ($r['mode'] === 'sale' ? 0.009 : 0.011) + crc32($r['city']) % 7 / 1000; // aylık %0,9–1,7
        for ($k = 1; $k <= 12; $k++) {
            $noise = 1 + (mt_rand(-15, 15) / 1000);
            $f = pow(1 + $growth, -$k) * $noise;
            $wpdb->replace($t, [
                'period' => gmdate('Y-m', strtotime($now . '-01 -' . $k . ' months')),
                'city' => $r['city'], 'mode' => $r['mode'], 'ptype' => $r['ptype'],
                'median_m2_gbp' => round($r['median_m2_gbp'] * $f, 2),
                'avg_price_gbp' => round($r['avg_price_gbp'] * $f, 2),
                'n' => max(EC_INDEX_MIN_N, (int) $r['n'] - intdiv($k, 3)),
                'is_demo' => 1, 'updated_at' => ec_now(),
            ]);
        }
    }
}

function ec_demo_unseed(): string
{
    global $wpdb;
    $ids = $wpdb->get_col('SELECT id FROM ' . ec_table('listings') . " WHERE source = 'demo'");
    foreach ($ids as $id) {
        foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('photos') . ' WHERE listing_id = %d', $id)) as $pid) {
            ec_photo_delete((int) $id, (int) $pid);
        }
        foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('panos') . ' WHERE listing_id = %d', $id)) as $pid) {
            ec_pano_delete((int) $id, (int) $pid);
        }
        ec_video_cleanup((int) $id);
        @rmdir(ec_photo_base()['dir'] . '/' . $id);
        $wpdb->delete(ec_table('listing_i18n'), ['listing_id' => $id]);
        $wpdb->delete(ec_table('listings'), ['id' => $id]);
    }
    foreach ($wpdb->get_col('SELECT id FROM ' . ec_table('projects') . " WHERE source = 'demo'") as $pid) {
        ec_project_delete((int) $pid);
    }
    foreach ((array) get_option('ec_demo_agents', []) as $a) {
        // İlk sürümün demo projeleri "demo" etiketi taşımıyordu; danışmana göre de temizle
        foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('projects') . ' WHERE agent_id = %d', $a)) as $pid) {
            ec_project_delete((int) $pid);
        }
        $wpdb->delete(ec_table('agents'), ['id' => $a]);
    }
    delete_option('ec_demo_agents');
    $wpdb->delete(ec_table('price_index'), ['is_demo' => 1]);
    ec_index_compute();
    // Blog: sadece hiç düzenlenmemiş demo yazılar silinir
    $posts = get_posts(['post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_ec_demo', 'meta_value' => 1]);
    $deleted = 0;
    foreach ($posts as $p) {
        if ($p->post_modified_gmt === $p->post_date_gmt || strtotime($p->post_modified_gmt) - strtotime($p->post_date_gmt) < 5) {
            $thumb = get_post_thumbnail_id($p->ID);
            if ($thumb) {
                wp_delete_attachment($thumb, true);
            }
            wp_delete_post($p->ID, true);
            $deleted++;
        }
    }
    // Silinen ilan / projelere bağlı talep, bildirim ve aktarım kayıtlarını temizle
    foreach (['leads', 'reports', 'syndication'] as $tb) {
        $wpdb->query('DELETE x FROM ' . ec_table($tb) . ' x LEFT JOIN ' . ec_table('listings') . ' l ON l.id = x.listing_id WHERE x.listing_id IS NOT NULL AND l.id IS NULL');
    }
    $wpdb->query('DELETE x FROM ' . ec_table('leads') . ' x LEFT JOIN ' . ec_table('projects') . ' p ON p.id = x.project_id WHERE x.project_id IS NOT NULL AND p.id IS NULL');
    return sprintf('%d demo ilan ve %d demo blog yazısı silindi.', count($ids), $deleted);
}

/** Örnek blog yazıları (Türkçe). Yayına almadan önce gözden geçirin. */
function ec_demo_posts(): array
{
    $legal = '<p><em>Bu yazı genel bilgilendirme amaçlıdır, hukuki danışmanlık değildir. Mevzuat ve uygulamalar değişebilir; işlem yapmadan önce mutlaka bir avukata danışın.</em></p>';
    return [
        [
            'KKTC’de tapu türleri: Türk koçanı, eşdeğer ve tahsis arasındaki fark',
            '<p>Kuzey Kıbrıs’ta ev bakarken ilanlarda sık sık “Türk koçanı”, “eşdeğer” ya da “tahsis” ifadelerini görürsünüz. Bu ifadeler mülkün tapu geçmişini anlatır ve özellikle yabancı alıcılar için fiyat kadar önemlidir.</p>'
            . '<h2>Türk koçanı</h2><p>1974 öncesinde de Kıbrıslı Türklere ait olan veya kaydı Türk mülkiyetinde bulunan topraklar için verilen tapudur. Mülkiyet geçmişi en az tartışmalı kabul edildiği için genellikle en çok tercih edilen ve değerini en iyi koruyan tapu türüdür.</p>'
            . '<h2>Eşdeğer koçan</h2><p>1974 sonrasında güneyde mal bırakan Kıbrıslı Türklere, bu mallarının karşılığı olarak kuzeyde verilen mülkler için düzenlenen tapudur. Bugün yaygın olarak alınıp satılır; ancak mülkün 1974 öncesi geçmişine dair soruları avukatınıza sormanız önerilir.</p>'
            . '<h2>Tahsis</h2><p>Devlet tarafından belirli kişilere tahsis edilen mülklerdir. Hukuki durumu diğerlerine göre daha karmaşık olabilir; satın almadan önce tapunun eşdeğere dönüştürülüp dönüştürülmediğini ve devrin mümkün olup olmadığını mutlaka kontrol ettirin.</p>'
            . '<h2>Projeden alım</h2><p>Henüz bitmemiş projelerde tapu çoğu zaman proje arazisinin üzerindedir ve daireye ait ayrı tapu inşaat bitince çıkar. Bu durumda satış sözleşmesinin Tapu Dairesi’ne kaydettirilmesi alıcıyı korumanın en önemli adımıdır.</p>'
            . '<p>AdaCasa’da her satılık ilanda tapu türü ayrı bir alan olarak gösterilir ve aramada filtrelenebilir.</p>' . $legal,
            'Türk koçanı, eşdeğer ve tahsis tapu ne demek? Kuzey Kıbrıs’ta ev alırken tapu türünün neden önemli olduğunu sade bir dille anlattık.',
        ],
        [
            'Yabancılar Kuzey Kıbrıs’ta nasıl ev alır? Adım adım süreç',
            '<p>Kuzey Kıbrıs’ta yabancıların mülk edinmesi mümkündür, ancak süreç Türkiye’den veya Avrupa’dan farklıdır. Aşağıdaki adımlar genel bir yol haritası sunar.</p>'
            . '<h2>1. Bağımsız bir avukat seçin</h2><p>Satıcının veya emlakçının önerdiği değil, sizin adınıza çalışacak bir avukatla ilerleyin. Avukat tapu kaydını, ipotek ve haciz durumunu ve satıcının satış yetkisini kontrol eder.</p>'
            . '<h2>2. Satış sözleşmesi ve kapora</h2><p>Taraflar fiyat ve ödeme planı üzerinde anlaşınca satış sözleşmesi imzalanır. Sözleşmenin kısa süre içinde Tapu Dairesi’ne kaydettirilmesi, mülkün başkasına satılmasına karşı alıcıyı korur.</p>'
            . '<h2>3. Alım izni</h2><p>Yabancı uyruklu alıcılar için mülk edinme izni başvurusu yapılır. Bu süreç genellikle aylar sürebilir; bu sürede sözleşmeniz kayıtlı olduğu için haklarınız korunur.</p>'
            . '<h2>4. Tapu devri</h2><p>İzin çıktıktan sonra tapu devri yapılır ve devir sırasında ilgili harç ve vergiler ödenir. Güncel oranları avukatınızdan öğrenin; ilan fiyatına bu masrafları da eklemeyi unutmayın.</p>'
            . '<h2>Pratik öneriler</h2><ul><li>Görmediğiniz mülk için para göndermeyin.</li><li>Tüm ödemeleri banka üzerinden ve belgeli yapın.</li><li>Projeden alımda inşaat firmasının daha önce teslim ettiği projeleri ziyaret edin.</li></ul>' . $legal,
            'Avukat seçiminden tapu devrine kadar, yabancıların Kuzey Kıbrıs’ta ev alma sürecinin adımları.',
        ],
        [
            'Kıbrıs’ta kiralık ev ararken dikkat edilecek 7 nokta',
            '<p>Öğrenci, çalışan ya da emekli olun; Kuzey Kıbrıs’ta kiralık ev ararken birkaç basit kontrol sizi sonradan yaşanacak sorunlardan korur.</p>'
            . '<ol><li><strong>Görmeden kapora yatırmayın.</strong> Evi bizzat ya da güvendiğiniz biri aracılığıyla görün.</li>'
            . '<li><strong>Karşınızdaki kişinin yetkisini doğrulayın.</strong> Ev sahibi mi, yetkili emlakçı mı? Kimlik ve yetki belgesi isteyin.</li>'
            . '<li><strong>Yazılı sözleşme yapın.</strong> Kira bedeli, artış koşulları, süre ve çıkış şartları açıkça yazılsın.</li>'
            . '<li><strong>Depozito şartlarını netleştirin.</strong> Ne zaman ve hangi koşullarla iade edileceği sözleşmede olsun.</li>'
            . '<li><strong>Elektrik ve su borcunu sorun.</strong> Önceki kiracıdan kalan fatura borcu olmadığından emin olun.</li>'
            . '<li><strong>Eşyaları fotoğraflayın.</strong> Eşyalı evlerde giriş günü eşya listesi ve fotoğraflar sizi korur.</li>'
            . '<li><strong>Kesintilere hazırlıklı olun.</strong> Jeneratör, güneş enerjisi veya su deposu olup olmadığını sorun.</li></ol>'
            . '<p>AdaCasa’da kiralık ilanları şehir, bölge ve oda sayısına göre filtreleyebilir, ilan sahibine doğrudan WhatsApp’tan yazabilirsiniz.</p>',
            'Kapora, sözleşme, depozito ve fatura borcu: Kuzey Kıbrıs’ta kiralık ev tutmadan önce kontrol etmeniz gereken yedi şey.',
        ],
    ];
}

function ec_demo_featured_image(int $postId, int $seed): void
{
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $up = wp_upload_dir();
    $file = $up['path'] . '/adacasa-blog-' . $postId . '.jpg';
    ec_demo_image($file, $seed, 'konut');
    $att = wp_insert_attachment(['post_mime_type' => 'image/jpeg', 'post_title' => get_the_title($postId), 'post_status' => 'inherit'], $file, $postId);
    if ($att && !is_wp_error($att)) {
        wp_update_attachment_metadata($att, wp_generate_attachment_metadata($att, $file));
        set_post_thumbnail($postId, $att);
    }
}

/** Fotoğraf yerine kullanılan sade, kurgusal manzara görseli üretir. $ptype: konut | arsa | ticari | proje */
function ec_demo_image(string $file, int $seed, string $ptype): void
{
    $w = 1600;
    $h = 1000;
    $im = imagecreatetruecolor($w, $h);
    $skies = [[[22, 52, 59], [217, 189, 131]], [[42, 92, 120], [241, 200, 160]], [[18, 40, 60], [180, 138, 69]], [[70, 120, 150], [236, 222, 190]], [[30, 70, 80], [250, 210, 170]]];
    [$top, $bot] = $skies[$seed % count($skies)];
    $horizon = (int) ($h * 0.62);
    for ($y = 0; $y < $horizon; $y++) {
        $t = $y / $horizon;
        $c = imagecolorallocate($im, (int) ($top[0] + ($bot[0] - $top[0]) * $t), (int) ($top[1] + ($bot[1] - $top[1]) * $t), (int) ($top[2] + ($bot[2] - $top[2]) * $t));
        imageline($im, 0, $y, $w, $y, $c);
    }
    $sun = imagecolorallocatealpha($im, 255, 236, 200, 40);
    imagefilledellipse($im, 300 + ($seed * 137) % 1000, 260 + ($seed * 53) % 120, 170, 170, $sun);
    for ($y = $horizon; $y < $h; $y++) {
        $t = ($y - $horizon) / ($h - $horizon);
        $c = imagecolorallocate($im, (int) (24 + 10 * $t), (int) (86 - 30 * $t), (int) (104 - 30 * $t));
        imageline($im, 0, $y, $w, $y, $c);
    }
    $hill = imagecolorallocate($im, 20, 44, 46);
    $pts = [0, $horizon];
    for ($x = 0; $x <= $w; $x += 80) {
        $pts[] = $x;
        $pts[] = (int) ($horizon - 120 - 70 * sin(($x + $seed * 90) / 260));
    }
    $pts[] = $w;
    $pts[] = $horizon;
    imagefilledpolygon($im, $pts, $hill);
    $wall = imagecolorallocate($im, 246, 241, 230);
    $roof = imagecolorallocate($im, 180, 138, 69);
    $win = imagecolorallocate($im, 22, 52, 59);
    $cx = 560 + ($seed * 71) % 420;
    $base = $horizon + 90;
    if ($ptype === 'arsa') {
        $grass = imagecolorallocate($im, 120, 140, 80);
        imagefilledrectangle($im, 0, $horizon + 40, $w, $h, $grass);
    } elseif ($ptype === 'proje') {
        // Çok katlı bina
        $floors = 5 + $seed % 3;
        $bw = 560;
        $top = $base - $floors * 62;
        imagefilledrectangle($im, $cx - $bw / 2, $top, $cx + $bw / 2, $base, $wall);
        imagefilledrectangle($im, $cx - $bw / 2 - 10, $top - 14, $cx + $bw / 2 + 10, $top, $roof);
        for ($f = 0; $f < $floors; $f++) {
            for ($k = 0; $k < 6; $k++) {
                $x = (int) ($cx - $bw / 2 + 30 + $k * 88);
                $y = $top + 16 + $f * 62;
                imagefilledrectangle($im, $x, $y, $x + 56, $y + 34, $win);
            }
        }
    } else {
        imagefilledrectangle($im, $cx - 230, $base - 230, $cx + 230, $base, $wall);
        imagefilledpolygon($im, [$cx - 270, $base - 230, $cx, $base - 380, $cx + 270, $base - 230], $roof);
        foreach ([-150, -10, 130] as $dx) {
            imagefilledrectangle($im, $cx + $dx, $base - 180, $cx + $dx + 70, $base - 100, $win);
        }
        imagefilledrectangle($im, $cx - 35, $base - 90, $cx + 35, $base, $win);
    }
    imagejpeg($im, $file, 88);
    imagedestroy($im);
}
