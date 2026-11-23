<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HeartbeatService;
use App\Services\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Read + manage "Modules / License" page for buyers under Administration.
 *
 * Shows what's enabled on this install AND lets a buyer install or
 * replace the license without shell access — the form accepts the
 * public key + a license zip (json + sig), verifies the signature
 * against the entered key, and writes everything into storage/license/.
 *
 * Activation still requires a license signed by the software owner's
 * private key — buyers cannot enable modules just by ticking boxes.
 */
class ModulesViewController extends Controller
{
    public function index(LicenseService $license, HeartbeatService $hb)
    {
        // Auto-refresh license status from the licensor when this page
        // is opened, bypassing the normal 55-min opportunistic throttle.
        // A small 30s page-local throttle keeps a spam-refresh from
        // hammering the receiver. Best-effort — if the receiver is slow
        // or offline, fall through and render whatever status the
        // install already had cached.
        $stamp = (string) config('modules.last_heartbeat_path');
        $age = is_file($stamp) ? (time() - filemtime($stamp)) : PHP_INT_MAX;
        if ($age > 30) {
            try { $hb->sendIfDue(true); } catch (\Throwable) {}
            // sendIfDue may have just rewritten revocation_status; drop the
            // payload memo so the status() call below sees the latest state.
            $license->clearCache();
        }

        $payload = $license->payload();
        $status  = $license->status();
        $enabled = $license->enabledModules();
        $catalog = (array) config('modules.catalog', []);

        return view('admin.modules', [
            'payload'         => $payload,
            'status'          => $status,
            'enabled'         => $enabled,
            'catalog'         => $catalog,
            'publicKeySource' => $this->publicKeySource(),
            'currentPubKey'   => (string) config('modules.public_key', ''),
            'heartbeatUrl'    => (string) config('modules.heartbeat_url', ''),
        ]);
    }

    /**
     * Install or replace the license. Accepts:
     *   - public_key : base64 ed25519 public key (32 bytes when decoded)
     *   - license_json : uploaded text/json file
     *   - license_sig  : uploaded text/sig file
     * Verifies the signature against the entered key BEFORE persisting
     * anything. On success, writes license.{json,sig} and buyer_config.json
     * and triggers one heartbeat so the receiver-cached status refreshes.
     */
    public function installLicense(Request $request, HeartbeatService $hb): RedirectResponse
    {
        $request->validate([
            'public_key'   => 'required|string|max:200',
            'license_json' => 'required|file|max:50',  // ~few hundred bytes typically
            'license_sig'  => 'required|file|max:10',  // 88-byte base64 sig
        ]);

        $pubB64 = trim((string) $request->input('public_key'));
        $pub = base64_decode($pubB64, true);
        if ($pub === false || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw ValidationException::withMessages([
                'public_key' => 'Public key is not a valid base64-encoded ed25519 key (32 bytes). Copy it exactly from your buyer detail page.',
            ]);
        }

        $jsonBytes = (string) file_get_contents($request->file('license_json')->getRealPath());
        $sigB64    = trim((string) file_get_contents($request->file('license_sig')->getRealPath()));
        $sig       = base64_decode($sigB64, true);

        if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw ValidationException::withMessages([
                'license_sig' => 'Signature file is malformed (not 64-byte base64). Upload license.sig exactly as it came in the zip.',
            ]);
        }

        try {
            $ok = sodium_crypto_sign_verify_detached($sig, $jsonBytes, $pub);
        } catch (\Throwable $e) {
            Log::warning('License install: signature check threw', ['err' => $e->getMessage()]);
            $ok = false;
        }
        if (!$ok) {
            throw ValidationException::withMessages([
                'license_json' => 'License signature does not match this public key. Either the key is from a different buyer or the license file was edited.',
            ]);
        }

        $payload = json_decode($jsonBytes, true);
        if (!is_array($payload)) {
            throw ValidationException::withMessages([
                'license_json' => 'License file is not valid JSON.',
            ]);
        }

        $licenseDir = dirname((string) config('modules.license_path'));
        if (!is_dir($licenseDir)) @mkdir($licenseDir, 0770, true);

        // Backup whatever's there (don't blindly overwrite a working license).
        $stamp = date('Ymd_His');
        foreach (['license.json', 'license.sig'] as $name) {
            $live = $licenseDir . '/' . $name;
            if (is_file($live)) {
                @copy($live, $licenseDir . "/{$name}.bak.{$stamp}");
            }
        }

        // Atomic-ish replace: write to .tmp then rename.
        $jsonPath = (string) config('modules.license_path');
        $sigPath  = (string) config('modules.signature_path');
        file_put_contents($jsonPath . '.tmp', $jsonBytes);
        file_put_contents($sigPath . '.tmp',  $sigB64 . "\n");
        @chmod($jsonPath . '.tmp', 0640);
        @chmod($sigPath . '.tmp',  0640);
        rename($jsonPath . '.tmp', $jsonPath);
        rename($sigPath . '.tmp',  $sigPath);

        // Drop any cached revocation status — it referred to the previous
        // license_id and would otherwise lock the freshly-installed one
        // until the next heartbeat refreshes it.
        $revPath = (string) config('modules.revocation_status_path');
        if ($revPath !== '' && is_file($revPath)) @unlink($revPath);

        // Persist buyer config + install metadata. Public key + heartbeat
        // URL go to buyer_config.json (so the next request picks them up
        // without any .env edit). recordLicenseInstall also writes the
        // install timestamp into the settings table — that's a second
        // place the dead-man fallback can read from, so a buyer can't
        // reset the grace clock by deleting buyer_config.json alone.
        $license = app(LicenseService::class);
        $license->updateBuyerConfig([
            'public_key'    => $pubB64,
            // Primary license-auth host is jamesheartcare.com (clinic domain). jhcpl.in
            // points to the SAME server and keeps working for existing installs, so
            // either host validates; new installs default to jamesheartcare.com.
            'heartbeat_url' => (string) config('modules.heartbeat_url', '') ?: 'https://jamesheartcare.com/specialitycare/api/license/heartbeat',
        ]);
        $license->recordLicenseInstall(
            (int) ($payload['license_id'] ?? 0),
            (string) ($payload['licensee'] ?? '')
        );

        // CRITICAL: invalidate the per-request payload cache before the
        // forced heartbeat below. $hb was DI-built at request start with
        // the same LicenseService singleton, which already cached the
        // PREVIOUS license's payload. Without this clearCache(), the
        // heartbeat would POST the old license_id and write a stale
        // revocation_status referring to the wrong license — leaving
        // the new license unenforced until the next non-cached request.
        $license->clearCache();
        \Illuminate\Support\Facades\Config::set('modules.public_key', $pubB64);

        // Best-effort heartbeat so revocation_status repopulates quickly.
        try { $hb->sendIfDue(true); } catch (\Throwable) {}

        $modules = implode(', ', (array) ($payload['modules'] ?? []));
        return redirect()->route('admin.modules.index')
            ->with('success', "License installed for {$payload['licensee']} — modules: {$modules}.");
    }

    /**
     * Tell the view where the active public key came from, so the form
     * can warn an admin that uploading via UI here will be overridden
     * by their .env until they remove it.
     */
    private function publicKeySource(): string
    {
        if (((string) env('MODULES_PUBLIC_KEY', '')) !== '') return 'env';
        $cfg = (string) config('modules.buyer_config_path');
        if ($cfg !== '' && is_file($cfg)) {
            $j = json_decode((string) @file_get_contents($cfg), true);
            if (is_array($j) && !empty($j['public_key'])) return 'buyer_config';
        }
        return 'none';
    }
}
