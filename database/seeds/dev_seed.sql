-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Development & Staging Demo Seed Data (NO REAL PATIENT/HOSPITAL SECRETS)
-- Default Passwords:
--   Admin: Admin@12345 (Hash generated with PASSWORD_BCRYPT)
--   Supervisor: Supervisor@123 (Hash generated with PASSWORD_BCRYPT)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users
INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `phone`, `password_hash`, `role`, `designation`, `department`, `is_active`)
VALUES
(1, 'EMP-ADMIN-01', 'Dr. Ramesh Patel', 'admin@shethlg.local', '+91 9876543210', '$2y$12$Kj1YVv9NkWmF13Uo4Y5qIeYvC81977e2sX.sTzZz.eQ4.B3X9kYqa', 'admin', 'Chief Medical Officer / Admin', 'Administration', 1),
(2, 'EMP-SUP-01', 'Sunita Sharma', 'supervisor1@shethlg.local', '+91 9876543211', '$2y$12$h3xY.d1hHkL9r3.5Y8M23u4XvQ4wW84tD1.O4xX48Xw7L745fXq.y', 'supervisor', 'Senior Floor Supervisor', 'Quality & Hygiene', 1),
(3, 'EMP-SUP-02', 'Mahesh Solanki', 'supervisor2@shethlg.local', '+91 9876543212', '$2y$12$h3xY.d1hHkL9r3.5Y8M23u4XvQ4wW84tD1.O4xX48Xw7L745fXq.y', 'supervisor', 'Shift Facility Supervisor', 'Facility Operations', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Buildings
INSERT INTO `buildings` (`id`, `code`, `name`, `description`, `total_floors`, `status`)
VALUES
(1, 'MHB', 'Main Hospital Block', 'Multi-specialty primary inpatient wards, intensive care, and administration', 5, 'active'),
(2, 'TEC', 'Trauma & Emergency Center', '24/7 Trauma, casualty, ambulance bay, and acute resuscitation units', 3, 'active'),
(3, 'DOW', 'Diagnostic & OPD Wing', 'Outpatient consults, pathology laboratory, CT/MRI radiology department', 4, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 3. Floors
INSERT INTO `floors` (`id`, `building_id`, `floor_number`, `floor_name`, `status`)
VALUES
(1, 1, 0, 'Ground Floor - Reception & Registration', 'active'),
(2, 1, 1, 'First Floor - General Medical & Surgical', 'active'),
(3, 1, 2, 'Second Floor - Intensive Care Units (ICU)', 'active'),
(4, 1, 3, 'Third Floor - Pediatric & Maternity Care', 'active'),
(5, 2, 0, 'Ground Floor - Emergency & Resuscitation', 'active'),
(6, 3, 0, 'Ground Floor - OPD Consultations & Triage', 'active')
ON DUPLICATE KEY UPDATE `floor_name` = VALUES(`floor_name`);

-- 4. Wards
INSERT INTO `wards` (`id`, `floor_id`, `ward_code`, `ward_name`, `ward_type`, `bed_capacity`, `qr_code_identifier`, `status`)
VALUES
(1, 3, 'MHB-F2-ICU1', 'Medical ICU (MICU)', 'icu', 24, 'QR-MHB-F2-ICU1-7788', 'active'),
(2, 2, 'MHB-F1-GENS', 'General Surgical Ward', 'surgical', 40, 'QR-MHB-F1-GENS-5521', 'active'),
(3, 4, 'MHB-F3-PED', 'Pediatric Care Ward', 'pediatric', 30, 'QR-MHB-F3-PED-3312', 'active'),
(4, 5, 'TEC-F0-EMRG', 'Emergency Casualty Ward', 'emergency', 18, 'QR-TEC-F0-EMRG-9901', 'active')
ON DUPLICATE KEY UPDATE `ward_name` = VALUES(`ward_name`);

-- 5. Inspection Locations
INSERT INTO `inspection_locations` (`id`, `ward_id`, `location_code`, `location_name`, `location_type`, `qr_code_identifier`, `status`)
VALUES
(1, 1, 'LOC-ICU-01', 'MICU Central Nursing Station', 'nursing_station', 'QR-LOC-ICU01-001', 'active'),
(2, 1, 'LOC-ICU-02', 'MICU Biomedical Waste Bay', 'waste_disposal', 'QR-LOC-ICU02-002', 'active'),
(3, 2, 'LOC-GSW-01', 'Surgical Ward Patient Area A', 'patient_bed', 'QR-LOC-GSW01-003', 'active'),
(4, 2, 'LOC-GSW-02', 'Surgical Ward Isolation Washroom', 'washroom', 'QR-LOC-GSW02-004', 'active')
ON DUPLICATE KEY UPDATE `location_name` = VALUES(`location_name`);

-- 6. Shifts
INSERT INTO `shifts` (`id`, `shift_code`, `shift_name`, `start_time`, `end_time`, `grace_period_minutes`, `status`)
VALUES
(1, 'SHIFT-M', 'Morning Shift', '08:00:00', '16:00:00', 15, 'active'),
(2, 'SHIFT-E', 'Evening Shift', '16:00:00', '00:00:00', 15, 'active'),
(3, 'SHIFT-N', 'Night Shift', '00:00:00', '08:00:00', 15, 'active')
ON DUPLICATE KEY UPDATE `shift_name` = VALUES(`shift_name`);

-- 7. SLA Policies
INSERT INTO `sla_policies` (`id`, `policy_name`, `priority`, `response_time_hours`, `resolution_time_hours`, `escalation_email`, `is_active`)
VALUES
(1, 'Critical Emergency SLA', 'critical', 1, 4, 'critical-alerts@shethlg.local', 1),
(2, 'High Priority SLA', 'high', 2, 8, 'escalations@shethlg.local', 1),
(3, 'Standard Medium SLA', 'medium', 4, 24, 'facility-support@shethlg.local', 1),
(4, 'Low Priority Routine SLA', 'low', 12, 48, 'admin-log@shethlg.local', 1)
ON DUPLICATE KEY UPDATE `policy_name` = VALUES(`policy_name`);

-- 8. Floor Plan Areas (Mapped to SVG element IDs)
INSERT INTO `floor_plan_areas` (`id`, `floor_id`, `ward_id`, `area_label`, `svg_element_id`, `shape_type`, `coordinates_json`, `color_code`, `inspection_status`, `notes`)
VALUES
(1, 3, 1, 'ICU Ward A', 'area-icu-a', 'rect', '{"x":50,"y":50,"width":270,"height":200}', '#198754', 'compliant', 'Cleaned and sanitized at morning round.'),
(2, 3, 1, 'Nursing Command Station', 'area-nursing-station', 'rect', '{"x":340,"y":50,"width":320,"height":200}', '#198754', 'compliant', 'Central medicine cabinet locked and monitored.'),
(3, 3, 2, 'General Ward 101', 'area-ward-101', 'rect', '{"x":680,"y":50,"width":270,"height":200}', '#ffc107', 'warning', 'Minor linen restock pending in cubicle 3.'),
(4, 3, 3, 'Pediatric Care Ward', 'area-ward-102', 'rect', '{"x":50,"y":400,"width":270,"height":200}', '#198754', 'compliant', 'Cleaned and sanitized.'),
(5, 3, NULL, 'Duty Doctor Cabin', 'area-doctor-cabin', 'rect', '{"x":340,"y":400,"width":150,"height":200}', '#0f2c59', 'pending', 'Shift handover pending.'),
(6, 3, NULL, 'Hygiene & Washroom Block', 'area-washroom', 'rect', '{"x":510,"y":400,"width":150,"height":200}', '#dc3545', 'critical', 'Drainage leak reported; urgent housekeeping ticket dispatched.'),
(7, 3, NULL, 'Biomedical Waste Disposal Station', 'area-waste-disposal', 'rect', '{"x":680,"y":400,"width":170,"height":200}', '#198754', 'compliant', 'Color-coded bins segregated per pollution control norms.'),
(8, 3, NULL, 'Emergency Fire Escape', 'area-fire-exit', 'rect', '{"x":860,"y":400,"width":90,"height":200}', '#198754', 'compliant', 'Corridor free from obstruction.')
ON DUPLICATE KEY UPDATE `inspection_status` = VALUES(`inspection_status`), `color_code` = VALUES(`color_code`);

-- 9. Supervisor Allocations
INSERT INTO `supervisor_assignments` (`id`, `user_id`, `building_id`, `floor_id`, `ward_id`, `assigned_by`, `status`, `effective_from`, `effective_to`)
VALUES
(1, 2, 1, 3, 1, 1, 'active', CURRENT_DATE, NULL),
(2, 2, 1, 3, 2, 1, 'active', CURRENT_DATE, NULL),
(3, 3, 1, 4, 3, 1, 'active', CURRENT_DATE, NULL),
(4, 3, 2, 5, 4, 1, 'active', CURRENT_DATE, NULL)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- 10. Hospital Settings
INSERT INTO `hospital_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `is_system`)
VALUES
('hospital_name', 'Sheth L.G. General Hospital', 'hospital_profile', 'Hospital official display name', 1),
('hospital_city', 'Ahmedabad', 'hospital_profile', 'City location', 1),
('hospital_state', 'Gujarat', 'hospital_profile', 'State location', 1),
('hospital_country', 'India', 'hospital_profile', 'Country location', 1),
('default_timezone', 'Asia/Kolkata', 'system', 'Application default timezone', 1),
('hospital_latitude', '22.9984', 'geofence', 'Hospital main building GPS latitude', 1),
('hospital_longitude', '72.6025', 'geofence', 'Hospital main building GPS longitude', 1),
('geofence_radius_meters', '150', 'geofence', 'Maximum allowed radius for physical attendance', 1),
('inspection_sla_alert_hours', '2', 'inspection', 'Hours before inspection window expires to trigger warning', 1)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

SET FOREIGN_KEY_CHECKS = 1;

