<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmClient
{
    public function __construct(private readonly FcmAccessTokenProvider $tokens) {}

    /** @param array<string, scalar|null> $data */
    public function send(string $deviceToken, string $title, string $body, array $data = []): FcmSendResult
    {
        $projectId = config('services.firebase.project_id');
        if (! is_string($projectId) || ! preg_match('/^[a-z0-9][a-z0-9-]{2,100}$/', $projectId)) {
            throw new RuntimeException('FIREBASE_PROJECT_ID معتبر تنظیم نشده است.');
        }
        $stringData = [];
        foreach ($data as $key => $value) {
            if ($value !== null && is_scalar($value)) {
                $stringData[(string) $key] = (string) $value;
            }
        }

        $response = Http::withToken($this->tokens->accessToken())->asJson()->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $stringData,
                    'android' => [
                        'priority' => 'high',
                        'notification' => ['channel_id' => 'navracar_updates', 'sound' => 'default'],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return new FcmSendResult(true);
        }

        $details = $response->json('error.details');
        $detailCode = collect(is_array($details) ? $details : [])->pluck('errorCode')->filter()->first();
        $status = $response->json('error.status');
        $code = is_string($detailCode) ? $detailCode : (is_string($status) ? $status : 'HTTP_'.$response->status());
        $invalid = in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT'], true);

        return new FcmSendResult(false, $invalid, $code);
    }
}
