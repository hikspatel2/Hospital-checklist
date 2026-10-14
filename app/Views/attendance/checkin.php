<?php
/**
 * Mobile-First GPS + QR Attendance Check-In View
 * Leverages HTML5 Geolocation, on-site QR validation, and idempotent network retry handling.
 */
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">

        <!-- Hospital Brand Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-primary text-white shadow-sm mb-2">
                <i class="bi bi-geo-alt-fill fs-2"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">On-Site Attendance Gateway</h1>
            <p class="text-muted small mb-0">Sheth L.G. General Hospital &bull; Mobile Presence Verification</p>
        </div>

        <!-- Spoofing Disclaimer Alert -->
        <div class="card border-0 shadow-sm bg-light mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-start">
                    <i class="bi bi-shield-exclamation text-warning fs-4 me-2"></i>
                    <div class="small text-muted" style="font-size: 0.78rem;">
                        <strong>Security Telemetry Notice:</strong> Browser GPS coordinates can be simulated or spoofed via mock apps. Physical hospital presence is verified by combining <strong>on-site optical QR scanning</strong> with <strong>server-side GPS geofencing</strong> and network audit logs.
                    </div>
                </div>
            </div>
        </div>

        <?php if ($existingCheckIn && empty($existingCheckIn['check_out_time'])): ?>
            <!-- Active Session Card (Ready for Check-Out) -->
            <div class="card border-0 shadow-sm mb-4 border-top border-success border-4">
                <div class="card-header bg-white py-3 text-center">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i> ACTIVE DUTY SESSION
                    </span>
                    <h5 class="fw-bold text-dark mt-2 mb-0">Currently Checked In</h5>
                    <small class="text-muted">Checked in on <?= date('d M Y, h:i A', strtotime($existingCheckIn['check_in_time'])) ?></small>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="bg-light rounded p-3 mb-3 border">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small d-block">Status:</span>
                                <span class="badge bg-success text-uppercase"><?= e($existingCheckIn['status']) ?></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Verification:</span>
                                <span class="badge bg-primary text-uppercase"><?= e($existingCheckIn['verification_status']) ?></span>
                            </div>
                        </div>
                    </div>

                    <form id="checkoutForm" action="<?= url('/attendance/checkout') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="attendance_id" value="<?= $existingCheckIn['id'] ?>">
                        <button type="submit" class="btn btn-danger w-100 py-3 fw-bold shadow-sm" id="btnCheckOut">
                            <i class="bi bi-box-arrow-right me-2"></i> Complete Duty Check-Out
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Check-In Form Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-qr-code-scan me-2 fs-5"></i>
                            <h6 class="card-title fw-bold mb-0">Daily Shift Verification</h6>
                        </div>
                        <span class="badge bg-white text-primary"><?= date('d M Y') ?></span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form id="attendanceCheckinForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="latitude" id="inputLatitude">
                        <input type="hidden" name="longitude" id="inputLongitude">
                        <input type="hidden" name="accuracy" id="inputAccuracy">

                        <!-- Shift Selector -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Assigned Work Shift <span class="text-danger">*</span></label>
                            <select class="form-select" name="shift_id" id="shiftSelect" required>
                                <?php if (!empty($activeShift)): ?>
                                    <option value="<?= $activeShift['shift_id'] ?>" selected>
                                        <?= e($activeShift['shift_name']) ?> (<?= date('h:i A', strtotime($activeShift['start_time'])) ?> - <?= date('h:i A', strtotime($activeShift['end_time'])) ?>)
                                        <?= !empty($activeShift['is_overnight']) ? '[Overnight]' : '' ?>
                                    </option>
                                <?php else: ?>
                                    <option value="">-- Select Active Shift --</option>
                                    <?php foreach ($allActiveShifts as $sh): ?>
                                        <option value="<?= $sh['id'] ?>">
                                            <?= e($sh['shift_name']) ?> (<?= date('h:i A', strtotime($sh['start_time'])) ?> - <?= date('h:i A', strtotime($sh['end_time'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- On-Site Ward QR Token Input -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Scan or Enter Ward QR Token <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-primary border-end-0">
                                    <i class="bi bi-qr-code"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 font-monospace text-uppercase" 
                                       name="qr_token" id="qrTokenInput" required 
                                       placeholder="e.g. LG-QR-SEC-XXXXXXXX" autocomplete="off">
                            </div>
                            <div class="form-text">Scan the physical QR code mounted in your assigned ward or department.</div>
                        </div>

                        <!-- Live GPS Telemetry Box -->
                        <div class="card border-0 bg-light p-3 mb-4 rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">
                                    <i class="bi bi-crosshair text-primary me-1"></i> GPS Geofence Telemetry
                                </span>
                                <span class="badge" id="gpsStatusBadge">Acquiring GPS...</span>
                            </div>
                            <div class="row g-2 small text-muted" id="gpsCoordinatesDisplay">
                                <div class="col-6">Latitude: <span class="font-monospace text-dark" id="displayLat">--</span></div>
                                <div class="col-6">Longitude: <span class="font-monospace text-dark" id="displayLon">--</span></div>
                                <div class="col-6">Accuracy: <span class="font-monospace text-dark" id="displayAccuracy">--</span></div>
                                <div class="col-6">Campus Dist: <span class="font-monospace text-dark" id="displayDistance">--</span></div>
                            </div>
                            <div class="alert alert-warning py-1 px-2 small mt-2 mb-0 d-none" id="gpsAccuracyWarning">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Poor accuracy (> 100m). Move outdoors or near a window.
                            </div>
                        </div>

                        <!-- Status Alert Message Area -->
                        <div id="checkinAlertArea"></div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" id="btnSubmitCheckin" disabled>
                            <span class="spinner-border spinner-border-sm me-2 d-none" id="checkinSpinner" role="status"></span>
                            <i class="bi bi-check-circle-fill me-1" id="checkinIcon"></i> Confirm Check-In &amp; Start Duty
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Guidance Box -->
        <div class="card border-0 shadow-sm text-center py-3 bg-white">
            <div class="card-body">
                <span class="text-muted small">Hospital Campus Geofence Perimeter: <strong>150 Meters</strong></span>
                <div class="text-muted" style="font-size: 0.72rem;">GPS accuracy threshold: &le; 100 meters &bull; Asia/Kolkata Timezone</div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputLat = document.getElementById('inputLatitude');
    const inputLon = document.getElementById('inputLongitude');
    const inputAcc = document.getElementById('inputAccuracy');
    const displayLat = document.getElementById('displayLat');
    const displayLon = document.getElementById('displayLon');
    const displayAcc = document.getElementById('displayAccuracy');
    const displayDist = document.getElementById('displayDistance');
    const gpsStatusBadge = document.getElementById('gpsStatusBadge');
    const gpsAccuracyWarning = document.getElementById('gpsAccuracyWarning');
    const btnSubmit = document.getElementById('btnSubmitCheckin');
    const alertArea = document.getElementById('checkinAlertArea');
    const checkinSpinner = document.getElementById('checkinSpinner');
    const checkinIcon = document.getElementById('checkinIcon');

    const campusLat = <?= json_encode($campus['latitude']) ?>;
    const campusLon = <?= json_encode($campus['longitude']) ?>;
    const campusRadius = <?= json_encode($campus['radius_meters']) ?>;

    // Haversine formula on client for instant distance display
    function getDistanceMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    if (navigator.geolocation && btnSubmit) {
        gpsStatusBadge.className = 'badge bg-warning text-dark';
        gpsStatusBadge.textContent = 'Requesting GPS...';

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                const acc = Math.round(pos.coords.accuracy);

                inputLat.value = lat;
                inputLon.value = lon;
                inputAcc.value = acc;

                displayLat.textContent = lat.toFixed(5);
                displayLon.textContent = lon.toFixed(5);
                displayAcc.textContent = acc + 'm';

                const dist = getDistanceMeters(lat, lon, campusLat, campusLon);
                displayDist.textContent = dist + 'm';

                if (acc > 100) {
                    gpsAccuracyWarning.classList.remove('d-none');
                    gpsStatusBadge.className = 'badge bg-warning text-dark';
                    gpsStatusBadge.textContent = 'Low Accuracy (' + acc + 'm)';
                } else if (dist <= campusRadius) {
                    gpsStatusBadge.className = 'badge bg-success';
                    gpsStatusBadge.textContent = 'Within Geofence (' + dist + 'm)';
                } else {
                    gpsStatusBadge.className = 'badge bg-danger';
                    gpsStatusBadge.textContent = 'Outside Geofence (' + dist + 'm)';
                }

                btnSubmit.disabled = false;
            },
            function(err) {
                gpsStatusBadge.className = 'badge bg-danger';
                gpsStatusBadge.textContent = 'GPS Permission Denied';
                alertArea.innerHTML = '<div class="alert alert-danger py-2 small mb-3"><i class="bi bi-x-circle me-1"></i> Location permission is required over HTTPS. Please enable browser GPS permissions.</div>';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Handle AJAX Check-In Submission with Idempotency
    const form = document.getElementById('attendanceCheckinForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            btnSubmit.disabled = true;
            checkinSpinner.classList.remove('d-none');
            checkinIcon.classList.add('d-none');
            alertArea.innerHTML = '';

            const formData = new FormData(form);

            fetch('<?= url('/attendance/checkin') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                checkinSpinner.classList.add('d-none');
                checkinIcon.classList.remove('d-none');

                if (data.success) {
                    alertArea.innerHTML = '<div class="alert alert-success py-2 small mb-3"><i class="bi bi-check-circle-fill me-1"></i> ' + data.message + '</div>';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    alertArea.innerHTML = '<div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (data.error || 'Check-in failed') + '</div>';
                }
            })
            .catch(() => {
                btnSubmit.disabled = false;
                checkinSpinner.classList.add('d-none');
                checkinIcon.classList.remove('d-none');
                alertArea.innerHTML = '<div class="alert alert-danger py-2 small mb-3">Network error. Please check your connection and retry.</div>';
            });
        });
    }
});
</script>
