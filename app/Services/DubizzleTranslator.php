<?php

namespace App\Services;

/**
 * ترجمهٔ فیلدهای مشخصات فنی آگهی (نه توضیحات آزاد) با دیکشنری ثابت فارسی.
 * اگر مقداری در دیکشنری نبود، همان متن اصلی برگردانده می‌شود تا چیزی گم نشود.
 */
class DubizzleTranslator
{
    private const FIELD_LABELS = [
        'body_type' => 'نوع بدنه',
        'doors' => 'تعداد درب',
        'engine_capacity_cc' => 'حجم موتور',
        'exterior_color' => 'رنگ بدنه',
        'fuel_type' => 'نوع سوخت',
        'horsepower' => 'قدرت موتور',
        'interior_color' => 'رنگ داخل کابین',
        'trim_level' => 'تیریم / نسخه',
        'no_of_cylinders' => 'تعداد سیلندر',
        'seating_capacity' => 'ظرفیت صندلی',
        'seller_type' => 'نوع فروشنده',
        'target_market' => 'بازار هدف',
        'transmission_type' => 'نوع گیربکس',
        'warranty' => 'گارانتی',
        'regional_specs' => 'اسپک منطقه‌ای',
        'steering_side' => 'فرمان',
        'kilometers' => 'کارکرد',
        'model_year' => 'سال ساخت',
        'location_text' => 'موقعیت مکانی (امارات)',
    ];

    private const VALUE_MAPS = [
        'fuel_type' => [
            'Petrol' => 'بنزینی', 'Gasoline' => 'بنزینی', 'Diesel' => 'دیزلی',
            'Electric' => 'برقی', 'Hybrid' => 'هیبریدی',
        ],
        'transmission_type' => [
            'Automatic Transmission' => 'اتوماتیک', 'Manual Transmission' => 'دستی',
            'Automatic' => 'اتوماتیک', 'Manual' => 'دستی',
        ],
        'body_type' => [
            'SUV' => 'شاسی‌بلند (SUV)', 'Sedan' => 'سدان', 'Coupe' => 'کوپه',
            'Hatchback' => 'هاچبک', 'Pickup Truck' => 'وانت', 'Convertible' => 'کروکی',
            'Van' => 'ون', 'Wagon' => 'استیشن', 'Crossover' => 'کراس‌اوور', 'Sports Car' => 'اسپرت',
        ],
        'regional_specs' => [
            'GCC Specs' => 'اسپک خلیج فارس (GCC)', 'American Specs' => 'اسپک آمریکا',
            'Japanese Specs' => 'اسپک ژاپن', 'European Specs' => 'اسپک اروپا',
            'Canadian Specs' => 'اسپک کانادا', 'Korean Specs' => 'اسپک کره جنوبی',
            'Chinese Specs' => 'اسپک چین',
        ],
        'steering_side' => [
            'Left Hand' => 'فرمان چپ', 'Right Hand' => 'فرمان راست',
        ],
        'seller_type' => [
            'Dealer' => 'نمایشگاه / فروشنده', 'Owner' => 'مالک (فروش شخصی)',
        ],
        'warranty' => [
            'Yes' => 'دارد', 'No' => 'ندارد',
        ],
        'exterior_color' => [
            'Black' => 'مشکی', 'White' => 'سفید', 'Silver' => 'نقره‌ای', 'Grey' => 'طوسی',
            'Gray' => 'طوسی', 'Blue' => 'آبی', 'Red' => 'قرمز', 'Brown' => 'قهوه‌ای',
            'Beige' => 'بژ', 'Gold' => 'طلایی', 'Green' => 'سبز', 'Orange' => 'نارنجی',
            'Yellow' => 'زرد', 'Burgundy' => 'زرشکی', 'Bronze' => 'برنزی', 'Purple' => 'بنفش',
        ],
        'interior_color' => [
            'Black' => 'مشکی', 'White' => 'سفید', 'Silver' => 'نقره‌ای', 'Grey' => 'طوسی',
            'Gray' => 'طوسی', 'Blue' => 'آبی', 'Red' => 'قرمز', 'Brown' => 'قهوه‌ای',
            'Beige' => 'بژ', 'Gold' => 'طلایی', 'Green' => 'سبز', 'Orange' => 'نارنجی',
            'Yellow' => 'زرد', 'Burgundy' => 'زرشکی', 'Bronze' => 'برنزی', 'Purple' => 'بنفش',
        ],
    ];

    public function label(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? $field;
    }

    public function value(string $field, ?string $english): ?string
    {
        if ($english === null || $english === '') {
            return null;
        }

        $map = self::VALUE_MAPS[$field] ?? null;
        if ($map === null) {
            return $english;
        }

        return $map[$english] ?? $english;
    }
}
