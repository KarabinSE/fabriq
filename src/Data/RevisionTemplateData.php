<?php

namespace Karabin\Fabriq\Data;

use Karabin\TranslatableRevisions\Models\RevisionTemplate;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class RevisionTemplateData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $type,
        public ?bool $locked,
        public mixed $source_model_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array $fields,
        public Lazy|array $groupedFields,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['fields', 'groupedFields'];
    }

    public static function fromModel(RevisionTemplate $template): self
    {
        return new self(
            id: (int) $template->id,
            name: $template->name,
            type: $template->type,
            locked: $template->locked !== null ? (bool) $template->locked : null,
            source_model_id: $template->source_model_id,
            created_at: $template->created_at?->toISOString(),
            updated_at: $template->updated_at?->toISOString(),
            fields: Lazy::create(fn () => $template->fields->map(fn ($field) => $field->toArray())->values()->all()),
            groupedFields: Lazy::create(fn () => $template->fields->groupBy('group')->toArray()),
        );
    }
}
