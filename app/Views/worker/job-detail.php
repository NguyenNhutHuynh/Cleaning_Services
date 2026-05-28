<?php
use App\Core\View;
use App\Models\BookingProgress;
/** @var array $job */
/** @var array $progress */
/** @var array $messages */
/** @var array|null $payment */
/** @var string $csrf */

$liveMode = isset($_GET['live']) && $_GET['live'] === '1';
$status = $job['status'] ?? '';
$progressOrder = [
  BookingProgress::ON_THE_WAY => 'Trên đường đến',
  BookingProgress::ARRIVED => 'Đã đến',
  BookingProgress::BEFORE_PHOTO => 'Tiến hành chụp ảnh trước dọn dẹp',
  BookingProgress::AFTER_PHOTO => 'Tiến hành chụp ảnh sau dọn dẹp',
  BookingProgress::COMPLETED => 'Hoàn thành',
];
$latestStep = null;
if (!empty($progress)) {
  $lastItem = $progress[count($progress) - 1] ?? null;
  if (is_array($lastItem)) {
    $latestStep = (string)($lastItem['step'] ?? '');
  }
}
$progressKeys = array_keys($progressOrder);
$currentIndex = $latestStep === null || $latestStep === '' ? -1 : array_search($latestStep, $progressKeys, true);
$nextIndex = ($currentIndex === false) ? 0 : $currentIndex + 1;
$nextStep = $progressKeys[$nextIndex] ?? null;
$progressLocked = $nextStep === null;
$offlineMode = defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE;
?>

<style>
.worker-job-detail {
  --primary: #2eaf7d;
  --primary-dark: #16805a;
  --primary-soft: #e8f7f0;
  --bg-soft: #f7fdf9;
  --text-dark: #1f2d3d;
  --text-muted: #546e7a;
  --border: #dcefe6;
  --white: #ffffff;
  --blue: #2563eb;
  --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
  --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

  max-width: 1180px;
  margin: 0 auto;
  padding: 24px 16px 60px;
  color: var(--text-dark);
}

.worker-job-detail * {
  box-sizing: border-box;
}

.worker-job-detail .home-hero {
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

.worker-job-detail .home-hero::after {
  content: "";
  position: absolute;
  right: -80px;
  bottom: -80px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(46,175,125,0.13);
}

.worker-job-detail .home-kicker {
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

.worker-job-detail .home-hero h1 {
  position: relative;
  margin: 0 0 12px;
  color: var(--text-dark);
  font-size: clamp(32px, 5vw, 52px);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.04em;
}

.worker-job-detail .home-hero p {
  position: relative;
  margin: 0;
  color: var(--text-muted);
  font-size: 17px;
}

.detail-section {
  margin-top: 40px;
}

.detail-card,
.map-card {
  padding: 34px;
  border-radius: 26px;
  background: var(--white);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.detail-card:hover,
.map-card:hover {
  transform: translateY(-3px);
  border-color: rgba(46,175,125,0.45);
  box-shadow: var(--shadow-md);
}

.detail-card h2,
.map-card h2 {
  margin: 0 0 24px;
  color: var(--text-dark);
  font-size: clamp(24px, 3vw, 34px);
  font-weight: 900;
  letter-spacing: -0.03em;
}

.info-grid,
.map-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
}

.info-item,
.map-info-item {
  padding: 16px;
  border-radius: 18px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
}

.info-label,
.map-info-item strong {
  display: block;
  margin-bottom: 6px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 800;
}

.info-value,
.map-info-item span {
  color: var(--text-dark);
  font-size: 15px;
  font-weight: 900;
  line-height: 1.5;
}

.info-value.primary {
  color: var(--primary);
}

.worker-btn,
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
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.worker-btn:hover,
.home-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(46,175,125,0.28);
}

.form-grid {
  display: grid;
  gap: 16px;
}

.form-field label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-dark);
  font-size: 14px;
  font-weight: 800;
}

.form-field input,
.form-field select,
.form-field textarea {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: #fcfffd;
  color: var(--text-dark);
  font-size: 15px;
  font-family: inherit;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
  outline: none;
  background: white;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

.form-field textarea {
  min-height: 110px;
  resize: vertical;
}

.file-input {
  display: block;
  width: 100%;
  padding: 14px;
  border: 1px dashed #bfe5d5;
  border-radius: 16px;
  background: var(--bg-soft);
}

.start-form {
  margin-top: 20px;
}

.eta-box {
  padding: 20px;
  border-radius: 20px;
  background: linear-gradient(135deg, var(--primary-soft), #ffffff);
  border: 1px solid var(--border);
  margin-bottom: 18px;
}

.eta-box p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.eta-box strong {
  color: var(--text-dark);
}

.eta-time {
  color: var(--primary);
  font-weight: 900;
}

.map-actions {
  display: flex;
  justify-content: center;
  gap: 14px;
  flex-wrap: wrap;
  margin: 22px 0;
}

.map-icon-btn {
  width: 48px;
  height: 48px;
  border: none;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  font-size: 21px;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(46,175,125,0.22);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.map-icon-btn:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: var(--shadow-md);
}

.map-icon-btn.secondary {
  background: linear-gradient(135deg, #7c3aed, #5b21b6);
}

#worker-map {
  width: 100%;
  height: 380px;
  overflow: hidden;
  border-radius: 22px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
}

.map-placeholder {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #78909c;
  text-align: center;
}

.progress-list,
.message-list {
  display: grid;
  gap: 16px;
  margin-top: 22px;
}

.progress-item,
.message-item {
  padding: 18px;
  border-radius: 20px;
  background: #fcfffd;
  border: 1px solid var(--border);
}

.progress-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.progress-step,
.message-sender {
  color: var(--text-dark);
  font-weight: 900;
}

.progress-time,
.message-time {
  color: #78909c;
  font-size: 13px;
  font-weight: 700;
}

.progress-note,
.message-content {
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

.divider {
  height: 1px;
  background: var(--border);
  margin: 24px 0;
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

@media (max-width: 768px) {
  .worker-job-detail {
    padding: 16px 12px 44px;
  }

  .worker-job-detail .home-hero {
    padding: 42px 18px;
    border-radius: 22px;
  }

  .detail-card,
  .map-card {
    padding: 22px;
    border-radius: 20px;
  }

  .info-grid,
  .map-info {
    grid-template-columns: 1fr;
  }

  .worker-btn,
  .home-btn {
    width: 100%;
  }

  #worker-map {
    height: 300px;
  }
}
</style>

<section class="home-container worker-job-detail">
  <header class="home-hero">
    <p class="home-kicker">WORKER • JOB DETAIL</p>
    <h1>Job #<?= View::e($job['id'] ?? '') ?></h1>
    <p>Chi tiết công việc và cập nhật tiến độ theo thời gian thực.</p>
  </header>

  <section class="detail-section">
    <div class="detail-card">
      <h2>Thông tin công việc</h2>

      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Khách hàng</span>
          <span class="info-value"><?= View::e($job['user_name'] ?? '') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Số điện thoại</span>
          <span class="info-value"><?= View::e($job['user_phone'] ?? '') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Địa chỉ</span>
          <span class="info-value" id="customerAddress"><?= View::e($job['location'] ?? '') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Công việc</span>
          <span class="info-value"><?= View::e($job['service_name'] ?? '') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Chi tiết</span>
          <span class="info-value"><?= View::e($job['description'] ?? 'Không có') ?></span>
        </div>

        <div class="info-item">
          <span class="info-label">Tiền thu khách</span>
          <span class="info-value primary">
            <?php if (in_array($status, ['confirmed', 'accepted', 'in_progress', 'completed'], true)): ?>
              Đã thanh toán
            <?php else: ?>
              <?= number_format((float)($job['service_price'] ?? 0), 0, ',', '.') ?>đ
            <?php endif; ?>
          </span>
        </div>

        <?php if ($payment !== null): ?>
          <div class="info-item">
            <span class="info-label">Lương worker</span>
            <span class="info-value primary"><?= number_format((float)($payment['worker_salary'] ?? 0), 0, ',', '.') ?>đ</span>
          </div>

          <div class="info-item">
            <span class="info-label">Phí công ty</span>
            <span class="info-value"><?= number_format((float)($payment['company_fee'] ?? 0), 0, ',', '.') ?>đ</span>
          </div>

          <div class="info-item">
            <span class="info-label">Thuế</span>
            <span class="info-value"><?= number_format((float)($payment['tax_amount'] ?? 0), 0, ',', '.') ?>đ</span>
          </div>
        <?php endif; ?>
      </div>

      <?php if (in_array($status, ['accepted', 'confirmed'], true)): ?>
        <form method="post" action="/worker/jobs/<?= (int)$job['id'] ?>/start" class="start-form">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <button type="submit" class="worker-btn">Let's go</button>
        </form>
      <?php endif; ?>
    </div>
  </section>

  <?php if (in_array($status, ['accepted', 'in_progress'], true)): ?>
    <section class="detail-section">
      <div class="detail-card">
        <h2>⏱️ Thời gian ước tính đến</h2>

        <?php if (!empty($job['estimated_arrival_time'])): ?>
          <div class="eta-box">
            <p>
              <strong>Thời gian dự kiến:</strong>
              <span class="eta-time"><?= View::e($job['estimated_arrival_time']) ?></span>
            </p>
          </div>
        <?php endif; ?>

        <form method="post" action="/worker/jobs/<?= (int)$job['id'] ?>/update-eta" class="form-grid">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

          <div class="form-field">
            <label for="eta">Cập nhật thời gian dự kiến đến</label>
            <input
              type="datetime-local"
              id="eta"
              name="estimated_arrival_time"
              value="<?= !empty($job['estimated_arrival_time']) ? View::e(str_replace(' ', 'T', substr($job['estimated_arrival_time'], 0, 16))) : '' ?>"
              required
            >
          </div>

          <button type="submit" class="worker-btn">Cập nhật ETA</button>
        </form>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($liveMode || in_array($status, ['in_progress', 'completed'], true)): ?>
    <section class="detail-section map-card">
      <h2>📍 Chỉ đường</h2>

      <div class="map-info">
        <div class="map-info-item">
          <strong>Vị trí hiện tại</strong>
          <span id="currentLocationStatus">Đang xác định...</span>
        </div>

        <div class="map-info-item">
          <strong>Vị trí khách hàng</strong>
          <span id="customerAddressMap"><?= View::e($job['location'] ?? 'Không xác định') ?></span>
        </div>
      </div>

      <div class="map-actions">
        <button type="button" onclick="openGoogleDirections()" class="map-icon-btn" title="<?= $offlineMode ? 'Xem chỉ đường nội bộ' : 'Mở chỉ đường trên Google Maps' ?>">📍</button>
        <button type="button" onclick="updateMap()" class="map-icon-btn secondary" title="Làm mới bản đồ vị trí khách">🗺️</button>
      </div>

      <div id="worker-map" role="region" aria-label="Bản đồ chỉ đường">
        <div class="map-placeholder">
          <div>
            <div style="font-size: 28px; margin-bottom: 8px;">📍</div>
            <div>Đang tải bản đồ...</div>
          </div>
        </div>
      </div>

      <script>
        var currentCoords = null;
        var offlineMode = <?= $offlineMode ? 'true' : 'false' ?>;

        function setCurrentLocationStatus(text) {
          var node = document.getElementById('currentLocationStatus');
          if (node) node.textContent = text;
        }

        function getCustomerAddress() {
          return document.getElementById('customerAddressMap')?.textContent?.trim()
            || document.getElementById('customerAddress')?.textContent?.trim()
            || '';
        }

        function getCurrentCoords(callback) {
          if (currentCoords && typeof currentCoords.lat === 'number' && typeof currentCoords.lng === 'number') {
            callback(currentCoords);
            return;
          }

          if (!window.isSecureContext) {
            setCurrentLocationStatus('Trình duyệt chặn vị trí vì đang dùng HTTP (cần HTTPS).');
            callback(null);
            return;
          }

          if (!navigator.geolocation) {
            setCurrentLocationStatus('Thiết bị không hỗ trợ định vị.');
            callback(null);
            return;
          }

          setCurrentLocationStatus('Đang lấy vị trí GPS...');
          navigator.geolocation.getCurrentPosition(
            function(position) {
              currentCoords = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
              };
              setCurrentLocationStatus(currentCoords.lat.toFixed(6) + ', ' + currentCoords.lng.toFixed(6));
              callback(currentCoords);
            },
            function() {
              setCurrentLocationStatus('Không lấy được vị trí hiện tại.');
              callback(null);
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 10000 }
          );
        }

        function openGoogleDirections() {
          var customerAddr = getCustomerAddress();
          if (!customerAddr) return;

          if (offlineMode) {
            alert('Chế độ test nội bộ: không mở Google Maps.\nĐịa chỉ khách hàng: ' + customerAddr);
            return;
          }

          getCurrentCoords(function(coords) {
            var origin = coords ? (coords.lat + ',' + coords.lng) : 'current location';
            var directionsUrl = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) +
              '&destination=' + encodeURIComponent(customerAddr) + '&travelmode=driving';
            window.open(directionsUrl, '_blank', 'noopener,noreferrer');
          });
        }

        function updateMap() {
          var customerAddr = getCustomerAddress();
          var mapContainer = document.getElementById('worker-map');

          if (!customerAddr) {
            mapContainer.innerHTML = '<p style="padding:20px;color:#78909c;text-align:center;">Chưa có địa chỉ</p>';
            return;
          }

          if (offlineMode) {
            mapContainer.innerHTML = '<div class="map-placeholder" style="padding:24px; min-height: 280px; display:flex; align-items:center; justify-content:center; text-align:center; border:1px dashed #dcefe6; border-radius:18px; background: linear-gradient(135deg, #f7fdf9 0%, #ffffff 100%);"><div><div style="font-size: 28px; margin-bottom: 8px;">🗺️</div><div style="font-weight: 800; color: #1f2d3d; margin-bottom: 6px;">Chế độ test nội bộ</div><div style="color:#546e7a; line-height:1.6;">Không hiển thị Google Maps khi đang test local.<br>Địa chỉ khách hàng: ' + customerAddr + '</div></div></div>';
            return;
          }

          getCurrentCoords(function(coords) {
            var embedUrl;

            if (coords) {
              embedUrl = 'https://maps.google.com/maps?f=d&source=s_d&saddr=' +
                encodeURIComponent(coords.lat + ',' + coords.lng) + '&daddr=' +
                encodeURIComponent(customerAddr) + '&output=embed&z=14';
            } else {
              embedUrl = 'https://maps.google.com/maps?q=' + encodeURIComponent(customerAddr) +
                '&t=m&z=16&ie=UTF8&iwloc=&output=embed';
            }

            mapContainer.innerHTML = '<iframe src="' + embedUrl + '" width="100%" height="100%" frameborder="0" style="border:none;border-radius:22px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
          });
        }

        (function() {
          if (getCustomerAddress()) updateMap();
        })();
      </script>
    </section>
  <?php endif; ?>

  <section class="detail-section">
    <div class="detail-card">
      <h2>Cập nhật tiến độ</h2>

      <form method="post" action="/worker/jobs/<?= (int)$job['id'] ?>/progress" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

        <div class="form-field">
          <label>Bước tiến độ</label>
          <select name="step" id="progressStep" required <?= $progressLocked ? 'disabled' : '' ?>>
            <?php foreach ($progressOrder as $value => $label): ?>
              <?php
                $disabled = $nextStep !== null && $value !== $nextStep;
                $selected = $nextStep !== null && $value === $nextStep;
              ?>
              <option value="<?= View::e($value) ?>" <?= $disabled ? 'disabled' : '' ?> <?= $selected ? 'selected' : '' ?>>
                <?= View::e($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($progressLocked): ?>
            <p class="progress-note" style="margin-top:8px;color:#b42318;font-weight:700;">Tiến độ đã hoàn thành.</p>
          <?php endif; ?>
        </div>

        <div class="form-field">
          <label>Ghi chú</label>
          <textarea name="note" rows="3"></textarea>
        </div>

        <div class="form-field">
          <label>Tệp đính kèm cập nhật (có thể chọn nhiều file)</label>
          <input type="file" name="photos[]" id="progressPhotos" multiple accept="image/*" class="file-input">
          <p class="progress-note" style="margin-top:8px;color:#b26a00;font-weight:700;">Chỉ được tải ảnh JPG, PNG, WEBP hoặc GIF. Có thể chọn nhiều ảnh cùng lúc.</p>
        </div>

        <button type="submit" class="worker-btn">Cập nhật tiến độ</button>
      </form>

      <?php if (!empty($progress)): ?>
        <div class="divider"></div>

        <div class="progress-list">
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
                    <?php $ext = strtolower(pathinfo((string)$photo, PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)): ?>
                      <a href="<?= View::e($photo) ?>" target="_blank" rel="noopener">
                        <img src="<?= View::e($photo) ?>" alt="progress">
                      </a>
                    <?php else: ?>
                      <a href="<?= View::e($photo) ?>" target="_blank" rel="noopener" class="progress-file-link">
                        📎 <?= View::e(basename((string)$photo)) ?>
                      </a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (($job['status'] ?? '') === 'completed'): ?>
        <div style="margin-top:18px;">
          <a class="worker-btn" href="/worker/jobs/<?= (int)$job['id'] ?>/report">Đi tới báo cáo hoàn thành</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="detail-section">
    <div class="detail-card">
      <h2>Trao đổi với khách hàng</h2>

      <div class="message-list">
        <?php if (empty($messages)): ?>
          <p class="empty-state">Chưa có tin nhắn.</p>
        <?php endif; ?>

        <?php foreach ($messages as $message): ?>
          <div class="message-item">
            <p class="message-sender">
              <?= View::e($message['sender_name'] ?? '') ?>
              <span style="color:var(--text-muted);font-weight:700;">
                (<?= View::e($message['sender_role'] ?? '') ?>)
              </span>
            </p>
            <p class="message-content"><?= View::e($message['content'] ?? '') ?></p>
            <small class="message-time"><?= View::e($message['created_at'] ?? '') ?></small>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="post" action="/worker/jobs/<?= (int)$job['id'] ?>/message" class="form-grid" style="margin-top:18px;">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

        <div class="form-field">
          <textarea name="content" rows="2" required placeholder="Nhập tin nhắn..."></textarea>
        </div>

        <button type="submit" class="worker-btn">Gửi tin nhắn</button>
      </form>
    </div>
  </section>
</section>

<script>
  (function () {
    const stepSelect = document.getElementById('progressStep');
    const photosInput = document.getElementById('progressPhotos');
    if (!stepSelect || !photosInput) return;

    const requiredPhotoSteps = ['arrived', 'before_photo', 'after_photo'];

    const syncRequired = () => {
      const value = stepSelect.value || '';
      photosInput.required = requiredPhotoSteps.includes(value);
    };

    stepSelect.addEventListener('change', syncRequired);
    syncRequired();
  })();
</script>