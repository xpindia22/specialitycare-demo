{{-- FILE: resources/views/billing/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Bill '.$bill->bill_no)
@section('page-title', 'Bill Details')
@section('content')

@include('billing._toolbar')
<div class="page-header">
    <div class="page-header-left">
        <h1>Bill — <span class="mono">{{ $bill->bill_no }}</span></h1>
        <div class="breadcrumb"><a href="{{ route('billing.index') }}">Billing</a> › {{ $bill->bill_no }}</div>
    </div>
    <a href="{{ route('billing.print', $bill) }}" class="btn btn-outline" target="_blank"><i class="fa-solid fa-print"></i> Print</a>
    <a href="{{ route('billing.pdf', $bill) }}" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> PDF</a>
    <button type="button" class="btn btn-outline" onclick="document.getElementById('email-bill-modal').classList.add('open')"><i class="fa-solid fa-envelope"></i> Email</button>
    <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-bill-modal').classList.add('open')"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
    @if($bill->due_amount > 0)
    <button class="btn btn-primary" onclick="document.getElementById('payment-modal').classList.add('open')"><i class="fa-solid fa-money-bill"></i> Add Payment</button>
    @endif
</div>

<div class="grid" style="grid-template-columns:1fr 340px;gap:20px;align-items:start">
<div>
    <!-- Patient & Bill Info -->
    <div class="patient-strip mb-3">
        <div class="ps-avatar">{{ strtoupper(substr($bill->patient->first_name,0,1)) }}</div>
        <div>
            <div class="ps-name">{{ $bill->patient->full_name }}</div>
            <div class="ps-jhc_id">{{ $bill->patient->jhc_id }}</div>
            <div class="ps-meta">
                <span><i class="fa-solid fa-calendar"></i> {{ $bill->patient->age }}</span>
                <span>{{ $bill->patient->gender }}</span>
                <span><i class="fa-solid fa-phone"></i> {{ $bill->patient->phone }}</span>
            </div>
        </div>
        <div class="ps-actions">
            <span class="badge {{ $bill->status==='Paid' ? 'badge-green' : ($bill->status==='Partial' ? 'badge-amber' : 'badge-red') }}" style="font-size:14px;padding:8px 16px">{{ $bill->status }}</span>
        </div>
    </div>

    <!-- Bill Items -->
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-list"></i> Bill Items</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>#</th><th>Item</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Discount</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                @foreach($bill->items as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td class="fw-600">{{ $item->item_name }}</td>
                    <td><span class="badge badge-navy">{{ $item->item_type }}</span></td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right" style="color:var(--sc-red)">{{ $item->discount > 0 ? '-₹'.number_format($item->discount,2) : '—' }}</td>
                    <td class="text-right fw-600">₹{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <table style="width:250px;margin-left:auto;border-collapse:collapse">
                <tr><td style="padding:5px 0;color:var(--sc-muted)">Subtotal</td><td style="text-align:right">₹{{ number_format($bill->subtotal, 2) }}</td></tr>
                @if($bill->discount_amount > 0)
                <tr><td style="padding:5px 0;color:var(--sc-red)">Discount ({{ $bill->discount_percent }}%)</td><td style="text-align:right;color:var(--sc-red)">-₹{{ number_format($bill->discount_amount, 2) }}</td></tr>
                @endif
                @if($bill->tax_amount > 0)
                <tr><td style="padding:5px 0">Tax</td><td style="text-align:right">₹{{ number_format($bill->tax_amount, 2) }}</td></tr>
                @endif
                @if($bill->insurance_amount > 0)
                <tr><td style="padding:5px 0;color:#6d28d9">Insurance{{ $bill->insurance_company ? ' ('.$bill->insurance_company.')' : '' }}</td><td style="text-align:right;color:#6d28d9">₹{{ number_format($bill->insurance_amount, 2) }}</td></tr>
                @if($bill->tpa_ref)<tr><td style="padding:2px 0;font-size:11px;color:var(--sc-muted)">TPA Ref</td><td style="text-align:right;font-size:11px;color:var(--sc-muted)">{{ $bill->tpa_ref }}</td></tr>@endif
                @endif
                <tr style="border-top:2px solid var(--sc-border)">
                    <td style="padding:8px 0;font-weight:700;font-size:16px">Total</td>
                    <td style="text-align:right;font-weight:700;font-size:16px;color:var(--sc-navy)">₹{{ number_format($bill->total_amount, 2) }}</td>
                </tr>
                <tr><td style="padding:4px 0;color:var(--sc-green)">Paid</td><td style="text-align:right;color:var(--sc-green);font-weight:600">₹{{ number_format($bill->paid_amount, 2) }}</td></tr>
                @if($bill->due_amount > 0)
                <tr><td style="padding:4px 0;color:var(--sc-red);font-weight:600">Due</td><td style="text-align:right;color:var(--sc-red);font-weight:700;font-size:15px">₹{{ number_format($bill->due_amount, 2) }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Payment History -->
    @if($bill->payments->count())
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-money-bill-wave"></i> Payment History</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>#</th><th>Date</th><th>Method</th><th>Ref</th><th class="text-right">Amount</th><th>Received By</th>@if(auth()->user()->isOwner())<th></th>@endif</tr></thead>
                <tbody>
                @foreach($bill->payments as $i => $pay)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>
                        <form method="POST" action="{{ route('billing.payment-date', [$bill, $pay]) }}" style="display:flex;gap:4px;align-items:center;margin:0" title="Change payment date">
                            @csrf
                            <input type="date" name="payment_date" class="form-control" style="padding:3px 6px;font-size:12.5px;width:auto" value="{{ $pay->payment_date->toDateString() }}" max="{{ today()->toDateString() }}" required>
                            <button type="submit" class="btn btn-outline btn-sm" style="padding:3px 8px"><i class="fa-solid fa-check"></i></button>
                        </form>
                    </td>
                    <td><span class="badge badge-teal">{{ $pay->payment_method }}</span></td>
                    <td class="mono text-muted">{{ $pay->transaction_ref ?: '—' }}</td>
                    <td class="text-right fw-600" style="color:var(--sc-green)">₹{{ number_format($pay->amount, 2) }}</td>
                    <td class="text-muted">{{ $pay->receivedBy?->name ?? '—' }}</td>
                    @if(auth()->user()->isOwner())
                    <td>
                        <form method="POST" action="{{ route('billing.payment.destroy', [$bill, $pay]) }}" style="margin:0"
                              onsubmit="return confirm('Delete this ₹{{ number_format($pay->amount, 2) }} {{ $pay->payment_method }} payment dated {{ $pay->payment_date->format('d M Y') }}?\n\nThe bill\'s paid and due amounts will be recalculated.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="padding:3px 8px;color:var(--sc-red)" title="Delete payment"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- SIDEBAR -->
<div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-circle-info"></i> Bill Info</div>
        <div class="card-body">
            @php $infoRows = [
                ['Bill No.', $bill->bill_no],
                ['Created By', $bill->createdBy?->name ?? '—'],
                ['Encounter', $bill->encounter ? $bill->encounter->encounter_no : '—'],
                ['Payment Mode', $bill->payment_method ?: '—'],
            ]; @endphp
            <form method="POST" action="{{ route('billing.date', $bill) }}" style="display:flex;justify-content:space-between;align-items:center;gap:6px;padding:5px 0;border-bottom:1px solid var(--sc-border);font-size:13px;margin:0">
                @csrf
                <span class="text-muted">Bill Date</span>
                <span style="display:flex;gap:4px;align-items:center">
                    <input type="date" name="bill_date" class="form-control" style="padding:3px 6px;font-size:12.5px;width:auto" value="{{ $bill->bill_date->toDateString() }}" max="{{ today()->toDateString() }}" required>
                    <button type="submit" class="btn btn-outline btn-sm" style="padding:3px 8px" title="Save bill date"><i class="fa-solid fa-check"></i></button>
                </span>
            </form>
            @foreach($infoRows as [$k,$v])
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--sc-border);font-size:13px">
                <span class="text-muted">{{ $k }}</span>
                <span class="fw-600 mono">{{ $v }}</span>
            </div>
            @endforeach
            @if($bill->notes)
            <div style="margin-top:10px;font-size:12px;color:var(--sc-muted)"><strong>Notes:</strong> {{ $bill->notes }}</div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-pen-to-square"></i> Change Status</div>
        <div class="card-body">
            <form method="POST" action="{{ route('billing.status', $bill) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Bill Status</label>
                    <select name="status" class="form-control">
                        @foreach(['Unpaid','Partial','Paid','Refunded'] as $status)
                            <option value="{{ $status }}" {{ $bill->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                @if($bill->due_amount > 0)
                <div class="form-group">
                    <label class="form-label">Payment Method if Marking Paid</label>
                    <select name="payment_method" class="form-control">
                        @foreach(['Cash','Card','UPI','NEFT','Cheque'] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Date if Marking Paid</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}">
                </div>
                @endif
                <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center">
                    <i class="fa-solid fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>

    @if(auth()->user()->isOwner())
    <div class="card mb-3">
        <div class="card-body" style="padding:12px">
            <form method="POST" action="{{ route('billing.destroy', $bill) }}" style="margin:0"
                  onsubmit="return confirm('Delete bill {{ $bill->bill_no }} (₹{{ number_format($bill->total_amount, 2) }}) and all its payments?\n\nUse this only for a bill entered by mistake, e.g. a duplicate. This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;color:var(--sc-red);border-color:var(--sc-red)">
                    <i class="fa-solid fa-trash"></i> Delete Bill
                </button>
            </form>
        </div>
    </div>
    @endif

    <div class="card mb-3">
        <div class="card-body" style="padding:12px">
            <a href="{{ route('billing.create', ['patient_id' => $bill->patient_id]) }}" class="btn btn-outline" style="width:100%;justify-content:center">
                <i class="fa-solid fa-file-invoice"></i> New Bill for This Patient
            </a>
        </div>
    </div>

    @if($bill->due_amount > 0)
    <div class="card" style="border:2px solid var(--sc-red)">
        <div class="card-header" style="background:rgba(232,76,76,.06)"><i class="fa-solid fa-circle-exclamation" style="color:var(--sc-red)"></i> Payment Due</div>
        <div class="card-body text-center">
            <div style="font-size:28px;font-weight:700;color:var(--sc-red)">₹{{ number_format($bill->due_amount, 2) }}</div>
            <div class="text-muted text-small mb-3">Outstanding balance</div>
            <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="document.getElementById('payment-modal').classList.add('open')">
                <i class="fa-solid fa-money-bill"></i> Record Payment
            </button>
        </div>
    </div>
    @else
    <div class="card" style="border:2px solid var(--sc-green)">
        <div class="card-body text-center" style="padding:20px">
            <i class="fa-solid fa-circle-check" style="font-size:32px;color:var(--sc-green);display:block;margin-bottom:8px"></i>
            <div class="fw-600" style="color:var(--sc-green)">Fully Paid</div>
        </div>
    </div>
    @endif
</div>
</div>

<!-- Add Payment Modal -->
@if($bill->due_amount > 0)
<div class="modal-overlay" id="payment-modal">
    <div class="modal-box" style="max-width:440px">
        <div class="modal-header">
            <i class="fa-solid fa-money-bill" style="color:var(--sc-teal)"></i>
            <h3>Record Payment</h3>
            <button class="modal-close" onclick="document.getElementById('payment-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('billing.payment', $bill) }}">
            @csrf
            <div class="modal-body">
                <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> Due amount: <strong>₹{{ number_format($bill->due_amount, 2) }}</strong></div>
                <div class="form-group">
                    <label class="form-label">Amount (₹) <span class="req">*</span></label>
                    <input type="number" name="amount" class="form-control" value="{{ $bill->due_amount }}" max="{{ $bill->due_amount }}" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method <span class="req">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        @foreach(['Cash','Card','UPI','NEFT','Cheque'] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Date <span class="req">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Transaction Reference</label>
                    <input type="text" name="transaction_ref" class="form-control" placeholder="UPI Ref, Cheque No., etc.">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('payment-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Record Payment</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Email Bill modal --}}
<div id="email-bill-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-envelope" style="color:var(--sc-teal)"></i>
            <h3>Email Bill</h3>
            <button class="modal-close" onclick="document.getElementById('email-bill-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('bills.email', $bill) }}">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">To <span class="req">*</span></label>
                    <input type="email" name="to" class="form-control" value="{{ $bill->patient->email ?? '' }}" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Auto-filled if left blank">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Auto-filled if left blank"></textarea>
                </div>
                <div class="text-muted" style="font-size:11px"><i class="fa-solid fa-info-circle"></i> The bill PDF is attached automatically. Make sure SMTP / mail settings are configured in .env (MAIL_*).</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('email-bill-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
            </div>
        </form>
    </div>
</div>

<div id="whatsapp-bill-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-brands fa-whatsapp" style="color:#25D366"></i>
            <h3>Send {{ $bill->status === 'Paid' ? 'Receipt' : 'Bill' }} via WhatsApp</h3>
            <button class="modal-close" onclick="document.getElementById('whatsapp-bill-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('bills.whatsapp', $bill) }}">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Send to <span class="req">*</span></label>
                    <input type="text" name="phone" class="form-control" value="{{ $bill->patient->phone ?? $bill->patient->alt_phone ?? '' }}" required>
                    <div class="text-muted" style="font-size:11px;margin-top:4px">Defaults to the patient's number — edit to send to a different one.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-bill-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-brands fa-whatsapp"></i> Send</button>
            </div>
        </form>
    </div>
</div>
@endsection
