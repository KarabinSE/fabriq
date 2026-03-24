<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Invitation;
use Spatie\LaravelData\Data;

class InvitationData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public ?int $invited_by,
        public string $uuid,
        public bool $is_valid,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Invitation $invitation): self
    {
        return new self(
            id: (int) $invitation->id,
            user_id: (int) $invitation->user_id,
            invited_by: $invitation->invited_by !== null ? (int) $invitation->invited_by : null,
            uuid: (string) $invitation->uuid,
            is_valid: $invitation->created_at !== null ? $invitation->created_at->diffInHours(now()) < 48 : false,
            created_at: $invitation->created_at?->toISOString(),
            updated_at: $invitation->updated_at?->toISOString(),
        );
    }
}
