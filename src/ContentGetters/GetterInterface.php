<?php

namespace Karabin\Fabriq\ContentGetters;

use Karabin\Fabriq\Models\ContentValue;

interface GetterInterface
{
    /**
     * Get transformed data.
     *
     * @return array|null
     */
    public static function get(ContentValue $meta, bool $isPublishing);
}
