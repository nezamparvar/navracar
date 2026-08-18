<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileAnalyticsEvent extends Model
{
    protected $fillable = [
        'mobile_app_installation_id', 'mobile_customer_id', 'name', 'page', 'properties', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array', 'occurred_at' => 'datetime'];
    }

    public function installation()
    {
        return $this->belongsTo(MobileAppInstallation::class, 'mobile_app_installation_id');
    }
}
