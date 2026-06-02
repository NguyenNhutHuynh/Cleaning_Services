<?php
use App\Core\View;
use App\Models\Booking;
use App\Models\User;

/** @var int $uid ID người dùng */
/** @var string $role Vai trò hiện tại */
/** @var string $name Tên hiển thị */
/** @var array $todayBookings Công việc hôm nay */
/** @var int $newJobs Số công việc chưa xem */
/** @var int $activeJobs Số công việc đang thực hiện */
/** @var int $completedThisWeek Công việc hoàn thành trong tuần */
?>

<style>
.worker-dashboard {
  --primary: #2eaf7d;
  --primary-dark: #16805a;
  --primary-soft: #e8f7f0;
  --bg-soft: #f7fdf9;
  --text-dark: #1f2d3d;
  --text-muted: #546e7a;
  --border: #dcefe6;
  --white: #ffffff;
  --blue: #2563eb;
  --orange: #f59e0b;
  --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
  --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

  max-width: 1180px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  color: var(--text-dark);
}

.worker-dashboard * {
  box-sizing: border-box;
}

.worker-dashboard .home-hero {
  position: relative;
  overflow: hidden;
  padding: 56px 28px;
  border-radius: 28px;
  text-align: center;
  background:
    radial-gradient(circle at top left, rgba(46,175,125,0.20), transparent 34%),
    linear-gradient(135deg, #f7fdf9 0%, #ffffff 48%, #e8f7f0 100%);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.worker-dashboard .home-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.worker-dashboard .home-kicker {
  position: relative;
  display: inline-flex;
  margin: 0 0 14px;
  padding: 7px 14px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 13px;
  font-weight: 900;
  letter-spacing: 0.08em;
}

.worker-dashboard .home-hero h1 {
  position: relative;
  margin: 0 0 12px;
  color: var(--text-dark);
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.worker-dashboard .home-hero p {
  position: relative;
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
}

.hero-actions {
  position: relative;
  margin-top: 24px;
  display: flex;
  justify-content: center;
  gap: 14px;
  flex-wrap: wrap;
}

.worker-btn {
  min-height: 46px;
  padding: 12px 26px;
  border-radius: 999px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  font-size: 15px;
  font-weight: 900;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.worker-btn:hover {
  transform: translateY(-2px);
}

.worker-btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  box-shadow: 0 10px 22px rgba(46,175,125,0.22);
}

.worker-btn-outline {
  background: white;
  color: var(--primary);
  border: 1.5px solid var(--primary);
}

.worker-btn-outline:hover {
  background: var(--primary-soft);
}

.worker-badge {
  min-width: 22px;
  height: 22px;
  padding: 0 7px;
  border-radius: 999px;
  background: #ff6b6b;
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 900;
}

.worker-section {
  margin-top: 40px;
}

.worker-section-card {
  padding: 34px;
  border-radius: 26px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.worker-section h2 {
  margin: 0 0 24px;
  color: var(--text-dark);
  font-size: clamp(24px, 3vw, 34px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.worker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
}

.worker-card {
  padding: 24px;
  border-radius: 22px;
  background: white;
  border: 1px solid var(--border);
  box-shadow: 0 5px 16px rgba(31,45,61,0.05);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  text-decoration: none;
  color: inherit;
}

.worker-card:hover {
  transform: translateY(-5px);
  border-color: rgba(46,175,125,0.45);
  box-shadow: var(--shadow-md);
}

.stat-card {
  text-align: center;
  background: linear-gradient(135deg, #ffffff, #f7fdf9);
}

.stat-card h3 {
  margin: 0 0 10px;
  color: var(--primary);
  font-size: 42px;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.stat-card p {
  margin: 0;
  color: var(--text-muted);
  font-weight: 700;
  line-height: 1.5;
}

.empty-work-card {
  text-align: center;
  padding: 46px 24px;
  border-radius: 26px;
  background: linear-gradient(135deg, var(--bg-soft), #ffffff);
  border: 1px dashed #cfe3d8;
}

.empty-work-card .empty-icon {
  margin: 0 0 12px;
  font-size: 56px;
}

.empty-work-card h3 {
  margin: 0 0 10px;
  color: var(--text-dark);
  font-size: 24px;
  font-weight: 900;
}

.empty-work-card p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.job-card {
  display: block;
  text-align: left;
}

.job-time {
  display: inline-flex;
  margin-bottom: 14px;
  padding: 8px 14px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 18px;
  font-weight: 900;
}

.job-card p {
  margin: 8px 0;
  color: var(--text-muted);
  line-height: 1.5;
}

.job-card strong {
  color: var(--text-dark);
}

.job-status {
  display: inline-flex;
  margin-top: 12px;
  padding: 7px 12px;
  border-radius: 999px;
  background: #f3f4f6;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 900;
}

.action-card h3 {
  margin: 0 0 10px;
  color: var(--text-dark);
  font-size: 20px;
  font-weight: 900;
}

.action-card p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

@media (max-width: 768px) {
  .worker-dashboard {
    padding: 16px 12px 44px;
  }

  .worker-dashboard .home-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .worker-section-card {
    padding: 22px;
    border-radius: 20px;
  }

  .worker-card {
    border-radius: 18px;
  }

  .hero-actions,
  .worker-btn {
    width: 100%;
  }
}
</style>

<section class="home-container worker-dashboard">
  <header class="home-hero">
    <p class="home-kicker">WORKER DASHBOARD</p>
    <h1>Xin chào, <?= View::e($name) ?></h1>
    <p>Xem lịch làm việc, nhận việc, cập nhật tiến độ công việc.</p>

    <div class="hero-actions">
      <a class="worker-btn worker-btn-primary" href="/worker/jobs">
        🎯 Nhận việc
        <?php if ($newJobs > 0): ?>
          <span class="worker-badge"><?= $newJobs ?></span>
        <?php endif; ?>
      </a>

      <a class="worker-btn worker-btn-outline" href="/worker/progress">📊 Xem tiến độ</a>
    </div>
  </header>

  <section class="worker-section" aria-label="Thống kê">
    <div class="worker-section-card">
      <h2>📈 Thống kê</h2>

      <div class="worker-grid">
        <article class="worker-card stat-card">
          <h3><?= $newJobs ?></h3>
          <p>Công việc chưa xem</p>
        </article>

        <article class="worker-card stat-card">
          <h3><?= $activeJobs ?></h3>
          <p>Công việc đang thực hiện</p>
        </article>

        <article class="worker-card stat-card">
          <h3><?= $completedThisWeek ?></h3>
          <p>Hoàn thành tuần này</p>
        </article>
      </div>
    </div>
  </section>

  <?php if (count($todayBookings) === 0): ?>
    <section class="worker-section" aria-label="Không có công việc hôm nay">
      <div class="empty-work-card">
        <p class="empty-icon">😌</p>
        <h3>Không có công việc hôm nay</h3>
        <p>Bạn có thể kiểm tra các công việc khác hoặc chỉnh sửa hồ sơ cá nhân.</p>
      </div>
    </section>
  <?php else: ?>
    <section class="worker-section" aria-label="Lịch công việc hôm nay">
      <div class="worker-section-card">
        <h2>📍 Lịch công việc hôm nay (<?= count($todayBookings) ?> việc)</h2>

        <div class="worker-grid">
          <?php foreach ($todayBookings as $booking): ?>
            <?php 
              $statusText = match($booking['status'] ?? '') {
                Booking::STATUS_PENDING => '⏳ Chờ thanh toán',
                Booking::STATUS_CONFIRMED => '⏳ Chờ xác nhận',
                Booking::STATUS_ACCEPTED => '✅ Đã nhận',
                Booking::STATUS_IN_PROGRESS => '🔄 Đang thực hiện',
                Booking::STATUS_COMPLETED => '🏁 Hoàn thành',
                Booking::STATUS_CANCELLED => '❌ Đã hủy',
                'paid' => '✅ Đã thanh toán',
                default => 'Không rõ'
              };
            ?>

            <a href="/worker/jobs/<?= (int)$booking['id'] ?>" class="worker-card job-card">
              <span class="job-time"><?= View::e($booking['time']) ?></span>
              <p><strong>Dịch vụ:</strong> <?= View::e($booking['service_name'] ?? 'N/A') ?></p>
              <?php
                $loc = trim((string)($booking['location'] ?? ''));
                $cust = trim((string)($booking['customer_address'] ?? $booking['user_address'] ?? ''));
              ?>
              <p><strong>Địa điểm:</strong> <?= View::e($loc !== '' ? $loc : ($cust !== '' ? $cust : 'N/A')) ?></p>
              <p><strong>Khách:</strong> <?= View::e($booking['user_name'] ?? 'N/A') ?></p>
              <span class="job-status"><?= View::e($statusText) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="worker-section" aria-label="Chức năng công việc">
    <div class="worker-section-card">
      <h2>Chức năng khác</h2>

      <div class="worker-grid">
        <a href="/worker/schedule" class="worker-card action-card">
          <h3>📅 Lịch làm việc</h3>
          <p>Xem toàn bộ lịch làm việc của bạn.</p>
        </a>

        <a href="/worker/progress" class="worker-card action-card">
          <h3>📊 Cập nhật tiến độ</h3>
          <p>Báo cáo trạng thái công việc theo thời gian.</p>
        </a>

        <a href="/account" class="worker-card action-card">
          <h3>👤 Hồ sơ cá nhân</h3>
          <p>Xem/chỉnh sửa thông tin và ảnh đại diện.</p>
        </a>
      </div>
    </div>
  </section>
</section>