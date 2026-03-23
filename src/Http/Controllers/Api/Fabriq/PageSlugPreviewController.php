<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\LivePageData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Repositories\EloquentPageRepository;
use Symfony\Component\HttpFoundation\Response;

class PageSlugPreviewController extends Controller
{
    public function show(EloquentPageRepository $repo, Request $request, string $slug): Response
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'error' => [
                    'code' => ApiResponseCode::Unauthorized->value,
                    'http_code' => 401,
                    'message' => 'The signature for the link is not valid',
                ],
            ], 401);
        }

        $result = $repo->findPreviewBySlug($slug);

        return LivePageData::fromModel($result)->wrap('data')->toResponse($request);
    }
}
