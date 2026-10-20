<?php
require __DIR__ . '/_layout.php';
$id = (int)get('id');
$p = $id ? row('SELECT * FROM products WHERE product_id = ?', [$id]) : null;
if ($id && !$p) { flash('Không tìm thấy sản phẩm.', 'err'); redirect('admin/products.php'); }
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('name'); $price = (float)post('price'); $brand = post('brand'); $cat = (int)post('category_id'); $desc = post('description');
    $img = $p['image_url'] ?? '';
    if ($name === '' || $price <= 0 || !$cat) $err = 'Cần nhập tên, giá lớn hơn 0 và chọn danh mục.';
    if ($err === '' && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) $err = 'Ảnh phải là JPG, PNG hoặc WEBP.';
        else {
            $img = 'sp_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/products/' . $img)) $err = 'Không lưu được ảnh, kiểm tra quyền thư mục uploads/products.';
        }
    }
    if ($err === '') {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($id) q('UPDATE products SET product_name=?, price=?, brand=?, category_id=?, description=?, image_url=? WHERE product_id=?', [$name, $price, $brand ?: null, $cat, $desc, $img, $id]);
            else { q('INSERT INTO products (product_name, price, brand, category_id, description, image_url) VALUES (?,?,?,?,?,?)', [$name, $price, $brand ?: null, $cat, $desc, $img]); $id = (int)$pdo->lastInsertId(); }
            foreach ((array)($_POST['v'] ?? []) as $v) {
                $vid = (int)($v['id'] ?? 0); $size = trim($v['size'] ?? ''); $color = trim($v['color'] ?? ''); $stock = max(0, (int)($v['stock'] ?? 0)); $sku = trim($v['sku'] ?? '');
                if ($vid && !empty($v['del'])) {
                    if (val('SELECT COUNT(*) FROM order_details WHERE variant_id = ?', [$vid])) q('UPDATE product_variants SET stock = 0 WHERE variant_id = ?', [$vid]);
                    else q('DELETE FROM product_variants WHERE variant_id = ? AND product_id = ?', [$vid, $id]);
                } elseif ($vid) q('UPDATE product_variants SET size=?, color=?, stock=?, sku=? WHERE variant_id=? AND product_id=?', [$size, $color, $stock, $sku ?: null, $vid, $id]);
                elseif ($size !== '' || $color !== '' || $stock > 0) q('INSERT INTO product_variants (product_id, size, color, stock, sku) VALUES (?,?,?,?,?)', [$id, $size, $color, $stock, $sku ?: null]);
            }
            $pdo->commit();
            flash('Đã lưu sản phẩm.'); redirect('admin/product_edit.php?id=' . $id);
        } catch (Exception $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); $err = 'Lỗi lưu dữ liệu: ' . $ex->getMessage(); }
    }
    $p = array_merge($p ?? [], ['product_name' => $name, 'price' => $price, 'brand' => $brand, 'category_id' => $cat, 'description' => $desc]);
}
$vars = $id ? rows('SELECT * FROM product_variants WHERE product_id = ? ORDER BY CAST(size AS UNSIGNED), size, color', [$id]) : [];
admin_head($id ? 'Sửa sản phẩm' : 'Thêm sản phẩm', 'products');
if ($err) echo '<div class="flash err" role="alert">' . e($err) . '</div>';
?>
<form method="post" enctype="multipart/form-data" class="panel" style="max-width:920px">
  <?= csrf_field() ?>
  <div class="field"><label for="name">Tên sản phẩm</label><input id="name" name="name" value="<?= e($p['product_name'] ?? '') ?>" required></div>
  <div class="row2">
    <div class="field"><label for="price">Giá (₫)</label><input id="price" type="number" name="price" min="0" step="1000" value="<?= e($p['price'] ?? '') ?>" required></div>
    <div class="field"><label for="brand">Thương hiệu</label><input id="brand" name="brand" value="<?= e($p['brand'] ?? '') ?>"></div>
  </div>
  <div class="field"><label for="cat">Danh mục</label><select id="cat" name="category_id" required><option value="">Chọn danh mục</option>
    <?php foreach (categories() as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (int)($p['category_id'] ?? 0) === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="desc">Mô tả (chất liệu, công nghệ, xuất xứ, bảo quản)</label><textarea id="desc" name="description" rows="5"><?= e($p['description'] ?? '') ?></textarea></div>
  <div class="field"><label for="img">Ảnh sản phẩm <?= !empty($p['image_url']) ? '(hiện tại: ' . e($p['image_url']) . ')' : '' ?></label><input id="img" type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></div>

  <h3>Tùy chọn &amp; tồn kho</h3>
  <div class="tablewrap"><table class="tbl" id="vt">
    <tr><th>Size</th><th>Màu</th><th>Tồn kho</th><th>SKU</th><th>Xóa</th></tr>
    <?php foreach ($vars as $i => $v): ?>
    <tr><td><input type="hidden" name="v[<?= $i ?>][id]" value="<?= (int)$v['variant_id'] ?>"><input class="fi" name="v[<?= $i ?>][size]" value="<?= e($v['size']) ?>"></td>
      <td><input class="fi" name="v[<?= $i ?>][color]" value="<?= e($v['color']) ?>"></td>
      <td><input class="fi" type="number" min="0" name="v[<?= $i ?>][stock]" value="<?= (int)$v['stock'] ?>"></td>
      <td><input class="fi" name="v[<?= $i ?>][sku]" value="<?= e($v['sku'] ?? '') ?>"></td>
      <td><input type="checkbox" name="v[<?= $i ?>][del]" value="1" aria-label="Xóa tùy chọn"></td></tr>
    <?php endforeach; ?>
  </table></div>
  <p><button type="button" class="btn ghost sm" id="addv">Thêm tùy chọn</button></p>
  <p class="small muted">Tùy chọn đã có đơn hàng sẽ không bị xóa hẳn, chỉ đưa tồn kho về 0.</p>
  <p><button class="btn">Lưu sản phẩm</button> <a class="btn ghost" href="<?= e(url('admin/products.php')) ?>">Quay lại</a></p>
</form>
<script>
(function(){
  var n = <?= count($vars) ?>;
  document.getElementById('addv').addEventListener('click', function(){
    var t = document.getElementById('vt'), r = t.insertRow(-1), i = n++;
    r.innerHTML = '<td><input type="hidden" name="v['+i+'][id]" value="0"><input class="fi" name="v['+i+'][size]"></td>'+
      '<td><input class="fi" name="v['+i+'][color]"></td><td><input class="fi" type="number" min="0" name="v['+i+'][stock]" value="0"></td>'+
      '<td><input class="fi" name="v['+i+'][sku]"></td><td></td>';
  });
})();
</script>
<?php admin_foot();
