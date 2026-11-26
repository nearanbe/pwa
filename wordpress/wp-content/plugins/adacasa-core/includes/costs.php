<?php
/**
 * Toplam maliyet hesaplayıcı kuralları (1.4). Kaynaklar ve güven düzeyi: docs/maliyet-arastirmasi.md
 *
 * Tapu devir harcı tarihe bağlıdır:
 *  - 07.10.2026 – 05.01.2027 (dahil): geçici indirim (Resmî Gazete 2026/196–197; Haber Kıbrıs, Özyalçın, Satış Ofisi).
 *  - Diğer tarihlerde: 15.05.2025 tüzüğü (Resmî Gazete, KTTO yayını).
 * KDV: 2025 KDV Oranları Tüzüğü — yeni konut (KDV mükellefi satıcı) ≤300 m² %5, ≥300 m² %10.
 * Pul: Pul Yasası 19/1963 (2/2026 dahil) — ilk 89.000.000 TL için binde 5, üstü binde 1.
 * Avukat, trafo, abonelik, izin harcı: piyasa aralıkları (düşük/orta güven), aralık olarak gösterilir.
 * Oranlar değişirse yalnızca bu dosya (ya da `ec_cost_rules` filtresi) güncellenir.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ec_cost_rules(?int $ts = null): array
{
    $ts = $ts ?? time();
    $d = wp_date('Y-m-d', $ts);
    $promo = $d >= '2026-10-07' && $d <= '2027-01-05';
    $r = [
        'as_of' => '2026-11-26',
        'promo' => $promo,
        'promo_until' => $promo ? '2027-01-05' : null,
        // Tapu harcı (%), kaçıncı taşınmaz → oran. Son eleman sonraki alımlar için de geçerli.
        'deed' => $promo ? [
            'kktc' => [2, 4],          // bir defaya mahsus hak %2, sonra %4
            'tr' => [4, 4, 5.5, 5.5, 6, 6],
            'foreign' => [4, 5.5, 6],
        ] : [
            'kktc' => [3, 6],
            'tr' => [6, 8, 9, 9, 9, 9],
            'foreign' => [9, 9, 9],
        ],
        'vat_small' => 5,      // yeni konut, kapalı alan < 300 m²
        'vat_large' => 10,     // ≥ 300 m²
        'vat_limit_m2' => 300,
        'stamp' => 0.5,        // %, ilk 89 milyon TL
        'stamp_high' => 0.1,   // %, 89 milyon TL üstü
        'stamp_limit_try' => 89000000,
        'lawyer_gbp' => [1500, 2500],
        'lawyer_vat' => 16,
        'trafo_gbp' => [1500, 3000],   // yalnız yeni projeler
        'utilities_gbp' => [350, 500], // su + elektrik aboneliği (yaklaşık)
        'permit_try' => 35450,          // yabancı izin başvuru harcı (yaklaşık, brüt asgari ücretin yarısı)
    ];
    return apply_filters('ec_cost_rules', $r, $ts);
}
