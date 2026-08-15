<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserExtensionPairingTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_can_generate_pairing_code()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');

        $this->assertNotNull($pairing);
        $this->assertEquals('staging', $pairing->environment);
        $this->assertEquals('pending', $pairing->status);
        $this->assertEquals(6, strlen($pairing->pairing_code));
        $this->assertNull($pairing->extension_token);
        $this->assertNull($pairing->paired_at);
    }

    public function test_pairing_code_is_numeric()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'production');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $pairing->pairing_code);
    }

    public function test_pairing_codes_are_unique()
    {
        $code1 = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging')->pairing_code;
        $code2 = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging')->pairing_code;

        $this->assertNotEquals($code1, $code2);
    }

    public function test_can_exchange_pairing_code_for_token()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;

        $result = BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertNotNull($result['token']);

        $exchanged = BrowserExtensionPairing::where('pairing_code', $code)->first();
        $this->assertEquals('active', $exchanged->status);
        $this->assertEquals('test-device', $exchanged->device_name);
        $this->assertNotNull($exchanged->extension_token);
        $this->assertNotNull($exchanged->paired_at);
    }

    public function test_cannot_exchange_invalid_code()
    {
        $result = BrowserExtensionPairing::exchangeCodeForToken('000000', 'test-device', 'test-fingerprint');

        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_cannot_exchange_expired_code()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $pairing->update(['created_at' => now()->subHour()->subMinute()]);
        $code = $pairing->pairing_code;

        $result = BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');

        $this->assertEquals('error', $result['status']);
    }

    public function test_cannot_exchange_already_used_code()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;

        BrowserExtensionPairing::exchangeCodeForToken($code, 'device1', 'fingerprint1');
        $result = BrowserExtensionPairing::exchangeCodeForToken($code, 'device2', 'fingerprint2');

        $this->assertEquals('error', $result['status']);
    }

    public function test_can_check_pairing_status()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');

        $this->assertTrue($pairing->isPending());
        $this->assertFalse($pairing->isActive());

        $code = $pairing->pairing_code;
        BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');

        $active = BrowserExtensionPairing::where('pairing_code', $code)->first();
        $this->assertFalse($active->isPending());
        $this->assertTrue($active->isActive());
    }

    public function test_can_revoke_pairing()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;
        BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');

        $active = BrowserExtensionPairing::where('pairing_code', $code)->first();
        $active->revoke();

        $this->assertTrue($active->isRevoked());
        $this->assertFalse($active->isActive());
        $this->assertNotNull($active->revoked_at);
    }

    public function test_revoked_token_cannot_be_used()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;
        $result = BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');
        $token = $result['token'];

        $active = BrowserExtensionPairing::where('pairing_code', $code)->first();
        $active->revoke();

        $found = BrowserExtensionPairing::where('extension_token', $token)
            ->where('status', 'active')
            ->first();

        $this->assertNull($found);
    }

    public function test_can_update_last_used_timestamp()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;
        BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');

        $active = BrowserExtensionPairing::where('pairing_code', $code)->first();
        $oldTime = $active->last_used_at;

        sleep(1);
        $active->updateLastUsed();

        $this->assertNotNull($active->last_used_at);
        if ($oldTime) {
            $this->assertGreaterThan($oldTime, $active->last_used_at);
        }
    }

    public function test_staging_and_production_are_separate()
    {
        $stagingPairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $productionPairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'production');

        $this->assertNotEquals($stagingPairing->pairing_code, $productionPairing->pairing_code);
        $this->assertEquals('staging', $stagingPairing->environment);
        $this->assertEquals('production', $productionPairing->environment);
    }

    public function test_pairing_expires_after_60_minutes()
    {
        $pairing = BrowserExtensionPairing::generatePairingCode($this->admin, 'staging');
        $code = $pairing->pairing_code;

        // Move time forward 61 minutes
        $pairing->update(['created_at' => now()->subHour()->subMinute()]);

        $result = BrowserExtensionPairing::exchangeCodeForToken($code, 'test-device', 'test-fingerprint');

        $this->assertEquals('error', $result['status']);
    }
}
