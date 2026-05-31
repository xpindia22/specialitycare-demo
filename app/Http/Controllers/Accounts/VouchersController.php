<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VouchersController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : Carbon::today()->subMonth();
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))   : Carbon::today();
        $typeCode = $request->query('type');
        $q = trim((string) $request->query('q', ''));

        $vouchers = Voucher::with('type', 'party', 'createdBy')
            ->whereBetween('voucher_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($typeCode, fn($x) => $x->whereHas('type', fn($t) => $t->where('code', $typeCode)))
            ->when($q, fn($x) => $x->where(fn($w) => $w->where('voucher_no', 'like', "%{$q}%")->orWhere('narration', 'like', "%{$q}%")->orWhere('reference_no', 'like', "%{$q}%")))
            ->orderByDesc('voucher_date')->orderByDesc('id')
            ->paginate(50)->withQueryString();

        $types = VoucherType::where('is_active', true)->orderBy('sort_order')->get();

        return view('accounts.vouchers.index', compact('vouchers', 'types', 'from', 'to', 'typeCode', 'q'));
    }

    public function create(string $typeCode = 'PAY')
    {
        $type    = VoucherType::byCode($typeCode);
        $ledgers = AccountLedger::with('group')->where('is_active', true)->orderBy('name')->get();
        $types   = VoucherType::where('is_active', true)->orderBy('sort_order')->get();
        $date    = Carbon::today();
        return view('accounts.vouchers.create', compact('type', 'types', 'ledgers', 'date'));
    }

    public function store(Request $request, VoucherService $svc)
    {
        $data = $request->validate([
            'type'           => ['required', 'string', 'exists:voucher_types,code'],
            'date'           => ['required', 'date'],
            'narration'      => ['nullable', 'string', 'max:2000'],
            'party_ledger'   => ['nullable', 'integer', 'exists:account_ledgers,id'],
            'reference_no'   => ['nullable', 'string', 'max:64'],
            'reference_date' => ['nullable', 'date'],
            'entries'                  => ['required', 'array', 'min:2'],
            'entries.*.ledger'         => ['required', 'integer', 'exists:account_ledgers,id'],
            'entries.*.dr_amount'      => ['nullable', 'numeric', 'min:0'],
            'entries.*.cr_amount'      => ['nullable', 'numeric', 'min:0'],
            'entries.*.narration'      => ['nullable', 'string', 'max:500'],
            'entries.*.hsn_sac'        => ['nullable', 'string', 'max:16'],
            'entries.*.taxable_amount' => ['nullable', 'numeric', 'min:0'],
            'entries.*.gst_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        try {
            $voucher = $svc->create([
                'type'           => $data['type'],
                'date'           => $data['date'],
                'narration'      => $data['narration'] ?? null,
                'party_ledger'   => $data['party_ledger'] ?? null,
                'reference_no'   => $data['reference_no'] ?? null,
                'reference_date' => $data['reference_date'] ?? null,
                'entries'        => collect($data['entries'])->map(fn($e) => [
                    'ledger'         => (int) $e['ledger'],
                    'dr'             => (float) ($e['dr_amount'] ?? 0),
                    'cr'             => (float) ($e['cr_amount'] ?? 0),
                    'narration'      => $e['narration']      ?? null,
                    'hsn_sac'        => $e['hsn_sac']        ?? null,
                    'taxable_amount' => isset($e['taxable_amount']) ? (float) $e['taxable_amount'] : null,
                    'gst_rate'       => isset($e['gst_rate'])       ? (float) $e['gst_rate']       : null,
                ])->all(),
                'created_by' => $request->user()?->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['voucher' => $e->getMessage()]);
        }

        return redirect()->route('accounts.vouchers.show', $voucher)->with('success', "Voucher {$voucher->voucher_no} posted.");
    }

    public function show(Voucher $voucher)
    {
        $voucher->load('type', 'party', 'createdBy', 'entries.ledger.group');
        return view('accounts.vouchers.show', compact('voucher'));
    }

    public function print(Voucher $voucher)
    {
        $voucher->load('type', 'party', 'createdBy', 'entries.ledger.group');
        return view('accounts.vouchers.print', compact('voucher'));
    }

    public function cancel(Voucher $voucher, Request $request, VoucherService $svc)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        if ($voucher->status === 'Cancelled') {
            return back()->withErrors(['voucher' => 'Voucher is already cancelled.']);
        }
        $svc->cancel($voucher, $request->input('reason'));
        return back()->with('success', "Voucher {$voucher->voucher_no} cancelled.");
    }
}
