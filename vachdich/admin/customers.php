<?php
require __DIR__ . '/_layout.php';
$list = rows("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) n, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.user_id AND o.status <> 'cancelled') s
              FROM users u WHERE u.role = 'customer' ORDER BY u.user_id DESC");
admin_head('Khách hàng', 'customers');
?>
<div class="tablewrap"><table class="tbl"><tr><th>Khách hàng</th><th>Liên hệ</th><th>Đăng ký</th><th class="r">Số đơn</th><th class="r">Đã mua</th></tr>
<?php foreach ($list as $u): ?>
  <tr><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?><br><span class="small muted"><?= e($u['phone'] ?? '') ?></span></td><td><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td><td class="r"><?= (int)$u['n'] ?></td><td class="r"><?= money($u['s']) ?></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="5" class="muted">Chưa có khách hàng đăng ký.</td></tr><?php endif; ?></table></div>
<?php admin_foot();
