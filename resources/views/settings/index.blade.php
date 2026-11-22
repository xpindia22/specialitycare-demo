{{-- FILE: resources/views/settings/index.blade.php --}}
@extends('layouts.app')
@section('title','Settings')
@section('page-title','Settings')
@section('content')
@php
    $activeSettingsTab = request('tab', session('settings_tab', 'clinic'));
    $activeSettingsTab = in_array($activeSettingsTab, ['clinic', 'ipd', 'services', 'lab', 'panels', 'allergies', 'chips', 'rules', 'ai_assistant'], true) ? $activeSettingsTab : 'clinic';
    $s = fn($key, $default='') => $settings->get($key)?->value ?? $default;
@endphp
<div class="page-header"><div class="page-header-left"><h1>System Settings</h1></div></div>

<div class="tab-container" id="settings-tabs">
<div class="tab-nav">
    <button class="tab-btn {{ $activeSettingsTab === 'clinic' ? 'active' : '' }}" data-tab="clinic"><i class="fa-solid fa-hospital"></i> Hospital Info</button>
    <button class="tab-btn {{ $activeSettingsTab === 'ipd' ? 'active' : '' }}" data-tab="ipd"><i class="fa-solid fa-bed-pulse"></i> IPD &amp; Ward Rates</button>
    <button class="tab-btn {{ $activeSettingsTab === 'services' ? 'active' : '' }}" data-tab="services"><i class="fa-solid fa-list-check"></i> Service Charges</button>
    <button class="tab-btn {{ $activeSettingsTab === 'lab' ? 'active' : '' }}" data-tab="lab"><i class="fa-solid fa-flask"></i> Lab Test Pricing</button>
    <button class="tab-btn {{ $activeSettingsTab === 'panels' ? 'active' : '' }}" data-tab="panels"><i class="fa-solid fa-layer-group"></i> Panel Pricing</button>
    <button class="tab-btn {{ $activeSettingsTab === 'allergies' ? 'active' : '' }}" data-tab="allergies"><i class="fa-solid fa-triangle-exclamation"></i> Allergy List</button>
    <button class="tab-btn {{ $activeSettingsTab === 'chips' ? 'active' : '' }}" data-tab="chips"><i class="fa-solid fa-tags"></i> Clinical Chips</button>
    <button class="tab-btn {{ $activeSettingsTab === 'rules' ? 'active' : '' }}" data-tab="rules"><i class="fa-solid fa-bell"></i> Clinical Rules</button>
    <button class="tab-btn {{ $activeSettingsTab === 'ai_assistant' ? 'active' : '' }}" data-tab="ai_assistant"><i class="fa-solid fa-robot"></i> AI Assistant</button>
</div>

<!-- Hospital / Clinic Settings -->
<div id="tab-clinic" class="tab-pane {{ $activeSettingsTab === 'clinic' ? 'active' : '' }}">
    <form method="POST" action="{{ route('settings.update') }}">
    @csrf
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-hospital"></i> Hospital Information</div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">Hospital / Clinic Name</label><input type="text" name="settings[clinic_name]" class="form-control" value="{{ $s('clinic_name') }}"></div>
                <div class="form-group"><label class="form-label">Registration No.</label><input type="text" name="settings[clinic_reg_no]" class="form-control" value="{{ $s('clinic_reg_no') }}"></div>
                <div class="form-group"><label class="form-label">Phone</label><input type="text" name="settings[clinic_phone]" class="form-control" value="{{ $s('clinic_phone') }}"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="settings[clinic_email]" class="form-control" value="{{ $s('clinic_email') }}"></div>
                <div class="form-group"><label class="form-label">Website</label><input type="text" name="settings[clinic_website]" class="form-control" value="{{ $s('clinic_website') }}"></div>
                <div class="form-group"><label class="form-label">GST Number</label><input type="text" name="settings[clinic_gst]" class="form-control" value="{{ $s('clinic_gst') }}"></div>
            </div>
            <div class="form-group"><label class="form-label">Address</label><textarea name="settings[clinic_address]" class="form-control" rows="2">{{ $s('clinic_address') }}</textarea></div>
            <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">Patient ID Prefix</label><input type="text" name="settings[jhc_id_prefix]" class="form-control" value="{{ $s('jhc_id_prefix','JHC') }}" maxlength="10"></div>
                <div class="form-group"><label class="form-label">Patient ID Digits</label><input type="number" name="settings[jhc_id_padding]" class="form-control" value="{{ $s('jhc_id_padding','5') }}" min="3" max="10"></div>
                <div class="form-group"><label class="form-label">Currency Symbol</label><input type="text" name="settings[currency]" class="form-control" value="{{ $s('currency','₹') }}" maxlength="5"></div>
                <div class="form-group"><label class="form-label">Bill Footer Text</label><input type="text" name="settings[footer_text]" class="form-control" value="{{ $s('footer_text') }}"></div>
                <div class="form-group"><label class="form-label">Recent Patients in Sidebar</label><input type="number" name="settings[recent_patients_count]" class="form-control" value="{{ $s('recent_patients_count','5') }}" min="1" max="20"><small class="text-muted">How many recently-viewed patients to keep in the left menu (1–20).</small></div>
            </div>
        </div>
    </div>

    {{-- Referral incentive defaults --}}
    <div class="card" style="margin-top:16px">
        <div class="card-header"><i class="fa-solid fa-hand-holding-dollar"></i> Referral Incentive Defaults</div>
        <div class="card-body">
            <p class="text-muted text-small" style="margin-bottom:14px">
                Flat incentive auto-credited to a patient's referring doctor (as set on the Referred By dropdown / patient registration). ECG and Echo pay out on every referred study; the consultation rate pays only once — on the patient's very first-ever visit, system-wide. These are separate from each referral source's own %/fixed rate on the Referrals page, which still governs OPD/IPD bill-based incentives.
            </p>
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">ECG Referral (₹)</label><input type="number" name="settings[referral_rate_ecg]" class="form-control" value="{{ $s('referral_rate_ecg','100') }}" min="0" step="0.01"></div>
                <div class="form-group"><label class="form-label">Echo Referral (₹)</label><input type="number" name="settings[referral_rate_echo]" class="form-control" value="{{ $s('referral_rate_echo','500') }}" min="0" step="0.01"></div>
                <div class="form-group"><label class="form-label">First Consultation Referral (₹)</label><input type="number" name="settings[referral_rate_consultation_first]" class="form-control" value="{{ $s('referral_rate_consultation_first','100') }}" min="0" step="0.01"></div>
            </div>
        </div>
    </div>

    {{-- Print header / footer --}}
    <div class="card" style="margin-top:16px">
        <div class="card-header"><i class="fa-solid fa-print"></i> Print Header & Footer</div>
        <div class="card-body">
            <p class="text-muted text-small" style="margin-bottom:14px">
                Controls the clinic header (doctor + clinic info at top) and footer (services + timings at bottom) shown on all clinical printouts — echo reports, lab reports, prescriptions, bills, etc. Uncheck to print without either band.
            </p>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:8px">
                        <input type="hidden" name="settings[print_header_enabled]" value="0">
                        <input type="checkbox" name="settings[print_header_enabled]" value="1" {{ $s('print_header_enabled','1') === '0' ? '' : 'checked' }}>
                        Show header on printouts
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:8px">
                        <input type="hidden" name="settings[print_footer_enabled]" value="0">
                        <input type="checkbox" name="settings[print_footer_enabled]" value="1" {{ $s('print_footer_enabled','1') === '0' ? '' : 'checked' }}>
                        Show footer on printouts
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Footer — Services line</label>
                <input type="text" name="settings[print_footer_services]" class="form-control" value="{{ $s('print_footer_services','Medical Consultations · Lab · Pharmacy · ECG · ECHO · Day Care') }}">
                <small class="text-muted">Plain text. Use · or • as separators.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Footer — Timings line</label>
                <input type="text" name="settings[print_footer_timings]" class="form-control" value="{{ $s('print_footer_timings','Mon – Sat: 8:00 AM – 1:00 PM & 4:00 PM – 6:30 PM   ·   Sundays Closed') }}">
            </div>
        </div>
    </div>

    {{-- Backup notification frequency (per channel) --}}
    @php
        // Migrate from the old single key if the per-channel keys aren't set yet.
        $legacyFreq = $s('backup_notify_frequency', 'every');
        $waFreq  = $s('backup_notify_whatsapp_frequency', $legacyFreq);
        $emFreq  = $s('backup_notify_email_frequency', $legacyFreq);
        $waHours = (int) $s('backup_notify_whatsapp_custom_hours', 4);
        $emHours = (int) $s('backup_notify_email_custom_hours', 4);
        $freqOpts = [
            'every'        => 'Every hour (on every backup run)',
            '2h'           => 'Every 2 hours',
            '3h'           => 'Every 3 hours',
            '6h'           => 'Every 6 hours',
            'daily'        => 'Once a day',
            'custom'       => 'Custom — every N hours…',
            'failure_only' => 'Only on FAILURE (silent on success)',
            'never'        => 'Never (turn this channel off)',
        ];
    @endphp
    <div class="card" style="margin-top:16px">
        <div class="card-header"><i class="fa-solid fa-database"></i> Backup Notifications</div>
        <div class="card-body">
            <p class="text-muted text-small" style="margin-bottom:6px">
                <strong>This only controls notifications — it does not change the backups.</strong> The database is backed up <strong>every hour</strong> by the server cron no matter what you choose here; this just decides how often each channel pings you with an "all&nbsp;clear".
            </p>
            <p class="text-muted text-small" style="margin-bottom:14px">
                WhatsApp and email throttle <strong>independently</strong> (e.g. email hourly, WhatsApp every 6 hours). <strong>Failures always notify</strong> on a channel unless you set that channel to "Never".
            </p>

            {{-- WhatsApp channel --}}
            <div class="form-group" style="max-width:480px">
                <label class="form-label"><i class="fa-brands fa-whatsapp" style="color:#25d366"></i> WhatsApp — notify me</label>
                <select name="settings[backup_notify_whatsapp_frequency]" class="form-control"
                        onchange="document.getElementById('waCustomRow').style.display = this.value==='custom' ? 'block' : 'none'">
                    @foreach($freqOpts as $v => $lbl)
                        <option value="{{ $v }}" {{ $waFreq==$v ? 'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="waCustomRow" style="max-width:480px;display:{{ $waFreq=='custom' ? 'block':'none' }}">
                <label class="form-label">WhatsApp custom interval (hours)</label>
                <input type="number" name="settings[backup_notify_whatsapp_custom_hours]" class="form-control"
                       min="1" max="168" step="1" value="{{ $waHours ?: 4 }}" style="max-width:160px">
            </div>

            <hr style="margin:14px 0;border:none;border-top:1px solid var(--sc-border)">

            {{-- Email channel --}}
            <div class="form-group" style="max-width:480px">
                <label class="form-label"><i class="fa-solid fa-envelope" style="color:#6366f1"></i> Email — notify me</label>
                <select name="settings[backup_notify_email_frequency]" class="form-control"
                        onchange="document.getElementById('emCustomRow').style.display = this.value==='custom' ? 'block' : 'none'">
                    @foreach($freqOpts as $v => $lbl)
                        <option value="{{ $v }}" {{ $emFreq==$v ? 'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="emCustomRow" style="max-width:480px;display:{{ $emFreq=='custom' ? 'block':'none' }}">
                <label class="form-label">Email custom interval (hours)</label>
                <input type="number" name="settings[backup_notify_email_custom_hours]" class="form-control"
                       min="1" max="168" step="1" value="{{ $emHours ?: 4 }}" style="max-width:160px">
            </div>

            <div class="text-small text-muted" style="margin-top:6px">Custom = one success ping roughly every N hours (1–168). Failures still notify immediately.</div>
        </div>
    </div>

    {{-- Shift hours --}}
    <div class="card" style="margin-top:16px">
        <div class="card-header"><i class="fa-solid fa-business-time"></i> Shift Hours</div>
        <div class="card-body">
            <p class="text-muted text-small" style="margin-bottom:14px">Default times shown on the duty roster. Change here at any time — the roster will reflect the new times immediately. For one-off shifts that don't fit a named slot, use "Custom hours" when adding the duty.</p>
            <div style="display:grid;grid-template-columns:120px 1fr 1fr;gap:10px 14px;align-items:center;max-width:560px">
                <div style="font-weight:600;color:#f59e0b"><i class="fa-solid fa-sun"></i> Morning</div>
                <input type="time" name="settings[shift_morning_start]" class="form-control" value="{{ $s('shift_morning_start','08:00') }}" required>
                <input type="time" name="settings[shift_morning_end]"   class="form-control" value="{{ $s('shift_morning_end','13:00') }}"   required>

                <div style="font-weight:600;color:#6366f1"><i class="fa-solid fa-cloud-sun"></i> Evening</div>
                <input type="time" name="settings[shift_evening_start]" class="form-control" value="{{ $s('shift_evening_start','16:00') }}" required>
                <input type="time" name="settings[shift_evening_end]"   class="form-control" value="{{ $s('shift_evening_end','18:30') }}"   required>

                <div style="font-weight:600;color:#334155"><i class="fa-solid fa-moon"></i> Night</div>
                <input type="time" name="settings[shift_night_start]" class="form-control" value="{{ $s('shift_night_start','22:00') }}" required>
                <input type="time" name="settings[shift_night_end]"   class="form-control" value="{{ $s('shift_night_end','06:00') }}"   required>
            </div>
            <div class="text-small text-muted" style="margin-top:10px">If end time is earlier than start, it's treated as the next day (e.g. Night 22:00–06:00).</div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Settings</button></div>
    </div>
    </form>
</div>

<!-- IPD & Ward Rates -->
<div id="tab-ipd" class="tab-pane {{ $activeSettingsTab === 'ipd' ? 'active' : '' }}">
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-bed-pulse"></i> Room Rent Rates (per night)</div>
        <div class="card-body" style="padding-bottom:0">
            <p class="text-muted text-small" style="margin-bottom:12px">These rates are charged automatically each night via the scheduled room-charge command. Click the pencil icon to edit.</p>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Ward Type</th><th class="text-right">Rate per Night (₹)</th><th style="width:120px">Status</th><th style="width:80px"></th></tr></thead>
                <tbody>
                @foreach($roomRents as $rr)
                <tr>
                    <td class="fw-600"><i class="fa-solid fa-bed" style="color:var(--sc-teal);margin-right:6px"></i>{{ $rr->name }}</td>
                    <td class="text-right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                            <span id="rr-price-{{ $rr->id }}" class="fw-600 mono">₹{{ number_format($rr->price,2) }}</span>
                            <input type="number" id="rr-input-{{ $rr->id }}" style="display:none;width:110px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:13px;text-align:right" value="{{ $rr->price }}" step="0.01" min="0">
                            <button class="btn btn-sm btn-outline" onclick="editRR({{ $rr->id }})" id="rr-edit-{{ $rr->id }}"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="saveRR({{ $rr->id }})" id="rr-save-{{ $rr->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td><span class="badge {{ $rr->is_active ? 'badge-green' : 'badge-gray' }}">{{ $rr->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-clock"></i> Room Charge Automation</div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--sc-muted);margin-bottom:14px">Room charges are generated automatically at <strong>00:05</strong> each night for all admitted patients. You can also trigger it manually.</p>
            <form method="POST" action="{{ route('ipd.room-charges') }}">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm('Generate room charges for today? This is idempotent — running it again will not create duplicates.')">
                    <i class="fa-solid fa-bolt"></i> Generate Room Charges Now
                </button>
            </form>
            <div class="mt-3" style="background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:6px;padding:12px;font-size:12px;font-family:monospace;color:var(--sc-muted)">
                Crontab entry (run as www-data or server user):<br>
                <code style="color:var(--sc-navy)">* * * * * php {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1</code>
            </div>
        </div>
    </div>
</div>

<!-- Service Charges -->
<div id="tab-services" class="tab-pane {{ $activeSettingsTab === 'services' ? 'active' : '' }}">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-list-check"></i> Service Charges</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Service Name</th><th>Category</th><th class="text-right">Price (₹)</th><th>Active</th></tr></thead>
                <tbody>
                @foreach($services as $svc)
                <tr>
                    <td class="fw-600">{{ $svc->name }}</td>
                    <td><span class="badge badge-navy">{{ $svc->category }}</span></td>
                    <td class="text-right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                            <span id="price-{{ $svc->id }}" class="fw-600">₹{{ number_format($svc->price, 2) }}</span>
                            <input type="number" id="price-input-{{ $svc->id }}" style="display:none;width:100px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ $svc->price }}" step="0.01" min="0">
                            <button class="btn btn-sm btn-outline" onclick="editPrice({{ $svc->id }})" id="edit-btn-{{ $svc->id }}"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="savePrice({{ $svc->id }})" id="save-btn-{{ $svc->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td><span class="badge {{ $svc->is_active ? 'badge-green' : 'badge-gray' }}">{{ $svc->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Lab Test Pricing -->
<div id="tab-lab" class="tab-pane {{ $activeSettingsTab === 'lab' ? 'active' : '' }}">
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-flask-vial"></i> Lab Test Prices
            <div class="card-actions">
                <button type="button" class="btn btn-sm btn-outline" onclick="openProfitBulk('lab')"><i class="fa-solid fa-sack-dollar"></i> Set Profit in Bulk</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="openTestModal()"><i class="fa-solid fa-plus"></i> Add Test</button>
            </div>
        </div>
        <div class="card-body" style="padding-bottom:0">
            <div class="form-group" style="max-width:420px">
                <label class="form-label">Search Tests</label>
                <input type="text" id="lab-test-search" class="form-control" placeholder="Search by test, code, category..." oninput="filterLabTests()">
            </div>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Test Name</th><th>Category</th><th>Code</th><th>Normal Range / Unit</th><th class="text-right">Price (₹)</th><th class="text-right" title="What the clinic actually earns out of the price">Profit (₹)</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                @foreach($labTests as $test)
                <tr class="lab-test-row {{ $test->is_active ? '' : 'is-inactive' }}" data-search="{{ strtolower($test->name.' '.$test->short_code.' '.$test->category?->name) }}">
                    <td class="fw-600">{{ $test->name }}</td>
                    <td>{{ $test->category?->name }}</td>
                    <td class="mono text-muted">{{ $test->short_code ?: '—' }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <span id="lab-normal-{{ $test->id }}">
                                @if($test->normal_range || $test->unit)<span class="fw-600">{{ $test->normal_range ?: '—' }}</span> <span class="text-muted">{{ $test->unit }}</span>@else<span class="text-muted">—</span>@endif
                            </span>
                            <input type="text" id="lab-normal-range-{{ $test->id }}" style="display:none;width:130px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ e($test->normal_range) }}" placeholder="e.g. 13-17">
                            <input type="text" id="lab-normal-unit-{{ $test->id }}" style="display:none;width:70px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ e($test->unit) }}" placeholder="unit">
                            <button class="btn btn-sm btn-outline" onclick="editLabNormal({{ $test->id }})" id="lab-normal-edit-btn-{{ $test->id }}" title="Edit normal range"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="saveLabNormal({{ $test->id }})" id="lab-normal-save-btn-{{ $test->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td class="text-right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                            <span id="lab-price-{{ $test->id }}" class="fw-600">₹{{ number_format($test->price, 2) }}</span>
                            <input type="number" id="lab-price-input-{{ $test->id }}" style="display:none;width:100px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ $test->price }}" step="0.01" min="0">
                            <button class="btn btn-sm btn-outline" onclick="editLabPrice({{ $test->id }})" id="lab-edit-btn-{{ $test->id }}"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="saveLabPrice({{ $test->id }})" id="lab-save-btn-{{ $test->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td class="text-right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                            <span id="lab-profit-{{ $test->id }}" class="fw-600" data-price="{{ $test->price }}">
                                @if($test->profit !== null)₹{{ number_format($test->profit, 2) }} <span class="text-small text-muted" style="font-weight:400">{{ $test->price > 0 ? round($test->profit / $test->price * 100) : 0 }}%</span>@else<span class="text-muted" style="font-weight:400">—</span>@endif
                            </span>
                            <input type="number" id="lab-profit-input-{{ $test->id }}" style="display:none;width:100px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ $test->profit }}" step="0.01" min="0" placeholder="₹ profit">
                            <button class="btn btn-sm btn-outline" onclick="editLabProfit({{ $test->id }})" id="lab-profit-edit-btn-{{ $test->id }}" title="Edit profit"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="saveLabProfit({{ $test->id }})" id="lab-profit-save-btn-{{ $test->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td><span class="badge {{ $test->is_active ? 'badge-green' : 'badge-gray' }}">{{ $test->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-right">
                        <button type="button" class="btn btn-sm btn-outline"
                            onclick="openTestModal(this)"
                            data-id="{{ $test->id }}"
                            data-name="{{ e($test->name) }}"
                            data-code="{{ e($test->short_code) }}"
                            data-category="{{ $test->lab_category_id }}"
                            data-sample="{{ e($test->sample_type) }}"
                            data-price="{{ $test->price }}"
                            data-profit="{{ $test->profit }}"
                            data-turnaround="{{ $test->turnaround_hours }}"
                            data-unit="{{ e($test->unit) }}"
                            data-normal="{{ e($test->normal_range) }}"
                            data-description="{{ e($test->description) }}"
                            data-instructions="{{ e($test->instructions) }}">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <form method="POST" action="{{ route('lab.tests.toggle-post', $test) }}" style="display:inline">
                            @csrf
                            <input type="hidden" name="settings_tab" value="lab">
                            <button type="submit" class="btn btn-sm {{ $test->is_active ? 'btn-outline' : 'btn-success' }}" title="{{ $test->is_active ? 'Hide from individual-test ordering (stays in panels)' : 'Make orderable again' }}">
                                <i class="fa-solid fa-{{ $test->is_active ? 'eye-slash' : 'eye' }}"></i> {{ $test->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('lab.tests.destroy', $test) }}" style="display:inline" onsubmit="return confirm('Delete test {{ addslashes($test->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="settings_tab" value="lab">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Panel Pricing -->
<div id="tab-panels" class="tab-pane {{ $activeSettingsTab === 'panels' ? 'active' : '' }}">
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-layer-group"></i> Blood & Lab Panel Prices
            <div class="card-actions">
                <button type="button" class="btn btn-sm btn-outline" onclick="openProfitBulk('panels')"><i class="fa-solid fa-sack-dollar"></i> Set Profit in Bulk</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="openPanelModal()"><i class="fa-solid fa-plus"></i> Add Panel</button>
            </div>
        </div>
        <div class="card-body" style="padding-bottom:0">
            <div class="form-group" style="max-width:420px">
                <label class="form-label">Search Panels</label>
                <input type="text" id="panel-search" class="form-control" placeholder="Search by panel name, code, or test..." oninput="filterPanels()">
            </div>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Panel Name</th><th>Code</th><th>Included Tests</th><th class="text-right">Price (₹)</th><th class="text-right" title="What the clinic actually earns out of the price">Profit (₹)</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                @foreach($labPanels as $panel)
                <tr class="panel-row" data-search="{{ strtolower($panel->name.' '.($panel->short_code ?? '').' '.$panel->tests->pluck('name')->implode(' ')) }}">
                    <td class="fw-600">{{ $panel->name }}</td>
                    <td class="mono text-muted">{{ $panel->short_code ?: '—' }}</td>
                    <td class="text-muted text-small">{{ $panel->tests->pluck('name')->join(' · ') ?: '—' }}</td>
                    <td class="text-right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                            <span id="panel-price-{{ $panel->id }}" class="fw-600">₹{{ number_format($panel->price, 2) }}</span>
                            <input type="number" id="panel-price-input-{{ $panel->id }}" style="display:none;width:100px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ $panel->price }}" step="0.01" min="0">
                            <button class="btn btn-sm btn-outline" onclick="editPanelPrice({{ $panel->id }})" id="panel-edit-btn-{{ $panel->id }}"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="savePanelPrice({{ $panel->id }})" id="panel-save-btn-{{ $panel->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td class="text-right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                            <span id="panel-profit-{{ $panel->id }}" class="fw-600">
                                @if($panel->profit !== null)₹{{ number_format($panel->profit, 2) }} <span class="text-small text-muted" style="font-weight:400">{{ $panel->price > 0 ? round($panel->profit / $panel->price * 100) : 0 }}%</span>@else<span class="text-muted" style="font-weight:400">—</span>@endif
                            </span>
                            <input type="number" id="panel-profit-input-{{ $panel->id }}" style="display:none;width:100px;padding:4px 8px;border:1px solid var(--sc-border);border-radius:5px;font-size:12px" value="{{ $panel->profit }}" step="0.01" min="0" placeholder="₹ profit">
                            <button class="btn btn-sm btn-outline" onclick="editPanelProfit({{ $panel->id }})" id="panel-profit-edit-btn-{{ $panel->id }}" title="Edit profit"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="savePanelProfit({{ $panel->id }})" id="panel-profit-save-btn-{{ $panel->id }}" style="display:none"><i class="fa-solid fa-save"></i></button>
                        </div>
                    </td>
                    <td class="text-right">
                        <button type="button" class="btn btn-sm btn-outline"
                            onclick="openPanelModal(this)"
                            data-id="{{ $panel->id }}"
                            data-name="{{ e($panel->name) }}"
                            data-code="{{ e($panel->short_code) }}"
                            data-description="{{ e($panel->description) }}"
                            data-turnaround="{{ $panel->turnaround_hours }}"
                            data-price="{{ $panel->price }}"
                            data-profit="{{ $panel->profit }}"
                            data-tests="{{ $panel->tests->pluck('id')->join(',') }}">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <form method="POST" action="{{ route('lab.panels.destroy', $panel) }}" style="display:inline" onsubmit="return confirm('Delete panel {{ addslashes($panel->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="settings_tab" value="panels">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Profit Modal -->
<div class="modal-overlay" id="profit-bulk-modal">
    <div class="modal-box">
        <div class="modal-header">
            <i class="fa-solid fa-sack-dollar" style="color:var(--sc-green)"></i>
            <h3>Set Profit in Bulk</h3>
            <button class="modal-close" onclick="document.getElementById('profit-bulk-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('lab.profit.bulk') }}" onsubmit="return confirmProfitBulk(this)">
            @csrf
            <input type="hidden" name="settings_tab" id="profit-bulk-tab" value="lab">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Apply to</label>
                    <label style="display:flex;gap:8px;align-items:center;margin:0 0 4px;font-weight:400"><input type="checkbox" name="apply_tests" value="1" checked> All individual tests</label>
                    <label style="display:flex;gap:8px;align-items:center;margin:0;font-weight:400"><input type="checkbox" name="apply_panels" value="1" checked> All panels</label>
                </div>
                <div class="form-group">
                    <label class="form-label">Only tests in category <span class="text-muted" style="font-weight:400">(optional; panels are not filtered)</span></label>
                    <select name="category_id" class="form-control">
                        <option value="">All categories</option>
                        @foreach($labCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Profit as</label>
                        <select name="mode" class="form-control" onchange="document.getElementById('profit-bulk-unit').textContent = this.value === 'percent' ? '% of each price' : '₹ per test / panel'">
                            <option value="percent">% of price</option>
                            <option value="fixed">Fixed ₹ amount</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Value <span class="text-muted" id="profit-bulk-unit" style="font-weight:400">% of each price</span></label>
                        <input type="number" name="value" class="form-control" step="0.01" min="0" required placeholder="e.g. 50">
                    </div>
                </div>
                <label style="display:flex;gap:8px;align-items:flex-start;margin:0;font-weight:400">
                    <input type="checkbox" name="past_orders" value="1" style="margin-top:3px">
                    <span>Also apply to past orders<br><span class="text-small text-muted">Recalculates profit on lab orders already placed (revenue reports for past dates will change). A fixed amount is capped at each price.</span></span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('profit-bulk-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Test Modal -->
<div class="modal-overlay" id="test-modal">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <i class="fa-solid fa-flask-vial" style="color:var(--sc-teal)"></i>
            <h3 id="test-modal-title">Add Individual Test</h3>
            <button class="modal-close" onclick="document.getElementById('test-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" id="test-form" action="{{ route('lab.tests.store') }}">
            @csrf
            <input type="hidden" name="_method" id="test-method" value="POST">
            <input type="hidden" name="settings_tab" value="lab">
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Test Name <span class="req">*</span></label>
                        <input type="text" name="name" id="test-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Short Code</label>
                        <input type="text" name="short_code" id="test-code" class="form-control" placeholder="e.g. CBC, LFT">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <select name="lab_category_id" id="test-category" class="form-control" required>
                            <option value="">Select category...</option>
                            @foreach($labCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sample Type</label>
                        <select name="sample_type" id="test-sample" class="form-control">
                            <option value="">Select...</option>
                            @foreach(['Blood','Urine','Stool','Sputum','Swab','Tissue','CSF','Other'] as $sample)
                                <option value="{{ $sample }}">{{ $sample }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₹) <span class="req">*</span></label>
                        <input type="number" name="price" id="test-price-field" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profit (₹)</label>
                        <input type="number" name="profit" id="test-profit-field" class="form-control" step="0.01" min="0" placeholder="Clinic's profit out of the price">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Turnaround (hours)</label>
                        <input type="number" name="turnaround_hours" id="test-turnaround" class="form-control" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Result Unit</label>
                        <input type="text" name="unit" id="test-unit" class="form-control" placeholder="mg/dL, %, g/L">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Normal Range</label>
                        <input type="text" name="normal_range" id="test-normal" class="form-control" placeholder="e.g. 70-110 mg/dL">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Method</label>
                    <textarea name="description" id="test-description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Instructions</label>
                    <input type="text" name="instructions" id="test-instructions" class="form-control" placeholder="e.g. Fasting required">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('test-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Add Test</button>
            </div>
        </form>
    </div>
</div>

<!-- Add / Edit Panel Modal -->
<div class="modal-overlay" id="panel-modal">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <i class="fa-solid fa-layer-group" style="color:var(--sc-teal)"></i>
            <h3 id="panel-modal-title">Add Panel</h3>
            <button class="modal-close" onclick="closePanelModal()">&times;</button>
        </div>
        <form method="POST" id="panel-form" action="{{ route('lab.panels.store') }}">
            @csrf
            <input type="hidden" name="_method" id="panel-method" value="POST">
            <input type="hidden" name="settings_tab" value="panels">
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Panel Name <span class="req">*</span></label>
                        <input type="text" name="name" id="panel-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Short Code</label>
                        <input type="text" name="short_code" id="panel-code" class="form-control" placeholder="e.g. CBC">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Turnaround (hours)</label>
                        <input type="number" name="turnaround_hours" id="panel-turnaround" class="form-control" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Panel Price (₹) <span class="req">*</span></label>
                        <input type="number" name="price" id="panel-price-field" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profit (₹)</label>
                        <input type="number" name="profit" id="panel-profit-field" class="form-control" step="0.01" min="0" placeholder="Clinic's profit out of the price">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="panel-description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Included Tests <span class="req">*</span></label>
                    <select name="test_ids[]" id="panel-tests" class="form-control select2" multiple required style="width:100%">
                        @foreach($activeLabTests as $test)
                            <option value="{{ $test->id }}" data-price="{{ $test->price }}">{{ $test->name }} (₹{{ number_format($test->price, 2) }})</option>
                        @endforeach
                    </select>
                    <div class="text-small text-muted" style="margin-top:6px">Selected test total: <strong id="panel-test-sum">₹0.00</strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="fillPanelSumPrice()"><i class="fa-solid fa-calculator"></i> Use Test Sum</button>
                <button type="button" class="btn btn-outline" onclick="closePanelModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Panel</button>
            </div>
        </form>
    </div>
</div>
</div>

{{-- ── TAB: ALLERGY LIST ── --}}
<div id="tab-allergies" class="tab-pane {{ $activeSettingsTab === 'allergies' ? 'active' : '' }}">

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #86efac;color:#14532d;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Add new allergen --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-plus-circle"></i> Add Allergen to List</div>
        <div class="card-body">
            <form method="POST" action="{{ route('allergens.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                @csrf
                <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                    <label class="form-label">Allergen Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Azithromycin" required maxlength="100" value="{{ old('name') }}">
                    @error('name')<div style="color:#ef4444;font-size:11px;margin-top:3px">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="min-width:180px;margin-bottom:0">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        @foreach(\App\Models\Allergen::CATEGORIES as $key => $cat)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-bottom:0"><i class="fa-solid fa-plus"></i> Add</button>
            </form>
        </div>
    </div>

    {{-- Existing allergens grouped by category --}}
    <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;align-items:start">
        @foreach(\App\Models\Allergen::CATEGORIES as $catKey => $catMeta)
        @php $items = $allergensByCategory->get($catKey, collect()); @endphp
        <div class="card">
            <div class="card-header" style="gap:8px">
                <i class="fa-solid {{ $catMeta['icon'] }}" style="color:{{ $catMeta['color'] }}"></i>
                {{ $catMeta['label'] }}
                <span class="badge badge-navy" style="margin-left:auto">{{ $items->count() }}</span>
            </div>
            <div class="card-body" style="padding:10px 12px">
                @forelse($items as $allergen)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-radius:6px;margin-bottom:4px;background:var(--sc-bg)">
                    <span style="font-size:13px;font-weight:500">{{ $allergen->name }}</span>
                    <form method="POST" action="{{ route('allergens.destroy', $allergen) }}" onsubmit="return confirm('Remove &quot;{{ addslashes($allergen->name) }}&quot; from the allergy list?')" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;cursor:pointer;color:#ef4444;padding:2px 6px;border-radius:4px;font-size:13px" title="Remove">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
                @empty
                <div style="color:var(--sc-muted);font-size:12px;padding:8px">No allergens in this category.</div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Clinical Quick-Pick Chips -->
<div id="tab-chips" class="tab-pane {{ $activeSettingsTab === 'chips' ? 'active' : '' }}">

    @if(session('success') && (session('settings_tab') === 'chips' || request('tab') === 'chips'))
    <div style="background:#f0fdf4;border:1px solid #86efac;color:#14532d;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if($errors->has('chip_text'))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first('chip_text') }}
    </div>
    @endif

    <div style="background:#f8fafc;border:1px solid var(--sc-border);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--sc-muted)">
        <i class="fa-solid fa-circle-info" style="color:var(--sc-teal)"></i>
        These are the one-click chips that appear above each clinical note field on the encounter page. Add the phrases your doctors use most often.
    </div>

    <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;align-items:start">
        @foreach(\App\Models\ClinicalChip::FIELDS as $fieldKey => $meta)
        @php $items = $clinicalChipsByField->get($fieldKey, collect()); @endphp
        <div class="card">
            <div class="card-header" style="gap:8px">
                <i class="fa-solid {{ $meta['icon'] }}" style="color:{{ $meta['color'] }}"></i>
                {{ $meta['label'] }}
                <span class="badge badge-navy" style="margin-left:auto">{{ $items->count() }}</span>
            </div>
            <div class="card-body" style="padding:10px 12px">
                @forelse($items as $chip)
                <div style="display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:6px;margin-bottom:4px;background:var(--sc-bg)">
                    <form method="POST" action="{{ route('clinical-chips.update', $chip) }}" style="margin:0;flex:1;display:flex;gap:6px;align-items:center">
                        @csrf @method('PUT')
                        <input type="text" name="text" value="{{ $chip->text }}" maxlength="200" required
                               class="form-control" style="font-size:12px;padding:4px 8px;flex:1;background:#fff">
                        <button type="submit" title="Save changes" style="background:none;border:none;cursor:pointer;color:var(--sc-teal);padding:2px 6px;border-radius:4px;font-size:13px">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('clinical-chips.destroy', $chip) }}" onsubmit="return confirm('Remove this chip?')" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit" title="Remove" style="background:none;border:none;cursor:pointer;color:#ef4444;padding:2px 6px;border-radius:4px;font-size:13px">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
                @empty
                <div style="color:var(--sc-muted);font-size:12px;padding:8px">No chips yet for this field.</div>
                @endforelse

                {{-- Add new chip --}}
                <form method="POST" action="{{ route('clinical-chips.store') }}" style="display:flex;gap:6px;margin-top:8px;padding-top:8px;border-top:1px dashed var(--sc-border)">
                    @csrf
                    <input type="hidden" name="field_key" value="{{ $fieldKey }}">
                    <input type="text" name="text" maxlength="200" required placeholder="Add new chip…"
                           class="form-control" style="font-size:12px;padding:4px 8px;flex:1">
                    <button type="submit" class="btn btn-primary" style="padding:4px 10px;font-size:12px">
                        <i class="fa-solid fa-plus"></i> Add
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Clinical Rules (reminders / CDS engine) --}}
<div id="tab-rules" class="tab-pane {{ $activeSettingsTab === 'rules' ? 'active' : '' }}">

    @if(session('success') && (session('settings_tab') === 'rules' || request('tab') === 'rules'))
    <div style="background:#f0fdf4;border:1px solid #86efac;color:#14532d;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if($errors->any() && (session('settings_tab') === 'rules' || old('suggestion') !== null))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
        <i class="fa-solid fa-circle-exclamation"></i>
        @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
    </div>
    @endif

    <div style="background:#f8fafc;border:1px solid var(--sc-border);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--sc-muted)">
        <i class="fa-solid fa-circle-info" style="color:var(--sc-teal)"></i>
        These rules power the <strong>Clinical Reminders</strong> popup on the encounter screen. As the doctor charts vitals
        and complaints, matching rules surface advisory checks (screenings, vaccines, work-up). Advisory only — nothing is auto-ordered.
    </div>

    {{-- Add new rule --}}
    <div class="card" style="margin-bottom:16px">
        <div class="card-header" style="cursor:pointer" onclick="document.getElementById('rule-add-body').classList.toggle('hidden')">
            <i class="fa-solid fa-plus-circle" style="color:var(--sc-teal)"></i> Add a new rule
            <i class="fa-solid fa-chevron-down" style="margin-left:auto;font-size:11px"></i>
        </div>
        <div id="rule-add-body" class="card-body {{ $errors->any() && !request()->routeIs('*update*') ? '' : 'hidden' }}" style="padding:14px">
            <form method="POST" action="{{ route('clinical-rules.store') }}">
                @csrf
                @include('settings._rule_fields', ['rule' => null])
                <div style="margin-top:12px;text-align:right">
                    <button type="submit" class="btn btn-primary" style="font-size:12px"><i class="fa-solid fa-plus"></i> Add rule</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Existing rules --}}
    @php
        $compSym = ['gte' => '≥', 'lte' => '≤', 'gt' => '>', 'lt' => '<', 'eq' => '='];
        $sevColor = ['urgent' => '#ef4444', 'warning' => '#f59e0b', 'info' => '#3b82f6'];
    @endphp
    <div class="card">
        <div class="card-header" style="gap:8px">
            <i class="fa-solid fa-bell"></i> Rules
            <span class="badge badge-navy" style="margin-left:auto">{{ $clinicalRules->count() }}</span>
        </div>
        <div class="card-body" style="padding:8px 10px">
            <div style="position:relative;margin-bottom:10px">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sc-muted);font-size:12px"></i>
                <input type="text" id="cds-rule-search" oninput="cdsFilterRules(this.value)" autocomplete="off"
                       placeholder="Search rules by name, keyword, category or suggestion…"
                       class="form-control" style="font-size:12px;padding-left:30px">
                <span id="cds-rule-search-clear" onclick="document.getElementById('cds-rule-search').value='';cdsFilterRules('')"
                      style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--sc-muted);font-size:13px"><i class="fa-solid fa-xmark"></i></span>
            </div>
            <div id="cds-rule-search-empty" style="display:none;color:var(--sc-muted);font-size:12px;padding:10px;text-align:center">No rules match your search.</div>
            @forelse($clinicalRules as $rule)
            @php $cm = $rule->categoryMeta(); @endphp
            <div class="cds-rule-row" data-search="{{ \Illuminate\Support\Str::lower($rule->name.' '.$rule->suggestion.' '.$rule->keywords.' '.$cm['label'].' '.$rule->category.' '.$rule->match_type.' '.$rule->metric) }}" style="border:1px solid var(--sc-border);border-radius:8px;margin-bottom:8px;overflow:hidden;{{ $rule->is_active ? '' : 'opacity:.55' }}">
                <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--sc-bg)">
                    <span title="{{ ucfirst($rule->severity) }}" style="width:9px;height:9px;border-radius:50%;flex:none;background:{{ $sevColor[$rule->severity] ?? '#3b82f6' }}"></span>
                    <i class="fa-solid {{ $cm['icon'] }}" style="color:{{ $cm['color'] }};flex:none"></i>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;color:var(--sc-navy)">{{ $rule->name }}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">
                            @if($rule->match_type === 'keyword')
                                mentions: <em>{{ \Illuminate\Support\Str::limit($rule->keywords, 60) }}</em>
                            @elseif($rule->match_type === 'demographic')
                                age {{ $compSym[$rule->comparator] ?? $rule->comparator }} {{ rtrim(rtrim((string)$rule->value_num,'0'),'.') }}
                            @else
                                {{ $rule->metric }} {{ $compSym[$rule->comparator] ?? $rule->comparator }} {{ rtrim(rtrim((string)$rule->value_num,'0'),'.') }}
                            @endif
                            @if($rule->gender) · {{ $rule->gender }} @endif
                            @if($rule->age_min !== null || $rule->age_max !== null) · age {{ $rule->age_min ?? '0' }}–{{ $rule->age_max ?? '∞' }} @endif
                            · <span style="color:{{ $cm['color'] }}">{{ $cm['label'] }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('clinical-rules.toggle', $rule) }}" style="margin:0">
                        @csrf
                        <button type="submit" title="{{ $rule->is_active ? 'Disable' : 'Enable' }}" style="background:none;border:none;cursor:pointer;color:{{ $rule->is_active ? '#22c55e' : 'var(--sc-muted)' }};font-size:16px">
                            <i class="fa-solid {{ $rule->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                    </form>
                    <button type="button" title="Edit" onclick="document.getElementById('rule-edit-{{ $rule->id }}').classList.toggle('hidden')"
                            style="background:none;border:none;cursor:pointer;color:var(--sc-teal);font-size:13px;padding:2px 6px"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="{{ route('clinical-rules.destroy', $rule) }}" onsubmit="return confirm('Remove rule “{{ $rule->name }}”?')" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit" title="Remove" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:13px;padding:2px 6px"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                </div>
                <div style="font-size:12px;color:var(--sc-navy);padding:8px 12px 10px 33px">
                    <i class="fa-solid fa-arrow-right" style="color:var(--sc-muted);font-size:10px"></i> {{ $rule->suggestion }}
                </div>
                <div id="rule-edit-{{ $rule->id }}" class="hidden" style="padding:12px;border-top:1px solid var(--sc-border);background:#fff">
                    <form method="POST" action="{{ route('clinical-rules.update', $rule) }}">
                        @csrf @method('PUT')
                        @include('settings._rule_fields', ['rule' => $rule])
                        <div style="margin-top:12px;text-align:right">
                            <button type="submit" class="btn btn-primary" style="font-size:12px"><i class="fa-solid fa-check"></i> Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
            @empty
            <div style="color:var(--sc-muted);font-size:12px;padding:12px">No rules yet. Add one above.</div>
            @endforelse
        </div>
    </div>

    <script>
    // Show only the condition fields relevant to the chosen trigger type, per form.
    function ruleToggleFields(sel) {
        var form = sel.closest('form');
        if (!form) return;
        var isKw = sel.value === 'keyword';
        form.querySelectorAll('.rule-kw-field').forEach(function (el) { el.style.display = isKw ? '' : 'none'; });
        form.querySelectorAll('.rule-num-field').forEach(function (el) { el.style.display = isKw ? 'none' : ''; });
    }
    document.querySelectorAll('#tab-rules .rule-match-type').forEach(function (s) { ruleToggleFields(s); });

    // Live client-side filter of the rules list.
    function cdsFilterRules(q) {
        q = (q || '').trim().toLowerCase();
        var terms = q.split(/\s+/).filter(Boolean);
        var rows = document.querySelectorAll('#tab-rules .cds-rule-row');
        var shown = 0;
        rows.forEach(function (row) {
            var hay = row.getAttribute('data-search') || '';
            var ok = terms.every(function (t) { return hay.indexOf(t) !== -1; });
            row.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        var empty = document.getElementById('cds-rule-search-empty');
        if (empty) empty.style.display = (rows.length && shown === 0) ? '' : 'none';
        var clr = document.getElementById('cds-rule-search-clear');
        if (clr) clr.style.display = q ? '' : 'none';
    }
    </script>
</div>

{{-- AI Assistant --}}
<div id="tab-ai_assistant" class="tab-pane {{ $activeSettingsTab === 'ai_assistant' ? 'active' : '' }}">
    @php $aiKeyConfigured = trim((string) setting('ai_assistant_api_key', '')) !== ''; @endphp
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-robot"></i> AI Clinical Assistant</div>
        <div class="card-body">
            <div style="background:#f8fafc;border:1px solid var(--sc-border);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--sc-muted)">
                <i class="fa-solid fa-circle-info" style="color:var(--sc-teal)"></i>
                The AI Clinical Assistant calls Anthropic's Claude API directly, billed to <strong>your own</strong> API key — not the vendor's. Get a key at
                <a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>, then paste it below. This install also needs the <strong>AI Clinical Assistant</strong> module licensed to appear on the encounter screen (see Admin → Modules).
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="settings_tab" value="ai_assistant">
                <div class="form-group" style="max-width:480px">
                    <label class="form-label">
                        Anthropic API Key
                        @if($aiKeyConfigured)<span class="badge badge-green" style="margin-left:6px">Configured</span>@else<span class="badge badge-gray" style="margin-left:6px">Not set</span>@endif
                    </label>
                    <input type="password" name="settings[ai_assistant_api_key]" class="form-control" autocomplete="off"
                           placeholder="{{ $aiKeyConfigured ? 'Leave blank to keep the current key' : 'sk-ant-...' }}">
                    <small class="text-muted">Never displayed once saved. Leave blank when saving to keep the existing key unchanged; enter a new value to replace it.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Key</button>
            </form>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
// Tab switching for settings
document.querySelectorAll('#settings-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#settings-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('#settings-tabs .tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

function editRR(id) {
    document.getElementById('rr-price-' + id).style.display = 'none';
    document.getElementById('rr-input-' + id).style.display = 'inline-block';
    document.getElementById('rr-edit-' + id).style.display = 'none';
    document.getElementById('rr-save-' + id).style.display = 'inline-flex';
}
function saveRR(id) {
    const val = document.getElementById('rr-input-' + id).value;
    $.post(`{{ url('settings/service') }}/${id}/price`, { price: val }, function(res) {
        if (res.success) {
            document.getElementById('rr-price-' + id).textContent = '₹' + parseFloat(val).toFixed(2);
            document.getElementById('rr-price-' + id).style.display = '';
            document.getElementById('rr-input-' + id).style.display = 'none';
            document.getElementById('rr-edit-' + id).style.display = '';
            document.getElementById('rr-save-' + id).style.display = 'none';
        }
    }).fail(function(xhr) { alert('Save failed: ' + xhr.status); });
}
function editPrice(id) {
    document.getElementById('price-' + id).style.display = 'none';
    document.getElementById('price-input-' + id).style.display = 'inline-block';
    document.getElementById('edit-btn-' + id).style.display = 'none';
    document.getElementById('save-btn-' + id).style.display = 'inline-flex';
}
function savePrice(id) {
    const val = document.getElementById('price-input-' + id).value;
    $.post(`{{ url('settings/service') }}/${id}/price`, { price: val }, function(res) {
        if (res.success) {
            document.getElementById('price-' + id).textContent = '₹' + parseFloat(val).toFixed(2);
            document.getElementById('price-' + id).style.display = '';
            document.getElementById('price-input-' + id).style.display = 'none';
            document.getElementById('edit-btn-' + id).style.display = '';
            document.getElementById('save-btn-' + id).style.display = 'none';
        }
    }).fail(function(xhr) { alert('Service price save failed: ' + xhr.status + ' ' + xhr.responseText); });
}
function editLabPrice(id) {
    document.getElementById('lab-price-' + id).style.display = 'none';
    document.getElementById('lab-price-input-' + id).style.display = 'inline-block';
    document.getElementById('lab-edit-btn-' + id).style.display = 'none';
    document.getElementById('lab-save-btn-' + id).style.display = 'inline-flex';
}
function saveLabPrice(id) {
    const val = document.getElementById('lab-price-input-' + id).value;
    $.post(`{{ url('lab/tests') }}/${id}/price`, { price: val }, function(res) {
        if (res.success) {
            document.getElementById('lab-price-' + id).textContent = '₹' + parseFloat(val).toFixed(2);
            labProfitRender(id, document.getElementById('lab-profit-input-' + id).value, val);
            document.getElementById('lab-price-' + id).style.display = '';
            document.getElementById('lab-price-input-' + id).style.display = 'none';
            document.getElementById('lab-edit-btn-' + id).style.display = '';
            document.getElementById('lab-save-btn-' + id).style.display = 'none';
        }
    }).fail(function(xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || ('Lab price save failed: ' + xhr.status + ' ' + xhr.responseText)); });
}
function openProfitBulk(tab) {
    document.getElementById('profit-bulk-tab').value = tab;
    document.getElementById('profit-bulk-modal').classList.add('open');
}
function confirmProfitBulk(form) {
    const what = [form.apply_tests.checked ? 'tests' : null, form.apply_panels.checked ? 'panels' : null].filter(Boolean).join(' and ');
    if (!what) { alert('Choose tests, panels, or both.'); return false; }
    const how = form.mode.value === 'percent' ? form.value.value + '% of price' : '₹' + form.value.value;
    return confirm('Set profit to ' + how + ' for all ' + what + '?' + (form.past_orders.checked ? '\n\nPast orders will be recalculated too.' : '') + '\n\nThis replaces the profits currently set.');
}
function labProfitRender(id, profit, price) {
    const el = document.getElementById('lab-profit-' + id);
    price = parseFloat(price); el.dataset.price = price;
    if (profit === '' || profit === null || isNaN(parseFloat(profit))) { el.innerHTML = '<span class="text-muted" style="font-weight:400">—</span>'; return; }
    profit = parseFloat(profit);
    const pct = price > 0 ? Math.round(profit / price * 100) : 0;
    el.innerHTML = '₹' + profit.toFixed(2) + ' <span class="text-small text-muted" style="font-weight:400">' + pct + '%</span>';
}
function editLabProfit(id) {
    document.getElementById('lab-profit-' + id).style.display = 'none';
    document.getElementById('lab-profit-input-' + id).style.display = 'inline-block';
    document.getElementById('lab-profit-edit-btn-' + id).style.display = 'none';
    document.getElementById('lab-profit-save-btn-' + id).style.display = 'inline-flex';
}
function saveLabProfit(id) {
    const val = document.getElementById('lab-profit-input-' + id).value;
    $.post(`{{ url('lab/tests') }}/${id}/profit`, { profit: val }, function(res) {
        if (res.success) {
            labProfitRender(id, res.profit, res.price);
            document.getElementById('lab-profit-' + id).style.display = '';
            document.getElementById('lab-profit-input-' + id).style.display = 'none';
            document.getElementById('lab-profit-edit-btn-' + id).style.display = '';
            document.getElementById('lab-profit-save-btn-' + id).style.display = 'none';
        }
    }).fail(function(xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || ('Lab profit save failed: ' + xhr.status)); });
}
function editLabNormal(id) {
    document.getElementById('lab-normal-' + id).style.display = 'none';
    document.getElementById('lab-normal-range-' + id).style.display = 'inline-block';
    document.getElementById('lab-normal-unit-' + id).style.display = 'inline-block';
    document.getElementById('lab-normal-edit-btn-' + id).style.display = 'none';
    document.getElementById('lab-normal-save-btn-' + id).style.display = 'inline-flex';
}
function saveLabNormal(id) {
    const range = document.getElementById('lab-normal-range-' + id).value;
    const unit  = document.getElementById('lab-normal-unit-' + id).value;
    $.post(`{{ url('lab/tests') }}/${id}/normal`, { normal_range: range, unit: unit }, function(res) {
        if (res.success) {
            const span = document.getElementById('lab-normal-' + id);
            span.innerHTML = (range || unit)
                ? '<span class="fw-600">' + (range ? $('<div>').text(range).html() : '—') + '</span> <span class="text-muted">' + $('<div>').text(unit).html() + '</span>'
                : '<span class="text-muted">—</span>';
            span.style.display = '';
            document.getElementById('lab-normal-range-' + id).style.display = 'none';
            document.getElementById('lab-normal-unit-' + id).style.display = 'none';
            document.getElementById('lab-normal-edit-btn-' + id).style.display = '';
            document.getElementById('lab-normal-save-btn-' + id).style.display = 'none';
        }
    }).fail(function(xhr) { alert('Normal range save failed: ' + xhr.status + ' ' + xhr.responseText); });
}
function filterLabTests() {
    const q = (document.getElementById('lab-test-search').value || '').toLowerCase().trim();
    document.querySelectorAll('.lab-test-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
}
function filterPanels() {
    const q = (document.getElementById('panel-search').value || '').toLowerCase().trim();
    document.querySelectorAll('.panel-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
}
function openTestModal(button = null) {
    const form = document.getElementById('test-form');
    form.reset();
    form.action = '{{ route("lab.tests.store") }}';
    document.getElementById('test-method').value = 'POST';
    document.getElementById('test-modal-title').textContent = 'Add Individual Test';

    if (button) {
        form.action = `{{ url('lab/tests') }}/${button.dataset.id}`;
        document.getElementById('test-method').value = 'PUT';
        document.getElementById('test-modal-title').textContent = 'Edit Individual Test';
        document.getElementById('test-name').value = button.dataset.name || '';
        document.getElementById('test-code').value = button.dataset.code || '';
        document.getElementById('test-category').value = button.dataset.category || '';
        document.getElementById('test-sample').value = button.dataset.sample || '';
        document.getElementById('test-price-field').value = button.dataset.price || 0;
        document.getElementById('test-profit-field').value = button.dataset.profit || '';
        document.getElementById('test-turnaround').value = button.dataset.turnaround || '';
        document.getElementById('test-unit').value = button.dataset.unit || '';
        document.getElementById('test-normal').value = button.dataset.normal || '';
        document.getElementById('test-description').value = button.dataset.description || '';
        document.getElementById('test-instructions').value = button.dataset.instructions || '';
    }

    document.getElementById('test-modal').classList.add('open');
}
function editPanelPrice(id) {
    document.getElementById('panel-price-' + id).style.display = 'none';
    document.getElementById('panel-price-input-' + id).style.display = 'inline-block';
    document.getElementById('panel-edit-btn-' + id).style.display = 'none';
    document.getElementById('panel-save-btn-' + id).style.display = 'inline-flex';
}
function savePanelPrice(id) {
    const val = document.getElementById('panel-price-input-' + id).value;
    $.ajax({
        url: `{{ url('lab/panels') }}/${id}/price`,
        method: 'PATCH',
        data: { price: val },
        success: function(res) {
            if (res.success) {
                document.getElementById('panel-price-' + id).textContent = '₹' + parseFloat(val).toFixed(2);
                panelProfitRender(id, document.getElementById('panel-profit-input-' + id).value, val);
                document.getElementById('panel-price-' + id).style.display = '';
                document.getElementById('panel-price-input-' + id).style.display = 'none';
                document.getElementById('panel-edit-btn-' + id).style.display = '';
                document.getElementById('panel-save-btn-' + id).style.display = 'none';
            }
        },
        error: function(xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || ('Panel price save failed: ' + xhr.status + ' ' + xhr.responseText)); }
    });
}
function panelProfitRender(id, profit, price) {
    const el = document.getElementById('panel-profit-' + id);
    price = parseFloat(price);
    if (profit === '' || profit === null || isNaN(parseFloat(profit))) { el.innerHTML = '<span class="text-muted" style="font-weight:400">—</span>'; return; }
    profit = parseFloat(profit);
    const pct = price > 0 ? Math.round(profit / price * 100) : 0;
    el.innerHTML = '₹' + profit.toFixed(2) + ' <span class="text-small text-muted" style="font-weight:400">' + pct + '%</span>';
}
function editPanelProfit(id) {
    document.getElementById('panel-profit-' + id).style.display = 'none';
    document.getElementById('panel-profit-input-' + id).style.display = 'inline-block';
    document.getElementById('panel-profit-edit-btn-' + id).style.display = 'none';
    document.getElementById('panel-profit-save-btn-' + id).style.display = 'inline-flex';
}
function savePanelProfit(id) {
    const val = document.getElementById('panel-profit-input-' + id).value;
    $.post(`{{ url('lab/panels') }}/${id}/profit`, { profit: val }, function(res) {
        if (res.success) {
            panelProfitRender(id, res.profit, res.price);
            document.getElementById('panel-profit-' + id).style.display = '';
            document.getElementById('panel-profit-input-' + id).style.display = 'none';
            document.getElementById('panel-profit-edit-btn-' + id).style.display = '';
            document.getElementById('panel-profit-save-btn-' + id).style.display = 'none';
        }
    }).fail(function(xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || ('Panel profit save failed: ' + xhr.status)); });
}

function updatePanelTestSum() {
    let sum = 0;
    Array.from(document.getElementById('panel-tests').selectedOptions).forEach(opt => {
        sum += parseFloat(opt.dataset.price || 0);
    });
    document.getElementById('panel-test-sum').textContent = '₹' + sum.toFixed(2);
    window._settingsPanelTestSum = sum;
}

function fillPanelSumPrice() {
    document.getElementById('panel-price-field').value = (window._settingsPanelTestSum || 0).toFixed(2);
}

function openPanelModal(button = null) {
    const form = document.getElementById('panel-form');
    form.reset();
    document.getElementById('panel-method').value = 'POST';
    form.action = '{{ route("lab.panels.store") }}';
    document.getElementById('panel-modal-title').textContent = 'Add Panel';
    $('#panel-tests').val(null).trigger('change');

    if (button) {
        document.getElementById('panel-modal-title').textContent = 'Edit Panel';
        document.getElementById('panel-method').value = 'PUT';
        form.action = `{{ url('lab/panels') }}/${button.dataset.id}`;
        document.getElementById('panel-name').value = button.dataset.name || '';
        document.getElementById('panel-code').value = button.dataset.code || '';
        document.getElementById('panel-description').value = button.dataset.description || '';
        document.getElementById('panel-turnaround').value = button.dataset.turnaround || '';
        document.getElementById('panel-price-field').value = button.dataset.price || 0;
        document.getElementById('panel-profit-field').value = button.dataset.profit || '';
        const ids = (button.dataset.tests || '').split(',').filter(Boolean);
        $('#panel-tests').val(ids).trigger('change');
    }

    document.getElementById('panel-modal').classList.add('open');
}

function closePanelModal() {
    document.getElementById('panel-modal').classList.remove('open');
}

$(document).ready(function() {
    $('#panel-tests').on('change', updatePanelTestSum);
});
</script>
@endpush
