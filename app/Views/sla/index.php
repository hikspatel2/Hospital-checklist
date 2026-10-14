<?php
/** @var array $policies */
/** @var array $categories */
/** @var array $priorities */
?>
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">SLA Engine</span>
                <h4 class="fw-bold mb-0 text-navy-dark">Service Level Agreement (SLA) Policies</h4>
            </div>
            <p class="text-muted small mb-0 mt-1">Configure automated response and resolution deadlines categorized by medical severity and facility discipline.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/complaints') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Complaints
            </a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createPolicyModal">
                    <i class="bi bi-plus-circle me-1"></i> New SLA Policy
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- SLA Policies Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Policy Name</th>
                            <th>Category Scope</th>
                            <th>Priority</th>
                            <th>Response Target</th>
                            <th>Resolution Target</th>
                            <th>Escalation Email</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($policies)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-stopwatch fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No custom SLA policies found. System will use default hospital standards.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($policies as $p): ?>
                                <?php 
                                $prioInfo = $priorities[$p['priority']] ?? ['label' => ucfirst($p['priority']), 'badge' => 'bg-secondary'];
                                $catLabel = ($p['category'] === 'all' || empty($p['category'])) 
                                    ? 'All Categories (Default)' 
                                    : ($categories[$p['category']] ?? ucfirst($p['category']));
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-navy-dark"><?= e($p['policy_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= e($catLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $prioInfo['badge'] ?> px-2 py-1">
                                            <?= e($prioInfo['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= (int)$p['response_time_hours'] ?> hrs</strong>
                                        <span class="small text-muted d-block"><?= (int)$p['response_time_hours'] * 60 ?> mins</span>
                                    </td>
                                    <td>
                                        <strong class="text-danger"><?= (int)$p['resolution_time_hours'] ?> hrs</strong>
                                    </td>
                                    <td>
                                        <span class="small text-muted font-monospace"><?= e($p['escalation_email'] ?: 'None') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <?php if (\App\Core\Auth::isAdmin()): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 py-1 edit-policy-btn"
                                                data-id="<?= $p['id'] ?>"
                                                data-name="<?= e($p['policy_name']) ?>"
                                                data-category="<?= e($p['category']) ?>"
                                                data-priority="<?= e($p['priority']) ?>"
                                                data-response="<?= $p['response_time_hours'] ?>"
                                                data-resolution="<?= $p['resolution_time_hours'] ?>"
                                                data-email="<?= e($p['escalation_email'] ?? '') ?>"
                                                data-bs-toggle="modal" data-bs-target="#createPolicyModal">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
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
</div>

<!-- MODAL: Create / Edit Policy -->
<div class="modal fade" id="createPolicyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= url('/sla-policies/store') ?>" method="POST" id="policyForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="policy_id" value="">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy-dark" id="modalTitle">Configure SLA Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Policy Name <span class="text-danger">*</span></label>
                        <input type="text" name="policy_name" id="policy_name" class="form-control rounded-3" placeholder="e.g. Critical Infection Control SLA" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Category Scope</label>
                            <select name="category" id="policy_category" class="form-select rounded-3">
                                <option value="all">All Categories (Default)</option>
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?= e($catKey) ?>"><?= e($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Priority <span class="text-danger">*</span></label>
                            <select name="priority" id="policy_priority" class="form-select rounded-3" required>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Response Target (Hours) <span class="text-danger">*</span></label>
                            <input type="number" name="response_time_hours" id="policy_response" class="form-control rounded-3" min="1" max="168" value="4" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Resolution Target (Hours) <span class="text-danger">*</span></label>
                            <input type="number" name="resolution_time_hours" id="policy_resolution" class="form-control rounded-3" min="1" max="720" value="24" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Escalation Alert Email</label>
                        <input type="email" name="escalation_email" id="policy_email" class="form-control rounded-3" placeholder="e.g. escalations@shethlg.local">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Save SLA Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-policy-btn');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('policyForm');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.innerText = 'Edit SLA Policy';
            document.getElementById('policy_id').value = this.dataset.id;
            document.getElementById('policy_name').value = this.dataset.name;
            document.getElementById('policy_category').value = this.dataset.category || 'all';
            document.getElementById('policy_priority').value = this.dataset.priority;
            document.getElementById('policy_response').value = this.dataset.response;
            document.getElementById('policy_resolution').value = this.dataset.resolution;
            document.getElementById('policy_email').value = this.dataset.email || '';
        });
    });

    const createModal = document.getElementById('createPolicyModal');
    createModal.addEventListener('hidden.bs.modal', function() {
        modalTitle.innerText = 'Configure SLA Policy';
        form.reset();
        document.getElementById('policy_id').value = '';
    });
});
</script>
