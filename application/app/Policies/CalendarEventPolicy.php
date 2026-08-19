<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\CalendarEvent;

class CalendarEventPolicy
{
    public function view(AdminUser $user, CalendarEvent $event): bool
    {
        return $user->isAdmin() || $event->assigned_to === $user->id;
    }

    public function update(AdminUser $user, CalendarEvent $event): bool
    {
        return $user->isAdmin() || $event->assigned_to === $user->id;
    }

    public function reschedule(AdminUser $user, CalendarEvent $event): bool
    {
        return $user->isAdmin() || $event->assigned_to === $user->id;
    }

    public function cancel(AdminUser $user, CalendarEvent $event): bool
    {
        return $user->isAdmin() || $event->assigned_to === $user->id;
    }

    public function complete(AdminUser $user, CalendarEvent $event): bool
    {
        return $user->isAdmin() || $event->assigned_to === $user->id;
    }

    public function delete(AdminUser $user, CalendarEvent $event): bool
    {
        return $user->isAdmin() || $event->assigned_to === $user->id;
    }
}
