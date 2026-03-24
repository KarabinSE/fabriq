<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\ImageData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\UpdateImageRequest;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\QueryBuilders\ImageSort;
use Karabin\Fabriq\QueryBuilders\TagSort;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    /**
     * Get index of the resource.
     */
    public function index(Request $request): Response
    {
        $number = $request->integer('number', 100);

        $images = QueryBuilder::for(Fabriq::getFqnModel('image'))
            ->allowedSorts([
                'id', 'created_at', 'updated_at', 'alt_text',
                AllowedSort::custom('file_name', new ImageSort),
                AllowedSort::custom('c_name', new ImageSort, 'name'),
                AllowedSort::custom('size', new ImageSort),
                AllowedSort::custom('tags', new TagSort, 'images'),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedIncludes(...Image::RELATIONSHIPS)
            ->has('mediaImages')
            ->paginate($number);

        $collection = new PaginatedDataCollection(
            ImageData::class,
            $images->through(fn (Image $image) => ImageData::fromModel($image)),
        );

        return $collection->wrap('data')->toResponse($request);
    }

    /**
     * Get a single image.
     *
     * @param  int  $id
     */
    public function show(Request $request, $id): Response
    {
        $image = QueryBuilder::for(Fabriq::getFqnModel('image'))
            ->allowedIncludes(...Image::RELATIONSHIPS)
            ->where('id', $id)
            ->firstOrFail();

        /** @var Image $image */

        return ImageData::fromModel($image)->wrap('data')->toResponse($request);
    }

    public function update(UpdateImageRequest $request, int $id): Response
    {
        $image = Fabriq::getFqnModel('image')::findOrFail($id);
        $image->fill($request->validated());
        $image->imageTags = $request->validated()['tags'] ?? [];
        $media = $image->getFirstmedia('images');
        $media->name = $request->name;
        $media->save();
        $image->save();

        return ImageData::fromModel($image)->wrap('data')->toResponse($request);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $image = Fabriq::getFqnModel('image')::findOrFail($id);
        $image->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'The image has been deleted',
        ]);
    }
}
