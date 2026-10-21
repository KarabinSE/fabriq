<?php

namespace Karabin\Fabriq\ContentGetters;

use Karabin\Fabriq\Models\ContentValue;

interface GetterInterface
{
    /** Get transformed content data. */
    public static function get(ContentValue $contentValue, bool $isPublishing): mixed;
}
