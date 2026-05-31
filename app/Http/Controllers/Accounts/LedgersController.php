<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Models\AccountLedger;
use App\Models\RecurringExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgersController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $groupId = $request->query('group_id');
        $partyType = $request->query('party_type');

        $ledgers = AccountLedger::with('group')
            ->when($q, fn($x) => $x->where(fn($w) => $w->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%")))
            ->when($groupId, fn($x) => $x->where('group_id', $groupId))
            ->when($partyType, fn($x) => $x->where('party_type', $partyType))
            ->orderBy('group_id')->orderBy('name')
            ->paginate(50)->withQueryString();

        $groups = AccountGroup::orderBy('sort_order')->get();

        return view('accounts.ledgers.index', compact('ledgers', 'groups', 'q', 'groupId', 'partyType'));
    }

    public function create()
    {
        $ledger = new AccountLedger(['opening_balance_dr_cr' => 'Dr', 'is_active' => true]);
        $groups = AccountGroup::orderBy('sort_order')->get();
        return view('accounts.ledgers.form', compact('ledger', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: $this->autoCode($data['name']);
        AccountLedger::create($data);
        return redirect()->route('accounts.ledgers.index')->with('success', "Ledger '{$data['name']}' created.");
    }

    public function show(AccountLedger $ledger, Request $request)
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : Carbon::today()->subMonths(3);
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))   : Carbon::today();

        // Opening balance = ledger's seeded opening + all postings BEFORE `from`
        $priorRows = DB::table('voucher_entries as ve')
            ->join('vouchers as v', 'v.id', '=', 've.voucher_id')
            ->where('v.status', 'Posted')
            ->where('ve.ledger_id', $ledger->id)
            ->where('v.voucher_date', '<', $from)
            ->select(DB::raw('SUM(ve.dr_amount) as dr'), DB::raw('SUM(ve.cr_amount) as cr'))
            ->first();
        $openingDrCrSign = $ledger->opening_balance_dr_cr === 'Dr' ? 1 : -1;
        $openingBalance  = round(((float) $ledger->opening_balance) * $openingDrCrSign + (float) ($priorRows->dr ?? 0) - (float) ($priorRows->cr ?? 0), 2);

        $entries = DB::table('voucher_entries as ve')
            ->join('vouchers as v',       'v.id',  '=', 've.voucher_id')
            ->join('voucher_types as vt', 'vt.id', '=', 'v.voucher_type_id')
            ->leftJoin('account_ledgers as p', 'p.id', '=', 'v.party_ledger_id')
            ->where('v.status', 'Posted')
            ->where('ve.ledger_id', $ledger->id)
            ->whereBetween('v.voucher_date', [$from, $to])
            ->orderBy('v.voucher_date')->orderBy('v.id')->orderBy('ve.line_no')
            ->select(
                'v.id as voucher_id', 'v.voucher_no', 'v.voucher_date', 'v.narration as v_narr',
                'vt.code as type_code', 'vt.name as type_name',
                'p.name as party_name',
                've.dr_amount', 've.cr_amount', 've.narration as e_narr'
            )
            ->get();

        $running = $openingBalance;
        $totals  = ['dr' => 0, 'cr' => 0];
        foreach ($entries as $e) {
            $running += (float) $e->dr_amount - (float) $e->cr_amount;
            $e->running = round($running, 2);
            $totals['dr'] += (float) $e->dr_amount;
            $totals['cr'] += (float) $e->cr_amount;
        }
        $closingBalance = round($running, 2);

        return view('accounts.ledgers.show', compact(
            'ledger', 'entries', 'openingBalance', 'closingBalance', 'totals', 'from', 'to'
        ));
    }

    public function edit(AccountLedger $ledger)
    {
        $groups = AccountGroup::orderBy('sort_order')->get();
        return view('accounts.ledgers.form', compact('ledger', 'groups'));
    }

    public function update(Request $request, AccountLedger $ledger)
    {
        $data = $this->validated($request, $ledger->id);
        $data['code'] = $data['code'] ?: $ledger->code;
        $ledger->update($data);
        return redirect()->route('accounts.ledgers.index')->with('success', "Ledger '{$ledger->name}' updated.");
    }

    public function destroy(AccountLedger $ledger)
    {
        if ($ledger->is_system) {
            return back()->withErrors(['ledger' => 'System ledgers cannot be deleted (they are referenced by the voucher engine).']);
        }
        if ($ledger->entries()->exists()) {
            return back()->withErrors(['ledger' => 'Cannot delete: ledger has posted entries. Mark inactive instead.']);
        }
        $usedByRecurring = RecurringExpense::where('expense_ledger_id', $ledger->id)
            ->orWhere('counter_ledger_id', $ledger->id)
            ->pluck('name');
        if ($usedByRecurring->isNotEmpty()) {
            return back()->withErrors(['ledger' => 'Cannot delete: still referenced by recurring expense(s): ' . $usedByRecurring->implode(', ') . '. Remove or repoint those first.']);
        }
        $name = $ledger->name;
        $ledger->delete();
        return redirect()->route('accounts.ledgers.index')->with('success', "Ledger '{$name}' deleted.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'code'                  => ['nullable', 'string', 'max:64'],
            'group_id'              => ['required', 'integer', 'exists:account_groups,id'],
            'opening_balance'       => ['nullable', 'numeric', 'min:0'],
            'opening_balance_dr_cr' => ['required', 'in:Dr,Cr'],
            'gstin'                 => ['nullable', 'string', 'max:20'],
            'state_code'            => ['nullable', 'string', 'max:4'],
            'party_type'            => ['nullable', 'in:Customer,Supplier,Employee,Bank,Cash,Tax,Other'],
            'contact_phone'         => ['nullable', 'string', 'max:32'],
            'contact_email'         => ['nullable', 'email', 'max:191'],
            'address'               => ['nullable', 'string'],
            'is_active'             => ['nullable', 'boolean'],
            'notes'                 => ['nullable', 'string'],
        ]);
    }

    /** Build a code from the name if user didn't supply one. */
    private function autoCode(string $name): string
    {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 60);
        // Disambiguate against existing
        $base = $slug ?: 'LEDGER';
        $i = 0;
        do {
            $code = $i === 0 ? $base : ($base . '-' . $i);
            $i++;
        } while (AccountLedger::where('code', $code)->exists());
        return $code;
    }
}
