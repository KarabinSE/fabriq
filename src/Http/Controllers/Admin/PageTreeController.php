<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminPages;

class PageTreeController extends AdminController
{
    use InteractsWithAdminPages;

    public function update(Request $request): RedirectResponse
    {
        Fabriq::getFqnModel('page')::rebuildSubtree($this->pageRoot(), $request->input('tree', []));

        return to_route('admin.pages.index')->with('status', 'Sidträdet uppdaterades.');
    }
}
