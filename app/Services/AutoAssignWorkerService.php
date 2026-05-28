<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\PermissionHelper;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use DateTimeImmutable;
use PDO;
use Throwable;

final class AutoAssignWorkerService
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= DB::pdo();
    }

    public function autoAssign(int $bookingId, int $actorId, string $actorRole): array
    {
        if (!$this->canActorAssignWorker($actorId, $actorRole, $bookingId)) {
            return [
                'success' => false,
                'message' => 'Permission denied',
                'reasons' => ['Actor is not allowed to assign this booking'],
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $booking = Booking::getById($bookingId);
            if ($booking === null) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Booking not found',
                    'reasons' => ['Booking was not found'],
                ];
            }

            $bookingStatus = (string)($booking['status'] ?? '');
            if (in_array($bookingStatus, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Booking is not assignable',
                    'reasons' => ['Booking already cancelled or completed'],
                ];
            }

            $service = Service::getById((int)($booking['service_id'] ?? 0));
            if ($service === null || (int)($service['is_active'] ?? 1) !== 1) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Service is not assignable',
                    'reasons' => ['Service is missing or inactive'],
                ];
            }

            $this->lockBookingRows($bookingId);

            $eligibleWorkers = $this->findEligibleWorkers($booking);
            if ($eligibleWorkers === []) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'No eligible worker found',
                    'reasons' => ['No worker matched service or availability'],
                ];
            }

            usort($eligibleWorkers, static function (array $left, array $right): int {
                $scoreCompare = (int)($right['score'] ?? 0) <=> (int)($left['score'] ?? 0);
                if ($scoreCompare !== 0) {
                    return $scoreCompare;
                }

                return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
            });

            $window = $this->buildBookingWindow($booking);
            $bookingId = (int)($booking['id'] ?? $bookingId);
            $currentWorkerId = (int)($booking['assigned_worker_id'] ?? 0);

            foreach ($eligibleWorkers as $worker) {
                $workerId = (int)($worker['id'] ?? 0);
                if ($workerId <= 0) {
                    continue;
                }

                if (!$this->isWorkerAvailable($workerId, $window['start'], $window['end'], $bookingId)) {
                    continue;
                }

                if (!$this->assignWorker($bookingId, $workerId)) {
                    continue;
                }

                $updatedStatus = $bookingStatus;
                if (in_array($bookingStatus, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED], true)) {
                    Booking::updateStatus($bookingId, Booking::STATUS_CONFIRMED);
                    $updatedStatus = Booking::STATUS_CONFIRMED;
                }

                $score = (int)($worker['score'] ?? 0);
                $reason = (string)($worker['match_reason'] ?? 'Worker matched service, area and availability');
                $this->pdo->commit();

                $this->logAutoAssign([
                    'booking_id' => $bookingId,
                    'worker_id' => $workerId,
                    'previous_worker_id' => $currentWorkerId > 0 ? $currentWorkerId : null,
                    'actor_id' => $actorId,
                    'actor_role' => $actorRole,
                    'score' => $score,
                    'reason' => $reason,
                    'status_before' => $bookingStatus,
                    'status_after' => $updatedStatus,
                    'metadata' => [
                        'service_id' => (int)($booking['service_id'] ?? 0),
                        'service_name' => (string)($service['name'] ?? ''),
                        'window_start' => $window['start'],
                        'window_end' => $window['end'],
                    ],
                ]);

                return [
                    'success' => true,
                    'message' => 'Worker assigned successfully',
                    'booking_id' => $bookingId,
                    'worker_id' => $workerId,
                    'score' => $score,
                    'reason' => $reason,
                ];
            }

            $this->pdo->rollBack();

            return [
                'success' => false,
                'message' => 'No eligible worker found',
                'reasons' => ['All eligible workers were busy in the selected time range'],
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            AuditLogger::write('auto_assign_worker_error', [
                'booking_id' => $bookingId,
                'actor_id' => $actorId,
                'actor_role' => $actorRole,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Auto assign failed',
                'reasons' => ['Unexpected error while auto assigning worker'],
            ];
        }
    }

    public function findEligibleWorkers(array $booking): array
    {
        $serviceId = (int)($booking['service_id'] ?? 0);
        if ($serviceId <= 0) {
            return [];
        }

        $service = Service::getById($serviceId);
        if ($service === null || (int)($service['is_active'] ?? 1) !== 1) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                u.id,
                u.name,
                u.email,
                u.phone,
                u.address,
                                COALESCE((SELECT AVG(r.rating) FROM booking_reviews r WHERE r.worker_id = u.id), 0) AS avg_rating,
                                COALESCE((SELECT COUNT(DISTINCT b2.id)
                                                 FROM bookings b2
                                                 JOIN booking_details bd2 ON bd2.booking_id = b2.id
                                                 WHERE b2.assigned_worker_id = u.id
                                                     AND bd2.detail_status = 'completed'), 0) AS completed_jobs,
                                COALESCE((SELECT COUNT(DISTINCT b2.id)
                                                 FROM bookings b2
                                                 JOIN booking_details bd2 ON bd2.booking_id = b2.id
                                                 WHERE b2.assigned_worker_id = u.id
                                                     AND bd2.detail_status IN ('confirmed', 'accepted', 'in_progress')), 0) AS active_jobs,
                                COALESCE((SELECT COUNT(DISTINCT b2.id)
                                                 FROM bookings b2
                                                 JOIN booking_details bd2 ON bd2.booking_id = b2.id
                                                 WHERE b2.assigned_worker_id = u.id
                                                     AND bd2.detail_status = 'cancelled'), 0) AS cancel_count,
                                COALESCE((SELECT COUNT(*) FROM booking_reports rep WHERE rep.worker_id = u.id), 0) AS complaint_count,
                                COALESCE((SELECT COUNT(DISTINCT b2.id)
                                                 FROM bookings b2
                                                 JOIN booking_details bd2 ON bd2.booking_id = b2.id
                                                 WHERE b2.assigned_worker_id = u.id
                                                     AND bd2.service_id = :service_id
                                                     AND bd2.detail_status = 'completed'), 0) AS same_service_experience
                         FROM users u
             WHERE u.role = :role
               AND u.approval_status = :approval_status
             ORDER BY u.id ASC"
        );
        $stmt->execute([
            'service_id' => $serviceId,
            'role' => User::ROLE_WORKER,
            'approval_status' => User::STATUS_ACTIVE,
        ]);

        $workers = $stmt->fetchAll() ?: [];
        if ($workers === []) {
            return [];
        }

        $serviceRows = array_values(array_filter(
            $workers,
            static fn(array $worker): bool => (int)($worker['same_service_experience'] ?? 0) > 0
        ));

        $candidatePool = $serviceRows !== [] ? $serviceRows : $workers;
        $eligible = [];
        foreach ($candidatePool as $worker) {
            $score = $this->calculateWorkerScore($worker, $booking);
            $eligible[] = [
                'id' => (int)($worker['id'] ?? 0),
                'name' => (string)($worker['name'] ?? ''),
                'email' => (string)($worker['email'] ?? ''),
                'phone' => (string)($worker['phone'] ?? ''),
                'address' => (string)($worker['address'] ?? ''),
                'score' => $score,
                'avg_rating' => (float)($worker['avg_rating'] ?? 0),
                'completed_jobs' => (int)($worker['completed_jobs'] ?? 0),
                'active_jobs' => (int)($worker['active_jobs'] ?? 0),
                'cancel_count' => (int)($worker['cancel_count'] ?? 0),
                'complaint_count' => (int)($worker['complaint_count'] ?? 0),
                'same_service_experience' => (int)($worker['same_service_experience'] ?? 0),
                'match_reason' => $score > 0 ? 'Worker matched service, area and availability' : 'Worker matched availability',
            ];
        }

        return $eligible;
    }

    public function isWorkerAvailable(int $workerId, string $start, string $end, int $bookingId = 0): bool
    {
        $startAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start) ?: new DateTimeImmutable($start);
        $endAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end) ?: new DateTimeImmutable($end);
        $durationMinutes = max(1, (int)(($endAt->getTimestamp() - $startAt->getTimestamp()) / 60));

        $stmt = $this->pdo->prepare(
            "SELECT bd.id
             FROM booking_details bd
             INNER JOIN bookings b ON b.id = bd.booking_id
             WHERE COALESCE(bd.assigned_worker_id, b.assigned_worker_id) = :worker_id
               AND b.id <> :booking_id
               AND COALESCE(bd.detail_status, 'pending') NOT IN ('cancelled', 'completed')
               AND TIMESTAMP(bd.work_date, bd.work_time) < :new_end
               AND DATE_ADD(TIMESTAMP(bd.work_date, bd.work_time), INTERVAL :duration_minutes MINUTE) > :new_start
             LIMIT 1 FOR UPDATE"
        );
        $stmt->bindValue(':worker_id', $workerId, PDO::PARAM_INT);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':new_end', $endAt->format('Y-m-d H:i:s'));
        $stmt->bindValue(':new_start', $startAt->format('Y-m-d H:i:s'));
        $stmt->bindValue(':duration_minutes', $durationMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() === false;
    }

    public function calculateWorkerScore(array $worker, array $booking): int
    {
        $rating = (float)($worker['avg_rating'] ?? 0);
        $completedJobs = (int)($worker['completed_jobs'] ?? 0);
        $activeJobs = (int)($worker['active_jobs'] ?? 0);
        $cancelCount = (int)($worker['cancel_count'] ?? 0);
        $complaintCount = (int)($worker['complaint_count'] ?? 0);
        $sameServiceExperience = (int)($worker['same_service_experience'] ?? 0);

        $sameAreaBonus = $this->hasSharedAreaFragment(
            (string)($worker['address'] ?? ''),
            (string)($booking['location'] ?? '')
        ) ? 15 : 0;

        $score = (int)round($rating * 20);
        $score += $completedJobs;
        $score += $sameServiceExperience * 10;
        $score += $sameAreaBonus;
        $score -= $activeJobs * 5;
        $score -= $cancelCount * 10;
        $score -= $complaintCount * 20;

        return $score;
    }

    public function assignWorker(int $bookingId, int $workerId): bool
    {
        return Booking::assignWorker($bookingId, $workerId);
    }

    public function canActorAssignWorker(int $actorId, string $actorRole, int $bookingId): bool
    {
        return PermissionHelper::canAssignWorker($actorId, $actorRole, $bookingId);
    }

    public function logAutoAssign(array $data): void
    {
        AuditLogger::write('auto_assign_worker', $data);
    }

    private function lockBookingRows(int $bookingId): void
    {
        $bookingLock = $this->pdo->prepare('SELECT id FROM bookings WHERE id = :id FOR UPDATE');
        $bookingLock->execute(['id' => $bookingId]);

        $detailLock = $this->pdo->prepare('SELECT id FROM booking_details WHERE booking_id = :id FOR UPDATE');
        $detailLock->execute(['id' => $bookingId]);
    }

    public function buildBookingWindow(array $booking): array
    {
        $date = trim((string)($booking['date'] ?? ''));
        $time = trim((string)($booking['time'] ?? ''));
        $serviceId = (int)($booking['service_id'] ?? 0);
        $service = $serviceId > 0 ? Service::getById($serviceId) : null;

        if ($service === null || (int)($service['is_active'] ?? 1) !== 1) {
            throw new \RuntimeException('Service is missing or inactive');
        }

        $start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time . ':00')
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time)
            ?: new DateTimeImmutable($date . ' ' . $time);

        $durationMinutes = $this->parseDurationMinutes((string)($service['duration'] ?? ''));
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'duration_minutes' => $durationMinutes,
        ];
    }

    private function parseDurationMinutes(string $durationText): int
    {
        $normalized = trim($this->normalizeText($durationText));
        if ($normalized === '') {
            return 60;
        }

        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:giờ|h)\s*(?:(\d+)\s*(?:phút|m))?/u', $normalized, $matches)) {
            $hours = (float)str_replace(',', '.', $matches[1]);
            $minutes = (int)round($hours * 60);
            if (!empty($matches[2])) {
                $minutes += (int)$matches[2];
            }

            return max(15, $minutes);
        }

        if (preg_match('/(\d+)\s*(?:phút|m)/u', $normalized, $matches)) {
            return max(15, (int)$matches[1]);
        }

        if (preg_match('/(\d+(?:[\.,]\d+)?)/u', $normalized, $matches)) {
            $value = (float)str_replace(',', '.', $matches[1]);
            if ($value <= 12) {
                return max(15, (int)round($value * 60));
            }

            return max(15, (int)round($value));
        }

        return 60;
    }

    private function hasSharedAreaFragment(string $workerAddress, string $bookingLocation): bool
    {
        $workerParts = $this->splitAddress($workerAddress);
        $bookingParts = $this->splitAddress($bookingLocation);

        foreach ($workerParts as $workerPart) {
            foreach ($bookingParts as $bookingPart) {
                if ($workerPart !== '' && $bookingPart !== '' && (str_contains($workerPart, $bookingPart) || str_contains($bookingPart, $workerPart))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function splitAddress(string $value): array
    {
        $parts = array_map('trim', preg_split('/[,\-\/]+/u', $this->normalizeText($value)) ?: []);
        return array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));
    }

    private function normalizeText(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }

        return strtolower($value);
    }
}