<?php

namespace Tests\Feature;

use App\Services\CarListingMapper;
use App\Services\DubizzleParser;
use Tests\TestCase;

class DubizzleParserTest extends TestCase
{
    private function sampleHtml(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/dubizzle_bmw_x4.html');
    }

    private function sampleUrl(): string
    {
        return 'https://dubai.dubizzle.com/motors/used-cars/bmw/x4/2026/1/10/aed-1750-pm-2019-bmw-x4-xdrive30i-m-sport--2-614---43ba18d828284431a018afb8933a5da1/';
    }

    public function test_it_extracts_core_fields_from_a_real_sample_page(): void
    {
        $parser = new DubizzleParser;
        $data = $parser->parse($this->sampleHtml(), $this->sampleUrl());

        $this->assertSame('bmw', $data['make']);
        $this->assertSame('x4', $data['model']);
        $this->assertSame(113000.0, $data['price_aed']);
        $this->assertSame('2019', $data['model_year']);
        $this->assertSame('96,932 km', $data['kilometers']);
        $this->assertSame('GCC Specs', $data['regional_specs']);
        $this->assertSame('Left Hand', $data['steering_side']);
        $this->assertSame('SUV', $data['body_type']);
        $this->assertSame('Petrol', $data['fuel_type']);
        $this->assertSame('Automatic Transmission', $data['transmission_type']);
        $this->assertSame('2000 - 2499 cc', $data['engine_capacity_cc']);
        $this->assertSame('Dealer', $data['seller_type']);
        $this->assertSame('Yes', $data['warranty']);
        $this->assertSame('Black', $data['exterior_color']);
        $this->assertSame('xDrive30i M Sport', $data['trim_level']);
        $this->assertStringContainsString('Ras Al Khor', $data['location_text']);
        $this->assertStringContainsString('BMW X4', $data['title_en']);
        $this->assertNotEmpty($data['description_en']);
        $this->assertGreaterThanOrEqual(3, count($data['images']));
        foreach ($data['images'] as $img) {
            $this->assertStringStartsWith('https://dbz-images.dubizzle.com/images/', $img);
        }
    }

    public function test_category_detection_matches_engine_size(): void
    {
        $mapper = new CarListingMapper;

        $this->assertSame('c2000', $mapper->detectCategory('2000 - 2499 cc', 'Petrol'));
        $this->assertSame('c1500', $mapper->detectCategory('1200 - 1499 cc', 'Petrol'));
        $this->assertSame('hybrid', $mapper->detectCategory('2000 - 2499 cc', 'Hybrid'));
        $this->assertSame('ev', $mapper->detectCategory('2000 - 2499 cc', 'Electric'));
        $this->assertSame('phev', $mapper->detectCategory('2000 - 2499 cc', 'Plug-in Hybrid'));
        $this->assertSame('c3001', $mapper->detectCategory('4000 - 4499 cc', 'Petrol'));
    }
}
