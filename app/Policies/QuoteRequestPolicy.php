<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\QuoteRequest;

class QuoteRequestPolicy
{
    public function view(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function update(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function updateTemperature(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function updateStatus(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function close(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function archive(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function unarchive(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin() || $request->assigned_to === $user->id;
    }

    public function delete(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function assign(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function restore(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(AdminUser $user, QuoteRequest $request): bool
    {
        return $user->isAdmin();
    }
}
