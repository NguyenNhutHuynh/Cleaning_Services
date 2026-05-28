<?php
use App\Core\View;
/** @var string $csrf Token CSRF */
/** @var ?string $error Thông báo lỗi */
?>

<style>
  .auth-container {
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

    max-width: 920px;
    margin: 44px auto;
    padding: 0 16px;
    color: var(--text-dark);
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
  }

  .auth-container * {
    box-sizing: border-box;
  }

  .auth-panel {
    position: relative;
    overflow: hidden;
    background:
      radial-gradient(circle at top left, rgba(46,175,125,0.16), transparent 34%),
      linear-gradient(135deg, #ffffff 0%, #f7fdf9 100%);
    border: 1px solid var(--border);
    border-radius: 28px;
    box-shadow: var(--shadow-md);
    padding: 38px;
  }

  .auth-panel::after {
    content: "";
    position: absolute;
    right: -80px;
    bottom: -80px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(46,175,125,0.10);
  }

  .auth-panel > * {
    position: relative;
    z-index: 1;
  }

  .auth-title {
    margin: 0;
    color: var(--text-dark);
    font-size: clamp(34px, 5vw, 46px);
    font-weight: 900;
    text-align: center;
    letter-spacing: -0.04em;
    line-height: 1.08;
  }

  .auth-subtitle {
    margin: 12px 0 28px;
    text-align: center;
    color: var(--text-muted);
    font-size: 16px;
    line-height: 1.55;
  }

  .auth-error {
    background: #fff1f1;
    border: 1px solid #ffd1d1;
    color: #b42318;
    border-radius: 18px;
    padding: 14px 16px;
    margin-bottom: 18px;
    font-size: 14px;
    font-weight: 700;
    box-shadow: var(--shadow-sm);
  }

  .auth-form {
    display: grid;
    gap: 18px;
  }

  .auth-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .auth-form-group {
    display: grid;
    gap: 8px;
  }

  .auth-form-group.full {
    grid-column: 1 / -1;
  }

  .auth-form-group label {
    color: var(--text-dark);
    font-weight: 800;
    font-size: 14px;
  }

  .auth-required {
    color: #e74c3c;
  }

  .auth-input {
    width: 100%;
    height: 52px;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 0 15px;
    color: var(--text-dark);
    background: #fcfffd;
    font-size: 15px;
    font-family: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, transform 0.2s ease;
  }

  select.auth-input {
    cursor: pointer;
  }

  .auth-input::placeholder {
    color: #8aa79b;
  }

  .auth-input:focus {
    outline: none;
    background: white;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
    transform: translateY(-1px);
  }

  .auth-btn {
    width: 100%;
    height: 52px;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    font-weight: 900;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 10px 22px rgba(46,175,125,0.22);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(46,175,125,0.28);
  }

  .auth-link {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
    text-align: center;
    color: var(--text-muted);
    font-size: 15px;
  }

  .auth-link a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 900;
  }

  .auth-link a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
  }

  @media (max-width: 760px) {
    .auth-container {
      margin: 28px auto;
      padding: 0 12px;
    }

    .auth-panel {
      padding: 28px 20px;
      border-radius: 22px;
    }

    .auth-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<section class="auth-container" aria-labelledby="auth-register-heading">
  <div class="auth-panel">
    <header>
      <h2 id="auth-register-heading" class="auth-title">Đăng ký</h2>
      <p class="auth-subtitle">Tạo tài khoản để đặt lịch và quản lý dịch vụ của bạn</p>
    </header>

    <?php if ($error): ?>
      <div class="auth-error"><?= View::e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/register" aria-label="Form đăng ký" class="auth-form">
      <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

      <div class="auth-grid">
        <div class="auth-form-group">
          <label for="name">Họ tên</label>
          <input id="name" name="name" required class="auth-input" placeholder="Nguyen Van A">
        </div>

        <div class="auth-form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required class="auth-input" placeholder="you@example.com">
        </div>

        <div class="auth-form-group">
          <label for="phone">Số điện thoại <span class="auth-required">*</span></label>
          <input id="phone" name="phone" type="tel" required class="auth-input" placeholder="Ví dụ: 0901234567">
        </div>

        <div class="auth-form-group">
          <label for="city">Thành phố <span class="auth-required">*</span></label>
          <select id="city" name="city" required class="auth-input">
            <option value="">-- Chọn thành phố --</option>
            <option value="TP.HCM">TP.HCM</option>
          </select>
        </div>

        <div class="auth-form-group">
          <label for="district">Quận/Huyện <span class="auth-required">*</span></label>
          <select id="district" name="district" required class="auth-input" disabled>
            <option value="">-- Chọn quận/huyện --</option>
          </select>
        </div>

        <div class="auth-form-group">
          <label for="ward">Phường/Xã <span class="auth-required">*</span></label>
          <select id="ward" name="ward" required class="auth-input" disabled>
            <option value="">-- Chọn phường/xã --</option>
          </select>
        </div>

        <div class="auth-form-group">
          <label for="role">Vai trò</label>
          <select id="role" name="role" required class="auth-input">
            <option value="customer">Khách hàng (Customer)</option>
            <option value="worker">Người lao động (Worker)</option>
          </select>
        </div>

        <div class="auth-form-group full">
          <label for="address_detail">Địa chỉ chi tiết <span class="auth-required">*</span></label>
          <input id="address_detail" name="address_detail" required class="auth-input" placeholder="Số nhà, tên đường...">
        </div>

        <div class="auth-form-group full">
          <label for="password">Mật khẩu</label>
          <input id="password" name="password" type="password" required minlength="6" class="auth-input" placeholder="Ít nhất 6 ký tự">
        </div>
      </div>

      <div class="auth-form-group">
        <button type="submit" class="auth-btn">Đăng ký</button>
      </div>
    </form>

    <nav class="auth-link" aria-label="Liên kết chuyển trang đăng nhập">
      Đã có tài khoản? <a href="/login">Đăng nhập</a>
    </nav>
  </div>
</section>

<script>
  (function () {
    const citySelect = document.getElementById('city');
    const districtSelect = document.getElementById('district');
    const wardSelect = document.getElementById('ward');
    const hcmProvinceCode = '79';
    const offlineMode = <?= (defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE) ? 'true' : 'false' ?>;
    const apiUrl = offlineMode
      ? '/assets/data/hcm-province.json'
      : 'https://provinces.open-api.vn/api/p/' + hcmProvinceCode + '?depth=3';

    const setSelectState = (select, enabled, placeholder) => {
      select.innerHTML = '';
      const option = document.createElement('option');
      option.value = '';
      option.textContent = placeholder;
      select.appendChild(option);
      select.disabled = !enabled;
    };

    const populateSelect = (select, items) => {
      items.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.name;
        option.textContent = item.name;
        option.dataset.code = item.code;
        select.appendChild(option);
      });
    };

    let cachedDistricts = [];

    const loadDistricts = async () => {
      setSelectState(districtSelect, false, 'Dang tai danh sach quan/huyen...');
      setSelectState(wardSelect, false, '-- Chon phuong/xa --');

      try {
        const response = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
          throw new Error('API response not ok');
        }

        const data = await response.json();
        cachedDistricts = Array.isArray(data.districts) ? data.districts : [];
        setSelectState(districtSelect, true, '-- Chon quan/huyen --');
        populateSelect(districtSelect, cachedDistricts);
      } catch (error) {
        setSelectState(districtSelect, true, '-- Chon quan/huyen --');
        const fallback = document.createElement('option');
        fallback.value = '';
        fallback.textContent = 'Khong the tai danh sach, vui long thu lai';
        districtSelect.appendChild(fallback);
      }
    };

    const updateWards = () => {
      const selected = districtSelect.selectedOptions[0];
      if (!selected || !selected.dataset.code) {
        setSelectState(wardSelect, false, '-- Chon phuong/xa --');
        return;
      }

      const districtCode = Number(selected.dataset.code);
      const district = cachedDistricts.find((item) => item.code === districtCode);
      const wards = district && Array.isArray(district.wards) ? district.wards : [];
      setSelectState(wardSelect, true, '-- Chon phuong/xa --');
      populateSelect(wardSelect, wards);
    };

    citySelect.addEventListener('change', () => {
      if (citySelect.value) {
        loadDistricts();
      } else {
        setSelectState(districtSelect, false, '-- Chon quan/huyen --');
        setSelectState(wardSelect, false, '-- Chon phuong/xa --');
      }
    });

    districtSelect.addEventListener('change', updateWards);

    if (citySelect.value === 'TP.HCM') {
      loadDistricts();
    }
  })();
</script>