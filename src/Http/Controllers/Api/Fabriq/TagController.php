<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\TagData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Tags\Tag;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    public const TAGGABLE_TYPES = [
        'images' => 'Karabin\Fabriq\Models\Image',
        'files' => 'Karabin\Fabriq\Models\File',
        'videos' => 'Karabin\Fabriq\Models\Video',
        'contacts' => 'Karabin\Fabriq\Models\Contact',
    ];

    public function index(Request $request): Response
    {
        $tags = QueryBuilder::for(Fabriq::getFqnModel('tag'))
            ->allowedFilters([
                AllowedFilter::scope('type', 'withType'),
            ])
            ->get();

        return TagData::collect($tags, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    /**
     * Associate a model with a tag.
     */
    public function store(Request $request): JsonResponse
    {
        $modelName = self::TAGGABLE_TYPES[$request->model_type] ?? null;
        if (! $modelName) {
            return response()->json([
                'error' => [
                    'code' => ApiResponseCode::WrongArgs->value,
                    'http_code' => 400,
                    'message' => 'This type is not taggable',
                ],
            ], 400);
        }

        $tags = collect($request->input('tags', []))
            ->filter(fn ($tag) => is_string($tag))
            ->map(fn (string $tag) => trim($tag))
            ->filter(fn (string $tag) => $tag !== '')
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->values()
            ->all();

        if ($tags === []) {
            return response()->json([
                'code' => ApiResponseCode::Success->value,
                'http_code' => 200,
                'message' => 'No tags to attach',
            ]);
        }

        foreach ($tags as $tag) {
            /** @var Tag $newTag */
            $newTag = Tag::findOrCreate($tag, $request->model_type);
            $newTag->save();
        }

        $modelName::whereIn('id', $request->models)
            ->select('id')
            ->get()
            ->each(function ($item) use ($tags, $request) {
                $item->attachTags($tags, $request->model_type);
            });

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Tags was attached',
        ]);
    }
}
