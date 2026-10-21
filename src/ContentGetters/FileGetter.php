<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Data\ContentResourceIdsData;
use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Data\FileContentData;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\File;

class FileGetter
{
    /**
     * Return a representation of an image.
     *
     * @param  bool  $publishing
     * @return mixed
     */
    public static function get(ContentValue $contentValue, $publishing = false)
    {
        if (empty($contentValue->toArray())) {
            return new ContentValueMetaData($contentValue->id);
        }

        $ids = ContentResourceContext::extractIds($contentValue->value);
        if ($ids === []) {
            return null;
        }

        $files = [];
        foreach (ContentResourceContext::findMany('file', $ids) as $resource) {
            if ($resource instanceof File) {
                $files[] = $resource;
            }
        }
        if ($files === []) {
            return null;
        }
        if ($publishing) {
            return new ContentResourceIdsData(array_map(fn (File $file) => (int) $file->id, $files));
        }

        $formatted = [];
        foreach ($files as $file) {
            $media = $file->getFirstMedia('files');
            if (! $media) {
                $formatted[] = null;

                continue;
            }

            $formatted[] = new FileContentData(
                id: (int) $file->id,
                uuid: (string) $media->uuid,
                name: (string) $media->name,
                file_name: (string) $media->file_name,
                thumb_src: $media->hasGeneratedConversion('file_thumb') ? (string) $media->getUrl('file_thumb') : '',
                extension: pathinfo($media->file_name, PATHINFO_EXTENSION),
                src: (string) $media->getUrl(),
                readable_name: $file->readable_name,
                caption: $file->caption,
                mime_type: (string) $media->mime_type,
                size: (int) $media->size,
                updated_at: $file->updated_at?->toISOString(),
                created_at: $file->created_at?->toISOString(),
            );
        }

        if (ContentResourceContext::hasMultipleReferenceValues($contentValue->value, $ids)) {
            return new Collection($formatted);
        }

        return $formatted[0] ?? null;
    }
}
