<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ButtonContentData extends Data
{
    public function __construct(
        public mixed $type,
        public mixed $text,
        public mixed $linkType,
        public mixed $page_id,
        public mixed $file,
        public mixed $url,
        public mixed $path,
        public mixed $thumb,
        public mixed $color,
        public mixed $arrow,
        public mixed $newTab,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromAttributes(array $attributes): self
    {
        return new self(
            type: self::attribute($attributes, 'type'),
            text: self::attribute($attributes, 'text'),
            linkType: self::attribute($attributes, 'linkType'),
            page_id: self::attribute($attributes, 'page_id'),
            file: self::attribute($attributes, 'file'),
            url: self::attribute($attributes, 'url'),
            path: self::attribute($attributes, 'path'),
            thumb: self::attribute($attributes, 'thumb'),
            color: self::attribute($attributes, 'color'),
            arrow: self::attribute($attributes, 'arrow'),
            newTab: self::attribute($attributes, 'newTab'),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function attribute(array $attributes, string $key): mixed
    {
        return array_key_exists($key, $attributes)
            ? $attributes[$key]
            : Optional::create();
    }
}
