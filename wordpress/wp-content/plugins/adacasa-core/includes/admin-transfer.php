<?php
/**
 * Emlak → Portföy aktarımı (1.3): ofisten ayrılan danışmanın kilitli ilanları.
 *
 * Danışman ofisten ayrılınca ekipteyken açtığı ilanlar taslağa alınıp kilitlenir (team.php ec_team_hold_portfolio).
 * Ofis yöneticisi site yönetimine başvurup ilanların kime geçeceğini bildirir; yönetici burada:
 *   · "Aktar": ilanlar seçilen kullanıcıya (ofis hesabı ya da ofisin şu anki ekibinden biri) geçer, önceki durumuna döner;
 *   · "Danışmana bırak": kilit kalkar, ilanlar danışmanda taslak olarak kalır.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    global $wpdb;
    $n = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . ec_table('listings') . ' WHERE hold_office_id IS NOT NULL');
    $label = 'Portföy aktarımı' . ($n ? ' <span class="awaiting-mod">' . $n . '</span>' : '');
    add_submenu_page('ec-listings', 'Portföy aktarımı', $label, 'manage_options', 'ec-transfer', 'ec_admin_transfer');
}, 21);

add_action('admin_post_ec_transfer', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkiniz yok');
    }
    check_admin_referer('ec_transfer');
    $office = (int) ($_POST['office'] ?? 0);
    $from = (int) ($_POST['from'] ?? 0);
    $keep = !empty($_POST['keep']);
    $to = $keep ? 0 : (int) ($_POST['to'] ?? 0);
    $only = array_map('intval', (array) ($_POST['ids'] ?? []));
    $msg = 'hata';
    $o = ec_agent($office);
    $f = ec_agent($from);
    $valid = $o && $f && ($keep || in_array($to, ec_transfer_targets($office), true));
    if ($valid && $only) {
        $n = ec_team_transfer_portfolio($office, $from, $to, $only);
        if ($n) {
            if ($to) {
                $t = ec_agent($to);
                ec_notify_member($t, 'Size ' . $n . ' ilan aktarıldı', "{$o['name']} portföyünden {$n} ilan hesabınıza aktarıldı (önceki sahibi: {$f['name']}). İlanlar Panelim → İlanlarım'da.\n" . ec_url_section('panel', ec_member_lang((int) $t['wp_user_id'])));
                ec_notify_member($f, 'Ofis portföyü aktarıldı', "{$o['name']} ofisinin başvurusu üzerine, ekipteyken açtığınız {$n} ilan ofisin bildirdiği kullanıcıya aktarıldı.");
                if ((int) $to !== $office) {
                    ec_notify_member($o, 'Portföy aktarımı tamamlandı', "{$f['name']} adlı danışmanın {$n} ilanı {$t['name']} hesabına aktarıldı.");
                }
                $msg = 'aktarildi';
            } else {
                ec_notify_member($f, 'İlanlarınızın kilidi kaldırıldı', "{$o['name']} ekibindeyken açtığınız {$n} ilanın kilidi kaldırıldı. İlanlar taslak olarak hesabınızda; dilediğinizde yayına gönderebilirsiniz.");
                $msg = 'birakildi';
            }
        }
    }
    wp_safe_redirect(admin_url('admin.php?page=ec-transfer&sonuc=' . $msg));
    exit;
});

/** Aktarılabilecek kullanıcılar: ofis hesabının kendisi ve şu anki ekibi @return int[] */
function ec_transfer_targets(int $office_id): array
{
    $ids = [$office_id];
    foreach (ec_team_members($office_id) as $m) {
        $ids[] = (int) $m['id'];
    }
    return $ids;
}

function ec_admin_transfer(): void
{
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ec_table('listings') . ' WHERE hold_office_id IS NOT NULL ORDER BY hold_office_id, agent_id, id', ARRAY_A);
    $groups = [];
    foreach ($rows as $r) {
        $groups[$r['hold_office_id'] . ':' . $r['agent_id']][] = $r;
    }
    $res = sanitize_key($_GET['sonuc'] ?? '');
    $st = ['draft' => 'Taslak', 'pending' => 'Onay bekliyordu', 'published' => 'Yayındaydı', 'rejected' => 'Reddedilmişti', 'sold' => 'Satıldı', 'passive' => 'Pasifti'];

    echo '<div class="wrap"><h1>Portföy aktarımı</h1>';
    if ($res === 'aktarildi') {
        echo '<div class="notice notice-success"><p>İlanlar aktarıldı ve önceki durumlarına döndü. İlgililere e-posta gitti.</p></div>';
    } elseif ($res === 'birakildi') {
        echo '<div class="notice notice-success"><p>Kilit kaldırıldı; ilanlar danışmanda taslak olarak kaldı.</p></div>';
    } elseif ($res === 'hata') {
        echo '<div class="notice notice-error"><p>İşlem yapılamadı. En az bir ilan ve geçerli bir kullanıcı seçin.</p></div>';
    }
    echo '<p>Bir danışman ofisten ayrıldığında (ya da çıkarıldığında), ekipteyken açtığı ilanlar ofisin portföyü sayılır: taslağa alınır ve kilitlenir. Danışman bu ilanları değiştiremez ve yayınlayamaz. Ofis size başvurup ilanların kime geçeceğini bildirince buradan aktarın.</p>';
    if (!$groups) {
        echo '<p><b>Bekleyen aktarım yok.</b></p></div>';
        return;
    }
    foreach ($groups as $key => $list) {
        [$oid, $aid] = array_map('intval', explode(':', $key));
        $o = ec_agent($oid);
        $a = ec_agent($aid);
        $items = array_column(ec_listings_present($list, 'tr'), null, 'id');
        echo '<div class="card" style="max-width:900px;margin:16px 0;padding:12px 16px">';
        echo '<h2 style="margin-top:4px">' . esc_html($o['name'] ?? ('Ofis #' . $oid)) . ' → ayrılan: ' . esc_html($a['name'] ?? ('#' . $aid)) . ' <span style="font-weight:normal">(' . count($list) . ' ilan)</span></h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('ec_transfer');
        echo '<input type="hidden" name="action" value="ec_transfer"><input type="hidden" name="office" value="' . $oid . '"><input type="hidden" name="from" value="' . $aid . '">';
        echo '<table class="widefat striped"><thead><tr><td style="width:28px"><input type="checkbox" checked onclick="this.closest(\'table\').querySelectorAll(\'tbody input\').forEach(c=>c.checked=this.checked)"></td><th>İlan</th><th>Ayrılmadan önce</th></tr></thead><tbody>';
        foreach ($list as $r) {
            $it = $items[$r['id']] ?? null;
            echo '<tr><td><input type="checkbox" name="ids[]" value="' . (int) $r['id'] . '" checked></td><td>#' . (int) $r['id'] . ' ' . esc_html($it['title'] ?? '') . '</td><td>' . esc_html($st[$r['hold_prev_status']] ?? (string) $r['hold_prev_status']) . '</td></tr>';
        }
        echo '</tbody></table><p>';
        echo '<label>Aktarılacak kullanıcı (ofisin bildirdiği): <select name="to"><option value="">— seçin —</option>';
        foreach (ec_transfer_targets($oid) as $tid) {
            $t = ec_agent($tid);
            if ($t) {
                echo '<option value="' . $tid . '">' . esc_html($t['name'] . ($tid === $oid ? ' (ofis hesabı)' : ' (ekipte)') . ' · ' . $t['email']) . '</option>';
            }
        }
        echo '</select></label> ';
        echo '<button class="button button-primary" onclick="if(!this.form.to.value){alert(\'Kullanıcı seçin\');return false}return confirm(\'Seçili ilanlar aktarılsın mı?\')">Aktar</button> ';
        echo '<button class="button" name="keep" value="1" onclick="return confirm(\'Kilit kaldırılsın ve ilanlar danışmanda taslak olarak kalsın mı?\')">Danışmana bırak</button>';
        echo '</p><p class="description">Listede olmayan biri bildirildiyse önce ofisin o kişiyi ekibine eklemesi gerekir.</p></form></div>';
    }
    echo '</div>';
}
