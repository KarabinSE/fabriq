<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Actions\UpdateMenuItemPaths;
use Karabin\Fabriq\ContentGetters\ContentResourceContext;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Repositories\Decorators\CachingMenuRepository;

class MenuItemTreeController extends Controller
{
    /**
     * Return index of the resource.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $menuItemRoot = MenuItem::where('menu_id', $id)
            ->whereNull('parent_id')
            ->first();
        $items = MenuItem::orderBy('sortindex')->descendantsOf($menuItemRoot->id);
        $tree = $items->toTree();

        return ContentResourceContext::withModels(
            $items,
            fn () => response()->json([
                'data' => $tree->toArray(),
            ]),
            false,
        );
    }

    /**
     * Update the resoource.
     */
    public function update(Request $request, int $id, UpdateMenuItemPaths $updateMenuItemPaths): JsonResponse
    {
        $menuItemRoot = MenuItem::where('menu_id', $id)
            ->whereNull('parent_id')
            ->first();
        $menuItemRoot->touch();

        $treeData = $request->tree;
        MenuItem::rebuildSubtree($menuItemRoot, $treeData);
        $updateMenuItemPaths($menuItemRoot);

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Tree updated successfully',
        ]);
    }

    /**
     * Return specific menu.
     */
    public function show(CachingMenuRepository $repo, Request $request, string $slug): JsonResponse
    {
        return response()->json($repo->findBySlug($slug));
    }
}
