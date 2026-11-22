# WhatsApp — Gupshup (BSP) setup

SpecialityCare's chosen production WhatsApp path. Gupshup is a Meta Business
Solution Provider: it hosts your WhatsApp Business Account on Meta Cloud and
handles number onboarding, so you avoid the Meta developer-console wrangling.

Set `WHATSAPP_PROVIDER=gupshup` once the values below are filled. The **code is
done** (`sendViaGupshup` in `WhatsAppService`); this is the account/config side.

> Same Meta rule applies: clinic-initiated messages (reminders) must use an
> **approved template**. Free-form text only reaches a patient within 24h of
> their last message to you.

---

## 1. Account + app
- Sign up at <https://www.gupshup.io>. ✅ done
- Create a WhatsApp app (we named it **`jhcplin`**). This name is sent as
  `src.name` on every API call → goes in `GUPSHUP_APP_NAME`.
- Storage region: choose **India** if offered (data residency), else USA is fine.

## 2. Register the clinic number  ("New phone number")
- Pick **New phone number** (not Migrate — that's only for numbers already live
  on another BSP).
- ⚠️ The number **must not be active on any WhatsApp app** (personal or WhatsApp
  Business app). Delete it from the app first, or use a fresh number.
- Verify via the OTP Gupshup sends.
- This verified number (digits only, with country code, no `+`, e.g.
  `917432001215`) → goes in `GUPSHUP_SOURCE`.
- Business verification with Meta (via Gupshup) is needed to lift messaging
  limits — Gupshup guides this; can run after initial testing.

## 3. Get the API key
- Gupshup dashboard → top-right profile/account → **API key** (Dashboard →
  your app → it also exposes the key). → goes in `GUPSHUP_API_KEY`.

## 4. Submit the templates
Create these in the Gupshup dashboard → your app → **Templates** → Create.
Category **Utility**, language **English**. Use the bodies from
`docs/WHATSAPP_META_SETUP.md` (Part C) — the `{{1}}…{{n}}` order must match,
the app fills them positionally.

After each is **approved**, Gupshup shows a **Template ID (a UUID)**. Put that
UUID — *not* the name — in the matching env var:

```dotenv
GUPSHUP_TPL_APPT_REMINDER=<uuid>
GUPSHUP_TPL_APPT_CONFIRM=<uuid>
GUPSHUP_TPL_FOLLOWUP=<uuid>
GUPSHUP_TPL_VACCINATION=<uuid>
GUPSHUP_TPL_BILLING=<uuid>
GUPSHUP_TPL_WELCOME=<uuid>
```

## 5. Flip the switch
In `.env` on the server:

```dotenv
WHATSAPP_PROVIDER=gupshup
GUPSHUP_API_KEY=...
GUPSHUP_SOURCE=917432001215
GUPSHUP_APP_NAME=jhcplin
# + the template UUIDs above
```

Then:

```bash
php artisan config:clear
```

Check **Admin → Notifications → WhatsApp** — banner should read
"Active provider: Gupshup". Send a test from that page (free-form works during
the 24h window; otherwise test a template via the reminder crons' `--dry-run`
then a real run).

To roll back instantly: `WHATSAPP_PROVIDER=twilio` + `config:clear`.

---

## Remaining code work (after templates approved)

The provider layer, `sendViaGupshup()`, config, log columns and admin UI are
**done**. What's left is the same deferred step noted in the Meta doc: switch the
business-initiated call sites from free-form `sendToPatient()` to
`sendTemplate()` so reminders send approved templates with positional params
(the 4 reminder crons + 2 observers + `AppointmentController@remindWhatsApp`).
Deferred until the real Gupshup template UUIDs exist so the param order matches.
Manual free-form sends (admin page, owner alerts, forum) stay on `send()`.
