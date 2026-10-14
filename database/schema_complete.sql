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
-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Multi-Floor Inspection & Complaint Facility Management System
-- Migration 002: Enhance Facility Schema for Floor Plans & Supervisor Assignments
-- ==============================================================================

-- 1. Enhance supervisor_assignments with explicit effective date bounds
ALTER TABLE `supervisor_assignments`
    ADD COLUMN IF NOT EXISTS `effective_from` DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER `assigned_at`,
    ADD COLUMN IF NOT EXISTS `effective_to` DATE DEFAULT NULL AFTER `effective_from`,
    ADD INDEX IF NOT EXISTS `idx_sa_effective` (`effective_from`, `effective_to`);

-- 2. Enhance floor_plan_areas with svg_element_id and description notes
ALTER TABLE `floor_plan_areas`
    ADD COLUMN IF NOT EXISTS `svg_element_id` VARCHAR(50) DEFAULT NULL AFTER `area_label`,
    ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `color_code`,
    ADD INDEX IF NOT EXISTS `idx_fpa_svg` (`svg_element_id`);
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
-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Migration 005: Enhance Complaints, SLA Policies & Secure Evidence
-- Phase 6: Complaint Management & SLA Workflow
-- UTF8mb4 / InnoDB / Strict Foreign Keys / Asia/Kolkata Timestamps
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Enhance SLA Policies (Support Category + Priority combinations)
ALTER TABLE `sla_policies` 
    ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'all' AFTER `policy_name`;

-- Drop original unique priority constraint and add compound unique key
ALTER TABLE `sla_policies` DROP INDEX `priority`;
ALTER TABLE `sla_policies` ADD UNIQUE KEY `uk_sla_cat_priority` (`category`, `priority`);

-- Seed standard category-specific policies if they do not exist
INSERT INTO `sla_policies` (`policy_name`, `category`, `priority`, `response_time_hours`, `resolution_time_hours`, `escalation_email`, `is_active`)
VALUES
    ('Biomedical Waste Critical SLA', 'biomedical_waste', 'critical', 1, 2, 'biomedical-alert@shethlg.local', 1),
    ('Biomedical Waste High SLA', 'biomedical_waste', 'high', 2, 4, 'biomedical-alert@shethlg.local', 1),
    ('Plumbing High SLA', 'plumbing', 'high', 2, 6, 'engineering@shethlg.local', 1),
    ('Electrical Critical SLA', 'electrical', 'critical', 1, 3, 'engineering@shethlg.local', 1),
    ('Infection Control Critical SLA', 'cleanliness', 'critical', 1, 2, 'infection-control@shethlg.local', 1)
ON DUPLICATE KEY UPDATE `is_active` = VALUES(`is_active`);

-- 2. Enhance Complaints Table
ALTER TABLE `complaints`
    MODIFY COLUMN `status` ENUM('open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'escalated', 'closed') NOT NULL DEFAULT 'open',
    ADD COLUMN `assigned_to_user_id` BIGINT UNSIGNED DEFAULT NULL AFTER `reported_by`,
    ADD COLUMN `response_due_at` DATETIME DEFAULT NULL AFTER `due_date`,
    ADD COLUMN `resolution_due_at` DATETIME DEFAULT NULL AFTER `response_due_at`,
    ADD COLUMN `first_responded_at` DATETIME DEFAULT NULL AFTER `resolution_due_at`,
    ADD COLUMN `is_response_breached` TINYINT(1) NOT NULL DEFAULT 0 AFTER `first_responded_at`,
    ADD COLUMN `is_resolution_breached` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_response_breached`,
    ADD COLUMN `resolution_notes` TEXT DEFAULT NULL AFTER `resolved_at`,
    ADD COLUMN `resolved_by` BIGINT UNSIGNED DEFAULT NULL AFTER `resolution_notes`,
    ADD COLUMN `reopened_at` DATETIME DEFAULT NULL AFTER `closed_at`,
    ADD COLUMN `reopened_by` BIGINT UNSIGNED DEFAULT NULL AFTER `reopened_at`,
    ADD COLUMN `reopen_reason` VARCHAR(255) DEFAULT NULL AFTER `reopened_by`,
    ADD INDEX `idx_comp_assigned` (`assigned_to_user_id`),
    ADD INDEX `idx_comp_breached` (`is_response_breached`, `is_resolution_breached`),
    ADD CONSTRAINT `fk_comp_assigned` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_comp_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_comp_reopener` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- 3. Enhance Complaint Updates Table
ALTER TABLE `complaint_updates`
    ADD COLUMN `update_type` ENUM('comment', 'status_change', 'assignment', 'reassignment', 'hold', 'reopen', 'resolution', 'closure') NOT NULL DEFAULT 'comment' AFTER `user_id`,
    ADD COLUMN `evidence_stored_name` VARCHAR(255) DEFAULT NULL AFTER `evidence_file_path`,
    ADD COLUMN `evidence_size_bytes` BIGINT UNSIGNED DEFAULT NULL AFTER `evidence_stored_name`,
    ADD COLUMN `evidence_mime_type` VARCHAR(100) DEFAULT NULL AFTER `evidence_size_bytes`,
    ADD COLUMN `evidence_hash` VARCHAR(64) DEFAULT NULL AFTER `evidence_mime_type`;

-- 4. Create Complaint Evidence Table
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

SET FOREIGN_KEY_CHECKS = 1;
-- ------------------------------------------------------------------------------
-- Sheth L.G. General Hospital, Ahmedabad
-- Migration 006: Settings & Security Hardening
-- Adds password reset tokens table and seeds standard hospital settings
-- ------------------------------------------------------------------------------

-- 1. Password Reset Tokens Table (Single-use, 15-minute expiry)
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

-- 2. Seed Default Hospital Settings Across All 7 Governance Categories
INSERT INTO `hospital_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `is_system`)
VALUES
    -- Category: General & Timezone
    ('hospital_name', 'Sheth L.G. General Hospital, Ahmedabad', 'general', 'Official hospital facility legal entity name', 1),
    ('hospital_subtitle', 'Multi-Floor Facility Inspection & Complaint Command Center', 'general', 'Portal subtitle and facility operational motto', 1),
    ('hospital_address', 'AMC Medical Education Trust, Maninagar, Ahmedabad - 380008, Gujarat, India', 'general', 'Hospital physical address for official reports and audits', 1),
    ('app_timezone', 'Asia/Kolkata', 'general', 'Standard clinical operational timezone (IST +05:30)', 1),

    -- Category: Attendance & Geofencing
    ('default_geofence_radius_meters', '150', 'attendance', 'Default radial geofence boundary in meters around hospital facility', 1),
    ('max_acceptable_gps_accuracy_meters', '100', 'attendance', 'Maximum acceptable GPS accuracy threshold in meters before flagging', 1),
    ('default_shift_grace_minutes', '15', 'attendance', 'Permitted check-in grace window in minutes before marking attendance as late', 1),
    ('require_gps_verification', '1', 'attendance', 'Enforce browser GPS geolocation verification during supervisor check-in', 1),

    -- Category: Inspections & Scheduling Defaults
    ('schedule_generation_days_ahead', '7', 'inspections', 'Number of days in advance to generate inspection schedule instances', 1),
    ('auto_mark_overdue_hours', '2', 'inspections', 'Hours after scheduled window end before marking incomplete rounds as overdue', 1),
    ('default_pass_threshold_percentage', '80.0', 'inspections', 'Minimum score percentage required to pass a facility quality audit', 1),
    ('enforce_qr_scan_per_ward', '1', 'inspections', 'Require rotating QR verification scan before conducting ward checklist', 1),

    -- Category: Grievance & SLA Targets
    ('default_complaint_response_hours', '4', 'complaints', 'Default response time commitment target in hours for standard grievances', 1),
    ('default_complaint_resolution_hours', '24', 'complaints', 'Default resolution time commitment target in hours for standard grievances', 1),
    ('auto_escalate_overdue_tickets', '1', 'complaints', 'Automatically elevate priority on tickets exceeding resolution deadlines', 1),

    -- Category: Notification Preferences
    ('enable_in_app_notifications', '1', 'notifications', 'Broadcast real-time operational notifications in top facility bar', 1),
    ('enable_email_alerts', '0', 'notifications', 'Dispatch email notifications for critical hospital events (Disabled in demo)', 1),
    ('alert_on_critical_failure', '1', 'notifications', 'Generate immediate notification upon checklist critical finding', 1),
    ('alert_on_sla_breach', '1', 'notifications', 'Generate immediate notification upon complaint SLA breach', 1),

    -- Category: Retention & Backups
    ('audit_log_retention_days', '365', 'retention_and_backups', 'Minimum retention period in days for append-only audit trail logs', 1),
    ('evidence_file_retention_days', '180', 'retention_and_backups', 'Retention period in days for complaint and inspection photo evidence', 1),
    ('backup_retention_days', '30', 'retention_and_backups', 'Retention period in days for automated hospital database backups', 1),
    ('automated_backup_enabled', '1', 'retention_and_backups', 'Daily automated database backup cron execution safeguard', 1),

    -- Category: Maintenance Mode & Clinical Alerts
    ('maintenance_mode_enabled', '0', 'maintenance', 'Toggle hospital system maintenance mode (Restricts non-admins with 503)', 1),
    ('maintenance_notice_message', 'Sheth L.G. General Hospital Portal is undergoing scheduled clinical maintenance. For emergency ward coordination, contact the control room at +91-79-25461380.', 'maintenance', 'Public announcement displayed during maintenance mode', 1)

ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `setting_group` = VALUES(`setting_group`);
-- ==============================================================================
-- SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
-- Multi-Floor Inspection & Complaint Facility Management System
-- Migration 007: Secure Backup, Retention, and Disaster Recovery Ledger
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. Enhance `backup_logs` table with metadata, encryption and verification flags
-- ------------------------------------------------------------------------------
ALTER TABLE `backup_logs`
    ADD COLUMN IF NOT EXISTS `archive_type` ENUM('database_only', 'full_with_evidence', 'incremental') NOT NULL DEFAULT 'database_only' AFTER `backup_job_id`,
    ADD COLUMN IF NOT EXISTS `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `file_hash_sha256`,
    ADD COLUMN IF NOT EXISTS `tables_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_encrypted`,
    ADD COLUMN IF NOT EXISTS `records_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `tables_count`,
    ADD COLUMN IF NOT EXISTS `storage_location` VARCHAR(100) NOT NULL DEFAULT 'local_restricted' AFTER `records_count`,
    ADD COLUMN IF NOT EXISTS `verified_at` DATETIME DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `restore_tested_at` DATETIME DEFAULT NULL AFTER `verified_at`;

-- ------------------------------------------------------------------------------
-- 2. Create `restore_logs` table for Disaster Recovery Auditing
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

-- ------------------------------------------------------------------------------
-- 3. Seed Standard Backup Jobs in `backup_jobs`
-- ------------------------------------------------------------------------------
INSERT INTO `backup_jobs` (`id`, `job_name`, `job_type`, `schedule_cron`, `retention_days`, `encrypt_backup`, `status`)
VALUES 
    (1, 'Daily Nightly Database Snapshot', 'database_only', '0 2 * * *', 7, 1, 'active'),
    (2, 'Weekly Full System & Evidence Backup', 'full_with_evidence', '0 3 * * 0', 30, 1, 'active')
ON DUPLICATE KEY UPDATE 
    `job_name` = VALUES(`job_name`),
    `retention_days` = VALUES(`retention_days`),
    `encrypt_backup` = VALUES(`encrypt_backup`);

-- Register Migration in schema_migrations
INSERT INTO `schema_migrations` (`version`, `name`, `executed_at`, `execution_time_ms`)
VALUES ('007', '007_backup_and_disaster_recovery.sql', NOW(), 12)
ON DUPLICATE KEY UPDATE `executed_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;
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

