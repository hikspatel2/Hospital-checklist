<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShiftAssignment;

/**
 * ShiftController
 * Administers hospital work shifts, overnight shift detection, and supervisor shift assignments.
 */
class ShiftController extends Controller
{
    private Shift $shiftModel;
    private UserShiftAssignment $assignmentModel;
    private User $userModel;
    private Building $buildingModel;

    public function __construct()
    {
        $this->shiftModel = new Shift();
        $this->assignmentModel = new UserShiftAssignment();
        $this->userModel = new User();
        $this->buildingModel = new Building();
    }

    /**
     * List all hospital shifts
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status', '');
        $shifts = $this->shiftModel->getAllWithStats($status !== '' ? (string)$status : null);

        return $this->render('shifts/index', [
            'shifts'        => $shifts,
            'currentStatus' => $status,
            'pageTitle'     => 'Work Shifts & Rosters',
        ]);
    }

    /**
     * Store new shift
     */
    public function store(Request $request): void
    {
        $post = $request->post();
        $code = strtoupper(trim((string)($post['shift_code'] ?? '')));
        $name = trim((string)($post['shift_name'] ?? ''));
        $description = trim((string)($post['description'] ?? ''));
        $startTime = trim((string)($post['start_time'] ?? ''));
        $endTime = trim((string)($post['end_time'] ?? ''));
        $graceMinutes = max(0, (int)($post['grace_period_minutes'] ?? 15));

        if (empty($code) || empty($name) || empty($startTime) || empty($endTime)) {
            Session::setFlash('error', 'Shift code, name, start time, and end time are required.');
            $this->redirect('/shifts');
            return;
        }

        if ($this->shiftModel->isCodeTaken($code)) {
            Session::setFlash('error', "Shift code '{$code}' already exists.");
            $this->redirect('/shifts');
            return;
        }

        // Automatic overnight detection: if end_time < start_time, it is overnight
        $isOvernight = Shift::isOvernightShift($startTime, $endTime) ? 1 : (isset($post['is_overnight']) ? 1 : 0);

        $shiftId = $this->shiftModel->create([
            'shift_code'           => $code,
            'shift_name'           => $name,
            'description'          => $description,
            'start_time'           => $startTime,
            'end_time'             => $endTime,
            'grace_period_minutes' => $graceMinutes,
            'is_overnight'         => $isOvernight,
            'status'               => 'active',
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Created hospital shift {$code} ({$name}) [{$startTime} to {$endTime}]" . ($isOvernight ? ' (Overnight)' : ''),
            Auth::id(),
            ['shift_id' => $shiftId, 'code' => $code]
        );

        Session::setFlash('success', "Shift '{$name}' ({$code}) created successfully.");
        $this->redirect('/shifts');
    }

    /**
     * Update existing shift
     */
    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            Session::setFlash('error', 'Shift record not found.');
            $this->redirect('/shifts');
            return;
        }

        $post = $request->post();
        $code = strtoupper(trim((string)($post['shift_code'] ?? '')));
        $name = trim((string)($post['shift_name'] ?? ''));
        $description = trim((string)($post['description'] ?? ''));
        $startTime = trim((string)($post['start_time'] ?? ''));
        $endTime = trim((string)($post['end_time'] ?? ''));
        $graceMinutes = max(0, (int)($post['grace_period_minutes'] ?? 15));

        if (empty($code) || empty($name) || empty($startTime) || empty($endTime)) {
            Session::setFlash('error', 'All shift parameters are required.');
            $this->redirect('/shifts');
            return;
        }

        if ($this->shiftModel->isCodeTaken($code, $id)) {
            Session::setFlash('error', "Shift code '{$code}' is already used by another shift.");
            $this->redirect('/shifts');
            return;
        }

        $isOvernight = Shift::isOvernightShift($startTime, $endTime) ? 1 : (isset($post['is_overnight']) ? 1 : 0);

        $this->shiftModel->update($id, [
            'shift_code'           => $code,
            'shift_name'           => $name,
            'description'          => $description,
            'start_time'           => $startTime,
            'end_time'             => $endTime,
            'grace_period_minutes' => $graceMinutes,
            'is_overnight'         => $isOvernight,
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Updated shift #{$id} parameters",
            Auth::id(),
            ['shift_id' => $id]
        );

        Session::setFlash('success', 'Shift updated successfully.');
        $this->redirect('/shifts');
    }

    /**
     * Toggle shift active/inactive status with safe historical deactivation
     */
    public function toggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            Session::setFlash('error', 'Shift not found.');
            $this->redirect('/shifts');
            return;
        }

        $newStatus = ($shift['status'] === 'active') ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $this->shiftModel->hasAttendanceRecords($id)) {
            $this->shiftModel->safeDeactivate($id, (int)Auth::id(), 'Deactivated via admin console; historical attendance logs preserved');
            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Safely deactivated shift {$shift['shift_code']} preserving historical attendance",
                Auth::id(),
                ['shift_id' => $id]
            );
            Session::setFlash('success', "Shift '{$shift['shift_name']}' safely deactivated. Historical records preserved.");
            $this->redirect('/shifts');
            return;
        }

        $this->shiftModel->update($id, ['status' => $newStatus]);
        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed shift {$shift['shift_code']} status to {$newStatus}",
            Auth::id(),
            ['shift_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Shift status changed to {$newStatus}.");
        $this->redirect('/shifts');
    }

    // =========================================================================
    // SUPERVISOR SHIFT ASSIGNMENTS
    // =========================================================================

    /**
     * List all supervisor shift allocations
     */
    public function assignmentsIndex(Request $request): Response
    {
        $filters = [
            'user_id'  => $request->query('user_id', ''),
            'shift_id' => $request->query('shift_id', ''),
            'status'   => $request->query('status', 'active'),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->assignmentModel->getAssignmentsWithDetails($filters, $page, 15);
        $supervisors = $this->userModel->getByRole('supervisor');
        $shifts = $this->shiftModel->getAllWithStats('active');
        $buildings = $this->buildingModel->getAllWithStats('active');

        return $this->render('shifts/assignments', [
            'assignments' => $pagination['data'],
            'pagination'  => $pagination,
            'filters'     => $filters,
            'supervisors' => $supervisors,
            'shifts'      => $shifts,
            'buildings'   => $buildings,
            'pageTitle'   => 'Supervisor Shift Rosters',
        ]);
    }

    /**
     * Store new supervisor shift assignment with overlap check
     */
    public function assignmentsStore(Request $request): void
    {
        $post = $request->post();
        $userId = (int)($post['user_id'] ?? 0);
        $shiftId = (int)($post['shift_id'] ?? 0);
        $effectiveFrom = trim((string)($post['effective_from'] ?? date('Y-m-d')));
        $effectiveTo = !empty($post['effective_to']) ? trim((string)$post['effective_to']) : null;
        $buildingId = !empty($post['building_id']) ? (int)$post['building_id'] : null;
        $floorId = !empty($post['floor_id']) ? (int)$post['floor_id'] : null;
        $wardId = !empty($post['ward_id']) ? (int)$post['ward_id'] : null;

        try {
            $assignmentId = $this->assignmentModel->createAssignment([
                'user_id'        => $userId,
                'shift_id'       => $shiftId,
                'building_id'    => $buildingId,
                'floor_id'       => $floorId,
                'ward_id'        => $wardId,
                'effective_from' => $effectiveFrom,
                'effective_to'   => $effectiveTo,
            ]);

            AuditLog::log(
                AuditLog::EVENT_USER_UPDATED,
                "Assigned supervisor #{$userId} to shift #{$shiftId} from {$effectiveFrom} to " . ($effectiveTo ?? 'indefinite'),
                Auth::id(),
                ['assignment_id' => $assignmentId]
            );

            Session::setFlash('success', 'Supervisor assigned to shift successfully.');
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/shift-assignments');
    }

    /**
     * Revoke/toggle supervisor shift assignment
     */
    public function assignmentsToggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $assignment = $this->assignmentModel->find($id);

        if (!$assignment) {
            Session::setFlash('error', 'Shift assignment record not found.');
            $this->redirect('/shift-assignments');
            return;
        }

        $newStatus = ($assignment['status'] === 'active') ? 'inactive' : 'active';
        $this->assignmentModel->update($id, ['status' => $newStatus]);

        AuditLog::log(
            AuditLog::EVENT_USER_UPDATED,
            "Changed supervisor shift assignment #{$id} status to {$newStatus}",
            Auth::id(),
            ['assignment_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Shift assignment status changed to {$newStatus}.");
        $this->redirect('/shift-assignments');
    }
}
