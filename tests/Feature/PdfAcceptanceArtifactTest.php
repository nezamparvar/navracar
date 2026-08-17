<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Invoice;
use App\Services\ProformaPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfAcceptanceArtifactTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_invoice_pdf_variants_are_rendered_as_acceptance_artifacts(): void
    {
        Storage::disk('public')->deleteDirectory('proformas/acceptance');

        $admin = AdminUser::create([
            'username' => 'pdf-acceptance-admin',
            'password_hash' => bcrypt('not-used'),
            'full_name' => 'PDF Acceptance',
            'role' => 'admin',
        ]);

        $generator = new ProformaPdfGenerator;
        $variants = [
            'full' => $this->invoice($admin, 'full', 'NVK-PDF-FULL'),
            'single-item' => $this->invoice($admin, 'single_item', 'NVK-PDF-SINGLE'),
        ];

        foreach ($variants as $variant => $invoice) {
            foreach (['fa', 'en'] as $language) {
                $generatedPath = $generator->fromInvoice($invoice, $language);
                $contents = Storage::disk('public')->get($generatedPath);
                $artifactPath = "proformas/acceptance/{$variant}-{$language}.pdf";

                $this->assertStringStartsWith('%PDF-', $contents);
                $this->assertGreaterThan(10_000, strlen($contents));
                Storage::disk('public')->put($artifactPath, $contents);
                Storage::disk('public')->assertExists($artifactPath);
            }
        }
    }

    private function invoice(AdminUser $admin, string $type, string $number): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'customer_name' => $type === 'full' ? 'علی رضایی' : 'Sara Ahmadi',
            'customer_phone' => '+971501234567',
            'customer_email' => 'pdf-acceptance@example.test',
            'car_label' => 'Toyota Land Cruiser 2025',
            'category' => 'c3001',
            'breakdown_json' => json_encode([
                ['key' => 'tariff_duty', 'label' => 'عوارض گمرکی', 'rate' => '120٪ از ارزش گمرکی', 'amount' => 360000000],
                ['key' => 'sea_freight', 'label' => 'حمل دریایی', 'rate' => 'درهم × نرخ ارز آزاد', 'amount' => 150000000],
            ], JSON_UNESCAPED_UNICODE),
            'total_amount' => 510000000,
            'discount_amount' => 10000000,
            'currency' => 'toman',
            'valid_until' => now()->addDays(3),
            'invoice_type' => $type,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
    }
}
