<?php
require_once __DIR__ . '/../config.php';
require_admin();
function admin_head($title, $active) {
    $flash = flash();
    $nav = ['index' => 'Tổng quan', 'products' => 'Sản phẩm & kho', 'categories' => 'Danh mục', 'orders' => 'Đơn hàng', 'customers' => 'Khách hàng', 'vouchers' => 'Khuyến mãi'];
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . ' | Quản trị ' . e(SITE_NAME) . '</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="' . e(url('assets/style.css')) . '"></head><body><div class="admin"><aside><a class="logo" href="' . e(url('admin/')) . '">' . e(SITE_NAME) . '</a>';
    foreach ($nav as $k => $v) echo '<a class="' . ($k === $active ? 'on' : '') . '" href="' . e(url('admin/' . ($k === 'index' ? '' : $k . '.php'))) . '">' . e($v) . '</a>';
    echo '<a href="' . e(url()) . '" style="margin-top:12px">← Xem cửa hàng</a><a href="' . e(url('logout.php')) . '">Đăng xuất</a></aside><section>';
    if ($flash) echo '<div class="flash ' . e($flash[1]) . '" role="status">' . e($flash[0]) . '</div>';
    echo '<h1 style="font-size:2.4rem">' . e($title) . '</h1>';
}
function admin_foot() { echo '</section></div><script src="' . e(url('assets/app.js')) . '"></script></body></html>'; }
