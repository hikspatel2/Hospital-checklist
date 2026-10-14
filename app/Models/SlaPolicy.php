<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * SLA Policy Model
 * Manages category and priority based response/resolution SLA targets.
 */
class SlaPolicy extends BaseModel
{
    protected string $table = 'sla_policies';

    /**
     * Get all active policies grouped by category or ordered
     */
    public function getActivePolicies(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query("
            SELECT * FROM `sla_policies` 
            WHERE `is_active` = 1 
            ORDER BY 
                CASE `category` WHEN 'all' THEN 99 ELSE 1 END ASC,
                `category` ASC,
                CASE `priority` 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    ELSE 4 
                END ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Save or update an SLA policy
     */
    public function savePolicy(array $data): int
    {
        $pdo = Database::getConnection();
        $id = !empty($data['id']) ? (int)$data['id'] : 0;

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE `sla_policies`
                SET `policy_name` = :name,
                    `category` = :category,
                    `priority` = :priority,
                    `response_time_hours` = :resp,
                    `resolution_time_hours` = :resol,
                    `escalation_email` = :email,
                    `is_active` = :active,
                    `updated_at` = CURRENT_TIMESTAMP
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':name'     => trim($data['policy_name']),
                ':category' => trim($data['category'] ?? 'all'),
                ':priority' => trim($data['priority']),
                ':resp'     => (int)$data['response_time_hours'],
                ':resol'    => (int)$data['resolution_time_hours'],
                ':email'    => !empty($data['escalation_email']) ? trim($data['escalation_email']) : null,
                ':active'   => !empty($data['is_active']) ? 1 : 0,
                ':id'       => $id,
            ]);
            return $id;
        }

        $stmt = $pdo->prepare("
            INSERT INTO `sla_policies` (
                `policy_name`, `category`, `priority`, `response_time_hours`,
                `resolution_time_hours`, `escalation_email`, `is_active`
            ) VALUES (
                :name, :category, :priority, :resp, :resol, :email, :active
            )
            ON DUPLICATE KEY UPDATE
                `policy_name` = VALUES(`policy_name`),
                `response_time_hours` = VALUES(`response_time_hours`),
                `resolution_time_hours` = VALUES(`resolution_time_hours`),
                `escalation_email` = VALUES(`escalation_email`),
                `is_active` = VALUES(`is_active`)
        ");
        $stmt->execute([
            ':name'     => trim($data['policy_name']),
            ':category' => trim($data['category'] ?? 'all'),
            ':priority' => trim($data['priority']),
            ':resp'     => (int)$data['response_time_hours'],
            ':resol'    => (int)$data['resolution_time_hours'],
            ':email'    => !empty($data['escalation_email']) ? trim($data['escalation_email']) : null,
            ':active'   => !empty($data['is_active']) ? 1 : 0,
        ]);

        return (int)$pdo->lastInsertId() ?: $id;
    }
}
