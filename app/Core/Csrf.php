<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Quản lý token CSRF (Cross-Site Request Forgery).
 * Tạo và xác thực token CSRF để bảo vệ biểu mẫu.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf';
    private const TOKEN_LENGTH = 32; // 32 byte = 64 ký tự hex

    public static function generateCsrfToken(): string
    {
        return self::token();
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        return self::verify($token);
    }

    public static function csrfInput(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    public static function csrfMetaTag(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    /**
     * Lấy hoặc tạo token CSRF cho session hiện tại.
     * Token được lưu trong session và trả về dưới dạng chuỗi hex.
     *
     * @return string Token CSRF (64 ký tự hex)
     */
    public static function token(): string
    {
        self::ensureSessionActive();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = self::generateToken();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Xác thực token CSRF với token trong session.
     * Sử dụng so sánh an toàn theo thời gian để tránh tấn công timing.
     *
     * @param string|null $token Token cần xác thực
     * @return bool True nếu token hợp lệ, ngược lại là false
     */
    public static function verify(?string $token): bool
    {
        self::ensureSessionActive();

        if (!is_string($token) || !isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Tạo token CSRF ngẫu nhiên mới.
     *
     * @return string Token ngẫu nhiên an toàn mật mã (định dạng hex)
     */
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Đảm bảo session đang hoạt động trước khi truy cập dữ liệu session.
     */
    private static function ensureSessionActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
