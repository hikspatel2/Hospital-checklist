<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Complaint Evidence Model
 * Manages secure file attachment records linked to complaints or timeline updates.
 */
class ComplaintEvidence extends BaseModel
{
    protected string $table = 'complaint_evidence';

    /**
     * Fetch evidence item with complaint metadata
     */
    public function getWithComplaint(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                ce.*,
                c.ticket_number,
                c.reported_by,
                c.assigned_to_user_id,
                u.name AS uploader_name
            FROM `complaint_evidence` ce
            JOIN `complaints` c ON ce.complaint_id = c.id
            JOIN `users` u ON ce.uploaded_by = u.id
            WHERE ce.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
