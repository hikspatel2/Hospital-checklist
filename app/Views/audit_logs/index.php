<?php
/**
 * Sheth L.G. General Hospital - Append-Only Audit Trail Console
 * @var array $logs
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var int $lastPage
 * @var array $filters
 * @var array $metrics
 * @var array $actions
 * @var array $tables
 * @var array $users
 */
?>

<div class="container-fluid px-3 px-md-4 py-3">
    <!-- Header with Security & Immutability Badges -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-3 border-bottom gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark px-2 py-1"><i class="bi bi-shield-check"></i> Append-Only</span>
                <h4 class="mb-0 fw-bold text-navy-dark">Security & Audit Trail Log</h4>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                    <i class="bi bi-lock-fill"></i> Tamper-Resistant
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Sheth L.G. General Hospital &bull; Immutable forensic ledger of user actions, facility changes, and report exports.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">
                <i class="bi bi-info-circle"></i> Log modification and deletion are strictly disallowed by system architecture.
            </span>
        </div>
    </div>

    <!-- Security KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                <div class="text-muted small text-uppercase fw-semibold">Total Logged Events</div>
                <div class="fs-4 fw-bold text-navy-dark mt-1"><?= number_format($metrics['total_logs'] ?? 0) ?></div>
                <div class="small text-muted mt-1"><i class="bi bi-database"></i> All Recorded History</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
                <div class="text-muted small text-uppercase fw-semibold">Today's Activity</div>
                <div class="fs-4 fw-bold text-info mt-1"><?= number_format($metrics['today_logs'] ?? 0) ?></div>
                <div class="small text-muted mt-1"><i class="bi bi-calendar-check"></i> Recorded Since 00:00 IST</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                <div class="text-muted small text-uppercase fw-semibold">Security Alerts</div>
                <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($metrics['security_alerts'] ?? 0) ?></div>
                <div class="small text-muted mt-1"><i class="bi bi-shield-exclamation"></i> Suspicious / Locked / Failed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                <div class="text-muted small text-uppercase fw-semibold">Report Exports Audited</div>
                <div class="fs-4 fw-bold text-success mt-1"><?= number_format($metrics['report_exports'] ?? 0) ?></div>
                <div class="small text-muted mt-1"><i class="bi bi-file-earmark-spreadsheet"></i> Forensic Export Track</div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('/audit-logs') ?>" class="row g-2 align-items-end">
                <!-- Search Keyword -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Search Keywords</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Action, IP, User, Table..." value="<?= e($filters['search'] ?? '') ?>">
                </div>

                <!-- Event Action Dropdown -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Action Type</label>
                    <select name="event_action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= e($act) ?>" <?= ($filters['event_action'] ?? '') === $act ? 'selected' : '' ?>>
                                <?= e($act) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Entity Table Dropdown -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Entity Table</label>
                    <select name="entity_table" class="form-select form-select-sm">
                        <option value="">All Tables</option>
                        <?php foreach ($tables as $tbl): ?>
                            <option value="<?= e($tbl) ?>" <?= ($filters['entity_table'] ?? '') === $tbl ? 'selected' : '' ?>>
                                <?= e($tbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Actor Dropdown -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Actor (User)</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Actors</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (!empty($filters['user_id']) && (int)$filters['user_id'] === (int)$u['id']) ? 'selected' : '' ?>>
                                <?= e($u['name']) ?> (<?= e($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-6 col-md-1">
                    <label class="form-label small fw-semibold text-muted mb-1">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label small fw-semibold text-muted mb-1">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? '') ?>">
                </div>

                <!-- Action Buttons -->
                <div class="col-12 col-md-auto ms-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="<?= url('/audit-logs') ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Log Records Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-navy-dark d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-primary"></i>
                <span>Audit Trail Records</span>
                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2">
                    <?= number_format($total) ?> total events
                </span>
            </h6>
            <div class="small text-muted">
                Showing Page <?= $page ?> of <?= max(1, $lastPage) ?>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-shield-x text-muted fs-1 d-block mb-2"></i>
                    <h6 class="text-muted fw-semibold">No audit entries found matching filters</h6>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase text-muted border-bottom">
                            <tr>
                                <th># ID</th>
                                <th>Timestamp (IST)</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Affected Entity</th>
                                <th>Client IP</th>
                                <th>User Agent Snippet</th>
                                <th class="text-end">Forensics</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td class="text-muted font-monospace">#<?= $l['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= date('d M Y, h:i:s A', strtotime($l['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($l['user_name'])): ?>
                                            <div class="fw-semibold text-navy-dark"><?= e($l['user_name']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-1"><?= ucfirst($l['user_role'] ?? 'user') ?></span>
                                                @<?= e($l['username']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">System / Anonymous</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $actClass = match($l['event_action']) {
                                            'LOGIN_SUCCESS' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'LOGIN_FAILURE', 'LOGIN_LOCKED_ATTEMPT', 'SUSPICIOUS_ACTIVITY' => 'bg-danger text-white',
                                            'REPORT_EXPORTED' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                            'INSPECTION_COMPLETED' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $actClass ?> px-2 py-1 rounded-pill">
                                            <?= e($l['event_action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?= e($l['entity_table']) ?></span>
                                        <?php if ($l['entity_id']): ?>
                                            <span class="badge bg-light text-dark border ms-1">#<?= (int)$l['entity_id'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace text-muted"><?= e($l['ip_address']) ?></td>
                                    <td>
                                        <span class="text-muted d-inline-block text-truncate" style="max-width: 180px;" title="<?= e($l['user_agent'] ?? '') ?>">
                                            <?= e($l['user_agent'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="inspectAuditLog(<?= (int)$l['id'] ?>)">
                                            <i class="bi bi-braces"></i> Diff
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($lastPage > 1): ?>
            <div class="card-footer bg-white py-2 border-top d-flex align-items-center justify-content-between">
                <div class="small text-muted">
                    Page <?= $page ?> of <?= $lastPage ?> (<?= number_format($total) ?> total)
                </div>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $q = $filters;
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <?php $q['page'] = $page - 1; ?>
                        <a class="page-link" href="<?= url('/audit-logs?' . http_build_query($q)) ?>">Previous</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++): ?>
                        <?php $q['page'] = $i; ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/audit-logs?' . http_build_query($q)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
                        <?php $q['page'] = $page + 1; ?>
                        <a class="page-link" href="<?= url('/audit-logs?' . http_build_query($q)) ?>">Next</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Inspecting Audit Log Payload Diff -->
<div class="modal fade" id="auditDiffModal" tabindex="-1" aria-labelledby="auditDiffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-navy-dark text-white py-3">
                <h5 class="modal-title fs-6 d-flex align-items-center gap-2" id="auditDiffModalLabel">
                    <i class="bi bi-shield-lock-fill text-warning"></i>
                    <span>Audit Event Forensic Details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-3 small bg-light p-3 rounded border">
                    <div class="col-6">
                        <strong>Event Action:</strong> <span id="modalAction" class="badge bg-primary"></span>
                    </div>
                    <div class="col-6">
                        <strong>Target Entity:</strong> <span id="modalEntity"></span>
                    </div>
                    <div class="col-6">
                        <strong>Actor:</strong> <span id="modalActor"></span>
                    </div>
                    <div class="col-6">
                        <strong>Timestamp:</strong> <span id="modalTime"></span>
                    </div>
                    <div class="col-6">
                        <strong>Client IP:</strong> <span id="modalIp" class="font-monospace"></span>
                    </div>
                    <div class="col-6">
                        <strong>User Agent:</strong> <span id="modalAgent" class="text-truncate d-inline-block" style="max-width: 250px;"></span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-2 bg-light h-100">
                            <h6 class="small fw-bold text-uppercase text-danger mb-2"><i class="bi bi-dash-circle"></i> Before Values (Old)</h6>
                            <pre id="modalOldValues" class="small bg-white p-2 border rounded" style="max-height: 250px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2 bg-light h-100">
                            <h6 class="small fw-bold text-uppercase text-success mb-2"><i class="bi bi-plus-circle"></i> After Values (New)</h6>
                            <pre id="modalNewValues" class="small bg-white p-2 border rounded" style="max-height: 250px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary mt-3 mb-0 small py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i>
                    <span>Sensitive fields (passwords, auth tokens, secrets) are automatically sanitized and redacted.</span>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close Inspector</button>
            </div>
        </div>
    </div>
</div>

<script>
function inspectAuditLog(logId) {
    var modal = new bootstrap.Modal(document.getElementById('auditDiffModal'));
    
    document.getElementById('modalAction').textContent = 'Loading...';
    document.getElementById('modalEntity').textContent = 'Loading...';
    document.getElementById('modalActor').textContent = 'Loading...';
    document.getElementById('modalTime').textContent = 'Loading...';
    document.getElementById('modalIp').textContent = 'Loading...';
    document.getElementById('modalAgent').textContent = 'Loading...';
    document.getElementById('modalOldValues').textContent = 'Loading...';
    document.getElementById('modalNewValues').textContent = 'Loading...';

    modal.show();

    fetch('<?= url('/audit-logs/') ?>' + logId)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            document.getElementById('modalAction').textContent = data.event_action || 'N/A';
            document.getElementById('modalEntity').textContent = (data.entity_table || 'system') + (data.entity_id ? ' #' + data.entity_id : '');
            document.getElementById('modalActor').textContent = (data.user_name || 'System') + ' (' + (data.user_role || 'system') + ')';
            document.getElementById('modalTime').textContent = data.created_at || 'N/A';
            document.getElementById('modalIp').textContent = data.ip_address || 'N/A';
            document.getElementById('modalAgent').textContent = data.user_agent || 'N/A';
            document.getElementById('modalAgent').title = data.user_agent || '';

            document.getElementById('modalOldValues').textContent = data.old_values ? JSON.stringify(data.old_values, null, 2) : '(None - New Record)';
            document.getElementById('modalNewValues').textContent = data.new_values ? JSON.stringify(data.new_values, null, 2) : '(None - Deletion/Revocation)';
        })
        .catch(function(err) {
            document.getElementById('modalOldValues').textContent = 'Error loading details.';
            document.getElementById('modalNewValues').textContent = 'Error loading details.';
        });
}
</script>
