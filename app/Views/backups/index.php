<?php
/** @var array $diskBackups */
/** @var array $dbLogs */
/** @var array $restoreLogs */
/** @var string|null $latestBackupTime */
/** @var bool $isStale */
/** @var string $totalStorage */
/** @var int $totalCount */
/** @var string|null $lastRestoreTest */
/** @var array $retention */
?>
<div class="container-fluid px-0">
    <!-- Header Title Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Phase 10</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Backup, Retention & Disaster Recovery</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Automated clinical database snapshots, AES-256 authenticated encryption, SHA-256 integrity verification, and safe disaster recovery.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="<?= url('/backups/cleanup') ?>" method="POST" class="d-inline" onsubmit="return confirm('Execute Grandfather-Father-Son retention cleanup on stale archives?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-3">
                    <i class="bi bi-recycle me-1"></i> Run Retention Cleanup
                </button>
            </form>
            <button type="button" class="btn btn-navy btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                <i class="bi bi-cloud-arrow-up me-1"></i> Create Backup Now
            </button>
        </div>
    </div>

    <!-- Health & Status Alerts -->
    <?php if (empty($diskBackups)): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-shield-slash-fill fs-3 me-3 text-danger"></i>
            <div>
                <strong class="d-block text-danger">CRITICAL SECURITY WARNING: No Backups Found on Disk!</strong>
                <span class="small">The clinical facility currently has zero backup snapshots. Generate a baseline backup immediately to ensure data recoverability.</span>
            </div>
        </div>
    <?php elseif ($isStale): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
            <div>
                <strong class="d-block text-warning">STALE BACKUP NOTICE: Last Backup is Over 24 Hours Old</strong>
                <span class="small">The last recorded backup was generated on <?= date('d M Y, h:i A', strtotime($latestBackupTime)) ?>. Check your Hostinger cron job or trigger a manual backup below.</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Latest Backup Status -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">LATEST BACKUP</span>
                        <span class="badge <?= empty($diskBackups) ? 'bg-danger' : ($isStale ? 'bg-warning' : 'bg-success') ?> bg-opacity-10 <?= empty($diskBackups) ? 'text-danger' : ($isStale ? 'text-warning' : 'text-success') ?> rounded-pill">
                            <?= empty($diskBackups) ? 'Missing' : ($isStale ? 'Stale' : 'Up to Date') ?>
                        </span>
                    </div>
                    <div class="h6 fw-bold mb-1 text-navy text-truncate">
                        <?= $latestBackupTime ? date('d M Y, h:i A', strtotime($latestBackupTime)) : 'None Recorded' ?>
                    </div>
                    <div class="text-muted small">
                        <?= !empty($diskBackups) ? e($diskBackups[0]['filename']) : 'No files found' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage Consumed -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">STORAGE CONSUMED</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= $totalCount ?> Archives</span>
                    </div>
                    <div class="h5 fw-bold mb-1 text-navy"><?= e($totalStorage) ?></div>
                    <div class="text-muted small">
                        Protected Storage: <code>storage/backups</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Verification -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">RESTORE TEST</span>
                        <span class="badge <?= $lastRestoreTest ? 'bg-success' : 'bg-secondary' ?> bg-opacity-10 <?= $lastRestoreTest ? 'text-success' : 'text-secondary' ?> rounded-pill">
                            <?= $lastRestoreTest ? 'Verified' : 'Pending Test' ?>
                        </span>
                    </div>
                    <div class="h6 fw-bold mb-1 text-navy">
                        <?= $lastRestoreTest ? date('d M Y, h:i A', strtotime($lastRestoreTest)) : 'Never Executed' ?>
                    </div>
                    <div class="text-muted small">
                        Dry-run schema & record test
                    </div>
                </div>
            </div>
        </div>

        <!-- Retention Policy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">RETENTION POLICY</span>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill">GFS Active</span>
                    </div>
                    <div class="h6 fw-bold mb-1 text-navy">
                        <?= (int)$retention['daily_days'] ?>d Daily / <?= (int)$retention['weekly_weeks'] ?>w Weekly / <?= (int)$retention['monthly_months'] ?>m Monthly
                    </div>
                    <div class="text-muted small">
                        Safeguard: Minimum 1 backup locked
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Backup Archives on Disk -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-navy mb-0">
                <i class="bi bi-hdd-network me-1 text-primary"></i> Available Backup Archives on Disk (Restricted Storage)
            </h6>
            <span class="badge bg-light text-muted border"><?= count($diskBackups) ?> files</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-muted">
                    <tr>
                        <th>Archive Filename</th>
                        <th>Type</th>
                        <th>Created (IST)</th>
                        <th>Size</th>
                        <th>Encryption</th>
                        <th>SHA-256 Checksum</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($diskBackups)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-cloud-slash fs-3 d-block mb-2 text-secondary"></i>
                                No backup archives currently stored on disk. Click <strong>Create Backup Now</strong> above.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($diskBackups as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?= $item['is_encrypted'] ? 'bi-shield-lock-fill text-success' : 'bi-file-earmark-code text-primary' ?> fs-5 me-2"></i>
                                        <div>
                                            <code class="fw-bold text-navy"><?= e($item['filename']) ?></code>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border">
                                        <?= e($item['type']) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y, h:i A', $item['mtime']) ?></td>
                                <td><span class="badge bg-light text-navy border"><?= e($item['size_formatted']) ?></span></td>
                                <td>
                                    <?php if ($item['is_encrypted']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="bi bi-lock-fill me-1"></i> AES-256-CBC
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Standard</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted font-monospace" style="font-size: 0.72rem;">
                                        SHA-256 Signature Active
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Verify Checksum -->
                                        <form action="<?= url('/backups/' . urlencode($item['filename']) . '/verify') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-secondary" title="Verify SHA-256 Checksum">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                        </form>

                                        <!-- Dry-Run Test -->
                                        <form action="<?= url('/backups/' . urlencode($item['filename']) . '/test-restore') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-info" title="Dry-Run Restore Test">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
                                        </form>

                                        <!-- Download -->
                                        <a href="<?= url('/backups/' . urlencode($item['filename']) . '/download') ?>" class="btn btn-outline-primary" title="Download Archive Stream">
                                            <i class="bi bi-download"></i>
                                        </a>

                                        <!-- Restore Modal Trigger -->
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#restoreModal" data-filename="<?= e($item['filename']) ?>" title="Disaster Restoration">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dual Ledgers: Backup Jobs History & Restore Audits -->
    <div class="row g-4 mb-4">
        <!-- Backup History Ledger -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0">
                        <i class="bi bi-clock-history me-1 text-primary"></i> Execution History (Database Ledger)
                    </h6>
                    <span class="badge bg-light text-muted border">Latest 15</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Timestamp</th>
                                <th>File / Trigger</th>
                                <th>Tables / Rows</th>
                                <th>Status</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dbLogs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No database execution logs recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dbLogs as $log): ?>
                                    <tr>
                                        <td><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                        <td>
                                            <div class="fw-semibold text-navy text-truncate" style="max-width: 220px;" title="<?= e($log['file_name']) ?>">
                                                <?= e($log['file_name']) ?>
                                            </div>
                                            <span class="text-muted" style="font-size: 0.7rem;">Trigger: <?= e($log['triggered_by']) ?></span>
                                        </td>
                                        <td>
                                            <?= (int)($log['tables_count'] ?? 0) ?> tbls / <?= number_format((int)($log['records_count'] ?? 0)) ?> rows
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'success' || $log['status'] === 'verified'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    <?= e(ucfirst($log['status'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                    Failed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($log['execution_duration_sec'] ?? '0.00') ?>s</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Disaster Recovery Audits -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0">
                        <i class="bi bi-shield-exclamation me-1 text-danger"></i> Disaster Restorations Ledger
                    </h6>
                    <span class="badge bg-light text-muted border">Immutable</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Pre-Restore Snapshot</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($restoreLogs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle fs-4 d-block mb-1 text-success"></i>
                                        Zero disaster restorations executed. Normal operational state.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($restoreLogs as $rLog): ?>
                                    <tr>
                                        <td><?= date('d M Y, h:i A', strtotime($rLog['created_at'])) ?></td>
                                        <td>
                                            <span class="fw-semibold text-navy"><?= e($rLog['user_name'] ?? 'Admin') ?></span>
                                        </td>
                                        <td>
                                            <code class="text-muted" style="font-size: 0.7rem;"><?= e($rLog['pre_restore_backup_file'] ?? 'snapshot.sql') ?></code>
                                        </td>
                                        <td>
                                            <span class="badge <?= $rLog['status'] === 'success' ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= $rLog['status'] === 'success' ? 'text-success' : 'text-danger' ?>">
                                                <?= e(ucfirst($rLog['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Create Backup Now -->
<div class="modal fade" id="createBackupModal" tabindex="-1" aria-labelledby="createBackupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= url('/backups/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-navy" id="createBackupModalLabel">
                        <i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Generate Hospital Backup
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        Select backup scope and encryption options. The backup will be atomically generated in restricted storage (<code>storage/backups/</code>) outside the public web root.
                    </p>

                    <!-- Scope Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-navy small">Backup Scope <span class="text-danger">*</span></label>
                        <div class="form-check p-3 border rounded-3 bg-light mb-2">
                            <input class="form-check-input" type="radio" name="archive_type" id="scope_db" value="database_only" checked>
                            <label class="form-check-label fw-bold text-navy" for="scope_db">
                                Database Only (Recommended Daily)
                            </label>
                            <div class="text-muted small">All clinical tables, users, inspections, complaints, audit logs, and settings.</div>
                        </div>
                        <div class="form-check p-3 border rounded-3 bg-light">
                            <input class="form-check-input" type="radio" name="archive_type" id="scope_full" value="full_with_evidence">
                            <label class="form-check-label fw-bold text-navy" for="scope_full">
                                Full System & Evidence Package
                            </label>
                            <div class="text-muted small">Database dump + all photo inspection evidence and SVG floor plans.</div>
                        </div>
                    </div>

                    <!-- Encryption Toggle -->
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="form-check form-switch p-0">
                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="encryptSwitch" name="encrypt" value="1" checked>
                            <label class="form-check-label fw-semibold text-navy small" for="encryptSwitch">
                                Enable Authenticated AES-256-CBC Encryption
                            </label>
                            <div class="text-muted small mt-1">Protects sensitive healthcare data with authenticated HMAC-SHA256 signatures.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy px-4 rounded-3 fw-semibold shadow-sm">
                        <i class="bi bi-play-circle me-1"></i> Start Backup Generation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Restore Disaster Recovery with Admin Re-Authentication -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="restoreForm" action="" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-danger bg-opacity-10 border-bottom border-danger border-opacity-25">
                    <h5 class="modal-title fw-bold text-danger" id="restoreModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Disaster Restoration
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger py-2 px-3 small border-0 mb-3">
                        <strong>CRITICAL SAFEGUARD:</strong> Restoring a backup replaces existing database records with the snapshot archive contents.
                    </div>

                    <p class="text-secondary small mb-2">
                        Target Archive: <strong id="restoreTargetFilename" class="text-navy font-monospace"></strong>
                    </p>

                    <div class="p-2 mb-3 bg-light rounded border small text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        A verified <strong>pre-restore safety backup</strong> will be automatically generated immediately before the restoration executes.
                    </div>

                    <!-- Re-authentication confirmation -->
                    <div class="mb-3">
                        <label for="admin_password" class="form-label fw-semibold text-navy small">
                            Administrator Re-Authentication Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="admin_password" id="admin_password" class="form-control" placeholder="Enter your login password to authorize" required>
                        <div class="form-text">Single-click accidental restore is strictly prohibited. You must confirm your account password.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 fw-semibold shadow-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Authorize & Restore Database
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const restoreModal = document.getElementById('restoreModal');
    if (restoreModal) {
        restoreModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const filename = button.getAttribute('data-filename');
            document.getElementById('restoreTargetFilename').textContent = filename;
            const form = document.getElementById('restoreForm');
            form.action = '<?= url('/backups') ?>/' + encodeURIComponent(filename) + '/restore';
        });
    }
});
</script>
