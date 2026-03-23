<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Models\Comment;
use Karabin\Fabriq\Models\Notification;

class NotificationController extends AdminController
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $user = $request->user();

        $unseen = Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('cleared_at')
            ->with(['notifiable.user', 'notifiable.commentable'])
            ->latest()
            ->paginate(10, ['*'], 'unseenPage');

        $seen = Notification::query()
            ->where('user_id', $user->id)
            ->whereNotNull('cleared_at')
            ->with(['notifiable.user', 'notifiable.commentable'])
            ->latest()
            ->paginate(10, ['*'], 'seenPage');

        return Inertia::render('Admin/Notifications/Index', [
            'pageTitle' => 'Notifications',
            'unseen' => $this->paginatedNotifications($unseen),
            'seen' => $this->paginatedNotifications($seen),
        ]);
    }

    public function update(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->forceFill([
            'cleared_at' => now(),
        ])->save();

        return to_route('admin.notifications')->with('status', 'Notisen markerades som hanterad.');
    }

    /**
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     pagination: array<string, int|null>
     * }
     */
    private function paginatedNotifications(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $this->transformNotifications($paginator),
            'pagination' => $this->paginationMeta($paginator),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformNotifications(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $notification) {
            if (! $notification instanceof Notification) {
                continue;
            }

            $items[] = $this->transformNotification($notification);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformNotification(Notification $notification): array
    {
        $notifiable = $notification->notifiable;
        $isComment = $notifiable instanceof Comment;
        $page = $isComment ? $notifiable->commentable : null;
        $author = $isComment ? $notifiable->user : null;
        $excerptSource = $isComment ? (string) $notifiable->comment : (string) ($notification->content ?? '');

        return [
            'id' => $notification->id,
            'title' => $isComment ? 'Omnämnd i kommentar' : 'Notis',
            'excerpt' => (string) Str::of(strip_tags($excerptSource))->squish()->limit(180),
            'createdAt' => $notification->created_at?->toIso8601String(),
            'createdAtLabel' => $notification->created_at?->diffForHumans(),
            'isCleared' => $notification->cleared_at !== null,
            'authorName' => $author?->name,
            'pageName' => $page?->name,
            'openPath' => $page ? '/admin/pages/'.$page->getKey().'/edit?openComments=1&commentId='.$notification->notifiable_id : null,
            'clearPath' => '/admin/notifications/'.$notification->id.'/clear',
        ];
    }
}
