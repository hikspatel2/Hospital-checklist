<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Complaint Assignment Model
 * Tracks immutable historical assignments and reassignments of complaints.
 */
class ComplaintAssignment extends BaseModel
{
    protected string $table = 'complaint_assignments';

    /**
     * Get assignment history for a complaint
     */
    public function getHistory(int $complaintId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                ca.*,
                u_to.name AS assigned_to_name,
                u_by.name AS assigned_by_name
            FROM `complaint_assignments` ca
            JOIN `users` u_to ON ca.assigned_to_user_id = u_to.id
            JOIN `users` u_by ON ca.assigned_by_user_id = u_by.id
            WHERE ca.complaint_id = :id
            ORDER BY ca.assigned_at DESC
        ");
        $stmt->execute([':id' => $complaintId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
