{{-- Reusable chip/tag multi-picker for medicine / symptoms.
     Params: $key, $field (RxOption field), $label, $name, $options (grouped),
             $extras, $value, $pinned (pinned drug values — drug fields only).
     Drug fields get a custom dropdown with a pin/unpin icon per entry;
     symptom fields keep a plain <select>. --}}
@php
    $isMed = in_array($field, ['diabetes_med', 'bp_med']);
    $noun  = $isMed ? 'medicine' : 'symptom';
    $pinnedList  = $pinned ?? [];
    $pinnedLower = array_map('strtolower', $pinnedList);
@endphp

@once
<style>
    .gl-combo{position:relative}
    .gl-combo-btn{text-align:left;cursor:pointer;background:#fff;display:flex;justify-content:space-between;align-items:center}
    .gl-combo-panel{display:none;position:absolute;z-index:60;left:0;right:0;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.14);margin-top:3px;max-height:260px;overflow:auto;padding:5px}
    .gl-combo-search{width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:13px;margin-bottom:4px}
    .gl-combo-grp{font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.3px;padding:5px 6px 1px}
    .gl-combo-item{display:flex;align-items:center;gap:8px;padding:5px 6px;border-radius:6px;cursor:pointer}
    .gl-combo-item:hover{background:#f1f5f9}
    .gl-combo-item>span{flex:1;font-size:13px;color:#334155}
    .gl-pin{border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.3;padding:0 2px;line-height:1;transition:opacity .12s}
    .gl-pin.pinned,.gl-pin:hover{opacity:1}
    .gl-combo-add{color:#0369a1;font-weight:600}
</style>
<script>
window.glCombo = {
    toggle:function(key){
        var p=document.getElementById('gl-'+key+'-panel'); if(!p)return;
        var wasOpen=p.style.display==='block';
        document.querySelectorAll('.gl-combo-panel').forEach(function(x){x.style.display='none';});
        if(!wasOpen){ p.style.display='block'; var s=p.querySelector('.gl-combo-search'); if(s){s.value='';glCombo.filter(key,'');try{s.focus();}catch(e){}} }
    },
    filter:function(key,q){
        q=(q||'').toLowerCase();
        document.querySelectorAll('#gl-'+key+'-panel .gl-combo-item[data-name]').forEach(function(it){
            it.style.display=it.getAttribute('data-name').indexOf(q)>=0?'':'none';
        });
    },
    add:function(key,name){ glAdd(key,name); glCombo.toggle(key); },
    addNew:function(key,field){
        var nv=(prompt('Add a new drug (saved for next time):')||'').replace(/,/g,' ').trim();
        glCombo.toggle(key); if(!nv)return;
        glAdd(key,nv);
        fetch(GL_RXOPT_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:field,value:nv})}).catch(function(){});
    },
    addFood:function(key,name){           // food = ask qty + use the entry's time picker
        var q=glAskFoodQty(name); glCombo.toggle(key);
        if(q!==null) glAddFood(key,name,q,glFoodWhen(key));
    },
    addNewFood:function(key){
        var nv=(prompt('Add a new food (saved for next time):')||'').replace(/,/g,' ').trim();
        glCombo.toggle(key); if(!nv)return;
        var q=glAskFoodQty(nv); if(q!==null) glAddFood(key,nv,q,glFoodWhen(key));
        fetch(GL_RXOPT_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:'food_item',value:nv})}).catch(function(){});
    },
    pin:function(btn,field,name){
        fetch(GL_RXPIN_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:field,value:name})})
          .then(function(r){return r.json();})
          .then(function(d){
              if(!d||!d.value)return;
              document.querySelectorAll('.gl-combo[data-field="'+field+'"] .gl-combo-item[data-name="'+name.toLowerCase()+'"]').forEach(function(it){
                  var b=it.querySelector('.gl-pin');
                  if(d.pinned){ b.classList.add('pinned'); b.title='Pinned — click to unpin'; var list=it.parentNode; list.insertBefore(it, list.firstChild); }
                  else { b.classList.remove('pinned'); b.title='Pin to top'; }
              });
          }).catch(function(){});
    }
};
document.addEventListener('click',function(e){ if(!e.target.closest||!e.target.closest('.gl-combo')) document.querySelectorAll('.gl-combo-panel').forEach(function(x){x.style.display='none';}); });
</script>
@endonce

<div class="form-group" style="margin:0">
    <label class="form-label">{{ $label }}</label>
    <div id="gl-{{ $key }}-chips" class="gl-chips"></div>
@if($isMed)
    <div class="gl-combo" data-field="{{ $field }}">
        <button type="button" class="form-control gl-combo-btn" onclick="glCombo.toggle('{{ $key }}')">＋ Add {{ $noun }}…<span style="opacity:.5">▾</span></button>
        <div class="gl-combo-panel" id="gl-{{ $key }}-panel">
            <input type="text" class="gl-combo-search" placeholder="Search {{ $noun }}…" oninput="glCombo.filter('{{ $key }}',this.value)">
            @foreach($pinnedList as $o)
                <div class="gl-combo-item" data-name="{{ strtolower($o) }}">
                    <button type="button" class="gl-pin pinned" title="Pinned — click to unpin" onclick="glCombo.pin(this,'{{ $field }}',@js($o))">📌</button>
                    <span onclick="glCombo.add('{{ $key }}',@js($o))">{{ $o }}</span>
                </div>
            @endforeach
            @foreach($options as $grp => $items)
                <div class="gl-combo-grp">{{ $grp }}</div>
                @foreach($items as $o)
                    @if(!in_array(strtolower($o), $pinnedLower))
                        <div class="gl-combo-item" data-name="{{ strtolower($o) }}">
                            <button type="button" class="gl-pin" title="Pin to top" onclick="glCombo.pin(this,'{{ $field }}',@js($o))">📌</button>
                            <span onclick="glCombo.add('{{ $key }}',@js($o))">{{ $o }}</span>
                        </div>
                    @endif
                @endforeach
            @endforeach
            @if(!empty($extras))
                <div class="gl-combo-grp">Added</div>
                @foreach($extras as $o)
                    @if(!in_array(strtolower($o), $pinnedLower))
                        <div class="gl-combo-item" data-name="{{ strtolower($o) }}">
                            <button type="button" class="gl-pin" title="Pin to top" onclick="glCombo.pin(this,'{{ $field }}',@js($o))">📌</button>
                            <span onclick="glCombo.add('{{ $key }}',@js($o))">{{ $o }}</span>
                        </div>
                    @endif
                @endforeach
            @endif
            <div class="gl-combo-item gl-combo-add" onclick="glCombo.addNew('{{ $key }}','{{ $field }}')">➕ Add new drug…</div>
        </div>
    </div>
@else
    <select class="form-control" onchange="glPick(this,'{{ $key }}','{{ $field }}')">
        <option value="">＋ Add {{ $noun }}…</option>
        @foreach($options as $grp => $items)
            <optgroup label="{{ $grp }}">
                @foreach($items as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
            </optgroup>
        @endforeach
        @if(!empty($extras))
            <optgroup label="Added">
                @foreach($extras as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
            </optgroup>
        @endif
        <option value="__add__">➕ Add new {{ $noun }}…</option>
    </select>
@endif
    <input type="hidden" name="{{ $name ?? $key }}" id="gl-{{ $key }}" value="{{ $value ?? '' }}">
</div>
