<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminCalendar;
use Karabin\Fabriq\Models\Event;

class CalendarController extends AdminController
{
    use InteractsWithAdminCalendar;

    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $month = $this->calendarMonth((string) $request->string('month'));
        [$monthStart, $monthEnd] = [$month->startOfMonth(), $month->endOfMonth()];

        return Inertia::render('Admin/Calendar/Index', [
            'pageTitle' => 'Kalender',
            'filters' => [
                'month' => $month->format('Y-m'),
            ],
            'calendar' => [
                'monthLabel' => $month->translatedFormat('F Y'),
                'startsAt' => $monthStart->toDateString(),
                'endsAt' => $monthEnd->toDateString(),
            ],
            'events' => $this->transformCalendarEvents($this->calendarEventsForMonth($monthStart, $monthEnd)),
        ]);
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return array<int, array<string, mixed>>
     */
    private function transformCalendarEvents(Collection $events): array
    {
        return $events
            ->sortBy([
                ['start', 'asc'],
                ['start_time', 'asc'],
            ])
            ->values()
            ->map(function (Event $event): array {
                return $this->transformEditableEvent($event);
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableEvent(Event $event): array
    {
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $localizedContent = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $localizedContent[$isoCode] = $event->getSimpleFieldContent($event->revision, $isoCode)->toArray();
        }

        $title = (string) data_get($localizedContent, 'sv.title', $event->title);

        return [
            'id' => $event->id,
            'title' => $title,
            'start' => $event->start?->toIso8601String(),
            'end' => $event->end?->toIso8601String(),
            'startDate' => $event->start?->toDateString(),
            'endDate' => $event->end?->toDateString(),
            'startTime' => $event->start_time,
            'endTime' => $event->end_time,
            'dailyInterval' => (int) $event->daily_interval,
            'hasInterval' => (bool) $event->daily_interval,
            'localizedContent' => $localizedContent,
            'preview' => [
                'description' => (string) data_get($localizedContent, 'sv.description', ''),
                'location' => (string) data_get($localizedContent, 'sv.location', ''),
            ],
            'updatedAt' => $event->updated_at?->toIso8601String(),
        ];
    }
}
