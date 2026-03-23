<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\RevisionTemplateData;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RevisionTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = QueryBuilder::for(RevisionTemplate::class)
            ->allowedFilters(
                AllowedFilter::exact('type')
            )->get();

        return RevisionTemplateData::collect($templates, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }
}
