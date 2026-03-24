<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Support\Str;
use Karabin\Fabriq\Models\Video;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class VideoData extends Data
{
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
        public ?string $c_name,
        public ?string $extension,
        public ?string $file_name,
        public ?string $thumb_src,
        public ?string $poster_src,
        public ?string $src,
        public ?string $alt_text,
        public ?string $caption,
        public ?string $mime_type,
        public ?int $size,
        public ?string $updated_at,
        public ?string $created_at,
        public Lazy|array $tags,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['tags'];
    }

    public static function fromModel(Video $video): self
    {
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            return new self(
                id: (int) $video->id,
                uuid: null,
                name: null,
                c_name: null,
                extension: null,
                file_name: null,
                thumb_src: null,
                poster_src: null,
                src: null,
                alt_text: $video->alt_text,
                caption: $video->caption,
                mime_type: null,
                size: null,
                updated_at: $video->updated_at?->toISOString(),
                created_at: $video->created_at?->toISOString(),
                tags: Lazy::create(fn () => self::buildTags($video)),
            );
        }

        return new self(
            id: (int) $video->id,
            uuid: (string) $media->uuid,
            name: (string) $media->name,
            c_name: (string) ($media->name.'.'.Str::afterLast($media->file_name, '.')),
            extension: (string) Str::afterLast($media->file_name, '.'),
            file_name: (string) $media->file_name,
            thumb_src: (string) $media->getUrl('thumb'),
            poster_src: (string) $media->getUrl('poster'),
            src: (string) $media->getUrl(),
            alt_text: $video->alt_text,
            caption: $video->caption,
            mime_type: $media->mime_type,
            size: $media->size,
            updated_at: $video->updated_at?->toISOString(),
            created_at: $video->created_at?->toISOString(),
            tags: Lazy::create(fn () => self::buildTags($video)),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildTags(Video $video): array
    {
        $tags = [];

        foreach ($video->tags as $tag) {
            $tags[] = [
                'id' => $tag->id,
                'name' => $tag->name,
                'value' => $tag->id,
                'type' => $tag->type,
            ];
        }

        return $tags;
    }
}
