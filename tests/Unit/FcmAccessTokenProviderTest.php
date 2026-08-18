<?php

namespace Tests\Unit;

use App\Services\FcmAccessTokenProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmAccessTokenProviderTest extends TestCase
{
    private ?string $credentialsPath = null;

    protected function tearDown(): void
    {
        if ($this->credentialsPath && is_file($this->credentialsPath)) {
            unlink($this->credentialsPath);
        }
        parent::tearDown();
    }

    public function test_it_exchanges_a_signed_service_account_jwt_without_exposing_the_private_key(): void
    {
        $opensslOptions = [];
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        if ($key === false && PHP_OS_FAMILY === 'Windows') {
            $windowsConfig = (getenv('ProgramFiles') ?: 'C:\\Program Files').'\\Git\\mingw64\\etc\\ssl\\openssl.cnf';
            if (is_file($windowsConfig)) {
                $opensslOptions = ['config' => $windowsConfig];
                $key = openssl_pkey_new(['private_key_bits' => 2048, ...$opensslOptions]);
            }
        }
        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $privatePem, null, $opensslOptions));
        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'navracar-fcm-');
        file_put_contents($this->credentialsPath, json_encode([
            'project_id' => 'navracar-stage',
            'client_email' => 'firebase-adminsdk@navracar-stage.iam.gserviceaccount.com',
            'private_key' => $privatePem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_THROW_ON_ERROR));
        config(['services.firebase.credentials' => $this->credentialsPath]);
        Cache::flush();
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'server-access-token', 'expires_in' => 3600,
        ])]);

        $token = app(FcmAccessTokenProvider::class)->accessToken();

        $this->assertSame('server-access-token', $token);
        Http::assertSent(function ($request) use ($privatePem) {
            return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && substr_count((string) $request['assertion'], '.') === 2
                && ! str_contains((string) $request->body(), $privatePem);
        });
    }
}
