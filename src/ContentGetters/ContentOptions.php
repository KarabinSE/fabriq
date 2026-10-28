<?php

namespace Karabin\Fabriq\ContentGetters;

class ContentOptions
{
    private bool $indexable = false;

    /**
     * @var array<int, string>
     */
    private array $indexableKeys = [];

    private string $titleKey = '';

    /** @var callable|null */
    private $indexFunction = null;

    /**
     * @var array<int, string>
     */
    private array $specialTypes = [];

    /**
     * @var array<string, string|callable>
     */
    private array $getters = [];

    /**
     * @var array<int, string>
     */
    private array $resourceReferenceKeys = [];

    /**
     * @param  array<int, string>  $indexableKeys
     */
    public function setIndexable(
        bool $indexable,
        array $indexableKeys = [],
        string $titleKey = '',
        ?callable $callable = null,
    ): self {
        $this->indexable = $indexable;
        $this->indexableKeys = $indexableKeys;
        $this->titleKey = $titleKey;
        $this->indexFunction = $callable;

        return $this;
    }

    public static function create(): self
    {
        return new self;
    }

    /**
     * @param  array<int, string>  $specialTypes
     */
    public function registerSpecialTypes(array $specialTypes): self
    {
        $this->specialTypes = array_values(array_unique([...$this->specialTypes, ...$specialTypes]));

        return $this;
    }

    /**
     * @param  array<string, string|callable>  $getters
     */
    public function registerGetters(array $getters): self
    {
        $this->getters = array_merge($this->getters, $getters);

        return $this;
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function registerResourceReferenceKeys(array $keys): self
    {
        $this->resourceReferenceKeys = array_values(array_unique([...$this->resourceReferenceKeys, ...$keys]));

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function specialTypes(): array
    {
        return $this->specialTypes;
    }

    /**
     * @return array<string, string|callable>
     */
    public function getters(): array
    {
        return $this->getters;
    }

    public function getter(string $key): string|callable|null
    {
        return $this->getters[$key] ?? null;
    }

    public function isSpecialType(string $type): bool
    {
        return in_array($type, $this->specialTypes, true);
    }

    public function isResourceReferenceKey(string $key): bool
    {
        return in_array($key, $this->resourceReferenceKeys, true);
    }

    public function isIndexable(): bool
    {
        return $this->indexable;
    }

    /**
     * @return array<int, string>
     */
    public function indexableKeys(): array
    {
        return $this->indexableKeys;
    }

    public function titleKey(): string
    {
        return $this->titleKey;
    }

    public function indexFunction(): ?callable
    {
        return $this->indexFunction;
    }
}
