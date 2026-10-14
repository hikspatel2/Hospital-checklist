<?php
/** @var array $complaint */
/** @var array $timeline */
/** @var array $evidence */
/** @var array $supervisors */
/** @var array $statuses */
/** @var array $priorities */
/** @var array $categories */

$prioInfo = $priorities[$complaint['priority']] ?? ['label' => ucfirst($complaint['priority']), 'badge' => 'bg-secondary'];
$statInfo = $statuses[$complaint['status']] ?? ['label' => ucfirst($complaint['status']), 'badge' => 'bg-secondary'];
$eval = $complaint['sla_evaluation'] ?? [];
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/complaints') ?>" class="text-decoration-none">Complaints</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($complaint['ticket_number']) ?></li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="fw-bold mb-0 text-navy-dark"><?= e($complaint['ticket_number']) ?></h4>
                <span class="badge <?= $statInfo['badge'] ?> px-2 py-1 fs-6">
                    <i class="bi <?= $statInfo['icon'] ?> me-1"></i><?= e($statInfo['label']) ?>
                </span>
                <span class="badge <?= $prioInfo['badge'] ?> px-2 py-1">
                    <i class="bi <?= $prioInfo['icon'] ?> me-1"></i><?= e($prioInfo['label']) ?> Priority
                </span>
                <span class="badge bg-light text-dark border">
                    <?= e($categories[$complaint['category']] ?? ucfirst($complaint['category'])) ?>
                </span>
            </div>
            <h5 class="fw-semibold text-secondary mt-2 mb-0"><?= e($complaint['title']) ?></h5>
        </div>

        <!-- Action Buttons Group -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#commentModal">
                <i class="bi bi-chat-left-text me-1"></i> Add Note
            </button>

            <?php if (!in_array($complaint['status'], ['resolved', 'closed'], true)): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="bi bi-person-gear me-1"></i> Assign
                </button>
                <button type="button" class="btn btn-outline-dark btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#statusModal">
                    <i class="bi bi-arrow-repeat me-1"></i> Status
                </button>
                <?php if ($complaint['status'] !== 'on_hold'): ?>
                    <button type="button" class="btn btn-outline-warning text-dark btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#holdModal">
                        <i class="bi bi-pause-circle me-1"></i> Put On Hold
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-success btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#resolveModal">
                    <i class="bi bi-check2-circle me-1"></i> Resolve Ticket
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-outline-warning text-dark btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#reopenModal">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reopen Ticket
                </button>
                <?php if ($complaint['status'] === 'resolved'): ?>
                    <form action="<?= url('/complaints/' . $complaint['id'] . '/close') ?>" method="POST" class="d-inline" onsubmit="return confirm('Officially close this complaint ticket?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-dark btn-sm rounded-3 shadow-sm">
                            <i class="bi bi-archive me-1"></i> Close
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (\App\Core\Auth::isAdmin()): ?>
                <?php if (empty($complaint['deleted_at'])): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Archive / Soft Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                <?php else: ?>
                    <form action="<?= url('/complaints/' . $complaint['id'] . '/restore') ?>" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-outline-success btn-sm rounded-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Soft Delete Notice Banner if archived -->
    <?php if (!empty($complaint['deleted_at'])): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-archive-fill fs-4"></i>
                <div>
                    <strong>This complaint record is soft-deleted and archived.</strong><br>
                    <span class="small">Deleted at <?= e($complaint['deleted_at']) ?>. Reason: <?= e($complaint['deletion_reason'] ?? 'Not specified') ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Column: Details, SLA Monitor & Timeline -->
        <div class="col-lg-8">
            <!-- SLA Compliance Monitor Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-stopwatch-fill me-2 text-danger"></i>SLA Compliance Monitor</h6>
                    <div><?= $complaint['sla_badge'] ?? '' ?></div>
                </div>
                <div class="card-body p-4 pt-1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-4 h-100">
                                <span class="small text-muted fw-semibold d-block">RESPONSE SLA</span>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">Target Deadline:</span>
                                        <span class="small fw-bold text-dark"><?= $complaint['response_due_at'] ? date('d M Y, H:i', strtotime($complaint['response_due_at'])) : 'N/A' ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="small text-muted">First Response:</span>
                                        <span class="small fw-semibold <?= $eval['is_response_breached'] ? 'text-danger' : 'text-success' ?>">
                                            <?= $complaint['first_responded_at'] ? date('d M Y, H:i', strtotime($complaint['first_responded_at'])) : 'Pending First Response' ?>
                                        </span>
                                    </div>
                                    <?php if ($eval['is_response_breached']): ?>
                                        <div class="badge bg-danger mt-2 w-100 text-start py-1">
                                            <i class="bi bi-exclamation-octagon me-1"></i>Response Target Breached
                                        </div>
                                    <?php else: ?>
                                        <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mt-2 w-100 text-start py-1">
                                            <i class="bi bi-check2 me-1"></i>Response SLA Compliant
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-4 h-100">
                                <span class="small text-muted fw-semibold d-block">RESOLUTION SLA</span>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">Target Deadline:</span>
                                        <span class="small fw-bold text-dark"><?= $complaint['resolution_due_at'] ? date('d M Y, H:i', strtotime($complaint['resolution_due_at'])) : 'N/A' ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="small text-muted">Resolved At:</span>
                                        <span class="small fw-semibold <?= $eval['is_resolution_breached'] ? 'text-danger' : 'text-success' ?>">
                                            <?= $complaint['resolved_at'] ? date('d M Y, H:i', strtotime($complaint['resolved_at'])) : 'Pending Resolution' ?>
                                        </span>
                                    </div>
                                    <?php if ($eval['is_resolution_breached']): ?>
                                        <div class="badge bg-danger mt-2 w-100 text-start py-1">
                                            <i class="bi bi-fire me-1"></i>Resolution Overdue by <?= $eval['resolution_overdue_hours'] ?> hrs
                                        </div>
                                    <?php else: ?>
                                        <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mt-2 w-100 text-start py-1">
                                            <i class="bi bi-check2 me-1"></i>Resolution SLA Compliant
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-card-text me-2 text-primary"></i>Grievance Description</h6>
                </div>
                <div class="card-body p-4 pt-1">
                    <p class="text-dark mb-0" style="white-space: pre-line;"><?= e($complaint['description']) ?></p>

                    <?php if (!empty($complaint['resolution_notes'])): ?>
                        <div class="mt-4 p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong class="text-success small">Resolution Notes (Resolved by <?= e($complaint['resolver_name'] ?? 'Supervisor') ?>):</strong>
                            </div>
                            <p class="text-dark small mb-0" style="white-space: pre-line;"><?= e($complaint['resolution_notes']) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($complaint['reopen_reason'])): ?>
                        <div class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                <strong class="text-dark small">Reopening Justification (Reopened by <?= e($complaint['reopener_name'] ?? 'Admin') ?>):</strong>
                            </div>
                            <p class="text-dark small mb-0"><?= e($complaint['reopen_reason']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chronological Timeline Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>Chronological Activity Timeline</h6>
                    <span class="badge bg-light text-muted border"><?= count($timeline) ?> Entries</span>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="timeline position-relative ps-4" style="border-left: 2px solid #e9ecef;">
                        <?php foreach ($timeline as $t): ?>
                            <?php 
                            $typeInfo = \App\Models\ComplaintUpdate::TYPES[$t['update_type']] ?? [
                                'label' => ucfirst($t['update_type']),
                                'icon'  => 'bi-circle',
                                'badge' => 'bg-secondary'
                            ];
                            ?>
                            <div class="position-relative mb-4 pb-2">
                                <!-- Marker Icon -->
                                <div class="position-absolute" style="left: -33px; top: 0px;">
                                    <span class="badge <?= $typeInfo['badge'] ?> rounded-circle p-2 shadow-sm">
                                        <i class="bi <?= $typeInfo['icon'] ?>"></i>
                                    </span>
                                </div>
                                <div class="card border-0 bg-light rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold small text-navy-dark"><?= e($t['user_name']) ?></span>
                                            <span class="badge bg-white text-muted border small"><?= ucfirst($t['user_role']) ?></span>
                                            <span class="badge <?= $typeInfo['badge'] ?> py-0 px-2 small"><?= $typeInfo['label'] ?></span>
                                        </div>
                                        <span class="text-muted small"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></span>
                                    </div>
                                    <p class="text-dark small mb-0 mt-2" style="white-space: pre-line;"><?= e($t['comment']) ?></p>

                                    <!-- Attached Evidence Link if associated with this update -->
                                    <?php if (!empty($t['evidence_id'])): ?>
                                        <div class="mt-2 pt-2 border-top">
                                            <a href="<?= url('/complaints/evidence/' . $t['evidence_id']) ?>" target="_blank" class="btn btn-white btn-sm border rounded-3 py-1 px-2 small text-decoration-none">
                                                <i class="bi bi-paperclip me-1 text-primary"></i>
                                                <?= e($t['original_filename']) ?> (<?= round($t['file_size_bytes'] / 1024, 1) ?> KB)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column: Location, Assignee & Evidence -->
        <div class="col-lg-4">
            <!-- Facility Location Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Facility Location</h6>
                </div>
                <div class="card-body p-4 pt-1">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Building:</span>
                            <span class="fw-semibold text-dark"><?= e($complaint['building_name']) ?></span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Floor:</span>
                            <span class="fw-semibold text-dark"><?= e($complaint['floor_name']) ?> (Floor <?= $complaint['floor_number'] ?>)</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Ward / Area:</span>
                            <span class="fw-semibold text-dark"><?= e($complaint['ward_name']) ?> (<?= e($complaint['ward_code']) ?>)</span>
                        </li>
                        <?php if (!empty($complaint['location_name'])): ?>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Location Checkpoint:</span>
                                <span class="fw-semibold text-dark"><?= e($complaint['location_name']) ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($complaint['inspection_reference'])): ?>
                            <li class="d-flex justify-content-between border-top pt-2 mt-1">
                                <span class="text-muted">Linked Inspection:</span>
                                <a href="<?= url('/inspections/' . $complaint['inspection_id']) ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= e($complaint['inspection_reference']) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Personnel & Ownership Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-people-fill me-2 text-primary"></i>Personnel & Assignment</h6>
                </div>
                <div class="card-body p-4 pt-1">
                    <div class="mb-3">
                        <span class="small text-muted d-block">Reported By</span>
                        <div class="fw-bold text-dark mt-1"><?= e($complaint['reporter_name']) ?></div>
                        <div class="small text-muted"><?= e($complaint['reporter_email']) ?></div>
                    </div>
                    <div class="border-top pt-3">
                        <span class="small text-muted d-block">Current Assignee</span>
                        <?php if (!empty($complaint['assignee_name'])): ?>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <div>
                                    <div class="fw-bold text-dark"><?= e($complaint['assignee_name']) ?></div>
                                    <div class="small text-muted"><?= e($complaint['assignee_email']) ?></div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 py-1" data-bs-toggle="modal" data-bs-target="#assignModal">
                                    Reassign
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <span class="badge bg-warning text-dark">Unassigned</span>
                                <button type="button" class="btn btn-primary btn-sm rounded-3 py-1" data-bs-toggle="modal" data-bs-target="#assignModal">
                                    Assign Now
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Evidence Attachments Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-paperclip me-2 text-dark"></i>Evidence Files</h6>
                    <span class="badge bg-light text-muted border"><?= count($evidence) ?></span>
                </div>
                <div class="card-body p-4 pt-1">
                    <?php if (empty($evidence)): ?>
                        <div class="text-center py-3 text-muted small">
                            <i class="bi bi-file-earmark-x fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            No evidence files attached.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($evidence as $ev): ?>
                                <div class="p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <i class="bi <?= str_contains($ev['mime_type'], 'pdf') ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark-image-fill text-primary' ?> fs-4"></i>
                                        <div class="text-truncate">
                                            <div class="small fw-bold text-dark text-truncate" title="<?= e($ev['original_filename']) ?>">
                                                <?= e($ev['original_filename']) ?>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                <?= round($ev['file_size_bytes'] / 1024, 1) ?> KB &bull; <?= e($ev['uploader_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="<?= url('/complaints/evidence/' . $ev['id'] . '?download=1') ?>" class="btn btn-outline-primary btn-sm rounded-3 py-1" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Assign / Reassign -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/complaints/' . $complaint['id'] . '/assign') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy-dark">Assign / Reassign Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Select Supervisor / Staff <span class="text-danger">*</span></label>
                        <select name="assigned_to_user_id" class="form-select rounded-3" required>
                            <option value="">Select Personnel...</option>
                            <?php foreach ($supervisors as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ((int)$complaint['assigned_to_user_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?> (<?= ucfirst($s['role']) ?> - <?= e($s['designation'] ?? 'Staff') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Assignment Instructions / Notes</label>
                        <textarea name="notes" rows="3" class="form-control rounded-3" placeholder="Provide actionable handover notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Update Status -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/complaints/' . $complaint['id'] . '/status') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy-dark">Update Ticket Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">New Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select rounded-3" required>
                            <option value="in_progress" <?= $complaint['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress (Work Dispatched)</option>
                            <option value="assigned" <?= $complaint['status'] === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                            <option value="on_hold" <?= $complaint['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold (Awaiting Parts/Approval)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Status Note / Progress Log <span class="text-danger">*</span></label>
                        <textarea name="comment" rows="3" class="form-control rounded-3" placeholder="Explain the current progress or reason for status transition..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy rounded-3 px-4">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Put On Hold -->
<div class="modal fade" id="holdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/complaints/' . $complaint['id'] . '/hold') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-warning text-dark"><i class="bi bi-pause-circle me-1"></i>Put Complaint On Hold</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Hold Reason & Expected Delay <span class="text-danger">*</span></label>
                        <textarea name="hold_reason" rows="3" class="form-control rounded-3" placeholder="e.g. Awaiting spare biomedical valve delivery from supplier..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-3 px-4">Place On Hold</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Resolve Ticket -->
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/complaints/' . $complaint['id'] . '/resolve') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-success"><i class="bi bi-check2-circle me-1"></i>Resolve Facility Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Corrective Action Taken & Resolution Notes <span class="text-danger">*</span></label>
                        <textarea name="resolution_notes" rows="4" class="form-control rounded-3" placeholder="Detail the repairs completed, sanitation actions, or replacements made..." required minlength="8"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Proof of Resolution Photo / Doc (Optional)</label>
                        <input type="file" name="resolution_evidence" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4">Confirm Resolution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Reopen Ticket -->
<div class="modal fade" id="reopenModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/complaints/' . $complaint['id'] . '/reopen') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-danger"><i class="bi bi-arrow-clockwise me-1"></i>Reopen Complaint Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small rounded-3 mb-3">
                        Reopening a resolved complaint resets its status to active queue and notifies facility leadership.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Justification / Recurrence Reason <span class="text-danger">*</span></label>
                        <textarea name="reopen_reason" rows="3" class="form-control rounded-3" placeholder="Explain why the issue is not satisfactorily resolved or has recurred..." required minlength="8"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4">Reopen Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Add Timeline Note -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/complaints/' . $complaint['id'] . '/comment') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy-dark">Post Timeline Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Note / Update Message <span class="text-danger">*</span></label>
                        <textarea name="comment" rows="3" class="form-control rounded-3" placeholder="Write operational remarks or updates..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Attach File (Optional)</label>
                        <input type="file" name="evidence" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Post Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Soft Delete (Admin Only) -->
<?php if (\App\Core\Auth::isAdmin()): ?>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="<?= url('/complaints/' . $complaint['id'] . '/delete') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold text-danger"><i class="bi bi-trash3 me-1"></i>Archive / Soft Delete Complaint</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger small rounded-3 mb-3">
                            This complaint will be removed from active queues and marked as archived. Historical logs and inspection references remain preserved.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Reason for Deletion <span class="text-danger">*</span></label>
                            <input type="text" name="deletion_reason" class="form-control rounded-3" placeholder="e.g. Duplicate test submission" required minlength="5">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-3 px-4">Archive Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
