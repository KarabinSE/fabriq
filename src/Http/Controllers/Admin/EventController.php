<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminCalendar;
use Karabin\Fabriq\Http\Requests\CreateEventRequest;
use Karabin\Fabriq\Models\Event;

class EventController extends AdminController
{
    use InteractsWithAdminCalendar;

    public function store(CreateEventRequest $request): RedirectResponse
    {
        $event = new Event;
        $event->fill($request->validated());
        $event->save();

        foreach ((array) $request->input('localizedContent', []) as $locale => $content) {
            $event->updateContent((array) $content, (string) $locale);
        }

        return $this->calendarRedirect($request, 'Händelsen skapades.');
    }

    public function update(CreateEventRequest $request, int $eventId): RedirectResponse
    {
        $event = Event::query()->findOrFail($eventId);
        $event->fill($request->validated());
        $event->save();

        foreach ((array) $request->input('localizedContent', []) as $locale => $content) {
            $event->updateContent((array) $content, (string) $locale);
        }

        return $this->calendarRedirect($request, 'Händelsen uppdaterades.');
    }

    public function destroy(Request $request, int $eventId): RedirectResponse
    {
        $event = Event::query()->findOrFail($eventId);
        $event->delete();

        return $this->calendarRedirect($request, 'Händelsen raderades.');
    }
}
