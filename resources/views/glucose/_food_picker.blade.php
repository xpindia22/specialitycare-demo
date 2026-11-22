{{-- Food chip-picker for meal_notes.
     Params: $key, $label, $name, $foodExtras, $value, $pinned (pinned food values)
     A date/time picker sets when the food was eaten (auto-placed by time); the
     food dropdown is a custom panel with a pin/unpin icon per item. --}}
@php
    $pinnedList  = $pinned ?? [];
    $pinnedLower = array_map('strtolower', $pinnedList);
@endphp
<div class="form-group" style="margin:0">
    <label class="form-label">{{ $label ?? 'Food / Meals eaten' }}</label>
    <div id="gl-{{ $key }}-chips" class="gl-chips"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px">
        <div>
            <label style="font-size:11px;color:#64748b;display:block;margin-bottom:2px">🍽️ Time eaten</label>
            <input type="datetime-local" id="gl-{{ $key }}-dt" class="form-control"
                   value="{{ request('date') ? request('date').'T'.now()->format('H:i') : now()->format('Y-m-d\TH:i') }}">
        </div>
        <div>
            <label style="font-size:11px;color:#64748b;display:block;margin-bottom:2px">Add food</label>
            <div class="gl-combo" data-field="food_item">
                <button type="button" class="form-control gl-combo-btn" onclick="glCombo.toggle('{{ $key }}')">＋ Add food…<span style="opacity:.5">▾</span></button>
                <div class="gl-combo-panel" id="gl-{{ $key }}-panel">
                    <input type="text" class="gl-combo-search" placeholder="Search food…" oninput="glCombo.filter('{{ $key }}',this.value)">
                    @foreach($pinnedList as $o)
                        <div class="gl-combo-item" data-name="{{ strtolower($o) }}">
                            <button type="button" class="gl-pin pinned" title="Pinned — click to unpin" onclick="glCombo.pin(this,'food_item',@js($o))">📌</button>
                            <span onclick="glCombo.addFood('{{ $key }}',@js($o))">{{ $o }}</span>
                        </div>
                    @endforeach
                    @foreach(\App\Models\DietLog::FOODS as $grp => $items)
                        <div class="gl-combo-grp">{{ $grp }}</div>
                        @foreach($items as $food => $kcal)
                            @if(!in_array(strtolower($food), $pinnedLower))
                                <div class="gl-combo-item" data-name="{{ strtolower($food) }}">
                                    <button type="button" class="gl-pin" title="Pin to top" onclick="glCombo.pin(this,'food_item',@js($food))">📌</button>
                                    <span onclick="glCombo.addFood('{{ $key }}',@js($food))">{{ $food }} <span style="color:#94a3b8;font-size:11px">{{ $kcal }} kcal</span></span>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                    @if(!empty($foodExtras))
                        <div class="gl-combo-grp">Added</div>
                        @foreach($foodExtras as $o)
                            @if(!in_array(strtolower($o), $pinnedLower))
                                <div class="gl-combo-item" data-name="{{ strtolower($o) }}">
                                    <button type="button" class="gl-pin" title="Pin to top" onclick="glCombo.pin(this,'food_item',@js($o))">📌</button>
                                    <span onclick="glCombo.addFood('{{ $key }}',@js($o))">{{ $o }}</span>
                                </div>
                            @endif
                        @endforeach
                    @endif
                    <div class="gl-combo-item gl-combo-add" onclick="glCombo.addNewFood('{{ $key }}')">➕ Add custom food…</div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="{{ $name ?? $key }}" id="gl-{{ $key }}" value="{{ $value ?? '' }}">
</div>
