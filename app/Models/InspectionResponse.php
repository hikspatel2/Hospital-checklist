<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * InspectionResponse Model
 * Historical submitted answers preserving item label snapshots and compliance status.
 */
class InspectionResponse extends BaseModel
{
    protected string $table = 'inspection_responses';

    /**
     * Get all responses for an inspection with item metadata
     */
    public function getByInspection(int $inspectionId): array
    {
        $sql = "SELECT ir.*, ci.item_type, ci.is_required, ci.severity
                FROM `{$this->table}` ir
                JOIN `checklist_items` ci ON ir.checklist_item_id = ci.id
                WHERE ir.inspection_id = :id
                ORDER BY ci.display_order ASC, ir.id ASC";
        return Database::selectAll($sql, ['id' => $inspectionId]);
    }
}
