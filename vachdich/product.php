<?php
require __DIR__ . '/config.php';
$id = (int)get('id');
$p  = row('SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id = p.category_id WHERE p.product_id = ?', [$id]);
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap"><div class="empty" style="margin:40px 0"><h2>Không tìm thấy sản phẩm</h2><a class="btn dark" href="' . e(url('shop.php')) . '">Về trang sản phẩm</a></div></div>';
    require __DIR__ . '/includes/footer.php'; exit;
}
$variants = rows('SELECT * FROM product_variants WHERE product_id = ? ORDER BY CAST(size AS UNSIGNED), size, color', [$id]);
$sizes = []; $colors = [];
foreach ($variants as $v) { if ($v['size'] !== '' && !in_array($v['size'], $sizes)) $sizes[] = $v['size']; if ($v['color'] !== '' && !in_array($v['color'], $colors)) $colors[] = $v['color']; }
$js = json_encode(array_map(function ($v) { return ['id' => (int)$v['variant_id'], 'size' => (string)$v['size'], 'color' => (string)$v['color'], 'stock' => (int)$v['stock']]; }, $variants), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$shoe = $apparel = false;
foreach ($sizes as $s) { if (ctype_digit($s) && (int)$s >= 34 && (int)$s <= 50) $shoe = true; if (in_array(strtoupper($s), ['S', 'M', 'L', 'XL', 'XXL'])) $apparel = true; }
$related = rows('SELECT * FROM products WHERE category_id = ? AND product_id <> ? ORDER BY created_at DESC LIMIT 4', [$p['category_id'], $id]);
$pageTitle = $p['product_name'];
require __DIR__ . '/includes/header.php';
?>
<?= page_banner($p['product_name'], [$p['category_name'] => 'shop.php?cat=' . (int)$p['category_id']]) ?>
<div class="wrap">
  <div class="pd" style="padding-top:28px">
    <div><?= product_thumb($p) ?></div>
    <div>
      <?php if (!empty($p['brand'])): ?><a class="brand" href="<?= e(url('shop.php?brand=' . urlencode($p['brand']))) ?>"><?= e($p['brand']) ?></a><?php endif; ?>
      <h1><?= e($p['product_name']) ?></h1>
      <p class="price"><?= money($p['price']) ?></p>
      <p class="muted small">Mã hàng: SP<?= str_pad((string)$p['product_id'], 4, '0', STR_PAD_LEFT) ?></p>

      <?php if (!$variants): ?>
        <p class="flash err">Sản phẩm chưa có tùy chọn để bán. Vui lòng quay lại sau.</p>
      <?php else: ?>
      <form method="post" action="<?= e(url('cart.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <input type="hidden" name="variant_id" id="variant_id" value="">
        <?php if ($sizes): ?><div class="opt-label">Kích cỡ</div>
          <div class="opts"><?php foreach ($sizes as $s): ?><button type="button" class="opt" data-k="size" data-v="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?></div><?php endif; ?>
        <?php if ($colors): ?><div class="opt-label">Màu sắc</div>
          <div class="opts"><?php foreach ($colors as $s): ?><button type="button" class="opt" data-k="color" data-v="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?></div><?php endif; ?>
        <div id="stock" class="stock" aria-live="polite"></div>
        <div class="buy">
          <div class="qty"><button type="button" data-d="-1" aria-label="Giảm">−</button><input id="qty" name="qty" type="number" value="1" min="1" max="99" aria-label="Số lượng"><button type="button" data-d="1" aria-label="Tăng">+</button></div>
          <button class="btn" id="add-btn" type="submit" disabled>Thêm vào giỏ</button>
        </div>
      </form>
      <script>window.VARIANTS = <?= $js ?>;</script>
      <?php endif; ?>

      <details class="box" open><summary>Thông tin sản phẩm</summary><p><?= nl2br(e($p['description'])) ?></p></details>
      <?php if ($shoe): ?>
      <details class="box"><summary>Bảng quy đổi size giày</summary>
        <div class="tablewrap" style="margin-top:10px"><table class="tbl"><tr><th>Size (EU)</th><th>38</th><th>39</th><th>40</th><th>41</th><th>42</th><th>43</th><th>44</th></tr>
        <tr><td>Chiều dài chân (cm)</td><td>23.5</td><td>24.5</td><td>25</td><td>26</td><td>26.5</td><td>27.5</td><td>28</td></tr></table></div>
        <p class="small muted">Bảng tham khảo. Mỗi hãng có thể lệch khoảng 0,5 cm; nếu chân bè, hãy chọn lớn hơn một size.</p></details>
      <?php endif; ?>
      <?php if ($apparel): ?>
      <details class="box"><summary>Bảng quy đổi size quần áo</summary>
        <div class="tablewrap" style="margin-top:10px"><table class="tbl"><tr><th>Size</th><th>Chiều cao</th><th>Cân nặng</th></tr>
        <tr><td>S</td><td>155–165 cm</td><td>50–58 kg</td></tr><tr><td>M</td><td>165–172 cm</td><td>58–66 kg</td></tr>
        <tr><td>L</td><td>170–177 cm</td><td>66–74 kg</td></tr><tr><td>XL</td><td>175–182 cm</td><td>74–84 kg</td></tr></table></div>
        <p class="small muted">Bảng tham khảo. Hãy nhắn Zalo nếu bạn nằm giữa hai size.</p></details>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($variants): ?>
  <div class="sticky-buy" aria-hidden="true">
    <span class="price"><?= money($p['price']) ?></span>
    <button type="button" class="btn" id="sticky-add-btn" disabled>Thêm vào giỏ</button>
  </div>
  <?php endif; ?>

  <?php if ($related): ?>
  <section style="padding-bottom:56px"><h2>Cùng môn <?= e($p['category_name']) ?></h2>
    <div class="grid"><?php foreach ($related as $r) echo product_card($r); ?></div></section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
