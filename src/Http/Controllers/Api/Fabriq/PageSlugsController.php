<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\LivePageData;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Repositories\EloquentPageRepository;
use Symfony\Component\HttpFoundation\Response;

class PageSlugsController extends Controller
{
    public function show(EloquentPageRepository $repo, Request $request, string $slug): Response
    {
        $result = $repo->findBySlug($slug);

        return LivePageData::fromModel($result)->wrap('data')->toResponse($request);
    }
}
