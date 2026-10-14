-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Multi-Floor Inspection & Complaint Facility Management System
-- Migration 004: Enhance Checklists, Template Versioning & Custom Scheduling
-- ==============================================================================

-- 1. Enhance checklist_templates for facility scoping
ALTER TABLE `checklist_templates`
    ADD COLUMN IF NOT EXISTS `target_building_id` BIGINT UNSIGNED DEFAULT NULL AFTER `category`,
    ADD COLUMN IF NOT EXISTS `target_floor_id` BIGINT UNSIGNED DEFAULT NULL AFTER `target_building_id`,
    ADD COLUMN IF NOT EXISTS `target_ward_id` BIGINT UNSIGNED DEFAULT NULL AFTER `target_floor_id`,
    ADD COLUMN IF NOT EXISTS `target_location_id` BIGINT UNSIGNED DEFAULT NULL AFTER `target_ward_id`,
    ADD INDEX IF NOT EXISTS `idx_ct_target_facility` (`target_building_id`, `target_floor_id`, `target_ward_id`, `target_location_id`);

-- 2. Enhance checklist_items with required/optional flag and severity level
ALTER TABLE `checklist_items`
    ADD COLUMN IF NOT EXISTS `is_required` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_critical`,
    ADD COLUMN IF NOT EXISTS `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium' AFTER `is_required`,
    ADD INDEX IF NOT EXISTS `idx_ci_severity` (`severity`);

-- 3. Enhance schedule_rules for custom recurrence, weekdays, exclusions, and supervisor assignment
ALTER TABLE `schedule_rules`
    ADD COLUMN IF NOT EXISTS `assigned_user_id` BIGINT UNSIGNED DEFAULT NULL AFTER `shift_id`,
    ADD COLUMN IF NOT EXISTS `target_location_id` BIGINT UNSIGNED DEFAULT NULL AFTER `target_ward_id`,
    ADD COLUMN IF NOT EXISTS `selected_weekdays` VARCHAR(60) DEFAULT NULL AFTER `frequency`,
    ADD COLUMN IF NOT EXISTS `time_window_start` TIME DEFAULT NULL AFTER `selected_weekdays`,
    ADD COLUMN IF NOT EXISTS `time_window_end` TIME DEFAULT NULL AFTER `time_window_start`,
    ADD COLUMN IF NOT EXISTS `exclusions_json` TEXT DEFAULT NULL AFTER `end_date`,
    ADD COLUMN IF NOT EXISTS `last_generated_date` DATE DEFAULT NULL AFTER `exclusions_json`,
    ADD INDEX IF NOT EXISTS `idx_sr_assigned_user` (`assigned_user_id`);

-- 4. Enhance inspection_schedules for idempotency, retry, and overdue states
ALTER TABLE `inspection_schedules`
    MODIFY COLUMN `status` ENUM('scheduled', 'pending', 'in_progress', 'completed', 'missed', 'overdue', 'cancelled') NOT NULL DEFAULT 'scheduled',
    ADD COLUMN IF NOT EXISTS `idempotency_key` VARCHAR(128) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `error_message` TEXT DEFAULT NULL AFTER `idempotency_key`,
    ADD COLUMN IF NOT EXISTS `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `error_message`,
    ADD UNIQUE KEY IF NOT EXISTS `uk_is_idempotency` (`idempotency_key`),
    ADD INDEX IF NOT EXISTS `idx_is_retry` (`retry_count`);

-- 5. Enhance inspections for submission timestamp
ALTER TABLE `inspections`
    ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME DEFAULT NULL AFTER `completed_at`,
    ADD INDEX IF NOT EXISTS `idx_insp_submitted` (`submitted_at`);

-- 6. Add default scheduling configuration settings
INSERT INTO `hospital_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `is_system`)
VALUES 
    ('schedule_generation_days_ahead', '7', 'scheduling', 'Number of days in advance to generate inspection schedule instances', 1),
    ('auto_mark_overdue_hours', '2', 'scheduling', 'Hours after time window end before marking incomplete schedule as overdue', 1)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
