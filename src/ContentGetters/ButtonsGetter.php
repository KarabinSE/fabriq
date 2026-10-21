<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Models\ContentValue;

class ButtonsGetter
{
    /**
     * Return a representation of an image.
     *
     * @param  bool  $publishing
     * @return mixed
     */
    public static function get(ContentValue $meta, $publishing = false)
    {
        if (empty($meta->toArray())) {
            return [
                'meta_id' => $meta->id,
            ];
        }
        $value = new Collection($meta->meta_value);

        $buttons = $value->map(function ($button) {
            $tempMeta = ContentValue::make([
                'meta_value' => $button,
            ]);
            $button = ButtonGetter::get($tempMeta);

            return $button;
        });

        return $buttons;
    }
}
