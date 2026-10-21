<?php

namespace Karabin\Fabriq\ContentGetters;

use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Data\SmartBlockContentData;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\SmartBlock;

class SmartBlockGetter
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
        $smartBlock = isset($ids[0]) ? ContentResourceContext::find('smartBlock', $ids[0]) : null;

        if (! $smartBlock instanceof SmartBlock) {
            return null;
        }

        if ($publishing) {
            return $smartBlock->id;
        }

        return new SmartBlockContentData(
            id: (int) $smartBlock->id,
            name: (string) $smartBlock->name,
            content: $smartBlock->getFieldContent(),
        );
    }
}
