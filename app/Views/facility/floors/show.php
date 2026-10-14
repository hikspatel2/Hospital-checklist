<?php
/**
 * Floor Detail View
 * Shows floor information, linked clinical wards, area inspection summaries,
 * protected blueprint upload, and safe deactivation.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/buildings') ?>">Buildings</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/buildings/' . $floor['building_id']) ?>"><?= e($floor['building_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($floor['floor_name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 fw-bold text-dark mb-0"><?= e($floor['floor_name']) ?></h1>
            <span class="badge bg-dark-subtle text-dark">Level <?= (int)$floor['floor_number'] ?></span>
            <?php if ($floor['status'] === 'active'): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
            <?php else: ?>
                <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i> Inactive</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/floor-plans?building_id=' . $floor['building_id'] . '&floor_id=' . $floor['id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-map me-1"></i> Interactive Blueprint
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#uploadPlanModal">
            <i class="bi bi-cloud-arrow-up me-1"></i> Upload Plan
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWardModal">
            <i class="bi bi-plus-lg me-1"></i> Add Ward
        </button>
    </div>
</div>

<!-- Inspection Status Ribbon / Floor Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small fw-semibold">TOTAL WARDS</div>
            <div class="h3 fw-bold text-primary mb-0"><?= count($wards) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small fw-semibold">ACTIVE AREAS</div>
            <div class="h3 fw-bold text-dark mb-0"><?= (int)($summary['total_areas'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center p-3 border-bottom border-success border-4">
            <div class="text-success small fw-semibold">COMPLIANT</div>
            <div class="h3 fw-bold text-success mb-0"><?= (int)($summary['compliant_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center p-3 border-bottom border-warning border-4">
            <div class="text-warning small fw-semibold">WARNING</div>
            <div class="h3 fw-bold text-warning mb-0"><?= (int)($summary['warning_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center p-3 border-bottom border-danger border-4">
            <div class="text-danger small fw-semibold">CRITICAL</div>
            <div class="h3 fw-bold text-danger mb-0"><?= (int)($summary['critical_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center p-3 border-bottom border-info border-4">
            <div class="text-info small fw-semibold">PENDING</div>
            <div class="h3 fw-bold text-info mb-0"><?= (int)($summary['pending_count'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Floor Attributes & Blueprint Details -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold text-dark mb-0">Floor Parameters</h5>
                <button type="button" class="btn btn-sm btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#editFloorModal">
                    Edit
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small d-block">Parent Building</label>
                    <a href="<?= url('/buildings/' . $floor['building_id']) ?>" class="fw-semibold text-primary text-decoration-none">
                        <?= e($floor['building_name']) ?> (<?= e($floor['building_code']) ?>)
                    </a>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Level Index</label>
                    <span class="fw-bold text-dark fs-5">Floor Level <?= (int)$floor['floor_number'] ?></span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Blueprint Blueprint File</label>
                    <?php if (!empty($floor['plan_image_path'])): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border">
                                <i class="bi bi-file-earmark-check-fill me-1"></i> Blueprint Configured
                            </span>
                            <a href="<?= e($floor['plan_image_path']) ?>" target="_blank" class="small text-muted">
                                <i class="bi bi-box-arrow-up-right"></i> Open File
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning border">No Blueprint Uploaded</span>
                    <?php endif; ?>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold small d-block">Floor Status</span>
                        <span class="text-muted" style="font-size: 0.78rem;">
                            <?= $floor['status'] === 'active' ? 'Active in inspection rotations' : 'Deactivated / Archived' ?>
                        </span>
                    </div>
                    <form action="<?= url('/admin/floors/' . $floor['id'] . '/toggle-status') ?>" method="POST" onsubmit="return confirm('Toggle status for this floor level? Historical inspections will be preserved.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $floor['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                            <?= $floor['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Interactive Blueprint Card -->
        <div class="card border-0 shadow-sm bg-primary text-white text-center p-4">
            <i class="bi bi-map display-4 mb-2"></i>
            <h5 class="fw-bold mb-1">Visual Floor Blueprint</h5>
            <p class="text-white-50 small mb-3">View room zones, real-time inspection color overlays, and bed metrics.</p>
            <a href="<?= url('/floor-plans?building_id=' . $floor['building_id'] . '&floor_id=' . $floor['id']) ?>" class="btn btn-light text-primary fw-semibold">
                Open Floor Blueprint
            </a>
        </div>
    </div>

    <!-- Right Column: Wards on this Floor -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-hospital text-primary me-2 fs-5"></i>
                    <h5 class="card-title fw-bold text-dark mb-0">Clinical Wards &amp; Units (<?= count($wards) ?>)</h5>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createWardModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Ward
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($wards)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-hospital text-muted display-5 mb-2 d-block"></i>
                        <h6 class="fw-bold">No Wards on this Floor</h6>
                        <p class="text-muted small mb-3">Assign clinical wards or patient rooms to this level.</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createWardModal">
                            Add First Ward
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4">Ward Code</th>
                                    <th>Ward Name</th>
                                    <th>Type</th>
                                    <th>Beds</th>
                                    <th>Checkpoints</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wards as $w): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-light text-primary border fw-bold">
                                                <?= e($w['ward_code']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= url('/wards/' . $w['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= e($w['ward_name']) ?>
                                            </a>
                                            <div class="text-muted small" style="font-size: 0.72rem;">
                                                QR: <?= e($w['qr_code_identifier']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info text-capitalize">
                                                <?= e(str_replace('_', ' ', $w['ward_type'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= (int)$w['bed_capacity'] ?></span>
                                            <span class="text-muted small">beds</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <?= (int)($w['checkpoint_count'] ?? 0) ?> points
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($w['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= url('/wards/' . $w['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
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

<!-- Modal: Edit Floor -->
<div class="modal fade" id="editFloorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/floors/' . $floor['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Edit Floor Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Number (Index) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="floor_number" value="<?= (int)$floor['floor_number'] ?>" required step="1">
                        <div class="form-text">Must be unique within <?= e($floor['building_name']) ?>.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Name / Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="floor_name" value="<?= e($floor['floor_name']) ?>" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Floor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Upload Blueprint File (Protected) -->
<div class="modal fade" id="uploadPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/floors/' . $floor['id'] . '/upload-plan') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-check me-2"></i>Upload Floor Plan Blueprint
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Upload a vector blueprint (<strong>.SVG</strong>) or high-resolution architectural map (<strong>.PNG, .JPG</strong>). Maximum 5MB.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Blueprint File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="floor_plan_file" required accept=".svg,.png,.jpg,.jpeg">
                        <div class="form-text">
                            Strict security check: SVG files are scanned for malicious scripts and entity exploits before server storage.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload &amp; Secure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Ward to Floor -->
<div class="modal fade" id="createWardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/wards') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="floor_id" value="<?= $floor['id'] ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-hospital me-2"></i>Add Ward to <?= e($floor['floor_name']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ward Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="ward_code" required placeholder="e.g. ICU-01 or GW-2A" maxlength="50">
                        <div class="form-text">Unique code across the hospital.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ward Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ward_name" required placeholder="e.g. Intensive Care Unit A, Female Medical Ward" maxlength="150">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Ward Specialty / Type</label>
                            <select class="form-select" name="ward_type">
                                <option value="general">General Ward</option>
                                <option value="icu">Intensive Care Unit (ICU)</option>
                                <option value="emergency">Emergency / Trauma</option>
                                <option value="pediatric">Pediatric Ward</option>
                                <option value="maternity">Maternity &amp; NICU</option>
                                <option value="surgical">Post-Surgical Care</option>
                                <option value="opd">Outpatient Department (OPD)</option>
                                <option value="radiology">Radiology &amp; Imaging</option>
                                <option value="lab">Pathology Laboratory</option>
                                <option value="administrative">Administrative / Office</option>
                                <option value="other">Other Facility Zone</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Bed Capacity</label>
                            <input type="number" class="form-control" name="bed_capacity" value="10" min="0" step="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Ward</button>
                </div>
            </form>
        </div>
    </div>
</div>
