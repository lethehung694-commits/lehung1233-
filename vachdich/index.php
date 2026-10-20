<?php
require __DIR__ . '/config.php';
$pageTitle = 'Đồ thể thao chính hãng';

$newArrivals = rows('SELECT p.*, (p.created_at >= DATE_SUB(NOW(), INTERVAL 21 DAY)) AS is_new,
                     (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.product_id) AS stock_total
                     FROM products p ORDER BY p.created_at DESC, p.product_id DESC LIMIT 5');
$flashItems = rows('SELECT p.* FROM products p
              LEFT JOIN (SELECT product_id, SUM(quantity) s FROM order_details GROUP BY product_id) x ON x.product_id = p.product_id
              ORDER BY COALESCE(x.s,0) DESC, p.created_at DESC, p.product_id DESC LIMIT 4');
$vouchers = rows('SELECT * FROM vouchers WHERE is_active=1 AND start_date <= NOW() AND end_date >= NOW() AND used_count < usage_limit ORDER BY end_date LIMIT 1');
$flashVoucher = $vouchers[0] ?? null;

$catCounts = array_column(rows('SELECT category_id, COUNT(*) n FROM products GROUP BY category_id'), 'n', 'category_id');
$statProducts  = (int)val('SELECT COUNT(*) FROM products');
$statCats      = count(categories());
$statDelivered = (int)val("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
$catIcons = [
    1 => '<circle cx="12" cy="12" r="9"/><path d="M12 7l4 3-1.5 4.5h-5L8 10z"/>',
    2 => '<path d="M6 18L18 6M14 3l7 7-3 3-7-7z"/><circle cx="6" cy="18" r="2.4"/>',
    3 => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="12" r="2.5"/><path d="M8.5 12h7"/>',
    4 => '<circle cx="9" cy="9" r="6"/><path d="M13.2 13.2L20 20M9 6v6l4 2"/>',
    5 => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13 13 0 0 1 0 18 13 13 0 0 1 0-18Z"/>',
    6 => '<path d="M3 16c1.5-2 3-2 4.5 0s3 2 4.5 0 3-2 4.5 0 3 2 4.5 0"/><circle cx="12" cy="8" r="4"/>',
];
// 4 ô quảng bá kiểu lệch (bento): 1 ô lớn + 3 ô nhỏ, dùng danh mục và ảnh có sẵn
$promoTiles = [
    'large' => ['cat' => 3, 'title' => 'BỀN BỈ MỖI NGÀY', 'sub' => 'Đồ tập gym & chạy bộ cho mọi buổi luyện tập', 'href' => 'shop.php?cat=3'],
    'top'   => ['cat' => 5, 'title' => 'SẢN PHẨM BÁN CHẠY', 'sub' => 'Được nhiều khách hàng lựa chọn nhất', 'href' => 'shop.php?sort=popular'],
    'b1'    => ['cat' => 1, 'title' => 'VÀO SÂN TỰ TIN', 'sub' => 'Bóng đá', 'href' => 'shop.php?cat=1'],
    'b2'    => ['cat' => 6, 'title' => 'CHINH PHỤC ĐƯỜNG BƠI', 'sub' => 'Bơi lội', 'href' => 'shop.php?cat=6'],
];
$blog = array_slice(blog_posts(), 0, 3);
$brands = rows("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand");

require __DIR__ . '/includes/header.php';
?>
<section class="hero-dark hero-v3">
  <div class="wrap hero-v3-in">
    <div class="hero-copy">
      <p class="eyebrow">Bộ sưu tập mùa mới</p>
      <h1 class="stack-title"><span class="outline">COLLECTION</span><span class="solid">Đồ Thể Thao</span></h1>
      <p class="price">Trang bị thể thao chính hãng, giá chỉ từ <b><?= money(190000) ?></b></p>
      <a class="btn" href="<?= e(url('shop.php')) ?>">Mua ngay</a>
    </div>
    <div class="hero-shoe-wrap"><img class="hero-shoe-img" src="<?= e(STOCK_SHOE_IMG) ?>" alt="Giày thể thao"></div>
  </div>
</section>

<section class="trust-row">
  <div class="wrap trust-row-in">
    <div class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/></svg><span>Miễn phí ship từ <?= money(FREE_SHIP_FROM) ?></span></div>
    <div class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4h16v6a8 8 0 0 1-8 8 8 8 0 0 1-8-8V4z"/><path d="M9 11l2 2 4-4"/></svg><span>Đổi trả trong 7 ngày</span></div>
    <div class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 15v-3a8 8 0 0 1 16 0v3"/><path d="M4 15a2 2 0 0 0 2 2h1v-5H5a1 1 0 0 0-1 1z"/><path d="M20 15a2 2 0 0 1-2 2h-1v-5h1a1 1 0 0 1 1 1z"/></svg><span>Tư vấn Zalo/Hotline</span></div>
    <div class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l2.6 5.3 5.9.8-4.3 4.1 1 5.8L12 16l-5.2 3 1-5.8-4.3-4.1 5.9-.8z"/></svg><span>Hàng chính hãng 100%</span></div>
  </div>
</section>

<section class="section white">
  <div class="wrap">
    <div class="feat-head reveal">
      <div class="feat-head-line"><span></span><p class="eyebrow-label" style="margin:0">Vừa cập bến</p><span></span></div>
      <h2>Hàng mới về</h2>
    </div>
    <div class="grid reveal"><?php foreach ($newArrivals as $p) echo product_card($p); ?></div>
    <p style="text-align:center;margin-top:32px" class="reveal"><a class="btn ghost" style="border-radius:0" href="<?= e(url('new.php')) ?>">Xem tất cả →</a></p>
  </div>
</section>

<?php if ($flashVoucher): ?>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="flash-sale reveal">
      <div class="fs-info">
        <span class="fs-bolt">⚡ FLASH SALE</span>
        <h3>Ưu đãi trong thời gian có hạn</h3>
        <p>Nhập mã <b><?= e($flashVoucher['code']) ?></b> —
          <?= $flashVoucher['discount_type'] === 'percent' ? 'giảm ' . rtrim(rtrim($flashVoucher['discount_value'], '0'), '.') . '%' : 'giảm ' . money($flashVoucher['discount_value']) ?>
          cho đơn từ <?= money($flashVoucher['min_order_amount']) ?></p>
        <div class="countdown fs-count" data-end="<?= e(date('c', strtotime($flashVoucher['end_date']))) ?>">--</div>
        <a class="btn" style="border-radius:0;background:#fff;color:#c1121f" href="<?= e(url('shop.php')) ?>">Mua ngay →</a>
      </div>
      <div class="fs-items">
        <?php foreach ($flashItems as $p): ?>
          <a class="fs-item" href="<?= e(url('product.php?id=' . (int)$p['product_id'])) ?>">
            <?= product_thumb($p) ?><span><?= e(mb_strimwidth($p['product_name'], 0, 26, '…')) ?></span><b><?= money($p['price']) ?></b>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section white">
  <div class="wrap">
    <p class="eyebrow-label reveal">Chọn theo môn bạn chơi</p>
    <h2 class="reveal">Danh mục nổi bật</h2>
    <div class="cat-scroll reveal">
      <?php foreach (categories() as $c): ?>
        <a class="cat-circle" href="<?= e(url('shop.php?cat=' . (int)$c['category_id'])) ?>">
          <span class="cc-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $catIcons[(int)$c['category_id']] ?? '<circle cx="12" cy="12" r="9"/>' ?></svg></span>
          <span class="cc-name"><?= e($c['category_name']) ?></span>
          <span class="cc-count"><?= (int)($catCounts[$c['category_id']] ?? 0) ?> sản phẩm</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap bento-grid reveal">
    <?php foreach ($promoTiles as $slot => $t): $img = CATEGORY_STOCK_IMG[$t['cat']] ?? STOCK_SHOE_IMG; ?>
      <a class="bento-tile bt-<?= e($slot) ?>" href="<?= e(url($t['href'])) ?>" style="background-image:linear-gradient(0deg,rgba(0,0,0,.6),rgba(0,0,0,.15)),url('<?= e($img) ?>')">
        <div><h3><?= e($t['title']) ?></h3><p><?= e($t['sub']) ?></p><span class="tile-cta">Mua ngay →</span></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section white">
  <div class="wrap">
    <div class="sec-head reveal"><h2>Kinh nghiệm chọn đồ</h2><a href="<?= e(url('page.php?p=blog')) ?>">Xem tất cả →</a></div>
    <div class="story-grid reveal">
      <?php foreach ($blog as $b): $img = CATEGORY_STOCK_IMG[$b['cat']] ?? STOCK_SHOE_IMG; ?>
        <a class="story-card" href="<?= e(url('page.php?p=blog')) ?>">
          <img src="<?= e($img) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
          <div class="story-body"><h3><?= e($b['title']) ?></h3><p><?= e(mb_strimwidth($b['body'], 0, 90, '…')) ?></p><span class="story-more">Đọc thêm →</span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="newsletter">
  <div class="wrap nl-in reveal">
    <div><h2>Tham gia cộng đồng Vạch Đích</h2><p>Nhận thông báo ưu đãi và hàng mới về sớm nhất.</p></div>
    <form id="newsletter-form" class="nl-form">
      <input type="email" required placeholder="Email của bạn"><button class="btn" type="submit">Đăng ký</button>
    </form>
  </div>
  <p class="wrap nl-msg small"></p>
</section>

<?php if ($brands): ?>
<section class="section white" style="padding:34px 0">
  <div class="wrap brand-strip reveal">
    <?php foreach ($brands as $b): ?><a href="<?= e(url('shop.php?brand=' . urlencode($b['brand']))) ?>"><?= e(mb_strtoupper($b['brand'], 'UTF-8')) ?></a><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
