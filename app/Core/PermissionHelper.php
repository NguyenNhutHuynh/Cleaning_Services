<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Booking;
use App\Models\User;

final class PermissionHelper
{
    public static function requireLogin(): void
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /login', true, 302);
            exit(0);
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array((string)Auth::role(), $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit(1);
        }
    }

    public static function canManageBooking(int $actorId, string $actorRole, int $bookingId): bool
    {
        if ($actorRole === User::ROLE_ADMIN) {
            return true;
        }

        if ($actorRole !== User::ROLE_MANAGER) {
            return false;
        }

        $booking = Booking::getById($bookingId);
        if ($booking === null) {
            return false;
        }

        foreach (['manager_id', 'assigned_manager_id', 'handled_by_manager_id', 'branch_manager_id'] as $scopeField) {
            if (array_key_exists($scopeField, $booking)) {
                return (int)($booking[$scopeField] ?? 0) === $actorId;
            }
        }

        return true;
    }

    public static function canAssignWorker(int $actorId, string $actorRole, int $bookingId): bool
    {
        return self::canManageBooking($actorId, $actorRole, $bookingId);
    }

    public static function canWorkerAccessJob(int $workerId, int $jobId): bool
    {
        $booking = Booking::getDetailById($jobId);
        return $booking !== null && (int)($booking['assigned_worker_id'] ?? 0) === $workerId;
    }

    public static function canCustomerAccessBooking(int $customerId, int $bookingId): bool
    {
        $booking = Booking::getById($bookingId);
        return $booking !== null && (int)($booking['user_id'] ?? 0) === $customerId;
    }
}