<?php

namespace App\Http\Requests\Auth;

use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('username', 'password'))) {
            RateLimiter::hit($this->throttleKey(), 300);

            ActivityLogger::error('تلاش ناموفق ورود به پنل', ['username' => $this->string('username')]);

            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور اشتباه است.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Six failed attempts lock the account for five minutes, mirroring the
     * previous PHP panel's login-attempt guard.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 6)) {
            return;
        }

        event(new Lockout($this));

        ActivityLogger::error('قفل موقت ورود به‌دلیل تلاش‌های ناموفق زیاد', ['username' => $this->string('username')]);

        throw ValidationException::withMessages([
            'username' => 'به‌دلیل تلاش‌های ناموفق زیاد، چند دقیقه صبر کنید و دوباره امتحان کنید.',
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
