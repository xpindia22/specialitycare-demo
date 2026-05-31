<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Owner-only panel for inspecting + managing the module license.
 *
 * Auth model: a single OWNER_KEY env value gates the panel. The login
 * action sets a session flag; EnsureOwner middleware enforces it on
 * every other action. If OWNER_KEY isn't set, the panel returns 404
 * for every URL (including the login page) — so it doesn't exist on
 * buyer servers that don't have the env var.
 */
class ModulesController extends Controller
{
    public function __construct(private readonly LicenseService $license) {}

    /** Login page — only renders if OWNER_KEY is set, else 404. */
    public function loginForm(Request $request)
    {
        $key = (string) config('modules.owner_key', '');
        if (strlen($key) < 16) abort(404);
        if ($request->session()->get('owner_authed')) {
            return redirect()->route('owner.modules.index');
        }
        return view('owner.login');
    }

    public function login(Request $request)
    {
        $key = (string) config('modules.owner_key', '');
        if (strlen($key) < 16) abort(404);

        $given = (string) $request->input('owner_key', '');
        // Constant-time comparison to dodge timing attacks.
        if (!hash_equals($key, $given)) {
            return back()->withErrors(['owner_key' => 'Invalid key.']);
        }
        $request->session()->regenerate();
        $request->session()->put('owner_authed', true);
        // Bind the session to the current OWNER_KEY so rotating the key in
        // .env immediately invalidates every existing owner session
        // (EnsureOwner re-checks this hash on each request).
        $request->session()->put('owner_key_hash', hash('sha256', $key));
        return redirect()->route('owner.modules.index');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['owner_authed', 'owner_key_hash']);
        return redirect()->route('owner.login');
    }

    public function index()
    {
        $payload   = $this->license->payload();
        $status    = $this->license->status();
        $enabled   = $this->license->enabledModules();
        $catalog   = (array) config('modules.catalog', []);
        $hasPriv   = is_file((string) config('modules.private_key_path'));

        return view('owner.modules', compact('payload', 'status', 'enabled', 'catalog', 'hasPriv'));
    }

    /**
     * Sign + install a new license in one step. Only works when the
     * private key file is present on this server (i.e. on the owner's
     * machine). Buyer deployments don't have the key file, so this
     * action returns a clear error there.
     */
    public function sign(Request $request, \App\Services\LicenseSigner $signer)
    {
        $data = $request->validate([
            'licensee'   => 'required|string|max:200',
            'modules'    => 'required|array|min:1',
            'modules.*'  => 'string|in:'.implode(',', array_keys((array) config('modules.catalog', []))),
            'expires_at' => 'nullable|date|after:today',
        ]);

        try {
            $signed = $signer->sign(
                $data['licensee'],
                $data['modules'],
                !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            );
            $signer->installLocally($signed['json'], $signed['signature_b64']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['private_key' => $e->getMessage()]);
        }

        return redirect()->route('owner.modules.index')->with('success', 'License signed and installed.');
    }

    /**
     * Upload an externally-signed license.json + license.sig pair.
     * The new files are validated against the public key BEFORE replacing
     * the live ones — a bad upload won't kill working modules.
     */
    public function upload(Request $request, \App\Services\LicenseSigner $signer)
    {
        $request->validate([
            'license_json' => 'required|file|max:64',
            'license_sig'  => 'required|file|max:8',
        ]);

        $json   = (string) file_get_contents($request->file('license_json')->getRealPath());
        $sigB64 = trim((string) file_get_contents($request->file('license_sig')->getRealPath()));
        $sig    = base64_decode($sigB64, true);
        $pub    = base64_decode((string) config('modules.public_key'), true);

        if ($sig === false || $pub === false || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return back()->withErrors(['license_json' => 'Bad signature or public key not configured.']);
        }
        if (!sodium_crypto_sign_verify_detached($sig, $json, $pub)) {
            return back()->withErrors(['license_json' => 'Signature verification failed. Files do not match this install\'s public key.']);
        }
        if (!is_array(json_decode($json, true))) {
            return back()->withErrors(['license_json' => 'license.json is not valid JSON.']);
        }

        try {
            $signer->installLocally($json, $sigB64);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['license_json' => $e->getMessage()]);
        }

        Log::info('License uploaded via owner panel');
        return redirect()->route('owner.modules.index')->with('success', 'License uploaded and verified.');
    }
}
