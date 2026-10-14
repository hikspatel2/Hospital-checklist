<?php
/** @var string $appName */
/** @var string $appSubtitle */
?>
<div class="row justify-content-center py-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
        
        <!-- Hospital Brand Header -->
        <div class="text-center mb-4">
            <div class="hospital-badge mx-auto mb-3 shadow" style="width: 58px; height: 58px; border-radius: 14px;">
                <i class="bi bi-hospital fs-2 text-white"></i>
            </div>
            <h2 class="h5 fw-bold text-navy mb-1"><?= e($appName) ?></h2>
            <p class="text-secondary small mb-0"><?= e($appSubtitle) ?></p>
            <div class="badge bg-navy bg-opacity-10 text-primary mt-2 px-3 py-1 border border-primary border-opacity-25">
                <i class="bi bi-shield-lock me-1"></i>Secure Personnel Sign-In
            </div>
        </div>

        <!-- Login Card -->
        <div class="card hospital-card shadow-sm border-0">
            <div class="card-body p-4 p-sm-5">
                <form action="<?= url('/login') ?>" method="POST" id="loginForm" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Identity Input -->
                    <div class="mb-3">
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
                                   placeholder="e.g. EMP-ADMIN-01 or name@shethlg.local" 
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="password" class="form-label fw-semibold text-navy small mb-0">
                                Account Password <span class="text-danger">*</span>
                            </label>
                            <a href="<?= url('/forgot-password') ?>" class="text-decoration-none small text-navy" style="font-size: 0.78rem;">
                                Forgot password?
                            </a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" 
                                   class="form-control bg-light border-start-0 border-end-0 ps-0" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter account password" 
                                   required>
                            <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn">
                                <i class="bi bi-eye" id="toggleEyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-navy py-2 fw-semibold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Portal
                        </button>
                    </div>

                    <!-- Rate Limiting & Security Notice -->
                    <div class="alert alert-secondary py-2 px-3 small border-0 text-muted bg-light mb-0" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle me-1 text-primary"></i>
                        Accounts lock for 15 minutes after 5 unsuccessful attempts. Public registration is prohibited.
                    </div>
                </form>
            </div>
        </div>

        <!-- Demo Credentials Panel (Development / Staging Only) -->
        <?php if (config('app.env') !== 'production'): ?>
            <div class="card mt-4 border border-info border-opacity-25 bg-white shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-navy" style="font-size: 0.72rem;">
                            <i class="bi bi-key-fill text-warning me-1"></i>Development Seed Logins
                        </span>
                        <span class="badge bg-warning bg-opacity-25 text-dark" style="font-size: 0.65rem;">Dev Demo</span>
                    </div>
                    <div class="row g-2 small text-secondary">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <strong class="d-block text-navy">Administrator</strong>
                                <code>admin@shethlg.local</code><br>
                                Pass: <code>Admin@12345</code>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <strong class="d-block text-navy">Supervisor</strong>
                                <code>supervisor1@shethlg.local</code><br>
                                Pass: <code>Supervisor@123</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Notice -->
        <div class="text-center mt-4 text-muted small">
            <span>&copy; <?= date('Y') ?> Sheth L.G. General Hospital, Ahmedabad</span><br>
            <span style="font-size: 0.72rem;">Municipal Corporation Health & Facility Operations</span>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('toggleEyeIcon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            eyeIcon.classList.toggle('bi-eye', !isPassword);
            eyeIcon.classList.toggle('bi-eye-slash', isPassword);
        });
    }
});
</script>
