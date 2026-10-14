<?php
/** @var string $appName */
/** @var string $appSubtitle */
?>
<header class="hospital-navbar navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <!-- Sidebar Toggle (Mobile & Tablet) -->
        <button class="btn btn-link text-white d-lg-none me-2 p-0" id="sidebarToggle" type="button" aria-label="Toggle navigation">
            <i class="bi bi-list fs-3"></i>
        </button>

        <!-- Brand Title & Subtitle -->
        <div class="navbar-brand-wrapper d-flex align-items-center">
            <div class="hospital-badge me-2">
                <i class="bi bi-hospital fs-4 text-white"></i>
            </div>
            <div>
                <h1 class="hospital-title mb-0"><?= e($appName) ?></h1>
                <div class="hospital-subtitle"><?= e($appSubtitle) ?></div>
            </div>
        </div>

        <!-- Top Right Actions -->
        <div class="ms-auto d-flex align-items-center gap-3">
            <!-- Asia/Kolkata Live Clock Display -->
            <div class="d-none d-md-flex align-items-center text-white-50 bg-navy-dark px-3 py-1 rounded-pill border border-secondary border-opacity-25 small">
                <i class="bi bi-clock me-2 text-info"></i>
                <span id="liveClock" class="fw-medium text-white"><?= now_kolkata('d M Y, h:i:s A') ?></span>
                <span class="badge bg-primary bg-opacity-25 text-info ms-2">IST</span>
            </div>

            <!-- Health Status Quick Link -->
            <a href="/health" class="btn btn-sm btn-outline-light rounded-pill px-3 d-none d-sm-inline-flex align-items-center" title="System Health">
                <span class="pulse-dot me-2"></span>
                <span>System Health</span>
            </a>

            <!-- Notification Bell Demo -->
            <div class="dropdown">
                <button class="btn btn-navy-icon position-relative text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="min-width: 280px;">
                    <li class="dropdown-header fw-bold text-dark border-bottom pb-2">Hospital Alerts & SLA</li>
                    <li><a class="dropdown-item py-2 small" href="#"><i class="bi bi-info-circle text-primary me-2"></i>Phase 1: Project Architecture ready</a></li>
                    <li><a class="dropdown-item py-2 small" href="#"><i class="bi bi-check-circle text-success me-2"></i>Relational database schema prepared</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center small text-primary fw-semibold" href="#">View Audit Logs</a></li>
                </ul>
            </div>

            <!-- User Profile & Authentication State -->
            <?php if ($authUser = \App\Core\Auth::user()): ?>
                <div class="dropdown ps-2 border-start border-white border-opacity-10">
                    <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar-circle me-2">
                            <i class="bi bi-person text-white"></i>
                        </div>
                        <div class="d-none d-xl-block text-start me-1">
                            <div class="text-white fw-bold small line-height-1"><?= e($authUser['name']) ?></div>
                            <small class="text-white-50" style="font-size: 0.72rem;"><?= ucfirst($authUser['role']) ?> &bull; <?= e($authUser['employee_id']) ?></small>
                        </div>
                        <i class="bi bi-chevron-down text-white-50 small ms-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="min-width: 220px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-navy small"><?= e($authUser['name']) ?></div>
                            <div class="text-muted" style="font-size: 0.72rem;"><?= e($authUser['email']) ?></div>
                            <span class="badge bg-primary mt-1 text-uppercase" style="font-size: 0.65rem;"><?= e($authUser['role']) ?></span>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 small" href="<?= url('/profile') ?>">
                                <i class="bi bi-person me-2 text-primary"></i>My Facility Profile
                            </a>
                        </li>
                        <?php if ($authUser['role'] === 'supervisor'): ?>
                            <li>
                                <a class="dropdown-item py-2 small" href="<?= url('/supervisor/overview') ?>">
                                    <i class="bi bi-card-checklist me-2 text-info"></i>Assigned Work
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item py-2 small" href="<?= url('/change-password') ?>">
                                <i class="bi bi-key me-2 text-warning"></i>Change Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= url('/logout') ?>" method="POST" class="px-2">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="ps-2 border-start border-white border-opacity-10">
                    <a href="<?= url('/login') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
