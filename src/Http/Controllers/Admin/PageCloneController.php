<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Actions\ClonePage;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminPages;

class PageCloneController extends AdminController
{
    use InteractsWithAdminPages;

    public function store(Request $request, int $pageId, ClonePage $clonePage): RedirectResponse
    {
        $page = Fabriq::getFqnModel('page')::query()->findOrFail($pageId);

        $clonePage(
            $this->pageRoot(),
            $page,
            trim((string) $request->string('name')) ?: 'Kopia av '.$page->name,
        );

        return to_route('admin.pages.index')->with('status', 'Sidan klonades.');
    }
}
