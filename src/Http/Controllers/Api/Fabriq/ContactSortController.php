<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class ContactSortController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $contacts = collect($request->contacts);

        foreach ($contacts as $contact) {
            Fabriq::getModelClass('contact')->where('id', $contact['id'])
                ->update(['sortindex' => $contact['sortindex']]);
        }

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Contact order has been updated',
        ]);
    }
}
