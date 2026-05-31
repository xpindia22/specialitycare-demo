<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OT Register {{ $from }} to {{ $to }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11px; padding: 15mm; }
  .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
  .clinic-name { font-size: 18px; font-weight: 700; }
  .doc-title { font-size: 14px; font-weight: 700; text-align: center; margin: 10px 0; text-transform: uppercase; }
  .period { text-align: center; font-size: 12px; color: #555; margin-bottom: 14px; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th { background: #0f1f3d; color: #fff; padding: 6px 6px; text-align: left; }
  td { padding: 5px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
  tr:nth-child(even) td { background: #f8f9fa; }
  .fw-700 { font-weight: 700; }
  .footer { margin-top: 16px; text-align: center; font-size: 10px; color: #888; }
</style>
</head>
<body onload="window.print()">
@if(setting('print_header_enabled','1') !== '0')
<div class="header">
  <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
</div>
@endif
<div class="doc-title">Operation Theater Register</div>
<div class="period">Period: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Op. No.</th>
      <th>Date</th>
      <th>Patient</th>
      <th>Procedure</th>
      <th>Surgeon</th>
      <th>Anesthesia</th>
      <th>Outcome</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
  @forelse($operations as $i => $op)
  <tr>
    <td>{{ $i+1 }}</td>
    <td class="fw-700">{{ $op->operation_no }}</td>
    <td>{{ $op->operation_date->format('d M Y') }}@if($op->start_time)<br>{{ \Carbon\Carbon::parse($op->start_time)->format('h:i A') }}@endif</td>
    <td class="fw-700">{{ $op->patient->full_name }}<br><span style="font-weight:400;color:#666">{{ $op->patient->jhc_id }}</span></td>
    <td>{{ $op->procedure_name }}</td>
    <td>Dr. {{ $op->surgeon->first_name }} {{ $op->surgeon->last_name }}</td>
    <td>{{ ucfirst($op->anesthesia_type) }}</td>
    <td>{{ \App\Models\Operation::OUTCOME_LABELS[$op->outcome] ?? $op->outcome }}</td>
    <td>{{ ucfirst(str_replace('_',' ',$op->status)) }}</td>
  </tr>
  @empty
  <tr><td colspan="9" style="text-align:center;padding:20px;color:#888">No operations in this period.</td></tr>
  @endforelse
  </tbody>
</table>

<div style="margin-top:12px;font-weight:700">Total Operations: {{ $operations->count() }}</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer">Printed on {{ now()->format('d M Y, h:i A') }} | {{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
@endif
</body>
</html>
