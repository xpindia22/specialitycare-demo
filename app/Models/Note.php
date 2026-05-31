<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model {
    protected $fillable = ['title', 'body', 'is_locked', 'created_by'];
    protected $casts = ['is_locked' => 'boolean'];

    public function author() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
