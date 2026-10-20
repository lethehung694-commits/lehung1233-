<?php
require __DIR__ . '/config.php';
$err = '';
$next = get('next', 'account.php');
if (!preg_match('/^[a-z_]+\.php$/', $next)) $next = 'account.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower(post('email'))]);
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['user_id'];
        redirect(($u['role'] ?? '') === 'admin' && $next === 'account.php' ? 'admin/' : $next);
    }
    $err = 'Email hoặc mật khẩu chưa đúng.';
}
$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap"><form class="panel auth" method="post">
  <h1 style="font-size:2.4rem">Đăng nhập</h1>
  <?php if ($err): ?><div class="flash err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(post('email')) ?>" required autofocus></div>
  <div class="field"><label for="pw">Mật khẩu</label><input id="pw" type="password" name="password" required></div>
  <button class="btn" style="width:100%">Đăng nhập</button>
  <p class="small muted">Chưa có tài khoản? <a href="<?= e(url('register.php')) ?>">Đăng ký</a></p>
</form></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
