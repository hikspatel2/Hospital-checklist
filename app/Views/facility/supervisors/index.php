<?php
/**
 * Supervisor Allocations View
 * Manages supervisor assignment to buildings, floors, and wards with effective dates and hierarchy checks.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Supervisor Allocations</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Supervisor Facility Allocations</h1>
        <p class="text-muted small mb-0">Building, Floor, and Ward Jurisdiction Assignments with Effective Dates</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
            <i class="bi bi-person-plus me-1"></i> New Allocation
        </button>
    </div>
</div>

<!-- Search & Filtering Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/supervisors') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
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
                <select class="form-select" name="building_id">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($filters['building_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['building_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="active_on_date" value="<?= e($filters['active_on_date'] ?? '') ?>" placeholder="Active on date">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Revoked / Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="<?= url('/supervisors') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Allocations Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($assignments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-5 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No Supervisor Allocations Found</h5>
                <p class="text-muted small mb-3">No facility assignments match your filter query.</p>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                    Create New Allocation
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Supervisor Personnel</th>
                            <th>Facility Jurisdiction Level</th>
                            <th>Target Entity</th>
                            <th>Effective Period</th>
                            <th>Assigned By</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $sa): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary-subtle text-primary me-2 fw-bold">
                                            <?= strtoupper(substr($sa['supervisor_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($sa['supervisor_name']) ?></div>
                                            <div class="text-muted small" style="font-size: 0.72rem;">
                                                <?= e($sa['employee_code']) ?> &bull; <?= e($sa['supervisor_email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($sa['ward_id'])): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <i class="bi bi-hospital me-1"></i> Ward Level
                                        </span>
                                    <?php elseif (!empty($sa['floor_id'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-layers me-1"></i> Floor Level
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-dark-subtle text-dark border">
                                            <i class="bi bi-building me-1"></i> Campus Wing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($sa['ward_name'])): ?>
                                        <div class="fw-bold text-dark"><?= e($sa['ward_name']) ?> (<?= e($sa['ward_code']) ?>)</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            <?= e($sa['building_name']) ?> &bull; Level <?= (int)$sa['floor_number'] ?>
                                        </div>
                                    <?php elseif (!empty($sa['floor_name'])): ?>
                                        <div class="fw-bold text-dark"><?= e($sa['floor_name']) ?> (L<?= (int)$sa['floor_number'] ?>)</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= e($sa['building_name']) ?></div>
                                    <?php else: ?>
                                        <div class="fw-bold text-dark"><?= e($sa['building_name'] ?? 'Whole Campus') ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">All floors &amp; wards in wing</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        <?= e(date('d M Y', strtotime($sa['effective_from']))) ?>
                                        &rarr;
                                        <?= !empty($sa['effective_to']) ? e(date('d M Y', strtotime($sa['effective_to']))) : '<span class="text-success">Present</span>' ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small"><?= e($sa['assigned_by_name'] ?? 'System') ?></span>
                                </td>
                                <td>
                                    <?php if ($sa['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Revoked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($sa['status'] === 'active'): ?>
                                        <form action="<?= url('/admin/supervisor-assignments/' . $sa['id'] . '/revoke') ?>" method="POST" class="d-inline" onsubmit="return confirm('Revoke this supervisor allocation?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Revoke
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Archived</span>
                                    <?php endif; ?>
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
                        Showing <?= count($assignments) ?> of <?= (int)$pagination['total_records'] ?> allocations
                    </span>
                    <nav aria-label="Supervisor assignments pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                                <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/supervisors?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
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

<!-- Modal: New Supervisor Allocation -->
<div class="modal fade" id="newAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/supervisor-assignments') ?>" method="POST" id="assignmentModalForm">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2"></i>New Supervisor Facility Allocation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supervisor Personnel <span class="text-danger">*</span></label>
                        <select class="form-select" name="user_id" required>
                            <option value="">-- Choose Supervisor --</option>
                            <?php foreach ($supervisors as $sup): ?>
                                <option value="<?= $sup['id'] ?>">
                                    <?= e($sup['full_name']) ?> (<?= e($sup['employee_code']) ?>) &bull; <?= e($sup['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h6 class="fw-bold text-primary text-uppercase mt-4 mb-2" style="font-size: 0.8rem;">
                        Facility Jurisdiction Scope
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Building Wing</label>
                            <select class="form-select" name="building_id" id="assignBuildingSelect">
                                <option value="">-- All / Not Bound --</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['building_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Floor Level</label>
                            <select class="form-select" name="floor_id" id="assignFloorSelect">
                                <option value="">-- All Floors in Wing --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Specific Ward</label>
                            <select class="form-select" name="ward_id" id="assignWardSelect">
                                <option value="">-- All Wards on Floor --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-text mb-4">
                        Hierarchy rule: Selecting a Building scopes all its floors. Selecting a Floor scopes that entire floor. Selecting a Ward limits jurisdiction to that specific ward.
                    </div>

                    <h6 class="fw-bold text-primary text-uppercase mb-2" style="font-size: 0.8rem;">
                        Effective Date Range
                    </h6>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="effective_from" value="<?= date('Y-m-d') ?>" required id="effectiveFromInput">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective End Date</label>
                            <input type="date" class="form-control" name="effective_to" id="effectiveToInput">
                            <div class="form-text">Leave blank for indefinite active assignment.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Confirm Allocation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bSelect = document.getElementById('assignBuildingSelect');
    const fSelect = document.getElementById('assignFloorSelect');
    const wSelect = document.getElementById('assignWardSelect');
    const fromInput = document.getElementById('effectiveFromInput');
    const toInput = document.getElementById('effectiveToInput');
    const modalForm = document.getElementById('assignmentModalForm');

    bSelect.addEventListener('change', function() {
        const bId = this.value;
        fSelect.innerHTML = '<option value="">-- All Floors in Wing --</option>';
        wSelect.innerHTML = '<option value="">-- All Wards on Floor --</option>';

        if (bId) {
            fetch('<?= url('/api/buildings/') ?>' + bId + '/floors')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.floors.length) {
                        d.floors.forEach(fl => {
                            const opt = document.createElement('option');
                            opt.value = fl.id;
                            opt.textContent = 'Floor ' + fl.floor_number + ' - ' + fl.floor_name;
                            fSelect.appendChild(opt);
                        });
                    }
                });
        }
    });

    fSelect.addEventListener('change', function() {
        const fId = this.value;
        wSelect.innerHTML = '<option value="">-- All Wards on Floor --</option>';

        if (fId) {
            fetch('<?= url('/api/floors/') ?>' + fId + '/wards')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.wards.length) {
                        d.wards.forEach(w => {
                            const opt = document.createElement('option');
                            opt.value = w.id;
                            opt.textContent = w.ward_name + ' (' + w.ward_code + ')';
                            wSelect.appendChild(opt);
                        });
                    }
                });
        }
    });

    modalForm.addEventListener('submit', function(e) {
        if (!bSelect.value && !fSelect.value && !wSelect.value) {
            alert('Please select at least a Building, Floor, or Ward for the supervisor allocation.');
            e.preventDefault();
            return;
        }

        if (toInput.value && toInput.value < fromInput.value) {
            alert('Effective End Date cannot precede Effective Start Date.');
            e.preventDefault();
            return;
        }
    });
});
</script>
