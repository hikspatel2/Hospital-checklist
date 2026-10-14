<?php
/** @var array $template */
/** @var array|null $currentVersion */
/** @var array $items */
/** @var bool $isVersionUsed */
/** @var array $versionHistory */
/** @var array $categories */
/** @var array $itemTypes */
/** @var array $severities */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/checklists') ?>" class="text-decoration-none">Templates</a></li>
                    <li class="breadcrumb-item active"><?= e($template['template_code']) ?></li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-navy-dark"><?= e($template['template_name']) ?></h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/checklists') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Templates
            </a>
            <?php if ($isVersionUsed): ?>
                <button type="button" class="btn btn-warning btn-sm rounded-3 px-3 shadow-sm text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#branchVersionModal">
                    <i class="bi bi-diagram-3 me-1"></i> Branch New Version (v<?= ((int)$currentVersion['version_number'] + 1) ?>)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Immutability Alert Banner -->
    <?php if ($isVersionUsed): ?>
        <div class="alert alert-warning border-warning border-opacity-50 rounded-4 shadow-sm mb-4 d-flex align-items-start gap-3 p-3">
            <i class="bi bi-shield-lock-fill fs-3 text-warning flex-shrink-0 mt-1"></i>
            <div>
                <h6 class="fw-bold mb-1 text-dark">Immutable Version Snapshot Protection Active (v<?= e((string)$currentVersion['version_number']) ?>)</h6>
                <p class="small text-muted mb-2">
                    This template version is already referenced by completed hospital inspections. Under the <strong>Global Project Rules</strong>, the meaning and structure of completed checklists cannot be retroactively modified.
                </p>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-secondary small">Direct edits disabled</span>
                    <button type="button" class="btn btn-sm btn-dark rounded-3 px-3 py-1" data-bs-toggle="modal" data-bs-target="#branchVersionModal">
                        <i class="bi bi-plus-circle me-1"></i> Branch Version <?= ((int)$currentVersion['version_number'] + 1) ?> to Add/Edit Items
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info border-info border-opacity-25 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-3 p-3">
            <i class="bi bi-pencil-square fs-4 text-info flex-shrink-0"></i>
            <div class="small">
                <strong>Active Version <?= e((string)($currentVersion['version_number'] ?? '1')) ?> (Draft/Editable):</strong>
                This version has not yet been locked by completed inspections. You can freely add, modify, or remove questions.
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left: Items Management -->
        <div class="col-lg-8">
            <!-- Add Item Form (Only enabled if version not used) -->
            <?php if (!$isVersionUsed && $currentVersion): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom border-light px-4 py-3">
                        <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Inspection Checkpoint / Question</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?= url("/admin/checklists/{$template['id']}/items") ?>">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Item Label / Question Text *</label>
                                    <input type="text" name="item_label" class="form-control form-control-sm" placeholder="e.g. Bio-medical waste bins properly color-coded and segregated" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Input Type *</label>
                                    <select name="item_type" class="form-select form-select-sm">
                                        <?php foreach ($itemTypes as $typeKey => $typeLabel): ?>
                                            <option value="<?= e($typeKey) ?>"><?= e($typeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Severity / Risk Level *</label>
                                    <select name="severity" class="form-select form-select-sm">
                                        <?php foreach ($severities as $sevKey => $sevLabel): ?>
                                            <option value="<?= e($sevKey) ?>" <?= $sevKey === 'medium' ? 'selected' : '' ?>><?= e($sevLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-center pt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="itemRequiredSwitch" checked>
                                        <label class="form-check-label small fw-semibold" for="itemRequiredSwitch">Required Answer</label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-center pt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="requires_evidence" value="1" id="itemEvidenceSwitch">
                                        <label class="form-check-label small fw-semibold" for="itemEvidenceSwitch">Requires Photo Evidence</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Inspection Criteria / Protocol Guidance</label>
                                    <input type="text" name="description" class="form-control form-control-sm" placeholder="Optional notes for supervisor on how to evaluate this item...">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4 shadow-sm">
                                        <i class="bi bi-plus-lg me-1"></i> Add Checkpoint Item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Existing Items List -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom border-light px-4 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-navy-dark">
                        <i class="bi bi-list-check me-2 text-primary"></i>Checklist Items (Version <?= e((string)($currentVersion['version_number'] ?? '1')) ?>)
                    </h6>
                    <span class="badge bg-light text-dark border"><?= count($items) ?> Total Items</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase fw-semibold">
                            <tr>
                                <th class="ps-4" style="width: 40px;">#</th>
                                <th>Checkpoint Label / Guidance</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Required</th>
                                <?php if (!$isVersionUsed): ?>
                                    <th class="text-end pe-4">Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-question-circle fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        No checklist items added to this version yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $idx => $item): ?>
                                    <tr>
                                        <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($item['item_label']) ?></div>
                                            <?php if (!empty($item['description'])): ?>
                                                <div class="small text-muted"><?= e($item['description']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($item['requires_evidence'])): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 small px-2 py-0 mt-1">
                                                    <i class="bi bi-camera me-1"></i>Photo Required
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border small">
                                                <?= e($itemTypes[$item['item_type']] ?? $item['item_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                                $sev = $item['severity'] ?? 'medium';
                                                $sevBadge = match($sev) {
                                                    'critical' => 'bg-danger text-white',
                                                    'high'     => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                    'medium'   => 'bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25',
                                                    default    => 'bg-light text-muted border',
                                                };
                                            ?>
                                            <span class="badge <?= $sevBadge ?> small px-2 py-1">
                                                <?= e(ucfirst($sev)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['is_required'])): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">Required</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border small">Optional</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (!$isVersionUsed): ?>
                                            <td class="text-end pe-4">
                                                <form method="POST" action="<?= url("/admin/checklists/{$template['id']}/items/{$item['id']}/delete") ?>" onsubmit="return confirm('Remove this question?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Delete Item">
                                                        <i class="bi bi-trash fs-6"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Version History & Metadata -->
        <div class="col-lg-4">
            <!-- Metadata Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom border-light px-4 py-3">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Template Metadata</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <div class="text-muted small">Category</div>
                        <div class="fw-bold text-dark"><?= e($categories[$template['category']] ?? $template['category']) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Template Code</div>
                        <div class="font-monospace fw-bold text-primary"><?= e($template['template_code']) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Current Status</div>
                        <div>
                            <?php if ($template['status'] === 'published'): ?>
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1">Published</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 px-2 py-1"><?= ucfirst($template['status']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($template['description'])): ?>
                        <div class="mb-3">
                            <div class="text-muted small">Guidelines</div>
                            <div class="small text-dark"><?= nl2br(e($template['description'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Version History Audit Log -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom border-light px-4 py-3">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Version Snapshot History</h6>
                </div>
                <div class="card-body p-3">
                    <div class="list-group list-group-flush">
                        <?php foreach ($versionHistory as $vh): ?>
                            <div class="list-group-item px-2 py-3 border-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge <?= $vh['is_current'] ? 'bg-primary' : 'bg-light text-muted border' ?> rounded-pill px-2 py-1">
                                        v<?= e((string)$vh['version_number']) ?> <?= $vh['is_current'] ? '(Active)' : '' ?>
                                    </span>
                                    <span class="small text-muted"><?= e(date('d M Y', strtotime($vh['created_at']))) ?></span>
                                </div>
                                <div class="small text-dark fw-semibold"><?= e($vh['changelog'] ?? 'Standard version snapshot') ?></div>
                                <div class="d-flex gap-3 small text-muted mt-1">
                                    <span><i class="bi bi-card-checklist me-1"></i><?= (int)($vh['item_count'] ?? 0) ?> items</span>
                                    <span><i class="bi bi-clipboard-check me-1"></i><?= (int)($vh['inspection_count'] ?? 0) ?> inspections</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Version Modal -->
<div class="modal fade" id="branchVersionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-navy text-white px-4 py-3">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-diagram-3 me-2"></i>Branch New Checklist Version</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url("/admin/checklists/{$template['id']}/branch-version") ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        This action will clone all items from <strong>Version <?= e((string)($currentVersion['version_number'] ?? '1')) ?></strong> into a new <strong>Version <?= ((int)($currentVersion['version_number'] ?? 1) + 1) ?></strong>.
                        All historical inspections will remain securely tied to Version <?= e((string)($currentVersion['version_number'] ?? '1')) ?>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Version Changelog / Reason *</label>
                        <textarea name="changelog" class="form-control form-control-sm" rows="3" placeholder="Explain what changes are being made to protocol or checkpoints..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-0">
                    <button type="button" class="btn btn-sm btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-3 px-4">Create Version <?= ((int)($currentVersion['version_number'] ?? 1) + 1) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
