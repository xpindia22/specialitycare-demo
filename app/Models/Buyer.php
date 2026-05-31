<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    protected $fillable = [
        'name', 'contact_name', 'contact_email', 'contact_phone',
        'city', 'state', 'address', 'notes',
        'public_key', 'private_key', 'keypair_generated_at',
        'default_grace_seconds',
    ];

    /**
     * private_key is encrypted at rest by Laravel using APP_KEY. Anyone
     * with shell access to the DB can't read raw private keys without
     * also knowing APP_KEY.
     */
    protected $casts = [
        'private_key'           => 'encrypted',
        'keypair_generated_at'  => 'datetime',
    ];

    protected $hidden = ['private_key'];

    protected static function booted(): void
    {
        // New buyer rows auto-get a unique ed25519 keypair so that licenses
        // signed for one buyer can't validate on another buyer's install.
        static::creating(function (Buyer $b) {
            if (empty($b->public_key) || empty($b->private_key)) {
                $b->generateKeypair(persist: false);
            }
        });
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(IssuedLicense::class)->latest('issued_at');
    }

    public function activeLicense(): ?IssuedLicense
    {
        return $this->licenses()
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    /**
     * Generate a fresh ed25519 keypair for this buyer.
     * Pass persist: true to save immediately; false leaves it on the
     * model for the parent ::create() flow to handle.
     */
    public function generateKeypair(bool $persist = true): void
    {
        $kp = sodium_crypto_sign_keypair();
        $this->public_key = base64_encode(sodium_crypto_sign_publickey($kp));
        $this->private_key = base64_encode(sodium_crypto_sign_secretkey($kp));
        $this->keypair_generated_at = now();
        if ($persist) $this->save();
    }

    public function publicKeyRaw(): string
    {
        return base64_decode((string) $this->public_key, true) ?: '';
    }

    public function privateKeyRaw(): string
    {
        return base64_decode((string) $this->private_key, true) ?: '';
    }

    public function publicKeyFingerprint(): string
    {
        $raw = $this->publicKeyRaw();
        return $raw === '' ? '' : substr(hash('sha256', $raw), 0, 16);
    }
}
