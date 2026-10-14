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
