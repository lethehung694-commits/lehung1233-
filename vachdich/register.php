<?php
require __DIR__ . '/config.php';
$err = ''; $f = ['name' => '', 'email' => '', 'phone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = ['name' => post('name'), 'email' => strtolower(post('email')), 'phone' => post('phone')];
    $pw = (string)($_POST['password'] ?? '');
    if ($f['name'] === '') $err = 'Vui lòng nhập họ tên.';
    elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err = 'Email chưa hợp lệ.';
    elseif (strlen($pw) < 6) $err = 'Mật khẩu cần ít nhất 6 ký tự.';
    elseif ($pw !== (string)($_POST['password2'] ?? '')) $err = 'Mật khẩu nhập lại chưa khớp.';
    elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$f['email']])) $err = 'Email này đã được đăng ký.';
    else {
        q("INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?,?,?,?, 'customer')",
          [$f['name'], $f['email'], password_hash($pw, PASSWORD_DEFAULT), $f['phone']]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)db()->lastInsertId();
        flash('Đăng ký thành công. Chào mừng bạn đến với ' . SITE_NAME . '!');
        redirect('account.php');
    }
}
$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap"><form class="panel auth" method="post">
  <h1 style="font-size:2.4rem">Tạo tài khoản</h1>
  <?php if ($err): ?><div class="flash err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <div class="field"><label for="n">Họ và tên</label><input id="n" name="name" value="<?= e($f['name']) ?>" required autofocus></div>
  <div class="field"><label for="e">Email</label><input id="e" type="email" name="email" value="<?= e($f['email']) ?>" required></div>
  <div class="field"><label for="p">Số điện thoại</label><input id="p" name="phone" value="<?= e($f['phone']) ?>" inputmode="tel"></div>
  <div class="row2">
    <div class="field"><label for="pw">Mật khẩu</label><input id="pw" type="password" name="password" required minlength="6"></div>
    <div class="field"><label for="pw2">Nhập lại</label><input id="pw2" type="password" name="password2" required></div>
  </div>
  <button class="btn" style="width:100%">Đăng ký</button>
  <p class="small muted">Đã có tài khoản? <a href="<?= e(url('login.php')) ?>">Đăng nhập</a></p>
</form></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
