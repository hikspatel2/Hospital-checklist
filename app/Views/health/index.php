<?php
/** @var array $health */
/** @var bool $isSystemHealthy */
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 fw-bold text-navy mb-1">System Diagnostics & Health Check</h2>
                <p class="text-secondary small mb-0">Sheth L.G. General Hospital &bull; Real-time Operational Telemetry</p>
            </div>
            <a href="<?= url('/health?format=json') ?>" target="_blank" class="btn btn-outline-navy btn-sm px-3">
                <i class="bi bi-code-slash me-1"></i>View Raw JSON
            </a>
        </div>

        <div class="card hospital-card shadow-sm border-0 mb-4">
            <div class="card-body p-4 text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle <?= $isSystemHealthy ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning-emphasis' ?> p-4 mb-3">
                    <i class="bi <?= $isSystemHealthy ? 'bi-shield-check' : 'bi-exclamation-triangle' ?> fs-1"></i>
                </div>
                <h3 class="h4 fw-bold mb-1 text-navy">
                    System Status: <?= $isSystemHealthy ? '<span class="text-success">Operational</span>' : '<span class="text-warning">Degraded Services</span>' ?>
                </h3>
                <p class="text-secondary small mb-3">
                    Telemetry captured at: <strong class="text-dark"><?= e($health['timestamp']) ?></strong>
                </p>
                <div class="d-inline-block bg-light px-3 py-1 rounded-pill small border">
                    Environment: <strong class="text-capitalize text-navy"><?= e($health['runtime']['environment']) ?></strong> &bull; PHP <?= e($health['runtime']['php_version']) ?>
                </div>
            </div>
        </div>

        <div class="card hospital-card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="card-title fw-bold text-navy mb-0">Core Subsystem Telemetry</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <!-- Application Subsystem -->
                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cpu fs-5 text-primary me-3"></i>
                            <div>
                                <div class="fw-semibold text-navy">Application Routing & Bootstrap</div>
                                <div class="text-muted small">MVC Controller Dispatcher & Session Engine</div>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success border-opacity-25">
                            <i class="bi bi-check-circle-fill me-1"></i><?= e($health['services']['application']) ?>
                        </span>
                    </div>

                    <!-- Database Subsystem -->
                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-database fs-5 text-primary me-3"></i>
                            <div>
                                <div class="fw-semibold text-navy">Relational Database Connectivity (PDO)</div>
                                <div class="text-muted small">InnoDB / UTF8mb4 MySQL Connection Check</div>
                            </div>
                        </div>
                        <?php if ($health['services']['database'] === 'connected'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success border-opacity-25">
                                <i class="bi bi-check-circle-fill me-1"></i>Connected
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 border border-danger border-opacity-25">
                                <i class="bi bi-x-circle-fill me-1"></i>Disconnected / Migrations Pending
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Storage Subsystem -->
                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-folder-check fs-5 text-primary me-3"></i>
                            <div>
                                <div class="fw-semibold text-navy">File Storage System Permissions</div>
                                <div class="text-muted small">storage/logs, storage/backups, storage/uploads</div>
                            </div>
                        </div>
                        <?php if ($health['services']['storage'] === 'writable'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success border-opacity-25">
                                <i class="bi bi-check-circle-fill me-1"></i>Writable & Hardened
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis px-3 py-2 border border-warning border-opacity-25">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Permission Check Needed
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Timezone Subsystem -->
                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock-history fs-5 text-primary me-3"></i>
                            <div>
                                <div class="fw-semibold text-navy">Hospital Operations Timezone</div>
                                <div class="text-muted small">Standard mandatory timezone constraint</div>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success border-opacity-25">
                            <i class="bi bi-check-circle-fill me-1"></i><?= e($health['services']['timezone']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light p-3 text-center text-muted small">
                <i class="bi bi-shield-lock me-1"></i>For hospital security compliance, connection credentials, internal hosts, and table specifics are masked from this diagnostic view.
            </div>
        </div>

        <div class="text-center mb-5">
            <a href="<?= url('/') ?>" class="btn btn-navy px-4">
                <i class="bi bi-arrow-left me-1"></i>Return to Facility Dashboard
            </a>
        </div>
    </div>
</div>
