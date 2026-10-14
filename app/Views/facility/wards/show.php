<?php
/**
 * Ward Detail View
 * Shows ward metadata, QR identifier card, inspection locations / checkpoints list,
 * assigned supervisors with effective dates, and safe deactivation.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/buildings') ?>">Buildings</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/buildings/' . $ward['building_id']) ?>"><?= e($ward['building_name']) ?></a></li>
                <li class="breadcrumb-item"><a href="<?= url('/floors/' . $ward['floor_id']) ?>"><?= e($ward['floor_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($ward['ward_name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 fw-bold text-dark mb-0"><?= e($ward['ward_name']) ?></h1>
            <span class="badge bg-primary fs-6"><?= e($ward['ward_code']) ?></span>
            <?php if ($ward['status'] === 'active'): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
            <?php else: ?>
                <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i> Inactive</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/floor-plans?building_id=' . $ward['building_id'] . '&floor_id=' . $ward['floor_id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-map me-1"></i> View on Blueprint
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editWardModal">
            <i class="bi bi-pencil me-1"></i> Edit Ward
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal">
            <i class="bi bi-plus-lg me-1"></i> Add Checkpoint
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Ward Profile & QR Identifier -->
    <div class="col-lg-4">
        <!-- QR Code Security Card -->
        <div class="card border-0 shadow-sm mb-4 text-center">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="card-title fw-bold mb-0">
                    <i class="bi bi-qr-code me-1"></i> Digital Ward Inspection Checkpoint
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="bg-light p-3 rounded d-inline-block border mb-3">
                    <!-- SVG QR Graphic Representation -->
                    <svg width="140" height="140" viewBox="0 0 100 100" class="d-block mx-auto">
                        <rect width="100" height="100" fill="#ffffff"/>
                        <!-- QR Corner Markers -->
                        <rect x="5" y="5" width="30" height="30" fill="#0f2c59"/>
                        <rect x="9" y="9" width="22" height="22" fill="#ffffff"/>
                        <rect x="13" y="13" width="14" height="14" fill="#0f2c59"/>

                        <rect x="65" y="5" width="30" height="30" fill="#0f2c59"/>
                        <rect x="69" y="9" width="22" height="22" fill="#ffffff"/>
                        <rect x="73" y="13" width="14" height="14" fill="#0f2c59"/>

                        <rect x="5" y="65" width="30" height="30" fill="#0f2c59"/>
                        <rect x="9" y="69" width="22" height="22" fill="#ffffff"/>
                        <rect x="13" y="73" width="14" height="14" fill="#0f2c59"/>

                        <!-- QR Bitmatrix Pattern -->
                        <rect x="42" y="10" width="8" height="8" fill="#0f2c59"/>
                        <rect x="54" y="10" width="6" height="6" fill="#0f2c59"/>
                        <rect x="42" y="24" width="6" height="6" fill="#0f2c59"/>
                        <rect x="15" y="44" width="8" height="8" fill="#0f2c59"/>
                        <rect x="28" y="44" width="6" height="6" fill="#0f2c59"/>
                        <rect x="42" y="42" width="16" height="16" fill="#0f2c59"/>
                        <rect x="46" y="46" width="8" height="8" fill="#ffffff"/>
                        <rect x="65" y="44" width="6" height="6" fill="#0f2c59"/>
                        <rect x="80" y="44" width="8" height="8" fill="#0f2c59"/>
                        <rect x="42" y="65" width="8" height="8" fill="#0f2c59"/>
                        <rect x="55" y="70" width="6" height="6" fill="#0f2c59"/>
                        <rect x="70" y="65" width="8" height="8" fill="#0f2c59"/>
                        <rect x="82" y="75" width="10" height="10" fill="#0f2c59"/>
                    </svg>
                </div>
                <div class="fw-bold text-dark mb-1">QR Code Token</div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control text-center font-monospace bg-light" id="qrTokenInput" value="<?= e($ward['qr_code_identifier']) ?>" readonly>
                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('qrTokenInput').value); alert('QR Token copied to clipboard!');">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="text-muted small">
                    Supervisors scan this token on mobile to initiate instant sanitation checklists and register complaints.
                </div>
            </div>
        </div>

        <!-- Ward Details Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="card-title fw-bold text-dark mb-0">Ward Metadata</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small d-block">Facility Wing &amp; Floor</label>
                    <span class="fw-semibold text-dark"><?= e($ward['building_name']) ?></span>
                    <span class="text-muted d-block small">Floor Level <?= (int)$ward['floor_number'] ?> &bull; <?= e($ward['floor_name']) ?></span>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="text-muted small d-block">Clinical Specialty</label>
                        <span class="badge bg-info-subtle text-info text-capitalize fs-6">
                            <?= e(str_replace('_', ' ', $ward['ward_type'])) ?>
                        </span>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small d-block">Inpatient Beds</label>
                        <span class="fw-bold text-dark fs-5"><?= (int)$ward['bed_capacity'] ?></span>
                        <span class="text-muted small">beds</span>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold small d-block">Operational Status</span>
                        <span class="text-muted" style="font-size: 0.78rem;">
                            <?= $ward['status'] === 'active' ? 'Active in inspection roster' : 'Archived / Inactive' ?>
                        </span>
                    </div>
                    <form action="<?= url('/admin/wards/' . $ward['id'] . '/toggle-status') ?>" method="POST" onsubmit="return confirm('Toggle status for this ward? Historical inspections will be preserved.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $ward['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                            <?= $ward['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Checkpoints & Assigned Supervisors -->
    <div class="col-lg-8">
        <!-- Section 1: Inspection Locations / Checkpoints -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-pin-map text-primary me-2 fs-5"></i>
                    <h5 class="card-title fw-bold text-dark mb-0">Granular Checkpoints (<?= count($locations) ?>)</h5>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Checkpoint
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($locations)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-pin-map text-muted display-5 mb-2 d-block"></i>
                        <h6 class="fw-bold">No Checkpoints Configured</h6>
                        <p class="text-muted small mb-3">Add specific inspection checkpoints (e.g. Washroom Block, Nursing Station, Bed Cubicle).</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                            Add First Checkpoint
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4">Code</th>
                                    <th>Checkpoint Name</th>
                                    <th>Zone Type</th>
                                    <th>QR Token</th>
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
                                        <td class="fw-semibold text-dark">
                                            <?= e($loc['location_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary text-capitalize">
                                                <?= e(str_replace('_', ' ', $loc['location_type'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code class="text-dark bg-light px-2 py-1 rounded small border">
                                                <?= e($loc['qr_code_identifier']) ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php if ($loc['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="<?= url('/admin/locations/' . $loc['id'] . '/toggle-status') ?>" method="POST" class="d-inline" onsubmit="return confirm('Toggle checkpoint status?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm <?= $loc['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="Toggle Status">
                                                    <?= $loc['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 2: Assigned Facility Supervisors -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-badge text-primary me-2 fs-5"></i>
                    <h5 class="card-title fw-bold text-dark mb-0">Assigned Facility Supervisors (<?= count($assignments) ?>)</h5>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignSupervisorModal">
                    <i class="bi bi-person-plus me-1"></i> Assign Supervisor
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-person-x text-muted fs-2 mb-2 d-block"></i>
                        <h6 class="fw-bold mb-1">No Dedicated Supervisors Assigned</h6>
                        <p class="text-muted small mb-2">Supervisors assigned to the parent floor or building also inherit inspection rights.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignSupervisorModal">
                            Assign Specific Supervisor
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4">Supervisor</th>
                                    <th>Effective From</th>
                                    <th>Effective To</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $sa): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= e($sa['supervisor_name']) ?></div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <?= e($sa['employee_code']) ?> &bull; <?= e($sa['supervisor_email']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= e(date('d M Y', strtotime($sa['effective_from']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($sa['effective_to'])): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <?= e(date('d M Y', strtotime($sa['effective_to']))) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success">Indefinite</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="<?= url('/admin/supervisor-assignments/' . $sa['id'] . '/revoke') ?>" method="POST" onsubmit="return confirm('Revoke this supervisor allocation?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Revoke
                                                </button>
                                            </form>
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

<!-- Modal: Edit Ward -->
<div class="modal fade" id="editWardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/wards/' . $ward['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Edit Ward Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ward Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="ward_code" value="<?= e($ward['ward_code']) ?>" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ward Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ward_name" value="<?= e($ward['ward_name']) ?>" required maxlength="150">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Specialty / Category</label>
                            <select class="form-select" name="ward_type">
                                <option value="general" <?= $ward['ward_type'] === 'general' ? 'selected' : '' ?>>General Ward</option>
                                <option value="icu" <?= $ward['ward_type'] === 'icu' ? 'selected' : '' ?>>ICU</option>
                                <option value="emergency" <?= $ward['ward_type'] === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                                <option value="pediatric" <?= $ward['ward_type'] === 'pediatric' ? 'selected' : '' ?>>Pediatric</option>
                                <option value="maternity" <?= $ward['ward_type'] === 'maternity' ? 'selected' : '' ?>>Maternity</option>
                                <option value="surgical" <?= $ward['ward_type'] === 'surgical' ? 'selected' : '' ?>>Surgical</option>
                                <option value="opd" <?= $ward['ward_type'] === 'opd' ? 'selected' : '' ?>>OPD</option>
                                <option value="radiology" <?= $ward['ward_type'] === 'radiology' ? 'selected' : '' ?>>Radiology</option>
                                <option value="lab" <?= $ward['ward_type'] === 'lab' ? 'selected' : '' ?>>Laboratory</option>
                                <option value="administrative" <?= $ward['ward_type'] === 'administrative' ? 'selected' : '' ?>>Administrative</option>
                                <option value="other" <?= $ward['ward_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Bed Capacity</label>
                            <input type="number" class="form-control" name="bed_capacity" value="<?= (int)$ward['bed_capacity'] ?>" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Ward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Checkpoint Location -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/locations') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="ward_id" value="<?= $ward['id'] ?>">
                <input type="hidden" name="return_to_ward" value="1">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pin-map me-2"></i>Add Inspection Checkpoint
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Checkpoint Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="location_code" required placeholder="e.g. ICU-WASH-01, BED-05" maxlength="50">
                        <div class="form-text">Unique uppercase identifier for this checkpoint.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Checkpoint Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="location_name" required placeholder="e.g. Patient Toilet Block, Hand Hygiene Sink" maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Checkpoint Category</label>
                        <select class="form-select" name="location_type">
                            <option value="washroom">Washroom / Restroom Block</option>
                            <option value="patient_bed">Patient Bed / Cubicle</option>
                            <option value="nursing_station">Nursing Station &amp; Counter</option>
                            <option value="corridor">Corridor &amp; Walkway</option>
                            <option value="doctor_cabin">Doctor Consultation Cabin</option>
                            <option value="waste_disposal">Biomedical Waste Disposal Station</option>
                            <option value="storage">Medicine &amp; Linen Storage</option>
                            <option value="emergency_exit">Emergency Fire Exit</option>
                            <option value="general">General Area Checkpoint</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Checkpoint</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Assign Supervisor -->
<div class="modal fade" id="assignSupervisorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/supervisor-assignments') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="building_id" value="<?= $ward['building_id'] ?>">
                <input type="hidden" name="floor_id" value="<?= $ward['floor_id'] ?>">
                <input type="hidden" name="ward_id" value="<?= $ward['id'] ?>">
                <input type="hidden" name="return_url" value="/admin/wards/<?= $ward['id'] ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-check me-2"></i>Assign Supervisor to <?= e($ward['ward_name']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Supervisor Personnel <span class="text-danger">*</span></label>
                        <select class="form-select" name="user_id" required>
                            <option value="">-- Choose Supervisor --</option>
                            <?php foreach ($supervisors as $sup): ?>
                                <option value="<?= $sup['id'] ?>">
                                    <?= e($sup['full_name']) ?> (<?= e($sup['employee_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="effective_from" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective To</label>
                            <input type="date" class="form-control" name="effective_to" placeholder="Leave empty for indefinite">
                            <div class="form-text">Optional end date.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>
