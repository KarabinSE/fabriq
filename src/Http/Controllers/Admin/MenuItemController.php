<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminMenus;
use Karabin\Fabriq\Http\Requests\UpdateMenuItemRequest;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;

class MenuItemController extends AdminController
{
    use InteractsWithAdminMenus;

    public function store(UpdateMenuItemRequest $request, int $menuId): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menuId);
        $root = $this->menuRoot($menu);

        $menuItem = Fabriq::getModelClass('menuItem');
        $menuItem->menu_id = $menu->id;
        $menuItem->parent_id = $root->id;
        $menuItem->type = (string) $request->input('item.type');
        $menuItem->page_id = $this->menuItemPageId($request);
        $menuItem->save();
        $menuItem->updateMetaContent($request->input('content', []));

        return to_route('admin.menus.edit', ['menuId' => $menu->id])
            ->with('status', 'Menypunkten skapades.');
    }

    public function update(UpdateMenuItemRequest $request, int $menuItemId): RedirectResponse
    {
        $menuItem = MenuItem::query()->findOrFail($menuItemId);
        $menuItem->type = (string) $request->input('item.type');
        $menuItem->page_id = $this->menuItemPageId($request);
        $menuItem->save();
        $menuItem->updateMetaContent($request->input('content', []));

        return to_route('admin.menus.edit', ['menuId' => $menuItem->menu_id])
            ->with('status', 'Menypunkten uppdaterades.');
    }

    public function destroy(int $menuItemId): RedirectResponse
    {
        $menuItem = MenuItem::query()->findOrFail($menuItemId);
        $menuId = $menuItem->menu_id;
        $menuItem->delete();

        return to_route('admin.menus.edit', ['menuId' => $menuId])
            ->with('status', 'Menypunkten raderades.');
    }
}
