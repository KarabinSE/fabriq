<?php

namespace Karabin\Fabriq\Http\Controllers\Api;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\GlobalSectionData;
use Karabin\Fabriq\Models\GlobalSection;
use Symfony\Component\HttpFoundation\Response;

class GlobalSectionController
{
    public function show(Request $request, string $handle): Response
    {
        $section = GlobalSection::with('template.fields')->where('handle', $handle)->firstOrFail();

        return GlobalSectionData::from($section)
            ->only('handle', 'content')->wrap('data')->toResponse($request);
    }
}
