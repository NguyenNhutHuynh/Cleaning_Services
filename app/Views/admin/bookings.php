<?php
use App\Core\View;
/** @var array $bookings */
/** @var array $workers */
/** @var string $csrf */

$bookingStatusMap = [
  'pending' => 'Chờ thanh toán',
  'paid' => 'Đã thanh toán',
  'confirmed' => 'Đã xác nhận',
  'accepted' => 'Đang thực hiện',
  'in_progress' => 'Đang thực hiện',
  'completed' => 'Đã hoàn thành',
  'cancelled' => 'Đã hủy',
];
?>

<style>
.admin-bookings {
  --primary: #2eaf7d;
  --primary-dark: #16805a;
  --primary-soft: #e8f7f0;
  --bg-soft: #f7fdf9;
  --white: #ffffff;
  --text-dark: #1f2d3d;
  --text-muted: #5f7482;
  --border: #dcefe6;
  --danger: #dc2626;
  --danger-soft: #fff1f1;
  --warning: #d97706;
  --warning-soft: #fff7ed;
  --success-soft: #dff6ea;
  --success-text: #0f7a55;
  --blue: #2563eb;
  --blue-soft: #eff6ff;
  --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
  --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

  max-width: 1240px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  color: var(--text-dark);
}

.admin-bookings * {
  box-sizing: border-box;
}

.admin-bookings .bookings-hero {
  position: relative;
  overflow: hidden;
  padding: 52px 28px;
  border-radius: 28px;
  text-align: center;
  background:
    radial-gradient(circle at top left, rgba(46,175,125,0.18), transparent 34%),
    linear-gradient(135deg, #f7fdf9 0%, #ffffff 48%, #e8f7f0 100%);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.admin-bookings .bookings-hero::after {
  content: "";
  position: absolute;
  right: -70px;
  bottom: -70px;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(46,175,125,0.10);
}

.admin-bookings .home-kicker {
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

.admin-bookings .bookings-hero h1 {
  position: relative;
  margin: 0 0 12px;
  color: var(--text-dark);
  font-size: clamp(32px, 5vw, 50px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.admin-bookings .bookings-hero p {
  position: relative;
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
  line-height: 1.6;
}

.bookings-section {
  margin-top: 34px;
}

.empty-bookings {
  margin: 0;
  padding: 34px 20px;
  text-align: center;
  border: 1px dashed var(--border);
  border-radius: 22px;
  color: var(--text-muted);
  background: var(--bg-soft);
  font-weight: 700;
}

.bookings-panel {
  overflow: hidden;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 26px;
  box-shadow: var(--shadow-sm);
}

.bookings-panel-head {
  padding: 24px 26px;
  border-bottom: 1px solid var(--border);
  background:
    radial-gradient(circle at top left, rgba(46,175,125,0.10), transparent 34%),
    linear-gradient(135deg, #ffffff, #f7fdf9);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.bookings-filters {
  padding: 18px 26px 0;
  display: grid;
  grid-template-columns: 1.3fr 1fr 1fr 1fr 1fr;
  gap: 12px;
}

.booking-filter-field {
  display: grid;
  gap: 8px;
}

.booking-filter-field label {
  font-size: 13px;
  font-weight: 900;
  color: var(--text-dark);
}

.booking-filter-field input,
.booking-filter-field select {
  width: 100%;
  min-height: 44px;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: #fcfffd;
  color: var(--text-dark);
  font: inherit;
  font-weight: 700;
}

.booking-filter-summary {
  margin: 0;
  padding: 0 26px 18px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.bookings-empty-result {
  display: none;
  padding: 18px 26px 0;
  margin: 0;
  color: var(--text-muted);
  text-align: center;
  font-weight: 700;
}

.bookings-empty-result.is-visible {
  display: block;
}

.bookings-panel-title {
  margin: 0;
  color: var(--text-dark);
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.bookings-count {
  display: inline-flex;
  align-items: center;
  padding: 7px 14px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 14px;
  font-weight: 900;
}

.table-wrap {
  width: 100%;
  overflow-x: visible;
}

.bookings-table {
  width: 100%;
  table-layout: fixed;
  border-collapse: collapse;
  background: var(--white);
}

.bookings-table thead {
  background: #fbfefd;
}

.bookings-table th {
  padding: 16px 14px;
  text-align: left;
  color: var(--text-dark);
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.035em;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}

.bookings-table td {
  padding: 18px 14px;
  vertical-align: top;
  border-bottom: 1px solid var(--border);
  color: var(--text-muted);
  line-height: 1.55;
  text-align: left;
}

.bookings-table tbody tr {
  transition: background 0.2s ease;
}

.bookings-table tbody tr:hover {
  background: #fbfefd;
}

.bookings-table tbody tr:last-child td {
  border-bottom: none;
}

.booking-id {
  display: inline-flex;
  min-width: 50px;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-weight: 900;
}

.booking-time {
  color: var(--text-dark);
  font-weight: 900;
  white-space: nowrap;
  text-align: left;
}

.booking-date {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 13px;
  text-align: left;
}

.booking-service {
  margin-bottom: 7px;
  color: var(--text-dark);
  font-weight: 900;
  text-align: left;
}

.booking-location {
  max-width: 100%;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.55;
  word-break: normal;
  overflow-wrap: anywhere;
  text-align: left;
}

.status-chip,
.payment-chip,
.worker-chip {
  display: inline-flex;
  width: fit-content;
  max-width: 100%;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  white-space: normal;
  text-align: center;
}

.status-chip {
  background: var(--primary-soft);
  color: var(--primary-dark);
}

.status-chip.status-pending {
  background: var(--warning-soft);
  color: var(--warning);
}

.status-chip.status-cancelled {
  background: var(--danger-soft);
  color: var(--danger);
}

.status-chip.status-completed {
  background: #f5f3ff;
  color: #7c3aed;
}

.status-chip.status-progress {
  background: var(--blue-soft);
  color: var(--blue);
}

.payment-chip.paid {
  background: var(--success-soft);
  color: var(--success-text);
}

.payment-chip.unpaid {
  background: #fee2e2;
  color: #991b1b;
}

.worker-chip {
  background: var(--blue-soft);
  color: var(--blue);
}

.worker-chip.empty {
  background: #f3f4f6;
  color: #6b7280;
}

.payment-stack,
.worker-stack {
  display: grid;
  justify-items: start;
  gap: 9px;
}

.row-actions {
  width: 100%;
  min-width: 0;
  display: grid;
  gap: 10px;
}

.payment-detail-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 34px;
  padding: 7px 11px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: #ffffff;
  color: var(--primary);
  font-size: 12px;
  font-weight: 900;
  text-decoration: none;
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.payment-detail-link:hover {
  transform: translateY(-1px);
  background: var(--primary-soft);
  box-shadow: var(--shadow-sm);
}

.top-actions {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 72px;
  gap: 8px;
  width: 100%;
}

.top-actions form {
  margin: 0;
  width: 100%;
}

.assign-form {
  margin: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 58px;
  gap: 8px;
  width: 100%;
}

.home-btn {
  width: 100%;
  min-height: 40px;
  padding: 9px 12px;
  border-radius: 999px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 13px;
  font-weight: 900;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #ffffff;
  box-shadow: 0 10px 22px rgba(46,175,125,0.18);
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  white-space: nowrap;
}

.home-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(46,175,125,0.26);
}

.home-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.home-btn-outline {
  background: #ffffff;
  color: var(--primary);
  border: 1.5px solid var(--primary);
  box-shadow: none;
}

.home-btn-outline:hover {
  background: var(--primary-soft);
  box-shadow: var(--shadow-sm);
}

.worker-select {
  width: 100%;
  min-width: 0;
  min-height: 40px;
  padding: 9px 12px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: #fcfffd;
  color: var(--text-dark);
  font-size: 13px;
  font-weight: 800;
  font-family: inherit;
}

.worker-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

.worker-select:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  background: #f5f7f6;
}

.note-danger,
.note-warning {
  margin: 0;
  padding: 9px 12px;
  border-radius: 14px;
  font-size: 12px;
  font-weight: 800;
  text-align: left;
  line-height: 1.45;
}

.note-danger {
  background: var(--danger-soft);
  color: #991b1b;
  border: 1px solid #fecaca;
}

.note-warning {
  background: var(--warning-soft);
  color: var(--warning);
  border: 1px solid #fed7aa;
}

@media (max-width: 980px) {
  .table-wrap {
    overflow-x: visible;
  }

  .bookings-table,
  .bookings-table thead,
  .bookings-table tbody,
  .bookings-table th,
  .bookings-table td,
  .bookings-table tr {
    display: block;
  }

  .bookings-table colgroup,
  .bookings-table thead {
    display: none;
  }

  .bookings-table tbody {
    display: grid;
    gap: 16px;
    padding: 18px;
  }

  .bookings-table tr {
    border: 1px solid var(--border);
    border-radius: 20px;
    background: #ffffff;
    overflow: hidden;
  }

  .bookings-table td {
    display: grid;
    grid-template-columns: 130px 1fr;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }

  .bookings-table td::before {
    content: attr(data-label);
    color: var(--text-dark);
    font-weight: 900;
    font-size: 13px;
  }

  .bookings-table td:last-child {
    border-bottom: none;
  }

  .payment-stack,
  .worker-stack,
  .row-actions {
    width: 100%;
  }

  .top-actions {
    grid-template-columns: 1fr 1fr;
  }

  .assign-form {
    grid-template-columns: minmax(0, 1fr) 72px;
  }
}

@media (max-width: 768px) {
  .admin-bookings {
    padding: 16px 12px 44px;
  }

  .admin-bookings .bookings-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .bookings-filters {
    grid-template-columns: 1fr;
    padding: 14px 18px 0;
  }

  .bookings-panel {
    border-radius: 20px;
  }

  .bookings-panel-head {
    padding: 22px;
  }

  .bookings-table td {
    grid-template-columns: 1fr;
  }

  .top-actions,
  .assign-form {
    grid-template-columns: 1fr;
  }

  .home-btn,
  .worker-select {
    width: 100%;
  }

  .payment-detail-link,
  .status-chip,
  .payment-chip,
  .worker-chip {
    width: fit-content;
  }
}
</style>

<section class="home-container admin-bookings">
  <header class="home-hero bookings-hero">
    <p class="home-kicker">ADMIN • ĐƠN ĐẶT</p>
    <h1>Quản lý đơn đặt</h1>
    <p>Danh sách đơn dạng bảng, dễ quét thông tin và thao tác nhanh hơn.</p>
  </header>

  <section class="bookings-section">
    <?php if (empty($bookings)): ?>
      <p class="empty-bookings">Chưa có đơn đặt nào.</p>
    <?php else: ?>
      <div class="bookings-panel">
        <div class="bookings-panel-head">
          <h2 class="bookings-panel-title">Danh sách đơn đặt</h2>
          <span class="bookings-count"><?= count($bookings) ?> đơn</span>
        </div>

        <div class="bookings-filters" id="adminBookingFilters">
          <div class="booking-filter-field">
            <label for="adminBookingSearch">Tìm kiếm</label>
            <input type="search" id="adminBookingSearch" placeholder="Mã đơn, khách hàng, dịch vụ, địa chỉ...">
          </div>

          <div class="booking-filter-field">
            <label for="adminBookingStatusFilter">Trạng thái</label>
            <select id="adminBookingStatusFilter">
              <option value="all">Tất cả</option>
              <option value="pending">Chờ thanh toán</option>
              <option value="paid">Đã thanh toán</option>
              <option value="confirmed">Đã xác nhận</option>
              <option value="accepted">Đang thực hiện</option>
              <option value="completed">Đã hoàn thành</option>
              <option value="cancelled">Đã hủy</option>
            </select>
          </div>

          <div class="booking-filter-field">
            <label for="adminBookingPaymentFilter">Thanh toán</label>
            <select id="adminBookingPaymentFilter">
              <option value="all">Tất cả</option>
              <option value="paid">Đã thanh toán</option>
              <option value="unpaid">Chưa thanh toán</option>
            </select>
          </div>

          <div class="booking-filter-field">
            <label for="adminBookingWorkerFilter">Worker</label>
            <select id="adminBookingWorkerFilter">
              <option value="all">Tất cả</option>
              <option value="assigned">Đã gán</option>
              <option value="unassigned">Chưa gán</option>
            </select>
          </div>

          <div class="booking-filter-field">
            <label for="adminBookingDateFilter">Ngày</label>
            <input type="date" id="adminBookingDateFilter">
          </div>
        </div>

        <p class="booking-filter-summary" id="adminBookingFilterSummary">Đang hiển thị toàn bộ đơn đặt.</p>

        <div class="table-wrap">
          <table class="bookings-table">
            <colgroup>
              <col style="width: 7%;">
              <col style="width: 11%;">
              <col style="width: 25%;">
              <col style="width: 12%;">
              <col style="width: 13%;">
              <col style="width: 10%;">
              <col style="width: 22%;">
            </colgroup>

            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Thời gian</th>
                <th>Dịch vụ / Địa điểm</th>
                <th>Trạng thái</th>
                <th>Thanh toán</th>
                <th>Worker</th>
                <th>Thao tác</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($bookings as $b): ?>
                <?php
                  $status = (string)($b['status'] ?? '');
                  $statusLabel = $bookingStatusMap[$status] ?? 'Không rõ';
                  $isCustomerPaid = !empty($b['is_customer_paid']);
                  $workerLabel = !empty($b['assigned_worker_id']) ? 'assigned' : 'unassigned';
                  $bookingDate = (string)($b['date'] ?? $b['work_date'] ?? '');
                  $bookingTime = (string)($b['time'] ?? $b['work_time'] ?? '');
                  $searchText = trim(strtolower(
                    (string)($b['id'] ?? '') . ' ' .
                    (string)($b['customer_name'] ?? $b['user_name'] ?? '') . ' ' .
                    (string)($b['service_name'] ?? '') . ' ' .
                    (string)($b['location'] ?? '') . ' ' .
                    (string)($b['assigned_worker_id'] ?? '')
                  ));

                  $statusClass = match ($status) {
                    'pending' => 'status-pending',
                    'cancelled' => 'status-cancelled',
                    'completed' => 'status-completed',
                    'accepted', 'in_progress' => 'status-progress',
                    default => '',
                  };
                ?>

                <tr
                  data-search="<?= View::e($searchText) ?>"
                  data-status="<?= View::e($status) ?>"
                  data-payment="<?= $isCustomerPaid ? 'paid' : 'unpaid' ?>"
                  data-worker="<?= View::e($workerLabel) ?>"
                  data-date="<?= View::e($bookingDate) ?>"
                >
                  <td data-label="Mã đơn">
                    <span class="booking-id">#<?= View::e($b['id']) ?></span>
                  </td>

                  <td data-label="Thời gian">
                    <div class="booking-time"><?= View::e($bookingTime) ?></div>
                    <div class="booking-date"><?= View::e($bookingDate) ?></div>
                  </td>

                  <td data-label="Dịch vụ / Địa điểm">
                    <div class="booking-service"><?= View::e($b['service_name'] ?? '') ?></div>
                    <div class="booking-location"><?= View::e($b['location']) ?></div>
                  </td>

                  <td data-label="Trạng thái">
                    <span class="status-chip <?= View::e($statusClass) ?>">
                      <?= View::e($statusLabel) ?>
                    </span>
                  </td>

                  <td data-label="Thanh toán">
                    <div class="payment-stack">
                      <span class="payment-chip <?= $isCustomerPaid ? 'paid' : 'unpaid' ?>">
                        <?= $isCustomerPaid ? 'Đã thanh toán' : 'Chưa thanh toán' ?>
                      </span>

                      <?php if ($isCustomerPaid && !empty($b['customer_paid_transaction_id'])): ?>
                        <a class="payment-detail-link" href="/admin/payment-transactions/<?= (int)$b['customer_paid_transaction_id'] ?>">
                          Xem giao dịch
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>

                  <td data-label="Worker">
                    <div class="worker-stack">
                      <?php if (!empty($b['assigned_worker_id'])): ?>
                        <span class="worker-chip">Worker #<?= View::e($b['assigned_worker_id']) ?></span>
                      <?php else: ?>
                        <span class="worker-chip empty">Chưa gán</span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <td data-label="Thao tác">
                    <div class="row-actions">
                      <div class="top-actions">
                        <a class="home-btn home-btn-outline" href="/admin/bookings/<?= (int)$b['id'] ?>">
                          Chi tiết
                        </a>

                        <form method="POST" action="/admin/bookings/cancel">
                          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                          <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                          <button class="home-btn home-btn-outline" type="submit">Hủy</button>
                        </form>
                      </div>

                      <form method="POST" action="/admin/bookings/assign" class="assign-form">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">

                        <select name="worker_id" class="worker-select" <?= $isCustomerPaid ? '' : 'disabled' ?>>
                          <option value="">-- Chọn worker --</option>
                          <?php foreach ($workers as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $b['assigned_worker_id'] == $w['id'] ? 'selected' : '' ?>>
                              <?= View::e($w['name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                          <button class="home-btn" type="submit" name="action" value="assign" <?= $isCustomerPaid ? '' : 'disabled' ?>>
                            Gán
                          </button>
                          <button class="home-btn home-btn-outline" type="submit" name="action" value="auto_assign" <?= $isCustomerPaid ? '' : 'disabled' ?>>
                            Tự động gán
                          </button>
                        </div>
                      </form>

                      <?php if (empty($b['assigned_worker_id'])): ?>
                        <?php if (!$isCustomerPaid): ?>
                          <p class="note-danger">Chưa thanh toán → chưa thể gán worker.</p>
                        <?php else: ?>
                          <p class="note-warning">Đã thanh toán → có thể gán worker.</p>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <p class="bookings-empty-result" id="adminBookingNoResults">Không có đơn nào khớp bộ lọc hiện tại.</p>
      </div>
    <?php endif; ?>
  </section>
</section>

<script>
  (function () {
    const searchInput = document.getElementById('adminBookingSearch');
    const statusFilter = document.getElementById('adminBookingStatusFilter');
    const paymentFilter = document.getElementById('adminBookingPaymentFilter');
    const workerFilter = document.getElementById('adminBookingWorkerFilter');
    const dateFilter = document.getElementById('adminBookingDateFilter');
    const rows = Array.from(document.querySelectorAll('.bookings-table tbody tr'));
    const summary = document.getElementById('adminBookingFilterSummary');
    const noResults = document.getElementById('adminBookingNoResults');

    if (!searchInput || !statusFilter || !paymentFilter || !workerFilter || !dateFilter || rows.length === 0) return;

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applyFilters = () => {
      const query = normalize(searchInput.value);
      const status = statusFilter.value;
      const payment = paymentFilter.value;
      const worker = workerFilter.value;
      const dateValue = dateFilter.value;
      let visibleCount = 0;

      rows.forEach((row) => {
        const text = normalize(row.getAttribute('data-search'));
        const rowStatus = normalize(row.getAttribute('data-status'));
        const rowPayment = normalize(row.getAttribute('data-payment'));
        const rowWorker = normalize(row.getAttribute('data-worker'));
        const rowDate = row.getAttribute('data-date') || '';
        const matchesQuery = !query || text.includes(query);
        const matchesStatus = status === 'all' || rowStatus === status;
        const matchesPayment = payment === 'all' || rowPayment === payment;
        const matchesWorker = worker === 'all' || rowWorker === worker;
        const matchesDate = !dateValue || rowDate === dateValue;
        const isVisible = matchesQuery && matchesStatus && matchesPayment && matchesWorker && matchesDate;

        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount += 1;
      });

      if (summary) {
        summary.textContent = visibleCount > 0
          ? 'Đang hiển thị ' + visibleCount + ' đơn khớp bộ lọc.'
          : 'Không có đơn nào khớp bộ lọc hiện tại.';
      }

      if (noResults) {
        noResults.classList.toggle('is-visible', visibleCount === 0);
      }
    };

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    paymentFilter.addEventListener('change', applyFilters);
    workerFilter.addEventListener('change', applyFilters);
    dateFilter.addEventListener('change', applyFilters);
    applyFilters();
  })();
</script>