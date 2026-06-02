<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDOException;

/**
 * Model PaymentTransaction - Quản lý giao dịch thanh toán PayOS
 * 
 * MIGRATION SQL (Chạy trước khi sử dụng):
 * 
 * CREATE TABLE IF NOT EXISTS payment_transactions (
 *     id INT PRIMARY KEY AUTO_INCREMENT,
 *     booking_id INT NOT NULL,
 *     order_code VARCHAR(255) UNIQUE NOT NULL,
 *     amount DECIMAL(10, 2) NOT NULL,
 *     status VARCHAR(50) NOT NULL DEFAULT 'pending',
 *     payment_method VARCHAR(50),
 *     transaction_id VARCHAR(255),
 *     payer_account_number VARCHAR(50),
 *     payer_name VARCHAR(255),
 *     webhook_raw_data LONGTEXT,
 *     webhook_signature VARCHAR(255),
 *     webhook_received_at DATETIME,
 *     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 *     paid_at DATETIME,
 *     updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *     KEY idx_booking_id (booking_id),
 *     KEY idx_order_code (order_code),
 *     KEY idx_status (status),
 *     CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
final class PaymentTransaction
{
    public const METHOD_CUSTOMER_PAYMENT = 'customer_payment';
    public const METHOD_WORKER_PAYOUT = 'worker_payout';

    public static function create(int $bookingId, int $orderCode, float $amount, ?string $status = null, ?string $description = null, ?string $paymentMethod = null): bool
    {
        try {
            $stmt = DB::pdo()->prepare("
                INSERT INTO payment_transactions 
                (booking_id, order_code, amount, status, payment_method, created_at)
                VALUES (:booking_id, :order_code, :amount, :status, :payment_method, NOW())
            ");
            
            return $stmt->execute([
                ':booking_id' => $bookingId,
                ':order_code' => (string)$orderCode,
                ':amount' => $amount,
                ':status' => $status ?? 'pending',
                ':payment_method' => $paymentMethod ?? self::METHOD_CUSTOMER_PAYMENT,
            ]);
        } catch (PDOException $e) {
            error_log('PaymentTransaction::create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin giao dịch theo Order Code
     */
    public static function getByOrderCode(string $orderCode): ?array
    {
        try {
            $stmt = DB::pdo()->prepare("
                SELECT * FROM payment_transactions 
                WHERE order_code = :order_code
                LIMIT 1
            ");
            
            $stmt->execute([':order_code' => $orderCode]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getByOrderCode error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy thông tin giao dịch theo ID.
     */
    public static function getById(int $id): ?array
    {
        try {
            $stmt = DB::pdo()->prepare(
                "SELECT * FROM payment_transactions WHERE id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();

            return $result ?: null;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getById error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy thông tin giao dịch theo Booking ID
     */
    public static function getByBookingId(int $bookingId): ?array
    {
        try {
            $stmt = DB::pdo()->prepare("
                SELECT * FROM payment_transactions 
                WHERE booking_id = :booking_id
                ORDER BY created_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([':booking_id' => $bookingId]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getByBookingId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy giao dịch thanh toán của khách hàng theo booking.
     */
    public static function getLatestCustomerByBookingId(int $bookingId): ?array
    {
        try {
            $stmt = DB::pdo()->prepare(
                "SELECT * FROM payment_transactions
                 WHERE booking_id = :booking_id
                   AND (
                        payment_method IS NULL
                        OR payment_method IN (:customer_method, :legacy_method)
                   )
                 ORDER BY created_at DESC
                 LIMIT 1"
            );

            $stmt->execute([
                ':booking_id' => $bookingId,
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getLatestCustomerByBookingId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy giao dịch khách thanh toán đã paid mới nhất theo booking.
     */
    public static function getLatestPaidCustomerByBookingId(int $bookingId): ?array
    {
        try {
            $stmt = DB::pdo()->prepare(
                "SELECT * FROM payment_transactions
                 WHERE booking_id = :booking_id
                   AND status = 'paid'
                   AND (
                        payment_method IS NULL
                        OR payment_method IN (:customer_method, :legacy_method)
                   )
                 ORDER BY paid_at DESC, created_at DESC
                 LIMIT 1"
            );

            $stmt->execute([
                ':booking_id' => $bookingId,
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getLatestPaidCustomerByBookingId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy giao dịch khách thanh toán gần nhất còn pending theo user.
     */
    public static function getLatestCustomerPendingByUserId(int $userId): ?array
    {
        try {
            $stmt = DB::pdo()->prepare(
                "SELECT pt.*
                 FROM payment_transactions pt
                 JOIN bookings b ON b.id = pt.booking_id
                 WHERE b.user_id = :user_id
                   AND (pt.payment_method IS NULL OR pt.payment_method IN (:customer_method, :legacy_method))
                   AND pt.status = 'pending'
                 ORDER BY pt.created_at DESC
                 LIMIT 1"
            );

            $stmt->execute([
                ':user_id' => $userId,
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getLatestCustomerPendingByUserId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kiểm tra booking đã có giao dịch khách thanh toán thành công chưa.
     */
    public static function hasSuccessfulCustomerPayment(int $bookingId): bool
    {
        try {
            // First, try to find payment with booking_id
            $stmt = DB::pdo()->prepare(
                "SELECT COUNT(*) AS total
                 FROM payment_transactions
                 WHERE booking_id = :booking_id
                   AND status = 'paid'
                   AND (
                        payment_method IS NULL
                        OR payment_method IN (:customer_method, :legacy_method)
                   )"
            );

            $stmt->execute([
                ':booking_id' => $bookingId,
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $result = $stmt->fetch();
            if ((int)($result['total'] ?? 0) > 0) {
                return true;
            }

            // Fallback: If no payment found by booking_id, try to find by user_id
            // This handles legacy data where payment_transactions might not have booking_id
            $booking = \App\Models\Booking::getById($bookingId);
            if ($booking === null) {
                return false;
            }

            $userId = (int)($booking['user_id'] ?? 0);
            if ($userId <= 0) {
                return false;
            }

            // Check if user has ANY paid payment (fallback for old data)
            $stmtFallback = DB::pdo()->prepare(
                "SELECT COUNT(*) AS total
                 FROM payment_transactions pt
                 JOIN bookings b ON b.id = pt.booking_id OR (pt.booking_id IS NULL AND b.user_id = :user_id)
                 WHERE b.id = :booking_id
                   AND pt.status = 'paid'
                   AND (
                        pt.payment_method IS NULL
                        OR pt.payment_method IN (:customer_method, :legacy_method)
                   )"
            );

            $stmtFallback->execute([
                ':booking_id' => $bookingId,
                ':user_id' => $userId,
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $resultFallback = $stmtFallback->fetch();
            return (int)($resultFallback['total'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log('PaymentTransaction::hasSuccessfulCustomerPayment error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tổng tiền khách đã thanh toán thành công.
     */
    public static function getTotalPaidCustomerRevenue(): float
    {
        try {
            $stmt = DB::pdo()->prepare(
                "SELECT COALESCE(SUM(amount), 0) AS total
                 FROM payment_transactions
                 WHERE status = 'paid'
                   AND (
                        payment_method IS NULL
                        OR payment_method IN (:customer_method, :legacy_method)
                   )"
            );

            $stmt->execute([
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $result = $stmt->fetch();
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getTotalPaidCustomerRevenue error: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Doanh thu khách thanh toán theo từng tháng gần nhất.
     */
    public static function getPaidCustomerRevenueByMonth(int $monthCount = 6): array
    {
        $monthCount = max(1, $monthCount);
        $monthlyData = [];

        for ($i = $monthCount - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthlyData[$month] = 0.0;
        }

        try {
            $stmt = DB::pdo()->prepare(
                "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month_key, COALESCE(SUM(amount), 0) AS month_total
                 FROM payment_transactions
                 WHERE status = 'paid'
                   AND paid_at IS NOT NULL
                   AND (
                        payment_method IS NULL
                        OR payment_method IN (:customer_method, :legacy_method)
                   )
                 GROUP BY month_key"
            );

            $stmt->execute([
                ':customer_method' => self::METHOD_CUSTOMER_PAYMENT,
                ':legacy_method' => 'bank_transfer',
            ]);

            $rows = $stmt->fetchAll() ?: [];
            foreach ($rows as $row) {
                $monthKey = (string)($row['month_key'] ?? '');
                if (isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = (float)($row['month_total'] ?? 0);
                }
            }
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getPaidCustomerRevenueByMonth error: ' . $e->getMessage());
        }

        return $monthlyData;
    }

    /**
     * Cập nhật trạng thái thanh toán
     * 
     * @param string $orderCode Mã đơn hàng
     * @param string $status Trạng thái mới: paid|failed|cancelled
     * @param array $data Dữ liệu bổ sung (transaction_id, payer_account_number, payer_name, webhook_raw_data, webhook_signature, webhook_received_at)
     */
    public static function updateStatus(string $orderCode, string $status, array $data = []): bool
    {
        try {
            $updates = ['status = :status'];
            $params = [':status' => $status, ':order_code' => $orderCode];
            
            if (isset($data['transaction_id'])) {
                $updates[] = 'transaction_id = :transaction_id';
                $params[':transaction_id'] = $data['transaction_id'];
            }
            
            if (isset($data['payer_account_number'])) {
                $updates[] = 'payer_account_number = :payer_account_number';
                $params[':payer_account_number'] = $data['payer_account_number'];
            }
            
            if (isset($data['payer_name'])) {
                $updates[] = 'payer_name = :payer_name';
                $params[':payer_name'] = $data['payer_name'];
            }
            
            if (isset($data['webhook_raw_data'])) {
                $updates[] = 'webhook_raw_data = :webhook_raw_data';
                $params[':webhook_raw_data'] = $data['webhook_raw_data'];
            }
            
            if (isset($data['webhook_signature'])) {
                $updates[] = 'webhook_signature = :webhook_signature';
                $params[':webhook_signature'] = $data['webhook_signature'];
            }
            
            if (isset($data['webhook_received_at'])) {
                $updates[] = 'webhook_received_at = :webhook_received_at';
                $params[':webhook_received_at'] = $data['webhook_received_at'];
            }
            
            if ($status === 'paid') {
                $updates[] = 'paid_at = NOW()';
            }
            
            $sql = "UPDATE payment_transactions SET " . implode(', ', $updates) . " WHERE order_code = :order_code";
            
            $stmt = DB::pdo()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('PaymentTransaction::updateStatus error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem giao dịch đã được thanh toán hay chưa
     */
    public static function isPaid(string $orderCode): bool
    {
        $transaction = self::getByOrderCode($orderCode);
        return $transaction !== null && $transaction['status'] === 'paid';
    }

    /**
     * Lấy danh sách tất cả giao dịch với phân trang
     */
    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = DB::pdo()->prepare("
                SELECT * FROM payment_transactions 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách giao dịch thanh toán của khách hàng kèm thông tin booking, khách và dịch vụ.
     */
    public static function getCustomerPaymentDetails(int $limit = 30, int $offset = 0): array
    {
        try {
            $stmt = DB::pdo()->prepare(" 
                SELECT
                    pt.*,
                    b.date AS booking_date,
                    b.time AS booking_time,
                    COALESCE(bd.location, b.location) AS booking_location,
                    b.description AS booking_description,
                    b.status AS booking_status,
                    b.assigned_worker_id,
                    u.name AS customer_name,
                    u.phone AS customer_phone,
                    u.address AS customer_address,
                    s.name AS service_name,
                    w.name AS worker_name,
                    w.phone AS worker_phone
                FROM payment_transactions pt
                JOIN bookings b ON b.id = pt.booking_id
                LEFT JOIN booking_details bd ON bd.booking_id = b.id
                JOIN users u ON u.id = b.user_id
                JOIN services s ON s.id = b.service_id
                LEFT JOIN users w ON w.id = b.assigned_worker_id
                WHERE pt.payment_method IS NULL
                   OR pt.payment_method IN (:customer_method, :legacy_method)
                ORDER BY pt.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            $stmt->bindValue(':customer_method', self::METHOD_CUSTOMER_PAYMENT);
            $stmt->bindValue(':legacy_method', 'bank_transfer');
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log('PaymentTransaction::getCustomerPaymentDetails error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Đếm tổng số giao dịch theo trạng thái
     */
    public static function countByStatus(string $status): int
    {
        try {
            $stmt = DB::pdo()->prepare("
                SELECT COUNT(*) as total FROM payment_transactions 
                WHERE status = :status
            ");
            
            $stmt->execute([':status' => $status]);
            $result = $stmt->fetch();
            
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log('PaymentTransaction::countByStatus error: ' . $e->getMessage());
            return 0;
        }
    }
}
