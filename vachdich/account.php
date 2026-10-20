<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = post('action');
    if ($act === 'profile') {
        q('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?', [post('name'), post('phone'), post('address'), $me['user_id']]);
        flash('Đã lưu thông tin cá nhân.');
    } elseif ($act === 'password') {
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['old'] ?? ''), $me['password_hash'])) flash('Mật khẩu hiện tại chưa đúng.', 'err');
        elseif (strlen($new) < 6) flash('Mật khẩu mới cần ít nhất 6 ký tự.', 'err');
        else { q('UPDATE users SET password_hash = ? WHERE user_id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['user_id']]); flash('Đã đổi mật khẩu.'); }
    } elseif ($act === 'cancel') {
        $oid = (int)post('order_id'); $pdo = db();
        $o = row('SELECT * FROM orders WHERE order_id = ? AND user_id = ?', [$oid, $me['user_id']]);
        if ($o && $o['status'] === 'pending') {
            $pdo->beginTransaction();
            foreach (rows('SELECT variant_id, quantity FROM order_details WHERE order_id = ?', [$oid]) as $d)
                q('UPDATE product_variants SET stock = stock + ? WHERE variant_id = ?', [$d['quantity'], $d['variant_id']]);
            q("UPDATE orders SET status = 'cancelled' WHERE order_id = ?", [$oid]);
            $pdo->commit();
            flash('Đã hủy đơn ' . order_code($oid) . '.');
        } else flash('Chỉ hủy được đơn đang xử lý.', 'err');
    }
    redirect('account.php');
}

$orders = rows('SELECT * FROM orders WHERE user_id = ? ORDER BY order_id DESC', [$me['user_id']]);
$pageTitle = 'Tài khoản của tôi';
require __DIR__ . '/includes/header.php';
?>
<?= page_banner('Tài khoản của tôi') ?>
<div class="wrap">
  <p class="muted" style="margin:18px 0 0"><?= e($me['email']) ?></p>
  <div class="two">
    <div>
      <h2>Đơn hàng</h2>
      <?php if (!$orders): ?><div class="empty"><p class="muted">Bạn chưa có đơn hàng nào.</p><a class="btn" href="<?= e(url('shop.php')) ?>">Mua sắm ngay</a></div><?php endif; ?>
      <?php foreach ($orders as $o): $items = rows('SELECT * FROM order_details WHERE order_id = ?', [$o['order_id']]); ?>
        <details class="box" style="margin-top:12px">
          <summary><?= e(order_code($o['order_id'])) ?> · <?= e(date('d/m/Y', strtotime($o['created_at']))) ?> · <?= money($o['total']) ?>
            <span class="badge <?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></summary>
          <?php foreach ($items as $i): ?>
            <div class="sum"><span><?= e($i['product_name']) ?> <span class="muted small">(<?= e(trim($i['size'] . ' ' . $i['color'])) ?>) × <?= (int)$i['quantity'] ?></span></span><span><?= money($i['price'] * $i['quantity']) ?></span></div>
          <?php endforeach; ?>
          <p class="small muted">Giao đến: <?= e($o['address']) ?> · <?= e(payment_label($o['payment_method'])) ?></p>
          <?php if ($o['status'] === 'pending' && $o['payment_method'] === 'qr' && vietqr_configured()): $note = order_code($o['order_id']); ?>
            <div class="qr-box" style="margin:12px 0">
              <img src="<?= e(vietqr_url($o['total'], $note)) ?>" alt="Mã QR chuyển khoản" class="qr-img" style="width:160px;height:160px">
              <div class="qr-info small"><p><span class="muted">Ngân hàng</span><b><?= e(BANK_NAME) ?></b></p><p><span class="muted">Số tiền</span><b><?= money($o['total']) ?></b></p><p><span class="muted">Nội dung</span><b><?= e($note) ?></b></p></div>
            </div>
          <?php endif; ?>
          <?php if ($o['status'] === 'pending'): ?>
            <form method="post" onsubmit="return confirm('Hủy đơn này?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>"><button class="btn ghost sm">Hủy đơn</button></form>
          <?php endif; ?>
        </details>
      <?php endforeach; ?>
    </div>
    <div>
      <form class="panel" method="post" style="margin-bottom:20px">
        <h3>Thông tin cá nhân</h3><?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <div class="field"><label for="n">Họ và tên</label><input id="n" name="name" value="<?= e($me['full_name']) ?>" required></div>
        <div class="field"><label for="p">Số điện thoại</label><input id="p" name="phone" value="<?= e($me['phone'] ?? '') ?>"></div>
        <div class="field"><label for="a">Địa chỉ nhận hàng mặc định</label><input id="a" name="address" value="<?= e($me['address'] ?? '') ?>"></div>
        <button class="btn dark sm">Lưu thay đổi</button>
      </form>
      <form class="panel" method="post">
        <h3>Đổi mật khẩu</h3><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <div class="field"><label for="o">Mật khẩu hiện tại</label><input id="o" type="password" name="old" required></div>
        <div class="field"><label for="w">Mật khẩu mới</label><input id="w" type="password" name="new" required minlength="6"></div>
        <button class="btn dark sm">Đổi mật khẩu</button>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
