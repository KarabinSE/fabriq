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
use Karabin\Fabriq\Support\MenuTreeSerializer;

class MenuItemTreeController extends Controller
{
    /**
     * Return index of the resource.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $menuItemRoot = MenuItem::rootForMenu($id)->first();

        if ($menuItemRoot === null) {
            return response()->json(['data' => []]);
        }

        $items = MenuItem::query()
            ->orderBy('sortindex')
            ->descendantsOf($menuItemRoot->id);
        $contentModels = $items->concat($items->pluck('page')->filter());

        $tree = $items->toTree();

        return ContentResourceContext::withModels(
            $contentModels,
            fn () => response()->json(MenuTreeSerializer::modelTree($tree)),
        );
    }

    /**
     * Update the resource.
     */
    public function update(Request $request, int $id, UpdateMenuItemPaths $updateMenuItemPaths): JsonResponse
    {
        $menuItemRoot = MenuItem::rootForMenu($id)->firstOrFail();
        $menuItemRoot->touch();

        /** @var array<int, array<string, mixed>> $treeData */
        $treeData = $this->removeContentFromTree($request->input('tree'));
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

    /**
     * Remove serialized content before passing tree attributes to the nested-set rebuild.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function removeContentFromTree(array $items): array
    {
        foreach ($items as $index => $item) {
            unset($item['content']);

            if (isset($item['children'])) {
                $item['children'] = $this->removeContentFromTree($item['children']);
            }

            $items[$index] = $item;
        }

        return $items;
    }
}
