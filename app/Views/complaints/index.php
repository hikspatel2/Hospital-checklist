<?php
/** @var array $complaints */
/** @var array $pagination */
/** @var array $filters */
/** @var array $stats */
/** @var array $buildings */
/** @var array $supervisors */
/** @var array $categories */
/** @var array $priorities */
/** @var array $statuses */
?>
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Phase 6</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Complaint Management & SLA Workflow</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">Lodge hospital grievances, track automated response/resolution SLAs, and monitor corrective maintenance.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <a href="<?= url('/sla-policies') ?>" class="btn btn-outline-primary btn-sm rounded-3">
                    <i class="bi bi-stopwatch me-1"></i> SLA Policies
                </a>
            <?php endif; ?>
            <a href="<?= url('/complaints/create') ?>" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Lodge Complaint
            </a>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">ACTIVE TICKETS</span>
                        <h3 class="fw-bold text-navy-dark mb-0 mt-1"><?= number_format($stats['total_active'] ?? 0) ?></h3>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 rounded-4 text-primary fs-4">
                        <i class="bi bi-inbox-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">CRITICAL / HIGH</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['critical_high'] ?? 0) ?></h3>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 rounded-4 text-danger fs-4">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">SLA OVERDUE</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?= number_format($stats['sla_breached'] ?? 0) ?></h3>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 rounded-4 text-warning fs-4">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">RESOLVED TODAY</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($stats['resolved_today'] ?? 0) ?></h3>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 rounded-4 text-success fs-4">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('/complaints') ?>" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search ticket #, title, desc..." value="<?= e($filters['search']) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $statKey => $statInfo): ?>
                            <option value="<?= e($statKey) ?>" <?= $filters['status'] === $statKey ? 'selected' : '' ?>><?= e($statInfo['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Priorities</option>
                        <?php foreach ($priorities as $prioKey => $prioInfo): ?>
                            <option value="<?= e($prioKey) ?>" <?= $filters['priority'] === $prioKey ? 'selected' : '' ?>><?= e($prioInfo['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $catKey => $catLabel): ?>
                            <option value="<?= e($catKey) ?>" <?= $filters['category'] === $catKey ? 'selected' : '' ?>><?= e($catLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sla_breach" class="form-select form-select-sm bg-light border-0">
                        <option value="">All SLA States</option>
                        <option value="any" <?= $filters['sla_breach'] === 'any' ? 'selected' : '' ?>>Overdue (Any Breach)</option>
                        <option value="response" <?= $filters['sla_breach'] === 'response' ? 'selected' : '' ?>>Response SLA Overdue</option>
                        <option value="resolution" <?= $filters['sla_breach'] === 'resolution' ? 'selected' : '' ?>>Resolution SLA Overdue</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-navy btn-sm w-100 rounded-3">Filter</button>
                    <a href="<?= url('/complaints') ?>" class="btn btn-light btn-sm rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Complaints Data Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Ticket / Title</th>
                            <th>Facility Location</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th>SLA Status</th>
                            <th>Lodged</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No complaints found matching the selected filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($complaints as $c): ?>
                                <?php 
                                $prioInfo = $priorities[$c['priority']] ?? ['label' => ucfirst($c['priority']), 'badge' => 'bg-secondary'];
                                $statInfo = $statuses[$c['status']] ?? ['label' => ucfirst($c['status']), 'badge' => 'bg-secondary'];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-navy-dark">
                                            <a href="<?= url('/complaints/' . $c['id']) ?>" class="text-decoration-none text-navy-dark">
                                                <?= e($c['ticket_number']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 240px;" title="<?= e($c['title']) ?>">
                                            <?= e($c['title']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1"></i><?= e($c['building_name'] ?? 'Hospital') ?>
                                        </span>
                                        <div class="small text-muted mt-1">
                                            <?= e($c['floor_name'] ?? '') ?> &bull; <?= e($c['ward_name'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold text-secondary">
                                            <?= e($categories[$c['category']] ?? ucfirst($c['category'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $prioInfo['badge'] ?> px-2 py-1">
                                            <?= e($prioInfo['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statInfo['badge'] ?> px-2 py-1">
                                            <?= e($statInfo['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['assignee_name'])): ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="bi bi-person-circle text-primary"></i>
                                                <span class="small fw-semibold text-dark"><?= e($c['assignee_name']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $c['sla_badge'] ?? '' ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d M Y', strtotime($c['created_at'])) ?><br>
                                        <span class="text-muted opacity-75"><?= date('H:i', strtotime($c['created_at'])) ?> IST</span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/complaints/' . $c['id']) ?>" class="btn btn-outline-primary btn-sm rounded-3">
                                            <i class="bi bi-eye me-1"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing page <?= $pagination['page'] ?> of <?= $pagination['total_pages'] ?> (<?= $pagination['total'] ?> total records)
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                            <li class="page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= url('/complaints?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
