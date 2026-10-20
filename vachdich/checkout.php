<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user();
$lines = cart_lines();
if (!$lines) { flash('Giỏ hàng đang trống.', 'err'); redirect('cart.php'); }
$sub = cart_subtotal($lines);
$f = ['name' => $me['full_name'] ?? '', 'phone' => $me['phone'] ?? '', 'address' => $me['address'] ?? '', 'note' => '', 'ship' => 'fast', 'pay' => 'cod'];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = ['name' => post('name'), 'phone' => post('phone'), 'address' => post('address'), 'note' => post('note'), 'ship' => post('ship'), 'pay' => post('pay')];
    if ($f['name'] === '' || $f['address'] === '') $err = 'Vui lòng nhập họ tên và địa chỉ nhận hàng.';
    elseif (!preg_match('/^[0-9+ .]{9,15}$/', $f['phone'])) $err = 'Số điện thoại chưa hợp lệ.';
    elseif (!in_array($f['ship'], ['fast', 'saving'])) $err = 'Hãy chọn phương thức vận chuyển.';
    elseif (!in_array($f['pay'], ['cod', 'qr', 'momo', 'zalopay', 'card'])) $err = 'Hãy chọn phương thức thanh toán.';

    if ($err === '') {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            foreach ($lines as $l) {
                $stock = (int)val('SELECT stock FROM product_variants WHERE variant_id = ? FOR UPDATE', [$l['variant_id']]);
                if ($stock < $l['qty']) throw new Exception('“' . $l['product_name'] . '” chỉ còn ' . $stock . ' sản phẩm. Hãy giảm số lượng trong giỏ.');
            }
            $disc = 0; $vcode = null;
            if (!empty($_SESSION['voucher'])) {
                [$disc, $msg, $v] = voucher_check($_SESSION['voucher'], $sub);
                if ($v) $vcode = $v['code']; else { $disc = 0; unset($_SESSION['voucher']); }
            }
            $ship = shipping_fee($f['ship'], $sub);
            $total = $sub - $disc + $ship;
            q('INSERT INTO orders (user_id, receiver_name, phone, address, note, shipping_method, payment_method, subtotal, shipping_fee, discount_amount, total, voucher_code, status)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
              [$me['user_id'], $f['name'], $f['phone'], $f['address'], $f['note'], $f['ship'], $f['pay'], $sub, $ship, $disc, $total, $vcode, 'pending']);
            $oid = (int)$pdo->lastInsertId();
            foreach ($lines as $l) {
                q('INSERT INTO order_details (order_id, product_id, variant_id, product_name, size, color, price, quantity) VALUES (?,?,?,?,?,?,?,?)',
                  [$oid, $l['product_id'], $l['variant_id'], $l['product_name'], $l['size'], $l['color'], $l['price'], $l['qty']]);
                q('UPDATE product_variants SET stock = stock - ? WHERE variant_id = ?', [$l['qty'], $l['variant_id']]);
            }
            if ($vcode) q('UPDATE vouchers SET used_count = used_count + 1 WHERE code = ?', [$vcode]);
            $pdo->commit();
            unset($_SESSION['cart'], $_SESSION['voucher']);
            redirect('order_success.php?id=' . $oid);
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $ex->getMessage();
        }
    }
}

$disc = 0;
if (!empty($_SESSION['voucher'])) { [$disc, $m, $v] = voucher_check($_SESSION['voucher'], $sub); }
$ship = shipping_fee($f['ship'], $sub);
$pageTitle = 'Thanh toán';
require __DIR__ . '/includes/header.php';
?>
<?= page_banner('Thanh toán', ['Giỏ hàng' => 'cart.php']) ?>
<div class="wrap">
  <?php if ($err): ?><div class="flash err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <form class="two" method="post">
    <?= csrf_field() ?>
    <div>
      <div class="panel" style="margin-bottom:20px">
        <h3>Thông tin giao hàng</h3>
        <div class="row2">
          <div class="field"><label for="name">Họ và tên</label><input id="name" name="name" value="<?= e($f['name']) ?>" required></div>
          <div class="field"><label for="phone">Số điện thoại</label><input id="phone" name="phone" value="<?= e($f['phone']) ?>" required inputmode="tel"></div>
        </div>
        <div class="field"><label for="address">Địa chỉ nhận hàng</label><input id="address" name="address" value="<?= e($f['address']) ?>" required></div>
        <div class="field" style="margin:0"><label for="note">Ghi chú cho cửa hàng (không bắt buộc)</label><textarea id="note" name="note" rows="2"><?= e($f['note']) ?></textarea></div>
      </div>
      <div class="panel" style="margin-bottom:20px">
        <h3>Vận chuyển</h3>
        <label class="choice"><input type="radio" name="ship" value="fast" <?= $f['ship'] === 'fast' ? 'checked' : '' ?>><span><b>Giao nhanh</b> <span class="muted">– <?= money(SHIP_FAST) ?></span></span></label>
        <label class="choice"><input type="radio" name="ship" value="saving" <?= $f['ship'] === 'saving' ? 'checked' : '' ?>><span><b>Giao tiết kiệm</b> <span class="muted">– <?= money(SHIP_SAVING) ?></span></span></label>
        <p class="small muted" style="margin:0">Đơn từ <?= money(FREE_SHIP_FROM) ?> được miễn phí vận chuyển.</p>
      </div>
      <div class="panel">
        <h3>Thanh toán</h3>
        <?php foreach (['cod', 'qr', 'momo', 'zalopay', 'card'] as $m): ?>
          <label class="choice"><input type="radio" name="pay" value="<?= $m ?>" <?= $f['pay'] === $m ? 'checked' : '' ?>><span><?= e(payment_label($m)) ?></span></label>
        <?php endforeach; ?>
        <p class="small muted" style="margin:0">Với hình thức không phải COD, cửa hàng sẽ liên hệ hướng dẫn thanh toán sau khi bạn đặt hàng.</p>
      </div>
    </div>
    <aside class="panel">
      <h3>Đơn hàng của bạn</h3>
      <?php foreach ($lines as $l): ?>
        <div class="sum"><span><?= e($l['product_name']) ?> <span class="muted small">(<?= e(trim($l['size'] . ' ' . $l['color'])) ?>) × <?= (int)$l['qty'] ?></span></span><span><?= money($l['total']) ?></span></div>
      <?php endforeach; ?>
      <div class="sum" style="border-top:1px solid var(--line);margin-top:8px"><span>Tạm tính</span><span><?= money($sub) ?></span></div>
      <?php if ($disc > 0): ?><div class="sum"><span>Giảm giá (<?= e($_SESSION['voucher']) ?>)</span><span>−<?= money($disc) ?></span></div><?php endif; ?>
      <div class="sum"><span>Vận chuyển</span><span><?= $ship ? money($ship) : 'Miễn phí' ?></span></div>
      <div class="sum total"><span>Tổng</span><span><?= money($sub - $disc + $ship) ?></span></div>
      <button class="btn" style="width:100%;margin-top:12px" type="submit">Đặt hàng</button>
      <p class="small muted"><a href="<?= e(url('cart.php')) ?>">Quay lại giỏ hàng</a></p>
    </aside>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
