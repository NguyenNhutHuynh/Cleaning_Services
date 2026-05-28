<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\UploadHelper;
use App\Core\View;
use App\Models\User;

/**
 * AccountController xử lý các nghiệp vụ quản lý tài khoản người dùng.
 * Bao gồm xem/sửa hồ sơ, quản lý ảnh đại diện và đổi mật khẩu.
 * Tất cả thao tác đều yêu cầu người dùng đã đăng nhập.
 */
final class AccountController
{
    /**
     * Hiển thị thông tin hồ sơ của người dùng.
     * Yêu cầu người dùng đã đăng nhập.
     *
     * @return void
     */
    public function profile(): void
    {
        $this->requireAuthentication();
        $uid = Auth::id();
        $user = User::findById((int)$uid);
        View::render('account/profile', ['user' => $user]);
    }

    /**
     * Hiển thị biểu mẫu chỉnh sửa hồ sơ.
     * Yêu cầu người dùng đã đăng nhập.
     *
     * @return void
     */
    public function edit(): void
    {
        $this->requireAuthentication();
        $uid = Auth::id();
        $user = User::findById((int)$uid);
        View::render('account/edit', ['user' => $user, 'csrf' => Csrf::token()]);
    }

    /**
     * Xử lý cập nhật hồ sơ với kiểm tra dữ liệu đầu vào.
     * Thực hiện cập nhật thông tin và tải ảnh đại diện (nếu có).
     * Yêu cầu request POST và xác thực CSRF token.
     *
     * @return void
     */
    public function update(): void
    {
        $this->requireAuthentication();


        $uid = Auth::id();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));

        $validationError = $this->validateUpdateInput($name, $email, $phone);
        if ($validationError) {
            $this->setSessionError($validationError);
            $this->redirect('/account/edit');
        }

        $ok = User::updateInfo((int)$uid, $name, $email, $phone !== '' ? $phone : null, $address !== '' ? $address : null);
        $avatarChanged = false;

        if (
            isset($_FILES['avatar'])
            && is_array($_FILES['avatar'])
            && (($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)
        ) {
            $avatarError = $this->handleAvatarUpload((int)$uid);
            if ($avatarError) {
                $this->setSessionError($avatarError);
                $this->redirect('/account/edit');
            }
            $avatarChanged = true;
        }

        if ($ok || $avatarChanged) {
            $this->setSessionSuccess('Cập nhật thông tin thành công.');
        } else {
            $this->setSessionError('Không có thay đổi hoặc cập nhật thất bại.');
        }
        $this->redirect('/account');
    }

    /**
     * Hiển thị biểu mẫu đổi mật khẩu.
     * Yêu cầu người dùng đã đăng nhập.
     *
     * @return void
     */
    public function changePassword(): void
    {
        $this->requireAuthentication();
        $uid = Auth::id();
        $user = User::findById((int)$uid);
        View::render('account/change-password', ['user' => $user, 'csrf' => Csrf::token()]);
    }

    /**
     * Xử lý yêu cầu cập nhật mật khẩu.
     * Kiểm tra mật khẩu hiện tại, điều kiện mật khẩu mới,
     * và xác nhận mật khẩu nhập lại khớp nhau.
     * Yêu cầu request POST và xác thực CSRF token.
     *
     * @return void
     */
    public function updatePassword(): void
    {
        $this->requireAuthentication();


        $uid = Auth::id();
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $validationError = $this->validatePasswordChange($uid, $current, $new, $confirm);
        if ($validationError) {
            $this->setSessionError($validationError);
            $this->redirect('/account/change-password');
        }

        $pwdChanged = User::updatePassword((int)$uid, password_hash($new, PASSWORD_DEFAULT));

        if ($pwdChanged) {
            $this->setSessionSuccess('Đổi mật khẩu thành công.');
        } else {
            $this->setSessionError('Đổi mật khẩu thất bại.');
        }
        $this->redirect('/account');
    }

    /**
     * Bắt buộc người dùng phải được xác thực.
     * Chuyển hướng về trang đăng nhập nếu chưa xác thực.
     *
     * @return void
     */
    private function requireAuthentication(): void
    {
        if (!Auth::isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    /**
     * Xác thực CSRF token từ request POST.
     * Trả về lỗi 419 nếu token không hợp lệ hoặc bị thiếu.
     *
     * @return void
     */
    private function verifyCsrfToken(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'CSRF token mismatch';
            exit(1);
        }
    }

    /**
     * Kiểm tra dữ liệu cập nhật hồ sơ người dùng.
     * Xác minh tên/email bắt buộc, định dạng email,
     * và định dạng số điện thoại nếu có nhập.
     *
     * @param string $name Họ tên người dùng
     * @param string $email Địa chỉ email
     * @param string $phone Số điện thoại (tùy chọn)
     * @return string|null Thông báo lỗi nếu kiểm tra thất bại, ngược lại là null
     */
    private function validateUpdateInput(string $name, string $email, string $phone): ?string
    {
        if ($name === '' || $email === '') {
            return 'Vui lòng nhập đầy đủ họ tên và email.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email không hợp lệ.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
            return 'Số điện thoại không hợp lệ.';
        }
        return null;
    }

    /**
     * Kiểm tra dữ liệu đổi mật khẩu.
     * Đảm bảo đủ trường nhập, mật khẩu hiện tại đúng,
     * mật khẩu mới đạt độ dài tối thiểu và xác nhận mật khẩu trùng khớp.
     *
     * @param int $uid ID người dùng
     * @param string $current Mật khẩu hiện tại nhập vào
     * @param string $new Mật khẩu mới nhập vào
     * @param string $confirm Xác nhận mật khẩu mới
     * @return string|null Thông báo lỗi nếu kiểm tra thất bại, ngược lại là null
     */
    private function validatePasswordChange(int $uid, string $current, string $new, string $confirm): ?string
    {
        if ($current === '' || $new === '' || $confirm === '') {
            return 'Vui lòng nhập đầy đủ thông tin mật khẩu.';
        }

        $user = User::findById($uid);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            return 'Mật khẩu hiện tại không đúng.';
        }

        if (strlen($new) < 6) {
            return 'Mật khẩu mới tối thiểu 6 ký tự.';
        }

        if ($new !== $confirm) {
            return 'Xác nhận mật khẩu không khớp.';
        }

        return null;
    }

    /**
     * Xử lý tải lên ảnh đại diện từ request POST.
    * Kiểm tra kích thước file (tối đa 2MB), định dạng (jpg/png/webp),
     * và lưu vào thư mục upload.
     *
     * @param int $uid ID người dùng dùng để tạo tên file avatar
     * @return string|null Thông báo lỗi nếu tải lên thất bại, null nếu thành công
     */
    private function handleAvatarUpload(int $uid): ?string
    {
        if (($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $result = UploadHelper::uploadImage(
            (array)($_FILES['avatar'] ?? []),
            dirname(__DIR__, 2) . '/public/uploads/avatars'
        );

        if (!($result['success'] ?? false)) {
            return (string)($result['error'] ?? 'Không thể lưu ảnh đại diện.');
        }

        User::updateAvatar($uid, '/' . ltrim((string)$result['path'], '/'));

        return null;
    }

    /**
     * Lấy và kiểm tra phần mở rộng ảnh từ tên file và MIME type.
     * Dò định dạng bằng getimagesize khi đuôi file không đáng tin cậy.
     *
     * @param string $filename Tên file gốc khi tải lên
     * @param string $tmpPath Đường dẫn file tạm để dò MIME
      * @return string|null Đuôi file hợp lệ (jpg, png, webp) hoặc null nếu không hợp lệ
     */
    private function getValidatedImageExtension(string $filename, string $tmpPath): ?string
    {
          $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowedExt, true)) {
            return $ext;
        }

        $imgInfo = @getimagesize($tmpPath);
        $mime = is_array($imgInfo) && isset($imgInfo['mime']) ? strtolower($imgInfo['mime']) : '';
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        return $mimeMap[$mime] ?? null;
    }

    /**
     * Gán thông báo lỗi vào session.
     *
     * @param string $message Nội dung lỗi cần hiển thị
     * @return void
     */
    private function setSessionError(string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['error'] = $message;
    }

    /**
     * Gán thông báo thành công vào session.
     *
     * @param string $message Nội dung thành công cần hiển thị
     * @return void
     */
    private function setSessionSuccess(string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['success'] = $message;
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
