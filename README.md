# SpecialityCare HMS — Demo (v2.0)

A full-featured **Hospital / Clinic Management System** built with Laravel. This
repository is a **self-contained demo**: it ships with a sample database
(fake / anonymised data) and a time-limited license so you can run the entire
product locally and evaluate it end-to-end.

> ⏳ **The bundled demo license expires on 2026-12-01.** All modules work until
> then; afterwards they lock automatically. To keep using the software beyond
> the demo period, you must **purchase a license** — see
> [Purchasing / using beyond 6 months](#purchasing--using-beyond-6-months).

---

## Feature highlights

A condensed list — see [`FEATURES.md`](FEATURES.md) for the full breakdown.

- **Patient Management** — registration with auto ID, global search + quick-view, dedup/merge, allergies, document store, outstanding dues.
- **Appointments** — booking, status workflow, one-click check-in / start visit, **FullCalendar** view with drag-to-reschedule.
- **OPD Queue** — token issuance, live public queue board, wait-time badges, printable register & slips.
- **Triage / Nursing** — triage levels, full vitals capture, quick-vitals modal, auto BMI, priority escalation.
- **Clinical Encounters** — SOAP notes with voice-to-text & auto-save, templates, vitals trend charts, diagnosis, follow-ups, ECG/Echo, patient timeline.
- **Prescriptions** — builder with editable dose/frequency/route dropdowns, templates, drug-interaction alerts, print/email, dispensing link.
- **Laboratory & Radiology** — orders from encounters, panels with reference ranges, result entry, status workflow, printable reports.
- **Pharmacy** — medicine catalogue, batch stock (FIFO), dispensing, low-stock & expiry alerts, HSN codes.
- **IPD** — admissions, ward/bed board, census, room charges, medications, I/O charts, discharge summaries, deposits.
- **Billing & Finance** — OPD/IPD bills, package billing, quick-bill modal, payments, overdue tracking, day-end report, insurance/claims.
- **Accounts & GST** — Tally-style double-entry, vouchers, GST (CGST/SGST/IGST), P&L, Balance Sheet, GSTR summaries.
- **Dashboard & Analytics** — live KPI cards, revenue charts with drill-down, alerts panel, bed occupancy, diagnosis analytics.
- **Notifications** — in-app bell, admin email alerts, WhatsApp/SMS module (bring your own provider).
- **Paediatrics** — vaccination tracker, WHO growth charts.
- **Consent & Legal, Referrals, Operation Theatre, HR & Payroll, Forum / Messaging.**
- **Staff & Admin** — multi-role users, editable permission matrix, doctor profiles, settings, activity logs.
- **Modular licensing** — features ship as ed25519-signed, toggleable modules.

---

## Requirements

- PHP **8.3+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `sodium`, `gd`, `zip`, `bcmath`, `intl`
- Composer
- MySQL **8.0+** or MariaDB **10.5+**
- Node.js 18+ and npm

## Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Environment file
cp .env.example .env
php artisan key:generate

# 3. Create the database
mysql -u root -p -e "CREATE DATABASE specialitycare_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Import the demo data
mysql -u root -p specialitycare_demo < databases/specialitycare-demo.sql

# 5. Set your DB credentials in .env
#    DB_DATABASE=specialitycare_demo
#    DB_USERNAME=root
#    DB_PASSWORD=your_password

# 6. Activate the bundled 6-month demo license
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

Open **http://localhost:8000**.

For production-style hosting, point your web server's document root at the
`public/` directory instead of using `php artisan serve`.

### Using the release zip instead

Each tagged release (see **Releases**) attaches a `specialitycare-demo-vX.Y.zip`
that contains the full source **and** the demo SQL. Download and unzip it, then
follow the same setup steps from step 1.

## Demo logins

All demo accounts use the password **`Demo@1234`**.

| Role   | Email                          | Password    |
|--------|--------------------------------|-------------|
| Admin  | `demo@specialitycare.co.in`    | `Demo@1234` |
| Admin  | `admin@demo.local`             | `Demo@1234` |
| Doctor | `doctor@demo.local`            | `Demo@1234` |

## Modules & licensing

Modules are gated by a signed license file (`storage/license/license.{json,sig}`)
verified with an ed25519 public key (`MODULES_PUBLIC_KEY` in `.env`). The bundled
demo license enables every module until **2026-12-01**, then they lock. The demo
runs fully **offline** — no licensing server is contacted.

## Purchasing / using beyond 6 months

This demo is for **evaluation only**. The bundled license stops working after
**2026-12-01**. If you want to deploy SpecialityCare for real use — or continue
past the demo window — you need a purchased commercial license, which includes:

- A license with the modules you need and an expiry that suits you (1 / 3 / 5 years).
- Your own per-install signing key (so your license can't be reused elsewhere).
- Installation help and updates.

**To purchase or request a quote, contact:**

- 📧 Email: **sales@specialitycare.co.in**
- 🌐 Website: **https://specialitycare.co.in**

> Do not rely on `MODULES_DEV_OVERRIDE` to bypass expiry — it exists only for
> local development and is not a license. Production use requires a purchased license.

## Notes

- The sample data is **fake / anonymised** — names, contact details and clinical
  records are for demonstration only and do not represent real patients.
- WhatsApp/SMS and outbound email are **disabled** by default (mail is written to
  `storage/logs/laravel.log`). Add your own provider credentials in `.env` to enable.
- This is a demonstration build, provided as-is without warranty. All rights reserved.
