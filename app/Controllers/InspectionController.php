<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\ChecklistItem;
use App\Models\ChecklistTemplateVersion;
use App\Models\Inspection;
use App\Models\InspectionResponse;
use App\Models\InspectionSchedule;
use App\Models\SupervisorAssignment;
use App\Models\Ward;

/**
 * InspectionController
 * Handles supervisor inspection execution, server-side validation, score calculation,
 * and transactional submission.
 */
class InspectionController extends Controller
{
    private Inspection $inspectionModel;
    private InspectionSchedule $scheduleModel;
    private ChecklistTemplateVersion $versionModel;
    private ChecklistItem $itemModel;
    private InspectionResponse $responseModel;
    private SupervisorAssignment $supervisorAssignModel;
    private Ward $wardModel;

    public function __construct()
    {
        $this->inspectionModel = new Inspection();
        $this->scheduleModel = new InspectionSchedule();
        $this->versionModel = new ChecklistTemplateVersion();
        $this->itemModel = new ChecklistItem();
        $this->responseModel = new InspectionResponse();
        $this->supervisorAssignModel = new SupervisorAssignment();
        $this->wardModel = new Ward();
    }

    /**
     * Supervisor Inspection Inbox: Assigned work for today & active shifts
     */
    public function myInspections(Request $request): Response
    {
        $userId = (int)Auth::id();
        $today = $request->query('date', date('Y-m-d'));

        $schedules = $this->scheduleModel->getSupervisorSchedules($userId, (string)$today);

        return $this->render('inspections/my_inspections', [
            'schedules'    => $schedules,
            'currentDate'  => $today,
            'statusBadges' => InspectionSchedule::STATUS_BADGES,
            'pageTitle'    => 'My Assigned Inspections',
        ]);
    }

    /**
     * Execute Inspection: Render dynamic checklist form
     */
    public function execute(Request $request): Response
    {
        $scheduleId = (int)$request->param('scheduleId');
        $userId = (int)Auth::id();
        $today = date('Y-m-d');

        $schedule = $this->scheduleModel->find($scheduleId);
        if (!$schedule) {
            Session::setFlash('error', 'Inspection schedule not found.');
            $this->redirect('/inspections/my');
            return new Response('', 302);
        }

        // Supervisor Permission Check: Is user assigned to this schedule or assigned to the facility ward?
        $isAssigned = false;
        if (Auth::isAdmin() || (int)$schedule['assigned_user_id'] === $userId) {
            $isAssigned = true;
        } else {
            $isAssigned = $this->supervisorAssignModel->isSupervisorAssigned($userId, null, null, (int)$schedule['ward_id'], $today);
        }

        if (!$isAssigned) {
            AuditLog::log(
                AuditLog::EVENT_SUSPICIOUS_ACTIVITY,
                "Unauthorized inspection execution attempt by supervisor #{$userId} on schedule #{$scheduleId}",
                $userId,
                ['schedule_id' => $scheduleId, 'ward_id' => $schedule['ward_id']]
            );
            Session::setFlash('error', 'Unauthorized: You are not assigned to conduct this scheduled inspection.');
            $this->redirect('/inspections/my');
            return new Response('', 302);
        }

        // Check or create inspection record
        $inspection = \App\Core\Database::selectOne(
            "SELECT * FROM `inspections` WHERE `schedule_id` = :sid LIMIT 1",
            ['sid' => $scheduleId]
        );

        $ward = $this->wardModel->find((int)$schedule['ward_id']);
        $version = $this->versionModel->getWithTemplate((int)$schedule['template_version_id']);
        $items = $this->versionModel->getItems((int)$schedule['template_version_id']);

        if (!$inspection) {
            // Create draft inspection session
            $inspectionNumber = Inspection::generateInspectionNumber();
            $inspectionId = $this->inspectionModel->create([
                'inspection_number'      => $inspectionNumber,
                'schedule_id'            => $scheduleId,
                'template_version_id'    => (int)$schedule['template_version_id'],
                'building_id'            => (int)($ward['building_id'] ?? 1),
                'floor_id'               => (int)($ward['floor_id'] ?? 1),
                'ward_id'                => (int)$schedule['ward_id'],
                'inspection_location_id' => !empty($schedule['inspection_location_id']) ? (int)$schedule['inspection_location_id'] : null,
                'inspector_user_id'      => $userId,
                'shift_id'               => !empty($schedule['shift_id']) ? (int)$schedule['shift_id'] : null,
                'status'                 => 'draft',
                'total_items'            => count($items),
                'started_at'             => date('Y-m-d H:i:s'),
            ]);

            // Update schedule status to in_progress
            $this->scheduleModel->update($scheduleId, ['status' => 'in_progress']);

            $inspection = $this->inspectionModel->find($inspectionId);
        }

        $existingResponses = [];
        if ($inspection) {
            $respRows = $this->responseModel->getByInspection((int)$inspection['id']);
            foreach ($respRows as $r) {
                $existingResponses[$r['checklist_item_id']] = [
                    'value'   => $r['response_value'],
                    'remarks' => $r['remarks'],
                ];
            }
        }

        return $this->render('inspections/execute', [
            'schedule'          => $schedule,
            'inspection'        => $inspection,
            'version'           => $version,
            'items'             => $items,
            'existingResponses' => $existingResponses,
            'severities'        => ChecklistItem::SEVERITIES,
            'pageTitle'         => "Perform Inspection: {$version['template_name']}",
        ]);
    }

    /**
     * Submit Final Inspection (Transactional, Server Validation, Scoring)
     */
    public function submit(Request $request): void
    {
        $scheduleId = (int)$request->param('scheduleId');
        $userId = (int)Auth::id();

        $schedule = $this->scheduleModel->find($scheduleId);
        if (!$schedule) {
            Session::setFlash('error', 'Inspection schedule not found.');
            $this->redirect('/inspections/my');
            return;
        }

        $inspection = \App\Core\Database::selectOne(
            "SELECT * FROM `inspections` WHERE `schedule_id` = :sid LIMIT 1",
            ['sid' => $scheduleId]
        );

        if (!$inspection) {
            Session::setFlash('error', 'Inspection session not found.');
            $this->redirect('/inspections/my');
            return;
        }

        $inspectionId = (int)$inspection['id'];
        $post = $request->post();
        $responses = $post['responses'] ?? [];
        $remarks = $post['remarks'] ?? [];
        $overallNotes = trim((string)($post['notes'] ?? ''));

        try {
            $result = $this->inspectionModel->submitInspection(
                $inspectionId,
                $userId,
                $responses,
                $remarks,
                !empty($overallNotes) ? $overallNotes : null
            );

            Session::setFlash('success', "Inspection submitted successfully! Score: {$result['score_percentage']}%. Critical issues: {$result['critical_issues']}.");
            $this->redirect("/inspections/{$inspectionId}");
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('error', $e->getMessage());
            $this->redirect("/inspections/execute/{$scheduleId}");
        } catch (\Throwable $e) {
            Session::setFlash('error', 'System error during submission: ' . $e->getMessage());
            $this->redirect("/inspections/execute/{$scheduleId}");
        }
    }

    /**
     * View Completed Inspection Historical Snapshot
     */
    public function show(Request $request): Response
    {
        $id = (int)$request->param('id');
        $inspection = $this->inspectionModel->getWithDetails($id);

        if (!$inspection) {
            Session::setFlash('error', 'Inspection record not found.');
            $this->redirect('/inspections/my');
            return new Response('', 302);
        }

        return $this->render('inspections/show', [
            'inspection' => $inspection,
            'pageTitle'  => "Inspection Report: {$inspection['inspection_number']}",
        ]);
    }
}
