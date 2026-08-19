<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BrowserExtensionPairing extends Model
{
    protected $fillable = [
        'admin_user_id', 'pairing_code_hash', 'token_hash', 'token_last_four',
        'device_name', 'environment', 'status', 'expires_at', 'paired_at',
        'last_used_at', 'revoked_at',
    ];

    protected $hidden = ['pairing_code_hash', 'token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paired_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->revoked_at === null
            && ! $this->isExpired()
            && $this->admin_user_id !== null;
    }

    public static function issue(AdminUser $user, string $environment, int $expiresInHours): array
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = hash('sha256', $code);
        } while (self::where('pairing_code_hash', $hash)->exists());

        $pairing = self::create([
            'admin_user_id' => $user->id,
            'pairing_code_hash' => $hash,
            'device_name' => 'Browser Extension',
            'environment' => $environment,
            'status' => 'pending',
            'expires_at' => now()->addHours($expiresInHours),
        ]);

        return ['pairing' => $pairing, 'pairing_code' => $code];
    }

    public static function exchange(string $code, string $environment, ?string $deviceName): array
    {
        return DB::transaction(function () use ($code, $environment, $deviceName) {
            $pairing = self::where('pairing_code_hash', hash('sha256', $code))
                ->lockForUpdate()
                ->first();

            if (! $pairing || $pairing->status !== 'pending' || $pairing->revoked_at || $pairing->isExpired()) {
                return ['status' => 'invalid'];
            }
            if ($pairing->environment !== $environment) {
                return ['status' => 'environment_mismatch'];
            }

            $token = bin2hex(random_bytes(32));
            $pairing->update([
                'pairing_code_hash' => null,
                'token_hash' => hash('sha256', $token),
                'token_last_four' => substr($token, -4),
                'device_name' => $deviceName ?: $pairing->device_name,
                'status' => 'active',
                'paired_at' => now(),
                'last_used_at' => now(),
            ]);

            return ['status' => 'success', 'pairing' => $pairing, 'token' => $token];
        });
    }

    public static function activeForToken(string $token): ?self
    {
        $pairing = self::where('token_hash', hash('sha256', $token))->first();

        return $pairing?->isActive() ? $pairing : null;
    }
}
