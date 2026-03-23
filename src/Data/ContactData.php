<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Contact;
use Karabin\TranslatableRevisions\Models\I18nLocale;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class ContactData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public bool $published,
        public int $sortindex,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array $localizedContent,
        public Lazy|array $content,
        public Lazy|array $tags,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['localizedContent', 'content', 'tags'];
    }

    public static function fromModel(Contact $contact): self
    {
        $tags = [];

        foreach ($contact->tags as $tag) {
            $tags[] = [
                'id' => $tag->id,
                'name' => $tag->name,
                'value' => $tag->id,
                'type' => $tag->type,
            ];
        }

        return new self(
            id: (int) $contact->id,
            name: (string) $contact->name,
            email: $contact->email,
            phone: $contact->phone,
            published: (bool) $contact->published,
            sortindex: (int) $contact->sortindex,
            created_at: $contact->created_at?->toISOString(),
            updated_at: $contact->updated_at?->toISOString(),
            localizedContent: Lazy::create(fn () => self::buildLocalizedContent($contact)),
            content: Lazy::create(fn () => $contact->getFieldContent($contact->revision)->toArray()),
            tags: Lazy::create(fn () => $tags),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function buildLocalizedContent(Contact $contact): array
    {
        $enabledLocales = I18nLocale::query()
            ->where('enabled', 1)
            ->select('iso_code')
            ->orderBy('id', 'desc')
            ->get();

        $result = [];

        foreach ($enabledLocales as $locale) {
            $result[(string) $locale->iso_code] = [
                'content' => $contact->getSimpleFieldContent($contact->revision, (string) $locale->iso_code),
            ];
        }

        return $result;
    }
}
