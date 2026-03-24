<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class ModelCountController extends Controller
{
    /**
     * Model map.
     *
     * @var array
     */
    protected $modelMap = [
        'pages' => 'page',
        'images' => 'image',
        'files' => 'file',
        'articles' => 'article',
    ];

    public function show(string $modelType): JsonResponse
    {
        if (! array_key_exists($modelType, $this->modelMap)) {
            return response()->json([
                'error' => [
                    'code' => ApiResponseCode::WrongArgs->value,
                    'http_code' => 400,
                    'message' => 'This model type can\'t be counted ('.$modelType.')',
                ],
            ], 400);
        }

        $count = Fabriq::getModelClass($this->modelMap[$modelType])
            ->without('media')
            ->get()->count();

        return response()->json([
            'data' => [
                'count' => $count,
            ],
        ]);
    }
}
