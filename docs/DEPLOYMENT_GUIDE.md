# Hostinger Shared Hosting Deployment Guide
## Sheth L.G. General Hospital, Ahmedabad
### Multi-Floor Inspection, Attendance, and SLA Complaint Management System

---

## 1. Executive Summary & Architecture Overview

This guide details the end-to-end production deployment procedure for **Sheth L.G. General Hospital's Facility Inspection Management System** onto a standard **Hostinger Shared Hosting** (cPanel / hPanel) environment.

### Architectural Principles
* **Runtime**: PHP 8.1+ (Tested and verified on PHP 8.2 and 8.3).
* **Database**: MySQL 8.0+ or MariaDB 10.6+ with `utf8mb4` encoding.
* **Web Server**: Apache 2.4+ with `mod_rewrite`, `mod_headers`, and `mod_authz_core`.
* **Zero Heavy Dependencies**: Pure PHP MVC, vanilla JavaScript, and Bootstrap 5. No Node.js, npm build step, Docker containerization, or VPS root/sudo permissions are required on the server.
* **Security-by-Default**: Secure sessions, CSRF token validation, SQL injection immunity via prepared statements, XSS output sanitization, CSV formula injection neutralization, authenticated evidence downloads, and GFS automated backups with optional AES-256 encryption.

---

## 2. Server Requirements & Pre-Flight Verification

### Required PHP Extensions
| Extension | Purpose | Hostinger Availability |
|---|---|---|
| `pdo` & `pdo_mysql` | Secure database connectivity | Enabled by default |
| `mbstring` | Multibyte string processing | Enabled by default |
| `openssl` | Cryptographic tokens & AES-256 backup encryption | Enabled by default |
| `json` | Structured JSON serialization & API responses | Enabled by default |
| `ctype` | Character classification & input validation | Enabled by default |
| `fileinfo` | Server-side MIME type detection for uploads | Enabled by default |
| `session` | Secure hardened HTTP session management | Enabled by default |
| `gd` *(Optional)* | Image manipulation and QR processing | Enabled by default in PHP Extensions |
| `curl` *(Optional)*| Outbound webhook / HTTP communications | Enabled by default |

### Recommended PHP Configuration (`php.ini` via hPanel)
* `upload_max_filesize = 10M`
* `post_max_size = 12M`
* `memory_limit = 128M` (or 256M)
* `max_execution_time = 60`
* `date.timezone = Asia/Kolkata`
* `display_errors = Off` (Never expose stack traces in production)
* `log_errors = On`

---

## 3. Directory Layout on Hostinger

Hostinger shared hosting provides SSH and File Manager access under `/home/u123456789/`. Two directory deployment architectures are supported:

### Recommended Architecture: Document Root Isolation (Outside Web Root)
In this layout, core application code is placed **above** the public web root, ensuring that PHP files, environment configurations, and storage directories cannot be accessed via direct HTTP URLs under any circumstances.

```
/home/u123456789/
├── hospital_app/                  <-- Core application (OUTSIDE public web root)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── docs/
│   ├── routes/
│   ├── scripts/
│   ├── storage/
│   │   ├── backups/               <-- 0750 permissions, protected by .htaccess
│   │   ├── logs/                  <-- 0750 permissions
│   │   ├── uploads/               <-- 0750 permissions, served via controller only
│   │   └── temp/                  <-- 0750 permissions
│   ├── .env                       <-- 0600 permissions, NEVER publicly served
│   ├── .htaccess                  <-- Root protection fallback
│   └── composer.json
└── domains/shethlg.com/public_html/ <-- Public document root
    ├── assets/                    <-- CSS, JS, images, icons
    ├── index.php                  <-- Front controller (points to ../../hospital_app)
    └── .htaccess                  <-- Apache rewrite and security headers
```

> **Note for Standard hPanel**: If Hostinger hPanel allows changing the website's document root to `public/` (via Websites > Manage > General > Change Document Root), point the document root directly to `/home/u123456789/hospital_app/public`.

### Fallback Architecture: Single Directory Deployment
If Hostinger configuration forces all files to reside inside `public_html/`:
1. Upload the entire project into `public_html/`.
2. The project's root `.htaccess` will automatically rewrite all web traffic into `public/index.php`.
3. The root `.htaccess` and `storage/.htaccess` block direct access to `.env`, `app/`, `config/`, `database/`, `storage/`, `scripts/`, and all `.sql`, `.log`, and `.md` files with a `403 Forbidden` response.

---

## 4. Step-by-Step Deployment Procedure

### Step 1: Database Creation & User Provisioning
1. Log in to **Hostinger hPanel** (`hpanel.hostinger.com`).
2. Navigate to **Databases** > **MySQL Databases**.
3. Create a new database and dedicated user:
   * **Database Name**: `u123456789_hospital`
   * **Database Username**: `u123456789_appuser`
   * **Password**: Create a cryptographically strong 24+ character password.
4. Note the database connection details:
   * **Host**: `localhost` (or `127.0.0.1`)
   * **Port**: `3306`

### Step 2: Upload Application Files
1. Create a zip archive of the project repository (excluding local `.git/`, `.env`, and test artifacts).
2. In Hostinger **File Manager** (or via SFTP/SSH), upload and extract the archive to `/home/u123456789/hospital_app/`.
3. If using Document Root Isolation, copy the contents of `hospital_app/public/` to `public_html/` and update the bootstrap path in `public_html/index.php`:
   ```php
   require_once dirname(__DIR__, 2) . '/hospital_app/app/Core/App.php';
   ```

### Step 3: Production Environment Configuration (`.env`)
1. In `/home/u123456789/hospital_app/`, copy `.env.example` to `.env`.
2. Generate a 32-character random key:
   ```bash
   php -r "echo bin2hex(random_bytes(16));"
   ```
3. Populate `.env` with production parameters:
   ```ini
   # Application Environment
   APP_ENV=production
   APP_DEBUG=false
   APP_NAME="Sheth L.G. General Hospital"
   APP_SUBTITLE="Multi-Floor Inspection & Complaint Facility Management"
   APP_URL=https://shethlg.com
   APP_TIMEZONE=Asia/Kolkata
   APP_KEY="your-generated-32-character-key"

   # Session Hardening
   SESSION_LIFETIME_MINUTES=60
   SESSION_SECURE_COOKIE=true
   SESSION_HTTPONLY=true
   SESSION_SAMESITE=Lax

   # Database Credentials (Hostinger)
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=u123456789_hospital
   DB_USER=u123456789_appuser
   DB_PASSWORD="your-strong-database-password"
   DB_CHARSET=utf8mb4
   DB_COLLATION=utf8mb4_unicode_ci

   # File Upload Limits
   UPLOAD_MAX_FILESIZE_MB=10
   ALLOWED_UPLOAD_MIME_TYPES=image/jpeg,image/png,image/webp,application/pdf

   # Automated Backup & Recovery
   BACKUP_STORAGE_PATH=storage/backups
   BACKUP_RETENTION_DAYS=7
   BACKUP_ENCRYPTION_KEY="your-generated-backup-encryption-key"

   # Hospital Coordinates (Ahmedabad Campus)
   DEFAULT_HOSPITAL_LATITUDE=22.9984
   DEFAULT_HOSPITAL_LONGITUDE=72.6025
   GPS_MAX_ALLOWED_RADIUS_METERS=150
   ```
4. Set strict file permissions on `.env`:
   ```bash
   chmod 600 .env
   ```

### Step 4: Directory Permissions Setup
Ensure the web server user can write to the logging, backup, and upload directories:
```bash
chmod -R 750 storage/
chmod 750 storage/logs
chmod 750 storage/backups
chmod 750 storage/uploads
chmod 750 storage/uploads/complaints
chmod 750 storage/temp
```

### Step 5: Database Migration Execution
Execute the database migration runner to build the schema in proper chronological sequence:
```bash
php scripts/migrate.php
```
Verify that all 7 migrations execute with `SUCCESS`:
* `001_create_initial_schema`
* `002_enhance_facility_and_floor_plan`
* `003_enhance_shifts_and_attendance`
* `004_enhance_checklists_and_scheduling`
* `005_enhance_complaints_and_sla`
* `006_settings_and_security_hardening`
* `007_backup_and_disaster_recovery`

### Step 6: Bootstrap Initial Administrator Account
Create the primary Administrator account without hardcoded credentials:
```bash
php scripts/create_admin.php --name="Chief Administrator" --email="admin@shethlg.org" --phone="9876543210" --password="SuperSecureAdminPassword2026!"
```
*Complexity requirement: At least 10 characters, including uppercase, lowercase, numbers, and symbols.*

### Step 7: Environment Pre-Flight Verification
Run the automated pre-flight audit script to confirm all production criteria are satisfied:
```bash
php scripts/deploy_check.php
```
Ensure the report returns `[STATUS] ENVIRONMENT 100% READY FOR HOSTINGER PRODUCTION DEPLOYMENT!`.

---

## 5. Hostinger Cron Job Configuration

Hostinger hPanel provides a dedicated **Cron Jobs** manager (**Advanced** > **Cron Jobs**). Set up the following three cron jobs:

### Cron 1: Inspection Schedule Generator & Overdue Monitor
* **Frequency**: Daily at 01:00 AM IST (`0 1 * * *`)
* **Command**:
  ```bash
  /usr/bin/php /home/u123456789/hospital_app/scripts/cron_generate_schedules.php >> /home/u123456789/hospital_app/storage/logs/cron_schedules.log 2>&1
  ```
* **Purpose**: Generates recurring inspection schedules 7 days in advance idempotently and flags past-due inspections as overdue.

### Cron 2: Automated Daily Database & Evidence Backup
* **Frequency**: Daily at 02:00 AM IST (`0 2 * * *`)
* **Command**:
  ```bash
  /usr/bin/php /home/u123456789/hospital_app/scripts/backup.php database_only >> /home/u123456789/hospital_app/storage/logs/cron_backup.log 2>&1
  ```
* **Purpose**: Creates an atomic, authenticated, and encrypted `.tar.gz.enc` backup snapshot and stores it in `storage/backups/`.

### Cron 3: Grandfather-Father-Son (GFS) Retention Purge
* **Frequency**: Daily at 03:00 AM IST (`0 3 * * *`)
* **Command**:
  ```bash
  /usr/bin/php /home/u123456789/hospital_app/scripts/cleanup_backups.php >> /home/u123456789/hospital_app/storage/logs/cron_cleanup.log 2>&1
  ```
* **Purpose**: Enforces retention limits (7 daily, 4 weekly, 3 monthly) and purges expired archives safely.

---

## 6. HTTPS & Security Header Configuration

1. In Hostinger hPanel, go to **Security** > **SSL** and activate a **Free Lifetime Let's Encrypt SSL** certificate.
2. Enable the **Force HTTPS** toggle in hPanel.
3. The project's `.htaccess` automatically issues the following headers:
   * `X-Content-Type-Options: nosniff`
   * `X-Frame-Options: SAMEORIGIN`
   * `X-XSS-Protection: 1; mode=block`
   * `Referrer-Policy: strict-origin-when-cross-origin`

---

## 7. Release Rollback & Disaster Recovery Procedures

### Application Code Rollback
1. Maintain date-stamped release folders on Hostinger (e.g., `/home/u123456789/releases/v11.0`, `/releases/v12.0`).
2. Point a symbolic link `hospital_app` to the active release:
   ```bash
   ln -sfn /home/u123456789/releases/v11.0 /home/u123456789/hospital_app
   ```
3. To roll back an application release, update the symlink to point to the previous version and verify `deploy_check.php`.

### Database Restore & Rollback
1. View available backups via CLI or the Admin Backup Dashboard:
   ```bash
   ls -la /home/u123456789/hospital_app/storage/backups/
   ```
2. Test the archive in an isolated staging table before restoring:
   ```bash
   php scripts/test_restore.php
   ```
3. Execute restore using `BackupService`:
   ```bash
   php -r "require 'app/Core/App.php'; require 'app/Core/Helpers.php'; new App\Core\App('.'); (new App\Services\BackupService())->restoreBackup('target_backup.tar.gz.enc', 1);"
   ```
4. Full operational disaster recovery workflows are documented in [DISASTER_RECOVERY.md](file:///d:/hospital%20php%20website/docs/DISASTER_RECOVERY.md).
