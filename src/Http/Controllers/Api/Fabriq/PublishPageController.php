<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class PublishPageController extends Controller
{
    /**
     * Publish a page revision.
     */
    public function store(Request $request, int $pageId): Response
    {
        $page = Fabriq::getFqnModel('page')::withoutEvents(function () use ($pageId) {
            $page = Fabriq::getFqnModel('page')::findOrFail($pageId);
            $page->publish($page->revision);

            return $page;
        });

        return Fabriq::getDto('page')::fromModel($page)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(200);
    }
}
