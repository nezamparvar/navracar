<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Models\BrowserExtensionPairing;
use Illuminate\Console\Command;

class GenerateExtensionPairingCode extends Command
{
    protected $signature = 'extension:generate-pairing-code {--user-id=} {--environment=staging} {--expires-in=24}';
    protected $description = 'Generate a pairing code for browser extension authentication';

    public function handle(): int
    {
        $userId = $this->option('user-id');
        $environment = $this->option('environment');
        $expiresInHours = (int) $this->option('expires-in');

        if (!in_array($environment, ['staging', 'production'])) {
            $this->error('Environment must be "staging" or "production"');
            return 1;
        }

        if ($userId) {
            $user = AdminUser::find($userId);
            if (!$user) {
                $this->error("User ID $userId not found");
                return 1;
            }
        } else {
            $user = null;
        }

        $pairingCode = BrowserExtensionPairing::generatePairingCode();
        $token = BrowserExtensionPairing::generateToken();
        $expiresAt = now()->addHours($expiresInHours);

        $pairing = BrowserExtensionPairing::create([
            'admin_user_id' => $user?->id,
            'pairing_code' => $pairingCode,
            'token' => $token,
            'device_name' => 'Browser Extension (Generated)',
            'environment' => $environment,
            'expires_at' => $expiresAt,
        ]);

        $this->info('Pairing code generated successfully');
        $this->line('');
        $this->line("Pairing Code: <fg=green>$pairingCode</>");
        $this->line("Environment:  <fg=green>$environment</>");
        $this->line("User ID:      " . ($user ? "<fg=green>{$user->id}</>" : '<fg=yellow>Not assigned</>'));
        $this->line("Expires At:   <fg=green>{$expiresAt->toDateTimeString()}</>");
        $this->line("ID (for DB):  <fg=blue>{$pairing->id}</>");
        $this->line('');
        $this->line('Share the pairing code with the user to authenticate the extension.');

        return 0;
    }
}
