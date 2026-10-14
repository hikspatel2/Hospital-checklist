<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

/**
 * Supervisor Facility Controller
 * Enforces strict ownership: supervisors can only view their assigned wards, floors, and shifts.
 */
class SupervisorController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Assigned Work & Facility Overview
     */
    public function overview(Request $request, ?string $id = null): Response
    {
        // If an explicit ID is requested in URL, enforce strict horizontal authorization
        $targetSupervisorId = ($id !== null && is_numeric($id)) ? (int)$id : (int)Auth::id();

        // Enforce IDOR protection: will throw 403 if supervisor tries to view someone else's ID
        Auth::authorizeSupervisorAccess($targetSupervisorId);

        $supervisor = $this->userModel->find($targetSupervisorId);
        if (!$supervisor) {
            $this->flash('error', 'Supervisor record not found.');
            $this->redirect('/dashboard');
        }

        // Fetch assigned facility entities
        $assignments = $this->userModel->getSupervisorAssignments($targetSupervisorId);

        // Fetch current active shift
        $activeShift = $this->userModel->getActiveShift($targetSupervisorId);

        // Extract assigned ward IDs for scoped data loading
        $assignedWardIds = array_filter(array_column($assignments, 'ward_id'));

        $recentInspections = [];
        $activeComplaints = [];

        if (!empty($assignedWardIds)) {
            $wardPlaceholders = implode(',', array_fill(0, count($assignedWardIds), '?'));

            // Inspections scoped exclusively to supervisor's assigned wards
            $inspSql = "SELECT i.*, w.ward_name, b.code AS building_code, f.floor_name
                        FROM `inspections` i
                        JOIN `wards` w ON i.ward_id = w.id
                        JOIN `buildings` b ON i.building_id = b.id
                        JOIN `floors` f ON i.floor_id = f.id
                        WHERE i.ward_id IN ({$wardPlaceholders})
                          AND i.deleted_at IS NULL
                        ORDER BY i.created_at DESC
                        LIMIT 5";

            $recentInspections = Database::select($inspSql, array_values($assignedWardIds));

            // Complaints scoped exclusively to supervisor's assigned wards
            $compSql = "SELECT c.*, w.ward_name, b.code AS building_code, s.policy_name
                        FROM `complaints` c
                        JOIN `wards` w ON c.ward_id = w.id
                        JOIN `buildings` b ON c.building_id = b.id
                        LEFT JOIN `sla_policies` s ON c.sla_policy_id = s.id
                        WHERE c.ward_id IN ({$wardPlaceholders})
                          AND c.deleted_at IS NULL
                          AND c.status NOT IN ('resolved', 'closed')
                        ORDER BY c.priority = 'critical' DESC, c.due_date ASC
                        LIMIT 5";

            $activeComplaints = Database::select($compSql, array_values($assignedWardIds));
        }

        return $this->render('supervisor/overview', [
            'supervisor' => $supervisor,
            'assignments' => $assignments,
            'activeShift' => $activeShift,
            'recentInspections' => $recentInspections,
            'activeComplaints' => $activeComplaints,
            'pageTitle' => 'Supervisor Assigned Work Overview',
        ]);
    }
}
