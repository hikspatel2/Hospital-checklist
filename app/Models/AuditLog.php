<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

/**
 * Append-only Audit Log Model
 * Tracks all security events, facility alterations, inspection lifecycles, and report exports.
 * Implements strict immutability with zero edit/delete interfaces and automated sensitive key redaction.
 */
class AuditLog extends BaseModel
{
    protected string $table = 'audit_logs';

    // Standard Audit Event Constants
    public const EVENT_FACILITY_CREATED     = 'FACILITY_CREATED';
    public const EVENT_FACILITY_UPDATED     = 'FACILITY_UPDATED';
    public const EVENT_USER_CREATED         = 'USER_CREATED';
    public const EVENT_USER_UPDATED         = 'USER_UPDATED';
    public const EVENT_USER_PASSWORD_CHANGE = 'USER_PASSWORD_CHANGE';
    public const EVENT_USER_STATUS_CHANGE   = 'USER_STATUS_CHANGE';
    public const EVENT_INSPECTION_COMPLETED = 'INSPECTION_COMPLETED';
    public const EVENT_SUSPICIOUS_ACTIVITY  = 'SUSPICIOUS_ACTIVITY';
    public const EVENT_REPORT_EXPORTED      = 'REPORT_EXPORTED';
    public const EVENT_LOGIN_SUCCESS        = 'LOGIN_SUCCESS';
    public const EVENT_LOGIN_FAILURE        = 'LOGIN_FAILURE';
    public const EVENT_LOGIN_LOCKED_ATTEMPT = 'LOGIN_LOCKED_ATTEMPT';
    public const EVENT_LOGOUT               = 'LOGOUT';

    private static array $sensitiveKeys = [
        'password', 'password_hash', 'current_password', 'new_password',
        'confirm_password', 'secret', 'token', 'csrf_token', 'api_key', 'key',
        'app_key', 'auth_token', 'cookie', 'session', 'session_id', 'remember_token'
    ];

    /**
     * Record an audit trail event (Primary method)
     */
    public static function record(
        ?int $userId,
        string $action,
        string $table,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $ip = '',
        string $userAgent = ''
    ): void {
        try {
            $cleanOld = $oldValues !== null ? json_encode(self::sanitize($oldValues), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
            $cleanNew = $newValues !== null ? json_encode(self::sanitize($newValues), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

            $sql = "INSERT INTO `audit_logs` 
                    (`user_id`, `event_action`, `entity_table`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`)
                    VALUES (:user_id, :event_action, :entity_table, :entity_id, :old_values, :new_values, :ip_address, :user_agent, NOW())";

            Database::executeStatement($sql, [
                'user_id'      => $userId,
                'event_action' => substr($action, 0, 100),
                'entity_table' => substr($table, 0, 100),
                'entity_id'    => $entityId,
                'old_values'   => $cleanOld,
                'new_values'   => $cleanNew,
                'ip_address'   => $ip !== '' ? substr($ip, 0, 45) : substr($_SERVER['REMOTE_ADDR'] ?? 'CLI', 0, 45),
                'user_agent'   => $userAgent !== '' ? substr($userAgent, 0, 500) : substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Fail open on audit log insert errors to prevent crashing core transaction, but log emergency file entry
            Logger::emergency("Failed to record audit log: {$e->getMessage()}", [
                'action'    => $action,
                'table'     => $table,
                'entity_id' => $entityId,
            ]);
        }
    }

    /**
     * Versatile logging alias supporting multiple controller call conventions
     */
    public static function log(
        string $action,
        string $arg2 = '',
        ?int $arg3 = null,
        ?array $arg4 = null,
        ?array $arg5 = null
    ): void {
        $knownTables = [
            'complaints', 'sla_policies', 'users', 'inspections', 'inspection_schedules',
            'buildings', 'floors', 'wards', 'shifts', 'schedules', 'audit_logs',
            'attendance', 'checklists', 'checklist_items', 'qr_tokens', 'reports'
        ];

        // Format A: log($action, $table, $entityId, $oldValues, $newValues)
        if (in_array(strtolower($arg2), $knownTables, true) || $arg5 !== null) {
            $userId    = \App\Core\Auth::id();
            $table     = $arg2;
            $entityId  = $arg3;
            $oldValues = $arg4;
            $newValues = $arg5;
        } else {
            // Format B: log($action, $description, $userId, $detailsArray)
            $userId = $arg3 ?? \App\Core\Auth::id();
            $table  = 'system';
            $entityId = null;

            if (is_array($arg4)) {
                foreach (['id', 'entity_id', 'building_id', 'floor_id', 'ward_id', 'inspection_id', 'schedule_id', 'user_id', 'attendance_id'] as $k) {
                    if (isset($arg4[$k]) && is_numeric($arg4[$k])) {
                        $entityId = (int)$arg4[$k];
                        break;
                    }
                }
            }

            $oldValues = null;
            $newValues = is_array($arg4) ? array_merge(['description' => $arg2], $arg4) : ['description' => $arg2];
        }

        self::record($userId, $action, $table, $entityId, $oldValues, $newValues);
    }

    /**
     * Get paginated and filtered audit log entries for Admin review
     */
    public static function getFiltered(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['event_action'])) {
            $conditions[] = "a.event_action = :event_action";
            $params['event_action'] = $filters['event_action'];
        }

        if (!empty($filters['entity_table'])) {
            $conditions[] = "a.entity_table = :entity_table";
            $params['entity_table'] = $filters['entity_table'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = "a.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(a.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(a.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(a.event_action LIKE :search OR a.entity_table LIKE :search OR a.ip_address LIKE :search OR u.name LIKE :search OR u.username LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total matching
        $countSql = "SELECT COUNT(*) FROM `audit_logs` a LEFT JOIN `users` u ON a.user_id = u.id WHERE {$whereClause}";
        $total = (int)Database::selectValue($countSql, $params);

        // Fetch paginated records
        $sql = "SELECT a.*, u.name as user_name, u.username, u.role as user_role
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.user_id = u.id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::selectAll($sql, $params);

        return [
            'data'      => $records,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Get summary metrics for the Audit Log Console
     */
    public static function getSummaryMetrics(): array
    {
        $today = date('Y-m-d');

        $totalLogs = (int)Database::selectValue("SELECT COUNT(*) FROM `audit_logs`");
        $todayLogs = (int)Database::selectValue("SELECT COUNT(*) FROM `audit_logs` WHERE DATE(created_at) = :today", ['today' => $today]);
        
        $suspiciousCount = (int)Database::selectValue(
            "SELECT COUNT(*) FROM `audit_logs` WHERE event_action IN (:act1, :act2, :act3)",
            [
                'act1' => self::EVENT_SUSPICIOUS_ACTIVITY,
                'act2' => self::EVENT_LOGIN_FAILURE,
                'act3' => self::EVENT_LOGIN_LOCKED_ATTEMPT,
            ]
        );

        $exportCount = (int)Database::selectValue(
            "SELECT COUNT(*) FROM `audit_logs` WHERE event_action = :act",
            ['act' => self::EVENT_REPORT_EXPORTED]
        );

        $distinctActors = (int)Database::selectValue(
            "SELECT COUNT(DISTINCT user_id) FROM `audit_logs` WHERE user_id IS NOT NULL"
        );

        return [
            'total_logs'       => $totalLogs,
            'today_logs'       => $todayLogs,
            'security_alerts'  => $suspiciousCount,
            'report_exports'   => $exportCount,
            'distinct_actors'  => $distinctActors,
        ];
    }

    /**
     * Sanitize array to ensure passwords, tokens, and secrets are never stored in plain text
     */
    public static function sanitize(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
            } elseif (is_string($key) && in_array(strtolower($key), self::$sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';
            } elseif (is_string($key) && (stripos($key, 'password') !== false || stripos($key, 'token') !== false || stripos($key, 'secret') !== false || stripos($key, 'key') !== false)) {
                $clean[$key] = '[REDACTED]';
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    public static function sanitizePayload(array $data): array
    {
        return self::sanitize($data);
    }
}
