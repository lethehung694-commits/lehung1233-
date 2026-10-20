<?php
require __DIR__ . '/config.php';
$pages = [
 'about' => ['Giới thiệu', '<p>' . SITE_NAME . ' là cửa hàng đồ thể thao dành cho người chơi thật: từ buổi đá bóng cuối tuần, trận cầu lông sau giờ làm đến giải chạy đầu tiên của bạn.</p><p>Chúng tôi chọn từng mặt hàng theo môn thể thao, ghi rõ chất liệu, công nghệ và bảng size để bạn chọn đúng ngay từ lần đầu, ít phải đổi trả.</p><p>Bạn không chắc nên chọn size hay loại nào? Nhắn Zalo, chúng tôi tư vấn trước khi bạn đặt hàng.</p>'],
 'contact' => ['Liên hệ', '<p>Hotline: <a href="tel:' . preg_replace('/\s+/', '', SITE_PHONE) . '">' . e(SITE_PHONE) . '</a></p><p>Email: <a href="mailto:' . e(SITE_EMAIL) . '">' . e(SITE_EMAIL) . '</a></p><p>Zalo: <a href="' . e(SITE_ZALO) . '" target="_blank" rel="noopener">Nhắn tin cho cửa hàng</a></p><p>Địa chỉ: ' . e(SITE_ADDRESS) . '</p><p><a class="btn dark sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/' . urlencode(SITE_ADDRESS) . '">Xem bản đồ</a></p>'],
 'shipping' => ['Vận chuyển & thanh toán', '<h3>Vận chuyển</h3><ul><li>Giao nhanh: ' . money(SHIP_FAST) . '. Giao tiết kiệm: ' . money(SHIP_SAVING) . '.</li><li>Miễn phí vận chuyển cho đơn từ ' . money(FREE_SHIP_FROM) . '.</li><li>Bạn theo dõi trạng thái đơn (Đang xử lý, Đang giao, Đã giao) trong mục Tài khoản.</li></ul><h3>Thanh toán</h3><ul><li>Thanh toán khi nhận hàng (COD).</li><li>Chuyển khoản QR, ví MoMo, ZaloPay, thẻ ATM/Credit: cửa hàng liên hệ hướng dẫn sau khi bạn đặt hàng.</li></ul>'],
 'return' => ['Đổi trả & hoàn tiền', '<ul><li>Đổi size trong 7 ngày kể từ khi nhận hàng nếu sản phẩm chưa qua sử dụng, còn nguyên tem mác và hộp.</li><li>Hoàn tiền khi sản phẩm lỗi do nhà sản xuất hoặc giao sai mẫu.</li><li>Liên hệ Zalo hoặc hotline để được hướng dẫn gửi hàng đổi trả.</li></ul><p class="small">Đây là nội dung mẫu, chủ cửa hàng hãy chỉnh lại cho đúng chính sách thực tế.</p>'],
 'warranty' => ['Bảo hành', '<ul><li>Giày: bảo hành keo, đế trong thời gian quy định của cửa hàng.</li><li>Vợt: hỗ trợ căng dây, kiểm tra khung khi có lỗi kỹ thuật.</li><li>Sản phẩm hư hỏng do sử dụng sai cách hoặc hao mòn tự nhiên không thuộc phạm vi bảo hành.</li></ul><p class="small">Đây là nội dung mẫu, chủ cửa hàng hãy chỉnh lại thời hạn bảo hành thực tế.</p>'],
 'privacy' => ['Bảo mật thông tin', '<p>Chúng tôi chỉ dùng họ tên, số điện thoại, địa chỉ và email của bạn để xử lý đơn hàng và hỗ trợ sau bán.</p><p>Mật khẩu được mã hóa, cửa hàng không thể xem mật khẩu của bạn. Chúng tôi không bán thông tin cá nhân cho bên thứ ba.</p>'],
];
$p = get('p', 'about');
$blog = blog_posts();
if ($p === 'blog') { $title = 'Kinh nghiệm chọn đồ'; }
elseif (isset($pages[$p])) { $title = $pages[$p][0]; }
else { http_response_code(404); $title = 'Không tìm thấy trang'; }
$pageTitle = $title;
require __DIR__ . '/includes/header.php';
?>
<?= page_banner($title) ?>
<div class="wrap <?= $p === 'blog' ? '' : 'prose' ?>" style="<?= $p === 'blog' ? 'padding:32px 0 56px' : '' ?>">
  <?php if ($p === 'blog'): ?>
    <div class="story-grid" style="margin-top:8px">
      <?php foreach ($blog as $b): $img = CATEGORY_STOCK_IMG[$b['cat']] ?? STOCK_SHOE_IMG; ?>
        <div class="story-card"><img src="<?= e($img) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
          <div class="story-body"><h3><?= e($b['title']) ?></h3><p><?= e($b['body']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  <?php elseif (isset($pages[$p])): echo $pages[$p][1]; else: ?>
    <p>Trang bạn tìm không tồn tại. <a href="<?= e(url()) ?>">Về trang chủ</a></p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
