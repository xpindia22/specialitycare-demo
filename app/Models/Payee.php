<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A named recurring expense recipient (staff, maid, cleaner, driver...).
 *
 * On create, automatically provisions a per-payee expense ledger under
 * the 'Salaries & Wages' sub-group so the P&L shows one line per person.
 * The user can override `ledger_id` in the form to point at an existing
 * ledger (e.g., reuse EXP-MAID for all maid payments).
 */
class Payee extends Model
{
    protected $fillable = [
        'name', 'role', 'monthly_amount',
        'ledger_id', 'pay_from_ledger_id', 'hr_staff_id', 'hr_doctor_id',
        'contact_phone', 'is_active', 'notes',
    ];
    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'is_active'      => 'bool',
    ];

    public function ledger()        { return $this->belongsTo(AccountLedger::class, 'ledger_id'); }
    public function payFromLedger() { return $this->belongsTo(AccountLedger::class, 'pay_from_ledger_id'); }
    public function hrStaff()       { return $this->belongsTo(\App\Models\Staff::class, 'hr_staff_id'); }
    public function hrDoctor()      { return $this->belongsTo(\App\Models\Doctor::class, 'hr_doctor_id'); }

    protected static function booted(): void
    {
        // If ledger_id wasn't provided, auto-create a per-payee ledger.
        static::creating(function (Payee $payee) {
            if (empty($payee->ledger_id)) {
                $payee->ledger_id = self::provisionLedger($payee->name)->id;
            }
        });

        // Keep the linked ledger's name in sync when the payee is renamed.
        static::updating(function (Payee $payee) {
            if ($payee->isDirty('name') && $payee->ledger_id) {
                $ledger = AccountLedger::find($payee->ledger_id);
                if ($ledger && str_starts_with($ledger->name, 'Salary - ')) {
                    $ledger->update(['name' => 'Salary - ' . $payee->name]);
                }
            }
        });

        // On delete, only drop the auto-created ledger if it has no entries.
        // The controller already prevents deletion if entries exist, but
        // defense in depth.
        static::deleting(function (Payee $payee) {
            $ledger = $payee->ledger;
            if ($ledger && !$ledger->entries()->exists() && str_starts_with($ledger->name, 'Salary - ')) {
                $ledger->forceDelete();
            }
        });
    }

    private static function provisionLedger(string $payeeName): AccountLedger
    {
        $groupId = AccountGroup::where('code', 'SALWAGES')->value('id');
        if (!$groupId) {
            // Fallback to plain Indirect Expenses if sub-group migration didn't run.
            $groupId = AccountGroup::where('code', 'IXP')->value('id');
        }

        $base = 'EXP-PAY-' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $payeeName));
        $base = substr($base, 0, 50);
        $code = $base;
        $i = 1;
        while (AccountLedger::where('code', $code)->exists()) {
            $code = $base . '-' . $i++;
        }

        return AccountLedger::create([
            'name'                  => 'Salary - ' . $payeeName,
            'code'                  => $code,
            'group_id'              => $groupId,
            'opening_balance'       => 0,
            'opening_balance_dr_cr' => 'Dr',
            'party_type'            => 'Employee',
            'is_system'             => 1, // hides delete button on Ledgers page
            'is_active'             => true,
        ]);
    }
}
