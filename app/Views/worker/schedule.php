<?php
use App\Core\View;

/** @var array $scheduleItems */
/** @var array $groupedByDate */
/** @var array $todayItems */
/** @var int $upcomingCount */

$statusStyles = [
  'confirmed' => ['label' => 'Chờ nhận việc', 'class' => 'status-waiting'],
  'accepted' => ['label' => 'Đã nhận việc', 'class' => 'status-accepted'],
  'in_progress' => ['label' => 'Đang thực hiện', 'class' => 'status-progress'],
  'completed' => ['label' => 'Hoàn thành', 'class' => 'status-completed'],
  'cancelled' => ['label' => 'Đã hủy', 'class' => 'status-cancelled'],
];

$formatDateLabel = static function (string $date): string {
  if ($date === '' || $date === 'unknown') {
    return 'Chưa xác định';
  }

  $timestamp = strtotime($date);
  if ($timestamp === false) {
    return $date;
  }

  $dayNames = [
    0 => 'Chủ nhật',
    1 => 'Thứ hai',
    2 => 'Thứ ba',
    3 => 'Thứ tư',
    4 => 'Thứ năm',
    5 => 'Thứ sáu',
    6 => 'Thứ bảy',
  ];

  $dayName = $dayNames[(int)date('w', $timestamp)] ?? 'Ngày';
  return $dayName . ', ' . date('d/m/Y', $timestamp);
};

$today = date('Y-m-d');
?>

<style>
.worker-schedule-page {
  --primary: #2eaf7d;
  --primary-dark: #16805a;
  --primary-soft: #e8f7f0;
  --bg-soft: #f7fdf9;
  --text-dark: #1f2d3d;
  --text-muted: #546e7a;
  --border: #dcefe6;
  --white: #ffffff;
  --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
  --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

  max-width: 1180px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  color: var(--text-dark);
}

.worker-schedule-page * {
  box-sizing: border-box;
}

.worker-schedule-page .hero {
  position: relative;
  overflow: hidden;
  padding: 44px 28px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top left, rgba(46,175,125,0.2), transparent 32%),
    linear-gradient(135deg, #f7fdf9 0%, #ffffff 54%, #e8f7f0 100%);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.worker-schedule-page .hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.worker-schedule-page .kicker {
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
  text-transform: uppercase;
}

.worker-schedule-page h1 {
  position: relative;
  margin: 0 0 12px;
  color: var(--text-dark);
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.worker-schedule-page .hero p {
  position: relative;
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
  line-height: 1.6;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-top: 24px;
}

.stat-card {
  padding: 18px 20px;
  border-radius: 20px;
  background: rgba(255,255,255,0.8);
  border: 1px solid rgba(220,239,230,0.9);
  backdrop-filter: blur(8px);
}

.stat-card strong {
  display: block;
  margin-top: 6px;
  color: var(--text-dark);
  font-size: 26px;
  font-weight: 900;
}

.stat-card span {
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.worker-section {
  margin-top: 36px;
}

.worker-section-card {
  padding: 32px;
  border-radius: 26px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 22px;
}

.section-head h2 {
  margin: 0;
  color: var(--text-dark);
  font-size: clamp(24px, 3vw, 34px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.section-head p {
  margin: 6px 0 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.day-group {
  margin-top: 18px;
  padding: 22px;
  border-radius: 22px;
  background: linear-gradient(180deg, #ffffff 0%, #fbfefc 100%);
  border: 1px solid var(--border);
}

.day-group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.day-group-header h3 {
  margin: 0;
  color: var(--text-dark);
  font-size: 20px;
  font-weight: 900;
}

.day-count {
  display: inline-flex;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 12px;
  font-weight: 900;
}

.schedule-list {
  display: grid;
  gap: 14px;
}

.schedule-item {
  display: grid;
  grid-template-columns: 120px 1fr auto;
  gap: 16px;
  align-items: center;
  padding: 18px 20px;
  border-radius: 18px;
  background: #ffffff;
  border: 1px solid var(--border);
  box-shadow: 0 5px 16px rgba(31,45,61,0.05);
}

.schedule-time {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 10px 14px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 18px;
  font-weight: 900;
}

.schedule-meta h4 {
  margin: 0 0 8px;
  color: var(--text-dark);
  font-size: 18px;
  font-weight: 900;
}

.schedule-meta p {
  margin: 4px 0;
  color: var(--text-muted);
  line-height: 1.55;
}

.schedule-meta strong {
  color: var(--text-dark);
}

.schedule-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
}

.schedule-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 16px;
  border-radius: 999px;
  background: var(--primary);
  color: #ffffff;
  text-decoration: none;
  font-size: 14px;
  font-weight: 900;
}

.schedule-link:hover {
  background: var(--primary-dark);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
}

.status-waiting { background: #e0f2fe; color: #075985; }
.status-accepted { background: #fef3c7; color: #92400e; }
.status-progress { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #dcfce7; color: #166534; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

.empty-state {
  padding: 32px;
  border-radius: 24px;
  background: linear-gradient(135deg, var(--bg-soft), #ffffff);
  border: 1px dashed #cfe3d8;
  text-align: center;
}

.empty-state h3 {
  margin: 0 0 10px;
  color: var(--text-dark);
  font-size: 24px;
  font-weight: 900;
}

.empty-state p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

@media (max-width: 768px) {
  .worker-schedule-page .hero,
  .worker-section-card,
  .day-group {
    padding: 22px;
  }

  .schedule-item {
    grid-template-columns: 1fr;
    justify-items: start;
  }

  .schedule-actions {
    align-items: flex-start;
  }

  .section-head,
  .day-group-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

<section class="worker-schedule-page">
  <section class="hero" aria-label="Lịch làm việc">
    <div class="kicker">Worker schedule</div>
    <h1>Lịch làm việc của bạn</h1>
    <p>Xem toàn bộ các đơn đã được phân công, sắp xếp theo ngày để bạn theo dõi ca làm và chuẩn bị trước khi đến nơi.</p>

    <div class="stats-grid">
      <div class="stat-card">
        <span>Tổng lịch</span>
        <strong><?= count($scheduleItems) ?></strong>
      </div>
      <div class="stat-card">
        <span>Hôm nay</span>
        <strong><?= count($todayItems) ?></strong>
      </div>
      <div class="stat-card">
        <span>Sắp tới</span>
        <strong><?= (int)$upcomingCount ?></strong>
      </div>
    </div>
  </section>

  <section class="worker-section" aria-label="Chi tiết lịch làm việc">
    <div class="worker-section-card">
      <div class="section-head">
        <div>
          <h2>Danh sách theo ngày</h2>
          <p>Các ca làm đã được phân công và có thể mở chi tiết ngay từ từng mục.</p>
        </div>
      </div>

      <?php if (empty($scheduleItems)): ?>
        <div class="empty-state">
          <h3>Chưa có lịch làm việc</h3>
          <p>Khi admin hoặc manager phân công đơn cho bạn, lịch sẽ xuất hiện ở đây.</p>
        </div>
      <?php else: ?>
        <?php foreach ($groupedByDate as $date => $items): ?>
          <div class="day-group">
            <div class="day-group-header">
              <h3><?= View::e($formatDateLabel((string)$date)) ?></h3>
              <span class="day-count"><?= count($items) ?> ca</span>
            </div>

            <div class="schedule-list">
              <?php foreach ($items as $item): ?>
                <?php
                  $status = (string)($item['status'] ?? '');
                  $statusInfo = $statusStyles[$status] ?? ['label' => $item['status_label'] ?? 'Không rõ', 'class' => 'status-waiting'];
                ?>
                <article class="schedule-item">
                  <div class="schedule-time"><?= View::e($item['time'] !== '' ? $item['time'] : '--:--') ?></div>

                  <div class="schedule-meta">
                    <h4><?= View::e($item['service_name']) ?></h4>
                    <p><strong>Khách hàng:</strong> <?= View::e($item['customer_name']) ?></p>
                    <?php $loc = trim((string)($item['location'] ?? '')); $cust = trim((string)($item['customer_address'] ?? '')); ?>
                    <?php if ($loc !== '' || $cust !== ''): ?>
                      <p><strong>Địa điểm:</strong> <?= View::e($loc !== '' ? $loc : $cust) ?></p>
                    <?php endif; ?>
                    <?php if ($item['phone'] !== ''): ?>
                      <p><strong>SĐT:</strong> <?= View::e($item['phone']) ?></p>
                    <?php endif; ?>
                  </div>

                  <div class="schedule-actions">
                    <span class="status-pill <?= View::e($statusInfo['class']) ?>"><?= View::e($statusInfo['label']) ?></span>
                    <a class="schedule-link" href="/worker/jobs/<?= (int)$item['id'] ?>">Xem chi tiết</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</section>