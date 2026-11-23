# SpecialityCare HMS — Demo (v1.0.0)

A full-featured, web-based **Hospital / Clinic Management System** built with
Laravel. This repository is a **self-contained demo**: it ships with a sample
database (fake / anonymised data) and a time-limited license so you can run the
entire product locally and evaluate every module end-to-end.

> ⏳ **The bundled demo license expires on 2027-05-22.** All modules work until
> then; afterwards they lock automatically. To keep using the software beyond
> the demo period you must **purchase a license** — see
> [Purchasing / using beyond the demo](#purchasing--using-beyond-the-demo).

---

## Table of contents

- [Quick start](#quick-start)
- [Demo logins](#demo-logins)
- [Requirements](#requirements)
- [Full feature list](#full-feature-list)
- [Modules & licensing](#modules--licensing)
- [Purchasing / using beyond the demo](#purchasing--using-beyond-the-demo)
- [Notes](#notes)

---

## Quick start

```bash
# 1. Install PHP dependencies
composer install

# 2. Environment file
cp .env.example .env
php artisan key:generate

# 3. Create the database
mysql -u root -p -e "CREATE DATABASE specialitycare_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Import the demo data (ships in this repo)
mysql -u root -p specialitycare_demo < databases/specialitycare-demo.sql

# 5. Set your DB credentials in .env
#    DB_DATABASE=specialitycare_demo
#    DB_USERNAME=root
#    DB_PASSWORD=your_password

# 6. Activate the bundled demo license
mkdir -p storage/license
cp demo-license/license.json demo-license/license.sig storage/license/
#    (MODULES_PUBLIC_KEY is already set in .env.example to match this license)

# 7. Storage symlink + front-end assets
php artisan storage:link
npm install
npm run build

# 8. Run it
php artisan serve
```

Open **http://localhost:8000** and sign in with a demo login below.

For production-style hosting, point your web server's document root at the
`public/` directory. If you serve the app under a sub-path (e.g.
`/specialitycare-demo`), set `RewriteBase` in `public/.htaccess` to match.

### Using the release zip

Each tagged release attaches a `specialitycare-demo-vX.Y.Z.zip` containing the
full source **and** the demo SQL. Download, unzip, then follow the Quick start
from step 1.

---

## Demo logins

All demo accounts use the password **`Demo@1234`** (also shown on the login page
with a one-click "Fill demo login" button).

| Role   | Email                          | Password    |
|--------|--------------------------------|-------------|
| Admin  | `demo@specialitycare.co.in`    | `Demo@1234` |
| Admin  | `admin@demo.local`             | `Demo@1234` |
| Doctor | `doctor@demo.local`            | `Demo@1234` |

---

## Requirements

- PHP **8.3+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `sodium`, `gd`, `zip`, `bcmath`, `intl`
- Composer
- MySQL **8.0+** or MariaDB **10.5+**
- Node.js **18+** and npm

---

## Full feature list

SpecialityCare covers the complete patient journey — appointment booking, OPD
queue, triage, clinical documentation, lab, radiology, pharmacy, IPD, billing,
accounts and discharge — in one platform with role-based access for admins,
doctors, nurses, receptionists, lab techs and pharmacists.

### 1. Patient Management
- Registration with JHC-ID auto-generation, demographics, emergency contacts, photo.
- Global search (name / ID / phone) with a slide-in Quick View (vitals, last encounter, balance, one-click New Bill / Admit / OPD Token).
- Smart duplicate detection and lossless merge.
- Allergy tracking surfaced during prescribing.
- Per-patient document store (lab reports, referrals, consents).
- Real-time outstanding-dues display.

### 2. Appointments
- Online booking with doctor, date, time and purpose.
- Status workflow: Scheduled → Confirmed → Checked-In → In-Progress → Completed / Cancelled.
- One-click check-in and "start visit" straight into the encounter.
- **FullCalendar** view with drag-to-reschedule; today's list on the dashboard.

### 3. OPD Queue
- Token issuance with doctor assignment and priority (Normal / Urgent).
- Live public queue board with auto-refresh.
- Real-time wait-time badges: green (<15 min), amber (15–30), red (>30).
- Printable OPD register and token slips.

### 4. Triage / Nursing Station
- Combined pending-triage + active-queue view (auto-refresh).
- Full triage form: level (Green/Yellow/Red), complaint, BP, pulse, temp, SpO₂, weight, height, auto-BMI, glucose, notes.
- Inline **Quick Vitals** AJAX modal for rapid capture without a triage level.
- Red triage auto-escalates to urgent; re-triage any time before visit end.

### 5. Clinical Encounters
- SOAP notes with **voice-to-text** dictation and **auto-save every 30 s**.
- Reusable encounter/note templates per doctor or specialty.
- Vitals capture with interactive **vitals-trend charts** on the profile.
- ICD-style diagnosis (primary/secondary), follow-up scheduling, patient timeline.
- ECG & Echo attachments; auto-drafted discharge summary.

### 6. Clinical Decision Support (Clinical Reminders)
- OpenEMR-style **live advisory pop-up** on the encounter screen.
- Suggestions driven by a configurable `clinical_rules` table (30 seeded rules across cardiology, diabetes, rheumatology/autoimmune, malignancy screening, etc.).
- Admin-editable rules with search under **Settings → Clinical Rules** (view/edit, no code changes).

### 7. Prescriptions
- Builder with editable dose/frequency/route/duration and instructions.
- Saveable prescription templates for common conditions.
- Real-time **drug-interaction alerts** (~100-rule database).
- "Expiring Rx" dashboard badge for undispensed prescriptions due within 7 days.
- One-click print / email of the Rx PDF; items flow into pharmacy dispensing.

### 8. Laboratory & Radiology
- Lab orders raised from the encounter; configurable tests/panels with age/gender reference ranges.
- Result entry with automatic abnormal-value flagging; Ordered → Collected → Processing → Completed.
- "Results ready" dashboard alert and pending-labs KPI; printable reports.
- Radiology requests (X-Ray, MRI, CT, Ultrasound) with findings/report entry and Ordered → Reported tracking.

### 9. Pharmacy
- Medicine catalogue (category, form, strength, reorder level, HSN).
- Batch-wise, FIFO-aware stock register; dispensing against OPD prescriptions.
- Low-stock and expiring-stock (within 90 days) dashboard alerts.
- Admin-managed drug-interaction rules; pharmacy dashboard with dispensing queue and expiry calendar.

### 10. IPD (In-Patient Department)
- Admissions with ward/bed, admitting doctor, diagnosis and deposit.
- Visual **bed board** colour-coded by length of stay, with ward-level long-stay alerts; live IPD census widget.
- Daily room-charge generation, inpatient medication/dose logging, diet plans, I/O (intake-output) charts.
- Discharge with condition, auto-drafted summary; full IPD charge tracking; advance deposits.

### 11. Billing & Finance
- Itemised OPD bills, **package billing**, and a pre-populated **Quick Bill** modal from the encounter.
- Consolidated IPD billing on discharge; full/partial payments with balance tracking.
- Overdue-bills badge (>7 days), pending-bills dashboard alert, day-end cash-reconciliation report.
- Insurance details and claims on admissions; per-patient and aggregate outstanding dues.

### 12. Accounts & GST
- Tally-style **double-entry** accounting with vouchers.
- GST (CGST/SGST/IGST), P&L, Balance Sheet and GSTR summaries.

### 13. Dashboard & Analytics
- Live KPI cards (OPD, appointments, IPD census, beds, revenue, labs, pharmacy), auto-refreshed.
- Monthly revenue chart with day-by-day **drill-down**; diagnosis-frequency analytics.
- Consolidated alerts panel; follow-ups-due widget; live bed-occupancy %.

### 14. Notifications
- In-app notification bell with unread badge.
- Admin email alerts on key events (admission, discharge, registration, lab-ready, low stock).
- **WhatsApp / SMS** module (bring your own Twilio / Gupshup / Meta provider): appointment confirmations, day-before reminders, follow-ups, vaccination and overdue-billing cron jobs, per-patient ad-hoc send, full audit log, E.164 normalisation, daily send ceiling and `--dry-run` / `--limit` safety flags.

### 15. Paediatrics
- Vaccination tracker against the standard schedule (due/overdue highlighting).
- WHO-standard weight-for-age and height-for-age growth charts.

### 16. Consent & Legal, Referrals, Operation Theatre
- Configurable consent templates, signed consent records, medical certificates.
- Outgoing referrals with status tracking and a referral dashboard.
- OT scheduling (surgeon, anaesthetist, room) with operative/post-op notes.

### 17. HR & Payroll
- Staff attendance register (present/absent/half-day/leave) with monthly summary.
- Pay adjustments (bonus/deduction/advance/reimbursement), monthly payroll run, printable salary slips.

### 18. Staff & Administration
- Users with roles (Admin, Doctor, Nurse, Receptionist, Lab Tech, Pharmacist); **multiple roles per user**.
- Editable role × capability **permission matrix** (no code changes).
- Doctor profiles (specialisation, qualifications, signature), hospital settings, full activity-log audit trail.

### 19. Forum / Messaging
- Built-in staff forum and patient messaging.

### 20. Data & Integration
- One-click SQL backup; CSV export (patients, encounters, billing, lab) and bulk CSV patient import.
- SMTP email for prescriptions/bills/notifications; formatted print views throughout.
- Internal AJAX API for search, stat refresh, drill-downs and quick actions.

### 21. UX & platform
- Responsive layout (sidebar collapses on smaller screens); consistent CSS design system.
- Global search + slide-in Quick View from any page; Select2 searchable dropdowns; Chart.js charts.

---

## Modules & licensing

Big features ship as toggleable, **ed25519-signed modules**. Only modules named
in the signed license file (`storage/license/license.{json,sig}`, verified with
`MODULES_PUBLIC_KEY`) are enabled — a buyer can't enable a module without a valid
signature, even with full shell/DB access, because the private signing key never
leaves the owner. The bundled demo license enables every module
(**hr, opd, ipd, pharmacy, lab, billing, notifications, accounts, forum,
ai_assistant**) until **2027-05-22**, then they lock. The demo runs fully
**offline** — no licensing server is contacted.

> Do not rely on `MODULES_DEV_OVERRIDE` to bypass expiry — it exists only for
> local development and is not a license.

---

## Purchasing / using beyond the demo

This demo is for **evaluation only**. The bundled license stops working after
**2027-05-22**. For real deployment — or to continue past the demo window — a
purchased commercial license includes:

- The modules you need with an expiry that suits you (1 / 3 / 5 years).
- Your own per-install signing keypair (so a license can't be reused elsewhere).
- Installation help and updates.

**To purchase or request a quote:**

- 📧 Email: **demo@specialitycare.co.in**
- 🌐 Website: **https://specialitycare.co.in**

---

## Notes

- The sample data is **fake / anonymised** — names, contact details and clinical
  records are for demonstration only and do not represent real patients.
- WhatsApp/SMS and outbound email are **disabled** by default (mail is written to
  `storage/logs/laravel.log`). Add your own provider credentials in `.env` to enable.
- This is a demonstration build, provided as-is without warranty. All rights reserved.
