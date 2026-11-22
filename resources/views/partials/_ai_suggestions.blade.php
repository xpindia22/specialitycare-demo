{{-- AI medicine-suggestion panel. Params: $patient, $focus ('diabetes'|'bp').
     Advisory decision support, admin/doctor only. --}}
@if(in_array(auth()->user()->role ?? '', ['admin','doctor']))
<style>
    .ai-sg-md h1,.ai-sg-md h2,.ai-sg-md h3{font-size:14px;margin:12px 0 4px;color:#1e3360;font-weight:700}
    .ai-sg-md ul{margin:4px 0 10px 18px} .ai-sg-md li{margin:2px 0}
    .ai-sg-md p{margin:5px 0;line-height:1.5} .ai-sg-md strong{color:#1e3360}
    .ai-sg-md em{color:#64748b}
    .ai-sg-note{font-size:11.5px;color:#94a3b8;margin-top:8px;line-height:1.5}
</style>
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-robot"></i> AI medicine suggestions
        <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">advisory · the doctor decides</span>
    </div>
    <div class="card-body">
        <button type="button" class="btn btn-primary btn-sm" id="ai-sg-btn-{{ $focus }}" onclick="aiSuggest('{{ $focus }}')">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Generate suggestions
        </button>
        <span class="text-muted text-small" style="margin-left:8px">Uses this patient's reports, current medicines, comorbidities &amp; allergies.</span>
        <div id="ai-sg-out-{{ $focus }}" style="display:none;margin-top:12px"></div>
    </div>
</div>
<script>
var AI_SG_URL  = @json(route('ai.suggest', $patient));
var AI_SG_CSRF = @json(csrf_token());
function aiSuggest(focus) {
    var btn = document.getElementById('ai-sg-btn-' + focus),
        out = document.getElementById('ai-sg-out-' + focus),
        old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Thinking…';
    out.style.display = 'block';
    out.innerHTML = '<span class="text-muted">Generating clinical suggestions…</span>';
    fetch(AI_SG_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': AI_SG_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ focus: focus })
    }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        if (!res.ok) {
            out.innerHTML = '<div class="alert alert-danger">' + (res.d.error || 'Request failed.') + '</div>';
        } else {
            out.innerHTML = '<div class="ai-sg-md">' + res.d.html + '</div>'
                + '<div class="ai-sg-note"><i class="fa-solid fa-circle-info"></i> AI-generated decision support — not a prescription. Verify against the patient and current guidelines before acting.</div>';
        }
      }).catch(function () { out.innerHTML = '<div class="alert alert-danger">Network error.</div>'; })
      .finally(function () { btn.disabled = false; btn.innerHTML = old; });
}
</script>
@endif
