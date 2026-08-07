<?php

namespace Database\Seeders;

use App\Models\LossReason;
use Illuminate\Database\Seeder;

class LossReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            'قیمت مناسب نبود',
            'از رقیب خرید کرد',
            'منصرف شد / نیاز برطرف شد',
            'عدم پاسخگویی مشتری',
            'شرایط اقامت/مجوز مناسب نبود',
            'سایر',
        ];

        foreach ($reasons as $i => $reason) {
            LossReason::updateOrCreate(['id' => $i + 1], ['reason' => $reason]);
        }
    }
}
