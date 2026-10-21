<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Karabin\Fabriq\Data\ButtonContentData;
use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\File;

class ButtonGetter
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
        $value = new Collection($contentValue->value);

        if (! isset($value['linkType'])) {
            return ButtonContentData::fromAttributes($value->all());
        }

        if ($value['linkType'] === 'external') {
            if (isset($value['url'])) {
                $value['path'] = $value['url'];
            }

            return ButtonContentData::fromAttributes($value->all());
        }

        if (Str::contains($value['linkType'], 'internal')) {
            if (empty($value['page_id'])) {
                return ButtonContentData::fromAttributes($value->all());
            }

            $page = ContentResourceContext::find('page', $value['page_id']);
            if (! $page) {
                return ButtonContentData::fromAttributes($value->all());
            }
            $paths = $page->localizedPaths->flatten();
            unset($value['url']);
            $value['path'] = $paths->first() ?? '/'.$page->slugs->where('locale', app()->getLocale())->first()->slug;

            return ButtonContentData::fromAttributes($value->all());
        }

        if ($value['linkType'] === 'file') {
            if (! isset($value['file'])) {
                return ButtonContentData::fromAttributes($value->all());
            }

            $fileIds = ContentResourceContext::extractIds($value['file']);
            $file = isset($fileIds[0]) ? ContentResourceContext::find('file', $fileIds[0]) : null;
            if (! $file instanceof File) {
                return ButtonContentData::fromAttributes($value->all());
            }
            $media = $file->getFirstMedia('files');
            if (! $media) {
                return ButtonContentData::fromAttributes($value->all());
            }
            $value['path'] = $media->getUrl();
            $value['url'] = $value['path'];
            $value['thumb'] = ($media->hasGeneratedConversion('file_thumb')) ? $media->getUrl('file_thumb') : '';

            return ButtonContentData::fromAttributes($value->all());
        }

        return ButtonContentData::fromAttributes($value->all());
    }
}
