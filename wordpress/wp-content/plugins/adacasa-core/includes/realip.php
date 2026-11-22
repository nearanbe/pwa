<?php
/**
 * Cloudflare arkasında gerçek ziyaretçi IP'si.
 * CF-Connecting-IP başlığına yalnızca istek gerçekten Cloudflare sunucularından geliyorsa güvenilir; aksi halde
 * başlık yok sayılır (sahte IP gönderilemez). Cloudflare kullanılmıyorsa hiçbir şey değişmez.
 * Erken yüklenir: IP başına sınırlar, formlar ve WordPress yorumları gerçek IP'yi görür.
 */
if (!defined('ABSPATH')) {
    exit;
}
(function () {
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $cf = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
    if ($remote === '' || $cf === '' || !filter_var($cf, FILTER_VALIDATE_IP)) {
        return;
    }
    $ranges = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
        '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];
    $a = @inet_pton($remote);
    if ($a === false) {
        return;
    }
    foreach ($ranges as $cidr) {
        [$net, $bits] = explode('/', $cidr);
        $b = @inet_pton($net);
        if ($b === false || strlen($a) !== strlen($b)) {
            continue;
        }
        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $rem = $bits % 8;
        if ($bytes && substr($a, 0, $bytes) !== substr($b, 0, $bytes)) {
            continue;
        }
        if ($rem) {
            $m = (0xFF << (8 - $rem)) & 0xFF;
            if ((ord($a[$bytes]) & $m) !== (ord($b[$bytes]) & $m)) {
                continue;
            }
        }
        $_SERVER['CF_PROXY_ADDR'] = $remote;
        $_SERVER['REMOTE_ADDR'] = $cf;
        return;
    }
})();
