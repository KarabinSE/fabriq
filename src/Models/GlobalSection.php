<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\Traits\HasContent;
use stdClass;

class GlobalSection extends Model
{
    use HasContent;

    protected $guarded = [];

    public function getContentCacheKeys(): array
    {
        return ['fabriq_globals|handle'];
    }

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes(['image'])
            ->registerGetters(['image' => ImageGetter::class])
            ->registerResourceReferenceKeys(['image']);
    }

    public function findContentTemplateField(string $fieldKey): ?ContentTemplateField
    {
        return $this->template->fields->firstWhere('key', $fieldKey);
    }

    public function getFieldsAttribute(): array
    {
        return $this->template->fields->toArray();
    }

    public function getContentAttribute(): stdClass
    {
        return (object) $this->fieldValues();
    }

    public function fieldValues(?string $locale = null): array
    {
        $values = $this->getFieldContent($locale);

        return $this->template->fields->mapWithKeys(
            fn (ContentTemplateField $field) => [$field->key => $values->get($field->key)]
        )->all();
    }
}
