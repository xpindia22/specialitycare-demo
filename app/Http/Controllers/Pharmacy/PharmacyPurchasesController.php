<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Medicine;
use App\Models\PharmacyPurchase;
use App\Services\StockReceiptPoster;
use Illuminate\Http\Request;

class PharmacyPurchasesController extends Controller
{
    public function index()
    {
        $purchases = PharmacyPurchase::with('supplier', 'voucher', 'items')
            ->orderByDesc('invoice_date')->orderByDesc('id')
            ->paginate(25);
        $missingTaxInfo = Medicine::where(fn($q) => $q->whereNull('hsn_code')->orWhere('hsn_code', '')->orWhereNull('gst_rate_id'))->count();
        return view('pharmacy.purchases.index', compact('purchases', 'missingTaxInfo'));
    }

    public function create()
    {
        return view('pharmacy.purchases.create', [
            'suppliers'         => AccountLedger::where('is_active', true)->where('party_type', 'Supplier')->orderBy('name')->get(),
            'medicines'         => Medicine::with('gstRate')->where('is_active', true)->orderBy('name')->get(),
            'categories'        => \App\Models\MedicineCategory::orderBy('name')->get(),
            'today'             => now()->toDateString(),
            'defaultSupplierId' => AccountLedger::defaultSupplier()?->id,
        ]);
    }

    public function store(Request $request, StockReceiptPoster $poster)
    {
        $data = $request->validate([
            'supplier_ledger_id'    => ['required', 'integer', 'exists:account_ledgers,id'],
            'invoice_no'            => ['nullable', 'string', 'max:64'],
            'invoice_date'          => ['required', 'date'],
            'payment_mode'          => ['required', 'in:Credit,Cash,Bank'],
            'narration'             => ['nullable', 'string', 'max:500'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.medicine_id'   => ['required', 'integer', 'exists:medicines,id'],
            'items.*.batch_number'  => ['nullable', 'string', 'max:64'],
            'items.*.expiry_date'   => ['nullable', 'date'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost'     => ['required', 'numeric', 'min:0'],
            'items.*.hsn_code'      => ['nullable', 'string', 'max:16'],
            'items.*.gst_rate'      => ['nullable', 'numeric', 'min:0', 'max:50'],
        ]);
        $data['created_by'] = auth()->id();

        $purchase = $poster->record($data);
        return redirect()->route('pharmacy.purchases.show', $purchase)
            ->with('success', "Stock receipt #{$purchase->id} saved. Voucher {$purchase->voucher->voucher_no} posted to accounts.");
    }

    public function show(PharmacyPurchase $purchase)
    {
        $purchase->load('items.medicine', 'supplier', 'voucher.entries.ledger');
        return view('pharmacy.purchases.show', compact('purchase'));
    }
}
