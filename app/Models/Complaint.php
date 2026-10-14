<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\AuditLog;
use App\Services\SlaService;
use PDO;

/**
 * Complaint Model
 * Manages hospital facility tickets, SLA response/resolution deadlines,
 * assignment lifecycles, authorized status transitions, and soft deletion.
 */
class Complaint extends BaseModel
{
    protected string $table = 'complaints';
    protected bool $useSoftDeletes = true;

    public const STATUS_OPEN        = 'open';
    public const STATUS_ASSIGNED    = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD     = 'on_hold';
    public const STATUS_RESOLVED    = 'resolved';
    public const STATUS_CLOSED      = 'closed';
    public const STATUS_ESCALATED   = 'escalated';

    public const STATUSES = [
        self::STATUS_OPEN        => ['label' => 'Open',        'badge' => 'bg-secondary', 'icon' => 'bi-folder'],
        self::STATUS_ASSIGNED    => ['label' => 'Assigned',    'badge' => 'bg-info text-dark', 'icon' => 'bi-person-check'],
        self::STATUS_IN_PROGRESS => ['label' => 'In Progress', 'badge' => 'bg-primary',   'icon' => 'bi-gear-wide-connected'],
        self::STATUS_ON_HOLD     => ['label' => 'On Hold',     'badge' => 'bg-warning text-dark', 'icon' => 'bi-pause-circle'],
        self::STATUS_RESOLVED    => ['label' => 'Resolved',    'badge' => 'bg-success',   'icon' => 'bi-check2-circle'],
        self::STATUS_CLOSED      => ['label' => 'Closed',      'badge' => 'bg-dark',      'icon' => 'bi-archive'],
        self::STATUS_ESCALATED   => ['label' => 'Escalated',   'badge' => 'bg-danger',    'icon' => 'bi-exclamation-octagon'],
    ];

    public const CATEGORIES = [
        'cleanliness'      => 'Cleanliness & Sanitation',
        'plumbing'         => 'Plumbing & Drainage',
        'electrical'       => 'Electrical & Lighting',
        'biomedical_waste' => 'Biomedical Waste Disposal',
        'civil_damage'     => 'Civil & Structural Damage',
        'pest_control'     => 'Pest Control',
        'supplies'         => 'Medical & Facility Supplies',
        'staff_behavior'   => 'Staff Conduct & Hygiene',
        'hvac'             => 'Air Conditioning & Ventilation',
        'other'            => 'Other Facility Grievance',
    ];

    public const PRIORITIES = [
        'critical' => ['label' => 'Critical', 'badge' => 'bg-danger text-white', 'icon' => 'bi-lightning-fill'],
        'high'     => ['label' => 'High',     'badge' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-triangle-fill'],
        'medium'   => ['label' => 'Medium',   'badge' => 'bg-primary text-white', 'icon' => 'bi-info-circle-fill'],
        'low'      => ['label' => 'Low',      'badge' => 'bg-secondary text-white', 'icon' => 'bi-arrow-down-circle'],
    ];

    /**
     * Allowed status transitions map
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_OPEN        => [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD, self::STATUS_RESOLVED, self::STATUS_CLOSED],
        self::STATUS_ASSIGNED    => [self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD, self::STATUS_RESOLVED, self::STATUS_CLOSED],
        self::STATUS_IN_PROGRESS => [self::STATUS_ON_HOLD, self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_ASSIGNED],
        self::STATUS_ON_HOLD     => [self::STATUS_IN_PROGRESS, self::STATUS_ASSIGNED, self::STATUS_RESOLVED, self::STATUS_CLOSED],
        self::STATUS_RESOLVED    => [self::STATUS_CLOSED, self::STATUS_OPEN], // Reopen transitions back to open
        self::STATUS_CLOSED      => [self::STATUS_OPEN],                      // Authorized reopen transitions back to open
        self::STATUS_ESCALATED   => [self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED],
    ];

    /**
     * Check if transition is valid
     */
    public static function canTransition(string $currentStatus, string $newStatus): bool
    {
        $currentStatus = strtolower($currentStatus);
        $newStatus = strtolower($newStatus);

        if ($currentStatus === $newStatus) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    /**
     * Generate unique ticket reference: CMP-YYYYMMDD-XXXX
     */
    public static function generateTicketNumber(): string
    {
        $dateStr = date('Ymd');
        $randomHex = strtoupper(bin2hex(random_bytes(2))); // 4 chars
        return "CMP-{$dateStr}-{$randomHex}";
    }

    /**
     * Create new complaint with calculated SLA deadlines and initial timeline entry
     */
    public function createComplaint(array $data, int $reportedBy): int
    {
        $pdo = Database::getConnection();

        $ticketNumber = self::generateTicketNumber();
        $category = $data['category'] ?? 'cleanliness';
        $priority = $data['priority'] ?? 'medium';

        // Calculate SLA deadlines
        $slaService = new SlaService();
        $deadlines = $slaService->calculateDeadlines($priority, $category);

        $assignedTo = !empty($data['assigned_to_user_id']) ? (int)$data['assigned_to_user_id'] : null;
        $initialStatus = $assignedTo ? self::STATUS_ASSIGNED : self::STATUS_OPEN;

        $stmt = $pdo->prepare("
            INSERT INTO `complaints` (
                `ticket_number`, `inspection_id`, `inspection_response_id`,
                `building_id`, `floor_id`, `ward_id`, `inspection_location_id`,
                `title`, `description`, `category`, `priority`,
                `sla_policy_id`, `response_due_at`, `resolution_due_at`,
                `due_date`, `status`, `reported_by`, `assigned_to_user_id`
            ) VALUES (
                :ticket_number, :inspection_id, :inspection_response_id,
                :building_id, :floor_id, :ward_id, :inspection_location_id,
                :title, :description, :category, :priority,
                :sla_policy_id, :response_due_at, :resolution_due_at,
                :due_date, :status, :reported_by, :assigned_to_user_id
            )
        ");

        $stmt->execute([
            ':ticket_number'          => $ticketNumber,
            ':inspection_id'          => !empty($data['inspection_id']) ? (int)$data['inspection_id'] : null,
            ':inspection_response_id' => !empty($data['inspection_response_id']) ? (int)$data['inspection_response_id'] : null,
            ':building_id'            => (int)$data['building_id'],
            ':floor_id'               => (int)$data['floor_id'],
            ':ward_id'                => (int)$data['ward_id'],
            ':inspection_location_id' => !empty($data['inspection_location_id']) ? (int)$data['inspection_location_id'] : null,
            ':title'                  => trim($data['title']),
            ':description'            => trim($data['description']),
            ':category'               => $category,
            ':priority'               => $priority,
            ':sla_policy_id'          => $deadlines['policy_id'],
            ':response_due_at'        => $deadlines['response_due_at'],
            ':resolution_due_at'      => $deadlines['resolution_due_at'],
            ':due_date'               => $deadlines['resolution_due_at'],
            ':status'                 => $initialStatus,
            ':reported_by'            => $reportedBy,
            ':assigned_to_user_id'    => $assignedTo,
        ]);

        $complaintId = (int)$pdo->lastInsertId();

        // 1. Initial Timeline Entry
        $updateStmt = $pdo->prepare("
            INSERT INTO `complaint_updates` (
                `complaint_id`, `user_id`, `update_type`, `previous_status`,
                `new_status`, `comment`
            ) VALUES (
                :complaint_id, :user_id, 'comment', NULL, :new_status, :comment
            )
        ");
        $updateStmt->execute([
            ':complaint_id' => $complaintId,
            ':user_id'      => $reportedBy,
            ':new_status'   => $initialStatus,
            ':comment'      => 'Complaint lodged: ' . trim($data['title']),
        ]);

        // 2. Initial Assignment Record if assigned immediately
        if ($assignedTo) {
            $assignStmt = $pdo->prepare("
                INSERT INTO `complaint_assignments` (
                    `complaint_id`, `assigned_to_user_id`, `assigned_by_user_id`, `notes`, `status`
                ) VALUES (
                    :complaint_id, :assigned_to, :assigned_by, :notes, 'active'
                )
            ");
            $assignStmt->execute([
                ':complaint_id' => $complaintId,
                ':assigned_to'  => $assignedTo,
                ':assigned_by'  => $reportedBy,
                ':notes'        => 'Assigned upon ticket creation.',
            ]);
        }

        // 3. Security Audit Log
        AuditLog::log(
            'complaint.create',
            'complaints',
            $complaintId,
            null,
            ['ticket_number' => $ticketNumber, 'title' => $data['title'], 'priority' => $priority]
        );

        return $complaintId;
    }

    /**
     * Assign or reassign complaint to a supervisor or hospital personnel
     */
    public function assign(int $id, int $assignToUserId, int $assignedByUserId, ?string $notes = null): bool
    {
        $complaint = $this->find($id);
        if (!$complaint) {
            return false;
        }

        $pdo = Database::getConnection();
        $previousAssignee = $complaint['assigned_to_user_id'] ? (int)$complaint['assigned_to_user_id'] : null;
        $isReassignment = ($previousAssignee !== null && $previousAssignee !== $assignToUserId);
        $updateType = $isReassignment ? 'reassignment' : 'assignment';

        // Mark previous assignments as 'reassigned'
        $pdo->prepare("
            UPDATE `complaint_assignments`
            SET `status` = 'reassigned'
            WHERE `complaint_id` = :id AND `status` = 'active'
        ")->execute([':id' => $id]);

        // Insert new assignment record
        $stmt = $pdo->prepare("
            INSERT INTO `complaint_assignments` (
                `complaint_id`, `assigned_to_user_id`, `assigned_by_user_id`, `notes`, `status`
            ) VALUES (
                :complaint_id, :assigned_to, :assigned_by, :notes, 'active'
            )
        ");
        $stmt->execute([
            ':complaint_id' => $id,
            ':assigned_to'  => $assignToUserId,
            ':assigned_by'  => $assignedByUserId,
            ':notes'        => $notes,
        ]);

        // Transition status if currently 'open'
        $newStatus = $complaint['status'];
        if ($complaint['status'] === self::STATUS_OPEN) {
            $newStatus = self::STATUS_ASSIGNED;
        }

        // Set first_responded_at if this is the first operational response
        $firstRespondedAt = $complaint['first_responded_at'] ?: date('Y-m-d H:i:s');

        $pdo->prepare("
            UPDATE `complaints`
            SET `assigned_to_user_id` = :assigned_to,
                `status` = :status,
                `first_responded_at` = :first_resp,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :id
        ")->execute([
            ':assigned_to' => $assignToUserId,
            ':status'      => $newStatus,
            ':first_resp'  => $firstRespondedAt,
            ':id'          => $id,
        ]);

        // Add timeline update
        $assigneeUser = (new User())->find($assignToUserId);
        $assigneeName = $assigneeUser['name'] ?? "User #{$assignToUserId}";
        $comment = $isReassignment
            ? "Complaint reassigned to {$assigneeName}. Notes: " . ($notes ?: 'None')
            : "Complaint assigned to {$assigneeName}. Notes: " . ($notes ?: 'None');

        $this->addTimelineUpdate($id, $assignedByUserId, $updateType, $comment, $complaint['status'], $newStatus);

        // Security Audit
        AuditLog::log('complaint.' . $updateType, 'complaints', $id, [
            'previous_assignee' => $previousAssignee,
        ], [
            'new_assignee' => $assignToUserId,
            'notes'        => $notes,
        ]);

        return true;
    }

    /**
     * Transition complaint status with validation and timeline update
     */
    public function updateStatus(
        int $id,
        string $newStatus,
        int $userId,
        string $comment,
        string $updateType = 'status_change'
    ): bool {
        $complaint = $this->find($id);
        if (!$complaint) {
            return false;
        }

        $currentStatus = $complaint['status'];
        if (!self::canTransition($currentStatus, $newStatus)) {
            throw new \InvalidArgumentException("Invalid status transition from '{$currentStatus}' to '{$newStatus}'.");
        }

        $pdo = Database::getConnection();

        // If moving out of 'open', record first_responded_at if not set
        $firstRespondedAt = $complaint['first_responded_at'];
        if (!$firstRespondedAt && $currentStatus === self::STATUS_OPEN && $newStatus !== self::STATUS_OPEN) {
            $firstRespondedAt = date('Y-m-d H:i:s');
        }

        $pdo->prepare("
            UPDATE `complaints`
            SET `status` = :status,
                `first_responded_at` = :first_resp,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :id
        ")->execute([
            ':status'     => $newStatus,
            ':first_resp' => $firstRespondedAt,
            ':id'         => $id,
        ]);

        $this->addTimelineUpdate($id, $userId, $updateType, $comment, $currentStatus, $newStatus);

        AuditLog::log('complaint.status_change', 'complaints', $id, [
            'status' => $currentStatus,
        ], [
            'status'  => $newStatus,
            'comment' => $comment,
        ]);

        return true;
    }

    /**
     * Place complaint on hold
     */
    public function putOnHold(int $id, int $userId, string $reason): bool
    {
        return $this->updateStatus($id, self::STATUS_ON_HOLD, $userId, 'Placed on hold: ' . $reason, 'hold');
    }

    /**
     * Mark complaint as resolved with mandatory resolution notes
     */
    public function resolve(int $id, int $userId, string $resolutionNotes): bool
    {
        $complaint = $this->find($id);
        if (!$complaint) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Check if resolution deadline was breached
        $slaService = new SlaService();
        $eval = $slaService->evaluateBreaches(array_merge($complaint, ['status' => 'resolved', 'resolved_at' => $now]));

        $pdo = Database::getConnection();
        $pdo->prepare("
            UPDATE `complaints`
            SET `status` = 'resolved',
                `resolved_at` = :resolved_at,
                `resolved_by` = :resolved_by,
                `resolution_notes` = :notes,
                `is_resolution_breached` = :is_breached,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :id
        ")->execute([
            ':resolved_at'  => $now,
            ':resolved_by'  => $userId,
            ':notes'        => trim($resolutionNotes),
            ':is_breached'  => $eval['is_resolution_breached'] ? 1 : 0,
            ':id'           => $id,
        ]);

        $this->addTimelineUpdate(
            $id,
            $userId,
            'resolution',
            'Complaint resolved: ' . trim($resolutionNotes),
            $complaint['status'],
            'resolved'
        );

        AuditLog::log('complaint.resolve', 'complaints', $id, null, [
            'resolved_by'      => $userId,
            'resolution_notes' => $resolutionNotes,
            'resolved_at'      => $now,
        ]);

        return true;
    }

    /**
     * Reopen a resolved or closed complaint with a mandatory reason
     */
    public function reopen(int $id, int $userId, string $reason): bool
    {
        $complaint = $this->find($id);
        if (!$complaint) {
            return false;
        }

        if (!in_array($complaint['status'], [self::STATUS_RESOLVED, self::STATUS_CLOSED], true)) {
            throw new \InvalidArgumentException("Only resolved or closed complaints can be reopened.");
        }

        if (trim($reason) === '') {
            throw new \InvalidArgumentException("A valid reason is required to reopen this complaint.");
        }

        $now = date('Y-m-d H:i:s');
        $pdo = Database::getConnection();

        // Reopen transitions to 'open' (or 'in_progress' if assigned)
        $newStatus = !empty($complaint['assigned_to_user_id']) ? self::STATUS_IN_PROGRESS : self::STATUS_OPEN;

        $pdo->prepare("
            UPDATE `complaints`
            SET `status` = :new_status,
                `reopened_at` = :reopened_at,
                `reopened_by` = :reopened_by,
                `reopen_reason` = :reason,
                `resolved_at` = NULL,
                `closed_at` = NULL,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :id
        ")->execute([
            ':new_status'   => $newStatus,
            ':reopened_at'  => $now,
            ':reopened_by'  => $userId,
            ':reason'       => trim($reason),
            ':id'           => $id,
        ]);

        $this->addTimelineUpdate(
            $id,
            $userId,
            'reopen',
            'Complaint reopened: ' . trim($reason),
            $complaint['status'],
            $newStatus
        );

        AuditLog::log('complaint.reopen', 'complaints', $id, [
            'status' => $complaint['status'],
        ], [
            'status'        => $newStatus,
            'reopened_by'   => $userId,
            'reopen_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Close complaint
     */
    public function close(int $id, int $userId, ?string $notes = null): bool
    {
        $complaint = $this->find($id);
        if (!$complaint) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $pdo = Database::getConnection();

        $pdo->prepare("
            UPDATE `complaints`
            SET `status` = 'closed',
                `closed_at` = :closed_at,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :id
        ")->execute([
            ':closed_at' => $now,
            ':id'        => $id,
        ]);

        $comment = 'Complaint closed.' . ($notes ? ' Notes: ' . $notes : '');
        $this->addTimelineUpdate($id, $userId, 'closure', $comment, $complaint['status'], 'closed');

        AuditLog::log('complaint.close', 'complaints', $id, null, ['closed_at' => $now]);

        return true;
    }

    /**
     * Soft delete complaint (preserving history and inspection links)
     */
    public function softDelete(int|string $id, ?int $deletedBy = null, ?string $reason = null): int
    {
        $complaint = $this->find((int)$id);
        if (!$complaint) {
            return 0;
        }

        $affected = parent::softDelete($id, $deletedBy, $reason);

        AuditLog::log('complaint.soft_delete', 'complaints', (int)$id, null, [
            'deleted_by'      => $deletedBy,
            'deletion_reason' => $reason,
        ]);

        return $affected;
    }

    /**
     * Restore soft-deleted complaint
     */
    public function restore(int|string $id, ?int $restoredBy = null): int
    {
        $affected = parent::restore($id);

        if ($affected > 0) {
            $this->addTimelineUpdate((int)$id, $restoredBy ?: (int)$id, 'comment', 'Complaint restored from archives.');

            AuditLog::log('complaint.restore', 'complaints', (int)$id, null, ['restored_by' => $restoredBy]);
        }

        return $affected;
    }

    /**
     * Add chronological timeline update entry
     */
    public function addTimelineUpdate(
        int $complaintId,
        int $userId,
        string $updateType,
        string $comment,
        ?string $prevStatus = null,
        ?string $newStatus = null
    ): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `complaint_updates` (
                `complaint_id`, `user_id`, `update_type`, `previous_status`,
                `new_status`, `comment`
            ) VALUES (
                :complaint_id, :user_id, :update_type, :prev_status, :new_status, :comment
            )
        ");
        $stmt->execute([
            ':complaint_id' => $complaintId,
            ':user_id'      => $userId,
            ':update_type'  => $updateType,
            ':prev_status'  => $prevStatus,
            ':new_status'   => $newStatus,
            ':comment'      => trim($comment),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Fetch complete complaint details with facility, reporter, assignee and SLA info
     */
    public function getDetails(int $id, bool $includeDeleted = false): ?array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                c.*,
                b.name AS building_name, b.code AS building_code,
                f.floor_name, f.floor_number,
                w.ward_name, w.ward_code,
                loc.location_name, loc.room_number,
                u_rep.name AS reporter_name, u_rep.email AS reporter_email,
                u_ass.name AS assignee_name, u_ass.email AS assignee_email,
                u_res.name AS resolver_name,
                u_reo.name AS reopener_name,
                insp.reference_number AS inspection_reference
            FROM `complaints` c
            JOIN `buildings` b ON c.building_id = b.id
            JOIN `floors` f ON c.floor_id = f.id
            JOIN `wards` w ON c.ward_id = w.id
            LEFT JOIN `inspection_locations` loc ON c.inspection_location_id = loc.id
            JOIN `users` u_rep ON c.reported_by = u_rep.id
            LEFT JOIN `users` u_ass ON c.assigned_to_user_id = u_ass.id
            LEFT JOIN `users` u_res ON c.resolved_by = u_res.id
            LEFT JOIN `users` u_reo ON c.reopened_by = u_reo.id
            LEFT JOIN `inspections` insp ON c.inspection_id = insp.id
            WHERE c.id = :id
        ";

        if (!$includeDeleted) {
            $sql .= " AND c.deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$complaint) {
            return null;
        }

        // Augment with live SLA breach evaluation
        $slaService = new SlaService();
        $complaint['sla_evaluation'] = $slaService->evaluateBreaches($complaint);
        $complaint['sla_badge'] = $slaService->formatSlaBadge($complaint);

        return $complaint;
    }

    /**
     * Fetch chronological timeline for a complaint
     */
    public function getTimeline(int $complaintId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                cu.*,
                u.name AS user_name, u.role AS user_role,
                ce.id AS evidence_id, ce.original_filename, ce.stored_filename,
                ce.file_size_bytes, ce.mime_type
            FROM `complaint_updates` cu
            JOIN `users` u ON cu.user_id = u.id
            LEFT JOIN `complaint_evidence` ce ON cu.id = ce.complaint_update_id
            WHERE cu.complaint_id = :id
            ORDER BY cu.created_at ASC
        ");
        $stmt->execute([':id' => $complaintId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all evidence attached to a complaint
     */
    public function getEvidence(int $complaintId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ce.*, u.name AS uploader_name
            FROM `complaint_evidence` ce
            JOIN `users` u ON ce.uploaded_by = u.id
            WHERE ce.complaint_id = :id
            ORDER BY ce.created_at DESC
        ");
        $stmt->execute([':id' => $complaintId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated and filtered complaints list
     */
    public function getFiltered(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::getConnection();
        $conditions = ["c.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "c.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $conditions[] = "c.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['category'])) {
            $conditions[] = "c.category = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['building_id'])) {
            $conditions[] = "c.building_id = :bldg";
            $params[':bldg'] = (int)$filters['building_id'];
        }

        if (!empty($filters['floor_id'])) {
            $conditions[] = "c.floor_id = :floor";
            $params[':floor'] = (int)$filters['floor_id'];
        }

        if (!empty($filters['ward_id'])) {
            $conditions[] = "c.ward_id = :ward";
            $params[':ward'] = (int)$filters['ward_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $conditions[] = "c.assigned_to_user_id = :assigned_to";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "c.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "c.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(c.ticket_number LIKE :search OR c.title LIKE :search OR c.description LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        // SLA Breach Filter
        if (!empty($filters['sla_breach'])) {
            if ($filters['sla_breach'] === 'response') {
                $conditions[] = "(c.is_response_breached = 1 OR (c.status = 'open' AND c.response_due_at < NOW()))";
            } elseif ($filters['sla_breach'] === 'resolution') {
                $conditions[] = "(c.is_resolution_breached = 1 OR (c.status NOT IN ('resolved', 'closed') AND c.resolution_due_at < NOW()))";
            } elseif ($filters['sla_breach'] === 'any') {
                $conditions[] = "(c.is_response_breached = 1 OR c.is_resolution_breached = 1 OR (c.status NOT IN ('resolved', 'closed') AND c.resolution_due_at < NOW()))";
            }
        }

        $whereClause = implode(' AND ', $conditions);

        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `complaints` c WHERE {$whereClause}");
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "
            SELECT 
                c.*,
                b.name AS building_name,
                f.floor_name,
                w.ward_name,
                u_rep.name AS reporter_name,
                u_ass.name AS assignee_name
            FROM `complaints` c
            JOIN `buildings` b ON c.building_id = b.id
            JOIN `floors` f ON c.floor_id = f.id
            JOIN `wards` w ON c.ward_id = w.id
            JOIN `users` u_rep ON c.reported_by = u_rep.id
            LEFT JOIN `users` u_ass ON c.assigned_to_user_id = u_ass.id
            WHERE {$whereClause}
            ORDER BY 
                CASE c.priority 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    ELSE 4 
                END ASC,
                c.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $slaService = new SlaService();
        foreach ($rows as &$row) {
            $row['sla_evaluation'] = $slaService->evaluateBreaches($row);
            $row['sla_badge'] = $slaService->formatSlaBadge($row);
        }

        return [
            'data'         => $rows,
            'total'        => $totalRecords,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => $totalPages,
        ];
    }
}
