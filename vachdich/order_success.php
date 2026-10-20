<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user();
$o = row('SELECT * FROM orders WHERE order_id = ?', [(int)get('id')]);
if (!$o || ((int)$o['user_id'] !== (int)$me['user_id'] && ($me['role'] ?? '') !== 'admin')) { flash('Không tìm thấy đơn hàng.', 'err'); redirect('account.php'); }
$items = rows('SELECT * FROM order_details WHERE order_id = ?', [$o['order_id']]);
$pageTitle = 'Đặt hàng thành công';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap" style="max-width:720px;padding:40px 0 64px">
  <h1 class="ph1">Cảm ơn bạn, đơn hàng đã được ghi nhận</h1>
  <p>Mã đơn hàng của bạn là <b><?= e(order_code($o['order_id'])) ?></b>. <?= $o['payment_method'] === 'cod' ? 'Bạn thanh toán khi nhận hàng.' : ($o['payment_method'] === 'qr' ? 'Quét mã QR bên dưới để chuyển khoản.' : 'Cửa hàng sẽ liên hệ số ' . e($o['phone']) . ' để hướng dẫn thanh toán ' . e(payment_label($o['payment_method'])) . '. Hãy ghi mã đơn ở nội dung chuyển khoản.') ?></p>

  <?php if ($o['payment_method'] === 'qr'): ?>
    <?php if (vietqr_configured()): $note = order_code($o['order_id']); ?>
    <div class="panel qr-box">
      <img src="<?= e(vietqr_url($o['total'], $note)) ?>" alt="Mã QR chuyển khoản <?= e(BANK_NAME) ?>" class="qr-img">
      <div class="qr-info">
        <p><span class="muted">Ngân hàng</span><b><?= e(BANK_NAME) ?></b></p>
        <p><span class="muted">Chủ tài khoản</span><b><?= e(BANK_ACCOUNT_NAME) ?></b></p>
        <p><span class="muted">Số tiền</span><b><?= money($o['total']) ?></b></p>
        <p><span class="muted">Nội dung CK</span><b><?= e($note) ?></b></p>
        <p class="small muted" style="margin-top:8px">Số tiền và nội dung đã được điền sẵn trong mã QR — chỉ cần mở app ngân hàng hoặc MoMo/ZaloPay và quét.</p>
      </div>
    </div>
    <?php else: ?>
    <div class="flash err">Chưa cấu hình số tài khoản nhận tiền — vào <code>config.php</code>, điền <code>BANK_ACCOUNT_NO</code> để mã QR hiển thị tự động. Trong lúc chờ, vui lòng liên hệ hotline <?= e(SITE_PHONE) ?> để được hướng dẫn chuyển khoản.</div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="panel">
    <?php foreach ($items as $i): ?>
      <div class="sum"><span><?= e($i['product_name']) ?> <span class="muted small">(<?= e(trim($i['size'] . ' ' . $i['color'])) ?>) × <?= (int)$i['quantity'] ?></span></span><span><?= money($i['price'] * $i['quantity']) ?></span></div>
    <?php endforeach; ?>
    <div class="sum"><span>Vận chuyển (<?= e(ship_label($o['shipping_method'])) ?>)</span><span><?= $o['shipping_fee'] > 0 ? money($o['shipping_fee']) : 'Miễn phí' ?></span></div>
    <?php if ($o['discount_amount'] > 0): ?><div class="sum"><span>Giảm giá</span><span>−<?= money($o['discount_amount']) ?></span></div><?php endif; ?>
    <div class="sum total"><span>Tổng</span><span><?= money($o['total']) ?></span></div>
    <p class="small muted" style="margin-bottom:0">Giao đến: <?= e($o['receiver_name']) ?>, <?= e($o['phone']) ?>, <?= e($o['address']) ?></p>
  </div>
  <p style="margin-top:20px"><a class="btn" href="<?= e(url('account.php')) ?>">Theo dõi đơn hàng</a> <a class="btn ghost" href="<?= e(url('shop.php')) ?>">Tiếp tục mua sắm</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
