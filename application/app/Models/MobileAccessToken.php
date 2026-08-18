<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileAccessToken extends Model
{
    protected $fillable = ['token_hash', 'name', 'last_used_at', 'expires_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function mobileCustomer()
    {
        return $this->belongsTo(MobileCustomer::class);
    }
}
