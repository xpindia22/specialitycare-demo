<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    protected $fillable = [
        'name', 'type', 'code', 'contact_person', 'contact_phone',
        'contact_email', 'address', 'gst_no', 'is_active',
    ];

    const TYPES = [
        'insurer' => 'Insurance Company',
        'tpa'     => 'TPA (Third-Party Admin)',
        'govt'    => 'Government Scheme',
    ];

    public function claims() { return $this->hasMany(InsuranceClaim::class); }
}
