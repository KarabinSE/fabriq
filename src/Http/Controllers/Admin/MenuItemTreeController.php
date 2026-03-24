<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminMenus;
use Karabin\Fabriq\Models\Menu;

class MenuItemTreeController extends AdminController
{
    use InteractsWithAdminMenus;

    public function update(Request $request, int $menuId): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menuId);

        Fabriq::getFqnModel('menuItem')::rebuildSubtree($this->menuRoot($menu), $request->input('tree', []));

        return to_route('admin.menus.edit', ['menuId' => $menu->id])
            ->with('status', 'Menyn uppdaterades.');
    }
}
