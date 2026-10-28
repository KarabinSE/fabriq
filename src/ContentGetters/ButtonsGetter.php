<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Models\ContentValue;

class ButtonsGetter
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

        return $value->map(function ($button) {
            $buttonContentValue = ContentValue::make([
                'value' => $button,
            ]);

            return ButtonGetter::get($buttonContentValue);
        });
    }
}
