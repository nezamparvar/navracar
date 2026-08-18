<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobilePushNotification extends Model
{
    protected $fillable = [
        'created_by', 'title', 'body', 'data', 'segment', 'status', 'targeted_count', 'sent_count',
        'failed_count', 'opened_count', 'disabled_count', 'queued_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array', 'segment' => 'array', 'queued_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    public function deliveries()
    {
        return $this->hasMany(MobilePushDelivery::class);
    }
}
