<?php
use App\Core\View;
/** @var array $bookings Danh sách đơn đặt */
/** @var string $csrf */

$statusLabels = [
    'pending'   => '<span class="booking-status booking-status-pending">⏳ Chờ thanh toán</span>',
    'paid'      => '<span class="booking-status booking-status-paid">✅ Đã thanh toán</span>',
    'confirmed' => '<span class="booking-status booking-status-paid">✅ Đã xác nhận (Đã thanh toán)</span>',
    'accepted'  => '<span class="booking-status booking-status-accepted">👷 Đang thực hiện</span>',
    'completed' => '<span class="booking-status booking-status-completed">🌟 Đã hoàn thành</span>',
    'cancelled' => '<span class="booking-status booking-status-cancelled">❌ Đã hủy</span>',
];

$serviceOptions = [];
foreach ($bookings as $bookingOption) {
  $serviceName = trim((string)($bookingOption['service_name'] ?? ''));
  if ($serviceName !== '') {
    $serviceOptions[$serviceName] = true;
  }
}
$serviceOptions = array_keys($serviceOptions);
?>

<style>
.bookings-page {
  --primary: #2eaf7d;
  --primary-dark: #16805a;
  --primary-soft: #e8f7f0;
  --bg-soft: #f7fdf9;
  --text-dark: #1f2d3d;
  --text-muted: #546e7a;
  --border: #dcefe6;
  --white: #ffffff;
  --blue: #2563eb;
  --red: #dc2626;
  --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
  --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

  max-width: 1180px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  color: var(--text-dark);
}

.bookings-page * {
  box-sizing: border-box;
}

.bookings-page .home-hero {
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

.bookings-page .home-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.bookings-page .home-kicker {
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

.bookings-page .home-hero h1 {
  position: relative;
  margin: 0 0 12px;
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
  color: var(--text-dark);
}

.bookings-page .home-hero p {
  position: relative;
  margin: 0;
  font-size: 17px;
  color: var(--text-muted);
}

.hero-actions {
  position: relative;
  margin-top: 24px;
}

.booking-btn {
  min-height: 46px;
  padding: 12px 18px;
  border-radius: 14px;
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

.booking-btn:hover {
  transform: translateY(-2px);
}

.booking-btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  box-shadow: 0 10px 22px rgba(46,175,125,0.22);
}

.booking-btn-blue {
  background: var(--blue);
  color: white;
  box-shadow: 0 10px 22px rgba(37,99,235,0.18);
}

.booking-btn-outline {
  background: white;
  color: var(--primary);
  border: 1.5px solid var(--primary);
}

.booking-btn-danger {
  background: white;
  color: var(--red);
  border: 1.5px solid var(--red);
}

.booking-btn-danger:hover {
  background: #fff1f1;
}

.bookings-section {
  margin-top: 40px;
  padding: 34px;
  border-radius: 26px;
  background: white;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.bookings-section h2 {
  margin: 0 0 24px;
  color: var(--text-dark);
  font-size: clamp(24px, 3vw, 34px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.booking-filters {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 20px;
  padding: 18px;
  border-radius: 20px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
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
  background: white;
  color: var(--text-dark);
  font: inherit;
  font-weight: 700;
}

.booking-filter-note {
  grid-column: 1 / -1;
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.booking-empty-result {
  display: none;
  margin: 18px 0 0;
  padding: 18px 20px;
  border-radius: 18px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  color: var(--text-muted);
  text-align: center;
  font-weight: 700;
}

.booking-empty-result.is-visible {
  display: block;
}

.booking-list {
  display: grid;
  gap: 18px;
}

.booking-empty {
  padding: 34px 20px;
  text-align: center;
  color: var(--text-muted);
  background: var(--bg-soft);
  border: 1px dashed #cfe3d8;
  border-radius: 20px;
}

.booking-empty a {
  color: var(--primary);
  font-weight: 900;
  text-decoration: none;
}

.booking-card {
  padding: 24px;
  border-radius: 22px;
  border: 1px solid var(--border);
  background: #ffffff;
  box-shadow: 0 5px 16px rgba(31,45,61,0.05);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.booking-card:hover {
  transform: translateY(-4px);
  border-color: rgba(46,175,125,0.45);
  box-shadow: var(--shadow-md);
}

.booking-card.is-cancelled {
  opacity: 0.72;
  background: #f9fafb;
  border-color: #e5e7eb;
}

.booking-card-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 18px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}

.booking-head-left {
  display: grid;
  gap: 10px;
  text-align: left;
  flex: 1;
}

.booking-code {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 7px 14px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 14px;
  font-weight: 900;
}

.booking-date,
.booking-location {
  margin: 0;
  text-align: left;
}

.booking-date {
  color: var(--text-dark);
  font-size: 16px;
  font-weight: 800;
}

.booking-location {
  color: var(--text-muted);
  line-height: 1.6;
}

.booking-head-right {
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
}

.booking-status {
  display: inline-flex;
  align-items: center;
  padding: 7px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 900;
  white-space: nowrap;
}

.booking-status-pending {
  background: #fff7ed;
  color: #c2410c;
}

.booking-status-paid {
  background: #e8f7f0;
  color: #16805a;
}

.booking-status-accepted {
  background: #eff6ff;
  color: #2563eb;
}

.booking-status-completed {
  background: #f5f3ff;
  color: #7c3aed;
}

.booking-status-cancelled {
  background: #fff1f1;
  color: #dc2626;
}

.booking-body {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 18px;
  align-items: start;
}

.booking-main {
  display: grid;
  gap: 14px;
}

.booking-info-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.booking-info-item {
  padding: 14px 16px;
  border-radius: 16px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
  text-align: left;
}

.booking-info-label {
  display: block;
  margin-bottom: 6px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
}

.booking-info-value {
  display: block;
  color: var(--text-dark);
  font-weight: 900;
  line-height: 1.5;
  text-align: left;
}

.booking-price {
  color: var(--primary);
}

.booking-description {
  margin: 0;
  padding: 14px 16px;
  border-radius: 16px;
  background: #fcfffd;
  border: 1px solid var(--border);
  color: var(--text-muted);
  line-height: 1.6;
  text-align: left;
}

.booking-side {
  display: grid;
  gap: 14px;
}

.booking-side-box {
  padding: 16px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: #fbfefd;
}

.booking-side-title {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 900;
  color: var(--text-dark);
  text-align: left;
}

.booking-meta-stack {
  display: grid;
  gap: 10px;
}

.booking-paid-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 8px 14px;
  background: #d1fae5;
  color: #065f46;
  font-weight: 900;
  border-radius: 999px;
  border: 1px solid #34d399;
  white-space: nowrap;
  width: fit-content;
}

.booking-actions {
  display: grid;
  gap: 10px;
}

.booking-actions-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.booking-actions form {
  margin: 0;
}

.booking-actions .booking-btn {
  width: 100%;
}

@media (max-width: 992px) {
  .booking-body {
    grid-template-columns: 1fr;
  }

  .booking-info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .bookings-page {
    padding: 16px 12px 44px;
  }

  .bookings-page .home-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .bookings-section {
    padding: 22px;
    border-radius: 20px;
  }

  .booking-card {
    padding: 20px;
    border-radius: 18px;
  }

  .booking-card-head {
    flex-direction: column;
    align-items: stretch;
  }

  .booking-filters {
    grid-template-columns: 1fr;
    padding: 14px;
  }

  .booking-head-right {
    justify-content: flex-start;
  }

  .booking-actions-row {
    grid-template-columns: 1fr;
  }

  .booking-paid-chip,
  .booking-actions,
  .booking-actions form,
  .booking-btn {
    width: 100%;
  }
}
</style>

<section class="home-container bookings-page">
  <header class="home-hero">
    <p class="home-kicker">TÀI KHOẢN • KHÁCH HÀNG</p>
    <h1>Đơn đặt của bạn</h1>
    <p>Xem trạng thái và chi tiết các lịch đã đặt.</p>

    <div class="hero-actions">
      <a class="booking-btn booking-btn-primary" href="/book">Đặt lịch mới</a>
    </div>
  </header>

  <section class="bookings-section">
    <h2>Danh sách đơn đặt</h2>

    <div class="booking-filters" id="bookingFilters">
      <div class="booking-filter-field">
        <label for="bookingSearch">Tìm kiếm</label>
        <input type="search" id="bookingSearch" placeholder="Mã đơn, dịch vụ, địa chỉ, mô tả...">
      </div>

      <div class="booking-filter-field">
        <label for="bookingStatusFilter">Trạng thái</label>
        <select id="bookingStatusFilter">
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
        <label for="bookingPaymentFilter">Thanh toán</label>
        <select id="bookingPaymentFilter">
          <option value="all">Tất cả</option>
          <option value="paid">Đã thanh toán</option>
          <option value="pending">Chưa thanh toán</option>
        </select>
      </div>

      <div class="booking-filter-field">
        <label for="bookingServiceFilter">Dịch vụ</label>
        <select id="bookingServiceFilter">
          <option value="all">Tất cả</option>
          <?php foreach ($serviceOptions as $serviceName): ?>
            <option value="<?= View::e(mb_strtolower($serviceName)) ?>"><?= View::e($serviceName) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <p class="booking-filter-note">Bộ lọc này hoạt động ngay trên trình duyệt để bạn tìm đơn nhanh hơn.</p>
    </div>

    <div class="booking-list">
      <?php if (empty($bookings)): ?>
        <p class="booking-empty">
          Bạn chưa có đơn đặt nào. <a href="/book">Đặt lịch ngay</a>.
        </p>
      <?php else: ?>
        <?php foreach ($bookings as $b): ?>
          <?php 
            $status = $b['status'] ?? '';
            $isCustomerPaid = !empty($b['is_customer_paid']);
            $displayStatus = $isCustomerPaid ? 'paid' : $status;
            $isCancelled = ($status === 'cancelled');
            $serviceName = trim((string)($b['service_name'] ?? ''));
                $searchText = trim(strtolower(
                  (string)($b['id'] ?? '') . ' ' .
                  $serviceName . ' ' .
                  (string)($b['location'] ?? '') . ' ' .
                  (string)($b['customer_address'] ?? $b['user_address'] ?? '') . ' ' .
                  (string)($b['description'] ?? '')
                ));
          ?>

          <article
            class="booking-card <?= $isCancelled ? 'is-cancelled' : '' ?>"
            data-search="<?= View::e($searchText) ?>"
            data-status="<?= View::e((string)$displayStatus) ?>"
            data-payment="<?= $isCustomerPaid ? 'paid' : 'pending' ?>"
            data-service="<?= View::e(mb_strtolower($serviceName)) ?>"
          >
            <div class="booking-card-head">
              <div class="booking-head-left">
                <span class="booking-code">#<?= View::e($b['id']) ?></span>
                <?php
                  $bookingDate = (string)($b['date'] ?? $b['work_date'] ?? '');
                  $bookingTime = (string)($b['time'] ?? $b['work_time'] ?? '');
                ?>
                <p class="booking-date">📅 <?= View::e($bookingDate) ?> • <?= View::e($bookingTime) ?></p>
                <?php $loc = trim((string)($b['location'] ?? '')); $cust = trim((string)($b['customer_address'] ?? $b['user_address'] ?? '')); ?>
                <p class="booking-location">📍 <?= View::e($loc !== '' ? $loc : $cust) ?></p>
              </div>

              <div class="booking-head-right">
                <?= $statusLabels[$displayStatus] ?? '<span class="booking-status" style="background:#f3f4f6;color:#6b7280;">' . View::e($displayStatus) . '</span>' ?>
              </div>
            </div>

            <div class="booking-body">
              <div class="booking-main">
                <div class="booking-info-grid">
                  <?php if (!empty($b['service_name'])): ?>
                    <div class="booking-info-item">
                      <span class="booking-info-label">Dịch vụ</span>
                      <span class="booking-info-value"><?= View::e($b['service_name']) ?></span>
                    </div>
                  <?php endif; ?>

                  <?php if (isset($b['quantity']) && (float)$b['quantity'] > 0): ?>
                    <div class="booking-info-item">
                      <span class="booking-info-label">Khối lượng</span>
                      <span class="booking-info-value">
                        <?= View::e((string)$b['quantity']) ?> <?= View::e((string)($b['measure_unit'] ?? '')) ?>
                      </span>
                    </div>
                  <?php endif; ?>

                  <div class="booking-info-item">
                    <span class="booking-info-label">Thành tiền tạm tính</span>
                    <span class="booking-info-value booking-price">
                      <?= number_format((float)($b['service_price'] ?? 0), 0, ',', '.') ?>đ
                    </span>
                  </div>
                </div>

                <?php if (!empty($b['description'])): ?>
                  <p class="booking-description"><?= View::e($b['description']) ?></p>
                <?php endif; ?>
              </div>

              <div class="booking-side">
                <div class="booking-side-box">
                  <h3 class="booking-side-title">Trạng thái thanh toán</h3>
                  <div class="booking-meta-stack">
                    <?php if ($isCustomerPaid || $status === 'paid' || $status === 'confirmed'): ?>
                      <span class="booking-paid-chip">✅ Đã thanh toán</span>
                    <?php elseif ($status === 'pending'): ?>
                      <span class="booking-status booking-status-pending">⏳ Chờ thanh toán</span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="booking-side-box">
                  <h3 class="booking-side-title">Thao tác</h3>
                  <div class="booking-actions">
                    <a class="booking-btn booking-btn-primary" href="/bookings/<?= (int)$b['id'] ?>">Theo dõi đơn</a>

                    <?php if ($status === 'pending' && !$isCustomerPaid): ?>
                      <form method="post" action="/bookings/<?= (int)$b['id'] ?>/repay">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <button type="submit" class="booking-btn booking-btn-blue">
                          💳 Thanh toán ngay
                        </button>
                      </form>
                    <?php endif; ?>

                    <div class="booking-actions-row">
                      <?php if (in_array($status, ['pending', 'confirmed', 'accepted'], true)): ?>
                        <form method="post" action="/bookings/<?= (int)$b['id'] ?>/cancel">
                          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                          <button
                            type="submit"
                            class="booking-btn booking-btn-danger"
                            onclick="return confirm('Bạn có chắc muốn hủy đơn này không?');"
                          >
                            🗑️ Hủy đơn
                          </button>
                        </form>
                      <?php endif; ?>

                      <?php if ($status === 'completed' && empty($b['has_review'])): ?>
                        <a class="booking-btn booking-btn-outline" href="/bookings/<?= (int)$b['id'] ?>/review">
                          ⭐ Đánh giá
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>

      <p class="booking-empty-result" id="bookingNoResults">Không tìm thấy đơn nào khớp bộ lọc hiện tại.</p>
    </div>
  </section>
</section>

<script>
  (function () {
    const searchInput = document.getElementById('bookingSearch');
    const statusFilter = document.getElementById('bookingStatusFilter');
    const paymentFilter = document.getElementById('bookingPaymentFilter');
    const serviceFilter = document.getElementById('bookingServiceFilter');
    const cards = Array.from(document.querySelectorAll('.booking-card'));
    const noResults = document.getElementById('bookingNoResults');

    if (!searchInput || !statusFilter || !paymentFilter || !serviceFilter || cards.length === 0) return;

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applyFilters = () => {
      const query = normalize(searchInput.value);
      const status = statusFilter.value;
      const payment = paymentFilter.value;
      const service = serviceFilter.value;
      let visibleCount = 0;

      cards.forEach((card) => {
        const text = normalize(card.getAttribute('data-search'));
        const cardStatus = normalize(card.getAttribute('data-status'));
        const cardPayment = normalize(card.getAttribute('data-payment'));
        const cardService = normalize(card.getAttribute('data-service'));
        const matchesQuery = !query || text.includes(query);
        const matchesStatus = status === 'all' || cardStatus === status;
        const matchesPayment = payment === 'all' || cardPayment === payment;
        const matchesService = service === 'all' || cardService === service;
        const isVisible = matchesQuery && matchesStatus && matchesPayment && matchesService;

        card.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount += 1;
      });

      if (noResults) {
        noResults.classList.toggle('is-visible', visibleCount === 0);
      }
    };

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    paymentFilter.addEventListener('change', applyFilters);
    serviceFilter.addEventListener('change', applyFilters);
    applyFilters();
  })();
</script>