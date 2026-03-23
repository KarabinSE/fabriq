<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\PageTreeOptionData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class PageTreeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pageRoot = Fabriq::getModelClass('page')
            ->where('name', 'root')
            ->whereNull('parent_id')->first();

        $tree = Fabriq::getModelClass('page')->orderBy('sortindex')
            ->with('template')
            ->descendantsOf($pageRoot->id)
            ->toTree();

        if ($request->has('selectOptions')) {
            return response()->json([
                'data' => PageTreeOptionData::collectTree($tree),
            ]);
        }

        return response()->json([
            'data' => $tree,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $pageRoot = Fabriq::getModelClass('page')->whereNull('parent_id')
            ->first();

        $treeData = $request->tree;
        Fabriq::getModelClass('page')->rebuildSubtree($pageRoot, $treeData);

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Tree updated successfully',
        ]);
    }
}
