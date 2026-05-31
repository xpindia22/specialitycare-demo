<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuedLicense extends Model
{
    protected $fillable = [
        'buyer_id', 'modules', 'issued_at', 'expires_at',
        'payment_amount', 'payment_currency', 'payment_received_at', 'payment_reference', 'payment_notes',
        'license_json', 'license_sig', 'public_key_fingerprint',
        'revoked_at', 'revoke_reason', 'created_by',
    ];

    protected $casts = [
        'modules'             => 'array',
        'issued_at'           => 'datetime',
        'expires_at'          => 'datetime',
        'payment_received_at' => 'date',
        'payment_amount'      => 'decimal:2',
        'revoked_at'          => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at) return 'revoked';
        if ($this->expires_at && $this->expires_at->isPast()) return 'expired';
        return 'active';
    }
}
