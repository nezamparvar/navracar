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
        $privatePem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCN+Ljw6rHfpUBd
bEWnu6UkP0TJWQyO5nCMtZx0i0yuO2pmmSLIKUbGTNqwzfS7aQZJez8Dy1id6xtu
s4rxCQbIfrAwBffJ3bzLhnsA2PTDMsmc9X+jekfKizljBklcNJdPVt8tgWaVzS43
zMkIQNLH3BbcTAo9EFrH8ZfBE9516zteKAEUGy5VGdLTNvgNLHQHm0ogq3G9Ru4c
Mw4dt1PIeIZFcb0FEOL3KP3xCV1HGVUiRMdZhbYko8bDkBJCnFLnDsNWBvpgHGFh
a8jhzhUvQhNMNjq6GAd+et4wRczqL4cxA+Pduo1C/xO6yZVXTZu5G1e0dT5rG2mL
kBTMrP+DAgMBAAECggEAPeGDDvNlO3OGdhIK3Fz0UrPHEjIfwKudtB82xi+vaSQZ
sQWffPpM0INQMQ9cVFGnPWEcatV310FScDKO5ZfHHp3lwtDfG9xr/ZQsygZGzUw2
R5VUIJfcceK0TT7AvBFqQFk/ptCHu9S2h/jZSSEkHpwGVNhKACrAPvVFbZFw/hk3
MavRKFg/nn5lAt4kbYiVKRzqcZ4pV8JtowCNNgWIu9lfGCulXZnk33U20cQbymZj
TYqR9hNxn+/oqme7Anm26B6C3X6X3B6suUXCRon3bLbJrW/aFKdD8QNuQIXHVgBR
Tok3zMgt0vKGJLJwtm1lOEbA36M0wO4Pudc/LQqbxQKBgQDGRGwPMQzTmB9XxJNr
L7NLPxkk8luYh0QU/oXhEkK0Y1/MuKGlotg+Y4cozYzs5vNonNwJZTppOwm+6f/Z
ADx3JyHxq0xG7MZvWsdoYi4q7475zU/knbVQMJq0MSbgUDNjHAjkE4OfWGWCM+Pn
U5pY76XXpU+0B1MB7QpSLF0vhQKBgQC3T89XAbKQG77ZMvyemVzw74cS4Weewy9T
pcdOjEn/bBqTcHpQBTnoH6EKnBdJNAXX1nmLpB4ipkHqr2YUzz5w0FXA6aRVXxqg
1iG3kuLR85yMTLH1aGteK+lNgiNwEu7MZ6hqx/Ou34GnJmG2cuFOO/OTByXAjdVi
dmn4KWqtZwKBgG0R9PRCgpDgrdVlsceUfSjW9Gkr3pHBUAXQoyvXFNURmgEQTPTG
6fkgXZlnwrAMuTkCmdWdV43OSaU6K2JgS+MspvLAznxyAxP9N5fH8yfxk5D+joXw
8G7F3kVkiMN7u1k5cfaueYkuYuFG1IhMqKtmYrCII5mhMdvsX/Imr7jVAoGAN3O0
I/zGeGN8QxXyZ0O7YsMCmEqJXvRqb6JZfUBg7hxUmb9PsL8z+iz/OUdon+NV1Snz
ELPvzcl/bS/r8GWU3OmY10eWR6ECXB8Kig6sAJjyBVr6BmO0/wLHi4PPloqTpE/D
GbIs2/yOqvKoYlm0IEdwckyaNfpxz1xEyCY3OAcCgYEAjyQQbUlRwkrFVa/2w3w6
QvkSbUEx0g8TI9f8/hlOcQ+5NuHboYwJCfEd+LwMkso19zQ+BQflG3nxEcli2246
bEfRVfh7uyv1BIaW+5DHSFXOj9L0RqsyJEu0RkXRZ0JzqucwiYAElCYwMPATpy5S
92s92UpVkZvVrzucBJkvBqA=
-----END PRIVATE KEY-----
PEM;
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
