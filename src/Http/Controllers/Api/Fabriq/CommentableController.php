<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\CommentData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateCommentRequest;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\Response;

class CommentableController extends Controller
{
    /**
     * Model map.
     *
     * @var array
     */
    protected $modelMap = [
        'pages' => 'page',
    ];

    public function index(Request $request, string $modelName, int $modelId): Response
    {
        if (! array_key_exists($modelName, $this->modelMap)) {
            return $this->errorWrongArgs('The specified model is not commentable');
        }

        $modelClass = $this->modelMap[$modelName];
        $model = Fabriq::getModelClass($modelClass)->where('id', $modelId)
            ->with('comments', 'comments.user', 'comments.user.roles', 'comments.children', 'comments.children.user')
            ->firstOrFail();

        return CommentData::collect($model->comments, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    public function store(CreateCommentRequest $request, string $modelName, int $modelId): Response
    {
        if (! array_key_exists($modelName, $this->modelMap)) {
            return $this->errorWrongArgs('The specified model is not commentable');
        }

        $modelClass = $this->modelMap[$modelName];
        $model = Fabriq::getModelClass($modelClass)->where('id', $modelId)
            ->firstOrFail();
        $comment = $model->commentAs($request->user(), $request->comment, $request->parent_id ?? null);

        return CommentData::fromModel($comment)
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
