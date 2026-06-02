<?php

declare(strict_types=1);

namespace App\Controllers\Manager;

use App\Core\Auth;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\User;

/**
 * BaseManagerController - Lớp cơ sở cho tất cả các controller của Manager.
 * 
 * Cung cấp các phương thức chung cho:
 * - Kiểm tra quyền truy cập (Manager hoặc Admin)
 * - Xử lý chuyển hướng khi chưa có quyền
 * - Các phương thức tiện ích chung
 * 
 * Chính sách quyền:
 * - Manager: Quản lý Booking, phân công Worker, duyệt/từ chối Worker, xem Customer/Worker, Chat
 * - Admin: Có toàn bộ quyền của Manager + quản lý Service, thống kê, quản lý tài khoản
 */
abstract class BaseManagerController
{
    protected function requireManagerRole(): void
    {
        if (!Auth::isAuthenticated()) {
            $this->redirect('/login');
        }

        $userRole = Auth::role();
        if ($userRole !== User::ROLE_MANAGER && $userRole !== User::ROLE_ADMIN) {
            $this->redirectToUserDashboard($userRole);
        }
    }

    protected function requireAdminRole(): void
    {
        if (!Auth::isAuthenticated() || Auth::role() !== User::ROLE_ADMIN) {
            $this->redirect('/login');
        }
    }

    protected function requireManagerRoleExclusive(): void
    {
        if (!Auth::isAuthenticated() || Auth::role() !== User::ROLE_MANAGER) {
            $this->redirect('/login');
        }
    }

    protected function redirectToUserDashboard(?string $role = null): void
    {
        $role = $role ?? Auth::role();

        match ($role) {
            User::ROLE_ADMIN => $this->redirect('/admin/dashboard'),
            User::ROLE_MANAGER => $this->redirect('/manager/dashboard'),
            User::ROLE_WORKER => $this->redirect('/worker/dashboard'),
            default => $this->redirect('/'),
        };
    }

    protected function setSessionMessage(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$type] = $message;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        exit(0);
    }

    protected function getActiveWorkers(): array
    {
        $allUsers = User::listAll();
        return array_values(array_filter(
            $allUsers,
            static fn(array $user): bool => ($user['role'] ?? '') === User::ROLE_WORKER
                && ($user['approval_status'] ?? '') === User::STATUS_ACTIVE
        ));
    }

    protected function getActiveCustomers(): array
    {
        $allUsers = User::listAll();
        return array_values(array_filter(
            $allUsers,
            static fn(array $user): bool => ($user['role'] ?? '') === User::ROLE_CUSTOMER
                && ($user['approval_status'] ?? '') === User::STATUS_ACTIVE
        ));
    }

    protected function enrichBookingsWithPaymentStatus(array $bookings): array
    {
        foreach ($bookings as &$booking) {
            $bookingId = (int)($booking['id'] ?? 0);
            $payment = PaymentTransaction::getLatestCustomerByBookingId($bookingId);
            $paidPayment = null;

            $booking['is_customer_paid'] = PaymentTransaction::hasSuccessfulCustomerPayment($bookingId);
            $booking['hasPaidPayment'] = $booking['is_customer_paid'];
            $booking['customer_payment_status'] = $payment['status'] ?? 'pending';
            $booking['customer_paid_amount'] = (float)($payment['amount'] ?? 0);
            $booking['customer_paid_at'] = $payment['paid_at'] ?? null;

            if (!empty($booking['is_customer_paid'])) {
                $paidPayment = PaymentTransaction::getLatestPaidCustomerByBookingId($bookingId);
            }

            $booking['customer_paid_transaction_id'] = $paidPayment['id'] ?? null;
        }
        unset($booking);

        return $bookings;
    }

    protected function verifyCsrfToken(?string $token): bool
    {
        $csrfClass = 'App\\Core\\Csrf';
        return class_exists($csrfClass) && $csrfClass::verify($token);
    }
}
