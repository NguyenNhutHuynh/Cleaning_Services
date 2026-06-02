<?php
use App\Core\View;
use App\Models\Booking;
use App\Models\PaymentTransaction;
/** @var array $bookings Danh sách các đơn đặt */
/** @var array $workers Danh sách worker */
/** @var string $csrf Token CSRF */
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
  <h1 style="margin: 0 0 24px; color: #1f2d3d; font-size: 32px;">Quản Lý Đơn Đặt</h1>

  <!-- BỘ LỌC -->
  <div style="background: #fff; border-radius: 12px; border: 1px solid #dcefe6; padding: 20px; margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px; color: #1f2d3d; font-size: 16px; font-weight: 600;">Bộ Lọc</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
      <div>
        <label style="display: block; margin-bottom: 6px; color: #546e7a; font-weight: 600; font-size: 13px;">Tìm kiếm (Khách/Dịch vụ)</label>
        <input type="text" id="searchInput" placeholder="Nhập tên khách hoặc dịch vụ..." style="width: 100%; padding: 10px; border: 1px solid #dcefe6; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
      </div>
      <div>
        <label style="display: block; margin-bottom: 6px; color: #546e7a; font-weight: 600; font-size: 13px;">Trạng Thái</label>
        <select id="statusFilter" style="width: 100%; padding: 10px; border: 1px solid #dcefe6; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
          <option value="">-- Tất cả --</option>
          <option value="pending">Chờ xử lý</option>
          <option value="confirmed">Đã xác nhận</option>
          <option value="in_progress">Đang thực hiện</option>
          <option value="completed">Hoàn thành</option>
          <option value="cancelled">Đã hủy</option>
        </select>
      </div>
      <div>
        <label style="display: block; margin-bottom: 6px; color: #546e7a; font-weight: 600; font-size: 13px;">Thanh Toán</label>
        <select id="paymentFilter" style="width: 100%; padding: 10px; border: 1px solid #dcefe6; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
          <option value="">-- Tất cả --</option>
          <option value="paid">Đã thanh toán</option>
          <option value="unpaid">Chưa thanh toán</option>
        </select>
      </div>
      <div style="display: flex; align-items: flex-end; gap: 8px;">
        <button type="button" onclick="resetFilters()" style="flex: 1; padding: 10px; background: #e8e8e8; color: #1f2d3d; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
          Xóa Lọc
        </button>
      </div>
    </div>
  </div>

  <div style="background: #fff; border-radius: 12px; border: 1px solid #dcefe6; overflow: hidden;">
    <table id="bookingsTable" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background: #f7fdf9; border-bottom: 1px solid #dcefe6;">
          <th style="padding: 16px; text-align: left; color: #1f2d3d; font-weight: 700;">Khách Hàng</th>
          <th style="padding: 16px; text-align: left; color: #1f2d3d; font-weight: 700;">Dịch Vụ</th>
          <th style="padding: 16px; text-align: left; color: #1f2d3d; font-weight: 700;">Ngày</th>
          <th style="padding: 16px; text-align: left; color: #1f2d3d; font-weight: 700;">Trạng Thái</th>
          <th style="padding: 16px; text-align: left; color: #1f2d3d; font-weight: 700;">Thanh Toán</th>
          <th style="padding: 16px; text-align: center; color: #1f2d3d; font-weight: 700;">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <tr style="border-bottom: 1px solid #dcefe6;" data-customer="<?= View::e(strtolower($booking['customer_name'] ?? '')) ?>" data-service="<?= View::e(strtolower($booking['service_name'] ?? '')) ?>" data-status="<?= View::e($booking['status'] ?? '') ?>" data-payment="<?= (!empty($booking['is_customer_paid']) || !empty($booking['hasPaidPayment'])) ? 'paid' : 'unpaid' ?>">
            <td style="padding: 16px; color: #546e7a;"><?= View::e($booking['customer_name'] ?? 'N/A') ?></td>
            <td style="padding: 16px; color: #546e7a;"><?= View::e($booking['service_name'] ?? 'N/A') ?></td>
            <td style="padding: 16px; color: #546e7a;"><?= View::e(($booking['date'] ?? $booking['booking_date'] ?? 'N/A')) ?></td>
            <td style="padding: 16px;">
              <span style="display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;
                <?php
                  $status = $booking['status'] ?? '';
                  if ($status === 'pending') {
                    echo 'background: #fff3cd; color: #856404;';
                  } elseif ($status === 'confirmed') {
                    echo 'background: #cfe2ff; color: #084298;';
                  } elseif ($status === 'in_progress') {
                    echo 'background: #d1ecf1; color: #0c5460;';
                  } elseif ($status === 'completed') {
                    echo 'background: #d4edda; color: #155724;';
                  } else {
                    echo 'background: #f8d7da; color: #721c24;';
                  }
                ?>">
                <?= View::e(ucfirst($status)) ?>
              </span>
            </td>
            <td style="padding: 16px;">
              <?php if (!empty($booking['is_customer_paid']) || !empty($booking['hasPaidPayment'])): ?>
                <span style="color: #28a745; font-weight: 600;">✓ Đã thanh toán</span>
              <?php else: ?>
                <span style="color: #dc3545; font-weight: 600;">✗ Chưa thanh toán</span>
              <?php endif; ?>
            </td>
            <td style="padding: 16px; text-align: center;">
              <a href="/manager/bookings/<?= $booking['id'] ?>" style="color: #2eaf7d; text-decoration: none; margin: 0 8px; font-weight: 600;">Chi Tiết</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (empty($bookings)): ?>
    <div style="text-align: center; padding: 60px 20px; color: #546e7a;">
      <p style="font-size: 18px;">Không có đơn đặt nào</p>
    </div>
  <?php endif; ?>
</div>

<script>
function filterTable() {
  const searchVal = document.getElementById('searchInput').value.toLowerCase();
  const statusVal = document.getElementById('statusFilter').value.toLowerCase();
  const paymentVal = document.getElementById('paymentFilter').value.toLowerCase();
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  let visibleCount = 0;

  rows.forEach(row => {
    const customer = row.getAttribute('data-customer');
    const service = row.getAttribute('data-service');
    const status = row.getAttribute('data-status');
    const payment = row.getAttribute('data-payment');

    const matchSearch = !searchVal || customer.includes(searchVal) || service.includes(searchVal);
    const matchStatus = !statusVal || status === statusVal;
    const matchPayment = !paymentVal || payment === paymentVal;

    const visible = matchSearch && matchStatus && matchPayment;
    row.style.display = visible ? '' : 'none';
    if (visible) visibleCount++;
  });
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('paymentFilter').value = '';
  filterTable();
}

document.getElementById('searchInput').addEventListener('keyup', filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);
document.getElementById('paymentFilter').addEventListener('change', filterTable);
</script>
