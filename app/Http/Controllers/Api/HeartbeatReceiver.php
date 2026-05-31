<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IssuedLicense;
use App\Models\LicenseHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives license heartbeats from buyer installs.
 *
 * Endpoint is public (no auth) — buyer installs cannot hold an auth
 * credential to use here. Spoofing risk is low: the receiver only
 * accepts known license_ids and the worst a spoofer can do is pollute
 * heartbeat logs for a license they already know about. Cloning
 * detection still works because legitimate installs continue to send
 * their genuine install_ids.
 */
class HeartbeatReceiver extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'license_id'  => 'required|integer',
            'install_id'  => 'required|string|max:64',
            'host'        => 'nullable|string|max:200',
            'app_version' => 'nullable|string|max:30',
        ]);

        $license = IssuedLicense::find($data['license_id']);
        if (!$license) {
            return response()->json(['ok' => false, 'reason' => 'unknown_license'], 404);
        }

        LicenseHeartbeat::create([
            'issued_license_id' => $license->id,
            'install_id'        => $data['install_id'],
            'host'              => $data['host'] ?? null,
            'ip'                => $request->ip(),
            'app_version'       => $data['app_version'] ?? null,
            'created_at'        => now(),
        ]);

        return response()->json([
            'ok'     => true,
            'status' => $license->status, // active|revoked|expired
        ]);
    }
}
