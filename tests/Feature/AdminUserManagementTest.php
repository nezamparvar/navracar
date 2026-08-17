<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'user-management-admin',
            'password_hash' => Hash::make('secret-password'),
            'full_name' => 'مدیر تست',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_create_user_with_supported_role_and_hashed_password(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'username' => 'new-content-manager',
            'password' => 'safe-password',
            'full_name' => 'مدیر محتوای جدید',
            'role' => 'content_manager',
        ]);

        $response
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'کاربر جدید با موفقیت ساخته شد.');

        $user = AdminUser::where('username', 'new-content-manager')->firstOrFail();

        $this->assertSame('مدیر محتوای جدید', $user->full_name);
        $this->assertSame('content_manager', $user->role);
        $this->assertTrue(Hash::check('safe-password', $user->password_hash));
        $this->assertNotSame('safe-password', $user->password_hash);
    }

    public function test_validation_errors_and_old_values_are_visible_in_the_form(): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->post(route('admin.users.store'), [
                'username' => 'invalid-user',
                'password' => 'short',
                'full_name' => 'کاربر نامعتبر',
                'role' => 'مدیر',
            ]);

        $response
            ->assertOk()
            ->assertSee('کاربر ساخته نشد. موارد زیر را اصلاح کنید:')
            ->assertSee('رمز عبور باید حداقل ۶ نویسه باشد.')
            ->assertSee('نقش انتخاب‌شده معتبر نیست.')
            ->assertSee('value="invalid-user"', false)
            ->assertSee('value="کاربر نامعتبر"', false)
            ->assertSee('data-testid="user-form-errors"', false);

        $this->assertDatabaseMissing('admin_users', ['username' => 'invalid-user']);
    }

    public function test_duplicate_username_error_is_shown_and_password_is_not_repopulated(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->post(route('admin.users.store'), [
                'username' => $admin->username,
                'password' => 'another-password',
                'full_name' => 'نام تکراری',
                'role' => 'sales',
            ]);

        $response
            ->assertOk()
            ->assertSee('این نام کاربری قبلاً ثبت شده است.')
            ->assertDontSee('value="another-password"', false);
    }

    public function test_success_message_is_rendered_inline_on_users_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->withSession(['success' => 'کاربر جدید با موفقیت ساخته شد.'])
            ->get(route('admin.users.index'));

        $response
            ->assertOk()
            ->assertSee('کاربر جدید با موفقیت ساخته شد.')
            ->assertSee('data-testid="user-form-success"', false);
    }

    public function test_role_values_have_stable_english_to_persian_mapping(): void
    {
        $this->assertSame(['admin', 'sales', 'content_manager'], AdminUser::ROLES);
        $this->assertSame([
            'admin' => 'مدیر',
            'sales' => 'کارشناس فروش',
            'content_manager' => 'مدیر محتوا',
        ], AdminUser::ROLE_LABELS);
    }
}
