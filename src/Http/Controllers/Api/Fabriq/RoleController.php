<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\RoleData;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\Role;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Role::orderBy('display_name')
            ->notHidden()
            ->get();

        return RoleData::collect($roles, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }
}
