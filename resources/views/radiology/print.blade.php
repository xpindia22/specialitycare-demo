<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Radiology Report — {{ $order->order_no }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #1a2942; padding: 20mm; }
  .header { text-align: center; border-bottom: 2px solid #0f1f3d; padding-bottom: 12px; margin-bottom: 16px; }
  .clinic-name { font-size: 20px; font-weight: 700; color: #0f1f3d; }
  .clinic-sub  { font-size: 11px; color: #666; }
  .doc-title   { font-size: 15px; font-weight: 700; text-align: center; margin: 12px 0; text-transform: uppercase; letter-spacing: 1px; }
  .section { margin-bottom: 12px; }
  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #0f1f3d; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 8px; }
  .grid { display: table; width: 100%; }
  .row  { display: table-row; }
  .cell { display: table-cell; padding: 3px 8px 3px 0; width: 50%; }
  .label { font-size: 10px; font-weight: 700; color: #666; text-transform: uppercase; display: block; }
  .value { font-size: 12px; }
  .text-box { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; border-radius: 4px; white-space: pre-wrap; line-height: 1.6; }
  .sig-section { margin-top: 40px; display: table; width: 100%; }
  .sig-cell { display: table-cell; text-align: center; width: 50%; }
  .sig-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 6px; font-size: 11px; color: #666; }
  .footer { margin-top: 16px; text-align: center; font-size: 10px; color: #888; border-top: 1px dashed #ccc; padding-top: 8px; }
  @media print { body { padding: 15mm; } }
</style>
</head>
<body onload="window.print()">
@if(setting('print_header_enabled','1') !== '0')
<div class="header">
  <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
  <div class="clinic-sub">{{ setting('clinic_address', '') }} | {{ setting('clinic_phone', '') }}</div>
</div>
@endif

<div class="doc-title">Radiology Report</div>

<div class="section">
  <div class="section-title">Order Information</div>
  <div class="grid">
    <div class="row">
      <div class="cell"><span class="label">Order No.</span><span class="value">{{ $order->order_no }}</span></div>
      <div class="cell"><span class="label">Date</span><span class="value">{{ $order->created_at->format('d M Y') }}</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Modality</span><span class="value">{{ \App\Models\RadiologyOrder::MODALITIES[$order->modality]['label'] ?? $order->modality }}</span></div>
      <div class="cell"><span class="label">Body Part</span><span class="value">{{ $order->body_part }}</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Ordered By</span><span class="value">{{ $order->orderedBy ? 'Dr. '.$order->orderedBy->first_name.' '.$order->orderedBy->last_name : '—' }}</span></div>
      <div class="cell"><span class="label">Urgency</span><span class="value">{{ ucfirst($order->urgency) }}</span></div>
    </div>
  </div>
</div>

<div class="section">
  <div class="section-title">Patient Information</div>
  <div class="grid">
    <div class="row">
      <div class="cell"><span class="label">Name</span><span class="value">{{ $order->patient->full_name }}</span></div>
      <div class="cell"><span class="label">JHC ID</span><span class="value">{{ $order->patient->jhc_id }}</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Age / Gender</span><span class="value">{{ $order->patient->age }} / {{ $order->patient->gender }}</span></div>
      @if($order->admission)
      <div class="cell"><span class="label">IPD No.</span><span class="value">{{ $order->admission->ipd_no }}</span></div>
      @endif
    </div>
  </div>
</div>

@if($order->clinical_indication)
<div class="section">
  <div class="section-title">Clinical Indication</div>
  <div class="text-box">{{ $order->clinical_indication }}</div>
</div>
@endif

@if($order->findings)
<div class="section">
  <div class="section-title">Findings</div>
  <div class="text-box">{{ $order->findings }}</div>
</div>
@endif

@if($order->impression)
<div class="section">
  <div class="section-title">Impression</div>
  <div class="text-box">{{ $order->impression }}</div>
</div>
@endif

<div class="sig-section">
  <div class="sig-cell">
    <div class="sig-line">Patient Acknowledgement</div>
  </div>
  <div class="sig-cell">
    <div class="sig-line">{{ $order->reported_by ?? 'Radiologist' }}<br>Signature &amp; Stamp</div>
  </div>
</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer">
  Reported on {{ $order->updated_at->format('d M Y, h:i A') }} | {{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}
</div>
@endif
</body>
</html>
