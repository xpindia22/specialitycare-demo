<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Compute a small, live-derived feed of items the user should see.
     * No persistent notifications table — we just query the underlying
     * domain tables and build a list. Cheap because each query is
     * date-bounded and limited.
     */
    public function feed()
    {
        $items = collect();

        // 0) Unread direct messages — alert the recipient in the bell. One item
        //    per thread (latest unread), newest first.
        $uid = auth()->id();
        DB::table('messages')
            ->join('message_thread_user as p', function ($j) use ($uid) {
                $j->on('p.message_thread_id', '=', 'messages.message_thread_id')
                  ->where('p.user_id', $uid)->whereNull('p.left_at');
            })
            ->join('users as sender', 'sender.id', '=', 'messages.user_id')
            ->whereNull('messages.deleted_at')
            ->where('messages.user_id', '!=', $uid)
            ->where(fn ($q) => $q->whereColumn('messages.created_at', '>', 'p.last_read_at')
                                 ->orWhereNull('p.last_read_at'))
            ->orderByDesc('messages.created_at')
            ->limit(20)
            ->select('messages.message_thread_id', 'messages.body', 'messages.created_at', 'sender.name as sender_name')
            ->get()
            ->groupBy('message_thread_id')
            ->take(6)
            ->each(function ($group) use ($items) {
                $m = $group->first();
                $more = $group->count() > 1 ? ' (+' . ($group->count() - 1) . ' more)' : '';
                $items->push([
                    'type'  => 'dm',
                    'icon'  => 'fa-comment-dots',
                    'color' => '#16a34a',
                    'label' => 'Message from ' . $m->sender_name,
                    'sub'   => \Illuminate\Support\Str::limit(trim(strip_tags($m->body ?? '')), 48) . $more,
                    'time'  => Carbon::parse($m->created_at)->diffForHumans(),
                    'url'   => route('messages.thread', $m->message_thread_id),
                ]);
            });

        // 1) Lab orders completed today — the doctor should review them
        DB::table('lab_orders')
            ->join('patients', 'patients.id', '=', 'lab_orders.patient_id')
            ->where('lab_orders.status', 'Completed')
            ->whereDate('lab_orders.updated_at', today())
            ->orderByDesc('lab_orders.updated_at')
            ->limit(8)
            ->select('lab_orders.id','lab_orders.order_no','lab_orders.updated_at','patients.first_name','patients.last_name','patients.jhc_id')
            ->get()
            ->each(function ($r) use ($items) {
                $items->push([
                    'type'  => 'lab',
                    'icon'  => 'fa-flask',
                    'color' => '#0ab4b4',
                    'label' => "Lab result ready — {$r->order_no}",
                    'sub'   => trim("{$r->first_name} {$r->last_name}") . " · {$r->jhc_id}",
                    'time'  => Carbon::parse($r->updated_at)->diffForHumans(),
                    'url'   => route('lab.order', $r->id),
                ]);
            });

        // 2) Unpaid bills > 7 days old
        DB::table('bills')
            ->join('patients', 'patients.id', '=', 'bills.patient_id')
            ->where('bills.status', '!=', 'Paid')
            ->where('bills.due_amount', '>', 0)
            ->where('bills.bill_date', '<=', today()->subDays(7))
            ->orderBy('bills.bill_date')
            ->limit(6)
            ->select('bills.id','bills.bill_no','bills.bill_date','bills.due_amount','patients.first_name','patients.last_name','patients.jhc_id')
            ->get()
            ->each(function ($r) use ($items) {
                $daysOld = Carbon::parse($r->bill_date)->diffInDays(today());
                $items->push([
                    'type'  => 'bill',
                    'icon'  => 'fa-file-invoice-dollar',
                    'color' => '#e84c4c',
                    'label' => "Overdue ₹" . number_format($r->due_amount, 0) . " — {$r->bill_no}",
                    'sub'   => trim("{$r->first_name} {$r->last_name}") . " · {$r->jhc_id} · {$daysOld}d old",
                    'time'  => Carbon::parse($r->bill_date)->diffForHumans(),
                    'url'   => route('billing.show', $r->id),
                ]);
            });

        // 3) Low stock medicines (≤ 5 units, > 0)
        DB::table('pharmacy_stock')
            ->join('medicines', 'medicines.id', '=', 'pharmacy_stock.medicine_id')
            ->where('pharmacy_stock.is_active', true)
            ->where('pharmacy_stock.quantity', '>', 0)
            ->where('pharmacy_stock.quantity', '<=', 5)
            ->orderBy('pharmacy_stock.quantity')
            ->limit(6)
            ->select('medicines.id', 'medicines.name', 'pharmacy_stock.quantity', 'pharmacy_stock.batch_number')
            ->get()
            ->each(function ($r) use ($items) {
                $items->push([
                    'type'  => 'stock',
                    'icon'  => 'fa-pills',
                    'color' => '#f5a623',
                    'label' => "Critical low stock: {$r->name}",
                    'sub'   => "Only {$r->quantity} left · batch " . ($r->batch_number ?: '—'),
                    'time'  => '',
                    'url'   => route('pharmacy.stock'),
                ]);
            });

        // 4) Stock batches expiring within 30 days
        DB::table('pharmacy_stock')
            ->join('medicines', 'medicines.id', '=', 'pharmacy_stock.medicine_id')
            ->where('pharmacy_stock.is_active', true)
            ->where('pharmacy_stock.quantity', '>', 0)
            ->whereNotNull('pharmacy_stock.expiry_date')
            ->whereBetween('pharmacy_stock.expiry_date', [today(), today()->addDays(30)])
            ->orderBy('pharmacy_stock.expiry_date')
            ->limit(6)
            ->select('medicines.name','pharmacy_stock.expiry_date','pharmacy_stock.batch_number')
            ->get()
            ->each(function ($r) use ($items) {
                $items->push([
                    'type'  => 'expiry',
                    'icon'  => 'fa-hourglass-end',
                    'color' => '#f5a623',
                    'label' => "Expiring {$r->name}",
                    'sub'   => 'Batch ' . ($r->batch_number ?: '—') . ' · ' . Carbon::parse($r->expiry_date)->format('d M Y'),
                    'time'  => Carbon::parse($r->expiry_date)->diffForHumans(),
                    'url'   => route('pharmacy.stock-register'),
                ]);
            });

        return response()->json([
            'items' => $items->values(),
            'count' => $items->count(),
        ]);
    }
}
