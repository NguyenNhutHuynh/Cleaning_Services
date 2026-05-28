<?php
use App\Core\Csrf;
use App\Core\View;
?>
<section class="home-container">
  <header class="home-hero">
    <p class="home-kicker">WORKER • DUYỆT TÀI KHOẢN</p>
    <?php $st = (string)($status ?? 'pending'); ?>
    <?php if ($st === 'rejected'): ?>
      <h1>Tài khoản đã bị từ chối</h1>
      <p>Lý do: <strong><?= View::e($reason ?? 'Liên hệ hỗ trợ để biết thêm chi tiết.') ?></strong></p>
    <?php elseif ($st === 'locked'): ?>
      <h1>Tài khoản đã bị khóa</h1>
      <p>Lý do: <strong><?= View::e($reason ?? 'Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ.') ?></strong></p>
    <?php elseif ($st === 'deleted'): ?>
      <h1>Tài khoản đã bị vô hiệu hóa</h1>
      <p>Lý do: <strong><?= View::e($reason ?? 'Tài khoản đã bị xóa/vô hiệu hóa.') ?></strong></p>
    <?php else: ?>
      <h1>Tài khoản đang chờ duyệt</h1>
      <p>Chúng tôi sẽ thông báo khi tài khoản của bạn được phê duyệt.</p>
    <?php endif; ?>
  </header>

  <section class="home-feature">
    <h2>Tiếp theo</h2>
    <div class="review-box">
      <?php if ($st === 'rejected'): ?>
        <p>• Tài khoản của bạn đã bị từ chối. Vui lòng xem lại thông tin đăng ký hoặc liên hệ hỗ trợ để được xem xét lại.</p>
      <?php elseif ($st === 'locked'): ?>
        <p>• Tài khoản của bạn đã bị khóa bởi quản trị viên. Vui lòng liên hệ hỗ trợ để biết thêm chi tiết.</p>
      <?php elseif ($st === 'deleted'): ?>
        <p>• Tài khoản của bạn đã bị vô hiệu hóa. Nếu đây là nhầm lẫn, vui lòng liên hệ hỗ trợ.</p>
      <?php else: ?>
        <p>• Thời gian duyệt thường trong 1-2 ngày làm việc.</p>
        <p>• Bạn vẫn có thể xem dịch vụ và liên hệ hỗ trợ.</p>
      <?php endif; ?>
      <div class="hero-actions" style="justify-content:flex-start;">
        <?php if ($st === 'rejected' || $st === 'locked' || $st === 'deleted'): ?>
          <!-- <a class="home-btn" href="/contact">Liên hệ hỗ trợ</a> -->
          <form method="post" action="/logout" class="logout-form" style="display:inline-block; margin:0;">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <button type="submit" class="home-btn home-btn-outline logout-button">Thoát</button>
          </form>
        <?php else: ?>
          <a class="home-btn home-btn-outline" href="/services">Xem dịch vụ</a>
          <!-- <a class="home-btn" href="/contact">Liên hệ hỗ trợ</a> -->
          <form method="post" action="/logout" class="logout-form" style="display:inline-block; margin:0;">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <button type="submit" class="home-btn home-btn-outline logout-button">Thoát</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
</section>