<?php
/** @var array $groupedSettings */
/** @var array $health */
/** @var array $recentAudit */
/** @var array $timezones */

$general = $groupedSettings['general'] ?? [];
$attendance = $groupedSettings['attendance'] ?? [];
$inspections = $groupedSettings['inspections'] ?? [];
$complaints = $groupedSettings['complaints'] ?? [];
$notifications = $groupedSettings['notifications'] ?? [];
$retention = $groupedSettings['retention_and_backups'] ?? [];
$maintenance = $groupedSettings['maintenance'] ?? [];
?>
<div class="container-fluid px-0">
    <!-- Header Title Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Phase 9</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Hospital Settings & Security Hardening</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Configure facility parameters, geofencing rules, SLA thresholds, data retention safeguards, and system health telemetry.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-3">
                <i class="bi bi-shield-check me-1"></i> Safeguard Defense Active
            </span>
            <a href="<?= url('/audit-logs?entity_table=hospital_settings') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-clock-history me-1"></i> Settings Audit Trail
            </a>
        </div>
    </div>

    <!-- System Health & Security Telemetry Cards -->
    <div class="row g-3 mb-4">
        <!-- Database Health -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">DATABASE LINK</span>
                        <span class="badge <?= $health['database']['connected'] ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= $health['database']['connected'] ? 'text-success' : 'text-danger' ?> rounded-pill">
                            <?= $health['database']['connected'] ? 'Operational' : 'Disconnected' ?>
                        </span>
                    </div>
                    <div class="h5 fw-bold mb-1 text-navy"><?= e($health['database']['latency']) ?> ms Latency</div>
                    <div class="text-muted small text-truncate" title="<?= e($health['database']['version']) ?>">
                        Engine: <?= e($health['database']['version']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Runtime Environment -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">RUNTIME & PHP</span>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill">PHP <?= e($health['php']['version']) ?></span>
                    </div>
                    <div class="h5 fw-bold mb-1 text-navy"><?= e($health['php']['memory_usage']) ?> Active</div>
                    <div class="text-muted small">
                        Max Upload: <?= e($health['php']['upload_max']) ?> | Peak: <?= e($health['php']['memory_peak']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timezone & Sync -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">CLINICAL TIMEZONE</span>
                        <span class="badge <?= $health['timezone']['synchronized'] ? 'bg-success' : 'bg-warning' ?> bg-opacity-10 <?= $health['timezone']['synchronized'] ? 'text-success' : 'text-warning' ?> rounded-pill">
                            <?= $health['timezone']['synchronized'] ? 'Synchronized' : 'Desynced' ?>
                        </span>
                    </div>
                    <div class="h5 fw-bold mb-1 text-navy"><?= e($health['timezone']['configured']) ?></div>
                    <div class="text-muted small">
                        Clock: <?= date('d M Y, h:i A') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Ledger & Mode -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">SECURITY LEDGER</span>
                        <span class="badge <?= $health['security']['maintenance_mode'] ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 <?= $health['security']['maintenance_mode'] ? 'text-danger' : 'text-success' ?> rounded-pill">
                            <?= $health['security']['maintenance_mode'] ? 'Maintenance Active' : 'Normal Operation' ?>
                        </span>
                    </div>
                    <div class="h5 fw-bold mb-1 text-navy"><?= number_format($health['security']['audit_trail_count']) ?> Events</div>
                    <div class="text-muted small">
                        CSRF & Cookies: Strict Hardened
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Navigation Tabs and Form -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-fill border-0" id="settingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-semibold text-navy border-0 rounded-0" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-pane" type="button" role="tab">
                        <i class="bi bi-hospital me-1"></i> General & Branding
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-navy border-0 rounded-0" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance-pane" type="button" role="tab">
                        <i class="bi bi-geo-alt me-1"></i> Attendance & GPS
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-navy border-0 rounded-0" id="inspections-tab" data-bs-toggle="tab" data-bs-target="#inspections-pane" type="button" role="tab">
                        <i class="bi bi-clipboard2-check me-1"></i> Inspections & Defaults
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-navy border-0 rounded-0" id="complaints-tab" data-bs-toggle="tab" data-bs-target="#complaints-pane" type="button" role="tab">
                        <i class="bi bi-exclamation-triangle me-1"></i> Complaint SLAs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-navy border-0 rounded-0" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications-pane" type="button" role="tab">
                        <i class="bi bi-bell me-1"></i> Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-navy border-0 rounded-0" id="retention-tab" data-bs-toggle="tab" data-bs-target="#retention-pane" type="button" role="tab">
                        <i class="bi bi-shield-lock me-1"></i> Retention & Safeguards
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-navy border-0 rounded-0" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance-pane" type="button" role="tab">
                        <i class="bi bi-tools me-1"></i> Maintenance Mode
                    </button>
                </li>
            </ul>
        </div>

        <form action="<?= url('/settings') ?>" method="POST" id="hospitalSettingsForm">
            <?= csrf_field() ?>
            <div class="card-body p-4">
                <div class="tab-content" id="settingsTabContent">
                    
                    <!-- TAB 1: General & Branding -->
                    <div class="tab-pane fade show active" id="general-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-navy small">Hospital Name <span class="text-danger">*</span></label>
                                <input type="text" name="hospital_name" class="form-control" value="<?= e($general['hospital_name']['setting_value'] ?? 'Sheth L.G. General Hospital, Ahmedabad') ?>" required minlength="3" maxlength="200">
                                <div class="form-text">Primary official facility header displayed on all clinical reports, dashboards, and QR codes.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-navy small">Hospital Subtitle</label>
                                <input type="text" name="hospital_subtitle" class="form-control" value="<?= e($general['hospital_subtitle']['setting_value'] ?? '') ?>" maxlength="255">
                                <div class="form-text">Subtitle displayed under the primary facility title.</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold text-navy small">Facility Physical Address</label>
                                <input type="text" name="hospital_address" class="form-control" value="<?= e($general['hospital_address']['setting_value'] ?? '') ?>" maxlength="255">
                                <div class="form-text">Official postal address used in formal compliance certificates and audit ledgers.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Application Timezone <span class="text-danger">*</span></label>
                                <select name="app_timezone" class="form-select" required>
                                    <?php $currentTz = $general['app_timezone']['setting_value'] ?? 'Asia/Kolkata'; ?>
                                    <?php foreach ($timezones as $tz): ?>
                                        <option value="<?= e($tz) ?>" <?= $tz === $currentTz ? 'selected' : '' ?>><?= e($tz) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Standard operational timezone (Default: <code>Asia/Kolkata</code>).</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Attendance & GPS Policy -->
                    <div class="tab-pane fade" id="attendance-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Default Geofence Radius (Meters) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="default_geofence_radius_meters" class="form-control" value="<?= e($attendance['default_geofence_radius_meters']['setting_value'] ?? '150') ?>" min="10" max="5000" required>
                                    <span class="input-group-text bg-light">meters</span>
                                </div>
                                <div class="form-text">Permitted distance from checkpoint coordinates for verified check-in.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Max Acceptable GPS Accuracy <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="max_acceptable_gps_accuracy_meters" class="form-control" value="<?= e($attendance['max_acceptable_gps_accuracy_meters']['setting_value'] ?? '100') ?>" min="10" max="1000" required>
                                    <span class="input-group-text bg-light">meters</span>
                                </div>
                                <div class="form-text">Signals with reported horizontal accuracy worse than this threshold are rejected.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Shift Grace Period <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="default_shift_grace_minutes" class="form-control" value="<?= e($attendance['default_shift_grace_minutes']['setting_value'] ?? '15') ?>" min="0" max="180" required>
                                    <span class="input-group-text bg-light">minutes</span>
                                </div>
                                <div class="form-text">Grace window before an attendance punch is recorded as late check-in.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch p-0 pt-2">
                                    <?php $reqGps = (int)($attendance['require_gps_verification']['setting_value'] ?? 1); ?>
                                    <label class="form-label fw-semibold text-navy small d-block mb-1" for="require_gps_switch">Enforce Hardware GPS Verification</label>
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="require_gps_switch" name="require_gps_verification" value="1" <?= $reqGps === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label text-muted small" for="require_gps_switch">
                                        Require browser geolocation coordinates to match registered checkpoint geofences.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Inspections & Scheduling Defaults -->
                    <div class="tab-pane fade" id="inspections-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Schedule Generation Horizon <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="schedule_generation_days_ahead" class="form-control" value="<?= e($inspections['schedule_generation_days_ahead']['setting_value'] ?? '7') ?>" min="1" max="60" required>
                                    <span class="input-group-text bg-light">days</span>
                                </div>
                                <div class="form-text">How far in advance recurring schedules are automatically generated by cron.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Auto-Mark Overdue Window <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="auto_mark_overdue_hours" class="form-control" value="<?= e($inspections['auto_mark_overdue_hours']['setting_value'] ?? '2') ?>" min="1" max="48" required>
                                    <span class="input-group-text bg-light">hours</span>
                                </div>
                                <div class="form-text">Hours after shift expiration before incomplete inspections are marked overdue.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Default Passing Score <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="default_pass_threshold_percentage" class="form-control" value="<?= e($inspections['default_pass_threshold_percentage']['setting_value'] ?? '80.0') ?>" min="10" max="100" required>
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                                <div class="form-text">Minimum scoring percentage required for an inspection to pass compliance.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch p-0 pt-2">
                                    <?php $enforceQr = (int)($inspections['enforce_qr_scan_per_ward']['setting_value'] ?? 1); ?>
                                    <label class="form-label fw-semibold text-navy small d-block mb-1" for="enforce_qr_switch">Enforce QR Scan at Inspection Start</label>
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="enforce_qr_switch" name="enforce_qr_scan_per_ward" value="1" <?= $enforceQr === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label text-muted small" for="enforce_qr_switch">
                                        Supervisors must scan the ward's opaque QR token before unlocking inspection questions.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: Complaint SLAs -->
                    <div class="tab-pane fade" id="complaints-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Default Response SLA <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="default_complaint_response_hours" class="form-control" value="<?= e($complaints['default_complaint_response_hours']['setting_value'] ?? '4') ?>" min="1" max="168" required>
                                    <span class="input-group-text bg-light">hours</span>
                                </div>
                                <div class="form-text">Target time to acknowledge and assign a new complaint.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Default Resolution SLA <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="default_complaint_resolution_hours" class="form-control" value="<?= e($complaints['default_complaint_resolution_hours']['setting_value'] ?? '24') ?>" min="1" max="720" required>
                                    <span class="input-group-text bg-light">hours</span>
                                </div>
                                <div class="form-text">Maximum acceptable time window to resolve reported facility issues.</div>
                            </div>
                            <div class="col-md-4">
                                <div class="pt-4">
                                    <div class="form-check form-switch p-0">
                                        <?php $autoEscalate = (int)($complaints['auto_escalate_overdue_tickets']['setting_value'] ?? 1); ?>
                                        <label class="form-label fw-semibold text-navy small d-block mb-1" for="auto_escalate_switch">Auto-Escalate SLA Breaches</label>
                                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="auto_escalate_switch" name="auto_escalate_overdue_tickets" value="1" <?= $autoEscalate === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label text-muted small" for="auto_escalate_switch">
                                            Notify hospital administrators immediately upon SLA breach.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: Notifications -->
                    <div class="tab-pane fade" id="notifications-pane" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="form-check form-switch p-0">
                                        <?php $inApp = (int)($notifications['enable_in_app_notifications']['setting_value'] ?? 1); ?>
                                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="in_app_switch" name="enable_in_app_notifications" value="1" <?= $inApp === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold text-navy small" for="in_app_switch">Enable In-App Notification Center</label>
                                        <div class="text-muted small mt-1">Broadcast real-time badges and bell alerts for supervisors and administrators.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="form-check form-switch p-0">
                                        <?php $emailAlerts = (int)($notifications['enable_email_alerts']['setting_value'] ?? 0); ?>
                                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="email_alerts_switch" name="enable_email_alerts" value="1" <?= $emailAlerts === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold text-navy small" for="email_alerts_switch">Enable Outbound Email Alerts</label>
                                        <div class="text-muted small mt-1">Send SMTP notifications for critical facility failures and emergency complaints.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="form-check form-switch p-0">
                                        <?php $critFail = (int)($notifications['alert_on_critical_failure']['setting_value'] ?? 1); ?>
                                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="crit_fail_switch" name="alert_on_critical_failure" value="1" <?= $critFail === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold text-navy small" for="crit_fail_switch">Instant Alert on Checklist Critical Item Failure</label>
                                        <div class="text-muted small mt-1">Triggers immediate high-priority alert when critical hygiene or safety checks fail.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="form-check form-switch p-0">
                                        <?php $slaAlert = (int)($notifications['alert_on_sla_breach']['setting_value'] ?? 1); ?>
                                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="sla_alert_switch" name="alert_on_sla_breach" value="1" <?= $slaAlert === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold text-navy small" for="sla_alert_switch">Alert on Complaint SLA Overdue</label>
                                        <div class="text-muted small mt-1">Dispatches notification when a complaint exceeds its maximum resolution window.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: Retention & Critical Safeguards -->
                    <div class="tab-pane fade" id="retention-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Audit Trail Retention <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="audit_log_retention_days" class="form-control" value="<?= e($retention['audit_log_retention_days']['setting_value'] ?? '365') ?>" min="30" max="3650" required>
                                    <span class="input-group-text bg-light">days</span>
                                </div>
                                <div class="form-text">Historical period before archived audit records are eligible for scheduled purge.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Evidence File Retention <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="evidence_file_retention_days" class="form-control" value="<?= e($retention['evidence_file_retention_days']['setting_value'] ?? '180') ?>" min="30" max="1825" required>
                                    <span class="input-group-text bg-light">days</span>
                                </div>
                                <div class="form-text">Retention period for inspection and complaint photo evidence outside web root.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-navy small">Database Backup Retention <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="backup_retention_days" class="form-control" value="<?= e($retention['backup_retention_days']['setting_value'] ?? '30') ?>" min="7" max="365" required>
                                    <span class="input-group-text bg-light">days</span>
                                </div>
                                <div class="form-text">Retention policy for automated nightly database snapshot archives.</div>
                            </div>

                            <!-- Critical Safeguards Immutability Card -->
                            <div class="col-12">
                                <div class="card border border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi bi-shield-fill-exclamation text-warning fs-5"></i>
                                            <h6 class="fw-bold mb-0 text-navy">Non-Negotiable Clinical Security Safeguards (Hardware-Locked)</h6>
                                        </div>
                                        <p class="small text-muted mb-3">
                                            The following security protections are hard-coded into the core compliance architecture. 
                                            Ordinary settings requests cannot disable them under any circumstances:
                                        </p>
                                        <div class="row g-2 small">
                                            <div class="col-md-3">
                                                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between">
                                                    <span><i class="bi bi-lock-fill text-success me-1"></i> Audit Logging</span>
                                                    <span class="badge bg-success">Mandatory ON</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between">
                                                    <span><i class="bi bi-lock-fill text-success me-1"></i> RBAC Enforcement</span>
                                                    <span class="badge bg-success">Mandatory ON</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between">
                                                    <span><i class="bi bi-lock-fill text-success me-1"></i> Backup Safeguards</span>
                                                    <span class="badge bg-success">Mandatory ON</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between">
                                                    <span><i class="bi bi-lock-fill text-success me-1"></i> HTTPS & CSP Headers</span>
                                                    <span class="badge bg-success">Mandatory ON</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 7: Maintenance Mode & Notices -->
                    <div class="tab-pane fade" id="maintenance-pane" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="form-check form-switch p-0">
                                        <?php $maintActive = (int)($maintenance['maintenance_mode_enabled']['setting_value'] ?? 0); ?>
                                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="maintenance_mode_switch" name="maintenance_mode_enabled" value="1" <?= $maintActive === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-navy" for="maintenance_mode_switch">
                                            Enable Clinical Maintenance Mode (HTTP 503)
                                        </label>
                                        <div class="text-danger small mt-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            When enabled, all non-administrative users are redirected to the Maintenance Notice page. 
                                            Only administrators can access the system to perform upgrades and configuration.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-navy small">Maintenance Notice Broadcast Message</label>
                                <textarea name="maintenance_notice_message" class="form-control" rows="3"><?= e($maintenance['maintenance_notice_message']['setting_value'] ?? '') ?></textarea>
                                <div class="form-text">Public emergency message and control room helpline displayed during system maintenance.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Form Action Footer -->
            <div class="card-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center border-top">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Changes will be verified and permanently logged in the audit trail.
                </span>
                <button type="submit" class="btn btn-navy px-4 py-2 rounded-3 fw-semibold shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Save Hospital Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Settings Audit Modifications Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-navy mb-0">
                <i class="bi bi-clock-history me-1 text-primary"></i> Recent Settings Configuration Audit Trail
            </h6>
            <a href="<?= url('/audit-logs?entity_table=hospital_settings') ?>" class="small text-decoration-none">
                View Full Audit Ledger <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-muted">
                    <tr>
                        <th>Timestamp (IST)</th>
                        <th>Administrator</th>
                        <th>Event Action</th>
                        <th>Keys Modified</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentAudit)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-shield-check fs-4 d-block mb-1 text-success"></i>
                                No configuration modifications recorded yet. Initial baseline configuration active.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentAudit as $log): ?>
                            <tr>
                                <td><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="fw-semibold text-navy"><?= e($log['actor_name'] ?? 'System') ?></span>
                                    <span class="text-muted" style="font-size: 0.72rem;">(ID: <?= (int)$log['user_id'] ?>)</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        <?= e($log['event_action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $newVal = json_decode($log['new_values'] ?? '[]', true);
                                    if (is_array($newVal)):
                                        foreach (array_slice(array_keys($newVal), 0, 3) as $k):
                                    ?>
                                        <code class="me-1"><?= e($k) ?></code>
                                    <?php endforeach; 
                                        if (count($newVal) > 3) echo ' <span class="text-muted">+' . (count($newVal) - 3) . ' more</span>';
                                    endif; ?>
                                </td>
                                <td><code><?= e($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
