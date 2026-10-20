<?php
require __DIR__ . '/_layout.php';
$rev    = (float)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'");
$nOrd   = (int)val('SELECT COUNT(*) FROM orders');
$nNew   = (int)val("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$nCus   = (int)val("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$low    = rows('SELECT v.*, p.product_name FROM product_variants v JOIN products p ON p.product_id = v.product_id WHERE v.stock <= 5 ORDER BY v.stock, p.product_name LIMIT 10');
$recent = rows('SELECT * FROM orders ORDER BY order_id DESC LIMIT 8');
$byDay = [];
foreach (rows("SELECT DATE(created_at) d, SUM(total) s FROM orders WHERE status <> 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)") as $r) $byDay[$r['d']] = (float)$r['s'];
$days = []; for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $days[$d] = $byDay[$d] ?? 0; }
$max = max(1, max($days));
admin_head('Tổng quan', 'index');
?>
<div class="stats">
  <div class="stat"><b><?= money($rev) ?></b><span>Doanh thu (không tính đơn hủy)</span></div>
  <div class="stat"><b><?= $nOrd ?></b><span>Tổng số đơn</span></div>
  <div class="stat"><b><?= $nNew ?></b><span>Đơn đang xử lý</span></div>
  <div class="stat"><b><?= $nCus ?></b><span>Khách hàng</span></div>
</div>
<h3>Doanh thu 7 ngày gần nhất</h3>
<div class="bars" style="margin-bottom:36px">
  <?php foreach ($days as $d => $s): ?><div style="height:<?= max(2, round($s / $max * 100)) ?>%" title="<?= e(money($s)) ?>"><i><?= e(date('d/m', strtotime($d))) ?></i></div><?php endforeach; ?>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:24px">
  <div><h3>Sắp hết hàng</h3>
    <div class="tablewrap"><table class="tbl"><tr><th>Sản phẩm</th><th>Tùy chọn</th><th class="r">Tồn</th></tr>
    <?php foreach ($low as $v): ?><tr><td><a href="<?= e(url('admin/product_edit.php?id=' . (int)$v['product_id'])) ?>"><?= e($v['product_name']) ?></a></td><td><?= e(trim($v['size'] . ' ' . $v['color'])) ?></td><td class="r"><b style="color:<?= $v['stock'] == 0 ? 'var(--err)' : 'var(--warn)' ?>"><?= (int)$v['stock'] ?></b></td></tr><?php endforeach; ?>
    <?php if (!$low): ?><tr><td colspan="3" class="muted">Chưa có mặt hàng nào sắp hết.</td></tr><?php endif; ?></table></div></div>
  <div><h3>Đơn mới nhất</h3>
    <div class="tablewrap"><table class="tbl"><tr><th>Mã</th><th>Khách</th><th class="r">Tổng</th><th>Trạng thái</th></tr>
    <?php foreach ($recent as $o): ?><tr><td><a href="<?= e(url('admin/orders.php?id=' . (int)$o['order_id'])) ?>"><?= e(order_code($o['order_id'])) ?></a></td><td><?= e($o['receiver_name']) ?></td><td class="r"><?= money($o['total']) ?></td><td><span class="badge <?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td></tr><?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="4" class="muted">Chưa có đơn hàng.</td></tr><?php endif; ?></table></div></div>
</div>
<?php admin_foot();
