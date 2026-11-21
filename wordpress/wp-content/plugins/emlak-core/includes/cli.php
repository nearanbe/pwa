<?php
/**
 * WP-CLI komutları:
 *   wp emlak seed      Demo veriyi ekler (kurgusal ilan, danışman, proje, blog, endeks geçmişi)
 *   wp emlak unseed    Demo veriyi siler
 *   wp emlak key <no>  Danışman için yeni API anahtarı üretir
 *   wp emlak rates     Döviz kurlarını şimdi günceller
 *   wp emlak index     Bu ayın fiyat endeksini şimdi hesaplar
 */

if (!defined('ABSPATH')) {
    exit;
}

class EC_CLI
{
    /** Demo verisi ekler. */
    public function seed(): void
    {
        $r = ec_demo_seed();
        is_wp_error($r) ? WP_CLI::error($r->get_error_message()) : WP_CLI::success($r);
    }

    /** Demo verisini siler. */
    public function unseed(): void
    {
        WP_CLI::success(ec_demo_unseed());
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

    /** Döviz kurlarını şimdi günceller. */
    public function rates(): void
    {
        $r = ec_rates_update();
        is_wp_error($r) ? WP_CLI::error($r->get_error_message()) : WP_CLI::success(wp_json_encode($r));
    }

    /** Bu ayın fiyat endeksini hesaplar. */
    public function index(): void
    {
        WP_CLI::success(ec_index_compute() . ' endeks satırı hesaplandı.');
    }
}

WP_CLI::add_command('emlak', 'EC_CLI');
