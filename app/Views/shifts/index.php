<?php
/**
 * Shifts Index View
 * Lists all hospital work shifts with timing boundaries, overnight indicators, grace periods, and management actions.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Work Shifts</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Hospital Work Shifts &amp; Boundaries</h1>
        <p class="text-muted small mb-0">Morning, Evening, Night &amp; Overnight Shift Rostering</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/shift-assignments') ?>" class="btn btn-outline-primary">
            <i class="bi bi-people me-1"></i> Shift Rosters
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createShiftModal">
            <i class="bi bi-plus-lg me-1"></i> Add Shift
        </button>
    </div>
</div>

<!-- Status Filter Pills -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Filter:</span>
            <a href="<?= url('/shifts') ?>" class="btn btn-sm <?= empty($currentStatus) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                All Shifts (<?= count($shifts) ?>)
            </a>
            <a href="<?= url('/shifts?status=active') ?>" class="btn btn-sm <?= $currentStatus === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>">
                Active Only
            </a>
            <a href="<?= url('/shifts?status=inactive') ?>" class="btn btn-sm <?= $currentStatus === 'inactive' ? 'btn-warning' : 'btn-outline-secondary' ?>">
                Inactive
            </a>
        </div>
        <div class="text-muted small">
            <i class="bi bi-clock-history text-primary me-1"></i> Overnight shifts cross midnight boundaries
        </div>
    </div>
</div>

<!-- Shifts Grid -->
<div class="row g-4">
    <?php if (empty($shifts)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-clock display-4 text-muted mb-2 d-block"></i>
                    <h5 class="fw-bold">No Shifts Found</h5>
                    <p class="text-muted small mb-3">Create morning, evening, or night duty shifts for hospital staff.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createShiftModal">
                        Add First Shift
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($shifts as $s): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 facility-card">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-primary border fw-bold fs-6 me-2"><?= e($s['shift_code']) ?></span>
                            <?php if (!empty($s['is_overnight'])): ?>
                                <span class="badge bg-dark text-white border" title="Crosses midnight boundary">
                                    <i class="bi bi-moon-stars-fill text-warning me-1"></i> Overnight
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info">
                                    <i class="bi bi-sun-fill text-warning me-1"></i> Daytime
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($s['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-dark mb-1"><?= e($s['shift_name']) ?></h5>
                        <p class="text-muted small mb-3">
                            <?= !empty($s['description']) ? e($s['description']) : 'Regular hospital inspection and clinical shift.' ?>
                        </p>

                        <div class="bg-light rounded p-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">TIMING WINDOW:</span>
                                <span class="fw-bold text-dark fs-6">
                                    <?= date('h:i A', strtotime($s['start_time'])) ?> &rarr; <?= date('h:i A', strtotime($s['end_time'])) ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Grace Period:</span>
                                <span class="badge bg-warning-subtle text-dark border">
                                    <?= (int)$s['grace_period_minutes'] ?> minutes
                                </span>
                            </div>
                        </div>

                        <div class="row g-2 text-center small">
                            <div class="col-6">
                                <div class="bg-light p-2 rounded">
                                    <div class="fw-bold text-primary fs-5"><?= (int)$s['active_supervisors_count'] ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">SUPERVISORS</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light p-2 rounded">
                                    <div class="fw-bold text-success fs-5"><?= (int)$s['total_attendance_count'] ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">ATTENDANCE</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center">
                        <form action="<?= url('/admin/shifts/' . $s['id'] . '/toggle-status') ?>" method="POST" onsubmit="return confirm('Toggle shift status? Historical attendance records will be preserved.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm <?= $s['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <a href="<?= url('/shift-assignments?shift_id=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">
                            View Roster <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Create Shift -->
<div class="modal fade" id="createShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/shifts') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-clock-history me-2"></i>Add Hospital Shift
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Shift Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="shift_code" required placeholder="e.g. SHIFT-M, SHIFT-N" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="shift_name" required placeholder="e.g. Morning Duty Shift, Overnight Emergency" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description / Specialty Scope</label>
                        <input type="text" class="form-control" name="description" placeholder="Optional shift notes or floor focus">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="start_time" required value="08:00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="end_time" required value="16:00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Grace Period (Minutes)</label>
                        <input type="number" class="form-control" name="grace_period_minutes" value="15" min="0" max="120">
                        <div class="form-text">Allowed check-in tolerance before attendance is marked as "Late".</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_overnight" value="1" id="isOvernightCheck">
                        <label class="form-check-label fw-semibold" for="isOvernightCheck">
                            Overnight Shift (Crosses midnight boundary, e.g. 22:00 to 06:00)
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
