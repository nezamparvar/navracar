<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['id' => 1, 'name' => 'سرنخ جدید', 'slug' => 'new-lead', 'sort_order' => 1, 'sla_hours' => 24],
            ['id' => 2, 'name' => 'لیست خودرو ارسال شد', 'slug' => 'car-list-sent', 'sort_order' => 2, 'sla_hours' => 24],
            ['id' => 3, 'name' => 'نیازسنجی', 'slug' => 'qualification', 'sort_order' => 3, 'sla_hours' => 48],
            ['id' => 4, 'name' => 'برآورد هزینه ارسال شد', 'slug' => 'cost-breakdown-sent', 'sort_order' => 4, 'sla_hours' => 48],
            ['id' => 5, 'name' => 'پیش‌نویس قرارداد ارسال شد', 'slug' => 'contract-draft-sent', 'sort_order' => 5, 'sla_hours' => 72],
            ['id' => 6, 'name' => 'مذاکره نهایی', 'slug' => 'final-negotiation', 'sort_order' => 6, 'sla_hours' => 48],
            ['id' => 7, 'name' => 'قرارداد امضا شد', 'slug' => 'contract-signed', 'sort_order' => 7, 'sla_hours' => 168],
            ['id' => 8, 'name' => 'در حال ترخیص / پرداخت', 'slug' => 'payment-customs', 'sort_order' => 8, 'sla_hours' => 336],
            ['id' => 9, 'name' => 'تحویل داده شد', 'slug' => 'delivered', 'sort_order' => 9, 'sla_hours' => 0],
            ['id' => 10, 'name' => 'از دست رفته', 'slug' => 'lost', 'sort_order' => 10, 'sla_hours' => 0],
        ];

        foreach ($stages as $stage) {
            PipelineStage::updateOrCreate(['id' => $stage['id']], $stage);
        }
    }
}
