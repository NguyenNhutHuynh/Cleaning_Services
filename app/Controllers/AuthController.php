<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\User; 

/**
 * AuthController xử lý xác thực người dùng (đăng nhập, đăng ký, đăng xuất).
 */
final class AuthController
{
    // Hằng số kiểm tra độ mạnh mật khẩu
    private const MIN_PASSWORD_LENGTH = 6;
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 15 * 60;
    private const LOGIN_LOCKOUT_SECONDS = 30 * 60;

    // Các vai trò người dùng được phép
    private const ALLOWED_ROLES = ['customer', 'worker'];

    // Mã trạng thái HTTP
    private const STATUS_CSRF_TOKEN_MISMATCH = 419;
    private const ALLOWED_CITIES = ['TP.HCM', 'Thành phố Hồ Chí Minh', 'Ho Chi Minh City'];

    /**
     * Hiển thị biểu mẫu đăng ký người dùng.
     */
    public static function showRegister(): void
    {
        View::render('auth/register', [
            'csrf' => Csrf::token(),
            'error' => null,
        ]);
    }

    /**
     * Xử lý dữ liệu gửi lên từ biểu mẫu đăng ký.
     * Tạo tài khoản mới với trạng thái duyệt phù hợp theo vai trò.
     */
    public static function register(): void
    {
        // CSRF token already verified by Router::dispatch() for all POST requests
        // No need to verify again here

        // Lấy và chuẩn hóa dữ liệu biểu mẫu
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = trim((string)($_POST['role'] ?? User::ROLE_CUSTOMER));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $district = trim((string)($_POST['district'] ?? ''));
        $ward = trim((string)($_POST['ward'] ?? ''));
        $addressDetail = trim((string)($_POST['address_detail'] ?? ''));

        // Kiểm tra dữ liệu đầu vào
        $validationError = self::validateRegistrationInput(
            $name,
            $email,
            $password,
            $role,
            $phone,
            $city,
            $district,
            $ward,
            $addressDetail
        );
        if ($validationError !== null) {
            View::render('auth/register', [
                'csrf' => Csrf::token(),
                'error' => $validationError,
            ]);
            return;
        }

        $fullAddress = $addressDetail . ', ' . $ward . ', ' . $district . ', ' . $city;

        // Tạo tài khoản người dùng
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $approvalStatus = $role === User::ROLE_WORKER ? User::STATUS_PENDING : User::STATUS_ACTIVE;
        $userId = User::create($name, $email, $passwordHash, $role, $approvalStatus, $phone, $fullAddress);

        // Gán thông báo thành công và chuyển hướng
        self::setSessionMessage(
            'success',
            $role === User::ROLE_WORKER
                ? 'Đăng ký thành công! Tài khoản worker đang chờ duyệt. Vui lòng đăng nhập sau khi được duyệt.'
                : 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.'
        );
        $_SESSION['login_email'] = $email;
        self::redirect('/login');
    }

    /**
     * Hiển thị biểu mẫu đăng nhập.
     */
    public static function showLogin(): void
    {
        $prefillEmail = (string)($_SESSION['login_email'] ?? '');
        unset($_SESSION['login_email']);

        $returnTo = self::resolveLoginReturnTo($_GET['return_to'] ?? null);
        if ($returnTo !== null) {
            $_SESSION['login_return_to'] = $returnTo;
        }

        $storedReturnTo = self::resolveLoginReturnTo($_SESSION['login_return_to'] ?? null);
        $contactLoginFlow = $storedReturnTo === '/contact';

        View::render('auth/login', [
            'csrf' => Csrf::token(),
            'error' => null,
            'email' => $prefillEmail,
            'returnTo' => $storedReturnTo,
            'contactLoginFlow' => $contactLoginFlow,
        ]);
    }

    /**
     * Xử lý dữ liệu gửi lên từ biểu mẫu đăng nhập.
     * Xác thực người dùng và chuyển hướng tới trang điều khiển phù hợp.
     */
    public static function login(): void
    {
        // CSRF token already verified by Router::dispatch() for all POST requests
        // No need to verify again here

        // Lấy dữ liệu biểu mẫu
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // Rate-limit theo IP + email: tối đa 5 lần trong cửa sổ 15 phút
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $throttleError = self::getThrottleError('user_login', $ip, $email);
        if ($throttleError !== null) {
            $returnTo = self::resolveLoginReturnTo($_SESSION['login_return_to'] ?? null);
            View::render('auth/login', [
                'csrf' => Csrf::token(),
                'error' => $throttleError,
                'email' => $email,
                'returnTo' => $returnTo,
                'contactLoginFlow' => $returnTo === '/contact',
            ]);
            return;
        }

        // Tìm người dùng theo email
        $user = User::findByEmail($email);
        $returnTo = self::resolveLoginReturnTo($_SESSION['login_return_to'] ?? null);

        // Kiểm tra mật khẩu và user existence
        $authOk = $user !== null && password_verify($password, $user['password_hash']);
        if (!$authOk) {
            self::recordLoginFailure('user_login', $ip, $email);

            View::render('auth/login', [
                'csrf' => Csrf::token(),
                'error' => 'Đăng nhập không thành công. Vui lòng kiểm tra email hoặc mật khẩu.',
                'email' => $email,
                'returnTo' => $returnTo,
                'contactLoginFlow' => $returnTo === '/contact',
            ]);
            return;
        }


        // Kiểm tra trạng thái tài khoản (nếu không cho phép đăng nhập vẫn trả về thông báo chung)
        $accountStatus = (string)($user['approval_status'] ?? User::STATUS_ACTIVE);
        $statusError = self::validateAccountStatus($accountStatus, $user);
        if ($statusError !== null) {
            self::recordLoginFailure('user_login', $ip, $email);

            View::render('auth/login', [
                'csrf' => Csrf::token(),
                'error' => 'Đăng nhập không thành công. Vui lòng kiểm tra email hoặc mật khẩu.',
                'email' => $email,
                'returnTo' => $returnTo,
                'contactLoginFlow' => $returnTo === '/contact',
            ]);
            return;
        }

        if ($returnTo === '/contact' && self::normalizeUserRole((string)$user['role']) !== User::ROLE_CUSTOMER) {
            $_SESSION['login_return_to'] = '/contact';
            View::render('auth/login', [
                'csrf' => Csrf::token(),
                'error' => 'Luồng đăng nhập từ trang liên hệ chỉ dành cho tài khoản khách hàng.',
                'email' => $email,
                'returnTo' => $returnTo,
                'contactLoginFlow' => true,
            ]);
            return;
        }

        // Chặn admin khỏi đăng nhập trên trang login thường
        $userRole = self::normalizeUserRole((string)$user['role']);
        if ($userRole === User::ROLE_ADMIN) {
            View::render('auth/login', [
                'csrf' => Csrf::token(),
                'error' => 'Tài khoản admin không thể đăng nhập trên trang này. Vui lòng sử dụng trang quản trị riêng biệt.',
                'email' => $email,
                'returnTo' => $returnTo,
                'contactLoginFlow' => $returnTo === '/contact',
            ]);
            return;
        }

        // Đăng nhập người dùng
        Auth::login((int)$user['id'], $userRole);

        // Thành công: xóa bộ đếm attempt cho bucket này
        self::clearLoginThrottle('user_login', $ip, $email);
        unset($_SESSION['login_return_to']);

        // Chuyển hướng tới trang điều khiển phù hợp
        if ($userRole === User::ROLE_CUSTOMER && $returnTo === '/contact') {
            self::redirect('/contact');
            return;
        }

        self::redirectToUserDashboard($userRole, $accountStatus);
    }

    /**
     * Hiển thị trang đăng nhập admin/manager (yêu cầu secret key).
     * Chỉ có thể truy cập được thông qua URL có chứa key chính xác.
     * Hỗ trợ chuyển đổi giữa admin và manager login thông qua form tabs.
     */
    public static function showAdminLogin(): void
    {
        // Kiểm tra xem session đã được xác thực hay chưa
        $sessionVerified = $_SESSION['admin_login_verified'] ?? false;
        $verifiedTime = $_SESSION['admin_login_verified_time'] ?? 0;
        $sessionValid = $sessionVerified && (time() - $verifiedTime) <= 300; // 5 minute window
        
        // Nếu session chưa được xác thực, kiểm tra secret key từ URL
        if (!$sessionValid) {
            $config = require __DIR__ . '/../../config/app.php';
            $adminKey = $config['admin']['login_key'] ?? 'admin-secret-key-2024';
            $providedKey = (string)($_GET['key'] ?? '');

            if (empty($providedKey) || !hash_equals($adminKey, $providedKey)) {
                // Invalid or missing key - redirect to home
                self::redirect('/');
                return;
            }

            // Key đúng - lưu vào session
            $_SESSION['admin_login_verified'] = true;
            $_SESSION['admin_login_verified_time'] = time();
        }

        // Lấy loại login từ GET parameter (admin hoặc manager)
        $loginType = trim((string)($_GET['type'] ?? 'admin'));
        if (!in_array($loginType, ['admin', 'manager'], true)) {
            $loginType = 'admin';
        }

        // Hiển thị form đăng nhập với hỗ trợ cho cả admin và manager
        View::render('auth/admin-login', [
            'csrf' => Csrf::token(),
            'error' => null,
            'email' => '',
            'hideChrome' => true,
            'loginType' => $loginType,
        ]);
    }

    /**
     * Xử lý đăng nhập cho tài khoản admin hoặc manager.
     * Kiểm tra loại login từ form và xác thực vai trò phù hợp.
     */
    public static function adminLogin(): void
    {
        // Verify admin session was properly initiated
        $sessionVerified = $_SESSION['admin_login_verified'] ?? false;
        $verifiedTime = $_SESSION['admin_login_verified_time'] ?? 0;
        
        if (!$sessionVerified || (time() - $verifiedTime) > 300) { // 5 minute window
            // Session expired or not verified - redirect to home
            self::redirect('/');
            return;
        }

        // Get form data
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $loginType = trim((string)($_POST['login_type'] ?? 'admin'));

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $throttleError = self::getThrottleError('admin_login:' . $loginType, $ip, $email);
        if ($throttleError !== null) {
            View::render('auth/admin-login', [
                'csrf' => Csrf::token(),
                'error' => $throttleError,
                'email' => $email,
                'hideChrome' => true,
                'loginType' => $loginType,
            ]);
            return;
        }
        
        // Validate login type
        if (!in_array($loginType, ['admin', 'manager'], true)) {
            $loginType = 'admin';
        }

        // Find user and verify credentials
        $user = User::findByEmail($email);
        
        // Check if user exists and password is correct
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            self::recordLoginFailure('admin_login:' . $loginType, $ip, $email);

            View::render('auth/admin-login', [
                'csrf' => Csrf::token(),
                'error' => 'Email hoặc mật khẩu không chính xác.',
                'email' => $email,
                'hideChrome' => true,
                'loginType' => $loginType,
            ]);
            return;
        }

        // Get user role
        $userRole = self::normalizeUserRole((string)$user['role']);

        // Validate role based on login type
        if ($loginType === 'admin') {
            if ($userRole !== User::ROLE_ADMIN) {
                self::recordLoginFailure('admin_login:' . $loginType, $ip, $email);
                View::render('auth/admin-login', [
                    'csrf' => Csrf::token(),
                    'error' => 'Tài khoản này không có quyền đăng nhập vào khu vực quản trị Admin. Chỉ tài khoản Admin mới có thể đăng nhập ở đây.',
                    'email' => $email,
                    'hideChrome' => true,
                    'loginType' => $loginType,
                ]);
                return;
            }
        } elseif ($loginType === 'manager') {
            if ($userRole !== User::ROLE_MANAGER && $userRole !== User::ROLE_ADMIN) {
                self::recordLoginFailure('admin_login:' . $loginType, $ip, $email);
                View::render('auth/admin-login', [
                    'csrf' => Csrf::token(),
                    'error' => 'Tài khoản này không có quyền đăng nhập vào khu vực quản lý. Chỉ tài khoản Manager hoặc Admin mới có thể đăng nhập ở đây.',
                    'email' => $email,
                    'hideChrome' => true,
                    'loginType' => $loginType,
                ]);
                return;
            }
        }

        // Check account status
        $accountStatus = (string)($user['approval_status'] ?? User::STATUS_ACTIVE);
        $statusError = self::validateAccountStatus($accountStatus, $user);
        if ($statusError !== null) {
            self::recordLoginFailure('admin_login:' . $loginType, $ip, $email);
            View::render('auth/admin-login', [
                'csrf' => Csrf::token(),
                'error' => 'Tài khoản này không thể đăng nhập. ' . $statusError,
                'email' => $email,
                'hideChrome' => true,
                'loginType' => $loginType,
            ]);
            return;
        }

        // Login successful
        Auth::login((int)$user['id'], $userRole);
        unset($_SESSION['admin_login_verified']);
        unset($_SESSION['admin_login_verified_time']);
        self::clearLoginThrottle('admin_login:' . $loginType, $ip, $email);
        
        // Redirect based on role
        if ($userRole === User::ROLE_ADMIN) {
            self::redirect('/admin/dashboard');
        } elseif ($userRole === User::ROLE_MANAGER) {
            self::redirect('/manager/dashboard');
        } else {
            // Fallback for unexpected role
            self::redirect('/');
        }
    }

    /**
     * Xử lý đăng xuất.
     * Xóa session và chuyển hướng về trang chủ hoặc trang đăng nhập quản lý.
     */
    public static function logout(): void
    {
        if (!Auth::isAuthenticated()) {
            self::redirect('/');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            View::render('auth/logout-confirm', [
                'csrf' => Csrf::token(),
                'role' => Auth::role(),
            ]);
            return;
        }

        Auth::logout();
        self::redirect('/');
    }

    /**
     * Kiểm tra một bucket login có đang bị khóa tạm thời không.
     */
    private static function getThrottleError(string $scope, string $ip, string $identifier): ?string
    {
        $state = self::getThrottleState($scope, $ip, $identifier);
        $now = time();

        if (($state['locked_until'] ?? 0) > $now) {
            return 'Quá nhiều lần đăng nhập không thành công. Vui lòng thử lại sau.';
        }

        $attempts = self::pruneAttempts((array)($state['attempts'] ?? []), $now);
        if (count($attempts) >= self::LOGIN_MAX_ATTEMPTS) {
            self::storeThrottleState($scope, $ip, $identifier, [
                'attempts' => $attempts,
                'locked_until' => $now + self::LOGIN_LOCKOUT_SECONDS,
            ]);

            return 'Quá nhiều lần đăng nhập không thành công. Vui lòng thử lại sau.';
        }

        self::storeThrottleState($scope, $ip, $identifier, [
            'attempts' => $attempts,
            'locked_until' => 0,
        ]);

        return null;
    }

    /**
     * Ghi nhận một lần đăng nhập thất bại.
     */
    private static function recordLoginFailure(string $scope, string $ip, string $identifier): void
    {
        $state = self::getThrottleState($scope, $ip, $identifier);
        $now = time();
        $attempts = self::pruneAttempts((array)($state['attempts'] ?? []), $now);
        $attempts[] = $now;

        $lockedUntil = (int)($state['locked_until'] ?? 0);
        if (count($attempts) >= self::LOGIN_MAX_ATTEMPTS) {
            $lockedUntil = $now + self::LOGIN_LOCKOUT_SECONDS;
        }

        self::storeThrottleState($scope, $ip, $identifier, [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ]);
    }

    /**
     * Xóa throttle bucket sau khi đăng nhập thành công.
     */
    private static function clearLoginThrottle(string $scope, string $ip, string $identifier): void
    {
        $bucket = self::makeThrottleBucket($scope, $ip, $identifier);
        unset($_SESSION['login_throttle'][$bucket]);
    }

    /**
     * Lấy trạng thái throttle hiện tại của một bucket.
     */
    private static function getThrottleState(string $scope, string $ip, string $identifier): array
    {
        $bucket = self::makeThrottleBucket($scope, $ip, $identifier);
        $state = $_SESSION['login_throttle'][$bucket] ?? null;

        return is_array($state) ? $state : [
            'attempts' => [],
            'locked_until' => 0,
        ];
    }

    /**
     * Lưu trạng thái throttle.
     */
    private static function storeThrottleState(string $scope, string $ip, string $identifier, array $state): void
    {
        $bucket = self::makeThrottleBucket($scope, $ip, $identifier);
        $_SESSION['login_throttle'][$bucket] = [
            'attempts' => array_values($state['attempts'] ?? []),
            'locked_until' => (int)($state['locked_until'] ?? 0),
            'updated_at' => time(),
        ];
    }

    /**
     * Tạo khóa throttle ổn định theo scope + IP + email.
     */
    private static function makeThrottleBucket(string $scope, string $ip, string $identifier): string
    {
        $normalizedIdentifier = strtolower(trim($identifier));
        return hash('sha256', strtolower($scope) . '|' . strtolower(trim($ip)) . '|' . $normalizedIdentifier);
    }

    /**
     * Loại bỏ các lần thử cũ ngoài cửa sổ cho phép.
     */
    private static function pruneAttempts(array $attempts, int $now): array
    {
        return array_values(array_filter(
            array_map('intval', $attempts),
            static fn(int $timestamp): bool => ($timestamp + self::LOGIN_WINDOW_SECONDS) >= $now
        ));
    }

    /**
     * Kiểm tra dữ liệu đầu vào của biểu mẫu đăng ký.
     *
     * @return string|null Thông báo lỗi nếu kiểm tra thất bại, ngược lại là null
     */
    private static function validateRegistrationInput(
        string $name,
        string $email,
        string $password,
        string $role,
        string $phone,
        string $city,
        string $district,
        string $ward,
        string $addressDetail
    ): ?string {
        // Kiểm tra các trường bắt buộc
        if (
            empty($name)
            || empty($email)
            || empty($password)
            || empty($phone)
            || empty($city)
            || empty($district)
            || empty($ward)
            || empty($addressDetail)
        ) {
            return 'Vui lòng điền đầy đủ các trường bắt buộc.';
        }

        if (!in_array($city, self::ALLOWED_CITIES, true)) {
            return 'Chỉ hỗ trợ đăng ký trong khu vực TP.HCM.';
        }

        if (mb_strlen($addressDetail) < 5) {
            return 'Vui lòng nhập địa chỉ chi tiết hợp lệ.';
        }

        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';
        if (!preg_match('/^(0|84)([0-9]{9,10})$/', $normalizedPhone)) {
            return 'Số điện thoại không hợp lệ.';
        }

        // Kiểm tra định dạng email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Vui lòng nhập địa chỉ email hợp lệ.';
        }

        // Kiểm tra độ dài mật khẩu
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'Mật khẩu phải có ít nhất ' . self::MIN_PASSWORD_LENGTH . ' ký tự.';
        }

        // Kiểm tra vai trò
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            return 'Vai trò người dùng không hợp lệ.';
        }

        // Kiểm tra email đã tồn tại hay chưa
        if (User::findByEmail($email) !== null) {
            return 'Địa chỉ email đã tồn tại.';
        }

        return null;
    }

    /**
     * Kiểm tra trạng thái tài khoản khi đăng nhập.
     * Xác định tài khoản có bị khóa hoặc đã bị xóa hay không.
     *
     * @param string $status Trạng thái tài khoản
     * @param array $user Dữ liệu người dùng
     * @return string|null Thông báo lỗi nếu tài khoản không thể đăng nhập, ngược lại là null
     */
    private static function validateAccountStatus(string $status, array $user): ?string
    {
        if ($status === User::STATUS_LOCKED) {
            $reason = trim((string)($user['reject_reason'] ?? ''));
            if ($reason === '') {
                $reason = 'Tài khoản đã bị khóa. Vui lòng liên hệ bộ phận hỗ trợ.';
            }

            return "Tài khoản đã bị khóa.\nLý do: {$reason}";
        }

        if ($status === User::STATUS_DELETED) {
            $reason = trim((string)($user['reject_reason'] ?? ''));
            if ($reason === '') {
                $reason = 'Tài khoản đã bị xóa hoặc bị vô hiệu hóa.';
            }

            return "Tài khoản đã bị vô hiệu hóa.\nLý do: {$reason}";
        }

        return null;
    }

    /**
     * Chuẩn hóa vai trò người dùng (đổi vai trò cũ 'cleaner' thành 'worker').
     */
    private static function normalizeUserRole(string $role): string
    {
        return $role === 'cleaner' ? User::ROLE_WORKER : $role;
    }

    /**
     * Chuyển hướng người dùng tới dashboard phù hợp theo vai trò và trạng thái.
     */
    private static function redirectToUserDashboard(string $role, string $status): void
    {
        $path = match ($role) {
            User::ROLE_ADMIN => '/admin/dashboard',
            User::ROLE_WORKER => $status !== User::STATUS_ACTIVE ? '/worker/pending' : '/worker/dashboard',
            User::ROLE_CUSTOMER => '/',
            default => '/',
        };

        self::redirect($path);
    }

    /**
     * Chỉ chấp nhận luồng quay lại an toàn từ trang liên hệ.
     */
    private static function resolveLoginReturnTo(mixed $returnTo): ?string
    {
        return $returnTo === '/contact' ? '/contact' : null;
    }

    /**
     * Gán thông báo flash vào session.
     */

    private static function setSessionMessage(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$type] = $message;
    }

    /**
     * Thực hiện chuyển hướng tới đường dẫn chỉ định.
     */
    private static function redirect(string $path): void
    {
        header("Location: $path");
        exit(0);
    }
}
