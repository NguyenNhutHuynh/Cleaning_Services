<?php
use App\Core\View;
use App\Models\BookingProgress;
/** @var array $booking */
/** @var array $progress */
/** @var array $messages */
/** @var array|null $payment */
/** @var bool $hasReview */
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

$bookingStatus = (string)($booking['status'] ?? '');
$bookingStatusLabel = !empty($isCustomerPaid) ? 'Đã thanh toán' : ($bookingStatusMap[$bookingStatus] ?? 'Không rõ');
?>

<style>
.booking-detail-page {
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

.booking-detail-page * {
  box-sizing: border-box;
}

.booking-detail-page .home-hero {
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

.booking-detail-page .home-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.booking-detail-page .home-kicker {
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

.booking-detail-page .home-hero h1 {
  position: relative;
  margin: 0 0 12px;
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
  color: var(--text-dark);
}

.booking-detail-page .home-hero p {
  position: relative;
  margin: 0;
  font-size: 17px;
  color: var(--text-muted);
}

.detail-layout {
  margin-top: 40px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

.detail-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 26px;
  padding: 30px;
  box-shadow: var(--shadow-sm);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.detail-card:hover {
  transform: translateY(-3px);
  border-color: rgba(46,175,125,0.38);
  box-shadow: var(--shadow-md);
}

.detail-card h2 {
  margin: 0 0 22px;
  color: var(--text-dark);
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
}

.info-item {
  padding: 16px;
  border-radius: 18px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
}

.info-label {
  display: block;
  margin-bottom: 6px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 800;
}

.info-value {
  color: var(--text-dark);
  font-size: 15px;
  font-weight: 900;
  line-height: 1.5;
}

.info-value.primary {
  color: var(--primary);
}

.progress-list,
.message-list {
  display: grid;
  gap: 16px;
}

.empty-state {
  padding: 24px;
  border-radius: 18px;
  background: var(--bg-soft);
  border: 1px dashed #cfe3d8;
  color: var(--text-muted);
  text-align: center;
  margin: 0;
}

.progress-item {
  position: relative;
  padding: 20px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: #fcfffd;
}

.progress-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.progress-step {
  color: var(--text-dark);
  font-weight: 900;
}

.progress-time {
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
}

.progress-note {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.progress-photos {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 14px;
}

.progress-photos img {
  width: 116px;
  height: 116px;
  object-fit: cover;
  border-radius: 16px;
  border: 1px solid var(--border);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.progress-photos img:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: var(--shadow-sm);
}

.arrival-box {
  padding: 20px;
  border-radius: 20px;
  background: linear-gradient(135deg, var(--primary-soft), #ffffff);
  border: 1px solid var(--border);
}

.arrival-box p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.arrival-box strong {
  color: var(--text-dark);
}

.arrival-time {
  color: var(--primary);
  font-weight: 900;
}

.message-item {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: #fcfffd;
}

.message-meta {
  margin: 0 0 8px;
  color: var(--text-dark);
  font-weight: 900;
}

.message-role {
  color: var(--text-muted);
  font-weight: 700;
}

.message-content {
  margin: 0 0 8px;
  color: var(--text-muted);
  line-height: 1.6;
}

.message-time {
  color: #78909c;
  font-size: 12px;
}

.message-form {
  margin-top: 18px;
}

.message-form textarea {
  width: 100%;
  min-height: 92px;
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid var(--border);
  font-family: inherit;
  font-size: 15px;
  color: var(--text-dark);
  background: #fcfffd;
  resize: vertical;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.message-form textarea:focus {
  outline: none;
  background: white;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

.detail-btn {
  margin-top: 12px;
  min-height: 46px;
  padding: 12px 24px;
  border-radius: 999px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  font-size: 15px;
  font-weight: 900;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 10px 22px rgba(46,175,125,0.22);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.detail-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(46,175,125,0.28);
}

.review-score {
  margin-bottom: 16px;
}

.review-stars {
  font-size: 28px;
  color: #ffc107;
}

.review-count {
  color: var(--text-muted);
  font-size: 15px;
  margin-left: 8px;
}

.review-comment {
  padding: 16px;
  border-radius: 18px;
  background: var(--bg-soft);
  border-left: 4px solid var(--primary);
  color: var(--text-muted);
  line-height: 1.6;
  font-style: italic;
}

.review-time {
  margin: 12px 0 0;
  color: #78909c;
  font-size: 13px;
}

@media (max-width: 768px) {
  .booking-detail-page {
    padding: 16px 12px 44px;
  }

  .booking-detail-page .home-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .detail-card {
    padding: 22px;
    border-radius: 20px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .detail-btn {
    width: 100%;
  }

  .progress-photos img {
    width: 96px;
    height: 96px;
  }
}
</style>

<section class="home-container booking-detail-page">
  <header class="home-hero">
    <p class="home-kicker">KHÁCH HÀNG • THEO DÕI ĐƠN</p>
    <h1>Đơn #<?= View::e($booking['id'] ?? '') ?></h1>
    <p>Theo dõi tiến độ làm việc, ảnh cập nhật và trao đổi trực tiếp với worker.</p>
  </header>

  <div class="detail-layout">
    <section class="detail-card">
      <h2>Thông tin đơn</h2>

      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Dịch vụ</span>
          <span class="info-value"><?= View::e($booking['service_name'] ?? '') ?></span>
        </div>

        <?php if (isset($booking['quantity']) && (float)$booking['quantity'] > 0): ?>
          <div class="info-item">
            <span class="info-label">Khối lượng</span>
            <span class="info-value">
              <?= View::e((string)$booking['quantity']) ?> <?= View::e((string)($booking['measure_unit'] ?? '')) ?>
            </span>
          </div>
        <?php endif; ?>

        <?php if (isset($booking['unit_price']) && (float)$booking['unit_price'] > 0): ?>
          <div class="info-item">
            <span class="info-label">Đơn giá</span>
            <span class="info-value primary">
              <?= number_format((float)$booking['unit_price'], 0, ',', '.') ?>đ/<?= View::e((string)($booking['measure_unit'] ?? '')) ?>
            </span>
          </div>
        <?php endif; ?>

        <div class="info-item">
          <span class="info-label">Địa chỉ</span>
          <?php
            $locationFull = trim((string)($booking['location'] ?? ''));
            $customerAddr = trim((string)($booking['customer_address'] ?? $booking['user_address'] ?? ''));
          ?>
          <span class="info-value" id="customerAddress">
            <?= View::e($locationFull !== '' ? $locationFull : $customerAddr) ?>
          </span>
          <?php if ($locationFull !== '' && $customerAddr !== '' && $locationFull !== $customerAddr): ?>
            <div style="margin-top:6px;font-size:13px;color:#6b7b82">Địa chỉ khách hàng: <?= View::e($customerAddr) ?></div>
          <?php endif; ?>
        </div>

        <div class="info-item">
          <span class="info-label">Thời gian</span>
          <span class="info-value"><?= View::e(($booking['date'] ?? '') . ' ' . ($booking['time'] ?? '')) ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Worker</span>
          <span class="info-value"><?= View::e($booking['worker_name'] ?? 'Chưa gán') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">SĐT worker</span>
          <span class="info-value"><?= View::e($booking['worker_phone'] ?? '') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Trạng thái</span>
          <span class="info-value primary"><?= View::e($bookingStatusLabel) ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Khách thanh toán</span>
          <span class="info-value primary">
            <?php if ($payment !== null): ?>
              <?= number_format((float)($payment['service_price'] ?? 0), 0, ',', '.') ?>đ
            <?php else: ?>
              <?= number_format((float)($booking['service_price'] ?? 0), 0, ',', '.') ?>đ
            <?php endif; ?>
          </span>
        </div>
      </div>
    </section>

    <section class="detail-card">
      <h2>Tiến độ công việc & ảnh cập nhật</h2>

      <div class="progress-list">
        <?php if (empty($progress)): ?>
          <p class="empty-state">Worker chưa cập nhật tiến độ.</p>
        <?php endif; ?>

        <?php foreach ($progress as $item): ?>
          <div class="progress-item">
            <div class="progress-head">
              <span class="progress-step"><?= View::e(BookingProgress::stepLabel((string)($item['step'] ?? ''))) ?></span>
              <span class="progress-time"><?= View::e($item['created_at'] ?? '') ?></span>
            </div>

            <?php if (!empty($item['note'])): ?>
              <p class="progress-note"><?= View::e($item['note']) ?></p>
            <?php endif; ?>

            <?php if (!empty($item['photos'])): ?>
              <div class="progress-photos">
                <?php foreach ($item['photos'] as $photo): ?>
                  <a href="<?= View::e($photo) ?>" target="_blank" rel="noopener">
                    <img src="<?= View::e($photo) ?>" alt="progress">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <?php if (!empty($booking['assigned_worker_id']) && in_array($booking['status'] ?? '', ['accepted', 'confirmed', 'in_progress'], true)): ?>
      <section class="detail-card">
        <h2>⏱️ Thời gian ước tính đến</h2>

        <div class="arrival-box">
          <?php if (!empty($booking['estimated_arrival_time'])): ?>
            <p>
              <strong>Worker sẽ đến lúc:</strong>
              <span class="arrival-time"><?= View::e($booking['estimated_arrival_time']) ?></span>
            </p>
            <p>Worker đã cập nhật thời gian dự kiến đến</p>
          <?php else: ?>
            <p><strong>Thời gian ước tính:</strong> Chưa có</p>
            <p>Worker chưa cập nhật thời gian dự kiến đến.</p>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="detail-card">
      <h2>Nhắn tin với worker</h2>

      <div class="message-list">
        <?php if (empty($messages)): ?>
          <p class="empty-state">Chưa có tin nhắn.</p>
        <?php endif; ?>

        <?php foreach ($messages as $message): ?>
          <div class="message-item">
            <p class="message-meta">
              <?= View::e($message['sender_name'] ?? '') ?>
              <span class="message-role">(<?= View::e($message['sender_role'] ?? '') ?>)</span>
            </p>
            <p class="message-content"><?= View::e($message['content'] ?? '') ?></p>
            <small class="message-time"><?= View::e($message['created_at'] ?? '') ?></small>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="post" action="/bookings/<?= (int)$booking['id'] ?>/message" class="message-form">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
        <textarea name="content" rows="2" required placeholder="Nhập tin nhắn..."></textarea>
        <button type="submit" class="detail-btn">Gửi tin nhắn</button>
      </form>
    </section>

    <?php if (($booking['status'] ?? '') === 'completed' && !empty($review)): ?>
      <section class="detail-card">
        <h2>✨ Đánh giá của bạn</h2>

        <div class="review-score">
          <p><strong>Điểm đánh giá:</strong></p>
          <div class="review-stars">
            <?= str_repeat('⭐', (int)($review['rating'] ?? 0)) ?>
            <span class="review-count">(<?= (int)($review['rating'] ?? 0) ?>/5)</span>
          </div>
        </div>

        <?php if (!empty($review['comment'])): ?>
          <p><strong>Bình luận của bạn:</strong></p>
          <div class="review-comment">
            "<?= View::e($review['comment']) ?>"
          </div>
        <?php endif; ?>

        <p class="review-time">Đánh giá vào: <?= View::e($review['created_at'] ?? '') ?></p>
      </section>
    <?php endif; ?>

    <?php if (($booking['status'] ?? '') === 'completed' && !$hasReview): ?>
      <section class="detail-card">
        <h2>Đánh giá sau khi hoàn thành</h2>
        <p>Đơn đã hoàn thành. Vui lòng đánh giá worker và để lại bình luận.</p>
        <a class="detail-btn" href="/bookings/<?= (int)$booking['id'] ?>/review">Đi tới trang đánh giá</a>
      </section>

      <script>
        setTimeout(function () {
          window.location.href = '/bookings/<?= (int)$booking['id'] ?>/review';
        }, 2200);
      </script>
    <?php endif; ?>
  </div>
</section>