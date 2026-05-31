<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Certificate — {{ $patient->full_name }}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f0f4f8; }

  /* Form (hidden from print) */
  .cert-form { background: #fff; border-radius: 10px; padding: 20px; max-width: 700px; margin: 0 auto 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
  .cert-form h3 { margin-bottom: 16px; color: #0f1f3d; font-size: 16px; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
  .form-group { display: flex; flex-direction: column; gap: 4px; }
  label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
  select, input, textarea { border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-family: inherit; outline: none; }
  select:focus, input:focus, textarea:focus { border-color: #0ab4b4; box-shadow: 0 0 0 3px rgba(10,180,180,.12); }
  .btn-print { background: #0f1f3d; color: #fff; border: none; padding: 10px 24px; border-radius: 7px; font-size: 14px; font-weight: 700; cursor: pointer; display: block; margin-top: 12px; }
  .btn-print:hover { background: #1e3360; }

  /* Certificate */
  .cert-wrap { max-width: 700px; margin: 0 auto; }
  .cert { background: #fff; border: 3px double #0f1f3d; padding: 40px; border-radius: 4px; }
  .cert-header { text-align: center; border-bottom: 2px solid #0f1f3d; padding-bottom: 16px; margin-bottom: 24px; }
  .cert-header .clinic-name { font-size: 22px; font-weight: 700; color: #0f1f3d; }
  .cert-header .clinic-sub  { font-size: 12px; color: #666; }
  .cert-title   { font-size: 18px; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; color: #0f1f3d; }
  .cert-body    { font-size: 14px; line-height: 2; color: #1a2942; }
  .cert-body .blank { display: inline-block; min-width: 120px; border-bottom: 1.5px solid #333; text-align: center; font-weight: 700; color: #0f1f3d; padding: 0 8px; }
  .cert-footer  { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; }
  .cert-date    { font-size: 13px; }
  .cert-sig     { text-align: center; }
  .cert-sig .sig-line { border-top: 1.5px solid #333; padding-top: 6px; font-size: 12px; color: #555; }
  .cert-note    { margin-top: 20px; font-size: 11px; color: #888; text-align: center; border-top: 1px dashed #ddd; padding-top: 10px; }

  @media print {
    body { background: #fff; padding: 0; }
    .cert-form { display: none; }
    .cert { border: 3px double #000; box-shadow: none; }
    .cert-header .clinic-name { color: #000; }
  }
</style>
</head>
<body>

{{-- Form (hidden from print) --}}
<div class="cert-form">
  <h3>Medical Certificate — Customize Before Printing</h3>
  <div class="form-row">
    <div class="form-group">
      <label>Certificate Type</label>
      <select id="certType" onchange="updateCert()">
        <option value="fitness" {{ $type === 'fitness' ? 'selected' : '' }}>Fitness Certificate</option>
        <option value="leave" {{ $type === 'leave' ? 'selected' : '' }}>Medical Leave Certificate</option>
      </select>
    </div>
    <div class="form-group">
      <label>Doctor Name</label>
      <input type="text" id="doctorName" value="Dr. " oninput="updateCert()" placeholder="Dr. Full Name">
    </div>
  </div>
  <div class="form-row" id="fitnessPurpose">
    <div class="form-group" style="grid-column:1/3">
      <label>Fit / Unfit for (purpose)</label>
      <input type="text" id="fitPurpose" value="employment/duty" oninput="updateCert()" placeholder="e.g. employment, travel, sports...">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>From Date</label>
      <input type="date" id="fromDate" value="{{ today()->toDateString() }}" oninput="updateCert()">
    </div>
    <div class="form-group">
      <label>To Date / Rest Days</label>
      <input type="text" id="toDate" value="" oninput="updateCert()" placeholder="e.g. 7 days / 2026-05-10">
    </div>
  </div>
  <div class="form-group" style="margin-bottom:12px">
    <label>Additional Notes</label>
    <textarea id="addNotes" rows="2" oninput="updateCert()" placeholder="Any additional remarks..."></textarea>
  </div>
  <button class="btn-print" onclick="window.print()">Print Certificate</button>
</div>

{{-- Certificate output --}}
<div class="cert-wrap">
<div class="cert">
  @if(setting('print_header_enabled','1') !== '0')
  <div class="cert-header">
    <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
    <div class="clinic-sub">{{ setting('clinic_address', '') }}</div>
  </div>
  @endif

  <div class="cert-title" id="certTitleDisplay">Fitness Certificate</div>

  <div class="cert-body" id="certBodyDisplay">
    To Whom It May Concern,<br><br>
    This is to certify that <span class="blank">{{ $patient->full_name }}</span>,
    aged <span class="blank">{{ $patient->age }}</span>,
    residing at <span class="blank">{{ $patient->address }}</span>,
    has been medically examined and is found to be
    <span class="blank" id="fitUnfit">FIT</span>
    for <span class="blank" id="fitPurposeDisplay">employment/duty</span>.<br><br>
    <span id="leaveSection" style="display:none">
    The patient was under medical care from <span class="blank" id="leaveFrom">{{ today()->format('d M Y') }}</span>
    to <span class="blank" id="leaveTo">—</span> and is advised
    <span class="blank" id="restDays">—</span> days of rest.<br><br>
    </span>
    <span id="notesSection" style="display:none"><em id="noteDisplay"></em><br><br></span>
    This certificate is issued on the request of the patient for the purpose of record.
  </div>

  <div class="cert-footer">
    <div class="cert-date">
      Date: <strong id="certDateDisplay">{{ today()->format('d M Y') }}</strong>
    </div>
    <div class="cert-sig">
      <div class="sig-line" id="certDoctorDisplay">Dr. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
      <div style="font-size:11px;color:#888;margin-top:4px">Signature &amp; Seal</div>
    </div>
  </div>

  @if(setting('print_footer_enabled','1') !== '0')
  <div class="cert-note">
    {{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }} — Valid only with official seal
  </div>
  @endif
</div>
</div>

<script>
function updateCert() {
    const type     = document.getElementById('certType').value;
    const doctor   = document.getElementById('doctorName').value;
    const purpose  = document.getElementById('fitPurpose').value;
    const from     = document.getElementById('fromDate').value;
    const to       = document.getElementById('toDate').value;
    const notes    = document.getElementById('addNotes').value;

    // Title
    document.getElementById('certTitleDisplay').textContent =
        type === 'fitness' ? 'Fitness Certificate' : 'Medical Leave Certificate';

    // Body elements
    document.getElementById('fitPurposeDisplay').textContent = purpose || 'employment/duty';
    document.getElementById('certDateDisplay').textContent = from ? new Date(from).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}) : '{{ today()->format("d M Y") }}';
    document.getElementById('certDoctorDisplay').textContent = doctor || 'Signature & Seal';

    // Leave section
    if (type === 'leave') {
        document.getElementById('leaveSection').style.display = 'inline';
        document.getElementById('fitnessPurpose').style.display = 'none';
        if (from) document.getElementById('leaveFrom').textContent = new Date(from).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
        document.getElementById('leaveTo').textContent = to || '—';
        document.getElementById('restDays').textContent = to || '—';
    } else {
        document.getElementById('leaveSection').style.display = 'none';
        document.getElementById('fitnessPurpose').style.display = '';
    }

    // Notes
    if (notes) {
        document.getElementById('notesSection').style.display = 'inline';
        document.getElementById('noteDisplay').textContent = notes;
    } else {
        document.getElementById('notesSection').style.display = 'none';
    }
}
updateCert();
</script>
</body>
</html>
