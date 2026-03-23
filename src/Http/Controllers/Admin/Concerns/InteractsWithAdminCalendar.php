<?php

namespace Karabin\Fabriq\Http\Controllers\Admin\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Karabin\Fabriq\Models\Event;
use Karabin\Fabriq\Services\CalendarService;

trait InteractsWithAdminCalendar
{
    protected function calendarMonth(string $value): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            try {
                return CarbonImmutable::parse($value.'-01')->startOfMonth();
            } catch (\Throwable) {
                return CarbonImmutable::now()->startOfMonth();
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /**
     * @return Collection<int, Event>
     */
    protected function calendarEventsForMonth(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        $events = Event::query()
            ->dateRange($monthStart->toDateString(), $monthEnd->toDateString())
            ->orderBy('start')
            ->get();

        $computedEvents = CalendarService::getComputedDailyIntervals($events, $monthEnd);

        return $events
            ->toBase()
            ->merge($computedEvents)
            ->filter(fn ($event) => $event instanceof Event)
            ->values();
    }

    protected function calendarRedirect(Request $request, string $status): RedirectResponse
    {
        return to_route('admin.calendar.index', [
            'month' => $this->calendarMonth((string) $request->input('month'))->format('Y-m'),
        ])->with('status', $status);
    }
}
