<?php
/**
 * Building Detail & Floor Levels View
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/buildings') ?>">Buildings</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($building['building_name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 fw-bold text-dark mb-0"><?= e($building['building_name']) ?></h1>
            <span class="badge bg-primary text-uppercase"><?= e($building['building_code']) ?></span>
            <?php if ($building['status'] === 'active'): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
            <?php else: ?>
                <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i> Inactive</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editBuildingModal">
            <i class="bi bi-pencil me-1"></i> Edit Details
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFloorModal">
            <i class="bi bi-plus-lg me-1"></i> Add Floor Level
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Building Profile & Status Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark mb-0">Building Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small d-block">Campus Identification</label>
                    <span class="fw-semibold text-dark fs-5"><?= e($building['building_name']) ?></span>
                    <span class="badge bg-light text-primary border ms-2"><?= e($building['building_code']) ?></span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Description</label>
                    <p class="text-dark mb-0">
                        <?= !empty($building['description']) ? nl2br(e($building['description'])) : '<span class="text-muted">No notes recorded.</span>' ?>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Record Created</label>
                    <span class="text-dark small"><?= e(date('d M Y, h:i A', strtotime($building['created_at']))) ?></span>
                </div>
                <hr>
                <!-- Safe Deactivation Controls -->
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold small d-block">Operational Status</span>
                        <span class="text-muted" style="font-size: 0.78rem;">
                            <?= $building['status'] === 'active' ? 'Accepting daily inspections' : 'Archived / Deactivated' ?>
                        </span>
                    </div>
                    <form action="<?= url('/admin/buildings/' . $building['id'] . '/toggle-status') ?>" method="POST" onsubmit="return confirm('Change operational status of this building? Historical inspection records will be preserved.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $building['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                            <?= $building['status'] === 'active' ? 'Deactivate Building' : 'Activate Building' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Safe Deactivation Notice Card -->
        <div class="card border-0 bg-primary-subtle shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-start">
                    <i class="bi bi-shield-lock-fill text-primary fs-4 me-2"></i>
                    <div>
                        <div class="fw-bold text-primary small">Data Protection Guarantee</div>
                        <div class="text-dark small" style="font-size: 0.78rem;">
                            Buildings and floors cannot be physically wiped if linked to inspections or complaints. Safe deactivation guarantees zero orphaned records.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Floor Levels Hierarchy -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-layers-fill text-primary me-2 fs-5"></i>
                    <h5 class="card-title fw-bold text-dark mb-0">Floor Levels (<?= count($floors) ?>)</h5>
                </div>
                <span class="badge bg-light text-muted border">Building #<?= $building['id'] ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($floors)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-layers text-muted display-5 mb-2 d-block"></i>
                        <h6 class="fw-bold">No Floor Levels Configured</h6>
                        <p class="text-muted small mb-3">Add ground floor, first floor, or basement for this hospital building.</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createFloorModal">
                            Add Floor Level
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4">Floor #</th>
                                    <th>Floor Name</th>
                                    <th>Wards</th>
                                    <th>Checkpoints</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($floors as $fl): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-dark-subtle text-dark fw-bold px-2 py-1">
                                                L<?= (int)$fl['floor_number'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= url('/floors/' . $fl['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= e($fl['floor_name']) ?>
                                            </a>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <?= !empty($fl['plan_image_path']) ? '<i class="bi bi-file-earmark-check text-success"></i> Blueprint Linked' : '<i class="bi bi-file-earmark-x text-muted"></i> No Blueprint' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary fw-semibold">
                                                <?= (int)$fl['ward_count'] ?> Wards
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info fw-semibold">
                                                <?= (int)$fl['checkpoint_count'] ?> Checkpoints
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($fl['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('/floors/' . $fl['id']) ?>" class="btn btn-outline-primary" title="Floor Details &amp; Wards">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="<?= url('/floor-plans?building_id=' . $building['id'] . '&floor_id=' . $fl['id']) ?>" class="btn btn-outline-info" title="Interactive Vector Blueprint">
                                                    <i class="bi bi-map"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Building -->
<div class="modal fade" id="editBuildingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/buildings/' . $building['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Edit <?= e($building['building_name']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Building Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="building_code" value="<?= e($building['building_code']) ?>" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Building Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="building_name" value="<?= e($building['building_name']) ?>" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?= e($building['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Create Floor Level -->
<div class="modal fade" id="createFloorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/floors') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="building_id" value="<?= $building['id'] ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-layers-fill me-2"></i>Add Floor Level
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        Adding floor to <strong><?= e($building['building_name']) ?></strong> (<?= e($building['building_code']) ?>). Floor numbers must be unique within this building.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Number (Index) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="floor_number" required placeholder="0 for Ground, 1 for 1st Floor, -1 for Basement" step="1">
                        <div class="form-text">0 = Ground Floor, 1 = First Floor, 2 = Second Floor, etc.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Name / Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="floor_name" required placeholder="e.g. Ground Floor - Trauma &amp; Emergency" maxlength="100">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Floor Level</button>
                </div>
            </form>
        </div>
    </div>
</div>
