<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Event;
use Karabin\TranslatableRevisions\Models\I18nLocale;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class EventData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $start,
        public string $end,
        public string $start_time,
        public string $end_time,
        public int $daily_interval,
        public bool $has_interval,
        public string $updated_at,
        public Lazy|array $localizedContent,
        public Lazy|array $content,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['localizedContent', 'content'];
    }

    public static function fromModel(Event $event): self
    {
        return new self(
            id: (int) $event->id,
            title: (string) $event->title,
            start: $event->start?->toISOString() ?? '',
            end: $event->end?->toISOString() ?? '',
            start_time: (string) $event->start_time,
            end_time: (string) $event->end_time,
            daily_interval: (int) $event->daily_interval,
            has_interval: (bool) $event->daily_interval,
            updated_at: (string) $event->updated_at->toISOString(),
            localizedContent: Lazy::create(fn () => self::buildLocalizedContent($event)),
            content: Lazy::create(fn () => $event->getFieldContent()->toArray()),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function buildLocalizedContent(Event $event): array
    {
        $enabledLocales = I18nLocale::query()
            ->where('enabled', 1)
            ->select('iso_code')
            ->orderBy('id', 'desc')
            ->get();

        $result = [];

        foreach ($enabledLocales as $locale) {
            $result[(string) $locale->iso_code] = [
                'content' => $event->getSimpleFieldContent($event->revision, (string) $locale->iso_code),
            ];
        }

        return $result;
    }
}
