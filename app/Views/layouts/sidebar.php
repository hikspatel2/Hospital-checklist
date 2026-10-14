<?php
/** @var string $currentPath */
$currentPath = $currentPath ?? '/';

$menuGroups = [
    'OPERATIONAL CORE' => [
        ['title' => 'Dashboard & Matrix', 'path' => '/', 'icon' => 'bi-speedometer2', 'badge' => null],
        ['title' => 'Interactive Floor Plan', 'path' => '/floor-plans', 'icon' => 'bi-map', 'badge' => 'Visual'],
        ['title' => 'Buildings & Floors', 'path' => '/buildings', 'icon' => 'bi-building', 'badge' => null],
        ['title' => 'Wards & Checkpoints', 'path' => '/wards', 'icon' => 'bi-hospital', 'badge' => null],
    ],
    'INSPECTIONS & QUALITY' => array_filter([
        ['title' => 'Checklist Templates', 'path' => '/checklists', 'icon' => 'bi-card-checklist', 'badge' => null],
        ['title' => 'Inspection Scheduling', 'path' => '/schedules', 'icon' => 'bi-calendar-check', 'badge' => null],
        ['title' => 'My Inspections', 'path' => '/inspections/my', 'icon' => 'bi-clipboard-check', 'badge' => 'Tasks'],
        ['title' => 'Complaints & SLA', 'path' => '/complaints', 'icon' => 'bi-exclamation-triangle', 'badge' => 'SLA'],
        \App\Core\Auth::isAdmin()
            ? ['title' => 'SLA Policies', 'path' => '/sla-policies', 'icon' => 'bi-stopwatch', 'badge' => 'Admin']
            : null,
        ['title' => 'Floor/Ward QR Codes', 'path' => '/qr-codes', 'icon' => 'bi-qr-code', 'badge' => null],
    ]),
    'STAFF & ATTENDANCE' => array_filter([
        \App\Core\Auth::isAdmin() 
            ? ['title' => 'Personnel Management', 'path' => '/users', 'icon' => 'bi-people-fill', 'badge' => 'Admin']
            : null,
        \App\Core\Auth::isSupervisor() 
            ? ['title' => 'My Assigned Work', 'path' => '/supervisor/overview', 'icon' => 'bi-card-checklist', 'badge' => 'Roster']
            : null,
        ['title' => 'Facility Rosters', 'path' => '/supervisors', 'icon' => 'bi-people', 'badge' => null],
        ['title' => 'Shift Rosters', 'path' => '/shift-assignments', 'icon' => 'bi-calendar-range', 'badge' => null],
        ['title' => 'Shift Timings', 'path' => '/shifts', 'icon' => 'bi-clock-history', 'badge' => null],
        ['title' => 'QR + GPS Attendance', 'path' => '/attendance', 'icon' => 'bi-geo-alt', 'badge' => 'GPS'],
        \App\Core\Auth::isAdmin()
            ? ['title' => 'Attendance Audit', 'path' => '/admin/attendance', 'icon' => 'bi-shield-check', 'badge' => 'Audit']
            : null,
    ]),
    'ANALYTICS & GOVERNANCE' => array_filter([
        ['title' => 'Reports & Export', 'path' => '/reports', 'icon' => 'bi-file-earmark-bar-graph', 'badge' => 'Export'],
        \App\Core\Auth::isAdmin()
            ? ['title' => 'Audit Trail Logs', 'path' => '/audit-logs', 'icon' => 'bi-shield-check', 'badge' => 'Append-only']
            : null,
        \App\Core\Auth::isAdmin()
            ? ['title' => 'Hospital Settings', 'path' => '/settings', 'icon' => 'bi-gear', 'badge' => 'Hardened']
            : null,
        \App\Core\Auth::isAdmin()
            ? ['title' => 'Backup & Disaster Recovery', 'path' => '/backups', 'icon' => 'bi-cloud-arrow-up', 'badge' => 'Hostinger']
            : null,
    ]),
];
?>
<aside class="hospital-sidebar" id="hospitalSidebar">
    <div class="sidebar-header px-4 py-3 d-flex align-items-center justify-content-between border-bottom border-white border-opacity-10">
        <div class="d-flex align-items-center">
            <div class="facility-icon-box me-2">
                <i class="bi bi-shield-plus text-primary fs-5"></i>
            </div>
            <div>
                <div class="text-white fw-bold small text-uppercase tracking-wider">Facility Portal</div>
                <div class="text-white-50" style="font-size: 0.7rem;">Hospital Operations</div>
            </div>
        </div>
        <button class="btn btn-sm btn-link text-white-50 d-lg-none p-0" id="sidebarCloseBtn" type="button">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <div class="sidebar-menu-wrapper py-3">
        <?php foreach ($menuGroups as $groupLabel => $items): ?>
            <div class="sidebar-group-title text-uppercase px-4 pt-3 pb-1 text-white-50 fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                <?= e($groupLabel) ?>
            </div>
            <ul class="nav flex-column mb-2">
                <?php foreach ($items as $item): ?>
                    <?php
                        $isActive = ($currentPath === $item['path']) || ($item['path'] !== '/' && str_starts_with($currentPath, $item['path']));
                    ?>
                    <li class="nav-item px-3 mb-1">
                        <a class="nav-link sidebar-link d-flex align-items-center justify-content-between rounded-3 px-3 py-2 <?= $isActive ? 'active' : '' ?>" href="<?= url($item['path']) ?>">
                            <div class="d-flex align-items-center">
                                <i class="bi <?= e($item['icon']) ?> sidebar-icon me-3"></i>
                                <span class="sidebar-text"><?= e($item['title']) ?></span>
                            </div>
                            <?php if ($item['badge']): ?>
                                <span class="badge badge-subtle <?= $isActive ? 'bg-white text-primary' : 'bg-secondary bg-opacity-25 text-white-50' ?> rounded-pill" style="font-size: 0.65rem;">
                                    <?= e($item['badge']) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>

    <!-- Sidebar Bottom Status Footer -->
    <div class="sidebar-footer p-3 border-top border-white border-opacity-10 mt-auto">
        <div class="bg-navy-dark p-2 rounded-3 d-flex align-items-center justify-content-between small">
            <div>
                <div class="text-white-50" style="font-size: 0.7rem;">System Version</div>
                <div class="text-white fw-semibold" style="font-size: 0.8rem;">v11.0 (Phase 11)</div>
            </div>
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">
                Verified
            </span>
        </div>
    </div>
</aside>
