<?php
/**
 * Plugin Name: MC Lê Tam – Video trang chủ
 * Description: Đổi 5 video ở mục "Xem Lê Tam trên sân khấu" của trang chủ (1 video lớn, 4 video nhỏ). Menu: MC Lê Tam → Video trang chủ.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

function mclt_hv_yt_id($u) {
    $u = trim((string) $u);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $u)) return $u;
    if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $u, $m)) return $m[1];
    return '';
}
function mclt_hv_slots() { return ['Video lớn', 'Video nhỏ 1', 'Video nhỏ 2', 'Video nhỏ 3', 'Video nhỏ 4']; }

add_action('admin_menu', function () {
    $parent = menu_page_url('mclt', false) ? 'mclt' : null;
    global $menu;
    $has = false;
    foreach ((array) $menu as $m) if (isset($m[2]) && $m[2] === 'mclt') $has = true;
    $title = 'Video trang chủ';
    if ($has) add_submenu_page('mclt', $title, $title, 'manage_options', 'mclt-home-videos', 'mclt_hv_page', 98);
    else add_menu_page($title, $title, 'manage_options', 'mclt-home-videos', 'mclt_hv_page', 'dashicons-video-alt3', 27);
}, 99);

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'mclt-home-videos') !== false) wp_enqueue_media();
});

function mclt_hv_page() {
    if (!current_user_can('manage_options')) return;
    $notice = '';
    if (isset($_POST['mclt_hv_nonce']) && wp_verify_nonce($_POST['mclt_hv_nonce'], 'mclt_hv_save')) {
        if (isset($_POST['reset'])) {
            delete_option('mclt_home_videos');
            $notice = 'Đã khôi phục 5 video ban đầu.';
        } else {
            $out = [];
            foreach (mclt_hv_slots() as $i => $_) {
                $url = sanitize_text_field(wp_unslash($_POST['url'][$i] ?? ''));
                $yt = mclt_hv_yt_id($url);
                $tag = sanitize_text_field(wp_unslash($_POST['tag'][$i] ?? ''));
                $dur = sanitize_text_field(wp_unslash($_POST['dur'][$i] ?? ''));
                $title = sanitize_text_field(wp_unslash($_POST['title'][$i] ?? ''));
                $img = (int) ($_POST['img'][$i] ?? 0);
                if ($yt && $title === '') {
                    $r = wp_remote_get('https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $yt), ['timeout' => 8]);
                    if (!is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
                        $j = json_decode(wp_remote_retrieve_body($r), true);
                        if (!empty($j['title'])) $title = sanitize_text_field($j['title']);
                    }
                }
                $out[$i] = ['url' => $url, 'yt' => $yt, 'img' => $img, 'tag' => $tag, 'dur' => $dur, 'title' => $title];
            }
            update_option('mclt_home_videos', $out);
            $notice = 'Đã lưu. Mở trang chủ và bấm Ctrl + Shift + R để xem video mới.';
        }
        do_action('litespeed_purge_all');
    }
    $opt = get_option('mclt_home_videos', []);
    echo '<div class="wrap"><h1>Video trang chủ</h1>';
    if ($notice) echo '<div class="notice notice-success"><p>' . esc_html($notice) . '</p></div>';
    echo '<p>Đổi 5 video ở mục <b>Xem Lê Tam trên sân khấu</b> của trang chủ. Ô nào để trống thì giữ nguyên video hiện tại.</p>';
    echo '<form method="post">';
    wp_nonce_field('mclt_hv_save', 'mclt_hv_nonce');
    foreach (mclt_hv_slots() as $i => $label) {
        $o = $opt[$i] ?? ['url' => '', 'img' => 0, 'tag' => '', 'dur' => '', 'title' => ''];
        $thumb = $o['img'] ? wp_get_attachment_image_url($o['img'], 'thumbnail') : '';
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 20px;margin:14px 0;max-width:820px">';
        echo '<h2 style="margin-top:0">' . esc_html($label) . '</h2><table class="form-table" role="presentation" style="margin:0">';
        echo '<tr><th>Link YouTube</th><td><input type="text" class="large-text" name="url[' . $i . ']" value="' . esc_attr($o['url']) . '" placeholder="https://www.youtube.com/watch?v=..."></td></tr>';
        echo '<tr><th>Ảnh bìa</th><td><input type="hidden" name="img[' . $i . ']" id="hv-img-' . $i . '" value="' . (int) $o['img'] . '"><img id="hv-pv-' . $i . '" src="' . esc_url($thumb) . '" style="max-width:120px;display:' . ($thumb ? 'block' : 'none') . ';margin-bottom:8px;border-radius:6px"><button type="button" class="button hv-pick" data-i="' . $i . '">Chọn ảnh</button> <button type="button" class="button-link hv-clr" data-i="' . $i . '">Bỏ ảnh</button><p class="description">Để trống thì tự lấy ảnh bìa trên YouTube.</p></td></tr>';
        echo '<tr><th>Nhãn</th><td><input type="text" name="tag[' . $i . ']" value="' . esc_attr($o['tag']) . '" placeholder="Ví dụ: Tiệc cưới, Sự kiện, Livestream"></td></tr>';
        echo '<tr><th>Thời lượng</th><td><input type="text" name="dur[' . $i . ']" value="' . esc_attr($o['dur']) . '" placeholder="Ví dụ: 4:48" style="width:100px"></td></tr>';
        echo '<tr><th>Tiêu đề</th><td><input type="text" class="large-text" name="title[' . $i . ']" value="' . esc_attr($o['title']) . '"><p class="description">Để trống thì tự lấy tên video trên YouTube.</p></td></tr>';
        echo '</table></div>';
    }
    echo '<p><button type="submit" class="button button-primary button-large">Lưu</button> ';
    echo '<button type="submit" name="reset" value="1" class="button" onclick="return confirm(\'Khôi phục 5 video ban đầu?\')">Khôi phục video ban đầu</button></p></form></div>';
    ?>
<script>
jQuery(function($){
  $('.hv-pick').on('click',function(){var i=$(this).data('i');var f=wp.media({title:'Chọn ảnh bìa',multiple:false,library:{type:'image'}});f.on('select',function(){var a=f.state().get('selection').first().toJSON();$('#hv-img-'+i).val(a.id);$('#hv-pv-'+i).attr('src',(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url)).show();});f.open();});
  $('.hv-clr').on('click',function(){var i=$(this).data('i');$('#hv-img-'+i).val(0);$('#hv-pv-'+i).hide();});
});
</script>
    <?php
}

add_action('wp_footer', function () {
    if (!is_front_page()) return;
    $opt = get_option('mclt_home_videos', []);
    if (!$opt) return;
    $cfg = [];
    foreach ($opt as $i => $o) {
        if (empty($o['yt'])) { $cfg[$i] = null; continue; }
        $img = !empty($o['img']) ? wp_get_attachment_image_url((int) $o['img'], 'large') : '';
        $tag = trim(($o['tag'] ?? '') . (($o['tag'] ?? '') !== '' && ($o['dur'] ?? '') !== '' ? ' · ' : '') . ($o['dur'] ?? ''));
        $cfg[$i] = ['yt' => $o['yt'], 'img' => $img ?: '', 'tag' => $tag, 'title' => $o['title'] ?? ''];
    }
    ?>
<script id="mclt-hv-js">(function(){var C=<?php echo wp_json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
function go(){
  var els=document.querySelectorAll('.lt-vbig,.lt-vsmall');
  C.forEach(function(c,i){
    var el=els[i];if(!c||!el)return;
    (el.className.match(/lt-yt-[\w-]+/g)||[]).forEach(function(k){el.classList.remove(k)});
    el.classList.add('lt-yt-'+c.yt);
    var im=el.querySelector('img');
    if(im){
      im.removeAttribute('srcset');im.removeAttribute('sizes');im.removeAttribute('data-src');im.loading='eager';
      if(c.img){im.src=c.img;}
      else{var base='https://i.ytimg.com/vi/'+c.yt+'/';im.onload=function(){if(im.naturalWidth<=120&&im.src.indexOf('hqdefault')<0)im.src=base+'hqdefault.jpg';};im.onerror=function(){im.onerror=null;im.src=base+'hqdefault.jpg';};im.src=base+'maxresdefault.jpg';}
      im.alt=c.title||'';
    }
    var h=el.querySelector('h3.elementor-heading-title,h3');
    var t=[].slice.call(el.querySelectorAll('.elementor-heading-title')).filter(function(x){return x!==h&&x.tagName!=='H3'})[0];
    if(h&&c.title)h.textContent=c.title;
    if(t&&c.tag)t.textContent=c.tag;
  });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',go);else go();
window.addEventListener('load',function(){setTimeout(go,300)});
})();</script>
    <?php
}, 41);
