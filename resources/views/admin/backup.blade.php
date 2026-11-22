@extends('layouts.app')
@section('title', 'Backup & Export')
@section('page-title', 'Backup & Export')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Backup &amp; Export</h1></div>
    <form method="POST" action="{{ route('admin.backup.run') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-down"></i> Run Backup Now</button>
    </form>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-database"></i> Database Backups</div>
        <div class="card-body" style="font-size:12.5px;color:var(--sc-muted);margin-bottom:8px">
            Click "Run Backup Now" to create a fresh gzipped MySQL dump. Files are kept for 30 days. To schedule daily backups, add this to crontab on the server:
            <pre style="font-size:11px;background:var(--sc-bg);padding:6px 10px;border-radius:6px;margin-top:6px">0 2 * * *  cd {{ base_path() }} && php artisan specialitycare:backup &gt;/dev/null 2&gt;&amp;1</pre>
        </div>
        @if($files->isEmpty())
            <div class="card-body" style="padding:0">
                <x-empty-state compact icon="fa-database" title="No backups yet" message="Click Run Backup Now to create one." />
            </div>
        @else
        <form method="POST" action="{{ route('admin.backup.delete') }}" id="backupDeleteForm"
              onsubmit="return confirm('Delete ' + this.querySelectorAll('.bk-cb:checked').length + ' selected backup(s)? This cannot be undone.')">
        @csrf
        <div class="card-body" style="display:flex;align-items:center;gap:10px;padding-top:0">
            <span class="text-small text-muted"><span id="bkCount">{{ $files->count() }}</span> saved backup(s) · <span id="bkSel">0</span> selected</span>
            <button type="submit" id="bkDeleteBtn" class="btn btn-sm btn-danger" style="margin-left:auto" disabled>
                <i class="fa-solid fa-trash"></i> Delete Selected
            </button>
        </div>
        <table class="sc-table">
            <thead>
                <tr>
                    <th style="width:28px"><input type="checkbox" id="bkAll" title="Select all"></th>
                    <th>File</th><th>Size</th><th>When</th><th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($files as $f)
            <tr>
                <td><input type="checkbox" class="bk-cb" name="names[]" value="{{ $f->name }}"></td>
                <td class="mono text-small">{{ $f->name }}</td>
                <td>{{ number_format($f->size / 1024, 0) }} KB</td>
                <td class="text-small">{{ $f->mtime->format('d M Y H:i') }} <span class="text-muted">({{ $f->mtime->diffForHumans() }})</span></td>
                <td><a href="{{ route('admin.backup.download', $f->name) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-download"></i></a></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </form>
        <script>
        (function () {
            var all = document.getElementById('bkAll'), btn = document.getElementById('bkDeleteBtn'),
                sel = document.getElementById('bkSel'), cbs = document.querySelectorAll('.bk-cb');
            function sync() {
                var n = document.querySelectorAll('.bk-cb:checked').length;
                sel.textContent = n; btn.disabled = n === 0;
                all.checked = n > 0 && n === cbs.length; all.indeterminate = n > 0 && n < cbs.length;
            }
            all.addEventListener('change', function () { cbs.forEach(function (c) { c.checked = all.checked; }); sync(); });
            cbs.forEach(function (c) { c.addEventListener('change', sync); });
        })();
        </script>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-file-csv"></i> CSV Exports</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <p style="font-size:12.5px;color:var(--sc-muted);margin:0 0 6px">
                Each download streams the table directly — large tables are chunked.
            </p>
            @foreach([
                ['patients',     'fa-users',          'Patients (master)'],
                ['encounters',   'fa-stethoscope',    'Encounters'],
                ['prescriptions','fa-prescription',   'Prescriptions'],
                ['lab_orders',   'fa-flask',          'Lab Orders'],
                ['bills',        'fa-file-invoice-dollar','Bills'],
                ['admissions',   'fa-bed-pulse',      'Admissions'],
                ['appointments', 'fa-calendar-check', 'Appointments'],
            ] as [$t, $i, $lbl])
            <a href="{{ route('admin.export.csv', $t) }}" class="btn btn-outline" style="justify-content:flex-start">
                <i class="fa-solid {{ $i }}"></i> Export {{ $lbl }} CSV
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
