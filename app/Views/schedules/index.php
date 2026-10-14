<?php
/** @var array $schedules */
/** @var array $pagination */
/** @var array $filters */
/** @var array $supervisors */
/** @var array $shifts */
/** @var array $wards */
/** @var array $statuses */
/** @var array $statusBadges */
?>
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Phase 5</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Inspection Schedules Calendar</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">Generated daily inspection schedules, supervisor assignments, and operational states.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/schedules/rules') ?>" class="btn btn-outline-primary btn-sm rounded-3">
                <i class="bi bi-clock-history me-1"></i> Recurrence Rules
            </a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#triggerGenModal">
                    <i class="bi bi-arrow-repeat me-1"></i> Run Generator
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('/schedules') ?>" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control form-control-sm bg-light border-0 font-monospace" value="<?= e($filters['date'] ?: date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Operational States</option>
                        <?php foreach ($statuses as $stKey => $stLabel): ?>
                            <option value="<?= e($stKey) ?>" <?= $filters['status'] === $stKey ? 'selected' : '' ?>><?= e($stLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="assigned_user_id" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Assigned Supervisors</option>
                        <?php foreach ($supervisors as $sup): ?>
                            <option value="<?= $sup['id'] ?>" <?= (string)$filters['assigned_user_id'] === (string)$sup['id'] ? 'selected' : '' ?>><?= e($sup['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy btn-sm w-100 rounded-3">Filter</button>
                    <a href="<?= url('/schedules') ?>" class="btn btn-light btn-sm rounded-3">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">Scheduled Date & Window</th>
                        <th>Checklist Template</th>
                        <th>Target Ward / Checkpoint</th>
                        <th>Shift</th>
                        <th>Assigned Supervisor</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                No scheduled inspections found for this date.
                                <?php if (\App\Core\Auth::isAdmin()): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-3" data-bs-toggle="modal" data-bs-target="#triggerGenModal">
                                            Run Generator for Today
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold font-monospace text-navy-dark"><?= e($s['scheduled_date']) ?></div>
                                    <?php if (!empty($s['scheduled_time_window_start']) && !empty($s['scheduled_time_window_end'])): ?>
                                        <div class="small text-muted font-monospace"><i class="bi bi-clock me-1"></i><?= substr($s['scheduled_time_window_start'], 0, 5) ?> - <?= substr($s['scheduled_time_window_end'], 0, 5) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($s['template_name']) ?></div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace small px-2 py-0">
                                        <?= e($s['template_code']) ?> (v<?= e((string)$s['version_number']) ?>)
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><i class="bi bi-hospital me-1 text-primary"></i><?= e($s['ward_name']) ?></span>
                                    <?php if (!empty($s['location_name'])): ?>
                                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($s['location_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['shift_name'])): ?>
                                        <span class="badge bg-light text-dark border small">
                                            <?= e($s['shift_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small">All Shifts</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['assigned_supervisor_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-person me-1 text-primary"></i><?= e($s['assigned_supervisor_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 small">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $badgeClass = $statusBadges[$s['status']] ?? 'bg-secondary text-white'; ?>
                                    <span class="badge <?= $badgeClass ?> px-2 py-1 small">
                                        <?= e($statuses[$s['status']] ?? ucfirst($s['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if (!empty($s['inspection_id'])): ?>
                                        <a href="<?= url("/inspections/{$s['inspection_id']}") ?>" class="btn btn-sm btn-outline-success rounded-3 px-2 py-1">
                                            <i class="bi bi-file-earmark-check me-1"></i> View Report
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url("/inspections/execute/{$s['id']}") ?>" class="btn btn-sm btn-primary rounded-3 px-2 py-1 shadow-sm">
                                            <i class="bi bi-play-circle me-1"></i> Inspect
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Run Schedule Generator Modal -->
<div class="modal fade" id="triggerGenModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-navy text-white px-4 py-3">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-arrow-repeat me-2"></i>Run Idempotent Schedule Generator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('/admin/schedules/generate') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Evaluates all active recurrence rules, matches weekdays, applies holiday exclusions, and generates concrete inspection tasks.
                        <strong>Guaranteed 100% idempotent:</strong> running this multiple times will never duplicate schedules.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Target Generation Date *</label>
                        <input type="date" name="target_date" class="form-control form-control-sm font-monospace" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-0">
                    <button type="button" class="btn btn-sm btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-3 px-4">Generate Schedules</button>
                </div>
            </form>
        </div>
    </div>
</div>
