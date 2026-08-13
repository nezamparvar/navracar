<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        AdminUser::updateOrCreate(
            ['username' => 'e2e-sales'],
            [
                'password_hash' => Hash::make('not-used-for-login'),
                'full_name' => 'E2E Sales',
                'role' => 'sales',
            ]
        );
    }
}
