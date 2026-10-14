<?php
/** @var string $appName */
/** @var string $appSubtitle */
?>
<div class="row justify-content-center py-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
        
        <!-- Hospital Brand Header -->
        <div class="text-center mb-4">
            <div class="hospital-badge mx-auto mb-3 shadow" style="width: 58px; height: 58px; border-radius: 14px;">
                <i class="bi bi-shield-lock-fill fs-2 text-white"></i>
            </div>
            <h2 class="h5 fw-bold text-navy mb-1"><?= e($appName) ?></h2>
            <p class="text-secondary small mb-0">Secure Credential Recovery</p>
            <div class="badge bg-primary bg-opacity-10 text-primary mt-2 px-3 py-1 border border-primary border-opacity-25">
                <i class="bi bi-shield-check me-1"></i>High-Entropy Token Validation
            </div>
        </div>

        <!-- Forgot Password Card -->
        <div class="card hospital-card shadow-sm border-0">
            <div class="card-body p-4 p-sm-5">
                <p class="text-secondary small mb-4">
                    Enter your registered Employee ID or hospital email address. If verified, a single-use password reset link valid for <strong>15 minutes</strong> will be generated.
                </p>

                <form action="<?= url('/forgot-password') ?>" method="POST" id="forgotPasswordForm" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Identity Input -->
                    <div class="mb-4">
                        <label for="identity" class="form-label fw-semibold text-navy small">
                            Employee ID or Hospital Email <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <input type="text" 
                                   class="form-control bg-light border-start-0 ps-0" 
                                   id="identity" 
                                   name="identity" 
                                   placeholder="e.g. EMP-ADMIN-01 or admin@shethlg.local" 
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-navy py-2 fw-semibold shadow-sm">
                            <i class="bi bi-key me-2"></i>Generate Secure Reset Link
                        </button>
                    </div>

                    <!-- Back to Login -->
                    <div class="text-center">
                        <a href="<?= url('/login') ?>" class="text-decoration-none small text-navy fw-semibold">
                            <i class="bi bi-arrow-left me-1"></i>Back to Sign-In
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Guard Notice -->
        <div class="alert alert-secondary mt-3 py-2 px-3 small border-0 text-muted bg-light text-center" style="font-size: 0.75rem;">
            <i class="bi bi-shield-slash me-1 text-primary"></i>
            Reset requests are rate-limited to 3 per 15 minutes per IP address. Account presence is never revealed to prevent user enumeration.
        </div>

        <!-- Footer Notice -->
        <div class="text-center mt-4 text-muted small">
            <span>&copy; <?= date('Y') ?> Sheth L.G. General Hospital, Ahmedabad</span>
        </div>

    </div>
</div>
