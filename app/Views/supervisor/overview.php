<?php
/** @var array $supervisor */
/** @var array $assignments */
/** @var ?array $activeShift */
/** @var array $recentInspections */
/** @var array $activeComplaints */
?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom">
    <div>
        <h2 class="h4 fw-bold text-navy mb-1">Supervisor Facility Roster & Assigned Work</h2>
        <p class="text-secondary small mb-0">
            Work overview for <strong class="text-navy"><?= e($supervisor['name']) ?></strong> (<?= e($supervisor['employee_id']) ?>) &bull; <?= e($supervisor['department'] ?? 'Facility Services') ?>
        </p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 d-inline-flex align-items-center">
            <i class="bi bi-shield-check me-2"></i>Server-side Ownership Enforced
        </span>
    </div>
</div>

<!-- Shift & Operational Window Banner -->
<div class="card hospital-card shadow-sm border-0 mb-4">
    <div class="card-body p-4 bg-navy-dark text-white rounded-3">
        <div class="row align-items-center g-3">
            <div class="col-md-7 d-flex align-items-center">
                <div class="facility-icon-box bg-primary me-3" style="width: 48px; height: 48px; border-radius: 12px;">
                    <i class="bi bi-clock-history fs-3 text-white"></i>
                </div>
                <div>
                    <div class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Current Shift Schedule</div>
                    <h4 class="h5 fw-bold mb-0">
                        <?= $activeShift ? e($activeShift['shift_name']) . " (" . e($activeShift['shift_code']) . ")" : 'No Shift Assigned For Today' ?>
                    </h4>
                    <?php if ($activeShift): ?>
                        <div class="small text-info mt-1">
                            Operational Hours: <strong><?= substr($activeShift['start_time'], 0, 5) ?> to <?= substr($activeShift['end_time'], 0, 5) ?></strong> (IST) &bull; <?= (int)$activeShift['grace_period_minutes'] ?>m grace
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-2 rounded-pill small">
                    <span class="pulse-dot"></span>
                    <span>Hospital Timezone: <strong>Asia/Kolkata</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assigned Facility Checkpoints -->
<div class="card hospital-card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="bi bi-building-check text-primary me-2 fs-5"></i>
            <h5 class="card-title fw-bold text-navy mb-0">Assigned Inspection Wards & Floors (<?= count($assignments) ?>)</h5>
        </div>
        <small class="text-muted">Strictly scoped to your authorized areas</small>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($assignments)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-secondary">
                        <tr>
                            <th class="ps-4">Building Block</th>
                            <th>Floor Level</th>
                            <th>Ward / Area</th>
                            <th>Checkpoint QR Code</th>
                            <th class="pe-4 text-end">Roster Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-navy">
                                    <i class="bi bi-hospital me-1 text-primary"></i>
                                    <?= e($a['building_name'] ?? 'Main Block') ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($a['floor_name'] ?? 'All Floors') ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= e($a['ward_name'] ?? 'Ward Block') ?></span>
                                    <?php if (!empty($a['ward_type'])): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 small"><?= e($a['ward_type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-qr-code text-muted"></i>
                                        <code class="text-primary fw-medium"><?= e($a['qr_code_identifier'] ?? 'QR-PENDING') ?></code>
                                    </div>
                                </td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        Authorized
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 text-secondary d-block mb-2"></i>
                <h6 class="fw-bold text-navy">No Facility Allocations Found</h6>
                <p class="small mb-0">You do not have any active building or ward assignments assigned by administration.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Wards Recent Inspections -->
    <div class="col-lg-6">
        <div class="card hospital-card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clipboard2-check text-primary me-2 fs-5"></i>
                    <h5 class="card-title fw-bold text-navy mb-0">Assigned Wards: Recent Inspections</h5>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentInspections)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentInspections as $insp): ?>
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold text-navy"><?= e($insp['inspection_number']) ?></div>
                                    <div class="text-muted small">
                                        <?= e($insp['ward_name']) ?> &bull; <?= e($insp['building_code']) ?> &bull; <?= format_datetime($insp['created_at']) ?>
                                    </div>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary text-uppercase px-2 py-1">
                                    <?= e($insp['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-calendar-x fs-3 d-block mb-2 text-secondary"></i>
                        No inspection records logged in your assigned wards yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Wards Active Complaints -->
    <div class="col-lg-6">
        <div class="card hospital-card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle text-danger me-2 fs-5"></i>
                    <h5 class="card-title fw-bold text-navy mb-0">Assigned Wards: Active Complaints & SLA</h5>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($activeComplaints)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($activeComplaints as $comp): ?>
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold text-navy"><?= e($comp['ticket_number']) ?>: <?= e($comp['title']) ?></div>
                                    <div class="text-muted small">
                                        <?= e($comp['ward_name']) ?> &bull; Due: <?= format_datetime($comp['due_date']) ?>
                                    </div>
                                </div>
                                <span class="badge <?= $comp['priority'] === 'critical' ? 'bg-danger' : 'bg-warning text-dark' ?> text-uppercase px-2 py-1">
                                    <?= e($comp['priority']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-shield-check fs-3 d-block mb-2 text-success"></i>
                        No unresolved complaints recorded in your assigned wards.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
