<?php
/**
 * Brevo (eski Sendinblue) HTTP API ile e-posta gönderimi.
 *
 * Paylaşımlı sunucular giden SMTP portlarını (587/465/2525) engelleyebilir
 * ya da şeffaf biçimde kendi sunucularına yönlendirebilir; bu durumda SMTP
 * relay hiçbir zaman çalışmaz ("kimlik doğrulanamadı"). Bu modül, e-postayı
 * Brevo'nun HTTPS API'si (port 443) üzerinden gönderir — bu port her zaman
 * açıktır, dolayısıyla SMTP engelleri bu yolu etkilemez.
 *
 * Ayarlar: Randevular › E-posta (SMTP) › Gönderim yöntemi: "Brevo API".
 *
 * @package feriha-randevu
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Ad <e-posta>" ya da düz "e-posta" biçimini ayrıştırır.
 *
 * @param string $value Adres satırı.
 * @return array{email:string,name:string}|null
 */
function fr_brevo_parse_address( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return null;
	}
	if ( preg_match( '/^\s*"?([^"<]*)"?\s*<\s*([^>]+)\s*>\s*$/', $value, $m ) ) {
		$email = sanitize_email( trim( $m[2] ) );
		$name  = trim( $m[1] );
	} else {
		$email = sanitize_email( $value );
		$name  = '';
	}
	return is_email( $email ) ? array(
		'email' => $email,
		'name'  => $name,
	) : null;
}

/**
 * wp_mail başlıklarını (string ya da dizi) satır dizisine çevirir.
 *
 * @param string|array $headers Başlıklar.
 * @return array
 */
function fr_brevo_header_lines( $headers ) {
	if ( empty( $headers ) ) {
		return array();
	}
	if ( is_array( $headers ) ) {
		return $headers;
	}
	return preg_split( "/\r\n|\r|\n/", (string) $headers );
}

/**
 * wp_mail'i kısa devre yaparak Brevo API ile gönderir.
 *
 * @param null|bool $short  Kısa devre değeri.
 * @param array     $atts   wp_mail argümanları (to, subject, message, headers, attachments).
 * @return null|bool null → normal wp_mail devam eder; bool → sonuç.
 */
function fr_brevo_pre_wp_mail( $short, $atts ) {
	$s = fr_smtp();
	if ( ! $s['enabled'] || 'brevo_api' !== $s['method'] || '' === trim( (string) $s['brevo_api_key'] ) ) {
		return $short; // Bu yöntem kapalı: WordPress'in normal gönderimine dokunma.
	}

	$to          = isset( $atts['to'] ) ? $atts['to'] : array();
	$subject     = isset( $atts['subject'] ) ? (string) $atts['subject'] : '';
	$message     = isset( $atts['message'] ) ? (string) $atts['message'] : '';
	$headers     = fr_brevo_header_lines( isset( $atts['headers'] ) ? $atts['headers'] : array() );
	$attachments = isset( $atts['attachments'] ) ? $atts['attachments'] : array();

	// Alıcılar.
	$to_list = array();
	foreach ( (array) $to as $one ) {
		foreach ( explode( ',', (string) $one ) as $piece ) {
			$addr = fr_brevo_parse_address( $piece );
			if ( $addr ) {
				$to_list[] = $addr;
			}
		}
	}
	if ( ! $to_list ) {
		return false;
	}

	// Başlıklardan From / Reply-To / içerik türü.
	$from       = null;
	$reply_to   = null;
	$is_html    = false;
	foreach ( $headers as $line ) {
		if ( ! is_string( $line ) || false === strpos( $line, ':' ) ) {
			continue;
		}
		list( $key, $val ) = explode( ':', $line, 2 );
		$key = strtolower( trim( $key ) );
		$val = trim( $val );
		if ( 'from' === $key ) {
			$from = fr_brevo_parse_address( $val );
		} elseif ( 'reply-to' === $key ) {
			$reply_to = fr_brevo_parse_address( $val );
		} elseif ( 'content-type' === $key && false !== stripos( $val, 'text/html' ) ) {
			$is_html = true;
		}
	}

	// From belirtilmemişse ayarlardaki gönderen, yoksa site varsayılanı.
	if ( ! $from ) {
		$from_email = is_email( $s['from_email'] ) ? $s['from_email'] : get_option( 'admin_email' );
		$from_name  = '' !== $s['from_name'] ? $s['from_name'] : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$from       = array(
			'email' => $from_email,
			'name'  => $from_name,
		);
	} elseif ( '' === $from['name'] && '' !== $s['from_name'] ) {
		$from['name'] = $s['from_name'];
	}

	$sender = array( 'email' => $from['email'] );
	if ( '' !== $from['name'] ) {
		$sender['name'] = $from['name'];
	}

	$body = array(
		'sender'  => $sender,
		'to'      => $to_list,
		'subject' => $subject,
	);
	if ( $is_html ) {
		$body['htmlContent'] = $message;
	} else {
		$body['textContent'] = $message;
	}
	if ( $reply_to ) {
		$body['replyTo'] = array( 'email' => $reply_to['email'] );
		if ( '' !== $reply_to['name'] ) {
			$body['replyTo']['name'] = $reply_to['name'];
		}
	}

	// Ekler (.ics vb.).
	$files = array();
	foreach ( (array) $attachments as $path ) {
		if ( $path && is_readable( $path ) ) {
			$data = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
			if ( false !== $data ) {
				$files[] = array(
					'content' => base64_encode( $data ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'name'    => basename( $path ),
				);
			}
		}
	}
	if ( $files ) {
		$body['attachment'] = $files;
	}

	$resp = wp_remote_post(
		'https://api.brevo.com/v3/smtp/email',
		array(
			'timeout' => 20,
			'headers' => array(
				'api-key'      => trim( (string) $s['brevo_api_key'] ),
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $resp ) ) {
		set_transient( 'fr_smtp_test_error', 'Brevo API bağlantı hatası: ' . $resp->get_error_message(), 60 );
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code( $resp );
	if ( $code >= 200 && $code < 300 ) {
		return true;
	}

	// Hata mesajını yakala (test ekranında gösterilir).
	$raw = wp_remote_retrieve_body( $resp );
	$msg = $raw;
	$dec = json_decode( $raw, true );
	if ( is_array( $dec ) && isset( $dec['message'] ) ) {
		$msg = $dec['message'];
	}
	set_transient( 'fr_smtp_test_error', 'Brevo API hatası (HTTP ' . $code . '): ' . mb_substr( (string) $msg, 0, 300 ), 60 );
	return false;
}
add_filter( 'pre_wp_mail', 'fr_brevo_pre_wp_mail', 10, 2 );
