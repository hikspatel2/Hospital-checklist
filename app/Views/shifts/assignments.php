<?php
/**
 * Supervisor Shift Assignments View
 * Lists supervisor shift rosters, validates effective dates, and enforces overlap prevention.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/shifts') ?>">Shifts</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shift Rosters</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Supervisor Shift Rostering</h1>
        <p class="text-muted small mb-0">Shift Allocations, Effective Date Windows &amp; Overlap Protection</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/shifts') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clock me-1"></i> Manage Shifts
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignShiftModal">
            <i class="bi bi-person-plus me-1"></i> Allocate Shift
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/shift-assignments') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select class="form-select" name="user_id">
                    <option value="">All Supervisors</option>
                    <?php foreach ($supervisors as $sup): ?>
                        <option value="<?= $sup['id'] ?>" <?= ($filters['user_id'] ?? '') == $sup['id'] ? 'selected' : '' ?>>
                            <?= e($sup['full_name']) ?> (<?= e($sup['employee_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="shift_id">
                    <option value="">All Shifts</option>
                    <?php foreach ($shifts as $sh): ?>
                        <option value="<?= $sh['id'] ?>" <?= ($filters['shift_id'] ?? '') == $sh['id'] ? 'selected' : '' ?>>
                            <?= e($sh['shift_name']) ?> (<?= e($sh['shift_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="<?= url('/shift-assignments') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Assignments Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($assignments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-5 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No Shift Rosters Found</h5>
                <p class="text-muted small mb-3">Allocate supervisors to active hospital work shifts.</p>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignShiftModal">
                    Allocate First Shift
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Supervisor</th>
                            <th>Shift &amp; Timings</th>
                            <th>Jurisdiction Scope</th>
                            <th>Effective Dates</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= e($a['supervisor_name']) ?></div>
                                    <div class="text-muted small" style="font-size: 0.72rem;">
                                        <?= e($a['employee_code']) ?> &bull; <?= e($a['supervisor_email']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-primary border fw-bold"><?= e($a['shift_code']) ?></span>
                                        <span class="fw-semibold text-dark"><?= e($a['shift_name']) ?></span>
                                        <?php if (!empty($a['is_overnight'])): ?>
                                            <span class="badge bg-dark text-warning p-1" title="Overnight Shift">
                                                <i class="bi bi-moon-stars-fill"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        <?= date('h:i A', strtotime($a['start_time'])) ?> &rarr; <?= date('h:i A', strtotime($a['end_time'])) ?>
                                        (Grace: <?= (int)$a['grace_period_minutes'] ?>m)
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($a['ward_name'])): ?>
                                        <span class="badge bg-info-subtle text-info border">
                                            Ward: <?= e($a['ward_name']) ?>
                                        </span>
                                    <?php elseif (!empty($a['floor_name'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border">
                                            Floor: <?= e($a['floor_name']) ?>
                                        </span>
                                    <?php elseif (!empty($a['building_name'])): ?>
                                        <span class="badge bg-dark-subtle text-dark border">
                                            Wing: <?= e($a['building_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Campus-wide</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        <?= date('d M Y', strtotime($a['effective_from'])) ?>
                                        &rarr;
                                        <?= !empty($a['effective_to']) ? date('d M Y', strtotime($a['effective_to'])) : '<span class="text-success">Present</span>' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($a['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="<?= url('/admin/shift-assignments/' . $a['id'] . '/toggle-status') ?>" method="POST" onsubmit="return confirm('Toggle roster status?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm <?= $a['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <?= $a['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
                <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        Showing <?= count($assignments) ?> of <?= (int)$pagination['total_records'] ?> shift allocations
                    </span>
                    <nav aria-label="Shift assignments pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                                <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/shift-assignments?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Allocate Supervisor to Shift -->
<div class="modal fade" id="assignShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/shift-assignments') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-check-fill me-2"></i>Allocate Supervisor to Work Shift
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-shield-check me-1"></i>
                        Overlapping Shift Defense: The system validates that supervisors cannot be assigned to overlapping shifts during the same date period.
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supervisor Personnel <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_id" required>
                                <option value="">-- Choose Supervisor --</option>
                                <?php foreach ($supervisors as $sup): ?>
                                    <option value="<?= $sup['id'] ?>">
                                        <?= e($sup['full_name']) ?> (<?= e($sup['employee_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Shift <span class="text-danger">*</span></label>
                            <select class="form-select" name="shift_id" required>
                                <option value="">-- Choose Shift --</option>
                                <?php foreach ($shifts as $sh): ?>
                                    <option value="<?= $sh['id'] ?>">
                                        <?= e($sh['shift_name']) ?> (<?= date('h:i A', strtotime($sh['start_time'])) ?> - <?= date('h:i A', strtotime($sh['end_time'])) ?>)
                                        <?= !empty($sh['is_overnight']) ? '[Overnight]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary text-uppercase mt-4 mb-2" style="font-size: 0.8rem;">
                        Effective Date Range
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="effective_from" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective End Date</label>
                            <input type="date" class="form-control" name="effective_to">
                            <div class="form-text">Leave blank for permanent / indefinite allocation.</div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary text-uppercase mt-4 mb-2" style="font-size: 0.8rem;">
                        Optional Facility Jurisdiction Scope
                    </h6>
                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Building Wing</label>
                            <select class="form-select" name="building_id" id="rosterBuildingSelect">
                                <option value="">-- All Wings --</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['building_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Floor Level</label>
                            <select class="form-select" name="floor_id" id="rosterFloorSelect">
                                <option value="">-- All Floors --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Specific Ward</label>
                            <select class="form-select" name="ward_id" id="rosterWardSelect">
                                <option value="">-- All Wards --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Allocate Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bSel = document.getElementById('rosterBuildingSelect');
    const fSel = document.getElementById('rosterFloorSelect');
    const wSel = document.getElementById('rosterWardSelect');

    bSel.addEventListener('change', function() {
        const bId = this.value;
        fSel.innerHTML = '<option value="">-- All Floors --</option>';
        wSel.innerHTML = '<option value="">-- All Wards --</option>';

        if (bId) {
            fetch('<?= url('/api/buildings/') ?>' + bId + '/floors')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.floors.length) {
                        d.floors.forEach(fl => {
                            const opt = document.createElement('option');
                            opt.value = fl.id;
                            opt.textContent = 'Floor ' + fl.floor_number + ' - ' + fl.floor_name;
                            fSel.appendChild(opt);
                        });
                    }
                });
        }
    });

    fSel.addEventListener('change', function() {
        const fId = this.value;
        wSel.innerHTML = '<option value="">-- All Wards --</option>';

        if (fId) {
            fetch('<?= url('/api/floors/') ?>' + fId + '/wards')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.wards.length) {
                        d.wards.forEach(w => {
                            const opt = document.createElement('option');
                            opt.value = w.id;
                            opt.textContent = w.ward_name + ' (' + w.ward_code + ')';
                            wSel.appendChild(opt);
                        });
                    }
                });
        }
    });
});
</script>
