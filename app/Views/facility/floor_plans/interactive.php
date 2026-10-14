<?php
/**
 * Interactive Floor Plan Viewer View
 * High-performance vector SVG hospital floor map with real-time inspection status overlay,
 * pan/zoom, room inspector drawer, and status update actions.
 */
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/buildings') ?>">Buildings</a></li>
                <li class="breadcrumb-item active" aria-current="page">Interactive Floor Plan</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Interactive Hospital Floor Plan</h1>
        <p class="text-muted small mb-0">Sheth L.G. General Hospital &bull; Real-Time Multi-Floor Vector Blueprints</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($activeFloor)): ?>
            <a href="<?= url('/floors/' . $activeFloor['id']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-gear me-1"></i> Floor Settings
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" id="btnRefreshFloorData" title="Refresh Live Status">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
</div>

<!-- Building & Floor Switcher Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?= url('/floor-plans') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small text-muted fw-semibold mb-1">Select Building Wing</label>
                <select class="form-select" name="building_id" id="planBuildingSelect" onchange="this.form.submit()">
                    <?php foreach ($buildings as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $selectedBuildingId == $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['building_name']) ?> (<?= e($b['building_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted fw-semibold mb-1">Select Floor Level</label>
                <select class="form-select" name="floor_id" id="planFloorSelect" onchange="this.form.submit()">
                    <?php if (empty($floors)): ?>
                        <option value="">No floors in this building</option>
                    <?php else: ?>
                        <?php foreach ($floors as $fl): ?>
                            <option value="<?= $fl['id'] ?>" <?= $selectedFloorId == $fl['id'] ? 'selected' : '' ?>>
                                Level <?= (int)$fl['floor_number'] ?> &bull; <?= e($fl['floor_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="d-flex align-items-center justify-content-between w-100 bg-light rounded px-3 py-2 border">
                    <div>
                        <span class="text-muted small d-block">ACTIVE LEVEL</span>
                        <span class="fw-bold text-dark"><?= !empty($activeFloor) ? e($activeFloor['floor_name']) : 'N/A' ?></span>
                    </div>
                    <?php if (!empty($activeFloor) && $activeFloor['status'] === 'active'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i> Active
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Live Inspection Status Legend & KPI Ribbon -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 status-pill-card" data-status-filter="all">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-muted small fw-semibold">TOTAL ZONES</span>
                <i class="bi bi-grid text-muted"></i>
            </div>
            <div class="h3 fw-bold text-dark mb-0 mt-1"><?= (int)($summary['total_areas'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 border-bottom border-success border-4 status-pill-card" data-status-filter="compliant">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-success small fw-semibold">COMPLIANT</span>
                <span class="badge rounded-pill bg-success-subtle text-success">✓</span>
            </div>
            <div class="h3 fw-bold text-success mb-0 mt-1"><?= (int)($summary['compliant_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 border-bottom border-warning border-4 status-pill-card" data-status-filter="warning">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-warning small fw-semibold">WARNING</span>
                <span class="badge rounded-pill bg-warning-subtle text-warning">!</span>
            </div>
            <div class="h3 fw-bold text-warning mb-0 mt-1"><?= (int)($summary['warning_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 border-bottom border-danger border-4 status-pill-card" data-status-filter="critical">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-danger small fw-semibold">CRITICAL</span>
                <span class="badge rounded-pill bg-danger-subtle text-danger">✕</span>
            </div>
            <div class="h3 fw-bold text-danger mb-0 mt-1"><?= (int)($summary['critical_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 border-bottom border-indigo border-4 status-pill-card" data-status-filter="pending" style="border-bottom-color: #4f46e5 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <span class="small fw-semibold text-primary">PENDING</span>
                <span class="badge rounded-pill bg-primary-subtle text-primary">⏳</span>
            </div>
            <div class="h3 fw-bold text-primary mb-0 mt-1"><?= (int)($summary['pending_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm p-3 border-bottom border-secondary border-4 status-pill-card" data-status-filter="inactive">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary small fw-semibold">INACTIVE</span>
                <span class="badge rounded-pill bg-secondary-subtle text-secondary">⊘</span>
            </div>
            <div class="h3 fw-bold text-secondary mb-0 mt-1"><?= (int)($summary['inactive_count'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Main Canvas & Inspector Drawer -->
<div class="row g-4">
    <!-- SVG Viewport Card -->
    <div class="col-lg-8 col-xl-9">
        <div class="card border-0 shadow-sm position-relative overflow-hidden">
            <!-- Viewport Control Overlay -->
            <div class="position-absolute top-0 start-0 m-3 z-3 d-flex gap-1 bg-white p-1 rounded shadow-sm border">
                <button type="button" class="btn btn-sm btn-light border-0" id="btnZoomIn" title="Zoom In">
                    <i class="bi bi-zoom-in"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light border-0" id="btnZoomOut" title="Zoom Out">
                    <i class="bi bi-zoom-out"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light border-0" id="btnResetZoom" title="Reset View">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>

            <div class="position-absolute top-0 end-0 m-3 z-3">
                <span class="badge bg-white text-dark shadow-sm border py-2 px-3 fw-semibold">
                    <i class="bi bi-cursor-fill text-primary me-1"></i> Click any room to inspect
                </span>
            </div>

            <!-- Floor Plan Canvas Container -->
            <div class="card-body p-2 bg-light text-center" style="min-height: 540px; display: flex; align-items: center; justify-content: center; overflow: hidden;" id="planContainer">
                <div id="svgPanWrapper" style="transform-origin: center center; transition: transform 0.2s ease; width: 100%; max-width: 960px;">
                    <!-- Embedded SVG Floor Plan -->
                    <?php
                        $svgPath = dirname(__DIR__, 4) . '/public/assets/img/sample_floor_plan.svg';
                        if (file_exists($svgPath)) {
                            echo file_get_contents($svgPath);
                        } else {
                            echo '<div class="alert alert-warning">Sample floor plan blueprint file not found.</div>';
                        }
                    ?>
                </div>
            </div>

            <!-- Canvas Footer Legend -->
            <div class="card-footer bg-white border-top py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-semibold text-muted">Legend:</span>
                    <span class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #198754;"></span>
                        Compliant
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #ffc107;"></span>
                        Warning
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #dc3545;"></span>
                        Critical
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #4f46e5;"></span>
                        Pending Review
                    </span>
                </div>
                <div class="text-muted">
                    <i class="bi bi-mouse me-1"></i> Pan &amp; Zoom enabled
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Interactive Room Inspector Drawer -->
    <div class="col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px;" id="roomInspectorCard">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <h6 class="card-title fw-bold mb-0" id="inspectorTitle">Room Zone Inspector</h6>
                </div>
                <span class="badge bg-white text-primary" id="inspectorRoomCode">SELECT</span>
            </div>
            <div class="card-body p-4">
                <div id="inspectorDefaultState" class="text-center py-4 text-muted">
                    <i class="bi bi-hand-index-thumb display-4 d-block mb-2 text-primary opacity-50"></i>
                    <h6 class="fw-bold text-dark">No Area Selected</h6>
                    <p class="small mb-0">Click any ward, ICU cubicle, or nursing station on the interactive floor blueprint to inspect.</p>
                </div>

                <div id="inspectorActiveState" style="display: none;">
                    <div class="mb-3">
                        <span class="badge" id="inspectorStatusBadge">Compliant</span>
                        <h5 class="fw-bold text-dark mt-2 mb-0" id="inspectorWardName">Zone Name</h5>
                        <small class="text-muted" id="inspectorWardType">Ward Specialty</small>
                    </div>

                    <div class="list-group list-group-flush border-top border-bottom mb-3 small">
                        <div class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Bed Capacity:</span>
                            <span class="fw-bold text-dark" id="inspectorBeds">0 beds</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Checkpoints:</span>
                            <span class="fw-bold text-primary" id="inspectorCheckpoints">0 locations</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Active Complaints:</span>
                            <span class="badge bg-danger-subtle text-danger" id="inspectorComplaints">0 Open</span>
                        </div>
                    </div>

                    <!-- Quick Status Modulation (Admin/Supervisor) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-2">Update Inspection Status:</label>
                        <div class="d-grid gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success text-start btnUpdateStatus" data-status="compliant">
                                <i class="bi bi-check-circle-fill me-1"></i> Mark Compliant
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning text-start btnUpdateStatus" data-status="warning">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Flag Warning
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger text-start btnUpdateStatus" data-status="critical">
                                <i class="bi bi-x-circle-fill me-1"></i> Mark Critical Issue
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary text-start btnUpdateStatus" data-status="pending">
                                <i class="bi bi-hourglass-split me-1"></i> Mark Pending
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2 pt-2 border-top">
                        <a href="#" class="btn btn-primary btn-sm" id="inspectorWardLink">
                            <i class="bi bi-eye me-1"></i> Open Full Ward Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentZoom = 1;
    let selectedZone = null;
    const panWrapper = document.getElementById('svgPanWrapper');
    const defaultState = document.getElementById('inspectorDefaultState');
    const activeState = document.getElementById('inspectorActiveState');
    const inspectorTitle = document.getElementById('inspectorTitle');
    const inspectorRoomCode = document.getElementById('inspectorRoomCode');
    const inspectorStatusBadge = document.getElementById('inspectorStatusBadge');
    const inspectorWardName = document.getElementById('inspectorWardName');
    const inspectorWardType = document.getElementById('inspectorWardType');
    const inspectorBeds = document.getElementById('inspectorBeds');
    const inspectorCheckpoints = document.getElementById('inspectorCheckpoints');
    const inspectorComplaints = document.getElementById('inspectorComplaints');
    const inspectorWardLink = document.getElementById('inspectorWardLink');

    // Zoom handlers
    document.getElementById('btnZoomIn').addEventListener('click', () => {
        if (currentZoom < 2.0) {
            currentZoom += 0.15;
            panWrapper.style.transform = `scale(${currentZoom})`;
        }
    });

    document.getElementById('btnZoomOut').addEventListener('click', () => {
        if (currentZoom > 0.6) {
            currentZoom -= 0.15;
            panWrapper.style.transform = `scale(${currentZoom})`;
        }
    });

    document.getElementById('btnResetZoom').addEventListener('click', () => {
        currentZoom = 1;
        panWrapper.style.transform = 'scale(1)';
    });

    // Room click events on SVG
    const zoneGroups = document.querySelectorAll('.floor-zone-group');
    zoneGroups.forEach(group => {
        group.addEventListener('click', function(e) {
            e.stopPropagation();

            // Clear previous selection highlight
            document.querySelectorAll('.room-zone.selected').forEach(el => el.classList.remove('selected'));

            const rect = this.querySelector('.room-zone');
            if (rect) rect.classList.add('selected');

            selectedZone = {
                id: this.getAttribute('data-id'),
                code: this.getAttribute('data-code'),
                name: this.getAttribute('data-name'),
                type: this.getAttribute('data-type'),
                capacity: this.getAttribute('data-capacity')
            };

            // Determine status from classes
            let currentStatus = 'pending';
            if (rect.classList.contains('status-compliant')) currentStatus = 'compliant';
            else if (rect.classList.contains('status-warning')) currentStatus = 'warning';
            else if (rect.classList.contains('status-critical')) currentStatus = 'critical';
            else if (rect.classList.contains('status-inactive')) currentStatus = 'inactive';

            // Show active state in inspector
            defaultState.style.display = 'none';
            activeState.style.display = 'block';

            inspectorRoomCode.textContent = selectedZone.code;
            inspectorWardName.textContent = selectedZone.name;
            inspectorWardType.textContent = (selectedZone.type || 'General').toUpperCase().replace('_', ' ');
            inspectorBeds.textContent = selectedZone.capacity + ' Inpatient Beds';
            inspectorCheckpoints.textContent = '4 Checkpoints';
            inspectorComplaints.textContent = currentStatus === 'critical' ? '1 Critical SLA' : '0 Active';
            inspectorWardLink.href = '<?= url('/wards/') ?>' + selectedZone.id;

            updateBadgeDisplay(currentStatus);
        });
    });

    function updateBadgeDisplay(status) {
        inspectorStatusBadge.className = 'badge text-uppercase';
        if (status === 'compliant') {
            inspectorStatusBadge.classList.add('bg-success');
            inspectorStatusBadge.textContent = '✓ Compliant';
        } else if (status === 'warning') {
            inspectorStatusBadge.classList.add('bg-warning', 'text-dark');
            inspectorStatusBadge.textContent = '! Warning';
        } else if (status === 'critical') {
            inspectorStatusBadge.classList.add('bg-danger');
            inspectorStatusBadge.textContent = '✕ Critical';
        } else if (status === 'inactive') {
            inspectorStatusBadge.classList.add('bg-secondary');
            inspectorStatusBadge.textContent = '⊘ Inactive';
        } else {
            inspectorStatusBadge.classList.add('bg-primary');
            inspectorStatusBadge.textContent = '⏳ Pending Inspection';
        }
    }

    // Quick status update buttons
    document.querySelectorAll('.btnUpdateStatus').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!selectedZone) return;
            const newStatus = this.getAttribute('data-status');
            const zoneGroup = document.querySelector(`.floor-zone-group[data-id="${selectedZone.id}"]`);
            if (zoneGroup) {
                const rect = zoneGroup.querySelector('.room-zone');
                if (rect) {
                    rect.classList.remove('status-compliant', 'status-warning', 'status-critical', 'status-pending', 'status-inactive');
                    rect.classList.add('status-' + newStatus);
                }
            }
            updateBadgeDisplay(newStatus);
            alert(`Inspection zone ${selectedZone.code} marked as ${newStatus.toUpperCase()}`);
        });
    });

    // Refresh button
    document.getElementById('btnRefreshFloorData').addEventListener('click', function() {
        location.reload();
    });
});
</script>
