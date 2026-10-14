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
use App\Models\Floor;
use App\Models\FloorPlanArea;
use App\Models\InspectionLocation;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\Ward;

/**
 * Facility Controller
 * Administers Sheth L.G. General Hospital infrastructure:
 * Buildings, Floors, Wards, Checkpoint Locations, and Supervisor Assignments.
 */
class FacilityController extends Controller
{
    private Building $buildingModel;
    private Floor $floorModel;
    private Ward $wardModel;
    private InspectionLocation $locationModel;
    private FloorPlanArea $areaModel;
    private SupervisorAssignment $assignmentModel;
    private User $userModel;

    public function __construct()
    {
        $this->buildingModel = new Building();
        $this->floorModel = new Floor();
        $this->wardModel = new Ward();
        $this->locationModel = new InspectionLocation();
        $this->areaModel = new FloorPlanArea();
        $this->assignmentModel = new SupervisorAssignment();
        $this->userModel = new User();
    }

    // =========================================================================
    // BUILDINGS
    // =========================================================================

    /**
     * List all hospital buildings with floor and ward stats
     */
    public function buildingsIndex(Request $request): Response
    {
        $status = $request->query('status', '');
        $buildings = $this->buildingModel->getAllWithStats($status !== '' ? (string)$status : null);

        return $this->render('facility/buildings/index', [
            'buildings' => $buildings,
            'currentStatus' => $status,
            'pageTitle' => 'Hospital Buildings Infrastructure',
        ]);
    }

    /**
     * Store new hospital building
     */
    public function buildingsStore(Request $request): void
    {
        $post = $request->post();
        $code = strtoupper(trim((string)($post['building_code'] ?? '')));
        $name = trim((string)($post['building_name'] ?? ''));
        $description = trim((string)($post['description'] ?? ''));

        if (empty($code) || empty($name)) {
            Session::setFlash('error', 'Building code and name are required.');
            $this->redirect('/admin/buildings');
            return;
        }

        if ($this->buildingModel->isCodeTaken($code)) {
            Session::setFlash('error', "Building code '{$code}' already exists.");
            $this->redirect('/admin/buildings');
            return;
        }

        $id = $this->buildingModel->create([
            'building_code' => $code,
            'building_name' => $name,
            'description'   => $description,
            'status'        => 'active'
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            'Created building ' . $code . ' (' . $name . ')',
            Auth::id(),
            ['building_id' => $id, 'code' => $code]
        );

        Session::setFlash('success', "Building '{$name}' created successfully.");
        $this->redirect('/admin/buildings/' . $id);
    }

    /**
     * View building details, floor levels, and quick actions
     */
    public function buildingsShow(Request $request): Response
    {
        $id = (int)$request->param('id');
        $building = $this->buildingModel->findWithFloors($id);

        if (!$building) {
            Session::setFlash('error', 'Building not found.');
            $this->redirect('/admin/buildings');
            return new Response('', 302);
        }

        return $this->render('facility/buildings/show', [
            'building'  => $building,
            'floors'    => $building['floors'] ?? [],
            'pageTitle' => $building['building_name'] . ' Details',
        ]);
    }

    /**
     * Update existing building
     */
    public function buildingsUpdate(Request $request): void
    {
        $id = (int)$request->param('id');
        $building = $this->buildingModel->find($id);

        if (!$building) {
            Session::setFlash('error', 'Building not found.');
            $this->redirect('/admin/buildings');
            return;
        }

        $post = $request->post();
        $code = strtoupper(trim((string)($post['building_code'] ?? '')));
        $name = trim((string)($post['building_name'] ?? ''));
        $description = trim((string)($post['description'] ?? ''));

        if (empty($code) || empty($name)) {
            Session::setFlash('error', 'Building code and name are required.');
            $this->redirect('/admin/buildings/' . $id);
            return;
        }

        if ($this->buildingModel->isCodeTaken($code, $id)) {
            Session::setFlash('error', "Building code '{$code}' is already used by another building.");
            $this->redirect('/admin/buildings/' . $id);
            return;
        }

        $this->buildingModel->update($id, [
            'building_code' => $code,
            'building_name' => $name,
            'description'   => $description
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Updated building {$code} details",
            Auth::id(),
            ['building_id' => $id]
        );

        Session::setFlash('success', 'Building updated successfully.');
        $this->redirect('/admin/buildings/' . $id);
    }

    /**
     * Safe activate/deactivate building
     */
    public function buildingsToggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $building = $this->buildingModel->find($id);

        if (!$building) {
            Session::setFlash('error', 'Building not found.');
            $this->redirect('/admin/buildings');
            return;
        }

        $currentStatus = $building['status'];
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

        // Check data protection rule when deactivating
        if ($newStatus === 'inactive' && $this->buildingModel->hasHistoricalInspections($id)) {
            // Safe deactivation is allowed, but log clearly that records are preserved
            $this->buildingModel->safeDeactivate($id, (int)Auth::id(), 'Deactivated via admin panel; historical inspections preserved');
            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Safely deactivated building {$building['building_code']} preserving historical records",
                Auth::id(),
                ['building_id' => $id, 'action' => 'safe_deactivate']
            );
            Session::setFlash('success', "Building '{$building['building_name']}' safely deactivated. Historical records and inspection logs remain intact.");
            $this->redirect('/admin/buildings/' . $id);
            return;
        }

        $this->buildingModel->update($id, ['status' => $newStatus]);
        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed building {$building['building_code']} status to {$newStatus}",
            Auth::id(),
            ['building_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Building status changed to {$newStatus}.");
        $this->redirect('/admin/buildings/' . $id);
    }

    // =========================================================================
    // FLOORS
    // =========================================================================

    /**
     * Store new floor in building
     */
    public function floorsStore(Request $request): void
    {
        $post = $request->post();
        $buildingId = (int)($post['building_id'] ?? 0);
        $floorNumber = (int)($post['floor_number'] ?? 0);
        $floorName = trim((string)($post['floor_name'] ?? ''));

        if ($buildingId <= 0 || empty($floorName)) {
            Session::setFlash('error', 'Valid building, floor number, and floor name are required.');
            $this->redirect('/admin/buildings/' . $buildingId);
            return;
        }

        if ($this->floorModel->isFloorNumberTaken($buildingId, $floorNumber)) {
            Session::setFlash('error', "Floor number {$floorNumber} already exists in this building. Duplicate floor codes/numbers are strictly prevented.");
            $this->redirect('/admin/buildings/' . $buildingId);
            return;
        }

        $floorId = $this->floorModel->create([
            'building_id'     => $buildingId,
            'floor_number'    => $floorNumber,
            'floor_name'      => $floorName,
            'plan_image_path' => '/assets/img/sample_floor_plan.svg',
            'status'          => 'active',
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Created Floor {$floorNumber} ({$floorName}) in building #{$buildingId}",
            Auth::id(),
            ['floor_id' => $floorId, 'building_id' => $buildingId]
        );

        Session::setFlash('success', "Floor '{$floorName}' created successfully.");
        $this->redirect('/admin/floors/' . $floorId);
    }

    /**
     * View Floor details, wards, checkpoints and interactive floor plan preview
     */
    public function floorsShow(Request $request): Response
    {
        $id = (int)$request->param('id');
        $floor = $this->floorModel->findWithBuilding($id);

        if (!$floor) {
            Session::setFlash('error', 'Floor not found.');
            $this->redirect('/admin/buildings');
            return new Response('', 302);
        }

        $wards = $this->wardModel->getByFloor($id, false);
        $areas = $this->areaModel->getByFloor($id);
        $summary = $this->areaModel->getSummaryByFloor($id);

        return $this->render('facility/floors/show', [
            'floor'     => $floor,
            'wards'     => $wards,
            'areas'     => $areas,
            'summary'   => $summary,
            'pageTitle' => $floor['building_name'] . ' - ' . $floor['floor_name'],
        ]);
    }

    /**
     * Update Floor details
     */
    public function floorsUpdate(Request $request): void
    {
        $id = (int)$request->param('id');
        $floor = $this->floorModel->find($id);

        if (!$floor) {
            Session::setFlash('error', 'Floor not found.');
            $this->redirect('/admin/buildings');
            return;
        }

        $post = $request->post();
        $floorNumber = (int)($post['floor_number'] ?? $floor['floor_number']);
        $floorName = trim((string)($post['floor_name'] ?? ''));

        if (empty($floorName)) {
            Session::setFlash('error', 'Floor name is required.');
            $this->redirect('/admin/floors/' . $id);
            return;
        }

        if ($this->floorModel->isFloorNumberTaken((int)$floor['building_id'], $floorNumber, $id)) {
            Session::setFlash('error', "Floor number {$floorNumber} already exists in this building.");
            $this->redirect('/admin/floors/' . $id);
            return;
        }

        $this->floorModel->update($id, [
            'floor_number' => $floorNumber,
            'floor_name'   => $floorName,
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Updated floor #{$id} ({$floorName})",
            Auth::id(),
            ['floor_id' => $id]
        );

        Session::setFlash('success', 'Floor updated successfully.');
        $this->redirect('/admin/floors/' . $id);
    }

    /**
     * Safe activate/deactivate floor
     */
    public function floorsToggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $floor = $this->floorModel->find($id);

        if (!$floor) {
            Session::setFlash('error', 'Floor not found.');
            $this->redirect('/admin/buildings');
            return;
        }

        $newStatus = ($floor['status'] === 'active') ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $this->floorModel->hasHistoricalInspections($id)) {
            $this->floorModel->safeDeactivate($id, (int)Auth::id(), 'Safely deactivated via admin console; historical inspections preserved');
            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Safely deactivated floor #{$id} preserving historical records",
                Auth::id(),
                ['floor_id' => $id]
            );
            Session::setFlash('success', "Floor '{$floor['floor_name']}' safely deactivated. Historical inspections and complaints preserved.");
            $this->redirect('/admin/floors/' . $id);
            return;
        }

        $this->floorModel->update($id, ['status' => $newStatus]);
        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed floor #{$id} status to {$newStatus}",
            Auth::id(),
            ['floor_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Floor status changed to {$newStatus}.");
        $this->redirect('/admin/floors/' . $id);
    }

    // =========================================================================
    // WARDS
    // =========================================================================

    /**
     * List all hospital wards with search, filters, and pagination
     */
    public function wardsIndex(Request $request): Response
    {
        $filters = [
            'search'      => (string)$request->query('search', ''),
            'building_id' => $request->query('building_id', ''),
            'floor_id'    => $request->query('floor_id', ''),
            'ward_type'   => (string)$request->query('ward_type', ''),
            'status'      => $request->query('status', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->wardModel->getAllWithDetails($filters, $page, 15);
        $buildings = $this->buildingModel->getAllWithStats();

        return $this->render('facility/wards/index', [
            'wards'      => $pagination['data'],
            'pagination' => $pagination,
            'filters'    => $filters,
            'buildings'  => $buildings,
            'pageTitle'  => 'Clinical Wards & Departments',
        ]);
    }

    /**
     * Ward creation form
     */
    public function wardsCreate(Request $request): Response
    {
        $buildings = $this->buildingModel->getAllWithStats('active');
        $selectedBuilding = (int)$request->query('building_id', 0);
        $selectedFloor = (int)$request->query('floor_id', 0);

        return $this->render('facility/wards/create', [
            'buildings'        => $buildings,
            'selectedBuilding' => $selectedBuilding,
            'selectedFloor'    => $selectedFloor,
            'pageTitle'        => 'Add New Clinical Ward / Department',
        ]);
    }

    /**
     * Store new ward
     */
    public function wardsStore(Request $request): void
    {
        $post = $request->post();
        $floorId = (int)($post['floor_id'] ?? 0);
        $wardCode = strtoupper(trim((string)($post['ward_code'] ?? '')));
        $wardName = trim((string)($post['ward_name'] ?? ''));
        $wardType = trim((string)($post['ward_type'] ?? 'general'));
        $bedCapacity = max(0, (int)($post['bed_capacity'] ?? 0));

        if ($floorId <= 0 || empty($wardCode) || empty($wardName)) {
            Session::setFlash('error', 'Floor selection, ward code, and ward name are required.');
            $this->redirect('/admin/wards/create');
            return;
        }

        $floor = $this->floorModel->find($floorId);
        if (!$floor) {
            Session::setFlash('error', 'Selected floor does not exist. Invalid hierarchy relationship.');
            $this->redirect('/admin/wards/create');
            return;
        }

        if ($this->wardModel->isCodeTaken($wardCode)) {
            Session::setFlash('error', "Ward code '{$wardCode}' already exists. Duplicate codes are strictly prevented.");
            $this->redirect('/admin/wards/create');
            return;
        }

        $qrCode = $this->wardModel->generateUniqueQrCode('QR-WARD');

        $wardId = $this->wardModel->create([
            'floor_id'           => $floorId,
            'ward_code'          => $wardCode,
            'ward_name'          => $wardName,
            'ward_type'          => $wardType,
            'bed_capacity'       => $bedCapacity,
            'qr_code_identifier' => $qrCode,
            'status'             => 'active'
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Created ward {$wardCode} ({$wardName}) on floor #{$floorId}",
            Auth::id(),
            ['ward_id' => $wardId, 'ward_code' => $wardCode, 'qr_code' => $qrCode]
        );

        Session::setFlash('success', "Ward '{$wardName}' ({$wardCode}) created successfully.");
        $this->redirect('/admin/wards/' . $wardId);
    }

    /**
     * Show Ward details with inspection checkpoints & supervisor assignments
     */
    public function wardsShow(Request $request): Response
    {
        $id = (int)$request->param('id');
        $ward = $this->wardModel->findWithHierarchy($id);

        if (!$ward) {
            Session::setFlash('error', 'Ward not found.');
            $this->redirect('/admin/wards');
            return new Response('', 302);
        }

        $locations = $this->locationModel->getByWard($id, false);
        $assignments = $this->assignmentModel->getAssignmentsWithDetails(['ward_id' => $id, 'status' => 'active'], 1, 50);
        $supervisors = $this->userModel->getByRole('supervisor');

        return $this->render('facility/wards/show', [
            'ward'         => $ward,
            'locations'    => $locations,
            'assignments'  => $assignments['data'] ?? [],
            'supervisors'  => $supervisors,
            'pageTitle'    => $ward['ward_name'] . ' (' . $ward['ward_code'] . ')',
        ]);
    }

    /**
     * Update Ward details
     */
    public function wardsUpdate(Request $request): void
    {
        $id = (int)$request->param('id');
        $ward = $this->wardModel->find($id);

        if (!$ward) {
            Session::setFlash('error', 'Ward not found.');
            $this->redirect('/admin/wards');
            return;
        }

        $post = $request->post();
        $wardCode = strtoupper(trim((string)($post['ward_code'] ?? '')));
        $wardName = trim((string)($post['ward_name'] ?? ''));
        $wardType = trim((string)($post['ward_type'] ?? 'general'));
        $bedCapacity = max(0, (int)($post['bed_capacity'] ?? 0));

        if (empty($wardCode) || empty($wardName)) {
            Session::setFlash('error', 'Ward code and name are required.');
            $this->redirect('/admin/wards/' . $id);
            return;
        }

        if ($this->wardModel->isCodeTaken($wardCode, $id)) {
            Session::setFlash('error', "Ward code '{$wardCode}' is already taken by another ward.");
            $this->redirect('/admin/wards/' . $id);
            return;
        }

        $this->wardModel->update($id, [
            'ward_code'    => $wardCode,
            'ward_name'    => $wardName,
            'ward_type'    => $wardType,
            'bed_capacity' => $bedCapacity,
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Updated ward {$wardCode} details",
            Auth::id(),
            ['ward_id' => $id]
        );

        Session::setFlash('success', 'Ward updated successfully.');
        $this->redirect('/admin/wards/' . $id);
    }

    /**
     * Safe activate/deactivate ward
     */
    public function wardsToggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $ward = $this->wardModel->find($id);

        if (!$ward) {
            Session::setFlash('error', 'Ward not found.');
            $this->redirect('/admin/wards');
            return;
        }

        $newStatus = ($ward['status'] === 'active') ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $this->wardModel->hasHistoricalInspections($id)) {
            $this->wardModel->safeDeactivate($id, (int)Auth::id(), 'Safely deactivated via admin console; historical inspections preserved');
            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Safely deactivated ward {$ward['ward_code']} preserving historical inspections",
                Auth::id(),
                ['ward_id' => $id]
            );
            Session::setFlash('success', "Ward '{$ward['ward_name']}' safely deactivated. Historical records remain preserved.");
            $this->redirect('/admin/wards/' . $id);
            return;
        }

        $this->wardModel->update($id, ['status' => $newStatus]);
        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed ward {$ward['ward_code']} status to {$newStatus}",
            Auth::id(),
            ['ward_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Ward status changed to {$newStatus}.");
        $this->redirect('/admin/wards/' . $id);
    }

    // =========================================================================
    // INSPECTION LOCATIONS (CHECKPOINTS)
    // =========================================================================

    /**
     * List all checkpoints with filters & QR identifiers
     */
    public function locationsIndex(Request $request): Response
    {
        $filters = [
            'search'        => (string)$request->query('search', ''),
            'building_id'   => $request->query('building_id', ''),
            'floor_id'      => $request->query('floor_id', ''),
            'ward_id'       => $request->query('ward_id', ''),
            'location_type' => (string)$request->query('location_type', ''),
            'status'        => $request->query('status', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->locationModel->getAllWithDetails($filters, $page, 15);
        $buildings = $this->buildingModel->getAllWithStats();

        return $this->render('facility/locations/index', [
            'locations'  => $pagination['data'],
            'pagination' => $pagination,
            'filters'    => $filters,
            'buildings'  => $buildings,
            'pageTitle'  => 'Inspection Checkpoint Locations',
        ]);
    }

    /**
     * Store new inspection checkpoint location
     */
    public function locationsStore(Request $request): void
    {
        $post = $request->post();
        $wardId = (int)($post['ward_id'] ?? 0);
        $locationCode = strtoupper(trim((string)($post['location_code'] ?? '')));
        $locationName = trim((string)($post['location_name'] ?? ''));
        $locationType = trim((string)($post['location_type'] ?? 'general'));

        if ($wardId <= 0 || empty($locationCode) || empty($locationName)) {
            Session::setFlash('error', 'Ward, location code, and location name are required.');
            $this->redirect('/admin/locations');
            return;
        }

        $ward = $this->wardModel->find($wardId);
        if (!$ward) {
            Session::setFlash('error', 'Selected ward does not exist.');
            $this->redirect('/admin/locations');
            return;
        }

        if ($this->locationModel->isCodeTaken($locationCode)) {
            Session::setFlash('error', "Location code '{$locationCode}' already exists.");
            $this->redirect('/admin/locations');
            return;
        }

        $qrCode = $this->locationModel->generateUniqueQrCode('QR-LOC');

        $locId = $this->locationModel->create([
            'ward_id'            => $wardId,
            'location_code'      => $locationCode,
            'location_name'      => $locationName,
            'location_type'      => $locationType,
            'qr_code_identifier' => $qrCode,
            'status'             => 'active'
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Created inspection location {$locationCode} in ward #{$wardId}",
            Auth::id(),
            ['location_id' => $locId, 'location_code' => $locationCode, 'qr_code' => $qrCode]
        );

        Session::setFlash('success', "Checkpoint '{$locationName}' ({$locationCode}) created successfully.");
        $redirectUrl = !empty($post['return_to_ward']) ? '/admin/wards/' . $wardId : '/admin/locations';
        $this->redirect($redirectUrl);
    }

    /**
     * Safe activate/deactivate inspection location
     */
    public function locationsToggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $location = $this->locationModel->find($id);

        if (!$location) {
            Session::setFlash('error', 'Checkpoint location not found.');
            $this->redirect('/admin/locations');
            return;
        }

        $newStatus = ($location['status'] === 'active') ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $this->locationModel->hasHistoricalInspections($id)) {
            $this->locationModel->safeDeactivate($id, (int)Auth::id(), 'Deactivated via admin console; historical inspections preserved');
            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Safely deactivated location checkpoint #{$id} preserving historical records",
                Auth::id(),
                ['location_id' => $id]
            );
            Session::setFlash('success', "Location '{$location['location_name']}' safely deactivated. Historical records preserved.");
            $this->redirect('/admin/locations');
            return;
        }

        $this->locationModel->update($id, ['status' => $newStatus]);
        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed location #{$id} status to {$newStatus}",
            Auth::id(),
            ['location_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Checkpoint status changed to {$newStatus}.");
        $this->redirect('/admin/locations');
    }

    // =========================================================================
    // SUPERVISOR ASSIGNMENTS
    // =========================================================================

    /**
     * List all supervisor assignments with filters
     */
    public function supervisorAssignmentsIndex(Request $request): Response
    {
        $filters = [
            'user_id'        => $request->query('user_id', ''),
            'building_id'    => $request->query('building_id', ''),
            'floor_id'       => $request->query('floor_id', ''),
            'ward_id'        => $request->query('ward_id', ''),
            'status'         => $request->query('status', 'active'),
            'active_on_date' => (string)$request->query('active_on_date', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->assignmentModel->getAssignmentsWithDetails($filters, $page, 15);
        $supervisors = $this->userModel->getByRole('supervisor');
        $buildings = $this->buildingModel->getAllWithStats();

        return $this->render('facility/supervisors/index', [
            'assignments' => $pagination['data'],
            'pagination'  => $pagination,
            'filters'     => $filters,
            'supervisors' => $supervisors,
            'buildings'   => $buildings,
            'pageTitle'   => 'Supervisor Facility Allocations',
        ]);
    }

    /**
     * Store new supervisor allocation with hierarchy check and effective dates
     */
    public function supervisorAssignmentsStore(Request $request): void
    {
        $post = $request->post();
        $userId = (int)($post['user_id'] ?? 0);
        $buildingId = !empty($post['building_id']) ? (int)$post['building_id'] : null;
        $floorId = !empty($post['floor_id']) ? (int)$post['floor_id'] : null;
        $wardId = !empty($post['ward_id']) ? (int)$post['ward_id'] : null;
        $effectiveFrom = !empty($post['effective_from']) ? trim((string)$post['effective_from']) : date('Y-m-d');
        $effectiveTo = !empty($post['effective_to']) ? trim((string)$post['effective_to']) : null;

        if ($userId <= 0) {
            Session::setFlash('error', 'Supervisor selection is required.');
            $this->redirect('/admin/supervisor-assignments');
            return;
        }

        try {
            $assignmentId = $this->assignmentModel->createAssignment([
                'user_id'        => $userId,
                'building_id'    => $buildingId,
                'floor_id'       => $floorId,
                'ward_id'        => $wardId,
                'assigned_by'    => (int)Auth::id(),
                'effective_from' => $effectiveFrom,
                'effective_to'   => $effectiveTo,
            ]);

            AuditLog::log(
                AuditLog::EVENT_USER_UPDATED,
                "Assigned supervisor #{$userId} to facility with effective dates {$effectiveFrom} to " . ($effectiveTo ?? 'indefinite'),
                Auth::id(),
                ['assignment_id' => $assignmentId, 'user_id' => $userId]
            );

            Session::setFlash('success', 'Supervisor assigned to facility successfully.');
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $returnUrl = !empty($post['return_url']) ? (string)$post['return_url'] : '/admin/supervisor-assignments';
        $this->redirect($returnUrl);
    }

    /**
     * Revoke supervisor assignment
     */
    public function supervisorAssignmentsRevoke(Request $request): void
    {
        $id = (int)$request->param('id');
        $assignment = $this->assignmentModel->find($id);

        if (!$assignment) {
            Session::setFlash('error', 'Assignment record not found.');
            $this->redirect('/admin/supervisor-assignments');
            return;
        }

        $this->assignmentModel->revokeAssignment($id, (int)Auth::id());

        AuditLog::log(
            AuditLog::EVENT_USER_UPDATED,
            "Revoked supervisor assignment #{$id}",
            Auth::id(),
            ['assignment_id' => $id]
        );

        Session::setFlash('success', 'Supervisor assignment revoked.');
        $this->redirect('/admin/supervisor-assignments');
    }

    // =========================================================================
    // AJAX API ENDPOINTS
    // =========================================================================

    /**
     * Get floors by building (for cascaded dropdowns)
     */
    public function apiFloorsByBuilding(Request $request): Response
    {
        $buildingId = (int)$request->param('id');
        $floors = $this->floorModel->getByBuilding($buildingId, true);
        return $this->json(['success' => true, 'floors' => $floors]);
    }

    /**
     * Get wards by floor (for cascaded dropdowns)
     */
    public function apiWardsByFloor(Request $request): Response
    {
        $floorId = (int)$request->param('id');
        $wards = $this->wardModel->getByFloor($floorId, true);
        return $this->json(['success' => true, 'wards' => $wards]);
    }
}
