<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
            'role' => [Rule::in(['admin', 'sales', 'content_manager'])],
        ]);

        AdminUser::create([
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'full_name' => $data['full_name'] ?? null,
            'role' => $data['role'] ?? 'sales',
        ]);

        return back()->with('success', 'کاربر جدید ساخته شد.');
    }

    public function updateRole(Request $request, AdminUser $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'نمی‌توانید نقش خودتان را تغییر دهید.');

        $data = $request->validate(['role' => [Rule::in(['admin', 'sales', 'content_manager'])]]);
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
