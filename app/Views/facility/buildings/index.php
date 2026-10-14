<?php
/**
 * Buildings Index View
 * Lists all hospital buildings with floor counts, ward counts, bed capacities, and quick-add modal.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Buildings &amp; Infrastructure</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Hospital Buildings &amp; Infrastructure</h1>
        <p class="text-muted small mb-0">Sheth L.G. General Hospital Campus Multi-Wing Infrastructure</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/floor-plans') ?>" class="btn btn-outline-primary">
            <i class="bi bi-map me-1"></i> Interactive Floor Plan
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBuildingModal">
            <i class="bi bi-plus-lg me-1"></i> Add Building
        </button>
    </div>
</div>

<!-- Status Filter Pills -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Filter Status:</span>
            <a href="<?= url('/buildings') ?>" class="btn btn-sm <?= empty($currentStatus) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                All Buildings (<?= count($buildings) ?>)
            </a>
            <a href="<?= url('/buildings?status=active') ?>" class="btn btn-sm <?= $currentStatus === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>">
                Active Only
            </a>
            <a href="<?= url('/buildings?status=inactive') ?>" class="btn btn-sm <?= $currentStatus === 'inactive' ? 'btn-warning' : 'btn-outline-secondary' ?>">
                Inactive / Archived
            </a>
        </div>
        <div class="text-muted small">
            <i class="bi bi-shield-check text-success me-1"></i> Safe Deactivation Enforced
        </div>
    </div>
</div>

<!-- Buildings Cards Grid -->
<div class="row g-4">
    <?php if (empty($buildings)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-building display-4 text-muted mb-3 d-block"></i>
                    <h5 class="fw-bold">No Buildings Found</h5>
                    <p class="text-muted mb-3">No hospital buildings match the selected status filter.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBuildingModal">
                        Add First Building
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($buildings as $b): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 facility-card transition-all">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="facility-badge-icon me-2 <?= $b['status'] === 'active' ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <span class="badge bg-light text-primary border fw-bold"><?= e($b['building_code']) ?></span>
                            </div>
                        </div>
                        <?php if ($b['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="bi bi-check-circle-fill me-1"></i> Active
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                <i class="bi bi-slash-circle me-1"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-dark mb-1">
                            <a href="<?= url('/buildings/' . $b['id']) ?>" class="text-decoration-none text-dark hover-primary">
                                <?= e($b['building_name']) ?>
                            </a>
                        </h5>
                        <p class="text-muted small mb-3">
                            <?= !empty($b['description']) ? e($b['description']) : 'Sheth L.G. General Hospital inpatient and clinical care wing.' ?>
                        </p>

                        <!-- Key Facility Metrics -->
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="fw-bold text-primary fs-5"><?= (int)$b['floor_count'] ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">FLOORS</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="fw-bold text-info fs-5"><?= (int)$b['ward_count'] ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">WARDS</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="fw-bold text-success fs-5"><?= (int)$b['total_beds'] ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">BEDS</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                        <a href="<?= url('/buildings/' . $b['id']) ?>" class="btn btn-sm btn-outline-primary">
                            Manage Floors <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        <a href="<?= url('/floor-plans?building_id=' . $b['id']) ?>" class="btn btn-sm btn-light text-primary" title="View on Floor Plan">
                            <i class="bi bi-map"></i> Plan
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Create Building -->
<div class="modal fade" id="createBuildingModal" tabindex="-1" aria-labelledby="createBuildingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/buildings') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="createBuildingModalLabel">
                        <i class="bi bi-building-add me-2"></i>Add Hospital Building
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="building_code" class="form-label fw-semibold">Building Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="building_code" name="building_code" required placeholder="e.g. BLD-A or SURG-WING" maxlength="50">
                        <div class="form-text">Unique uppercase code across the hospital campus.</div>
                    </div>
                    <div class="mb-3">
                        <label for="building_name" class="form-label fw-semibold">Building / Wing Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="building_name" name="building_name" required placeholder="e.g. Main Hospital Building, Trauma Center" maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description / Notes</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Clinical specialties, OPD counters, emergency zones..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Building
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
