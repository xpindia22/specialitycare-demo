{{-- Partial: single uploaded report row. Expects $report and $encounter --}}
<div class="report-row">
    <div class="report-icon">
        <i class="fa-solid {{ str_contains($report->mime_type ?? '', 'pdf') ? 'fa-file-pdf' : (str_contains($report->mime_type ?? '', 'image') ? 'fa-file-image' : 'fa-file-lines') }}"></i>
    </div>
    <div style="min-width:0;flex:1">
        <div class="report-title">{{ $report->title ?: $report->original_name }}</div>
        <div class="report-meta">{{ $report->file_size_label }} · {{ $report->created_at->format('d M Y h:i A') }}</div>
        @if($report->notes)<div class="text-small mt-1">{{ $report->notes }}</div>@endif
    </div>
    <div class="report-actions">
        <a href="{{ asset('storage/'.$report->file_path) }}" target="_blank" class="btn btn-sm btn-outline" title="View"><i class="fa-solid fa-eye"></i></a>
        <a href="{{ asset('storage/'.$report->file_path) }}" download="{{ $report->original_name }}" class="btn btn-sm btn-outline" title="Download"><i class="fa-solid fa-download"></i></a>
        <form method="POST" action="{{ route('encounters.reports.delete', [$encounter, $report]) }}" style="display:inline" onsubmit="return confirm('Delete this file?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline" title="Delete"><i class="fa-solid fa-trash"></i></button>
        </form>
    </div>
</div>
