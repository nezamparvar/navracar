<?php

namespace App\Models;

use App\Services\VehiclePricing\VehiclePricingCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteRequest extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name', 'phone', 'email', 'notes', 'car_label', 'category', 'temperature',
        'breakdown_json', 'totals_json', 'total_with_profit', 'email_sent', 'source',
        'budget_range', 'country', 'city', 'assigned_to', 'created_by', 'follow_up_status',
        'current_stage_id', 'loss_reason', 'next_call_date', 'ip_address', 'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'next_call_date' => 'date',
            'email_sent' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function assignee()
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'current_stage_id');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class, 'request_id')->latest('created_at');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'request_id');
    }

    public function breakdown(): array
    {
        return json_decode($this->breakdown_json ?? '[]', true) ?: [];
    }

    public function breakdownForDisplay(): array
    {
        return array_filter($this->breakdown(), fn (array $row) => ($row['key'] ?? '') !== 'service_fee');
    }

    public function totals(): array
    {
        $decoded = json_decode($this->totals_json ?? '{}', true) ?: [];

        return isset($decoded['display']) && is_array($decoded['display'])
            ? $decoded['display']
            : $decoded;
    }

    public function pricingMetadata(): array
    {
        $decoded = json_decode($this->totals_json ?? '{}', true) ?: [];

        return isset($decoded['pricing_snapshot']) ? $decoded : [];
    }

    public function categoryLabel(): string
    {
        return VehiclePricingCatalog::CATEGORIES[$this->category]['label'] ?? (string) $this->category;
    }
}
