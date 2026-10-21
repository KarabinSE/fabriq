<?php

namespace Karabin\Fabriq\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Exceptions\ContentFieldNotFound;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Models\I18nTerm;

trait HasContent
{
    /** @var array<string, ContentTemplateField> */
    protected array $contentFieldCache = [];

    protected static function bootHasContent(): void
    {
        static::deleting(fn ($model) => $model->contentTerms()->delete());
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }

    public function updateContent(array $fieldData, ?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        $updated = collect($fieldData)->map(function ($value, $fieldKey) use ($locale) {
            $field = $this->getTemplateField((string) $fieldKey);
            $term = $this->contentTerms()->updateOrCreate(['field_key' => $field->key]);
            $definition = I18nDefinition::firstOrNew(['term_id' => $term->id]);
            $definition->setTranslation('content', $field->translated ? $locale : '*', $value);
            $definition->save();

            return [
                'term' => $term,
                'definition' => $definition,
            ];
        });

        event(new ContentUpdated($this, $locale, collect($fieldData)));

        return $updated;
    }

    public function getSimpleFieldContent(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        return $this->contentTerms()
            ->with('definition')
            ->get()
            ->mapWithKeys(function (I18nTerm $term) use ($locale) {
                $translations = $term->definition?->getTranslations('content') ?? [];
                $value = $translations[$locale] ?? $translations['*'] ?? null;

                return $value === null ? [] : [(string) $term->field_key => $value];
            });
    }

    public function getFieldContent(?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        return $this->getSimpleFieldContent($locale)->mapWithKeys(function ($value, $fieldKey) {
            $field = $this->getTemplateField((string) $fieldKey);

            if ($field->repeater) {
                return [$fieldKey => $this->getRepeater($value)];
            }

            $getter = $this->getContentGetters()[$field->type] ?? null;
            if ($getter !== null && method_exists($this, $getter)) {
                return [$fieldKey => $this->handleCallable([$this, $getter], new ContentValue(['meta_value' => $value]))];
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

        return new ContentValue(['meta_key' => $fieldKey, 'meta_value' => $data]);
    }

    public function getMeta(ContentValue $meta): mixed
    {
        $getter = $this->getContentGetters()[$meta->meta_key] ?? null;

        return $getter !== null && method_exists($this, $getter)
            ? $this->handleCallable([$this, $getter], $meta)
            : $meta->meta_value;
    }

    public function getRepeater(mixed $repeater): array
    {
        return collect($repeater)->map(function ($item) {
            return collect($item)->map(function ($value, $key) {
                $getter = $this->getContentGetters()[(string) $key] ?? null;

                if ($getter !== null && method_exists($this, $getter)) {
                    return $this->handleCallable([$this, $getter], new ContentValue(['meta_value' => $value]));
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
        return collect($children)->map(function ($child) {
            return collect($child)->map(function ($value, $key) {
                $getter = $this->getContentGetters()[(string) $key] ?? null;

                return $getter !== null && method_exists($this, $getter)
                    ? $this->handleCallable([$this, $getter], new ContentValue(['meta_value' => $value]))
                    : $value;
            });
        });
    }

    public function handleCallable(callable $callable, ContentValue $value): mixed
    {
        return $callable($value);
    }

    /** @return MorphMany<I18nTerm, $this> */
    public function contentTerms(): MorphMany
    {
        return $this->morphMany(I18nTerm::class, 'model')
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
     * @return array<string, string>
     */
    protected function getContentGetters(): array
    {
        return [];
    }
}
