@php
    /** @var \App\Models\ClinicalRule|null $rule */
    $rule = $rule ?? null;
    $mt   = old('match_type', $rule->match_type ?? 'keyword');
    $metricLabels = [
        'bmi' => 'BMI', 'bp_systolic' => 'BP systolic', 'bp_diastolic' => 'BP diastolic',
        'blood_glucose' => 'Blood glucose', 'temperature' => 'Temperature (°F)', 'spo2' => 'SpO₂ (%)',
        'pulse' => 'Pulse', 'respiratory_rate' => 'Resp. rate', 'weight' => 'Weight (kg)', 'height' => 'Height (cm)',
    ];
    $compLabels = ['gte' => '≥', 'lte' => '≤', 'gt' => '>', 'lt' => '<', 'eq' => '='];
@endphp
<div class="rule-fields" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div class="form-group" style="grid-column:1/-1;margin:0">
        <label class="form-label" style="font-size:11px">Name</label>
        <input type="text" name="name" maxlength="140" required value="{{ old('name', $rule->name ?? '') }}"
               class="form-control" style="font-size:12px" placeholder="e.g. Overweight (BMI ≥ 25)">
    </div>

    <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:11px">Category</label>
        <select name="category" class="form-control" style="font-size:12px">
            @foreach(\App\Models\ClinicalRule::CATEGORIES as $k => $m)
                <option value="{{ $k }}" @selected(old('category', $rule->category ?? 'symptom') === $k)>{{ $m['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:11px">Severity</label>
        <select name="severity" class="form-control" style="font-size:12px">
            @foreach(\App\Models\ClinicalRule::SEVERITIES as $s)
                <option value="{{ $s }}" @selected(old('severity', $rule->severity ?? 'info') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group" style="grid-column:1/-1;margin:0">
        <label class="form-label" style="font-size:11px">Trigger when…</label>
        <select name="match_type" class="form-control rule-match-type" style="font-size:12px" onchange="ruleToggleFields(this)">
            <option value="keyword"     @selected($mt === 'keyword')>Text mentions a keyword (complaint / history / exam)</option>
            <option value="vital"       @selected($mt === 'vital')>A vital crosses a threshold</option>
            <option value="demographic" @selected($mt === 'demographic')>Patient age crosses a threshold</option>
        </select>
    </div>

    {{-- Vital / demographic numeric condition --}}
    <div class="rule-num-field form-group" style="margin:0">
        <label class="form-label" style="font-size:11px">Metric</label>
        <select name="metric" class="form-control" style="font-size:12px">
            <optgroup label="Vitals">
                @foreach($metricLabels as $k => $lbl)
                    <option value="{{ $k }}" @selected(old('metric', $rule->metric ?? 'bmi') === $k)>{{ $lbl }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Demographic">
                <option value="age" @selected(old('metric', $rule->metric ?? '') === 'age')>Age (years)</option>
            </optgroup>
        </select>
    </div>
    <div class="rule-num-field form-group" style="margin:0">
        <label class="form-label" style="font-size:11px">Is</label>
        <div style="display:flex;gap:6px">
            <select name="comparator" class="form-control" style="font-size:12px;width:70px">
                @foreach($compLabels as $k => $lbl)
                    <option value="{{ $k }}" @selected(old('comparator', $rule->comparator ?? 'gte') === $k)>{{ $lbl }}</option>
                @endforeach
            </select>
            <input type="number" step="0.1" name="value_num" value="{{ old('value_num', $rule->value_num ?? '') }}"
                   class="form-control" style="font-size:12px" placeholder="value">
        </div>
    </div>

    {{-- Keyword condition --}}
    <div class="rule-kw-field form-group" style="grid-column:1/-1;margin:0">
        <label class="form-label" style="font-size:11px">Keywords <span style="color:var(--sc-muted);font-weight:400">(comma-separated; any match triggers)</span></label>
        <input type="text" name="keywords" maxlength="500" value="{{ old('keywords', $rule->keywords ?? '') }}"
               class="form-control" style="font-size:12px" placeholder="chest pain, chest tightness, retrosternal">
    </div>
    <div class="rule-kw-field form-group" style="grid-column:1/-1;margin:0">
        <label class="form-label" style="font-size:11px">Scan fields <span style="color:var(--sc-muted);font-weight:400">(optional; blank = complaint, HPI, PMH, social hx, exam)</span></label>
        <input type="text" name="keyword_fields" maxlength="255" value="{{ old('keyword_fields', $rule->keyword_fields ?? '') }}"
               class="form-control" style="font-size:12px" placeholder="chief_complaint, social_history">
    </div>

    {{-- Optional scoping --}}
    <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:11px">Only for gender <span style="color:var(--sc-muted);font-weight:400">(optional)</span></label>
        <select name="gender" class="form-control" style="font-size:12px">
            <option value="">Any</option>
            <option value="male"   @selected(old('gender', $rule->gender ?? '') === 'male')>Male</option>
            <option value="female" @selected(old('gender', $rule->gender ?? '') === 'female')>Female</option>
        </select>
    </div>
    <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:11px">Only ages <span style="color:var(--sc-muted);font-weight:400">(optional)</span></label>
        <div style="display:flex;gap:6px;align-items:center">
            <input type="number" name="age_min" min="0" max="150" value="{{ old('age_min', $rule->age_min ?? '') }}" class="form-control" style="font-size:12px" placeholder="min">
            <span style="color:var(--sc-muted)">–</span>
            <input type="number" name="age_max" min="0" max="150" value="{{ old('age_max', $rule->age_max ?? '') }}" class="form-control" style="font-size:12px" placeholder="max">
        </div>
    </div>

    <div class="form-group" style="grid-column:1/-1;margin:0">
        <label class="form-label" style="font-size:11px">Suggestion shown to the doctor</label>
        <textarea name="suggestion" maxlength="600" required rows="2" class="form-control" style="font-size:12px"
                  placeholder="What to check / look for / order…">{{ old('suggestion', $rule->suggestion ?? '') }}</textarea>
    </div>

    <label style="grid-column:1/-1;display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;margin:0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->is_active ?? true))> Active
    </label>
</div>
