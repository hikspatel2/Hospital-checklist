<?php
/**
 * Create Ward View
 * Provides cascaded building/floor hierarchy selection and validates unique ward codes.
 */
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/wards') ?>">Clinical Wards</a></li>
                <li class="breadcrumb-item active" aria-current="page">New Ward</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-hospital fs-4 me-2"></i>
                    <div>
                        <h5 class="card-title fw-bold mb-0">Add Clinical Ward / Department</h5>
                        <small class="text-white-50">Establish patient care zone, bed allotment, and unique QR code checkpoint</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?= url('/admin/wards') ?>" method="POST" id="createWardForm">
                    <?= csrf_field() ?>

                    <!-- Step 1: Hospital Facility Hierarchy -->
                    <h6 class="fw-bold text-primary text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                        1. Facility Hierarchy Placement
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="building_id" class="form-label fw-semibold">Building Wing <span class="text-danger">*</span></label>
                            <select class="form-select" id="building_id" name="building_id" required>
                                <option value="">-- Select Hospital Building --</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $selectedBuilding == $b['id'] ? 'selected' : '' ?>>
                                        <?= e($b['building_name']) ?> (<?= e($b['building_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="floor_id" class="form-label fw-semibold">Floor Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="floor_id" name="floor_id" required>
                                <option value="">-- Select Building First --</option>
                            </select>
                            <div class="form-text">Floors are dynamically loaded based on building selection.</div>
                        </div>
                    </div>

                    <!-- Step 2: Ward Parameters -->
                    <h6 class="fw-bold text-primary text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                        2. Ward Identity &amp; Capacity
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="ward_code" class="form-label fw-semibold">Ward Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="ward_code" name="ward_code" required placeholder="e.g. ICU-01, GW-3B" maxlength="50">
                            <div class="form-text">Unique uppercase identifier.</div>
                        </div>
                        <div class="col-md-8">
                            <label for="ward_name" class="form-label fw-semibold">Ward / Department Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ward_name" name="ward_name" required placeholder="e.g. Intensive Care Unit - A, Male Medical Ward" maxlength="150">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="ward_type" class="form-label fw-semibold">Specialty / Category</label>
                            <select class="form-select" id="ward_type" name="ward_type">
                                <option value="general">General Ward</option>
                                <option value="icu">Intensive Care Unit (ICU)</option>
                                <option value="emergency">Emergency / Trauma Care</option>
                                <option value="pediatric">Pediatric Ward</option>
                                <option value="maternity">Maternity &amp; NICU</option>
                                <option value="surgical">Post-Surgical Care</option>
                                <option value="opd">Outpatient Department (OPD)</option>
                                <option value="radiology">Radiology &amp; Imaging</option>
                                <option value="lab">Pathology Laboratory</option>
                                <option value="administrative">Administrative &amp; Duty</option>
                                <option value="other">Other Facility Zone</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bed_capacity" class="form-label fw-semibold">Inpatient Bed Capacity</label>
                            <input type="number" class="form-control" id="bed_capacity" name="bed_capacity" value="12" min="0" step="1">
                            <div class="form-text">Set 0 for outpatient, laboratories, or administrative departments.</div>
                        </div>
                    </div>

                    <div class="alert alert-light border d-flex align-items-center mb-4">
                        <i class="bi bi-qr-code text-primary fs-4 me-3"></i>
                        <div class="small text-muted">
                            A unique, cryptographically secure <strong>QR Code Identifier</strong> (e.g. <code>QR-WARD-XXXXXXXX</code>) will automatically be provisioned upon creation for mobile inspection and complaint reporting.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= url('/wards') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Create Ward &amp; Generate QR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buildingSelect = document.getElementById('building_id');
    const floorSelect = document.getElementById('floor_id');
    const preselectedFloor = <?= json_encode($selectedFloor) ?>;

    function loadFloors(buildingId, selectFloorId = 0) {
        if (!buildingId) {
            floorSelect.innerHTML = '<option value="">-- Select Building First --</option>';
            return;
        }

        floorSelect.innerHTML = '<option value="">Loading floors...</option>';

        fetch('<?= url('/api/buildings/') ?>' + buildingId + '/floors')
            .then(res => res.json())
            .then(data => {
                floorSelect.innerHTML = '<option value="">-- Select Floor Level --</option>';
                if (data.success && data.floors.length > 0) {
                    data.floors.forEach(fl => {
                        const opt = document.createElement('option');
                        opt.value = fl.id;
                        opt.textContent = 'Floor Level ' + fl.floor_number + ' - ' + fl.floor_name;
                        if (selectFloorId && parseInt(fl.id) === parseInt(selectFloorId)) {
                            opt.selected = true;
                        }
                        floorSelect.appendChild(opt);
                    });
                } else {
                    floorSelect.innerHTML = '<option value="">No floors found in this building</option>';
                }
            })
            .catch(() => {
                floorSelect.innerHTML = '<option value="">Failed to load floors</option>';
            });
    }

    buildingSelect.addEventListener('change', function() {
        loadFloors(this.value);
    });

    if (buildingSelect.value) {
        loadFloors(buildingSelect.value, preselectedFloor);
    }
});
</script>
