<?php
/** @var array $schedules */
/** @var string $currentDate */
/** @var array $statusBadges */
?>
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Supervisor Portal</span>
                <h4 class="fw-bold mb-0 text-navy-dark">My Assigned Inspections</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">Pending and scheduled facility inspections assigned to your profile for today.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="date" class="form-control form-control-sm font-monospace bg-white" value="<?= e($currentDate) ?>" onchange="location.href='?date=' + this.value">
        </div>
    </div>

    <?php if (empty($schedules)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-check2-circle fs-1 text-success mb-3"></i>
            <h5 class="fw-bold text-dark">All Caught Up!</h5>
            <p class="text-muted small mb-0">No pending inspections currently assigned to your duty roster for <?= e(date('d M Y', strtotime($currentDate))) ?>.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($schedules as $s): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow transition">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small px-2 py-1">
                                <?= e($s['category'] ?? 'Hygiene') ?>
                            </span>
                            <?php $badge = $statusBadges[$s['status']] ?? 'bg-secondary text-white'; ?>
                            <span class="badge <?= $badge ?> small px-2 py-1">
                                <?= e(ucfirst($s['status'])) ?>
                            </span>
                        </div>

                        <h6 class="fw-bold text-navy-dark mb-1"><?= e($s['template_name']) ?></h6>
                        <div class="small font-monospace text-muted mb-3"><?= e($s['template_code']) ?> (v<?= e((string)$s['version_number']) ?>)</div>

                        <div class="bg-light p-2 rounded-3 small mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-hospital me-2 text-primary"></i>
                                <span class="fw-semibold text-dark">Ward:</span>
                                <span class="ms-1"><?= e($s['ward_name']) ?></span>
                            </div>
                            <?php if (!empty($s['location_name'])): ?>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <span class="fw-semibold text-dark">Location:</span>
                                    <span class="ms-1"><?= e($s['location_name']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($s['shift_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock me-2 text-primary"></i>
                                    <span class="fw-semibold text-dark">Shift:</span>
                                    <span class="ms-1"><?= e($s['shift_name']) ?> (<?= substr($s['shift_start'], 0, 5) ?> - <?= substr($s['shift_end'], 0, 5) ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto">
                            <?php if ($s['status'] === 'completed'): ?>
                                <a href="<?= url("/inspections/{$s['inspection_id']}") ?>" class="btn btn-outline-success btn-sm w-100 rounded-3">
                                    <i class="bi bi-file-earmark-check me-1"></i> View Submitted Report
                                </a>
                            <?php else: ?>
                                <a href="<?= url("/inspections/execute/{$s['id']}") ?>" class="btn btn-primary btn-sm w-100 rounded-3 shadow-sm">
                                    <i class="bi bi-play-circle me-1"></i> <?= $s['status'] === 'in_progress' ? 'Continue Inspection' : 'Start Inspection' ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
