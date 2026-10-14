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
