<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * SLA Policy & Deadline Calculation Service
 * Computes response and resolution deadlines based on category and priority,
 * detects overdue breaches, and formats SLA compliance metrics.
 */
class SlaService
{
    /**
     * Default fallback SLA hours when database policy is not explicitly defined
     */
    public const DEFAULT_SLAS = [
        'critical' => ['response_hours' => 1,  'resolution_hours' => 4],
        'high'     => ['response_hours' => 2,  'resolution_hours' => 8],
        'medium'   => ['response_hours' => 4,  'resolution_hours' => 24],
        'low'      => ['response_hours' => 12, 'resolution_hours' => 48],
    ];

    /**
     * Specific Category SLA Overrides (e.g., Biomedical Waste, Critical Infection Control)
     */
    public const CATEGORY_OVERRIDES = [
        'biomedical_waste' => [
            'critical' => ['response_hours' => 1, 'resolution_hours' => 2],
            'high'     => ['response_hours' => 2, 'resolution_hours' => 4],
        ],
        'cleanliness' => [
            'critical' => ['response_hours' => 1, 'resolution_hours' => 2],
        ],
        'plumbing' => [
            'critical' => ['response_hours' => 1, 'resolution_hours' => 3],
            'high'     => ['response_hours' => 2, 'resolution_hours' => 6],
        ],
        'electrical' => [
            'critical' => ['response_hours' => 1, 'resolution_hours' => 3],
        ],
    ];

    /**
     * Find best matching SLA policy for a category and priority
     */
    public function findPolicy(string $priority, string $category = 'all'): array
    {
        $priority = strtolower($priority);
        $category = strtolower($category);

        try {
            $pdo = Database::getConnection();

            // 1. Try exact category + priority match
            $stmt = $pdo->prepare("
                SELECT * FROM `sla_policies`
                WHERE `category` = :category AND `priority` = :priority AND `is_active` = 1
                LIMIT 1
            ");
            $stmt->execute([':category' => $category, ':priority' => $priority]);
            $policy = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($policy) {
                return [
                    'id'                     => (int)$policy['id'],
                    'policy_name'            => $policy['policy_name'],
                    'category'               => $policy['category'],
                    'priority'               => $policy['priority'],
                    'response_time_hours'    => (int)$policy['response_time_hours'],
                    'resolution_time_hours'  => (int)$policy['resolution_time_hours'],
                    'escalation_email'       => $policy['escalation_email'] ?? null,
                ];
            }

            // 2. Try generic 'all' category + priority match
            $stmt = $pdo->prepare("
                SELECT * FROM `sla_policies`
                WHERE `category` = 'all' AND `priority` = :priority AND `is_active` = 1
                LIMIT 1
            ");
            $stmt->execute([':priority' => $priority]);
            $policy = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($policy) {
                return [
                    'id'                     => (int)$policy['id'],
                    'policy_name'            => $policy['policy_name'],
                    'category'               => 'all',
                    'priority'               => $policy['priority'],
                    'response_time_hours'    => (int)$policy['response_time_hours'],
                    'resolution_time_hours'  => (int)$policy['resolution_time_hours'],
                    'escalation_email'       => $policy['escalation_email'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback to in-memory configuration if DB query fails or during unit tests
        }

        // 3. In-memory category overrides fallback
        if (isset(self::CATEGORY_OVERRIDES[$category][$priority])) {
            $override = self::CATEGORY_OVERRIDES[$category][$priority];
            return [
                'id'                    => null,
                'policy_name'           => ucfirst($category) . ' ' . ucfirst($priority) . ' SLA',
                'category'              => $category,
                'priority'              => $priority,
                'response_time_hours'   => $override['response_hours'],
                'resolution_time_hours' => $override['resolution_hours'],
                'escalation_email'      => null,
            ];
        }

        // 4. Default priority SLA fallback
        $default = self::DEFAULT_SLAS[$priority] ?? self::DEFAULT_SLAS['medium'];
        return [
            'id'                    => null,
            'policy_name'           => 'Standard ' . ucfirst($priority) . ' SLA',
            'category'              => 'all',
            'priority'              => $priority,
            'response_time_hours'   => $default['response_hours'],
            'resolution_time_hours' => $default['resolution_hours'],
            'escalation_email'      => null,
        ];
    }

    /**
     * Calculate response and resolution deadlines from a given start timestamp
     */
    public function calculateDeadlines(string $priority, string $category = 'all', ?string $startTime = null): array
    {
        $policy = $this->findPolicy($priority, $category);
        $baseTimestamp = $startTime ? strtotime($startTime) : time();

        $responseDue = date('Y-m-d H:i:s', $baseTimestamp + ($policy['response_time_hours'] * 3600));
        $resolutionDue = date('Y-m-d H:i:s', $baseTimestamp + ($policy['resolution_time_hours'] * 3600));

        return [
            'policy_id'             => $policy['id'],
            'policy_name'           => $policy['policy_name'],
            'response_time_hours'   => $policy['response_time_hours'],
            'resolution_time_hours' => $policy['resolution_time_hours'],
            'response_due_at'       => $responseDue,
            'resolution_due_at'     => $resolutionDue,
        ];
    }

    /**
     * Evaluate SLA compliance and breach statuses for a complaint
     */
    public function evaluateBreaches(array $complaint, ?string $currentTime = null): array
    {
        $now = $currentTime ? strtotime($currentTime) : time();

        $responseDue = !empty($complaint['response_due_at']) ? strtotime($complaint['response_due_at']) : null;
        $resolutionDue = !empty($complaint['resolution_due_at']) ? strtotime($complaint['resolution_due_at']) : null;
        $firstRespondedAt = !empty($complaint['first_responded_at']) ? strtotime($complaint['first_responded_at']) : null;
        $resolvedAt = !empty($complaint['resolved_at']) ? strtotime($complaint['resolved_at']) : null;
        $status = strtolower($complaint['status'] ?? 'open');

        // Response SLA evaluation:
        // Breached if:
        // a) not yet responded and now > responseDue, OR
        // b) first response timestamp occurred after responseDue
        $isResponseBreached = false;
        if ($responseDue) {
            if ($firstRespondedAt) {
                $isResponseBreached = $firstRespondedAt > $responseDue;
            } elseif ($status === 'open' && $now > $responseDue) {
                $isResponseBreached = true;
            }
        }

        // Resolution SLA evaluation:
        // Breached if:
        // a) resolved and resolvedAt > resolutionDue, OR
        // b) not resolved/closed and now > resolutionDue
        $isResolutionBreached = false;
        if ($resolutionDue) {
            if ($resolvedAt) {
                $isResolutionBreached = $resolvedAt > $resolutionDue;
            } elseif (!in_array($status, ['resolved', 'closed'], true) && $now > $resolutionDue) {
                $isResolutionBreached = true;
            }
        }

        // Remaining / Overdue time calculation
        $responseRemainingSeconds = $responseDue ? ($responseDue - $now) : 0;
        $resolutionRemainingSeconds = $resolutionDue ? ($resolutionDue - $now) : 0;

        return [
            'is_response_breached'           => $isResponseBreached,
            'is_resolution_breached'         => $isResolutionBreached,
            'has_any_breach'                 => ($isResponseBreached || $isResolutionBreached),
            'response_remaining_seconds'     => $responseRemainingSeconds,
            'resolution_remaining_seconds'   => $resolutionRemainingSeconds,
            'response_overdue_hours'         => $responseRemainingSeconds < 0 ? abs(round($responseRemainingSeconds / 3600, 1)) : 0,
            'resolution_overdue_hours'       => $resolutionRemainingSeconds < 0 ? abs(round($resolutionRemainingSeconds / 3600, 1)) : 0,
        ];
    }

    /**
     * Get HTML Badge or summary tag for SLA Status
     */
    public function formatSlaBadge(array $complaint): string
    {
        $eval = $this->evaluateBreaches($complaint);
        $status = strtolower($complaint['status'] ?? 'open');

        if ($status === 'resolved' || $status === 'closed') {
            if ($eval['is_resolution_breached']) {
                return '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Resolved (SLA Breached)</span>';
            }
            return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Resolved in SLA</span>';
        }

        if ($eval['is_resolution_breached']) {
            return '<span class="badge bg-danger"><i class="bi bi-fire me-1"></i>Resolution SLA Overdue (' . $eval['resolution_overdue_hours'] . 'h)</span>';
        }

        if ($eval['is_response_breached']) {
            return '<span class="badge bg-danger"><i class="bi bi-clock-history me-1"></i>Response SLA Overdue</span>';
        }

        if ($eval['resolution_remaining_seconds'] < 7200) { // Under 2 hours remaining
            $mins = max(1, round($eval['resolution_remaining_seconds'] / 60));
            return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Due Soon (' . $mins . 'm)</span>';
        }

        $hours = round($eval['resolution_remaining_seconds'] / 3600, 1);
        return '<span class="badge bg-info text-dark"><i class="bi bi-shield-check me-1"></i>SLA OK (~' . $hours . 'h left)</span>';
    }
}
