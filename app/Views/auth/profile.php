<?php
/** @var array $user */
/** @var array $assignments */
/** @var ?array $activeShift */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom">
    <div>
        <h2 class="h4 fw-bold text-navy mb-1">Personnel Facility Profile</h2>
        <p class="text-secondary small mb-0">Official personnel details, facility authorizations, and active shift allocation.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="<?= url('/change-password') ?>" class="btn btn-outline-navy btn-sm px-3 shadow-sm">
            <i class="bi bi-shield-lock me-1"></i>Change Password
        </a>
        <?php if ($user['role'] === 'supervisor'): ?>
            <a href="<?= url('/supervisor/overview') ?>" class="btn btn-navy btn-sm px-3 shadow-sm">
                <i class="bi bi-card-checklist me-1"></i>Assigned Work
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- User Information Card -->
    <div class="col-lg-4">
        <div class="card hospital-card shadow-sm border-0 text-center p-4">
            <div class="user-avatar-circle mx-auto mb-3 shadow" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="bi bi-person-badge text-white"></i>
            </div>
            <h4 class="h5 fw-bold text-navy mb-1"><?= e($user['name']) ?></h4>
            <div class="text-muted small mb-2"><?= e($user['designation'] ?? 'Hospital Staff') ?></div>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="badge bg-primary px-3 py-1 rounded-pill text-uppercase" style="font-size: 0.72rem;">
                    <?= e($user['role']) ?>
                </span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill" style="font-size: 0.72rem;">
                    Active Account
                </span>
            </div>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Employee ID:</span>
                    <strong class="text-navy"><?= e($user['employee_id']) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Hospital Email:</span>
                    <span class="text-dark"><?= e($user['email']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Department:</span>
                    <span class="text-dark"><?= e($user['department'] ?? 'General') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Contact Phone:</span>
                    <span class="text-dark"><?= e($user['phone'] ?? '—') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Last Sign-In:</span>
                    <span class="text-dark"><?= format_datetime($user['last_login_at']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Last IP Address:</span>
                    <span class="text-muted font-monospace"><?= e($user['last_login_ip'] ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Shift & Assignments (If Supervisor) -->
    <div class="col-lg-8">
        <?php if ($user['role'] === 'supervisor'): ?>
            <!-- Current Shift Block -->
            <div class="card hospital-card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-history text-primary me-2 fs-5"></i>
                        <h5 class="card-title fw-bold text-navy mb-0">Active Shift Allocation</h5>
                    </div>
                    <?php if ($activeShift): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1">
                            Shift Active
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning bg-opacity-25 text-dark px-3 py-1">
                            No Active Shift
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <?php if ($activeShift): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Shift Title</div>
                                    <div class="fw-bold text-navy h6 mb-0"><?= e($activeShift['shift_name']) ?></div>
                                    <small class="text-secondary">(<?= e($activeShift['shift_code']) ?>)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Operational Window</div>
                                    <div class="fw-bold text-dark h6 mb-0">
                                        <?= substr($activeShift['start_time'], 0, 5) ?> – <?= substr($activeShift['end_time'], 0, 5) ?>
                                    </div>
                                    <small class="text-muted">Asia/Kolkata (IST)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Grace Allowance</div>
                                    <div class="fw-bold text-success h6 mb-0"><?= (int)$activeShift['grace_period_minutes'] ?> Minutes</div>
                                    <small class="text-muted">Attendance tolerance</small>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0 text-muted">
                            <i class="bi bi-info-circle me-1 text-primary"></i>No recurring shift is presently assigned to your employee account. Contact the facility administrator.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assigned Facility Checkpoints -->
            <div class="card hospital-card shadow-sm border-0">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-building-check text-primary me-2 fs-5"></i>
                        <h5 class="card-title fw-bold text-navy mb-0">Assigned Facility Checkpoints (<?= count($assignments) ?>)</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($assignments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-4">Building</th>
                                        <th>Floor</th>
                                        <th>Ward / Facility</th>
                                        <th>QR Identifier</th>
                                        <th class="pe-4 text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-navy">
                                                <i class="bi bi-building me-1 text-muted"></i>
                                                <?= e($a['building_name'] ?? 'Hospital Complex') ?>
                                            </td>
                                            <td><?= e($a['floor_name'] ?? 'All Floors') ?></td>
                                            <td>
                                                <span class="fw-medium text-dark"><?= e($a['ward_name'] ?? 'Assigned Block') ?></span>
                                                <?php if (!empty($a['ward_type'])): ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 small"><?= e($a['ward_type']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded text-primary small">
                                                    <?= e($a['qr_code_identifier'] ?? 'QR-PENDING') ?>
                                                </code>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                    Assigned
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-geo-alt fs-2 text-secondary d-block mb-2"></i>
                            <p class="mb-0 small">No specific wards or floors have been assigned to your roster yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Administrator Authority Overview -->
            <div class="card hospital-card shadow-sm border-0">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="card-title fw-bold text-navy mb-0">Administrative Privileges & Permissions</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 text-dark small mb-4">
                        <i class="bi bi-shield-check me-2 text-info fs-5 align-middle"></i>
                        As a designated <strong>Administrator</strong> for Sheth L.G. General Hospital, you have full oversight across all buildings, floors, wards, staff rosters, audit logs, and backup recovery operations.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light border h-100">
                                <div class="fw-semibold text-navy small mb-1"><i class="bi bi-people me-1 text-primary"></i>Personnel Management</div>
                                <p class="text-muted small mb-2">Provision new hospital employees, deactivate accounts, and reset credentials.</p>
                                <a href="<?= url('/users') ?>" class="btn btn-outline-primary btn-sm">Manage Personnel</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light border h-100">
                                <div class="fw-semibold text-navy small mb-1"><i class="bi bi-shield-check me-1 text-primary"></i>Audit Trail Verification</div>
                                <p class="text-muted small mb-2">Monitor all security events, authentication attempts, and data updates.</p>
                                <a href="<?= url('/audit-logs') ?>" class="btn btn-outline-navy btn-sm">Inspect Audit Logs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
