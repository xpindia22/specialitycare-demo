<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vaccination Card — {{ $patient->full_name }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; font-size:13px; color:#1a2942; background:#fff; max-width:800px; margin:0 auto; padding:24px 28px; }
.header { display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1a2942;padding-bottom:12px;margin-bottom:14px; }
.clinic { font-size:20px;font-weight:800;color:#1a2942 }
.title { font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#0ab4b4 }
.meta { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;font-size:12px; }
.meta strong { display:block;font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748b;margin-bottom:1px;font-weight:600 }
table { width:100%;border-collapse:collapse;margin-top:6px;font-size:12px; }
th,td { border-bottom:1px solid #e2e8f0;padding:7px 10px;text-align:left;vertical-align:top }
th { background:#f8fafc;color:#475569;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.3px }
.footer { margin-top:18px;font-size:11px;color:#64748b;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px }
.no-print { text-align:center;margin-bottom:12px;padding:10px;background:#f0f4f8;border-radius:8px }
.btn { background:#1a2942;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:600 }
.btn-grey { background:#e2e8f0;color:#1a2942;margin-left:8px }
@media print { .no-print { display:none } body { padding:14px 18px } }
</style>
</head>
<body>
<div class="no-print">
    <button class="btn" onclick="window.print()">&#128424; Print</button>
    <button class="btn btn-grey" onclick="window.close()">Close</button>
</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="header">
    <div>
        <div class="clinic">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">{{ setting('clinic_address', '') }}</div>
    </div>
    <div style="text-align:right">
        <div class="title">Vaccination Record</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">Issued {{ now()->format('d M Y') }}</div>
    </div>
</div>
@endif

<div class="meta">
    <div><strong>Name</strong>{{ $patient->full_name }}</div>
    <div><strong>JHC ID</strong>{{ $patient->jhc_id }}</div>
    <div><strong>Sex / Age</strong>{{ $patient->gender ?? '—' }} / {{ $patient->age }}</div>
    <div><strong>Date of Birth</strong>{{ optional($patient->date_of_birth)->format('d M Y') ?: '—' }}</div>
    <div><strong>Phone</strong>{{ $patient->phone ?: '—' }}</div>
    <div><strong>Mother's Name</strong>{{ $patient->mother_name ?? '—' }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>Vaccine</th>
            <th>Dose</th>
            <th>Date Given</th>
            <th>Batch</th>
            <th>Site</th>
            <th>Given By</th>
        </tr>
    </thead>
    <tbody>
    @forelse($given as $v)
    <tr>
        <td><strong>{{ $v->vaccine_name }}</strong></td>
        <td>{{ $v->dose_label }}</td>
        <td>{{ $v->given_date->format('d M Y') }}</td>
        <td class="mono">{{ $v->batch_number ?: '—' }}</td>
        <td>{{ $v->site ?: '—' }}</td>
        <td>{{ optional($v->givenBy)->name ?: '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px">No vaccinations recorded yet.</td></tr>
    @endforelse
    </tbody>
</table>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer">This card is a record of vaccinations administered at {{ setting('clinic_name', '') }}. Bring it to every visit.</div>
@endif
</body>
</html>
