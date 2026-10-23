<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Data\ContentResourceIdsData;
use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Data\VideoContentData;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\Video;

class VideoGetter
{
    /**
     * Return a representation of an video.
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

        $videos = [];
        foreach (ContentResourceContext::findMany('video', $ids) as $resource) {
            if ($resource instanceof Video) {
                $videos[] = $resource;
            }
        }
        if ($videos === []) {
            return null;
        }
        if ($publishing) {
            return new ContentResourceIdsData(array_map(fn (Video $video) => (int) $video->id, $videos));
        }

        $formatted = [];
        foreach ($videos as $video) {
            $media = $video->getFirstMedia('videos');
            if (! $media) {
                $formatted[] = null;

                continue;
            }

            $formatted[] = new VideoContentData(
                id: (int) $video->id,
                file_name: (string) $media->file_name,
                src: (string) $media->getUrl(),
                thumb_src: (string) $media->getUrl('thumb'),
                poster_src: (string) $media->getUrl('poster'),
                alt_text: $video->alt_text,
                caption: $video->caption,
                mime_type: (string) $media->mime_type,
                size: (int) $media->size,
                meta_id: $contentValue->id,
            );
        }

        if (ContentResourceContext::hasMultipleReferenceValues($contentValue->value, $ids)) {
            return new Collection($formatted);
        }

        return $formatted[0] ?? null;
    }
}
