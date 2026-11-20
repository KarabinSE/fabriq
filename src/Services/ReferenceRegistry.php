<?php

namespace Karabin\Fabriq\Services;

use Closure;
use InvalidArgumentException;
use Karabin\Fabriq\ContentGetters\ContentResourceContext;

class ReferenceRegistry
{
    /** @var array<string, array{type: string, extractor: ?Closure}> */
    private array $fields = [];

    /** @var array<string, list<Closure>> */
    private array $dependencies = [];

    public function __construct()
    {
        foreach (['image', 'main_image', 'meta_og_image', 'logo_image'] as $key) {
            $this->registerField($key, 'image');
        }
        foreach (['file', 'video', 'smartBlock'] as $key) {
            $this->registerField($key, $key);
        }
    }

    /**
     * The extractor returns resource IDs from a field value.
     *
     * @param  Closure(mixed): iterable<int|string>|null  $extractor
     */
    public function registerField(string $key, string $resourceType, ?Closure $extractor = null): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('A reference field key cannot be empty.');
        }
        self::validateType($resourceType);
        $this->fields[$key] = ['type' => $resourceType, 'extractor' => $extractor];
    }

    public function hasField(string $key): bool
    {
        return isset($this->fields[$key]);
    }

    /** @return array{type: string, ids: iterable<int|string>} */
    public function extract(string $key, mixed $value): array
    {
        $field = $this->fields[$key];

        return [
            'type' => $field['type'],
            'ids' => $field['extractor'] !== null
                ? ($field['extractor'])($value)
                : ContentResourceContext::extractIds($value),
        ];
    }

    /**
     * @param  Closure(int): iterable<array{type: string, id: int}>  $resolver
     */
    public function registerDependencies(string $type, Closure $resolver): void
    {
        self::validateType($type);
        $this->dependencies[$type][] = $resolver;
    }

    public static function validateType(string $type): void
    {
        if ($type === '' || strlen($type) > 32 || str_contains($type, ':')) {
            throw new InvalidArgumentException('Reference types must be 1-32 characters and cannot contain a colon.');
        }
    }

    /** @return iterable<array{type: string, id: int}> */
    public function dependencies(string $type, int $id): iterable
    {
        foreach ($this->dependencies[$type] ?? [] as $resolver) {
            foreach ($resolver($id) as $reference) {
                self::validateType($reference['type']);
                yield ['type' => $reference['type'], 'id' => self::validateId($reference['id'])];
            }
        }
    }

    private static function validateId(mixed $id): int
    {
        if (! is_int($id) || $id <= 0) {
            throw new InvalidArgumentException('Reference dependency IDs must be positive integers.');
        }

        return $id;
    }
}
