<?php

namespace Karabin\Fabriq\Exceptions;

use RuntimeException;

class ContentFieldNotFound extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Content field [{$key}] is not defined by a template.");
    }
}
