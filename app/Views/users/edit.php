<?php
/** @var array $user */
?>
<div class="row justify-content-center py-3">
    <div class="col-12 col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h4 fw-bold text-navy mb-1">Edit Personnel Profile</h2>
                <p class="text-secondary small mb-0">Update employee credentials, assigned department, and role authorization.</p>
            </div>
            <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Personnel List
            </a>
        </div>

        <div class="card hospital-card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form action="<?= url("/users/{$user['id']}") ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3 mb-3">
                        <!-- Employee ID (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-navy small">Employee ID</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?= e($user['employee_id']) ?>" readonly>
                            <div class="form-text small" style="font-size: 0.75rem;">Employee identifiers are immutable once issued.</div>
                        </div>

                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold text-navy small">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?= e($user['name']) ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold text-navy small">
                                Hospital Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= e($user['email']) ?>" 
                                   required>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold text-navy small">Contact Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Role -->
                        <div class="col-md-4">
                            <label for="role" class="form-label fw-semibold text-navy small">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="supervisor" <?= $user['role'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="col-md-4">
                            <label for="department" class="form-label fw-semibold text-navy small">Department / Wing</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="department" 
                                   name="department" 
                                   value="<?= e($user['department'] ?? '') ?>">
                        </div>

                        <!-- Designation -->
                        <div class="col-md-4">
                            <label for="designation" class="form-label fw-semibold text-navy small">Designation</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="designation" 
                                   name="designation" 
                                   value="<?= e($user['designation'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('/users') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-navy px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i>Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
