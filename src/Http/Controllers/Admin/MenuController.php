<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminMenus;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminPages;
use Karabin\Fabriq\Models\Menu;

class MenuController extends AdminController
{
    use InteractsWithAdminMenus;
    use InteractsWithAdminPages;

    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $menus = Menu::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Menus/Index', [
            'pageTitle' => 'Menus',
            'menus' => $this->transformMenus($menus),
        ]);
    }

    public function show(Request $request, int $menuId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $menu = Menu::query()->findOrFail($menuId);

        return Inertia::render('Admin/Menus/Edit', [
            'pageTitle' => 'Redigera meny',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => (string) $menu->slug,
                'updatedAt' => $menu->updated_at?->toIso8601String(),
            ],
            'menuTree' => $this->menuTree($menu),
            'pageOptions' => $this->pageTreeOptions(),
        ]);
    }

    /**
     * @param  iterable<int, Menu>  $menus
     * @return array<int, array<string, mixed>>
     */
    private function transformMenus(iterable $menus): array
    {
        $items = [];

        foreach ($menus as $menu) {
            $items[] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => (string) $menu->slug,
                'updatedAt' => $menu->updated_at?->toIso8601String(),
                'editPath' => '/admin/menus/'.$menu->id.'/edit',
            ];
        }

        return $items;
    }
}
