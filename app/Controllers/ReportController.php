<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\CsvExportService;
use App\Services\ReportService;

/**
 * Report Management & Secure Export Controller
 */
class ReportController extends BaseController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    /**
     * Interactive Report Hub with Multi-criteria Filtering
     */
    public function index(Request $request): Response
    {
        $currentUser = Auth::user();
        $reportType = $request->query('type', 'inspections_summary');
        $validTypes = [
            'inspections_summary',
            'completion_report',
            'attendance_report',
            'exceptions_report',
            'failures_report',
            'sla_report'
        ];

        if (!in_array($reportType, $validTypes, true)) {
            $reportType = 'inspections_summary';
        }

        $filters = $request->all();
        $reportData = $this->fetchReportData($reportType, $filters, $currentUser);
        $options = $this->reportService->getFilterDropdownOptions($currentUser);

        return $this->view('reports/index', [
            'reportType'  => $reportType,
            'reportData'  => $reportData,
            'filters'     => $filters,
            'options'     => $options,
            'currentUser' => $currentUser,
            'title'       => 'Reports & Facility Analytics',
        ]);
    }

    /**
     * Secure CSV Export with CWE-1236 protection and server-side authorization enforcement
     */
    public function exportCsv(Request $request): void
    {
        $currentUser = Auth::user();
        $reportType = $request->query('type', 'inspections_summary');
        $filters = $request->all();

        $reportData = $this->fetchReportData($reportType, $filters, $currentUser);
        $rows = $reportData['rows'] ?? [];
        $dates = $reportData['dates'] ?? [];
        $dateSuffix = ($dates['start_date'] ?? date('Ymd')) . '_' . ($dates['end_date'] ?? date('Ymd'));

        [$filename, $headers, $formattedRows] = $this->formatExportData($reportType, $rows, $dateSuffix);

        CsvExportService::stream(
            $filename,
            $headers,
            $formattedRows,
            (int)$currentUser['id'],
            $reportType
        );
    }

    /**
     * Print-Friendly Report View & Browser PDF Export
     */
    public function printView(Request $request): Response
    {
        $currentUser = Auth::user();
        $reportType = $request->query('type', 'inspections_summary');
        $filters = $request->all();

        $reportData = $this->fetchReportData($reportType, $filters, $currentUser);

        return $this->view('reports/print', [
            'reportType'  => $reportType,
            'reportData'  => $reportData,
            'filters'     => $filters,
            'currentUser' => $currentUser,
            'title'       => 'Print Report - ' . ($reportData['title'] ?? 'Hospital Report'),
        ]);
    }

    /**
     * Dispatch data fetch based on active report type
     */
    private function fetchReportData(string $reportType, array $filters, array $currentUser): array
    {
        switch ($reportType) {
            case 'completion_report':
                return $this->reportService->getCompletionReport($filters, $currentUser);
            case 'attendance_report':
                return $this->reportService->getAttendanceReport($filters, $currentUser);
            case 'exceptions_report':
                return $this->reportService->getExceptionsReport($filters, $currentUser);
            case 'failures_report':
                return $this->reportService->getFailuresReport($filters, $currentUser);
            case 'sla_report':
                return $this->reportService->getSlaReport($filters, $currentUser);
            case 'inspections_summary':
            default:
                return $this->reportService->getInspectionSummaryReport($filters, $currentUser);
        }
    }

    /**
     * Format headers and cell rows for CSV export
     */
    private function formatExportData(string $reportType, array $rows, string $dateSuffix): array
    {
        $headers = [];
        $formatted = [];
        $filename = "hospital_report_{$reportType}_{$dateSuffix}.csv";

        switch ($reportType) {
            case 'completion_report':
                $filename = "completion_report_{$dateSuffix}.csv";
                $headers = [
                    'Building Code', 'Building Name', 'Floor', 'Ward Code', 'Ward Name',
                    'Total Scheduled', 'Completed', 'Pending', 'Missed',
                    'Completion Rate (%)', 'Avg Score (%)', 'Critical Issues'
                ];
                foreach ($rows as $r) {
                    $formatted[] = [
                        $r['building_code'],
                        $r['building_name'],
                        $r['floor_name'] . ' (Fl ' . $r['floor_number'] . ')',
                        $r['ward_code'],
                        $r['ward_name'],
                        $r['total_scheduled'],
                        $r['completed_count'],
                        $r['pending_count'],
                        $r['missed_count'],
                        $r['completion_rate'] . '%',
                        $r['avg_score'] !== null ? $r['avg_score'] . '%' : 'N/A',
                        $r['critical_issues']
                    ];
                }
                break;

            case 'attendance_report':
                $filename = "attendance_audit_{$dateSuffix}.csv";
                $headers = [
                    'Date', 'Supervisor Name', 'Employee ID', 'Shift Name',
                    'Check In', 'Check Out', 'Status', 'Verification Status',
                    'Distance to Hospital (m)', 'Within Geofence', 'Flag Reason'
                ];
                foreach ($rows as $r) {
                    $formatted[] = [
                        $r['attendance_date'],
                        $r['supervisor_name'],
                        $r['employee_id'],
                        $r['shift_name'] . " ({$r['shift_start']}-{$r['shift_end']})",
                        $r['check_in_time'],
                        $r['check_out_time'] ?? 'Active',
                        strtoupper((string)$r['status']),
                        ucwords(str_replace('_', ' ', (string)$r['verification_status'])),
                        $r['distance_to_hospital_meters'] !== null ? $r['distance_to_hospital_meters'] : 'N/A',
                        !empty($r['is_within_geofence']) ? 'Yes' : 'No',
                        $r['flag_reason'] ?? 'None'
                    ];
                }
                break;

            case 'exceptions_report':
                $filename = "inspection_exceptions_{$dateSuffix}.csv";
                $headers = [
                    'Schedule ID', 'Date', 'Time Window', 'Building', 'Floor', 'Ward',
                    'Shift', 'Assigned Supervisor', 'Status', 'Exception Category', 'Overdue'
                ];
                foreach ($rows as $r) {
                    $formatted[] = [
                        '#SCH-' . $r['id'],
                        $r['scheduled_date'],
                        ($r['scheduled_time_window_start'] ?? '00:00') . ' - ' . ($r['scheduled_time_window_end'] ?? '23:59'),
                        $r['building_name'],
                        $r['floor_name'],
                        $r['ward_name'] . ' (' . $r['ward_code'] . ')',
                        $r['shift_name'] ?? 'General',
                        $r['assigned_supervisor'] ?? 'Unassigned',
                        ucwords(str_replace('_', ' ', (string)$r['status'])),
                        $r['exception_type'],
                        !empty($r['is_overdue']) ? 'YES' : 'NO'
                    ];
                }
                break;

            case 'failures_report':
                $filename = "checklist_failures_{$dateSuffix}.csv";
                $headers = [
                    'Inspection Number', 'Date', 'Building', 'Floor', 'Ward',
                    'Inspector', 'Checklist Item', 'Severity', 'Response Value',
                    'Score Earned', 'Critical Failure', 'Inspector Remarks'
                ];
                foreach ($rows as $r) {
                    $formatted[] = [
                        $r['inspection_number'],
                        $r['inspection_date'],
                        $r['building_name'],
                        $r['floor_name'],
                        $r['ward_name'],
                        $r['inspector_name'],
                        $r['item_label_snapshot'] ?? $r['item_label'],
                        strtoupper((string)$r['severity']),
                        $r['response_value'],
                        $r['score_earned'],
                        !empty($r['is_critical_failure']) ? 'YES' : 'NO',
                        $r['remarks'] ?? ''
                    ];
                }
                break;

            case 'sla_report':
                $filename = "complaint_sla_report_{$dateSuffix}.csv";
                $headers = [
                    'Ticket #', 'Title', 'Category', 'Priority', 'Ward',
                    'Reporter', 'Status', 'Created At', 'Due Date', 'Resolved At',
                    'SLA Policy', 'SLA Breached'
                ];
                foreach ($rows as $r) {
                    $formatted[] = [
                        $r['ticket_number'],
                        $r['title'],
                        ucwords(str_replace('_', ' ', (string)$r['category'])),
                        strtoupper((string)$r['priority']),
                        $r['ward_name'] . ' (' . $r['building_code'] . ')',
                        $r['reporter_name'],
                        ucwords(str_replace('_', ' ', (string)$r['status'])),
                        $r['created_at'],
                        $r['due_date'] ?? 'N/A',
                        $r['resolved_at'] ?? 'Pending',
                        $r['policy_name'] ?? 'Standard Hospital SLA',
                        !empty($r['is_sla_breached']) ? 'BREACHED' : 'OK'
                    ];
                }
                break;

            case 'inspections_summary':
            default:
                $filename = "inspections_summary_{$dateSuffix}.csv";
                $headers = [
                    'Inspection #', 'Date & Time', 'Building', 'Floor', 'Ward',
                    'Shift', 'Inspector Name', 'Status', 'Score (%)',
                    'Passed Items', 'Failed Items', 'Critical Issues', 'QR Verified'
                ];
                foreach ($rows as $r) {
                    $formatted[] = [
                        $r['inspection_number'],
                        $r['started_at'],
                        $r['building_name'] . ' (' . $r['building_code'] . ')',
                        $r['floor_name'],
                        $r['ward_name'] . ' (' . $r['ward_code'] . ')',
                        $r['shift_name'] ?? 'General',
                        $r['inspector_name'],
                        ucwords(str_replace('_', ' ', (string)$r['status'])),
                        $r['score_percentage'] !== null ? $r['score_percentage'] . '%' : 'N/A',
                        $r['passed_items'],
                        $r['failed_items'],
                        $r['critical_issues_count'],
                        !empty($r['qr_verified']) ? 'YES' : 'NO'
                    ];
                }
                break;
        }

        return [$filename, $headers, $formatted];
    }
}
