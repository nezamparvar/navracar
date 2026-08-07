<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LossReason extends Model
{
    public $timestamps = false;

    protected $fillable = ['reason', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
