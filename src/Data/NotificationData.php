<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Notification;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class NotificationData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public ?int $notifiable_id,
        public ?string $notifiable_type,
        public ?string $location,
        public ?string $type,
        public ?string $content,
        public ?string $cleared_at,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array|null $notifiable,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['notifiable'];
    }

    public static function fromModel(Notification $notification): self
    {
        return new self(
            id: (int) $notification->id,
            user_id: (int) $notification->user_id,
            notifiable_id: $notification->notifiable_id ? (int) $notification->notifiable_id : null,
            notifiable_type: $notification->notifiable_type,
            location: $notification->location,
            type: $notification->type,
            content: $notification->content,
            cleared_at: $notification->cleared_at?->toISOString(),
            created_at: $notification->created_at?->toISOString(),
            updated_at: $notification->updated_at?->toISOString(),
            notifiable: Lazy::whenLoaded('notifiable', $notification, fn () => $notification->notifiable?->toArray()),
        );
    }
}
