<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class BlockController extends Controller
{
    public function index() {}

    public function store(Request $request) {}

    public function show(int $id)
    {
        $block = Fabriq::getFqnModel('block')::buildQuery(Fabriq::getFqnModel('block'))
            ->findOrFail($id);

        return Fabriq::getDto('block')::from($block);
    }

    public function destroy($id) {}
}
