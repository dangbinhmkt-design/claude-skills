<?php
/**
 * Plugin Name: MC Lê Tam – Form đặt lịch
 * Description: Form đặt lịch MC ở cuối các trang, lưu thông tin khách gửi và báo về email. Menu: Đặt lịch MC.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

const MCLT_BK_TYPES = ['Bất động sản', 'Sự kiện doanh nghiệp', 'Tiệc cưới', 'Khác'];

/* ---------- Lưu trữ ---------- */
add_action('init', function () {
    register_post_type('mclt_booking', [
        'labels' => ['name' => 'Đặt lịch MC', 'singular_name' => 'Yêu cầu đặt lịch', 'menu_name' => 'Đặt lịch MC', 'all_items' => 'Tất cả yêu cầu'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-calendar-alt',
        'menu_position' => 26, 'supports' => ['title'],
        'capability_type' => 'post', 'capabilities' => ['create_posts' => 'do_not_allow'], 'map_meta_cap' => true,
    ]);
});

add_filter('manage_mclt_booking_posts_columns', function ($c) {
    return ['cb' => $c['cb'], 'title' => 'Họ và tên', 'bk_phone' => 'Số điện thoại', 'bk_date' => 'Ngày tổ chức', 'bk_type' => 'Loại sự kiện', 'bk_note' => 'Ghi chú', 'date' => 'Gửi lúc'];
});
add_action('manage_mclt_booking_posts_custom_column', function ($col, $id) {
    $map = ['bk_phone' => '_phone', 'bk_date' => '_event_date', 'bk_type' => '_type', 'bk_note' => '_note'];
    if (!isset($map[$col])) return;
    $v = get_post_meta($id, $map[$col], true);
    if ($col === 'bk_date' && $v) $v = date_i18n('d/m/Y', strtotime($v));
    if ($col === 'bk_note') $v = wp_trim_words($v, 18, '…');
    echo esc_html($v);
}, 10, 2);

add_action('add_meta_boxes', function () {
    add_meta_box('mclt_bk_detail', 'Thông tin khách gửi', function ($p) {
        $rows = ['Họ và tên' => $p->post_title, 'Số điện thoại' => get_post_meta($p->ID, '_phone', true), 'Ngày tổ chức' => ($d = get_post_meta($p->ID, '_event_date', true)) ? date_i18n('d/m/Y', strtotime($d)) : '', 'Loại sự kiện' => get_post_meta($p->ID, '_type', true), 'Gửi từ trang' => get_post_meta($p->ID, '_page', true)];
        echo '<table class="form-table">';
        foreach ($rows as $k => $v) echo '<tr><th>' . esc_html($k) . '</th><td>' . esc_html($v) . '</td></tr>';
        echo '<tr><th>Ghi chú</th><td>' . nl2br(esc_html(get_post_meta($p->ID, '_note', true))) . '</td></tr></table>';
    }, 'mclt_booking', 'normal', 'high');
});

/* ---------- Cài đặt email ---------- */
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=mclt_booking', 'Cài đặt form đặt lịch', 'Cài đặt', 'manage_options', 'mclt-booking-settings', function () {
        if (isset($_POST['mclt_bk_email']) && check_admin_referer('mclt_bk_save')) {
            $list = array_filter(array_map('sanitize_email', array_map('trim', explode(',', wp_unslash($_POST['mclt_bk_email'])))));
            update_option('mclt_bk_email', implode(', ', $list));
            $cc = array_filter(array_map('sanitize_email', array_map('trim', explode(',', wp_unslash($_POST['mclt_bk_cc'] ?? '')))));
            update_option('mclt_bk_cc', implode(', ', $cc));
            do_action('litespeed_purge_all');
            echo '<div class="notice notice-success"><p>Đã lưu.</p></div>';
        }
        $v = get_option('mclt_bk_email', get_option('admin_email'));
        echo '<div class="wrap"><h1>Cài đặt form đặt lịch</h1><form method="post">';
        wp_nonce_field('mclt_bk_save');
        echo '<table class="form-table"><tr><th><label for="e">Email nhận thông tin</label></th><td><input id="e" class="regular-text" name="mclt_bk_email" value="' . esc_attr($v) . '"><p class="description">Mỗi lượt khách gửi form sẽ báo về email này. Có thể nhập nhiều email, cách nhau bằng dấu phẩy.</p></td></tr></table>';
        echo '<table class="form-table"><tr><th><label for="c">Email CC</label></th><td><input id="c" class="regular-text" name="mclt_bk_cc" value="' . esc_attr(get_option('mclt_bk_cc', '')) . '"><p class="description">Các email này cũng nhận một bản (CC). Có thể để trống.</p></td></tr></table>';
        submit_button('Lưu');
        echo '</form></div>';
    });
});

/* ---------- Nhận form ---------- */
function mclt_bk_submit() {
    $fail = function ($m) { wp_send_json(['ok' => false, 'msg' => $m]); };
    if (!empty($_POST['website'])) wp_send_json(['ok' => true]); // honeypot
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone = preg_replace('/[^0-9+]/', '', wp_unslash($_POST['phone'] ?? ''));
    $date = sanitize_text_field(wp_unslash($_POST['event_date'] ?? ''));
    $type = sanitize_text_field(wp_unslash($_POST['type'] ?? ''));
    $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
    $page = esc_url_raw(wp_unslash($_POST['page'] ?? ''));
    if (mb_strlen($name) < 2) $fail('Anh/chị vui lòng nhập họ và tên.');
    if (!preg_match('/^(\+?84|0)\d{8,10}$/', $phone)) $fail('Số điện thoại chưa đúng, anh/chị kiểm tra lại giúp.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) < strtotime('today')) $fail('Anh/chị vui lòng chọn ngày tổ chức từ hôm nay trở đi.');
    if ($type !== '' && !in_array($type, MCLT_BK_TYPES, true)) $type = '';
    $ip = md5($_SERVER['REMOTE_ADDR'] ?? '');
    $k = 'mclt_bk_' . $ip; $n = (int) get_transient($k);
    if ($n >= 5) $fail('Anh/chị gửi hơi nhiều lần, vui lòng thử lại sau ít phút.');
    set_transient($k, $n + 1, HOUR_IN_SECONDS);

    $id = wp_insert_post(['post_type' => 'mclt_booking', 'post_status' => 'publish', 'post_title' => $name], true);
    if (is_wp_error($id)) $fail('Chưa gửi được, anh/chị thử lại giúp em.');
    update_post_meta($id, '_phone', $phone); update_post_meta($id, '_event_date', $date);
    update_post_meta($id, '_type', $type); update_post_meta($id, '_note', $note); update_post_meta($id, '_page', $page);

    $to = get_option('mclt_bk_email', get_option('admin_email'));
    $body = "Có khách vừa gửi thông tin đặt lịch MC trên mcletam.com\n\nHọ và tên: $name\nSố điện thoại: $phone\nNgày tổ chức: " . date_i18n('d/m/Y', strtotime($date)) . "\nLoại sự kiện: " . ($type ?: '(không chọn)') . "\nGhi chú:\n" . ($note ?: '(không có)') . "\n\nGửi từ trang: $page\nXem trong quản trị: " . admin_url('edit.php?post_type=mclt_booking');
    $headers = [];
    if ($cc = get_option('mclt_bk_cc', '')) $headers[] = 'Cc: ' . $cc;
    wp_mail($to, 'Đặt lịch MC mới: ' . $name . ' – ' . date_i18n('d/m/Y', strtotime($date)), $body, $headers);
    wp_send_json(['ok' => true]);
}
add_action('wp_ajax_mclt_bk', 'mclt_bk_submit');
add_action('wp_ajax_nopriv_mclt_bk', 'mclt_bk_submit');

/* ---------- Hiển thị ---------- */
add_action('wp_footer', function () {
    if (!is_singular('page')) return;
    global $post;
    $slug = $post ? $post->post_name : '';
    $pre = ['mc-bat-dong-san' => 'Bất động sản', 'mc-su-kien' => 'Sự kiện doanh nghiệp', 'mc-tiec-cuoi' => 'Tiệc cưới'][$slug] ?? '';
    $opts = '<option value="">Chọn loại sự kiện</option>';
    foreach (MCLT_BK_TYPES as $t) $opts .= '<option' . ($t === $pre ? ' selected' : '') . '>' . esc_html($t) . '</option>';
    ?>
<style id="mclt-bk-css">
#dat-lich{scroll-margin-top:84px}
.lt-ctawrap{background:linear-gradient(180deg,#3e5d7a 0,#3e5d7a 50%,#223b52 50%,#223b52 100%)}
.mclt-bk{background:#fff;border-radius:36px;box-shadow:0 24px 60px rgba(33,51,71,.18);padding:56px;display:flex;gap:64px;width:100%;box-sizing:border-box;font-family:'Montserrat',sans-serif;color:#3e5d7a}
.mclt-bk *{box-sizing:border-box}
.mclt-bk-intro{flex:0 0 380px}
.mclt-bk-eb{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;letter-spacing:.14em;color:#607e97;text-transform:uppercase;margin-bottom:20px}
.mclt-bk-eb i{color:#e8863a;font-style:normal;letter-spacing:0}.mclt-bk-eb b{display:block;width:28px;height:1px;background:#e8863a}
.mclt-bk h2{font-size:30px;line-height:38px;text-wrap:balance;font-weight:500;letter-spacing:-.02em;color:#3e5d7a;margin:0 0 20px}
.mclt-bk-intro p{font-size:16px;line-height:26px;color:#5f7488;margin:0 0 24px}
.mclt-bk-steps{list-style:none;margin:0;padding:12px 0 0;display:flex;flex-direction:column;gap:20px}
.mclt-bk-steps li{display:flex;gap:16px;align-items:flex-start}
.mclt-bk-steps em{flex:0 0 32px;height:32px;border-radius:50%;border:1px solid #e8863a;background:#fdf1e6;color:#e8863a;font:600 13px/30px 'Montserrat',sans-serif;text-align:center;font-style:normal}
.mclt-bk-steps strong{display:block;font-size:15px;font-weight:600;color:#3e5d7a}.mclt-bk-steps span{display:block;font-size:14px;line-height:22px;color:#5f7488}
.mclt-bk-form{flex:1;display:flex;flex-direction:column;gap:20px;min-width:0}
.mclt-bk-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.mclt-bk-f label{display:block;font-size:13px;font-weight:600;color:#3e5d7a;margin-bottom:8px}.mclt-bk-f label sup{color:#e8863a;font-size:13px;top:0}
.mclt-bk-f input,.mclt-bk-f select,.mclt-bk-f textarea{width:100%;background:#f7f4ef;border:1px solid #d6dfe7;border-radius:14px;padding:15px 18px;font:400 15px 'Montserrat',sans-serif;color:#3e5d7a;outline:0;appearance:none;-webkit-appearance:none;box-shadow:none;margin:0}
.mclt-bk-f select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none'%3E%3Cpath d='M5 8l5 5 5-5' stroke='%23607e97' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;padding-right:44px}
.mclt-bk-f input[type=date]{min-height:52px}
.mclt-bk-f textarea{min-height:150px;resize:vertical;line-height:24px}
.mclt-bk-f input::placeholder,.mclt-bk-f textarea::placeholder{color:#8a9aaa}
.mclt-bk-f input:focus,.mclt-bk-f select:focus,.mclt-bk-f textarea:focus{border-color:#3e5d7a;background:#fff}
.mclt-bk-hp{position:absolute!important;left:-9999px!important;height:0;overflow:hidden}
.mclt-bk-act{display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.mclt-bk-btn{background:#3e5d7a;color:#fff;border:0;border-radius:999px;padding:18px 30px;font:600 14px 'Montserrat',sans-serif;cursor:pointer;transition:background .2s}
.mclt-bk-btn:hover{background:#2f4a63}.mclt-bk-btn[disabled]{opacity:.6;cursor:wait}
.mclt-bk-priv{font-size:13px;color:#8a9aaa}
.mclt-bk-err{font-size:14px;color:#c0392b;min-height:0;margin:0}
.mclt-bk-ok{display:none;flex:1;flex-direction:column;justify-content:center;gap:12px}
.mclt-bk-ok h3{font-size:28px;font-weight:500;color:#3e5d7a;margin:0}.mclt-bk-ok p{font-size:16px;line-height:26px;color:#5f7488;margin:0}
.mclt-bk.done .mclt-bk-form{display:none}.mclt-bk.done .mclt-bk-ok{display:flex}
@media(max-width:1100px){.mclt-bk{gap:40px;padding:44px}.mclt-bk-intro{flex-basis:300px}.mclt-bk h2{font-size:26px;line-height:34px}}
@media(max-width:767px){
.lt-ctawrap{padding-left:0!important;padding-right:0!important}
.mclt-bk{flex-direction:column;gap:14px;padding:22px;border-radius:28px}
.mclt-bk-intro{flex:none}.mclt-bk-intro p,.mclt-bk-steps,.mclt-bk-priv{display:none}
.mclt-bk-eb{margin-bottom:12px}.mclt-bk h2{font-size:24px;line-height:31px;margin:0}
.mclt-bk-form{gap:14px}.mclt-bk-row{grid-template-columns:1fr;gap:14px}
.mclt-bk-f label{margin-bottom:6px}.mclt-bk-f input,.mclt-bk-f select,.mclt-bk-f textarea{padding:12px 16px}.mclt-bk-f input[type=date]{min-height:48px}
.mclt-bk-f textarea{min-height:104px}.mclt-bk-btn{width:100%;padding:18px}.mclt-bk-act{gap:0}
}
</style>
<template id="mclt-bk-tpl">
<div class="mclt-bk" id="dat-lich">
  <div class="mclt-bk-intro">
    <div class="mclt-bk-eb"><i>✦</i><b></b>Đặt lịch MC</div>
    <h2>Gửi thông tin để Lê&nbsp;Tam tư vấn lịch dẫn</h2>
    <p>Anh/chị để lại thông tin cơ bản và mô tả nhanh chương trình. Lê Tam sẽ xem lịch rồi liên hệ lại với anh/chị.</p>
    <ol class="mclt-bk-steps">
      <li><em>1</em><div><strong>Gửi thông tin</strong><span>Họ tên, số điện thoại và ngày dự kiến tổ chức.</span></div></li>
      <li><em>2</em><div><strong>Lê Tam xem lịch</strong><span>Kiểm tra lịch trống và đọc brief của anh/chị.</span></div></li>
      <li><em>3</em><div><strong>Chốt lịch &amp; báo giá</strong><span>Trao đổi chi tiết kịch bản và chi phí.</span></div></li>
    </ol>
  </div>
  <form class="mclt-bk-form" novalidate>
    <div class="mclt-bk-row">
      <div class="mclt-bk-f"><label for="bk-name">Họ và tên <sup>*</sup></label><input id="bk-name" name="name" type="text" placeholder="Nguyễn Văn A" autocomplete="name" required></div>
      <div class="mclt-bk-f"><label for="bk-phone">Số điện thoại <sup>*</sup></label><input id="bk-phone" name="phone" type="tel" placeholder="09xx xxx xxx" autocomplete="tel" required></div>
    </div>
    <div class="mclt-bk-row">
      <div class="mclt-bk-f"><label for="bk-date">Ngày tổ chức dự kiến <sup>*</sup></label><input id="bk-date" name="event_date" type="date" required></div>
      <div class="mclt-bk-f"><label for="bk-type">Loại sự kiện</label><select id="bk-type" name="type"><?php echo $opts; ?></select></div>
    </div>
    <div class="mclt-bk-f"><label for="bk-note">Ghi chú cho MC</label><textarea id="bk-note" name="note" placeholder="Anh/chị cần MC như thế nào? Mô tả ngắn về chương trình: tên sự kiện, địa điểm, quy mô, thời lượng, phong cách mong muốn…"></textarea></div>
    <div class="mclt-bk-hp"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <p class="mclt-bk-err" role="alert"></p>
    <div class="mclt-bk-act"><button class="mclt-bk-btn" type="submit">Gửi thông tin đặt lịch ↗</button><span class="mclt-bk-priv">Thông tin chỉ dùng để liên hệ về lịch dẫn.</span></div>
  </form>
  <div class="mclt-bk-ok"><h3>Cảm ơn anh/chị đã gửi thông tin</h3><p>Lê Tam đã nhận được yêu cầu và sẽ xem lịch rồi liên hệ lại với anh/chị trong thời gian sớm nhất.</p></div>
</div>
</template>
<script id="mclt-bk-js">(function(){
var AJAX=<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
function mount(){
  var tpl=document.getElementById('mclt-bk-tpl');if(!tpl||document.getElementById('dat-lich'))return;
  var host=document.querySelector('.lt-ctawrap > .e-con-inner')||document.querySelector('.lt-ctawrap');
  var node=tpl.content.firstElementChild.cloneNode(true);
  if(host){host.innerHTML='';host.appendChild(node);}else{var f=document.querySelector('.lt-footer')||document.body;f.parentNode.insertBefore(node,f);}
  var form=node.querySelector('form'),err=node.querySelector('.mclt-bk-err'),btn=node.querySelector('.mclt-bk-btn');
  var d=new Date(),m=('0'+(d.getMonth()+1)).slice(-2),dd=('0'+d.getDate()).slice(-2);
  form.querySelector('[name=event_date]').min=d.getFullYear()+'-'+m+'-'+dd;
  form.addEventListener('submit',function(e){
    e.preventDefault();err.textContent='';
    var fd=new FormData(form);fd.append('action','mclt_bk');fd.append('page',location.href.split('#')[0]);
    btn.disabled=true;var old=btn.textContent;btn.textContent='Đang gửi…';
    fetch(AJAX,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
      if(j.ok){node.classList.add('done');node.scrollIntoView({behavior:'smooth',block:'center'});}
      else{err.textContent=j.msg||'Chưa gửi được, anh/chị thử lại giúp.';btn.disabled=false;btn.textContent=old;}
    }).catch(function(){err.textContent='Mất kết nối, anh/chị thử lại giúp.';btn.disabled=false;btn.textContent=old;});
  });
}
function wire(){
  document.querySelectorAll('a').forEach(function(a){
    var t=(a.textContent||'').trim().toLowerCase();
    var hb=a.classList.contains('elementor-button')&&a.closest('.lt-header');
    if(hb||/^(xem chương trình tiêu biểu|đặt lịch mc|liên hệ lịch dẫn)/.test(t)){
      if(hb||/^xem chương trình/.test(t)){var s=a.querySelector('.elementor-button-text')||a;if(s.children.length===0)s.textContent='Đặt lịch MC';}
      a.setAttribute('href','#dat-lich');
    }
  });
}
document.addEventListener('click',function(e){
  var a=e.target.closest&&e.target.closest('a[href="#dat-lich"]');if(!a)return;
  var el=document.getElementById('dat-lich');if(el){e.preventDefault();e.stopPropagation();el.scrollIntoView({behavior:'smooth',block:'start'});}
},true);
function go(){mount();wire();setTimeout(wire,1200);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',go);else go();
window.addEventListener('load',wire);
})();</script>
    <?php
}, 45);
