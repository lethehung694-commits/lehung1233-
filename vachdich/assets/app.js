(function () {
  /* Menu di động */
  var mb = document.querySelector('[data-menu]');
  if (mb) mb.addEventListener('click', function () {
    var a = document.querySelector('.acct'), n = document.querySelector('.mainnav');
    var on = a.classList.toggle('open'); if (n) n.classList.toggle('open', on);
    mb.setAttribute('aria-expanded', on);
  });

  /* Bộ lọc tự gửi khi đổi */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  /* Số lượng */
  document.querySelectorAll('.qty').forEach(function (q) {
    var i = q.querySelector('input');
    q.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () {
        var max = parseInt(i.max || '99', 10), v = parseInt(i.value || '1', 10) + parseInt(b.dataset.d, 10);
        i.value = Math.max(parseInt(i.min || '1', 10), Math.min(max, v));
      });
    });
  });

  /* Yêu thích (lưu cục bộ trên trình duyệt, không cần đăng nhập) */
  var WKEY = 'vd_wishlist';
  function wishGet() { try { return JSON.parse(localStorage.getItem(WKEY) || '[]'); } catch (e) { return []; } }
  function wishSet(a) { try { localStorage.setItem(WKEY, JSON.stringify(a)); } catch (e) {} }
  function wishRender() {
    var list = wishGet();
    document.querySelectorAll('[data-wish]').forEach(function (b) {
      b.classList.toggle('on', list.indexOf(b.dataset.wish) > -1 || list.indexOf(parseInt(b.dataset.wish, 10)) > -1);
    });
    var c = document.getElementById('wish-count'); if (c) c.textContent = list.length;
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-wish]');
    if (!b) return;
    e.preventDefault();
    var id = b.dataset.wish, list = wishGet(), i = list.indexOf(id);
    if (i === -1) i = list.map(String).indexOf(id);
    if (i > -1) list.splice(i, 1); else list.push(id);
    wishSet(list); wishRender();
  });
  wishRender();

  /* Form đăng ký nhận tin (chưa nối email thật, chỉ báo đã ghi nhận) */
  var nl = document.getElementById('newsletter-form');
  if (nl) nl.addEventListener('submit', function (e) {
    e.preventDefault();
    var msg = nl.querySelector('.nl-msg'); if (msg) msg.textContent = 'Cảm ơn bạn! (form này cần nối dịch vụ email thật để hoạt động)';
    nl.reset();
  });

  /* Đếm ngược thời gian thực tới hạn dùng mã giảm giá */
  document.querySelectorAll('.countdown[data-end]').forEach(function (el) {
    var end = new Date(el.dataset.end).getTime();
    function tick() {
      var diff = end - Date.now();
      if (diff <= 0) { el.textContent = 'Đã hết hạn'; return; }
      var d = Math.floor(diff / 86400000), h = Math.floor(diff % 86400000 / 3600000), m = Math.floor(diff % 3600000 / 60000);
      el.textContent = d > 0 ? ('Còn ' + d + ' ngày ' + h + ' giờ') : ('Còn ' + h + ' giờ ' + m + ' phút');
    }
    tick(); setInterval(tick, 60000);
  });

  /* Hiệu ứng xuất hiện khi cuộn */
  var reveals = document.querySelectorAll('.reveal');
  if (reveals.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('show'); io.unobserve(en.target); } });
    }, { threshold: 0.15 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('show'); });
  }

  /* Thanh mua nhanh dính (di động) đồng bộ với form chính */
  var stickyBtn = document.getElementById('sticky-add-btn'), mainBtn = document.getElementById('add-btn');
  if (stickyBtn && mainBtn) {
    stickyBtn.addEventListener('click', function () { mainBtn.click(); });
    var syncSticky = function () { stickyBtn.disabled = mainBtn.disabled; };
    new MutationObserver(syncSticky).observe(mainBtn, { attributes: true, attributeFilter: ['disabled'] });
    syncSticky();
  }

  /* Chọn biến thể ở trang sản phẩm */
  var V = window.VARIANTS;
  if (!V) return;
  var sel = { size: null, color: null };
  var btns = document.querySelectorAll('.opt');
  var vid = document.getElementById('variant_id'), stockEl = document.getElementById('stock'),
      add = document.getElementById('add-btn'), qty = document.getElementById('qty');

  function find() {
    return V.find(function (v) { return (sel.size === null || v.size === sel.size) && (sel.color === null || v.color === sel.color); });
  }
  function refresh() {
    btns.forEach(function (b) {
      var k = b.dataset.k, val = b.dataset.v, other = k === 'size' ? 'color' : 'size';
      var ok = V.some(function (v) { return v[k] === val && v.stock > 0 && (sel[other] === null || v[other] === sel[other]); });
      b.disabled = !ok;
      b.classList.toggle('on', sel[k] === val);
    });
    var v = find();
    var ready = v && v.stock > 0;
    vid.value = v ? v.id : '';
    add.disabled = !ready;
    if (!v) { stockEl.textContent = 'Chọn đủ tùy chọn để xem tồn kho.'; stockEl.className = 'stock'; return; }
    qty.max = v.stock;
    if (parseInt(qty.value, 10) > v.stock) qty.value = Math.max(1, v.stock);
    stockEl.textContent = v.stock === 0 ? 'Hết hàng' : (v.stock <= 5 ? 'Chỉ còn ' + v.stock + ' sản phẩm' : 'Còn hàng');
    stockEl.className = 'stock' + (v.stock === 0 ? ' out' : (v.stock <= 5 ? ' low' : ''));
  }
  btns.forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.dataset.k; sel[k] = sel[k] === b.dataset.v ? null : b.dataset.v; refresh();
    });
  });
  /* chọn sẵn biến thể còn hàng đầu tiên */
  var first = V.find(function (v) { return v.stock > 0; });
  if (first) { sel.size = first.size; sel.color = first.color; }
  /* nếu chỉ có một giá trị cho size/color thì coi như đã chọn */
  ['size', 'color'].forEach(function (k) {
    var set = {}; V.forEach(function (v) { set[v[k]] = 1; });
    if (Object.keys(set).length === 1) sel[k] = V[0][k];
  });
  refresh();
})();
