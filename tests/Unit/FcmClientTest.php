<?php

namespace Tests\Unit;

use App\Services\FcmAccessTokenProvider;
use App\Services\FcmClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmClientTest extends TestCase
{
    public function test_it_sends_an_http_v1_message_with_server_only_bearer_auth(): void
    {
        config(['services.firebase.project_id' => 'navracar-stage']);
        Http::fake(['https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/navracar-stage/messages/123'])]);
        $client = new FcmClient($this->tokens());

        $result = $client->send('device-token-value', 'عنوان', 'متن', ['url' => '/vehicles', 'notification_id' => 12]);

        $this->assertTrue($result->successful);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer server-access-token')
                && $request->data()['message']['token'] === 'device-token-value'
                && $request->data()['message']['data']['notification_id'] === '12';
        });
    }

    public function test_it_identifies_an_unregistered_device_token(): void
    {
        config(['services.firebase.project_id' => 'navracar-stage']);
        Http::fake(['https://fcm.googleapis.com/*' => Http::response([
            'error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]],
        ], 404)]);

        $result = (new FcmClient($this->tokens()))->send('expired-device-token', 'عنوان', 'متن');

        $this->assertFalse($result->successful);
        $this->assertTrue($result->invalidToken);
        $this->assertSame('UNREGISTERED', $result->errorCode);
    }

    private function tokens(): FcmAccessTokenProvider
    {
        return new class extends FcmAccessTokenProvider
        {
            public function accessToken(): string
            {
                return 'server-access-token';
            }
        };
    }
}
