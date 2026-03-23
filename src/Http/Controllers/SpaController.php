<?php

namespace Karabin\Fabriq\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json('Get outta here!', 404);
        }

        abort(404);
    }
}
