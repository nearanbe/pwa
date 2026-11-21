<?php
/** AdaCasa Core eklentisi kapalıyken gösterilen sade sayfa (eklenti fonksiyonu kullanmaz). */
if (!defined('ABSPATH')) {
    exit;
}
status_header(503);
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html(get_bloginfo('name')); ?></title>
<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8f9fb;color:#142036;font-family:system-ui,sans-serif;text-align:center;padding:16px}h1{font-family:Georgia,serif;font-weight:500}</style>
</head><body><div><h1><?php echo esc_html(get_bloginfo('name')); ?></h1><p>Sitemiz kısa bir bakım çalışmasındadır. Lütfen biraz sonra tekrar deneyin.</p></div></body></html>
