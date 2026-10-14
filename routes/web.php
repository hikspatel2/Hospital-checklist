<?php
declare(strict_types=1);

/**
 * Web Application Route Definitions
 * @var App\Core\Router $router
 */

use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\ChecklistController;
use App\Controllers\ComplaintController;
use App\Controllers\FacilityController;
use App\Controllers\FloorPlanController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\InspectionController;
use App\Controllers\QrController;
use App\Controllers\ScheduleController;
use App\Controllers\AuditLogController;
use App\Controllers\BackupController;
use App\Controllers\ReportController;
use App\Controllers\SettingController;
use App\Controllers\ShiftController;
use App\Controllers\SlaPolicyController;
use App\Controllers\SupervisorController;
use App\Controllers\UserController;

// ------------------------------------------------------------------------------
// Public & System Telemetry Routes
// ------------------------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);
$router->get('/api/health', [HealthController::class, 'index']);

// ------------------------------------------------------------------------------
// Guest Authentication Routes (Blocked if already authenticated)
// ------------------------------------------------------------------------------
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], ['guest']);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], ['guest', 'csrf']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword'], ['guest']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], ['guest', 'csrf']);

// ------------------------------------------------------------------------------
// Authenticated Personnel Routes (Protected by 'auth')
// ------------------------------------------------------------------------------
$router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);
$router->get('/profile', [AuthController::class, 'profile'], ['auth']);
$router->get('/change-password', [AuthController::class, 'showChangePassword'], ['auth']);
$router->post('/change-password', [AuthController::class, 'changePassword'], ['auth', 'csrf']);

// ------------------------------------------------------------------------------
// Facility Supervisor Routes (Scoped assignments & horizontal authorization)
// ------------------------------------------------------------------------------
$router->get('/supervisor/overview', [SupervisorController::class, 'overview'], ['auth', 'supervisor']);
$router->get('/supervisor/{id}/overview', [SupervisorController::class, 'overview'], ['auth', 'supervisor']);

// ------------------------------------------------------------------------------
// Administrator Personnel Management Routes (Strictly 'admin')
// ------------------------------------------------------------------------------
$router->get('/users', [UserController::class, 'index'], ['auth', 'admin']);
$router->get('/users/create', [UserController::class, 'create'], ['auth', 'admin']);
$router->post('/users', [UserController::class, 'store'], ['auth', 'admin', 'csrf']);
$router->get('/users/{id}/edit', [UserController::class, 'edit'], ['auth', 'admin']);
$router->put('/users/{id}', [UserController::class, 'update'], ['auth', 'admin', 'csrf']);
$router->post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'], ['auth', 'admin', 'csrf']);
$router->post('/users/{id}/reset-password', [UserController::class, 'resetPassword'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 3: Hospital Facility Infrastructure (Buildings, Floors, Wards, Locations)
// ------------------------------------------------------------------------------
// Buildings
$router->get('/buildings', [FacilityController::class, 'buildingsIndex'], ['auth']);
$router->post('/admin/buildings', [FacilityController::class, 'buildingsStore'], ['auth', 'admin', 'csrf']);
$router->get('/buildings/{id}', [FacilityController::class, 'buildingsShow'], ['auth']);
$router->post('/admin/buildings/{id}', [FacilityController::class, 'buildingsUpdate'], ['auth', 'admin', 'csrf']);
$router->post('/admin/buildings/{id}/toggle-status', [FacilityController::class, 'buildingsToggleStatus'], ['auth', 'admin', 'csrf']);

// Floors
$router->post('/admin/floors', [FacilityController::class, 'floorsStore'], ['auth', 'admin', 'csrf']);
$router->get('/floors/{id}', [FacilityController::class, 'floorsShow'], ['auth']);
$router->post('/admin/floors/{id}', [FacilityController::class, 'floorsUpdate'], ['auth', 'admin', 'csrf']);
$router->post('/admin/floors/{id}/toggle-status', [FacilityController::class, 'floorsToggleStatus'], ['auth', 'admin', 'csrf']);
$router->post('/admin/floors/{id}/upload-plan', [FloorPlanController::class, 'uploadPlan'], ['auth', 'admin', 'csrf']);

// Wards
$router->get('/wards', [FacilityController::class, 'wardsIndex'], ['auth']);
$router->get('/admin/wards/create', [FacilityController::class, 'wardsCreate'], ['auth', 'admin']);
$router->post('/admin/wards', [FacilityController::class, 'wardsStore'], ['auth', 'admin', 'csrf']);
$router->get('/wards/{id}', [FacilityController::class, 'wardsShow'], ['auth']);
$router->post('/admin/wards/{id}', [FacilityController::class, 'wardsUpdate'], ['auth', 'admin', 'csrf']);
$router->post('/admin/wards/{id}/toggle-status', [FacilityController::class, 'wardsToggleStatus'], ['auth', 'admin', 'csrf']);

// Inspection Locations (Checkpoints)
$router->get('/locations', [FacilityController::class, 'locationsIndex'], ['auth']);
$router->post('/admin/locations', [FacilityController::class, 'locationsStore'], ['auth', 'admin', 'csrf']);
$router->post('/admin/locations/{id}/toggle-status', [FacilityController::class, 'locationsToggleStatus'], ['auth', 'admin', 'csrf']);

// Supervisor Facility Allocations
$router->get('/supervisors', [FacilityController::class, 'supervisorAssignmentsIndex'], ['auth']);
$router->post('/admin/supervisor-assignments', [FacilityController::class, 'supervisorAssignmentsStore'], ['auth', 'admin', 'csrf']);
$router->post('/admin/supervisor-assignments/{id}/revoke', [FacilityController::class, 'supervisorAssignmentsRevoke'], ['auth', 'admin', 'csrf']);

// Interactive Floor Plans & Dynamic Status Overlays
$router->get('/floor-plans', [FloorPlanController::class, 'interactive'], ['auth']);
$router->get('/api/floors/{id}/plan-areas', [FloorPlanController::class, 'apiFloorAreas'], ['auth']);
$router->post('/api/floor-plans/areas/{id}/status', [FloorPlanController::class, 'apiUpdateAreaStatus'], ['auth', 'csrf']);
$router->post('/api/floor-plans/areas', [FloorPlanController::class, 'apiSaveArea'], ['auth', 'admin', 'csrf']);

// Cascaded Facility AJAX Endpoints
$router->get('/api/buildings/{id}/floors', [FacilityController::class, 'apiFloorsByBuilding'], ['auth']);
$router->get('/api/floors/{id}/wards', [FacilityController::class, 'apiWardsByFloor'], ['auth']);

// ------------------------------------------------------------------------------
// Phase 4: Shift Management, Supervisor Rostering & Overnight Shifts
// ------------------------------------------------------------------------------
$router->get('/shifts', [ShiftController::class, 'index'], ['auth', 'admin']);
$router->post('/admin/shifts', [ShiftController::class, 'store'], ['auth', 'admin', 'csrf']);
$router->post('/admin/shifts/{id}', [ShiftController::class, 'update'], ['auth', 'admin', 'csrf']);
$router->post('/admin/shifts/{id}/toggle-status', [ShiftController::class, 'toggleStatus'], ['auth', 'admin', 'csrf']);

// Shift Roster Allocations
$router->get('/shift-assignments', [ShiftController::class, 'assignmentsIndex'], ['auth']);
$router->post('/admin/shift-assignments', [ShiftController::class, 'assignmentsStore'], ['auth', 'admin', 'csrf']);
$router->post('/admin/shift-assignments/{id}/toggle-status', [ShiftController::class, 'assignmentsToggleStatus'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 4: QR Codes (Opaque Revocable Tokens & SVG Badges)
// ------------------------------------------------------------------------------
$router->get('/qr-codes', [QrController::class, 'index'], ['auth', 'admin']);
$router->post('/admin/qr-codes/generate', [QrController::class, 'generate'], ['auth', 'admin', 'csrf']);
$router->post('/admin/qr-codes/{id}/revoke', [QrController::class, 'revoke'], ['auth', 'admin', 'csrf']);
$router->get('/qr-codes/svg', [QrController::class, 'renderSvg'], ['auth']);
$router->get('/api/qr-codes/verify', [QrController::class, 'apiVerify'], ['auth']);

// ------------------------------------------------------------------------------
// Phase 4: GPS Attendance Verification & Audit Overrides
// ------------------------------------------------------------------------------
$router->get('/attendance', [AttendanceController::class, 'checkinView'], ['auth']);
$router->post('/attendance/checkin', [AttendanceController::class, 'processCheckin'], ['auth', 'csrf']);
$router->post('/attendance/checkout', [AttendanceController::class, 'processCheckout'], ['auth', 'csrf']);
$router->get('/admin/attendance', [AttendanceController::class, 'adminIndex'], ['auth', 'admin']);
$router->post('/admin/attendance/{id}/override', [AttendanceController::class, 'adminOverride'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 5: Versioned Checklists, Items, and Immutability Snapshots
// ------------------------------------------------------------------------------
$router->get('/checklists', [ChecklistController::class, 'index'], ['auth']);
$router->post('/admin/checklists', [ChecklistController::class, 'store'], ['auth', 'admin', 'csrf']);
$router->get('/checklists/{id}/edit', [ChecklistController::class, 'edit'], ['auth', 'admin']);
$router->post('/admin/checklists/{id}/items', [ChecklistController::class, 'addItem'], ['auth', 'admin', 'csrf']);
$router->post('/admin/checklists/{id}/items/{itemId}/delete', [ChecklistController::class, 'deleteItem'], ['auth', 'admin', 'csrf']);
$router->post('/admin/checklists/{id}/branch-version', [ChecklistController::class, 'branchVersion'], ['auth', 'admin', 'csrf']);
$router->post('/admin/checklists/{id}/toggle-status', [ChecklistController::class, 'toggleStatus'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 5: Custom Inspection Schedules & Recurrence Engine
// ------------------------------------------------------------------------------
$router->get('/schedules', [ScheduleController::class, 'schedulesIndex'], ['auth']);
$router->get('/schedules/rules', [ScheduleController::class, 'rulesIndex'], ['auth', 'admin']);
$router->post('/admin/schedules/rules', [ScheduleController::class, 'rulesStore'], ['auth', 'admin', 'csrf']);
$router->post('/admin/schedules/rules/{id}/toggle-status', [ScheduleController::class, 'rulesToggleStatus'], ['auth', 'admin', 'csrf']);
$router->post('/admin/schedules/generate', [ScheduleController::class, 'triggerGeneration'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 5: Supervisor Inspection Inbox, Dynamic Execution & Transactional Submit
// ------------------------------------------------------------------------------
$router->get('/inspections/my', [InspectionController::class, 'myInspections'], ['auth']);
$router->get('/inspections/execute/{scheduleId}', [InspectionController::class, 'execute'], ['auth']);
$router->post('/inspections/execute/{scheduleId}/submit', [InspectionController::class, 'submit'], ['auth', 'csrf']);
$router->get('/inspections/{id}', [InspectionController::class, 'show'], ['auth']);

// ------------------------------------------------------------------------------
// Phase 6: Complaint Management & SLA Workflow
// ------------------------------------------------------------------------------
$router->get('/complaints', [ComplaintController::class, 'index'], ['auth']);
$router->get('/complaints/create', [ComplaintController::class, 'create'], ['auth']);
$router->post('/complaints/store', [ComplaintController::class, 'store'], ['auth', 'csrf']);
$router->get('/complaints/{id}', [ComplaintController::class, 'show'], ['auth']);
$router->post('/complaints/{id}/assign', [ComplaintController::class, 'assign'], ['auth', 'csrf']);
$router->post('/complaints/{id}/status', [ComplaintController::class, 'updateStatus'], ['auth', 'csrf']);
$router->post('/complaints/{id}/hold', [ComplaintController::class, 'putOnHold'], ['auth', 'csrf']);
$router->post('/complaints/{id}/resolve', [ComplaintController::class, 'resolve'], ['auth', 'csrf']);
$router->post('/complaints/{id}/reopen', [ComplaintController::class, 'reopen'], ['auth', 'csrf']);
$router->post('/complaints/{id}/close', [ComplaintController::class, 'close'], ['auth', 'csrf']);
$router->post('/complaints/{id}/comment', [ComplaintController::class, 'addComment'], ['auth', 'csrf']);
$router->post('/complaints/{id}/delete', [ComplaintController::class, 'delete'], ['auth', 'admin', 'csrf']);
$router->post('/complaints/{id}/restore', [ComplaintController::class, 'restore'], ['auth', 'admin', 'csrf']);
$router->get('/complaints/evidence/{id}', [ComplaintController::class, 'downloadEvidence'], ['auth']);

// SLA Policies Configuration (Admin Only)
$router->get('/sla-policies', [SlaPolicyController::class, 'index'], ['auth', 'admin']);
$router->post('/sla-policies/store', [SlaPolicyController::class, 'store'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 8: Reports, Secure CSV Exports, Print Views & Immutable Audit Trail
// ------------------------------------------------------------------------------
$router->get('/reports', [ReportController::class, 'index'], ['auth']);
$router->get('/reports/export', [ReportController::class, 'exportCsv'], ['auth']);
$router->get('/reports/print', [ReportController::class, 'printView'], ['auth']);

// Append-only Audit Trail Console (Admin Only)
$router->get('/audit-logs', [AuditLogController::class, 'index'], ['auth', 'admin']);
$router->get('/audit-logs/{id}', [AuditLogController::class, 'show'], ['auth', 'admin']);

// ------------------------------------------------------------------------------
// Phase 9: Hospital Settings & Security Hardening (Admin Only)
// ------------------------------------------------------------------------------
$router->get('/settings', [SettingController::class, 'index'], ['auth', 'admin']);
$router->post('/settings', [SettingController::class, 'update'], ['auth', 'admin', 'csrf']);

// ------------------------------------------------------------------------------
// Phase 10: Secure Backup, Retention & Disaster Recovery (Admin Only)
// ------------------------------------------------------------------------------
$router->get('/backups', [BackupController::class, 'index'], ['auth', 'admin']);
$router->post('/backups/create', [BackupController::class, 'create'], ['auth', 'admin', 'csrf']);
$router->get('/backups/{filename}/download', [BackupController::class, 'download'], ['auth', 'admin']);
$router->post('/backups/{filename}/verify', [BackupController::class, 'verify'], ['auth', 'admin', 'csrf']);
$router->post('/backups/{filename}/test-restore', [BackupController::class, 'testRestore'], ['auth', 'admin', 'csrf']);
$router->post('/backups/{filename}/restore', [BackupController::class, 'restore'], ['auth', 'admin', 'csrf']);
$router->post('/backups/cleanup', [BackupController::class, 'cleanup'], ['auth', 'admin', 'csrf']);


