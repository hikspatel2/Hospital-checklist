<?php
/**
 * Sheth L.G. General Hospital - Reports & Facility Analytics Console
 * @var array $reportData
 * @var string $reportType
 * @var array $filters
 * @var array $options
 * @var array $currentUser
 */

$kpis = $reportData['kpis'] ?? [];
$rows = $reportData['rows'] ?? [];
$dates = $reportData['dates'] ?? [];
$activePreset = $dates['preset'] ?? 'this_month';

// Build query string for export/print preserving active filters
$queryParams = $filters;
$queryParams['type'] = $reportType;
$exportUrl = url('/reports/export?' . http_build_query($queryParams));
$printUrl = url('/reports/print?' . http_build_query($queryParams));
?>

<div class="container-fluid px-3 px-md-4 py-3">
    <!-- Header with Hospital Branding & Export Actions -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-3 border-bottom gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1"><i class="bi bi-file-earmark-bar-graph"></i> Phase 8</span>
                <h4 class="mb-0 fw-bold text-navy-dark">Reports & Facility Analytics</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Sheth L.G. General Hospital &bull; Compliance, Attendance, Exceptions & SLA Performance Metrics
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= e($printUrl) ?>" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-printer"></i>
                <span>Print / Save PDF</span>
            </a>
            <a href="<?= e($exportUrl) ?>" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span>Export CSV (CWE-1236 Safe)</span>
            </a>
        </div>
    </div>

    <!-- Multi-Criteria Filter Card -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('/reports') ?>" id="reportFilterForm" class="row g-2 align-items-end">
                <input type="hidden" name="type" value="<?= e($reportType) ?>">

                <!-- Date Range Preset -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Time Period</label>
                    <select name="date_preset" id="datePresetSelect" class="form-select form-select-sm" onchange="toggleCustomDates()">
                        <option value="today" <?= $activePreset === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="yesterday" <?= $activePreset === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                        <option value="this_week" <?= $activePreset === 'this_week' ? 'selected' : '' ?>>This Week</option>
                        <option value="this_month" <?= $activePreset === 'this_month' ? 'selected' : '' ?>>This Month</option>
                        <option value="last_month" <?= $activePreset === 'last_month' ? 'selected' : '' ?>>Last Month</option>
                        <option value="custom" <?= $activePreset === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Custom Start & End Dates -->
                <div class="col-6 col-md-2" id="startDateCol" style="<?= $activePreset === 'custom' ? '' : 'display:none;' ?>">
                    <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($dates['start_date'] ?? '') ?>">
                </div>

                <div class="col-6 col-md-2" id="endDateCol" style="<?= $activePreset === 'custom' ? '' : 'display:none;' ?>">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($dates['end_date'] ?? '') ?>">
                </div>

                <!-- Building Dropdown -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Building</label>
                    <select name="building_id" class="form-select form-select-sm">
                        <option value="">All Buildings</option>
                        <?php foreach ($options['buildings'] as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= (!empty($filters['building_id']) && (int)$filters['building_id'] === (int)$b['id']) ? 'selected' : '' ?>>
                                <?= e($b['code']) ?> - <?= e($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ward Dropdown -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Ward</label>
                    <select name="ward_id" class="form-select form-select-sm">
                        <option value="">All Wards</option>
                        <?php foreach ($options['wards'] as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= (!empty($filters['ward_id']) && (int)$filters['ward_id'] === (int)$w['id']) ? 'selected' : '' ?>>
                                <?= e($w['code']) ?> - <?= e($w['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Shift Dropdown -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Shift</label>
                    <select name="shift_id" class="form-select form-select-sm">
                        <option value="">All Shifts</option>
                        <?php foreach ($options['shifts'] as $sh): ?>
                            <option value="<?= $sh['id'] ?>" <?= (!empty($filters['shift_id']) && (int)$filters['shift_id'] === (int)$sh['id']) ? 'selected' : '' ?>>
                                <?= e($sh['name']) ?> (<?= substr($sh['start_time'], 0, 5) ?>-<?= substr($sh['end_time'], 0, 5) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                    <!-- Supervisor Dropdown (Admin only) -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Supervisor</label>
                        <select name="supervisor_id" class="form-select form-select-sm">
                            <option value="">All Supervisors</option>
                            <?php foreach ($options['supervisors'] as $sup): ?>
                                <option value="<?= $sup['id'] ?>" <?= (!empty($filters['supervisor_id']) && (int)$filters['supervisor_id'] === (int)$sup['id']) ? 'selected' : '' ?>>
                                    <?= e($sup['name']) ?> (<?= e($sup['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Filter Actions -->
                <div class="col-12 col-md-auto ms-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-funnel"></i> Apply Filter
                    </button>
                    <a href="<?= url('/reports?type=' . urlencode($reportType)) ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Type Navigation Tabs -->
    <ul class="nav nav-pills report-nav-tabs mb-4 pb-2 border-bottom flex-nowrap overflow-auto" id="reportTabs">
        <?php
        $tabs = [
            'inspections_summary' => ['icon' => 'bi-clipboard-data', 'label' => 'Inspections Summary'],
            'completion_report'   => ['icon' => 'bi-bar-chart-steps', 'label' => 'Floor/Ward Completion'],
            'attendance_report'   => ['icon' => 'bi-person-check', 'label' => 'Supervisor Attendance'],
            'exceptions_report'   => ['icon' => 'bi-clock-history', 'label' => 'Exceptions & Missed'],
            'failures_report'     => ['icon' => 'bi-exclamation-octagon', 'label' => 'Checklist Failures'],
            'sla_report'          => ['icon' => 'bi-shield-exclamation', 'label' => 'Complaints & SLA'],
        ];
        ?>
        <?php foreach ($tabs as $tKey => $tData): ?>
            <?php
            $tabFilter = $filters;
            $tabFilter['type'] = $tKey;
            $isActive = $reportType === $tKey;
            ?>
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-semibold small d-flex align-items-center gap-2 text-nowrap <?= $isActive ? 'active shadow-sm' : 'text-secondary' ?>"
                   href="<?= url('/reports?' . http_build_query($tabFilter)) ?>">
                    <i class="bi <?= e($tData['icon']) ?>"></i>
                    <span><?= e($tData['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- KPI Metric Cards Bar -->
    <div class="row g-3 mb-4">
        <?php if ($reportType === 'inspections_summary'): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Total Audits</div>
                    <div class="fs-4 fw-bold text-navy-dark mt-1"><?= number_format($kpis['total_inspections'] ?? 0) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-calendar-event"></i> <?= e($dates['start_date']) ?> to <?= e($dates['end_date']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Completed</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= number_format($kpis['completed'] ?? 0) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-check-circle"></i> Submitted/Approved</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Average Score</div>
                    <div class="fs-4 fw-bold text-info mt-1"><?= number_format($kpis['avg_score'] ?? 0, 1) ?>%</div>
                    <div class="small text-muted mt-1"><i class="bi bi-qr-code-scan"></i> QR Rate: <?= number_format($kpis['qr_verified_rate'] ?? 0, 1) ?>%</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Critical Findings</div>
                    <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($kpis['total_critical'] ?? 0) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-x-circle"></i> Failed Items: <?= number_format($kpis['total_failed'] ?? 0) ?></div>
                </div>
            </div>

        <?php elseif ($reportType === 'completion_report'): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Total Scheduled</div>
                    <div class="fs-4 fw-bold text-navy-dark mt-1"><?= number_format($kpis['total_scheduled'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Completed</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= number_format($kpis['total_completed'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Pending Action</div>
                    <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($kpis['total_pending'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-teal border-4" style="border-color: #0d9488 !important;">
                    <div class="text-muted small text-uppercase fw-semibold">Completion Rate</div>
                    <div class="fs-4 fw-bold text-teal mt-1" style="color: #0d9488;"><?= number_format($kpis['completion_rate'] ?? 0, 1) ?>%</div>
                </div>
            </div>

        <?php elseif ($reportType === 'attendance_report'): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Total Check-Ins</div>
                    <div class="fs-4 fw-bold text-navy-dark mt-1"><?= number_format($kpis['total_attendance'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                    <div class="text-muted small text-uppercase fw-semibold">GPS Verified</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= number_format($kpis['verified_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Flagged / Suspicious</div>
                    <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($kpis['flagged_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Late Arrivals</div>
                    <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($kpis['late_count'] ?? 0) ?></div>
                </div>
            </div>

        <?php elseif ($reportType === 'exceptions_report'): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Total Exceptions</div>
                    <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($kpis['total_exceptions'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-dark border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Missed Inspections</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($kpis['missed_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Overdue</div>
                    <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($kpis['overdue_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Pending Action</div>
                    <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($kpis['pending_count'] ?? 0) ?></div>
                </div>
            </div>

        <?php elseif ($reportType === 'failures_report'): ?>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Total Failures</div>
                    <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($kpis['total_failures'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm p-3 border-start border-dark border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Critical Findings</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($kpis['critical_findings'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Affected Inspections</div>
                    <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($kpis['affected_audits'] ?? 0) ?></div>
                </div>
            </div>

        <?php elseif ($reportType === 'sla_report'): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Total Complaints</div>
                    <div class="fs-4 fw-bold text-navy-dark mt-1"><?= number_format($kpis['total_complaints'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                    <div class="text-muted small text-uppercase fw-semibold">Resolved</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= number_format($kpis['resolved_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                    <div class="text-muted small text-uppercase fw-semibold">SLA Breaches</div>
                    <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($kpis['sla_breaches'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
                    <div class="text-muted small text-uppercase fw-semibold">SLA Compliance Rate</div>
                    <div class="fs-4 fw-bold text-info mt-1"><?= number_format($kpis['compliance_rate'] ?? 0, 1) ?>%</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Data Table Container -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-navy-dark d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i>
                <span><?= e($reportData['title'] ?? 'Report Records') ?></span>
                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2">
                    <?= count($rows) ?> records
                </span>
            </h6>
            <div class="small text-muted">
                <i class="bi bi-clock"></i> Generated: <?= date('d M Y, h:i A') ?> IST
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted fs-1 d-block mb-2"></i>
                    <h6 class="text-muted fw-semibold">No records found matching current criteria</h6>
                    <p class="text-muted small mb-0">Try adjusting the date range or removing specific filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase text-muted border-bottom">
                            <?php if ($reportType === 'inspections_summary'): ?>
                                <tr>
                                    <th>Inspection #</th>
                                    <th>Date & Time</th>
                                    <th>Facility Ward</th>
                                    <th>Shift</th>
                                    <th>Inspector</th>
                                    <th>Status</th>
                                    <th class="text-end">Score</th>
                                    <th class="text-center">Verification</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            <?php elseif ($reportType === 'completion_report'): ?>
                                <tr>
                                    <th>Facility Location</th>
                                    <th>Building</th>
                                    <th>Floor</th>
                                    <th class="text-center">Scheduled</th>
                                    <th class="text-center">Completed</th>
                                    <th class="text-center">Pending</th>
                                    <th class="text-center">Missed</th>
                                    <th class="text-end">Avg Score</th>
                                    <th class="text-end">Completion Rate</th>
                                </tr>
                            <?php elseif ($reportType === 'attendance_report'): ?>
                                <tr>
                                    <th>Date</th>
                                    <th>Supervisor</th>
                                    <th>Shift</th>
                                    <th>Check In / Out</th>
                                    <th>Status</th>
                                    <th>Verification</th>
                                    <th class="text-end">Geofence Dist.</th>
                                    <th>Flags</th>
                                </tr>
                            <?php elseif ($reportType === 'exceptions_report'): ?>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Scheduled Date</th>
                                    <th>Time Window</th>
                                    <th>Location</th>
                                    <th>Supervisor</th>
                                    <th>Status</th>
                                    <th>Exception Type</th>
                                    <th>Overdue Alert</th>
                                </tr>
                            <?php elseif ($reportType === 'failures_report'): ?>
                                <tr>
                                    <th>Inspection #</th>
                                    <th>Date</th>
                                    <th>Ward</th>
                                    <th>Inspector</th>
                                    <th>Checklist Item</th>
                                    <th>Severity</th>
                                    <th>Response</th>
                                    <th class="text-center">Critical Finding</th>
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
                        <tbody class="small">
                            <?php if ($reportType === 'inspections_summary'): ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="fw-semibold text-primary">
                                            <a href="<?= url('/inspections/' . $r['id']) ?>" class="text-decoration-none">
                                                <?= e($r['inspection_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= date('d M Y, h:i A', strtotime($r['started_at'])) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($r['ward_name']) ?> (<?= e($r['ward_code']) ?>)</div>
                                            <div class="text-muted" style="font-size:0.75rem;"><?= e($r['building_name']) ?> &bull; <?= e($r['floor_name']) ?></div>
                                        </td>
                                        <td><?= e($r['shift_name'] ?? 'General') ?></td>
                                        <td><?= e($r['inspector_name']) ?></td>
                                        <td>
                                            <?php
                                            $stClass = match($r['status']) {
                                                'approved', 'submitted' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                'under_review' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                                                'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                default => 'bg-secondary bg-opacity-10 text-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $stClass ?> px-2 py-1 rounded-pill">
                                                <?= ucwords(str_replace('_', ' ', $r['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold <?= ((float)$r['score_percentage'] >= 80) ? 'text-success' : 'text-danger' ?>">
                                            <?= $r['score_percentage'] !== null ? number_format((float)$r['score_percentage'], 1) . '%' : 'N/A' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($r['qr_verified'])): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill" title="QR Token Verified">
                                                    <i class="bi bi-qr-code"></i> QR Verified
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill">
                                                    Manual
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('/inspections/' . $r['id']) ?>" class="btn btn-outline-primary btn-sm py-0 px-2" title="View Full Inspection Audit">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php elseif ($reportType === 'completion_report'): ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-navy-dark"><?= e($r['ward_name']) ?></div>
                                            <div class="text-muted" style="font-size:0.75rem;">Code: <?= e($r['ward_code']) ?></div>
                                        </td>
                                        <td><?= e($r['building_name']) ?> (<?= e($r['building_code']) ?>)</td>
                                        <td><?= e($r['floor_name']) ?> (Fl <?= e($r['floor_number']) ?>)</td>
                                        <td class="text-center fw-semibold"><?= (int)$r['total_scheduled'] ?></td>
                                        <td class="text-center text-success fw-semibold"><?= (int)$r['completed_count'] ?></td>
                                        <td class="text-center text-warning fw-semibold"><?= (int)$r['pending_count'] ?></td>
                                        <td class="text-center text-danger fw-semibold"><?= (int)$r['missed_count'] ?></td>
                                        <td class="text-end fw-bold">
                                            <?= $r['avg_score'] !== null ? number_format((float)$r['avg_score'], 1) . '%' : 'N/A' ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                    <div class="progress-bar bg-success" style="width: <?= (float)$r['completion_rate'] ?>%"></div>
                                                </div>
                                                <span class="fw-bold"><?= number_format((float)$r['completion_rate'], 1) ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php elseif ($reportType === 'attendance_report'): ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($r['attendance_date']) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($r['supervisor_name']) ?></div>
                                            <div class="text-muted" style="font-size:0.75rem;">ID: <?= e($r['employee_id']) ?></div>
                                        </td>
                                        <td><?= e($r['shift_name']) ?> (<?= substr($r['shift_start'], 0, 5) ?>-<?= substr($r['shift_end'], 0, 5) ?>)</td>
                                        <td>
                                            <div><i class="bi bi-box-arrow-in-right text-success"></i> <?= date('h:i A', strtotime($r['check_in_time'])) ?></div>
                                            <?php if ($r['check_out_time']): ?>
                                                <div class="text-muted"><i class="bi bi-box-arrow-left text-secondary"></i> <?= date('h:i A', strtotime($r['check_out_time'])) ?></div>
                                            <?php else: ?>
                                                <div class="badge bg-primary bg-opacity-10 text-primary">Active On-duty</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $r['status'] === 'present' ? 'success' : ($r['status'] === 'late' ? 'warning' : 'danger') ?> bg-opacity-10 text-<?= $r['status'] === 'present' ? 'success' : ($r['status'] === 'late' ? 'warning' : 'danger') ?> px-2 py-1 rounded-pill">
                                                <?= strtoupper($r['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($r['verification_status'] === 'verified'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill">
                                                    <i class="bi bi-shield-check"></i> GPS Verified
                                                </span>
                                            <?php elseif ($r['verification_status'] === 'suspicious_gps'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-pill">
                                                    <i class="bi bi-geo-alt-fill"></i> Suspicious GPS
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info bg-opacity-10 text-info px-2 py-1 rounded-pill">
                                                    <?= ucwords(str_replace('_', ' ', $r['verification_status'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?= $r['distance_to_hospital_meters'] !== null ? number_format((float)$r['distance_to_hospital_meters'], 1) . ' m' : 'N/A' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($r['is_flagged'])): ?>
                                                <span class="badge bg-danger text-white px-2 py-1" title="<?= e($r['flag_reason'] ?? 'Flagged review') ?>">
                                                    <i class="bi bi-flag-fill"></i> Flagged
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">None</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php elseif ($reportType === 'exceptions_report'): ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="fw-semibold text-primary">#SCH-<?= e($r['id']) ?></td>
                                        <td><?= e($r['scheduled_date']) ?></td>
                                        <td><?= e($r['scheduled_time_window_start'] ?? '00:00') ?> - <?= e($r['scheduled_time_window_end'] ?? '23:59') ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($r['ward_name']) ?> (<?= e($r['ward_code']) ?>)</div>
                                            <div class="text-muted" style="font-size:0.75rem;"><?= e($r['building_name']) ?> &bull; <?= e($r['floor_name']) ?></div>
                                        </td>
                                        <td><?= e($r['assigned_supervisor'] ?? 'Unassigned') ?></td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill">
                                                <?= ucwords(str_replace('_', ' ', $r['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $exBadge = match($r['exception_type']) {
                                                'Missed' => 'bg-danger text-white',
                                                'Overdue' => 'bg-warning text-dark',
                                                default => 'bg-info bg-opacity-10 text-info'
                                            };
                                            ?>
                                            <span class="badge <?= $exBadge ?> px-2 py-1 rounded-pill">
                                                <?= e($r['exception_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($r['is_overdue'])): ?>
                                                <span class="text-danger fw-bold"><i class="bi bi-alarm-fill"></i> OVERDUE</span>
                                            <?php else: ?>
                                                <span class="text-muted">On Schedule</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php elseif ($reportType === 'failures_report'): ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="fw-semibold text-primary">
                                            <a href="<?= url('/inspections/' . $r['inspection_id']) ?>" class="text-decoration-none">
                                                <?= e($r['inspection_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= date('d M Y, h:i A', strtotime($r['inspection_date'])) ?></td>
                                        <td><?= e($r['ward_name']) ?> (<?= e($r['ward_code']) ?>)</td>
                                        <td><?= e($r['inspector_name']) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($r['item_label_snapshot'] ?? $r['item_label']) ?></div>
                                            <?php if (!empty($r['remarks'])): ?>
                                                <div class="text-danger small italic"><i class="bi bi-chat-left-quote"></i> "<?= e($r['remarks']) ?>"</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $r['severity'] === 'critical' ? 'danger' : 'warning' ?> bg-opacity-10 text-<?= $r['severity'] === 'critical' ? 'danger' : 'dark' ?> px-2 py-1 rounded-pill">
                                                <?= strtoupper($r['severity']) ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-secondary bg-opacity-25 text-dark"><?= e($r['response_value']) ?></span></td>
                                        <td class="text-center">
                                            <?php if (!empty($r['is_critical_failure'])): ?>
                                                <span class="badge bg-danger text-white px-2 py-1 rounded-pill">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> CRITICAL
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-dark px-2 py-1 rounded-pill">
                                                    Non-Compliant
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php elseif ($reportType === 'sla_report'): ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="fw-semibold text-primary">
                                            <a href="<?= url('/complaints/' . $r['id']) ?>" class="text-decoration-none">
                                                <?= e($r['ticket_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= e($r['title']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $r['priority'] === 'critical' ? 'danger' : ($r['priority'] === 'high' ? 'warning' : 'secondary') ?> px-2 py-1 rounded-pill">
                                                <?= strtoupper($r['priority']) ?>
                                            </span>
                                        </td>
                                        <td><?= e($r['ward_name']) ?> (<?= e($r['building_code']) ?>)</td>
                                        <td><?= e($r['reporter_name']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill">
                                                <?= ucwords(str_replace('_', ' ', $r['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $r['due_date'] ? date('d M Y, h:i A', strtotime($r['due_date'])) : 'N/A' ?></td>
                                        <td>
                                            <?php if (!empty($r['is_sla_breached'])): ?>
                                                <span class="badge bg-danger text-white px-2 py-1 rounded-pill">
                                                    <i class="bi bi-exclamation-octagon-fill"></i> BREACHED
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill">
                                                    <i class="bi bi-check-circle"></i> WITHIN SLA
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleCustomDates() {
    var preset = document.getElementById('datePresetSelect').value;
    var startCol = document.getElementById('startDateCol');
    var endCol = document.getElementById('endDateCol');
    if (preset === 'custom') {
        startCol.style.display = 'block';
        endCol.style.display = 'block';
    } else {
        startCol.style.display = 'none';
        endCol.style.display = 'none';
    }
}
</script>
