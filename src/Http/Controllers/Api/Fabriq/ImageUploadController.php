<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Karabin\Fabriq\Data\ImageData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\UploadImageRequest;
use Symfony\Component\HttpFoundation\Response;

class ImageUploadController extends Controller
{
    public function store(UploadImageRequest $request): Response
    {
        $image = Fabriq::getModelClass('image');
        $image->save();
        try {
            $image->saveMedia($request->has('url'));
        } catch (\Throwable $exception) {
            $image->delete();

            return response()->json([
                'error' => [
                    'code' => ApiResponseCode::InternalError->value,
                    'http_code' => 500,
                    'message' => 'Kunde inte ladda upp filen',
                    'exception' => $exception->getMessage(),
                ],
            ], 500);
        }

        return ImageData::fromModel($image)
            ->toResponse($request)
            ->setStatusCode(200);
    }
}
