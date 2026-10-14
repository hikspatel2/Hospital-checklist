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
