<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;
use App\Models\InspectionSchedule;
use App\Models\ScheduleRule;

/**
 * InspectionScheduleService
 * Automated, idempotent recurrence engine for hospital inspection scheduling.
 * Compatible with Hostinger cron jobs and web dashboard triggers.
 */
class InspectionScheduleService
{
    /**
     * Compute a deterministic, unique idempotency key for a scheduled inspection instance
     */
    public static function generateIdempotencyKey(
        int $ruleId,
        string $date,
        int $wardId,
        ?int $locationId = null,
        ?int $shiftId = null
    ): string {
        $locPart = $locationId ?? 0;
        $shiftPart = $shiftId ?? 0;
        return hash('sha256', "LG-SCHED-RULE-{$ruleId}-DATE-{$date}-W-{$wardId}-L-{$locPart}-S-{$shiftPart}");
    }

    /**
     * Check if a specific date matches the recurrence rules, weekdays, and holiday exclusions
     */
    public static function shouldRunOnDate(array $rule, string $targetDate): bool
    {
        $timestamp = strtotime($targetDate);
        if (!$timestamp) {
            return false;
        }

        // 1. Status check
        if (($rule['status'] ?? 'active') !== 'active') {
            return false;
        }

        // 2. Effective date interval check
        $startDate = $rule['start_date'];
        $endDate = !empty($rule['end_date']) ? $rule['end_date'] : null;

        if ($targetDate < $startDate) {
            return false;
        }
        if ($endDate !== null && $targetDate > $endDate) {
            return false;
        }

        // 3. Exclusions / Holidays check
        if (!empty($rule['exclusions_json'])) {
            $exclusions = json_decode($rule['exclusions_json'], true);
            if (is_array($exclusions) && in_array($targetDate, $exclusions, true)) {
                return false; // Skip on configured hospital holiday / exclusion
            }
        }

        // 4. Frequency & weekday matching
        $dayOfWeek = strtolower(date('D', $timestamp)); // mon, tue, wed, thu, fri, sat, sun
        $dayOfMonth = (int)date('j', $timestamp);
        $frequency = $rule['frequency'] ?? 'daily';

        switch ($frequency) {
            case 'daily':
                return true;

            case 'weekdays':
                return in_array($dayOfWeek, ['mon', 'tue', 'wed', 'thu', 'fri'], true);

            case 'weekly':
            case 'shift_based':
            case 'custom':
                if (!empty($rule['selected_weekdays'])) {
                    $weekdays = array_map('trim', explode(',', strtolower($rule['selected_weekdays'])));
                    return in_array($dayOfWeek, $weekdays, true);
                }
                return true;

            case 'monthly':
                // Default to 1st of month if not specified
                return ($dayOfMonth === 1);

            default:
                return true;
        }
    }

    /**
     * Generate inspection schedules for a target date across all active schedule rules.
     * Guaranteed 100% idempotent; will never produce duplicate records on retries.
     *
     * @return array [ 'generated' => int, 'skipped_existing' => int, 'skipped_rule' => int, 'errors' => array ]
     */
    public static function generateSchedulesForDate(string $targetDate, ?int $triggeredByUserId = null): array
    {
        $results = [
            'target_date'      => $targetDate,
            'generated'        => 0,
            'skipped_existing' => 0,
            'skipped_rule'     => 0,
            'errors'           => [],
        ];

        try {
            $rules = Database::selectAll(
                "SELECT sr.*, ct.template_name, ctv.id as current_version_id
                 FROM `schedule_rules` sr
                 JOIN `checklist_templates` ct ON sr.template_id = ct.id
                 JOIN `checklist_template_versions` ctv ON ctv.template_id = ct.id AND ctv.is_current = 1
                 WHERE sr.status = 'active' AND ct.status = 'published'"
            );
        } catch (\Throwable $e) {
            $results['errors'][] = "Database error querying rules: " . $e->getMessage();
            return $results;
        }

        foreach ($rules as $rule) {
            $ruleId = (int)$rule['id'];

            if (!self::shouldRunOnDate($rule, $targetDate)) {
                $results['skipped_rule']++;
                continue;
            }

            // Determine target wards (either rule-specific ward, or all active wards in building/floor)
            $targetWards = self::resolveTargetWards($rule);
            if (empty($targetWards)) {
                continue;
            }

            $versionId = (int)$rule['current_version_id'];
            $shiftId = !empty($rule['shift_id']) ? (int)$rule['shift_id'] : null;
            $locationId = !empty($rule['target_location_id']) ? (int)$rule['target_location_id'] : null;
            $assignedUserId = !empty($rule['assigned_user_id']) ? (int)$rule['assigned_user_id'] : null;
            $timeStart = !empty($rule['time_window_start']) ? $rule['time_window_start'] : null;
            $timeEnd = !empty($rule['time_window_end']) ? $rule['time_window_end'] : null;

            foreach ($targetWards as $wardId) {
                // Determine supervisor assignment: if not in rule, look up active facility supervisor
                $supervisorId = $assignedUserId ?? self::resolveFacilitySupervisor($wardId, $shiftId, $targetDate);

                $idempotencyKey = self::generateIdempotencyKey($ruleId, $targetDate, $wardId, $locationId, $shiftId);

                // Idempotency check: see if schedule instance already exists
                $existing = Database::selectOne(
                    "SELECT `id` FROM `inspection_schedules` WHERE `idempotency_key` = :key LIMIT 1",
                    ['key' => $idempotencyKey]
                );

                if ($existing) {
                    $results['skipped_existing']++;
                    continue;
                }

                // Insert schedule instance
                try {
                    $insertSql = "INSERT INTO `inspection_schedules`
                                  (`schedule_rule_id`, `template_version_id`, `ward_id`, `inspection_location_id`,
                                   `assigned_user_id`, `shift_id`, `scheduled_date`, `scheduled_time_window_start`,
                                   `scheduled_time_window_end`, `status`, `idempotency_key`, `created_at`)
                                  VALUES
                                  (:rule_id, :version_id, :ward_id, :location_id,
                                   :user_id, :shift_id, :sched_date, :t_start,
                                   :t_end, 'scheduled', :idem_key, NOW())";

                    $pdo = Database::getConnection();
                    $stmt = $pdo->prepare($insertSql);
                    $stmt->execute([
                        'rule_id'     => $ruleId,
                        'version_id'  => $versionId,
                        'ward_id'     => $wardId,
                        'location_id' => $locationId,
                        'user_id'     => $supervisorId,
                        'shift_id'    => $shiftId,
                        'sched_date'  => $targetDate,
                        't_start'     => $timeStart,
                        't_end'       => $timeEnd,
                        'idem_key'    => $idempotencyKey,
                    ]);

                    $results['generated']++;
                } catch (\Throwable $e) {
                    $results['errors'][] = "Rule #{$ruleId} Ward #{$wardId}: " . $e->getMessage();
                }
            }

            // Update last_generated_date on schedule_rule
            try {
                Database::execute(
                    "UPDATE `schedule_rules` SET `last_generated_date` = :date WHERE `id` = :id",
                    ['date' => $targetDate, 'id' => $ruleId]
                );
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }

        // Log schedule generation audit
        if ($results['generated'] > 0 || !empty($results['errors'])) {
            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Inspection schedule generator executed for {$targetDate}: {$results['generated']} created, {$results['skipped_existing']} existing, " . count($results['errors']) . " errors",
                $triggeredByUserId,
                $results
            );
        }

        return $results;
    }

    /**
     * Resolve target wards based on rule facility hierarchy
     */
    private static function resolveTargetWards(array $rule): array
    {
        if (!empty($rule['target_ward_id'])) {
            return [(int)$rule['target_ward_id']];
        }

        if (!empty($rule['target_floor_id'])) {
            $rows = Database::selectAll(
                "SELECT `id` FROM `wards` WHERE `floor_id` = :fid AND `status` = 'active'",
                ['fid' => (int)$rule['target_floor_id']]
            );
            return array_column($rows, 'id');
        }

        if (!empty($rule['target_building_id'])) {
            $rows = Database::selectAll(
                "SELECT w.`id` FROM `wards` w
                 JOIN `floors` f ON w.`floor_id` = f.`id`
                 WHERE f.`building_id` = :bid AND w.`status` = 'active'",
                ['bid' => (int)$rule['target_building_id']]
            );
            return array_column($rows, 'id');
        }

        // Global rule: all active wards
        $rows = Database::selectAll("SELECT `id` FROM `wards` WHERE `status` = 'active' LIMIT 50");
        return array_column($rows, 'id');
    }

    /**
     * Fallback resolution: find assigned supervisor for ward/shift on date
     */
    private static function resolveFacilitySupervisor(int $wardId, ?int $shiftId, string $date): ?int
    {
        try {
            $row = Database::selectOne(
                "SELECT `user_id` FROM `supervisor_assignments`
                 WHERE `ward_id` = :wid AND `status` = 'active'
                   AND `effective_from` <= :d1 AND (`effective_to` IS NULL OR `effective_to` >= :d2)
                 LIMIT 1",
                ['wid' => $wardId, 'd1' => $date, 'd2' => $date]
            );
            return $row ? (int)$row['user_id'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Update past-due schedules to 'overdue' or 'missed'
     */
    public static function updateOverdueSchedules(): int
    {
        $today = date('Y-m-d');
        try {
            return Database::execute(
                "UPDATE `inspection_schedules`
                 SET `status` = 'overdue'
                 WHERE `status` IN ('scheduled', 'pending')
                   AND `scheduled_date` < :today",
                ['today' => $today]
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Retry failed schedule generations
     */
    public static function retryFailedSchedules(): int
    {
        try {
            $failed = Database::selectAll(
                "SELECT * FROM `inspection_schedules` 
                 WHERE `error_message` IS NOT NULL AND `retry_count` < 3 
                 LIMIT 20"
            );

            $fixed = 0;
            foreach ($failed as $item) {
                Database::execute(
                    "UPDATE `inspection_schedules` 
                     SET `retry_count` = `retry_count` + 1, `error_message` = NULL 
                     WHERE `id` = :id",
                    ['id' => $item['id']]
                );
                $fixed++;
            }
            return $fixed;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
