<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Building;
use App\Models\Complaint;
use App\Models\Floor;
use App\Models\User;
use App\Models\Ward;
use App\Services\SecureEvidenceService;
use App\Services\SlaService;
use PDO;

/**
 * Complaint Controller
 * Handles lodging, filtering, workflow state transitions, assignments,
 * SLA monitoring, and secure evidence streaming.
 */
class ComplaintController extends Controller
{
    private Complaint $complaintModel;
    private SecureEvidenceService $evidenceService;
    private SlaService $slaService;

    public function __construct()
    {
        $this->complaintModel = new Complaint();
        $this->evidenceService = new SecureEvidenceService();
        $this->slaService = new SlaService();
    }

    /**
     * Complaint Directory & Filters
     */
    public function index(Request $request): Response
    {
        $filters = [
            'status'      => $request->query('status', ''),
            'priority'    => $request->query('priority', ''),
            'category'    => $request->query('category', ''),
            'building_id' => $request->query('building_id', ''),
            'floor_id'    => $request->query('floor_id', ''),
            'ward_id'     => $request->query('ward_id', ''),
            'assigned_to' => $request->query('assigned_to', ''),
            'sla_breach'  => $request->query('sla_breach', ''),
            'date_from'   => $request->query('date_from', ''),
            'date_to'     => $request->query('date_to', ''),
            'search'      => $request->query('search', ''),
        ];

        $page = (int)$request->query('page', 1);
        $result = $this->complaintModel->getFiltered($filters, $page, 15);

        // Calculate KPI summary stats
        $stats = $this->getKpiSummaryStats();

        // Facility and Personnel lookup lists for filter bars
        $buildings = (new Building())->all('name ASC');
        $supervisors = (new User())->all('name ASC');

        return $this->view('complaints/index', [
            'pageTitle'    => 'Complaints & SLA Management',
            'complaints'   => $result['data'],
            'pagination'   => $result,
            'filters'      => $filters,
            'stats'        => $stats,
            'buildings'    => $buildings,
            'supervisors'  => $supervisors,
            'categories'   => Complaint::CATEGORIES,
            'priorities'   => Complaint::PRIORITIES,
            'statuses'     => Complaint::STATUSES,
        ]);
    }

    /**
     * Show complaint creation form
     */
    public function create(Request $request): Response
    {
        $buildings = (new Building())->all('name ASC');
        $floors = (new Floor())->all('floor_number ASC');
        $wards = (new Ward())->all('ward_name ASC');
        $supervisors = (new User())->all('name ASC');

        $prefill = [
            'inspection_id'          => $request->query('inspection_id', ''),
            'inspection_response_id' => $request->query('inspection_response_id', ''),
            'building_id'            => $request->query('building_id', ''),
            'floor_id'               => $request->query('floor_id', ''),
            'ward_id'                => $request->query('ward_id', ''),
            'location_id'            => $request->query('location_id', ''),
            'title'                  => $request->query('title', ''),
            'category'               => $request->query('category', 'cleanliness'),
            'priority'               => $request->query('priority', 'medium'),
        ];

        return $this->view('complaints/create', [
            'pageTitle'   => 'Lodge Facility Complaint',
            'buildings'   => $buildings,
            'floors'      => $floors,
            'wards'       => $wards,
            'supervisors' => $supervisors,
            'prefill'     => $prefill,
            'categories'  => Complaint::CATEGORIES,
            'priorities'  => Complaint::PRIORITIES,
        ]);
    }

    /**
     * Store new complaint with SLA deadline calculations and optional evidence
     */
    public function store(Request $request): void
    {
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $title = trim((string)$request->post('title', ''));
        $description = trim((string)$request->post('description', ''));
        $category = (string)$request->post('category', 'cleanliness');
        $priority = (string)$request->post('priority', 'medium');
        $buildingId = (int)$request->post('building_id', 0);
        $floorId = (int)$request->post('floor_id', 0);
        $wardId = (int)$request->post('ward_id', 0);

        if ($title === '' || $description === '' || $buildingId <= 0 || $floorId <= 0 || $wardId <= 0) {
            Session::flash('error', 'Please fill in all mandatory fields (Title, Description, Building, Floor, and Ward).');
            Response::redirect('/complaints/create');
            return;
        }

        try {
            $complaintId = $this->complaintModel->createComplaint([
                'title'                  => $title,
                'description'            => $description,
                'category'               => $category,
                'priority'               => $priority,
                'building_id'            => $buildingId,
                'floor_id'               => $floorId,
                'ward_id'                => $wardId,
                'inspection_location_id' => $request->post('inspection_location_id') ?: null,
                'inspection_id'          => $request->post('inspection_id') ?: null,
                'inspection_response_id' => $request->post('inspection_response_id') ?: null,
                'assigned_to_user_id'    => $request->post('assigned_to_user_id') ?: null,
            ], (int)$user['id']);

            // Handle optional evidence upload
            $file = $request->file('evidence');
            if ($file && !empty($file['name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                try {
                    $this->evidenceService->storeUpload(
                        $file,
                        $complaintId,
                        (int)$user['id'],
                        null,
                        'Initial complaint evidence'
                    );
                } catch (\Throwable $fileEx) {
                    Session::flash('warning', 'Complaint lodged successfully, but evidence upload failed: ' . $fileEx->getMessage());
                    Response::redirect('/complaints/' . $complaintId);
                    return;
                }
            }

            Session::flash('success', 'Complaint ticket successfully created and SLA timers initiated.');
            Response::redirect('/complaints/' . $complaintId);
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to create complaint: ' . $e->getMessage());
            Response::redirect('/complaints/create');
        }
    }

    /**
     * Show complaint details, chronological timeline, SLA status and action modals
     */
    public function show(Request $request, mixed $param = null): Response
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $complaint = $this->complaintModel->getDetails($id, Auth::isAdmin());

        if (!$complaint) {
            Session::flash('error', 'Complaint record not found.');
            Response::redirect('/complaints');
            exit;
        }

        $timeline = $this->complaintModel->getTimeline($id);
        $evidence = $this->complaintModel->getEvidence($id);
        $supervisors = (new User())->all('name ASC');

        return $this->view('complaints/show', [
            'pageTitle'   => "Complaint {$complaint['ticket_number']}",
            'complaint'   => $complaint,
            'timeline'    => $timeline,
            'evidence'    => $evidence,
            'supervisors' => $supervisors,
            'statuses'    => Complaint::STATUSES,
            'priorities'  => Complaint::PRIORITIES,
            'categories'  => Complaint::CATEGORIES,
        ]);
    }

    /**
     * Assign / Reassign complaint
     */
    public function assign(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $assignToId = (int)$request->post('assigned_to_user_id', 0);
        $notes = trim((string)$request->post('notes', ''));

        if ($assignToId <= 0) {
            Session::flash('error', 'Please select a valid personnel/supervisor to assign.');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $this->complaintModel->assign($id, $assignToId, (int)$user['id'], $notes ?: null);
            Session::flash('success', 'Complaint assigned successfully.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Assignment failed: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Update complaint status with mandatory comment
     */
    public function updateStatus(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $newStatus = trim((string)$request->post('status', ''));
        $comment = trim((string)$request->post('comment', ''));

        if ($comment === '') {
            Session::flash('error', 'A comment is required when changing ticket status.');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $this->complaintModel->updateStatus($id, $newStatus, (int)$user['id'], $comment);
            Session::flash('success', 'Status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Status update failed: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Place complaint on hold
     */
    public function putOnHold(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $reason = trim((string)$request->post('hold_reason', ''));
        if (strlen($reason) < 5) {
            Session::flash('error', 'Please provide a clear reason for putting this complaint on hold (minimum 5 characters).');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $this->complaintModel->putOnHold($id, (int)$user['id'], $reason);
            Session::flash('success', 'Complaint has been placed on hold.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to place on hold: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Mark complaint as resolved with mandatory resolution notes
     */
    public function resolve(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $resolutionNotes = trim((string)$request->post('resolution_notes', ''));
        if (strlen($resolutionNotes) < 8) {
            Session::flash('error', 'Resolution notes are mandatory and must detail the corrective actions taken (min 8 characters).');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $this->complaintModel->resolve($id, (int)$user['id'], $resolutionNotes);

            // Handle optional proof of resolution attachment
            $file = $request->file('resolution_evidence');
            if ($file && !empty($file['name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                try {
                    $this->evidenceService->storeUpload(
                        $file,
                        $id,
                        (int)$user['id'],
                        null,
                        'Proof of Resolution attachment'
                    );
                } catch (\Throwable $e) {
                    // Log upload error but keep complaint resolved
                }
            }

            Session::flash('success', 'Complaint marked as Resolved.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to resolve complaint: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Reopen a resolved or closed complaint with a mandatory reason
     */
    public function reopen(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $reason = trim((string)$request->post('reopen_reason', ''));
        if (strlen($reason) < 8) {
            Session::flash('error', 'A clear justification is required to reopen this ticket (minimum 8 characters).');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $this->complaintModel->reopen($id, (int)$user['id'], $reason);
            Session::flash('warning', 'Complaint ticket has been reopened.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to reopen complaint: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Close complaint
     */
    public function close(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $notes = trim((string)$request->post('closure_notes', ''));

        try {
            $this->complaintModel->close($id, (int)$user['id'], $notes ?: null);
            Session::flash('success', 'Complaint ticket officially closed.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to close complaint: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Add chronological comment/timeline update with optional attachment
     */
    public function addComment(Request $request, mixed $param = null): void
    {
        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }

        $comment = trim((string)$request->post('comment', ''));
        if ($comment === '') {
            Session::flash('error', 'Comment cannot be empty.');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $updateId = $this->complaintModel->addTimelineUpdate(
                $id,
                (int)$user['id'],
                'comment',
                $comment
            );

            // Handle optional file attachment
            $file = $request->file('evidence');
            if ($file && !empty($file['name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $this->evidenceService->storeUpload(
                    $file,
                    $id,
                    (int)$user['id'],
                    $updateId,
                    'Timeline note attachment'
                );
            }

            Session::flash('success', 'Update posted to complaint timeline.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to add comment: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Soft delete complaint (Admin only)
     */
    public function delete(Request $request, mixed $param = null): void
    {
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized. Only administrators can delete complaint records.');
            Response::redirect('/complaints');
            return;
        }

        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $reason = trim((string)$request->post('deletion_reason', ''));

        if (strlen($reason) < 5) {
            Session::flash('error', 'A deletion explanation reason is required (minimum 5 characters).');
            Response::redirect('/complaints/' . $id);
            return;
        }

        try {
            $this->complaintModel->softDelete($id, (int)Auth::id(), $reason);
            Session::flash('success', 'Complaint record moved to archives (soft-deleted).');
        } catch (\Throwable $e) {
            Session::flash('error', 'Deletion failed: ' . $e->getMessage());
        }

        Response::redirect('/complaints');
    }

    /**
     * Restore soft-deleted complaint (Admin only)
     */
    public function restore(Request $request, mixed $param = null): void
    {
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized. Only administrators can restore archived complaints.');
            Response::redirect('/complaints');
            return;
        }

        $id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));

        try {
            $this->complaintModel->restore($id, (int)Auth::id());
            Session::flash('success', 'Complaint ticket successfully restored from archives.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Restoration failed: ' . $e->getMessage());
        }

        Response::redirect('/complaints/' . $id);
    }

    /**
     * Authorized Evidence Download
     */
    public function downloadEvidence(Request $request, mixed $param = null): void
    {
        $evidenceId = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));
        $user = Auth::user();

        if (!$user) {
            http_response_code(401);
            echo "Authentication required.";
            exit;
        }

        $asAttachment = (bool)$request->query('download', false);
        $this->evidenceService->serveEvidence($evidenceId, $user, $asAttachment);
    }

    /**
     * Summary metrics for dashboard badges
     */
    private function getKpiSummaryStats(): array
    {
        $stats = [
            'total_active'   => 0,
            'critical_high'  => 0,
            'sla_breached'   => 0,
            'resolved_today' => 0,
        ];

        try {
            $pdo = Database::getConnection();

            $res = $pdo->query("
                SELECT 
                    COUNT(CASE WHEN `status` IN ('open', 'assigned', 'in_progress', 'on_hold') THEN 1 END) AS total_active,
                    COUNT(CASE WHEN `status` IN ('open', 'assigned', 'in_progress', 'on_hold') AND `priority` IN ('critical', 'high') THEN 1 END) AS critical_high,
                    COUNT(CASE WHEN `status` IN ('open', 'assigned', 'in_progress', 'on_hold') AND (`resolution_due_at` < NOW() OR `is_resolution_breached` = 1) THEN 1 END) AS sla_breached,
                    COUNT(CASE WHEN `status` = 'resolved' AND DATE(`resolved_at`) = CURDATE() THEN 1 END) AS resolved_today
                FROM `complaints`
                WHERE `deleted_at` IS NULL
            ")->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                $stats['total_active']   = (int)$res['total_active'];
                $stats['critical_high']  = (int)$res['critical_high'];
                $stats['sla_breached']   = (int)$res['sla_breached'];
                $stats['resolved_today'] = (int)$res['resolved_today'];
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        return $stats;
    }
}
