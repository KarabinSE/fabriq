<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Karabin\Fabriq\Data\ImageData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\Image;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\Response;

class ImageableController extends Controller
{
    /**
     * Get associated images for another model.
     *
     * @param  string  $model
     * @param  int  $modelId
     *
     * @throws InvalidArgumentException
     */
    public function index(Request $request, $model, $modelId): Response
    {
        $guess = Str::lower(Str::studly(Str::singular($model)));
        $relatedModelClass = config('fabriq.models.'.$guess);

        if (! class_exists($relatedModelClass)) {
            throw new InvalidArgumentException('The related model was not found, you might want to add a mapping in your '.Fabriq::getFqnModel('image').' model');
        }

        $relatedModel = $relatedModelClass::findOrFail($modelId);

        return ImageData::collect($relatedModel->images, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    /**
     * Associate an image with another model.
     *
     * @param  int  $imageId
     * @param  string  $model
     */
    public function store(Request $request, $imageId, $model): Response
    {
        $modelId = $request->model_id;
        $image = Image::findOrFail($imageId);
        $guess = Str::lower(Str::studly(Str::singular($model)));
        $relatedModelClass = config('fabriq.models.'.$guess);

        if (! class_exists($relatedModelClass)) {
            throw new InvalidArgumentException('The related model was not found, you might want to add a mapping in your '.Fabriq::getFqnModel('image').' model');
        }

        $relatedModel = $relatedModelClass::findOrFail($modelId);

        try {
            $relatedModel->images()->attach($image, ['sortindex' => $relatedModel->images->count()]);
        } catch (Exception $e) {
            return $this->errorWrongArgs('Image has no relation to '.$model);
        }

        return ImageData::fromModel($image)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }

    private function errorWrongArgs(string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => ApiResponseCode::WrongArgs->value,
                'http_code' => 400,
                'message' => $message,
            ],
        ], 400);
    }
}
