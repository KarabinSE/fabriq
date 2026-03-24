<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\ContactData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateContactRequest;
use Karabin\Fabriq\Http\Requests\UpdateContactRequest;
use Karabin\Fabriq\Models\Contact;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    /**
     * Returns an index of contacts.
     */
    public function index(Request $request): Response
    {
        $number = $request->integer('number', 100);
        $allowedIncludes = [
            ...Fabriq::getFqnModel('contact')::RELATIONSHIPS,
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('content', new NoOpInclude),
        ];

        $contacts = QueryBuilder::for(Fabriq::getFqnModel('contact'))
            ->allowedSorts('name', 'email', 'updated_at', 'sortindex', 'published')
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedIncludes(...$allowedIncludes)
            ->paginate($number);

        $collection = new PaginatedDataCollection(
            ContactData::class,
            $contacts->through(fn (Contact $contact) => ContactData::fromModel($contact)),
        );

        return $collection->wrap('data')->toResponse($request);
    }

    public function show(Request $request, int $id): Response
    {
        $allowedIncludes = [
            ...Fabriq::getFqnModel('contact')::RELATIONSHIPS,
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('content', new NoOpInclude),
        ];

        $contact = QueryBuilder::for(Fabriq::getFqnModel('contact'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var Contact $contact */

        return ContactData::fromModel($contact)->wrap('data')->toResponse($request);
    }

    public function store(CreateContactRequest $request): Response
    {
        $contact = Fabriq::getModelClass('contact');
        $contact->name = $request->name;
        $contact->save();

        return ContactData::fromModel($contact)->wrap('data')->toResponse($request);
    }

    public function update(UpdateContactRequest $request, int $id): Response
    {
        $contact = Fabriq::getFqnModel('contact')::findOrFail($id);
        $validated = $request->validated();

        $contact->fill(collect($validated)->except('tags')->all());

        $contact->contactTags = $request->tags;
        $contact->localizedContent = $request->localizedContent;

        $contact->updateContent([
            'image' => $request->content['image'] ?? null,
            'enabled_locales' => $request->content['enabled_locales'] ?? [],
        ], $request->input('locale', app()->getLocale()));

        $contact->saveQuietly();

        return ContactData::fromModel($contact)->wrap('data')->toResponse($request);
    }

    public function destroy(int $id): JsonResponse
    {
        $contact = Fabriq::getFqnModel('contact')::findOrFail($id);
        $contact->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'The contact has been deleted successfully',
        ]);
    }
}
