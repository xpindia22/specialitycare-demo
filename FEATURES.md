# SpecialityCare — Hospital Management System
**Demo Clinic Pvt. Ltd. | Laravel 11 | Built May 2025**

---

## Overview

SpecialityCare is a full-featured, web-based Hospital Management System built for a multi-speciality clinic. It covers the complete patient journey — from appointment booking and OPD queue management through clinical encounter documentation, laboratory, pharmacy, IPD admission, billing, and discharge — all within a single unified platform accessible by admin, doctors, nurses, and receptionists through role-based access control.

---

## 1. Patient Management

- **Registration & Profile** — Complete patient registration with JHC ID auto-generation, demographics, contact details, emergency contacts, and photo upload.
- **Global Search** — Instant search from any page by name, JHC ID, or phone number with a slide-in Quick View panel showing vitals, last encounter, outstanding balance, and one-click actions (New Bill, Admit, OPD Token).
- **Deduplication** — Smart duplicate detection on registration; admin can merge duplicate records without data loss.
- **Allergy Tracking** — Allergen and allergy records linked to the patient profile; surface during prescribing.
- **Document Store** — Upload and manage patient documents (lab reports, referral letters, consents, etc.).
- **Outstanding Dues** — Real-time outstanding balance shown on the patient profile and in the Quick View panel.

---

## 2. Appointments

- **Online Booking** — Book appointments with doctor, date, time, and purpose.
- **Status Workflow** — Scheduled → Confirmed → Checked-In → In-Progress → Completed / Cancelled.
- **One-Click Check-In / Start Visit** — Receptionist checks in the patient; doctor starts the encounter directly from the appointment card.
- **Dashboard View** — Today's appointment list with status badges and action buttons on the main dashboard.

---

## 3. OPD Queue Management

- **Token Issuance** — Issue OPD tokens at the front desk with patient search, doctor assignment, and priority (Normal / Urgent).
- **Live Queue Board** — Public-facing display board showing called token with auto-refresh.
- **Wait-Time Column** — Real-time elapsed wait badge on the nursing station queue: green (<15 min), amber (15–30 min), red (>30 min).
- **OPD Register** — Printable day-wise register of all tokens.
- **Token Slip** — Printable token slip with patient details and queue number.

---

## 4. Triage / Nursing Station

- **Combined Queue View** — Single page showing pending triage and full active queue, auto-refreshes every 45 seconds.
- **Full Triage Form** — Capture triage level (Green / Yellow / Red), chief complaint, BP, pulse, temperature (°F), SpO₂, weight, height, BMI (auto-computed), blood glucose, and nurse notes.
- **Quick Vitals** — Inline AJAX modal on the queue row to record BP, pulse, temp, SpO₂, and weight without leaving the page and without requiring a triage level — ideal for rapid vital capture before the doctor is ready.
- **Priority Escalation** — Red triage automatically escalates the token to urgent.
- **Re-Triage** — Nurse can update triage data at any point before the visit ends.
- **Triage Summary View** — View saved triage record with full vitals and assessor details.

---

## 5. Clinical Encounters

- **Encounter Workflow** — Each visit creates an Encounter linked to the patient, doctor, and appointment (optional).
- **SOAP Notes** — Structured Subjective / Objective / Assessment / Plan fields with voice-to-text dictation (Web Speech API) and auto-save every 30 seconds.
- **Encounter Templates** — Reusable note templates per doctor or specialty.
- **Vitals Recording** — BP, pulse, temperature, SpO₂, weight, height, BMI on every encounter.
- **Vitals Trend Charts** — Interactive chart on the patient profile showing vitals history across encounters.
- **Diagnosis** — ICD-style diagnosis entry with primary/secondary classification.
- **Follow-Up Scheduling** — Set a follow-up date; due follow-ups appear on the dashboard and nurse station.
- **Discharge Summary Auto-Draft** — Auto-generates a structured discharge summary from the encounter data.
- **ECG & Echo Recordings** — Attach ECG and echocardiogram records to an encounter.

---

## 6. Prescriptions

- **Prescription Builder** — Add medicines with dose, frequency, duration, route, and instructions.
- **Prescription Templates** — Save and reuse prescription templates for common conditions.
- **Drug Interaction Alerts** — Real-time warnings when adding a medicine that interacts with another already on the prescription (backed by a ~100-rule interaction database).
- **Expiring Rx Alert** — Dashboard badge and sidebar card listing undispensed prescriptions whose duration expires within 7 days, so nurses can follow up on collection.
- **Print / Email Rx** — One-click print and email of the prescription PDF to the patient.
- **Dispensing Link** — Prescription items flow directly into the pharmacy dispensing workflow; dispensed items are marked to avoid re-dispensing.

---

## 7. Laboratory

- **Lab Order Management** — Doctors raise lab orders from within an encounter; orders are visible to the lab technician.
- **Lab Tests & Panels** — Configurable test catalogue with reference ranges by age and gender.
- **Result Entry** — Technician enters results per test item; abnormal values are flagged automatically.
- **Status Workflow** — Ordered → Collected → Processing → Completed.
- **Results Ready Alert** — Dashboard badge when lab results are completed today and awaiting clinical review.
- **Pending Labs KPI** — Dashboard stat card for all in-progress orders.
- **Print Report** — Formatted lab report printout.

---

## 8. Radiology

- **Radiology Orders** — Raise radiology requests (X-Ray, MRI, CT, Ultrasound, etc.) from the encounter.
- **Report Entry** — Attach findings and reports to the order.
- **Status Tracking** — Ordered → Reported workflow.

---

## 9. Pharmacy

- **Medicine Catalogue** — Master list of medicines with category, form, strength, reorder level, and HSN code.
- **Stock Management** — Batch-wise stock register with purchase date, expiry date, and quantity; FIFO-aware.
- **Dispensing** — Dispense against OPD prescriptions; quantity is deducted from the correct batch.
- **Low Stock Alert** — Dashboard badge and detail card for medicines at or below reorder level.
- **Expiring Stock Alert** — Dashboard badge and detail card for batches expiring within 90 days.
- **Drug Interaction Rules** — Admin-managed interaction rules (~100 seeded pairs); real-time warnings in the prescription builder.
- **Pharmacy Dashboard** — Overview of dispensing queue, stock status, and expiry calendar.

---

## 10. IPD (In-Patient Department)

- **Admissions** — Admit a patient to a ward and bed with admitting doctor, admission date, diagnosis, and deposit.
- **Ward & Bed Management** — Configure wards and beds; track availability in real time.
- **Bed Board** — Visual bed board color-coded by length of stay: green (day 1), amber (days 2–3), orange (days 4–6), red (day 7+) with a day-count badge on each tile and a ward-level long-stay alert.
- **IPD Census** — Dashboard sidebar widget showing patient count per ward.
- **Room Charges** — Daily room charge generation for all admitted patients (one-click from dashboard).
- **IPD Medications** — Administer inpatient medications and log doses.
- **Diet Prescriptions** — Attach diet plans to IPD admissions.
- **Nursing Notes (I/O Chart)** — Intake/output charting for IPD patients.
- **Discharge Process** — Discharge with date, discharge diagnosis, condition on discharge, and auto-drafted summary.
- **IPD Charges** — Track all charges (room, procedures, consumables) for the admission.
- **Deposits & Advance** — Record and track advance deposits against the admission.

---

## 11. Billing & Finance

- **OPD Bills** — Generate itemised bills for outpatient visits with service charges.
- **Package Billing** — Pre-configured service packages that populate multiple line items at once.
- **Quick Bill Modal** — From the encounter page, open a pre-populated bill modal with consultation fee and lab items already filled; add more services from a searchable catalogue; set discount, payment method, and paid amount — without navigating away.
- **IPD Bills** — Consolidated billing for all IPD charges on discharge.
- **Payments** — Record full or partial payments; track balance due.
- **Overdue Bills Badge** — Sidebar badge showing count of unpaid bills older than 7 days.
- **Pending Bills Alert** — Dashboard alert for all unpaid bills.
- **Insurance & Claims** — Attach insurance details to admissions; manage claims.
- **Day-End Report** — End-of-day financial summary for cash reconciliation.
- **Outstanding Dues** — Per-patient and aggregate outstanding balance views.

---

## 12. Dashboard & Analytics

- **KPI Cards** — Live stat cards for OPD tokens, appointments, IPD census, beds, revenue, labs, and pharmacy — auto-refreshed via AJAX every 60 seconds.
- **Revenue Chart** — Monthly OPD + IPD combined bar chart for the current year.
- **Revenue Drill-Down** — Click any month on the revenue chart to open a day-by-day modal chart with OPD, IPD, and combined totals.
- **Alerts Section** — Consolidated alert panel: pending labs, low stock, expiring stock, unpaid bills, lab results ready, and expiring prescriptions.
- **Follow-Ups Due** — Sidebar widget listing today's follow-up encounters with one-click navigation.
- **Diagnosis Analytics** — Frequency charts for diagnoses across encounters (top conditions, trends).
- **Bed Occupancy %** — Live occupancy percentage with a color-coded progress bar on the dashboard.

---

## 13. Notifications

- **In-App Notification Bell** — Bell icon in the topbar with unread badge; dropdown lists recent system notifications.
- **Admin Email Notifications** — Automatic email alerts to admin on key events: new admission, discharge, new patient registration, lab result ready, low stock.
- **WhatsApp via Twilio** — (Module: `notifications`) Patient WhatsApp messages over Twilio. Auto-confirm on appointment booking; cron jobs for day-before reminders, encounter follow-ups, vaccinations due in next 3 days, and overdue billing. Per-patient ad-hoc "Send WhatsApp" button with templated greeting. Admin page for test sends and audit log of every message (status, error, Twilio SID). Phone numbers normalised to E.164; junk/legacy numbers rejected. Hard daily ceiling (`TWILIO_DAILY_LIMIT`) and per-command `--dry-run` / `--limit` flags so a runaway batch can't blast a large patient base.

---

## 14. Paediatric Features

- **Vaccination Tracker** — Record and track immunisations against the standard vaccination schedule; due and overdue vaccines highlighted.
- **Paediatric Growth Charts** — WHO-standard weight-for-age and height-for-age charts plotted from vitals history.

---

## 15. Consent & Legal

- **Consent Templates** — Configurable consent form templates (surgical, anaesthesia, general treatment, etc.).
- **Consent Records** — Issue and record signed consent for a patient encounter or admission; attach scanned signature.
- **Medical Certificates** — Generate medical fitness or leave certificates from an encounter.

---

## 16. Referrals

- **Outgoing Referrals** — Refer a patient to an external specialist or facility; track referral status.
- **Referral Dashboard** — Overview of all active referrals.

---

## 17. Operation Theatre

- **OT Scheduling** — Schedule surgical procedures with surgeon, anaesthetist, date, and room.
- **OT Notes** — Record operative findings and post-op instructions.

---

## 18. HR & Payroll

- **Staff Attendance** — Daily attendance register with present / absent / half-day / leave marking and monthly summary view.
- **Pay Adjustments** — Per-staff bonuses, deductions, advances, and reimbursements logged against the pay period.
- **Monthly Payroll Run** — Generate payroll for a month from base salary, attendance, and adjustments; locked once finalised.
- **Salary Slips** — Printable salary slip per staff member per month with full breakdown.

---

## 19. Staff & Administration

- **User Management** — Create staff accounts with roles: Admin, Doctor, Nurse, Receptionist, Lab Technician, Pharmacist.
- **Multi-Role per User** — A single user can hold multiple roles simultaneously (e.g., Doctor + Admin); the sidebar and permissions union across all assigned roles.
- **Permission Matrix** — Editable role × capability matrix in the admin panel; tweak what each role can see and do without touching code.
- **Doctor Profiles** — Specialization, qualifications, schedule, and signature for prescriptions.
- **Settings** — Hospital name, logo, address, contact details, currency, and system preferences.
- **Activity Logs** — Full audit trail of all key actions with user, timestamp, and description.
- **Reports & Certificates** — Reports and certificate generation surfaced as their own sidebar sections (split out from Administration).

---

## 20. Modules & Licensing

- **Modular Catalogue** — Big features (HR, OPD, IPD, Pharmacy, Lab, Billing) ship as toggleable modules; only licensed modules are enabled on a deployment.
- **Cryptographic Gating** — License is an ed25519-signed JSON file. The buyer cannot enable a module without a valid signature, even with full shell and DB access — the private signing key never leaves the owner's machine.
- **Per-Buyer Keypair** — Each buyer install gets its own keypair so a leaked or copied license cannot be reused on a different deployment.
- **Friendly 402 Page** — Routes for unlicensed modules render a clean "module not licensed" page instead of a hard error.
- **Owner Panel** — Hidden owner-only panel (gated by `OWNER_KEY` env var; returns 404 without it) for issuing, viewing, and re-signing licenses. Owner sessions are invalidated automatically when `OWNER_KEY` rotates.
- **Buyer Ledger** — Owner-side dashboard tracking each buyer, their issued licenses, modules enabled, and renewal status.
- **Heartbeat Clone-Detection** — Each install phones home periodically with its install ID; the owner panel surfaces duplicate or suspicious heartbeats so cloned deployments can be spotted.
- **Self-Healing License Writes** — License files are re-written from the panel with world-readable perms so a misconfigured filesystem can't silently disable modules.

---

## 21. Data & Integration

- **Backup** — One-click full database backup downloadable as a SQL file.
- **CSV Export** — Export patients, encounters, billing, and lab data to CSV for reporting.
- **Import** — Bulk patient import via CSV.
- **Email** — SMTP-integrated email for prescriptions, bills, and admin notifications.
- **Print** — Formatted print views for tokens, slips, bills, lab reports, prescriptions, and discharge summaries.
- **API** — Internal API endpoints for AJAX operations (search, stats refresh, drill-down data, quick actions).

---

## 20. UX & Developer Notes

- **Responsive Design** — Works on desktop and tablet; sidebar collapses on smaller screens.
- **Design System** — Custom CSS variables (`--sc-navy`, `--sc-teal`, `--sc-amber`, `--sc-red`, `--sc-green`) applied consistently; card, table, badge, and button components.
- **Global Search** — Searches patients, encounters, and bills from the topbar on any page.
- **Quick View Panel** — Slide-in patient summary panel from search results without navigating away.
- **Voice-to-Text** — Web Speech API dictation on clinical note fields.
- **Auto-Save** — Encounter notes auto-saved every 30 seconds to prevent data loss.
- **Select2 Dropdowns** — Searchable medicine and service dropdowns throughout.
- **Chart.js** — Interactive charts for revenue, vitals trends, and growth charts.
