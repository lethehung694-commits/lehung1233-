<?php
require __DIR__ . '/_layout.php';
$statuses = ['pending', 'shipping', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)post('id'); $new = post('status');
    $o = row('SELECT * FROM orders WHERE order_id = ?', [$id]);
    if ($o && in_array($new, $statuses) && $new !== $o['status']) {
        if ($o['status'] === 'cancelled') flash('Đơn đã hủy không thể mở lại. Hãy tạo đơn mới cho khách.', 'err');
        else {
            $pdo = db(); $pdo->beginTransaction();
            if ($new === 'cancelled')
                foreach (rows('SELECT variant_id, quantity FROM order_details WHERE order_id = ?', [$id]) as $d)
                    q('UPDATE product_variants SET stock = stock + ? WHERE variant_id = ?', [$d['quantity'], $d['variant_id']]);
            q('UPDATE orders SET status = ? WHERE order_id = ?', [$new, $id]);
            $pdo->commit();
            flash('Đã cập nhật đơn ' . order_code($id) . ' thành “' . status_label($new) . '”.');
        }
    }
    redirect('admin/orders.php?id=' . $id);
}

$id = (int)get('id');
if ($id && ($o = row('SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?', [$id]))) {
    $items = rows('SELECT * FROM order_details WHERE order_id = ?', [$id]);
    admin_head('Đơn ' . order_code($id), 'orders');
    ?>
    <p class="no-print"><a href="<?= e(url('admin/orders.php')) ?>">← Tất cả đơn hàng</a></p>
    <div class="two">
      <div class="panel">
        <h3>Sản phẩm</h3>
        <?php foreach ($items as $i): ?><div class="sum"><span><?= e($i['product_name']) ?> <span class="muted small">(<?= e(trim($i['size'] . ' ' . $i['color'])) ?>) × <?= (int)$i['quantity'] ?></span></span><span><?= money($i['price'] * $i['quantity']) ?></span></div><?php endforeach; ?>
        <div class="sum" style="border-top:1px solid var(--line);margin-top:8px"><span>Tạm tính</span><span><?= money($o['subtotal']) ?></span></div>
        <?php if ($o['discount_amount'] > 0): ?><div class="sum"><span>Giảm giá <?= e($o['voucher_code']) ?></span><span>−<?= money($o['discount_amount']) ?></span></div><?php endif; ?>
        <div class="sum"><span>Vận chuyển (<?= e(ship_label($o['shipping_method'])) ?>)</span><span><?= money($o['shipping_fee']) ?></span></div>
        <div class="sum total"><span>Tổng</span><span><?= money($o['total']) ?></span></div>
      </div>
      <div>
        <div class="panel" style="margin-bottom:16px">
          <h3>Người nhận</h3>
          <p style="margin:0"><b><?= e($o['receiver_name']) ?></b><br><?= e($o['phone']) ?><br><?= e($o['address']) ?></p>
          <p class="small muted">Thanh toán: <?= e(payment_label($o['payment_method'])) ?><br>Đặt lúc: <?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?><?= $o['note'] ? '<br>Ghi chú: ' . e($o['note']) : '' ?></p>
        </div>
        <?php if ($o['payment_method'] === 'qr' && vietqr_configured()): $note = order_code($o['order_id']); ?>
        <div class="panel no-print" style="margin-bottom:16px">
          <h3>QR đối chiếu chuyển khoản</h3>
          <img src="<?= e(vietqr_url($o['total'], $note)) ?>" alt="QR" style="width:150px;height:150px;border:1px solid var(--line);border-radius:8px">
          <p class="small muted" style="margin:8px 0 0">Nội dung CK: <b><?= e($note) ?></b> — kiểm tra sao kê ngân hàng có đúng nội dung và số tiền <?= money($o['total']) ?> chưa trước khi chuyển sang "Đang giao".</p>
        </div>
        <?php endif; ?>
        <form class="panel no-print" method="post">
          <h3>Trạng thái</h3><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
          <div class="field"><select name="status" aria-label="Trạng thái"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?></select></div>
          <button class="btn dark sm">Cập nhật</button> <button class="btn ghost sm" type="button" onclick="window.print()">In hóa đơn</button>
        </form>
      </div>
    </div>
    <?php admin_foot(); exit;
}

$st = get('status');
$list = in_array($st, $statuses) ? rows('SELECT * FROM orders WHERE status = ? ORDER BY order_id DESC', [$st]) : rows('SELECT * FROM orders ORDER BY order_id DESC');
admin_head('Đơn hàng', 'orders');
?>
<form class="filterbar" method="get"><select name="status" onchange="this.form.submit()" aria-label="Lọc trạng thái"><option value="">Tất cả trạng thái</option>
  <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $st === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?></select></form>
<div class="tablewrap"><table class="tbl"><tr><th>Mã</th><th>Ngày</th><th>Người nhận</th><th>Thanh toán</th><th class="r">Tổng</th><th>Trạng thái</th></tr>
<?php foreach ($list as $o): ?>
  <tr><td><a href="<?= e(url('admin/orders.php?id=' . (int)$o['order_id'])) ?>"><?= e(order_code($o['order_id'])) ?></a></td><td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td><td><?= e($o['receiver_name']) ?><br><span class="small muted"><?= e($o['phone']) ?></span></td><td><?= e(payment_label($o['payment_method'])) ?></td><td class="r"><?= money($o['total']) ?></td><td><span class="badge <?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="6" class="muted">Chưa có đơn hàng.</td></tr><?php endif; ?></table></div>
<?php admin_foot();
