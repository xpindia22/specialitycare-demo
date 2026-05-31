<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'user_name', 'action', 'description', 'ip_address'];
    protected $casts = ['created_at' => 'datetime'];

    public static function log(string $action, string $description): void
    {
        static::create([
            'user_id'     => auth()->id(),
            'user_name'   => auth()->user()?->name ?? 'System',
            'action'      => $action,
            'description' => $description,
            'ip_address'  => request()->ip(),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
