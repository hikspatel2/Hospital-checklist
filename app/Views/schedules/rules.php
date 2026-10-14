<?php
/** @var array $rules */
/** @var array $pagination */
/** @var array $filters */
/** @var array $templates */
/** @var array $shifts */
/** @var array $buildings */
/** @var array $supervisors */
/** @var array $frequencies */
?>
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Phase 5</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Inspection Recurrence Rules</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">Automated recurrence rules, weekday filters, time windows, and holiday exclusions.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/schedules') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-calendar-check me-1"></i> Schedules Calendar
            </a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                    <i class="bi bi-plus-circle me-1"></i> New Recurrence Rule
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recurrence Rules Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">Rule / Template</th>
                        <th>Frequency</th>
                        <th>Weekdays / Time Window</th>
                        <th>Facility Scope</th>
                        <th>Assigned Supervisor</th>
                        <th>Effective Period</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($rules)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                No recurrence rules defined yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rules as $r): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-navy-dark"><?= e($r['template_name']) ?></div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace small px-2 py-0">
                                        <?= e($r['template_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1 small">
                                        <?= e($frequencies[$r['frequency']] ?? $r['frequency']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($r['selected_weekdays'])): ?>
                                        <div class="small fw-semibold text-uppercase text-primary"><?= e(strtoupper($r['selected_weekdays'])) ?></div>
                                    <?php else: ?>
                                        <div class="small text-muted">All Days</div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['time_window_start']) && !empty($r['time_window_end'])): ?>
                                        <div class="small text-muted font-monospace"><i class="bi bi-clock me-1"></i><?= substr($r['time_window_start'], 0, 5) ?> - <?= substr($r['time_window_end'], 0, 5) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['ward_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-hospital me-1 text-primary"></i>Ward: <?= e($r['ward_name']) ?></span>
                                    <?php elseif (!empty($r['floor_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-layers me-1 text-primary"></i>Floor: <?= e($r['floor_name']) ?></span>
                                    <?php elseif (!empty($r['building_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-building me-1 text-primary"></i>Bldg: <?= e($r['building_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small">All Wards</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['assigned_user_name'])): ?>
                                        <span class="small fw-semibold text-dark"><i class="bi bi-person me-1 text-primary"></i><?= e($r['assigned_user_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small">Auto from Ward Roster</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-dark font-monospace"><?= e($r['start_date']) ?></div>
                                    <div class="small text-muted font-monospace">to <?= e($r['end_date'] ?? 'Indefinite') ?></div>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'active'): ?>
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1">Active</span>
                                    <?php elseif ($r['status'] === 'paused'): ?>
                                        <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 px-2 py-1">Paused</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-15 text-secondary border px-2 py-1">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if (\App\Core\Auth::isAdmin()): ?>
                                        <form method="POST" action="<?= url("/admin/schedules/rules/{$r['id']}/toggle-status") ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm <?= $r['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?> rounded-3 px-2 py-1">
                                                <?= $r['status'] === 'active' ? '<i class="bi bi-pause-circle me-1"></i>Pause' : '<i class="bi bi-play-circle me-1"></i>Resume' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Recurrence Rule Modal -->
<div class="modal fade" id="createRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-navy text-white px-4 py-3">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-clock-history me-2"></i>Create Custom Recurrence Rule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('/admin/schedules/rules') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Checklist Template *</label>
                            <select name="template_id" class="form-select form-select-sm" required>
                                <option value="">Select Published Template...</option>
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= e($t['template_name']) ?> (<?= e($t['template_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Recurrence Frequency *</label>
                            <select name="frequency" class="form-select form-select-sm" id="ruleFrequencySelect" required>
                                <?php foreach ($frequencies as $fKey => $fLabel): ?>
                                    <option value="<?= e($fKey) ?>"><?= e($fLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Weekdays Checkboxes -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Active Weekdays</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $dKey => $dLabel): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="weekdays[]" value="<?= $dKey ?>" id="day_<?= $dKey ?>" checked>
                                        <label class="form-check-label small" for="day_<?= $dKey ?>"><?= $dLabel ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Time Window -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Time Window Start</label>
                            <input type="time" name="time_window_start" class="form-control form-control-sm" value="09:00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Time Window End</label>
                            <input type="time" name="time_window_end" class="form-control form-control-sm" value="13:00">
                        </div>

                        <!-- Shift & Supervisor -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Shift Timing (Optional)</label>
                            <select name="shift_id" class="form-select form-select-sm">
                                <option value="">Any Shift</option>
                                <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['shift_name']) ?> (<?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Specific Supervisor (Optional)</label>
                            <select name="assigned_user_id" class="form-select form-select-sm">
                                <option value="">Auto-assign from Ward Duty Roster</option>
                                <?php foreach ($supervisors as $sup): ?>
                                    <option value="<?= $sup['id'] ?>"><?= e($sup['full_name']) ?> (<?= e($sup['employee_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Target Building -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Building (Optional)</label>
                            <select name="target_building_id" class="form-select form-select-sm">
                                <option value="">All Hospital Buildings</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['building_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Effective Dates -->
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Effective From *</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Effective To</label>
                            <input type="date" name="end_date" class="form-control form-control-sm">
                        </div>

                        <!-- Holiday / Exclusions -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Hospital Holidays / Exclusion Dates</label>
                            <textarea name="exclusions_text" class="form-control form-control-sm" rows="2" placeholder="Comma-separated YYYY-MM-DD dates to exclude, e.g. 2026-01-26, 2026-08-15, 2026-10-02"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-0">
                    <button type="button" class="btn btn-sm btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-3 px-4">Create Recurrence Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
