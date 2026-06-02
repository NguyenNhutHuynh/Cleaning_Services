<?php
use App\Core\View;
/** @var array $job */
/** @var bool $hasReport */
/** @var string $csrf */
?>
<section class="home-container">
  <header class="home-hero">
    <p class="home-kicker">WORKER • BÁO CÁO</p>
    <h1>Báo cáo hoàn thành job #<?= View::e($job['id'] ?? '') ?></h1>
    <p>Ghi lại khó khăn và ghi chú sau khi hoàn thành công việc.</p>
  </header>

  <section class="home-feature">
    <h2>Thông tin job</h2>
    <div class="review-box">
      <p><strong>Khách hàng:</strong> <?= View::e($job['user_name'] ?? '') ?></p>
      <?php
        $loc = trim((string)($job['location'] ?? ''));
        $cust = trim((string)($job['customer_address'] ?? $job['user_address'] ?? ''));
      ?>
      <p><strong>Địa chỉ:</strong> <?= View::e($loc !== '' ? $loc : $cust) ?></p>
      <?php if ($loc !== '' && $cust !== '' && $loc !== $cust): ?>
        <p style="font-size:13px;color:#6b7b82">Khách: <?= View::e($cust) ?></p>
      <?php endif; ?>
      <p><strong>Dịch vụ:</strong> <?= View::e($job['service_name'] ?? '') ?></p>
      <p><strong>Tiền thu khách:</strong>
        <?php if (in_array($job['status'] ?? '', ['confirmed', 'accepted', 'in_progress', 'completed'], true)): ?>
          Đã thanh toán
        <?php else: ?>
          <?= number_format((float)($job['service_price'] ?? 0), 0, ',', '.') ?>đ
        <?php endif; ?>
      </p>
    </div>
  </section>

  <section class="home-feature">
    <h2>Gửi báo cáo</h2>
    <div class="review-box">
      <?php if ($hasReport): ?>
        <p>Bạn đã gửi báo cáo cho job này.</p>
        <a class="home-btn" href="/worker/jobs">Quay lại danh sách job</a>
      <?php else: ?>
        <form method="post" action="/worker/jobs/<?= (int)$job['id'] ?>/report">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <label>Khó khăn gặp phải</label>
          <textarea name="difficulties" rows="4" style="width:100%;padding:10px;border-radius:8px;margin:8px 0 12px;"></textarea>

          <label>Ghi chú thêm</label>
          <textarea name="note" rows="4" style="width:100%;padding:10px;border-radius:8px;margin:8px 0 12px;"></textarea>

          <button type="submit" class="home-btn">Gửi báo cáo hoàn thành</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</section>
