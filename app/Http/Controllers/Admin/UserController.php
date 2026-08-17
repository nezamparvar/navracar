<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = AdminUser::withCount(['assignedRequests as assigned_count'])
            ->orderByRaw("role = 'admin' desc")
            ->orderBy('username')
            ->get();

        return view('admin.users.index', [
            'pageTitle' => 'مدیریت کاربران پنل',
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:admin_users,username'],
            'password' => ['required', 'string', 'min:6'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(AdminUser::ROLES)],
        ], [
            'username.required' => 'نام کاربری الزامی است.',
            'username.max' => 'نام کاربری نمی‌تواند بیشتر از ۶۴ نویسه باشد.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۶ نویسه باشد.',
            'full_name.max' => 'نام کامل نمی‌تواند بیشتر از ۲۵۵ نویسه باشد.',
            'role.required' => 'انتخاب نقش الزامی است.',
            'role.in' => 'نقش انتخاب‌شده معتبر نیست.',
        ]);

        try {
            AdminUser::create([
                'username' => $data['username'],
                'password_hash' => Hash::make($data['password']),
                'full_name' => $data['full_name'] ?? null,
                'role' => $data['role'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.users.index')
                ->withInput([
                    'username' => $data['username'],
                    'full_name' => $data['full_name'] ?? null,
                    'role' => $data['role'],
                ])
                ->withErrors([
                    'user_creation' => 'ساخت کاربر به‌دلیل خطای سرور انجام نشد. دوباره تلاش کنید یا لاگ سیستم را بررسی کنید.',
                ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر جدید با موفقیت ساخته شد.');
    }

    public function updateRole(Request $request, AdminUser $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'نمی‌توانید نقش خودتان را تغییر دهید.');

        $data = $request->validate(['role' => ['required', Rule::in(AdminUser::ROLES)]]);
        $user->update(['role' => $data['role']]);

        return back()->with('success', 'نقش کاربر به‌روزرسانی شد.');
    }

    public function resetPassword(Request $request, AdminUser $user)
    {
        $data = $request->validate(['new_password' => ['required', 'string', 'min:6']]);
        $user->update(['password_hash' => Hash::make($data['new_password'])]);

        return back()->with('success', 'رمز عبور بازنشانی شد.');
    }
}
