<div class="row justify-content-center py-3">
    <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h5 fw-bold text-navy mb-1">Update Account Password</h2>
                <p class="text-secondary small mb-0">Enforce enterprise password complexity standards.</p>
            </div>
            <a href="<?= url('/profile') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Profile
            </a>
        </div>

        <div class="card hospital-card shadow-sm border-0">
            <div class="card-body p-4 p-sm-5">
                <form action="<?= url('/change-password') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Current Password -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold text-navy small">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="current_password" 
                               name="current_password" 
                               required 
                               autofocus>
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold text-navy small">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password" 
                               name="new_password" 
                               required 
                               minlength="8">
                        <div class="form-text small" style="font-size: 0.75rem;">
                            Minimum 8 characters with at least one uppercase letter, one lowercase letter, and one number.
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold text-navy small">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required 
                               minlength="8">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-navy py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i>Save New Password
                        </button>
                        <a href="<?= url('/profile') ?>" class="btn btn-light border py-2 text-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
