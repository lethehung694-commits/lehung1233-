<?php
require __DIR__ . '/config.php';

$cat   = (int)get('cat');
$brand = get('brand');
$size  = get('size');
$color = get('color');
$price = get('price');
$sort  = get('sort', 'new');
$kw    = get('q');
$page  = max(1, (int)get('page', 1));

$where = ['1=1']; $args = [];
if ($cat)   { $where[] = 'p.category_id = ?'; $args[] = $cat; }
if ($brand !== '') { $where[] = 'p.brand = ?'; $args[] = $brand; }
if ($kw !== '') {
    $where[] = '(p.product_name LIKE ? OR p.description LIKE ? OR p.brand LIKE ? OR p.product_id = ?)';
    $args[] = "%$kw%"; $args[] = "%$kw%"; $args[] = "%$kw%"; $args[] = (int)$kw;
}
if (preg_match('/^(\d*)-(\d*)$/', $price, $m)) {
    if ($m[1] !== '') { $where[] = 'p.price >= ?'; $args[] = (int)$m[1]; }
    if ($m[2] !== '') { $where[] = 'p.price <= ?'; $args[] = (int)$m[2]; }
}
if ($size !== '')  { $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.product_id AND v.size = ? AND v.stock > 0)'; $args[] = $size; }
if ($color !== '') { $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.product_id AND v.color = ? AND v.stock > 0)'; $args[] = $color; }
$W = implode(' AND ', $where);

$orders = [
    'new'        => 'p.created_at DESC, p.product_id DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular'    => '(SELECT COALESCE(SUM(od.quantity),0) FROM order_details od WHERE od.product_id = p.product_id) DESC, p.product_id DESC',
];
$ORDER = $orders[$sort] ?? $orders['new'];

$total = (int)val("SELECT COUNT(*) FROM products p WHERE $W", $args);
$pages = max(1, (int)ceil($total / PER_PAGE));
$page  = min($page, $pages);
$off   = ($page - 1) * PER_PAGE;
$items = rows("SELECT p.*, (p.created_at >= DATE_SUB(NOW(), INTERVAL 21 DAY)) AS is_new, (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.product_id) AS stock_total FROM products p WHERE $W ORDER BY $ORDER LIMIT " . PER_PAGE . " OFFSET $off", $args);

$brands = rows("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' " . ($cat ? 'AND category_id = ' . $cat : '') . ' ORDER BY brand');
$sizes  = rows("SELECT DISTINCT v.size FROM product_variants v JOIN products p ON p.product_id = v.product_id WHERE v.size <> '' " . ($cat ? 'AND p.category_id = ' . $cat : '') . ' ORDER BY v.size');
$colors = rows("SELECT DISTINCT v.color FROM product_variants v JOIN products p ON p.product_id = v.product_id WHERE v.color <> '' " . ($cat ? 'AND p.category_id = ' . $cat : '') . ' ORDER BY v.color');
$ranges = ['' => 'Tất cả mức giá', '-500000' => 'Dưới 500.000₫', '500000-1000000' => '500.000₫ – 1 triệu', '1000000-2000000' => '1 – 2 triệu', '2000000-4000000' => '2 – 4 triệu', '4000000-' => 'Trên 4 triệu'];

$curName = 'Tất cả sản phẩm';
foreach (categories() as $c) if ((int)$c['category_id'] === $cat) $curName = $c['category_name'];
if ($kw !== '') $curName = 'Kết quả cho “' . $kw . '”';
$pageTitle = $curName;

function shop_qs($over = []) {
    $p = array_merge($_GET, $over);
    $p = array_filter($p, function ($v) { return $v !== '' && $v !== null; });
    return 'shop.php' . ($p ? '?' . http_build_query($p) : '');
}
require __DIR__ . '/includes/header.php';
?>
<?= page_banner($curName) ?>
<div class="wrap">
  <p class="muted" style="margin:18px 0 0"><?= $total ?> sản phẩm</p>
  <form class="shop" method="get" action="<?= e(url('shop.php')) ?>">
    <?php if ($kw !== ''): ?><input type="hidden" name="q" value="<?= e($kw) ?>"><?php endif; ?>
    <aside class="filters" aria-label="Bộ lọc">
      <h4>Môn thể thao</h4>
      <label><input type="radio" name="cat" value="" data-autosubmit <?= !$cat ? 'checked' : '' ?>> Tất cả</label>
      <?php foreach (categories() as $c): ?>
        <label><input type="radio" name="cat" value="<?= (int)$c['category_id'] ?>" data-autosubmit <?= $cat === (int)$c['category_id'] ? 'checked' : '' ?>> <?= e($c['category_name']) ?></label>
      <?php endforeach; ?>
      <?php if ($brands): ?><h4>Thương hiệu</h4>
        <select name="brand" data-autosubmit><option value="">Tất cả</option>
        <?php foreach ($brands as $b): ?><option <?= $brand === $b['brand'] ? 'selected' : '' ?>><?= e($b['brand']) ?></option><?php endforeach; ?></select><?php endif; ?>
      <?php if ($sizes): ?><h4>Kích cỡ</h4>
        <select name="size" data-autosubmit><option value="">Tất cả</option>
        <?php foreach ($sizes as $s): ?><option <?= $size === $s['size'] ? 'selected' : '' ?>><?= e($s['size']) ?></option><?php endforeach; ?></select><?php endif; ?>
      <?php if ($colors): ?><h4>Màu sắc</h4>
        <select name="color" data-autosubmit><option value="">Tất cả</option>
        <?php foreach ($colors as $s): ?><option <?= $color === $s['color'] ? 'selected' : '' ?>><?= e($s['color']) ?></option><?php endforeach; ?></select><?php endif; ?>
      <h4>Khoảng giá</h4>
      <?php foreach ($ranges as $k => $lbl): ?>
        <label><input type="radio" name="price" value="<?= e($k) ?>" data-autosubmit <?= $price === $k ? 'checked' : '' ?>> <?= e($lbl) ?></label>
      <?php endforeach; ?>
      <p style="margin:16px 0 0"><a class="small" href="<?= e(url('shop.php')) ?>">Xóa bộ lọc</a></p>
    </aside>
    <div>
      <div class="toolbar">
        <span class="muted small">Trang <?= $page ?>/<?= $pages ?></span>
        <label class="small" style="display:flex;gap:8px;align-items:center;margin:0">Sắp xếp
          <select name="sort" data-autosubmit>
            <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Mới nhất</option>
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Phổ biến</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
          </select></label>
      </div>
      <?php if (!$items): ?>
        <div class="empty"><h3>Chưa có sản phẩm phù hợp</h3><p class="muted">Thử bỏ bớt bộ lọc hoặc tìm từ khóa khác.</p><a class="btn dark" href="<?= e(url('shop.php')) ?>">Xem tất cả</a></div>
      <?php else: ?>
        <div class="grid reveal"><?php foreach ($items as $p) echo product_card($p); ?></div>
        <?php if ($pages > 1): ?><nav class="pager" aria-label="Phân trang">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?><span class="cur"><?= $i ?></span>
            <?php else: ?><a href="<?= e(url(shop_qs(['page' => $i]))) ?>"><?= $i ?></a><?php endif; ?>
          <?php endfor; ?></nav><?php endif; ?>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
