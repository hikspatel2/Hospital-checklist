-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Multi-Floor Inspection & Complaint Facility Management System
-- Migration 001: Initial Relational Database Schema
-- UTF8mb4 / InnoDB Engine / Strict Foreign Keys / Asia/Kolkata Timestamps
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 0. Schema Migrations Tracking Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `execution_time_ms` INT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 1. Users Table (Admin & Supervisor Roles)
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
    `qr_code_identifier` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_wards_floor` (`floor_id`),
    INDEX `idx_wards_type` (`ward_type`),
    INDEX `idx_wards_deleted` (`deleted_at`),
    CONSTRAINT `fk_wards_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Inspection Locations Table (Granular checkpoints within wards/floors)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_locations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `location_code` VARCHAR(50) NOT NULL UNIQUE,
    `location_name` VARCHAR(150) NOT NULL,
    `location_type` ENUM('washroom', 'patient_bed', 'nursing_station', 'corridor', 'doctor_cabin', 'waste_disposal', 'storage', 'emergency_exit', 'general') NOT NULL DEFAULT 'general',
    `qr_code_identifier` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_locations_ward` (`ward_id`),
    INDEX `idx_locations_qr` (`qr_code_identifier`),
    INDEX `idx_locations_deleted` (`deleted_at`),
    CONSTRAINT `fk_locations_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Floor Plan Areas (Interactive Map Overlays)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `floor_plan_areas` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `area_label` VARCHAR(100) NOT NULL,
    `shape_type` ENUM('polygon', 'rect', 'circle') NOT NULL DEFAULT 'polygon',
    `coordinates_json` LONGTEXT NOT NULL,
    `color_code` VARCHAR(20) DEFAULT '#0f2c59',
    `inspection_status` ENUM('compliant', 'warning', 'critical', 'pending', 'inactive') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_fpa_floor` (`floor_id`),
    INDEX `idx_fpa_ward` (`ward_id`),
    INDEX `idx_fpa_status` (`inspection_status`),
    CONSTRAINT `fk_fpa_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_fpa_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Shifts Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shifts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shift_code` VARCHAR(20) NOT NULL UNIQUE,
    `shift_name` VARCHAR(100) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `grace_period_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_shifts_status` (`status`),
    INDEX `idx_shifts_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. Supervisor Building/Ward Assignments Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `supervisor_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED DEFAULT NULL,
    `floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_by` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sa_user` (`user_id`),
    INDEX `idx_sa_ward` (`ward_id`),
    INDEX `idx_sa_building` (`building_id`),
    INDEX `idx_sa_status` (`status`),
    CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sa_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. User Shift Assignments Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_shift_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `shift_id` BIGINT UNSIGNED NOT NULL,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_usa_user` (`user_id`),
    INDEX `idx_usa_shift` (`shift_id`),
    INDEX `idx_usa_dates` (`effective_from`, `effective_to`),
    CONSTRAINT `fk_usa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_usa_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. Checklist Templates Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checklist_templates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_code` VARCHAR(50) NOT NULL UNIQUE,
    `template_name` VARCHAR(150) NOT NULL,
    `category` ENUM('hygiene', 'safety', 'equipment', 'infection_control', 'facilities', 'general') NOT NULL DEFAULT 'hygiene',
    `description` TEXT DEFAULT NULL,
    `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_ct_category` (`category`),
    INDEX `idx_ct_status` (`status`),
    INDEX `idx_ct_deleted` (`deleted_at`),
    CONSTRAINT `fk_ct_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. Checklist Template Versions Table (Immutable historical snapshots)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checklist_template_versions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `changelog` TEXT DEFAULT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 1,
    `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_template_version` (`template_id`, `version_number`),
    INDEX `idx_ctv_current` (`template_id`, `is_current`),
    CONSTRAINT `fk_ctv_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ctv_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. Checklist Items Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checklist_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_version_id` BIGINT UNSIGNED NOT NULL,
    `item_label` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `item_type` ENUM('pass_fail', 'rating_1_to_5', 'yes_no_na', 'text', 'numeric', 'multiple_choice') NOT NULL DEFAULT 'pass_fail',
    `options_json` LONGTEXT DEFAULT NULL,
    `requires_evidence` TINYINT(1) NOT NULL DEFAULT 0,
    `is_critical` TINYINT(1) NOT NULL DEFAULT 0,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ci_version` (`template_version_id`),
    INDEX `idx_ci_order` (`display_order`),
    CONSTRAINT `fk_ci_version` FOREIGN KEY (`template_version_id`) REFERENCES `checklist_template_versions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. Schedule Rules Table (Recurrence engine)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedule_rules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `frequency` ENUM('hourly', 'shift_based', 'daily', 'weekly', 'monthly', 'custom') NOT NULL DEFAULT 'shift_based',
    `custom_cron_expression` VARCHAR(100) DEFAULT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_building_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_floor_id` BIGINT UNSIGNED DEFAULT NULL,
    `target_ward_id` BIGINT UNSIGNED DEFAULT NULL,
    `status` ENUM('active', 'paused', 'expired') NOT NULL DEFAULT 'active',
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sr_template` (`template_id`),
    INDEX `idx_sr_status` (`status`),
    CONSTRAINT `fk_sr_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_building` FOREIGN KEY (`target_building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_floor` FOREIGN KEY (`target_floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_ward` FOREIGN KEY (`target_ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. Inspection Schedules Table (Concrete scheduled instances)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_schedules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `schedule_rule_id` BIGINT UNSIGNED DEFAULT NULL,
    `template_version_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `inspection_location_id` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `scheduled_date` DATE NOT NULL,
    `scheduled_time_window_start` TIME DEFAULT NULL,
    `scheduled_time_window_end` TIME DEFAULT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'missed', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_is_date_status` (`scheduled_date`, `status`),
    INDEX `idx_is_assigned_user` (`assigned_user_id`),
    INDEX `idx_is_ward` (`ward_id`),
    CONSTRAINT `fk_is_rule` FOREIGN KEY (`schedule_rule_id`) REFERENCES `schedule_rules` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_version` FOREIGN KEY (`template_version_id`) REFERENCES `checklist_template_versions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_location` FOREIGN KEY (`inspection_location_id`) REFERENCES `inspection_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_is_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. Inspections Table (Core inspection execution records)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspections` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_number` VARCHAR(50) NOT NULL UNIQUE,
    `schedule_id` BIGINT UNSIGNED DEFAULT NULL,
    `template_version_id` BIGINT UNSIGNED NOT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `inspection_location_id` BIGINT UNSIGNED DEFAULT NULL,
    `inspector_user_id` BIGINT UNSIGNED NOT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `status` ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    `score_percentage` DECIMAL(5,2) DEFAULT NULL,
    `total_items` INT UNSIGNED NOT NULL DEFAULT 0,
    `passed_items` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_items` INT UNSIGNED NOT NULL DEFAULT 0,
    `critical_issues_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
    `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
    `gps_accuracy_meters` DECIMAL(8,2) DEFAULT NULL,
    `qr_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_insp_status` (`status`),
    INDEX `idx_insp_ward` (`ward_id`),
    INDEX `idx_insp_inspector` (`inspector_user_id`),
    INDEX `idx_insp_started` (`started_at`),
    INDEX `idx_insp_deleted` (`deleted_at`),
    CONSTRAINT `fk_insp_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `inspection_schedules` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_version` FOREIGN KEY (`template_version_id`) REFERENCES `checklist_template_versions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_location` FOREIGN KEY (`inspection_location_id`) REFERENCES `inspection_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_inspector` FOREIGN KEY (`inspector_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_insp_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. Inspection Responses Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_responses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` BIGINT UNSIGNED NOT NULL,
    `checklist_item_id` BIGINT UNSIGNED NOT NULL,
    `item_label_snapshot` VARCHAR(255) NOT NULL,
    `response_value` VARCHAR(255) NOT NULL,
    `score_earned` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_compliant` TINYINT(1) NOT NULL DEFAULT 1,
    `is_critical_failure` TINYINT(1) NOT NULL DEFAULT 0,
    `remarks` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ir_inspection` (`inspection_id`),
    INDEX `idx_ir_item` (`checklist_item_id`),
    INDEX `idx_ir_compliance` (`is_compliant`),
    CONSTRAINT `fk_ir_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ir_item` FOREIGN KEY (`checklist_item_id`) REFERENCES `checklist_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. Inspection Evidence Table (Photos, media, documents)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_evidence` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` BIGINT UNSIGNED NOT NULL,
    `inspection_response_id` BIGINT UNSIGNED DEFAULT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_hash` VARCHAR(64) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
    `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ie_inspection` (`inspection_id`),
    INDEX `idx_ie_response` (`inspection_response_id`),
    CONSTRAINT `fk_ie_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ie_response` FOREIGN KEY (`inspection_response_id`) REFERENCES `inspection_responses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ie_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 18. Inspection Status History Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspection_status_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` BIGINT UNSIGNED NOT NULL,
    `previous_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` BIGINT UNSIGNED NOT NULL,
    `comments` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ish_inspection` (`inspection_id`),
    CONSTRAINT `fk_ish_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ish_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. Attendance Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `shift_id` BIGINT UNSIGNED NOT NULL,
    `attendance_date` DATE NOT NULL,
    `check_in_time` DATETIME NOT NULL,
    `check_out_time` DATETIME DEFAULT NULL,
    `status` ENUM('present', 'late', 'half_day', 'absent', 'flagged_review') NOT NULL DEFAULT 'present',
    `verification_status` ENUM('verified', 'suspicious_gps', 'admin_override', 'manual_entry') NOT NULL DEFAULT 'verified',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY `uk_user_date_shift` (`user_id`, `attendance_date`, `shift_id`),
    INDEX `idx_att_user` (`user_id`),
    INDEX `idx_att_date` (`attendance_date`),
    INDEX `idx_att_status` (`status`),
    INDEX `idx_att_deleted` (`deleted_at`),
    CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_att_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. QR Tokens Table (Cryptographically hashed rotating tokens)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token_type` ENUM('ward_checkin', 'location_inspection', 'supervisor_attendance') NOT NULL,
    `entity_type` ENUM('building', 'floor', 'ward', 'location', 'user') NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `raw_code_snippet` VARCHAR(100) NOT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
    `revoked_at` DATETIME DEFAULT NULL,
    `revoked_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qrt_hash` (`token_hash`),
    INDEX `idx_qrt_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_qrt_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_qrt_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 21. Attendance Verification Events (GPS + QR audit trail)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance_verification_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attendance_id` BIGINT UNSIGNED NOT NULL,
    `event_type` ENUM('check_in', 'check_out', 'waypoint_scan') NOT NULL,
    `qr_token_id` BIGINT UNSIGNED DEFAULT NULL,
    `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
    `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
    `gps_accuracy_meters` DECIMAL(8,2) DEFAULT NULL,
    `distance_to_hospital_meters` DECIMAL(10,2) DEFAULT NULL,
    `is_within_geofence` TINYINT(1) NOT NULL DEFAULT 1,
    `is_flagged` TINYINT(1) NOT NULL DEFAULT 0,
    `flag_reason` VARCHAR(255) DEFAULT NULL,
    `device_user_agent` TEXT DEFAULT NULL,
    `client_ip` VARCHAR(45) NOT NULL,
    `admin_override_by` BIGINT UNSIGNED DEFAULT NULL,
    `admin_override_reason` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ave_att` (`attendance_id`),
    INDEX `idx_ave_flag` (`is_flagged`),
    CONSTRAINT `fk_ave_att` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ave_qr` FOREIGN KEY (`qr_token_id`) REFERENCES `qr_tokens` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ave_admin` FOREIGN KEY (`admin_override_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 22. SLA Policies Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sla_policies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `policy_name` VARCHAR(100) NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL UNIQUE,
    `response_time_hours` INT UNSIGNED NOT NULL,
    `resolution_time_hours` INT UNSIGNED NOT NULL,
    `escalation_email` VARCHAR(150) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 23. Complaints Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `inspection_id` BIGINT UNSIGNED DEFAULT NULL,
    `inspection_response_id` BIGINT UNSIGNED DEFAULT NULL,
    `building_id` BIGINT UNSIGNED NOT NULL,
    `floor_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `inspection_location_id` BIGINT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` ENUM('cleanliness', 'plumbing', 'electrical', 'biomedical_waste', 'civil_damage', 'pest_control', 'supplies', 'staff_behavior', 'other') NOT NULL DEFAULT 'cleanliness',
    `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `sla_policy_id` BIGINT UNSIGNED DEFAULT NULL,
    `due_date` DATETIME DEFAULT NULL,
    `status` ENUM('open', 'assigned', 'in_progress', 'resolved', 'escalated', 'closed') NOT NULL DEFAULT 'open',
    `reported_by` BIGINT UNSIGNED NOT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `closed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
    `deletion_reason` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_comp_status` (`status`),
    INDEX `idx_comp_priority` (`priority`),
    INDEX `idx_comp_ward` (`ward_id`),
    INDEX `idx_comp_due` (`due_date`),
    INDEX `idx_comp_deleted` (`deleted_at`),
    CONSTRAINT `fk_comp_insp` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_resp` FOREIGN KEY (`inspection_response_id`) REFERENCES `inspection_responses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_loc` FOREIGN KEY (`inspection_location_id`) REFERENCES `inspection_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_sla` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_comp_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 24. Complaint Assignments Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaint_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` BIGINT UNSIGNED NOT NULL,
    `assigned_to_user_id` BIGINT UNSIGNED NOT NULL,
    `assigned_by_user_id` BIGINT UNSIGNED NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('active', 'reassigned', 'completed') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ca_complaint` (`complaint_id`),
    INDEX `idx_ca_assigned_to` (`assigned_to_user_id`),
    CONSTRAINT `fk_ca_comp` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ca_assignee` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ca_assigner` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 25. Complaint Updates Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaint_updates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `previous_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) DEFAULT NULL,
    `comment` TEXT NOT NULL,
    `evidence_file_path` VARCHAR(255) DEFAULT NULL,
    `is_internal_only` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cu_complaint` (`complaint_id`),
    CONSTRAINT `fk_cu_comp` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_cu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 26. Audit Logs Table (Append-only security log)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `event_action` VARCHAR(100) NOT NULL,
    `entity_table` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED DEFAULT NULL,
    `old_values` LONGTEXT DEFAULT NULL,
    `new_values` LONGTEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_al_user` (`user_id`),
    INDEX `idx_al_entity` (`entity_table`, `entity_id`),
    INDEX `idx_al_action` (`event_action`),
    INDEX `idx_al_created` (`created_at`),
    CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 27. Notifications Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('inspection_assigned', 'sla_warning', 'complaint_escalation', 'attendance_alert', 'system') NOT NULL DEFAULT 'system',
    `link_url` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notif_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 28. Hospital Settings Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospital_settings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 1,
    `updated_by` BIGINT UNSIGNED DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hs_group` (`setting_group`),
    CONSTRAINT `fk_hs_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 29. Backup Jobs Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_jobs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_name` VARCHAR(100) NOT NULL,
    `job_type` ENUM('database_only', 'full_with_evidence', 'incremental') NOT NULL DEFAULT 'database_only',
    `schedule_cron` VARCHAR(50) NOT NULL DEFAULT '0 2 * * *',
    `retention_days` INT UNSIGNED NOT NULL DEFAULT 7,
    `encrypt_backup` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active', 'paused') NOT NULL DEFAULT 'active',
    `last_run_at` DATETIME DEFAULT NULL,
    `next_run_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bj_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 30. Backup Logs Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `backup_job_id` BIGINT UNSIGNED DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `file_hash_sha256` VARCHAR(64) NOT NULL,
    `status` ENUM('success', 'failed', 'verified', 'restored') NOT NULL DEFAULT 'success',
    `execution_duration_sec` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `error_message` TEXT DEFAULT NULL,
    `triggered_by` VARCHAR(50) NOT NULL DEFAULT 'cron',
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bl_status` (`status`),
    INDEX `idx_bl_created` (`created_at`),
    CONSTRAINT `fk_bl_job` FOREIGN KEY (`backup_job_id`) REFERENCES `backup_jobs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_bl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
