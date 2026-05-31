<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\IssuedLicense;
use App\Services\LicenseSigner;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LicensesController extends Controller
{
    public function __construct(private readonly LicenseSigner $signer) {}

    public function index()
    {
        $licenses = IssuedLicense::with('buyer')->latest('issued_at')->paginate(50);
        return view('owner.licenses.index', compact('licenses'));
    }

    public function create(?Buyer $buyer = null)
    {
        $buyers  = Buyer::orderBy('name')->get();
        $catalog = (array) config('modules.catalog', []);
        return view('owner.licenses.create', compact('buyers', 'buyer', 'catalog'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'buyer_id'                => 'required|exists:buyers,id',
            'modules'                 => 'required|array|min:1',
            'modules.*'               => 'string|in:'.implode(',', array_keys((array) config('modules.catalog', []))),
            'expires_at'              => 'nullable|date|after:today',
            // Per-license override of the buyer's default dead-man grace.
            // Null = inherit buyer.default_grace_seconds; if that's also
            // null the install will use its own config default.
            'heartbeat_grace_seconds' => 'nullable|integer|min:0|max:31536000',
            'payment_amount'          => 'nullable|numeric|min:0',
            'payment_currency'        => 'nullable|string|max:8',
            'payment_received_at'     => 'nullable|date|before_or_equal:today',
            'payment_reference'       => 'nullable|string|max:120',
            'payment_notes'           => 'nullable|string|max:2000',
            'install_locally'         => 'nullable|boolean',
        ]);

        $buyer = Buyer::findOrFail($data['buyer_id']);
        if (empty($buyer->public_key) || empty($buyer->private_key)) {
            return back()->withErrors(['buyer_id' =>
                "Buyer '{$buyer->name}' has no keypair yet. Open the buyer page and click 'Generate Keypair' first."
            ])->withInput();
        }

        // Resolve grace: per-license form value > buyer default > unset (install uses its config).
        $grace = array_key_exists('heartbeat_grace_seconds', $data) && $data['heartbeat_grace_seconds'] !== null
            ? (int) $data['heartbeat_grace_seconds']
            : ($buyer->default_grace_seconds !== null ? (int) $buyer->default_grace_seconds : null);
        if ($grace !== null && $grace > 0 && $grace < 60) {
            return back()->withErrors(['heartbeat_grace_seconds' =>
                'Minimum positive grace is 60 seconds. Use 0 to disable dead-man enforcement entirely.'
            ])->withInput();
        }

        try {
            // First sign without license_id (we don't know it until insert).
            $signed = $this->signer->signForBuyer(
                $buyer,
                $data['modules'],
                !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
                null,
                $grace,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['private_key' => $e->getMessage()])->withInput();
        }

        $license = IssuedLicense::create([
            'buyer_id'              => $buyer->id,
            'modules'               => $signed['payload']['modules'],
            'issued_at'             => $signed['payload']['issued_at'],
            'expires_at'            => $signed['payload']['expires_at'],
            'payment_amount'        => $data['payment_amount'] ?? null,
            'payment_currency'      => $data['payment_currency'] ?? 'INR',
            'payment_received_at'   => $data['payment_received_at'] ?? null,
            'payment_reference'     => $data['payment_reference'] ?? null,
            'payment_notes'         => $data['payment_notes'] ?? null,
            'license_json'          => $signed['json'],
            'license_sig'           => $signed['signature_b64'],
            'public_key_fingerprint'=> $signed['fingerprint'],
            'created_by'            => auth()->id(),
        ]);

        // Re-sign with the just-assigned license_id so heartbeats can include it.
        try {
            $resigned = $this->signer->signForBuyer(
                $buyer,
                $data['modules'],
                !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
                $license->id,
                $grace,
            );
            $license->update([
                'license_json' => $resigned['json'],
                'license_sig'  => $resigned['signature_b64'],
            ]);
            $signed = $resigned;
        } catch (\RuntimeException) {
            // Initial sign succeeded; fail-safe to that one if re-sign blows up.
        }

        if (!empty($data['install_locally'])) {
            try {
                $this->signer->installLocally($signed['json'], $signed['signature_b64']);
            } catch (\RuntimeException $e) {
                return redirect()->route('owner.licenses.show', $license)
                    ->with('warning', "License saved but local install failed: ".$e->getMessage());
            }
        }

        return redirect()->route('owner.licenses.show', $license)
            ->with('success', 'License issued for '.$buyer->name.'.');
    }

    public function show(IssuedLicense $license)
    {
        $license->load('buyer', 'createdBy');
        return view('owner.licenses.show', compact('license'));
    }

    /**
     * Stream the license as a zip with json + sig + a README explaining where to drop them.
     */
    public function download(IssuedLicense $license)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'lic') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Cannot create archive');
        }
        $zip->addFromString('license.json', $license->license_json);
        $zip->addFromString('license.sig',  $license->license_sig);
        $zip->addFromString('README.txt', $this->readmeText($license));
        $zip->close();

        $filename = sprintf('specialitycare-license-%s-%s.zip',
            \Illuminate\Support\Str::slug($license->buyer->name),
            $license->issued_at->format('Ymd')
        );
        return response()->download($tmp, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    /**
     * Install this license to THIS server's storage/license/ — turns on the
     * configured modules on the install you're managing right now.
     */
    public function installLocally(IssuedLicense $license)
    {
        try {
            $this->signer->installLocally($license->license_json, $license->license_sig);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['install' => $e->getMessage()]);
        }
        return back()->with('success', 'License installed on this server. Modules updated.');
    }

    public function revoke(Request $request, IssuedLicense $license)
    {
        $data = $request->validate(['revoke_reason' => 'nullable|string|max:255']);
        $license->update([
            'revoked_at'    => now(),
            'revoke_reason' => $data['revoke_reason'] ?? null,
        ]);
        return back()->with('success', 'License marked as revoked in the ledger.');
    }

    public function unrevoke(IssuedLicense $license)
    {
        $license->update(['revoked_at' => null, 'revoke_reason' => null]);
        return back()->with('success', 'Revocation cleared.');
    }

    private function readmeText(IssuedLicense $license): string
    {
        $modules = implode(', ', $license->modules);
        $exp     = $license->expires_at ? $license->expires_at->format('d M Y') : 'never';
        $payload = json_decode($license->license_json, true);
        $grace   = is_array($payload) && array_key_exists('heartbeat_grace_seconds', $payload)
            ? (int) $payload['heartbeat_grace_seconds']
            : null;
        $graceTxt = $grace === null
            ? "(install default)"
            : ($grace === 0 ? "disabled (dead-man off)" : $this->humanSeconds($grace));
        return <<<TXT
SpecialityCare License File
============================

Licensee:   {$license->buyer->name}
Issued:     {$license->issued_at->format('d M Y')}
Expires:    {$exp}
Modules:    {$modules}
Dead-man:   {$graceTxt}
Fingerprint: {$license->public_key_fingerprint}

INSTALLATION
------------
1. Copy BOTH files (license.json and license.sig) to:
     <your-install-path>/storage/license/

2. Ensure the web server can read them:
     chmod 750 storage/license
     chmod 640 storage/license/license.json storage/license/license.sig
     chgrp -R www-data storage/license   (or whichever user runs PHP-FPM/Apache)

3. Set the following in your .env (values shown on the buyer detail page):
     MODULES_PUBLIC_KEY=<your buyer-specific public key>
     MODULES_HEARTBEAT_URL=https://demo.example.com/specialitycare/api/license/heartbeat

4. Clear Laravel config cache and reload PHP-FPM:
     php artisan config:clear
     sudo systemctl reload php8.3-fpm.service   (or your PHP-FPM unit)

5. Reload the app — enabled modules will appear in the sidebar.

SECURITY & ENFORCEMENT
----------------------
- Editing license.json invalidates the signature. ALL modules will be disabled.
- Do NOT share this license with other installations. The licensee field is
  recorded in the signed payload; sharing is traceable.
- This install heartbeats to the licensor automatically on every authenticated
  request (throttled). If your license is revoked upstream, modules disable
  on the next heartbeat — within seconds on an active install.
- Dead-man: if the install fails to heartbeat for {$graceTxt}, modules disable
  until a successful heartbeat is recorded.
- Lost the file? Contact us for a replacement.

TXT;
    }

    private function humanSeconds(int $s): string
    {
        if ($s % 86400 === 0) { $d = (int)($s/86400); return "$d day".($d===1?'':'s'); }
        if ($s % 3600  === 0) { $h = (int)($s/3600);  return "$h hour".($h===1?'':'s'); }
        if ($s % 60    === 0) { $m = (int)($s/60);    return "$m min".($m===1?'':'s'); }
        return "$s seconds";
    }
}
