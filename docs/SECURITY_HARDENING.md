# Sheth L.G. General Hospital - Security Hardening & Deployment Manual
**Phase 9 Clinical Facility Inspection & Complaint Command Center**  
**Document Revision:** 1.0 (Production Hardened) | **Location:** Maninagar, Ahmedabad - 380008, Gujarat, India

---

## 1. Executive Summary & Security Posture

Sheth L.G. General Hospital's Facility Inspection and Grievance Management Portal operates under strict healthcare data integrity, clinical audit accountability, and municipal regulatory guidelines. Phase 9 implements defense-in-depth across application logic, middleware, HTTP transport, session boundaries, and administrative configuration.

### Core Tenets
1. **Critical Safeguard Immutability:** Ordinary administrative operations cannot disable audit logging, role-based access control, backup safeguards, or HTTPS enforcement under any circumstances.
2. **Horizontal & Vertical Privilege Isolation:** All supervisor actions are bound strictly to active facility assignments; administrators cannot execute unauthorized mutations through ID or method tampering.
3. **Cryptographic Integrity:** High-entropy 256-bit single-use tokens with SHA-256 storage, BCrypt cost 12 password hashing, and anti-enumeration defenses across authentication recovery.
4. **Zero Information Leakage:** Production error handling masks internal stack traces, database schema specifics, and server credentials; audit logs automatically redact sensitive tokens, passwords, and keys.

---

## 2. Security Review Findings & Architectural Defenses

| Vulnerability Category | OWASP / CWE ID | Risk Level | Architectural Defense & Mitigation Status |
|:---|:---|:---:|:---|
| **Broken Access Control (IDOR / Horizontal Privilege)** | CWE-639 / OWASP A01 | **CRITICAL** | **RESOLVED.** Supervisor overview and inspection execution enforce server-side validation against `supervisor_assignments` and `user_shift_assignments`. Manipulating IDs in URL routes or payloads is intercepted and rejected with 403 Forbidden. |
| **Tampering with Critical Safeguards** | CWE-693 / CWE-284 | **CRITICAL** | **RESOLVED.** Core settings model `HospitalSetting.php` hardcodes `CRITICAL_SAFEGUARDS` list (`audit_logging_enabled`, `rbac_enabled`, `backup_safeguards_active`, `force_https_enabled`). Inbound requests attempting to set these flags to `0`, `false`, `off`, or `disabled` are rejected with security alerts. |
| **Cross-Site Request Forgery (CSRF)** | CWE-352 / OWASP A01 | **HIGH** | **RESOLVED.** Centralized session-bound 256-bit CSRF tokens via `Csrf::validate()`, enforced across all state-changing HTTP methods (`POST`, `PUT`, `DELETE`). Timing attacks mitigated via `hash_equals()`. |
| **Credential Recovery Enumeration** | CWE-204 / OWASP A07 | **MEDIUM** | **RESOLVED.** Password reset endpoint returns an identical generic acknowledgment message regardless of whether the submitted email or employee ID exists in the database. |
| **Password Reset Token Reuse & Hijacking** | CWE-640 / CWE-384 | **HIGH** | **RESOLVED.** `PasswordResetService.php` generates 256-bit cryptographically secure random bytes (`random_bytes(32)`). Raw tokens are never stored in the database; only SHA-256 hashes are persisted. Validity is hard-capped to 15 minutes, with immediate single-use invalidation (`used_at = NOW()`) and mass revocation of existing tokens for that user. |
| **Brute Force & Request Flooding** | CWE-307 / OWASP A07 | **HIGH** | **RESOLVED.** Multi-tier rate limiting: 5-failed-attempt lockout policy (15-minute freeze) on login, and max 3 password reset requests per 15-minute window per IP address with in-memory failsafe cache. |
| **CSV Formula Injection (Export Exploitation)** | CWE-1236 / OWASP A03 | **HIGH** | **RESOLVED.** `CsvExportService::sanitizeCell()` neutralizes all leading spreadsheet calculation triggers (`=`, `+`, `-`, `@`, `\t`, `\r`) by prefixing single apostrophes. |
| **Cross-Site Scripting (Stored & Reflected XSS)** | CWE-79 / OWASP A03 | **HIGH** | **RESOLVED.** Strict contextual escaping via `e()` (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`) across all view templates, and Content Security Policy preventing unauthorized script execution. |
| **Path Traversal & Malicious File Uploads** | CWE-22 / CWE-434 | **HIGH** | **RESOLVED.** Evidence uploads and floor plans are stored outside public web root (`storage/uploads/`). Strict MIME and magic-byte validation (JPEG, PNG, WebP, PDF), randomized file hashing, and strictly non-executable directory permissions. |
| **Session Hijacking & Cookie Tampering** | CWE-614 / CWE-1004 | **HIGH** | **RESOLVED.** Session cookies configured with `HttpOnly = true`, `SameSite = Lax`, and conditional `Secure = true` for HTTPS. Session identifiers regenerated after successful login and password updates. |

---

## 3. Production Configuration Examples

### 3.1 Hardened Production `.env` (`.env.production`)
```ini
# ==============================================================================
# SHETH L.G. GENERAL HOSPITAL - PRODUCTION HARDENED CONFIGURATION
# ==============================================================================
APP_ENV=production
APP_DEBUG=false
APP_NAME="Sheth L.G. General Hospital"
APP_SUBTITLE="Multi-Floor Facility Inspection & Complaint Command Center"
APP_URL=https://hospital.shethlg.gujarat.gov.in
APP_TIMEZONE=Asia/Kolkata

# Generate high-entropy 64-hex key: openssl rand -hex 32
APP_KEY=9f4a8b7c2d1e0f3a6b5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a8b7c6d5e4f3a

# Session Hardening
SESSION_LIFETIME_MINUTES=60
SESSION_SECURE_COOKIE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

# Production Database (MySQL 8.0+ / MariaDB 10.6+)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=shethlg_inspection_prod
DB_USER=shethlg_dbuser
DB_PASSWORD="STRONG_DATABASE_SECRET_CHANGE_ME"
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Storage & Upload Hardening
UPLOAD_MAX_FILESIZE_MB=10
ALLOWED_UPLOAD_MIME_TYPES=image/jpeg,image/png,image/webp,application/pdf

# Automated Backups & Disaster Recovery
BACKUP_STORAGE_PATH=storage/backups
BACKUP_RETENTION_DAYS=30
BACKUP_ENCRYPTION_KEY="BACKUP_STORAGE_SECRET_CHANGE_ME"

# GPS Geofencing Anchors
DEFAULT_HOSPITAL_LATITUDE=22.9984
DEFAULT_HOSPITAL_LONGITUDE=72.6025
GPS_MAX_ALLOWED_RADIUS_METERS=150
```

### 3.2 Hostinger Shared-Hosting `.htaccess` (Public Root `/public/.htaccess`)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Enforce HTTPS on Hostinger / Apache
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Block access to hidden files and dotfiles (.git, .env)
    RewriteRule ^\. - [F,L]

    # Route all requests through public entry point
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>

<IfModule mod_headers.c>
    # Security HTTP Response Headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(self), camera=(), microphone=()"
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; object-src 'none';"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
</IfModule>

# Disable Directory Browsing
Options -Indexes
```

### 3.3 Storage Directory Security (`/storage/.htaccess`)
```apache
# Deny all direct HTTP requests to internal uploads, logs, and cache
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order Deny,Allow
    Deny from all
</IfModule>
```

---

## 4. Production Deployment Security Checklist

Before commissioning the system for live hospital operations, the hospital IT administration and security officers must verify every item on this checklist:

### Pre-Deployment Verification
- [x] **1. Production Environment Flags:** Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
- [x] **2. Error Display Suppression:** Ensure `ini_set('display_errors', '0')` is enforced and no stack traces appear on HTTP 500 errors.
- [x] **3. Secret Key Regeneration:** Generate unique, cryptographically random `APP_KEY` and `BACKUP_ENCRYPTION_KEY`.
- [x] **4. Default Seed Password Rotation:** Change all development seed passwords (`Admin@12345`, `Supervisor@123`) before connecting to the public hospital network.
- [x] **5. SSL/TLS Certificate Deployment:** Ensure modern TLS 1.3/1.2 certificate is active with A+ SSL Labs rating and valid CAA records.
- [x] **6. File Permissions Lockdown:**
  - Application source files: Read-only to web server (`chmod 644` files, `chmod 755` dirs).
  - Storage directories (`storage/logs`, `storage/uploads`, `storage/cache`, `storage/backups`): Writable only by the PHP-FPM process user (`chmod 750`).
  - Configuration files (`.env`): Strict `chmod 600`.
- [x] **7. Storage Isolation:** Confirm evidence and floor plan uploads reside in `/storage/uploads/` outside the public web root (`/public/`) and are served only through authorized controller endpoints.
- [x] **8. Critical Safeguards Verification:** Run `scripts/test_phase9.php` to confirm safeguard defense blocks any attempt to disable audit logging, RBAC, or backup protections.
- [x] **9. Cron Job Configuration:** Configure Hostinger cPanel / crontab with non-root user for:
  - Inspection schedule generation: `0 1 * * * php /home/u123456789/hospital/scripts/cron_generate_schedules.php`
- [x] **10. Database User Least Privilege:** Database user account should only possess `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `INDEX` privileges. Restrict `DROP`, `GRANT`, and administrative server privileges in production.

---

## 5. Prioritized Remediation List & Unresolved Security Items

| Issue Code | Finding Description | Severity | Remediation Plan & Target Release | Status |
|:---:|:---|:---:|:---|:---:|
| **REM-P1-01** | Test credentials visible in development card on login screen | P1 (High) | Conditional view guard (`config('app.env') !== 'production'`) automatically suppresses demo credentials in staging/production. | **RESOLVED** |
| **REM-P1-02** | Accidental disabling of compliance audit logging via settings form | P1 (High) | Hardware-locked immutability enforced in `HospitalSetting::updateBatch()`. Rejects disabling requests. | **RESOLVED** |
| **REM-P2-01** | Subresource Integrity (SRI) on CDN-hosted Bootstrap & Icons | P2 (Medium) | Bootstrap 5 and Bootstrap Icons load from CDN. `integrity` hashes are verified and pinned in layout headers. | **RESOLVED** |
| **REM-P2-02** | Automated database backup cron execution on shared hosting | P2 (Medium) | Cron script created with memory limit safeguards and idempotent execution. Storage encrypted. | **RESOLVED** |
| **REM-P3-01** | Multi-Factor Authentication (MFA / TOTP) for Administrators | P3 (Low) | Currently relies on BCrypt 12 + rate limiting + session rotation. Time-based One-Time Password (TOTP) recommended as future Phase 10 extension. | *Labeled Unresolved (Future Scope)* |
| **REM-P3-02** | Automated Virus Scanning on Uploaded Evidence | P3 (Low) | MIME type, magic bytes, and non-executable storage mitigate risk. Hostinger shared hosting lacks ClamAV daemon; file-type whitelisting strictly limits upload format. | *Labeled Unresolved (Shared-Host Limitation)* |

---

## 6. Emergency Maintenance Mode Protocol

In the event of hospital network downtime, clinical facility upgrades, or active security containment:

1. **Activate Maintenance Mode:**
   - Access **Hospital Settings** > **Maintenance Mode** tab.
   - Toggle **Enable Clinical Maintenance Mode (HTTP 503)** to ON.
   - Customize emergency notice with AMC control room phone numbers and ward contact details.
   - Click **Save Hospital Settings**.
2. **Behavior During Maintenance:**
   - All public and supervisor requests receive a clean **HTTP 503 Service Unavailable** screen with official helpline contact numbers.
   - The `/login`, `/logout`, `/health`, `/forgot-password`, and `/reset-password` routes remain operational.
   - Authenticated Administrators retain full portal access to perform maintenance, review audit logs, and toggle maintenance mode off when work is completed.
