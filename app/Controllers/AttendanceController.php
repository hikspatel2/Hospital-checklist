<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\InspectionLocation;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\Ward;
use App\Services\GeoLocationService;
use App\Services\QrCodeService;

/**
 * AttendanceController
 * Handles supervisor mobile QR + GPS attendance check-in, geofence verification,
 * duplicate defense, and administrative audit overrides.
 */
class AttendanceController extends Controller
{
    private Attendance $attendanceModel;
    private Shift $shiftModel;
    private UserShiftAssignment $userShiftModel;
    private SupervisorAssignment $supervisorAssignModel;
    private Ward $wardModel;
    private InspectionLocation $locationModel;
    private User $userModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->shiftModel = new Shift();
        $this->userShiftModel = new UserShiftAssignment();
        $this->supervisorAssignModel = new SupervisorAssignment();
        $this->wardModel = new Ward();
        $this->locationModel = new InspectionLocation();
        $this->userModel = new User();
    }

    /**
     * Supervisor Check-In Interface
     */
    public function checkinView(Request $request): Response
    {
        $userId = (int)Auth::id();
        $today = date('Y-m-d');

        // Look for today's active shift
        $activeShift = $this->userShiftModel->getActiveShiftForUserOnDate($userId, $today);
        $allActiveShifts = $this->shiftModel->getAllWithStats('active');

        // Check if user already checked in today
        $existingCheckIn = null;
        if ($activeShift) {
            $existingCheckIn = $this->attendanceModel->getActiveCheckIn($userId, (int)$activeShift['shift_id'], $today);
        }

        $campus = GeoLocationService::getCampusCoordinates();

        return $this->render('attendance/checkin', [
            'activeShift'      => $activeShift,
            'allActiveShifts'  => $allActiveShifts,
            'existingCheckIn'  => $existingCheckIn,
            'campus'           => $campus,
            'pageTitle'        => 'QR + GPS Attendance Verification',
        ]);
    }

    /**
     * Process Check-In API (POST /attendance/checkin)
     * Performs QR verification, supervisor assignment check, GPS Haversine verification,
     * and idempotent duplicate prevention.
     */
    public function processCheckin(Request $request): Response
    {
        $post = $request->post();
        $userId = (int)Auth::id();
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $qrToken = trim((string)($post['qr_token'] ?? ''));
        $shiftId = (int)($post['shift_id'] ?? 0);
        $lat = !empty($post['latitude']) ? (float)$post['latitude'] : null;
        $lon = !empty($post['longitude']) ? (float)$post['longitude'] : null;
        $accuracy = !empty($post['accuracy']) ? (float)$post['accuracy'] : 999.0;

        // 1. Validate Shift
        if ($shiftId <= 0) {
            // Try to find supervisor's active shift assignment
            $activeAssignedShift = $this->userShiftModel->getActiveShiftForUserOnDate($userId, $today);
            if ($activeAssignedShift) {
                $shiftId = (int)$activeAssignedShift['shift_id'];
            } else {
                return $this->json(['success' => false, 'error' => 'No active shift selected or assigned to your profile.'], 400);
            }
        }

        $shift = $this->shiftModel->find($shiftId);
        if (!$shift || $shift['status'] !== 'active') {
            return $this->json(['success' => false, 'error' => 'Selected shift is not active.'], 400);
        }

        // 2. Idempotency Check: if check-in already recorded for today, return gracefully
        $existingCheckIn = $this->attendanceModel->getActiveCheckIn($userId, $shiftId, $today);
        if ($existingCheckIn) {
            return $this->json([
                'success'       => true,
                'idempotent'    => true,
                'message'       => 'Check-in was already recorded for this shift.',
                'attendance_id' => (int)$existingCheckIn['id'],
                'check_in_time' => $existingCheckIn['check_in_time'],
                'status'        => $existingCheckIn['status'],
                'verification'  => $existingCheckIn['verification_status']
            ]);
        }

        // 3. QR Token Verification
        if (empty($qrToken)) {
            return $this->json(['success' => false, 'error' => 'QR Code token is required for physical on-site check-in.'], 400);
        }

        $tokenResult = QrCodeService::verifyToken($qrToken);
        if (!$tokenResult['valid']) {
            AuditLog::log(
                AuditLog::EVENT_SUSPICIOUS_ACTIVITY,
                "Rejected attendance check-in for user #{$userId}: {$tokenResult['error']}",
                $userId,
                ['qr_token' => substr($qrToken, 0, 10) . '...']
            );
            return $this->json(['success' => false, 'error' => $tokenResult['error']], 403);
        }

        $qrRecord = $tokenResult['token'];
        $entityType = $qrRecord['entity_type'];
        $entityId = (int)$qrRecord['entity_id'];

        $wardId = null;
        $locationId = null;
        $targetLat = null;
        $targetLon = null;
        $customRadius = null;

        if ($entityType === 'ward') {
            $wardId = $entityId;
            $ward = $this->wardModel->find($wardId);
            if ($ward) {
                $targetLat = !empty($ward['gps_latitude']) ? (float)$ward['gps_latitude'] : null;
                $targetLon = !empty($ward['gps_longitude']) ? (float)$ward['gps_longitude'] : null;
                $customRadius = !empty($ward['geofence_radius_meters']) ? (int)$ward['geofence_radius_meters'] : null;
            }
        } elseif ($entityType === 'location') {
            $locationId = $entityId;
            $loc = $this->locationModel->find($locationId);
            if ($loc) {
                $wardId = (int)$loc['ward_id'];
                $targetLat = !empty($loc['gps_latitude']) ? (float)$loc['gps_latitude'] : null;
                $targetLon = !empty($loc['gps_longitude']) ? (float)$loc['gps_longitude'] : null;
                $customRadius = !empty($loc['geofence_radius_meters']) ? (int)$loc['geofence_radius_meters'] : null;
            }
        }

        // 4. Authorization Check: Is supervisor assigned to this facility?
        $isAssigned = $this->supervisorAssignModel->isSupervisorAssigned($userId, null, null, $wardId, $today);
        if (!$isAssigned && !Auth::isAdmin()) {
            AuditLog::log(
                AuditLog::EVENT_SUSPICIOUS_ACTIVITY,
                "Supervisor #{$userId} attempted check-in to unauthorized ward #{$wardId}",
                $userId,
                ['ward_id' => $wardId, 'entity_type' => $entityType]
            );
            return $this->json([
                'success' => false,
                'error'   => 'Unauthorized: You are not assigned to this hospital ward or facility zone on today\'s roster.'
            ], 403);
        }

        // 5. GPS Geofence & Accuracy Verification
        if ($lat === null || $lon === null) {
            return $this->json([
                'success' => false,
                'error'   => 'GPS coordinates missing. Please grant location permissions in your browser to verify hospital presence.'
            ], 400);
        }

        $geoResult = GeoLocationService::verifyCoordinates(
            $lat,
            $lon,
            $accuracy,
            $targetLat,
            $targetLon,
            $customRadius
        );

        // 6. Shift Grace Period Evaluation
        $isLate = !Shift::isWithinGracePeriod($now, $shift['start_time'], (int)$shift['grace_period_minutes']);
        $attendanceStatus = $isLate ? 'late' : 'present';

        if ($geoResult['is_flagged']) {
            $attendanceStatus = 'flagged_review';
        }

        // 7. Save Attendance Record & Telemetry
        $recordResult = $this->attendanceModel->recordCheckIn([
            'user_id'                     => $userId,
            'shift_id'                    => $shiftId,
            'ward_id'                     => $wardId,
            'location_id'                 => $locationId,
            'qr_token_id'                 => (int)$qrRecord['id'],
            'attendance_date'             => $today,
            'check_in_time'               => $now,
            'status'                      => $attendanceStatus,
            'verification_status'         => $geoResult['verification_status'],
            'gps_latitude'                => $lat,
            'gps_longitude'               => $lon,
            'gps_accuracy_meters'         => $accuracy,
            'distance_to_hospital_meters' => $geoResult['distance_meters'],
            'is_within_geofence'          => $geoResult['is_within_geofence'] ? 1 : 0,
            'is_flagged'                  => $geoResult['is_flagged'] ? 1 : 0,
            'flag_reason'                 => $geoResult['flag_reason'],
            'notes'                       => $isLate ? "Checked in after grace period ({$shift['grace_period_minutes']} mins)" : null,
        ]);

        AuditLog::log(
            AuditLog::EVENT_INSPECTION_COMPLETED,
            "Recorded attendance check-in for user #{$userId} on shift {$shift['shift_code']} ({$attendanceStatus}, {$geoResult['verification_status']})",
            $userId,
            ['attendance_id' => $recordResult['attendance_id'], 'distance' => $geoResult['distance_meters']]
        );

        return $this->json([
            'success'          => true,
            'attendance_id'    => $recordResult['attendance_id'],
            'status'           => $attendanceStatus,
            'verification'     => $geoResult['verification_status'],
            'is_flagged'       => $geoResult['is_flagged'],
            'distance_meters'  => $geoResult['distance_meters'],
            'message'          => $geoResult['is_flagged']
                ? 'Check-in recorded with caution: ' . $geoResult['flag_reason']
                : 'Presence successfully verified via QR and hospital GPS geofence.'
        ]);
    }

    /**
     * Process Check-Out API (POST /attendance/checkout)
     */
    public function processCheckout(Request $request): Response
    {
        $userId = (int)Auth::id();
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $post = $request->post();
        $attendanceId = (int)($post['attendance_id'] ?? 0);

        if ($attendanceId <= 0) {
            // Find open check-in for today
            $active = Database::selectOne(
                "SELECT id FROM `attendance` WHERE `user_id` = :uid AND `attendance_date` = :date AND `check_out_time` IS NULL LIMIT 1",
                ['uid' => $userId, 'date' => $today]
            );
            if (!$active) {
                return $this->json(['success' => false, 'error' => 'No active check-in found to check out from.'], 400);
            }
            $attendanceId = (int)$active['id'];
        }

        $this->attendanceModel->recordCheckOut($attendanceId, $now);

        AuditLog::log(
            AuditLog::EVENT_INSPECTION_COMPLETED,
            "Supervisor #{$userId} checked out of attendance session #{$attendanceId}",
            $userId,
            ['attendance_id' => $attendanceId]
        );

        return $this->json([
            'success'        => true,
            'message'        => 'Checked out successfully. Have a great day!',
            'check_out_time' => $now
        ]);
    }

    /**
     * Admin Attendance Audit & Override Console
     */
    public function adminIndex(Request $request): Response
    {
        $filters = [
            'user_id'             => $request->query('user_id', ''),
            'shift_id'            => $request->query('shift_id', ''),
            'date'                => $request->query('date', ''),
            'status'              => $request->query('status', ''),
            'verification_status' => $request->query('verification_status', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->attendanceModel->getAllWithDetails($filters, $page, 15);
        $supervisors = $this->userModel->getByRole('supervisor');
        $shifts = $this->shiftModel->getAllWithStats();

        return $this->render('attendance/admin', [
            'attendanceList' => $pagination['data'],
            'pagination'     => $pagination,
            'filters'        => $filters,
            'supervisors'    => $supervisors,
            'shifts'         => $shifts,
            'pageTitle'      => 'Attendance Verification & Audit Trail',
        ]);
    }

    /**
     * Admin Override Status (POST /admin/attendance/{id}/override)
     */
    public function adminOverride(Request $request): void
    {
        $id = (int)$request->param('id');
        $post = $request->post();

        $newVerifStatus = (string)($post['verification_status'] ?? 'admin_override');
        $newStatus = (string)($post['status'] ?? 'present');
        $reason = trim((string)($post['reason'] ?? ''));

        if (strlen($reason) < 10) {
            Session::setFlash('error', 'Mandatory explanation reason (minimum 10 characters) is strictly required for administrative override.');
            $this->redirect('/admin/attendance');
            return;
        }

        try {
            $this->attendanceModel->applyAdminOverride(
                $id,
                $newVerifStatus,
                $newStatus,
                (int)Auth::id(),
                $reason
            );

            Session::setFlash('success', 'Attendance record status updated and correction logged to immutable audit history.');
        } catch (\Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/admin/attendance');
    }
}
