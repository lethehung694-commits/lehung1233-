<?php
require __DIR__ . '/_layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = post('action'); $id = (int)post('id');
    if ($act === 'add' && post('name') !== '') { q('INSERT INTO categories (category_name, description) VALUES (?,?)', [post('name'), post('description')]); flash('Đã thêm danh mục.'); }
    elseif ($act === 'save' && post('name') !== '') { q('UPDATE categories SET category_name=?, description=? WHERE category_id=?', [post('name'), post('description'), $id]); flash('Đã lưu danh mục.'); }
    elseif ($act === 'delete') {
        if (val('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id])) flash('Danh mục còn sản phẩm, hãy chuyển sản phẩm sang danh mục khác trước.', 'err');
        else { q('DELETE FROM categories WHERE category_id = ?', [$id]); flash('Đã xóa danh mục.'); }
    }
    redirect('admin/categories.php');
}
$list = rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) n FROM categories c ORDER BY category_id');
admin_head('Danh mục', 'categories');
?>
<?php foreach ($list as $c): $f = 'cf' . (int)$c['category_id']; ?>
<form id="<?= $f ?>" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['category_id'] ?>"></form>
<?php endforeach; ?>
<form id="cf-new" method="post"><?= csrf_field() ?></form>
<div class="tablewrap"><table class="tbl"><tr><th>Tên</th><th>Mô tả</th><th class="r">Sản phẩm</th><th></th></tr>
<?php foreach ($list as $c): $f = 'cf' . (int)$c['category_id']; ?>
  <tr>
    <td><input class="fi" form="<?= $f ?>" name="name" value="<?= e($c['category_name']) ?>" aria-label="Tên"></td>
    <td><input class="fi" form="<?= $f ?>" name="description" value="<?= e($c['description']) ?>" aria-label="Mô tả"></td>
    <td class="r"><?= (int)$c['n'] ?></td>
    <td><div class="actions"><button class="btn dark sm" form="<?= $f ?>" name="action" value="save">Lưu</button>
      <button class="btn danger sm" form="<?= $f ?>" name="action" value="delete" onclick="return confirm('Xóa danh mục này?')">Xóa</button></div></td></tr>
<?php endforeach; ?>
  <tr><td><input class="fi" form="cf-new" name="name" placeholder="Tên danh mục mới" aria-label="Tên mới"></td><td><input class="fi" form="cf-new" name="description" placeholder="Mô tả" aria-label="Mô tả mới"></td><td></td><td><button class="btn sm" form="cf-new" name="action" value="add">Thêm</button></td></tr>
</table></div>
<?php admin_foot();
