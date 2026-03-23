<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Actions\ClonePage;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminPages;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\TransformsBlockTypes;
use Karabin\Fabriq\Http\Requests\CreatePageRequest;
use Karabin\Fabriq\Http\Requests\UpdatePageRequest;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\User;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;

class PageController extends AdminController
{
    use InteractsWithAdminPages;
    use TransformsBlockTypes;

    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        return Inertia::render('Admin/Pages/Index', [
            'pageTitle' => 'Pages',
            'pageTree' => $this->pageTree(),
            'templates' => $this->pageTemplates(),
        ]);
    }

    public function show(Request $request, int $pageId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $page = Fabriq::getFqnModel('page')::query()
            ->with([
                'template.fields',
                'slugs',
                'updatedByUser',
            ])
            ->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        return Inertia::render('Admin/Pages/Edit', [
            'pageTitle' => 'Redigera sida',
            'page' => $this->transformEditablePage($page),
            'blockTypes' => $this->transformBlockTypes(
                BlockType::query()
                    ->where('active', 1)
                    ->orderBy('name')
                    ->get()
            ),
            'pageOptions' => $this->pageTreeOptions(),
            'commentUsers' => $this->transformCommentUsers(
                User::query()
                    ->orderBy('name')
                    ->get()
            ),
            'commentContext' => [
                'openComments' => $request->boolean('openComments'),
                'commentId' => $request->filled('commentId') ? $request->integer('commentId') : null,
            ],
        ]);
    }

    public function store(CreatePageRequest $request, ClonePage $clonePage): RedirectResponse
    {
        $template = RevisionTemplate::query()
            ->where('type', 'page')
            ->findOrFail($request->integer('template_id'));

        $pageRoot = $this->pageRoot();
        $pageModel = Fabriq::getModelClass('page');

        if ($template->locked && $template->source_model_id) {
            $sourcePage = Fabriq::getFqnModel('page')::query()->findOrFail((int) $template->source_model_id);
            $page = $clonePage($pageRoot, $sourcePage, (string) $request->string('name'));

            return to_route('admin.pages.index')
                ->with('status', 'Sidan skapades från låst mall: '.$page->name);
        }

        $pageModel->name = (string) $request->string('name');
        $pageModel->template_id = $template->id;
        $pageModel->parent_id = $pageRoot->id;
        $pageModel->updated_by = $request->user()->id;
        $pageModel->save();

        return to_route('admin.pages.index')->with('status', 'Sidan skapades.');
    }

    public function update(UpdatePageRequest $request, int $pageId): RedirectResponse|JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::query()->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        $page = $this->persistPageEditorState($page, $request);

        return $this->pageMutationResponse($request, $page, 'Sidan sparades som utkast.');
    }

    public function destroy(int $pageId): RedirectResponse
    {
        $page = Fabriq::getFqnModel('page')::query()
            ->withCount('children')
            ->findOrFail($pageId);

        if ($page->parent_id === null) {
            return to_route('admin.pages.index')->with('status', 'Rotsidan får inte raderas.');
        }

        if ($page->children_count > 0) {
            return to_route('admin.pages.index')->with('status', 'Sidan har undersidor. Flytta eller radera dem först.');
        }

        $page->delete();

        return to_route('admin.pages.index')->with('status', 'Sidan raderades.');
    }
}
