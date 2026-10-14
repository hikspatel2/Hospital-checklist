<?php
/** @var array $schedule */
/** @var array|null $inspection */
/** @var array $version */
/** @var array $items */
/** @var array $existingResponses */
/** @var array $severities */
?>
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/inspections/my') ?>" class="text-decoration-none">My Inspections</a></li>
                    <li class="breadcrumb-item active"><?= e($inspection['inspection_number'] ?? 'New Inspection') ?></li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-navy-dark"><?= e($version['template_name']) ?></h4>
            <div class="small text-muted mt-1">
                <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace"><?= e($version['template_code']) ?> (v<?= e((string)$version['version_number']) ?>)</span>
                <span class="ms-2"><i class="bi bi-hospital text-primary me-1"></i>Ward: <?= e($schedule['ward_name'] ?? 'General') ?></span>
                <?php if (!empty($schedule['location_name'])): ?>
                    <span class="ms-2"><i class="bi bi-geo-alt text-primary me-1"></i>Location: <?= e($schedule['location_name']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <a href="<?= url('/inspections/my') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-x-circle me-1"></i> Cancel & Return
            </a>
        </div>
    </div>

    <!-- Inspection Execution Form -->
    <form method="POST" action="<?= url("/inspections/execute/{$schedule['id']}/submit") ?>" id="inspectionForm">
        <?= csrf_field() ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Checklist Questions List -->
                <?php if (empty($items)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                        <i class="bi bi-exclamation-circle fs-1 mb-2 text-warning"></i>
                        <h5>No Checkpoints in this Version</h5>
                        <p class="small mb-0">This checklist template version does not contain any questions. Please contact an administrator.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $idx => $item): ?>
                        <?php
                            $itemId = (int)$item['id'];
                            $currentVal = $existingResponses[$itemId]['value'] ?? '';
                            $currentRemark = $existingResponses[$itemId]['remarks'] ?? '';
                            $isRequired = (int)($item['is_required'] ?? 1);
                            $sev = $item['severity'] ?? 'medium';
                        ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-3 p-4 inspection-question-card <?= $sev === 'critical' ? 'border-start border-danger border-4' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border fw-bold"><?= $idx + 1 ?></span>
                                    <h6 class="fw-bold mb-0 text-navy-dark"><?= e($item['item_label']) ?></h6>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($isRequired): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 small px-2 py-1">Required</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small px-2 py-1">Optional</span>
                                    <?php endif; ?>

                                    <?php if ($sev === 'critical'): ?>
                                        <span class="badge bg-danger text-white small px-2 py-1">Critical Safety</span>
                                    <?php elseif ($sev === 'high'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 small px-2 py-1">High Risk</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($item['description'])): ?>
                                <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i><?= e($item['description']) ?></p>
                            <?php endif; ?>

                            <!-- Answer Input Controls by Type -->
                            <div class="my-3">
                                <?php if ($item['item_type'] === 'pass_fail'): ?>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="responses[<?= $itemId ?>]" id="pass_<?= $itemId ?>" value="Pass" <?= $currentVal === 'Pass' ? 'checked' : '' ?> <?= $isRequired ? 'required' : '' ?>>
                                        <label class="btn btn-outline-success py-2 fw-semibold" for="pass_<?= $itemId ?>">
                                            <i class="bi bi-check-circle me-1"></i> PASS
                                        </label>

                                        <input type="radio" class="btn-check" name="responses[<?= $itemId ?>]" id="fail_<?= $itemId ?>" value="Fail" <?= $currentVal === 'Fail' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger py-2 fw-semibold" for="fail_<?= $itemId ?>">
                                            <i class="bi bi-x-circle me-1"></i> FAIL
                                        </label>
                                    </div>

                                <?php elseif ($item['item_type'] === 'yes_no_na'): ?>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="responses[<?= $itemId ?>]" id="yes_<?= $itemId ?>" value="Yes" <?= $currentVal === 'Yes' ? 'checked' : '' ?> <?= $isRequired ? 'required' : '' ?>>
                                        <label class="btn btn-outline-primary py-2 fw-semibold" for="yes_<?= $itemId ?>">YES</label>

                                        <input type="radio" class="btn-check" name="responses[<?= $itemId ?>]" id="no_<?= $itemId ?>" value="No" <?= $currentVal === 'No' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger py-2 fw-semibold" for="no_<?= $itemId ?>">NO</label>

                                        <input type="radio" class="btn-check" name="responses[<?= $itemId ?>]" id="na_<?= $itemId ?>" value="N/A" <?= $currentVal === 'N/A' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary py-2 fw-semibold" for="na_<?= $itemId ?>">N/A</label>
                                    </div>

                                <?php elseif ($item['item_type'] === 'numeric'): ?>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-123"></i></span>
                                        <input type="number" step="any" name="responses[<?= $itemId ?>]" class="form-control" placeholder="Enter numeric measurement or quantity..." value="<?= e($currentVal) ?>" <?= $isRequired ? 'required' : '' ?>>
                                    </div>

                                <?php else: ?>
                                    <textarea name="responses[<?= $itemId ?>]" class="form-control form-control-sm" rows="2" placeholder="Enter supervisor observations or comments..." <?= $isRequired ? 'required' : '' ?>><?= e($currentVal) ?></textarea>
                                <?php endif; ?>
                            </div>

                            <!-- Remarks & Evidence Callout -->
                            <div class="row g-2 align-items-center mt-2">
                                <div class="col-md-8">
                                    <input type="text" name="remarks[<?= $itemId ?>]" class="form-control form-control-sm bg-light border-0" placeholder="Optional notes/remarks on this checkpoint..." value="<?= e($currentRemark) ?>">
                                </div>
                                <?php if (!empty($item['requires_evidence'])): ?>
                                    <div class="col-md-4 text-md-end">
                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25 small px-2 py-1">
                                            <i class="bi bi-camera me-1"></i>Evidence Required
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar: Overview & Submit -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px;">
                    <div class="card-header bg-white border-bottom border-light px-4 py-3">
                        <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-clipboard-check me-2 text-primary"></i>Submission Summary</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Overall Inspection Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="4" placeholder="Enter any general observations, environmental conditions, or administrative comments..."></textarea>
                        </div>

                        <div class="alert alert-light border small text-muted mb-4">
                            <i class="bi bi-shield-check me-1 text-success"></i>
                            <strong>Submission Guarantee:</strong> This inspection will be recorded in an immutable database transaction with timestamps and historical response snapshots.
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 shadow-sm fw-bold">
                            <i class="bi bi-send-check me-1"></i> Finalize & Submit Inspection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
