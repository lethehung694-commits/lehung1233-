<?php
require __DIR__ . '/_layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = post('action'); $id = (int)post('id');
    if ($act === 'toggle') q('UPDATE vouchers SET is_active = 1 - is_active WHERE voucher_id = ?', [$id]);
    elseif ($act === 'delete') q('DELETE FROM vouchers WHERE voucher_id = ?', [$id]);
    elseif ($act === 'add') {
        $code = strtoupper(post('code')); $type = post('type') === 'fixed' ? 'fixed' : 'percent';
        $val = (float)post('value'); $start = post('start'); $end = post('end');
        if ($code === '' || $val <= 0 || $start === '' || $end === '' || $end < $start) flash('Cần nhập mã, giá trị giảm > 0 và khoảng ngày hợp lệ.', 'err');
        elseif (val('SELECT COUNT(*) FROM vouchers WHERE code = ?', [$code])) flash('Mã này đã tồn tại.', 'err');
        else {
            q('INSERT INTO vouchers (code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, start_date, end_date, is_active) VALUES (?,?,?,?,?,?,0,?,?,1)',
              [$code, $type, $val, (float)post('min'), post('max') === '' ? null : (float)post('max'), max(1, (int)post('limit', 100)), $start . ' 00:00:00', $end . ' 23:59:59']);
            flash('Đã tạo mã ' . $code . '.');
        }
    }
    redirect('admin/vouchers.php');
}
$list = rows('SELECT * FROM vouchers ORDER BY voucher_id DESC');
admin_head('Khuyến mãi', 'vouchers');
?>
<form class="panel" method="post" style="max-width:920px;margin-bottom:24px"><h3>Tạo mã giảm giá</h3><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="row2">
    <div class="field"><label for="c">Mã</label><input id="c" name="code" required></div>
    <div class="field"><label for="t">Loại</label><select id="t" name="type"><option value="percent">Giảm theo %</option><option value="fixed">Giảm số tiền cố định</option></select></div>
    <div class="field"><label for="v">Giá trị giảm (% hoặc ₫)</label><input id="v" type="number" name="value" min="1" step="any" required></div>
    <div class="field"><label for="m">Đơn tối thiểu (₫)</label><input id="m" type="number" name="min" min="0" value="0"></div>
    <div class="field"><label for="x">Giảm tối đa (₫, để trống nếu không giới hạn)</label><input id="x" type="number" name="max" min="0"></div>
    <div class="field"><label for="l">Số lượt dùng</label><input id="l" type="number" name="limit" min="1" value="100"></div>
    <div class="field"><label for="s">Từ ngày</label><input id="s" type="date" name="start" value="<?= date('Y-m-d') ?>" required></div>
    <div class="field"><label for="e">Đến ngày</label><input id="e" type="date" name="end" value="<?= date('Y-m-d', strtotime('+30 day')) ?>" required></div>
  </div><button class="btn dark sm">Tạo mã</button></form>
<div class="tablewrap"><table class="tbl"><tr><th>Mã</th><th>Giảm</th><th>Đơn tối thiểu</th><th>Đã dùng</th><th>Hiệu lực</th><th>Trạng thái</th><th></th></tr>
<?php foreach ($list as $v): ?>
  <tr><td><b><?= e($v['code']) ?></b></td>
    <td><?= $v['discount_type'] === 'percent' ? rtrim(rtrim($v['discount_value'], '0'), '.') . '%' : money($v['discount_value']) ?><?= $v['max_discount_amount'] !== null ? '<br><span class="small muted">tối đa ' . money($v['max_discount_amount']) . '</span>' : '' ?></td>
    <td><?= money($v['min_order_amount']) ?></td><td><?= (int)$v['used_count'] ?>/<?= (int)$v['usage_limit'] ?></td>
    <td class="small"><?= e(date('d/m/Y', strtotime($v['start_date']))) ?> – <?= e(date('d/m/Y', strtotime($v['end_date']))) ?></td>
    <td><span class="badge <?= $v['is_active'] ? 'delivered' : 'cancelled' ?>"><?= $v['is_active'] ? 'Đang bật' : 'Đã tắt' ?></span></td>
    <td><div class="actions">
      <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$v['voucher_id'] ?>"><button class="btn ghost sm"><?= $v['is_active'] ? 'Tắt' : 'Bật' ?></button></form>
      <form class="inline" method="post" onsubmit="return confirm('Xóa mã này?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$v['voucher_id'] ?>"><button class="btn danger sm">Xóa</button></form></div></td></tr>
<?php endforeach; ?></table></div>
<?php admin_foot();
