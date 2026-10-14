<div class="row justify-content-center py-3">
    <div class="col-12 col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h4 fw-bold text-navy mb-1">Provision Hospital Personnel</h2>
                <p class="text-secondary small mb-0">Create authorized personnel accounts for facility supervisors and administrative officers.</p>
            </div>
            <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Personnel List
            </a>
        </div>

        <div class="card hospital-card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form action="<?= url('/users') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <!-- Employee ID -->
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label fw-semibold text-navy small">
                                Employee ID <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="employee_id" 
                                   name="employee_id" 
                                   placeholder="e.g. EMP-SUP-05" 
                                   required 
                                   autofocus>
                            <div class="form-text small" style="font-size: 0.75rem;">Unique municipal hospital employee identifier.</div>
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
                                   placeholder="e.g. Dr. Krunal Trivedi" 
                                   required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Hospital Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold text-navy small">
                                Hospital Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="e.g. ktrivedi@shethlg.local" 
                                   required>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold text-navy small">
                                Contact Phone Number
                            </label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="e.g. +91 98765 43210">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Role Selection -->
                        <div class="col-md-4">
                            <label for="role" class="form-label fw-semibold text-navy small">
                                System Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="supervisor" selected>Supervisor</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="col-md-4">
                            <label for="department" class="form-label fw-semibold text-navy small">
                                Department / Wing
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="department" 
                                   name="department" 
                                   placeholder="e.g. Quality, Sanitation, ICU">
                        </div>

                        <!-- Designation -->
                        <div class="col-md-4">
                            <label for="designation" class="form-label fw-semibold text-navy small">
                                Official Designation
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="designation" 
                                   name="designation" 
                                   placeholder="e.g. Senior Floor Supervisor">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Initial Password -->
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold text-navy small">
                                Initial Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Minimum 8 characters" 
                                   required 
                                   minlength="8">
                            <div class="form-text small" style="font-size: 0.75rem;">Employee must change this upon first login.</div>
                        </div>

                        <!-- Active State -->
                        <div class="col-md-6">
                            <label for="is_active" class="form-label fw-semibold text-navy small">
                                Initial Account Status
                            </label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="1" selected>Active (Immediate Login Enabled)</option>
                                <option value="0">Deactivated (Suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('/users') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-navy px-4 fw-semibold shadow-sm">
                            <i class="bi bi-person-check-fill me-1"></i>Provision Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
