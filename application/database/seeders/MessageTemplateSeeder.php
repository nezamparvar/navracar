<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'id' => 1,
                'title' => 'پاسخ اولیه به سرنخ جدید',
                'category' => 'initial_response',
                'body' => "سلام {{customer_name}} عزیز، وقت بخیر 🌹\nممنون از تماستون با ناوراکار. من {{salesperson_name}} هستم و مشاور شما برای واردات خودرو.\nچند لحظه وقت میدید تا نیازتون رو دقیق‌تر بررسی کنم؟",
            ],
            [
                'id' => 2,
                'title' => 'ارسال لیست خودرو و کانال‌های رسمی',
                'category' => 'car_list',
                'body' => "سلام {{customer_name}} عزیز\nلیست خودروهای مجاز طرح واردات ناوراکار و کانال‌های رسمی شرکت رو براتون ارسال می‌کنم: {{official_channels}}\nهر سوالی داشتید در خدمتم هستم.",
            ],
            [
                'id' => 3,
                'title' => 'ارسال برآورد هزینه',
                'category' => 'cost_breakdown',
                'body' => "سلام {{customer_name}} عزیز\nبرآورد هزینه واردات {{car_model}} رو براتون آماده کردم.\nجمع کل تخمینی: {{total_price}} تومان\nاین یک برآورد اولیه است؛ برای قیمت قطعی در خدمتتون هستم.",
            ],
            [
                'id' => 4,
                'title' => 'پیگیری مشتری داغ (Hot)',
                'category' => 'follow_up_hot',
                'body' => "سلام {{customer_name}} عزیز، وقت بخیر\nپیگیر درخواستتون برای {{car_model}} هستم. اگه سوال یا ابهامی مونده در خدمتتون هستم تا تصمیم نهایی رو راحت‌تر بگیرید.",
            ],
            [
                'id' => 5,
                'title' => 'پیگیری مشتری معمولی (Warm)',
                'category' => 'follow_up_warm',
                'body' => "سلام {{customer_name}} عزیز\nخواستم حالتون رو بپرسم و ببینم روی گزینه {{car_model}} به جمع‌بندی رسیدید یا سوال دیگه‌ای هست؟",
            ],
            [
                'id' => 6,
                'title' => 'پیگیری مشتری سرد (Cold)',
                'category' => 'follow_up_cold',
                'body' => "سلام {{customer_name}} عزیز\nهنوز پیشنهاد واردات {{car_model}} براتون معتبره. اگه علاقه‌مند بودید خوشحال میشم دوباره کمکتون کنم.",
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(['id' => $template['id']], $template);
        }
    }
}
