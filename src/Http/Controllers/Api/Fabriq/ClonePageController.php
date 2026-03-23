<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Actions\ClonePage;
use Karabin\Fabriq\Data\PageData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\Page;
use Symfony\Component\HttpFoundation\Response;

class ClonePageController extends Controller
{
    /**
     * Create a new resource.
     */
    public function store(Request $request, int $id, ClonePage $clonePage): Response
    {
        $pageRoot = Fabriq::getModelClass('page')->whereNull('parent_id')
            ->where('name', 'root')
            ->select('id')
            ->firstOrFail();
        $pageToClone = Fabriq::getModelClass('page')->findOrFail($id);
        $page = Fabriq::getModelClass('page');

        $page = $clonePage($pageRoot, $pageToClone, $request->name ?? 'Kopia av '.$pageToClone->name);

        /** @var Page $page */

        return PageData::fromModel($page)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }
}
