<?php

namespace Tests\Feature\Commands;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateExtensionPairingCodeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function command_generates_valid_pairing_code(): void
    {
        $this->artisan('extension:generate-pairing-code')
            ->expectsOutput('Pairing code generated successfully')
            ->assertExitCode(0);

        $this->assertCount(1, BrowserExtensionPairing::all());
        $pairing = BrowserExtensionPairing::first();
        $this->assertNotNull($pairing->pairing_code);
        $this->assertNotNull($pairing->token);
        $this->assertEquals('staging', $pairing->environment);
        $this->assertNotNull($pairing->expires_at);
    }

    /** @test */
    public function command_generates_6_digit_pairing_code(): void
    {
        $this->artisan('extension:generate-pairing-code')
            ->assertExitCode(0);

        $pairing = BrowserExtensionPairing::first();
        $this->assertTrue(ctype_digit($pairing->pairing_code));
        $this->assertEquals(6, strlen($pairing->pairing_code));
    }

    /** @test */
    public function command_accepts_user_id(): void
    {
        $user = AdminUser::factory()->create();

        $this->artisan('extension:generate-pairing-code', ['--user-id' => $user->id])
            ->assertExitCode(0);

        $pairing = BrowserExtensionPairing::first();
        $this->assertEquals($user->id, $pairing->admin_user_id);
    }

    /** @test */
    public function command_fails_with_invalid_user_id(): void
    {
        $this->artisan('extension:generate-pairing-code', ['--user-id' => 99999])
            ->expectsOutput('User ID 99999 not found')
            ->assertExitCode(1);
    }

    /** @test */
    public function command_accepts_environment_option(): void
    {
        $this->artisan('extension:generate-pairing-code', ['--environment' => 'production'])
            ->assertExitCode(0);

        $pairing = BrowserExtensionPairing::first();
        $this->assertEquals('production', $pairing->environment);
    }

    /** @test */
    public function command_rejects_invalid_environment(): void
    {
        $this->artisan('extension:generate-pairing-code', ['--environment' => 'invalid'])
            ->expectsOutput('Environment must be "staging" or "production"')
            ->assertExitCode(1);
    }

    /** @test */
    public function command_accepts_expiration_option(): void
    {
        $now = now();
        $this->artisan('extension:generate-pairing-code', ['--expires-in' => '48'])
            ->assertExitCode(0);

        $pairing = BrowserExtensionPairing::first();
        $this->assertTrue($pairing->expires_at->isAfter($now->addHours(47)));
        $this->assertTrue($pairing->expires_at->isBefore($now->addHours(49)));
    }

    /** @test */
    public function generated_pairing_codes_are_unique(): void
    {
        $this->artisan('extension:generate-pairing-code')->assertExitCode(0);
        $this->artisan('extension:generate-pairing-code')->assertExitCode(0);
        $this->artisan('extension:generate-pairing-code')->assertExitCode(0);

        $codes = BrowserExtensionPairing::pluck('pairing_code')->toArray();
        $this->assertEquals(3, count($codes));
        $this->assertEquals(3, count(array_unique($codes)));
    }
}
