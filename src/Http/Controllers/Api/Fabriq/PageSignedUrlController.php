<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class PageSignedUrlController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $page = Fabriq::getModelClass('page')->findOrFail($id);

        $signedURL = URL::signedRoute('pages.show.preview', ['slug' => $page->slugs->first()->slug]);

        return response()->json([
            'computed_path' => $page->localizedPaths,
            'signed_url' => $signedURL,
            'encoded_signed_url' => base64_encode($signedURL),
        ]);
    }
}
