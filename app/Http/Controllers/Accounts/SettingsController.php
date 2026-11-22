<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function edit()
    {
        $values = DB::table('settings')->where('group', 'accounts')->pluck('value', 'key')->all();
        $values += DB::table('settings')->where('group', 'clinic')->whereIn('key', ['clinic_name', 'clinic_address'])->pluck('value', 'key')->all();
        return view('accounts.settings', compact('values'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'clinic_gstin'       => ['nullable', 'string', 'max:20'],
            'clinic_state_code'  => ['nullable', 'string', 'max:4'],
            'clinic_state_name'  => ['nullable', 'string', 'max:100'],
            'clinic_legal_name'  => ['nullable', 'string', 'max:200'],
            'fy_start_month'     => ['required', 'integer', 'min:1', 'max:12'],
            'accounts_books_opening_date'  => ['nullable', 'date'],
            'accounts_auto_post_bills'     => ['nullable', 'boolean'],
            'accounts_auto_post_purchases' => ['nullable', 'boolean'],
            'accounts_auto_post_dispense'  => ['nullable', 'boolean'],
            'accounts_pharmacy_sale_markup_pct' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);
        // Normalise the cutover date to Y-m-d (or '' to clear it).
        $data['accounts_books_opening_date'] = $data['accounts_books_opening_date']
            ? \Illuminate\Support\Carbon::parse($data['accounts_books_opening_date'])->toDateString()
            : '';
        // Booleans default to 0 when checkbox is unchecked (not posted)
        $data['accounts_auto_post_bills']     = (string) (int) ($data['accounts_auto_post_bills']     ?? 0);
        $data['accounts_auto_post_purchases'] = (string) (int) ($data['accounts_auto_post_purchases'] ?? 0);
        $data['accounts_auto_post_dispense']  = (string) (int) ($data['accounts_auto_post_dispense']  ?? 0);
        $data['accounts_pharmacy_sale_markup_pct'] = (string) (float) ($data['accounts_pharmacy_sale_markup_pct'] ?? 0);

        foreach ($data as $k => $v) {
            DB::table('settings')->updateOrInsert(
                ['key' => $k],
                ['key' => $k, 'value' => (string) $v, 'group' => 'accounts', 'updated_at' => now()]
            );
        }
        return back()->with('success', 'Accounts settings saved.');
    }
}
