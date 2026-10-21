<?php

namespace Karabin\Fabriq\ContentGetters;

class ContentOptions
{
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
}
