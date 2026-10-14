<?php
/** @var array $templates */
/** @var array $pagination */
/** @var array $filters */
/** @var array $buildings */
/** @var array $categories */
?>
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Phase 5</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Inspection Checklist Templates</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">Multi-facility standardized checklists, immutable version snapshots, and severity configuration.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/schedules/rules') ?>" class="btn btn-outline-primary btn-sm rounded-3">
                <i class="bi bi-clock-history me-1"></i> Recurrence Rules
            </a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                    <i class="bi bi-plus-circle me-1"></i> Create Template
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('/checklists') ?>" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search template name or code..." value="<?= e($filters['search']) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $catKey => $catLabel): ?>
                            <option value="<?= e($catKey) ?>" <?= $filters['category'] === $catKey ? 'selected' : '' ?>><?= e($catLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Statuses</option>
                        <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="archived" <?= $filters['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-navy btn-sm w-100 rounded-3">Filter</button>
                    <a href="<?= url('/checklists') ?>" class="btn btn-light btn-sm rounded-3">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">Code / Template</th>
                        <th>Category</th>
                        <th>Target Facility Scope</th>
                        <th>Active Version</th>
                        <th>Items</th>
                        <th>Inspections Done</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-card-checklist fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No checklist templates found matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $t): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-navy-dark"><?= e($t['template_name']) ?></div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace small px-2 py-0">
                                        <?= e($t['template_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 small">
                                        <?= e($categories[$t['category']] ?? $t['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($t['ward_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-hospital me-1 text-primary"></i>Ward: <?= e($t['ward_name']) ?></span>
                                    <?php elseif (!empty($t['floor_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-layers me-1 text-primary"></i>Floor: <?= e($t['floor_name']) ?></span>
                                    <?php elseif (!empty($t['building_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-building me-1 text-primary"></i>Bldg: <?= e($t['building_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small">All Hospital Zones</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1">
                                        v<?= e((string)($t['current_version_number'] ?? '1')) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= (int)($t['item_count'] ?? 0) ?> items</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= (int)($t['inspection_count'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'published'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Published</span>
                                    <?php elseif ($t['status'] === 'draft'): ?>
                                        <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 px-2 py-1">Draft</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">Archived</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <a href="<?= url("/checklists/{$t['id']}/edit") ?>" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1" title="Edit Template & Items">
                                            <i class="bi bi-pencil-square me-1"></i> Manage
                                        </a>
                                        <?php if (\App\Core\Auth::isAdmin()): ?>
                                            <form method="POST" action="<?= url("/admin/checklists/{$t['id']}/toggle-status") ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm <?= $t['status'] === 'published' ? 'btn-outline-warning' : 'btn-outline-success' ?> rounded-3 px-2 py-1" title="Toggle Publish Status">
                                                    <?= $t['status'] === 'published' ? 'Unpublish' : 'Publish' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Showing <?= count($templates) ?> of <?= $pagination['total_records'] ?> templates</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $p ?>&category=<?= e($filters['category']) ?>&status=<?= e($filters['status']) ?>&search=<?= e($filters['search']) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-navy text-white px-4 py-3">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-plus-circle me-2"></i>Create Inspection Checklist Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('/admin/checklists') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Template Code *</label>
                            <input type="text" name="template_code" class="form-control form-control-sm text-uppercase font-monospace" placeholder="e.g. HYG-ICU-DAILY" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Template Name *</label>
                            <input type="text" name="template_name" class="form-control form-control-sm" placeholder="e.g. ICU Daily Hygiene & Sterilization Checklist" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select name="category" class="form-select form-select-sm" required>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?= e($catKey) ?>"><?= e($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Facility Scope (Target Building)</label>
                            <select name="target_building_id" class="form-select form-select-sm">
                                <option value="">Global (All Hospital Buildings)</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['building_name']) ?> (<?= e($b['building_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description / Protocol Guidelines</label>
                            <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="Enter inspection criteria, standards, and regulatory guidance for supervisors..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="isMandatoryCheck" checked>
                                <label class="form-check-label small" for="isMandatoryCheck">
                                    Mandatory checklist (Flag facility compliance alert if missed)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-0">
                    <button type="button" class="btn btn-sm btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-3 px-4">Create & Add Items</button>
                </div>
            </form>
        </div>
    </div>
</div>
