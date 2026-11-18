<?php
/**
 * Cloudflare arkasında gerçek ziyaretçi IP'si.
 * Yalnızca istek gerçekten Cloudflare sunucularından geliyorsa CF-Connecting-IP başlığına güvenilir;
 * aksi halde başlık yok sayılır (sahte IP gönderilemez). Cloudflare kullanılmıyorsa hiçbir şey değişmez.
 */
defined('ABSPATH') || exit;

function adv_cf_ranges() {
	return [
		'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
		'190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
		'104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
		'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
	];
}

function adv_ip_in_cidr($ip, $cidr) {
	[$net, $bits] = explode('/', $cidr);
	$a = @inet_pton($ip); $b = @inet_pton($net);
	if ($a === false || $b === false || strlen($a) !== strlen($b)) return false;
	$bits = (int) $bits; $bytes = intdiv($bits, 8); $rem = $bits % 8;
	if ($bytes && substr($a, 0, $bytes) !== substr($b, 0, $bytes)) return false;
	if ($rem) { $m = chr((0xFF << (8 - $rem)) & 0xFF); if ((ord($a[$bytes]) & ord($m)) !== (ord($b[$bytes]) & ord($m))) return false; }
	return true;
}

function adv_from_cloudflare($ip) {
	foreach (adv_cf_ranges() as $r) if (adv_ip_in_cidr($ip, $r)) return true;
	return false;
}

// Erken çalışır: WordPress'in tamamı (form, oturum açma kayıtları, eklentiler) gerçek IP'yi görür
(function () {
	$remote = $_SERVER['REMOTE_ADDR'] ?? '';
	$cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
	if ($remote && $cf && adv_from_cloudflare($remote) && filter_var($cf, FILTER_VALIDATE_IP)) {
		$_SERVER['ADV_PROXY_ADDR'] = $remote;
		$_SERVER['REMOTE_ADDR'] = $cf;
	}
	// Cloudflare https'i sunucuya http ile iletiyorsa (Flexible) WordPress yine https olduğunu bilsin
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' && isset($_SERVER['ADV_PROXY_ADDR'])) $_SERVER['HTTPS'] = 'on';
})();
