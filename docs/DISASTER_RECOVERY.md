# Sheth L.G. General Hospital, Ahmedabad
## Disaster Recovery Runbook & Secure Backup Manual
**Document Version:** 10.0 (Phase 10: Secure Backup, Retention & Disaster Recovery)  
**System:** Facility Inspection & Complaint Command Center  
**Target Environment:** Hostinger Shared / Cloud Hosting (Linux / cPanel / hPanel)  
**Classification:** Confidential - Hospital Operations & IT Administration Only

---

## 1. Executive Summary & Recovery Objectives

This manual defines the disaster recovery (DR) protocols, automated backup operations, encryption standards, and restoration procedures for the Sheth L.G. General Hospital Inspection & Complaint Management System.

### Recovery Metrics
* **RPO (Recovery Point Objective):** 24 hours (Daily automated cron execution at 02:00 AM IST) or manual pre-deployment / pre-restore on demand.
* **RTO (Recovery Time Objective):** < 45 minutes on Hostinger shared or VPS hosting using pure-PHP streaming or host-level CLI import.
* **Storage Location:** Restricted directory outside public web root (`storage/backups/`) protected by `.htaccess` (`Require all denied`), file permission masking, and authenticated AES-256-CBC encryption.
* **Audit Dual-Ledger:** Every backup, verification, dry-run test, and restore operation is recorded simultaneously in the MySQL database (`backup_logs`, `restore_logs`) and an append-only filesystem ledger (`storage/logs/recovery.log`) so that total database destruction does not erase audit evidence.

---

## 2. Backup Architecture & Security Safeguards

```
+-----------------------------------------------------------------------------------+
|                        HOSPITAL DATA & AUDIT SOURCES                             |
|  - MySQL Database (30+ Tables: Users, Inspections, Complaints, SlAs, Logs)        |
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

### Encryption Key Management
* **Cipher:** AES-256-CBC with HMAC-SHA256 signature verification (Encrypt-then-MAC).
* **Key Source:** Derived from `APP_BACKUP_KEY` or `APP_KEY` stored strictly in server-level environment variables (`.env`).
* **Format:** Raw binary 32-byte AES key + 32-byte HMAC key.
* **Archive Envelope:** `[16-byte random IV] + [32-byte HMAC Signature] + [AES-256 Ciphertext]`.
* **Tamper Prevention:** During restore or verification, HMAC is validated via `hash_equals()` before decryption begins. If altered by even 1 bit, execution aborts with a tamper warning.

---

## 3. Hostinger Shared Hosting Compatibility & Cron Setup

Shared hosting accounts (such as Hostinger Premium / Business / Cloud) typically enforce security restrictions:
* `exec()`, `passthru()`, `shell_exec()`, and `system()` may be restricted or disabled in `php.ini`.
* Direct access to binary `mysqldump` or `mysql` CLI is often not guaranteed in basic shared plans.
* Memory limits typically range from 128MB to 512MB per process.

### BackupService Mitigation
`BackupService` implements a zero-dependency, pure-PHP streaming engine utilizing PDO cursors. It streams data directly from MySQL to disk without loading entire database tables into PHP memory, guaranteeing 100% execution compatibility on any Hostinger tier.

### Hostinger Cron Job Configuration

To schedule automated daily backups in Hostinger hPanel or cPanel:

1. Log into your **Hostinger Control Panel** (hPanel).
2. Navigate to **Advanced -> Cron Jobs**.
3. Choose **Custom** interval and enter the schedule:

#### Daily Automated Backup (02:00 AM IST / 20:30 UTC):
```bash
0 2 * * * /usr/bin/php /home/u123456789/domains/shethlg.com/scripts/backup.php database_only >> /home/u123456789/domains/shethlg.com/storage/logs/cron_backup.log 2>&1
```

#### Weekly Full Archive with Evidence (Every Sunday at 03:00 AM IST):
```bash
0 3 * * 0 /usr/bin/php /home/u123456789/domains/shethlg.com/scripts/backup.php full_with_evidence >> /home/u123456789/domains/shethlg.com/storage/logs/cron_backup.log 2>&1
```

#### Daily GFS Retention Cleanup (04:00 AM IST):
```bash
0 4 * * * /usr/bin/php /home/u123456789/domains/shethlg.com/scripts/cleanup_backups.php >> /home/u123456789/domains/shethlg.com/storage/logs/cron_cleanup.log 2>&1
```

> **Note for Hostinger Operators:** Replace `/home/u123456789/domains/shethlg.com/` with your actual hosting home path displayed on your hPanel dashboard (under **Hosting Details -> Absolute Path**). Check with `which php` or host documentation if `/usr/bin/php` or `/usr/local/bin/php` is the default CLI binary.

---

## 4. Disaster Recovery & Restoration Procedures

### Scenario A: Minor Incident / Accidental Data Corruption
*(The web server is operational, but tables or records have been corrupted or truncated.)*

1. **Log in as Administrator** to the Hospital Command Center.
2. Navigate to **Security & Administration -> Backup & Disaster Recovery** (`/backups`).
3. Locate the target backup archive in the **Available Backup Archives** table.
4. Click **Dry-Run Test** to execute isolated syntax and schema validation without touching live tables. Verify:
   * Header signature is valid.
   * All core hospital tables (`users`, `buildings`, `wards`, `inspections`, `complaints`, etc.) are intact.
5. Click **Restore Archive**.
6. A security modal will prompt for your **Current Admin Password**. Enter your password to re-authenticate.
7. Click **Confirm Disaster Restoration**.
8. **Automated Safety Sequence Initiated:**
   * An emergency **Pre-Restore Backup** is automatically created before any tables are touched.
   * Foreign key checks are safely disabled (`SET FOREIGN_KEY_CHECKS = 0`).
   * The SQL statements are executed inside a database transaction.
   * Foreign key checks are restored (`SET FOREIGN_KEY_CHECKS = 1`).
   * Details are written to `restore_logs` and `storage/logs/recovery.log`.
9. The browser will redirect with a success banner showing execution duration and statements restored.

---

### Scenario B: Catastrophic Server / Database Loss (Bare-Metal Shared Hosting Rebuild)
*(The database was dropped, corrupted beyond repair, or the hosting account was provisioned on a fresh server.)*

#### Step 1: Provision Clean Database on Hostinger
1. In hPanel, go to **Databases -> MySQL Databases**.
2. Create a new database: e.g., `u123456789_shethlg`.
3. Create a database user with full privileges: e.g., `u123456789_admin`.
4. Securely record the DB Name, DB User, and DB Password.

#### Step 2: Deploy Application Files & Configure Environment
1. Deploy or clone the application code into the server directory.
2. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
3. Set your live database credentials in `.env`:
   ```ini
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=u123456789_shethlg
   DB_USERNAME=u123456789_admin
   DB_PASSWORD="your_strong_password"
   APP_KEY="your_original_encryption_key"
   ```
   > **CRITICAL:** `APP_KEY` or `APP_BACKUP_KEY` MUST match the key used to encrypt the backup archives. If the key is lost, encrypted backups cannot be decrypted.

#### Step 3: Run Baseline Schema Migrations
Run the migration CLI runner to initialize schema structures:
```bash
php scripts/migrate.php
```

#### Step 4: Transfer and Place Backup Archive
1. Place the backup file (e.g., `backup_shethlg_database_only_20261014_120000_a1b2c3d4.sql.enc`) into:
   ```
   /home/u123456789/domains/shethlg.com/storage/backups/
   ```
2. Ensure folder permissions are set to `0750` or `0755` so PHP can read the files.

#### Step 5: Test and Execute CLI Restoration
Run the standalone dry-run verification:
```bash
php scripts/test_restore.php backup_shethlg_database_only_20261014_120000_a1b2c3d4.sql.enc
```
Once verified, execute restore via CLI or PHP interactive session:
```bash
php -r "
require 'app/Core/App.php';
require 'app/Core/Helpers.php';
new App(__DIR__);
\$svc = new App\Services\BackupService();
\$res = \$svc->executeRestore('backup_shethlg_database_only_20261014_120000_a1b2c3d4.sql.enc', 1, 'production');
print_r(\$res);
"
```

#### Step 6: Restore Evidence Uploads (If Restoring Full Archive)
If restoring a `full_with_evidence` zip archive:
1. Extract the `uploads/` directory from the zip archive into `storage/uploads/`.
2. Confirm permissions on evidence subdirectories:
   ```bash
   chmod -R 0750 storage/uploads/
   ```
3. Verify that complaint evidence photos and floor plan SVGs load properly in the web interface.

---

## 5. Post-Restore Verification Checklist

Immediately after restoring a backup into staging or production, execute this 7-point validation:

| Check | Item | Verification Procedure | Expected Result |
|---|---|---|---|
| 1 | **Database Connectivity** | Access `/health` endpoint | HTTP 200, status: ok |
| 2 | **Core Table Row Counts** | Query `SELECT COUNT(*) FROM users;` and `inspections;` | Matches pre-incident counts |
| 3 | **Referential Integrity** | Inspect ward-to-floor and inspection-to-template links | Zero orphaned foreign key records |
| 4 | **Admin Login** | Log in with known Admin credentials | Successful session regeneration |
| 5 | **Supervisor Roster** | Inspect `/users` and `/supervisor-assignments` | All active supervisors and shifts present |
| 6 | **Evidence Availability** | Open latest resolved complaint evidence | Image loads without 404 or broken file error |
| 7 | **Audit Trail Integrity** | Review `/audit-logs` and `storage/logs/recovery.log` | Restore event documented with timestamp and actor |

---

## 6. Retention Policy (Grandfather-Father-Son)

The backup engine automatically enforces the hospital's GFS retention rule:
* **Daily Backups:** Retain all daily backups generated within the last **7 days**.
* **Weekly Backups:** Retain 1 backup per week for the last **4 weeks** (oldest of that week).
* **Monthly Backups:** Retain 1 backup per month for the last **3 months** (oldest of that month).
* **Older Backups:** Purged safely if they exceed 90 days.

### Strict Safeguard Rules:
1. **Never Delete the Only Backup:** If only 1 backup archive exists in `storage/backups/`, the retention engine will NEVER purge it, regardless of its age.
2. **Never Delete the Latest Backup:** The most recent backup archive is always exempt from purging to maintain a valid recovery point.
3. **Never Purge Unvalidated Backups:** Backups in `.tmp` state are cleaned after 24 hours of abandonment, preventing incomplete archive accumulation.

---

## 7. Emergency Contact & Escalation Protocol

In the event of an unrecoverable database or host failure:

1. **Incident Commander:** Head of IT Infrastructure & Hospital Operations.
2. **Database Administrator:** Senior Application Engineer.
3. **Hosting Provider:** Hostinger Priority Support (Ticket via hPanel Help Center).
4. **Data Protection Officer:** Sheth L.G. General Hospital Security Council.
5. **Recovery Log Evidence:** Immediately preserve `storage/logs/recovery.log` and `storage/logs/app.log` off-site.
