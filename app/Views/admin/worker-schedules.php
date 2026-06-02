<?php
use App\Core\View;
/** @var array $stats */
/** @var array $groupedByWorker */
/** @var array $scheduleItems */

$statusStyles = [
  'pending' => ['label' => 'Chờ xử lý', 'class' => 'status-pending'],
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
?>

<style>
.admin-worker-schedules {
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

  max-width: 1280px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  color: var(--text-dark);
}

.admin-worker-schedules * {
  box-sizing: border-box;
}

.worker-hero {
  position: relative;
  overflow: hidden;
  padding: 54px 28px;
  border-radius: 28px;
  text-align: center;
  background:
    radial-gradient(circle at top left, rgba(46,175,125,0.20), transparent 34%),
    linear-gradient(135deg, #f7fdf9 0%, #ffffff 48%, #e8f7f0 100%);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.worker-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.worker-hero .home-kicker,
.worker-hero h1,
.worker-hero p,
.worker-hero .hero-actions {
  position: relative;
  z-index: 1;
}

.worker-hero .home-kicker {
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

.worker-hero h1 {
  margin: 0 0 12px;
  font-size: clamp(32px, 5vw, 50px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.worker-hero p {
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
  line-height: 1.6;
}

.hero-actions {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.home-btn {
  min-height: 44px;
  padding: 10px 20px;
  border-radius: 999px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 14px;
  font-weight: 900;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  box-shadow: 0 10px 22px rgba(46,175,125,0.22);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.home-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(46,175,125,0.28);
}

.home-btn-outline {
  background: #fff;
  color: var(--primary);
  border: 1.5px solid var(--primary);
  box-shadow: none;
}

.schedule-summary {
  margin-top: 26px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
}

.worker-filters {
  margin-top: 26px;
  padding: 18px;
  border-radius: 20px;
  background: rgba(255,255,255,0.78);
  border: 1px solid var(--border);
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1fr;
  gap: 12px;
  backdrop-filter: blur(8px);
}

.worker-filter-field {
  display: grid;
  gap: 8px;
}

.worker-filter-field label {
  font-size: 13px;
  font-weight: 900;
  color: var(--text-dark);
}

.worker-filter-field input,
.worker-filter-field select {
  width: 100%;
  min-height: 44px;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: white;
  color: var(--text-dark);
  font: inherit;
  font-weight: 700;
}

.filter-summary {
  grid-column: 1 / -1;
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.summary-card {
  padding: 18px 20px;
  border-radius: 20px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.summary-card span {
  display: block;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
}

.summary-card strong {
  display: block;
  margin-top: 6px;
  color: var(--text-dark);
  font-size: 26px;
  font-weight: 900;
}

.group-list {
  margin-top: 34px;
  display: grid;
  gap: 20px;
}

.worker-card {
  padding: 30px;
  border-radius: 26px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.worker-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 22px;
}

.worker-head h2 {
  margin: 0;
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.worker-head p {
  margin: 6px 0 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.worker-badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.worker-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 12px;
  font-weight: 900;
}

.day-group {
  margin-top: 18px;
  padding: 22px;
  border-radius: 22px;
  background: linear-gradient(180deg, #ffffff 0%, #fbfefc 100%);
  border: 1px solid var(--border);
}

.day-group.is-hidden,
.worker-card.is-hidden {
  display: none;
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
  font-size: 18px;
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

.schedule-table-container {
  overflow-x: auto;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: white;
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.schedule-table thead {
  background: var(--bg-soft);
}

.schedule-table th,
.schedule-table td {
  padding: 14px 16px;
  vertical-align: top;
  border-bottom: 1px solid var(--border);
}

.schedule-table th {
  text-align: left;
  font-weight: 900;
  color: var(--text-dark);
}

.schedule-table td {
  color: var(--text-muted);
  font-weight: 700;
}

.schedule-table tbody tr:hover {
  background: #f9fffc;
}

.schedule-table tbody tr:last-child td {
  border-bottom: none;
}

.schedule-stack {
  display: grid;
  gap: 5px;
}

.schedule-stack strong {
  color: var(--text-dark);
}

.schedule-stack span {
  line-height: 1.45;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-waiting { background: #e0f2fe; color: #075985; }
.status-accepted { background: #fef3c7; color: #92400e; }
.status-progress { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #dcfce7; color: #166534; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

.empty-state {
  margin-top: 26px;
  padding: 28px;
  border-radius: 22px;
  border: 1px dashed #cfe3d8;
  background: var(--bg-soft);
  text-align: center;
}

.empty-state h3 {
  margin: 0 0 10px;
  font-size: 24px;
  font-weight: 900;
}

.empty-state p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

@media (max-width: 768px) {
  .admin-worker-schedules {
    padding: 16px 12px 44px;
  }

  .worker-hero,
  .worker-card,
  .day-group {
    padding: 22px;
    border-radius: 20px;
  }

  .worker-head,
  .day-group-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .worker-filters {
    grid-template-columns: 1fr;
    padding: 14px;
  }

  .worker-badges {
    justify-content: flex-start;
  }

  .home-btn {
    width: 100%;
  }
}
</style>

<section class="admin-worker-schedules">
  <header class="worker-hero">
    <p class="home-kicker">ADMIN • LỊCH NHÂN VIÊN</p>
    <h1>Lịch làm việc của nhân viên</h1>
    <p>Theo dõi đầy đủ các ca làm đã được phân công: ngày, giờ, khách hàng, dịch vụ, địa chỉ, trạng thái và tiến độ gần nhất.</p>

    <div class="hero-actions">
      <a href="/admin/dashboard" class="home-btn home-btn-outline">← Về dashboard</a>
      <a href="/admin/stats" class="home-btn">📊 Báo cáo thống kê</a>
    </div>

    <div class="schedule-summary">
      <div class="summary-card">
        <span>Tổng phân công</span>
        <strong><?= (int)($stats['total_assignments'] ?? 0) ?></strong>
      </div>
      <div class="summary-card">
        <span>Worker có lịch</span>
        <strong><?= (int)($stats['worker_count'] ?? 0) ?></strong>
      </div>
      <div class="summary-card">
        <span>Hôm nay</span>
        <strong><?= (int)($stats['today_count'] ?? 0) ?></strong>
      </div>
      <div class="summary-card">
        <span>Đang hoạt động</span>
        <strong><?= (int)($stats['active_count'] ?? 0) ?></strong>
      </div>
    </div>

    <div class="worker-filters" id="workerFilters">
      <div class="worker-filter-field">
        <label for="workerSearch">Tìm kiếm</label>
        <input type="search" id="workerSearch" placeholder="Worker, khách hàng, dịch vụ, địa chỉ...">
      </div>

      <div class="worker-filter-field">
        <label for="workerStatusFilter">Trạng thái</label>
        <select id="workerStatusFilter">
          <option value="all">Tất cả</option>
          <option value="pending">Chờ xử lý</option>
          <option value="confirmed">Chờ nhận việc</option>
          <option value="accepted">Đã nhận việc</option>
          <option value="in_progress">Đang thực hiện</option>
          <option value="completed">Hoàn thành</option>
          <option value="cancelled">Đã hủy</option>
        </select>
      </div>

      <div class="worker-filter-field">
        <label for="workerDateFilter">Ngày</label>
        <input type="date" id="workerDateFilter">
      </div>

      <div class="worker-filter-field">
        <label for="workerSortFilter">Sắp xếp</label>
        <select id="workerSortFilter">
          <option value="date_asc">Ngày tăng dần</option>
          <option value="date_desc">Ngày giảm dần</option>
        </select>
      </div>

      <p class="filter-summary" id="workerFilterSummary">Đang hiển thị toàn bộ lịch phân công.</p>
    </div>
  </header>

  <?php if (empty($groupedByWorker)): ?>
    <div class="empty-state">
      <h3>Chưa có lịch làm việc</h3>
      <p>Khi admin phân công booking cho worker, lịch sẽ xuất hiện ở đây.</p>
    </div>
  <?php else: ?>
    <div class="group-list" id="workerScheduleList">
      <?php foreach ($groupedByWorker as $workerGroup): ?>
        <section class="worker-card" data-worker-name="<?= View::e(strtolower((string)($workerGroup['worker_name'] ?? ''))) ?>" data-worker-id="<?= (int)($workerGroup['worker_id'] ?? 0) ?>">
          <div class="worker-head">
            <div>
              <h2><?= View::e((string)($workerGroup['worker_name'] ?? 'Worker')) ?></h2>
              <p>
                <?= View::e((string)($workerGroup['worker_phone'] ?? '')) ?>
                <?php if (!empty($workerGroup['worker_phone'])): ?>
                  •
                <?php endif; ?>
                <?= count($workerGroup['items'] ?? []) ?> ca làm
              </p>
            </div>
            <div class="worker-badges">
              <span class="worker-badge">Worker #<?= (int)($workerGroup['worker_id'] ?? 0) ?></span>
            </div>
          </div>

          <?php
            $itemsByDate = [];
            foreach (($workerGroup['items'] ?? []) as $item) {
              $date = (string)($item['date'] ?? 'unknown');
              $itemsByDate[$date][] = $item;
            }
          ?>

          <?php foreach ($itemsByDate as $date => $items): ?>
            <div class="day-group" data-date="<?= View::e((string)$date) ?>">
              <div class="day-group-header">
                <h3><?= View::e($formatDateLabel((string)$date)) ?></h3>
                <span class="day-count"><?= count($items) ?> booking</span>
              </div>

              <div class="schedule-table-container">
                <table class="schedule-table">
                  <thead>
                    <tr>
                      <th>Giờ</th>
                      <th>Khách hàng</th>
                      <th>Dịch vụ</th>
                      <th>Địa chỉ</th>
                      <th>Trạng thái</th>
                      <th>Tiến độ gần nhất</th>
                      <th>Chi tiết</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $item): ?>
                      <?php
                        $status = (string)($item['status'] ?? '');
                        $statusInfo = $statusStyles[$status] ?? ['label' => $item['status_label'] ?? 'Không rõ', 'class' => 'status-pending'];
                      ?>
                      <tr
                        <?php
                          $searchText = strtolower(trim((string)($item['worker_name'] ?? '') . ' ' . (string)($item['customer_name'] ?? '') . ' ' . (string)($item['service_name'] ?? '') . ' ' . (string)($item['location'] ?? '') . ' ' . (string)($item['customer_address'] ?? '')));
                        ?>
                        data-search="<?= View::e($searchText) ?>"
                        data-status="<?= View::e((string)$status) ?>"
                        data-date="<?= View::e((string)($item['date'] ?? '')) ?>"
                      >
                        <td>
                          <div class="schedule-stack">
                            <strong><?= View::e($item['time'] !== '' ? $item['time'] : '--:--') ?></strong>
                            <?php if (!empty($item['estimated_arrival_time'])): ?>
                              <span>ETA: <?= View::e((string)$item['estimated_arrival_time']) ?></span>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td>
                          <div class="schedule-stack">
                            <strong><?= View::e((string)($item['customer_name'] ?? '')) ?></strong>
                            <?php if (!empty($item['customer_phone'])): ?>
                              <span>SĐT: <?= View::e((string)$item['customer_phone']) ?></span>
                            <?php endif; ?>
                            <?php
                              $loc = trim((string)($item['location'] ?? ''));
                              $cust = trim((string)($item['customer_address'] ?? ''));
                            ?>
                            <?php if ($loc !== '' || $cust !== ''): ?>
                              <span>Địa chỉ: <?= View::e($loc !== '' ? $loc : $cust) ?></span>
                              <?php if ($loc !== '' && $cust !== '' && $loc !== $cust): ?>
                                <span style="display:block;font-size:12px;color:#6b7b82">Khách: <?= View::e($cust) ?></span>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td>
                          <div class="schedule-stack">
                            <strong><?= View::e((string)($item['service_name'] ?? '')) ?></strong>
                            <span>Booking #<?= (int)($item['booking_id'] ?? 0) ?></span>
                          </div>
                        </td>
                        <td>
                          <div class="schedule-stack">
                            <?php
                              $loc2 = trim((string)($item['location'] ?? ''));
                              $cust2 = trim((string)($item['customer_address'] ?? ''));
                            ?>
                            <strong><?= View::e($loc2 !== '' ? $loc2 : $cust2) ?></strong>
                            <?php if ($loc2 !== '' && $cust2 !== '' && $loc2 !== $cust2): ?>
                              <div style="font-size:12px;color:#6b7b82"><?= View::e($cust2) ?></div>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td>
                          <span class="status-pill <?= View::e($statusInfo['class']) ?>"><?= View::e($statusInfo['label']) ?></span>
                        </td>
                        <td><?= View::e((string)($item['latest_step'] ?? 'Chưa cập nhật')) ?></td>
                        <td>
                          <a href="/admin/bookings/<?= (int)($item['booking_id'] ?? 0) ?>" class="home-btn home-btn-outline">Xem đơn</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
  (function () {
    const searchInput = document.getElementById('workerSearch');
    const statusFilter = document.getElementById('workerStatusFilter');
    const dateFilter = document.getElementById('workerDateFilter');
    const sortFilter = document.getElementById('workerSortFilter');
    const summary = document.getElementById('workerFilterSummary');
    const workerCards = Array.from(document.querySelectorAll('.worker-card'));

    if (!searchInput || !statusFilter || !dateFilter || !sortFilter || workerCards.length === 0) return;

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applySort = () => {
      const list = document.getElementById('workerScheduleList');
      if (!list) return;

      const cards = Array.from(list.querySelectorAll('.worker-card'));
      const isDesc = sortFilter.value === 'date_desc';

      cards.forEach((card) => {
        const groups = Array.from(card.querySelectorAll('.day-group'));
        groups.sort((a, b) => {
          const aDate = a.getAttribute('data-date') || '';
          const bDate = b.getAttribute('data-date') || '';
          return isDesc ? bDate.localeCompare(aDate) : aDate.localeCompare(bDate);
        });

        groups.forEach((group) => card.appendChild(group));
      });
    };

    const applyFilters = () => {
      const query = normalize(searchInput.value);
      const status = statusFilter.value;
      const dateValue = dateFilter.value;
      let visibleRows = 0;

      workerCards.forEach((card) => {
        let cardVisible = false;
        const dayGroups = Array.from(card.querySelectorAll('.day-group'));

        dayGroups.forEach((group) => {
          let groupVisible = false;
          const rows = Array.from(group.querySelectorAll('tbody tr'));

          rows.forEach((row) => {
            const text = normalize(row.getAttribute('data-search'));
            const rowStatus = normalize(row.getAttribute('data-status'));
            const rowDate = row.getAttribute('data-date') || '';
            const matchesQuery = !query || text.includes(query);
            const matchesStatus = status === 'all' || rowStatus === status;
            const matchesDate = !dateValue || rowDate === dateValue;
            const isVisible = matchesQuery && matchesStatus && matchesDate;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) {
              groupVisible = true;
              cardVisible = true;
              visibleRows += 1;
            }
          });

          group.classList.toggle('is-hidden', !groupVisible);
        });

        card.classList.toggle('is-hidden', !cardVisible);
      });

      if (summary) {
        summary.textContent = visibleRows > 0
          ? 'Đang hiển thị ' + visibleRows + ' ca làm khớp bộ lọc.'
          : 'Không có ca làm nào khớp bộ lọc hiện tại.';
      }
    };

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    dateFilter.addEventListener('change', applyFilters);
    sortFilter.addEventListener('change', () => {
      applySort();
      applyFilters();
    });

    applySort();
    applyFilters();
  })();
</script>
