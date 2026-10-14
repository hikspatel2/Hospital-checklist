# Administrator User Manual
## Sheth L.G. General Hospital, Ahmedabad
### Multi-Floor Inspection, Attendance, and SLA Complaint Management System

---

## 1. System Overview & Role Permissions

The **Sheth L.G. General Hospital Facility Management System** provides centralized governance over hospital cleanliness, sanitation, equipment readiness, supervisor shift attendance, and facility maintenance complaint resolution.

### Role Privileges
| Functionality | Administrator (`admin`) | Supervisor (`supervisor`) |
|---|:---:|:---:|
| Hospital Facility Structure (Buildings, Floors, Wards) | Full Access (Create, Edit, Deactivate) | View Assigned Only |
| QR Attendance Management & Printing | Full Access | QR Scan / Check-in Only |
| Shift Configuration & Supervisor Assignments | Full Access | View Assigned Shifts |
| Checklist Template Creation & Versioning | Full Access | View Assigned Templates |
| Inspection Schedule Rules & Generation | Full Access | View Assigned Schedules |
| Inspection Execution & Submission | View & Audit | Full Execution (Pass/Fail, Photos) |
| Complaint Management & Assignments | Full Access (Assign, Reopen, Close) | Create & Update Assigned |
| Executive Dashboard & Inspection Matrix | Full Campus View | Assigned Location View |
| Reports & Secure CSV Exports | Full Access | Assigned Reports Only |
| Hospital Settings & Geofence Policy | Full Access | No Access (403 Forbidden) |
| Automated Backups & Disaster Recovery | Full Access | No Access (403 Forbidden) |
| Audit Trail Logs | Full Access | No Access (403 Forbidden) |

---

## 2. Access, Login & Session Security

1. **Accessing the Portal**: Navigate to `https://your-domain.com/login` using any modern desktop or mobile browser.
2. **Authentication**: Enter your official email address and password.
3. **Session Inactivity Timeout**: For security compliance in clinical environments, sessions automatically expire after **60 minutes** of inactivity (configurable in Hospital Settings).
4. **Session Fixation Protection**: The system rotates the internal session identifier upon login and every 30 minutes thereafter.
5. **Logout**: Click the **Sign Out** button in the sidebar or top navigation bar. This invalidates the server session and clears client cookies immediately.

---

## 3. Hospital Facility Hierarchy Management

The hospital is modeled hierarchically: **Building -> Floor -> Ward / Inspection Zone**.

### Buildings
* Navigate to **Facility** > **Buildings**.
* Click **Add Building**. Provide Building Name (e.g., *Sardar Vallabhbhai Patel Block*), Code, Total Floors, and optional GPS coordinates.
* Status can be set to `Active` or `Inactive`. Inactivating a building preserves all historical records while hiding it from active inspection schedules.

### Floors
* Navigate to **Facility** > **Floors**.
* Floors belong to a specific Building and feature a floor index (e.g., Basement = `-1`, Ground = `0`, 1st Floor = `1`).
* Floor plans and emergency egress notes can be recorded.

### Wards & Inspection Zones
* Navigate to **Facility** > **Wards**.
* Assign each ward to a specific building and floor (e.g., *ICU Ward 3A*, *Pediatric General Ward*, *Emergency Trauma Centre*).
* Configure specific GPS coordinates for the ward if fine-grained attendance verification is required.
* **Generate QR Code**: Each ward has a unique cryptographic token. Click **Print QR Code** to generate a laminated poster for supervisor physical scan verification.

---

## 4. Shifts & Supervisor Assignments

### Shift Configuration
* Navigate to **Settings** > **Shifts**.
* Configure the hospital's standard working shifts:
  * **Morning Shift**: `07:00:00` to `15:00:00` (Grace period: 15 mins)
  * **Evening Shift**: `15:00:00` to `23:00:00` (Grace period: 15 mins)
  * **Night Shift**: `23:00:00` to `07:00:00` (Grace period: 15 mins)
* Define cross-midnight cut-offs and check-in windows.

### Supervisor Assignments
* Navigate to **Facility** > **Supervisor Assignments**.
* Assign active supervisors to specific wards and shifts.
* An individual supervisor can be assigned to multiple wards within a floor or across departments.

---

## 5. Checklist Templates, Versioning & Scheduling

### Checklist Template Creation
* Navigate to **Checklists** > **Templates**.
* Click **Create Template**. Enter Title (e.g., *High-Touch Surface Bio-Cleaning Audit*), Category (Sanitation, Biomedical Waste, Electrical, HVAC, Safety), and Target Area.
* Add checklist items with rich question types:
  * `Pass / Fail`: Binary compliance check.
  * `Yes / No`: Condition confirmation.
  * `Numeric Value`: Reading with lower and upper acceptable bounds (e.g., Room Temperature: 18°C - 24°C).
  * `Text Remark`: Freeform clinical or environmental note.
  * `Evidence Requirement`: Enforce photo upload before submission.
  * `Severity Weight`: Minor, Major, or Critical.

### Versioning & Historical Immutability
* **Draft**: Templates in draft mode can be freely modified.
* **Publishing**: When published, the template version is frozen (e.g., `v1.0`).
* **Immutability Guarantee**: Any completed inspection permanently references the exact version snapshot used during the audit. If questions are modified later, a new version (`v2.0`) is spawned without altering historical compliance reports.

### Custom Inspection Schedules
* Navigate to **Checklists** > **Schedules**.
* Define automated recurring rules:
  * Frequency: Daily, Specific Weekdays, or Bi-weekly.
  * Target Ward(s) and assigned Supervisor(s).
  * Shift linkage and inspection time windows.
  * Exclusions and Pause/Resume toggles for ward maintenance or renovations.

---

## 6. Real-Time Inspection Matrix & Live Status Monitoring

* Navigate to **Dashboard** > **Inspection Matrix**.
* View a live, color-coded matrix displaying all hospital wards against today's shifts:
  * ⚪ **Scheduled**: Upcoming inspection waiting for shift start.
  * 🟡 **In Progress**: Supervisor has checked in and begun auditing.
  * 🟢 **Completed**: Successfully audited with pass score calculated.
  * 🔴 **Overdue**: Scheduled time passed without completion.
  * ⚫ **Missed**: Shift ended without an audit submission.
* Click any cell to inspect item-by-item responses, supervisor remarks, GPS verification telemetry, and photo evidence.

---

## 7. Complaint Management & SLA Escalation Workflow

* Navigate to **Complaints** > **All Complaints**.
* **Filter Complaints**: Filter by Status, Category, Priority, Ward, Assignee, and SLA Breach status.
* **Assigning Complaints**: Click on a complaint ticket to assign or reassign it to a facility engineer or supervisor with instructions.
* **SLA Calculation**:
  * Response SLA: Target time to acknowledge and assign.
  * Resolution SLA: Target time to resolve the physical defect.
  * If the deadline passes without status moving to `Resolved`, the system automatically flags the ticket as `SLA Breached` in red.
* **Reopening Tickets**: Administrators may reopen a resolved or closed ticket if post-inspection verification shows incomplete work. A mandatory audit reason must be supplied.

---

## 8. Supervisor Attendance & Geofenced QR Auditing

* Navigate to **Attendance** > **Logs**.
* Review daily shift check-ins and check-outs across all departments.
* **Telemetry Data Recorded**:
  * Check-in timestamp and Shift linkage.
  * Scanned QR token and Ward ID.
  * GPS coordinates (Latitude, Longitude) and accuracy radius.
  * Distance from hospital campus center (calculated via the Haversine formula).
  * Geofence verification status: `Verified (Within Campus)` or `Flagged (Outside Geofence)`.

---

## 9. Reports Generation & Secure CSV Export

* Navigate to **Reports**.
* Select report type:
  1. **Inspection Completion Report**: Daily, weekly, and monthly compliance metrics.
  2. **Ward & Floor Breakdown Report**: Performance across hospital wings.
  3. **Checklist Failure & Critical Findings Report**: Recurring sanitation or safety defects.
  4. **Complaint Resolution & SLA Breach Report**: Response times and technician efficiency.
  5. **Supervisor Attendance & Geofence Audit Report**: Punctuality and presence verification.
* **Export CSV**: Click **Export CSV** to generate a sanitized data file.
  * *Formula Injection Protection*: All cells starting with `=`, `+`, `-`, or `@` are safely neutralized to prevent spreadsheet macro execution.
  * *Audit Logged*: Every report export records the user ID, timestamp, and query parameters in the audit log.

---

## 10. Automated Backups & Disaster Recovery

* Navigate to **Settings** > **Backups**.
* **Create Backup**:
  * `Database Only`: Full MySQL database dump with structure and data.
  * `Full Archive (DB + Evidence)`: Database dump plus all uploaded photos and PDFs in `storage/uploads/`.
  * `Enable Encryption`: Encrypts the archive using AES-256-CBC with HMAC-SHA256 authentication.
* **Isolated Test Restore**: Click **Test Restore** next to any archive. The system unzips and verifies table schemas in temporary staging tables without impacting live operations.
* **Retention Management**: The system automatically retains 7 daily, 4 weekly, and 3 monthly archives, purging older snapshots safely.
