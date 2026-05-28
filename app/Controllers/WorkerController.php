<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\UploadHelper;
use App\Core\View;
use App\Models\AdminUserMessage;
use App\Models\AdminWorkerMessage;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\BookingPayment;
use App\Models\BookingProgress;
use App\Models\BookingReport;
use App\Models\BookingReview;
use App\Models\User;

/**
 * WorkerController xử lý bảng điều khiển và nghiệp vụ công việc cho nhân viên.
 * Bao gồm xem đơn được phân công, lịch làm việc,
 * theo dõi tiến độ và trang trạng thái duyệt tài khoản.
 * Hầu hết thao tác yêu cầu vai trò worker và trạng thái đã duyệt.
 */
final class WorkerController
{
    /**
     * Hiển thị bảng điều khiển chính của nhân viên.
     * Yêu cầu vai trò worker và trạng thái duyệt đang hoạt động.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $this->requireApprovedWorkerRole();
        $uid = Auth::id();
        $user = User::findById($uid);
        
        // Lấy danh sách công việc được phân công cho nhân viên
        $allBookings = Booking::getByWorkerId($uid);
        
        // Lọc công việc hôm nay
        $today = date('Y-m-d');
        $todayBookings = array_values(array_filter(
            $allBookings,
            static fn(array $booking): bool => ($booking['date'] ?? '') === $today &&
                                               in_array($booking['status'] ?? '', 
                                                   [Booking::STATUS_ACCEPTED, 
                                                    Booking::STATUS_IN_PROGRESS,
                                                    Booking::STATUS_CONFIRMED], true)
        ));
        
        // Đếm công việc chưa xem
        $newJobs = count(array_filter($allBookings, 
            static fn(array $b): bool => ($b['status'] ?? '') === Booking::STATUS_CONFIRMED));
        
        // Đếm công việc đang thực hiện
        $activeJobs = count(array_filter($allBookings, 
            static fn(array $b): bool => ($b['status'] ?? '') === Booking::STATUS_IN_PROGRESS));
        
        // Lấy công việc hoàn thành trong tuần
        $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));
        $completedThisWeek = count(array_filter($allBookings, 
            static fn(array $b): bool => ($b['status'] ?? '') === Booking::STATUS_COMPLETED &&
                                        ($b['updated_at'] ?? '') >= $oneWeekAgo));
        
        View::render('worker/dashboard', [
            'uid' => $uid,
            'role' => User::ROLE_WORKER,
            'name' => $user['name'] ?? 'Worker',
            'todayBookings' => $todayBookings,
            'newJobs' => $newJobs,
            'activeJobs' => $activeJobs,
            'completedThisWeek' => $completedThisWeek,
        ]);
    }

    /**
     * Hiển thị các công việc đang chờ để nhân viên nhận.
     * Yêu cầu vai trò worker và trạng thái duyệt đang hoạt động.
     *
     * @return void
     */
    public function jobs(): void
    {
        $this->requireApprovedWorkerRole();
        $workerId = (int)Auth::id();
        $bookings = Booking::getByWorkerId($workerId);

        $readyJobs = array_values(array_filter(
            $bookings,
            static fn(array $booking): bool => ($booking['status'] ?? '') === Booking::STATUS_CONFIRMED
        ));

        $activeJobs = array_values(array_filter(
            $bookings,
            static fn(array $booking): bool => in_array($booking['status'] ?? '', [Booking::STATUS_ACCEPTED, Booking::STATUS_IN_PROGRESS], true)
        ));

        View::render('worker/jobs', [
            'readyJobs' => $readyJobs,
            'activeJobs' => $activeJobs,
            'csrf' => Csrf::token(),
        ]);
    }

    public function messages(): void
    {
        $this->requireApprovedWorkerRole();
        $workerId = (int)Auth::id();

        $directMessages = AdminUserMessage::byUserId($workerId);
        $messages = AdminWorkerMessage::byWorkerId($workerId);
        $grouped = [];
        foreach ($messages as $message) {
            $bookingId = (int)($message['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                continue;
            }

            if (!isset($grouped[$bookingId])) {
                $grouped[$bookingId] = [
                    'booking_id' => $bookingId,
                    'service_name' => $message['service_name'] ?? '',
                    'customer_name' => $message['customer_name'] ?? '',
                    'messages' => [],
                ];
            }
            $grouped[$bookingId]['messages'][] = $message;
        }

        View::render('worker/messages', [
            'directMessages' => $directMessages,
            'threads' => array_values($grouped),
            'csrf' => Csrf::token(),
        ]);
    }

    public function sendAdminDirectMessage(): void
    {
        $this->requireApprovedWorkerRole();


        $workerId = (int)Auth::id();
        $content = trim((string)($_POST['content'] ?? ''));

        if ($content === '') {
            $_SESSION['error'] = 'Tin nhắn không được để trống.';
            $this->redirect('/worker/messages#direct-admin-chat');
        }

        AdminUserMessage::add($workerId, $workerId, User::ROLE_WORKER, $content);
        $_SESSION['success'] = 'Đã gửi tin nhắn cho admin.';
        $this->redirect('/worker/messages#direct-admin-chat');
    }

    public function sendAdminMessage(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/messages');
        }

        $content = trim((string)($_POST['content'] ?? ''));
        if ($content === '') {
            $_SESSION['error'] = 'Tin nhắn không được để trống.';
            $this->redirect('/worker/messages#booking-' . $id);
        }

        AdminWorkerMessage::add(
            $id,
            (int)Auth::id(),
            (int)Auth::id(),
            User::ROLE_WORKER,
            $content
        );

        $_SESSION['success'] = 'Đã gửi tin nhắn cho admin.';
        $this->redirect('/worker/messages#booking-' . $id);
    }

    /**
     * Worker xác nhận nhận việc.
     */
    public function acceptJob(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        if (($booking['status'] ?? '') !== Booking::STATUS_CONFIRMED) {
            $_SESSION['error'] = 'Công việc chưa sẵn sàng để nhận.';
            $this->redirect('/worker/jobs');
        }

        Booking::updateStatus($id, Booking::STATUS_ACCEPTED);
        $_SESSION['success'] = 'Đã nhận việc. Bạn có thể bắt đầu ngay.';
        $this->redirect('/worker/jobs/' . $id);
    }

    /**
     * Hiển thị chi tiết công việc để bắt đầu thực hiện.
     */
    public function jobDetail(int $id): void
    {
        $this->requireApprovedWorkerRole();
        $booking = $this->findOwnedBooking($id);

        if ($booking === null) {
            $_SESSION['error'] = 'Không tìm thấy công việc.';
            $this->redirect('/worker/jobs');
        }

        $progress = BookingProgress::byBookingId($id);
        $messages = BookingMessage::byBookingId($id);
        $payment = BookingPayment::byBookingId($id);

        View::render('worker/job-detail', [
            'job' => $booking,
            'progress' => $progress,
            'messages' => $messages,
            'payment' => $payment,
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Worker bấm Let's go để bắt đầu di chuyển.
     */
    public function startJob(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        $status = (string)($booking['status'] ?? '');
        if (!in_array($status, [Booking::STATUS_ACCEPTED, Booking::STATUS_CONFIRMED], true)) {
            $_SESSION['error'] = 'Không thể bắt đầu công việc này.';
            $this->redirect('/worker/jobs/' . $id);
        }

        Booking::updateStatus($id, Booking::STATUS_IN_PROGRESS);
        BookingProgress::add($id, BookingProgress::ON_THE_WAY, 'Worker bắt đầu di chuyển.', (int)Auth::id());

        $_SESSION['success'] = 'Bắt đầu di chuyển! Công việc đã được bắt đầu.';
        $this->redirect('/worker/jobs/' . $id . '?live=1');
    }

    /**
     * Worker cập nhật tiến độ và ảnh.
     */
    public function updateProgress(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        $step = trim((string)($_POST['step'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));
        $progressOrder = [
            BookingProgress::ON_THE_WAY,
            BookingProgress::ARRIVED,
            BookingProgress::BEFORE_PHOTO,
            BookingProgress::AFTER_PHOTO,
            BookingProgress::COMPLETED,
        ];
        $requiredPhotoSteps = [
            BookingProgress::ARRIVED,
            BookingProgress::BEFORE_PHOTO,
            BookingProgress::AFTER_PHOTO,
        ];

        if (!in_array($step, $progressOrder, true)) {
            $_SESSION['error'] = 'Bước tiến độ không hợp lệ.';
            $this->redirect('/worker/jobs/' . $id);
        }

        $latestStep = BookingProgress::latestStep($id);
        $currentIndex = $latestStep === null ? -1 : array_search($latestStep, $progressOrder, true);
        $nextIndex = ($currentIndex === false) ? 0 : $currentIndex + 1;
        $expectedStep = $progressOrder[$nextIndex] ?? null;

        if ($expectedStep === null) {
            $_SESSION['error'] = 'Tiến độ đã hoàn thành, không thể cập nhật thêm.';
            $this->redirect('/worker/jobs/' . $id);
        }

        if ($step !== $expectedStep) {
            $_SESSION['error'] = 'Vui lòng cập nhật theo thứ tự. Bước tiếp theo: ' . BookingProgress::stepLabel($expectedStep) . '.';
            $this->redirect('/worker/jobs/' . $id);
        }

        $photoError = $this->validateUploadedProgressPhotos(in_array($step, $requiredPhotoSteps, true));
        if ($photoError !== null) {
            $_SESSION['error'] = $photoError;
            $this->redirect('/worker/jobs/' . $id);
        }

        $progressId = BookingProgress::add($id, $step, $note !== '' ? $note : null, (int)Auth::id());
        $this->uploadProgressPhotos($progressId);

        if ($step === BookingProgress::COMPLETED) {
            Booking::updateStatus($id, Booking::STATUS_COMPLETED);
            $_SESSION['success'] = 'Công việc đã hoàn thành. Vui lòng gửi báo cáo.';
            $this->redirect('/worker/jobs/' . $id . '/report');
        }

        if (($booking['status'] ?? '') !== Booking::STATUS_IN_PROGRESS) {
            Booking::updateStatus($id, Booking::STATUS_IN_PROGRESS);
        }

        $_SESSION['success'] = 'Đã cập nhật tiến độ.';
        $this->redirect('/worker/jobs/' . $id);
    }

    private function validateUploadedProgressPhotos(bool $requireAtLeastOne): ?string
    {
        if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
            return $requireAtLeastOne ? 'Vui lòng tải ít nhất 1 ảnh cho bước này.' : null;
        }

        $count = count($_FILES['photos']['name']);
        $hasImage = false;
        for ($index = 0; $index < $count; $index++) {
            $error = (int)($_FILES['photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                return 'Tải ảnh tiến độ thất bại.';
            }

            $file = [
                'name' => (string)($_FILES['photos']['name'][$index] ?? ''),
                'type' => (string)($_FILES['photos']['type'][$index] ?? ''),
                'tmp_name' => (string)($_FILES['photos']['tmp_name'][$index] ?? ''),
                'error' => $error,
                'size' => (int)($_FILES['photos']['size'][$index] ?? 0),
            ];

            $validation = UploadHelper::validateImageUpload($file);
            if (!($validation['success'] ?? false)) {
                return (string)($validation['error'] ?? 'Ảnh tiến độ không hợp lệ.');
            }

            $hasImage = true;
        }

        if ($requireAtLeastOne && !$hasImage) {
            return 'Vui lòng tải ít nhất 1 ảnh cho bước này.';
        }

        return null;
    }

    /**
     * Worker cập nhật thời gian ước tính đến.
     */
    public function updateETA(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        $eta = trim((string)($_POST['estimated_arrival_time'] ?? ''));
        if ($eta === '') {
            $_SESSION['error'] = 'Vui lòng chọn thời gian dự kiến.';
            $this->redirect('/worker/jobs/' . $id);
        }

        // Convert datetime-local format (YYYY-MM-DDTHH:MM) to database format (YYYY-MM-DD HH:MM)
        $eta = str_replace('T', ' ', $eta);

        if (Booking::updateEstimatedArrivalTime($id, $eta)) {
            $_SESSION['success'] = 'Đã cập nhật thời gian dự kiến đến.';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật thời gian dự kiến.';
        }

        $this->redirect('/worker/jobs/' . $id);
    }

    /**
     * Worker gửi tin nhắn cho khách hàng.
     */
    public function sendMessage(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        $message = trim((string)($_POST['content'] ?? ''));
        if ($message === '') {
            $_SESSION['error'] = 'Tin nhắn không được để trống.';
            $this->redirect('/worker/jobs/' . $id);
        }

        BookingMessage::add($id, (int)Auth::id(), $message);
        $this->redirect('/worker/jobs/' . $id);
    }

    /**
     * Trang báo cáo sau khi hoàn thành job.
     */
    public function completionReport(int $id): void
    {
        $this->requireApprovedWorkerRole();
        $booking = $this->findOwnedBooking($id);

        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        if (($booking['status'] ?? '') !== Booking::STATUS_COMPLETED) {
            $_SESSION['error'] = 'Chỉ có thể gửi báo cáo sau khi hoàn thành công việc.';
            $this->redirect('/worker/jobs/' . $id);
        }

        View::render('worker/completion-report', [
            'job' => $booking,
            'hasReport' => BookingReport::exists($id),
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Gửi báo cáo và tạo dữ liệu thanh toán.
     */
    public function submitReport(int $id): void
    {
        $this->requireApprovedWorkerRole();


        $booking = $this->findOwnedBooking($id);
        if ($booking === null) {
            $this->redirect('/worker/jobs');
        }

        if (($booking['status'] ?? '') !== Booking::STATUS_COMPLETED) {
            $_SESSION['error'] = 'Công việc chưa hoàn thành.';
            $this->redirect('/worker/jobs/' . $id);
        }

        if (!BookingReport::exists($id)) {
            $difficulties = trim((string)($_POST['difficulties'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));
            
            // Gộp cả difficulties và note thành 1 report
            $report = '';
            if ($difficulties !== '') {
                $report .= "Khó khăn gặp phải:\n" . $difficulties;
            }
            if ($note !== '') {
                if ($report !== '') $report .= "\n\n";
                $report .= "Ghi chú thêm:\n" . $note;
            }
            
            BookingReport::add(
                $id,
                (int)Auth::id(),
                $report !== '' ? $report : null
            );
        }

        BookingPayment::createIfNotExists(
            $id,
            (int)$booking['user_id'],
            (int)$booking['assigned_worker_id'],
            (float)($booking['service_price'] ?? 0)
        );

        $_SESSION['success'] = 'Đã gửi báo cáo hoàn thành.';
        $this->redirect('/worker/jobs');
    }

    /**
     * Hiển thị tiến độ các job của worker.
     */
    public function progress(): void
    {
        $this->requireApprovedWorkerRole();
        $workerId = (int)Auth::id();
        $jobs = Booking::getByWorkerId($workerId);

        $items = [];
        foreach ($jobs as $job) {
            $latest = BookingProgress::latestStep((int)$job['id']);
            $items[] = [
                'booking_id' => (int)$job['id'],
                'step' => $latest !== null ? BookingProgress::stepLabel($latest) : 'Chưa cập nhật',
                'time' => ($job['date'] ?? '') . ' ' . ($job['time'] ?? ''),
                'status' => $job['status'] ?? '',
            ];
        }

        View::render('worker/progress', ['progress' => $items]);
    }

    /**
     * Hiển thị lịch làm việc từ các đơn đã phân công cho nhân viên.
     * Yêu cầu vai trò worker và trạng thái duyệt đang hoạt động.
     * Lịch này gom các đơn đã phân công theo ngày để worker dễ theo dõi.
     *
     * @return void
     */
    public function schedule(): void
    {
        $this->requireApprovedWorkerRole();
        $workerId = (int)Auth::id();
        $bookings = Booking::getByWorkerId($workerId);

        $scheduleItems = array_values(array_map(
            static function (array $booking): array {
                $status = (string)($booking['status'] ?? '');

                return [
                    'id' => (int)($booking['id'] ?? 0),
                    'date' => (string)($booking['date'] ?? ''),
                    'time' => (string)($booking['time'] ?? ''),
                    'service_name' => (string)($booking['service_name'] ?? 'N/A'),
                    'location' => (string)($booking['location'] ?? 'N/A'),
                    'customer_name' => (string)($booking['user_name'] ?? 'N/A'),
                    'phone' => (string)($booking['user_phone'] ?? ''),
                    'status' => $status,
                    'status_label' => match ($status) {
                        Booking::STATUS_CONFIRMED => 'Chờ nhận việc',
                        Booking::STATUS_ACCEPTED => 'Đã nhận việc',
                        Booking::STATUS_IN_PROGRESS => 'Đang thực hiện',
                        Booking::STATUS_COMPLETED => 'Hoàn thành',
                        Booking::STATUS_CANCELLED => 'Đã hủy',
                        default => ucfirst($status !== '' ? $status : 'unknown'),
                    },
                ];
            },
            $bookings
        ));

        $today = date('Y-m-d');
        $upcomingCount = count(array_filter(
            $scheduleItems,
            static fn(array $item): bool => ($item['status'] ?? '') !== Booking::STATUS_CANCELLED &&
                (($item['date'] ?? '') >= $today)
        ));

        $todayItems = array_values(array_filter(
            $scheduleItems,
            static fn(array $item): bool => ($item['date'] ?? '') === $today &&
                ($item['status'] ?? '') !== Booking::STATUS_CANCELLED
        ));

        $groupedByDate = [];
        foreach ($scheduleItems as $item) {
            $date = $item['date'] !== '' ? $item['date'] : 'unknown';
            $groupedByDate[$date][] = $item;
        }

        ksort($groupedByDate);

        View::render('worker/schedule', [
            'scheduleItems' => $scheduleItems,
            'groupedByDate' => $groupedByDate,
            'todayItems' => $todayItems,
            'upcomingCount' => $upcomingCount,
            'csrf' => Csrf::token(),
        ]);
    }

    /**
     * Tìm job theo id và kiểm tra worker sở hữu job đó.
     */
    private function findOwnedBooking(int $bookingId): ?array
    {
        $booking = Booking::getDetailById($bookingId);
        if ($booking === null) {
            return null;
        }

        if ((int)($booking['assigned_worker_id'] ?? 0) !== (int)Auth::id()) {
            return null;
        }

        return $booking;
    }

    private function uploadProgressPhotos(int $progressId): void
    {
        if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
            return;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/progress';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $count = count($_FILES['photos']['name']);
        for ($index = 0; $index < $count; $index++) {
            if ((int)($_FILES['photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $file = [
                'name' => (string)$_FILES['photos']['name'][$index],
                'type' => (string)$_FILES['photos']['type'][$index],
                'tmp_name' => (string)$_FILES['photos']['tmp_name'][$index],
                'error' => (int)$_FILES['photos']['error'][$index],
                'size' => (int)$_FILES['photos']['size'][$index],
            ];

            $result = UploadHelper::uploadImage($file, $uploadDir);
            if (!($result['success'] ?? false)) {
                continue;
            }

            BookingProgress::addPhoto($progressId, '/' . ltrim((string)$result['path'], '/'));
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

    /**
     * Hiển thị trang trạng thái duyệt cho worker đang chờ hoặc bị từ chối.
     * Worker đã đăng nhập đều có thể truy cập bất kể trạng thái duyệt.
     * Hiển thị trạng thái chờ xử lý hoặc lý do từ chối.
     *
     * @return void
     */
    public function pending(): void
    {
        $this->requireWorkerRole();
        $uid = Auth::id();
        $user = User::findById($uid);
        View::render('worker/pending', [
            'status' => $user['approval_status'] ?? 'pending',
            'reason' => $user['reject_reason'] ?? null,
            'name' => $user['name'] ?? 'Worker',
            'hideChrome' => true,
        ]);
    }

    /**
     * Bắt buộc người dùng đã xác thực với vai trò worker.
     * Chuyển về trang đăng nhập nếu chưa xác thực hoặc sai vai trò.
     *
     * @return void
     */
    private function requireWorkerRole(): void
    {
        if (!Auth::isAuthenticated() || !Auth::hasRole(User::ROLE_WORKER)) {
            $this->redirect('/login');
        }
    }

    /**
     * Bắt buộc người dùng đã xác thực vai trò worker và có trạng thái duyệt active.
     * Chuyển về đăng nhập nếu chưa xác thực, chuyển trang pending nếu chưa được duyệt.
     *
     * @return void
     */
    private function requireApprovedWorkerRole(): void
    {
        $this->requireWorkerRole();

        $uid = Auth::id();
        $user = User::findById($uid);
        $approvalStatus = $user['approval_status'] ?? '';

        if ($approvalStatus !== 'active') {
            $this->redirect('/worker/pending');
        }
    }

    /**
     * Chuyển hướng tới URL chỉ định và kết thúc xử lý.
     *
     * @param string $path Đường dẫn URL cần chuyển hướng
     * @return void
     */
    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit(0);
    }
}
