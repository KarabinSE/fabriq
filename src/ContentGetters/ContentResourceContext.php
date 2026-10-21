<?php

namespace Karabin\Fabriq\ContentGetters;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\I18nDefinition;

class ContentResourceContext
{
    private static ?self $current = null;

    /** @var array<string, Model> */
    private array $resources = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $content = [];

    /** @var array<string, ContentTemplateField> */
    private array $templateFields = [];

    /** @var array<string, bool> */
    private array $loadedTypes = [];

    /** @var array<string, array<int, int|string>> */
    private array $additionalReferences = [];

    /**
     * @param  iterable<Model>  $models
     */
    public static function withModels(iterable $models, Closure $callback, bool $loadReferencedResources = true): mixed
    {
        $previous = self::$current;
        self::$current = new self($models, $loadReferencedResources);

        try {
            return $callback();
        } finally {
            self::$current = $previous;
        }
    }

    /**
     * @param  iterable<Model>  $models
     */
    public static function withModelsWhenContentIsRequested(Request $request, iterable $models, Closure $callback): mixed
    {
        if (! str_contains((string) $request->query('include', ''), 'content')) {
            return $callback();
        }

        return self::withModels($models, $callback);
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public static function find(string $type, int|string $id): ?Model
    {
        return self::findMany($type, [$id])->first();
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Model>
     */
    public static function findMany(string $type, array $ids): Collection
    {
        $ids = array_values($ids);
        $queryIds = array_values(array_unique($ids));

        if ($ids === []) {
            return collect();
        }

        if (! self::$current) {
            $model = Fabriq::getFqnModel($type);
            $query = $model::query();
            if (in_array($type, ['image', 'file', 'video'], true)) {
                $query->with('media');
            } elseif ($type === 'page') {
                $query->with(['menuItems' => fn ($menuItemQuery) => $menuItemQuery->without('page')]);
            }

            $resources = $query->whereIn('id', $queryIds)
                ->get()
                ->keyBy(fn (Model $resource) => $resource->getKey());

            return collect($ids)
                ->map(fn ($id) => $resources->get($id))
                ->filter()
                ->values();
        }

        $context = self::$current;
        $missingIds = ($context->loadedTypes[$type] ?? false)
            ? []
            : array_values(array_filter($queryIds, fn ($id) => ! isset($context->resources[$type.'|'.$id])));

        if ($missingIds !== []) {
            $model = Fabriq::getFqnModel($type);
            $query = $model::query();
            if (in_array($type, ['image', 'file', 'video'], true)) {
                $query->with('media');
            } elseif ($type === 'page') {
                $query->with(['menuItems' => fn ($menuItemQuery) => $menuItemQuery->without('page')]);
            }

            foreach ($query->whereIn('id', $missingIds)->get() as $resource) {
                $context->resources[$type.'|'.$resource->getKey()] = $resource;
            }
        }

        return collect($ids)
            ->map(fn ($id) => $context->resources[$type.'|'.$id] ?? null)
            ->filter()
            ->values();
    }

    public static function contentFor(Model $model): ?array
    {
        $key = $model->getMorphClass().'|'.$model->getKey();

        return self::$current && array_key_exists($key, self::$current->content)
            ? self::$current->content[$key]
            : null;
    }

    public static function templateField(Model $model, string $key): ?ContentTemplateField
    {
        $context = self::$current;
        if (! $context) {
            return null;
        }

        $templateId = $model->getAttribute('template_id');
        if ($templateId && isset($context->templateFields[$templateId.'|'.$key])) {
            return $context->templateFields[$templateId.'|'.$key];
        }

        $slug = method_exists($model, 'contentTemplateSlug') ? $model->contentTemplateSlug() : null;

        return $slug ? ($context->templateFields[$slug.'|'.$key] ?? null) : null;
    }

    /**
     * @param  iterable<Model>  $models
     */
    private function __construct(iterable $models, bool $loadReferencedResources)
    {
        $models = $this->includeLoadedChildren(collect($models)->values());

        $this->loadContent($models);
        if (! $loadReferencedResources) {
            return;
        }

        foreach ($models as $model) {
            $this->collectReferences($model->getAttribute('content'), $this->additionalReferences);
        }
        $this->loadTemplateFields($models);

        $smartBlockClass = Fabriq::getFqnModel('smartBlock');
        $loadedSmartBlocks = [];

        while (true) {
            $smartBlockIds = $this->referencedIds('smartBlock');
            $newIds = array_values(array_diff($smartBlockIds, $loadedSmartBlocks));
            $loadedSmartBlocks = [...$loadedSmartBlocks, ...$newIds];

            if ($newIds === []) {
                break;
            }

            $smartBlocks = $smartBlockClass::query()->whereIn('id', $newIds)->get();
            $this->loadedTypes['smartBlock'] = true;
            foreach ($smartBlocks as $smartBlock) {
                $this->resources['smartBlock|'.$smartBlock->getKey()] = $smartBlock;
            }
            $this->loadTemplateFields($smartBlocks);
            $smartBlocks = $this->includeLoadedChildren($smartBlocks);
            $this->loadContent($smartBlocks);
        }

        foreach (['image', 'file', 'video', 'page'] as $type) {
            $ids = $this->referencedIds($type);
            $this->loadedTypes[$type] = true;
            if ($ids === []) {
                continue;
            }

            $model = Fabriq::getFqnModel($type);
            $query = $model::query();
            if ($type === 'page') {
                $query->with(['menuItems' => fn ($menuItemQuery) => $menuItemQuery->without('page')]);
            } else {
                $query->with('media');
            }
            $resources = $query->whereIn('id', $ids)->get();

            foreach ($resources as $resource) {
                $this->resources[$type.'|'.$resource->getKey()] = $resource;
            }
        }
    }

    /**
     * @param  Collection<int, Model>  $models
     * @return Collection<int, Model>
     */
    private function includeLoadedChildren(Collection $models): Collection
    {
        $included = collect();
        $pending = $models;

        while ($pending->isNotEmpty()) {
            $model = $pending->shift();
            $key = $model->getMorphClass().'|'.$model->getKey();

            if ($included->has($key)) {
                continue;
            }

            $included->put($key, $model);

            foreach (['blocks', 'children'] as $relation) {
                if (! $model->relationLoaded($relation)) {
                    continue;
                }

                $related = $model->getRelation($relation);
                if ($related instanceof Model) {
                    $pending->push($related);
                } elseif ($related instanceof Collection) {
                    $pending = $pending->concat($related);
                }
            }
        }

        return $included->values();
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    private function loadTemplateFields(Collection $models): void
    {
        $templateIds = $models->pluck('template_id')->filter()->unique()->values();
        $templateSlugs = $models
            ->filter(fn (Model $model) => ! $model->getAttribute('template_id') && method_exists($model, 'contentTemplateSlug'))
            ->map(fn (Model $model) => $model->contentTemplateSlug())
            ->filter()
            ->unique()
            ->values();

        if ($templateIds->isEmpty() && $templateSlugs->isEmpty()) {
            return;
        }

        $fields = ContentTemplateField::query()
            ->with('template:id,slug')
            ->where(function ($query) use ($templateIds, $templateSlugs) {
                if ($templateIds->isNotEmpty()) {
                    $query->whereIn('template_id', $templateIds);
                }
                if ($templateSlugs->isNotEmpty()) {
                    $method = $templateIds->isNotEmpty() ? 'orWhereHas' : 'whereHas';
                    $query->{$method}('template', fn ($templateQuery) => $templateQuery->whereIn('slug', $templateSlugs));
                }
            })
            ->get();

        foreach ($fields as $field) {
            $this->templateFields[$field->template_id.'|'.$field->key] = $field;
            if ($field->template) {
                $this->templateFields[$field->template->slug.'|'.$field->key] = $field;
            }
        }
    }

    /**
     * @param  iterable<Model>  $models
     */
    private function loadContent(iterable $models): void
    {
        $groups = collect($models)
            ->filter(fn (Model $model) => $model->exists && method_exists($model, 'contentDefinitions'))
            ->groupBy(fn (Model $model) => $model->getMorphClass());

        foreach ($groups as $morphType => $group) {
            foreach ($group as $model) {
                $this->content[$morphType.'|'.$model->getKey()] ??= [];
            }

            $definitions = I18nDefinition::query()
                ->where('model_type', $morphType)
                ->whereIn('model_id', $group->map(fn (Model $model) => $model->getKey())->all())
                ->whereNotNull('field_key')
                ->get();

            foreach ($definitions as $definition) {
                $translations = $definition->getTranslations('content');
                $this->content[$morphType.'|'.$definition->model_id][$definition->field_key] = $translations;
            }
        }
    }

    /**
     * @return array<int, int|string>
     */
    private function referencedIds(string $type): array
    {
        $ids = [];

        foreach ($this->content as $fields) {
            foreach ($fields as $fieldKey => $translations) {
                foreach ($translations as $value) {
                    $this->collectReferences($value, $ids, $fieldKey);
                }
            }
        }

        return array_values(array_unique([
            ...($ids[$type] ?? []),
            ...($this->additionalReferences[$type] ?? []),
        ]));
    }

    /**
     * @param  array<string, array<int, int|string>>  $ids
     */
    private function collectReferences(mixed $value, array &$ids, ?string $key = null): void
    {
        if ($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if ($key !== null && in_array($key, ['image', 'main_image', 'meta_og_image', 'logo_image', 'file', 'video', 'smartBlock', 'page_id'], true)) {
            foreach (self::extractIds($value) as $id) {
                $type = match ($key) {
                    'image', 'main_image', 'meta_og_image', 'logo_image' => 'image',
                    'page_id' => 'page',
                    default => $key,
                };
                $ids[$type][] = $id;
            }
        }

        if (! is_array($value)) {
            return;
        }

        foreach ($value as $childKey => $child) {
            if (is_string($childKey)) {
                $this->collectReferences($child, $ids, $childKey);
            } else {
                $this->collectReferences($child, $ids);
            }
        }
    }

    /**
     * @return array<int, int|string>
     */
    public static function extractIds(mixed $value): array
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        if (isset($value['id']) && (is_int($value['id']) || (is_string($value['id']) && ctype_digit($value['id'])))) {
            return [$value['id']];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_int($item) || (is_string($item) && ctype_digit($item))) {
                $ids[] = $item;
            } elseif (is_array($item) && isset($item['id'])) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public static function hasMultipleReferenceValues(mixed $value, array $ids): bool
    {
        return is_array($value)
            && self::isList($value)
            && count($ids) > 1;
    }

    public static function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }
}
