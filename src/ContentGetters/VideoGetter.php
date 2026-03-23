<?php

namespace Karabin\Fabriq\ContentGetters;

use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Video;
use Karabin\TranslatableRevisions\Models\RevisionMeta;

class VideoGetter
{
    /**
     * Return a representation of an video.
     *
     * @param  bool  $publishing
     * @return mixed
     */
    public static function get(RevisionMeta $meta, $publishing = false)
    {
        if (empty($meta->toArray())) {
            return [
                'meta_id' => $meta->id,
            ];
        }

        $video = Fabriq::getModelClass('video')
            ->whereIn('id', (array) $meta->meta_value)->first();

        if (! $video) {
            return null;
        }
        if ($publishing) {
            return [$video->id];
        }
        $media = $video->getFirstMedia('videos');

        return [
            'id' => $video->id,
            'file_name' => $media->file_name,
            'src' => $media->getUrl(),
            'thumb_src' => $media->getUrl('thumb'),
            'poster_src' => $media->getUrl('poster'),
            'alt_text' => $video->alt_text,
            'caption' => $video->caption,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'meta_id' => $meta->id,
        ];
    }
}
