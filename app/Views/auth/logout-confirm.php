<?php
use App\Core\View;
?>
<section class="home-container">
  <header class="home-hero">
    <p class="home-kicker">ĐĂNG XUẤT</p>
    <h1>Bạn muốn đăng xuất khỏi hệ thống?</h1>
    <p>
      <?php if (($role ?? '') === 'admin' || ($role ?? '') === 'manager'): ?>
        Bạn đang ở khu vực quản lý. Sau khi đăng xuất, bạn sẽ quay về trang chủ.
      <?php else: ?>
        Phiên hiện tại sẽ kết thúc ngay khi xác nhận.
      <?php endif; ?>
    </p>
  </header>

  <section class="home-feature">
    <div class="review-box" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
      <form method="post" action="/logout" class="logout-form" style="display:inline-block; margin:0;">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
        <button type="submit" class="home-btn logout-button">Xác nhận đăng xuất</button>
      </form>
      <a class="home-btn home-btn-outline" href="/">Quay lại</a>
    </div>
  </section>
</section>