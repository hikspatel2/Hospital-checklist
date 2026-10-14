<?php
/**
 * QR Code Tokens Administration View
 * Manages opaque, revocable QR tokens, rotation, vector SVG badges, and print cards.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">QR Checkpoints</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">QR Code Checkpoint Administration</h1>
        <p class="text-muted small mb-0">Opaque, Revocable Cryptographic QR Tokens for Wards &amp; Checkpoints</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#libInstructionsModal">
            <i class="bi bi-info-circle me-1"></i> Library Info
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateQrModal">
            <i class="bi bi-plus-lg me-1"></i> Generate / Rotate QR
        </button>
    </div>
</div>

<!-- Architecture & Security Note -->
<div class="card border-0 shadow-sm bg-primary-subtle mb-4">
    <div class="card-body p-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-shield-lock-fill text-primary fs-4 me-3"></i>
            <div>
                <div class="fw-bold text-primary small">Cryptographic Opaque Token Guarantee</div>
                <div class="text-dark small" style="font-size: 0.8rem;">
                    Hospital QR codes contain <strong>opaque random tokens</strong> (e.g. <code>LG-QR-SEC-XXXXXXXX</code>) whose SHA-256 hashes are verified strictly against the server. No confidential patient data, supervisor credentials, or database IDs are ever embedded in physical QR codes. Tokens can be revoked or rotated instantly by hospital administration.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/qr-codes') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select class="form-select" name="token_type">
                    <option value="">All Checkpoint Types</option>
                    <option value="ward_checkin" <?= ($filters['token_type'] ?? '') === 'ward_checkin' ? 'selected' : '' ?>>Ward Check-in</option>
                    <option value="location_inspection" <?= ($filters['token_type'] ?? '') === 'location_inspection' ? 'selected' : '' ?>>Location Checkpoint</option>
                    <option value="supervisor_attendance" <?= ($filters['token_type'] ?? '') === 'supervisor_attendance' ? 'selected' : '' ?>>Supervisor Attendance</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="entity_type">
                    <option value="">All Target Entities</option>
                    <option value="ward" <?= ($filters['entity_type'] ?? '') === 'ward' ? 'selected' : '' ?>>Wards</option>
                    <option value="location" <?= ($filters['entity_type'] ?? '') === 'location' ? 'selected' : '' ?>>Inspection Locations</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="is_revoked">
                    <option value="">All Statuses</option>
                    <option value="0" <?= ($filters['is_revoked'] ?? '') === '0' ? 'selected' : '' ?>>Active Only</option>
                    <option value="1" <?= ($filters['is_revoked'] ?? '') === '1' ? 'selected' : '' ?>>Revoked / Rotated</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="<?= url('/qr-codes') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tokens Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($tokens)): ?>
            <div class="text-center py-5">
                <i class="bi bi-qr-code display-5 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No QR Tokens Found</h5>
                <p class="text-muted small mb-3">Generate secure QR tokens for clinical wards or inspection locations.</p>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#generateQrModal">
                    Generate First Token
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Entity &amp; Target</th>
                            <th>Token Type</th>
                            <th>Opaque Snippet</th>
                            <th>Issued On</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $t): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= e($t['entity_name']) ?></div>
                                    <span class="badge bg-secondary-subtle text-secondary text-capitalize" style="font-size: 0.72rem;">
                                        <?= e($t['entity_type']) ?> #<?= $t['entity_id'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info text-capitalize">
                                        <?= e(str_replace('_', ' ', $t['token_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <code class="text-dark bg-light px-2 py-1 rounded small border">
                                        <?= e($t['raw_code_snippet']) ?>
                                    </code>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></div>
                                    <div class="text-muted small" style="font-size: 0.72rem;">by <?= e($t['created_by_name'] ?? 'System') ?></div>
                                </td>
                                <td>
                                    <?php if ((int)$t['is_revoked'] === 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border">
                                            <i class="bi bi-x-circle me-1"></i> Revoked
                                        </span>
                                        <div class="text-muted small" style="font-size: 0.7rem;">
                                            <?= !empty($t['revoked_at']) ? date('d M Y', strtotime($t['revoked_at'])) : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btnPreviewBadge" 
                                                data-token="<?= e($t['raw_code_snippet']) ?>"
                                                data-title="<?= e($t['entity_name']) ?>"
                                                data-type="<?= e($t['token_type']) ?>"
                                                title="View Vector Badge">
                                            <i class="bi bi-qr-code"></i> Badge
                                        </button>
                                        <?php if ((int)$t['is_revoked'] === 0): ?>
                                            <form action="<?= url('/admin/qr-codes/' . $t['id'] . '/revoke') ?>" method="POST" class="d-inline" onsubmit="return confirm('Revoke this QR token immediately? Any supervisor scanning this code will be rejected.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="Revoke Token">
                                                    Revoke
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
                        Showing <?= count($tokens) ?> of <?= (int)$pagination['total_records'] ?> tokens
                    </span>
                    <nav aria-label="QR tokens pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                                <li class="page-item <?= $p === (int)$pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/qr-codes?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>">
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

<!-- Modal: Generate / Rotate QR Token -->
<div class="modal fade" id="generateQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/admin/qr-codes/generate') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-qr-code me-2"></i>Generate / Rotate Opaque QR Token
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        Generating a new token for an entity automatically revokes prior tokens, preventing unauthorized historical copies.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Entity Type</label>
                        <select class="form-select" name="entity_type" id="genEntityType">
                            <option value="ward">Clinical Ward</option>
                            <option value="location">Inspection Location</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Ward Target <span class="text-danger">*</span></label>
                        <select class="form-select" name="entity_id" required>
                            <option value="">-- Choose Ward --</option>
                            <?php foreach ($wards as $w): ?>
                                <option value="<?= $w['id'] ?>">
                                    <?= e($w['ward_name']) ?> (<?= e($w['ward_code']) ?>) &bull; <?= e($w['building_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Token Purpose</label>
                        <select class="form-select" name="token_type">
                            <option value="ward_checkin">Ward Presence Check-In</option>
                            <option value="location_inspection">Granular Sanitation Checkpoint</option>
                            <option value="supervisor_attendance">Supervisor Attendance Gateway</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Opaque Token</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Printable QR Badge Modal -->
<div class="modal fade" id="badgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="badgeModalTitle">Hospital Inspection QR Badge</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center" id="printableBadgeArea">
                <div class="border rounded p-4 bg-white shadow-sm d-inline-block">
                    <div class="text-uppercase fw-bold text-primary small mb-1">SHETH L.G. GENERAL HOSPITAL</div>
                    <div class="text-muted small mb-3">Ahmedabad &bull; Quality &amp; Sanitation Checkpoint</div>
                    <div id="badgeSvgContainer" class="d-flex justify-content-center mb-3">
                        <!-- SVG injected via JS -->
                    </div>
                    <div class="fw-bold text-dark fs-5" id="badgeEntityTitle">Ward Checkpoint</div>
                    <div class="text-muted small font-monospace" id="badgeTokenSnippet">LG-QR-SEC-XXXXXXXX</div>
                    <div class="mt-2 text-muted" style="font-size: 0.72rem;">Scan via Hospital Staff Mobile Portal</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i> Print Badge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Compatible QR Library Installation Instructions -->
<div class="modal fade" id="libInstructionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-terminal me-2"></i>Compatible QR Library Installation Guide
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <h6 class="fw-bold text-primary">1. Standalone Zero-Dependency Setup (Active by Default)</h6>
                <p class="small text-muted">
                    This system includes an integrated, pure PHP standalone vector SVG QR matrix generator in <code>App\Services\QrCodeService</code>. It functions out-of-the-box on Hostinger shared hosting without requiring Composer CLI, GD image extensions, or external Node/npm servers.
                </p>

                <h6 class="fw-bold text-primary mt-4">2. Optional Composer Library Installation</h6>
                <p class="small text-muted">
                    If Composer is available in your deployment pipeline or VPS, you can install the industry-standard BaconQrCode library:
                </p>
                <div class="bg-dark text-white p-3 rounded font-monospace small mb-3">
                    composer require bacon/bacon-qr-code:^3.0
                </div>
                <p class="small text-muted mb-0">
                    The <code>QrCodeService</code> automatically detects if <code>\BaconQrCode\Writer</code> is installed and seamlessly upgrades to it.
                </p>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Understood</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const badgeModal = new bootstrap.Modal(document.getElementById('badgeModal'));
    const badgeModalTitle = document.getElementById('badgeModalTitle');
    const badgeEntityTitle = document.getElementById('badgeEntityTitle');
    const badgeTokenSnippet = document.getElementById('badgeTokenSnippet');
    const badgeSvgContainer = document.getElementById('badgeSvgContainer');

    document.querySelectorAll('.btnPreviewBadge').forEach(btn => {
        btn.addEventListener('click', function() {
            const token = this.getAttribute('data-token');
            const title = this.getAttribute('data-title');
            const type = this.getAttribute('data-type');

            badgeModalTitle.textContent = 'Checkpoint Badge - ' + title;
            badgeEntityTitle.textContent = title;
            badgeTokenSnippet.textContent = token;

            // Load vector SVG from endpoint
            badgeSvgContainer.innerHTML = '<div class="spinner-border text-primary my-4" role="status"></div>';
            badgeModal.show();

            fetch('<?= url('/qr-codes/svg?token=') ?>' + encodeURIComponent(token))
                .then(r => r.text())
                .then(svg => {
                    badgeSvgContainer.innerHTML = svg;
                })
                .catch(() => {
                    badgeSvgContainer.innerHTML = '<span class="text-danger small">Failed to render badge</span>';
                });
        });
    });
});
</script>
