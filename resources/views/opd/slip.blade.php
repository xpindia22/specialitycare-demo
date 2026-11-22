<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OPD Token Slip — {{ $token->display }}</title>
<style>
  body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #fff; }
  .slip { width: 300px; margin: 0 auto; border: 2px solid #0f1f3d; border-radius: 10px; padding: 20px; text-align: center; }
  .clinic-name { font-size: 16px; font-weight: 700; color: #0f1f3d; margin-bottom: 4px; }
  .clinic-sub  { font-size: 11px; color: #666; margin-bottom: 16px; }
  .token-box   { background: #0f1f3d; color: #0ab4b4; font-size: 56px; font-weight: 900; padding: 16px; border-radius: 8px; margin: 16px 0; letter-spacing: 2px; font-family: monospace; }
  .info-row    { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; border-bottom: 1px dashed #ddd; padding-bottom: 6px; }
  .info-label  { color: #666; font-weight: 600; }
  .info-val    { font-weight: 700; color: #0f1f3d; text-align: right; }
  .badge-urgent { background: #dc2626; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 8px; }
  .footer      { margin-top: 16px; font-size: 11px; color: #666; border-top: 1px solid #ddd; padding-top: 12px; }
  .queue-pos   { font-size: 13px; color: #0ab4b4; font-weight: 700; margin: 8px 0; }
  @media print {
    body { margin: 0; padding: 0; }
    .slip { border: 2px solid #000; }
  }
</style>
</head>
<body onload="window.print()">
<div class="slip">
  @if(setting('print_header_enabled','1') !== '0')
  <div class="clinic-name">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
  <div class="clinic-sub">OPD Token Slip</div>
  @endif

  @if($token->priority === 'urgent')
  <div class="badge-urgent">&#9888; URGENT</div>
  @endif

  <div class="token-box">{{ $token->display }}</div>

  <div class="queue-pos">Queue Position: {{ $position }}</div>

  <div class="info-row">
    <span class="info-label">Patient</span>
    <span class="info-val">{{ $token->patient->full_name }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">JHC ID</span>
    <span class="info-val">{{ $token->patient->jhc_id }}</span>
  </div>
  @if($token->doctor)
  <div class="info-row">
    <span class="info-label">Doctor</span>
    <span class="info-val">Dr. {{ $token->doctor->first_name }} {{ $token->doctor->last_name }}</span>
  </div>
  @endif
  <div class="info-row">
    <span class="info-label">Date</span>
    <span class="info-val">{{ $token->token_date->format('d M Y') }}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Time</span>
    <span class="info-val">{{ $token->created_at->format('h:i A') }}</span>
  </div>
  @if($token->purpose)
  <div class="info-row">
    <span class="info-label">Purpose</span>
    <span class="info-val">{{ $token->purpose }}</span>
  </div>
  @endif

  @if(setting('print_footer_enabled','1') !== '0')
  <div class="footer">
    Please wait to be called.<br>
    Keep this slip with you.
  </div>
  @endif
</div>
</body>
</html>
