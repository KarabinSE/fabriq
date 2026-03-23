<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\MenuItemData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\UpdateMenuItemRequest;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class MenuItemController extends Controller
{
    /**
     * Show a single item.
     */
    public function show(Request $request, int $id): Response
    {
        $allowedIncludes = [
            ...MenuItem::RELATIONSHIPS,
            AllowedInclude::custom('content', new NoOpInclude),
            AllowedInclude::custom('localizedContent', new NoOpInclude),
        ];

        $item = QueryBuilder::for(Fabriq::getFqnModel('menuItem'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var MenuItem $item */

        return MenuItemData::fromModel($item)->wrap('data')->toResponse($request);
    }

    /**
     * Update a menu item.
     */
    public function update(UpdateMenuItemRequest $request, int $id): Response
    {
        $allowedIncludes = [
            ...MenuItem::RELATIONSHIPS,
            AllowedInclude::custom('content', new NoOpInclude),
            AllowedInclude::custom('localizedContent', new NoOpInclude),
        ];

        $item = QueryBuilder::for(Fabriq::getFqnModel('menuItem'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var MenuItem $item */
        $content = $request->input('content', []);

        if (! is_array($content)) {
            $content = [];
        }

        $item->updateMetaContent($content);
        $item->page_id = $request->input('item.page_id');
        $item->type = $request->input('item.type');
        $item->page_id = $request->input('item.page_id');
        if ($item->type === 'external') {
            $item->page_id = null;
        }
        $item->save();

        return MenuItemData::fromModel($item)->wrap('data')->toResponse($request);
    }

    /**
     * Store a new menu item.
     */
    public function store(UpdateMenuItemRequest $request, int $menuId): Response
    {
        $menuItemRoot = Fabriq::getFqnModel('menuItem')::where('menu_id', $menuId)
            ->whereNull('parent_id')
            ->first();
        $menuItem = Fabriq::getModelClass('menuItem');
        $menuItem->page_id = $request->input('item.page_id');
        $menuItem->parent_id = $menuItemRoot->id;
        $menuItem->menu_id = $menuId;
        $menuItem->type = $request->input('item.type');
        $menuItem->save();
        $content = $request->input('content', []);

        if (! is_array($content)) {
            $content = [];
        }

        $menuItem->updateMetaContent($content);

        return MenuItemData::fromModel($menuItem)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }

    /**
     * Delete a menu item.
     */
    public function destroy(int $id): JsonResponse
    {
        $menuItem = Fabriq::getFqnModel('menuItem')::findOrFail($id);
        $menuItem->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'The menu item has been deleted',
        ]);
    }
}
