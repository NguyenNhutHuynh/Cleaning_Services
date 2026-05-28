<?php
declare(strict_types=1);

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key) && empty($_ENV[$key]) && empty($_SERVER[$key])) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

$config = require __DIR__ . '/../config/app.php';
$offlineMode = defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE;

// Secure session cookie settings
// Only set secure flag if truly HTTPS in production
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
           (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if ($isHttps) {
  ini_set('session.cookie_secure', '1');
}

$cookieParams = [
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',  // Empty = browser uses document.domain; works for all subdomains
  'secure' => $isHttps,  // false in local development (HTTP), true in production (HTTPS)
  'httponly' => true,  // Prevent JS access
  'samesite' => 'Lax',  // Protect against CSRF while allowing top-level navigation
];
session_name($config['app']['session_name']);
session_set_cookie_params($cookieParams);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if ($offlineMode) {
  header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data:; frame-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self'; connect-src 'self';");
} else {
  header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; frame-src https://maps.google.com https://www.google.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://maps.google.com https://www.google.com;");
}

// Tự tải đơn giản cho namespace App\
spl_autoload_register(function($class){
  $prefix = 'App\\';
  if (str_starts_with($class, $prefix)) {
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($path)) require $path;
  }
});

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\ServicesController;
use App\Controllers\PricingController;
use App\Controllers\ContactController;
use App\Controllers\BookingController;
use App\Controllers\AdminController;
use App\Controllers\Manager\ManagerController;
use App\Controllers\CleanerController;
use App\Controllers\CustomerController;
use App\Controllers\WorkerController;
use App\Controllers\AccountController;
use App\Core\Auth;
use App\Core\View;
use App\Core\DB;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;

$router = new Router();

// Trang chủ
$router->get('/', function() {
  $uid = Auth::id();
  $user = $uid ? User::findById((int)$uid) : null;
  
  // Get featured services (first 6)
  $allServices = Service::all();
  $featuredServices = array_slice($allServices, 0, 6);

  $normalizeServiceName = static function(string $name): string {
    if (function_exists('mb_strtolower')) {
      return mb_strtolower($name);
    }

    return strtolower($name);
  };

  $serviceIdByName = [];
  foreach ($allServices as $service) {
    $name = trim((string)($service['name'] ?? ''));
    if ($name === '') {
      continue;
    }

    $serviceIdByName[$normalizeServiceName($name)] = (int)($service['id'] ?? 0);
  }

  $buildComboLink = static function(string $serviceName) use ($serviceIdByName, $normalizeServiceName): string {
    $key = $normalizeServiceName($serviceName);
    $serviceId = $serviceIdByName[$key] ?? 0;

    return $serviceId > 0 ? '/book?service=' . $serviceId : '/book';
  };

  $comboLinks = [
    'tong_ve_sinh' => $buildComboLink('Combo Tổng vệ sinh'),
    'gia_dinh' => $buildComboLink('Combo Gia đình'),
    'chuyen_nha' => $buildComboLink('Combo Chuyển Nhà'),
  ];
  
  // Get statistics
  $totalServices = count($allServices);
  
  // Count total bookings
  $allBookings = Booking::getAll();
  $totalBookings = count($allBookings);
  
  // Count completed bookings (successfully finished jobs)
  $completedBookings = count(array_filter(
    $allBookings,
    static fn(array $b): bool => ($b['status'] ?? '') === 'completed'
  ));
  
  // Count active approved workers
  $stmt = DB::pdo()->prepare(
    "SELECT COUNT(*) as total FROM users WHERE role = 'worker' AND approval_status = 'active'"
  );
  $stmt->execute();
  $workerResult = $stmt->fetch();
  $totalWorkers = (int)($workerResult['total'] ?? 0);
  
  // Average rating from reviews
  $avgRatingStmt = DB::pdo()->query(
    "SELECT AVG(rating) as avg_rating
     FROM booking_reviews
     WHERE is_hidden IS NULL OR is_hidden = 0"
  );
  $ratingResult = $avgRatingStmt->fetch();
  $averageRating = $ratingResult ? round((float)($ratingResult['avg_rating'] ?? 4.9), 1) : 4.9;
  
  View::render('home', [
    'uid' => $uid,
    'name' => $user['name'] ?? null,
    'featuredServices' => $featuredServices,
    'comboLinks' => $comboLinks,
    'totalBookings' => $totalBookings,
    'completedBookings' => $completedBookings,
    'totalWorkers' => $totalWorkers,
    'averageRating' => $averageRating,
    'totalServices' => $totalServices,
  ]);
});

// Xác thực
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/admin/login', [AuthController::class, 'showAdminLogin']);
$router->post('/admin/login', [AuthController::class, 'adminLogin']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

// Điều Khoản & Chính Sách
$router->get('/terms', function() {
  View::render('terms');
});
$router->get('/privacy', function() {
  View::render('privacy');
});

// Các dịch vụ
$router->get('/services', [ServicesController::class, 'index']);
// Chi tiết dịch vụ qua truy vấn: /service?id=123
$router->get('/service', [ServicesController::class, 'show']);

// Bảng giá
$router->get('/pricing', [PricingController::class, 'index']);

// Liên hệ
$router->get('/contact', [ContactController::class, 'index']);
$router->post('/contact', [ContactController::class, 'store']);

// Đặt lịch
$router->get('/book', [BookingController::class, 'create']);
$router->post('/book', [BookingController::class, 'store']);
$router->get('/bookings', [BookingController::class, 'index']);
$router->get('/bookings/{id}', [BookingController::class, 'detail']);
$router->get('/bookings/payment-return', [BookingController::class, 'paymentReturn']);
$router->post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
$router->post('/bookings/{id}/message', [BookingController::class, 'sendMessage']);
$router->post('/bookings/{id}/repay', [BookingController::class, 'repay']);
$router->get('/bookings/{id}/review', [BookingController::class, 'review']);
$router->post('/bookings/{id}/review', [BookingController::class, 'submitReview']);

// Bảng điều khiển theo vai trò
$router->get('/admin', function() { header('Location: /admin/dashboard'); exit; });
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/cleaner/dashboard', [CleanerController::class, 'dashboard']);
$router->get('/worker/dashboard', [WorkerController::class, 'dashboard']);
$router->get('/worker/pending', [WorkerController::class, 'pending']);
$router->get('/customer/dashboard', [CustomerController::class, 'dashboard']);
$router->get('/customer/messages', [CustomerController::class, 'messages']);
$router->post('/customer/messages', [CustomerController::class, 'sendMessage']);

// Trang quản lý Admin
$router->get('/admin/services', [AdminController::class, 'services']);
$router->post('/admin/services/update', [AdminController::class, 'updateService']);
$router->post('/admin/services/toggle', [AdminController::class, 'toggleService']);
$router->post('/admin/services/delete', [AdminController::class, 'deleteService']);
$router->post('/admin/services/create', [AdminController::class, 'createService']);
$router->get('/admin/bookings', [AdminController::class, 'bookings']);
$router->get('/admin/bookings/{id}', [AdminController::class, 'bookingDetail']);
$router->get('/admin/payment-transactions/{id}', [AdminController::class, 'paymentTransactionDetail']);
$router->post('/admin/bookings/{id}/message', [AdminController::class, 'sendBookingMessage']);
$router->post('/admin/reviews/{id}/hide', [AdminController::class, 'hideReview']);
$router->post('/admin/reviews/{id}/show', [AdminController::class, 'showReview']);
// Các hành động đặt lịch Admin
$router->post('/admin/bookings/confirm', [AdminController::class, 'confirmBooking']);
$router->post('/admin/bookings/cancel', [AdminController::class, 'cancelBooking']);
$router->post('/admin/bookings/assign', [AdminController::class, 'assignBooking']);
$router->get('/admin/moderation', [AdminController::class, 'moderation']);
$router->get('/admin/worker-schedules', [AdminController::class, 'workerSchedules']);
$router->post('/admin/contact/reply', [AdminController::class, 'replyContact']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->get('/admin/user', [AdminController::class, 'userDetail']);
$router->get('/admin/user/json', [AdminController::class, 'userDetailJson']);
$router->get('/admin/user/messages', [AdminController::class, 'userMessagesJson']);
$router->post('/admin/user/message', [AdminController::class, 'sendUserMessage']);
$router->get('/admin/stats', [AdminController::class, 'stats']);
// Phê duyệt người dùng Admin
$router->post('/admin/users/approve', [AdminController::class, 'approveWorker']);
$router->post('/admin/users/reject', [AdminController::class, 'rejectWorker']);
// Các hành động quản lý người dùng Admin
$router->post('/admin/user/update', [AdminController::class, 'userUpdate']);
$router->post('/admin/user/lock', [AdminController::class, 'userLock']);
$router->post('/admin/user/unlock', [AdminController::class, 'userUnlock']);
$router->post('/admin/user/delete', [AdminController::class, 'userDelete']);
// ===== MANAGER ROUTES (Quản lý vận hành) =====
// Manager Dashboard
$router->get('/manager', function() { header('Location: /manager/dashboard'); exit; });
$router->get('/manager/dashboard', [ManagerController::class, 'dashboard']);

// Manager Bookings
$router->get('/manager/bookings', [ManagerController::class, 'bookings']);
$router->get('/manager/bookings/{id}', [ManagerController::class, 'bookingDetail']);
$router->post('/manager/bookings/confirm', [ManagerController::class, 'confirmBooking']);
$router->post('/manager/bookings/cancel', [ManagerController::class, 'cancelBooking']);
$router->post('/manager/bookings/assign', [ManagerController::class, 'assignBooking']);

// Manager Workers
$router->get('/manager/workers', [ManagerController::class, 'workers']);
$router->get('/manager/workers/{id}', [ManagerController::class, 'workerDetail']);
$router->post('/manager/workers/approve', [ManagerController::class, 'approveWorker']);
$router->post('/manager/workers/reject', [ManagerController::class, 'rejectWorker']);

// Manager Customers
$router->get('/manager/customers', [ManagerController::class, 'customers']);
$router->get('/manager/customers/{id}', [ManagerController::class, 'customerDetail']);
// Trang người dọn dẹp
$router->get('/cleaner/jobs', [CleanerController::class, 'jobs']);
$router->get('/cleaner/progress', [CleanerController::class, 'progress']);
$router->get('/cleaner/schedule', [CleanerController::class, 'schedule']);

// Trang công nhân (tên vai trò mới)
$router->get('/worker/jobs', [WorkerController::class, 'jobs']);
$router->get('/worker/messages', [WorkerController::class, 'messages']);
$router->post('/worker/messages/{id}', [WorkerController::class, 'sendAdminMessage']);
$router->post('/worker/messages/direct', [WorkerController::class, 'sendAdminDirectMessage']);
$router->post('/worker/jobs/{id}/accept', [WorkerController::class, 'acceptJob']);
$router->get('/worker/jobs/{id}', [WorkerController::class, 'jobDetail']);
$router->post('/worker/jobs/{id}/start', [WorkerController::class, 'startJob']);
$router->post('/worker/jobs/{id}/progress', [WorkerController::class, 'updateProgress']);
$router->post('/worker/jobs/{id}/update-eta', [WorkerController::class, 'updateETA']);
$router->post('/worker/jobs/{id}/message', [WorkerController::class, 'sendMessage']);
$router->get('/worker/jobs/{id}/report', [WorkerController::class, 'completionReport']);
$router->post('/worker/jobs/{id}/report', [WorkerController::class, 'submitReport']);
$router->get('/worker/progress', [WorkerController::class, 'progress']);
$router->get('/worker/schedule', [WorkerController::class, 'schedule']);

// Tài khoản (hồ sơ & cài đặt)
$router->get('/account', [AccountController::class, 'profile']);
$router->get('/account/edit', [AccountController::class, 'edit']);
$router->post('/account/edit', [AccountController::class, 'update']);
$router->get('/account/change-password', [AccountController::class, 'changePassword']);
$router->post('/account/update-password', [AccountController::class, 'updatePassword']);

$router->dispatch();
