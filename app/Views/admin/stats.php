<?php
use App\Core\View;
/** @var array $stats Dữ liệu thống kê */

$paymentMethodLabels = [
  'customer_payment' => 'Thanh toán khách',
  'bank_transfer' => 'Chuyển khoản',
  'worker_payout' => 'Trả lương worker',
];

$paymentStatusLabels = [
  'paid' => 'Đã thanh toán',
  'pending' => 'Đang chờ',
  'failed' => 'Thất bại',
  'cancelled' => 'Đã hủy',
];
?>

<section class="home-container admin-stats">
  <header class="home-hero">
    <p class="home-kicker">ADMIN • THỐNG KÊ</p>
    <h1>Thống kê hệ thống</h1>
    <p>Tổng quan nhanh về dịch vụ, đơn đặt và người dùng.</p>
  </header>

  <div class="hero-actions">
    <button class="home-btn" id="exportPdfBtn">📥 Tải xuống PDF</button>
    
    <button class="home-btn home-btn-outline" id="refreshBtn">🔄 Làm mới</button>
  </div>

  <?php if (defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE): ?>
    <div style="margin-top:16px;padding:14px 16px;border:1px dashed #d9efe5;border-radius:12px;background:#f7fdf9;color:#546e7a;">
      Chế độ test nội bộ đang bật, nên biểu đồ/PDF dùng thư viện ngoài đã được tắt.
    </div>
  <?php endif; ?>

  <section class="stats-group">
    <div class="stats-group-head stats-group-head-overview">
      <h2>Tổng quan hệ thống</h2>
      <button type="button" class="stats-toggle-btn" data-target="overviewGroup" aria-expanded="true">Thu gọn</button>
    </div>

    <div class="stats-group-content" id="overviewGroup">
      <section class="home-stats" id="statsOverview">
        <div class="stat-card">
          <strong><?= View::e($stats['service_count']) ?></strong>
          <span>Dịch vụ</span>
        </div>
        <div class="stat-card">
          <strong><?= View::e($stats['booking_count']) ?></strong>
          <span>Đơn đặt</span>
        </div>
        <div class="stat-card">
          <strong><?= View::e($stats['user_count']) ?></strong>
          <span>Người dùng</span>
        </div>
        <div class="stat-card">
          <strong><?= View::e($stats['contact_count']) ?></strong>
          <span>Tin nhắn</span>
        </div>
      </section>
    </div>
  </section>

  <section class="stats-group">
    <div class="stats-group-head">
      <h2>Doanh thu và hiệu suất</h2>
      <button type="button" class="stats-toggle-btn" data-target="revenueGroup" aria-expanded="true">Thu gọn</button>
    </div>

    <div class="stats-group-content" id="revenueGroup">
      <section class="home-stats revenue-stats">
        <div class="stat-card stat-revenue">
          <strong><?= number_format($stats['total_revenue'], 0, ',', '.') ?>đ</strong>
          <span>💰 Tổng doanh thu</span>
        </div>
        <div class="stat-card stat-aov">
          <strong><?= number_format($stats['average_order_value'], 0, ',', '.') ?>đ</strong>
          <span>📊 Giá trị đơn TB</span>
        </div>
        <div class="stat-card stat-conversion">
          <strong><?= View::e($stats['conversion_rate']) ?>%</strong>
          <span>🎯 Tỷ lệ chuyển đổi</span>
        </div>
        <div class="stat-card stat-completion">
          <strong><?= View::e($stats['completion_rate']) ?>%</strong>
          <span>✅ Tỷ lệ hoàn thành</span>
        </div>
      </section>
    </div>
  </section>

  <section class="stats-group">
    <div class="stats-group-head">
      <h2>Biểu đồ phân tích</h2>
      <button type="button" class="stats-toggle-btn" data-target="chartsGroup" aria-expanded="true">Thu gọn</button>
    </div>

    <div class="stats-group-content" id="chartsGroup">
      <div class="charts-grid">
        <section class="chart-section">
          <div class="chart-header">
            <h2>💰 Doanh thu 6 tháng gần nhất</h2>
            <div class="chart-controls">
              <select id="revenueChartType" class="chart-select">
                <option value="bar">Biểu đồ cột</option>
                <option value="line">Biểu đồ đường</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <canvas id="monthlyRevenueChart"></canvas>
          </div>
        </section>

        <section class="chart-section">
          <div class="chart-header">
            <h2>📈 Xu hướng đơn đặt 6 tháng gần nhất</h2>
            <div class="chart-controls">
              <select id="lineChartType" class="chart-select">
                <option value="line">Biểu đồ đường</option>
                <option value="bar">Biểu đồ cột</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <canvas id="monthlyBookingsChart"></canvas>
          </div>
        </section>

        <section class="chart-section">
          <div class="chart-header">
            <h2>📊 Phân bố trạng thái đơn đặt</h2>
            <div class="chart-controls">
              <select id="statusChartType" class="chart-select">
                <option value="pie">Biểu đồ tròn</option>
                <option value="doughnut">Biểu đồ vành khuyên</option>
                <option value="bar">Biểu đồ cột</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <canvas id="bookingStatusChart"></canvas>
          </div>
        </section>

        <section class="chart-section">
          <div class="chart-header">
            <h2>👥 Phân bố vai trò người dùng</h2>
            <div class="chart-controls">
              <select id="roleChartType" class="chart-select">
                <option value="doughnut">Biểu đồ vành khuyên</option>
                <option value="pie">Biểu đồ tròn</option>
                <option value="bar">Biểu đồ cột</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <canvas id="userRoleChart"></canvas>
          </div>
        </section>
      </div>
    </div>
  </section>

  <section class="home-feature stats-group">
    <div class="stats-group-head">
      <h2>📋 Chi tiết thống kê</h2>
      <button type="button" class="stats-toggle-btn" data-target="detailGroup" aria-expanded="true">Thu gọn</button>
    </div>

    <div class="stats-group-content" id="detailGroup">
      <div class="stats-table-container">
        <table class="stats-table">
          <thead>
            <tr>
              <th>Chỉ số</th>
              <th>Giá trị</th>
              <th>Tỷ lệ</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Đơn đã xác nhận</td>
              <td><?= View::e($stats['booking_status_breakdown']['confirmed'] ?? 0) ?></td>
              <td><?= View::e($stats['confirmed_rate']) ?>%</td>
            </tr>
            <tr>
              <td>Đơn đang chờ</td>
              <td><?= View::e($stats['booking_status_breakdown']['pending'] ?? 0) ?></td>
              <td><?= View::e($stats['pending_rate']) ?>%</td>
            </tr>
            <tr>
              <td>Đơn hoàn thành</td>
              <td><?= View::e($stats['booking_status_breakdown']['completed'] ?? 0) ?></td>
              <td><?= $stats['booking_count'] > 0 ? round(($stats['booking_status_breakdown']['completed'] / $stats['booking_count']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
              <td>Đơn đã hủy</td>
              <td><?= View::e($stats['booking_status_breakdown']['cancelled'] ?? 0) ?></td>
              <td><?= $stats['booking_count'] > 0 ? round(($stats['booking_status_breakdown']['cancelled'] / $stats['booking_count']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr class="divider-row">
              <td colspan="3"></td>
            </tr>
            <tr class="highlight-row">
              <td>💰 Tổng doanh thu</td>
              <td colspan="2"><?= number_format($stats['total_revenue'], 0, ',', '.') ?>đ</td>
            </tr>
            <tr class="highlight-row">
              <td>📊 Giá trị đơn trung bình</td>
              <td colspan="2"><?= number_format($stats['average_order_value'], 0, ',', '.') ?>đ</td>
            </tr>
            <tr class="highlight-row">
              <td>🎯 Tỷ lệ chuyển đổi</td>
              <td colspan="2"><?= View::e($stats['conversion_rate']) ?>%</td>
            </tr>
            <tr class="highlight-row">
              <td>✅ Tỷ lệ hoàn thành</td>
              <td colspan="2"><?= View::e($stats['completion_rate']) ?>%</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="home-feature stats-group">
    <div class="stats-group-head">
      <h2>💳 Danh sách thanh toán của khách</h2>
      <button type="button" class="stats-toggle-btn" data-target="paymentsGroup" aria-expanded="true">Thu gọn</button>
    </div>

    <div class="stats-group-content" id="paymentsGroup">
      <div class="payment-summary">
        <p>Hiển thị <?= count($stats['customer_payments'] ?? []) ?> giao dịch gần nhất của khách hàng, kèm đầy đủ thông tin booking, dịch vụ và người thanh toán.</p>
      </div>

      <div class="payment-filters" id="paymentFilters">
        <div class="payment-filter-field">
          <label for="paymentSearch">Tìm kiếm</label>
          <input type="search" id="paymentSearch" placeholder="Order code, khách hàng, booking, txn...">
        </div>

        <div class="payment-filter-field">
          <label for="paymentStatusFilter">Trạng thái</label>
          <select id="paymentStatusFilter">
            <option value="all">Tất cả</option>
            <option value="paid">Đã thanh toán</option>
            <option value="pending">Đang chờ</option>
            <option value="failed">Thất bại</option>
            <option value="cancelled">Đã hủy</option>
          </select>
        </div>

        <div class="payment-filter-field">
          <label for="paymentMethodFilter">Phương thức</label>
          <select id="paymentMethodFilter">
            <option value="all">Tất cả</option>
            <option value="customer_payment">Thanh toán khách</option>
            <option value="bank_transfer">Chuyển khoản</option>
            <option value="worker_payout">Trả lương worker</option>
          </select>
        </div>

        <div class="payment-filter-field">
          <label for="paymentDateFilter">Ngày tạo</label>
          <input type="date" id="paymentDateFilter">
        </div>

        <p class="payment-filter-summary" id="paymentFilterSummary">Đang hiển thị toàn bộ giao dịch.</p>
      </div>

      <div class="stats-table-container">
        <table class="stats-table payments-table">
          <thead>
            <tr>
              <th>Giao dịch</th>
              <th>Khách hàng</th>
              <th>Booking / Dịch vụ</th>
              <th>Thanh toán</th>
              <th>Trạng thái</th>
              <th>Thời gian</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($stats['customer_payments'])): ?>
              <tr>
                <td colspan="6" style="text-align:center; color: var(--text-muted);">Chưa có giao dịch thanh toán của khách.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($stats['customer_payments'] as $payment): ?>
                <?php
                  $paymentMethod = (string)($payment['payment_method'] ?? '');
                  $paymentStatus = (string)($payment['status'] ?? 'pending');
                  $bookingStatus = (string)($payment['booking_status'] ?? '');
                  $searchText = trim(strtolower(
                    (string)($payment['id'] ?? '') . ' ' .
                    (string)($payment['order_code'] ?? '') . ' ' .
                    (string)($payment['transaction_id'] ?? '') . ' ' .
                    (string)($payment['payer_name'] ?? '') . ' ' .
                    (string)($payment['customer_name'] ?? '') . ' ' .
                    (string)($payment['service_name'] ?? '') . ' ' .
                    (string)($payment['booking_location'] ?? '')
                  ));
                  $methodLabel = $paymentMethodLabels[$paymentMethod] ?? ($paymentMethod !== '' ? $paymentMethod : 'Không rõ');
                  $statusLabel = $paymentStatusLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : 'Không rõ');
                ?>
                <tr
                  data-search="<?= View::e($searchText) ?>"
                  data-status="<?= View::e($paymentStatus) ?>"
                  data-method="<?= View::e($paymentMethod !== '' ? $paymentMethod : 'unknown') ?>"
                  data-date="<?= View::e(substr((string)($payment['created_at'] ?? ''), 0, 10)) ?>"
                >
                  <td>
                    <div class="payment-stack">
                      <strong>#<?= View::e((string)($payment['id'] ?? '')) ?></strong>
                      <span>Order: <?= View::e((string)($payment['order_code'] ?? '')) ?></span>
                      <?php if (!empty($payment['transaction_id'])): ?>
                        <span>Txn: <?= View::e((string)$payment['transaction_id']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['payer_name'])): ?>
                        <span>Người thanh toán: <?= View::e((string)$payment['payer_name']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="payment-stack">
                      <strong><?= View::e((string)($payment['customer_name'] ?? '')) ?></strong>
                      <?php if (!empty($payment['customer_phone'])): ?>
                        <span>SĐT: <?= View::e((string)$payment['customer_phone']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['customer_address'])): ?>
                        <span>Địa chỉ: <?= View::e((string)$payment['customer_address']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="payment-stack">
                      <strong>Booking #<?= View::e((string)($payment['booking_id'] ?? '')) ?> - <?= View::e((string)($payment['service_name'] ?? '')) ?></strong>
                      <?php if (!empty($payment['booking_date']) || !empty($payment['booking_time'])): ?>
                        <span><?= View::e(trim((string)($payment['booking_date'] ?? '') . ' ' . (string)($payment['booking_time'] ?? ''))) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['booking_location'])): ?>
                        <span>Địa điểm: <?= View::e((string)$payment['booking_location']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['worker_name'])): ?>
                        <span>Worker: <?= View::e((string)$payment['worker_name']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['booking_description'])): ?>
                        <span>Ghi chú: <?= View::e((string)$payment['booking_description']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="payment-stack">
                      <strong><?= number_format((float)($payment['amount'] ?? 0), 0, ',', '.') ?>đ</strong>
                      <span class="payment-pill payment-pill-method"><?= View::e($methodLabel) ?></span>
                    </div>
                  </td>
                  <td>
                    <div class="payment-stack">
                      <span class="payment-pill payment-pill-status payment-status-<?= View::e($paymentStatus) ?>"><?= View::e($statusLabel) ?></span>
                      <?php if ($bookingStatus !== ''): ?>
                        <span>Booking: <?= View::e($bookingStatus) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['payer_account_number'])): ?>
                        <span>TK: <?= View::e((string)$payment['payer_account_number']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="payment-stack">
                      <span>Tạo: <?= View::e((string)($payment['created_at'] ?? '')) ?></span>
                      <?php if (!empty($payment['paid_at'])): ?>
                        <span>Paid: <?= View::e((string)$payment['paid_at']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($payment['webhook_received_at'])): ?>
                        <span>Webhook: <?= View::e((string)$payment['webhook_received_at']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <p class="payment-empty-result" id="paymentNoResults">Không có giao dịch nào khớp bộ lọc hiện tại.</p>
    </div>
  </section>
</section>

<?php if (!(defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<?php endif; ?>

<script>
(function() {
  'use strict';

  const statsData = <?= json_encode($stats) ?>;
  const offlineMode = <?= (defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE) ? 'true' : 'false' ?>;

  if (offlineMode || typeof Chart === 'undefined' || typeof html2canvas === 'undefined' || !window.jspdf) {
    const exportButton = document.getElementById('exportPdfBtn');
    if (exportButton) {
      exportButton.disabled = true;
      exportButton.title = 'Chế độ test nội bộ không hỗ trợ xuất PDF';
    }
    return;
  }

  const colors = {
    primary: '#2eaf7d',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6',
    purple: '#8b5cf6',
    pink: '#ec4899',
  };

  const commonOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: true,
        position: 'bottom',
      },
      tooltip: {
        backgroundColor: 'rgba(31, 45, 61, 0.92)',
        padding: 12,
        titleFont: { size: 14, weight: 'bold' },
        bodyFont: { size: 13 },
        cornerRadius: 8,
      },
    },
  };

  function formatCurrency(value) {
    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
  }

  let revenueChart = null;
  const revenueLabels = Object.keys(statsData.monthly_revenue);
  const revenueValues = Object.values(statsData.monthly_revenue);

  function createRevenueChart(type = 'bar') {
    const ctx = document.getElementById('monthlyRevenueChart');
    if (!ctx) return;

    if (revenueChart) revenueChart.destroy();

    revenueChart = new Chart(ctx, {
      type: type,
      data: {
        labels: revenueLabels,
        datasets: [{
          label: 'Doanh thu (VNĐ)',
          data: revenueValues,
          backgroundColor: type === 'bar'
            ? 'rgba(46, 175, 125, 0.78)'
            : 'rgba(46, 175, 125, 0.12)',
          borderColor: colors.primary,
          borderWidth: 2,
          tension: 0.4,
          fill: type === 'line',
        }]
      },
      options: {
        ...commonOptions,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return formatCurrency(value);
              }
            }
          }
        },
        plugins: {
          ...commonOptions.plugins,
          tooltip: {
            ...commonOptions.plugins.tooltip,
            callbacks: {
              label: function(context) {
                return 'Doanh thu: ' + formatCurrency(context.parsed.y);
              }
            }
          }
        }
      }
    });
  }

  let monthlyChart = null;
  const monthlyLabels = Object.keys(statsData.monthly_bookings);
  const monthlyValues = Object.values(statsData.monthly_bookings);

  function createMonthlyChart(type = 'line') {
    const ctx = document.getElementById('monthlyBookingsChart');
    if (!ctx) return;

    if (monthlyChart) monthlyChart.destroy();

    monthlyChart = new Chart(ctx, {
      type: type,
      data: {
        labels: monthlyLabels,
        datasets: [{
          label: 'Số đơn đặt',
          data: monthlyValues,
          backgroundColor: type === 'bar' ? colors.primary : 'rgba(46, 175, 125, 0.12)',
          borderColor: colors.primary,
          borderWidth: 2,
          tension: 0.4,
          fill: type === 'line',
        }]
      },
      options: {
        ...commonOptions,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        }
      }
    });
  }

  let statusChart = null;
  const statusLabels = ['Chờ xác nhận', 'Đã xác nhận', 'Hoàn thành', 'Đã hủy'];
  const statusValues = [
    statsData.booking_status_breakdown.pending || 0,
    statsData.booking_status_breakdown.confirmed || 0,
    statsData.booking_status_breakdown.completed || 0,
    statsData.booking_status_breakdown.cancelled || 0,
  ];

  function createStatusChart(type = 'pie') {
    const ctx = document.getElementById('bookingStatusChart');
    if (!ctx) return;

    if (statusChart) statusChart.destroy();

    const isBarChart = type === 'bar';

    statusChart = new Chart(ctx, {
      type: type,
      data: {
        labels: statusLabels,
        datasets: [{
          label: 'Số lượng',
          data: statusValues,
          backgroundColor: [colors.warning, colors.success, colors.info, colors.danger],
          borderColor: '#fff',
          borderWidth: 2,
        }]
      },
      options: {
        ...commonOptions,
        ...(isBarChart && {
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1 }
            }
          }
        })
      }
    });
  }

  let roleChart = null;
  const roleLabels = ['Admin', 'Worker', 'Customer'];
  const roleValues = [
    statsData.user_role_distribution.admin || 0,
    statsData.user_role_distribution.worker || 0,
    statsData.user_role_distribution.customer || 0,
  ];

  function createRoleChart(type = 'doughnut') {
    const ctx = document.getElementById('userRoleChart');
    if (!ctx) return;

    if (roleChart) roleChart.destroy();

    const isBarChart = type === 'bar';

    roleChart = new Chart(ctx, {
      type: type,
      data: {
        labels: roleLabels,
        datasets: [{
          label: 'Số lượng',
          data: roleValues,
          backgroundColor: [colors.danger, colors.primary, colors.success],
          borderColor: '#fff',
          borderWidth: 2,
        }]
      },
      options: {
        ...commonOptions,
        ...(isBarChart && {
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1 }
            }
          }
        })
      }
    });
  }

  createRevenueChart('bar');
  createMonthlyChart('line');
  createStatusChart('pie');
  createRoleChart('doughnut');

  document.getElementById('revenueChartType')?.addEventListener('change', (e) => {
    createRevenueChart(e.target.value);
  });

  document.getElementById('lineChartType')?.addEventListener('change', (e) => {
    createMonthlyChart(e.target.value);
  });

  document.getElementById('statusChartType')?.addEventListener('change', (e) => {
    createStatusChart(e.target.value);
  });

  document.getElementById('roleChartType')?.addEventListener('change', (e) => {
    createRoleChart(e.target.value);
  });

  document.getElementById('refreshBtn')?.addEventListener('click', () => {
    location.reload();
  });

  const paymentSearch = document.getElementById('paymentSearch');
  const paymentStatusFilter = document.getElementById('paymentStatusFilter');
  const paymentMethodFilter = document.getElementById('paymentMethodFilter');
  const paymentDateFilter = document.getElementById('paymentDateFilter');
  const paymentRows = Array.from(document.querySelectorAll('.payments-table tbody tr'));
  const paymentSummary = document.getElementById('paymentFilterSummary');
  const paymentNoResults = document.getElementById('paymentNoResults');

  if (paymentSearch && paymentStatusFilter && paymentMethodFilter && paymentDateFilter && paymentRows.length > 0) {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applyPaymentFilters = () => {
      const query = normalize(paymentSearch.value);
      const status = paymentStatusFilter.value;
      const method = paymentMethodFilter.value;
      const dateValue = paymentDateFilter.value;
      let visibleCount = 0;

      paymentRows.forEach((row) => {
        const rowSearch = normalize(row.getAttribute('data-search'));
        const rowStatus = normalize(row.getAttribute('data-status'));
        const rowMethod = normalize(row.getAttribute('data-method'));
        const rowDate = row.getAttribute('data-date') || '';

        const matchesQuery = !query || rowSearch.includes(query);
        const matchesStatus = status === 'all' || rowStatus === status;
        const matchesMethod = method === 'all' || rowMethod === method;
        const matchesDate = !dateValue || rowDate === dateValue;
        const isVisible = matchesQuery && matchesStatus && matchesMethod && matchesDate;

        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount += 1;
      });

      if (paymentSummary) {
        paymentSummary.textContent = visibleCount > 0
          ? 'Đang hiển thị ' + visibleCount + ' giao dịch khớp bộ lọc.'
          : 'Không có giao dịch nào khớp bộ lọc hiện tại.';
      }

      if (paymentNoResults) {
        paymentNoResults.classList.toggle('is-visible', visibleCount === 0);
      }
    };

    paymentSearch.addEventListener('input', applyPaymentFilters);
    paymentStatusFilter.addEventListener('change', applyPaymentFilters);
    paymentMethodFilter.addEventListener('change', applyPaymentFilters);
    paymentDateFilter.addEventListener('change', applyPaymentFilters);
    applyPaymentFilters();
  }

  document.querySelectorAll('.stats-toggle-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const target = targetId ? document.getElementById(targetId) : null;
      if (!target) return;

      const isExpanded = btn.getAttribute('aria-expanded') === 'true';

      if (isExpanded) {
        target.classList.add('is-collapsed');
        window.setTimeout(() => {
          if (target.classList.contains('is-collapsed')) {
            target.classList.add('is-hidden');
          }
        }, 360);

        btn.textContent = 'Mở rộng';
        btn.setAttribute('aria-expanded', 'false');
        return;
      }

      target.classList.remove('is-hidden');
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          target.classList.remove('is-collapsed');
        });
      });

      btn.textContent = 'Thu gọn';
      btn.setAttribute('aria-expanded', 'true');

      if (targetId === 'chartsGroup') {
        setTimeout(() => {
          revenueChart?.resize();
          monthlyChart?.resize();
          statusChart?.resize();
          roleChart?.resize();
        }, 220);
      }
    });
  });

  document.getElementById('exportPdfBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('exportPdfBtn');
    if (!btn) return;

    const originalButtonText = btn.textContent;
    const hiddenControls = [];

    btn.disabled = true;
    btn.textContent = '⏳ Đang xử lý...';

    try {
      const exportRoot = document.querySelector('.admin-stats');
      if (!exportRoot) {
        throw new Error('Không tìm thấy khu vực thống kê để xuất PDF.');
      }

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF('p', 'mm', 'a4');
      const pageWidth = pdf.internal.pageSize.getWidth();
      const pageHeight = pdf.internal.pageSize.getHeight();
      const margin = 15;
      const usableWidth = pageWidth - margin * 2;
      const usableHeight = pageHeight - margin * 2;

      const controls = exportRoot.querySelectorAll('.stats-toggle-btn, #refreshBtn, #exportPdfBtn');
      controls.forEach((control) => {
        control.dataset.pdfHidden = '1';
        control.style.visibility = 'hidden';
        hiddenControls.push(control);
      });

      const capture = await html2canvas(exportRoot, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#f7fdf9',
      });

      const sliceHeight = Math.max(1, Math.floor((capture.width * usableHeight) / usableWidth));
      let renderedHeight = 0;
      let pageIndex = 0;

      while (renderedHeight < capture.height) {
        const currentSliceHeight = Math.min(sliceHeight, capture.height - renderedHeight);
        const pageCanvas = document.createElement('canvas');
        pageCanvas.width = capture.width;
        pageCanvas.height = currentSliceHeight;

        const context = pageCanvas.getContext('2d');
        if (!context) {
          throw new Error('Không thể tạo canvas cho PDF.');
        }

        context.drawImage(
          capture,
          0,
          renderedHeight,
          capture.width,
          currentSliceHeight,
          0,
          0,
          capture.width,
          currentSliceHeight
        );

        if (pageIndex > 0) {
          pdf.addPage();
        }

        const pageImage = pageCanvas.toDataURL('image/png');
        const renderedSliceHeight = (currentSliceHeight * usableWidth) / capture.width;
        pdf.addImage(pageImage, 'PNG', margin, margin, usableWidth, renderedSliceHeight);

        renderedHeight += currentSliceHeight;
        pageIndex += 1;
      }

      const fileName = `Bao-cao-thong-ke-${new Date().toISOString().split('T')[0]}.pdf`;
      pdf.save(fileName);

      btn.textContent = '✅ Tải xuống thành công!';
      setTimeout(() => {
        btn.disabled = false;
        btn.textContent = originalButtonText;
      }, 2000);
    } catch (error) {
      console.error('Lỗi khi xuất PDF:', error);
      alert('Có lỗi xảy ra khi xuất PDF. Vui lòng thử lại.');
      btn.disabled = false;
      btn.textContent = originalButtonText;
    } finally {
      hiddenControls.forEach((control) => {
        control.style.visibility = '';
        delete control.dataset.pdfHidden;
      });
    }
  });
})();
</script>

<style>
.admin-stats {
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
  display: grid;
  gap: 24px;
}

.admin-stats * {
  box-sizing: border-box;
}

.admin-stats .home-hero {
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

.admin-stats .home-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.admin-stats .home-kicker {
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

.admin-stats .home-hero h1 {
  position: relative;
  margin: 0 0 12px;
  color: var(--text-dark);
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.admin-stats .home-hero p {
  position: relative;
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.home-btn {
  min-height: 46px;
  padding: 12px 24px;
  border-radius: 999px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 15px;
  font-weight: 900;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  box-shadow: 0 10px 22px rgba(46,175,125,0.22);
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.home-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(46,175,125,0.28);
}

.home-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.home-btn-outline {
  background: white;
  color: var(--primary);
  border: 1.5px solid var(--primary);
  box-shadow: none;
}

.home-btn-outline:hover {
  background: var(--primary-soft);
  box-shadow: var(--shadow-sm);
}

.stats-group {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 26px;
  padding: 28px;
  box-shadow: var(--shadow-sm);
}

.stats-group-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 18px;
  margin-bottom: 18px;
  border-bottom: 1px solid var(--border);
}

.stats-group-head h2 {
  margin: 0;
  color: var(--text-dark);
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.stats-toggle-btn {
  min-height: 40px;
  padding: 9px 18px;
  border-radius: 999px;
  border: 1.5px solid var(--primary);
  background: white;
  color: var(--primary);
  font-weight: 900;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.stats-toggle-btn:hover {
  transform: translateY(-2px);
  background: var(--primary-soft);
  box-shadow: var(--shadow-sm);
}

.stats-group-content {
  overflow: hidden;
  opacity: 1;
  transform: scaleY(1);
  transform-origin: top center;
  transition: transform 0.35s ease, opacity 0.25s ease;
}

.stats-group-content.is-collapsed {
  transform: scaleY(0);
  opacity: 0;
}

.stats-group-content.is-hidden {
  display: none;
}

.home-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}

.stat-card {
  position: relative;
  overflow: hidden;
  min-height: 130px;
  padding: 24px;
  border-radius: 22px;
  background: linear-gradient(135deg, #ffffff, var(--bg-soft));
  border: 1px solid var(--border);
  box-shadow: 0 5px 16px rgba(31,45,61,0.05);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.stat-card::after {
  content: "";
  position: absolute;
  right: -40px;
  bottom: -40px;
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: rgba(46,175,125,0.10);
}

.stat-card:hover {
  transform: translateY(-5px);
  border-color: rgba(46,175,125,0.45);
  box-shadow: var(--shadow-md);
}

.stat-card strong {
  position: relative;
  z-index: 1;
  display: block;
  margin-bottom: 9px;
  color: var(--primary);
  font-size: clamp(24px, 3vw, 34px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.stat-card span {
  position: relative;
  z-index: 1;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 800;
  line-height: 1.5;
}

.revenue-stats .stat-card {
  background: linear-gradient(135deg, #ffffff, #f7fdf9);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.chart-section {
  background: #ffffff;
  border-radius: 24px;
  padding: 22px;
  border: 1px solid var(--border);
  box-shadow: 0 5px 16px rgba(31,45,61,0.05);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.chart-section:hover {
  transform: translateY(-3px);
  border-color: rgba(46,175,125,0.38);
  box-shadow: var(--shadow-md);
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 18px;
}

.chart-header h2 {
  margin: 0;
  color: var(--text-dark);
  font-size: 19px;
  font-weight: 900;
  letter-spacing: -0.02em;
}

.chart-select {
  min-height: 42px;
  padding: 9px 14px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: #fcfffd;
  color: var(--text-dark);
  font-weight: 800;
  cursor: pointer;
}

.chart-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

.chart-container {
  position: relative;
  height: 320px;
  max-height: 400px;
}

.chart-container canvas {
  max-height: 100%;
}

.stats-table-container {
  overflow-x: auto;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: white;
}

.stats-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.stats-table thead {
  background: var(--bg-soft);
}

.stats-table th {
  padding: 15px 18px;
  text-align: left;
  font-weight: 900;
  color: var(--text-dark);
  border-bottom: 2px solid var(--border);
}

.stats-table td {
  padding: 15px 18px;
  border-bottom: 1px solid var(--border);
  color: var(--text-muted);
  font-weight: 700;
}

.stats-table tbody tr:hover {
  background: #f9fffc;
}

.stats-table tbody tr:last-child td {
  border-bottom: none;
}

.divider-row td {
  padding: 5px !important;
  background: var(--bg-soft) !important;
  border-bottom: 2px solid var(--border) !important;
}

.highlight-row {
  background: var(--primary-soft) !important;
}

.highlight-row td {
  color: var(--primary-dark) !important;
  font-weight: 900;
}

.highlight-row:hover {
  background: #d9f2e7 !important;
}

.payment-summary {
  margin-bottom: 16px;
  padding: 16px 18px;
  border-radius: 18px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
}

.payment-summary p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
  font-weight: 700;
}

.payment-filters {
  margin-bottom: 16px;
  padding: 16px 18px;
  border-radius: 18px;
  background: #fbfffd;
  border: 1px solid var(--border);
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1fr;
  gap: 12px;
}

.payment-filter-field {
  display: grid;
  gap: 8px;
}

.payment-filter-field label {
  font-size: 13px;
  font-weight: 900;
  color: var(--text-dark);
}

.payment-filter-field input,
.payment-filter-field select {
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

.payment-filter-summary {
  grid-column: 1 / -1;
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.payment-empty-result {
  display: none;
  margin-top: 14px;
  padding: 18px 20px;
  border-radius: 18px;
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  color: var(--text-muted);
  text-align: center;
  font-weight: 700;
}

.payment-empty-result.is-visible {
  display: block;
}

.payments-table td {
  vertical-align: top;
}

.payment-stack {
  display: grid;
  gap: 6px;
}

.payment-stack strong {
  color: var(--text-dark);
}

.payment-stack span {
  color: var(--text-muted);
  line-height: 1.45;
}

.payment-pill {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
}

.payment-pill-method {
  background: var(--primary-soft);
  color: var(--primary-dark);
}

.payment-pill-status {
  background: #eef2ff;
  color: #3730a3;
}

.payment-status-paid {
  background: #dcfce7;
  color: #166534;
}

.payment-status-pending {
  background: #fef3c7;
  color: #92400e;
}

.payment-status-failed,
.payment-status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 980px) {
  .home-stats,
  .charts-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 980px) {
  .payment-filters {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .admin-stats {
    padding: 16px 12px 44px;
  }

  .admin-stats .home-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .stats-group {
    padding: 22px;
    border-radius: 20px;
  }

  .stats-group-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .stats-toggle-btn,
  .home-btn {
    width: 100%;
  }

  .chart-section {
    border-radius: 20px;
  }

  .chart-container {
    height: 280px;
  }

  .payment-filters {
    grid-template-columns: 1fr;
    padding: 14px;
  }
}

@media (max-width: 560px) {
  .home-stats,
  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>