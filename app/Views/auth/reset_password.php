<?php
/** @var string $appName */
/** @var string $token */
/** @var array $tokenRecord */
?>
<div class="row justify-content-center py-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
        
        <!-- Hospital Brand Header -->
        <div class="text-center mb-4">
            <div class="hospital-badge mx-auto mb-3 shadow" style="width: 58px; height: 58px; border-radius: 14px;">
                <i class="bi bi-shield-check fs-2 text-white"></i>
            </div>
            <h2 class="h5 fw-bold text-navy mb-1"><?= e($appName) ?></h2>
            <p class="text-secondary small mb-0">Set New Account Password</p>
            <div class="badge bg-success bg-opacity-10 text-success mt-2 px-3 py-1 border border-success border-opacity-25">
                <i class="bi bi-check-circle me-1"></i>Token Verified for <?= e($tokenRecord['full_name'] ?? 'User') ?>
            </div>
        </div>

        <!-- Reset Password Card -->
        <div class="card hospital-card shadow-sm border-0">
            <div class="card-body p-4 p-sm-5">
                <form action="<?= url('/reset-password') ?>" method="POST" id="resetPasswordForm" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <!-- Identity confirmation badge -->
                    <div class="mb-3 p-2 bg-light rounded border text-muted small">
                        <div><strong class="text-navy">Employee:</strong> <?= e($tokenRecord['employee_id'] ?? '') ?></div>
                        <div><strong class="text-navy">Email:</strong> <?= e($tokenRecord['email'] ?? '') ?></div>
                    </div>

                    <!-- New Password Input -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold text-navy small">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control bg-light border-start-0 ps-0" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Minimum 8 chars, mixed case, number, symbol" 
                                   minlength="8"
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <!-- Confirm Password Input -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold text-navy small">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control bg-light border-start-0 ps-0" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Re-enter new password" 
                                   minlength="8"
                                   required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-navy py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i>Update Password & Sign In
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="<?= url('/login') ?>" class="text-decoration-none small text-muted">
                            Cancel and Return to Sign-In
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Guard Notice -->
        <div class="alert alert-secondary mt-3 py-2 px-3 small border-0 text-muted bg-light text-center" style="font-size: 0.75rem;">
            <i class="bi bi-lock-fill me-1 text-primary"></i>
            This token will be permanently marked used upon submission. Any prior active sessions will require re-authentication.
        </div>

    </div>
</div>
