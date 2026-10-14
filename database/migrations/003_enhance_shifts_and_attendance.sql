-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Multi-Floor Inspection & Complaint Facility Management System
-- Migration 003: Enhance Shifts, QR Tokens & GPS Attendance Verification
-- ==============================================================================

-- 1. Enhance shifts table for overnight shifts & description
ALTER TABLE `shifts`
    ADD COLUMN IF NOT EXISTS `is_overnight` TINYINT(1) NOT NULL DEFAULT 0 AFTER `grace_period_minutes`,
    ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) DEFAULT NULL AFTER `shift_name`,
    ADD INDEX IF NOT EXISTS `idx_shifts_overnight` (`is_overnight`);

-- 2. Enhance user_shift_assignments table for location scoping
ALTER TABLE `user_shift_assignments`
    ADD COLUMN IF NOT EXISTS `building_id` BIGINT UNSIGNED DEFAULT NULL AFTER `shift_id`,
    ADD COLUMN IF NOT EXISTS `floor_id` BIGINT UNSIGNED DEFAULT NULL AFTER `building_id`,
    ADD COLUMN IF NOT EXISTS `ward_id` BIGINT UNSIGNED DEFAULT NULL AFTER `floor_id`,
    ADD INDEX IF NOT EXISTS `idx_usa_facility` (`building_id`, `floor_id`, `ward_id`);

-- 3. Enhance inspection_locations for GPS geofencing
ALTER TABLE `inspection_locations`
    ADD COLUMN IF NOT EXISTS `gps_latitude` DECIMAL(10,8) DEFAULT NULL AFTER `location_type`,
    ADD COLUMN IF NOT EXISTS `gps_longitude` DECIMAL(11,8) DEFAULT NULL AFTER `gps_latitude`,
    ADD COLUMN IF NOT EXISTS `geofence_radius_meters` INT UNSIGNED DEFAULT NULL AFTER `gps_longitude`;

-- 4. Enhance wards for GPS geofencing
ALTER TABLE `wards`
    ADD COLUMN IF NOT EXISTS `gps_latitude` DECIMAL(10,8) DEFAULT NULL AFTER `bed_capacity`,
    ADD COLUMN IF NOT EXISTS `gps_longitude` DECIMAL(11,8) DEFAULT NULL AFTER `gps_latitude`,
    ADD COLUMN IF NOT EXISTS `geofence_radius_meters` INT UNSIGNED DEFAULT NULL AFTER `gps_longitude`;

-- 5. Enhance attendance table for location & QR linkage
ALTER TABLE `attendance`
    ADD COLUMN IF NOT EXISTS `ward_id` BIGINT UNSIGNED DEFAULT NULL AFTER `shift_id`,
    ADD COLUMN IF NOT EXISTS `location_id` BIGINT UNSIGNED DEFAULT NULL AFTER `ward_id`,
    ADD COLUMN IF NOT EXISTS `qr_token_id` BIGINT UNSIGNED DEFAULT NULL AFTER `location_id`,
    ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `verification_status`,
    ADD INDEX IF NOT EXISTS `idx_att_facility` (`ward_id`, `location_id`),
    ADD INDEX IF NOT EXISTS `idx_att_qr` (`qr_token_id`);

-- 6. Add default location radius setting if not already present
INSERT INTO `hospital_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `is_system`)
VALUES ('max_acceptable_gps_accuracy_meters', '100', 'geofence', 'Maximum acceptable GPS accuracy threshold in meters before flagging', 1)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
