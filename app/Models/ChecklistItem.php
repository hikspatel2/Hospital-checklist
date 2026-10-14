<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * ChecklistItem Model
 * Individual inspection questions/checkpoints within an immutable template version snapshot.
 */
class ChecklistItem extends BaseModel
{
    protected string $table = 'checklist_items';

    public const ITEM_TYPES = [
        'pass_fail'       => 'Pass / Fail',
        'yes_no_na'       => 'Yes / No / N/A',
        'numeric'         => 'Numeric Value',
        'text'            => 'Descriptive Text',
        'multiple_choice' => 'Multiple Choice',
    ];

    public const SEVERITIES = [
        'low'      => 'Low Impact',
        'medium'   => 'Medium / Standard',
        'high'     => 'High Risk',
        'critical' => 'Critical Patient Safety',
    ];

    /**
     * Reorder items within a version
     */
    public function reorder(int $versionId, array $orderedIds): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `{$this->table}` SET `display_order` = :order WHERE `id` = :id AND `template_version_id` = :vid");
        foreach ($orderedIds as $index => $id) {
            $stmt->execute([
                'order' => $index + 1,
                'id'    => (int)$id,
                'vid'   => $versionId,
            ]);
        }
        return true;
    }
}
