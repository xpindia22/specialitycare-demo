<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Discharge Summary — {{ $admission->ipd_no }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1a2942; line-height: 1.5; padding: 20mm; }
  .header { text-align: center; border-bottom: 2px solid #0f1f3d; padding-bottom: 12px; margin-bottom: 16px; }
  .clinic-name { font-size: 20px; font-weight: 700; color: #0f1f3d; }
  .clinic-sub  { font-size: 12px; color: #555; margin-top: 4px; }
  .doc-title   { font-size: 16px; font-weight: 700; text-align: center; margin: 14px 0; color: #0f1f3d; text-transform: uppercase; letter-spacing: 1px; }
  .section     { margin-bottom: 14px; }
  .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #0f1f3d; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 8px; }
  .grid { display: table; width: 100%; }
  .row  { display: table-row; }
  .cell { display: table-cell; padding: 4px 8px 4px 0; width: 50%; vertical-align: top; }
  .label { font-weight: 700; color: #555; font-size: 10px; text-transform: uppercase; display: block; }
  .value { font-size: 12px; }
  .badge-mlc { display: inline-block; background: #dc2626; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-left: 8px; }
  .summary-box { background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; padding: 10px; white-space: pre-wrap; line-height: 1.6; }
  .charges-table { width: 100%; border-collapse: collapse; font-size: 11px; }
  .charges-table th { background: #0f1f3d; color: #fff; padding: 6px 8px; text-align: left; }
  .charges-table td { padding: 5px 8px; border-bottom: 1px solid #eee; }
  .total-row td { font-weight: 700; background: #f0f4f8; }
  .signature-section { margin-top: 30px; display: table; width: 100%; }
  .sig-cell { display: table-cell; text-align: center; width: 50%; padding: 10px; }
  .sig-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 6px; font-size: 11px; color: #555; }
  .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
  .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 80px; opacity: .04; color: #0f1f3d; font-weight: 900; z-index: -1; white-space: nowrap; }
</style>
</head>
<body>
<div class="watermark">DISCHARGE SUMMARY</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="header">
  <div class="clinic-name">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
  <div class="clinic-sub">{{ setting('clinic_address', '') }} | {{ setting('clinic_phone', '') }}</div>
</div>
@endif

<div class="doc-title">Discharge Summary
  @if($admission->is_mlc) <span class="badge-mlc">MLC</span> @endif
</div>

<div class="section">
  <div class="section-title">Patient Information</div>
  <div class="grid">
    <div class="row">
      <div class="cell"><span class="label">Patient Name</span><span class="value">{{ $admission->patient->full_name }}</span></div>
      <div class="cell"><span class="label">JHC ID</span><span class="value">{{ $admission->patient->jhc_id }}</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Age / Gender</span><span class="value">{{ $admission->patient->age }} / {{ $admission->patient->gender }}</span></div>
      <div class="cell"><span class="label">Blood Group</span><span class="value">{{ $admission->patient->blood_group ?: 'N/A' }}</span></div>
    </div>
    <div class="cell"><span class="label">Address</span><span class="value">{{ $admission->patient->address }}</span></div>
  </div>
</div>

<div class="section">
  <div class="section-title">Admission Details</div>
  <div class="grid">
    <div class="row">
      <div class="cell"><span class="label">IPD Number</span><span class="value">{{ $admission->ipd_no }}</span></div>
      <div class="cell"><span class="label">Admission Type</span><span class="value">{{ \App\Models\Admission::ADMISSION_TYPES[$admission->admission_type] ?? $admission->admission_type }}</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Admission Date</span><span class="value">{{ $admission->admission_date->format('d M Y, h:i A') }}</span></div>
      <div class="cell"><span class="label">Discharge Date</span><span class="value">{{ $admission->discharge_date?->format('d M Y, h:i A') ?? 'N/A' }}</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Ward / Bed</span><span class="value">{{ $admission->ward->name }} — Bed {{ $admission->bed->bed_number }}</span></div>
      <div class="cell"><span class="label">Length of Stay</span><span class="value">{{ $admission->days_admitted }} day(s)</span></div>
    </div>
    <div class="row">
      <div class="cell"><span class="label">Attending Doctor</span><span class="value">Dr. {{ $admission->admittingDoctor->full_name }}</span></div>
      <div class="cell"><span class="label">Discharge Condition</span><span class="value">{{ \App\Models\Admission::DISCHARGE_CONDITIONS[$admission->discharge_condition] ?? ($admission->discharge_condition ?: 'N/A') }}</span></div>
    </div>
  </div>
</div>

@if($admission->provisional_diagnosis)
<div class="section">
  <div class="section-title">Diagnosis</div>
  <div class="summary-box">{{ $admission->provisional_diagnosis }}</div>
</div>
@endif

@if($admission->discharge_notes)
<div class="section">
  <div class="section-title">Discharge Notes</div>
  <div class="summary-box">{{ $admission->discharge_notes }}</div>
</div>
@endif

@if($admission->discharge_summary)
<div class="section">
  <div class="section-title">Discharge Summary</div>
  <div class="summary-box">{{ $admission->discharge_summary }}</div>
</div>
@endif

@if($admission->prescriptions && $admission->prescriptions->count())
<div class="section">
  <div class="section-title">Discharge Medications</div>
  <table class="charges-table">
    <thead><tr><th>Drug</th><th>Dose</th><th>Route</th><th>Frequency</th><th>Duration</th></tr></thead>
    <tbody>
    @foreach($admission->prescriptions->where('is_active', true) as $rx)
    <tr>
      <td>{{ $rx->drug_name }}</td>
      <td>{{ $rx->dose }}</td>
      <td>{{ $rx->route }}</td>
      <td>{{ $rx->frequency }}</td>
      <td>{{ $rx->end_date ? $rx->start_date->diffInDays($rx->end_date).' days' : 'Ongoing' }}</td>
    </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif

@if($admission->charges && $admission->charges->count())
<div class="section">
  <div class="section-title">Charges Summary</div>
  <table class="charges-table">
    <thead><tr><th>Category</th><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
    @foreach($admission->charges as $charge)
    <tr>
      <td>{{ \App\Models\IpdCharge::CATEGORIES[$charge->category]['label'] ?? $charge->category }}</td>
      <td>{{ $charge->description }}</td>
      <td style="text-align:right">&#8377;{{ number_format($charge->amount, 2) }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-row">
      <td colspan="2">Total</td>
      <td style="text-align:right">&#8377;{{ number_format($admission->charges->sum('amount'), 2) }}</td>
    </tr>
    </tfoot>
  </table>
</div>
@endif

<div class="signature-section">
  <div class="sig-cell">
    <div class="sig-line">Patient / Attendant Signature</div>
  </div>
  <div class="sig-cell">
    <div class="sig-line">Dr. {{ $admission->admittingDoctor->full_name }}<br>{{ $admission->admittingDoctor->specialization }}</div>
  </div>
</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer">
  Generated on {{ now()->format('d M Y, h:i A') }} | {{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}
</div>
@endif
</body>
</html>
