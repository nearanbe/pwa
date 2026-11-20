<?php
/**
 * WP-CLI komutları:
 *   wp emlak seed      Demo danışman, ilan, proje ve örnek görseller ekler (kurgusal veri)
 *   wp emlak unseed    Demo verileri siler
 *   wp emlak key <no>  Danışman için yeni API anahtarı üretir
 */

if (!defined('ABSPATH')) {
    exit;
}

class EC_CLI
{
    /** Demo verisi ekler. */
    public function seed(): void
    {
        global $wpdb;
        $agents = [
            ['Deniz Yıldız', 'Mavi Ada Emlak', 'demo1@example.com', '905330000001', 'girne'],
            ['Kerem Aksoy', 'Zeytin Property', 'demo2@example.com', '905330000002', 'lefkosa'],
            ['Selin Arda', 'Liman Gayrimenkul', 'demo3@example.com', '905330000003', 'gazimagusa'],
            ['Mert Kaya', 'Kuzey Yapı', 'demo4@example.com', '905330000004', 'iskele'],
        ];
        $agentIds = [];
        foreach ($agents as $a) {
            $wpdb->insert(ec_table('agents'), [
                'kind' => 'kurumsal', 'company_type' => 'Emlak Acentesi', 'name' => $a[0], 'office' => $a[1],
                'email' => $a[2], 'phone' => '+' . $a[3], 'whatsapp' => $a[3], 'city' => $a[4],
                'status' => 'active', 'created_at' => ec_now(), 'updated_at' => ec_now(),
            ]);
            $agentIds[] = (int) $wpdb->insert_id;
        }

        // mode, ptype, subtype, city, district, price, currency, m2, rooms, baths, deed, furnished, featured, agent, [tr, en, ru]
        $L = [
            ['sale', 'konut', 'daire', 'girne', 'Merkez', 139000, 'GBP', 115, '3+1', 2, 'turk-kocani', 0, 1, 0, ['Merkezde deniz manzaralı 3+1 daire', 'Sea view 3-bedroom apartment in the centre', 'Квартира 3+1 с видом на море в центре']],
            ['sale', 'konut', 'villa', 'girne', 'Lapta', 385000, 'GBP', 240, '4+1', 3, 'turk-kocani', 0, 1, 1, ['Havuzlu müstakil villa', 'Detached villa with private pool', 'Отдельная вилла с бассейном']],
            ['sale', 'konut', 'penthouse', 'lefkosa', 'Gönyeli', 138900, 'GBP', 150, '3+1', 2, 'esdeger', 0, 1, 2, ['Şehir manzaralı penthouse', 'Penthouse with city views', 'Пентхаус с видом на город']],
            ['sale', 'arsa', 'konut-imarli', 'iskele', 'Bafra', 95000, 'GBP', 650, null, null, 'turk-kocani', null, 0, 3, ['Denize yakın imarlı arsa', 'Building plot close to the sea', 'Участок под застройку рядом с морем']],
            ['sale', 'konut', 'daire', 'gazimagusa', 'Mormenekşe', 72000, 'GBP', 85, '2+1', 1, 'proje', 0, 0, 0, ['Üniversiteye yakın yatırımlık daire', 'Investment flat near the university', 'Инвестиционная квартира у университета']],
            ['sale', 'ticari', 'dukkan', 'girne', 'Merkez', 210000, 'GBP', 90, null, 1, 'turk-kocani', null, 0, 1, ['Ana cadde üzerinde dükkan', 'Shop on the main street', 'Магазин на главной улице']],
            ['sale', 'konut', 'daire', 'iskele', 'Long Beach', 119000, 'GBP', 78, '1+1', 1, 'proje', 1, 1, 3, ['Long Beach’te sitede eşyalı 1+1', 'Furnished 1-bed in a Long Beach complex', 'Меблированная 1+1 в комплексе на Лонг-Бич']],
            ['sale', 'konut', 'ikiz', 'girne', 'Çatalköy', 255000, 'GBP', 190, '3+1', 3, 'turk-kocani', 0, 0, 0, ['Dağ ve deniz manzaralı ikiz villa', 'Semi-detached villa with mountain and sea views', 'Таунхаус с видом на горы и море']],
            ['rent', 'konut', 'daire', 'girne', 'Kaşgar', 650, 'GBP', 90, '2+1', 1, null, 1, 0, 2, ['Kaşgar’da eşyalı kiralık 2+1', 'Furnished 2-bed for rent in Kaşgar', 'Сдаётся меблированная 2+1 в Кашгаре']],
            ['rent', 'konut', 'villa', 'iskele', 'Long Beach', 1800, 'GBP', 200, '3+1', 2, null, 1, 1, 3, ['Deniz kenarında kiralık villa', 'Beachfront villa for rent', 'Вилла у моря в аренду']],
            ['rent', 'konut', 'daire', 'lefkosa', 'Hamitköy', 520, 'GBP', 70, '1+1', 1, null, 1, 0, 1, ['Eşyalı kiralık 1+1', 'Furnished 1-bed for rent', 'Сдаётся меблированная 1+1']],
            ['rent', 'konut', 'studyo', 'gazimagusa', 'Sakarya', 380, 'GBP', 45, '1+0', 1, null, 1, 0, 2, ['Öğrenciye uygun stüdyo daire', 'Student-friendly studio', 'Студия для студентов']],
            ['daily', 'konut', 'bungalow', 'girne', 'Alsancak', 85, 'GBP', 60, '1+1', 1, null, 1, 1, 0, ['Günlük kiralık bungalow', 'Bungalow for holiday rental', 'Бунгало посуточно']],
            ['daily', 'konut', 'villa', 'guzelyurt', 'Merkez', 160, 'GBP', 180, '3+1', 2, null, 1, 0, 1, ['Portakal bahçesinde tatil villası', 'Holiday villa in an orange grove', 'Вилла для отдыха в апельсиновом саду']],
        ];
        $descTpl = [
            'tr' => '%s. %s bölgesinde, %s m² kullanım alanı. Ulaşım, market ve sahile yakın. Detaylı bilgi ve randevu için danışmanımızla iletişime geçin.',
            'en' => '%s. Located in %s with %s m² of living space. Close to transport, shops and the beach. Contact our agent for details and viewings.',
            'ru' => '%s. Район %s, площадь %s м². Рядом транспорт, магазины и пляж. Свяжитесь с нашим агентом для подробностей и просмотра.',
        ];
        $tmpDir = get_temp_dir();
        $n = 0;
        foreach ($L as $i => $x) {
            $i18n = [];
            foreach (['tr', 'en', 'ru'] as $k => $lang) {
                $i18n[$lang] = ['title' => $x[14][$k], 'description' => sprintf($descTpl[$lang], $x[14][$k], $x[4], $x[7] ?? '—')];
            }
            $id = ec_listing_create([
                'mode' => $x[0], 'ptype' => $x[1], 'subtype' => $x[2], 'city' => $x[3], 'district' => $x[4],
                'price' => $x[5], 'currency' => $x[6], 'area_m2' => $x[7], 'rooms' => $x[8], 'baths' => $x[9],
                'deed_type' => $x[10], 'furnished' => $x[11], 'status' => 'published', 'i18n' => $i18n,
            ], $agentIds[$x[13]], 'demo');
            if (is_wp_error($id)) {
                WP_CLI::warning('İlan eklenemedi: ' . wp_json_encode($id->get_error_data()));
                continue;
            }
            if ($x[12]) {
                $wpdb->update(ec_table('listings'), ['featured_until' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS)], ['id' => $id]);
            }
            for ($p = 0; $p < 3; $p++) {
                $f = $tmpDir . 'ec-demo-' . $id . '-' . $p . '.jpg';
                self::demo_image($f, $i * 3 + $p, $x[1]);
                ec_photo_add($id, $f);
                @unlink($f);
            }
            $n++;
        }

        $P = [
            ['Mavi Sahil Residence', 'Mavi Ada İnşaat', 'girne', 'Alsancak', 48, '2027-12-01', 120000, 320000, '%35 peşin, 36 ay taksit'],
            ['Zeytin Park', 'Zeytin Yapı', 'gazimagusa', 'Mormenekşe', 32, '2027-06-01', 65000, 140000, '%30 peşin, 24 ay taksit'],
            ['Liman Towers', 'Liman İnşaat', 'iskele', 'Long Beach', 164, '2028-03-01', 105000, 290000, '%40 peşin, 48 ay taksit'],
            ['Kuzey Vadi Evleri', 'Kuzey Yapı', 'lefkosa', 'Gönyeli', 24, '2027-09-01', 98000, 175000, '%50 peşin, 18 ay taksit'],
        ];
        foreach ($P as $k => $p) {
            $wpdb->insert(ec_table('projects'), [
                'agent_id' => $agentIds[$k], 'name' => $p[0], 'developer' => $p[1], 'city' => $p[2], 'district' => $p[3],
                'units' => $p[4], 'delivery_date' => $p[5], 'price_min_gbp' => $p[6], 'price_max_gbp' => $p[7],
                'payment_plan' => $p[8], 'created_at' => ec_now(), 'updated_at' => ec_now(),
            ]);
        }
        update_option('ec_demo_agents', $agentIds);
        WP_CLI::success("$n demo ilan, " . count($agentIds) . ' danışman ve ' . count($P) . ' proje eklendi.');
    }

    /** Demo verilerini siler. */
    public function unseed(): void
    {
        global $wpdb;
        $ids = $wpdb->get_col('SELECT id FROM ' . ec_table('listings') . " WHERE source = 'demo'");
        foreach ($ids as $id) {
            foreach ($wpdb->get_col($wpdb->prepare('SELECT id FROM ' . ec_table('photos') . ' WHERE listing_id = %d', $id)) as $pid) {
                ec_photo_delete((int) $id, (int) $pid);
            }
            @rmdir(ec_photo_base()['dir'] . '/' . $id);
            $wpdb->delete(ec_table('listing_i18n'), ['listing_id' => $id]);
            $wpdb->delete(ec_table('listings'), ['id' => $id]);
        }
        $agents = (array) get_option('ec_demo_agents', []);
        foreach ($agents as $a) {
            $wpdb->delete(ec_table('projects'), ['agent_id' => $a]);
            $wpdb->delete(ec_table('agents'), ['id' => $a]);
        }
        delete_option('ec_demo_agents');
        WP_CLI::success(count($ids) . ' demo ilan silindi.');
    }

    /**
     * Danışman için yeni API anahtarı üretir.
     * ## OPTIONS
     * <agent_id>
     * : Danışman numarası
     */
    public function key(array $args): void
    {
        $a = ec_agent((int) $args[0]);
        if (!$a) {
            WP_CLI::error('Danışman bulunamadı');
        }
        WP_CLI::line(ec_agent_issue_key((int) $a['id']));
    }

    /** Fotoğraf yerine kullanılan sade, kurgusal manzara görseli üretir. */
    private static function demo_image(string $file, int $seed, string $ptype): void
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
}

WP_CLI::add_command('emlak', 'EC_CLI');
