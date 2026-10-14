<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Shift;
use App\Models\User;
use App\Models\Ward;
use App\Services\DashboardService;

/**
 * Dashboard & Inspection Matrix Controller
 * Recreates the hospital visual hierarchy with database-backed KPIs,
 * interactive matrix filters, supervisor scoping, and SLA tracking.
 */
class HomeController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    /**
     * Hospital Facility Dashboard & Inspection Matrix
     */
    public function index(Request $request): Response
    {
        $currentUser = Auth::user() ?: [
            'id'   => 0,
            'role' => 'admin', // Default to admin preview if not logged in
            'name' => 'Facility Officer',
        ];

        $filters = [
            'date'          => $request->query('date', date('Y-m-d')),
            'shift_id'      => $request->query('shift_id', ''),
            'building_id'   => $request->query('building_id', ''),
            'floor_id'      => $request->query('floor_id', ''),
            'ward_id'       => $request->query('ward_id', ''),
            'supervisor_id' => $request->query('supervisor_id', ''),
        ];

        // 1. Fetch Inspection Metrics
        $inspectionMetrics = $this->dashboardService->getInspectionMetrics($filters, $currentUser);

        // 2. Fetch Attendance Metrics
        $attendanceMetrics = $this->dashboardService->getAttendanceMetrics($filters, $currentUser);

        // 3. Fetch Complaints & SLA Metrics
        $complaintMetrics = $this->dashboardService->getComplaintMetrics($filters, $currentUser);

        // 4. Fetch Building / Floor / Ward Inspection Matrix
        $matrix = $this->dashboardService->getInspectionMatrix($filters, $currentUser);

        // Group matrix rows by Building -> Floor for clean multi-tier visual hierarchy
        $groupedMatrix = [];
        foreach ($matrix as $row) {
            $bldgKey = $row['building_name'];
            $floorKey = $row['floor_name'] . " (Floor " . $row['floor_number'] . ")";
            if (!isset($groupedMatrix[$bldgKey])) {
                $groupedMatrix[$bldgKey] = [];
            }
            if (!isset($groupedMatrix[$bldgKey][$floorKey])) {
                $groupedMatrix[$bldgKey][$floorKey] = [];
            }
            $groupedMatrix[$bldgKey][$floorKey][] = $row;
        }

        // 5. Lookups for Filter Select Controls
        $buildings = (new Building())->all('name ASC');
        $floors = (new Floor())->all('floor_number ASC');
        $wards = (new Ward())->all('ward_name ASC');
        $shifts = (new Shift())->all('start_time ASC');
        $supervisors = (new User())->all('name ASC');

        return $this->view('home/index', [
            'pageTitle'          => 'Hospital Inspection & Facility Matrix',
            'filters'            => $filters,
            'inspectionMetrics'  => $inspectionMetrics,
            'attendanceMetrics'  => $attendanceMetrics,
            'complaintMetrics'   => $complaintMetrics,
            'matrix'             => $matrix,
            'groupedMatrix'      => $groupedMatrix,
            'buildings'          => $buildings,
            'floors'             => $floors,
            'wards'              => $wards,
            'shifts'             => $shifts,
            'supervisors'        => $supervisors,
            'currentUser'        => $currentUser,
        ]);
    }
}
