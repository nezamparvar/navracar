<?php

namespace Tests\Feature;

use App\Mail\ProformaInvoiceMail;
use App\Mail\QuoteRequestReceived;
use App\Models\AdminUser;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProformaPdfTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'admin-test',
            'password_hash' => bcrypt('secret'),
            'full_name' => 'Test Admin',
            'role' => 'admin',
        ]);
    }

    public function test_quote_request_generates_a_downloadable_pdf_and_emails_the_customer(): void
    {
        Storage::fake('public');
        Mail::fake();

        $response = $this->postJson(route('public.quote-requests.store'), [
            'name' => 'علی رضایی',
            'phone' => '09121234567',
            'email' => 'ali@example.com',
            'car' => 'Mercedes-Benz S500',
            'category' => 'بنزینی بالای ۳۰۰۰ سی‌سی',
            'breakdown' => [
                ['label' => 'سود بازرگانی', 'rate' => '۶۵٪', 'amount' => '500,000,000 تومان'],
            ],
            'totals' => [
                'جمع کل نهایی' => '950,000,000 تومان',
            ],
            'website' => '',
            'pageLoadedAt' => 0,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertNotNull($response->json('pdfUrl'));

        $lead = QuoteRequest::first();
        Storage::disk('public')->assertExists('proformas/quote-'.$lead->id.'.pdf');

        Mail::assertSent(QuoteRequestReceived::class);
        Mail::assertSent(ProformaInvoiceMail::class, fn ($mail) => $mail->lead->id === $lead->id);
    }

    public function test_quote_request_pdf_download_requires_a_valid_signature(): void
    {
        Storage::fake('public');
        Mail::fake();

        $this->postJson(route('public.quote-requests.store'), [
            'name' => 'سارا احمدی',
            'phone' => '09351234567',
            'car' => 'BMW X5',
            'breakdown' => [['label' => 'سود بازرگانی', 'rate' => '۴۵٪', 'amount' => '200,000,000 تومان']],
            'totals' => ['جمع کل نهایی' => '400,000,000 تومان'],
            'website' => '',
            'pageLoadedAt' => 0,
        ]);

        $lead = QuoteRequest::first();

        $this->get('/quote-requests/'.$lead->id.'/pdf')->assertForbidden();

        $signedUrl = URL::signedRoute('public.quote-requests.pdf', ['quoteRequest' => $lead->id]);
        $this->get($signedUrl)->assertOk();
    }

    public function test_admin_can_download_official_invoice_as_pdf(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $invoice = Invoice::create([
            'invoice_number' => 'NVK-2026-00099',
            'customer_name' => 'رضا کریمی',
            'customer_phone' => '09121112233',
            'car_label' => 'BMW X5',
            'category' => 'بنزینی ۲۵۰۰ تا ۳۰۰۰ سی‌سی',
            'breakdown_json' => json_encode([['label' => 'سود بازرگانی', 'rate' => '۴۵٪', 'amount' => '300,000,000']], JSON_UNESCAPED_UNICODE),
            'total_amount' => 700000000,
            'discount_amount' => 20000000,
            'currency' => 'toman',
            'invoice_type' => 'full',
            'status' => 'پیش‌نویس',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        Storage::disk('public')->assertExists('proformas/invoice-'.$invoice->id.'.pdf');
    }
}
