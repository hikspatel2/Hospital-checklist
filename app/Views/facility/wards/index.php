<?php
/**
 * Wards Index View
 * Lists all clinical wards across Sheth L.G. General Hospital with search, filters, pagination, and QR tokens.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Clinical Wards</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Clinical Wards &amp; Departments</h1>
        <p class="text-muted small mb-0">Multi-Floor Patient Care Zones, Specialty Units &amp; Inpatient Wards</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/locations') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-pin-map me-1"></i> Checkpoints
        </a>
        <a href="<?= url('/floor-plans') ?>" class="btn btn-outline-primary">
            <i class="bi bi-map me-1"></i> Interactive Plan
        </a>
        <a href="<?= url('/admin/wards/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Ward
        </a>
    </div>
</div>

<!-- Search & Filtering Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/wards') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" name="search" placeholder="Search ward name, code, QR..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="building_id" id="filterBuildingSelect">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($filters['building_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['building_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="ward_type">
                    <option value="">All Specialties</option>
                    <option value="general" <?= ($filters['ward_type'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
                    <option value="icu" <?= ($filters['ward_type'] ?? '') === 'icu' ? 'selected' : '' ?>>ICU</option>
                    <option value="emergency" <?= ($filters['ward_type'] ?? '') === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                    <option value="pediatric" <?= ($filters['ward_type'] ?? '') === 'pediatric' ? 'selected' : '' ?>>Pediatric</option>
                    <option value="maternity" <?= ($filters['ward_type'] ?? '') === 'maternity' ? 'selected' : '' ?>>Maternity</option>
                    <option value="surgical" <?= ($filters['ward_type'] ?? '') === 'surgical' ? 'selected' : '' ?>>Surgical</option>
                    <option value="opd" <?= ($filters['ward_type'] ?? '') === 'opd' ? 'selected' : '' ?>>OPD</option>
                    <option value="radiology" <?= ($filters['ward_type'] ?? '') === 'radiology' ? 'selected' : '' ?>>Radiology</option>
                    <option value="lab" <?= ($filters['ward_type'] ?? '') === 'lab' ? 'selected' : '' ?>>Laboratory</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="<?= url('/wards') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Wards Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($wards)): ?>
            <div class="text-center py-5">
                <i class="bi bi-hospital display-5 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No Wards Found</h5>
                <p class="text-muted small mb-3">No clinical wards match the current filter criteria.</p>
                <a href="<?= url('/admin/wards/create') ?>" class="btn btn-sm btn-primary">
                    Create New Ward
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Ward Code</th>
                            <th>Ward &amp; Specialty</th>
                            <th>Facility Location</th>
                            <th>Beds</th>
                            <th>Checkpoints</th>
                            <th>QR Identifier</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wards as $w): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-primary border fw-bold fs-6">
                                        <?= e($w['ward_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= url('/wards/' . $w['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                        <?= e($w['ward_name']) ?>
                                    </a>
                                    <div>
                                        <span class="badge bg-info-subtle text-info text-capitalize" style="font-size: 0.72rem;">
                                            <?= e(str_replace('_', ' ', $w['ward_type'])) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($w['building_name']) ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        Level <?= (int)$w['floor_number'] ?> &bull; <?= e($w['floor_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark fs-6"><?= (int)$w['bed_capacity'] ?></span>
                                    <span class="text-muted small">beds</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold">
                                        <?= (int)($w['checkpoint_count'] ?? 0) ?> locations
                                    </span>
                                </td>
                                <td>
                                    <code class="text-dark bg-light px-2 py-1 rounded small border">
                                        <?= e($w['qr_code_identifier']) ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if ($w['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-slash-circle me-1"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/wards/' . $w['id']) ?>" class="btn btn-outline-primary" title="Ward Details">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="<?= url('/floor-plans?building_id=' . $w['building_id'] . '&floor_id=' . $w['floor_id']) ?>" class="btn btn-outline-info" title="Locate on Floor Plan">
                                            <i class="bi bi-geo-alt"></i>
                                        </a>
                                    </div>
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
                        Showing <?= count($wards) ?> of <?= (int)$pagination['total_records'] ?> wards
                    </span>
                    <nav aria-label="Ward pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                                <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/wards?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
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
