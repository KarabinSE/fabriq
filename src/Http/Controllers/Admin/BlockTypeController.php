<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\TransformsBlockTypes;
use Karabin\Fabriq\Http\Requests\CreateBlockTypeRequest;
use Karabin\Fabriq\Http\Requests\UpdateBlockTypeRequest;
use Karabin\Fabriq\Models\BlockType;

class BlockTypeController extends AdminController
{
    use TransformsBlockTypes;

    public function index(Request $request): Response|JsonResponse
    {
        return $this->renderIndex($request);
    }

    public function show(Request $request, int $blockTypeId): Response|JsonResponse
    {
        return $this->renderIndex($request, $blockTypeId);
    }

    public function store(CreateBlockTypeRequest $request): RedirectResponse
    {
        $blockType = new BlockType;
        $blockType->fill($request->validated());
        $blockType->active = true;
        $blockType->type = 'block';
        $blockType->options = [
            'recommended_for' => [],
            'visible_for' => [],
            'hidden_for' => [],
        ];
        $blockType->save();

        return to_route('admin.block-types.edit', ['blockTypeId' => $blockType->id])
            ->with('status', 'Blocktypen skapades.');
    }

    public function update(UpdateBlockTypeRequest $request, int $blockTypeId): RedirectResponse
    {
        $blockType = BlockType::query()->findOrFail($blockTypeId);
        $blockType->fill($request->validated());
        $blockType->save();

        return to_route('admin.block-types.edit', ['blockTypeId' => $blockType->id])
            ->with('status', 'Blocktypen uppdaterades.');
    }

    public function destroy(int $blockTypeId): RedirectResponse
    {
        $blockType = BlockType::query()->findOrFail($blockTypeId);
        $blockType->delete();

        return to_route('admin.block-types.index')->with('status', 'Blocktypen raderades.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableBlockType(BlockType $blockType): array
    {
        $options = $this->normalizeBlockTypeOptions($blockType->options);

        return [
            'id' => $blockType->id,
            'name' => $blockType->name,
            'componentName' => $blockType->component_name,
            'base64Svg' => $blockType->base_64_svg,
            'hasChildren' => (bool) $blockType->has_children,
            'options' => $options,
        ];
    }

    private function renderIndex(Request $request, ?int $editingBlockTypeId = null): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $sort = (string) $request->string('sort', 'name');
        [$sortColumn, $sortDirection] = $this->normalizeBlockTypeSort($sort);

        $blockTypes = BlockType::query()
            ->where('active', 1)
            ->orderBy($sortColumn, $sortDirection)
            ->get();

        $editingBlockType = null;

        if ($editingBlockTypeId !== null) {
            $editingBlockTypeModel = BlockType::query()->findOrFail($editingBlockTypeId);
            $editingBlockType = $this->transformEditableBlockType($editingBlockTypeModel);
        }

        return Inertia::render('Admin/BlockTypes/Index', [
            'pageTitle' => 'Blocktyper',
            'filters' => [
                'sort' => ($sortDirection === 'desc' ? '-' : '').$sortColumn,
            ],
            'blockTypes' => $this->transformBlockTypes($blockTypes),
            'editingBlockType' => $editingBlockType,
            'templates' => $this->templateOptions(),
        ]);
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeBlockTypeSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'created_at', 'updated_at', 'component_name'];

        if (! in_array($column, $allowed, true)) {
            return ['name', 'asc'];
        }

        return [$column, $direction];
    }
}
