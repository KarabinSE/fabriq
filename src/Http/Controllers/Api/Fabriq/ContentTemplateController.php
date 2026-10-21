<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\ContentTemplateData;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\ContentTemplate;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class ContentTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = QueryBuilder::for(ContentTemplate::class)
            ->allowedFilters(
                AllowedFilter::exact('type')
            )->get();

        return ContentTemplateData::collect($templates, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }
}
