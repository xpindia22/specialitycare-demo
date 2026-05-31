<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseHeartbeat extends Model
{
    public $timestamps = false; // only created_at, no updated_at
    protected $fillable = [
        'issued_license_id', 'install_id', 'host', 'ip', 'app_version', 'created_at',
    ];
    protected $casts = ['created_at' => 'datetime'];

    public function license(): BelongsTo
    {
        return $this->belongsTo(IssuedLicense::class, 'issued_license_id');
    }
}
