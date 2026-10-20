<?php
require __DIR__ . '/config.php';

$page = max(1, (int)get('page', 1));
$total = (int)val('SELECT COUNT(*) FROM products');
$pages = max(1, (int)ceil($total / PER_PAGE));
$page  = min($page, $pages);
$off   = ($page - 1) * PER_PAGE;

$items = rows('SELECT p.*, (p.created_at >= DATE_SUB(NOW(), INTERVAL 21 DAY)) AS is_new,
               (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.product_id) AS stock_total
               FROM products p ORDER BY p.created_at DESC, p.product_id DESC LIMIT ' . PER_PAGE . " OFFSET $off");

$pageTitle = 'Hàng mới về';
require __DIR__ . '/includes/header.php';
?>
<?= page_banner('Hàng mới về') ?>
<div class="wrap" style="padding-bottom:56px">
  <p class="muted" style="margin:18px 0 22px">Sản phẩm được cập nhật gần đây nhất, xếp theo ngày đăng mới nhất trước — <?= $total ?> sản phẩm.</p>
  <?php if (!$items): ?>
    <div class="empty"><h3>Chưa có sản phẩm</h3><a class="btn dark" href="<?= e(url('shop.php')) ?>">Xem tất cả sản phẩm</a></div>
  <?php else: ?>
    <div class="grid reveal"><?php foreach ($items as $p) echo product_card($p); ?></div>
    <?php if ($pages > 1): ?><nav class="pager" aria-label="Phân trang">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i === $page): ?><span class="cur"><?= $i ?></span>
        <?php else: ?><a href="<?= e(url('new.php?page=' . $i)) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?></nav><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
