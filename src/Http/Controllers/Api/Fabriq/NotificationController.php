<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\NotificationData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\ClearNotificationRequest;
use Karabin\Fabriq\Models\Notification;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    private int $defaultPageSize = 100;

    public function index(Request $request): Response
    {
        $number = $request->integer('number', $this->defaultPageSize);

        $notifications = QueryBuilder::for(Fabriq::getFqnModel('notification'))
            ->allowedFilters([
                AllowedFilter::scope('unseen'),
                AllowedFilter::scope('seen'),
            ])
            ->allowedIncludes(...array_map(
                fn (string $include) => AllowedInclude::relationship($include),
                Notification::RELATIONSHIPS,
            ))
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($number);

        return NotificationData::collect($notifications, PaginatedDataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    public function update(ClearNotificationRequest $request, int $id): Response
    {
        $notification = Notification::findOrFail($id);
        $notification->cleared_at = now();

        $notification->save();

        return NotificationData::fromModel($notification)
            ->wrap('data')
            ->toResponse($request);
    }
}
