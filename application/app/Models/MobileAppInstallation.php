<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileAppInstallation extends Model
{
    protected $fillable = [
        'installation_id', 'secret_hash', 'mobile_customer_id', 'analytics_consent', 'notifications_consent',
        'device_manufacturer', 'device_model', 'platform', 'os_version', 'app_version', 'locale',
        'country', 'city', 'acquisition_source', 'acquisition_campaign', 'last_seen_at',
        'push_token', 'push_token_hash', 'push_token_updated_at',
    ];

    protected $hidden = ['secret_hash', 'push_token', 'push_token_hash'];

    protected function casts(): array
    {
        return [
            'analytics_consent' => 'boolean',
            'notifications_consent' => 'boolean',
            'last_seen_at' => 'datetime',
            'push_token' => 'encrypted',
            'push_token_updated_at' => 'datetime',
        ];
    }

    public function events()
    {
        return $this->hasMany(MobileAnalyticsEvent::class);
    }

    public function customer()
    {
        return $this->belongsTo(MobileCustomer::class, 'mobile_customer_id');
    }
}
