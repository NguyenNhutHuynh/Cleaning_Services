<?php
use App\Core\View;
/** @var array $previousContacts */
/** @var bool $isAuthenticated */
/** @var array $contactPrefill */
?>

<style>
    .contact-page {
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

    .contact-page * {
        box-sizing: border-box;
    }

    .contact-page .home-hero {
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

    .contact-page .home-hero::after {
        content: "";
        position: absolute;
        right: -80px;
        bottom: -80px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(46,175,125,0.13);
    }

    .contact-page .home-hero h1 {
        position: relative;
        margin: 0 0 12px;
        font-size: clamp(32px, 5vw, 52px);
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -0.04em;
        color: var(--text-dark);
    }

    .contact-page .home-hero p {
        position: relative;
        margin: 0;
        font-size: 17px;
        color: var(--text-muted);
    }

    .contact-form-section {
        margin-top: 40px;
        display: grid;
        grid-template-columns: 1.25fr 0.75fr;
        gap: 26px;
    }

    .contact-form-box,
    .contact-info-box,
    .contact-history-card,
    .working-hours-section {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 24px;
        box-shadow: var(--shadow-sm);
    }

    .contact-form-box {
        padding: 32px;
    }

    .contact-form-box h2,
    .contact-history-section h2,
    .working-hours-section h2,
    .contact-cta-section h2 {
        margin: 0 0 24px;
        color: var(--text-dark);
        font-size: clamp(24px, 3vw, 34px);
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .contact-form {
        display: grid;
        gap: 18px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-dark);
        font-weight: 700;
        font-size: 14px;
    }

    .required {
        color: #e74c3c;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid var(--border);
        border-radius: 14px;
        font-size: 15px;
        font-family: inherit;
        color: var(--text-dark);
        background: #fcfffd;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .form-group textarea {
        min-height: 130px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(46,175,125,0.12);
    }

    .contact-form button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        min-height: 46px;
        padding: 12px 28px;
        border: none;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 22px rgba(46,175,125,0.22);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .contact-form button:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(46,175,125,0.28);
    }

    .contact-form button:disabled {
        background: #bbb;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .alert {
        max-width: 900px;
        margin: 30px auto 0;
        padding: 16px 20px;
        border-radius: 18px;
        font-weight: 700;
        box-shadow: var(--shadow-sm);
    }

    .alert-success {
        background: #e8f7f0;
        color: #16805a;
        border: 1px solid #bdebd7;
    }

    .alert-error {
        background: #fff1f1;
        color: #b42318;
        border: 1px solid #ffd1d1;
    }

    .contact-info-section {
        display: grid;
        gap: 18px;
    }

    .contact-info-box {
        padding: 24px;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .contact-info-box:hover {
        transform: translateY(-4px);
        border-color: rgba(46,175,125,0.45);
        box-shadow: var(--shadow-md);
    }

    .contact-info-box h3 {
        margin: 0 0 12px;
        color: var(--text-dark);
        font-size: 19px;
        font-weight: 800;
    }

    .contact-info-box p {
        margin: 0;
        color: var(--text-muted);
        font-size: 14px;
        line-height: 1.6;
    }

    .contact-info-box p:first-of-type {
        color: var(--primary);
        font-size: 18px;
        font-weight: 800;
    }

    .contact-info-box a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 800;
    }

    .contact-info-box a:hover {
        text-decoration: underline;
    }

    .info-detail {
        margin-top: 6px !important;
        color: var(--text-muted) !important;
        font-size: 14px !important;
        font-weight: 400 !important;
    }

    .info-subtitle {
        color: var(--text-dark) !important;
        font-size: 16px !important;
        font-weight: 700 !important;
    }

.social-links {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    margin-top: 14px;
}

.social-link {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: var(--primary-soft);
    text-decoration: none;
    color: var(--primary);
    transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

    .social-link:hover {
        transform: translateY(-3px);
        background: #d9f2e7;
        box-shadow: var(--shadow-sm);
    }

    .social-link svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
    }

    .social-link-facebook { color: #1877f2; }
    .social-link-github { color: #1f2328; }
    .social-link-mail { color: #ea4335; }

    .contact-history-section {
        margin-top: 50px;
    }

    .contact-history-card {
        padding: 24px;
        margin-bottom: 18px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .contact-history-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .history-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 14px;
    }

    .history-card-header h3 {
        color: var(--text-dark);
        margin: 0 0 8px;
        font-size: 20px;
        font-weight: 800;
    }

    .history-card-header p {
        margin: 0;
        color: var(--text-muted);
        font-size: 14px;
    }

    .status-badge {
        display: inline-flex;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .status-replied {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-pending {
        background: #fff3e0;
        color: #e65100;
    }

    .history-message {
        background: #f7fdf9;
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid var(--border);
        margin: 14px 0;
    }

    .history-message p {
        margin: 0;
        color: var(--text-dark);
        line-height: 1.6;
    }

    .admin-reply {
        background: #e8f7f0;
        padding: 16px;
        border-radius: 16px;
        border-left: 4px solid var(--primary);
        margin: 14px 0;
    }

    .admin-reply-title {
        margin: 0 0 8px;
        color: var(--primary-dark);
        font-weight: 800;
    }

    .admin-reply-content {
        margin: 0;
        color: #1b5e20;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .admin-reply-time {
        margin: 8px 0 0;
        font-size: 12px;
        color: #558b2f;
    }

    .working-hours-section {
        margin-top: 50px;
        padding: 34px;
        text-align: center;
        background: linear-gradient(135deg, #f7fdf9, #ffffff);
    }

    .hours-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 16px;
    }

    .hours-item {
        padding: 20px;
        border-radius: 18px;
        background: white;
        border: 1px solid var(--border);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hours-item:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-sm);
    }

    .hours-item strong {
        display: block;
        color: var(--text-dark);
        font-weight: 800;
    }

    .hours-item p {
        margin: 8px 0 0;
        color: var(--text-muted);
    }

    .contact-cta-section {
        margin-top: 44px;
        padding: 38px 24px;
        border-radius: 26px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        text-align: center;
        box-shadow: var(--shadow-md);
    }

    .contact-cta-section h2 {
        color: white;
        margin-bottom: 22px;
    }

    .cta-buttons {
        display: flex;
        gap: 14px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .cta-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 12px 26px;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 800;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .cta-btn:hover {
        transform: translateY(-2px);
    }

    .cta-btn-primary {
        background: white;
        color: var(--primary-dark);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }

    .cta-btn-outline {
        color: white;
        border: 1.5px solid rgba(255,255,255,0.75);
        background: rgba(255,255,255,0.08);
    }

    .cta-btn-outline:hover {
        background: rgba(255,255,255,0.16);
    }

    @media (max-width: 900px) {
        .contact-form-section {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .contact-page {
            padding: 16px 12px 44px;
        }

        .contact-page .home-hero {
            padding: 42px 18px;
            border-radius: 22px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .contact-form-box,
        .contact-info-box,
        .working-hours-section,
        .contact-cta-section,
        .contact-history-card {
            border-radius: 20px;
        }

        .contact-form-box,
        .working-hours-section {
            padding: 22px;
        }

        .history-card-header {
            flex-direction: column;
        }

        .contact-form button,
        .cta-btn {
            width: 100%;
        }
    }
</style>

<section class="home-container contact-page">
    <header class="home-hero">
        <h1>Liên hệ với chúng tôi</h1>
        <p>Chúng tôi sẵn sàng trả lời mọi câu hỏi của bạn</p>
    </header>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= View::e($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?= View::e($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <section class="contact-form-section">
        <div class="contact-form-box">
            <h2>Gửi tin nhắn</h2>

            <?php if ($isAuthenticated): ?>
                <form method="POST" action="/contact" class="contact-form" id="contact-form">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ tên <span class="required">*</span></label>
                            <input type="text" name="name" required placeholder="Nhập họ tên của bạn" value="<?= View::e((string)($contactPrefill['name'] ?? '')) ?>" <?= $isAuthenticated ? 'readonly' : '' ?>>
                        </div>

                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" required placeholder="example@email.com" value="<?= View::e((string)($contactPrefill['email'] ?? '')) ?>" <?= $isAuthenticated ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Điện thoại <span class="required">*</span></label>
                            <input type="tel" name="phone" required placeholder="0123456789" value="<?= View::e((string)($contactPrefill['phone'] ?? '')) ?>" <?= $isAuthenticated ? 'readonly' : '' ?>>
                        </div>

                        <div class="form-group">
                            <label>Chủ đề <span class="required">*</span></label>
                            <select name="subject" required>
                                <option value="">-- Chọn chủ đề --</option>
                                <option value="Hỏi giá">Hỏi giá dịch vụ</option>
                                <option value="Tư vấn">Tư vấn dịch vụ</option>
                                <option value="Khiếu nại">Khiếu nại / Phản hồi</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tin nhắn <span class="required">*</span></label>
                        <textarea name="message" required placeholder="Viết tin nhắn của bạn..."></textarea>
                    </div>

                    <button type="submit">Gửi tin nhắn</button>
                </form>
            <?php else: ?>
                <div class="alert alert-error" style="margin-top: 0;">
                    Bạn cần <a href="/login?return_to=/contact">đăng nhập tài khoản khách hàng</a> để gửi tin nhắn liên hệ.
                </div>
            <?php endif; ?>
        </div>

        <div class="contact-info-section" id="contact-info">
            <div class="contact-info-box">
                <h3>📞 Điện thoại</h3>
                <p>0382 583 013</p>
                <p class="info-detail">Hỗ trợ 24/7</p>
            </div>

            <div class="contact-info-box">
                <h3>✉️ Email</h3>
                <p><a href="mailto:nhuthuynhforwork@gmail.com">nhuthuynhforwork@gmail.com</a></p>
                <p class="info-detail">Liên hệ công việc và hỗ trợ trực tiếp</p>
            </div>

            <div class="contact-info-box">
                <h3>📍 Địa chỉ</h3>
                <p class="info-subtitle">12 Nguyễn Văn Bảo, Quận Gò Vấp</p>
                <p class="info-detail">TP. Hồ Chí Minh, Việt Nam</p>
            </div>

            <div class="contact-info-box">
                <h3>Mạng xã hội</h3>

                <div class="social-links">
                    <a href="https://www.facebook.com/Minashhh.04" class="social-link social-link-facebook" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M13.5 8H16V4.5h-2.9C10.2 4.5 8.5 6.3 8.5 9.4V12H6v3.5h2.5V23h3.7v-7.5H15l.5-3.5h-3.3V9.8c0-1 .3-1.8 1.8-1.8z"/>
                        </svg>
                    </a>

                    <a href="https://github.com/NguyenNhutHuynh" class="social-link social-link-github" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 .5C5.7.5.8 5.5.8 11.7c0 5 3.2 9.2 7.7 10.7.6.1.8-.3.8-.6v-2.2c-3.1.7-3.8-1.3-3.8-1.3-.5-1.3-1.2-1.6-1.2-1.6-1-.7.1-.7.1-.7 1.1.1 1.7 1.1 1.7 1.1 1 .1 2.6 2.1 2.6 2.1.9 1.5 2.4 1.1 3 .9.1-.7.3-1.1.6-1.3-2.5-.3-5.2-1.2-5.2-5.6 0-1.2.4-2.2 1.1-3-.1-.3-.5-1.4.1-2.9 0 0 .9-.3 3 .1.9-.2 1.9-.3 2.9-.3s2 .1 2.9.3c2.1-.4 3-.1 3-.1.6 1.5.2 2.6.1 2.9.7.8 1.1 1.8 1.1 3 0 4.4-2.7 5.3-5.3 5.6.4.3.7 1 .7 2.1v3.1c0 .3.2.7.8.6 4.5-1.5 7.7-5.7 7.7-10.7C23.2 5.5 18.3.5 12 .5z"/>
                        </svg>
                    </a>

                    <a href="mailto:nhuthuynhforwork@gmail.com" class="social-link social-link-mail" aria-label="Email">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2.5 5.5h19a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-19a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1zm1.2 2L12 13.2l8.3-5.7H3.7zm17.8 9.8V8.7l-9 6.2a1 1 0 0 1-1.1 0l-9-6.2v8.6h19z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($isAuthenticated && !empty($previousContacts)): ?>
        <section class="contact-history-section">
            <h2>Phản hồi từ Admin</h2>

            <?php foreach ($previousContacts as $contact): ?>
                <div class="contact-history-card">
                    <div class="history-card-header">
                        <div>
                            <h3><?= View::e((string)($contact['subject'] ?? '')) ?></h3>
                            <p>Gửi lúc: <?= View::e((string)($contact['created_at'] ?? '')) ?></p>
                        </div>

                        <span class="status-badge <?= $contact['status'] === 'replied' ? 'status-replied' : 'status-pending' ?>">
                            <?= $contact['status'] === 'replied' ? 'Đã trả lời' : 'Chờ xử lý' ?>
                        </span>
                    </div>

                    <div class="history-message">
                        <p>"<?= View::e((string)($contact['message'] ?? '')) ?>"</p>
                    </div>

                    <?php if (!empty($contact['reply'])): ?>
                        <div class="admin-reply">
                            <p class="admin-reply-title">Phản hồi từ Admin:</p>
                            <p class="admin-reply-content"><?= View::e((string)$contact['reply']) ?></p>
                            <p class="admin-reply-time">
                                Trả lời lúc: <?= View::e((string)($contact['replied_at'] ?? '')) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="working-hours-section">
        <h2>Giờ làm việc</h2>

        <div class="hours-grid">
            <div class="hours-item">
                <strong>Thứ 2 - 6</strong>
                <p>7:00 - 22:00</p>
            </div>

            <div class="hours-item">
                <strong>Thứ 7</strong>
                <p>8:00 - 22:00</p>
            </div>

            <div class="hours-item">
                <strong>Chủ nhật</strong>
                <p>8:00 - 20:00</p>
            </div>

            <div class="hours-item">
                <strong>Lễ tết</strong>
                <p>Mở cửa thường</p>
            </div>
        </div>
    </section>

    <section class="contact-cta-section">
        <h2>Cần dịch vụ ngay?</h2>

        <div class="cta-buttons">
            <a href="/book" class="cta-btn cta-btn-primary">Đặt lịch</a>
            <a href="tel:1900123456" class="cta-btn cta-btn-outline">Gọi ngay</a>
        </div>
    </section>
</section>