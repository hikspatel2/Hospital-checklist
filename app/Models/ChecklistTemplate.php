<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * ChecklistTemplate Model
 * Manages hospital inspection checklist templates, categories, and facility scoping.
 */
class ChecklistTemplate extends BaseModel
{
    protected string $table = 'checklist_templates';

    public const CATEGORIES = [
        'hygiene'           => 'Hygiene & Cleanliness',
        'safety'            => 'Patient & Workplace Safety',
        'equipment'         => 'Medical & Bio-Medical Equipment',
        'infection_control' => 'Infection Control Protocol',
        'facilities'        => 'Civil & Environmental Facilities',
        'general'           => 'General Hospital Administration',
    ];

    /**
     * Get paginated templates with active version number, item counts, and facility details
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["ct.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['category'])) {
            $conditions[] = "ct.category = :category";
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "ct.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(ct.template_name LIKE :search OR ct.template_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` ct WHERE {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT ct.*,
                       u.full_name as created_by_name,
                       ctv.id as current_version_id,
                       ctv.version_number as current_version_number,
                       ctv.changelog as version_changelog,
                       b.building_name, f.floor_name, f.floor_number, w.ward_name, loc.location_name,
                       (SELECT COUNT(*) FROM `checklist_items` ci WHERE ci.template_version_id = ctv.id) as item_count,
                       (SELECT COUNT(*) FROM `inspections` i WHERE i.template_version_id = ctv.id) as inspection_count
                FROM `{$this->table}` ct
                LEFT JOIN `checklist_template_versions` ctv ON ctv.template_id = ct.id AND ctv.is_current = 1
                LEFT JOIN `users` u ON ct.created_by = u.id
                LEFT JOIN `buildings` b ON ct.target_building_id = b.id
                LEFT JOIN `floors` f ON ct.target_floor_id = f.id
                LEFT JOIN `wards` w ON ct.target_ward_id = w.id
                LEFT JOIN `inspection_locations` loc ON ct.target_location_id = loc.id
                WHERE {$whereClause}
                ORDER BY ct.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::selectAll($sql, $params);

        return [
            'data' => $records,
            'pagination' => [
                'total_records' => $totalRecords,
                'total_pages'   => $totalPages,
                'current_page'  => $page,
                'per_page'      => $perPage,
                'has_previous'  => $page > 1,
                'has_next'      => $page < $totalPages,
            ]
        ];
    }

    /**
     * Check if a template code is already taken
     */
    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `{$this->table}` WHERE `template_code` = :code AND `deleted_at` IS NULL";
        $params = ['code' => strtoupper($code)];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $row = Database::selectOne($sql, $params);
        return $row !== null;
    }

    /**
     * Check if template has historical inspection records
     */
    public function hasHistoricalInspections(int $templateId): bool
    {
        $sql = "SELECT COUNT(*) as cnt 
                FROM `inspections` i
                JOIN `checklist_template_versions` ctv ON i.template_version_id = ctv.id
                WHERE ctv.template_id = :tid";
        $row = Database::selectOne($sql, ['tid' => $templateId]);
        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    /**
     * Safe deactivation preserving all historical inspection records
     */
    public function safeDeactivate(int $templateId, int $userId, string $reason): bool
    {
        return $this->update($templateId, [
            'status'          => 'archived',
            'deleted_by'      => $userId,
            'deletion_reason' => $reason,
        ]);
    }
}
