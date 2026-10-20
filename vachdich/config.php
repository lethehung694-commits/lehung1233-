<?php
/* ===== Cấu hình ===== */
session_start();
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

const DB_HOST = '127.0.0.1';
const DB_NAME = 'web_the_thao';
const DB_USER = 'root';
const DB_PASS = '';            // XAMPP mặc định để trống

const SITE_NAME      = 'Vạch Đích';
const SITE_PHONE     = '0900 000 000';   // đổi thành số thật
const SITE_EMAIL     = 'hotro@vachdich.vn';
const SITE_ZALO      = 'https://zalo.me/0900000000';
const SITE_ADDRESS   = 'Bắc Ninh, Việt Nam';
const FREE_SHIP_FROM = 1000000;  // miễn phí vận chuyển từ mức này
const SHIP_FAST      = 35000;
const SHIP_SAVING    = 20000;

// ===== Chuyển khoản QR (VietQR) =====
// Điền đúng số tài khoản thật của bạn vào BANK_ACCOUNT_NO rồi lưu lại file này.
// Danh sách mã BIN các ngân hàng: https://vietqr.io/danh-sach-ngan-hang
const BANK_NAME        = 'Vietcombank';
const BANK_BIN         = '970436';                 // Mã BIN Vietcombank
const BANK_ACCOUNT_NO  = '1058798517';
const BANK_ACCOUNT_NAME = 'LE THE HUNG';
const PER_PAGE       = 12;
const STOCK_SHOE_IMG   = 'https://images.unsplash.com/photo-1615743472612-93b21e520fad?auto=format&fit=crop&w=1200&q=80'; // Solen Feyissa, Unsplash License
const HERO_ATHLETE_IMG = 'https://images.unsplash.com/photo-1779104782112-3c890f523ec5?auto=format&fit=crop&w=1400&q=75'; // Mali Seward, Unsplash License
// Ảnh mẫu theo môn thể thao (category_id) - tất cả đều free, Unsplash License, dùng khi sản phẩm chưa có ảnh riêng
const CATEGORY_STOCK_IMG = [
    1 => 'https://images.unsplash.com/photo-1520804588595-ac2328f38c4a?auto=format&fit=crop&w=1200&q=80', // Bóng đá - Wuilmar Matias-Morales
    2 => 'https://images.unsplash.com/photo-1708312604109-16c0be9326cd?auto=format&fit=crop&w=1200&q=80', // Cầu lông - Allison Saeng
    3 => 'https://images.unsplash.com/photo-1493690283958-32df2c86326e?auto=format&fit=crop&w=1200&q=80', // Gym & Running - Cyril Saulnier
    4 => 'https://images.unsplash.com/photo-1669139185686-5e0b2355a104?auto=format&fit=crop&w=1200&q=80', // Tennis - Swanky Fella
    5 => 'https://images.unsplash.com/photo-1540848893531-5eece9a5fa64?auto=format&fit=crop&w=1200&q=80', // Bóng rổ - Marcel Schreiber
    6 => 'https://images.unsplash.com/photo-1567970851135-571097364150?auto=format&fit=crop&w=1200&q=80', // Bơi lội - AllGo
];
// Ảnh mẫu riêng cho một số LOẠI sản phẩm cụ thể (khớp theo từ khóa trong tên), ưu tiên hơn ảnh chung của danh mục.
// Tất cả đều miễn phí bản quyền (Unsplash License), dùng khi sản phẩm chưa có ảnh riêng.
const KEYWORD_STOCK_IMG = [
    'găng tay'  => 'assets/uploads/gang-tay-thu-mon.png', // Găng tay thủ môn - ảnh do chủ shop tự thiết kế
    'thảm'      => 'assets/uploads/tham-yoga.png', // Thảm yoga - ảnh do chủ shop tự chụp
    'đai lưng'  => 'assets/uploads/dai-lung-gym.png', // Đai tập gym - ảnh do chủ shop tự chụp và thiết kế
    'kính bơi'  => 'assets/uploads/kinh-boi.png', // Kính bơi - ảnh do chủ shop cung cấp
    'mercurial' => 'assets/uploads/giay-mercurial.png', // Giày Mercurial - ảnh do chủ shop tự chụp
    'bóng rổ'   => 'https://images.unsplash.com/photo-1625038627556-966ed84eaa97?auto=format&fit=crop&w=1200&q=80', // Bóng rổ - the blowup
    'bóng tennis' => 'https://images.unsplash.com/photo-1627314387807-df615e8567de?auto=format&fit=crop&w=1200&q=80', // Bóng tennis - Matthias David
    'vợt tennis' => 'https://images.unsplash.com/photo-1615326882458-e0d45b097f55?auto=format&fit=crop&w=1200&q=80', // Vợt tennis - Girl with red hat
    'quả cầu'   => 'assets/uploads/cau-long.png', // Quả cầu lông - ảnh do chủ shop tự chụp (dùng "quả cầu" để không trùng với vợt cầu lông)
];

$__root = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__dir  = str_replace('\\', '/', __DIR__);
define('BASE', ($__root !== '' && strpos($__dir, $__root) === 0) ? substr($__dir, strlen($__root)) : '');

/* ===== Database ===== */
function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('Không kết nối được database: ' . htmlspecialchars($ex->getMessage()));
        }
    }
    return $pdo;
}
function q($sql, $params = []) { $st = db()->prepare($sql); $st->execute($params); return $st; }
function rows($sql, $params = []) { return q($sql, $params)->fetchAll(); }
function row($sql, $params = []) { $r = q($sql, $params)->fetch(); return $r ?: null; }
function val($sql, $params = []) { $r = q($sql, $params)->fetchColumn(); return $r === false ? null : $r; }

/* ===== Tiện ích ===== */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n) { return number_format((float)$n, 0, ',', '.') . '₫'; }
function url($p = '') { return BASE . '/' . ltrim($p, '/'); }
function redirect($p) { header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p))); exit; }
function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function csrf_check() {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) { http_response_code(400); exit('Phiên làm việc không hợp lệ, hãy tải lại trang.'); }
}
function post($k, $d = '') { return trim((string)($_POST[$k] ?? $d)); }
function get($k, $d = '') { return trim((string)($_GET[$k] ?? $d)); }

function current_user() {
    static $u = false;
    if ($u === false) $u = empty($_SESSION['uid']) ? null : row('SELECT * FROM users WHERE user_id=?', [$_SESSION['uid']]);
    return $u;
}
function require_login() {
    if (!current_user()) { flash('Hãy đăng nhập để tiếp tục.', 'err'); redirect('login.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME']))); }
}
function require_admin() {
    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== 'admin') { http_response_code(403); flash('Bạn cần đăng nhập bằng tài khoản quản trị.', 'err'); redirect('login.php'); }
}

/* ===== Danh mục / sản phẩm ===== */
function categories() { static $c = null; if ($c === null) $c = rows('SELECT * FROM categories ORDER BY category_id'); return $c; }
function cat_emoji($id) { $m = [1 => '⚽', 2 => '🏸', 3 => '🏋️', 4 => '🎾', 5 => '🏀', 6 => '🏊']; return $m[(int)$id] ?? '🏅'; }
function cat_name($id) { foreach (categories() as $c) if ((int)$c['category_id'] === (int)$id) return $c['category_name']; return ''; }
function is_shoe($p) { return mb_stripos((string)($p['product_name'] ?? ''), 'giày') === 0; }
function shoe_photo($cls = '') {
    return '<img class="' . e($cls) . '" src="' . e(STOCK_SHOE_IMG) . '" alt="Giày thể thao" loading="lazy">';
}
function category_photo($catId, $cls = '', $alt = '') {
    $src = CATEGORY_STOCK_IMG[(int)$catId] ?? null;
    if (!$src) return null;
    return '<img class="' . e($cls) . '" src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy">';
}
// Ảnh riêng theo từ khóa trong tên sản phẩm (ưu tiên cao nhất), trả về null nếu không có từ khóa nào khớp.
function keyword_photo($p, $cls = '') {
    $name = mb_strtolower((string)($p['product_name'] ?? ''), 'UTF-8');
    foreach (KEYWORD_STOCK_IMG as $kw => $src) {
        if (mb_strpos($name, $kw) !== false) {
            $full = (stripos($src, 'http://') === 0 || stripos($src, 'https://') === 0) ? $src : url($src);
            return '<img class="' . e($cls) . '" src="' . e($full) . '" alt="' . e($p['product_name']) . '" loading="lazy">';
        }
    }
    return null;
}
// Trả về ảnh mẫu đúng nhất cho 1 sản phẩm: theo từ khóa trong tên trước, không có thì theo danh mục.
function product_stock_photo($p, $cls = '') {
    return keyword_photo($p, $cls) ?: category_photo($p['category_id'] ?? 0, $cls, $p['product_name'] ?? '');
}
function product_thumb($p, $cls = '') {
    $f = basename((string)($p['image_url'] ?? ''));
    if ($f !== '' && is_file(__DIR__ . '/uploads/products/' . $f))
        return '<img class="thumb ' . e($cls) . '" src="' . e(url('uploads/products/' . $f)) . '" alt="' . e($p['product_name']) . '" loading="lazy">';
    if (is_shoe($p)) $inner = keyword_photo($p, 'stock-shoe') ?: shoe_photo('stock-shoe');
    else $inner = product_stock_photo($p, 'stock-shoe') ?: '<span>' . cat_emoji($p['category_id'] ?? 0) . '</span>';
    return '<div class="thumb ph ' . e($cls) . '" role="img" aria-label="' . e($p['product_name']) . '">' . $inner . '</div>';
}
function product_card($p) {
    $meta = implode(' / ', array_filter([(string)($p['brand'] ?? ''), cat_name($p['category_id'] ?? 0)]));
    $badge = '';
    if (isset($p['is_new']) && $p['is_new']) $badge = '<span class="tag tag-new">Mới</span>';
    elseif (isset($p['is_best']) && $p['is_best']) $badge = '<span class="tag tag-best">Bán chạy</span>';
    if (array_key_exists('stock_total', $p) && $p['stock_total'] !== null) {
        $st = (int)$p['stock_total'];
        if ($st === 0) $badge = '<span class="tag tag-out">Hết hàng</span>';
        elseif ($st <= 5) $badge = '<span class="tag tag-low">Sắp hết – còn ' . $st . '</span>';
    }
    $wish = '<button type="button" class="wish-btn" data-wish="' . (int)$p['product_id'] . '" aria-label="Yêu thích" onclick="event.preventDefault()">'
          . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7-4.35-9.5-8.8C.8 7.6 2.6 4 6 4c2 0 3.3 1 6 3.6C14.7 5 16 4 18 4c3.4 0 5.2 3.6 3.5 7.2C19 15.65 12 20 12 20z"/></svg></button>';
    $h  = '<a class="card" data-cat="' . (int)$p['category_id'] . '" href="' . e(url('product.php?id=' . (int)$p['product_id'])) . '">';
    $h .= '<div class="thumb-wrap">' . product_thumb($p) . $badge . $wish . '</div>';
    $h .= '<div class="card-body"><span class="meta">' . e($meta) . '</span>';
    $h .= '<h3>' . e($p['product_name']) . '</h3><p class="price">' . money($p['price']) . '</p></div></a>';
    return $h;
}
function status_label($s) {
    $m = ['pending' => 'Đang xử lý', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao', 'cancelled' => 'Đã hủy'];
    return $m[$s] ?? $s;
}
function order_code($id) { return 'VD' . str_pad((string)$id, 6, '0', STR_PAD_LEFT); }

/* ===== Giỏ hàng ===== */
function cart() { return $_SESSION['cart'] ?? []; }
function cart_count() { return array_sum(cart()); }
function cart_lines() {
    $cart = cart();
    if (!$cart) return [];
    $ids = array_map('intval', array_keys($cart));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $rs = rows("SELECT v.variant_id, v.size, v.color, v.stock, p.product_id, p.product_name, p.price, p.image_url, p.category_id
                FROM product_variants v JOIN products p ON p.product_id = v.product_id WHERE v.variant_id IN ($in)", $ids);
    $lines = [];
    foreach ($rs as $r) {
        $r['qty']   = (int)$cart[$r['variant_id']];
        $r['total'] = $r['qty'] * (float)$r['price'];
        $lines[] = $r;
    }
    return $lines;
}
function cart_subtotal($lines) { return array_sum(array_column($lines, 'total')); }
function shipping_fee($method, $subtotal) {
    if ($subtotal <= 0 || $subtotal >= FREE_SHIP_FROM) return 0;
    return $method === 'saving' ? SHIP_SAVING : SHIP_FAST;
}
/* Trả về [discount, message, voucherRow] */
function voucher_check($code, $subtotal) {
    $v = row('SELECT * FROM vouchers WHERE code=?', [$code]);
    $now = date('Y-m-d H:i:s');
    if (!$v || !(int)$v['is_active']) return [0, 'Mã giảm giá không tồn tại hoặc đã tắt.', null];
    if ($now < $v['start_date'] || $now > $v['end_date']) return [0, 'Mã giảm giá đã hết hạn hoặc chưa đến ngày áp dụng.', null];
    if ((int)$v['used_count'] >= (int)$v['usage_limit']) return [0, 'Mã giảm giá đã hết lượt dùng.', null];
    if ($subtotal < (float)$v['min_order_amount']) return [0, 'Đơn hàng cần từ ' . money($v['min_order_amount']) . ' để dùng mã này.', null];
    $d = $v['discount_type'] === 'percent' ? $subtotal * (float)$v['discount_value'] / 100 : (float)$v['discount_value'];
    if ($v['max_discount_amount'] !== null && $d > (float)$v['max_discount_amount']) $d = (float)$v['max_discount_amount'];
    return [min($d, $subtotal), 'OK', $v];
}

function payment_label($m) {
    $l = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'qr' => 'Chuyển khoản QR', 'momo' => 'Ví MoMo', 'zalopay' => 'ZaloPay', 'card' => 'Thẻ ATM / Credit'];
    return $l[$m] ?? $m;
}
function ship_label($m) { return $m === 'saving' ? 'Giao tiết kiệm' : 'Giao nhanh'; }


/* ===== Bài viết kinh nghiệm (dùng chung ở trang chủ và trang blog) ===== */
function blog_posts() {
    return [
        ['slug' => 'vot-cau-long', 'title' => 'Cách chọn vợt cầu lông cho người mới bắt đầu', 'cat' => 2,
         'body' => 'Người mới nên chọn vợt nhẹ (khoảng 4U, 80–84 g), điểm cân bằng hơi nặng đầu hoặc trung tính và thân vợt dẻo vừa để dễ đánh mà không mỏi cổ tay. Vợt quá cứng hoặc quá nặng đầu dễ gây chấn thương khi kỹ thuật chưa vững. Hãy chọn cỡ cán vừa tay và căng dây ở mức trung bình thấp trước, tăng dần khi đã quen.'],
        ['slug' => 'giay-chay-bo', 'title' => 'Chọn giày chạy bộ: đệm êm hay phản hồi nhanh?', 'cat' => 3,
         'body' => 'Giày đệm êm hợp chạy dài, chạy chậm và người nặng cân vì giảm chấn cho khớp. Giày phản hồi nhanh, nhẹ hợp tập tốc độ và thi đấu. Hãy thử vào cuối ngày khi chân đã nở, chừa khoảng một ngón tay phía trước mũi giày và chạy thử vài bước nếu có thể.'],
        ['slug' => 'do-chan', 'title' => 'Đo chân đúng cách để chọn size giày', 'cat' => 5,
         'body' => 'Đứng thẳng trên tờ giấy, đánh dấu điểm dài nhất của bàn chân rồi đo bằng thước. Đo cả hai chân và lấy chân dài hơn, cộng thêm 0,5–1 cm cho giày thể thao. So chiều dài này với bảng size ở trang sản phẩm. Nếu chân bè hoặc mu cao, hãy chọn lớn hơn một size hoặc nhắn Zalo để được tư vấn.'],
    ];
}

/* ===== Banner đầu trang con (đồng bộ giao diện các trang) ===== */
function page_banner($title, $crumbs = []) {
    $h = '<section class="page-banner"><div class="wrap"><nav class="pb-crumbs" aria-label="Điều hướng"><a href="' . e(url()) . '">Trang chủ</a>';
    foreach ($crumbs as $label => $href) {
        if (is_int($label)) { $h .= ' <span>/</span> ' . e($href); continue; }
        $h .= ' <span>/</span> <a href="' . e(url($href)) . '">' . e($label) . '</a>';
    }
    $h .= '</nav><h1>' . e($title) . '</h1></div></section>';
    return $h;
}


/* ===== QR chuyển khoản VietQR (tự điền số tiền + nội dung theo đơn hàng) ===== */
function vietqr_configured() {
    return BANK_ACCOUNT_NO !== '' && BANK_ACCOUNT_NO !== 'DIEN_SO_TAI_KHOAN_VAO_DAY';
}
function vietqr_url($amount, $note) {
    $base = 'https://img.vietqr.io/image/' . BANK_BIN . '-' . BANK_ACCOUNT_NO . '-compact2.png';
    return $base . '?amount=' . (int)$amount . '&addInfo=' . rawurlencode($note) . '&accountName=' . rawurlencode(BANK_ACCOUNT_NAME);
}
