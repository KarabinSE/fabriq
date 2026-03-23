<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\EventData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateEventRequest;
use Karabin\Fabriq\Models\Event;
use Karabin\Fabriq\Services\CalendarService;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $number = $request->integer('number', 100);

        $events = QueryBuilder::for(Fabriq::getFqnModel('event'))
            ->allowedFilters(AllowedFilter::scope('dateRange'))
            ->paginate($number);

        $end = CarbonImmutable::parse(explode(',', $request->filter['dateRange'])[1]);
        $computedEvents = CalendarService::getComputedDailyIntervals($events, $end);

        $mergedEvents = $events->toBase()->merge($computedEvents);
        $eventData = $mergedEvents->map(function ($event) {
            if ($event instanceof Event) {
                return EventData::fromModel($event);
            }

            return EventData::fromModel(Event::make((array) $event));
        });

        $collection = new DataCollection(EventData::class, $eventData);

        return $collection->wrap('data')->toResponse($request);
    }

    public function show(Request $request, int $id): Response
    {
        $event = Event::where('id', $id)->firstOrFail();

        return EventData::fromModel($event)->wrap('data')->toResponse($request);
    }

    public function store(CreateEventRequest $request): Response
    {
        $event = new Event;
        $event->fill($request->validated());
        $event->save();

        foreach ($request['localizedContent'] as $locale => $content) {
            $event->updateContent($content, $locale);
        }

        return EventData::fromModel($event)->wrap('data')->toResponse($request);
    }

    public function update(CreateEventRequest $request, int $id): Response
    {
        $event = Event::findOrFail($id);
        $event->fill($request->validated());
        $event->save();

        foreach ($request['localizedContent'] as $locale => $content) {
            $event->updateContent($content, $locale);
        }

        return EventData::fromModel($event)->wrap('data')->toResponse($request);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Event was deleted succesfully',
        ]);
    }
}
