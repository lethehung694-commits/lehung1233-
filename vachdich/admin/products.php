<?php
require __DIR__ . '/_layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete') {
        $id = (int)post('id');
        try {
            db()->beginTransaction();
            q('DELETE FROM product_variants WHERE product_id = ?', [$id]);
            q('DELETE FROM products WHERE product_id = ?', [$id]);
            db()->commit(); flash('Đã xóa sản phẩm.');
        } catch (Exception $ex) { if (db()->inTransaction()) db()->rollBack(); flash('Không xóa được: ' . $ex->getMessage(), 'err'); }
    } elseif (post('action') === 'restock_all') {
        $qty = max(0, (int)post('qty', 50));
        $pdo = db(); $pdo->beginTransaction();
        // nâng tồn kho các biến thể hiện có lên tối thiểu $qty (không hạ thấp biến thể đang có nhiều hơn)
        q('UPDATE product_variants SET stock = GREATEST(stock, ?)', [$qty]);
        // sản phẩm chưa có biến thể nào thì tạo 1 biến thể mặc định để có thể bán được
        $noVariant = rows('SELECT product_id FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.product_id)');
        foreach ($noVariant as $pr) q("INSERT INTO product_variants (product_id, size, color, stock, sku) VALUES (?, 'Tiêu chuẩn', '', ?, NULL)", [$pr['product_id'], $qty]);
        $pdo->commit();
        flash('Đã đặt tồn kho tối thiểu ' . $qty . ' cho toàn bộ sản phẩm (' . count($noVariant) . ' sản phẩm được tạo biến thể mặc định).');
    }
    redirect('admin/products.php');
}
$kw = get('q'); $cat = (int)get('cat');
$w = ['1=1']; $a = [];
if ($kw !== '') { $w[] = '(p.product_name LIKE ? OR p.brand LIKE ?)'; $a[] = "%$kw%"; $a[] = "%$kw%"; }
if ($cat) { $w[] = 'p.category_id = ?'; $a[] = $cat; }
$list = rows('SELECT p.*, c.category_name, (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.product_id) AS stock, (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.product_id) AS nv
              FROM products p LEFT JOIN categories c ON c.category_id = p.category_id WHERE ' . implode(' AND ', $w) . ' ORDER BY p.product_id DESC', $a);
admin_head('Sản phẩm & kho', 'products');
?>
<form class="panel" method="post" style="max-width:640px;margin-bottom:20px;display:flex;gap:10px;align-items:end;flex-wrap:wrap">
  <?= csrf_field() ?><input type="hidden" name="action" value="restock_all">
  <div class="field" style="margin:0"><label for="rq">Nạp nhanh tồn kho tối thiểu cho toàn bộ sản phẩm</label>
    <input id="rq" type="number" name="qty" min="1" value="50" style="width:120px"></div>
  <button class="btn dark sm">Nạp kho ngay</button>
  <p class="small muted" style="width:100%;margin:6px 0 0">Chỉ nâng tồn kho lên (không hạ sản phẩm đang có sẵn nhiều hàng); sản phẩm chưa có size/màu sẽ được tạo 1 tùy chọn mặc định để bán được ngay.</p>
</form>
<form class="filterbar" method="get">
  <input name="q" value="<?= e($kw) ?>" placeholder="Tìm tên, thương hiệu" aria-label="Tìm">
  <select name="cat" aria-label="Danh mục"><option value="">Tất cả danh mục</option><?php foreach (categories() as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select>
  <button class="btn dark sm">Lọc</button>
  <a class="btn sm" href="<?= e(url('admin/product_edit.php')) ?>">Thêm sản phẩm</a>
</form>
<div class="tablewrap"><table class="tbl">
  <tr><th>#</th><th>Sản phẩm</th><th>Danh mục</th><th>Thương hiệu</th><th class="r">Giá</th><th class="r">Tồn kho</th><th></th></tr>
  <?php foreach ($list as $p): ?>
  <tr><td><?= (int)$p['product_id'] ?></td><td><?= e($p['product_name']) ?></td><td><?= e($p['category_name']) ?></td><td><?= e($p['brand'] ?? '') ?></td>
    <td class="r"><?= money($p['price']) ?></td>
    <td class="r"><?= $p['nv'] == 0 ? '<span style="color:var(--err)">Chưa có tùy chọn</span>' : (int)$p['stock'] ?></td>
    <td><div class="actions"><a class="btn ghost sm" href="<?= e(url('admin/product_edit.php?id=' . (int)$p['product_id'])) ?>">Sửa</a>
      <form class="inline" method="post" onsubmit="return confirm('Xóa sản phẩm này và các tùy chọn của nó?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>"><button class="btn danger sm">Xóa</button></form></div></td></tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="7" class="muted">Không có sản phẩm.</td></tr><?php endif; ?>
</table></div>
<?php admin_foot();
