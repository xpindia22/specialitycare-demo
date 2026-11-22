{{-- Dose ↔ rate maths shared by the calculator and the ICU chart (mirrors App\Models\IcuInfusion). --}}
<script>
window.ICU = (function () {
    const DRUGS = {!! json_encode(config('icu_drugs.infusions'), JSON_UNESCAPED_UNICODE) !!};
    const FACTOR = { mg: { mg: 1, mcg: 1000 }, mcg: { mcg: 1, mg: 0.001 }, U: { U: 1 } };

    function parts(unit) { const p = unit.split('/'); return { num: p[0], perKg: p.includes('kg'), time: p[p.length - 1] }; }
    // concentration in the dose unit's numerator per ml (e.g. mcg/ml for mcg/kg/min)
    function conc(amount, amountUnit, volume, doseUnit) {
        const f = (FACTOR[amountUnit] || {})[parts(doseUnit).num];
        if (f === undefined || !(volume > 0)) return NaN;
        return amount * f / volume;
    }
    function perHour(doseUnit, weight) {
        const p = parts(doseUnit);
        if (p.perKg && !(weight > 0)) return NaN;
        return (p.perKg ? weight : 1) * (p.time === 'min' ? 60 : 1);
    }
    function rate(dose, c, doseUnit, weight) { return dose * perHour(doseUnit, weight) / c; }
    function dose(rate, c, doseUnit, weight) { return rate * c / perHour(doseUnit, weight); }
    function fmt(n, unit) {
        if (n === null || n === undefined || !isFinite(n)) return '—';
        const a = Math.abs(n), d = a >= 100 ? 0 : a >= 10 ? 1 : a >= 1 ? 2 : 3;
        return String(parseFloat(n.toFixed(d))) + (unit ? ' ' + unit : '');
    }
    // where a dose sits against the drug's usual range: 'low' | 'ok' | 'high' | 'over'
    function band(drug, d) {
        if (!drug || !isFinite(d)) return null;
        if (d > drug.max) return 'over';
        if (d > drug.range[1]) return 'high';
        if (d < drug.range[0]) return 'low';
        return 'ok';
    }
    function steps(drug) {
        const out = [], s = drug.step || drug.range[0];
        for (let d = Math.min(drug.start, drug.range[0]); d <= drug.max + 1e-9 && out.length < 60; d += s) out.push(parseFloat(d.toFixed(4)));
        return out;
    }
    return { DRUGS, parts, conc, perHour, rate, dose, fmt, band, steps };
})();
</script>
