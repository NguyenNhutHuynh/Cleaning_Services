<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDOException;

/**
 * Model Booking dùng để quản lý các lịch đặt dịch vụ.
 * Xử lý tạo mới, truy vấn và cập nhật trạng thái đơn đặt của khách hàng.
 * 
 * Ghi chú: Từ schema mới, chi tiết booking được lưu trong bảng booking_details.
 * Các aliases được dùng để giữ tương thích với code cũ (status, date, time, description).
 */
final class Booking
{
    private static ?array $statusEnumCache = null;

    // Các trạng thái của đơn đặt lịch
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Tạo một đơn đặt lịch mới trong cơ sở dữ liệu.
     * Tạo record trong bookings (metadata) và booking_details (chi tiết).
     *
     * @param int $userId ID người dùng (khách hàng)
     * @param int $serviceId ID dịch vụ được đặt
     * @param string $date Ngày đặt lịch (YYYY-MM-DD)
     * @param string $time Giờ đặt lịch (HH:MM)
     * @param string $location Địa điểm thực hiện dịch vụ
     * @param string|null $description Ghi chú bổ sung (tùy chọn)
     * @return int ID của đơn đặt vừa tạo
     */
    public static function create(
        int $userId,
        int $serviceId,
        string $date,
        string $time,
        string $location,
        ?string $description,
        float $quantity = 1.0,
        ?string $measureUnit = null,
        ?float $unitPrice = null,
        ?float $lineTotal = null
    ): int {
        // Insert into bookings using the current table schema.
        $user = User::findById($userId);
        $service = Service::getById($serviceId);
        $servicePriceSnapshot = $unitPrice ?? (float)($service['price'] ?? 0);

        $stmtBooking = DB::pdo()->prepare(
            "INSERT INTO bookings (
                user_id, service_id, date, time, location, description,
                customer_name_snapshot, customer_phone_snapshot,
                service_name_snapshot, service_price_snapshot,
                quantity, measure_unit, unit_price, line_total,
                status, payment_status, created_at, updated_at
            ) VALUES (
                :user_id, :service_id, :date, :time, :location, :description,
                :customer_name_snapshot, :customer_phone_snapshot,
                :service_name_snapshot, :service_price_snapshot,
                :quantity, :measure_unit, :unit_price, :line_total,
                :status, :payment_status, NOW(), NOW()
            )"
        );
        $stmtBooking->execute([
            'user_id' => $userId,
            'service_id' => $serviceId,
            'date' => $date,
            'time' => $time,
            'location' => $location,
            'description' => $description,
            'customer_name_snapshot' => $user['name'] ?? null,
            'customer_phone_snapshot' => $user['phone'] ?? null,
                'service_name_snapshot' => $service['name'] ?? null,
                'service_price_snapshot' => $servicePriceSnapshot,
            'quantity' => $quantity,
            'measure_unit' => $measureUnit ?? '',
            'unit_price' => $unitPrice ?? 0,
            'line_total' => $lineTotal ?? 0,
            'status' => self::STATUS_PENDING,
            'payment_status' => 'pending',
        ]);
        $bookingId = (int)DB::pdo()->lastInsertId();

        // Insert into booking_details (full details)
        $stmtDetail = DB::pdo()->prepare(
            "INSERT INTO booking_details (
                booking_id, service_id, work_date, work_time, location,
                quantity, measure_unit, unit_price, line_total,
                detail_status, note, created_at, updated_at
            ) VALUES (
                :booking_id, :service_id, :work_date, :work_time, :location,
                :quantity, :measure_unit, :unit_price, :line_total,
                :detail_status, :note, NOW(), NOW()
            )"
        );
        $stmtDetail->execute([
            'booking_id' => $bookingId,
            'service_id' => $serviceId,
            'location' => $location,
            'work_date' => $date,
            'work_time' => $time,
            'quantity' => $quantity,
            'measure_unit' => $measureUnit ?? '',
            'unit_price' => $unitPrice ?? 0,
            'line_total' => $lineTotal ?? 0,
            'detail_status' => 'pending',
            'note' => $description,
        ]);

        return $bookingId;
    }

    /**
     * Lấy toàn bộ đơn đặt kèm thông tin người dùng, dịch vụ và chi tiết từ booking_details.
     * Fallback để xử lý dữ liệu cũ không có booking_details.
     *
     * @return array Danh sách toàn bộ đơn đặt và thông tin chi tiết
     */
    public static function getAll(): array
    {
        $sql = "SELECT
                    b.id,
                    b.user_id,
                    COALESCE(b.assigned_worker_id, bd.assigned_worker_id) AS assigned_worker_id,
                    b.assigned_at,
                    b.estimated_arrival_time,
                    b.confirmed_at,
                    b.started_at,
                    b.completed_at,
                        COALESCE(bd.location, b.location) AS location,
                    b.customer_name_snapshot,
                    b.customer_phone_snapshot,
                    b.service_name_snapshot,
                    b.service_price_snapshot,
                    b.worker_start_address,
                    b.distance_km,
                    b.eta_minutes,
                    b.payment_method,
                    b.payment_status,
                    b.paid_at,
                    b.payment_amount,
                    b.payment_ref,
                    b.created_at AS booking_created_at,
                    b.updated_at AS booking_updated_at,
                    bd.id AS booking_detail_id,
                    bd.booking_id,
                    bd.service_id,
                    bd.location,
                    bd.assigned_worker_id AS detail_assigned_worker_id,
                    bd.assigned_at AS detail_assigned_at,
                    bd.estimated_arrival_time AS detail_estimated_arrival_time,
                    bd.work_date,
                    bd.work_date AS date,
                    bd.work_time,
                    bd.work_time AS time,
                    bd.quantity,
                    bd.measure_unit,
                    bd.unit_price,
                    bd.line_total,
                    bd.detail_status AS status,
                    bd.note AS description,
                    bd.created_at AS booking_detail_created_at,
                    bd.updated_at AS booking_detail_updated_at,
                    u.name AS user_name,
                    u.name AS customer_name,
                    u.phone AS user_phone,
                    u.address AS user_address,
                    s.name AS service_name,
                    s.price AS service_price,
                    s.unit AS service_unit,
                    w.name AS worker_name,
                    w.phone AS worker_phone
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                LEFT JOIN booking_details bd ON bd.booking_id = b.id
                LEFT JOIN services s ON s.id = bd.service_id
                LEFT JOIN users w ON w.id = bd.assigned_worker_id
                ORDER BY COALESCE(bd.work_date, DATE(b.created_at)) DESC, COALESCE(bd.work_time, TIME(b.created_at)) DESC, b.id DESC";
        $stmt = DB::pdo()->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Tìm đơn đặt theo ID (lấy từ bookings và booking_details).
     * Fallback để xử lý dữ liệu cũ không có booking_details.
     *
     * @param int $id ID đơn đặt
     * @return array|null Dữ liệu đơn đặt hoặc null nếu không tìm thấy
     */
    public static function getById(int $id): ?array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT
                b.id,
                b.user_id,
                COALESCE(b.assigned_worker_id, bd.assigned_worker_id) AS assigned_worker_id,
                b.assigned_at,
                b.estimated_arrival_time,
                b.confirmed_at,
                b.started_at,
                b.completed_at,
                COALESCE(bd.location, b.location) AS location,
                b.customer_name_snapshot,
                b.customer_phone_snapshot,
                b.service_name_snapshot,
                b.service_price_snapshot,
                b.worker_start_address,
                b.distance_km,
                b.eta_minutes,
                b.payment_method,
                b.payment_status,
                b.paid_at,
                b.payment_amount,
                b.payment_ref,
                b.created_at AS booking_created_at,
                b.updated_at AS booking_updated_at,
                bd.id AS booking_detail_id,
                bd.booking_id,
                bd.service_id,
                bd.location,
                bd.work_date,
                bd.work_date AS date,
                bd.work_time,
                bd.work_time AS time,
                bd.quantity,
                bd.measure_unit,
                bd.unit_price,
                bd.line_total,
                bd.detail_status AS status,
                bd.note AS description,
                bd.created_at AS booking_detail_created_at,
                bd.updated_at AS booking_detail_updated_at
             FROM bookings b
             LEFT JOIN booking_details bd ON bd.booking_id = b.id
             WHERE b.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        
        // Return null if booking doesn't exist
        if (!$result) {
            return null;
        }
        
        return $result;
    }

    /**
     * Lấy chi tiết một đơn đặt (kèm khách hàng, worker, dịch vụ từ booking_details).
     * Nếu booking_details trống, vẫn trả về thông tin booking cơ bản.
     */
    public static function getDetailById(int $id): ?array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT
                b.id,
                b.user_id,
                COALESCE(b.assigned_worker_id, bd.assigned_worker_id) AS assigned_worker_id,
                b.assigned_at,
                b.estimated_arrival_time,
                b.confirmed_at,
                b.started_at,
                b.completed_at,
                COALESCE(bd.location, b.location) AS location,
                b.customer_name_snapshot,
                b.customer_phone_snapshot,
                b.service_name_snapshot,
                b.service_price_snapshot,
                b.worker_start_address,
                b.distance_km,
                b.eta_minutes,
                b.payment_method,
                b.payment_status,
                b.paid_at,
                b.payment_amount,
                b.payment_ref,
                b.created_at AS booking_created_at,
                b.updated_at AS booking_updated_at,
                bd.id AS booking_detail_id,
                bd.booking_id,
                bd.service_id,
                bd.location,
                bd.assigned_worker_id AS detail_assigned_worker_id,
                bd.assigned_at AS detail_assigned_at,
                bd.estimated_arrival_time AS detail_estimated_arrival_time,
                bd.work_date,
                bd.work_date AS date,
                bd.work_time,
                bd.work_time AS time,
                bd.quantity,
                bd.measure_unit,
                bd.unit_price,
                bd.line_total,
                bd.detail_status AS status,
                bd.note AS description,
                bd.created_at AS booking_detail_created_at,
                bd.updated_at AS booking_detail_updated_at,
                u.name AS user_name,
                u.email AS customer_email,
                u.phone AS user_phone,
                u.phone AS customer_phone,
                u.address AS user_address,
                u.address AS customer_address,
                u.name AS customer_name,
                s.name AS service_name,
                s.price AS service_price,
                s.unit AS service_unit,
                w.name AS worker_name,
                w.phone AS worker_phone,
                w.address AS worker_address
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN booking_details bd ON bd.booking_id = b.id
             LEFT JOIN services s ON s.id = bd.service_id
             LEFT JOIN users w ON w.id = bd.assigned_worker_id
             WHERE b.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        
        // If no result, return null (booking doesn't exist)
        if (!$result) {
            return null;
        }
        
        return $result;
    }

    /**
     * Lấy toàn bộ đơn đặt của một người dùng cụ thể (từ booking_details).
     * Fallback để xử lý dữ liệu cũ không có booking_details.
     *
     * @param int $userId ID người dùng (khách hàng)
     * @return array Danh sách đơn đặt của khách hàng
     */
    public static function getByUserId(int $userId): array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT
                b.id,
                b.user_id,
                COALESCE(b.assigned_worker_id, bd.assigned_worker_id) AS assigned_worker_id,
                b.assigned_at,
                b.estimated_arrival_time,
                b.confirmed_at,
                b.started_at,
                b.completed_at,
                    COALESCE(bd.location, b.location) AS location,
                b.customer_name_snapshot,
                b.customer_phone_snapshot,
                b.service_name_snapshot,
                b.service_price_snapshot,
                b.worker_start_address,
                b.distance_km,
                b.eta_minutes,
                b.payment_method,
                b.payment_status,
                b.paid_at,
                b.payment_amount,
                b.payment_ref,
                b.created_at AS booking_created_at,
                b.updated_at AS booking_updated_at,
                bd.id AS booking_detail_id,
                bd.booking_id,
                bd.service_id,
                bd.location,
                bd.work_date,
                bd.work_date AS date,
                bd.work_time,
                bd.work_time AS time,
                bd.quantity,
                bd.measure_unit,
                bd.unit_price,
                bd.line_total,
                bd.detail_status AS status,
                bd.note AS description,
                bd.created_at AS booking_detail_created_at,
                bd.updated_at AS booking_detail_updated_at,
                s.name AS service_name,
                s.price AS service_price,
                w.name AS worker_name
             FROM bookings b
             LEFT JOIN booking_details bd ON bd.booking_id = b.id
             LEFT JOIN services s ON s.id = bd.service_id
             LEFT JOIN users w ON w.id = bd.assigned_worker_id
             WHERE b.user_id = :uid
             ORDER BY COALESCE(bd.work_date, DATE(b.created_at)) DESC, COALESCE(bd.work_time, TIME(b.created_at)) DESC, b.id DESC"
        );
        $stmt->execute(['uid' => $userId]);
        $results = $stmt->fetchAll() ?: [];
        
        return $results;
    }

    /**
     * Lấy danh sách đơn đã gán cho worker (từ booking_details).
     * Fallback để xử lý dữ liệu cũ không có booking_details.
     */
    public static function getByWorkerId(int $workerId): array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT
                b.id,
                b.user_id,
                COALESCE(b.assigned_worker_id, bd.assigned_worker_id) AS assigned_worker_id,
                b.assigned_at,
                b.estimated_arrival_time,
                b.confirmed_at,
                b.started_at,
                b.completed_at,
                COALESCE(bd.location, b.location) AS location,
                b.customer_name_snapshot,
                b.customer_phone_snapshot,
                b.service_name_snapshot,
                b.service_price_snapshot,
                b.worker_start_address,
                b.distance_km,
                b.eta_minutes,
                b.payment_method,
                b.payment_status,
                b.paid_at,
                b.payment_amount,
                b.payment_ref,
                b.created_at AS booking_created_at,
                b.updated_at AS booking_updated_at,
                bd.id AS booking_detail_id,
                bd.booking_id,
                bd.service_id,
                bd.location,
                bd.work_date,
                bd.work_date AS date,
                bd.work_time,
                bd.work_time AS time,
                bd.quantity,
                bd.measure_unit,
                bd.unit_price,
                bd.line_total,
                bd.detail_status AS status,
                bd.note AS description,
                bd.created_at AS booking_detail_created_at,
                bd.updated_at AS booking_detail_updated_at,
                u.name AS user_name,
                u.phone AS user_phone,
                u.name AS customer_name,
                s.name AS service_name,
                s.price AS service_price
             FROM bookings b
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN booking_details bd ON bd.booking_id = b.id
             LEFT JOIN services s ON s.id = bd.service_id
             WHERE COALESCE(bd.assigned_worker_id, b.assigned_worker_id) = :wid
             ORDER BY bd.work_date ASC, bd.work_time ASC, b.id ASC"
        );
        $stmt->execute(['wid' => $workerId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Cập nhật trạng thái của đơn đặt (cập nhật detail_status trong booking_details).
     *
     * @param int $id ID đơn đặt
     * @param string $status Trạng thái mới
     * @return bool True nếu cập nhật thành công
     */
    public static function updateStatus(int $id, string $status): bool
    {
        $safeStatus = self::resolveStatusForDatabase($status);

        $stmt = DB::pdo()->prepare(
            "UPDATE booking_details SET detail_status = :status, updated_at = NOW() WHERE booking_id = :id"
        );
        return $stmt->execute(['status' => $safeStatus, 'id' => $id]);
    }

    /**
     * Phân công nhân viên cho một đơn đặt (cập nhật booking_details).
     *
     * @param int $id ID đơn đặt
     * @param int $workerId ID nhân viên (người dùng)
     * @return bool True nếu phân công thành công
     */
    public static function assignWorker(int $id, int $workerId): bool
    {
        $pdo = DB::pdo();
        $ownsTransaction = !$pdo->inTransaction();

        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }

            $bookingId = $id;
            $workerIdValue = $workerId;
            $assignedStatus = self::STATUS_ASSIGNED;

            $stmtBooking = $pdo->prepare(
                "UPDATE bookings
                 SET assigned_worker_id = :wid,
                     status = :status,
                     assigned_at = NOW(),
                     updated_at = NOW()
                 WHERE id = :id"
            );
            $stmtBooking->bindParam(':wid', $workerIdValue, \PDO::PARAM_INT);
            $stmtBooking->bindParam(':status', $assignedStatus, \PDO::PARAM_STR);
            $stmtBooking->bindParam(':id', $bookingId, \PDO::PARAM_INT);

            if (!$stmtBooking->execute()) {
                throw new PDOException('Failed to update bookings assignment');
            }

            $detailExists = $pdo->prepare(
                "SELECT 1 FROM booking_details WHERE booking_id = :id LIMIT 1"
            );
            $detailExists->bindParam(':id', $bookingId, \PDO::PARAM_INT);
            $detailExists->execute();

            if ($detailExists->fetchColumn() !== false) {
                $stmtDetail = $pdo->prepare(
                    "UPDATE booking_details
                     SET assigned_worker_id = :wid,
                         assigned_at = NOW(),
                         detail_status = :status,
                         updated_at = NOW()
                     WHERE booking_id = :id"
                );
                $stmtDetail->bindParam(':wid', $workerIdValue, \PDO::PARAM_INT);
                $stmtDetail->bindParam(':status', $assignedStatus, \PDO::PARAM_STR);
                $stmtDetail->bindParam(':id', $bookingId, \PDO::PARAM_INT);

                if (!$stmtDetail->execute()) {
                    throw new PDOException('Failed to update booking_details assignment');
                }
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return true;
        } catch (PDOException $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Booking::assignWorker error: ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật thời gian ước tính worker sẽ đến (cập nhật booking_details).
     *
     * @param int $id ID đơn đặt
     * @param string $estimatedArrivalTime Thời gian ước tính (định dạng: YYYY-MM-DD HH:MM)
     * @return bool True nếu cập nhật thành công
     */
    public static function updateEstimatedArrivalTime(int $id, string $estimatedArrivalTime): bool
    {
        try {
            $stmt = DB::pdo()->query("SHOW COLUMNS FROM booking_details LIKE 'estimated_arrival_time'");
            if ($stmt->fetch() === false) {
                return true;
            }
        } catch (PDOException $exception) {
            return true;
        }

        $stmt = DB::pdo()->prepare(
            "UPDATE booking_details SET estimated_arrival_time = :eta, updated_at = NOW() WHERE booking_id = :id"
        );
        return $stmt->execute(['eta' => $estimatedArrivalTime, 'id' => $id]);
    }

    /**
     * Chuyển trạng thái ứng dụng sang trạng thái hợp lệ theo enum hiện có trong DB.
     * Giúp tương thích với schema cũ chưa có các trạng thái mở rộng.
     */
    private static function resolveStatusForDatabase(string $status): string
    {
        $supported = self::getSupportedStatuses();
        if (in_array($status, $supported, true)) {
            return $status;
        }

        $fallbacks = [
            self::STATUS_ASSIGNED => self::STATUS_ASSIGNED,
            self::STATUS_ACCEPTED => self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS => self::STATUS_CONFIRMED,
            'on_the_way' => self::STATUS_CONFIRMED,
            'arrived' => self::STATUS_CONFIRMED,
            'before_cleaning' => self::STATUS_CONFIRMED,
            'after_cleaning' => self::STATUS_CONFIRMED,
            'before_cleaning_photo' => self::STATUS_CONFIRMED,
            'after_cleaning_photo' => self::STATUS_CONFIRMED,
        ];

        $candidate = $fallbacks[$status] ?? self::STATUS_PENDING;
        if (in_array($candidate, $supported, true)) {
            return $candidate;
        }

        return $supported[0] ?? self::STATUS_PENDING;
    }

    /**
     * Lấy danh sách giá trị enum cột booking_details.detail_status từ DB.
     */
    private static function getSupportedStatuses(): array
    {
        if (self::$statusEnumCache !== null) {
            return self::$statusEnumCache;
        }

        try {
            $stmt = DB::pdo()->query("SHOW COLUMNS FROM booking_details LIKE 'detail_status'");
            $column = $stmt->fetch();
            $type = (string)($column['Type'] ?? '');

            if (preg_match('/^enum\\((.*)\\)$/i', $type, $matches) === 1) {
                $values = str_getcsv($matches[1], ',', "'");
                self::$statusEnumCache = array_values(array_filter(array_map('trim', $values)));
            } else {
                self::$statusEnumCache = [
                    self::STATUS_PENDING,
                    self::STATUS_ASSIGNED,
                    self::STATUS_CONFIRMED,
                    self::STATUS_COMPLETED,
                    self::STATUS_CANCELLED,
                ];
            }
        } catch (PDOException $exception) {
            self::$statusEnumCache = [
                self::STATUS_PENDING,
                self::STATUS_ASSIGNED,
                self::STATUS_CONFIRMED,
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
            ];
        }

        return self::$statusEnumCache;
    }
}
