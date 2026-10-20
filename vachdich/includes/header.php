<?php
require_once __DIR__ . '/../config.php';
$cats = categories();
$me   = current_user();
$pageTitle = $pageTitle ?? 'Đồ thể thao chính hãng';
$flash = flash();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<a class="skip" href="#main">Bỏ qua điều hướng</a>
<div class="topbar">
  <div class="wrap topbar-in">
    <span>🚚 Miễn phí vận chuyển cho đơn từ <?= money(FREE_SHIP_FROM) ?></span>
    <span>📞 Hotline tư vấn: <a href="tel:<?= e(preg_replace('/\s+/', '', SITE_PHONE)) ?>"><?= e(SITE_PHONE) ?></a></span>
    <span>↩ Đổi size trong 7 ngày</span>
  </div>
</div>
<header class="site-header">
  <div class="wrap bar">
    <a class="logo" href="<?= e(url()) ?>"><?= e(SITE_NAME) ?></a>
    <nav class="mainnav" aria-label="Điều hướng chính">
      <a href="<?= e(url()) ?>">Trang chủ</a>
      <div class="dd">
        <a class="dd-t" href="<?= e(url('shop.php')) ?>">Danh mục</a>
        <div class="dd-menu">
          <?php foreach ($cats as $c): ?><a href="<?= e(url('shop.php?cat=' . (int)$c['category_id'])) ?>"><?= e($c['category_name']) ?></a><?php endforeach; ?>
          <a href="<?= e(url('shop.php')) ?>">Tất cả sản phẩm</a>
        </div>
      </div>
      <a href="<?= e(url('new.php')) ?>">Hàng mới</a>
      <a href="<?= e(url('page.php?p=blog')) ?>">Kinh nghiệm</a>
      <a href="<?= e(url('page.php?p=contact')) ?>">Liên hệ</a>
    </nav>
    <form class="search" action="<?= e(url('shop.php')) ?>" method="get" role="search">
      <input type="search" name="q" value="<?= e(get('q')) ?>" placeholder="Tìm sản phẩm…" aria-label="Tìm sản phẩm">
      <button type="submit" aria-label="Tìm">Tìm</button>
    </form>
    <nav class="acct" aria-label="Tài khoản">
      <?php if ($me): ?>
        <?php if (($me['role'] ?? '') === 'admin'): ?><a href="<?= e(url('admin/')) ?>">Quản trị</a><?php endif; ?>
        <a href="<?= e(url('account.php')) ?>"><?= e($me['full_name'] ?: 'Tài khoản') ?></a>
        <a href="<?= e(url('logout.php')) ?>">Đăng xuất</a>
      <?php else: ?>
        <a href="<?= e(url('login.php')) ?>">Đăng nhập</a>
      <?php endif; ?>
      <a class="wish-link" id="wish-link" href="<?= e(url('shop.php')) ?>">Yêu thích <b id="wish-count">0</b></a>
      <a class="cart-link" href="<?= e(url('cart.php')) ?>">Giỏ hàng <b><?= cart_count() ?></b></a>
    </nav>
    <button class="menu-btn" aria-label="Mở menu" aria-expanded="false" data-menu>Menu</button>
  </div>
</header>
<main id="main">
<?php if ($flash): ?><div class="wrap"><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div></div><?php endif; ?>
