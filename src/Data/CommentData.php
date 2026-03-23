<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Comment;
use Karabin\Fabriq\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class CommentData extends Data
{
    public function __construct(
        public int $id,
        public string $comment,
        public string $created_at,
        public ?string $anonmyzed_at,
        public ?int $user_id,
        public Lazy|array $user,
        public Lazy|array $children,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['user', 'children'];
    }

    public static function fromModel(Comment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            comment: (string) $comment->comment,
            created_at: (string) $comment->created_at?->toIsoString(),
            anonmyzed_at: $comment->anonymized_at ? (string) $comment->anonymized_at : null,
            user_id: $comment->user_id ? (int) $comment->user_id : null,
            user: Lazy::create(fn () => $comment->user instanceof User ? UserData::fromModel($comment->user)->toArray() : []),
            children: Lazy::create(fn () => $comment->children->map(fn (Comment $child) => self::fromModel($child)->toArray())->values()->all()),
        );
    }
}
