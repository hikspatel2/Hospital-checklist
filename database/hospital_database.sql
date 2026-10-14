-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Multi-Floor Inspection & Complaint Facility Management System
-- Clean, Production-Ready, Standalone Consolidated Database Dump
-- Compatible with MySQL 5.7+, MySQL 8.x, and MariaDB 10.x+
-- Character Set: UTF8mb4 / Collation: utf8mb4_unicode_ci / Engine: InnoDB
-- Timezone: Asia/Kolkata (IST +05:30)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- ------------------------------------------------------------------------------
-- 0. Schema Migrations Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `execution_time_ms` INT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 1. Users Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'supervisor') NOT NULL DEFAULT 'supervisor',
    `designation` VARCHAR(100) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_users_role_status` (`role`, `is_active`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Buildings Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `buildings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `total_floors` INT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_buildings_status` (`status`),
    INDEX `idx_buildings_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Floors Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `floors` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_number` INT NOT NULL,
    `floor_name` VARCHAR(100) NOT NULL,
    `plan_image_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY `uk_building_floor` (`building_id`, `floor_number`),
    INDEX `idx_floors_building` (`building_id`),
    INDEX `idx_floors_deleted` (`deleted_at`),
    CONSTRAINT `fk_floors_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Wards Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wards` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_code` VARCHAR(50) NOT NULL UNIQUE,
    `ward_name` VARCHAR(150) NOT NULL,
    `ward_type` ENUM('general', 'icu', 'emergency', 'pediatric', 'maternity', 'surgical', 'opd', 'radiology', 'lab', 'administrative', 'other') NOT NULL DEFAULT 'general',
    `bed_capacity` INT UNSIGNED NOT NULL DEFAULT 0,
    `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
    `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
    `geofence_radius_meters` INT UNSIGNED DEFAULT NULL,
    `qr_code_identifier` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_wards_floor` (`floor_id`),
    INDEX `idx_wards_type_status` (`ward_type`, `status`),
    INDEX `idx_wards_qr` (`qr_code_identifier`),
    INDEX `idx_wards_deleted` (`deleted_at`),
    CONSTRAINT `fk_wards_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Inspection Locations Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_locations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `location_code` VARCHAR(50) NOT NULL UNIQUE,
    `location_name` VARCHAR(150) NOT NULL,
    `location_type` ENUM('patient_bed', 'washroom', 'nursing_station', 'corridor', 'store_room', 'doctor_cabin', 'waste_disposal', 'fire_exit', 'utility', 'other') NOT NULL DEFAULT 'patient_bed',
    `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
    `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
    `geofence_radius_meters` INT UNSIGNED DEFAULT NULL,
    `qr_code_identifier` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_locations_ward` (`ward_id`),
    INDEX `idx_locations_type` (`location_type`),
    INDEX `idx_locations_qr` (`qr_code_identifier`),
    INDEX `idx_locations_deleted` (`deleted_at`),
    CONSTRAINT `fk_locations_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Shifts Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shifts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shift_code` VARCHAR(50) NOT NULL UNIQUE,
    `shift_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `grace_period_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
    `is_overnight` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_shifts_status` (`status`),
    INDEX `idx_shifts_overnight` (`is_overnight`),
    INDEX `idx_shifts_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. User Shift Assignments Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_shift_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `shift_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED DEFAULT NULL,
    `floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_date` DATE NOT NULL,
    `status` ENUM('scheduled', 'completed', 'cancelled', 'absent') NOT NULL DEFAULT 'scheduled',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_shift_date` (`user_id`, `assigned_date`),
    INDEX `idx_usa_user` (`user_id`),
    INDEX `idx_usa_shift` (`shift_id`),
    INDEX `idx_usa_facility` (`building_id`, `floor_id`, `ward_id`),
    INDEX `idx_usa_date_status` (`assigned_date`, `status`),
    CONSTRAINT `fk_usa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_usa_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. Supervisor Allocations Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `supervisor_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_by` BIGINT UNSIGNED NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sa_user` (`user_id`),
    INDEX `idx_sa_facility` (`building_id`, `floor_id`, `ward_id`),
    INDEX `idx_sa_effective` (`effective_from`, `effective_to`),
    CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. QR Verification Tokens Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('ward', 'location') NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `token_string` VARCHAR(128) NOT NULL UNIQUE,
    `qr_data` TEXT NOT NULL,
    `salt` VARCHAR(32) NOT NULL,
    `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL,
    `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
    INDEX `idx_qr_entity` (`entity_type`, `entity_id`),
    INDEX `idx_qr_token` (`token_string`),
    INDEX `idx_qr_expiry` (`expires_at`, `is_revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. Attendance Verification Ledger
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `shift_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `location_id` BIGINT UNSIGNED DEFAULT NULL,
    `qr_token_id` BIGINT UNSIGNED DEFAULT NULL,
    `attendance_date` DATE NOT NULL,
    `check_in_time` DATETIME NOT NULL,
    `check_out_time` DATETIME DEFAULT NULL,
    `check_in_lat` DECIMAL(10,8) DEFAULT NULL,
    `check_in_lng` DECIMAL(11,8) DEFAULT NULL,
    `check_in_accuracy` DECIMAL(8,2) DEFAULT NULL,
    `check_out_lat` DECIMAL(10,8) DEFAULT NULL,
    `check_out_lng` DECIMAL(11,8) DEFAULT NULL,
    `check_out_accuracy` DECIMAL(8,2) DEFAULT NULL,
    `qr_scanned_check_in` VARCHAR(100) DEFAULT NULL,
    `qr_scanned_check_out` VARCHAR(100) DEFAULT NULL,
    `verification_status` ENUM('verified', 'flagged_gps', 'flagged_qr', 'flagged_both', 'manual_override') NOT NULL DEFAULT 'verified',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_attendance_user_date` (`user_id`, `attendance_date`),
    INDEX `idx_att_user` (`user_id`),
    INDEX `idx_att_shift` (`shift_id`),
    INDEX `idx_att_date` (`attendance_date`),
    INDEX `idx_att_facility` (`ward_id`, `location_id`),
    INDEX `idx_att_qr` (`qr_token_id`),
    INDEX `idx_att_verification` (`verification_status`),
    CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_att_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. Checklist Templates Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checklist_templates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` ENUM('cleanliness', 'infection_control', 'fire_safety', 'equipment', 'medication_storage', 'biomedical_waste', 'general_facility', 'custom') NOT NULL DEFAULT 'cleanliness',
    `target_building_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_location_id` BIGINT UNSIGNED DEFAULT NULL,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_ct_category` (`category`),
    INDEX `idx_ct_status` (`is_published`, `is_active`),
    INDEX `idx_ct_target_facility` (`target_building_id`, `target_floor_id`, `target_ward_id`, `target_location_id`),
    INDEX `idx_ct_deleted` (`deleted_at`),
    CONSTRAINT `fk_ct_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. Checklist Sections Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checklist_sections` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cs_template_sort` (`template_id`, `sort_order`),
    CONSTRAINT `fk_cs_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. Checklist Items Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checklist_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `section_id` BIGINT UNSIGNED NOT NULL,
    `item_code` VARCHAR(50) NOT NULL,
    `question_text` TEXT NOT NULL,
    `response_type` ENUM('yes_no_na', 'rating_1_5', 'text', 'photo_mandatory', 'numeric') NOT NULL DEFAULT 'yes_no_na',
    `weight` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `is_critical` TINYINT(1) NOT NULL DEFAULT 0,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `guidance_notes` TEXT DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ci_section_sort` (`section_id`, `sort_order`),
    INDEX `idx_ci_critical` (`is_critical`),
    INDEX `idx_ci_severity` (`severity`),
    CONSTRAINT `fk_ci_section` FOREIGN KEY (`section_id`) REFERENCES `checklist_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. Schedule Recurrence Rules Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedule_rules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_name` VARCHAR(150) NOT NULL,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_location_id` BIGINT UNSIGNED DEFAULT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `frequency` ENUM('daily', 'twice_daily', 'weekly', 'biweekly', 'monthly', 'shift_based') NOT NULL DEFAULT 'daily',
    `selected_weekdays` VARCHAR(60) DEFAULT NULL,
    `time_window_start` TIME DEFAULT NULL,
    `time_window_end` TIME DEFAULT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `exclusions_json` TEXT DEFAULT NULL,
    `last_generated_date` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sr_template` (`template_id`),
    INDEX `idx_sr_facility` (`building_id`, `floor_id`, `target_ward_id`, `target_location_id`),
    INDEX `idx_sr_active_dates` (`is_active`, `start_date`, `end_date`),
    INDEX `idx_sr_assigned_user` (`assigned_user_id`),
    CONSTRAINT `fk_sr_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. Inspection Schedules Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_schedules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_id` BIGINT UNSIGNED DEFAULT NULL,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `location_id` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `scheduled_date` DATE NOT NULL,
    `scheduled_time_window_start` TIME DEFAULT NULL,
    `scheduled_time_window_end` TIME DEFAULT NULL,
    `status` ENUM('scheduled', 'pending', 'in_progress', 'completed', 'missed', 'overdue', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `idempotency_key` VARCHAR(128) DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_is_idempotency` (`idempotency_key`),
    INDEX `idx_is_date_status` (`scheduled_date`, `status`),
    INDEX `idx_is_assigned_user` (`assigned_user_id`),
    INDEX `idx_is_facility` (`building_id`, `floor_id`, `ward_id`, `location_id`),
    INDEX `idx_is_retry` (`retry_count`),
    CONSTRAINT `fk_is_rule` FOREIGN KEY (`rule_id`) REFERENCES `schedule_rules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_is_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. Inspections Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspections` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_code` VARCHAR(50) NOT NULL UNIQUE,
    `schedule_id` BIGINT UNSIGNED DEFAULT NULL,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `template_version` INT UNSIGNED NOT NULL DEFAULT 1,
    `inspector_user_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `location_id` BIGINT UNSIGNED DEFAULT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `started_at` DATETIME NOT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `submitted_at` DATETIME DEFAULT NULL,
    `qr_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `qr_token_scanned` VARCHAR(128) DEFAULT NULL,
    `gps_lat` DECIMAL(10,8) DEFAULT NULL,
    `gps_lng` DECIMAL(11,8) DEFAULT NULL,
    `gps_accuracy` DECIMAL(8,2) DEFAULT NULL,
    `geofence_compliant` TINYINT(1) NOT NULL DEFAULT 1,
    `total_items` INT UNSIGNED NOT NULL DEFAULT 0,
    `items_completed` INT UNSIGNED NOT NULL DEFAULT 0,
    `items_passed` INT UNSIGNED NOT NULL DEFAULT 0,
    `items_failed` INT UNSIGNED NOT NULL DEFAULT 0,
    `items_na` INT UNSIGNED NOT NULL DEFAULT 0,
    `critical_failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `score_percentage` DECIMAL(5,2) DEFAULT NULL,
    `compliance_status` ENUM('passed', 'passed_with_warnings', 'failed', 'critical_breach') DEFAULT NULL,
    `offline_sync_id` VARCHAR(100) DEFAULT NULL UNIQUE,
    `sync_status` ENUM('synced', 'conflict', 'pending') NOT NULL DEFAULT 'synced',
    `synced_at` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_insp_inspector` (`inspector_user_id`),
    INDEX `idx_insp_facility` (`building_id`, `floor_id`, `ward_id`, `location_id`),
    INDEX `idx_insp_started` (`started_at`),
    INDEX `idx_insp_submitted` (`submitted_at`),
    INDEX `idx_insp_status` (`compliance_status`),
    INDEX `idx_insp_sync` (`sync_status`),
    CONSTRAINT `fk_insp_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `inspection_schedules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_user` FOREIGN KEY (`inspector_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. Inspection Responses Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_responses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` BIGINT UNSIGNED NOT NULL,
    `item_id` BIGINT UNSIGNED NOT NULL,
    `response_value` VARCHAR(255) DEFAULT NULL,
    `is_passed` TINYINT(1) DEFAULT NULL,
    `is_critical_fail` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_insp_item` (`inspection_id`, `item_id`),
    INDEX `idx_ir_inspection` (`inspection_id`),
    INDEX `idx_ir_item` (`item_id`),
    INDEX `idx_ir_passed` (`is_passed`, `is_critical_fail`),
    CONSTRAINT `fk_ir_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ir_item` FOREIGN KEY (`item_id`) REFERENCES `checklist_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 18. Response Photos Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `response_photos` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `response_id` BIGINT UNSIGNED NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `sha256_hash` VARCHAR(64) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rp_response` (`response_id`),
    CONSTRAINT `fk_rp_response` FOREIGN KEY (`response_id`) REFERENCES `inspection_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. Inspection Signatures Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_signatures` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `signer_user_id` BIGINT UNSIGNED NOT NULL,
    `signature_data` LONGTEXT NOT NULL,
    `signed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `signer_ip` VARCHAR(45) NOT NULL,
    `signer_user_agent` TEXT NOT NULL,
    CONSTRAINT `fk_isig_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_isig_user` FOREIGN KEY (`signer_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. Floor Plan Areas Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `floor_plan_areas` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `area_label` VARCHAR(100) NOT NULL,
    `svg_element_id` VARCHAR(50) DEFAULT NULL,
    `shape_type` ENUM('rect', 'polygon', 'path', 'circle') NOT NULL DEFAULT 'rect',
    `coordinates_json` TEXT NOT NULL,
    `color_code` VARCHAR(20) NOT NULL DEFAULT '#198754',
    `notes` TEXT DEFAULT NULL,
    `inspection_status` ENUM('pending', 'compliant', 'warning', 'critical') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_fpa_floor` (`floor_id`),
    INDEX `idx_fpa_ward` (`ward_id`),
    INDEX `idx_fpa_svg` (`svg_element_id`),
    INDEX `idx_fpa_status` (`inspection_status`),
    CONSTRAINT `fk_fpa_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_fpa_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 21. SLA Policies Table (Clean Consolidated Schema)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sla_policies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `policy_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'all',
    `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    `response_time_hours` INT UNSIGNED NOT NULL,
    `resolution_time_hours` INT UNSIGNED NOT NULL,
    `escalation_email` VARCHAR(150) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_sla_cat_priority` (`category`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 22. Complaints Table (Clean Consolidated Schema)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `inspection_id` BIGINT UNSIGNED DEFAULT NULL,
    `inspection_response_id` BIGINT UNSIGNED DEFAULT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `location_id` BIGINT UNSIGNED DEFAULT NULL,
    `category` ENUM('cleanliness', 'infection_control', 'fire_safety', 'equipment', 'medication_storage', 'biomedical_waste', 'general_facility', 'plumbing', 'electrical', 'security', 'other') NOT NULL DEFAULT 'cleanliness',
    `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'escalated', 'closed') NOT NULL DEFAULT 'open',
    `reported_by` BIGINT UNSIGNED NOT NULL,
    `assigned_to_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `sla_policy_id` BIGINT UNSIGNED DEFAULT NULL,
    `due_date` DATETIME DEFAULT NULL,
    `response_due_at` DATETIME DEFAULT NULL,
    `resolution_due_at` DATETIME DEFAULT NULL,
    `first_responded_at` DATETIME DEFAULT NULL,
    `is_response_breached` TINYINT(1) NOT NULL DEFAULT 0,
    `is_resolution_breached` TINYINT(1) NOT NULL DEFAULT 0,
    `resolved_at` DATETIME DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `resolved_by` BIGINT UNSIGNED DEFAULT NULL,
    `closed_at` DATETIME DEFAULT NULL,
    `reopened_at` DATETIME DEFAULT NULL,
    `reopened_by` BIGINT UNSIGNED DEFAULT NULL,
    `reopen_reason` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_comp_status_priority` (`status`, `priority`),
    INDEX `idx_comp_facility` (`building_id`, `floor_id`, `ward_id`, `location_id`),
    INDEX `idx_comp_category` (`category`),
    INDEX `idx_comp_reporter` (`reported_by`),
    INDEX `idx_comp_assigned` (`assigned_to_user_id`),
    INDEX `idx_comp_breached` (`is_response_breached`, `is_resolution_breached`),
    INDEX `idx_comp_due_date` (`due_date`),
    CONSTRAINT `fk_comp_insp` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_assigned` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_sla` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_reopener` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 23. Complaint Updates Ledger Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaint_updates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `update_type` ENUM('comment', 'status_change', 'assignment', 'reassignment', 'hold', 'reopen', 'resolution', 'closure') NOT NULL DEFAULT 'comment',
    `previous_status` ENUM('open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'escalated', 'closed') DEFAULT NULL,
    `new_status` ENUM('open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'escalated', 'closed') DEFAULT NULL,
    `comment` TEXT NOT NULL,
    `evidence_file_path` VARCHAR(255) DEFAULT NULL,
    `evidence_stored_name` VARCHAR(255) DEFAULT NULL,
    `evidence_size_bytes` BIGINT UNSIGNED DEFAULT NULL,
    `evidence_mime_type` VARCHAR(100) DEFAULT NULL,
    `evidence_hash` VARCHAR(64) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cu_complaint` (`complaint_id`),
    INDEX `idx_cu_user` (`user_id`),
    CONSTRAINT `fk_cu_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 24. Complaint Evidence Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaint_evidence` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` BIGINT UNSIGNED NOT NULL,
    `complaint_update_id` BIGINT UNSIGNED DEFAULT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_hash` VARCHAR(64) NOT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ce_complaint` (`complaint_id`),
    INDEX `idx_ce_update` (`complaint_update_id`),
    INDEX `idx_ce_uploader` (`uploaded_by`),
    CONSTRAINT `fk_ce_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ce_update` FOREIGN KEY (`complaint_update_id`) REFERENCES `complaint_updates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ce_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 25. Password Reset Tokens Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_prt_user` (`user_id`),
    INDEX `idx_prt_hash` (`token_hash`),
    INDEX `idx_prt_expires` (`expires_at`),
    CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 26. Hospital Settings Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospital_settings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 27. Audit Logs Table (Append-Only)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NOT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 28. Backup Jobs Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_jobs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_name` VARCHAR(150) NOT NULL,
    `job_type` ENUM('database_only', 'full_with_evidence', 'incremental') NOT NULL DEFAULT 'database_only',
    `schedule_cron` VARCHAR(50) NOT NULL DEFAULT '0 2 * * *',
    `retention_days` INT UNSIGNED NOT NULL DEFAULT 7,
    `encrypt_backup` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active', 'paused') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 29. Backup Logs Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `backup_job_id` BIGINT UNSIGNED DEFAULT NULL,
    `archive_type` ENUM('database_only', 'full_with_evidence', 'incremental') NOT NULL DEFAULT 'database_only',
    `backup_file_path` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL,
    `file_hash_sha256` VARCHAR(64) NOT NULL,
    `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
    `tables_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `records_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `storage_location` VARCHAR(100) NOT NULL DEFAULT 'local_restricted',
    `status` ENUM('success', 'failed', 'purged') NOT NULL DEFAULT 'success',
    `verified_at` DATETIME DEFAULT NULL,
    `restore_tested_at` DATETIME DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bl_status` (`status`),
    INDEX `idx_bl_created` (`created_at`),
    CONSTRAINT `fk_bl_job` FOREIGN KEY (`backup_job_id`) REFERENCES `backup_jobs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 30. Restore Logs Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `restore_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `backup_log_id` BIGINT UNSIGNED DEFAULT NULL,
    `initiated_by` BIGINT UNSIGNED NOT NULL,
    `restore_target` ENUM('production', 'staging', 'isolated_test') NOT NULL DEFAULT 'production',
    `pre_restore_backup_file` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('in_progress', 'success', 'failed', 'rolled_back') NOT NULL DEFAULT 'in_progress',
    `execution_duration_sec` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `tables_restored` INT UNSIGNED NOT NULL DEFAULT 0,
    `records_restored` INT UNSIGNED NOT NULL DEFAULT 0,
    `validation_details` JSON DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rl_status` (`status`),
    INDEX `idx_rl_created` (`created_at`),
    CONSTRAINT `fk_rl_backup` FOREIGN KEY (`backup_log_id`) REFERENCES `backup_logs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_rl_user` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- INITIAL CORE DATA SEEDING (Idempotent: ON DUPLICATE KEY UPDATE)
-- ==============================================================================

-- 1. Users (Admin password: Admin@12345, Supervisor password: Supervisor@123)
INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `phone`, `password_hash`, `role`, `designation`, `department`, `is_active`)
VALUES
(1, 'EMP-ADMIN-01', 'Dr. Ramesh Patel', 'admin@shethlg.local', '+91 9876543210', '$2y$12$Kj1YVv9NkWmF13Uo4Y5qIeYvC81977e2sX.sTzZz.eQ4.B3X9kYqa', 'admin', 'Chief Medical Officer / Admin', 'Administration', 1),
(2, 'EMP-SUP-01', 'Sunita Sharma', 'supervisor1@shethlg.local', '+91 9876543211', '$2y$12$h3xY.d1hHkL9r3.5Y8M23u4XvQ4wW84tD1.O4xX48Xw7L745fXq.y', 'supervisor', 'Senior Floor Supervisor', 'Quality & Hygiene', 1),
(3, 'EMP-SUP-02', 'Mahesh Solanki', 'supervisor2@shethlg.local', '+91 9876543212', '$2y$12$h3xY.d1hHkL9r3.5Y8M23u4XvQ4wW84tD1.O4xX48Xw7L745fXq.y', 'supervisor', 'Shift Facility Supervisor', 'Facility Operations', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `role` = VALUES(`role`), `is_active` = VALUES(`is_active`);

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
INSERT INTO `sla_policies` (`id`, `policy_name`, `category`, `priority`, `response_time_hours`, `resolution_time_hours`, `escalation_email`, `is_active`)
VALUES
(1, 'Critical Emergency SLA', 'all', 'critical', 1, 4, 'critical-alerts@shethlg.local', 1),
(2, 'High Priority SLA', 'all', 'high', 2, 8, 'escalations@shethlg.local', 1),
(3, 'Standard Medium SLA', 'all', 'medium', 4, 24, 'facility-support@shethlg.local', 1),
(4, 'Low Priority Routine SLA', 'all', 'low', 12, 48, 'admin-log@shethlg.local', 1),
(5, 'Biomedical Waste Critical SLA', 'biomedical_waste', 'critical', 1, 2, 'biomedical-alert@shethlg.local', 1),
(6, 'Biomedical Waste High SLA', 'biomedical_waste', 'high', 2, 4, 'biomedical-alert@shethlg.local', 1),
(7, 'Plumbing High SLA', 'plumbing', 'high', 2, 6, 'engineering@shethlg.local', 1),
(8, 'Electrical Critical SLA', 'electrical', 'critical', 1, 3, 'engineering@shethlg.local', 1),
(9, 'Infection Control Critical SLA', 'cleanliness', 'critical', 1, 2, 'infection-control@shethlg.local', 1)
ON DUPLICATE KEY UPDATE `policy_name` = VALUES(`policy_name`), `response_time_hours` = VALUES(`response_time_hours`), `resolution_time_hours` = VALUES(`resolution_time_hours`);

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

-- 10. Checklist Template, Sections & Items
INSERT INTO `checklist_templates` (`id`, `code`, `title`, `description`, `category`, `version`, `is_published`, `is_active`, `created_by`)
VALUES
(1, 'CHK-ICU-DAILY', 'Daily Intensive Care Unit (ICU) Hygiene & Safety Audit', 'Comprehensive infection control, medical gas, linen sanitation and biomedical waste protocol', 'infection_control', 1, 1, 1, 1),
(2, 'CHK-GEN-ROUNDS', 'General Ward Routine Facility Inspection', 'Daily inspection of patient amenities, washroom cleanliness, water supply, and floor hygiene', 'cleanliness', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `checklist_sections` (`id`, `template_id`, `title`, `sort_order`)
VALUES
(1, 1, 'Infection Control & Sterility', 1),
(2, 1, 'Biomedical Waste Management', 2),
(3, 1, 'Emergency Equipment & Medical Gas', 3),
(4, 2, 'Patient Bed & Ward Cleanliness', 1),
(5, 2, 'Washroom & Sanitation Fixtures', 2)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `checklist_items` (`id`, `section_id`, `item_code`, `question_text`, `response_type`, `weight`, `is_critical`, `is_required`, `severity`, `sort_order`)
VALUES
(1, 1, 'ICU-IC-01', 'Are hand hygiene sanitizer dispensers filled and functioning at all entry points?', 'yes_no_na', 2.00, 1, 1, 'critical', 1),
(2, 1, 'ICU-IC-02', 'Is high-touch surface disinfection (bed rails, IV poles, monitors) completed with chlorine/alcohol solution?', 'yes_no_na', 2.00, 1, 1, 'critical', 2),
(3, 2, 'ICU-BW-01', 'Are color-coded BMW bins (Yellow, Red, Blue, White puncture-proof) lined and under 75% capacity?', 'yes_no_na', 3.00, 1, 1, 'critical', 1),
(4, 2, 'ICU-BW-02', 'Is the biohazard barcoded bag sealing register updated for the current shift?', 'yes_no_na', 1.00, 0, 1, 'medium', 2),
(5, 3, 'ICU-EQ-01', 'Are central oxygen pipeline and suction pressures within green calibrated ranges?', 'yes_no_na', 3.00, 1, 1, 'critical', 1),
(6, 3, 'ICU-EQ-02', 'Is the crash cart defibrillator battery checked and test-shock certified?', 'yes_no_na', 2.00, 1, 1, 'critical', 2),
(7, 4, 'GEN-CLN-01', 'Are bed sheets, pillow covers and mattress protectors clean, spotless and sanitized?', 'yes_no_na', 1.50, 0, 1, 'medium', 1),
(8, 4, 'GEN-CLN-02', 'Is the ward floor mopped with hospital-grade disinfectant per schedule?', 'yes_no_na', 1.50, 0, 1, 'medium', 2),
(9, 5, 'GEN-WSH-01', 'Are washroom toilets, taps, and drains clean with running water and odor-free?', 'yes_no_na', 2.00, 0, 1, 'high', 1)
ON DUPLICATE KEY UPDATE `question_text` = VALUES(`question_text`);

-- 11. Schedule Rules
INSERT INTO `schedule_rules` (`id`, `rule_name`, `template_id`, `building_id`, `floor_id`, `target_ward_id`, `shift_id`, `assigned_user_id`, `frequency`, `selected_weekdays`, `start_date`, `is_active`, `created_by`)
VALUES
(1, 'Daily Morning ICU Audit', 1, 1, 3, 1, 1, 2, 'daily', 'mon,tue,wed,thu,fri,sat,sun', CURRENT_DATE, 1, 1),
(2, 'Daily Evening General Ward Audit', 2, 1, 2, 2, 2, 3, 'daily', 'mon,tue,wed,thu,fri,sat,sun', CURRENT_DATE, 1, 1)
ON DUPLICATE KEY UPDATE `rule_name` = VALUES(`rule_name`);

-- 12. Hospital Governance Settings
INSERT INTO `hospital_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `is_system`)
VALUES
    ('hospital_name', 'Sheth L.G. General Hospital, Ahmedabad', 'general', 'Official hospital facility legal entity name', 1),
    ('hospital_subtitle', 'Multi-Floor Facility Inspection & Complaint Command Center', 'general', 'Portal subtitle and facility operational motto', 1),
    ('hospital_address', 'AMC Medical Education Trust, Maninagar, Ahmedabad - 380008, Gujarat, India', 'general', 'Hospital physical address for official reports and audits', 1),
    ('app_timezone', 'Asia/Kolkata', 'general', 'Standard clinical operational timezone (IST +05:30)', 1),
    ('hospital_city', 'Ahmedabad', 'general', 'City location', 1),
    ('hospital_state', 'Gujarat', 'general', 'State location', 1),
    ('hospital_country', 'India', 'general', 'Country location', 1),
    ('hospital_latitude', '22.9984', 'geofence', 'Hospital main building GPS latitude', 1),
    ('hospital_longitude', '72.6025', 'geofence', 'Hospital main building GPS longitude', 1),
    ('default_geofence_radius_meters', '150', 'attendance', 'Default radial geofence boundary in meters around hospital facility', 1),
    ('geofence_radius_meters', '150', 'attendance', 'Legacy fallback geofence radius', 1),
    ('max_acceptable_gps_accuracy_meters', '100', 'attendance', 'Maximum acceptable GPS accuracy threshold in meters before flagging', 1),
    ('default_shift_grace_minutes', '15', 'attendance', 'Permitted check-in grace window in minutes before marking attendance as late', 1),
    ('require_gps_verification', '1', 'attendance', 'Enforce browser GPS geolocation verification during supervisor check-in', 1),
    ('schedule_generation_days_ahead', '7', 'inspections', 'Number of days in advance to generate inspection schedule instances', 1),
    ('auto_mark_overdue_hours', '2', 'inspections', 'Hours after scheduled window end before marking incomplete rounds as overdue', 1),
    ('default_pass_threshold_percentage', '80.0', 'inspections', 'Minimum score percentage required to pass a facility quality audit', 1),
    ('enforce_qr_scan_per_ward', '1', 'inspections', 'Require rotating QR verification scan before conducting ward checklist', 1),
    ('inspection_sla_alert_hours', '2', 'inspections', 'Hours before inspection window expires to trigger warning', 1),
    ('default_complaint_response_hours', '4', 'complaints', 'Default response time commitment target in hours for standard grievances', 1),
    ('default_complaint_resolution_hours', '24', 'complaints', 'Default resolution time commitment target in hours for standard grievances', 1),
    ('auto_escalate_overdue_tickets', '1', 'complaints', 'Automatically elevate priority on tickets exceeding resolution deadlines', 1),
    ('enable_in_app_notifications', '1', 'notifications', 'Broadcast real-time operational notifications in top facility bar', 1),
    ('enable_email_alerts', '0', 'notifications', 'Dispatch email notifications for critical hospital events (Disabled in demo)', 1),
    ('alert_on_critical_failure', '1', 'notifications', 'Generate immediate notification upon checklist critical finding', 1),
    ('alert_on_sla_breach', '1', 'notifications', 'Generate immediate notification upon complaint SLA breach', 1),
    ('audit_log_retention_days', '365', 'retention_and_backups', 'Minimum retention period in days for append-only audit trail logs', 1),
    ('evidence_file_retention_days', '180', 'retention_and_backups', 'Retention period in days for complaint and inspection photo evidence', 1),
    ('backup_retention_days', '30', 'retention_and_backups', 'Retention period in days for automated hospital database backups', 1),
    ('automated_backup_enabled', '1', 'retention_and_backups', 'Daily automated database backup cron execution safeguard', 1)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 13. Backup Jobs
INSERT INTO `backup_jobs` (`id`, `job_name`, `job_type`, `schedule_cron`, `retention_days`, `encrypt_backup`, `status`)
VALUES 
    (1, 'Daily Nightly Database Snapshot', 'database_only', '0 2 * * *', 7, 1, 'active'),
    (2, 'Weekly Full System & Evidence Backup', 'full_with_evidence', '0 3 * * 0', 30, 1, 'active')
ON DUPLICATE KEY UPDATE 
    `job_name` = VALUES(`job_name`),
    `retention_days` = VALUES(`retention_days`),
    `encrypt_backup` = VALUES(`encrypt_backup`);

-- Register Migrations in schema_migrations
INSERT INTO `schema_migrations` (`version`, `name`, `executed_at`, `execution_time_ms`)
VALUES 
    ('001', '001_create_initial_schema.sql', NOW(), 25),
    ('002', '002_enhance_facility_and_floor_plan.sql', NOW(), 10),
    ('003', '003_enhance_shifts_and_attendance.sql', NOW(), 12),
    ('004', '004_enhance_checklists_and_scheduling.sql', NOW(), 14),
    ('005', '005_enhance_complaints_and_sla.sql', NOW(), 18),
    ('006', '006_settings_and_security_hardening.sql', NOW(), 15),
    ('007', '007_backup_and_disaster_recovery.sql', NOW(), 12)
ON DUPLICATE KEY UPDATE `executed_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;
