---
name: "mc-portfolio-website"
description: "Quy trình dựng website portfolio cho MC bằng code trực tiếp (HTML/CSS) theo Figma đã duyệt, kèm bộ quy tắc responsive, plugin chỉnh chi tiết và bàn giao hướng dẫn cho khách."
---

# Dựng website portfolio cho MC

Áp dụng khi làm trang giới thiệu MC (bất động sản, sự kiện, tiệc cưới, video) trên WordPress của khách, bám theo thiết kế Figma đã duyệt. Rút ra từ dự án MC Lê Tam.

## Hướng đi (chốt ngày 03/11/2026)
Không dựng và xử lý trang trong Elementor nữa. Dựng thẳng bằng code CSS (kèm HTML), sau đó viết plugin để điều chỉnh chi tiết. Lý do: lưu Elementor hay trả lỗi 503, và phần lớn độ khớp Figma vẫn phải làm bằng CSS/JS trong plugin.

Đường tắt từ Figma: Claude đọc Figma qua MCP (get_metadata, get_screenshot, get_design_context) rồi viết code, không cần plugin chuyển đổi. Nếu có trang gấp mà khách vẫn muốn Elementor, UiChemy là phương án dự phòng (gói trả phí từ khoảng 11 USD/tháng, vẫn phải sửa tay responsive). Nếu sau này chuyển block theme, đổi bước 3 của mục 2 sang xuất `theme.json` và pattern.

## 0. Nguyên tắc làm việc với anh Bình
- Xưng "em", gọi "Anh". Tiếng Việt tự nhiên, tránh cấu trúc "A, không phải B" bằng gạch ngang.
- Chất lượng tối thiểu 9/10 trước khi giao. Mơ hồ thì hỏi tối đa 5 câu, không đoán.
- Xem trước (preview) rồi mới xuất file. Không sửa nội dung đã đánh dấu "final".
- Brief của agency: không đưa Facebook, số điện thoại, email, Zalo, QR (agency gửi khách riêng). Chỉ dùng dữ liệu thật của khách, không bịa lời chứng thực hay số liệu. Bỏ trang Liên hệ.
- Thứ tự nội dung theo feedback khách: mục bất động sản đẩy show A&K Tower lên đầu; "Quy trình hợp tác" đặt gần cuối trang chủ; logo đối tác dùng ảnh demo, khách thay logo thật sau.

## 1. Khảo sát đầu vào
1. Xin link Figma đã duyệt (cả desktop và mobile), lấy cấu trúc từng trang.
2. Liệt kê trang cần có. Bộ chuẩn: Trang chủ, Giới thiệu, MC Bất động sản, MC Sự kiện, MC Tiệc cưới, Video.
3. Kiểm tra WordPress của khách: theme (Hello Elementor), trang cũ hoặc template có sẵn. Ghi lại ID trang cũ và TUYỆT ĐỐI không đụng vào.
4. Thu thập nội dung thật: ảnh, clip YouTube, danh sách chương trình đã dẫn, album. Chương trình chưa có ảnh thì đánh dấu bản nháp và báo lại cho Anh.

## 2. Dựng trang bằng code
1. Chuyển hệ thống thiết kế từ Figma (màu, font, khoảng cách, bo góc) thành biến CSS `:root` một lần, ghi vào skill để trang sau không phải dò lại. Coi đầu ra `get_design_context` là tham chiếu để lấy giá trị chính xác, không dán nguyên.
2. Viết từng trang bằng HTML và CSS, bám đúng Figma. Mỗi trang là template hoặc shortcode do plugin của mình cung cấp.
3. Đặt class và thuộc tính `data-` ổn định cho mọi khối có thể sửa (chữ, ảnh, nút, thẻ). Đây là điểm móc cho trình sửa tại chỗ ở mục 3.
4. Viết CSS theo tiền tố riêng (ví dụ `lt-` cho giao diện, `mclt-` cho plugin) và để CSS giao diện tách ở backend, không trộn vào nội dung.
5. Plugin riêng (ví dụ `mcletam-portfolio`) chứa: custom post type Chương trình, CSS và template các trang, editor sửa tại chỗ, REST lưu override, CSS mobile, script phóng ảnh.
6. Cài plugin bằng cách nén thư mục thành zip, tải lên tại wp-admin `plugin-install.php?tab=upload`, chọn "Thay thế bản hiện tại với bản đã tải lên". Các bước tải lên làm tuần tự.

## 3. Plugin chỉnh chi tiết cho khách
- Thanh công cụ nổi (`#mclt-bar`), khách bấm "Sửa nội dung" ngay trên trang.
- Override lưu qua REST `mclt/v1/save` vào option, khóa theo `pageId:blockId` (dùng `data-` id của khối).
- Chữ: sửa trực tiếp. Ảnh: đổi ảnh, chỉnh rộng ảnh, vị trí ngang/dọc, phóng to, khung cắt (Gốc, 3:2, 4:3, Vuông, 16:9, Dọc 3:4, 4:5, 2:3), nút Đặt lại.
- Khung cắt: đặt style lên chính khối bọc ảnh (width, aspect-ratio, overflow hidden), ảnh bên trong dùng object-fit cover, object-position, transform scale.
- Thumbnail chương trình, album và mục Khoảnh khắc có thể không có công cụ cắt. Phải nói rõ trong hướng dẫn.

### Plugin bổ sung (mã nguồn ở `plugins/`)
- `mclt-video-hero`: đổi clip nổi bật trang Video (menu MC Lê Tam, Clip nổi bật). Có sẵn lớp phủ gradient và text-shadow cho phần chú thích để chữ không chìm vào ảnh, và CSS mobile cho thẻ video (nhãn nằm trên tiêu đề, tiêu đề tối đa 3 dòng).
- `mclt-home-videos`: đổi 5 video trang chủ (1 lớn `.lt-vbig`, 4 nhỏ `.lt-vsmall`, theo thứ tự trong DOM). Ô trống thì giữ nguyên video cũ.
- `mclt-booking`: form đặt lịch thay khối CTA cuối trang (`.lt-ctawrap > .e-con-inner`), mọi nút CTA và nút menu trỏ về `#dat-lich`. Lưu bằng post type `mclt_booking`, báo email (To và CC trong Đặt lịch MC, Cài đặt), có honeypot và giới hạn 5 lượt mỗi giờ theo IP.
- Plugin chính `mcletam-portfolio` (v1.15.0) gồm nhiều file (programs, footer, moments, tablet, mobile, reviews, partners, editor.js, assets). Bản đầy đủ nằm trong file sao lưu .wpress, không để ở repo này.

### Quy trình vận hành trên site khách
- LiteSpeed Cache giữ HTML cũ sau khi sửa plugin. Cách xóa nhanh: lưu một form cài đặt có gọi `do_action('litespeed_purge_all')`. Khi kiểm tra bằng fetch, thêm tham số chống cache.
- Sửa PHP plugin ngay trên site: mở Plugin File Editor, đặt nội dung bằng CodeMirror `setValue`, rồi bấm `document.getElementById('submit').click()` (không dùng `form.submit()` vì bị ô tên "submit" che mất). Tắt hộp thoại "Leave site" bằng `onbeforeunload=null`.
- Kiểm tra mobile bằng iframe cùng nguồn rộng 390px.
- Công cụ JS của Chrome chặn kết quả giống URL, query hoặc base64. Muốn lấy mã nguồn từ trình soạn thảo plugin: thay `=`, `?`, `&` bằng ký tự khác rồi đọc từng đoạn khoảng 1000 ký tự, ghép lại, đối chiếu SHA-256.
- Sau mỗi tính năng mới phải cập nhật Google Doc hướng dẫn (bảng đầu trang, bảng nhớ nhanh 5.4) và xuất lại PDF: dùng Drive `download_file_content` với `exportMimeType` application/pdf, rồi `create_file` với tham chiếu file sha256 để tải lên cùng thư mục.
- Bản test của form đặt lịch tạo bản ghi thật trong WordPress. Không tự xóa nếu Anh chưa cho phép.

## 4. Quy tắc responsive (rút từ feedback của Anh trên bản đã dựng)
Breakpoint: mobile `max-width:767px`, tablet 768 đến 1100px, desktop trên 1100px. Mọi quy tắc mobile bọc trong `@media(max-width:767px)`.

**Chữ và xuống dòng**
- Đặt `text-wrap:pretty` cho tiêu đề và đoạn văn, `text-wrap:balance` cho nút, để không rớt một chữ lẻ ở cuối dòng.
- Tiêu đề mục (01/02/03) luôn một dòng, `white-space:nowrap`, cỡ khoảng 16px, không đè lên ảnh.
- Chữ lớn trang trí (MC hero, REAL ESTATE) tính theo `vw` (17.5vw cho hero chủ, 13.5vw cho chữ khổng lồ trang con), `line-height:1`, tối đa vừa khung. Màn 430px cho khoảng 58px.
- Nhãn nhỏ 13px, nút phụ 14px, bộ đếm ảnh 14px. Chữ dưới 12.5px trên mobile thì phải tăng.
- Dòng ngắt chủ động dùng thẻ `.mclt-br` (ẩn trên desktop, `display:inline` trên mobile).

**Nút và nhóm nút**
- Hai nút cạnh nhau: cao bằng nhau, chữ căn giữa cả hai chiều (`display:flex; align-items:center; justify-content:center; min-height:56px`), mỗi nút `flex:1 1 0`, `gap:10px`.
- Hai nút CTA ở hero: mỗi nút `calc(50% - 25px)`, cao 60px, neo trái và phải 20px.
- Ba nút chọn lĩnh vực nằm một hàng, `gap:8px`, chữ 12.5px, `nowrap`.
- Nhóm nhãn (chip): 2 cột đều nhau (`flex:1 1 calc(50% - 5px)`), chữ căn giữa, không xuống dòng, cao tối thiểu 38px. Chip dài (logo, tên đơn vị) chiếm cả hàng và được phép xuống dòng, `line-height:1.4`.

**Cụm số liệu**
- Cụm số liệu 3 mục xếp 3 hàng cân đối thay vì 2 + 1. Mỗi hàng: số rộng cố định 138px bên trái, nhãn bên phải, viền trên mảnh `#E7DFD3`, đệm dọc 18px. Ẩn dải phân cách dọc của desktop.

**Khối và nền**
- Khối giới thiệu định vị (`lt-dinhvi`): `min-width:250px`, `max-width:calc(100% - 40px)`, đệm 16px 26px, không bó chữ.
- Nền xanh nhạt phải đậm hơn (`#2F4A63`) để chữ cam dễ đọc. Chữ cam trên nền tối dùng `#FFB074`, chữ xám xanh `#D5E0EA`.
- Thẻ sự kiện: giãn đều khoảng cách giữa loại sự kiện, tên cô dâu chú rể, mô tả và dải ảnh (tiêu đề cách trên 16px, mô tả cách trên 10px, `line-height:1.7`, đệm dưới 24px).
- Bước quy trình cuối (04) phải có đường nối như các bước trên.
- Ẩn bớt phần phụ trên mobile bằng class `lt-hide-m`, không xóa khỏi desktop.

**Ảnh**
- Ảnh hero các trang con: `object-fit:cover`, vùng giữ lại chỉnh bằng `object-position` (ví dụ 50% 45% và 50% 28%), không méo hình.
- Bấm ảnh để phóng to (lightbox): nền `rgba(10,18,28,.94)`, nút đóng 40px, nút trước/sau 38px trên mobile, có bộ đếm ảnh, cho phép lướt khi có nhiều ảnh.
- Mũi tên ↗ dạng chữ bị iPhone đổi thành emoji màu. Thay bằng SVG đơn sắc theo `currentColor`.

**Header**
- Logo header 96px trên mobile (128px desktop), đệm dọc header 8px.

**Tablet (iPad, 768 đến 1100px, màn cảm ứng)**
- Hiển thị đúng bố cục desktop, tự co vừa màn hình bằng thẻ viewport: `width=1100, initial-scale=w/1100`. Không viết bố cục tablet riêng.
- Đây mới là xử lý tạm. Tinh chỉnh từng cỡ máy để sau. Lỗi đã biết: ảnh khối Thư ngỏ và thẻ "8+" lệch nhau ở tablet.
- Chữ trong ô "MC SỰ KIỆN" nên tăng từ 11px lên 13px.

**Kỹ thuật ghi đè CSS**
- Khi bị CSS cũ đè, nâng độ ưu tiên bằng cách lặp class (`.mclt-sm.mclt-sm.mclt-sm`) cùng tiền tố `html body`, hạn chế dùng `!important` tràn lan ngoài khối mobile.
- Dùng `:has()` để nhận biết số phần tử con (ví dụ cụm có đúng 3 mục) thay vì thêm class thủ công.

## 5. Kiểm tra trước khi giao
- Mở từng trang thật bằng trình duyệt giả lập thiết bị (DevTools emulation), không dùng Chrome headless vì bị kẹp chiều rộng tối thiểu và ảnh mobile bị cắt.
- Kiểm tra ít nhất 360px, 430px, 820px (tablet) và 1920px. Đo `getComputedStyle` rồi so với số liệu Figma. Đừng chỉ kiểm bản desktop rồi báo "khớp hết", vì bản mobile thường lệch cỡ chữ, màu, thứ tự hàng.
- Chụp ảnh cạnh khung Figma và xác định phần tử nào nằm ở mép nào.
- Kiểm tra từng tính năng editor: sửa chữ, đổi ảnh, cắt, đặt lại, lưu, tải lại trang.
- Báo rõ những gì chưa kiểm chứng (ví dụ hiển thị ảnh cắt trên mobile).
- Nếu cần bản Elementor, chỉ xuất file `.json` từng trang SAU KHI Anh duyệt.

## 6. Hướng dẫn sử dụng cho khách
1. Viết bằng ngôn ngữ đời thường, chia phần theo việc khách muốn làm: Bắt đầu, sửa chữ và ảnh, thêm hoặc sửa chương trình, thêm clip YouTube, đổi clip nổi bật và video trang chủ, form đặt lịch, đổi ảnh Khoảnh khắc và logo đối tác, ẩn hoặc xóa nội dung.
2. Mỗi phần có ảnh chụp màn hình thật, in đậm tên nút, in nghiêng ghi chú, bảng tra nhanh "Bạn muốn... / Làm ở đâu / Xem phần".
3. Phần ảnh chia nhỏ Phần A đến D (kích thước, vị trí, khung cắt, đặt lại), có ví dụ trước và sau.
4. Giao hai bản: Google Doc và PDF, lưu vào thư mục Drive Anh chỉ định.
5. Định dạng Google Doc bằng documents.batchUpdate: lề 54pt, Montserrat 10.5pt, H1 26pt navy, H2 17pt navy gạch chân cam 1.5pt, H3 13pt, H4 11.5pt cam, bảng có hàng đầu navy chữ trắng đậm, hàng xen kẽ màu kem, ảnh căn giữa. Màu: navy #223B52, cam #E8863A, kem #F6F1EA, chữ #2B3B47.
6. Export từ Claude Docs làm mất ảnh. Muốn bản có ảnh thì dựng PDF hoặc docx từ markdown với ảnh cục bộ.

## 7. Việc tồn đọng thường gặp
Tinh chỉnh tablet theo từng cỡ máy, clip hero cho Sự kiện và Tiệc cưới, lời chứng thực thật từ khách, chương trình nháp chưa có album, ticket hosting nếu Elementor lỗi 503.

## Mẹo Drive và Docs
- Drive web không có file input sẵn. Hook `HTMLInputElement.prototype.click` để bắt input rồi dùng file_upload.
- Drive MCP: `update_file` với parentId để chuyển file, `trash_file` có thể khôi phục.
- Google Docs MCP: `read_doc` để lấy chỉ số, rồi `update_doc` với requests. Bảng dùng `tableStartLocation` đúng chỉ số, định dạng ô theo `tableRange`.