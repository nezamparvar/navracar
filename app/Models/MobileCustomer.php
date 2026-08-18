<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class MobileCustomer extends Authenticatable
{
    protected $fillable = ['name', 'phone', 'email', 'password_hash'];

    protected $hidden = ['password_hash'];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function accessTokens()
    {
        return $this->hasMany(MobileAccessToken::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(CarListing::class, 'mobile_favorites')->withTimestamps();
    }

    public function quoteRequests()
    {
        return $this->hasMany(QuoteRequest::class);
    }
}
