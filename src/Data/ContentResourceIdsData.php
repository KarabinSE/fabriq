<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class ContentResourceIdsData implements Arrayable, JsonSerializable
{
    /**
     * @param  array<int, int>  $ids
     */
    public function __construct(
        private array $ids,
    ) {}

    /**
     * @return array<int, int>
     */
    public function toArray(): array
    {
        return $this->ids;
    }

    /**
     * @return array<int, int>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
