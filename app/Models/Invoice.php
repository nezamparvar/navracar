<?php

namespace App\Models;

use App\Services\VehiclePricing\VehiclePricingCatalog;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public $timestamps = false;

    public const CURRENCIES = [
        'toman' => 'تومان',
        'aed' => 'درهم (AED)',
        'usd' => 'دلار (USD)',
    ];

    protected $fillable = [
        'request_id', 'invoice_number', 'customer_name', 'customer_phone', 'customer_address',
        'customer_email', 'car_label', 'category', 'breakdown_json', 'total_amount', 'discount_amount',
        'currency', 'exchange_rate', 'valid_until', 'payment_terms', 'invoice_type', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'valid_until' => 'date',
        ];
    }

    public function request()
    {
        return $this->belongsTo(QuoteRequest::class, 'request_id');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function breakdown(): array
    {
        $decoded = json_decode($this->breakdown_json ?? '[]', true) ?: [];

        return isset($decoded['rows']) && is_array($decoded['rows'])
            ? $decoded['rows']
            : $decoded;
    }

    public function breakdownForDisplay(): array
    {
        return array_filter($this->breakdown(), fn (array $row) => ($row['key'] ?? '') !== 'service_fee');
    }

    public function pricingMetadata(): array
    {
        $decoded = json_decode($this->breakdown_json ?? '[]', true) ?: [];

        return isset($decoded['pricing_mode']) ? $decoded : [];
    }

    public function categoryLabel(): string
    {
        return VehiclePricingCatalog::CATEGORIES[$this->category]['label'] ?? (string) $this->category;
    }

    public function payableAmount(): float
    {
        return (float) $this->total_amount - (float) $this->discount_amount;
    }
}
