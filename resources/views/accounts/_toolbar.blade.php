{{-- Accounts quick-action toolbar. Included at the top of every accounts
     page so the Quick Expense / Pay Salary / … icons are always reachable
     without going back to the dashboard. Styles mirror the dashboard's
     acc-btn (filled, white text, hover-darken). Kept self-contained so it
     renders identically on any subpage. --}}
<style>
    .acc-toolbar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .acc-toolbar .acc-btn { font-weight:600; color:#fff !important; border:1px solid transparent; transition:transform .12s, box-shadow .12s, filter .15s; }
    .acc-toolbar .acc-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.12); filter:brightness(0.88); color:#fff !important; }
    .acc-toolbar .acc-btn i { color:#fff !important; }
    .acc-toolbar .acc-btn.home      { background:#0f172a; }   /* dashboard  */
    .acc-toolbar .acc-btn.expense   { background:#b91c1c; }   /* dark red   */
    .acc-toolbar .acc-btn.salary    { background:#b45309; }   /* dark amber */
    .acc-toolbar .acc-btn.supplier  { background:#0369a1; }   /* dark sky   */
    .acc-toolbar .acc-btn.voucher   { background:#6d28d9; }   /* dark purple*/
    .acc-toolbar .acc-btn.payees    { background:#15803d; }   /* dark green */
    .acc-toolbar .acc-btn.suppliers { background:#1d4ed8; }   /* dark blue  */
    .acc-toolbar .acc-btn.ledgers   { background:#334155; }   /* dark slate */
    .acc-toolbar .acc-btn.recurring { background:#9333ea; }   /* dark violet*/
    .acc-toolbar .acc-btn.settings  { background:#4b5563; }   /* dark grey  */
</style>
<div class="acc-toolbar">
    <a href="{{ route('accounts.index') }}"                  class="btn acc-btn home"><i class="fa-solid fa-book"></i> Accounts</a>
    <a href="{{ route('accounts.expenses.quick') }}"         class="btn acc-btn expense"><i class="fa-solid fa-receipt"></i> Quick Expense</a>
    <a href="{{ route('accounts.expenses.salary') }}"        class="btn acc-btn salary"><i class="fa-solid fa-money-bill-wave"></i> Pay Salary</a>
    <a href="{{ route('accounts.expenses.pay-supplier') }}"  class="btn acc-btn supplier"><i class="fa-solid fa-truck-fast"></i> Pay Supplier</a>
    <a href="{{ route('accounts.vouchers.create','PAY') }}"  class="btn acc-btn voucher"><i class="fa-solid fa-plus"></i> Voucher</a>
    <a href="{{ route('accounts.payees.index') }}"           class="btn acc-btn payees"><i class="fa-solid fa-people-group"></i> Payees</a>
    <a href="{{ route('accounts.suppliers.index') }}"        class="btn acc-btn suppliers"><i class="fa-solid fa-truck-fast"></i> Suppliers</a>
    <a href="{{ route('accounts.ledgers.index') }}"          class="btn acc-btn ledgers"><i class="fa-solid fa-list"></i> Ledgers</a>
    <a href="{{ route('accounts.recurring.index') }}"        class="btn acc-btn recurring"><i class="fa-solid fa-repeat"></i> Recurring</a>
    <a href="{{ route('accounts.settings.edit') }}"          class="btn acc-btn settings"><i class="fa-solid fa-gear"></i></a>
    {{-- Optional page-specific action(s), appended into the same row.
         Pass via @include('accounts._toolbar', ['extra' => [
             ['url' => route(...), 'icon' => 'fa-plus', 'label' => 'New Ledger', 'bg' => '#0d9488'],
         ]]) --}}
    @foreach($extra ?? [] as $x)
        <a href="{{ $x['url'] }}" class="btn acc-btn" style="background:{{ $x['bg'] ?? '#0d9488' }}"><i class="fa-solid {{ $x['icon'] ?? 'fa-plus' }}"></i> {{ $x['label'] }}</a>
    @endforeach
</div>
