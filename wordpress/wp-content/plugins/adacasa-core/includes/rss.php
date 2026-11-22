<?php
/**
 * RSS akışı (/feed/) için paylaşım görseli (1.1.9).
 * Her blog yazısına öne çıkan görselin Instagram'a uygun JPEG kopyası <enclosure> ve <media:content> olarak eklenir.
 * n8n'deki RSS paylaşım akışı görseli buradan alır (enclosure.url). Kopya: uploads/sosyal/{yazı}-{görsel}.jpg
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Öne çıkan görselin 1440 px'i geçmeyen, oranı 4:5 – 1.91:1 arasında JPEG kopyası. @return array{url:string,size:int}|null */
function ec_rss_gorsel(int $post_id): ?array
{
    $mid = (int) get_post_thumbnail_id($post_id);
    if (!$mid) {
        return null;
    }
    $src = get_attached_file($mid);
    if (!$src || !file_exists($src)) {
        return null;
    }
    $up = wp_upload_dir();
    $dir = trailingslashit($up['basedir']) . 'sosyal';
    $ad = $post_id . '-' . $mid . '-' . substr(md5($src . filemtime($src)), 0, 6) . '.jpg';
    $dst = $dir . '/' . $ad;
    if (!file_exists($dst)) {
        wp_mkdir_p($dir);
        $ed = wp_get_image_editor($src);
        if (is_wp_error($ed)) {
            return null;
        }
        $s = $ed->get_size();
        $w = (int) $s['width'];
        $h = (int) $s['height'];
        if ($w < 2 || $h < 2) {
            return null;
        }
        $r = $w / $h;
        [$cx, $cy, $cw, $ch] = [0, 0, $w, $h];
        if ($r > 1.91) {
            $cw = (int) round($h * 1.91);
            $cx = (int) (($w - $cw) / 2);
        } elseif ($r < 0.8) {
            $ch = (int) round($w / 0.8);
            $cy = (int) (($h - $ch) / 2);
        }
        $k = min(1, 1440 / $cw);
        $ed->crop($cx, $cy, $cw, $ch, (int) round($cw * $k), (int) round($ch * $k));
        $ed->set_quality(86);
        $sonuc = $ed->save($dst, 'image/jpeg');
        if (is_wp_error($sonuc)) {
            return null;
        }
    }
    return ['url' => trailingslashit($up['baseurl']) . 'sosyal/' . $ad, 'size' => (int) filesize($dst)];
}

add_action('rss2_ns', function () {
    echo 'xmlns:media="http://search.yahoo.com/mrss/"' . "\n";
});

add_action('rss2_item', function () {
    $g = ec_rss_gorsel((int) get_the_ID());
    if (!$g) {
        return;
    }
    printf("\t\t<enclosure url=\"%s\" length=\"%d\" type=\"image/jpeg\" />\n", esc_url($g['url']), $g['size']);
    printf("\t\t<media:content url=\"%s\" medium=\"image\" type=\"image/jpeg\" />\n", esc_url($g['url']));
});
