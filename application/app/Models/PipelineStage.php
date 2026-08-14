<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'sort_order', 'sla_hours', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function leads()
    {
        return $this->hasMany(QuoteRequest::class, 'current_stage_id');
    }
}
