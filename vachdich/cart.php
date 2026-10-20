<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = post('action');
    if ($act === 'add') {
        $vid = (int)post('variant_id'); $qty = max(1, (int)post('qty', 1));
        $v = row('SELECT stock FROM product_variants WHERE variant_id = ?', [$vid]);
        if (!$v || (int)$v['stock'] < 1) { flash('Tùy chọn này đã hết hàng.', 'err'); redirect($_SERVER['HTTP_REFERER'] ?? 'shop.php'); }
        $have = (int)($_SESSION['cart'][$vid] ?? 0);
        $_SESSION['cart'][$vid] = min($have + $qty, (int)$v['stock']);
        flash('Đã thêm vào giỏ hàng.');
    } elseif ($act === 'update') {
        foreach ((array)($_POST['qty'] ?? []) as $vid => $qty) {
            $vid = (int)$vid; $qty = (int)$qty;
            if ($qty <= 0) unset($_SESSION['cart'][$vid]);
            else {
                $stock = (int)val('SELECT stock FROM product_variants WHERE variant_id = ?', [$vid]);
                $_SESSION['cart'][$vid] = min($qty, max(1, $stock));
            }
        }
        flash('Đã cập nhật giỏ hàng.');
    } elseif ($act === 'remove') {
        unset($_SESSION['cart'][(int)post('variant_id')]);
    } elseif ($act === 'voucher') {
        $code = strtoupper(post('code'));
        [$d, $msg, $v] = voucher_check($code, cart_subtotal(cart_lines()));
        if ($v) { $_SESSION['voucher'] = $code; flash('Đã áp dụng mã ' . $code . ', giảm ' . money($d) . '.'); }
        else { unset($_SESSION['voucher']); flash($msg, 'err'); }
    } elseif ($act === 'voucher_remove') {
        unset($_SESSION['voucher']);
    }
    redirect('cart.php');
}

$lines = cart_lines();
$sub = cart_subtotal($lines);
$disc = 0;
if (!empty($_SESSION['voucher'])) {
    [$disc, $msg, $v] = voucher_check($_SESSION['voucher'], $sub);
    if (!$v) { unset($_SESSION['voucher']); flash($msg, 'err'); $disc = 0; }
}
$ship = shipping_fee('fast', $sub);
$pageTitle = 'Giỏ hàng';
require __DIR__ . '/includes/header.php';
?>
<?= page_banner('Giỏ hàng') ?>
<div class="wrap">
  <?php if (!$lines): ?>
    <div class="empty" style="margin-bottom:56px"><h3>Giỏ hàng đang trống</h3><p class="muted">Chọn môn bạn chơi và thêm vài món vào giỏ.</p><a class="btn" href="<?= e(url('shop.php')) ?>">Xem sản phẩm</a></div>
  <?php else: ?>
  <div class="two">
    <form class="panel" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="update">
      <?php foreach ($lines as $l): ?>
        <div class="line">
          <div class="thumb-wrap"><?= product_thumb($l) ?></div>
          <div class="info">
            <a href="<?= e(url('product.php?id=' . (int)$l['product_id'])) ?>"><?= e($l['product_name']) ?></a>
            <div class="small muted"><?= e(trim($l['size'] . ' ' . ($l['color'] !== '' && $l['size'] !== '' ? '· ' : '') . $l['color'])) ?></div>
            <div class="small"><?= money($l['price']) ?></div>
            <?php if ($l['qty'] > $l['stock']): ?><div class="small" style="color:var(--err)">Chỉ còn <?= (int)$l['stock'] ?> sản phẩm, hãy giảm số lượng.</div><?php endif; ?>
          </div>
          <div class="qty"><button type="button" data-d="-1" aria-label="Giảm">−</button><input type="number" name="qty[<?= (int)$l['variant_id'] ?>]" value="<?= (int)$l['qty'] ?>" min="0" max="<?= max(1, (int)$l['stock']) ?>" aria-label="Số lượng"><button type="button" data-d="1" aria-label="Tăng">+</button></div>
          <strong style="min-width:96px;text-align:right"><?= money($l['total']) ?></strong>
          <button class="btn ghost sm" type="submit" form="rm-<?= (int)$l['variant_id'] ?>" aria-label="Xóa">Xóa</button>
        </div>
      <?php endforeach; ?>
      <p style="margin:16px 0 0"><button class="btn dark sm" type="submit">Cập nhật số lượng</button></p>
    </form>
    <?php foreach ($lines as $l): ?>
      <form id="rm-<?= (int)$l['variant_id'] ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>"></form>
    <?php endforeach; ?>

    <aside class="panel">
      <h3>Tóm tắt đơn hàng</h3>
      <form method="post" style="display:flex;gap:8px;margin-bottom:14px">
        <?= csrf_field() ?>
        <?php if (!empty($_SESSION['voucher'])): ?>
          <input type="hidden" name="action" value="voucher_remove">
          <span class="fi" style="flex:1"><b><?= e($_SESSION['voucher']) ?></b> đã áp dụng</span><button class="btn ghost sm">Bỏ</button>
        <?php else: ?>
          <input type="hidden" name="action" value="voucher">
          <input class="fi" name="code" placeholder="Mã giảm giá" aria-label="Mã giảm giá"><button class="btn dark sm">Áp dụng</button>
        <?php endif; ?>
      </form>
      <div class="sum"><span>Tạm tính</span><span><?= money($sub) ?></span></div>
      <?php if ($disc > 0): ?><div class="sum"><span>Giảm giá</span><span>−<?= money($disc) ?></span></div><?php endif; ?>
      <div class="sum"><span>Vận chuyển từ</span><span><?= $ship ? money($ship) : 'Miễn phí' ?></span></div>
      <div class="sum total"><span>Tổng</span><span><?= money($sub - $disc + $ship) ?></span></div>
      <?php if ($sub < FREE_SHIP_FROM): ?><p class="small muted">Mua thêm <?= money(FREE_SHIP_FROM - $sub) ?> để được miễn phí vận chuyển.</p><?php endif; ?>
      <a class="btn" style="display:block" href="<?= e(url('checkout.php')) ?>">Tiến hành thanh toán</a>
    </aside>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
