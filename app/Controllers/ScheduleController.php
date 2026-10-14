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
use App\Models\ChecklistTemplate;
use App\Models\InspectionSchedule;
use App\Models\ScheduleRule;
use App\Models\Shift;
use App\Models\User;
use App\Models\Ward;
use App\Services\InspectionScheduleService;

/**
 * ScheduleController
 * Administers custom inspection recurrence rules, schedule calendars, and automated generation.
 */
class ScheduleController extends Controller
{
    private ScheduleRule $ruleModel;
    private InspectionSchedule $scheduleModel;
    private ChecklistTemplate $templateModel;
    private Shift $shiftModel;
    private Building $buildingModel;
    private Ward $wardModel;
    private User $userModel;

    public function __construct()
    {
        $this->ruleModel = new ScheduleRule();
        $this->scheduleModel = new InspectionSchedule();
        $this->templateModel = new ChecklistTemplate();
        $this->shiftModel = new Shift();
        $this->buildingModel = new Building();
        $this->wardModel = new Ward();
        $this->userModel = new User();
    }

    /**
     * View all scheduled inspection instances (Calendar / List)
     */
    public function schedulesIndex(Request $request): Response
    {
        $filters = [
            'status'           => $request->query('status', ''),
            'date'             => $request->query('date', ''),
            'assigned_user_id' => $request->query('assigned_user_id', ''),
            'ward_id'          => $request->query('ward_id', ''),
            'shift_id'         => $request->query('shift_id', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->scheduleModel->getAllWithDetails($filters, $page, 15);
        $supervisors = $this->userModel->getByRole('supervisor');
        $shifts = $this->shiftModel->getAllWithStats('active');
        $wards = $this->wardModel->getAllWithDetails(['status' => 'active'], 1, 100)['data'] ?? [];

        return $this->render('schedules/index', [
            'schedules'   => $pagination['data'],
            'pagination'  => $pagination,
            'filters'     => $filters,
            'supervisors' => $supervisors,
            'shifts'      => $shifts,
            'wards'       => $wards,
            'statuses'    => InspectionSchedule::STATUSES,
            'statusBadges'=> InspectionSchedule::STATUS_BADGES,
            'pageTitle'   => 'Inspection Schedules Calendar',
        ]);
    }

    /**
     * View all custom recurrence schedule rules
     */
    public function rulesIndex(Request $request): Response
    {
        $filters = [
            'status'      => $request->query('status', ''),
            'frequency'   => $request->query('frequency', ''),
            'template_id' => $request->query('template_id', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->ruleModel->getAllWithDetails($filters, $page, 15);
        $templates = $this->templateModel->getAllWithDetails(['status' => 'published'], 1, 100)['data'] ?? [];
        $shifts = $this->shiftModel->getAllWithStats('active');
        $buildings = $this->buildingModel->getAllWithStats('active');
        $supervisors = $this->userModel->getByRole('supervisor');

        return $this->render('schedules/rules', [
            'rules'       => $pagination['data'],
            'pagination'  => $pagination,
            'filters'     => $filters,
            'templates'   => $templates,
            'shifts'      => $shifts,
            'buildings'   => $buildings,
            'supervisors' => $supervisors,
            'frequencies' => ScheduleRule::FREQUENCIES,
            'pageTitle'   => 'Inspection Recurrence Rules',
        ]);
    }

    /**
     * Store new custom recurrence schedule rule
     */
    public function rulesStore(Request $request): void
    {
        $post = $request->post();
        $templateId = (int)($post['template_id'] ?? 0);
        $frequency = (string)($post['frequency'] ?? 'daily');
        $startDate = trim((string)($post['start_date'] ?? date('Y-m-d')));
        $endDate = !empty($post['end_date']) ? trim((string)$post['end_date']) : null;
        $shiftId = !empty($post['shift_id']) ? (int)$post['shift_id'] : null;
        $assignedUserId = !empty($post['assigned_user_id']) ? (int)$post['assigned_user_id'] : null;

        $buildingId = !empty($post['target_building_id']) ? (int)$post['target_building_id'] : null;
        $floorId = !empty($post['target_floor_id']) ? (int)$post['target_floor_id'] : null;
        $wardId = !empty($post['target_ward_id']) ? (int)$post['target_ward_id'] : null;
        $locationId = !empty($post['target_location_id']) ? (int)$post['target_location_id'] : null;

        $weekdays = !empty($post['weekdays']) && is_array($post['weekdays']) ? implode(',', $post['weekdays']) : null;
        $timeStart = !empty($post['time_window_start']) ? trim((string)$post['time_window_start']) : null;
        $timeEnd = !empty($post['time_window_end']) ? trim((string)$post['time_window_end']) : null;

        // Parse exclusions / holidays (comma-separated or lines)
        $exclusionsJson = null;
        if (!empty($post['exclusions_text'])) {
            $dates = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)$post['exclusions_text'])));
            $exclusionsJson = json_encode(array_values($dates));
        }

        if ($templateId <= 0) {
            Session::setFlash('error', 'Checklist template selection is required.');
            $this->redirect('/schedules/rules');
            return;
        }

        if ($endDate !== null && $endDate < $startDate) {
            Session::setFlash('error', 'Effective end date cannot precede start date.');
            $this->redirect('/schedules/rules');
            return;
        }

        $ruleId = $this->ruleModel->create([
            'template_id'        => $templateId,
            'frequency'          => $frequency,
            'selected_weekdays'  => $weekdays,
            'time_window_start'  => $timeStart,
            'time_window_end'    => $timeEnd,
            'shift_id'           => $shiftId,
            'assigned_user_id'   => $assignedUserId,
            'target_building_id' => $buildingId,
            'target_floor_id'    => $floorId,
            'target_ward_id'     => $wardId,
            'target_location_id' => $locationId,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'exclusions_json'    => $exclusionsJson,
            'status'             => 'active',
            'created_by'         => (int)Auth::id(),
        ]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_CREATED,
            "Created recurrence schedule rule #{$ruleId} for template #{$templateId} ({$frequency})",
            Auth::id(),
            ['rule_id' => $ruleId]
        );

        Session::setFlash('success', 'Recurrence schedule rule created successfully.');
        $this->redirect('/schedules/rules');
    }

    /**
     * Pause / Resume schedule rule
     */
    public function rulesToggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $rule = $this->ruleModel->find($id);

        if (!$rule) {
            Session::setFlash('error', 'Schedule rule not found.');
            $this->redirect('/schedules/rules');
            return;
        }

        $newStatus = ($rule['status'] === 'active') ? 'paused' : 'active';
        $this->ruleModel->update($id, ['status' => $newStatus]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed schedule rule #{$id} status to {$newStatus}",
            Auth::id(),
            ['rule_id' => $id, 'status' => $newStatus]
        );

        Session::setFlash('success', "Schedule rule status changed to {$newStatus}.");
        $this->redirect('/schedules/rules');
    }

    /**
     * Manually trigger schedule generator for today or target date (idempotent)
     */
    public function triggerGeneration(Request $request): void
    {
        $targetDate = trim((string)($request->post('target_date') ?? date('Y-m-d')));

        $res = InspectionScheduleService::generateSchedulesForDate($targetDate, (int)Auth::id());

        if (!empty($res['errors'])) {
            Session::setFlash('warning', "Schedule generation completed with issues: {$res['generated']} created, {$res['skipped_existing']} already existing. " . implode('; ', array_slice($res['errors'], 0, 2)));
        } else {
            Session::setFlash('success', "Schedule generation for {$targetDate} completed: {$res['generated']} new inspections scheduled, {$res['skipped_existing']} existing instances preserved idempotently.");
        }

        $this->redirect('/schedules');
    }
}
