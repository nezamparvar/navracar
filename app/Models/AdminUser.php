<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'role',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isContentManager(): bool
    {
        return $this->role === 'content_manager';
    }

    /**
     * دسترسی به بخش‌های محتوایی (آگهی خودرو، وبلاگ، اسلایدر، منو) — هم مدیر
     * کامل و هم مدیر محتوا مجازند؛ تنظیمات/کاربران/قالب‌ها فقط مدیر کامل.
     */
    public function canManageContent(): bool
    {
        return $this->isAdmin() || $this->isContentManager();
    }

    public function displayName(): string
    {
        return $this->full_name ?: $this->username;
    }

    public function assignedRequests()
    {
        return $this->hasMany(QuoteRequest::class, 'assigned_to');
    }
}
