<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Local/dev convenience only. In production, create real accounts with
     * `php artisan admin:create-user` instead of relying on this seeder.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        AdminUser::updateOrCreate(
            ['username' => 'admin'],
            [
                'password_hash' => Hash::make('password'),
                'full_name' => 'مدیر سیستم',
                'role' => 'admin',
            ]
        );
    }
}
