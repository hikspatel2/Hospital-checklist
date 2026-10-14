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
