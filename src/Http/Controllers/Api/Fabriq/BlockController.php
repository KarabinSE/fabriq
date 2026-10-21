<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class BlockController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function show(Request $request, int $id): Response
    {
        $block = Fabriq::getFqnModel('block')::buildQuery(Fabriq::getFqnModel('block'))
            ->findOrFail($id);

        return Fabriq::getDto('block')::from($block)->toResponse($request);
    }

    public function destroy(int $id): void {}
}
