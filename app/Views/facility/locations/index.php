<?php
/**
 * Inspection Locations / Checkpoints Index View
 * Lists all inspection checkpoints across the hospital with QR codes, filters, and safe deactivation.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Inspection Checkpoints</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Inspection Checkpoints &amp; QR Locations</h1>
        <p class="text-muted small mb-0">Granular Sanitation &amp; Clinical Checkpoints (Washrooms, Beds, Stations)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/wards') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-hospital me-1"></i> Wards
        </a>
        <a href="<?= url('/floor-plans') ?>" class="btn btn-outline-primary">
            <i class="bi bi-map me-1"></i> Interactive Floor Plan
        </a>
    </div>
</div>

<!-- Search & Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/locations') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" name="search" placeholder="Search checkpoint name, code, QR..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
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
                <select class="form-select" name="location_type">
                    <option value="">All Zone Types</option>
                    <option value="washroom" <?= ($filters['location_type'] ?? '') === 'washroom' ? 'selected' : '' ?>>Washrooms</option>
                    <option value="patient_bed" <?= ($filters['location_type'] ?? '') === 'patient_bed' ? 'selected' : '' ?>>Patient Beds</option>
                    <option value="nursing_station" <?= ($filters['location_type'] ?? '') === 'nursing_station' ? 'selected' : '' ?>>Nursing Stations</option>
                    <option value="corridor" <?= ($filters['location_type'] ?? '') === 'corridor' ? 'selected' : '' ?>>Corridors</option>
                    <option value="doctor_cabin" <?= ($filters['location_type'] ?? '') === 'doctor_cabin' ? 'selected' : '' ?>>Doctor Cabins</option>
                    <option value="waste_disposal" <?= ($filters['location_type'] ?? '') === 'waste_disposal' ? 'selected' : '' ?>>Waste Disposal</option>
                    <option value="storage" <?= ($filters['location_type'] ?? '') === 'storage' ? 'selected' : '' ?>>Storage</option>
                    <option value="emergency_exit" <?= ($filters['location_type'] ?? '') === 'emergency_exit' ? 'selected' : '' ?>>Emergency Exits</option>
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
                <a href="<?= url('/locations') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Locations Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($locations)): ?>
            <div class="text-center py-5">
                <i class="bi bi-pin-map display-5 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No Checkpoints Found</h5>
                <p class="text-muted small mb-0">No checkpoints match your filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Checkpoint Name</th>
                            <th>Zone Type</th>
                            <th>Facility Hierarchy</th>
                            <th>QR Identifier</th>
                            <th>Inspections</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-primary border fw-bold">
                                        <?= e($loc['location_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block"><?= e($loc['location_name']) ?></span>
                                    <span class="text-muted small" style="font-size: 0.72rem;">ID #<?= $loc['id'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary text-capitalize">
                                        <?= e(str_replace('_', ' ', $loc['location_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($loc['ward_name']) ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        <?= e($loc['building_name']) ?> &bull; L<?= (int)$loc['floor_number'] ?> (<?= e($loc['floor_name']) ?>)
                                    </div>
                                </td>
                                <td>
                                    <code class="text-dark bg-light px-2 py-1 rounded small border">
                                        <?= e($loc['qr_code_identifier']) ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        <?= (int)$loc['inspection_count'] ?> visits
                                    </span>
                                </td>
                                <td>
                                    <?php if ($loc['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="<?= url('/admin/locations/' . $loc['id'] . '/toggle-status') ?>" method="POST" class="d-inline" onsubmit="return confirm('Toggle checkpoint status? Historical records will be safely preserved.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm <?= $loc['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <?= $loc['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
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
                        Showing <?= count($locations) ?> of <?= (int)$pagination['total_records'] ?> checkpoints
                    </span>
                    <nav aria-label="Locations pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                                <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/locations?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
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
