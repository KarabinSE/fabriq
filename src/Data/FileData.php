<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Support\Str;
use Karabin\Fabriq\Models\File;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class FileData extends Data
{
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
        public ?string $c_name,
        public ?string $extension,
        public ?string $file_name,
        public ?string $thumb_src,
        public ?string $src,
        public ?string $readable_name,
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

    public static function fromModel(File $file): self
    {
        $media = $file->getFirstMedia('files');

        if (! $media) {
            return new self(
                id: (int) $file->id,
                uuid: null,
                name: null,
                c_name: null,
                extension: null,
                file_name: null,
                thumb_src: null,
                src: null,
                readable_name: $file->readable_name,
                caption: $file->caption,
                mime_type: null,
                size: null,
                updated_at: $file->updated_at?->toISOString(),
                created_at: $file->created_at?->toISOString(),
                tags: Lazy::create(fn () => self::buildTags($file)),
            );
        }

        return new self(
            id: (int) $file->id,
            uuid: (string) $media->uuid,
            name: (string) $media->name,
            c_name: (string) ($media->name.'.'.Str::afterLast($media->file_name, '.')),
            extension: (string) Str::afterLast($media->file_name, '.'),
            file_name: (string) $media->file_name,
            thumb_src: $media->hasGeneratedConversion('file_thumb') ? (string) $media->getUrl('file_thumb') : '',
            src: (string) $media->getUrl(),
            readable_name: $file->readable_name,
            caption: $file->caption,
            mime_type: $media->mime_type,
            size: $media->size,
            updated_at: $file->updated_at?->toISOString(),
            created_at: $file->created_at?->toISOString(),
            tags: Lazy::create(fn () => self::buildTags($file)),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildTags(File $file): array
    {
        $tags = [];

        foreach ($file->tags as $tag) {
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
