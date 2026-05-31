<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;

class BuyersController extends Controller
{
    public function index()
    {
        $buyers = Buyer::with(['licenses' => fn ($q) => $q->latest('issued_at')->limit(1)])
            ->withCount('licenses')
            ->orderBy('name')
            ->get();
        return view('owner.buyers.index', compact('buyers'));
    }

    public function create()
    {
        return view('owner.buyers.form', ['buyer' => new Buyer()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateBuyer($request);
        $buyer = Buyer::create($data);
        return redirect()->route('owner.buyers.show', $buyer)->with('success', "Buyer '$buyer->name' added.");
    }

    public function show(Buyer $buyer)
    {
        $buyer->load(['licenses.createdBy']);
        return view('owner.buyers.show', compact('buyer'));
    }

    public function edit(Buyer $buyer)
    {
        return view('owner.buyers.form', compact('buyer'));
    }

    public function update(Request $request, Buyer $buyer)
    {
        $data = $this->validateBuyer($request);
        $buyer->update($data);
        return redirect()->route('owner.buyers.show', $buyer)->with('success', 'Buyer updated.');
    }

    /**
     * Generate (or rotate) the buyer's ed25519 keypair.
     * Rotating invalidates ALL past licenses signed with the old key —
     * the buyer's installs will need a fresh license + new public key in
     * their .env.
     */
    public function regenerateKeypair(Buyer $buyer)
    {
        $hadOne = !empty($buyer->public_key);
        $buyer->generateKeypair();

        return back()->with('success',
            $hadOne
                ? 'Keypair regenerated. ALL existing licenses for this buyer are now invalid — issue a fresh license and update their .env.'
                : 'Keypair generated. You can now issue licenses for this buyer.'
        );
    }

    private function validateBuyer(Request $request): array
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:200',
            'contact_name'          => 'nullable|string|max:120',
            'contact_email'         => 'nullable|email|max:160',
            'contact_phone'         => 'nullable|string|max:40',
            'city'                  => 'nullable|string|max:80',
            'state'                 => 'nullable|string|max:80',
            'address'               => 'nullable|string|max:500',
            'notes'                 => 'nullable|string|max:2000',
            // 0 = no dead-man enforcement; >0 = seconds of grace.
            // Minimum 60 when positive so a brief receiver outage doesn't
            // immediately lock the install. Max 1 year.
            'default_grace_seconds' => 'nullable|integer|min:0|max:31536000',
        ]);
        if (array_key_exists('default_grace_seconds', $data)
            && $data['default_grace_seconds'] !== null
            && $data['default_grace_seconds'] > 0
            && $data['default_grace_seconds'] < 60) {
            abort(redirect()->back()
                ->withErrors(['default_grace_seconds' => 'Minimum positive grace is 60 seconds. Use 0 to disable dead-man enforcement entirely.'])
                ->withInput());
        }
        return $data;
    }
}
