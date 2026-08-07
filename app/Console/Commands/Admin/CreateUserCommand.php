<?php

namespace App\Console\Commands\Admin;

use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    protected $signature = 'admin:create-user
        {username : Login username}
        {--password= : Plain-text password (prompted if omitted)}
        {--role=admin : admin or sales}
        {--name= : Full name shown in the panel}';

    protected $description = 'Create or update an admin panel account (admin or sales role)';

    public function handle(): int
    {
        $username = $this->argument('username');
        $role = $this->option('role');
        $password = $this->option('password') ?: $this->secret('رمز عبور را وارد کنید (حداقل ۶ کاراکتر)');

        $validator = Validator::make(
            ['username' => $username, 'password' => $password, 'role' => $role],
            ['username' => ['required', 'string', 'max:64'], 'password' => ['required', 'string', 'min:6'], 'role' => ['required', 'in:admin,sales']]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = AdminUser::updateOrCreate(
            ['username' => $username],
            [
                'password_hash' => Hash::make($password),
                'full_name' => $this->option('name'),
                'role' => $role,
            ]
        );

        $this->info("حساب «{$user->username}» با نقش «{$role}» با موفقیت ثبت/به‌روزرسانی شد.");

        return self::SUCCESS;
    }
}
