<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VinCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vin', 'make', 'model', 'model_year', 'plant_country', 'verdict', 'source',
        'country', 'city', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
