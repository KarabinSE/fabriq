<?php

namespace Karabin\Fabriq\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\ContentReferenceTransformer;
use Karabin\Fabriq\ContentGetters\ContentResourceContext;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Exceptions\ContentFieldNotFound;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\I18nDefinition;

trait HasContent
{
    /** @var array<string, ContentTemplateField> */
    protected array $contentFieldCache = [];

    protected static function bootHasContent(): void
    {
        static::deleting(fn ($model) => $model->contentDefinitions()->delete());
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }

    public function updateContent(array $fieldData, ?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $options = $this->getContentOptions();
        $updated = collect($fieldData)->map(function ($value, $fieldKey) use ($locale, $options) {
            $field = $this->getTemplateField((string) $fieldKey);
            $value = ContentReferenceTransformer::normalize($value, $options, $field->type);
            $definition = $this->contentDefinitions()->firstOrNew(['field_key' => $field->key]);
            $definition->setTranslation('content', $field->translated ? $locale : '*', $value);
            $definition->save();

            return $definition;
        });

        event(new ContentUpdated($this, $locale, collect($fieldData)));

        return $updated;
    }

    public function getSimpleFieldContent(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        $content = ContentResourceContext::contentFor($this);
        if ($content !== null) {
            $content = collect($content)->mapWithKeys(function (array $translations, string $fieldKey) use ($locale) {
                $value = $translations[$locale] ?? $translations['*'] ?? null;

                return $value === null ? [] : [$fieldKey => $value];
            });

            return $this->filterToTemplateFields($content);
        }

        $content = $this->contentDefinitions()
            ->get()
            ->mapWithKeys(function (I18nDefinition $definition) use ($locale) {
                $translations = $definition->getTranslations('content');
                $value = $translations[$locale] ?? $translations['*'] ?? null;

                return $value === null ? [] : [(string) $definition->field_key => $value];
            });

        return $this->filterToTemplateFields($content);
    }

    public function getFieldContent(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $options = $this->getContentOptions();

        return $this->getSimpleFieldContent($locale)->mapWithKeys(function ($value, $fieldKey) use ($options) {
            $field = $this->getTemplateField((string) $fieldKey);

            if ($field->repeater) {
                return [$fieldKey => $this->getRepeater($value)];
            }

            $getter = $options->getter((string) $fieldKey)
                ?? $options->getter($field->type);
            if ($getter !== null) {
                return [$fieldKey => ContentReferenceTransformer::invokeGetter($getter, $value, $this)];
            }

            return [$fieldKey => $value];
        });
    }

    public function updateMetaContent(array $data): array
    {
        return $this->updateContent($data)->all();
    }

    public function updateMetaItem(string|int $fieldKey, mixed $data): ContentValue
    {
        $this->updateContent([(string) $fieldKey => $data]);

        return new ContentValue(['key' => $fieldKey, 'value' => $data]);
    }

    public function getMeta(ContentValue $contentValue): mixed
    {
        $getter = $this->getContentOptions()->getter((string) $contentValue->key);

        return $getter !== null
            ? ContentReferenceTransformer::invokeGetter($getter, $contentValue->value, $this)
            : $contentValue->value;
    }

    public function getRepeater(mixed $repeater): array
    {
        $getters = $this->getContentOptions()->getters();

        return collect($repeater)->map(function ($item) use ($getters) {
            return collect($item)->map(function ($value, $key) use ($getters) {
                $getter = $getters[(string) $key] ?? null;

                if ($getter !== null) {
                    return ContentReferenceTransformer::invokeGetter($getter, $value, $this);
                }

                if (str_contains((string) $key, 'children')) {
                    return $this->handleChildRepeater($value);
                }

                return $value;
            });
        })->toArray();
    }

    public function handleChildRepeater(mixed $children): Collection
    {
        $getters = $this->getContentOptions()->getters();

        return collect($children)->map(function ($child) use ($getters) {
            return collect($child)->map(function ($value, $key) use ($getters) {
                $getter = $getters[(string) $key] ?? null;

                return $getter !== null
                    ? ContentReferenceTransformer::invokeGetter($getter, $value, $this)
                    : $value;
            });
        });
    }

    public function handleCallable(callable $callable, ContentValue $value): mixed
    {
        return $callable($value);
    }

    /** @return MorphMany<I18nDefinition, $this> */
    public function contentDefinitions(): MorphMany
    {
        return $this->morphMany(I18nDefinition::class, 'model')
            ->whereNotNull('field_key');
    }

    /**
     * @return array<int, string>
     */
    public function getContentCacheKeys(): array
    {
        return [];
    }

    protected function getTemplateField(string $fieldKey): ContentTemplateField
    {
        $cacheKey = $this->getAttribute('template_id').'|'.$fieldKey;

        if (isset($this->contentFieldCache[$cacheKey])) {
            return $this->contentFieldCache[$cacheKey];
        }

        $field = ContentResourceContext::templateField($this, $fieldKey);
        if ($field) {
            return $this->contentFieldCache[$cacheKey] = $field;
        }

        $query = ContentTemplateField::query()->where('key', $fieldKey);

        if ($templateId = $this->getAttribute('template_id')) {
            $query->where('template_id', $templateId);
        } elseif ($templateSlug = $this->getContentTemplateSlug()) {
            $query->whereHas('template', fn ($templateQuery) => $templateQuery->where('slug', $templateSlug));
        }

        $field = $query->first();

        if (! $field && $this->getAttribute('template_id')) {
            $field = ContentTemplateField::query()->where('key', $fieldKey)->first();
        }

        if (! $field) {
            throw ContentFieldNotFound::forKey($fieldKey);
        }

        return $this->contentFieldCache[$cacheKey] = $field;
    }

    protected function getContentTemplateSlug(): ?string
    {
        return null;
    }

    /**
     * @param  Collection<string, mixed>  $content
     */
    private function filterToTemplateFields(Collection $content): Collection
    {
        if ($content->isEmpty()) {
            return $content;
        }

        $templateId = $this->getAttribute('template_id');
        $templateSlug = $templateId ? null : $this->getContentTemplateSlug();
        if (! $templateId && ! $templateSlug) {
            return $content;
        }

        $templateFieldKeys = ContentResourceContext::templateFieldKeys($this);
        if ($templateFieldKeys === null) {
            $query = ContentTemplateField::query()->whereIn('key', $content->keys());

            if ($templateId) {
                $query->where('template_id', $templateId);
            } else {
                $query->whereHas('template', fn ($templateQuery) => $templateQuery->where('slug', $templateSlug));
            }

            $templateFieldKeys = $query->pluck('key')->all();
        }

        return $content->only($templateFieldKeys);
    }

    public function contentTemplateSlug(): ?string
    {
        return $this->getContentTemplateSlug();
    }

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create();
    }
}
