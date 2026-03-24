<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Http\Controllers\Controller;

class EmailVerificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Email verification request sent',
        ]);
    }
}
