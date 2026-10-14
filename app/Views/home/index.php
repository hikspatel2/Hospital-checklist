<?php
/** @var array $filters */
/** @var array $inspectionMetrics */
/** @var array $attendanceMetrics */
/** @var array $complaintMetrics */
/** @var array $matrix */
/** @var array $groupedMatrix */
/** @var array $buildings */
/** @var array $floors */
/** @var array $wards */
/** @var array $shifts */
/** @var array $supervisors */
/** @var array $currentUser */
?>
<div class="container-fluid px-0">
    <!-- Header: Hospital Title & Live Operations Clock -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Phase 7 Matrix</span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Live IST</span>
            </div>
            <h4 class="fw-bold mb-0 text-navy-dark mt-1">Sheth L.G. General Hospital, Ahmedabad</h4>
            <p class="text-muted small mb-0">Multi-Floor Facility Inspection & Complaint Command Matrix &bull; Maninagar, Ahmedabad</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= url('/floor-plans') ?>" class="btn btn-outline-primary btn-sm rounded-3 shadow-sm">
                <i class="bi bi-map me-1"></i> Interactive Floor Plan
            </a>
            <a href="<?= url('/complaints/create') ?>" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm">
                <i class="bi bi-exclamation-triangle me-1"></i> Lodge Complaint
            </a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <a href="<?= url('/schedules') ?>" class="btn btn-navy btn-sm rounded-3 px-3 shadow-sm">
                    <i class="bi bi-calendar-check me-1"></i> Schedules
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Multi-Criteria Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('/') ?>" class="row g-2 align-items-center">
                <!-- Date -->
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1 fw-semibold">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm bg-light border-0" value="<?= e($filters['date']) ?>">
                </div>
                <!-- Shift -->
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1 fw-semibold">Shift</label>
                    <select name="shift_id" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Shifts</option>
                        <?php foreach ($shifts as $sh): ?>
                            <option value="<?= $sh['id'] ?>" <?= ((string)$filters['shift_id'] === (string)$sh['id']) ? 'selected' : '' ?>>
                                <?= e($sh['shift_name']) ?> (<?= substr($sh['start_time'], 0, 5) ?> - <?= substr($sh['end_time'], 0, 5) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Building -->
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1 fw-semibold">Building</label>
                    <select name="building_id" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Buildings</option>
                        <?php foreach ($buildings as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ((string)$filters['building_id'] === (string)$b['id']) ? 'selected' : '' ?>>
                                <?= e($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Floor -->
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1 fw-semibold">Floor</label>
                    <select name="floor_id" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Floors</option>
                        <?php foreach ($floors as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= ((string)$filters['floor_id'] === (string)$f['id']) ? 'selected' : '' ?>>
                                <?= e($f['floor_name']) ?> (Fl <?= $f['floor_number'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Supervisor (Admin only) -->
                <?php if (\App\Core\Auth::isAdmin()): ?>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1 fw-semibold">Supervisor</label>
                        <select name="supervisor_id" class="form-select form-select-sm bg-light border-0">
                            <option value="">All Supervisors</option>
                            <?php foreach ($supervisors as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ((string)$filters['supervisor_id'] === (string)$s['id']) ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <!-- Actions -->
                <div class="<?= \App\Core\Auth::isAdmin() ? 'col-md-2' : 'col-md-4' ?> d-flex gap-2 align-items-end pt-3">
                    <button type="submit" class="btn btn-navy btn-sm rounded-3 flex-grow-1 shadow-sm">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>
                    <a href="<?= url('/') ?>" class="btn btn-light btn-sm rounded-3 px-3" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Row 1: Today's Inspection Matrix KPIs -->
    <div class="row g-3 mb-4">
        <!-- Total Scheduled -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Scheduled</span>
                    <span class="p-2 bg-navy bg-opacity-10 text-navy-dark rounded-3"><i class="bi bi-calendar-check"></i></span>
                </div>
                <h3 class="fw-bold text-navy-dark mb-0"><?= number_format($inspectionMetrics['total_scheduled']) ?></h3>
                <span class="small text-muted mt-1 d-block">Facility Rounds</span>
            </div>
        </div>

        <!-- Completed Inspections -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Completed</span>
                    <span class="p-2 bg-success bg-opacity-10 text-success rounded-3"><i class="bi bi-check-circle-fill"></i></span>
                </div>
                <h3 class="fw-bold text-success mb-0"><?= number_format($inspectionMetrics['completed']) ?></h3>
                <span class="small text-success mt-1 d-block fw-semibold"><?= $inspectionMetrics['compliance_rate'] ?>% Compliance</span>
            </div>
        </div>

        <!-- Pending Inspections -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Pending</span>
                    <span class="p-2 bg-warning bg-opacity-10 text-warning-emphasis rounded-3"><i class="bi bi-clock-history"></i></span>
                </div>
                <h3 class="fw-bold text-warning mb-0"><?= number_format($inspectionMetrics['pending']) ?></h3>
                <span class="small text-muted mt-1 d-block">Awaiting Review</span>
            </div>
        </div>

        <!-- Overdue Inspections -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Overdue</span>
                    <span class="p-2 bg-danger bg-opacity-10 text-danger rounded-3"><i class="bi bi-exclamation-triangle-fill"></i></span>
                </div>
                <h3 class="fw-bold text-danger mb-0"><?= number_format($inspectionMetrics['overdue']) ?></h3>
                <span class="small text-danger mt-1 d-block fw-semibold">Action Required</span>
            </div>
        </div>

        <!-- Missed Inspections -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Missed</span>
                    <span class="p-2 bg-dark bg-opacity-10 text-dark rounded-3"><i class="bi bi-x-circle-fill"></i></span>
                </div>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($inspectionMetrics['missed']) ?></h3>
                <span class="small text-muted mt-1 d-block">Unfulfilled</span>
            </div>
        </div>

        <!-- Average Score -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Quality Score</span>
                    <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3"><i class="bi bi-patch-check-fill"></i></span>
                </div>
                <h3 class="fw-bold text-primary mb-0"><?= $inspectionMetrics['average_score'] ?>%</h3>
                <span class="small text-muted mt-1 d-block">Audit Standard</span>
            </div>
        </div>
    </div>

    <!-- Row 2: Attendance & Complaints / SLA Breaches -->
    <div class="row g-3 mb-4">
        <!-- Attendance: Verified Present -->
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">SUPERVISOR ATTENDANCE</span>
                        <div class="d-flex align-items-baseline gap-2 mt-1">
                            <h3 class="fw-bold text-success mb-0"><?= number_format($attendanceMetrics['verified_present']) ?></h3>
                            <span class="small text-muted">Verified Present</span>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <span class="badge bg-light text-dark border small">Late: <?= $attendanceMetrics['late'] ?></span>
                            <?php if ($attendanceMetrics['flagged_suspicious'] > 0): ?>
                                <span class="badge bg-danger text-white small"><i class="bi bi-flag-fill me-1"></i>Flagged: <?= $attendanceMetrics['flagged_suspicious'] ?></span>
                            <?php endif; ?>
                            <?php if ($attendanceMetrics['unverified_manual'] > 0): ?>
                                <span class="badge bg-warning text-dark small">Unverified: <?= $attendanceMetrics['unverified_manual'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 rounded-4 text-success fs-3">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Facility Complaints -->
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">ACTIVE COMPLAINTS</span>
                        <div class="d-flex align-items-baseline gap-2 mt-1">
                            <h3 class="fw-bold text-danger mb-0"><?= number_format($complaintMetrics['total_active']) ?></h3>
                            <span class="small text-muted">Grievances Lodged</span>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 small">
                                Critical / High: <?= $complaintMetrics['critical_high'] ?>
                            </span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">
                                Resolved Today: <?= $complaintMetrics['resolved_today'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 rounded-4 text-danger fs-3">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLA Breaches / Overdue -->
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">SLA OVERDUE BREACHES</span>
                        <div class="d-flex align-items-baseline gap-2 mt-1">
                            <h3 class="fw-bold text-warning mb-0"><?= number_format($complaintMetrics['resolution_breached']) ?></h3>
                            <span class="small text-muted">Breached Targets</span>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 small">
                                Response Overdue: <?= $complaintMetrics['response_breached'] ?>
                            </span>
                            <a href="<?= url('/complaints?sla_breach=any') ?>" class="small text-decoration-none fw-semibold">
                                View Overdue &rarr;
                            </a>
                        </div>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 rounded-4 text-warning-emphasis fs-3">
                        <i class="bi bi-stopwatch-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN INSPECTION MATRIX: Grouped by Building -> Floor -> Ward -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Multi-Floor Inspection Command Matrix</h5>
                <span class="small text-muted">Facility compliance status for date: <strong><?= date('d M Y', strtotime($filters['date'])) ?></strong></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border"><i class="bi bi-hospital me-1"></i><?= count($matrix) ?> Active Checkpoints</span>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (empty($groupedMatrix)): ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="p-4 bg-light rounded-circle d-inline-block mb-3 text-secondary">
                        <i class="bi bi-building-slash fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-navy-dark">No Inspection Locations Found</h5>
                    <p class="text-muted small mb-3">No facility wards match the selected filters or assigned permissions for this date.</p>
                    <a href="<?= url('/') ?>" class="btn btn-navy btn-sm rounded-3 px-4 shadow-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Matrix Filters
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($groupedMatrix as $bldgName => $floorsGroup): ?>
                    <!-- Building Section Banner -->
                    <div class="bg-light px-4 py-2 border-top border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-building text-primary fw-bold"></i>
                            <span class="fw-bold text-navy-dark text-uppercase tracking-wider small"><?= e($bldgName) ?></span>
                        </div>
                        <span class="badge bg-white text-muted border small">Building Block</span>
                    </div>

                    <?php foreach ($floorsGroup as $floorLabel => $wardRows): ?>
                        <!-- Floor Sub-header -->
                        <div class="px-4 py-2 bg-white border-bottom d-flex align-items-center gap-2">
                            <i class="bi bi-layers text-secondary small"></i>
                            <span class="small fw-semibold text-secondary"><?= e($floorLabel) ?></span>
                            <span class="badge bg-light text-muted border ms-auto small"><?= count($wardRows) ?> Wards</span>
                        </div>

                        <!-- Wards Table for this Floor -->
                        <div class="table-responsive mb-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-white text-muted small text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-4 py-2" style="width: 25%;">Ward / Checkpoint</th>
                                        <th style="width: 15%;">Ward Type</th>
                                        <th style="width: 20%;">Supervisor on Duty</th>
                                        <th style="width: 15%;">Inspection Status</th>
                                        <th style="width: 10%;">Complaints</th>
                                        <th class="pe-4 text-end" style="width: 15%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wardRows as $row): ?>
                                        <?php $badge = $row['badge']; ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-navy-dark"><?= e($row['ward_name']) ?></div>
                                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">
                                                    <?= e($row['ward_code']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border small text-capitalize">
                                                    <?= e($row['ward_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['supervisor_name'])): ?>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="bi bi-person-circle text-primary"></i>
                                                        <span class="small fw-semibold text-dark"><?= e($row['supervisor_name']) ?></span>
                                                    </div>
                                                    <?php if (!empty($row['shift_name'])): ?>
                                                        <span class="small text-muted d-block" style="font-size: 0.72rem;"><?= e($row['shift_name']) ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="small text-muted fst-italic">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badge['class'] ?> px-2 py-1">
                                                    <i class="bi <?= $badge['icon'] ?> me-1"></i><?= e($badge['label']) ?>
                                                </span>
                                                <?php if ($row['critical_issues'] > 0): ?>
                                                    <span class="badge bg-danger small ms-1" title="Critical Issues Reported">
                                                        <i class="bi bi-fire"></i> <?= $row['critical_issues'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['active_complaints'] > 0): ?>
                                                    <a href="<?= url('/complaints?ward_id=' . $row['ward_id']) ?>" class="badge bg-danger text-white text-decoration-none">
                                                        <i class="bi bi-exclamation-circle-fill me-1"></i><?= $row['active_complaints'] ?> Active
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <?php if ($row['status_state'] === 'COMPLETED' && $row['inspection_id']): ?>
                                                        <a href="<?= url('/inspections/' . $row['inspection_id']) ?>" class="btn btn-outline-success btn-sm rounded-3 py-1 px-2" title="View Inspection Report">
                                                            <i class="bi bi-file-earmark-check"></i> Report
                                                        </a>
                                                    <?php elseif (in_array($row['status_state'], ['PENDING', 'IN_PROGRESS', 'OVERDUE'], true) && $row['schedule_id']): ?>
                                                        <a href="<?= url('/inspections/execute/' . $row['schedule_id']) ?>" class="btn btn-primary btn-sm rounded-3 py-1 px-2 shadow-sm" title="Execute Inspection Round">
                                                            <i class="bi bi-play-fill"></i> Inspect
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= url('/complaints/create?ward_id=' . $row['ward_id']) ?>" class="btn btn-outline-secondary btn-sm rounded-3 py-1 px-2" title="Lodge Complaint">
                                                            <i class="bi bi-plus-circle"></i> Issue
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
