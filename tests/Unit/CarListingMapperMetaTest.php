<?php

namespace Tests\Unit;

use App\Services\CarListingMapper;
use PHPUnit\Framework\TestCase;

class CarListingMapperMetaTest extends TestCase
{
    public function test_it_builds_stable_meta_from_browser_extension_vehicle_fields(): void
    {
        $mapper = new CarListingMapper;
        $vehicle = [
            'title' => 'Toyota Camry XSE 2020',
            'make' => 'Toyota',
            'model' => 'Camry',
            'trim' => 'XSE',
            'year' => '2020',
            'price_aed' => 50000,
            'mileage_km' => '45,000 km',
        ];

        $first = $mapper->resolveMeta($vehicle, $vehicle['title']);
        $second = $mapper->resolveMeta($vehicle, $vehicle['title']);

        $this->assertSame($first, $second);
        $this->assertSame('Toyota Camry XSE 2020 | ناوراکار', $first['meta_title']);
        $this->assertSame(
            'Toyota Camry XSE 2020 — قیمت 50,000 درهم — کارکرد 45,000 km — به همراه جدول کامل هزینه ترخیص، عوارض گمرکی و پلاک برای واردات به ایران.',
            $first['meta_description'],
        );
    }

    public function test_it_preserves_existing_non_empty_meta_and_fills_only_blank_values(): void
    {
        $meta = (new CarListingMapper)->resolveMeta([
            'title' => 'Toyota Camry 2020',
            'price_aed' => 50000,
            'meta_title' => '  عنوان اختصاصی <b>خودرو</b>  ',
            'meta_description' => "  \n ",
        ], 'Toyota Camry 2020');

        $this->assertSame('عنوان اختصاصی خودرو', $meta['meta_title']);
        $this->assertSame(
            'Toyota Camry 2020 — قیمت 50,000 درهم — به همراه جدول کامل هزینه ترخیص، عوارض گمرکی و پلاک برای واردات به ایران.',
            $meta['meta_description'],
        );
    }

    public function test_meta_generation_is_marketplace_agnostic(): void
    {
        $mapper = new CarListingMapper;
        $vehicle = [
            'title' => 'Nissan Patrol 2024',
            'price_aed' => 250000,
            'mileage_km' => '12,000 km',
        ];

        $results = [];
        foreach (['dubizzle', 'dubicars', 'yallamotor'] as $source) {
            $results[] = $mapper->resolveMeta($vehicle + ['source' => $source], $vehicle['title']);
        }

        $this->assertSame($results[0], $results[1]);
        $this->assertSame($results[1], $results[2]);
    }
}
