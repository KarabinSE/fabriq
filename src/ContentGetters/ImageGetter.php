<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Data\ContentResourceIdsData;
use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Data\ImageContentData;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\Image;

class ImageGetter extends BaseGetter implements GetterInterface
{
    public static function get(ContentValue $contentValue, bool $publishing = false): mixed
    {
        if (empty($contentValue->toArray())) {
            return new ContentValueMetaData($contentValue->id);
        }

        if ($contentValue->value === null || empty($contentValue->value)) {
            return null;
        }

        $ids = ContentResourceContext::extractIds($contentValue->value);
        if ($ids === []) {
            return null;
        }

        $images = [];
        foreach (ContentResourceContext::findMany('image', $ids) as $resource) {
            if ($resource instanceof Image) {
                $images[] = $resource;
            }
        }
        if ($images === []) {
            return null;
        }
        if ($publishing) {
            return new ContentResourceIdsData(array_map(fn (Image $image) => (int) $image->id, $images));
        }

        $formatted = [];
        foreach ($images as $image) {
            $media = $image->getFirstMedia('images');
            if (! $media) {
                $formatted[] = null;

                continue;
            }
            $responsiveFiles = $media->responsiveImages()->files;
            $responsiveImage = $responsiveFiles->first();

            $formatted[] = new ImageContentData(
                id: (int) $image->id,
                file_name: (string) $media->file_name,
                src: (string) $media->getUrl(),
                thumb_src: (string) $media->getUrl('thumb'),
                webp_src: $media->hasGeneratedConversion('webp') ? (string) $media->getUrl('webp') : '',
                og_image_src: $media->hasGeneratedConversion('og_image') ? (string) $media->getUrl('og_image') : '',
                srcset: $media->getSrcSet() ? (string) $media->getSrcset() : null,
                alt_text: (string) $image->alt_text,
                caption: $image->caption,
                mime_type: (string) $media->mime_type,
                size: (int) $media->size,
                width: $responsiveImage?->width(),
                height: $responsiveImage?->height(),
                custom_crop: (bool) $image->custom_crop,
                responsive: (string) $media->toHtml(),
                x_position: (string) $image->x_position,
                y_position: (string) $image->y_position,
                meta_id: $contentValue->id,
            );
        }

        if (ContentResourceContext::hasMultipleReferenceValues($contentValue->value, $ids)) {
            return new Collection($formatted);
        }

        return $formatted[0] ?? null;
    }
}
