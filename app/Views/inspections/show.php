<?php
/** @var array $inspection */
$score = (float)($inspection['score_percentage'] ?? 0.0);
$scoreBadge = ($score >= 85) ? 'bg-success' : (($score >= 60) ? 'bg-warning text-dark' : 'bg-danger');
?>
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/schedules') ?>" class="text-decoration-none">Schedules</a></li>
                    <li class="breadcrumb-item active"><?= e($inspection['inspection_number']) ?></li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-navy-dark"><?= e($inspection['template_name']) ?></h4>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace small px-2 py-1">
                    v<?= e((string)$inspection['version_number']) ?> Snapshot
                </span>
            </div>
            <div class="small text-muted mt-1 font-monospace">
                Reference ID: <?= e($inspection['inspection_number']) ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-printer me-1"></i> Print Report
            </button>
            <a href="<?= url('/schedules') ?>" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Return to Schedules
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Compliance Score</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <h3 class="fw-bold mb-0 text-navy-dark"><?= number_format($score, 1) ?>%</h3>
                    <span class="badge <?= $scoreBadge ?> small px-2 py-1">
                        <?= $score >= 85 ? 'Compliant' : ($score >= 60 ? 'Conditional' : 'Non-Compliant') ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Checkpoints Evaluated</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)$inspection['total_items'] ?></h3>
                    <span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i><?= (int)$inspection['passed_items'] ?> Passed</span>
                    <span class="text-danger small fw-semibold ms-1"><i class="bi bi-x-circle me-1"></i><?= (int)$inspection['failed_items'] ?> Failed</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Critical Safety Issues</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <h3 class="fw-bold mb-0 <?= (int)$inspection['critical_issues_count'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= (int)$inspection['critical_issues_count'] ?>
                    </h3>
                    <span class="badge <?= (int)$inspection['critical_issues_count'] > 0 ? 'bg-danger text-white' : 'bg-success bg-opacity-15 text-success border border-success border-opacity-25' ?> small px-2 py-1">
                        <?= (int)$inspection['critical_issues_count'] > 0 ? 'Immediate Action' : 'Zero Hazards' ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Inspector & Facility</div>
                <div class="fw-bold text-dark text-truncate mt-1"><?= e($inspection['inspector_name']) ?></div>
                <div class="small text-muted text-truncate"><i class="bi bi-hospital me-1"></i><?= e($inspection['ward_name']) ?> (<?= e($inspection['building_name']) ?>)</div>
            </div>
        </div>
    </div>

    <!-- Responses Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom border-light px-4 py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-navy-dark"><i class="bi bi-card-checklist me-2 text-primary"></i>Checklist Responses (Historical Snapshot v<?= e((string)$inspection['version_number']) ?>)</h6>
            <span class="small text-muted font-monospace">Submitted at: <?= e(date('d M Y, h:i A', strtotime($inspection['completed_at'] ?? $inspection['created_at']))) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4" style="width: 40px;">#</th>
                        <th>Question / Checkpoint (Snapshot)</th>
                        <th>Submitted Answer</th>
                        <th>Compliance</th>
                        <th>Severity</th>
                        <th class="pe-4">Supervisor Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($inspection['responses'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No responses recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inspection['responses'] as $idx => $r): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($r['item_label_snapshot']) ?></div>
                                </td>
                                <td>
                                    <span class="fw-bold font-monospace <?= strtolower($r['response_value']) === 'fail' ? 'text-danger' : (strtolower($r['response_value']) === 'pass' ? 'text-success' : 'text-dark') ?>">
                                        <?= e($r['response_value']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($r['is_compliant'])): ?>
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 small px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Compliant
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 small px-2 py-1">
                                            <i class="bi bi-x-circle me-1"></i>Non-Compliant
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['is_critical_failure'])): ?>
                                        <span class="badge bg-danger text-white small px-2 py-1">CRITICAL FAILURE</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border small px-2 py-1"><?= e(ucfirst($r['severity'] ?? 'medium')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-muted small">
                                    <?= !empty($r['remarks']) ? e($r['remarks']) : '<span class="text-muted opacity-50">—</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notes Card if present -->
    <?php if (!empty($inspection['notes'])): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light">
            <h6 class="fw-bold mb-2 text-navy-dark"><i class="bi bi-chat-left-text me-2 text-primary"></i>Overall Inspection Notes</h6>
            <p class="small text-muted mb-0"><?= nl2br(e($inspection['notes'])) ?></p>
        </div>
    <?php endif; ?>
</div>
