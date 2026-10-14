<?php
/** @var array $buildings */
/** @var array $floors */
/** @var array $wards */
/** @var array $supervisors */
/** @var array $categories */
/** @var array $priorities */
/** @var array $prefill */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb & Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/complaints') ?>" class="text-decoration-none">Complaints</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Lodge New Ticket</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-navy-dark">Lodge Hospital Facility Complaint</h4>
            <p class="text-muted small mb-0 mt-1">Report maintenance, hygiene, or safety issues with automated SLA tracking.</p>
        </div>
        <a href="<?= url('/complaints') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Complaints
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>Complaint Information</h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <form action="<?= url('/complaints/store') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <?php if (!empty($prefill['inspection_id'])): ?>
                            <input type="hidden" name="inspection_id" value="<?= e($prefill['inspection_id']) ?>">
                        <?php endif; ?>
                        <?php if (!empty($prefill['inspection_response_id'])): ?>
                            <input type="hidden" name="inspection_response_id" value="<?= e($prefill['inspection_response_id']) ?>">
                        <?php endif; ?>

                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Issue Summary / Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Water leakage from ceiling pipe near Bed 4" value="<?= e($prefill['title']) ?>" required>
                        </div>

                        <!-- Category & Priority Grid -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select rounded-3" required>
                                    <?php foreach ($categories as $catKey => $catLabel): ?>
                                        <option value="<?= e($catKey) ?>" <?= $prefill['category'] === $catKey ? 'selected' : '' ?>><?= e($catLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select rounded-3" required>
                                    <option value="critical" <?= $prefill['priority'] === 'critical' ? 'selected' : '' ?>>Critical (Response: 1h, Resolution: 2-4h)</option>
                                    <option value="high" <?= $prefill['priority'] === 'high' ? 'selected' : '' ?>>High (Response: 2h, Resolution: 6-8h)</option>
                                    <option value="medium" <?= $prefill['priority'] === 'medium' ? 'selected' : '' ?>>Medium (Response: 4h, Resolution: 24h)</option>
                                    <option value="low" <?= $prefill['priority'] === 'low' ? 'selected' : '' ?>>Low (Response: 12h, Resolution: 48h)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Location Grid -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Building <span class="text-danger">*</span></label>
                                <select name="building_id" class="form-select rounded-3" required>
                                    <option value="">Select Building...</option>
                                    <?php foreach ($buildings as $b): ?>
                                        <option value="<?= $b['id'] ?>" <?= ((string)$prefill['building_id'] === (string)$b['id']) ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Floor <span class="text-danger">*</span></label>
                                <select name="floor_id" class="form-select rounded-3" required>
                                    <option value="">Select Floor...</option>
                                    <?php foreach ($floors as $f): ?>
                                        <option value="<?= $f['id'] ?>" <?= ((string)$prefill['floor_id'] === (string)$f['id']) ? 'selected' : '' ?>><?= e($f['floor_name']) ?> (Floor <?= $f['floor_number'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Ward <span class="text-danger">*</span></label>
                                <select name="ward_id" class="form-select rounded-3" required>
                                    <option value="">Select Ward...</option>
                                    <?php foreach ($wards as $w): ?>
                                        <option value="<?= $w['id'] ?>" <?= ((string)$prefill['ward_id'] === (string)$w['id']) ? 'selected' : '' ?>><?= e($w['ward_name']) ?> (<?= e($w['ward_code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Initial Assignee -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Assign Directly To (Optional)</label>
                            <select name="assigned_to_user_id" class="form-select rounded-3">
                                <option value="">Leave Unassigned (Open Queue)</option>
                                <?php foreach ($supervisors as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= ucfirst($s['role']) ?> - <?= e($s['designation'] ?? 'Staff') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Detailed Description & Observations <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4" class="form-control rounded-3" placeholder="Provide complete clinical or physical details, exact location within ward, safety hazards..." required></textarea>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted">Attach Photo / Document Evidence (Optional)</label>
                            <input type="file" name="evidence" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            <div class="form-text small text-muted">
                                Allowed formats: JPG, PNG, WEBP, PDF (Max 5 MB). Stored securely outside public web root with SHA-256 integrity hashing.
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('/complaints') ?>" class="btn btn-light rounded-3 px-4">Cancel</a>
                            <button type="submit" class="btn btn-danger rounded-3 px-4 shadow-sm">
                                <i class="bi bi-send-fill me-1"></i> Lodge Complaint Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Helper Guide -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-shield-check me-2 text-primary"></i>SLA Standards Guide</h6>
                </div>
                <div class="card-body p-4 pt-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-2">
                            <span class="badge bg-danger mt-1">Critical</span>
                            <div class="small">
                                <strong>Immediate Patient Risk / Hazard</strong><br>
                                Response within 1 hour; resolution within 2 to 4 hours. Automated escalation alerts triggered upon breach.
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <span class="badge bg-warning text-dark mt-1">High</span>
                            <div class="small">
                                <strong>Severe Equipment / Drainage Failure</strong><br>
                                Response within 2 hours; resolution within 6 to 8 hours.
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <span class="badge bg-primary mt-1">Medium</span>
                            <div class="small">
                                <strong>Standard Operational Grievance</strong><br>
                                Response within 4 hours; resolution within 24 hours.
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <span class="badge bg-secondary mt-1">Low</span>
                            <div class="small">
                                <strong>Routine Minor Cosmetic Repair</strong><br>
                                Response within 12 hours; resolution within 48 hours.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
