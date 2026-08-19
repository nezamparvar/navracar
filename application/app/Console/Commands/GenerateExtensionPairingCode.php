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
            $this->error('The --user-id option is required.');
            return 1;
        }

        if ($expiresInHours < 1 || $expiresInHours > 168) {
            $this->error('Expiry must be between 1 and 168 hours.');
            return 1;
        }

        $issued = BrowserExtensionPairing::issue($user, $environment, $expiresInHours);
        $pairing = $issued['pairing'];
        $pairingCode = $issued['pairing_code'];

        $this->info('Pairing code generated successfully');
        $this->line('');
        $this->line("Pairing Code: <fg=green>$pairingCode</>");
        $this->line("Environment:  <fg=green>$environment</>");
        $this->line("User ID:      <fg=green>{$user->id}</>");
        $this->line("Expires At:   <fg=green>{$pairing->expires_at->toDateTimeString()}</>");
        $this->line("ID (for DB):  <fg=blue>{$pairing->id}</>");
        $this->line('');
        $this->line('Share the pairing code with the user to authenticate the extension.');

        return 0;
    }
}
