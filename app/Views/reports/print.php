<?php
/**
 * Sheth L.G. General Hospital - Print-Friendly Report View & Browser PDF Export
 * @var array $reportData
 * @var string $reportType
 * @var array $filters
 * @var array $currentUser
 */

$kpis = $reportData['kpis'] ?? [];
$rows = $reportData['rows'] ?? [];
$dates = $reportData['dates'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sheth L.G. General Hospital - Report') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background: #ffffff;
            font-size: 11pt;
        }
        .hospital-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .report-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 600;
        }
        .table-print {
            font-size: 9pt;
            width: 100%;
            border-collapse: collapse;
        }
        .table-print th {
            background-color: #f1f5f9 !important;
            color: #334155;
            border-bottom: 2px solid #cbd5e1;
            padding: 6px 8px;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .table-print td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 8px;
        }
        .table-print tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .signature-box {
            border-top: 1px dashed #94a3b8;
            padding-top: 6px;
            margin-top: 40px;
            text-align: center;
            font-size: 9pt;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: none !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="p-3">

    <!-- Screen Control Buttons (Hidden on print) -->
    <div class="no-print d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded border">
        <div>
            <span class="fw-bold text-navy-dark"><i class="bi bi-printer"></i> Print-Ready Report</span> &bull; 
            <span class="text-muted small">Formatted for A4 paper and "Save as PDF"</span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-printer-fill"></i> Print / Save PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
                Close Window
            </button>
        </div>
    </div>

    <!-- Official Hospital Letterhead -->
    <div class="hospital-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 text-uppercase tracking-wide" style="color: #0f172a;">Sheth L.G. General Hospital</h4>
            <div class="small text-muted">AMC Medical Education Trust &bull; Maninagar, Ahmedabad - 380008, Gujarat, India</div>
            <div class="small fw-semibold text-primary mt-1"><?= e($reportData['title'] ?? 'Official Facility Inspection & Quality Report') ?></div>
        </div>
        <div class="text-end">
            <div class="badge bg-dark px-2 py-1 text-uppercase" style="font-size: 8pt;">Official Audit Document</div>
            <div class="small text-muted mt-1">Generated: <?= date('d M Y, h:i A') ?> IST</div>
            <div class="small text-muted">Auditor: <?= e($currentUser['name'] ?? 'System') ?> (<?= ucfirst($currentUser['role'] ?? 'Staff') ?>)</div>
        </div>
    </div>

    <!-- Applied Criteria Summary -->
    <div class="bg-light p-2 rounded border mb-3 small d-flex justify-content-between align-items-center">
        <div>
            <strong>Reporting Window:</strong> <?= e($dates['start_date']) ?> to <?= e($dates['end_date']) ?>
            &bull; <strong>Scope:</strong> <?= ($currentUser['role'] === 'supervisor') ? 'Assigned Facility Work' : 'Hospital Campus Wide' ?>
        </div>
        <div>
            <strong>Total Records:</strong> <?= count($rows) ?>
        </div>
    </div>

    <!-- KPI Summary Row for Print -->
    <?php if (!empty($kpis)): ?>
        <div class="row g-2 mb-3 text-center small">
            <?php foreach ($kpis as $kName => $kVal): ?>
                <div class="col">
                    <div class="border rounded p-2 bg-light">
                        <div class="text-muted text-uppercase" style="font-size: 7.5pt;"><?= ucwords(str_replace('_', ' ', $kName)) ?></div>
                        <div class="fw-bold fs-6 mt-1"><?= is_numeric($kVal) ? (is_float($kVal) ? number_format($kVal, 1) : number_format($kVal)) : e((string)$kVal) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Printable Table -->
    <table class="table-print mb-4">
        <thead>
            <?php if ($reportType === 'inspections_summary'): ?>
                <tr>
                    <th>Audit #</th>
                    <th>Date / Time</th>
                    <th>Ward / Location</th>
                    <th>Shift</th>
                    <th>Inspector</th>
                    <th>Status</th>
                    <th class="text-end">Score</th>
                    <th class="text-center">Verification</th>
                </tr>
            <?php elseif ($reportType === 'completion_report'): ?>
                <tr>
                    <th>Ward Location</th>
                    <th>Building</th>
                    <th>Floor</th>
                    <th class="text-center">Scheduled</th>
                    <th class="text-center">Completed</th>
                    <th class="text-center">Pending</th>
                    <th class="text-center">Missed</th>
                    <th class="text-end">Rate (%)</th>
                </tr>
            <?php elseif ($reportType === 'attendance_report'): ?>
                <tr>
                    <th>Date</th>
                    <th>Supervisor</th>
                    <th>Shift</th>
                    <th>Check In</th>
                    <th>Status</th>
                    <th>Verification</th>
                    <th class="text-end">Geofence Dist.</th>
                    <th>Flag Reason</th>
                </tr>
            <?php elseif ($reportType === 'exceptions_report'): ?>
                <tr>
                    <th>Schedule #</th>
                    <th>Date</th>
                    <th>Window</th>
                    <th>Location</th>
                    <th>Supervisor</th>
                    <th>Status</th>
                    <th>Exception</th>
                    <th>Overdue Alert</th>
                </tr>
            <?php elseif ($reportType === 'failures_report'): ?>
                <tr>
                    <th>Audit #</th>
                    <th>Date</th>
                    <th>Ward</th>
                    <th>Inspector</th>
                    <th>Checklist Item</th>
                    <th>Severity</th>
                    <th>Response</th>
                    <th>Critical Finding</th>
                </tr>
            <?php elseif ($reportType === 'sla_report'): ?>
                <tr>
                    <th>Ticket #</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Ward</th>
                    <th>Reporter</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>SLA Status</th>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="8" class="text-center py-3 text-muted">No records match the requested criteria.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php if ($reportType === 'inspections_summary'): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['inspection_number']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($r['started_at'])) ?></td>
                            <td><?= e($r['ward_name']) ?> (<?= e($r['building_code']) ?>)</td>
                            <td><?= e($r['shift_name'] ?? 'General') ?></td>
                            <td><?= e($r['inspector_name']) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $r['status'])) ?></td>
                            <td class="text-end fw-bold"><?= $r['score_percentage'] !== null ? number_format((float)$r['score_percentage'], 1) . '%' : 'N/A' ?></td>
                            <td class="text-center"><?= !empty($r['qr_verified']) ? 'QR Verified' : 'Manual' ?></td>
                        </tr>
                    <?php elseif ($reportType === 'completion_report'): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['ward_name']) ?> (<?= e($r['ward_code']) ?>)</td>
                            <td><?= e($r['building_name']) ?></td>
                            <td>Floor <?= e($r['floor_number']) ?></td>
                            <td class="text-center"><?= (int)$r['total_scheduled'] ?></td>
                            <td class="text-center text-success fw-bold"><?= (int)$r['completed_count'] ?></td>
                            <td class="text-center"><?= (int)$r['pending_count'] ?></td>
                            <td class="text-center text-danger"><?= (int)$r['missed_count'] ?></td>
                            <td class="text-end fw-bold"><?= number_format((float)$r['completion_rate'], 1) ?>%</td>
                        </tr>
                    <?php elseif ($reportType === 'attendance_report'): ?>
                        <tr>
                            <td><?= e($r['attendance_date']) ?></td>
                            <td class="fw-bold"><?= e($r['supervisor_name']) ?> (<?= e($r['employee_id']) ?>)</td>
                            <td><?= e($r['shift_name']) ?></td>
                            <td><?= date('h:i A', strtotime($r['check_in_time'])) ?></td>
                            <td><?= strtoupper($r['status']) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $r['verification_status'])) ?></td>
                            <td class="text-end"><?= $r['distance_to_hospital_meters'] !== null ? number_format((float)$r['distance_to_hospital_meters'], 1) . ' m' : 'N/A' ?></td>
                            <td><?= e($r['flag_reason'] ?? 'None') ?></td>
                        </tr>
                    <?php elseif ($reportType === 'exceptions_report'): ?>
                        <tr>
                            <td class="fw-bold">#SCH-<?= e($r['id']) ?></td>
                            <td><?= e($r['scheduled_date']) ?></td>
                            <td><?= e($r['scheduled_time_window_start'] ?? '00:00') ?>-<?= e($r['scheduled_time_window_end'] ?? '23:59') ?></td>
                            <td><?= e($r['ward_name']) ?> (<?= e($r['building_code']) ?>)</td>
                            <td><?= e($r['assigned_supervisor'] ?? 'Unassigned') ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $r['status'])) ?></td>
                            <td class="fw-bold"><?= e($r['exception_type']) ?></td>
                            <td><?= !empty($r['is_overdue']) ? 'OVERDUE' : 'Pending' ?></td>
                        </tr>
                    <?php elseif ($reportType === 'failures_report'): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['inspection_number']) ?></td>
                            <td><?= date('d M Y', strtotime($r['inspection_date'])) ?></td>
                            <td><?= e($r['ward_name']) ?></td>
                            <td><?= e($r['inspector_name']) ?></td>
                            <td><?= e($r['item_label_snapshot'] ?? $r['item_label']) ?></td>
                            <td><?= strtoupper($r['severity']) ?></td>
                            <td><?= e($r['response_value']) ?></td>
                            <td class="fw-bold text-danger"><?= !empty($r['is_critical_failure']) ? 'CRITICAL' : 'Non-Compliant' ?></td>
                        </tr>
                    <?php elseif ($reportType === 'sla_report'): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['ticket_number']) ?></td>
                            <td><?= e($r['title']) ?></td>
                            <td><?= strtoupper($r['priority']) ?></td>
                            <td><?= e($r['ward_name']) ?></td>
                            <td><?= e($r['reporter_name']) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $r['status'])) ?></td>
                            <td><?= $r['due_date'] ? date('d M Y, h:i A', strtotime($r['due_date'])) : 'N/A' ?></td>
                            <td class="fw-bold <?= !empty($r['is_sla_breached']) ? 'text-danger' : 'text-success' ?>">
                                <?= !empty($r['is_sla_breached']) ? 'BREACHED' : 'WITHIN SLA' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Hospital Sign-Off Authentication Block -->
    <div class="row mt-5 pt-3">
        <div class="col-4">
            <div class="signature-box">
                <strong>Reporting Officer / Supervisor</strong><br>
                <span>Prepared & Certified</span>
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <strong>Quality Assurance In-Charge</strong><br>
                <span>Hospital Quality Cell</span>
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <strong>Medical Superintendent</strong><br>
                <span>Sheth L.G. General Hospital</span>
            </div>
        </div>
    </div>

</body>
</html>
