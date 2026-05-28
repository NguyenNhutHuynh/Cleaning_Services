<?php
use App\Core\View;
/** @var array $transaction */
/** @var array|null $booking */

$paymentMethod = (string)($transaction['payment_method'] ?? '');
$paymentMethodLabel = 'Không rõ';
if ($paymentMethod === 'customer_payment') {
  $paymentMethodLabel = 'Chuyển khoản';
} elseif ($paymentMethod === 'worker_payout') {
  $paymentMethodLabel = 'Trả lương worker';
} elseif ($paymentMethod === 'bank_transfer') {
  $paymentMethodLabel = 'Chuyển khoản';
} elseif ($paymentMethod !== '') {
  $paymentMethodLabel = $paymentMethod;
}

$status = (string)($transaction['status'] ?? '');
$statusLabel = $status !== '' ? $status : 'pending';
$statusLabelMap = [
  'paid' => 'Đã thanh toán',
  'pending' => 'Đang chờ',
  'failed' => 'Thất bại',
  'cancelled' => 'Đã hủy',
];
if (isset($statusLabelMap[$statusLabel])) {
  $statusLabel = $statusLabelMap[$statusLabel];
}

$bookingStatusMap = [
  'pending' => 'Chờ xử lý',
  'confirmed' => 'Đã xác nhận',
  'accepted' => 'Đã nhận việc',
  'in_progress' => 'Đang thực hiện',
  'completed' => 'Hoàn thành',
  'cancelled' => 'Đã hủy',
];

$rawData = (string)($transaction['webhook_raw_data'] ?? '');
$prettyRaw = '';
if ($rawData !== '') {
    $decoded = json_decode($rawData, true);
    if (is_array($decoded)) {
        $prettyRaw = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

$rawDisplay = $prettyRaw !== '' ? $prettyRaw : ($rawData !== '' ? $rawData : 'Chưa có dữ liệu webhook.');
?>

<style>
.admin-payment-detail {
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

.admin-payment-detail * {
  box-sizing: border-box;
}

.payment-hero {
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

.payment-hero::after {
  content: "";
  position: absolute;
  right: -70px;
  bottom: -70px;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(46,175,125,0.10);
}

.payment-hero .home-kicker,
.payment-hero h1,
.payment-hero p,
.payment-hero .hero-actions {
  position: relative;
  z-index: 1;
}

.payment-hero .home-kicker {
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

.payment-hero h1 {
  margin: 0 0 10px;
  font-size: clamp(30px, 5vw, 48px);
  font-weight: 900;
  letter-spacing: -0.04em;
}

.payment-hero p {
  margin: 0;
  color: var(--text-muted);
  font-size: 16px;
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

.home-btn-outline:hover {
  background: var(--primary-soft);
  box-shadow: var(--shadow-sm);
}

.detail-section {
  margin-top: 26px;
  padding: 24px;
  border-radius: 24px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.detail-section h2 {
  margin: 0 0 18px;
  font-size: 20px;
  font-weight: 900;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px 20px;
}

.info-item {
  display: grid;
  gap: 6px;
}

.info-label {
  font-size: 12px;
  font-weight: 900;
  color: var(--text-muted);
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.info-value {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-dark);
  word-break: break-word;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 12px;
  font-weight: 900;
  text-transform: capitalize;
}

.pre-box {
  margin: 0;
  padding: 16px;
  border-radius: 18px;
  background: #f8faf9;
  border: 1px solid var(--border);
  font-size: 12px;
  line-height: 1.55;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-word;
}

@media (max-width: 768px) {
  .admin-payment-detail {
    padding: 16px 12px 44px;
  }

  .payment-hero {
    padding: 40px 18px;
    border-radius: 22px;
  }

  .detail-section {
    padding: 20px;
    border-radius: 20px;
  }
}
</style>

<section class="home-container admin-payment-detail">
  <header class="home-hero payment-hero">
    <p class="home-kicker">ADMIN • GIAO DỊCH</p>
    <h1>Đơn hàng #<?= (int)($booking['id'] ?? 0) ?></h1>
    <p>Thông tin thanh toán của khách hàng được ghi nhận từ PayOS.</p>
    <div class="hero-actions">
      <?php if (!empty($booking['id'])): ?>
        <a class="home-btn home-btn-outline" href="/admin/bookings/<?= (int)$booking['id'] ?>">Xem đơn</a>
      <?php endif; ?>
      <a class="home-btn home-btn-outline" href="/admin/bookings">Quay lại danh sách đơn</a>
    </div>
  </header>

  <section class="detail-section">
    <h2>Tóm tắt thanh toán</h2>
    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">Trạng thái</span>
        <span class="info-value"><span class="status-pill"><?= View::e($statusLabel) ?></span></span>
      </div>
      <div class="info-item">
        <span class="info-label">Số tiền</span>
        <span class="info-value"><?= number_format((float)($transaction['amount'] ?? 0), 0, ',', '.') ?>đ</span>
      </div>
      <div class="info-item">
        <span class="info-label">Phương thức</span>
        <span class="info-value"><?= View::e($paymentMethodLabel) ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Mã đơn</span>
        <span class="info-value"><?= View::e((string)($transaction['order_code'] ?? '')) ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Mã giao dịch</span>
        <span class="info-value"><?= View::e((string)($transaction['transaction_id'] ?? '')) ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Số tài khoản</span>
        <span class="info-value"><?= View::e((string)($transaction['payer_account_number'] ?? '')) ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Thanh toán lúc</span>
        <span class="info-value"><?= View::e((string)($transaction['paid_at'] ?? '')) ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Tạo/Cập nhật</span>
        <span class="info-value">Tạo: <?= View::e((string)($transaction['created_at'] ?? '')) ?></span>
        <span class="info-value">Cập nhật: <?= View::e((string)($transaction['updated_at'] ?? '')) ?></span>
      </div>
    </div>
  </section>

  <?php if (!empty($booking)): ?>
    <section class="detail-section">
      <h2>Thông tin đơn</h2>
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Mã đơn</span>
          <span class="info-value">#<?= (int)($booking['id'] ?? 0) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Khách hàng</span>
          <span class="info-value"><?= View::e((string)($booking['user_name'] ?? '')) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Số điện thoại</span>
          <span class="info-value"><?= View::e((string)($booking['user_phone'] ?? '')) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Dịch vụ</span>
          <span class="info-value"><?= View::e((string)($booking['service_name'] ?? '')) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Giá dịch vụ</span>
          <span class="info-value"><?= number_format((float)($booking['service_price'] ?? 0), 0, ',', '.') ?>đ</span>
        </div>
        <div class="info-item">
          <span class="info-label">Lịch làm</span>
          <span class="info-value"><?= View::e((string)($booking['date'] ?? '')) ?> <?= View::e((string)($booking['time'] ?? '')) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Địa chỉ</span>
          <span class="info-value"><?= View::e((string)($booking['location'] ?? '')) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Trạng thái đơn</span>
          <?php
            $bookingStatus = (string)($booking['status'] ?? '');
            $bookingStatusLabel = $bookingStatusMap[$bookingStatus] ?? $bookingStatus;
          ?>
          <span class="info-value"><span class="status-pill"><?= View::e($bookingStatusLabel) ?></span></span>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- <section class="detail-section">
    <h2>Dữ liệu webhook</h2>
    <pre class="pre-box"><?= View::e($rawDisplay) ?></pre>
  </section> -->
</section>
