<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BrowserExtensionPairing extends Model
{
    protected $fillable = [
        'pairing_code',
        'extension_token',
        'environment',
        'status',
        'created_by',
        'device_name',
        'device_fingerprint',
        'paired_at',
        'last_used_at',
        'revoked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public static function generatePairingCode(): string
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('pairing_code', $code)->exists());

        return $code;
    }

    public static function generateExtensionToken(): string
    {
        return 'ext_'.Str::random(48);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at?->isFuture() !== false;
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function activate(string $deviceName, ?string $fingerprint = null): void
    {
        $this->update([
            'status' => 'active',
            'extension_token' => self::generateExtensionToken(),
            'device_name' => $deviceName,
            'device_fingerprint' => $fingerprint,
            'paired_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);
    }

    public function revoke(): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
