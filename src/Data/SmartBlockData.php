<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\SmartBlock;
use Karabin\TranslatableRevisions\Models\I18nLocale;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class SmartBlockData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array $localizedContent,
        public Lazy|array $content,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['localizedContent', 'content'];
    }

    public static function fromModel(SmartBlock $smartBlock): self
    {
        return new self(
            id: (int) $smartBlock->id,
            name: (string) $smartBlock->name,
            created_at: $smartBlock->created_at?->toISOString(),
            updated_at: $smartBlock->updated_at?->toISOString(),
            localizedContent: Lazy::create(fn () => self::buildLocalizedContent($smartBlock)),
            content: Lazy::create(fn () => $smartBlock->getFieldContent($smartBlock->revision)->toArray()),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function buildLocalizedContent(SmartBlock $smartBlock): array
    {
        $enabledLocales = I18nLocale::query()
            ->where('enabled', 1)
            ->select('iso_code')
            ->orderBy('id', 'desc')
            ->get();

        $result = [];

        foreach ($enabledLocales as $locale) {
            $result[(string) $locale->iso_code] = [
                'content' => $smartBlock->getSimpleFieldContent($smartBlock->revision, (string) $locale->iso_code),
            ];
        }

        return $result;
    }
}
