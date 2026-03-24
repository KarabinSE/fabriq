<?php

namespace Karabin\Fabriq\Http\Controllers\Admin\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\User;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;
use Karabin\TranslatableRevisions\Models\RevisionTemplateField;

trait InteractsWithAdminPages
{
    /**
     * @param  iterable<int, User>  $users
     * @return array<int, array{id: int, name: string, email: string}>
     */
    protected function transformCommentUsers(iterable $users): array
    {
        $items = [];

        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $items[] = [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function pageTree(): array
    {
        $pageRoot = Fabriq::getFqnModel('page')::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->first();

        if ($pageRoot === null) {
            return [];
        }

        $tree = Fabriq::getFqnModel('page')::query()
            ->orderBy('sortindex')
            ->with('template')
            ->descendantsOf($pageRoot->id)
            ->toTree();

        return $this->transformPageTreeItems($tree);
    }

    /**
     * @return array<int, array{id: int, name: string, label: string, depth: int}>
     */
    protected function pageTreeOptions(): array
    {
        $pageRoot = Fabriq::getFqnModel('page')::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->first();

        if ($pageRoot === null) {
            return [];
        }

        $tree = Fabriq::getFqnModel('page')::query()
            ->orderBy('sortindex')
            ->descendantsOf($pageRoot->id)
            ->toTree();

        return $this->transformPageTreeOptions($tree);
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function transformPageTreeItems(iterable $items): array
    {
        $tree = [];

        foreach ($items as $item) {
            $tree[] = [
                'id' => $item->id,
                'name' => $item->name,
                'template' => [
                    'id' => $item->template?->id,
                    'name' => $item->template?->name ?? 'Okänd mall',
                ],
                'editPath' => '/admin/pages/'.$item->id.'/edit',
                'children' => $this->transformPageTreeItems($item->children ?? []),
            ];
        }

        return $tree;
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @return array<int, array{id: int, name: string, label: string, depth: int}>
     */
    protected function transformPageTreeOptions(iterable $items, int $depth = 1): array
    {
        $options = [];

        foreach ($items as $item) {
            $prefix = str_repeat('-', max($depth, 1));

            $options[] = [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'label' => $prefix.' '.$item->name,
                'depth' => $depth,
            ];

            foreach ($this->transformPageTreeOptions($item->children ?? [], $depth + 1) as $child) {
                $options[] = $child;
            }
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function pageTemplates(): array
    {
        $templates = RevisionTemplate::query()
            ->where('type', 'page')
            ->orderBy('name')
            ->get();

        $items = [];

        foreach ($templates as $template) {
            $items[] = [
                'id' => $template->id,
                'name' => $template->name,
                'locked' => (bool) $template->locked,
                'sourceModelId' => $template->source_model_id ? (int) $template->source_model_id : null,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    protected function transformEditablePage(Page $page): array
    {
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $hasMultipleLocales = $supportedLocales->count() > 1;
        $localizedContent = [];
        $pathSummary = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $content = $page->getSimpleFieldContent($page->revision, $isoCode)->toArray();
            $slugs = $page->slugs
                ->where('locale', $isoCode)
                ->pluck('slug')
                ->filter(fn ($slug) => is_string($slug) && $slug !== '')
                ->values();

            $localizedContent[$isoCode] = $content;

            $pathSummary[$isoCode] = [
                'label' => (string) data_get($locale, 'native', strtoupper($isoCode)),
                'slugs' => $slugs->map(fn (string $slug): string => '/'.$slug)->all(),
                'absolutePaths' => $slugs
                    ->map(fn (string $slug): string => rtrim((string) config('fabriq.front_end_domain'), '/')
                        .($hasMultipleLocales ? '/'.$isoCode : '')
                        .'/'.$slug)
                    ->all(),
                'boxesCount' => is_array(data_get($content, 'boxes')) ? count((array) data_get($content, 'boxes')) : 0,
            ];
        }

        $fieldGroups = $page->template?->fields
            ?->groupBy(fn ($field) => (string) ($field->group ?: 'main_content'))
            ->map(fn ($fields, $group): array => [
                'name' => $group,
                'fields' => $fields
                    ->map(fn (RevisionTemplateField $field): array => [
                        'id' => (int) $field->id,
                        'name' => (string) $field->name,
                        'key' => (string) $field->key,
                        'type' => (string) $field->type,
                        'translated' => (bool) $field->translated,
                        'repeater' => (bool) $field->repeater,
                        'options' => is_array($field->options) ? $field->options : [],
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all() ?? [];

        return [
            'id' => $page->id,
            'name' => $page->name,
            'revision' => (int) $page->revision,
            'publishedVersion' => $page->published_version ? (int) $page->published_version : null,
            'updatedAt' => $page->updated_at?->toIso8601String(),
            'updatedByName' => $page->updatedByUser?->name,
            'template' => [
                'id' => $page->template?->id,
                'name' => $page->template?->name ?? 'Okänd mall',
                'slug' => $page->template?->slug ? (string) $page->template->slug : null,
                'locked' => (bool) ($page->template?->locked ?? false),
                'sourceModelId' => $page->template?->source_model_id ? (int) $page->template->source_model_id : null,
                'sourceEditPath' => $page->template?->source_model_id
                    ? '/admin/pages/'.$page->template->source_model_id.'/edit'
                    : null,
            ],
            'fieldGroups' => $fieldGroups,
            'localizedContent' => $localizedContent,
            'paths' => $pathSummary,
        ];
    }

    protected function persistPageEditorState(Page $page, Request $request): Page
    {
        $page->name = (string) $request->string('name');
        $page->localizedContent = (array) $request->input('localizedContent', []);
        $page->updated_by = $request->user()->id;
        $page->save();

        return $this->loadPageEditorRelations($page->fresh());
    }

    protected function pageMutationResponse(Request $request, Page $page, string $status): RedirectResponse|JsonResponse
    {
        $page = $this->loadPageEditorRelations($page);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'page' => $this->transformEditablePage($page),
            ]);
        }

        return to_route('admin.pages.edit', ['pageId' => $page->id])->with('status', $status);
    }

    protected function loadPageEditorRelations(Page $page): Page
    {
        $page->loadMissing([
            'template.fields',
            'slugs',
            'updatedByUser',
        ]);

        return $page;
    }

    protected function pageRoot(): object
    {
        return Fabriq::getFqnModel('page')::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->firstOrFail();
    }
}
