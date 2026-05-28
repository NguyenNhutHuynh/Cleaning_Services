<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\User;
?>
<nav class="dashboard" aria-label="Điều hướng chính">
  <div class="dashboard-logo">
    <?php
      $homeLink = '/';
      if (Auth::id()) {
        $role = Auth::role();
        if ($role === 'admin') $homeLink = '/admin/dashboard';
        elseif ($role === 'manager') $homeLink = '/manager/dashboard';
        elseif ($role === 'worker') $homeLink = '/worker/dashboard';
        else $homeLink = '/';
      }
    ?>
    <a href="<?= View::e($homeLink) ?>" style="display:flex;align-items:center;text-decoration:none;">
      <img src="/assets/img/logo_nobg.png" alt="Logo" style="height: 100%; margin-right:10px;">
    </a>
  </div>
  <div class="functions">
    <?php if (Auth::id()): ?>
      <?php
        $role = Auth::role();
        $user = User::findById((int)Auth::id());
        $initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
      ?>
      <!-- ADMIN MENU -->
      <?php if ($role === 'admin'): ?>
        <a href="/admin/dashboard" class="function-btn">Trang quản trị</a>
        <a href="/admin/services" class="function-btn">Dịch vụ</a>
        <a href="/admin/bookings" class="function-btn">Đơn đặt</a>
        <a href="/admin/moderation" class="function-btn">Kiểm duyệt</a>
        <a href="/admin/users" class="function-btn">Người dùng</a>
        <a href="/admin/worker-schedules" class="function-btn">Lịch nhân viên</a>
        <a href="/admin/stats" class="function-btn">Thống kê</a>
      <!-- MANAGER MENU -->
      <?php elseif ($role === 'manager'): ?>
        <a href="/manager/dashboard" class="function-btn">Bảng điều khiển</a>
        <a href="/manager/bookings" class="function-btn">Đơn đặt</a>
        <a href="/manager/workers" class="function-btn">Nhân viên</a>
        <a href="/manager/customers" class="function-btn">Khách hàng</a>
      <!-- WORKER MENU -->
      <?php elseif ($role === 'worker'): ?>
        <a href="/worker/dashboard" class="function-btn">Bảng điều khiển</a>
        <a href="/worker/jobs" class="function-btn">Nhận việc</a>
        <a href="/worker/progress" class="function-btn">Tiến độ</a>
        <a href="/worker/schedule" class="function-btn">Lịch làm</a>
      <!-- CUSTOMER MENU -->
      <?php else: ?>
        <a href="/" class="function-btn">Trang chủ</a>
        <a href="/services" class="function-btn">Dịch vụ</a>
        <a href="/book" class="function-btn">Đặt lịch</a>
        <a href="/pricing" class="function-btn">Bảng giá</a>
        <a href="/contact" class="function-btn">Liên hệ</a>
        <a href="/bookings" class="function-btn">Lịch đã đặt</a>
        
      <?php endif; ?>
      <div class="user-menu">
        <button class="avatar-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false" title="Quản lý tài khoản">
          <?php if (!empty($user['avatar'])): ?>
            <img src="<?= View::e($user['avatar']) ?>" alt="Avatar">
          <?php else: ?>
            <?= View::e($initial) ?>
          <?php endif; ?>
        </button>
        <div class="user-dropdown" id="userDropdown" role="menu">
          <div class="user-header">Chào <?= View::e($user['name'] ?? '') ?>,</div>
          <a href="/account" role="menuitem">Thông tin cá nhân</a>
          <a href="/account/edit" role="menuitem">Cập nhật thông tin</a>
          <a href="/account/change-password" role="menuitem">Đổi mật khẩu</a>
          <?php if ($role === 'worker'): ?>
            <a href="/worker/messages" role="menuitem">Tin nhắn</a>
          <?php elseif ($role === 'customer'): ?>
            <a href="/customer/messages" role="menuitem">Tin nhắn</a>
          <?php endif; ?>
          <form method="post" action="/logout" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <button type="submit" role="menuitem" class="logout-button">Đăng xuất</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <a href="/services" class="function-btn">Dịch vụ</a>
      <a href="/pricing" class="function-btn">Bảng giá</a>
      <a href="/contact" class="function-btn">Liên hệ</a>
      <a href="/login" class="function-btn">Đăng nhập</a>
      <a href="/register" class="function-btn">Đăng ký</a>
    <?php endif; ?>
  </div>
</nav>
<script>
  (function(){
    const btn = document.getElementById('userMenuBtn');
    const dd = document.getElementById('userDropdown');
    if (!btn || !dd) return;
    btn.addEventListener('click', function(){
      const open = dd.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function(e){
      if (!dd.classList.contains('open')) return;
      const within = dd.contains(e.target) || btn.contains(e.target);
      if (!within) dd.classList.remove('open');
    });
  })();
</script>
