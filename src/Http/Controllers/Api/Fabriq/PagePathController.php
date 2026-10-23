<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class PagePathController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $page = Fabriq::getModelClass('page')->whereId($id)
            ->with(['menuItems' => fn ($query) => $query->without('page')])
            ->firstOrFail();

        $paths = $page->transformPaths();

        return response()->json(['data' => $paths]);
    }
}
