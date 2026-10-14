<?php
/** @var array $users */
/** @var array $pagination */
/** @var array $filters */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom">
    <div>
        <h2 class="h4 fw-bold text-navy mb-1">Personnel & Access Management</h2>
        <p class="text-secondary small mb-0">Administer hospital employees, supervisor authorizations, account states, and security credentials.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="<?= url('/users/create') ?>" class="btn btn-navy btn-sm px-3 shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i>Provision New Personnel
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card hospital-card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/users') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control bg-light border-start-0" 
                           placeholder="Search by name, employee ID, email, or designation..." 
                           value="<?= e($filters['search']) ?>">
                </div>
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="role" class="form-select form-select-sm bg-light">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Administrators</option>
                    <option value="supervisor" <?= $filters['role'] === 'supervisor' ? 'selected' : '' ?>>Supervisors</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <select name="status" class="form-select form-select-sm bg-light">
                    <option value="">All Statuses</option>
                    <option value="1" <?= $filters['status'] === '1' ? 'selected' : '' ?>>Active Only</option>
                    <option value="0" <?= $filters['status'] === '0' ? 'selected' : '' ?>>Deactivated Only</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-navy btn-sm w-100">Filter</button>
                <?php if (!empty($filters['search']) || !empty($filters['role']) || $filters['status'] !== ''): ?>
                    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Personnel Table Card -->
<div class="card hospital-card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-secondary">
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Role & Dept</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Last Sign-In</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-circle me-3" style="width: 38px; height: 38px; font-size: 1rem;">
                                            <i class="bi bi-person text-white"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-navy"><?= e($u['name']) ?></div>
                                            <small class="text-muted font-monospace"><?= e($u['employee_id']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-primary' : 'bg-info bg-opacity-75 text-dark' ?> text-uppercase me-1" style="font-size: 0.68rem;">
                                            <?= e($u['role']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?= e($u['department'] ?? 'General') ?> &bull; <?= e($u['designation'] ?? 'Staff') ?></small>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= e($u['email']) ?></div>
                                    <small class="text-muted"><?= e($u['phone'] ?? '—') ?></small>
                                </td>
                                <td>
                                    <?php if ((int)$u['is_active'] === 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                            <i class="bi bi-slash-circle me-1"></i>Deactivated
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><?= format_datetime($u['last_login_at']) ?></div>
                                    <small class="text-muted font-monospace"><?= e($u['last_login_ip'] ?? '—') ?></small>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2">
                                            <li>
                                                <a class="dropdown-item small" href="<?= url("/users/{$u['id']}/edit") ?>">
                                                    <i class="bi bi-pencil me-2 text-primary"></i>Edit Profile
                                                </a>
                                            </li>
                                            <?php if ($u['role'] === 'supervisor'): ?>
                                                <li>
                                                    <a class="dropdown-item small" href="<?= url("/supervisor/{$u['id']}/overview") ?>">
                                                        <i class="bi bi-eye me-2 text-info"></i>View Assigned Work
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <button class="dropdown-item small" type="button" data-bs-toggle="modal" data-bs-target="#resetPwdModal<?= $u['id'] ?>">
                                                    <i class="bi bi-key me-2 text-warning"></i>Reset Password
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php if ((int)$u['id'] !== \App\Core\Auth::id()): ?>
                                                <li>
                                                    <form action="<?= url("/users/{$u['id']}/toggle-status") ?>" method="POST" onsubmit="return confirm('Confirm changing account status for <?= e($u['name']) ?>?');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="dropdown-item small <?= (int)$u['is_active'] === 1 ? 'text-danger' : 'text-success' ?>">
                                                            <i class="bi <?= (int)$u['is_active'] === 1 ? 'bi-person-x' : 'bi-person-check' ?> me-2"></i>
                                                            <?= (int)$u['is_active'] === 1 ? 'Deactivate Account' : 'Activate Account' ?>
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <!-- Password Reset Modal for User -->
                                    <div class="modal fade" id="resetPwdModal<?= $u['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $u['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title h6 fw-bold mb-0" id="modalLabel<?= $u['id'] ?>">
                                                        Reset Password: <?= e($u['name']) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="<?= url("/users/{$u['id']}/reset-password") ?>" method="POST">
                                                    <?= csrf_field() ?>
                                                    <div class="modal-body p-4">
                                                        <p class="text-secondary small mb-3">
                                                            Enter a new temporary password for <strong><?= e($u['name']) ?></strong> (<?= e($u['employee_id']) ?>). Any active account lockouts will be automatically cleared.
                                                        </p>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small text-navy">New Password</label>
                                                            <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters" required minlength="8">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-navy btn-sm">Confirm Password Reset</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                No personnel records matching your filter criteria were found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Footer -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer bg-white d-flex align-items-center justify-content-between p-3 border-top">
            <span class="text-muted small">
                Showing <?= count($users) ?> of <?= $pagination['total_records'] ?> personnel records
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('/users?page=' . ($pagination['current_page'] - 1) . '&search=' . urlencode($filters['search']) . '&role=' . urlencode($filters['role']) . '&status=' . urlencode($filters['status'])) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/users?page=' . $i . '&search=' . urlencode($filters['search']) . '&role=' . urlencode($filters['role']) . '&status=' . urlencode($filters['status'])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('/users?page=' . ($pagination['current_page'] + 1) . '&search=' . urlencode($filters['search']) . '&role=' . urlencode($filters['role']) . '&status=' . urlencode($filters['status'])) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>
