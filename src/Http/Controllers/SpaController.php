<?php

namespace Karabin\Fabriq\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->wantsJson()) {
            return response()->json('Get outta here!', 404);
        }

        /** @var view-string $viewString * */
        $viewString = 'vendor.fabriq.index';

        return view($viewString);
    }
}
