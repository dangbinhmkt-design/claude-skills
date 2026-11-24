<?php
/**
 * Plugin Name: MC Lê Tam – Clip nổi bật
 * Description: Cho phép đổi clip YouTube lớn (hero) ở trang Video: dán link, chọn ảnh bìa, sửa nhãn và tiêu đề.
 * Version: 1.0.0
 * Author: Domus Marketing
 */
if (!defined('ABSPATH')) exit;

const MCLT_VH_OPT = 'mclt_video_hero';

function mclt_vh_get() {
    $o = get_option(MCLT_VH_OPT, array());
    return wp_parse_args(is_array($o) ? $o : array(), array('url' => '', 'yt' => '', 'img' => 0, 'tag' => '', 'title' => ''));
}

function mclt_vh_yt_id($url) {
    $url = trim((string) $url);
    if ($url === '') return '';
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) return $url;
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];
    return '';
}

add_action('admin_menu', function () {
    add_submenu_page('mclt', 'Clip nổi bật trang Video', 'Clip nổi bật', 'manage_options', 'mclt-video-hero', 'mclt_vh_page');
}, 99);

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string) $hook, 'mclt-video-hero') === false) return;
    wp_enqueue_media();
});

function mclt_vh_purge() {
    do_action('litespeed_purge_all');
    if (function_exists('rocket_clean_domain')) rocket_clean_domain();
}

function mclt_vh_page() {
    if (!current_user_can('manage_options')) return;
    $msg = '';
    if (isset($_POST['mclt_vh_nonce']) && wp_verify_nonce($_POST['mclt_vh_nonce'], 'mclt_vh_save')) {
        if (!empty($_POST['reset'])) {
            delete_option(MCLT_VH_OPT);
            $msg = array('ok', 'Đã trả về clip gốc.');
        } else {
            $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
            $raw = isset($_POST['url']) ? sanitize_text_field(wp_unslash($_POST['url'])) : '';
            $yt  = mclt_vh_yt_id($url ?: $raw);
            if ($raw !== '' && $yt === '') {
                $msg = array('err', 'Link YouTube chưa đúng. Hãy dán link dạng youtube.com/watch?v=... hoặc youtu.be/...');
            } else {
                $o = array(
                    'url'   => $raw,
                    'yt'    => $yt,
                    'img'   => isset($_POST['img']) ? absint($_POST['img']) : 0,
                    'tag'   => isset($_POST['tag']) ? sanitize_text_field(wp_unslash($_POST['tag'])) : '',
                    'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
                );
                if ($yt && $o['title'] === '') {
                    $r = wp_remote_get('https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $yt), array('timeout' => 8));
                    if (!is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
                        $j = json_decode(wp_remote_retrieve_body($r), true);
                        if (!empty($j['title'])) $o['title'] = sanitize_text_field($j['title']);
                    }
                }
                update_option(MCLT_VH_OPT, $o, false);
                $msg = array('ok', 'Đã lưu. Mở trang Video và bấm Ctrl + Shift + R để xem clip mới.');
            }
        }
        mclt_vh_purge();
    }
    $o = mclt_vh_get();
    $thumb = $o['img'] ? wp_get_attachment_image_url($o['img'], 'medium') : '';
    ?>
    <div class="wrap" style="max-width:760px">
      <h1>Clip nổi bật trang Video</h1>
      <p>Đây là clip lớn ở đầu trang Video. Dán link YouTube mới rồi bấm Lưu. Để trống toàn bộ để dùng clip gốc.</p>
      <?php if ($msg) : ?><div class="notice notice-<?php echo $msg[0] === 'ok' ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html($msg[1]); ?></p></div><?php endif; ?>
      <form method="post">
        <?php wp_nonce_field('mclt_vh_save', 'mclt_vh_nonce'); ?>
        <table class="form-table" role="presentation">
          <tr><th><label for="mv-url">Link YouTube</label></th>
              <td><input type="text" id="mv-url" name="url" class="large-text" value="<?php echo esc_attr($o['url']); ?>" placeholder="https://www.youtube.com/watch?v=...">
              <p class="description">Chấp nhận link youtube.com/watch, youtu.be, shorts.</p></td></tr>
          <tr><th>Ảnh bìa</th>
              <td><input type="hidden" id="mv-img" name="img" value="<?php echo esc_attr($o['img']); ?>">
              <div id="mv-prev" style="margin-bottom:8px"><?php if ($thumb) echo '<img src="' . esc_url($thumb) . '" style="max-width:320px;border-radius:8px">'; ?></div>
              <button type="button" class="button" id="mv-pick">Chọn ảnh</button>
              <button type="button" class="button-link" id="mv-clear" style="margin-left:8px">Bỏ ảnh</button>
              <p class="description">Để trống thì tự lấy ảnh bìa của chính clip YouTube. Nên dùng ảnh ngang 16:9.</p></td></tr>
          <tr><th><label for="mv-tag">Nhãn nhỏ</label></th>
              <td><input type="text" id="mv-tag" name="tag" class="regular-text" value="<?php echo esc_attr($o['tag']); ?>" placeholder="Ví dụ: TIỆC CƯỚI · 11:32">
              <p class="description">Dòng chữ nhỏ phía trên tiêu đề. Để trống thì giữ nhãn hiện tại.</p></td></tr>
          <tr><th><label for="mv-title">Tiêu đề</label></th>
              <td><input type="text" id="mv-title" name="title" class="large-text" value="<?php echo esc_attr($o['title']); ?>">
              <p class="description">Để trống thì hệ thống tự lấy tiêu đề từ YouTube khi lưu, hoặc giữ tiêu đề hiện tại nếu không lấy được.</p></td></tr>
        </table>
        <p class="submit"><button class="button button-primary" type="submit">Lưu</button>
        <button class="button" type="submit" name="reset" value="1" onclick="return confirm('Trả về clip gốc?')">Trả về clip gốc</button></p>
      </form>
    </div>
    <script>
    (function(){
      var f, pick=document.getElementById('mv-pick');
      pick.addEventListener('click',function(){
        if(f){f.open();return}
        f=wp.media({title:'Chọn ảnh bìa',button:{text:'Dùng ảnh này'},multiple:false,library:{type:'image'}});
        f.on('select',function(){var a=f.state().get('selection').first().toJSON();document.getElementById('mv-img').value=a.id;
          var u=(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url);document.getElementById('mv-prev').innerHTML='<img src="'+u+'" style="max-width:320px;border-radius:8px">'});
        f.open();
      });
      document.getElementById('mv-clear').addEventListener('click',function(){document.getElementById('mv-img').value='';document.getElementById('mv-prev').innerHTML=''});
    })();
    </script>
    <?php
}

add_action('wp_footer', function () {
    if (!is_singular('page')) return;
    $o = mclt_vh_get();
    if ($o['yt'] === '') return;
    $img = $o['img'] ? wp_get_attachment_image_url($o['img'], 'full') : 'https://i.ytimg.com/vi/' . $o['yt'] . '/maxresdefault.jpg';
    $cfg = array('yt' => $o['yt'], 'img' => $img, 'tag' => $o['tag'], 'title' => $o['title']);
    ?>
<script id="mclt-vh-js">(function(){var S=document.createElement('style');S.textContent='.lt-vfeat .lt-vcap::before{content:"";position:absolute;z-index:-1;left:-44px;right:44px;bottom:-40px;border-radius:0 0 28px 28px;top:-110px;height:auto!important;transform:none!important;background:linear-gradient(to top,rgba(8,20,38,.94) 0,rgba(8,20,38,.78) 50%,rgba(8,20,38,0) 100%);pointer-events:none}.lt-vfeat .lt-vcap .elementor-heading-title{text-shadow:0 2px 12px rgba(0,0,0,.65)}';document.head.appendChild(S);var C=<?php echo wp_json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
function go(){var f=document.querySelector('.lt-vfeat');if(!f)return;
[].slice.call(f.classList).forEach(function(c){if(/^lt-yt-/.test(c))f.classList.remove(c)});f.classList.add('lt-yt-'+C.yt);
var im=f.querySelector('.lt-vfeat-i img, img');
if(im&&C.img){im.removeAttribute('srcset');im.removeAttribute('sizes');im.removeAttribute('data-src');im.removeAttribute('data-lazy-src');im.src=C.img;
 if(C.img.indexOf('maxresdefault')>-1){im.onerror=function(){im.onerror=null;im.src='https://i.ytimg.com/vi/'+C.yt+'/hqdefault.jpg'};im.onload=function(){if(im.naturalWidth<=120){im.onload=null;im.src='https://i.ytimg.com/vi/'+C.yt+'/hqdefault.jpg'}}}}
var cap=f.querySelector('.lt-vcap');if(cap){var h=cap.querySelector('h1,h2,h3,h4'),t=cap.querySelector('.elementor-heading-title');
 if(C.tag&&t&&t!==h)t.textContent=C.tag;if(C.title&&h)h.textContent=C.title;}}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',go);else go();})();</script>
    <?php
}, 40);
add_action('wp_head', function () { if (is_singular('page')) echo '<style id="mclt-vc-css">@media(max-width:767px){.lt-gc.lt-cn{align-items:center!important;align-content:center!important;grid-template-rows:auto auto!important;row-gap:0!important;column-gap:14px!important}.lt-gc.lt-cn>:nth-child(2){grid-row:1!important;align-self:end!important}.lt-gc.lt-cn>:nth-child(3){grid-row:2!important;align-self:start!important}.lt-gc.lt-cn .mclt-sm,.lt-gc.lt-cn .mclt-sm *{font-size:11px!important;line-height:14px!important;letter-spacing:.08em!important;margin:0 0 4px!important}.lt-gc.lt-cn .elementor-heading-title:not(.mclt-sm){font-size:14px!important;line-height:20px!important;display:-webkit-box!important;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}}</style>'; }, 99);
