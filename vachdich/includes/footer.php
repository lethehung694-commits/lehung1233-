</main>
<footer class="site-footer">
  <div class="wrap cols">
    <div>
      <div class="logo"><?= e(SITE_NAME) ?></div>
      <p>Đồ thể thao chính hãng cho bóng đá, cầu lông, gym, tennis, bóng rổ và bơi lội.</p>
    </div>
    <div>
      <h4>Hỗ trợ</h4>
      <a href="<?= e(url('page.php?p=shipping')) ?>">Vận chuyển &amp; thanh toán</a>
      <a href="<?= e(url('page.php?p=return')) ?>">Đổi trả &amp; hoàn tiền</a>
      <a href="<?= e(url('page.php?p=warranty')) ?>">Bảo hành</a>
      <a href="<?= e(url('page.php?p=privacy')) ?>">Bảo mật thông tin</a>
    </div>
    <div>
      <h4>Cửa hàng</h4>
      <a href="<?= e(url('page.php?p=about')) ?>">Giới thiệu</a>
      <a href="<?= e(url('page.php?p=contact')) ?>">Liên hệ</a>
      <a href="<?= e(url('page.php?p=blog')) ?>">Kinh nghiệm chọn đồ</a>
    </div>
    <div>
      <h4>Cần tư vấn size?</h4>
      <a href="<?= e(SITE_ZALO) ?>" target="_blank" rel="noopener">Nhắn Zalo</a>
      <a href="tel:<?= e(preg_replace('/\s+/', '', SITE_PHONE)) ?>"><?= e(SITE_PHONE) ?></a>
    </div>
  </div>
  <div class="wrap pay-row">
    <span class="pay-badge">COD</span><span class="pay-badge">QR</span><span class="pay-badge">MoMo</span><span class="pay-badge">ZaloPay</span><span class="pay-badge">ATM/Visa</span>
  </div>
  <div class="wrap copy">© <?= date('Y') ?> <?= e(SITE_NAME) ?></div>
</footer>
<a class="zalo-fab" href="<?= e(SITE_ZALO) ?>" target="_blank" rel="noopener">Chat Zalo</a>
<script src="<?= e(url('assets/app.js')) ?>"></script>
</body>
</html>
