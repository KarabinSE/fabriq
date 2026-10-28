<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Data\ImageContentData;
use Karabin\Fabriq\Data\MediaContentData;
use Karabin\Fabriq\Data\VideoContentData;
use Karabin\Fabriq\Models\ContentValue;
use Spatie\LaravelData\Optional;

class MediaGetter implements GetterInterface
{
    public static function get(ContentValue $contentValue, bool $isPublishing = false): mixed
    {
        if ($contentValue->value['type'] === 'image') {
            $imageData = self::getImage($contentValue->value['image']);
            $videoData = array_key_exists('video', $contentValue->value)
                ? self::getVideo($contentValue->value['video'])
                : Optional::create();

            return new MediaContentData(
                type: 'image',
                fullscreen: $contentValue->value['fullscreen'] ?? true,
                image: $imageData,
                video: $videoData,
            );
        }

        if ($contentValue->value['type'] === 'video') {
            $videoData = self::getVideo($contentValue->value['video']);
            $imageData = array_key_exists('image', $contentValue->value)
                ? self::getImage($contentValue->value['image'])
                : Optional::create();

            return new MediaContentData(
                type: 'video',
                fullscreen: $contentValue->value['fullscreen'] ?? true,
                image: $imageData,
                video: $videoData,
            );
        }

        return new MediaContentData(
            type: 'image',
            fullscreen: true,
            image: null,
            video: null,
        );
    }

    private static function getImage(mixed $value): ImageContentData|Collection|null
    {
        return ImageGetter::get(new ContentValue(['value' => $value]));
    }

    private static function getVideo(mixed $value): VideoContentData|Collection|null
    {
        return VideoGetter::get(new ContentValue(['value' => $value]));
    }
}
