<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDOException;

/**
 * Model User dùng để quản lý tài khoản và xác thực người dùng.
 * Xử lý tạo mới, truy vấn, cập nhật và luồng duyệt tài khoản.
 */
final class User
{
    // Các cột dữ liệu dùng chung khi truy vấn người dùng
    private const BASE_USER_COLUMNS = 'id, name, email, phone, address, avatar, COALESCE(password_hash, password) AS password_hash, role, approval_status, approved_by, approved_at, reject_reason';
    private static ?array $approvalStatusEnumCache = null;

    // Các trạng thái tài khoản người dùng
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_DELETED = 'deleted';

    // Các vai trò người dùng
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_WORKER = 'worker';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ADMIN = 'admin';

    /**
     * Kiểm tra người dùng có phải là admin không.
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là admin
     */
    public static function isAdmin(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? '') === self::ROLE_ADMIN;
    }

    /**
     * Kiểm tra người dùng có phải là manager không.
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là manager
     */
    public static function isManager(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? '') === self::ROLE_MANAGER;
    }

    /**
     * Kiểm tra người dùng có phải là worker không.
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là worker
     */
    public static function isWorker(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? '') === self::ROLE_WORKER;
    }

    /**
     * Kiểm tra người dùng có phải là customer không.
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là customer
     */
    public static function isCustomer(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? '') === self::ROLE_CUSTOMER;
    }

    /**
     * Kiểm tra người dùng có một trong các vai trò được chỉ định không.
     *
     * @param array|null $user Dữ liệu người dùng
     * @param string|array $roles Vai trò(s) cần kiểm tra
     * @return bool True nếu người dùng có một trong các vai trò
     */
    public static function hasRole(?array $user, string|array $roles): bool
    {
        if ($user === null) {
            return false;
        }

        $userRole = $user['role'] ?? '';
        $rolesToCheck = is_string($roles) ? [$roles] : $roles;
        return in_array($userRole, $rolesToCheck, true);
    }

    /**
     * Kiểm tra người dùng có phải là admin hoặc manager không (các vai trò quản lý).
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là admin hoặc manager
     */
    public static function isManagerialRole(?array $user): bool
    {
        return self::isAdmin($user) || self::isManager($user);
    }

    /**
     * Kiểm tra người dùng có phải là operational staff (manager) không.
     * Manager có quyền quản lý booking, worker, customer nhưng không quản lý dịch vụ hay tài khoản admin.
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là manager
     */
    public static function canManageOperations(?array $user): bool
    {
        return self::isManager($user) || self::isAdmin($user);
    }

    /**
     * Kiểm tra người dùng có phải là super admin không.
     * Chỉ super admin mới có thể quản lý dịch vụ, thống kê doanh thu và tài khoản admin/manager.
     *
     * @param array|null $user Dữ liệu người dùng
     * @return bool True nếu là admin
     */
    public static function isSuperAdmin(?array $user): bool
    {
        return self::isAdmin($user);
    }

    /**
     * Tìm người dùng theo ID.
     *
     * @param int $id ID người dùng
     * @return array|null Dữ liệu người dùng hoặc null nếu không tìm thấy
     */
    public static function findById(int $id): ?array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT " . self::BASE_USER_COLUMNS . " FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Tìm worker khả dụng nhất cho một booking mới.
     *
     * @param string $bookingDate Ngày đặt lịch (YYYY-MM-DD)
     * @param string $bookingTime Giờ đặt lịch (HH:MM hoặc HH:MM:SS)
     * @return array|false Mảng dữ liệu worker phù hợp nhất hoặc false nếu không tìm thấy
     */
    public static function findAvailableWorker(string $bookingDate, string $bookingTime): array|false
    {
        $bookingDate = trim($bookingDate);
        $bookingTime = trim($bookingTime);

        if ($bookingDate === '' || $bookingTime === '') {
            return false;
        }

        $sql = "
            SELECT
                u.id,
                u.name,
                u.email,
                u.phone,
                u.address,
                u.role,
                u.approval_status,
                (
                    SELECT COUNT(DISTINCT b_count.id)
                    FROM bookings b_count
                    LEFT JOIN booking_details bd_count ON bd_count.booking_id = b_count.id
                    WHERE COALESCE(b_count.assigned_worker_id, bd_count.assigned_worker_id) = u.id
                      AND b_count.`date` = :count_booking_date
                      AND LOWER(COALESCE(b_count.status, '')) NOT IN ('cancelled', 'rejected')
                ) AS job_count
            FROM users u
            WHERE LOWER(u.role) = :worker_role
              AND LOWER(u.approval_status) = :active_status
              AND NOT EXISTS (
                  SELECT 1
                  FROM bookings b_busy
                  LEFT JOIN booking_details bd_busy ON bd_busy.booking_id = b_busy.id
                  WHERE COALESCE(b_busy.assigned_worker_id, bd_busy.assigned_worker_id) = u.id
                    AND b_busy.`date` = :busy_booking_date
                    AND LOWER(COALESCE(b_busy.status, '')) NOT IN ('cancelled', 'rejected')
                    AND ABS(
                        TIMESTAMPDIFF(
                            MINUTE,
                            CONCAT(b_busy.`date`, ' ', b_busy.`time`),
                            CONCAT(:busy_booking_date_time, ' ', :busy_booking_time)
                        )
                    ) < :min_gap_minutes
              )
            ORDER BY job_count ASC, u.id ASC
            LIMIT 1
        ";

        try {
            $stmt = DB::pdo()->prepare($sql);

            $workerRole = strtolower(self::ROLE_WORKER);
            $activeStatus = strtolower(self::STATUS_ACTIVE);
            $countBookingDate = $bookingDate;
            $busyBookingDate = $bookingDate;
            $busyBookingDateTime = $bookingDate;
            $busyBookingTime = $bookingTime;
            $minGapMinutes = 150;

            $stmt->bindParam(':worker_role', $workerRole, \PDO::PARAM_STR);
            $stmt->bindParam(':active_status', $activeStatus, \PDO::PARAM_STR);
            $stmt->bindParam(':count_booking_date', $countBookingDate, \PDO::PARAM_STR);
            $stmt->bindParam(':busy_booking_date', $busyBookingDate, \PDO::PARAM_STR);
            $stmt->bindParam(':busy_booking_date_time', $busyBookingDateTime, \PDO::PARAM_STR);
            $stmt->bindParam(':busy_booking_time', $busyBookingTime, \PDO::PARAM_STR);
            $stmt->bindParam(':min_gap_minutes', $minGapMinutes, \PDO::PARAM_INT);

            $stmt->execute();

            $worker = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $worker ?: false;
        } catch (PDOException $exception) {
            error_log('User::findAvailableWorker error: ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Tìm người dùng theo địa chỉ email.
     *
     * @param string $email Email cần tìm
     * @return array|null Dữ liệu người dùng hoặc null nếu không tìm thấy
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT " . self::BASE_USER_COLUMNS . " FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Tạo tài khoản người dùng mới.
     *
     * @param string $name Tên hiển thị
     * @param string $email Địa chỉ email
     * @param string $passwordHash Mật khẩu đã băm
     * @param string $role Vai trò (customer, worker, admin)
     * @param string $approvalStatus Trạng thái duyệt ban đầu
     * @return int ID người dùng vừa tạo
     */
    public static function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role = self::ROLE_CUSTOMER,
        string $approvalStatus = self::STATUS_ACTIVE,
        ?string $phone = null,
        ?string $address = null
    ): int {
        $resolvedApprovalStatus = self::resolveApprovalStatusForDatabase($approvalStatus);

        $payload = [
            'name' => $name,
            'email' => $email,
            'hash' => $passwordHash,
            'role' => $role,
            'approval_status' => $resolvedApprovalStatus,
            'phone' => $phone,
            'address' => $address,
        ];

        try {
            $stmt = DB::pdo()->prepare(
                "INSERT INTO users (name, email, phone, address, password_hash, password, role, approval_status)
                 VALUES (:name, :email, :phone, :address, :hash, :hash, :role, :approval_status)"
            );
            $stmt->execute($payload);
        } catch (PDOException $exception) {
            try {
                $stmt = DB::pdo()->prepare(
                    "INSERT INTO users (name, email, phone, address, password_hash, role, approval_status)
                     VALUES (:name, :email, :phone, :address, :hash, :role, :approval_status)"
                );
                $stmt->execute($payload);
            } catch (PDOException $secondaryException) {
                $stmt = DB::pdo()->prepare(
                    "INSERT INTO users (name, email, phone, address, password, role, approval_status)
                     VALUES (:name, :email, :phone, :address, :hash, :role, :approval_status)"
                );
                $stmt->execute($payload);
            }
        }

        return (int)DB::pdo()->lastInsertId();
    }

    /**
     * Cập nhật thông tin cơ bản của người dùng (tên, email, điện thoại, địa chỉ).
     * Đảm bảo email là duy nhất trong hệ thống.
     *
     * @param int $id ID người dùng cần cập nhật
     * @param string $name Tên hiển thị mới
     * @param string $email Email mới
     * @param string|null $phone Số điện thoại mới
     * @param string|null $address Địa chỉ mới
     * @return bool True nếu cập nhật thành công, false nếu email đã tồn tại
     */
    public static function updateInfo(
        int $id,
        string $name,
        string $email,
        ?string $phone,
        ?string $address
    ): bool {
        // Kiểm tra email có đang thuộc về người dùng khác hay không
        $stmt = DB::pdo()->prepare("SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1");
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = DB::pdo()->prepare(
            "UPDATE users SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id"
        );
        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'id' => $id,
        ]);
    }

    /**
     * Cập nhật toàn bộ trường có thể chỉnh sửa từ trang quản trị.
     *
     * @param int $id ID người dùng
     * @param string $name Tên người dùng
     * @param string $email Email người dùng
     * @param string|null $phone Số điện thoại
     * @param string|null $address Địa chỉ
     * @param string $role Vai trò
     * @param string $approvalStatus Trạng thái tài khoản
     * @param string|null $rejectReason Lý do từ chối/khóa/xóa
     * @return bool True nếu cập nhật thành công, false nếu email đã tồn tại
     */
    public static function updateAdminEditable(
        int $id,
        string $name,
        string $email,
        ?string $phone,
        ?string $address,
        string $role,
        string $approvalStatus,
        ?string $rejectReason
    ): bool {
        $stmt = DB::pdo()->prepare("SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1");
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetch()) {
            return false;
        }

        $resolvedApprovalStatus = self::resolveApprovalStatusForDatabase($approvalStatus);

        $stmt = DB::pdo()->prepare(
            "UPDATE users
             SET name = :name,
                 email = :email,
                 phone = :phone,
                 address = :address,
                 role = :role,
                 approval_status = :approval_status,
                 reject_reason = :reject_reason
             WHERE id = :id"
        );

        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'role' => $role,
            'approval_status' => $resolvedApprovalStatus,
            'reject_reason' => $rejectReason,
            'id' => $id,
        ]);
    }

    /**
     * Cập nhật mật khẩu đã băm của người dùng.
     *
     * @param int $id ID người dùng
     * @param string $passwordHash Mật khẩu băm mới
     * @return bool True nếu cập nhật thành công
     */
    public static function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = DB::pdo()->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        return $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    /**
     * Cập nhật đường dẫn ảnh đại diện của người dùng.
     *
     * @param int $id ID người dùng
     * @param string|null $avatarPath Đường dẫn avatar mới hoặc null để xóa
     * @return bool True nếu cập nhật thành công
     */
    public static function updateAvatar(int $id, ?string $avatarPath): bool
    {
        $stmt = DB::pdo()->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
        return $stmt->execute(['avatar' => $avatarPath, 'id' => $id]);
    }

    /**
     * Lấy danh sách toàn bộ người dùng (thông tin cơ bản) cho trang quản trị.
     *
     * @return array Danh sách người dùng [id, name, email, phone, address, role, approval_status, created_at]
     */
    public static function listAll(): array
    {
        $stmt = DB::pdo()->query(
            "SELECT id, name, email, phone, address, role, approval_status, created_at FROM users ORDER BY id DESC"
        );
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Lấy toàn bộ người dùng với thông tin đầy đủ (cho trang quản trị).
     *
     * @return array Danh sách người dùng với dữ liệu đầy đủ
     */
    public static function getAllUsers(): array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT id, name, email, phone, address, role, approval_status, reject_reason, created_at FROM users ORDER BY created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Lấy danh sách nhân viên đang chờ duyệt.
     *
     * @return array Danh sách yêu cầu đăng ký worker đang chờ duyệt
     */
    public static function listPendingWorkers(): array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT " . self::BASE_USER_COLUMNS . " FROM users 
             WHERE role = :role AND approval_status = :status 
             ORDER BY id DESC"
        );
        $stmt->execute([
            'role' => self::ROLE_WORKER,
            'status' => self::STATUS_PENDING,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Duyệt tài khoản worker đang chờ xử lý.
     *
     * @param int $userId ID người dùng worker
     * @param int $adminId ID quản trị viên thực hiện duyệt
     * @return bool True nếu duyệt thành công
     */
    public static function approveWorker(int $userId, int $adminId): bool
    {
        $stmt = DB::pdo()->prepare(
            "UPDATE users 
             SET approval_status = :active_status, approved_by = :adminId, approved_at = NOW(), reject_reason = NULL 
             WHERE id = :id AND role = :role AND approval_status = :pending_status"
        );
        return $stmt->execute([
            'active_status' => self::STATUS_ACTIVE,
            'adminId' => $adminId,
            'id' => $userId,
            'role' => self::ROLE_WORKER,
            'pending_status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Từ chối tài khoản worker đang chờ xử lý.
     *
     * @param int $userId ID người dùng worker
     * @param int $adminId ID quản trị viên thực hiện từ chối
     * @param string|null $reason Lý do từ chối (tùy chọn)
     * @return bool True nếu từ chối thành công
     */
    public static function rejectWorker(int $userId, int $adminId, ?string $reason): bool
    {
        $stmt = DB::pdo()->prepare(
            "UPDATE users 
             SET approval_status = :rejected_status, approved_by = :adminId, approved_at = NOW(), reject_reason = :reason 
             WHERE id = :id AND role = :role AND approval_status = :pending_status"
        );
        return $stmt->execute([
            'rejected_status' => self::STATUS_REJECTED,
            'adminId' => $adminId,
            'reason' => $reason,
            'id' => $userId,
            'role' => self::ROLE_WORKER,
            'pending_status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Thiết lập trạng thái duyệt cho người dùng.
     *
     * @param int $userId ID người dùng
     * @param string $status Trạng thái duyệt mới
     * @return bool True nếu cập nhật thành công
     */
    public static function setStatus(int $userId, string $status): bool
    {
        $status = self::resolveApprovalStatusForDatabase($status);
        $stmt = DB::pdo()->prepare("UPDATE users SET approval_status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $userId]);
    }

    /**
     * Thiết lập trạng thái duyệt và lý do cho người dùng.
     *
     * @param int $userId ID người dùng
     * @param string $status Trạng thái duyệt mới
     * @param string|null $reason Lý do thay đổi trạng thái (tùy chọn)
     * @return bool True nếu cập nhật thành công
     */
    public static function setStatusAndReason(int $userId, string $status, ?string $reason): bool
    {
        $status = self::resolveApprovalStatusForDatabase($status);
        $stmt = DB::pdo()->prepare(
            "UPDATE users SET approval_status = :status, reject_reason = :reason WHERE id = :id"
        );
        return $stmt->execute(['status' => $status, 'reason' => $reason, 'id' => $userId]);
    }

    /**
     * Khóa tài khoản người dùng với lý do tùy chọn.
     *
     * @param int $id ID người dùng
     * @param string $reason Lý do khóa tài khoản
     * @return bool True nếu khóa thành công
     */
    public static function lockUser(int $id, string $reason): bool
    {
        return self::setStatusAndReason($id, self::STATUS_LOCKED, $reason);
    }

    /**
     * Mở khóa tài khoản người dùng.
     *
     * @param int $id ID người dùng
     * @return bool True nếu mở khóa thành công
     */
    public static function unlockUser(int $id): bool
    {
        return self::setStatusAndReason($id, self::STATUS_ACTIVE, null);
    }

    /**
     * Xóa người dùng khỏi cơ sở dữ liệu.
     *
     * @param int $userId ID người dùng cần xóa
     * @return bool True nếu xóa thành công
     */
    public static function delete(int $userId): bool
    {
        $stmt = DB::pdo()->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $userId]);
    }

    /**
     * Cập nhật đầy đủ hồ sơ người dùng.
     * Đây là hàm tiện ích để cập nhật đồng thời nhiều trường.
     *
     * @param int $id ID người dùng
     * @param string $name Tên hiển thị
     * @param string $email Địa chỉ email
     * @param string $role Vai trò người dùng
     * @param string $approvalStatus Trạng thái duyệt
     * @return bool True nếu cập nhật thành công
     */
    public static function updateUser(
        int $id,
        string $name,
        string $email,
        string $role,
        string $approvalStatus
    ): bool {
        $approvalStatus = self::resolveApprovalStatusForDatabase($approvalStatus);

        $stmt = DB::pdo()->prepare(
            "UPDATE users SET name = :name, email = :email, role = :role, approval_status = :status WHERE id = :id"
        );
        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $approvalStatus,
            'id' => $id,
        ]);
    }

    /**
     * Chuyển trạng thái ứng dụng sang trạng thái hợp lệ theo enum hiện có trong DB.
     * Giúp tương thích với schema cũ chưa có các trạng thái mở rộng.
     */
    private static function resolveApprovalStatusForDatabase(string $status): string
    {
        $supported = self::getSupportedApprovalStatuses();
        if (in_array($status, $supported, true)) {
            return $status;
        }

        $fallbacks = [
            self::STATUS_LOCKED => self::STATUS_REJECTED,
            self::STATUS_DELETED => self::STATUS_REJECTED,
            self::STATUS_ACTIVE => self::STATUS_PENDING,
        ];

        $candidate = $fallbacks[$status] ?? self::STATUS_PENDING;
        if (in_array($candidate, $supported, true)) {
            return $candidate;
        }

        return $supported[0] ?? self::STATUS_PENDING;
    }

    /**
     * Lấy danh sách giá trị enum cột users.approval_status từ DB.
     */
    private static function getSupportedApprovalStatuses(): array
    {
        if (self::$approvalStatusEnumCache !== null) {
            return self::$approvalStatusEnumCache;
        }

        try {
            $stmt = DB::pdo()->query("SHOW COLUMNS FROM users LIKE 'approval_status'");
            $column = $stmt->fetch();
            $type = (string)($column['Type'] ?? '');

            if (preg_match('/^enum\\((.*)\\)$/i', $type, $matches) === 1) {
                $values = str_getcsv($matches[1], ',', "'");
                self::$approvalStatusEnumCache = array_values(array_filter(array_map('trim', $values)));
            } else {
                self::$approvalStatusEnumCache = [
                    self::STATUS_ACTIVE,
                    self::STATUS_PENDING,
                    self::STATUS_REJECTED,
                    self::STATUS_LOCKED,
                    self::STATUS_DELETED,
                ];
            }
        } catch (PDOException $exception) {
            self::$approvalStatusEnumCache = [
                self::STATUS_ACTIVE,
                self::STATUS_PENDING,
                self::STATUS_REJECTED,
                self::STATUS_LOCKED,
                self::STATUS_DELETED,
            ];
        }

        return self::$approvalStatusEnumCache;
    }
}
