# WhatsApp — Meta Cloud API setup

SpecialityCare can send WhatsApp via two backends, chosen by `WHATSAPP_PROVIDER`
in `.env`:

- `twilio` — the current Twilio sandbox (test only).
- `meta` — **Meta WhatsApp Cloud API, direct** (production, cheapest).

This guide gets you from the Twilio sandbox to a live Meta sender. The **code is
already done** — once the steps below are complete you flip one env var.

> **The key rule:** any message *the clinic initiates* (appointment reminder,
> follow-up nudge, vaccination/bill reminder, welcome) must use a **pre-approved
> template**. Free-form text only reaches a patient within **24 hours** of *their*
> last message to you. This is Meta's rule, not ours — so the templates below
> must be created and approved before reminders will send.

---

## Part A — what you do in Meta (one-time, ~1–2 hrs + review wait)

1. **Meta Business account** — create/verify a Business at
   <https://business.facebook.com>. Business verification (company docs) is
   needed to raise messaging limits beyond the trial tier.
2. **A dedicated phone number** for the API. ⚠️ It **cannot** be a number that's
   currently active in the WhatsApp Business *app* — pick a fresh SIM/number, or
   migrate (and delete it from the app first). It must be able to receive an
   SMS/call OTP.
3. **Create a Meta App** at <https://developers.facebook.com> → "Business" type →
   add the **WhatsApp** product. This gives you a test number + a **Phone number
   ID** and a **WhatsApp Business Account (WABA) ID**.
4. **Register your real number** under WhatsApp → API Setup, verify via OTP.
5. **Permanent access token** — the token shown in API Setup is temporary (24h).
   Create a **System User** (Business Settings → Users → System Users) with admin
   access to the WhatsApp app, then **Generate Token** with the
   `whatsapp_business_messaging` + `whatsapp_business_management` scopes. Copy
   this **permanent** token.
6. **Submit the message templates** (Part C). Approval is usually minutes–hours.

Collect these four values for `.env`:

| `.env` key | Where in Meta |
|---|---|
| `META_WA_TOKEN` | System User → permanent token |
| `META_WA_PHONE_NUMBER_ID` | WhatsApp → API Setup → "Phone number ID" |
| `META_WA_WABA_ID` | WhatsApp → API Setup → "WhatsApp Business Account ID" |
| `META_WA_API_VERSION` | leave `v21.0` |

---

## Part B — flip the switch

In `.env` on the server:

```dotenv
WHATSAPP_PROVIDER=meta
META_WA_TOKEN=EAAG...               # permanent token
META_WA_PHONE_NUMBER_ID=123456789012345
META_WA_WABA_ID=109876543210987
META_WA_TEMPLATE_LANG=en
# set each to the exact name Meta approved (see Part C):
META_WA_TPL_APPT_REMINDER=appointment_reminder
META_WA_TPL_APPT_CONFIRM=appointment_confirm
META_WA_TPL_FOLLOWUP=followup_reminder
META_WA_TPL_VACCINATION=vaccination_due
META_WA_TPL_BILLING=bill_overdue
META_WA_TPL_WELCOME=patient_welcome
```

Then:

```bash
php artisan config:clear
```

Check **Admin → Notifications → WhatsApp**: the banner should read
"Active provider: Meta WhatsApp Cloud API". Send yourself a free-form test from
that page **only after messaging the clinic number first** (24h window), or test
a template via the reminder commands' `--dry-run` then a real run.

To roll back instantly: set `WHATSAPP_PROVIDER=twilio` and `config:clear`.

---

## Part C — templates to submit

Create these in Meta Business Manager → WhatsApp Manager → **Message templates**.
Category **Utility** (transactional; cheaper + faster approval than Marketing).
Language **English**. Use the exact body below — the `{{n}}` numbered variables
**must stay in this order**, because the app fills them positionally.

After approval, put the approved template *name* in the matching `META_WA_TPL_*`
env var. If Meta makes you rename one, just update the env var — no code change.

> The wording mirrors what the app sends today on Twilio, condensed to fit
> template rules. **Meta rejects a template whose body starts or ends with a
> variable** (and variables can't be adjacent). So every body below starts with
> "Hi " and ends with static text (`Regards, {{5}} team.` etc.) — do NOT move a
> `{{n}}` to the very start/end. The doctor clause `{{2}}` now includes the word
> "with" so it never relies on a fragile leading space inside the variable.
>
> These exact texts were submitted to Gupshup via API on 2026-05-22 (the first
> set, which ended in `— {{5}}`, was rejected for the start/end rule). The app
> fills `{{n}}` positionally, so the order must not change.

### 1. `appointment_reminder`  (used by appointment reminder cron + the manual "remind" button)
```
Hi {{1}}, reminder: your appointment {{2}} is on {{3}}. Appointment no: {{4}}. Please arrive 10 minutes early. Reply if you need to reschedule. Regards, {{5}} team.
```
Variables: `{{1}}` patient name · `{{2}}` "with Dr. X" (or "with our doctor") · `{{3}}` date/time · `{{4}}` appointment no · `{{5}}` clinic name.

### 2. `appointment_confirm`  (sent when an appointment is booked)
```
Hi {{1}}, your appointment {{2}} is confirmed for {{3}}. Appointment no: {{4}}. Please arrive 10 minutes early. Reply if you need to reschedule. Regards, {{5}} team.
```
Variables: same as above.

### 3. `followup_reminder`  (follow-up reminder cron)
```
Hi {{1}}, friendly reminder: {{2}} advised a follow-up visit on {{3}}. {{4}} Please call us to confirm your slot. Regards, {{5}} team.
```
Variables: `{{1}}` name · `{{2}}` "Dr. X" (or "your doctor") · `{{3}}` date · `{{4}}` note/instructions (or "-") · `{{5}}` clinic.

### 4. `vaccination_due`  (vaccination reminder cron)
```
Hi {{1}}, vaccination reminder: {{2}} ({{3}} dose) is due on {{4}}. Please book a slot at your earliest convenience. Regards, {{5}} team.
```
Variables: `{{1}}` name · `{{2}}` vaccine name · `{{3}}` dose · `{{4}}` due date · `{{5}}` clinic.

### 5. `bill_overdue`  (overdue-bill reminder cron)
```
Hi {{1}}, reminder: bill #{{2}} of Rs {{3}} dated {{4}} is overdue. Please contact the clinic to settle it. Thank you, {{5}} team.
```
Variables: `{{1}}` name · `{{2}}` bill no · `{{3}}` amount · `{{4}}` bill date · `{{5}}` clinic.

### 6. `patient_welcome`  (sent on new patient registration)
```
Hi {{1}}, your registration at {{2}} is confirmed. Your patient ID is {{3}}. Please save this number for appointment confirmations, reminders and reports.
```
Variables: `{{1}}` name · `{{2}}` clinic name · `{{3}}` patient ID. (Only 3 variables; ends on static text.)
> **Why "registration confirmed" and not "welcome to":** Meta rejected the
> "Hi {{1}}, welcome to {{2}}…" wording as **Incorrect Category** under UTILITY
> (a welcome reads as MARKETING). Phrasing it as a transactional registration
> confirmation keeps it UTILITY (cheaper, no opt-in friction). Don't reintroduce
> "welcome to" under UTILITY.

---

## Part D — remaining code work (after templates are approved)

The provider layer, Meta sender, `sendTemplate()`, config, log columns and the
admin UI are **done**. What's left is to switch the **business-initiated** call
sites from free-form `sendToPatient()` to `sendTemplate()` so they send the
approved templates with positional params:

- `app/Console/Commands/SendAppointmentWhatsAppReminders.php`
- `app/Console/Commands/SendFollowUpWhatsAppReminders.php`
- `app/Console/Commands/SendVaccinationDueWhatsApp.php`
- `app/Console/Commands/SendOverdueBillWhatsApp.php`
- `app/Observers/AppointmentObserver.php` (confirm) · `app/Observers/PatientObserver.php` (welcome)
- `app/Http/Controllers/AppointmentController.php@remindWhatsApp`

Each keeps building today's human-readable body (for the audit log + the
`[ref:...]` de-dup) and additionally passes `(templateKey, [params...])`. On the
Twilio backend `sendTemplate()` still sends that body as free-form, so the
sandbox keeps working unchanged. **This step is intentionally deferred until the
real template names/wording are approved** so the param order matches Meta.

Manual free-form sends (admin page, owner alerts, forum) stay on `send()` and are
unaffected.
