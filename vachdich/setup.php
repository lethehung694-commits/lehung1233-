<?php
/* Chạy MỘT LẦN: http://localhost/<thư-mục-web>/setup.php  – xong hãy XÓA file này. */
require __DIR__ . '/config.php';

$schema = [
  'users' => ['pk' => 'user_id', 'cols' => [
    'full_name' => "VARCHAR(120) NOT NULL DEFAULT ''", 'email' => "VARCHAR(160) NOT NULL DEFAULT ''",
    'password_hash' => "VARCHAR(255) NOT NULL DEFAULT ''", 'phone' => 'VARCHAR(20) NULL', 'address' => 'VARCHAR(255) NULL',
    'role' => "VARCHAR(20) NOT NULL DEFAULT 'customer'", 'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP']],
  'product_variants' => ['pk' => 'variant_id', 'cols' => [
    'product_id' => 'INT NOT NULL DEFAULT 0', 'size' => "VARCHAR(20) NOT NULL DEFAULT ''", 'color' => "VARCHAR(40) NOT NULL DEFAULT ''",
    'stock' => 'INT NOT NULL DEFAULT 0', 'sku' => 'VARCHAR(60) NULL']],
  'orders' => ['pk' => 'order_id', 'cols' => [
    'user_id' => 'INT NULL', 'receiver_name' => "VARCHAR(120) NOT NULL DEFAULT ''", 'phone' => "VARCHAR(20) NOT NULL DEFAULT ''",
    'address' => "VARCHAR(255) NOT NULL DEFAULT ''", 'note' => 'TEXT NULL', 'shipping_method' => "VARCHAR(20) NOT NULL DEFAULT 'fast'",
    'payment_method' => "VARCHAR(20) NOT NULL DEFAULT 'cod'", 'subtotal' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
    'shipping_fee' => 'DECIMAL(12,2) NOT NULL DEFAULT 0', 'discount_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
    'total' => 'DECIMAL(12,2) NOT NULL DEFAULT 0', 'voucher_code' => 'VARCHAR(40) NULL',
    'status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'", 'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP']],
  'order_details' => ['pk' => 'detail_id', 'cols' => [
    'order_id' => 'INT NOT NULL DEFAULT 0', 'product_id' => 'INT NOT NULL DEFAULT 0', 'variant_id' => 'INT NULL',
    'product_name' => "VARCHAR(200) NOT NULL DEFAULT ''", 'size' => "VARCHAR(20) NOT NULL DEFAULT ''", 'color' => "VARCHAR(40) NOT NULL DEFAULT ''",
    'price' => 'DECIMAL(12,2) NOT NULL DEFAULT 0', 'quantity' => 'INT NOT NULL DEFAULT 1']],
];
function table_cols($t) {
    return array_column(rows('SELECT COLUMN_NAME n, IS_NULLABLE nl, COLUMN_DEFAULT d, EXTRA x, COLUMN_TYPE ty, COLUMN_KEY k FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB_NAME, $t]), null, 'n');
}

$log = []; $warn = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    foreach ($schema as $t => $def) {
        $existed = (bool)val('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB_NAME, $t]);
        if (!$existed) { $pdo->exec("CREATE TABLE $t ({$def['pk']} INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $log[] = "Đã tạo bảng $t."; }
        $have = table_cols($t);
        if (!isset($have[$def['pk']])) $warn[] = "Bảng $t không có cột khóa chính “{$def['pk']}”" . ($t === 'order_details' ? ' (không sao, code không dùng cột này).' : ' – hãy gửi ảnh tab Structure của bảng này để chỉnh code cho khớp.');
        foreach ($def['cols'] as $c => $ddl) {
            if (!isset($have[$c])) { $pdo->exec("ALTER TABLE $t ADD COLUMN $c $ddl"); $log[] = "Đã thêm cột $t.$c."; }
            elseif (in_array($c, ['status', 'role', 'shipping_method', 'payment_method']) && stripos($have[$c]['ty'], 'enum') === 0) {
                $pdo->exec("ALTER TABLE $t MODIFY $c $ddl"); $log[] = "Đã đổi $t.$c từ ENUM sang VARCHAR.";
            }
        }
        // cột lạ bắt buộc nhập mà không có giá trị mặc định sẽ làm INSERT lỗi -> cho phép NULL
        foreach (table_cols($t) as $n => $c) {
            if (isset($def['cols'][$n]) || $n === $def['pk'] || $c['k'] === 'PRI') continue;
            if ($c['nl'] === 'NO' && $c['d'] === null && stripos($c['x'], 'auto_increment') === false && stripos($c['x'], 'GENERATED') === false) {
                $pdo->exec("ALTER TABLE $t MODIFY $n {$c['ty']} NULL"); $log[] = "Cột $t.$n (có sẵn của bạn) đã cho phép NULL để không chặn việc thêm dữ liệu.";
            }
        }
    }
    // products.brand
    if (!val('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [DB_NAME, 'products', 'brand'])) {
        $pdo->exec('ALTER TABLE products ADD COLUMN brand VARCHAR(60) NULL'); $log[] = 'Đã thêm cột products.brand.';
    }
    $known = ['Adidas', 'Nike', 'Yonex', 'Molten', 'Wilson', 'Dunlop', 'Speedo', 'Thành Công', 'Puma', 'Mizuno', 'Lining', 'Victor', 'Kamito', 'Babolat', 'Head', 'Under Armour'];
    $n = 0;
    foreach (rows("SELECT product_id, product_name FROM products WHERE brand IS NULL OR brand = ''") as $p)
        foreach ($known as $b) if (stripos($p['product_name'], $b) !== false) { q('UPDATE products SET brand = ? WHERE product_id = ?', [$b, $p['product_id']]); $n++; break; }
    if ($n) $log[] = "Đã tự nhận diện thương hiệu cho $n sản phẩm (dựa vào tên).";

    // Tạo tùy chọn size/màu/tồn kho cho sản phẩm chưa có
    $seeded = 0;
    foreach (rows('SELECT p.product_id, p.product_name FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.product_id)') as $p) {
        $nm = mb_strtolower($p['product_name']);
        if (strpos($nm, 'giày') === 0) { $sizes = ['39', '40', '41', '42', '43']; $colors = ['Đen', 'Trắng']; }
        elseif (strpos($nm, 'găng') === 0) { $sizes = ['8', '9', '10']; $colors = ['Đen']; }
        elseif (strpos($nm, 'đai') === 0) { $sizes = ['M', 'L', 'XL']; $colors = ['Đen']; }
        elseif (strpos($nm, 'thảm') === 0) { $sizes = ['Tiêu chuẩn']; $colors = ['Tím', 'Xanh']; }
        else { $sizes = ['Tiêu chuẩn']; $colors = ['']; }
        foreach ($sizes as $s) foreach ($colors as $c)
            q('INSERT INTO product_variants (product_id, size, color, stock, sku) VALUES (?,?,?,?,?)', [$p['product_id'], $s, $c, mt_rand(4, 30), 'SP' . $p['product_id'] . '-' . $s . '-' . mb_substr($c, 0, 2)]);
        $seeded++;
    }
    if ($seeded) $log[] = "Đã tạo size/màu/tồn kho mẫu cho $seeded sản phẩm (hãy chỉnh lại số thật trong trang quản trị).";

    // Tài khoản admin
    $email = strtolower(post('email')); $pw = (string)($_POST['password'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pw) >= 6) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $u = row('SELECT user_id FROM users WHERE email = ?', [$email]);
        if ($u) { q("UPDATE users SET password_hash = ?, role = 'admin' WHERE user_id = ?", [$hash, $u['user_id']]); $log[] = "Đã đặt $email làm quản trị viên."; }
        else { q("INSERT INTO users (full_name, email, password_hash, role) VALUES ('Quản trị viên', ?, ?, 'admin')", [$email, $hash]); $log[] = "Đã tạo tài khoản quản trị $email."; }
    } else $warn[] = 'Chưa tạo tài khoản quản trị: cần email hợp lệ và mật khẩu từ 6 ký tự.';
    if (!$log) $log[] = 'Mọi thứ đã sẵn sàng, không cần thay đổi gì thêm.';
}
$pageTitle = 'Cài đặt';
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cài đặt | <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head><body>
<div class="wrap" style="max-width:640px;padding:40px 0">
  <h1 style="font-size:2.6rem">Cài đặt <?= e(SITE_NAME) ?></h1>
  <p class="muted">Bước này kiểm tra database <b><?= e(DB_NAME) ?></b>, thêm các cột còn thiếu, tạo dữ liệu size/tồn kho mẫu và tạo tài khoản quản trị. Không xóa dữ liệu hiện có.</p>
  <?php foreach ($log as $l): ?><div class="flash ok"><?= e($l) ?></div><?php endforeach; ?>
  <?php foreach ($warn as $l): ?><div class="flash err"><?= e($l) ?></div><?php endforeach; ?>
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p><a class="btn" href="<?= e(url()) ?>">Xem trang chủ</a> <a class="btn dark" href="<?= e(url('login.php')) ?>">Đăng nhập quản trị</a></p>
    <p class="small">Xong rồi thì hãy <b>xóa file setup.php</b> khỏi thư mục web.</p>
  <?php else: ?>
  <form class="panel" method="post"><?= csrf_field() ?>
    <div class="field"><label for="e">Email quản trị</label><input id="e" type="email" name="email" required></div>
    <div class="field"><label for="p">Mật khẩu quản trị (tối thiểu 6 ký tự)</label><input id="p" type="password" name="password" minlength="6" required></div>
    <button class="btn">Chạy cài đặt</button>
  </form>
  <?php endif; ?>
</div></body></html>
