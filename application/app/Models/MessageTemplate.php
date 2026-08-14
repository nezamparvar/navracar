<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['title', 'category', 'body', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public const CATEGORIES = [
        'initial_response' => 'پاسخ اولیه',
        'car_list' => 'لیست خودرو',
        'cost_breakdown' => 'برآورد هزینه',
        'contract_draft' => 'پیش‌نویس قرارداد',
        'follow_up_hot' => 'پیگیری داغ',
        'follow_up_warm' => 'پیگیری معمولی',
        'follow_up_cold' => 'پیگیری سرد',
        'custom' => 'سفارشی',
    ];
}
