<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'car_label', 'category', 'real_price_aed', 'customs_price_aed', 'free_rate', 'customs_rate',
        'sea_freight_aed', 'permits_aed', 'storage_toman', 'sum_customs', 'sum_plate',
        'total_no_profit', 'service_profit', 'total_with_profit', 'country', 'city', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
