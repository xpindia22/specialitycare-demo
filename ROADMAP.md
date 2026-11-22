# SpecialityCare — Roadmap

Pending features, ordered by what would actually help **this clinic right now** (low footfall, single location, no govt scheme empanelment). Acquisition first; compliance/scale later. WhatsApp/SMS at the bottom per user preference.

---

## Tier 1 — Footfall & online presence  *(highest priority)*

The clinic's bottleneck is patients walking in, not workflow gaps. These features either bring patients in or bring lapsed patients back.

1. ✅ **Public online booking page** (`/book`) — done 2026-05-09. Shareable URL for Google Business Profile, Justdial, prescription pads, social. Receptionist sees an "Online" badge in the appointments list.
2. **Public clinic landing page** (`/`) — replace the bare login redirect with a clinic info page (name, address, hours, doctors, services, "Book Now" button). Improves SEO and looks professional when someone clicks the link.
3. **Lapsed-patient finder** — dashboard widget listing patients last seen >90 days ago, with one-click call/SMS prompt. Pulls them back.
4. **Google review prompt** — after a completed visit, generate a one-tap review link the receptionist can SMS/share. More reviews → better Google Maps ranking.
5. **Referral source tracking on every patient** — surface the existing `referred_by` / `referral_source_id` field on the patient registration form (Walk-in / Google / Justdial / Friend / Doctor / Pamphlet). Three months of data tells you what's actually working.
6. **Share-link for reports & prescriptions** — secure short link the patient opens on their phone to view their lab report / prescription. Builds trust + word of mouth.
7. **Embeddable "Book Appointment" widget** — small JS snippet you can paste on any website (Justdial-style listing, blog, etc.) that opens the booking form in an iframe.

## Tier 2 — Patient experience polish

8. **Patient portal** — patients log in (phone OTP) to view past reports, prescriptions, bills, upcoming appointments.
9. **Telemedicine / video consult** — Jitsi/Twilio embed, link auto-generated per appointment. Useful for follow-ups even with low volume.
10. **e-Prescription with digital signature** — DSC-signed PDF for legal validity.
11. **Patient feedback / NPS** — post-visit one-tap rating + comment, drives Tier 1 #4.
12. **Self-check-in kiosk** — patient scans QR / enters phone at the front-desk tablet, auto-issues OPD token.

## Tier 3 — Inventory & operations  *(only when volume picks up)*

13. **Purchase orders + GRN** — full PO → GRN → stock-in workflow with supplier ledger.
14. **Non-drug consumables** — gloves, syringes, OT kits, lab reagents.
15. **Barcode / QR** — patient wristband, medicine label, lab sample.
16. **Multi-branch / multi-tenant** — only relevant if you open a second branch.

## Tier 4 — Compliance & integration  *(deferred — needs volume + empanelment)*

17. **ABDM / ABHA integration** — digital health records via the National Health Authority. Useful regardless of scheme; needs NHA sandbox registration first. Not urgent at current volume.
18. **GST-compliant tax invoice** — HSN/SAC, CGST/SGST/IGST split. Only matters once turnover crosses GST registration threshold (₹20 L/yr).
19. **PM-JAY / govt scheme module** — only if the clinic empanels.
20. **HL7 / LIS integration** — auto-pull results from lab analysers.
21. **TPA / insurance e-claim** — direct submission to TPAs.
22. **Govt HMIS reporting** — pre-formatted exports for state/central health reporting.

## Tier 5 — HR & administration

23. **Granular permission editor** — per-action permission matrix.
24. **Staff attendance** — biometric/manual punch-in (duty roster already exists).
25. **Payroll** — salary computation, payslips.

## Tier 6 — Infrastructure  *(do when you grow)*

26. **Automated scheduled backups** — cron-based daily DB + uploads with off-site copy.
27. **Two-factor authentication** — TOTP for admin/doctor logins.
28. **Audit log viewer UI** — filterable list for the activity_logs table.

## Tier 7 — Communication  *(last per user preference)*

29. **Notification engine — WhatsApp / SMS** — appointment reminders, lab-ready alerts, prescription delivery, follow-up nudges, lapsed-patient outreach. Channels: WhatsApp Business API (Meta or BSP like Gupshup/AiSensy), SMS gateway (MSG91/TextLocal). Per-event templates configurable in Settings.

---

*Last updated: 2026-05-09 — re-prioritised after the user noted very low footfall (~1 patient per 2 days) and no current use of govt schemes.*
