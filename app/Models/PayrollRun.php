<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PayrollRun extends Model
{
    protected $fillable = [
        'staff_id','doctor_id','year','month','days_in_month',
        'days_present','days_paid_leave','days_off','days_unpaid_leave','days_absent','days_payable',
        'base_salary','base_pay_prorated','credits','debits','net_payable',
        'status','payment_method','paid_at','paid_by','notes',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'days_present'      => 'decimal:2',
        'days_paid_leave'   => 'decimal:2',
        'days_off'          => 'decimal:2',
        'days_unpaid_leave' => 'decimal:2',
        'days_absent'       => 'decimal:2',
        'days_payable'      => 'decimal:2',
        'base_salary'       => 'decimal:2',
        'base_pay_prorated' => 'decimal:2',
        'credits'           => 'decimal:2',
        'debits'            => 'decimal:2',
        'net_payable'       => 'decimal:2',
    ];

    public function staff()  { return $this->belongsTo(Staff::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function paidBy() { return $this->belongsTo(User::class, 'paid_by'); }

    public function subject()
    {
        return $this->doctor_id ? $this->doctor : $this->staff;
    }

    public function getSubjectNameAttribute(): string
    {
        $s = $this->subject();
        if (!$s) return '—';
        return $this->doctor_id ? 'Dr. ' . trim($s->first_name . ' ' . $s->last_name)
                                : trim($s->first_name . ' ' . $s->last_name);
    }

    public function getMonthLabelAttribute(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    /**
     * Compute (or recompute) a payroll run for a single staff member OR doctor.
     * `$subject` must be a Staff or Doctor instance.
     */
    public static function computeForMonth(Model $subject, int $year, int $month): self
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $daysInMonth = $start->daysInMonth;

        $fkColumn = $subject instanceof Doctor ? 'doctor_id' : 'staff_id';
        $salary   = (float) ($subject->salary ?? 0);

        $attendance = Attendance::where($fkColumn, $subject->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('status');

        $tally = function (string $status) use ($attendance): float {
            $rows = $attendance->get($status, collect());
            $perDay = Attendance::STATUSES[$status]['days'] ?? 0;
            return (float) $rows->count() * $perDay;
        };

        $daysPresent      = $tally('present') + $tally('half_day');
        $daysPaidLeave    = $tally('paid_leave');
        $daysOff          = $tally('week_off') + $tally('holiday');
        $daysUnpaidLeave  = $tally('unpaid_leave');
        $daysAbsent       = $tally('absent');
        $daysPayable      = $daysPresent + $daysPaidLeave + $daysOff;

        $basePayProrated  = $daysInMonth > 0 ? round($salary * $daysPayable / $daysInMonth, 2) : 0.0;

        $adjustments = PayrollAdjustment::where($fkColumn, $subject->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();
        $credits = 0.0; $debits = 0.0;
        foreach ($adjustments as $adj) {
            $sign = PayrollAdjustment::TYPES[$adj->type]['sign'] ?? 1;
            if ($sign > 0) $credits += (float) $adj->amount;
            else           $debits  += (float) $adj->amount;
        }

        $netPayable = round($basePayProrated + $credits - $debits, 2);

        $lookup = [$fkColumn => $subject->id, 'year' => $year, 'month' => $month];
        $run = self::firstOrNew($lookup);

        if ($run->exists && $run->status === 'paid') {
            return $run;
        }

        $run->fill([
            'days_in_month'     => $daysInMonth,
            'days_present'      => $daysPresent,
            'days_paid_leave'   => $daysPaidLeave,
            'days_off'          => $daysOff,
            'days_unpaid_leave' => $daysUnpaidLeave,
            'days_absent'       => $daysAbsent,
            'days_payable'      => $daysPayable,
            'base_salary'       => $salary,
            'base_pay_prorated' => $basePayProrated,
            'credits'           => $credits,
            'debits'            => $debits,
            'net_payable'       => $netPayable,
            'status'            => $run->status ?? 'pending',
        ])->save();

        return $run;
    }
}
