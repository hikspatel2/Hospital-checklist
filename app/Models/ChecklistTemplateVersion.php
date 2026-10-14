<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * ChecklistTemplateVersion Model
 * Immutable snapshot versions of hospital checklist templates.
 */
class ChecklistTemplateVersion extends BaseModel
{
    protected string $table = 'checklist_template_versions';

    /**
     * Get all items belonging to a specific template version
     */
    public function getItems(int $versionId): array
    {
        $sql = "SELECT * FROM `checklist_items` 
                WHERE `template_version_id` = :vid 
                ORDER BY `display_order` ASC, `id` ASC";
        return Database::selectAll($sql, ['vid' => $versionId]);
    }

    /**
     * Get full details of a version including parent template
     */
    public function getWithTemplate(int $versionId): ?array
    {
        $sql = "SELECT ctv.*, ct.template_name, ct.template_code, ct.category, ct.status as template_status,
                       ct.target_building_id, ct.target_floor_id, ct.target_ward_id, ct.target_location_id,
                       u.full_name as created_by_name
                FROM `{$this->table}` ctv
                JOIN `checklist_templates` ct ON ctv.template_id = ct.id
                LEFT JOIN `users` u ON ctv.created_by = u.id
                WHERE ctv.id = :vid";
        return Database::selectOne($sql, ['vid' => $versionId]);
    }
}
