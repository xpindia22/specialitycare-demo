<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\GstRate;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Services\MedicineHsnClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineTaxFixController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $catId = $request->query('category');
        $onlyDefault = (bool) $request->query('only_default');

        $medicines = Medicine::with('category', 'gstRate')
            ->where('is_active', true)
            ->when($q, fn($x) => $x->where('name', 'like', "%{$q}%"))
            ->when($catId, fn($x) => $x->where('medicine_category_id', $catId))
            ->when($onlyDefault, fn($x) => $x->where(fn($w) => $w->where('hsn_code', '30049099')->whereHas('gstRate', fn($g) => $g->where('rate', 12))->orWhereHas('gstRate', fn($g) => $g->where('rate', 0))))
            ->orderBy('name')
            ->paginate(50)->withQueryString();

        return view('pharmacy.medicines.tax-fix', [
            'medicines'   => $medicines,
            'categories'  => MedicineCategory::orderBy('name')->get(),
            'gstRates'    => GstRate::orderBy('rate')->get(),
            'q'           => $q,
            'catId'       => $catId,
            'onlyDefault' => $onlyDefault,
        ]);
    }

    public function bulkSave(Request $request)
    {
        $data = $request->validate([
            'rows'              => 'required|array|min:1',
            'rows.*.id'         => 'required|integer|exists:medicines,id',
            'rows.*.hsn_code'   => 'nullable|string|max:16',
            'rows.*.gst_rate_id'=> 'nullable|integer|exists:gst_rates,id',
        ]);

        $changed = 0;
        DB::transaction(function () use ($data, &$changed) {
            foreach ($data['rows'] as $row) {
                $m = Medicine::find($row['id']);
                $hsn = $row['hsn_code'] ?: '30049099';
                $gst = $row['gst_rate_id'] ?: GstRate::where('rate', 12)->value('id');
                if ($m->hsn_code !== $hsn || (int) $m->gst_rate_id !== (int) $gst) {
                    $m->forceFill(['hsn_code' => $hsn, 'gst_rate_id' => $gst])->save();
                    $changed++;
                }
            }
        });

        return back()->with('success', "Saved — {$changed} medicines updated.");
    }

    public function autoClassify(Request $request, MedicineHsnClassifier $classifier)
    {
        $scope = $request->input('scope', 'only_default'); // only_default | all
        $defaultGstId = GstRate::where('rate', 0)->value('id');

        $q = Medicine::with('category', 'gstRate')->where('is_active', true);
        if ($scope === 'only_default') {
            $q->where(fn($x) => $x->where('gst_rate_id', $defaultGstId)->orWhereNull('gst_rate_id'));
        }

        $changed = 0;
        DB::transaction(function () use ($q, $classifier, &$changed) {
            foreach ($q->cursor() as $m) {
                $new = $classifier->classify($m);
                if ($m->hsn_code !== $new['hsn_code'] || (int) $m->gst_rate_id !== (int) $new['gst_rate_id']) {
                    $m->forceFill($new)->save();
                    $changed++;
                }
            }
        });

        return back()->with('success', "Auto-classified — {$changed} medicines reclassified. Review and adjust below.");
    }
}
