<?php

namespace Karabin\Fabriq\ContentGetters;

use Illuminate\Database\Eloquent\Model;
use Karabin\Fabriq\Models\ContentValue;

class ContentReferenceTransformer
{
    public static function normalize(mixed $value, ContentOptions $options, ?string $fieldType = null): mixed
    {
        if ($fieldType !== null && $options->isResourceReferenceKey($fieldType)) {
            return self::normalizeReference($value);
        }

        $value = self::toArray($value);
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $child) {
            if ($options->isResourceReferenceKey((string) $key)) {
                $value[$key] = self::normalizeReference($child);
            } elseif (is_array($child) || $child instanceof \stdClass) {
                $value[$key] = self::normalize($child, $options);
            }
        }

        return $value;
    }

    public static function resolve(mixed $value, ContentOptions $options, ?Model $model = null): mixed
    {
        $value = self::toArray($value);
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $child) {
            $getter = $options->getter((string) $key);

            if ($getter !== null && $options->isSpecialType((string) $key)) {
                $value[$key] = self::invokeGetter($getter, $child, $model);
            } elseif (is_array($child) || $child instanceof \stdClass) {
                $value[$key] = self::resolve($child, $options, $model);
            }
        }

        return $value;
    }

    public static function invokeGetter(string|callable $getter, mixed $value, ?Model $model): mixed
    {
        $contentValue = new ContentValue(['value' => self::toArray($value)]);

        if (is_string($getter) && $model && method_exists($model, $getter)) {
            return $model->{$getter}($contentValue);
        }

        if (is_string($getter) && class_exists($getter) && is_callable([$getter, 'get'])) {
            return $getter::get($contentValue);
        }

        return $getter($contentValue);
    }

    private static function normalizeReference(mixed $value): mixed
    {
        $value = self::toArray($value);

        $ids = ContentResourceContext::extractIds($value);

        if ($ids === []) {
            return $value;
        }

        if (is_array($value) && ContentResourceContext::isList($value) && count($ids) > 1) {
            return array_map(fn ($id) => is_numeric($id) ? (int) $id : $id, $ids);
        }

        return is_numeric($ids[0]) ? (int) $ids[0] : $ids[0];
    }

    private static function toArray(mixed $value): mixed
    {
        return $value instanceof \stdClass ? (array) $value : $value;
    }
}
