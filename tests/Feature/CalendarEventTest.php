<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CalendarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $username): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password_hash' => bcrypt('secret'),
            'full_name' => ucfirst($role).' Test',
            'role' => $role,
        ]);
    }

    public function test_sales_can_create_an_event_for_themselves(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-1');

        $response = $this->actingAs($sales)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(10, 30)->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('calendar_events', 1);
        $event = CalendarEvent::first();
        $this->assertSame($sales->id, $event->assigned_to);
        $this->assertSame($sales->id, $event->created_by);
        $this->assertSame(CalendarEvent::STATUS_SCHEDULED, $event->status);
    }

    public function test_sales_cannot_create_an_event_assigned_to_someone_else(): void
    {
        $salesA = $this->makeUser('sales', 'sales-cal-a');
        $salesB = $this->makeUser('sales', 'sales-cal-b');

        $response = $this->actingAs($salesA)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $salesB->id,
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->addMinutes(15)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_admin_can_create_an_event_for_any_sales_user(): void
    {
        $admin = $this->makeUser('admin', 'admin-cal-1');
        $sales = $this->makeUser('sales', 'sales-cal-2');

        $response = $this->actingAs($admin)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_DELIVERY_MEETING,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('calendar_events', 1);
        $this->assertSame($sales->id, CalendarEvent::first()->assigned_to);
    }

    public function test_overlapping_events_for_the_same_assignee_are_rejected(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-3');

        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($sales)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(10, 30)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(11, 30)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('calendar_events', 1);
    }

    public function test_non_overlapping_back_to_back_events_are_allowed(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-4');

        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($sales)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(11, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(11, 30)->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('calendar_events', 2);
    }

    public function test_cancelled_events_do_not_block_new_bookings(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-5');

        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => CalendarEvent::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($sales)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(10, 15)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(10, 45)->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('calendar_events', 2);
    }

    public function test_sales_only_sees_their_own_events_in_the_calendar(): void
    {
        $salesA = $this->makeUser('sales', 'sales-cal-6a');
        $salesB = $this->makeUser('sales', 'sales-cal-6b');

        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $salesA->id,
            'created_by' => $salesA->id,
            'starts_at' => now()->setTime(10, 0),
            'ends_at' => now()->setTime(11, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);
        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $salesB->id,
            'created_by' => $salesB->id,
            'starts_at' => now()->setTime(12, 0),
            'ends_at' => now()->setTime(13, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($salesA)->get(route('admin.calendar.index', ['view' => 'day']));

        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(1, $events);
        $this->assertSame($salesA->id, $events->first()->assigned_to);
    }

    public function test_admin_sees_all_events_in_the_calendar(): void
    {
        $admin = $this->makeUser('admin', 'admin-cal-2');
        $salesA = $this->makeUser('sales', 'sales-cal-7a');
        $salesB = $this->makeUser('sales', 'sales-cal-7b');

        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $salesA->id,
            'created_by' => $salesA->id,
            'starts_at' => now()->setTime(10, 0),
            'ends_at' => now()->setTime(11, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);
        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $salesB->id,
            'created_by' => $salesB->id,
            'starts_at' => now()->setTime(12, 0),
            'ends_at' => now()->setTime(13, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.calendar.index', ['view' => 'day']));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('events'));
    }

    public function test_end_before_start_is_rejected(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-8');

        $response = $this->actingAs($sales)->post(route('admin.calendar.store'), [
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(11, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasErrors('ends_at');
        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_owner_can_complete_and_cancel_their_event(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-9');
        $event = CalendarEvent::create([
            'type' => CalendarEvent::TYPE_PAYMENT_CALL,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(15),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $this->actingAs($sales)->post(route('admin.calendar.complete', $event))->assertRedirect();
        $this->assertSame(CalendarEvent::STATUS_COMPLETED, $event->fresh()->status);
    }

    public function test_sales_cannot_complete_another_sales_users_event(): void
    {
        $salesA = $this->makeUser('sales', 'sales-cal-10a');
        $salesB = $this->makeUser('sales', 'sales-cal-10b');
        $event = CalendarEvent::create([
            'type' => CalendarEvent::TYPE_PAYMENT_CALL,
            'assigned_to' => $salesA->id,
            'created_by' => $salesA->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(15),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $this->actingAs($salesB)->post(route('admin.calendar.complete', $event))->assertForbidden();
        $this->assertSame(CalendarEvent::STATUS_SCHEDULED, $event->fresh()->status);
    }

    public function test_rescheduling_into_an_overlap_is_rejected(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-11');
        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);
        $movable = CalendarEvent::create([
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay()->setTime(14, 0),
            'ends_at' => now()->addDay()->setTime(14, 30),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($sales)->put(route('admin.calendar.update', $movable), [
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(9, 30)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(10, 30)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertSame(14, $movable->fresh()->starts_at->hour);
    }

    public function test_rescheduling_an_event_against_itself_is_allowed(): void
    {
        $sales = $this->makeUser('sales', 'sales-cal-12');
        $event = CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 0),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($sales)->put(route('admin.calendar.update', $event), [
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'starts_at' => now()->addDay()->setTime(9, 15)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(10, 15)->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame(15, $event->fresh()->starts_at->minute);
    }

    public function test_content_manager_cannot_access_the_calendar(): void
    {
        $content = $this->makeUser('content_manager', 'content-cal-1');

        $this->actingAs($content)->get(route('admin.calendar.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.calendar.index'))->assertRedirect(route('login'));
    }
}
