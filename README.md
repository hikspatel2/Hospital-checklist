# Sheth L.G. General Hospital, Ahmedabad
## Multi-Floor Inspection & Complaint Facility Management System

A robust, enterprise-grade Hospital Facility Inspection, Attendance, and SLA Complaint Management System built with PHP 8.x, MySQL/MariaDB, PDO, Bootstrap 5, and vanilla JavaScript.

Developed strictly in accordance with the **Global Project Rules** for Sheth L.G. General Hospital, Ahmedabad, Gujarat.

---

## 1. System Architecture

The application implements a clean **Model-View-Controller (MVC)** design pattern with zero heavy external runtime dependencies, ensuring seamless deployment on **Hostinger Web Hosting (cPanel / hPanel)** and standard Apache/PHP environments without Node.js, Docker, or VPS requirements.

```
/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php          # Main inspection matrix & facility dashboard
│   │   ├── HealthController.php        # Safe telemetry & operational diagnostics
│   │   ├── AuthController.php          # Login, logout, profile & password change
│   │   ├── UserController.php          # Admin-only employee provisioning & management
│   │   └── SupervisorController.php    # Scoped supervisor work overview (IDOR-safe)
│   ├── Core/
│   │   ├── App.php                     # Central bootstrap & environment loader
│   │   ├── Auth.php                    # Authentication, rate limit, lockout & RBAC
│   │   ├── Database.php                # PDO singleton, transactions, safe logging
│   │   ├── Router.php                  # Parametric router with middleware support
│   │   ├── Request.php                 # Sanitized HTTP request abstraction
│   │   ├── Response.php                # HTTP response with security headers
│   │   ├── Controller.php              # Base controller with validation & view helpers
│   │   ├── View.php                    # Layout & template rendering engine
│   │   ├── Session.php                 # Secure session manager (HttpOnly, SameSite)
│   │   ├── Csrf.php                    # Cryptographic CSRF token verification
│   │   ├── Logger.php                  # Append-only file logger with data masking
│   │   ├── ErrorHandler.php            # Production-safe global exception handler
│   │   ├── Helpers.php                 # Global utilities & Asia/Kolkata timestamps
│   │   └── Middleware/
│   │       ├── AuthMiddleware.php      # Protects authenticated routes
│   │       ├── GuestMiddleware.php     # Redirects authenticated from guest views
│   │       ├── AdminMiddleware.php     # Strict administrator privilege barrier
│   │       └── SupervisorMiddleware.php # Supervisor/admin authorization barrier
│   ├── Models/
│   │   ├── BaseModel.php               # PDO query builder, soft deletes, pagination
│   │   ├── User.php                    # User accounts, lockout & supervisor assignments
│   │   └── AuditLog.php                # Append-only security audit trail
│   └── Views/
│       ├── layouts/
│       │   ├── main.php                # Primary responsive HTML5 shell
│       │   ├── header.php              # Navy top navigation, live clock & user dropdown
│       │   ├── sidebar.php             # 16-module facility sidebar navigation
│       │   └── footer.php              # Hospital credentials & timezone status
│       ├── auth/
│       │   ├── login.php               # Hospital branded sign-in with lockout notice
│       │   ├── profile.php             # Employee profile, shift & assignments
│       │   └── change_password.php     # Current-password verified password change
│       ├── users/
│       │   ├── index.php               # Admin personnel list with filters & reset modal
│       │   ├── create.php              # Provision new hospital employee form
│       │   └── edit.php                # Edit employee profile & role assignment
│       ├── supervisor/
│       │   └── overview.php            # Scoped supervisor assigned work overview
│       ├── home/
│       │   └── index.php               # Facility matrix dashboard view
│       └── health/
│           └── index.php               # Credential-safe system diagnostics view
├── config/
│   ├── app.php                         # Application & session configuration
│   └── database.php                    # PDO connection & charset configuration
├── database/
│   ├── migrations/
│   │   └── 001_create_initial_schema.sql # 30 relational tables & foreign keys
│   └── seeds/
│       └── dev_seed.sql                # Non-production demo hospital data
├── public/                             # Public Web Document Root (Hostinger public_html)
│   ├── .htaccess                       # URL rewriting & security headers
│   ├── index.php                       # Public front-controller entry point
│   └── assets/
│       ├── css/
│       │   └── style.css               # Navy-blue hospital design system
│       └── js/
│           └── app.js                  # Vanilla JS drawer & CSRF handler
├── scripts/
│   ├── migrate.php                     # CLI database migration runner
│   ├── seed.php                        # CLI development seeder
│   └── test_phase2.php                 # Automated Phase 2 security & RBAC test suite
├── storage/                            # Kept OUTSIDE public web root
│   ├── backups/                        # Encrypted automated daily backups
│   ├── logs/                           # Append-only daily audit logs
│   └── uploads/                        # Inspection evidence & attachments
├── .env.example                        # Safe environment template
├── .gitignore                          # Git exclusions for secrets and uploads
└── README.md                           # Documentation & installation guide
```

---

## 2. Technology Requirements

- **PHP**: 8.1, 8.2, or 8.3
  - Extensions: `pdo`, `pdo_mysql`, `mbstring`, `json`, `openssl`
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB 10.4+ (InnoDB engine, `utf8mb4`)
- **Web Server**: Apache with `mod_rewrite` enabled
- **Timezone**: `Asia/Kolkata` (mandatory IST standard throughout)
- **Frontend**: HTML5, Vanilla CSS3, Bootstrap 5.3, Bootstrap Icons, Vanilla JS (No Node.js build step needed)

---

## 3. Relational Database Architecture

Phase 1 provides **30 relational tables** with explicit foreign keys, indexes, and soft-deletion columns:

| Module Group | Relational Tables |
|---|---|
| **Users & RBAC** | `users`, `schema_migrations` |
| **Hospital Facility Hierarchy** | `buildings`, `floors`, `wards`, `inspection_locations`, `floor_plan_areas` |
| **Shifts & Supervisors** | `shifts`, `supervisor_assignments`, `user_shift_assignments` |
| **Checklists & Recurrence** | `checklist_templates`, `checklist_template_versions`, `checklist_items`, `schedule_rules`, `inspection_schedules` |
| **Inspection Execution** | `inspections`, `inspection_responses`, `inspection_evidence`, `inspection_status_history` |
| **Attendance & Security** | `attendance`, `qr_tokens`, `attendance_verification_events` |
| **Complaints & SLA Management**| `sla_policies`, `complaints`, `complaint_assignments`, `complaint_updates` |
| **Governance & Operations** | `audit_logs`, `notifications`, `hospital_settings`, `backup_jobs`, `backup_logs` |

> [!IMPORTANT]
> **Accidental Cascade Protection**: All foreign key constraints use `ON DELETE RESTRICT ON UPDATE CASCADE`. Historical inspections, attendance logs, and complaints can never be cascade-deleted.

---

## 4. Local Development Setup

### Option A: Using PHP Built-in Server (Fastest)

1. Clone or copy the project into your local directory:
   ```bash
   cd "d:/hospital php website"
   ```

2. Copy the environment configuration:
   ```bash
   copy .env.example .env
   ```
   Edit `.env` to configure your local MySQL database credentials (`DB_NAME`, `DB_USER`, `DB_PASSWORD`).

3. Create the database in MySQL:
   ```sql
   CREATE DATABASE hospital_inspection_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Run migrations and demo seeds:
   ```bash
   php scripts/migrate.php
   php scripts/seed.php
   ```

5. Launch the PHP built-in web server pointing to the `public/` directory:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. Open your browser:
   - Dashboard: `http://127.0.0.1:8000`
   - Health Check: `http://127.0.0.1:8000/health`
   - Health JSON: `http://127.0.0.1:8000/health?format=json`

---

### Option B: Using XAMPP / WampServer / Apache

1. Place the project folder into your web server directory (e.g. `C:\xampp\htdocs\hospital`).
2. In Apache `httpd-vhosts.conf`, configure a VirtualHost with `DocumentRoot` pointing to the `/public` folder:
   ```apache
   <VirtualHost *:80>
       ServerName hospital.local
       DocumentRoot "C:/xampp/htdocs/hospital/public"
       <Directory "C:/xampp/htdocs/hospital/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Add `127.0.0.1 hospital.local` to your Windows `hosts` file (`C:\Windows\System32\drivers\etc\hosts`).
4. Run `php scripts/migrate.php` and `php scripts/seed.php`.

---

## 5. Hostinger Production Installation Guide

Hostinger uses standard **hPanel / cPanel** with Apache web servers.

### Step 1: Directory Structure on Hostinger
On Hostinger shared hosting, place the root project files **outside** `public_html` so that sensitive files (`.env`, `storage/`, `database/`, `app/`) cannot be accessed over the web:

```
/home/u123456789/
├── hospital_app/               <-- Place app, config, database, scripts, storage, .env here
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── storage/
│   ├── .env
│   └── ...
└── public_html/                <-- Place contents of public/ here
    ├── .htaccess
    ├── index.php
    └── assets/
```

### Step 2: Configure `public_html/index.php` for Hostinger
In `public_html/index.php`, update the `$basePath` to reference the application folder:
```php
$basePath = dirname(__DIR__) . '/hospital_app';
```

### Step 3: MySQL Database Setup via Hostinger hPanel
1. Navigate to **hPanel -> Databases -> MySQL Databases**.
2. Create a new database and user:
   - Database: `u123456789_hospital`
   - Username: `u123456789_admin`
   - Password: `[StrongRandomPassword]`
3. In hPanel File Manager, edit `hospital_app/.env`:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-hospital-domain.com
   DB_HOST=localhost
   DB_NAME=u123456789_hospital
   DB_USER=u123456789_admin
   DB_PASSWORD=[StrongRandomPassword]
   ```
4. Import `database/migrations/001_create_initial_schema.sql` via **phpMyAdmin** or run `php scripts/migrate.php` via **SSH Terminal**.

### Step 4: Storage Directory Permissions
Ensure permissions on `hospital_app/storage/` are set to `0755` so PHP can write logs and backups:
```bash
chmod -R 755 hospital_app/storage
```

### Step 5: Automated Daily Backups via Hostinger Cron Jobs
In hPanel -> **Advanced -> Cron Jobs**, configure a daily job at 02:00 IST:
```bash
/usr/bin/php /home/u123456789/hospital_app/scripts/backup.php > /dev/null 2>&1
```

---

## 6. Demo Non-Production Credentials

When seeded via `php scripts/seed.php`:

| Role | Email | Password | Designation |
|---|---|---|---|
| **Admin** | `admin@shethlg.local` | `Admin@12345` | Chief Medical Officer / Admin |
| **Supervisor 1** | `supervisor1@shethlg.local` | `Supervisor@123` | Senior Floor Supervisor |
| **Supervisor 2** | `supervisor2@shethlg.local` | `Supervisor@123` | Shift Facility Supervisor |

> [!WARNING]
> These credentials are for development/staging environments only. In production, real admin accounts must be provisioned with high-entropy unique passwords and multi-factor authorization.

---

## 7. Security Hardening & Data Retention Rules

- **CSRF Defense**: All POST, PUT, DELETE requests automatically require a valid `csrf_token`.
- **Session Protection**: `SLGGH_SESSID` cookie configured with `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS. Automatic session rotation every 30 minutes; inactivity timeout at 60 minutes.
- **XSS Prevention**: Central `e()` escaping utility with `ENT_QUOTES | ENT_SUBSTITUTE` UTF-8 encoding.
- **SQL Injection Prevention**: 100% PDO prepared statements.
- **Audit Logs**: Append-only audit table logging user ID, table, action, IP address, and JSON diffs.
- **Safe Error Handling**: Detailed traces in development; production displays a clean hospital-branded error page without leaking database names, credentials, or internal file paths.

---

## 8. Phase 4: Shift Management & QR/GPS Attendance Verification

### Features
1. **Hospital Shift Administration**:
   - Start and end time scheduling, grace period enforcement (default 15 mins), and overnight shift auto-detection (e.g. 22:00 to 06:00 crossing midnight).
   - Safe deactivation of shifts with historical attendance protection.
2. **Supervisor Shift Rosters**:
   - Effective start and end date scheduling.
   - Overlap prevention algorithm: prevents assigning the same supervisor to overlapping shifts or time windows.
   - Cascaded facility allocation (Building -> Floor -> Ward).
3. **Opaque Revocable QR Tokens**:
   - Physical tokens generated as `LG-QR-SEC-[HEX32]`.
   - Never embed confidential database IDs, passwords, or patient PII.
   - Stored in database as SHA-256 hashes (`token_hash`).
   - Admin single-click regeneration and instant revocation.
4. **GPS Geofence Attendance Verification**:
   - Browser Geolocation API over HTTPS.
   - Server-side Haversine great-circle distance verification against hospital campus centroid (`22.9984, 72.6025`, 150m radius) or custom ward/location radius.
   - GPS accuracy thresholding: coordinates with accuracy > 100m are flagged for review.
   - Idempotency defense: repeated mobile check-in requests return existing session without duplicate records.
   - Auditable Admin Override: status corrections require a mandatory explanation reason (min 10 characters) logged to immutable audit history.

### Compatible QR Library Installation Instructions
The system includes a pure PHP vector SVG QR generator that operates with **zero external dependencies** on standard Hostinger shared hosting plans.

For enhanced raster rendering, install `bacon/bacon-qr-code`:
```bash
composer require bacon/bacon-qr-code
```
The application dynamically detects the library via `class_exists('\BaconQrCode\Writer')`. If installed, it automatically leverages BaconQrCode; otherwise, it seamlessly falls back to the built-in pure PHP vector SVG engine.

### Browser GPS Spoofing Reality & Dual Verification
> [!IMPORTANT]
> **Client-Side GPS Limitation Notice**:
> Browser geolocation coordinates can be simulated or spoofed via browser Developer Tools (Sensors panel) or mobile GPS mocking apps. Browser GPS alone **cannot independently guarantee physical presence**.
>
> To mitigate this limitation, Sheth L.G. General Hospital utilizes a **Dual Verification Model**:
> 1. A physical, opaque, revocable QR code printed and mounted inside the specific hospital ward or checkpoint.
> 2. Geofence radius validation with GPS accuracy thresholding (>100m flagged) and server-side client IP logging.
> 3. Administrative audit console with mandatory override explanations.

### Running Phase 4 Automated Tests
```bash
php scripts/test_phase4.php
```
All 11 verification tests validate shift boundaries, grace periods, overlap prevention, opaque tokens, SVG generation, revocation, geofencing, accuracy thresholds, idempotency, and admin overrides.

---

## 9. Phase 5: Versioned Checklists & Custom Inspection Scheduling

### Core Architectural Features
1. **Admin Checklist Template Engine & Facility Scoping**:
   - Templates organized across standard hospital categories: `hygiene`, `safety`, `equipment`, `infection_control`, `facilities`, `general`.
   - Scoped specifically to facility levels: Campus-wide, Building, Floor, Ward, or individual Inspection Location.
2. **Dynamic Checklist Item Types & Strict Severities**:
   - Supported item types: `pass_fail`, `yes_no_na`, `numeric` (with min/max ranges), `text`, and `multiple_choice` (JSON options).
   - Strict severity ratings: `low`, `medium`, `high`, `critical`.
   - Mandatory response enforcement (`is_required`) and evidence requirements (`requires_evidence`).
3. **Template Snapshot Immutability & Safe Version Branching**:
   - Checklists referenced in completed inspections are permanently locked to preserve audit integrity.
   - Modifications automatically trigger version branching (`v1` -> `v2`, `v3`, etc.) with changelog documentation.
   - Completed inspections retain their exact snapshot reference (`template_version_id`) and historical response labels (`item_label_snapshot`).
4. **Custom Recurrence Engine**:
   - Schedules configured by recurrence rules (`daily`, `weekdays`, `weekly`, `shift_based`, `monthly`, `custom`).
   - Granular constraints: selected weekdays (Mon-Sun), shift assignments, time window constraints (`time_window_start`/`end`), and holiday exclusions JSON.
   - State management: `active`, `paused`, or `archived`.
5. **Deterministic Idempotent Schedule Generation**:
   - Every schedule instance is keyed with an SHA-256 idempotency hash: `RULE-{id}-DATE-{Y-m-d}-W-{ward}-L-{loc}-S-{shift}`.
   - Running the schedule generation multiple times per day guarantees zero duplicate schedules.
   - Operational states tracked: `scheduled`, `pending`, `in_progress`, `completed`, `missed`, `overdue`, `cancelled`.
6. **Supervisor Inspection Execution & Transactional Final Submission**:
   - Supervisors only see assigned, active inspections within their authorized facility scope.
   - Dynamic checklist form with color-coded severity tags and photo evidence indicators.
   - Server-side mandatory response validation prevents incomplete submissions.
   - Submissions execute inside atomic `PDO::beginTransaction()` / `commit()` blocks, computing compliance score % and logging critical issues to immutable audit trails.

### Hostinger Cron Job Setup for Automated Schedule Generation
To automate daily inspection schedule creation and monitor overdue tasks, configure a cron job in Hostinger **hPanel -> Advanced -> Cron Jobs**:

```bash
# Run daily at 00:05 IST to generate today's scheduled inspections
/usr/bin/php /home/u123456789/hospital_app/scripts/cron_generate_schedules.php --date=today > /dev/null 2>&1

# (Optional) Run every hour to flag overdue inspections and retry failed tasks
/usr/bin/php /home/u123456789/hospital_app/scripts/cron_generate_schedules.php --overdue-only > /dev/null 2>&1
```

For local testing or manual schedule generation:
```bash
# Generate schedules for today
php scripts/cron_generate_schedules.php

# Generate schedules for a specific date
php scripts/cron_generate_schedules.php --date=2026-10-15

# Flag overdue inspections and retry failed generations
php scripts/cron_generate_schedules.php --overdue-only
```

### Running Phase 5 Automated Tests
Execute the comprehensive Phase 5 test suite covering all recurrence rules, weekday filters, holiday exclusions, idempotency keys, duplicate prevention, template immutability, version branching, required field validation, and historical snapshot preservation:
```bash
php scripts/test_phase5.php
```
Test results verify 100% compliance across all 11 test assertions.

---

## 10. Phase 6: Complaint Management & SLA Workflow

### Core Architectural Features
1. **Hospital Grievance & Facility Ticket Engine**:
   - Tickets linked to facility locations: Building, Floor, Ward, Inspection Location, and linked Inspections.
   - Standard categories: `cleanliness`, `plumbing`, `electrical`, `biomedical_waste`, `civil_damage`, `pest_control`, `supplies`, `staff_behavior`, `hvac`, `other`.
   - Formatted ticket references: `CMP-YYYYMMDD-XXXX`.
2. **Automated SLA Response & Resolution Targets**:
   - Multi-tier SLA policies configured by **Category** and **Priority** (`critical`, `high`, `medium`, `low`).
   - Category-specific overrides (e.g. Biomedical Waste Critical requires 1h response / 2h resolution; Standard Medium requires 4h response / 24h resolution).
   - Automated deadline calculation: `response_due_at` and `resolution_due_at`.
   - Real-time overdue and breach detection: flags response breaches and resolution breaches with calculated overdue hours.
3. **Status Workflow State Machine**:
   - Supported lifecycle states: `Open`, `Assigned`, `In Progress`, `On Hold`, `Resolved`, `Closed`, and `Escalated`.
   - Strict transition rules prevent illegal status bypasses.
   - Authorized reopening with mandatory justification: resolved or closed complaints can be reopened by authorized personnel with a clear audit reason.
4. **Assignment & Chronological Update History**:
   - Full reassignment history preserved in `complaint_assignments` with active/reassigned status.
   - Chronological timeline updates in `complaint_updates` capturing status changes, operational comments, holds, reassignments, and proof of resolution attachments.
5. **Secure Evidence Uploads Outside Public Web Root**:
   - Stored in `storage/uploads/complaints/` (strictly outside the public web root).
   - Strict MIME validation (`finfo_open(FILEINFO_MIME_TYPE)`) and deep magic byte inspection for JPEG, PNG, WEBP, and PDF files.
   - Anti-malware and script scanning: detects embedded PHP tags (`<?php`, `<?=`), `<script>`, or executable signatures.
   - Never trusts user-supplied filenames or extensions: derives canonical safe extensions from verified MIME types and generates randomized filenames (`bin2hex(random_bytes(16)) . '.' . $ext`).
   - Cryptographic integrity: SHA-256 hash calculated and stored for each evidence file.
   - Authorized streaming delivery: files served only via authenticated, access-checked streaming controller (`GET /complaints/evidence/{id}`).
6. **Data Retention & Soft Deletion**:
   - Complaints are never dropped or deleted via ordinary UI actions.
   - Admin-only soft deletion records `deleted_at`, `deleted_by`, and `deletion_reason` while preserving all related inspection responses, assignments, and audit logs.
   - One-click restoration workflow for archived complaints.

### Running Phase 6 Automated Tests
```bash
php scripts/test_phase6.php
```
All 11 tests validate category/priority structs, SLA policy lookups, deadline calculations, overdue breach detection, status transition state machine, reassignment history, chronological updates, authorized reopening, secure evidence upload validation, upload security defenses (rejecting spoofed extensions, oversize files, and PHP scripts), evidence authorization barriers, and soft deletion/restoration.

---

## 11. Phase 7: Hospital Dashboard & Inspection Matrix

### Core Architectural Features
1. **Visual Hierarchy & Hospital Branding**:
   - Recreates the command matrix layout for **Sheth L.G. General Hospital, Ahmedabad**.
   - Fully integrated responsive navigation with dynamic role-based module filtering.
2. **Database-Backed KPI Aggregations (Zero Hardcoding)**:
   - **Today's Inspection Metrics**: Total scheduled rounds, completed inspections, pending tasks, overdue rounds, missed rounds, compliance rate percentage, and average quality score.
   - **Supervisor Attendance Metrics**: Verified present (QR + GPS geofenced), late arrivals, flagged/suspicious GPS check-ins, and unverified manual check-ins.
   - **Strict Verification Enforcement**: Manual/unverified entries are explicitly isolated and never counted as verified present.
   - **Facility Grievance & SLA Breach Metrics**: Active complaints, critical/high priority count, response SLA breaches, resolution SLA breaches, and tickets resolved today.
3. **Cartesian Product & Double-Counting Prevention**:
   - Inspection, attendance, and complaint metrics are queried through independent, isolated aggregation queries.
   - Strictly avoids Cartesian join multiplication between disparate tables.
4. **Multi-Tier Building / Floor / Ward Inspection Matrix**:
   - Structured visual hierarchy grouped by **Building &rarr; Floor &rarr; Ward**.
   - Status indicators per ward: `Completed` (with compliance score %), `In Progress`, `Pending`, `Overdue`, `Missed`, and `Not Scheduled`.
   - Supervisor on duty and shift allocation.
   - Active complaints count with real-time hazard badges.
   - Quick action shortcuts: execute inspection round, review submitted report, or lodge grievance.
5. **Supervisor Permission Scoping & Isolation**:
   - Administrators monitor the entire hospital campus across all buildings and floors.
   - Supervisors are strictly scoped to their assigned wards and facility shifts, preventing unauthorized data leakage.
6. **Multi-Criteria Filter Engine**:
   - Dynamic filters: Date (`Asia/Kolkata` IST), Shift, Building, Floor, Ward, and Supervisor.
   - Parameterized query bindings and strict integer type-casting prevent SQL injection.
7. **Empty State & Mobile Responsiveness**:
   - Graceful empty state when filters yield zero matching checkpoints.
   - Responsive cards and horizontally-scrollable matrix tables for tablets and mobile devices.

### Running Phase 7 Automated Tests
```bash
php scripts/test_phase7.php
```
All 11 tests validate inspection KPI aggregation accuracy, unverified attendance exclusion, complaint & SLA breach calculations, Cartesian join double-counting prevention, facility/date/shift filter application, supervisor permission scoping, matrix status indicators & badge rendering, empty state handling, Asia/Kolkata timezone standard consistency, multi-tier matrix grouping, and filter sanitization.

---

## 12. Phase 8: Reports, Secure Exports & Immutable Audit Trail

### Core Architectural Features
1. **Multi-Tab Facility Analytics & Reporting Hub (`/reports`)**:
   - **Inspections Summary**: Aggregates daily, weekly, and monthly rounds, completed inspections, average quality scores (%), passed items, failed items, critical findings, and QR geofence verification rates.
   - **Floor-wise & Ward-wise Completion**: Computes total scheduled, completed, pending, and missed rounds per building, floor, and ward, with real-time percentage completion rates and average scores.
   - **Supervisor Attendance & Verification Audit**: Audits staff check-ins, shifts, on-duty status, geofence compliance distances (meters), GPS verification statuses (`verified`, `suspicious_gps`, `admin_override`), and review flags.
   - **Exceptions & Missed Inspections**: Tracks pending, in-progress, missed, and overdue inspection schedules with real-time alert badges.
   - **Checklist Failures & Critical Findings**: Forensic extraction of non-compliant checklist responses, severity levels, response values, inspector remarks, and critical failure indicators.
   - **Complaints Status & SLA Breach Analysis**: Real-time tracking of ticket priorities, status transitions, response/resolution due dates, and SLA breach calculations.

2. **CWE-1236 CSV Formula Injection Defense**:
   - Spreadsheets (Microsoft Excel, LibreOffice Calc, Google Sheets) execute formula expressions when cell values start with `=`, `+`, `-`, `@`, `\t`, or `\r`.
   - `CsvExportService::sanitizeCell()` neutralizes all dangerous triggers by prepending a single quote (`'`), ensuring formulas are safely rendered as plain text.
   - Preserves all safe numerical values, booleans, and legitimate hyphenated texts (e.g. `ICU - Bed 4`).
   - UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) is prepended to ensure seamless Unicode and Gujarati character rendering in Microsoft Excel.

3. **Export Security & Ephemeral Storage Architecture**:
   - Enforces identical server-side role permissions as on-screen views: Supervisors can only export data for their assigned wards or conducted tasks; Admins retain campus-wide access.
   - Streams CSV exports directly to output (`php://output`) with strict anti-caching and attachment download headers.
   - Temporary file generation (`storage/temp/exports/`) is isolated strictly outside the public web root with automated self-purging of files older than 1 hour.
   - Every report export event is forensically recorded in the immutable audit trail with actor ID, report type, filename, rows count, and client timestamp.

4. **Print-Friendly Views & Browser PDF Export (`/reports/print`)**:
   - Dedicated print-optimized CSS layout (`@media print` rules) formatted for standard A4 paper.
   - Includes official **Sheth L.G. General Hospital** letterhead, Ahmedabad address, generation timestamp (`Asia/Kolkata` IST), applied filter parameters, and KPI summary blocks.
   - Official sign-off authentication blocks for **Reporting Officer / Supervisor**, **Quality Assurance In-Charge**, and **Medical Superintendent**.

5. **Immutable Append-Only Audit Trail Console (`/audit-logs`)**:
   - Accessible strictly to authorized **Admins** (`AdminMiddleware`).
   - Strictly possesses **zero edit, update, modify, or delete routes or methods**.
   - Records actor ID, event action, affected entity table, entity ID, old values JSON, new values JSON, client IP address, user agent, and timestamp.
   - **Automated Sensitive Key Masking**: Passwords, password hashes, auth tokens, session IDs, CSRF tokens, and API keys are automatically redacted (`[REDACTED]`) before database insertion.
   - Interactive forensic diff modal allows administrators to inspect before/after values with side-by-side comparison.

### Running Phase 8 Automated Tests
```bash
php scripts/test_phase8.php
```
All 12 tests validate CSV formula injection prevention (CWE-1236 neutralization), sensitive data redaction in audit logs, audit trail immutability and append-only architecture, server-side permission scoping, filter input validation and SQL parameterization defense, inspections summary report accuracy, completion rate calculations, attendance & GPS geofencing verification audits, exception schedule detection, checklist failure extraction, complaint SLA breach tracking, and forensic audit event recording on report export.

---

## 13. Phase 9: Hospital Settings & Security Hardening

### Core Operational & Governance Settings (`/settings`)
1. **General & Facility Branding**:
   - Hospital official name, subtitle, physical address, and standard application timezone (`Asia/Kolkata`).
   - Dynamic branding reflected across all clinical reports, printable letterheads, navigation bars, and QR code badges.
2. **Attendance & GPS Policy**:
   - Default geofence radius configuration (10 to 5,000 meters).
   - Maximum acceptable GPS accuracy filter (10 to 1,000 meters; poor signals flagged as suspicious).
   - Shift grace period setting (0 to 180 minutes) before attendance is flagged as late.
   - Hardware GPS verification toggle switch.
3. **Inspections & Scheduling Defaults**:
   - Schedule generation advance horizon (1 to 60 days ahead).
   - Incomplete inspection auto-mark overdue window (1 to 48 hours).
   - Default passing compliance threshold percentage (10% to 100%).
   - Mandatory QR checkpoint scan policy toggle.
4. **Complaint SLA Policies**:
   - Hospital default initial response target (1 to 168 hours).
   - Hospital default issue resolution target (1 to 720 hours).
   - Auto-escalation trigger on overdue SLA breach.
5. **Notification Preferences**:
   - In-app notification center broadcast toggle.
   - Outbound email alerts for emergency complaints and critical failures.
   - High-priority instant alerts on checklist critical failure and SLA overdue.
6. **Data Retention & Backup Policies**:
   - Audit trail log retention policy (30 to 3,650 days).
   - Evidence photo & media retention policy (30 to 1,825 days).
   - Database backup snapshot retention policy (7 to 365 days).
   - Automated nightly database backup toggle.
7. **Maintenance Notice & Emergency Broadcast**:
   - Clinical Maintenance Mode switch (HTTP 503).
   - Emergency contact notice broadcast for hospital control room and ward helplines.

### Security Hardening & Critical Safeguards Architecture
1. **Critical Safeguards Immutability (CWE-693 / CWE-284)**:
   - Non-negotiable hospital compliance safeguards are hardware-locked inside `HospitalSetting.php`:
     - `audit_logging_enabled` (Mandatory ON)
     - `rbac_enabled` (Mandatory ON)
     - `backup_safeguards_active` (Mandatory ON)
     - `force_https_enabled` (Mandatory ON)
   - Any administrative form submission or script attempting to disable these safeguards is intercepted, rejected with an error, and forensically audited.
2. **Comprehensive HTTP Security Headers**:
   - `X-Content-Type-Options: nosniff` (MIME sniffing prevention)
   - `X-Frame-Options: SAMEORIGIN` (Clickjacking defense)
   - `X-XSS-Protection: 1; mode=block`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: geolocation=(self), camera=(), microphone=()`
   - `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; object-src 'none';`
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (enforced when HTTPS is active)
3. **Cryptographic Password Reset Workflow**:
   - High-entropy 256-bit random tokens (`bin2hex(random_bytes(32))`).
   - Plaintext tokens are never stored; only SHA-256 hashes are persisted in the database.
   - Strict 15-minute expiration window (`expires_at > NOW()`).
   - Strict single-use invalidation (`used_at = NOW()`) immediately upon submission, with simultaneous invalidation of all other active tokens for that user.
   - Anti-enumeration protection: Returns identical generic message regardless of account presence.
   - IP Rate Limiting: Maximum 3 password reset requests per 15-minute window per IP address with in-memory failsafe cache.
4. **Maintenance Mode Barrier (HTTP 503)**:
   - Integrated middleware (`MaintenanceMiddleware`) intercepts incoming requests.
   - Renders a clean 503 Service Unavailable page with official emergency helpline numbers for public and supervisors.
   - Whitelist protection: `/login`, `/logout`, `/health`, `/api/health`, `/forgot-password`, and `/reset-password` remain accessible.
   - Authenticated Administrators seamlessly bypass maintenance to configure settings or deploy updates.
5. **Real-Time System Health Diagnostic Telemetry**:
   - Live database latency ping (ms), engine version, and connection state.
   - PHP runtime telemetry: active memory, peak memory, upload limits, execution timeout.
   - Timezone synchronization verification against `Asia/Kolkata`.
   - Filesystem write permissions check across `/storage/logs`, `/storage/uploads/floor_plans`, `/storage/uploads/evidence`, and `/storage/cache`.
   - Security ledger integrity count and active safeguard status.

### Running Phase 9 Automated Tests
```bash
php scripts/test_phase9.php
```
All 11 tests validate:
- Hospital settings configuration, defaults, and 7 functional categories
- Critical safeguard tampering defense (audit logging, RBAC, backups locked)
- Settings input validation boundaries (geofencing 10-5000m, accuracy 10-1000m, retention, timezones)
- Security HTTP headers verification (X-Frame, CSP, Referrer, Permissions)
- Password reset token cryptographic entropy (256-bit) and anti-enumeration
- Rate limiting on password reset requests (max 3 / 15m per IP)
- Single-use token invalidation and 15-minute expiration verification
- Maintenance mode 503 barrier and unrestricted emergency route whitelist
- Centralized CSRF token generation and output escaping defense
- Horizontal authorization and request method tampering defense
- Production error handling and safe logging (zero secret leakage)

---

## 14. Phase 10: Secure Backup, Retention & Disaster Recovery

### Architectural Overview & Security Guarantees
Phase 10 delivers an enterprise-grade automated backup, Grandfather-Father-Son retention, and disaster recovery subsystem specifically optimized for **Hostinger shared hosting** environments while adhering to hospital data protection requirements.

```
+-----------------------------------------------------------------------------------+
|                        HOSPITAL DATA & AUDIT SOURCES                             |
|  - MySQL Database (30+ Tables: Users, Inspections, Complaints, SLAs, Logs)        |
|  - Evidence Files (storage/uploads/: Complaint photos, inspection photos, floor)   |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|              BACKUP ENGINE (app/Services/BackupService.php)                        |
|  1. Concurrency Lock: Checks storage/backups/.backup.lock (30 min timeout)        |
|  2. Pure-PHP Streaming Dump: SET TRANSACTION ISOLATION LEVEL REPEATABLE READ      |
|     - Chunked INSERT streams (500 rows/batch) preventing PHP memory exhaustion    |
|  3. Evidence Archival: ZipArchive compression of storage/uploads/                 |
|  4. Strict Exclusions: .env, APP_KEY, .tmp, .lock, debug logs, session files      |
|  5. Atomic Commit: Writes to *.tmp.* first, validates SHA-256, renames atomically |
|  6. Authenticated Encryption: OpenSSL AES-256-CBC with HMAC-SHA256 (Encrypt-then-MAC)
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                   SECURE STORAGE & RETENTION CLEANUP                             |
|  - Storage: storage/backups/ (Outside Web Root, .htaccess protected)              |
|  - Retention Policy (GFS): 7 Daily | 4 Weekly | 3 Monthly                         |
|  - Single-Backup Safeguard: NEVER purges the only remaining or latest backup       |
|  - Logging: storage/logs/recovery.log & backup_logs table                         |
+-----------------------------------------------------------------------------------+
```

### Key Engineering Features

1. **Hostinger Shared-Hosting Compatibility**:
   - Shared hosts often restrict `exec()`, `system()`, and binary `mysqldump`. `BackupService` features a zero-dependency, pure-PHP streaming dump engine using PDO cursors that writes chunked 500-row `INSERT` batches directly to disk, keeping memory consumption below 16MB regardless of database size.
2. **Secure Restricted Storage (`storage/backups/`)**:
   - Backups reside strictly outside the public web root.
   - Secondary `.htaccess` barrier enforces `Require all denied` and `Deny from all`.
   - All download and restore requests sanitize filename inputs using `basename()` to eliminate Path Traversal (CWE-22) vulnerabilities.
3. **Atomic File Commit Pattern**:
   - In-progress archives are generated with a `.tmp` extension (e.g., `backup_shethlg_database_only_...tmp.sql`).
   - Only after generation completes, record counts match, and SHA-256 checksums are calculated is the archive atomically renamed to its permanent filename. An incomplete or interrupted backup can never be marked successful or overwrite a valid archive.
4. **Authenticated Encryption (AES-256-CBC with Encrypt-then-MAC)**:
   - Sensitive hospital patient, staff, and inspection records can be encrypted using AES-256-CBC.
   - Authenticity is sealed with HMAC-SHA256. The file envelope stores `[16-byte IV][32-byte HMAC][Ciphertext]`.
   - During decryption, the HMAC signature is strictly verified using constant-time `hash_equals()` before decryption begins, preventing tampering and padding oracle attacks.
5. **Grandfather-Father-Son (GFS) Retention Policy**:
   - **Daily:** Retains daily backups for the last **7 days**.
   - **Weekly:** Retains 1 backup per week for the last **4 weeks**.
   - **Monthly:** Retains 1 backup per month for the last **3 months**.
   - **Strict Safeguard:** The retention engine will **NEVER delete the only remaining backup** or the most recent backup on disk, even if its age exceeds configured thresholds.
6. **Dual-Ledger Audit Persistence**:
   - In a severe disaster where the MySQL database is damaged or dropped, relying exclusively on database tables for recovery history would erase recovery records.
   - All backup events, checksum verifications, retention purges, dry-run validations, and restore actions are written simultaneously to the database (`backup_logs`, `restore_logs`) and an append-only filesystem ledger (`storage/logs/recovery.log`).
7. **Admin Backup Dashboard (`/backups`)**:
   - Displays real-time status: freshness indicator (warning banner if no backup in 24 hours), storage consumed, total archives, and last verified test restore.
   - Interactive table with archive details: filename, size, SHA-256 hash, encrypted status, and creation timestamp.
   - Action buttons for SHA-256 verification, safe dry-run test restore, secure streamed download, and disaster restoration.
8. **Disaster Restoration with Re-Authentication Guard**:
   - Restoration is strictly restricted to authenticated Administrators.
   - Requires entering the Administrator's current password for re-authentication.
   - **Mandatory Pre-Restore Backup:** The system automatically creates a safety snapshot of the existing database before executing any restore statements.
   - Restores within a database transaction with safe foreign key toggling (`SET FOREIGN_KEY_CHECKS = 0/1`).

---

### Command-Line Interface (CLI) Utilities

```bash
# 1. Execute automated database-only backup (AES-256 encrypted by default)
php scripts/backup.php database_only

# 2. Execute full backup including evidence uploads (storage/uploads/)
php scripts/backup.php full_with_evidence

# 3. Execute backup without encryption (for local debugging)
php scripts/backup.php database_only --no-encrypt

# 4. Enforce Grandfather-Father-Son retention cleanup
php scripts/cleanup_backups.php

# 5. Run standalone dry-run restore validation (zero production impact)
php scripts/test_restore.php
php scripts/test_restore.php backup_shethlg_database_only_20261014_120000_a1b2c3d4.sql.enc

# 6. Execute Phase 10 verification test suite
php scripts/test_phase10.php
```

---

### Hostinger Cron Configuration Pattern

In Hostinger hPanel or cPanel (**Advanced -> Cron Jobs**), configure the following cron commands:

```bash
# Nightly Database Backup (02:00 AM IST / 20:30 UTC)
0 2 * * * /usr/bin/php /home/u123456789/domains/shethlg.com/scripts/backup.php database_only >> /home/u123456789/domains/shethlg.com/storage/logs/cron_backup.log 2>&1

# Weekly Full Archive with Evidence (Every Sunday at 03:00 AM IST)
0 3 * * 0 /usr/bin/php /home/u123456789/domains/shethlg.com/scripts/backup.php full_with_evidence >> /home/u123456789/domains/shethlg.com/storage/logs/cron_backup.log 2>&1

# Daily GFS Retention Cleanup (04:00 AM IST)
0 4 * * * /usr/bin/php /home/u123456789/domains/shethlg.com/scripts/cleanup_backups.php >> /home/u123456789/domains/shethlg.com/storage/logs/cron_cleanup.log 2>&1
```

> For step-by-step bare-metal restoration guides and emergency response protocols, refer to [docs/DISASTER_RECOVERY.md](file:///d:/hospital%20php%20website/docs/DISASTER_RECOVERY.md).

---

### Phase 10 Automated Test Suite Verification

```bash
php scripts/test_phase10.php
```

All 10 tests passed successfully:
* `[TEST 1]`: Storage Directory Restriction & .htaccess Access Control `[PASS]`
* `[TEST 2]`: Concurrency Lock & Overlapping Backup Prevention `[PASS]`
* `[TEST 3]`: Authenticated AES-256-CBC Encryption & HMAC Tamper Detection `[PASS]`
* `[TEST 4]`: SHA-256 Checksum Calculation & Tamper Detection `[PASS]`
* `[TEST 5]`: Grandfather-Father-Son (GFS) Retention Policy (7d / 4w / 3m) `[PASS]`
* `[TEST 6]`: Single-Backup Preservation Safeguard (Never Delete Only Backup) `[PASS]`
* `[TEST 7]`: Safe Dry-Run Restore Engine & Schema Validation `[PASS]`
* `[TEST 8]`: Dual-Ledger Audit Logging (recovery.log File Persistence) `[PASS]`
* `[TEST 9]`: Path Traversal & Malicious File Name Sanitization `[PASS]`
* `[TEST 10]`: CLI Utilities & Disaster Recovery Manual Completeness `[PASS]`

---

## 15. Phase 11: Full System Integration & Regression Testing

### Integration Architecture & Audit Scope
Phase 11 establishes a master regression test harness and performs deep integration auditing across all 10 completed phases to guarantee seamless cross-module workflows, rock-solid security defenses, and 100% Hostinger shared-hosting compliance.

```
+-----------------------------------------------------------------------------------+
|               PHASE 11 MASTER INTEGRATION TEST HARNESS (scripts/test_phase11.php) |
+-----------------------------------------------------------------------------------+
|  [INT-01] Auth & RBAC: Bcrypt Hashing (Cost 12), Role Scoping & Inactivation      |
|  [INT-02] CSRF Defense: Cryptographic Tokens & Timing-Safe Request Interception    |
|  [INT-03] Data Access: SQL Injection Resistance & Strict PDO Parameterization     |
|  [INT-04] View Security: XSS Output Encoding & HTML Entity Sanitization (e())    |
|  [INT-05] Access Control: IDOR Defense on Supervisor Assigned Work Overview       |
|  [INT-06] QR Checkpoints: Opaque Revocable Token Generation & Revocation Checks   |
|  [INT-07] GPS Geofencing: Haversine Great-Circle Distance & Inaccurate GPS Flag   |
|  [INT-08] Attendance Retry: Rapid Duplicate Check-In Idempotency Protection       |
|  [INT-09] Cron Scheduling: Recurrence Rules & SHA-256 Idempotency Determinism     |
|  [INT-10] Transactions: Inspection Snapshot Preservation & ACID Rollback          |
|  [INT-11] Data Governance: Safe Entity Deactivation vs Hard-Deletion Safeguard    |
|  [INT-12] Soft Deletion: Complaint Soft-Deletion with Reason & Admin Restoration   |
|  [INT-13] Disaster Recovery: Backup Lockfile Concurrency & Authenticated AES-256 |
|  [INT-14] Staging Restore: Safe Dry-Run Restore Schema & Hospital Table Checks    |
|  [INT-15] Reporting Security: CSV Formula Injection Neutralization (CWE-1236)     |
|  [INT-16] Telemetry & Logs: Zero Secrets Leakage in Logs & Diagnostic Endpoints    |
+-----------------------------------------------------------------------------------+
```

### Defects Discovered & Resolved During Integration Audit
1. **Route Parameter Binding Gap (High Severity)**:
   - *Issue:* Multiple controllers (`FacilityController`, `ShiftController`, `ScheduleController`, `ChecklistController`, `QrController`, `AttendanceController`, `BackupController`) called `$request->param('id')` or `$request->param('filename')`, but the `Request` class lacked the `param()` method and route parameters were not populated on the `$request` instance.
   - *Fix:* Added `private array $params = []`, `setParams(array $params): self`, `param(string $key, mixed $default = null): mixed`, and `params(): array` to [`Request.php`](file:///d:/hospital%20php%20website/app/Core/Request.php). Wired parameter population in [`Router.php`](file:///d:/hospital%20php%20website/app/Core/Router.php) immediately upon regex pattern match.
2. **Controller Parameter Signature Mismatch in Complaint Management**:
   - *Issue:* Action methods in [`ComplaintController.php`](file:///d:/hospital%20php%20website/app/Controllers/ComplaintController.php) declared `array $params` as their second argument, whereas the router unpacked route parameters as scalar arguments (`...array_values($params)`), risking a PHP `TypeError` when triggered via web routes.
   - *Fix:* Refactored all 11 action methods in `ComplaintController.php` (`show`, `assign`, `updateStatus`, `putOnHold`, `resolve`, `reopen`, `close`, `addComment`, `delete`, `restore`, `downloadEvidence`) to accept `mixed $param = null` and resolve the ID safely using `$id = is_array($param) ? (int)($param['id'] ?? 0) : (int)($param ?: $request->param('id', 0));`.
3. **Backup Controller Method Signatures**:
   - *Issue:* `BackupController` methods (`download`, `verify`, `testRestore`, `restore`) did not accept route parameters as method arguments.
   - *Fix:* Added optional `?string $filename = null` parameter to all 4 methods with fallback to `$request->param('filename')`.
4. **CSRF Method Convenience Alias**:
   - *Issue:* Core security tests referenced `Csrf::token()`, whereas the class declared `Csrf::getToken()`.
   - *Fix:* Added `Csrf::token()` as a permanent alias in [`Csrf.php`](file:///d:/hospital%20php%20website/app/Core/Csrf.php).

---

### Executing Phase 11 Master Integration Test Suite
```bash
php scripts/test_phase11.php
```

**Verification Results:**
* `[INT-01]` [Auth & RBAC]: `[PASS]` (745ms)
* `[INT-02]` [CSRF Security]: `[PASS]` (0.77ms)
* `[INT-03]` [Data Access]: `[PASS]` (0.01ms)
* `[INT-04]` [View Security]: `[PASS]` (0.03ms)
* `[INT-05]` [Access Control]: `[PASS]` (0ms)
* `[INT-06]` [QR Checkpoints]: `[PASS]` (2.01ms)
* `[INT-07]` [GPS Geofencing]: `[PASS]` (8164ms)
* `[INT-08]` [Attendance Retry]: `[PASS]` (0.02ms)
* `[INT-09]` [Cron Scheduling]: `[PASS]` (2.34ms)
* `[INT-10]` [Transactions]: `[PASS]` (0.01ms)
* `[INT-11]` [Data Governance]: `[PASS]` (0ms)
* `[INT-12]` [Soft Deletion]: `[PASS]` (0.01ms)
* `[INT-13]` [Disaster Recovery]: `[PASS]` (23.45ms)
* `[INT-14]` [Disaster Recovery]: `[PASS]` (2056ms)
* `[INT-15]` [Reporting Security]: `[PASS]` (1.55ms)
* `[INT-16]` [Telemetry & Logs]: `[PASS]` (2050ms)

**Total Tests Run:** 16 | **Tests Passed:** 16 | **Tests Failed:** 0 | **Success Rate:** 100%

---

## 16. Phase 12: Hostinger Shared Hosting Deployment and Final Handover

### Production Deployment Architecture
The application is engineered specifically for **Hostinger Shared Hosting** (cPanel / hPanel) with zero heavy runtime dependencies (pure PHP 8.1+, MySQL 8.0+/MariaDB, Apache, Bootstrap 5, vanilla JS).

#### Security-Hardened Directory Layout
```
/home/u123456789/
├── hospital_app/                  <-- Core application (OUTSIDE public web root)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── docs/
│   ├── routes/
│   ├── scripts/
│   ├── storage/                   <-- Protected by storage/.htaccess (Deny all)
│   │   ├── backups/
│   │   ├── logs/
│   │   ├── uploads/
│   │   └── temp/
│   ├── .env                       <-- 0600 permissions, NEVER publicly served
│   ├── .htaccess                  <-- Root protection fallback
│   └── composer.json
└── domains/shethlg.com/public_html/ <-- Public document root
    ├── assets/                    <-- Static CSS, JS, images, icons
    ├── index.php                  <-- Front controller
    └── .htaccess                  <-- Rewrite & security headers
```

### Pre-Flight Verification Script
Run the automated environment auditor to verify PHP version, extensions, writable folders, and configuration:
```bash
php scripts/deploy_check.php
```
* **Checks Performed**: PHP >= 8.1, `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `ctype`, `fileinfo`, `session`, memory limits, writable storage directories, `.env` key length, production debug mode, `.htaccess` existence, and MySQL schema migrations.

### Administrator Bootstrap (Without Hardcoded Passwords)
To bootstrap or update the initial Administrator account on production:
```bash
php scripts/create_admin.php --name="Chief Administrator" --email="admin@shethlg.org" --phone="9876543210" --password="SuperSecurePassword2026!"
```
* Complexity Enforced: Minimum 10 characters, mixed case, numeric digits, and special symbols. Hashed using bcrypt with cost 12.

### Hostinger hPanel Cron Job Configuration
Configure the following three cron jobs via **Hostinger hPanel** > **Advanced** > **Cron Jobs**:
1. **Inspection Schedules & Overdue Monitor** (Daily at 01:00 AM IST):
   ```bash
   /usr/bin/php /home/u123456789/hospital_app/scripts/cron_generate_schedules.php >> /home/u123456789/hospital_app/storage/logs/cron_schedules.log 2>&1
   ```
2. **Automated Daily Backup** (Daily at 02:00 AM IST):
   ```bash
   /usr/bin/php /home/u123456789/hospital_app/scripts/backup.php database_only >> /home/u123456789/hospital_app/storage/logs/cron_backup.log 2>&1
   ```
3. **GFS Retention Policy Cleanup** (Daily at 03:00 AM IST):
   ```bash
   /usr/bin/php /home/u123456789/hospital_app/scripts/cleanup_backups.php >> /home/u123456789/hospital_app/storage/logs/cron_cleanup.log 2>&1
   ```

### Operational Documentation Library
* **Deployment Guide**: [`docs/DEPLOYMENT_GUIDE.md`](file:///d:/hospital%20php%20website/docs/DEPLOYMENT_GUIDE.md)
* **Administrator Manual**: [`docs/ADMIN_MANUAL.md`](file:///d:/hospital%20php%20website/docs/ADMIN_MANUAL.md)
* **Supervisor Manual**: [`docs/SUPERVISOR_MANUAL.md`](file:///d:/hospital%20php%20website/docs/SUPERVISOR_MANUAL.md)
* **Disaster Recovery Plan**: [`docs/DISASTER_RECOVERY.md`](file:///d:/hospital%20php%20website/docs/DISASTER_RECOVERY.md)
* **Maintenance & Operations**: [`docs/MAINTENANCE_CHECKLIST.md`](file:///d:/hospital%20php%20website/docs/MAINTENANCE_CHECKLIST.md)
* **Security Hardening**: [`docs/SECURITY_HARDENING.md`](file:///d:/hospital%20php%20website/docs/SECURITY_HARDENING.md)

