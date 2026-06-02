<?php
use App\Core\View;

/** @var array $services */
/** @var string|null $searchQuery */

$totalServices = is_countable($services ?? []) ? count($services) : 0;

$minPrice = null;
if (!empty($services)) {
    foreach ($services as $serviceItem) {
        $price = (int)($serviceItem['price'] ?? 0);
        if ($price > 0 && ($minPrice === null || $price < $minPrice)) {
            $minPrice = $price;
        }
    }
}
?>

<section class="home-container services-page">
    <header class="home-hero services-page-hero">
        <p class="services-kicker">CLEANING SERVICES</p>
        <h1>Dịch vụ của chúng tôi</h1>
        <p>Danh sách dịch vụ vệ sinh chuyên nghiệp, giá minh bạch, đặt lịch nhanh và hỗ trợ linh hoạt theo nhu cầu của bạn.</p>

        <div class="services-hero-actions">
            <a href="/book" class="services-btn services-btn-primary">Đặt lịch ngay</a>
            <a href="/contact" class="services-btn services-btn-outline">Tư vấn miễn phí</a>
        </div>
    </header>

    <section class="services-info-section" aria-label="Thông tin nổi bật">
        <div class="services-info-card">
            <h2>Vì sao nên chọn dịch vụ của chúng tôi?</h2>
            <p>Chúng tôi tập trung vào trải nghiệm đặt lịch nhanh, quy trình rõ ràng và chất lượng vệ sinh ổn định.</p>

            <div class="services-info-grid">
                <div class="info-mini-card">
                    <strong>👥 Nhân viên chuyên nghiệp</strong>
                    <span>Được phân công theo từng loại dịch vụ và lịch làm việc phù hợp.</span>
                </div>

                <div class="info-mini-card">
                    <strong>🧴 Dụng cụ an toàn</strong>
                    <span>Ưu tiên quy trình vệ sinh sạch, gọn, phù hợp gia đình và văn phòng.</span>
                </div>

                <div class="info-mini-card">
                    <strong>📋 Quy trình rõ ràng</strong>
                    <span>Mỗi dịch vụ đều có mô tả, mức giá và thao tác đặt lịch minh bạch.</span>
                </div>

                <div class="info-mini-card">
                    <strong>📞 Hỗ trợ nhanh</strong>
                    <span>Có thể liên hệ tư vấn nếu bạn chưa biết nên chọn gói nào.</span>
                </div>
            </div>
        </div>
    </section>
    
    <section class="services-search-wrap">
        <div class="services-search-head">
            <div>
                <h2>Tìm dịch vụ phù hợp</h2>
                <p>Tìm theo tên dịch vụ, khu vực cần vệ sinh hoặc nhu cầu làm sạch.</p>
            </div>

            <div class="services-search-badges">
                <span class="services-count"><?= $totalServices ?> dịch vụ</span>
                <span class="services-hint">Cập nhật mới nhất</span>
            </div>
        </div>

        <form method="GET" action="/services" class="services-search-form">
            <div class="services-search-field">
                <label for="searchInput">Từ khóa tìm kiếm</label>
                <input
                    type="text"
                    id="searchInput"
                    name="q"
                    placeholder="Ví dụ: sofa, kính, nhà bếp, tổng vệ sinh..."
                    value="<?= View::e($searchQuery ?? '') ?>"
                    class="services-search-input"
                    minlength="2"
                />
            </div>

            <div class="services-search-actions">
                <button type="submit" class="services-btn services-btn-primary">Tìm kiếm</button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="/services" class="services-btn services-btn-outline">Xóa</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if (!empty($searchQuery)): ?>
        <section class="services-search-result">
            <p>
                Kết quả tìm kiếm cho: <strong><?= View::e($searchQuery) ?></strong>
                <?php if (empty($services)): ?>
                    - Không tìm thấy dịch vụ nào
                <?php else: ?>
                    - Tìm thấy <strong><?= count($services) ?></strong> dịch vụ
                <?php endif; ?>
            </p>
        </section>
    <?php endif; ?>

    

    <section class="services-list-section">
        <div class="section-heading">
            <h2>Danh sách dịch vụ</h2>
            <p>Chọn dịch vụ phù hợp với nhu cầu vệ sinh nhà ở, văn phòng, sofa, kính hoặc tổng vệ sinh.</p>
        </div>

        <div class="services-grid services-page-grid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $s): ?>
                    <article class="service-card services-page-card">
                        <a class="services-thumb" href="/service?id=<?= (int)$s['id'] ?>" aria-label="Xem chi tiết <?= View::e($s['name']) ?>">
                            <?php if (!empty($s['image_path'])): ?>
                                <img src="<?= View::e($s['image_path']) ?>" alt="<?= View::e($s['name']) ?>">
                            <?php else: ?>
                                <div class="services-thumb-fallback">
                                    <?= View::e($s['icon'] ?: '🧹') ?>
                                </div>
                            <?php endif; ?>

                            <span class="services-thumb-badge">Chi tiết</span>
                        </a>

                        <div class="services-content">
                            <div class="services-title-row">
                                <h3>
                                    <a href="/service?id=<?= (int)$s['id'] ?>">
                                        <?= View::e($s['icon'] ?? '') ?> <?= View::e($s['name']) ?>
                                    </a>
                                </h3>

                                <span class="services-price-badge">
                                    <?= number_format((int)$s['price'], 0, ',', '.') ?><?= $s['unit'] ? 'đ/' . View::e($s['unit']) : 'đ' ?>
                                </span>
                            </div>

                            <?php if (!empty($s['description'])): ?>
                                <p class="services-desc"><?= View::e($s['description']) ?></p>
                            <?php else: ?>
                                <p class="services-desc">Dịch vụ tiêu chuẩn với quy trình làm sạch bài bản, trang thiết bị hiện đại và đội ngũ chuyên nghiệp.</p>
                            <?php endif; ?>

                            <div class="services-meta-list">
                                <?php if (!empty($s['duration'])): ?>
                                    <span>⏱️ <?= View::e($s['duration']) ?></span>
                                <?php else: ?>
                                    <span>⏱️ Linh hoạt theo lịch</span>
                                <?php endif; ?>

                                <?php if (!empty($s['minimum'])): ?>
                                    <span>📋 Tối thiểu <?= number_format((int)$s['minimum'], 0, ',', '.') ?>đ</span>
                                <?php else: ?>
                                    <span>📋 Báo giá rõ ràng</span>
                                <?php endif; ?>

                                <span>✅ Có thể đặt online</span>
                            </div>

                            <div class="services-card-actions">
                                <a href="/service?id=<?= (int)$s['id'] ?>" class="services-btn services-btn-outline">Xem chi tiết</a>
                                <a href="/book?service=<?= (int)$s['id'] ?>" class="services-btn services-btn-primary">Đặt lịch</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="services-empty-state">
                    <?php if (!empty($searchQuery)): ?>
                        Không tìm thấy dịch vụ nào khớp với "<strong><?= View::e($searchQuery) ?></strong>". Hãy thử tìm kiếm với từ khóa khác.
                    <?php else: ?>
                        Chưa có dịch vụ nào được kích hoạt.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <section class="services-process-section">
        <div class="section-heading">
            <h2>Quy trình đặt dịch vụ</h2>
            <p>Chỉ vài bước để chọn dịch vụ, xác nhận lịch và theo dõi quá trình thực hiện.</p>
        </div>

        <div class="process-grid">
            <article class="process-card">
                <span>01</span>
                <h3>Chọn dịch vụ</h3>
                <p>Xem mô tả, giá và thông tin chi tiết của từng dịch vụ.</p>
            </article>

            <article class="process-card">
                <span>02</span>
                <h3>Đặt lịch</h3>
                <p>Chọn thời gian, địa điểm và gửi yêu cầu đặt lịch online.</p>
            </article>

            <article class="process-card">
                <span>03</span>
                <h3>Xác nhận và thanh toán</h3>
                <p>Admin kiểm tra đơn, xác nhận thông tin và phân công worker.</p>
            </article>

            <article class="process-card">
                <span>04</span>
                <h3>Thực hiện</h3>
                <p>Worker đến đúng lịch, cập nhật tiến độ và hoàn tất công việc.</p>
            </article>
        </div>
    </section>


    <section class="services-checklist-section">
        <div class="checklist-content">
            <h2>Cam kết khi sử dụng dịch vụ</h2>
            <p>Chúng tôi giúp bạn dễ dàng theo dõi chất lượng dịch vụ từ lúc đặt lịch đến khi hoàn thành.</p>
        </div>

        <div class="checklist-grid">
            <div> Giá rõ ràng trước khi đặt</div>
            <div> Có trang chi tiết từng dịch vụ</div>
            <div> Theo dõi trạng thái đơn</div>
            <div> Có đánh giá sau khi hoàn thành</div>
            <div> Hỗ trợ đổi lịch khi cần</div>
            <div> Worker được phân công rõ ràng</div>
        </div>
    </section>

    <section class="services-final-cta">
        <h2>Chưa biết nên chọn dịch vụ nào?</h2>
        <p>Gửi yêu cầu tư vấn để chúng tôi gợi ý gói phù hợp với diện tích, tình trạng nhà và ngân sách của bạn.</p>

        <div class="services-final-actions">
            <a href="/book" class="services-btn services-btn-light">Đặt lịch ngay</a>
            <a href="/contact" class="services-btn services-btn-glass">Liên hệ tư vấn</a>
        </div>
    </section>
</section>

<style>
.services-page {
    --primary: #2eaf7d;
    --primary-dark: #16805a;
    --primary-soft: #e8f7f0;
    --bg-soft: #f7fdf9;
    --text-dark: #1f2d3d;
    --text-muted: #546e7a;
    --border: #dcefe6;
    --white: #ffffff;
    --warning: #f59e0b;
    --shadow-sm: 0 8px 24px rgba(31,45,61,0.08);
    --shadow-md: 0 16px 40px rgba(31,45,61,0.12);

    max-width: 1180px;
    margin: 0 auto;
    padding: 24px 16px 60px;
    color: var(--text-dark);
    position: relative;
}

.services-page * {
    box-sizing: border-box;
}

.services-page::before {
    content: '';
    position: absolute;
    inset: 180px 0 auto;
    height: 320px;
    background: radial-gradient(ellipse at center, rgba(46,175,125,0.14) 0%, rgba(46,175,125,0) 70%);
    pointer-events: none;
    z-index: 0;
}

.services-page > * {
    position: relative;
    z-index: 1;
}

.services-page-hero {
    position: relative;
    overflow: hidden;
    padding: 62px 28px;
    border-radius: 30px;
    text-align: center;
    background:
        radial-gradient(circle at top left, rgba(46,175,125,0.20), transparent 34%),
        linear-gradient(135deg, #f7fdf9 0%, #ffffff 48%, #e8f7f0 100%);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}

.services-page-hero::after {
    content: "";
    position: absolute;
    right: -80px;
    bottom: -80px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(46,175,125,0.13);
}

.services-kicker {
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

.services-page-hero h1 {
    position: relative;
    margin: 0 0 14px;
    font-size: clamp(34px, 5vw, 56px);
    line-height: 1.08;
    font-weight: 900;
    letter-spacing: -0.05em;
    color: var(--text-dark);
}

.services-page-hero p {
    position: relative;
    margin: 0 auto;
    max-width: 760px;
    font-size: 17px;
    line-height: 1.65;
    color: var(--text-muted);
}

.services-hero-actions {
    position: relative;
    margin-top: 26px;
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

.services-overview {
    margin-top: 28px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.overview-card {
    padding: 24px 20px;
    border-radius: 24px;
    background: var(--white);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    text-align: center;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.overview-card:hover {
    transform: translateY(-5px);
    border-color: rgba(46,175,125,0.45);
    box-shadow: var(--shadow-md);
}

.overview-icon {
    display: inline-flex;
    margin-bottom: 12px;
    width: 48px;
    height: 48px;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: var(--primary-soft);
    font-size: 24px;
}

.overview-card strong {
    display: block;
    color: var(--primary);
    font-size: 26px;
    font-weight: 900;
    margin-bottom: 6px;
}

.overview-card p {
    margin: 0;
    color: var(--text-muted);
    line-height: 1.5;
    font-weight: 700;
}

.services-search-wrap,
.services-info-card,
.services-combo-section,
.services-checklist-section {
    margin-top: 34px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 26px;
    padding: 28px;
    box-shadow: var(--shadow-sm);
}

.services-search-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 18px;
}

.services-search-head h2,
.services-info-card h2,
.checklist-content h2 {
    margin: 0 0 8px;
    color: var(--text-dark);
    font-size: clamp(24px, 3vw, 34px);
    font-weight: 900;
    letter-spacing: -0.03em;
}

.services-search-head p,
.services-info-card p,
.checklist-content p,
.section-heading p,
.services-final-cta p {
    margin: 0;
    color: var(--text-muted);
    line-height: 1.6;
}

.services-search-badges {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.services-count,
.services-hint {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 14px;
}

.services-count {
    background: var(--primary-soft);
    color: var(--primary-dark);
}

.services-hint {
    background: #f3f4f6;
    color: var(--text-muted);
}

.services-search-form {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.services-search-field {
    flex: 1;
    min-width: 250px;
}

.services-search-field label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-dark);
    font-weight: 900;
    font-size: 14px;
}

.services-search-input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid var(--border);
    border-radius: 16px;
    font-size: 15px;
    font-family: inherit;
    background: #fcfffd;
    color: var(--text-dark);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.services-search-input:focus {
    outline: none;
    background: white;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
}

.services-search-actions,
.services-card-actions,
.services-final-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.services-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    padding: 12px 24px;
    border-radius: 999px;
    border: none;
    text-decoration: none;
    font-size: 15px;
    font-weight: 900;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.services-btn:hover {
    transform: translateY(-2px);
}

.services-btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    box-shadow: 0 10px 22px rgba(46,175,125,0.22);
}

.services-btn-outline {
    background: white;
    color: var(--primary);
    border: 1.5px solid var(--primary);
}

.services-btn-outline:hover {
    background: var(--primary-soft);
}

.services-btn-light {
    background: white;
    color: var(--primary-dark);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.services-btn-glass {
    color: white;
    border: 1.5px solid rgba(255,255,255,0.75);
    background: rgba(255,255,255,0.08);
}

.services-search-result {
    margin-top: 18px;
    padding: 16px 18px;
    background: var(--primary-soft);
    border-left: 4px solid var(--primary);
    border-radius: 16px;
}

.services-search-result p {
    margin: 0;
    color: var(--primary-dark);
}

.services-info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-top: 24px;
}

.info-mini-card {
    padding: 20px;
    border-radius: 20px;
    background: linear-gradient(135deg, #ffffff, var(--bg-soft));
    border: 1px solid var(--border);
}

.info-mini-card strong {
    display: block;
    color: var(--text-dark);
    font-size: 16px;
    margin-bottom: 8px;
}

.info-mini-card span {
    color: var(--text-muted);
    line-height: 1.55;
    font-size: 14px;
}

.services-list-section,
.services-process-section {
    margin-top: 44px;
}

.section-heading {
    margin-bottom: 24px;
    text-align: center;
}

.section-heading h2 {
    margin: 0 0 8px;
    color: var(--text-dark);
    font-size: clamp(24px, 3vw, 34px);
    font-weight: 900;
    letter-spacing: -0.03em;
}

.services-page-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 22px;
}

.services-page-card {
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 0;
    background: var(--white);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.services-page-card:hover {
    transform: translateY(-6px);
    border-color: rgba(46,175,125,0.45);
    box-shadow: var(--shadow-md);
}

.services-thumb {
    position: relative;
    display: block;
    width: 100%;
    height: 220px;
    overflow: hidden;
    background: #f4f9f6;
}

.services-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.services-page-card:hover .services-thumb img {
    transform: scale(1.06);
}

.services-thumb-badge {
    position: absolute;
    right: 14px;
    top: 14px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,0.92);
    color: var(--primary-dark);
    font-size: 12px;
    font-weight: 900;
    box-shadow: var(--shadow-sm);
}

.services-thumb-fallback {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #eef6f2 0%, #dff1e7 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
}

.services-content {
    padding: 22px;
}

.services-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.services-content h3 {
    margin: 0;
    color: var(--text-dark);
    font-size: 21px;
    font-weight: 900;
    letter-spacing: -0.02em;
}

.services-content h3 a {
    text-decoration: none;
    color: inherit;
}

.services-price-badge {
    background: var(--primary-soft);
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 13px;
    color: var(--primary);
    font-weight: 900;
    white-space: nowrap;
}

.services-desc {
    margin: 12px 0 0;
    color: var(--text-muted);
    line-height: 1.6;
    min-height: 50px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.services-meta-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 16px;
}

.services-meta-list span {
    display: inline-flex;
    padding: 7px 10px;
    border-radius: 999px;
    background: #f7fbf9;
    border: 1px solid var(--border);
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 800;
}

.services-card-actions {
    margin-top: 18px;
}

.services-card-actions .services-btn {
    flex: 1;
    min-width: 120px;
}

.services-empty-state {
    grid-column: 1/-1;
    color: var(--text-muted);
    text-align: center;
    padding: 50px 24px;
    background: var(--bg-soft);
    border: 1px dashed #cfe3d8;
    border-radius: 22px;
}

.process-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.process-card {
    padding: 24px;
    border-radius: 24px;
    background: var(--white);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.process-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.process-card span {
    display: inline-flex;
    margin-bottom: 16px;
    width: 46px;
    height: 46px;
    border-radius: 16px;
    align-items: center;
    justify-content: center;
    background: var(--primary-soft);
    color: var(--primary-dark);
    font-weight: 900;
}

.process-card h3 {
    margin: 0 0 8px;
    color: var(--text-dark);
    font-size: 20px;
    font-weight: 900;
}

.process-card p {
    margin: 0;
    color: var(--text-muted);
    line-height: 1.6;
}

.services-combo-section {
    text-align: center;
}

.services-combo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 18px;
    margin-top: 24px;
}

.services-combo-card {
    position: relative;
    background: white;
    padding: 26px;
    border-radius: 22px;
    border: 1px solid var(--border);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    text-align: left;
}

.services-combo-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-sm);
}

.services-combo-card.is-featured {
    border-color: rgba(46,175,125,0.5);
    box-shadow: var(--shadow-sm);
}

.combo-tag {
    display: inline-flex;
    margin-bottom: 14px;
    padding: 7px 12px;
    border-radius: 999px;
    background: var(--primary-soft);
    color: var(--primary-dark);
    font-size: 12px;
    font-weight: 900;
}

.services-combo-card h3 {
    color: var(--text-dark);
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 900;
}

.services-combo-card p {
    margin: 0;
    color: var(--text-muted);
    line-height: 1.6;
}

.services-combo-card ul {
    margin: 16px 0 0;
    padding-left: 18px;
    color: var(--text-muted);
    line-height: 1.8;
}

.services-combo-price {
    margin-top: 18px;
    padding: 18px;
    border-radius: 16px;
    background: var(--primary-soft);
    text-align: center;
}

.services-combo-card strong {
    display: block;
    font-size: 24px;
    color: var(--primary);
    font-weight: 900;
}

.services-combo-card small {
    display: block;
    margin-top: 5px;
    color: var(--text-muted);
}

.services-checklist-section {
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    gap: 24px;
    align-items: center;
}

.checklist-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.checklist-grid div {
    padding: 14px 16px;
    border-radius: 16px;
    background: var(--bg-soft);
    border: 1px solid var(--border);
    color: var(--primary-dark);
    font-weight: 900;
}

.services-final-cta {
    margin-top: 44px;
    padding: 42px 24px;
    border-radius: 26px;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 34%),
        linear-gradient(135deg, var(--primary), var(--primary-dark));
    text-align: center;
    box-shadow: var(--shadow-md);
}

.services-final-cta h2 {
    color: white;
    margin: 0 0 12px;
    font-size: clamp(24px, 3vw, 34px);
    font-weight: 900;
    letter-spacing: -0.03em;
}

.services-final-cta p {
    max-width: 680px;
    margin: 0 auto 24px;
    color: rgba(255,255,255,0.88);
}

.services-final-actions {
    justify-content: center;
}

@media (max-width: 980px) {
    .services-overview,
    .services-info-grid,
    .process-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .services-checklist-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .services-page {
        padding: 16px 12px 44px;
    }

    .services-page-hero {
        padding: 42px 18px;
        border-radius: 22px;
    }

    .services-search-wrap,
    .services-info-card,
    .services-page-card,
    .services-combo-section,
    .services-checklist-section,
    .services-final-cta {
        border-radius: 20px;
        padding: 22px;
    }

    .services-search-head,
    .services-title-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .services-search-actions,
    .services-card-actions,
    .services-final-actions,
    .services-btn {
        width: 100%;
    }

    .services-overview,
    .services-info-grid,
    .process-grid,
    .checklist-grid {
        grid-template-columns: 1fr;
    }

    .services-thumb {
        height: 190px;
    }
}
</style>