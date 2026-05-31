<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\IssuedLicense;
use App\Models\LicenseHeartbeat;
use Illuminate\Support\Facades\DB;

class HeartbeatsController extends Controller
{
    public function index()
    {
        // Per-license summary: how many distinct install_ids and hosts have
        // checked in over the last 14 days. Anything > 1 distinct install_id
        // OR > 1 distinct host for the same install_id is a clone signal.
        $since = now()->subDays(14);

        $rows = LicenseHeartbeat::selectRaw('
                issued_license_id,
                COUNT(*) as total,
                COUNT(DISTINCT install_id) as distinct_installs,
                COUNT(DISTINCT host) as distinct_hosts,
                COUNT(DISTINCT ip) as distinct_ips,
                MAX(created_at) as last_seen,
                MIN(created_at) as first_seen
            ')
            ->where('created_at', '>=', $since)
            ->groupBy('issued_license_id')
            ->orderByDesc('distinct_installs')
            ->orderByDesc('last_seen')
            ->get();

        $licenses = IssuedLicense::with('buyer')->whereIn('id', $rows->pluck('issued_license_id'))->get()->keyBy('id');

        // For licenses with multiple installs, fetch the latest per-install
        // breakdown so the UI can show who's pirating what.
        $perInstall = LicenseHeartbeat::selectRaw('
                issued_license_id, install_id, host, ip,
                MAX(created_at) as last_seen, COUNT(*) as hits
            ')
            ->where('created_at', '>=', $since)
            ->whereIn('issued_license_id', $rows->where('distinct_installs', '>', 1)->pluck('issued_license_id'))
            ->groupBy('issued_license_id', 'install_id', 'host', 'ip')
            ->orderByDesc('last_seen')
            ->get()
            ->groupBy('issued_license_id');

        return view('owner.heartbeats.index', compact('rows', 'licenses', 'perInstall', 'since'));
    }
}
