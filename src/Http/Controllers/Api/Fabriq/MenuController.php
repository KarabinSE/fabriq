<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\MenuData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\Menu;
use Spatie\LaravelData\PaginatedDataCollection;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    /**
     * Get index of the resource.
     */
    public function index(Request $request): Response
    {
        $number = $request->integer('number', 100);

        $paginator = Fabriq::getFqnModel('menu')::paginate($number);

        return MenuData::collect($paginator, PaginatedDataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    /**
     * Get a single resource.
     */
    public function show(Request $request, int $id): Response
    {
        $menu = Fabriq::getFqnModel('menu')::where('id', $id)
            ->firstOrFail();

        /** @var Menu $menu */

        return MenuData::fromModel($menu)->wrap('data')->toResponse($request);
    }

    /**
     * Create a new resource.
     */
    public function store(Request $request): Response
    {
        $menu = Fabriq::getModelClass('menu');
        $menu->name = $request->name;
        $menu->save();

        Fabriq::getFqnModel('menuItem')::create([
            'menu_id' => $menu->id,
        ]);

        return MenuData::fromModel($menu)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }

    /**
     * Update a resource.
     */
    public function update(Request $request, int $id): Response
    {
        $menu = Fabriq::getFqnModel('menu')::where('id', $id)
            ->firstOrFail();
        $menu->name = $request->name;
        $menu->save();

        /** @var Menu $menu */

        return MenuData::fromModel($menu)->wrap('data')->toResponse($request);
    }

    /**
     * Destroy a resource.
     */
    public function destroy(int $id): JsonResponse
    {
        $menu = Fabriq::getFqnModel('menu')::where('id', $id)->firstOrFail();
        $menu->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Menu has been deleted',
        ]);
    }
}
