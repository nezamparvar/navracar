<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobilePushDelivery extends Model
{
    protected $fillable = [
        'mobile_push_notification_id', 'mobile_app_installation_id', 'status', 'error_code', 'sent_at', 'opened_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'opened_at' => 'datetime'];
    }
}
