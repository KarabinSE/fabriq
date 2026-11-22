<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\GlobalSectionData;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\UpdateGlobalSectionRequest;
use Karabin\Fabriq\Models\GlobalSection;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\Response;

class GlobalSectionController extends Controller
{
    public function index(Request $request): Response
    {
        $sections = GlobalSection::orderBy('name')->get(['id', 'name', 'handle'])->toArray();

        return GlobalSectionData::collect($sections, DataCollection::class)->wrap('data')->toResponse($request);
    }

    public function show(Request $request, string $handle): Response
    {
        $section = GlobalSection::with('template.fields')->where('handle', $handle)->firstOrFail();

        return GlobalSectionData::from($section)->wrap('data')->toResponse($request);
    }

    public function update(UpdateGlobalSectionRequest $request): Response
    {
        $section = $request->section();
        $validated = $request->validated();
        $section->updateContent($validated['content'], $validated['locale']);
        $section->touch();

        return GlobalSectionData::from($section, ['content' => (object) $section->fieldValues($validated['locale'])])
            ->only('handle', 'content')->wrap('data')->toResponse($request);
    }
}
