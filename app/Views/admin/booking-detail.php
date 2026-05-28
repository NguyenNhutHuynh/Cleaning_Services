<?php
use App\Core\View;
use App\Models\BookingProgress;
/** @var array $booking */
/** @var array $progress */
/** @var array $messages */
/** @var array $adminWorkerMessages */
/** @var array|null $payment */
/** @var array|null $customerPayment */
/** @var array|null $customerPaidTransaction */
/** @var array|null $report */
/** @var array|null $review */
/** @var array $workers */

$bookingStatusMap = [
  'pending' => 'Chờ thanh toán',
  'paid' => 'Đã thanh toán',
  'confirmed' => 'Đã xác nhận',
  'accepted' => 'Đang thực hiện',
  'in_progress' => 'Đang thực hiện',
  'completed' => 'Đã hoàn thành',
  'cancelled' => 'Đã hủy',
];

$isCustomerPaid = $customerPayment !== null && (($customerPayment['status'] ?? '') === 'paid');
$assignedWorkerId = (int)($booking['assigned_worker_id'] ?? 0);
$bookingStatus = (string)($booking['status'] ?? '');
$bookingStatusLabel = $bookingStatusMap[$bookingStatus] ?? 'Không rõ';
?>

<style>
.admin-booking-detail {
  --primary: #2eaf7d;
  --primary-dark: #16805a;
  --primary-soft: #e8f7f0;
  --bg-soft: #f7fdf9;
  --text-dark: #1f2d3d;
  --text-muted: #546e7a;
  --border: #dcefe6;
  --white: #ffffff;
  --danger: #dc2626;
  --danger-soft: #fff1f1;
  --warning: #d97706;
  --warning-soft: #fff7ed;
  --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
  --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

  max-width: 1180px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  display: grid;
  gap: 24px;
  color: var(--text-dark);
}

.admin-booking-detail * {
  box-sizing: border-box;
}

.booking-detail-hero {
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

.booking-detail-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.booking-detail-hero .home-kicker,
.booking-detail-hero h1,
.booking-detail-hero p,
.booking-detail-hero .hero-back {
  position: relative;
  z-index: 1;
}

.booking-detail-hero .home-kicker {
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

.booking-detail-hero h1 {
  margin: 0 0 12px;
  color: var(--text-dark);
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.booking-detail-hero p {
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
  line-height: 1.6;
}

.hero-back {
  margin-top: 24px;
}

.home-feature {
  padding: 30px;
  border-radius: 26px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.home-feature h2 {
  margin: 0 0 22px;
  color: var(--text-dark);
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.review-box {
  display: grid;
  gap: 14px;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 14px;
}

.meta-item {
  padding: 16px;
  border-radius: 18px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
  color: var(--text-muted);
  line-height: 1.6;
}

.meta-item strong {
  display: block;
  margin-bottom: 6px;
  color: var(--text-dark);
  font-weight: 900;
}

.status-chip {
  display: inline-flex;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 13px;
  font-weight: 900;
  text-transform: capitalize;
}

.payment-box,
.assign-box,
.empty-box {
  padding: 18px;
  border-radius: 20px;
  background: #fcfffd;
  border: 1px solid var(--border);
}

.payment-box p,
.assign-box p,
.empty-box p {
  margin: 0;
  line-height: 1.6;
  color: var(--text-muted);
}

.payment-paid {
  color: #065f46;
  font-weight: 900;
}

.payment-inline {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.payment-detail-link {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: white;
  color: var(--primary);
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.payment-detail-link:hover {
  background: var(--primary-soft);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.payment-unpaid {
  color: #991b1b;
  font-weight: 900;
}

.assign-form {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  margin-top: 12px;
}

.worker-select {
  min-height: 44px;
  min-width: 260px;
  padding: 11px 14px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: white;
  color: var(--text-dark);
  font-weight: 800;
}

.worker-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

.note-danger,
.note-success,
.note-warning {
  margin-top: 10px !important;
  padding: 12px 14px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 800;
}

.note-danger {
  background: var(--danger-soft);
  color: #991b1b !important;
  border: 1px solid #fecaca;
}

.note-success {
  background: var(--primary-soft);
  color: var(--primary-dark) !important;
  border: 1px solid #bdebd7;
}

.note-warning {
  background: var(--warning-soft);
  color: var(--warning) !important;
  border: 1px solid #fed7aa;
}

.home-btn {
  min-height: 44px;
  padding: 11px 22px;
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
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.home-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(46,175,125,0.28);
}

.home-btn:disabled {
  opacity: 0.55;
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

.timeline-list,
.message-list {
  display: grid;
  gap: 14px;
}

.timeline-item,
.message-item {
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: #fcfffd;
  box-shadow: 0 5px 16px rgba(31,45,61,0.05);
}

.timeline-item p,
.message-item p {
  margin: 0 0 8px;
  color: var(--text-muted);
  line-height: 1.6;
}

.timeline-item p:last-child,
.message-item p:last-child {
  margin-bottom: 0;
}

.timeline-item strong,
.message-item strong {
  color: var(--text-dark);
  font-weight: 900;
}

.timeline-time,
.message-time {
  color: #78909c;
  font-size: 13px;
  font-weight: 700;
}

.photo-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.photo-list img {
  width: 112px;
  height: 112px;
  object-fit: cover;
  border-radius: 16px;
  border: 1px solid var(--border);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.photo-list img:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: var(--shadow-sm);
}

.report-box {
  background: var(--warning-soft);
  padding: 16px;
  border-radius: 18px;
  border-left: 4px solid var(--warning);
}

.report-box p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.review-stars {
  color: #ffb400;
  font-size: 1.3rem;
  letter-spacing: 1px;
}

.message-form {
  margin-top: 12px;
  display: grid;
  gap: 10px;
}

.message-form label {
  font-weight: 900;
  color: var(--text-dark);
}

.message-form textarea {
  width: 100%;
  min-height: 96px;
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: #fcfffd;
  color: var(--text-dark);
  font-family: inherit;
  font-size: 15px;
  resize: vertical;
}

.message-form textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

@media (max-width: 768px) {
  .admin-booking-detail {
    padding: 16px 12px 44px;
  }

  .booking-detail-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .home-feature {
    padding: 22px;
    border-radius: 20px;
  }

  .assign-form,
  .worker-select,
  .home-btn {
    width: 100%;
  }

  .photo-list img {
    width: 96px;
    height: 96px;
  }
}
</style>

<section class="home-container admin-booking-detail booking-detail-page">
  <header class="home-hero booking-detail-hero">
    <p class="home-kicker">ADMIN • CHI TIẾT ĐƠN</p>
    <h1>Đơn #<?= (int)($booking['id'] ?? 0) ?></h1>
    <p>Theo dõi toàn bộ quá trình worker thực hiện công việc.</p>
    <div class="hero-back">
      <a class="home-btn home-btn-outline" href="/admin/bookings">Quay lại danh sách đơn</a>
    </div>
  </header>

  <section class="home-feature">
    <h2>Thông tin tổng quan</h2>
    <div class="review-box">
      <div class="meta-grid">
        <div class="meta-item">
          <strong>Dịch vụ</strong>
          <?= View::e((string)($booking['service_name'] ?? '')) ?>
        </div>

        <div class="meta-item">
          <strong>Khách hàng</strong>
          <?= View::e((string)($booking['user_name'] ?? '')) ?> (<?= View::e((string)($booking['user_phone'] ?? '')) ?>)
        </div>

        <div class="meta-item">
          <strong>Địa chỉ</strong>
          <?= View::e((string)($booking['location'] ?? '')) ?>
        </div>

        <div class="meta-item">
          <strong>Lịch làm</strong>
          <?= View::e((string)($booking['date'] ?? '')) ?> <?= View::e((string)($booking['time'] ?? '')) ?>
        </div>

        <div class="meta-item">
          <strong>Worker</strong>
          <?= View::e((string)($booking['worker_name'] ?? 'Chưa phân công')) ?> (<?= View::e((string)($booking['worker_phone'] ?? '')) ?>)
        </div>

        <div class="meta-item">
          <strong>Trạng thái</strong>
          <span class="status-chip"><?= View::e($bookingStatusLabel) ?></span>
        </div>
      </div>

      <div class="payment-box">
        <?php if ($customerPaidTransaction !== null): ?>
          <p class="payment-inline">
            <strong>Thanh toán khách:</strong>
            <span class="payment-paid">Đã thanh toán</span>
            <a class="payment-detail-link" href="/admin/payment-transactions/<?= (int)$customerPaidTransaction['id'] ?>">Xem chi tiết giao dịch</a>
          </p>
          <p><strong>Thời gian thanh toán:</strong> <?= View::e((string)($customerPaidTransaction['paid_at'] ?? '')) ?></p>
        <?php elseif ($customerPayment !== null): ?>
          <p><strong>Thanh toán khách:</strong> <span class="payment-unpaid">Chưa thanh toán</span></p>
          <p><strong>Số tiền cần thanh toán:</strong> <?= number_format((float)($customerPayment['amount'] ?? ($booking['service_price'] ?? 0)), 0, ',', '.') ?>đ</p>
        <?php elseif ($payment !== null): ?>
          <p><strong>Thanh toán:</strong> <?= number_format((float)($payment['service_price'] ?? 0), 0, ',', '.') ?>đ</p>
        <?php else: ?>
          <p><strong>Thanh toán khách:</strong> <span class="payment-unpaid">Chưa thanh toán</span></p>
          <p><strong>Số tiền cần thanh toán:</strong> <?= number_format((float)($booking['service_price'] ?? 0), 0, ',', '.') ?>đ</p>
        <?php endif; ?>
      </div>

      <div class="assign-box">
        <p><strong>Phân công worker:</strong></p>

        <form method="POST" action="/admin/bookings/assign" class="assign-form">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)($booking['id'] ?? 0) ?>">
          <input type="hidden" name="return_to" value="/admin/bookings/<?= (int)($booking['id'] ?? 0) ?>">

          <select name="worker_id" class="worker-select" <?= $isCustomerPaid ? '' : 'disabled' ?>>
            <option value="">-- Chọn worker --</option>
            <?php foreach (($workers ?? []) as $worker): ?>
              <option value="<?= (int)$worker['id'] ?>" <?= $assignedWorkerId === (int)$worker['id'] ? 'selected' : '' ?>>
                Worker #<?= (int)$worker['id'] ?> · <?= View::e((string)$worker['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="home-btn home-btn-outline" type="submit" name="action" value="assign" <?= $isCustomerPaid ? '' : 'disabled' ?>>Gán worker</button>
            <button class="home-btn" type="submit" name="action" value="auto_assign" <?= $isCustomerPaid ? '' : 'disabled' ?>>Tự động gán</button>
          </div>
        </form>

        <?php if (!$isCustomerPaid): ?>
          <p class="note-danger">Chỉ gán được worker sau khi khách thanh toán thành công.</p>
        <?php elseif ($assignedWorkerId > 0): ?>
          <p class="note-success">Đơn đã có worker, bạn có thể đổi sang worker khác nếu cần.</p>
        <?php else: ?>
          <p class="note-warning">Đơn đã thanh toán, bạn có thể phân công worker ngay tại đây.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="home-feature">
    <h2>Worker đã làm gì (tiến độ)</h2>
    <div class="review-box">
      <?php if (empty($progress)): ?>
        <div class="empty-box">
          <p>Worker chưa cập nhật tiến độ.</p>
        </div>
      <?php else: ?>
        <div class="timeline-list">
          <?php foreach ($progress as $item): ?>
            <article class="timeline-item">
              <p>
                <strong><?= View::e(BookingProgress::stepLabel((string)($item['step'] ?? ''))) ?></strong>
                <span class="timeline-time">• <?= View::e((string)($item['created_at'] ?? '')) ?></span>
              </p>

              <?php if (!empty($item['note'])): ?>
                <p><?= View::e((string)$item['note']) ?></p>
              <?php endif; ?>

              <?php if (!empty($item['photos'])): ?>
                <div class="photo-list">
                  <?php foreach ($item['photos'] as $photo): ?>
                    <a href="<?= View::e((string)$photo) ?>" target="_blank" rel="noopener">
                      <img src="<?= View::e((string)$photo) ?>" alt="progress">
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="home-feature">
    <h2>Báo cáo hoàn thành</h2>
    <div class="review-box">
      <?php if ($report === null): ?>
        <div class="empty-box">
          <p>Worker chưa gửi báo cáo hoàn thành.</p>
        </div>
      <?php else: ?>
        <?php if (!empty($report['difficulties'])): ?>
          <div class="report-box">
            <p style="white-space: pre-wrap;"><?= View::e((string)$report['difficulties']) ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($report['summary'])): ?>
          <p><strong>Tóm tắt:</strong> <?= View::e((string)$report['summary']) ?></p>
        <?php endif; ?>

        <p><small>Gửi lúc: <?= View::e((string)($report['created_at'] ?? '')) ?></small></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="home-feature">
    <h2>Đánh giá của khách hàng</h2>
    <div class="review-box">
      <?php if ($review === null): ?>
        <div class="empty-box">
          <p>Khách hàng chưa gửi đánh giá.</p>
        </div>
      <?php else: ?>
        <p>
          <strong>Điểm:</strong>
          <span class="review-stars"><?= str_repeat('★', (int)($review['rating'] ?? 0)) ?></span>
          (<?= (int)($review['rating'] ?? 0) ?>/5)
        </p>

        <?php if (!empty($review['comment'])): ?>
          <p><strong>Nội dung:</strong> "<?= View::e((string)$review['comment']) ?>"</p>
        <?php endif; ?>

        <p><small>Đánh giá lúc: <?= View::e((string)($review['created_at'] ?? '')) ?></small></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="home-feature" id="admin-worker-messages">
    <h2>Trao đổi Admin - Worker</h2>
    <div class="review-box">
      <?php if (empty($adminWorkerMessages)): ?>
        <div class="empty-box">
          <p>Chưa có tin nhắn giữa admin và worker.</p>
        </div>
      <?php else: ?>
        <div class="message-list">
          <?php foreach ($adminWorkerMessages as $message): ?>
            <article class="message-item">
              <p>
                <strong><?= View::e((string)($message['sender_name'] ?? '')) ?></strong>
                (<?= View::e((string)($message['sender_role'] ?? '')) ?>)
              </p>
              <p><?= View::e((string)($message['content'] ?? '')) ?></p>
              <small class="message-time"><?= View::e((string)($message['created_at'] ?? '')) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="/admin/bookings/<?= (int)($booking['id'] ?? 0) ?>/message" class="message-form">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
        <label for="admin-message-content">Nhắn tin cho worker</label>
        <textarea id="admin-message-content" name="content" rows="3" required placeholder="Nhập tin nhắn..."></textarea>
        <button class="home-btn" type="submit">Gửi tin nhắn</button>
      </form>
    </div>
  </section>

  <section class="home-feature" id="booking-messages">
    <h2>Trao đổi khách - worker</h2>
    <div class="review-box">
      <?php if (empty($messages)): ?>
        <div class="empty-box">
          <p>Chưa có tin nhắn trong đơn này.</p>
        </div>
      <?php else: ?>
        <div class="message-list">
          <?php foreach ($messages as $message): ?>
            <article class="message-item">
              <p>
                <strong><?= View::e((string)($message['sender_name'] ?? '')) ?></strong>
                (<?= View::e((string)($message['sender_role'] ?? '')) ?>)
              </p>
              <p><?= View::e((string)($message['content'] ?? '')) ?></p>
              <small class="message-time"><?= View::e((string)($message['created_at'] ?? '')) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</section>