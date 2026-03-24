<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminPages;
use Karabin\Fabriq\Http\Requests\UpdatePageRequest;
use Karabin\Fabriq\Models\Page;

class PagePublishController extends AdminController
{
    use InteractsWithAdminPages;

    public function store(UpdatePageRequest $request, int $pageId): RedirectResponse|JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::query()->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        $page = $this->persistPageEditorState($page, $request);

        Fabriq::getFqnModel('page')::withoutEvents(function () use ($page): void {
            $page->publish($page->revision);
        });

        return $this->pageMutationResponse($request, $page->fresh(), 'Sidan sparades och publicerades.');
    }
}
