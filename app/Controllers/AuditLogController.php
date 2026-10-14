<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;

/**
 * Append-Only Audit Trail Controller (Admin Exclusive)
 * Provides tamper-resistant inspection of all hospital security, clinical, and operational changes.
 * Strictly possesses ZERO update, modify, or delete capabilities.
 */
class AuditLogController extends Controller
{
    /**
     * Audit Log Review Console
     */
    public function index(Request $request): Response
    {
        $filters = [
            'event_action' => $request->query('event_action'),
            'entity_table' => $request->query('entity_table'),
            'user_id'      => $request->query('user_id'),
            'date_from'    => $request->query('date_from'),
            'date_to'      => $request->query('date_to'),
            'search'       => $request->query('search'),
        ];

        $page = (int)$request->query('page', 1);
        $paginated = AuditLog::getFiltered($filters, $page, 25);
        $metrics = AuditLog::getSummaryMetrics();

        // Get distinct event actions and tables for filter dropdowns
        $actions = Database::selectAll("SELECT DISTINCT event_action FROM `audit_logs` ORDER BY event_action ASC");
        $tables = Database::selectAll("SELECT DISTINCT entity_table FROM `audit_logs` ORDER BY entity_table ASC");
        $users = Database::selectAll("SELECT id, name, username FROM `users` ORDER BY name ASC");

        return $this->view('audit_logs/index', [
            'logs'      => $paginated['data'],
            'total'     => $paginated['total'],
            'page'      => $paginated['page'],
            'perPage'   => $paginated['per_page'],
            'lastPage'  => $paginated['last_page'],
            'filters'   => $filters,
            'metrics'   => $metrics,
            'actions'   => array_column($actions, 'event_action'),
            'tables'    => array_column($tables, 'entity_table'),
            'users'     => $users,
            'title'     => 'Security & Audit Trail Log',
        ]);
    }

    /**
     * AJAX/JSON single audit entry inspector
     */
    public function show(Request $request, string $id): Response
    {
        $logId = (int)$id;
        $sql = "SELECT a.*, u.name as user_name, u.username, u.role as user_role
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.user_id = u.id
                WHERE a.id = :id
                LIMIT 1";

        $log = Database::selectOne($sql, ['id' => $logId]);
        if (!$log) {
            return $this->json(['error' => 'Audit log record not found'], 404);
        }

        $oldValues = $log['old_values'] ? json_decode((string)$log['old_values'], true) : null;
        $newValues = $log['new_values'] ? json_decode((string)$log['new_values'], true) : null;

        return $this->json([
            'id'           => $log['id'],
            'user_name'    => $log['user_name'] ?? 'System / Anonymous',
            'username'     => $log['username'] ?? 'N/A',
            'user_role'    => $log['user_role'] ?? 'System',
            'event_action' => $log['event_action'],
            'entity_table' => $log['entity_table'],
            'entity_id'    => $log['entity_id'],
            'ip_address'   => $log['ip_address'],
            'user_agent'   => $log['user_agent'],
            'created_at'   => $log['created_at'],
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
        ]);
    }
}
