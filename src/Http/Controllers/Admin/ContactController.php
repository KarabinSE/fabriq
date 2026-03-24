<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Data\ContactData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Requests\CreateContactRequest;
use Karabin\Fabriq\Http\Requests\UpdateContactRequest;
use Karabin\Fabriq\Models\Contact;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ContactController extends AdminController
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->input('search', $request->input('filter.search', '')));
        $sort = $this->normalizeContactSort((string) $request->string('sort', 'sortindex'));
        /** @var class-string<Contact> $contactModel */
        $contactModel = Fabriq::getFqnModel('contact');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $contacts = QueryBuilder::for($contactModel, $request)
            ->allowedSorts([
                'name',
                'email',
                'phone',
                'sortindex',
                'published',
                'updated_at',
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedIncludes('tags')
            ->with('tags')
            ->paginate(25);

        return Inertia::render('Admin/Contacts/Index', [
            'pageTitle' => 'Kontakter',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'contacts' => ContactData::collect($contacts, PaginatedDataCollection::class),
        ]);
    }

    public function store(CreateContactRequest $request): RedirectResponse
    {
        $contact = Fabriq::getModelClass('contact');
        $contact->name = (string) $request->string('name');
        $contact->save();

        return to_route('admin.contacts.index')->with('status', 'Kontakten skapades.');
    }

    public function show(Request $request, int $contactId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $contact = Fabriq::getFqnModel('contact')::query()
            ->with('tags')
            ->findOrFail($contactId);

        abort_unless($contact instanceof Contact, 404);

        return Inertia::render('Admin/Contacts/Edit', [
            'pageTitle' => 'Redigera kontakt',
            'contact' => $this->transformEditableContact($contact),
            'availableTags' => $this->contactTagNames(),
        ]);
    }

    public function update(UpdateContactRequest $request, int $contactId): RedirectResponse
    {
        $contact = Fabriq::getFqnModel('contact')::query()->findOrFail($contactId);

        abort_unless($contact instanceof Contact, 404);

        $validated = $request->validated();

        $contact->fill(collect($validated)->except('tags')->all());
        $contact->contactTags = $validated['tags'] ?? [];
        $contact->localizedContent = $validated['localizedContent'] ?? [];

        $contact->updateContent([
            'image' => data_get($validated, 'content.image'),
            'enabled_locales' => data_get($validated, 'content.enabled_locales', []),
        ], (string) $request->input('locale', app()->getLocale()));

        $contact->saveQuietly();

        return to_route('admin.contacts.edit', ['contactId' => $contact->id])
            ->with('status', 'Kontakten uppdaterades.');
    }

    public function destroy(int $contactId): RedirectResponse
    {
        $contact = Fabriq::getFqnModel('contact')::query()->findOrFail($contactId);

        abort_unless($contact instanceof Contact, 404);

        $contact->delete();

        return to_route('admin.contacts.index')->with('status', 'Kontakten raderades.');
    }

    private function normalizeContactSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['name', 'email', 'phone', 'sortindex', 'published', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return 'sortindex';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableContact(Contact $contact): array
    {
        $content = $contact->getFieldContent($contact->revision);
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $localizedContent = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $localizedContent[$isoCode] = $contact->getSimpleFieldContent($contact->revision, $isoCode)->toArray();
        }

        $enabledLocales = $content->get('enabled_locales', []);

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'mobile' => $contact->mobile,
            'published' => (bool) $contact->published,
            'sortindex' => $contact->sortindex,
            'tags' => $contact->tags->pluck('name')->values()->all(),
            'content' => [
                'image' => $content->get('image'),
                'enabled_locales' => is_array($enabledLocales) ? array_values($enabledLocales) : [],
            ],
            'localizedContent' => $localizedContent,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function contactTagNames(): array
    {
        $tagModel = Fabriq::getFqnModel('tag');

        return $tagModel::query()
            ->where('type', 'contacts')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }
}
