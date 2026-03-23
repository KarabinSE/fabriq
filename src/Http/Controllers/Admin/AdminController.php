<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Karabin\Fabriq\Http\Controllers\Controller;

abstract class AdminController extends Controller
{
    protected function jsonGuard(Request $request): ?JsonResponse
    {
        if (! $request->wantsJson()) {
            return null;
        }

        return response()->json('Get outta here!', 404);
    }

    /**
     * @return array<string, int|null>
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
