<?php
/**
 * Admin Attendance Verification & Audit Console View
 * Provides auditable management, GPS coordinate telemetry inspection, and mandatory override tracking.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Attendance Audit</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Attendance Verification &amp; GPS Audit</h1>
        <p class="text-muted small mb-0">Supervisor QR + GPS Geofence Records &amp; Administrative Corrections</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/attendance') ?>" class="btn btn-outline-primary">
            <i class="bi bi-geo-alt me-1"></i> Mobile Check-In
        </a>
        <a href="<?= url('/shifts') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clock me-1"></i> Shift Timing Rules
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/admin/attendance') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select class="form-select" name="user_id">
                    <option value="">All Personnel</option>
                    <?php foreach ($supervisors as $sup): ?>
                        <option value="<?= $sup['id'] ?>" <?= ($filters['user_id'] ?? '') == $sup['id'] ? 'selected' : '' ?>>
                            <?= e($sup['full_name']) ?> (<?= e($sup['employee_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="shift_id">
                    <option value="">All Shifts</option>
                    <?php foreach ($shifts as $sh): ?>
                        <option value="<?= $sh['id'] ?>" <?= ($filters['shift_id'] ?? '') == $sh['id'] ? 'selected' : '' ?>>
                            <?= e($sh['shift_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date" value="<?= e($filters['date'] ?? '') ?>" placeholder="Attendance date">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="verification_status">
                    <option value="">All Verifications</option>
                    <option value="verified" <?= ($filters['verification_status'] ?? '') === 'verified' ? 'selected' : '' ?>>Verified On-Site</option>
                    <option value="suspicious_gps" <?= ($filters['verification_status'] ?? '') === 'suspicious_gps' ? 'selected' : '' ?>>Suspicious / Out-of-Bounds</option>
                    <option value="admin_override" <?= ($filters['verification_status'] ?? '') === 'admin_override' ? 'selected' : '' ?>>Admin Override</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="<?= url('/admin/attendance') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Audit Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($attendanceList)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-5 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No Attendance Records Found</h5>
                <p class="text-muted small mb-0">No records match your filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Personnel</th>
                            <th>Shift Window</th>
                            <th>Date &amp; Times</th>
                            <th>Ward Checkpoint</th>
                            <th>GPS Telemetry</th>
                            <th>Verification</th>
                            <th class="text-end pe-4">Audit Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceList as $att): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= e($att['supervisor_name']) ?></div>
                                    <div class="text-muted small" style="font-size: 0.72rem;">
                                        <?= e($att['employee_code']) ?> &bull; <?= e($att['supervisor_email']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border fw-bold"><?= e($att['shift_code']) ?></span>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        <?= date('h:i A', strtotime($att['start_time'])) ?> - <?= date('h:i A', strtotime($att['end_time'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($att['attendance_date'])) ?></div>
                                    <div class="small">
                                        <span class="text-success fw-bold">In:</span> <?= date('h:i A', strtotime($att['check_in_time'])) ?>
                                        <?php if (!empty($att['check_out_time'])): ?>
                                            &bull; <span class="text-danger fw-bold">Out:</span> <?= date('h:i A', strtotime($att['check_out_time'])) ?>
                                        <?php else: ?>
                                            &bull; <span class="badge bg-warning-subtle text-dark">Active Duty</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($att['ward_name'])): ?>
                                        <span class="fw-semibold text-dark d-block"><?= e($att['ward_name']) ?></span>
                                        <span class="text-muted small" style="font-size: 0.72rem;"><?= e($att['ward_code']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Campus Gate</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($att['distance_to_hospital_meters'])): ?>
                                        <div class="small">
                                            Dist: <strong><?= (int)$att['distance_to_hospital_meters'] ?>m</strong>
                                            <?php if ((int)$att['is_within_geofence'] === 1): ?>
                                                <span class="badge bg-success-subtle text-success p-1">Inside</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger p-1">Out of Bounds</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            Acc: <?= (int)$att['gps_accuracy_meters'] ?>m &bull; <?= number_format((float)$att['gps_latitude'], 4) ?>, <?= number_format((float)$att['gps_longitude'], 4) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">No GPS Stream</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($att['verification_status'] === 'verified'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> Verified
                                        </span>
                                    <?php elseif ($att['verification_status'] === 'admin_override'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" title="<?= e($att['admin_override_reason'] ?? '') ?>">
                                            <i class="bi bi-shield-check me-1"></i> Admin Override
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border" title="<?= e($att['flag_reason'] ?? 'Flagged for review') ?>">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Flagged GPS
                                        </span>
                                    <?php endif; ?>
                                    <div class="text-muted small text-capitalize mt-1" style="font-size: 0.72rem;">
                                        Duty: <?= e($att['status']) ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary btnOpenOverrideModal"
                                            data-id="<?= $att['id'] ?>"
                                            data-name="<?= e($att['supervisor_name']) ?>"
                                            data-date="<?= e($att['attendance_date']) ?>"
                                            data-status="<?= e($att['status']) ?>"
                                            data-vstatus="<?= e($att['verification_status']) ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Override
                                    </button>
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
                        Showing <?= count($attendanceList) ?> of <?= (int)$pagination['total_records'] ?> records
                    </span>
                    <nav aria-label="Attendance pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                                <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/admin/attendance?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
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

<!-- Modal: Auditable Admin Override -->
<div class="modal fade" id="overrideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="overrideForm" action="" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-lock me-2"></i>Auditable Attendance Override
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Administrative Correction Guarantee: This override is permanently recorded in the immutable audit log with your user ID and mandatory explanation reason.
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block">Personnel &amp; Date</label>
                        <span class="fw-bold text-dark fs-6" id="modalSupervisorName">Supervisor</span>
                        <span class="text-muted d-block small" id="modalAttendanceDate">Date</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Duty Status</label>
                            <select class="form-select" name="status" id="modalStatusSelect">
                                <option value="present">Present (On-Time)</option>
                                <option value="late">Late Duty</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Verification Status</label>
                            <select class="form-select" name="verification_status" id="modalVerifSelect">
                                <option value="admin_override">Admin Override (Approved)</option>
                                <option value="verified">Verified</option>
                                <option value="suspicious_gps">Flag Suspicious</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mandatory Explanation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required minlength="10" 
                                  placeholder="State exact administrative justification (e.g. GPS receiver hardware malfunction verified on-site by admin; supervisor present in ward)."></textarea>
                        <div class="form-text">Minimum 10 characters required. Preserved in immutable audit trail.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Confirm &amp; Audit Override
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const overrideModal = new bootstrap.Modal(document.getElementById('overrideModal'));
    const overrideForm = document.getElementById('overrideForm');
    const modalSupervisorName = document.getElementById('modalSupervisorName');
    const modalAttendanceDate = document.getElementById('modalAttendanceDate');
    const modalStatusSelect = document.getElementById('modalStatusSelect');
    const modalVerifSelect = document.getElementById('modalVerifSelect');

    document.querySelectorAll('.btnOpenOverrideModal').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const date = this.getAttribute('data-date');
            const status = this.getAttribute('data-status');
            const vstatus = this.getAttribute('data-vstatus');

            modalSupervisorName.textContent = name;
            modalAttendanceDate.textContent = 'Date: ' + date;
            modalStatusSelect.value = status;
            modalVerifSelect.value = 'admin_override';

            overrideForm.action = '<?= url('/admin/attendance/') ?>' + id + '/override';
            overrideModal.show();
        });
    });
});
</script>
