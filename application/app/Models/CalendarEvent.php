<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    public const TYPE_FOLLOW_UP_CALL = 'follow_up_call';

    public const TYPE_CONSULTATION_MEETING = 'consultation_meeting';

    public const TYPE_PAYMENT_CALL = 'payment_call';

    public const TYPE_DELIVERY_MEETING = 'delivery_meeting';

    public const TYPES = [
        self::TYPE_FOLLOW_UP_CALL => ['label' => 'تماس پیگیری', 'icon' => 'phone'],
        self::TYPE_CONSULTATION_MEETING => ['label' => 'جلسه مشاوره', 'icon' => 'calendar'],
        self::TYPE_PAYMENT_CALL => ['label' => 'تماس پرداخت', 'icon' => 'phone'],
        self::TYPE_DELIVERY_MEETING => ['label' => 'جلسه تحویل', 'icon' => 'check-circle'],
    ];

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'برنامه‌ریزی‌شده',
        self::STATUS_COMPLETED => 'انجام‌شده',
        self::STATUS_CANCELLED => 'لغوشده',
    ];

    protected $fillable = [
        'type', 'title', 'quote_request_id', 'assigned_to', 'created_by',
        'starts_at', 'ends_at', 'timezone', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function quoteRequest()
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function assignee()
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type]['label'] ?? $this->type;
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type]['icon'] ?? 'calendar';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function displayTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        $carLabel = $this->quoteRequest?->car_label;

        return $carLabel ? $this->typeLabel().' — '.$carLabel : $this->typeLabel();
    }

    /**
     * Only the assignee's own events unless the viewer is admin — same scoping
     * convention as QuoteRequest/KanbanController (assigned_to === auth id for sales).
     */
    public function scopeForUser(Builder $query, AdminUser $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('assigned_to', $user->id);
    }

    /**
     * Events for the same assignee whose [starts_at, ends_at) interval overlaps the given
     * range. Cancelled events never block a new booking. Pass $excludeId when checking an
     * existing event being rescheduled so it doesn't collide with itself.
     */
    public static function overlapping(int $assignedTo, \DateTimeInterface $startsAt, \DateTimeInterface $endsAt, ?int $excludeId = null): Builder
    {
        return self::query()
            ->where('assigned_to', $assignedTo)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }
}
