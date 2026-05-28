<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\BookingPayment;
use App\Models\BookingProgress;
use App\Models\BookingReview;
use App\Models\PaymentTransaction;
use App\Models\Service;
use App\Models\User;

/**
 * BookingController xử lý tạo mới và quản lý lịch đặt dịch vụ.
 */
final class BookingController
{
    /**
     * Hiển thị toàn bộ đơn đặt của người dùng đang đăng nhập.
     */
    public function index(): void
    {
        $this->requireAuthentication();

        // ====================================================================
        // BẮT TÍN HIỆU TỪ PAYOS VÀ LÀM SẠCH URL
        // ====================================================================
        if (isset($_GET['code']) || isset($_GET['cancel'])) {
            $code = $_GET['code'] ?? null;
            $cancel = $_GET['cancel'] ?? 'false';
            
            if ($code === '00' && $cancel === 'false') {
                $this->syncSuccessfulPaymentFromQuery((int)Auth::id(), $_GET);
                $_SESSION['success'] = '🎉 Thanh toán thành công! Vui lòng chờ hệ thống cập nhật trạng thái.';
            } else {
                $_SESSION['error'] = '❌ Giao dịch thanh toán thất bại hoặc đã bị hủy.';
            }
            
            // Tự động lấy URL hiện tại và cắt bỏ phần đuôi ?code=...
            $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
            header("Location: " . $cleanUrl);
            exit;
        }

        $userId = Auth::id();
        $bookings = Booking::getByUserId($userId);

        foreach ($bookings as &$booking) {
            $booking['has_review'] = BookingReview::exists((int)$booking['id']);
            $booking['is_customer_paid'] = PaymentTransaction::hasSuccessfulCustomerPayment((int)$booking['id']);
        }
        unset($booking);

        View::render('customer/bookings', [
            'bookings' => $bookings,
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Hiển thị biểu mẫu tạo lịch đặt cùng danh sách dịch vụ khả dụng.
     */
    public function create(): void
    {
        $this->requireAuthentication();

        $services = Service::all();
        $selectedServiceId = $this->extractServiceIdFromQuery();
        $user = User::findById((int)Auth::id());
        $userAddress = trim((string)($user['address'] ?? ''));

        View::render('customer/book', [
            'services' => $services,
            'selected' => $selectedServiceId,
            'userAddress' => $userAddress,
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Xử lý dữ liệu gửi lên từ biểu mẫu đặt lịch.
     * Kiểm tra dữ liệu và tạo đơn đặt mới.
     */
    public function store(): void
    {
        $this->requireAuthentication();
        $this->requirePostRequest();

        // Lấy dữ liệu từ biểu mẫu
        $serviceId = (int)($_POST['service'] ?? 0);
        $date = trim((string)($_POST['date'] ?? ''));
        $time = trim((string)($_POST['time'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $quantity = $this->parseQuantity($_POST['quantity'] ?? null);
        $agreeTerms = isset($_POST['agree_terms']);

        // Kiểm tra các trường bắt buộc
        if (!$this->validateBookingData($serviceId, $date, $time, $location, $agreeTerms)) {
            $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
            $this->redirect('/book');
            return;
        }

        $service = Service::getById($serviceId);
        if ($service === null) {
            $_SESSION['error'] = 'Dịch vụ không tồn tại.';
            $this->redirect('/book');
            return;
        }

        if ($quantity <= 0) {
            $_SESSION['error'] = 'Vui lòng nhập số lượng hợp lệ.';
            $this->redirect('/book');
            return;
        }

        $serviceUnit = (string)($service['unit'] ?? 'lần');
        if ($this->requiresIntegerQuantity($serviceUnit) && floor($quantity) !== $quantity) {
            $_SESSION['error'] = 'Đơn vị này chỉ chấp nhận số nguyên.';
            $this->redirect('/book');
            return;
        }

        $unitPrice = (float)($service['price'] ?? 0);
        $lineTotal = round($unitPrice * $quantity, 2);

        // Kiểm tra ngày đặt phải ở tương lai
        if ($this->isDateInPast($date)) {
            $_SESSION['error'] = 'Ngày đặt lịch phải là ngày trong tương lai.';
            $this->redirect('/book');
            return;
        }

        // Tạo đơn đặt lịch
        $bookingId = Booking::create(
            Auth::id(),
            $serviceId,
            $date,
            $time,
            $location,
            $description ?: null,
            $quantity,
            $serviceUnit,
            $unitPrice,
            $lineTotal
        );

        $availableWorker = User::findAvailableWorker($date, $time);
        if (is_array($availableWorker) && !empty($availableWorker['id'])) {
            if (Booking::assignWorker($bookingId, (int)$availableWorker['id'])) {
                error_log('Đã auto-assign thành công');
            }
        } else {
            error_log('Không có nhân viên rảnh, đơn hàng giữ trạng thái Pending');
        }

        // ====================================================================
        // CHUYỂN HƯỚNG TỚI PAYOS CHECKOUT
        // ====================================================================
        // Sau khi tạo booking thành công, gọi API PayOS để lấy link thanh toán chính thức
        
        // Sinh mã đơn hàng (PayOS yêu cầu kiểu int)
        // Sử dụng timestamp + booking ID để đảm bảo duy nhất
        $orderCode = (int)(time() . $bookingId);
        
        // Lấy amount (tổng tiền thanh toán)
        $amount = (int)$lineTotal;
        
        // Gọi API PayOS để tạo payment link
        $checkoutUrl = $this->createPaymentLinkWithPayOS($bookingId, $orderCode, $amount);
        
        if ($checkoutUrl) {
            // Chuyển hướng đến trang thanh toán chính thức của PayOS
            header("Location: " . $checkoutUrl);
            exit;
        } else {
            // Nếu gọi API thất bại, hiển thị lỗi
            $_SESSION['error'] = 'Không thể tạo link thanh toán. Vui lòng thử lại.';
            $this->redirect('/bookings');
        }
    }

    /**
     * Nhận callback từ PayOS khi thanh toán thành công và đồng bộ trạng thái vào DB.
     */
    public function paymentReturn(): void
    {
        $this->requireAuthentication();

        error_log('PAYOS payment return callback: ' . json_encode([
            'user_id' => Auth::id(),
            'code' => $_GET['code'] ?? null,
            'status' => $_GET['status'] ?? null,
            'payment_success' => $_GET['payment_success'] ?? null,
            'booking_id' => $_GET['booking_id'] ?? null,
            'order_code' => $_GET['order_code'] ?? null,
        ], JSON_UNESCAPED_UNICODE));

        $this->syncSuccessfulPaymentFromQuery((int)Auth::id(), $_GET);
        $this->redirect('/bookings');
    }

    /**
     * Hủy một đơn đặt lịch.
     * Chỉ cho phép người dùng hủy các đơn của chính họ.
     *
     * @param int $id ID đơn đặt cần hủy
     */
    public function cancel(int $id): void
    {
        $this->requireAuthentication();
        $this->requirePostRequest();


        $booking = Booking::getById($id);

        // Kiểm tra đơn đặt tồn tại và thuộc về người dùng hiện tại
        if ($booking === null || (int)$booking['user_id'] !== Auth::id()) {
            $_SESSION['error'] = 'Không tìm thấy đơn đặt.';
            $this->redirect('/bookings');
            return;
        }

        $currentStatus = (string)($booking['status'] ?? '');
        $cancellableStatuses = [
            Booking::STATUS_PENDING,
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_ACCEPTED,
        ];

        if (!in_array($currentStatus, $cancellableStatuses, true)) {
            $_SESSION['error'] = 'Đơn này không thể hủy ở trạng thái hiện tại.';
            $this->redirect('/bookings');
            return;
        }

        // Cập nhật trạng thái đơn đặt
        Booking::updateStatus($id, Booking::STATUS_CANCELLED);

        $_SESSION['success'] = 'Hủy đơn đặt thành công.';
        $this->redirect('/bookings');
    }

    /**
     * Chi tiết booking để khách theo dõi tiến độ, chat, thanh toán.
     */
    public function detail(int $id): void
    {
        $this->requireAuthentication();

        $booking = Booking::getDetailById($id);
        if ($booking === null || (int)$booking['user_id'] !== (int)Auth::id()) {
            $_SESSION['error'] = 'Không tìm thấy đơn đặt.';
            $this->redirect('/bookings');
        }

        $progress = BookingProgress::byBookingId($id);
        $messages = BookingMessage::byBookingId($id);
        $payment = BookingPayment::byBookingId($id);
        $isCustomerPaid = PaymentTransaction::hasSuccessfulCustomerPayment($id);
        $hasReview = BookingReview::exists($id);
        $review = BookingReview::getByBookingId($id);

        View::render('customer/booking-detail', [
            'booking' => $booking,
            'progress' => $progress,
            'messages' => $messages,
            'payment' => $payment,
            'isCustomerPaid' => $isCustomerPaid,
            'hasReview' => $hasReview,
            'review' => $review,
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Khách gửi tin nhắn cho worker.
     */
    public function sendMessage(int $id): void
    {
        $this->requireAuthentication();
        $this->requirePostRequest();


        $booking = Booking::getById($id);
        if ($booking === null || (int)$booking['user_id'] !== (int)Auth::id()) {
            $this->redirect('/bookings');
        }

        $content = trim((string)($_POST['content'] ?? ''));
        if ($content === '') {
            $_SESSION['error'] = 'Tin nhắn không được để trống.';
            $this->redirect('/bookings/' . $id);
        }

        BookingMessage::add($id, (int)Auth::id(), $content);
        $this->redirect('/bookings/' . $id);
    }

    /**
     * Trang đánh giá khi đơn hoàn thành.
     */
    public function review(int $id): void
    {
        $this->requireAuthentication();

        $booking = Booking::getDetailById($id);
        if ($booking === null || (int)$booking['user_id'] !== (int)Auth::id()) {
            $this->redirect('/bookings');
        }

        if (($booking['status'] ?? '') !== Booking::STATUS_COMPLETED) {
            $_SESSION['error'] = 'Đơn đặt chưa hoàn thành.';
            $this->redirect('/bookings/' . $id);
        }

        if (BookingReview::exists($id)) {
            $_SESSION['success'] = 'Bạn đã đánh giá đơn này rồi.';
            $this->redirect('/bookings/' . $id);
        }

        View::render('customer/review', [
            'booking' => $booking,
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Gửi đánh giá và bình luận.
     */
    public function submitReview(int $id): void
    {
        $this->requireAuthentication();
        $this->requirePostRequest();


        $booking = Booking::getDetailById($id);
        if ($booking === null || (int)$booking['user_id'] !== (int)Auth::id()) {
            $this->redirect('/bookings');
        }

        if (BookingReview::exists($id)) {
            $this->redirect('/bookings/' . $id);
        }

        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim((string)($_POST['comment'] ?? ''));
        if ($rating < 1 || $rating > 5) {
            $_SESSION['error'] = 'Điểm đánh giá phải nằm trong khoảng từ 1 đến 5.';
            $this->redirect('/bookings/' . $id . '/review');
        }

        BookingReview::add(
            $id,
            (int)Auth::id(),
            (int)($booking['assigned_worker_id'] ?? 0),
            $rating,
            $comment !== '' ? $comment : null
        );

        $_SESSION['success'] = 'Cảm ơn bạn đã gửi đánh giá.';
        $this->redirect('/bookings/' . $id);
    }

    /**
     * Lấy ID dịch vụ từ chuỗi truy vấn.
     */
    private function extractServiceIdFromQuery(): ?int
    {
        return isset($_GET['service']) ? (int)$_GET['service'] : null;
    }

    /**
     * Kiểm tra dữ liệu biểu mẫu đặt lịch.
     */
    private function validateBookingData(
        int $serviceId,
        string $date,
        string $time,
        string $location,
        bool $agreeTerms
    ): bool {
        return $serviceId > 0 && !empty($date) && !empty($time)
            && !empty($location) && $agreeTerms;
    }

    /**
     * Kiểm tra một chuỗi ngày có nằm trong quá khứ hay không.
     *
     * @param string $date Chuỗi ngày (YYYY-MM-DD)
     * @return bool True nếu ngày nhỏ hơn ngày hiện tại
     */
    private function isDateInPast(string $date): bool
    {
        $bookingDate = strtotime($date);
        $today = strtotime(date('Y-m-d'));
        return $bookingDate < $today;
    }

    /**
     * Đảm bảo phương thức request là POST.
     */
    private function requirePostRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/book');
        }
    }

    /**
     * Đảm bảo người dùng đã được xác thực.
     */
    private function requireAuthentication(): void
    {
        if (!Auth::isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    private function verifyCsrfToken(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Mã bảo mật không hợp lệ. Vui lòng thử lại.';
            exit(1);
        }
    }

    private function parseQuantity(mixed $rawValue): float
    {
        $normalized = str_replace(',', '.', trim((string)$rawValue));
        return is_numeric($normalized) ? (float)$normalized : 0.0;
    }

    private function requiresIntegerQuantity(string $unit): bool
    {
        $normalized = mb_strtolower(trim($unit));
        return str_contains($normalized, 'phòng')
            || str_contains($normalized, 'phong')
            || str_contains($normalized, 'lần')
            || str_contains($normalized, 'lan')
            || str_contains($normalized, 'gói')
            || str_contains($normalized, 'goi');
    }

    /**
     * Thực hiện chuyển hướng tới đường dẫn chỉ định.
     *
     * @param string $path Đường dẫn cần chuyển hướng tới
     */
    private function redirect(string $path): void
    {
        header("Location: $path");
        exit(0);
    }

    /**
     * Đồng bộ thanh toán khách hàng từ query string trả về bởi PayOS.
     */
    private function syncSuccessfulPaymentFromQuery(int $userId, array $query): void
    {
        $isSuccessful = (($query['code'] ?? '') === '00')
            || (($query['status'] ?? '') === 'PAID')
            || (($query['payment_success'] ?? '') === '1');

        if (!$isSuccessful) {
            error_log('PAYOS payment sync skipped: callback not successful');
            return;
        }

        $orderCode = trim((string)($query['order_code'] ?? $query['orderCode'] ?? ''));
        $bookingId = (int)($query['booking_id'] ?? 0);

        $payment = null;
        if ($orderCode !== '') {
            $payment = PaymentTransaction::getByOrderCode($orderCode);
        }

        if ($payment === null && $bookingId > 0) {
            $payment = PaymentTransaction::getByBookingId($bookingId);
        }

        if ($payment === null) {
            $payment = PaymentTransaction::getLatestCustomerPendingByUserId($userId);
        }

        if ($payment === null) {
            error_log('PAYOS payment sync skipped: no payment transaction found');
            return;
        }

        $resolvedBookingId = (int)($payment['booking_id'] ?? 0);
        $resolvedOrderCode = (string)($payment['order_code'] ?? $orderCode);

        error_log("PAYOS payment sync start: booking_id={$resolvedBookingId}, order_code={$resolvedOrderCode}");

        PaymentTransaction::updateStatus($resolvedOrderCode, 'paid', [
            'transaction_id' => (string)($query['id'] ?? $query['transactionId'] ?? ''),
            'payer_account_number' => (string)($query['accountNumber'] ?? $query['payer_account_number'] ?? ''),
            'payer_name' => (string)($query['accountName'] ?? $query['payer_name'] ?? ''),
            'webhook_raw_data' => json_encode($query, JSON_UNESCAPED_UNICODE),
            'webhook_received_at' => date('Y-m-d H:i:s'),
        ]);

        if ($resolvedBookingId > 0) {
            Booking::updateStatus($resolvedBookingId, Booking::STATUS_CONFIRMED);
        }

        error_log("PAYOS payment sync done: booking_id={$resolvedBookingId}, order_code={$resolvedOrderCode}");
    }

    /**
     * Gọi API PayOS để tạo payment link.
     * 
     * YÊU CẦU ĐẦU VÀO:
     * - $orderCode PHẢI là int, được tính từ time() . $bookingId
     * - $amount PHẢI là int (VNĐ, không thập phân)
     * 
     * QUY TRÌNH:
     * 1. Kiểm tra config PayOS
     * 2. Sinh signature HMAC_SHA256 theo chuẩn: amount, cancelUrl, description, orderCode, returnUrl
     * 3. Gọi API: POST /v1/payment-requests
     * 4. Bắt lỗi chi tiết và in ra response
     * 
     * @param int $bookingId ID của booking
     * @param int $orderCode Mã đơn hàng (kiểu integer, bắt buộc)
     * @param int $amount Số tiền thanh toán VNĐ (kiểu integer, bắt buộc)
     * @return string|null URL checkout của PayOS hoặc null nếu thất bại
     */
    private function createPaymentLinkWithPayOS(int $bookingId, int $orderCode, int $amount): ?string
    {
        // ============================================================================
        // BƯỚC 1: KIỂM TRA CONFIG PAYOS
        // ============================================================================
        
        $config = require __DIR__ . '/../../config/app.php';
        $baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
        $offlineMode = defined('APP_OFFLINE_MODE') && APP_OFFLINE_MODE;

        if ($offlineMode) {
            $paymentCreated = PaymentTransaction::create(
                $bookingId,
                $orderCode,
                (float)$amount,
                'pending',
                'TEST_PAYOS_' . $orderCode,
                PaymentTransaction::METHOD_CUSTOMER_PAYMENT
            );

            if (!$paymentCreated) {
                error_log('PAYOS offline mode: failed to create test payment transaction for booking #' . $bookingId);
                return null;
            }

            $query = http_build_query([
                'booking_id' => $bookingId,
                'order_code' => $orderCode,
                'code' => '00',
                'status' => 'PAID',
                'payment_success' => '1',
                'test_mode' => '1',
            ]);

            return '/bookings/payment-return?' . $query;
        }

        $clientId = $config['payos']['client_id'] ?? '';
        $apiKey = $config['payos']['api_key'] ?? '';
        $checksumKey = $config['payos']['checksum_key'] ?? '';
        
        if (empty($clientId) || empty($apiKey) || empty($checksumKey)) {
            die('❌ LỖI CONFIG: Thiếu PayOS credentials (client_id, api_key, hoặc checksum_key)');
        }
        
        // ============================================================================
        // BƯỚC 2: EẤN KIỂU DỮ LIỆU NGHIÊM NGẶT (BẮT BUỘC)
        // ============================================================================
        
        // Ép orderCode và amount về int (yêu cầu PayOS)
        $orderCode = (int)$orderCode;
        $amount = (int)$amount;
        
        if ($orderCode <= 0 || $amount <= 0) {
            die("❌ LỖI DỮ LIỆU: orderCode ({$orderCode}) hoặc amount ({$amount}) phải > 0");
        }
        
        // ============================================================================
        // BƯỚC 3: CHUẨN BỊ DỮ LIỆU GỬI ĐẾN PAYOS
        // ============================================================================
        
        $baseUrl = $baseUrl !== '' ? $baseUrl : 'https://suasively-metaphoric-gearldine.ngrok-free.dev';
        $description = "DVWA_" . $orderCode . "_" . $bookingId;
        $cancelUrl = $baseUrl . '/bookings';
        $returnUrl = $baseUrl . '/bookings/payment-return?booking_id=' . $bookingId . '&order_code=' . $orderCode;
        
        $paymentData = [
            'orderCode' => $orderCode,         // int
            'amount' => $amount,               // int
            'description' => $description,     // string
            'cancelUrl' => $cancelUrl,         // string
            'returnUrl' => $returnUrl,         // string
            'signature' => '',                 // sẽ tính bên dưới
        ];
        
        // ============================================================================
        // BƯỚC 4: TÍNH TOÁN SIGNATURE (HMAC_SHA256) THEO CHUẨN PAYOS
        // ============================================================================
        
        // QUAN TRỌNG: Phải sắp xếp theo thứ tự alphabet của key!
        // Thứ tự: amount, cancelUrl, description, orderCode, returnUrl
        
        // Chuẩn bị dữ liệu cho signature (không bao gồm signature field)
        $dataForSignature = [
            'amount' => $paymentData['amount'],
            'cancelUrl' => $paymentData['cancelUrl'],
            'description' => $paymentData['description'],
            'orderCode' => $paymentData['orderCode'],
            'returnUrl' => $paymentData['returnUrl'],
        ];
        
        // Sắp xếp theo key A-Z (ksort)
        ksort($dataForSignature);
        
        // Nối thành chuỗi: key1=value1&key2=value2&...
        $signatureString = '';
        foreach ($dataForSignature as $key => $value) {
            if (!empty($signatureString)) {
                $signatureString .= '&';
            }
            $signatureString .= $key . '=' . $value;
        }
        
        // Log signature string để debug
        error_log("=== PAYOS SIGNATURE DEBUG ===");
        error_log("Signature String: {$signatureString}");
        
        // Tính HMAC_SHA256 với checksum key
        $paymentData['signature'] = hash_hmac('sha256', $signatureString, $checksumKey);
        
        error_log("Computed Signature: " . $paymentData['signature']);
        error_log("Checksum Key: {$checksumKey}");
        error_log("============================");
        
        // ============================================================================
        // BƯỚC 5: CHUẨN BỊ REQUEST VÀ GỬI CURL
        // ============================================================================
        
        $apiUrl = "https://api-merchant.payos.vn/v2/payment-requests";
        $payload = json_encode($paymentData);
        
        error_log("Request Payload: {$payload}");
        
        // Khởi tạo cURL
        $ch = curl_init($apiUrl);
        
        // Cấu hình cURL - BẮT BUỘC CÓ ĐỦ 3 HEADERS
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',                    // ✅ Header 1
            'x-client-id: ' . $clientId,                         // ✅ Header 2
            'x-api-key: ' . $apiKey,                             // ✅ Header 3
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // ============================================================================
        // BƯỚC 6: THỰC THI REQUEST VÀ LẤY RESPONSE
        // ============================================================================
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Nếu cURL lỗi (network error)
        if ($response === false) {
            die("❌ LỖI CURL: {$curlError}");
        }
        
        // ============================================================================
        // BƯỚC 7: BẮTLỖI CHI TIẾT - IN RESPONSE LỖI RA MÀYSN HÌ
        // ============================================================================
        
        // Log response để debug
        error_log("HTTP Code: {$httpCode}");
        error_log("Response: {$response}");
        
        // Nếu HTTP code không phải 200, in ra lỗi
        if ($httpCode !== 200) {
            // Lỗi từ phía HTTP
            die("❌ LỖI HTTP {$httpCode}:\n" . $response);
        }
        
        // Parse JSON response
        $result = json_decode($response, true);
        
        if ($result === null) {
            die("❌ LỖI PARSE JSON:\n" . $response);
        }
        
        // Kiểm tra response code từ PayOS (phải là '00')
        $payosCode = $result['code'] ?? null;
        $payosMessage = $result['message'] ?? 'Unknown';
        
        if ($payosCode !== '00') {
            // PayOS trả về code lỗi
            die("❌ LỖI PAYOS (Code: {$payosCode}):\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        // Kiểm tra checkoutUrl có tồn tại không
        if (!isset($result['data']['checkoutUrl'])) {
            die("❌ LỖI: PayOS không trả về checkoutUrl\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        // ============================================================================
        // BƯỚC 8: THÀNH CÔNG - LƯU PAYMENT_TRANSACTION VÀ TRẢ VỀ CHECKOUTURL
        // ============================================================================
        
        $checkoutUrl = $result['data']['checkoutUrl'];
        
        // Lưu payment_transaction record
        PaymentTransaction::create(
            $bookingId,
            $orderCode,
            $amount,
            'pending',
            $description,
            PaymentTransaction::METHOD_CUSTOMER_PAYMENT
        );
        
        error_log("✅ SUCCESS: Created payment link for booking #{$bookingId}");
        
        return $checkoutUrl;
    }

    /**
     * Xử lý yêu cầu thanh toán lại từ danh sách đơn đặt.
     * Route: POST /bookings/{id}/repay
     */
    public function repay(int $id): void
    {
        $this->requireAuthentication();
        $this->requirePostRequest();

        
        // Kiểm tra booking tồn tại và thuộc người dùng
        $booking = Booking::getDetailById($id);
        if ($booking === null || (int)$booking['user_id'] !== Auth::id()) {
            $_SESSION['error'] = 'Không tìm thấy đơn đặt.';
            $this->redirect('/bookings');
            return;
        }
        
        // Chỉ cho phép trả tiền cho đơn đang chờ thanh toán
        if (($booking['status'] ?? '') !== Booking::STATUS_PENDING) {
            $_SESSION['error'] = 'Đơn này không cần thanh toán.';
            $this->redirect('/bookings');
            return;
        }
        
        // Sinh order code và tạo payment link
        $orderCode = (int)(time() . $id);
        $amount = (int)($booking['service_price'] ?? 0);
        
        $checkoutUrl = $this->createPaymentLinkWithPayOS($id, $orderCode, $amount);
        
        if ($checkoutUrl) {
            header("Location: " . $checkoutUrl);
            exit;
        } else {
            $_SESSION['error'] = 'Không thể tạo link thanh toán. Vui lòng thử lại.';
            $this->redirect('/bookings');
        }
    }

}
