<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Actions\ClonePage;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateArticleRequest;
use Karabin\Fabriq\Http\Requests\CreateBlockTypeRequest;
use Karabin\Fabriq\Http\Requests\CreateContactRequest;
use Karabin\Fabriq\Http\Requests\CreateEventRequest;
use Karabin\Fabriq\Http\Requests\CreatePageRequest;
use Karabin\Fabriq\Http\Requests\CreateSmartBlockRequest;
use Karabin\Fabriq\Http\Requests\CreateUserRequest;
use Karabin\Fabriq\Http\Requests\UpdateArticleRequest;
use Karabin\Fabriq\Http\Requests\UpdateBlockTypeRequest;
use Karabin\Fabriq\Http\Requests\UpdateContactRequest;
use Karabin\Fabriq\Http\Requests\UpdateMenuItemRequest;
use Karabin\Fabriq\Http\Requests\UpdatePageRequest;
use Karabin\Fabriq\Http\Requests\UpdateSmartBlockRequest;
use Karabin\Fabriq\Http\Requests\UpdateUserRequest;
use Karabin\Fabriq\Mail\AccountInvitation;
use Karabin\Fabriq\Models\Article;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\Comment;
use Karabin\Fabriq\Models\Contact;
use Karabin\Fabriq\Models\Event;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Invitation;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Notification;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Role;
use Karabin\Fabriq\Models\SmartBlock;
use Karabin\Fabriq\Models\User;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\QueryBuilders\FileSort;
use Karabin\Fabriq\QueryBuilders\ImageSort;
use Karabin\Fabriq\QueryBuilders\VideoSort;
use Karabin\Fabriq\Services\CalendarService;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;
use Karabin\TranslatableRevisions\Models\RevisionTemplateField;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class AdminPageController extends Controller
{
    public function dashboard(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        return Inertia::render('Admin/Dashboard/Index', [
            'pageTitle' => 'Dashboard',
            'summary' => [
                [
                    'label' => 'Runtime',
                    'value' => 'Inertia + Vue 3 + TypeScript',
                ],
                [
                    'label' => 'Current path',
                    'value' => '/admin/dashboard',
                ],
                [
                    'label' => 'Logged in as',
                    'value' => (string) $request->user()?->email,
                ],
            ],
        ]);
    }

    public function notifications(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $user = $request->user();

        $unseen = Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('cleared_at')
            ->with(['notifiable.user', 'notifiable.commentable'])
            ->latest()
            ->paginate(10, ['*'], 'unseenPage');

        $seen = Notification::query()
            ->where('user_id', $user->id)
            ->whereNotNull('cleared_at')
            ->with(['notifiable.user', 'notifiable.commentable'])
            ->latest()
            ->paginate(10, ['*'], 'seenPage');

        return Inertia::render('Admin/Notifications/Index', [
            'pageTitle' => 'Notifications',
            'unseen' => $this->paginatedNotifications($unseen),
            'seen' => $this->paginatedNotifications($seen),
        ]);
    }

    public function clearNotification(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->forceFill([
            'cleared_at' => now(),
        ])->save();

        return to_route('admin.notifications')->with('status', 'Notisen markerades som hanterad.');
    }

    public function pages(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        return Inertia::render('Admin/Pages/Index', [
            'pageTitle' => 'Pages',
            'pageTree' => $this->pageTree(),
            'templates' => $this->pageTemplates(),
        ]);
    }

    public function editPage(Request $request, int $pageId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $page = Fabriq::getFqnModel('page')::query()
            ->with([
                'template.fields',
                'slugs',
                'updatedByUser',
            ])
            ->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        return Inertia::render('Admin/Pages/Edit', [
            'pageTitle' => 'Redigera sida',
            'page' => $this->transformEditablePage($page),
            'blockTypes' => $this->transformBlockTypes(
                BlockType::query()
                    ->where('active', 1)
                    ->orderBy('name')
                    ->get()
            ),
            'pageOptions' => $this->pageTreeOptions(),
            'commentUsers' => $this->transformCommentUsers(
                User::query()
                    ->orderBy('name')
                    ->get()
            ),
            'commentContext' => [
                'openComments' => $request->boolean('openComments'),
                'commentId' => $request->filled('commentId') ? $request->integer('commentId') : null,
            ],
        ]);
    }

    public function storePage(CreatePageRequest $request, ClonePage $clonePage): RedirectResponse
    {
        $template = RevisionTemplate::query()
            ->where('type', 'page')
            ->findOrFail($request->integer('template_id'));

        $pageRoot = $this->pageRoot();
        $pageModel = Fabriq::getModelClass('page');

        if ($template->locked && $template->source_model_id) {
            $sourcePage = Fabriq::getFqnModel('page')::query()->findOrFail((int) $template->source_model_id);
            $page = $clonePage($pageRoot, $sourcePage, (string) $request->string('name'));

            return to_route('admin.pages.index')
                ->with('status', 'Sidan skapades från låst mall: '.$page->name);
        }

        $pageModel->name = (string) $request->string('name');
        $pageModel->template_id = $template->id;
        $pageModel->parent_id = $pageRoot->id;
        $pageModel->updated_by = $request->user()->id;
        $pageModel->save();

        return to_route('admin.pages.index')->with('status', 'Sidan skapades.');
    }

    public function updatePage(UpdatePageRequest $request, int $pageId): RedirectResponse|JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::query()->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        $page = $this->persistPageEditorState($page, $request);

        return $this->pageMutationResponse($request, $page, 'Sidan sparades som utkast.');
    }

    public function publishPage(UpdatePageRequest $request, int $pageId): RedirectResponse|JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::query()->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        $page = $this->persistPageEditorState($page, $request);

        Fabriq::getFqnModel('page')::withoutEvents(function () use ($page): void {
            $page->publish($page->revision);
        });

        return $this->pageMutationResponse($request, $page->fresh(), 'Sidan sparades och publicerades.');
    }

    public function previewPageUrl(Request $request, int $pageId): JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::query()
            ->with('slugs')
            ->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $locale = (string) $request->string('locale', (string) data_get($supportedLocales->first(), 'iso_code', app()->getLocale()));
        $slug = $page->slugs->firstWhere('locale', $locale)?->slug ?? $page->slugs->first()?->slug;

        abort_if(! is_string($slug) || $slug === '', 404, 'Could not build preview URL for page.');

        $signedUrl = URL::signedRoute('pages.show.preview', ['slug' => $slug]);
        $prefix = $supportedLocales->count() > 1 ? '/'.$locale : '';

        return response()->json([
            'data' => [
                'url' => rtrim((string) config('fabriq.front_end_domain'), '/')
                    .$prefix
                    .'/'.$slug
                    .'?preview='
                    .base64_encode($signedUrl),
            ],
        ]);
    }

    public function clonePage(Request $request, int $pageId, ClonePage $clonePage): RedirectResponse
    {
        $page = Fabriq::getFqnModel('page')::query()->findOrFail($pageId);

        $clonePage(
            $this->pageRoot(),
            $page,
            trim((string) $request->string('name')) ?: 'Kopia av '.$page->name,
        );

        return to_route('admin.pages.index')->with('status', 'Sidan klonades.');
    }

    public function destroyPage(int $pageId): RedirectResponse
    {
        $page = Fabriq::getFqnModel('page')::query()
            ->withCount('children')
            ->findOrFail($pageId);

        if ($page->parent_id === null) {
            return to_route('admin.pages.index')->with('status', 'Rotsidan får inte raderas.');
        }

        if ($page->children_count > 0) {
            return to_route('admin.pages.index')->with('status', 'Sidan har undersidor. Flytta eller radera dem först.');
        }

        $page->delete();

        return to_route('admin.pages.index')->with('status', 'Sidan raderades.');
    }

    public function updatePageTree(Request $request): RedirectResponse
    {
        Fabriq::getFqnModel('page')::rebuildSubtree($this->pageRoot(), $request->input('tree', []));

        return to_route('admin.pages.index')->with('status', 'Sidträdet uppdaterades.');
    }

    public function smartBlocks(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort', 'name');
        [$sortColumn, $sortDirection] = $this->normalizeSmartBlockSort($sort);

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => ($sortDirection === 'desc' ? '-' : '').$sortColumn,
        ]);

        $smartBlocks = QueryBuilder::for(SmartBlock::query(), $request)
            ->allowedSorts([
                'name',
                'updated_at',
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->paginate(25);

        return Inertia::render('Admin/SmartBlocks/Index', [
            'pageTitle' => 'Smarta block',
            'filters' => [
                'search' => $search,
                'sort' => ($sortDirection === 'desc' ? '-' : '').$sortColumn,
            ],
            'smartBlocks' => [
                'data' => $this->transformSmartBlocks($smartBlocks),
                'pagination' => $this->paginationMeta($smartBlocks),
            ],
        ]);
    }

    public function storeSmartBlock(CreateSmartBlockRequest $request): RedirectResponse
    {
        $smartBlock = new SmartBlock;
        $smartBlock->name = (string) $request->string('name');
        $smartBlock->save();

        return to_route('admin.smart-blocks.index')->with([
            'status' => 'Det smarta blocket skapades.',
            'status_action_label' => 'Gå till blocket',
            'status_action_href' => '/admin/smart-blocks/'.$smartBlock->id.'/edit',
        ]);
    }

    public function editSmartBlock(Request $request, int $smartBlockId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $smartBlock = SmartBlock::query()->findOrFail($smartBlockId);

        return Inertia::render('Admin/SmartBlocks/Edit', [
            'pageTitle' => 'Redigera smart block',
            'smartBlock' => $this->transformEditableSmartBlock($smartBlock),
            'blockTypes' => $this->transformBlockTypes(
                BlockType::query()
                    ->where('active', 1)
                    ->orderBy('name')
                    ->get()
            ),
            'pageOptions' => $this->pageTreeOptions(),
        ]);
    }

    public function updateSmartBlock(UpdateSmartBlockRequest $request, int $smartBlockId): RedirectResponse
    {
        $smartBlock = SmartBlock::query()->findOrFail($smartBlockId);
        $validated = $request->validated();

        $smartBlock->name = (string) ($validated['name'] ?? $smartBlock->name);
        $smartBlock->localizedContent = $validated['localizedContent'] ?? [];
        $smartBlock->touch();
        $smartBlock->save();

        return to_route('admin.smart-blocks.edit', ['smartBlockId' => $smartBlock->id])
            ->with('status', 'Det smarta blocket uppdaterades.');
    }

    public function destroySmartBlock(int $smartBlockId): RedirectResponse
    {
        $smartBlock = SmartBlock::query()->findOrFail($smartBlockId);
        $smartBlock->delete();

        return to_route('admin.smart-blocks.index')->with('status', 'Det smarta blocket raderades.');
    }

    public function blockTypes(Request $request): Response|JsonResponse
    {
        return $this->renderBlockTypesPage($request);
    }

    public function editBlockType(Request $request, int $blockTypeId): Response|JsonResponse
    {
        return $this->renderBlockTypesPage($request, $blockTypeId);
    }

    public function storeBlockType(CreateBlockTypeRequest $request): RedirectResponse
    {
        $blockType = new BlockType;
        $blockType->fill($request->validated());
        $blockType->active = true;
        $blockType->type = 'block';
        $blockType->options = [
            'recommended_for' => [],
            'visible_for' => [],
            'hidden_for' => [],
        ];
        $blockType->save();

        return to_route('admin.block-types.edit', ['blockTypeId' => $blockType->id])
            ->with('status', 'Blocktypen skapades.');
    }

    public function updateBlockType(UpdateBlockTypeRequest $request, int $blockTypeId): RedirectResponse
    {
        $blockType = BlockType::query()->findOrFail($blockTypeId);
        $blockType->fill($request->validated());
        $blockType->save();

        return to_route('admin.block-types.edit', ['blockTypeId' => $blockType->id])
            ->with('status', 'Blocktypen uppdaterades.');
    }

    public function destroyBlockType(int $blockTypeId): RedirectResponse
    {
        $blockType = BlockType::query()->findOrFail($blockTypeId);
        $blockType->delete();

        return to_route('admin.block-types.index')->with('status', 'Blocktypen raderades.');
    }

    public function articles(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort', '-publishes_at');
        [$sortColumn, $sortDirection] = $this->normalizeArticleSort($sort);
        $articleModel = Fabriq::getFqnModel('article');

        $articles = $articleModel::query()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(20);

        return Inertia::render('Admin/Articles/Index', [
            'pageTitle' => 'Nyheter',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'articles' => [
                'data' => $this->transformArticles($articles),
                'pagination' => $this->paginationMeta($articles),
            ],
        ]);
    }

    public function contacts(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
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
            ->with('tags')
            ->paginate(25);

        return Inertia::render('Admin/Contacts/Index', [
            'pageTitle' => 'Kontakter',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'contacts' => [
                'data' => $this->transformContacts($contacts),
                'pagination' => $this->paginationMeta($contacts),
            ],
        ]);
    }

    public function storeArticle(CreateArticleRequest $request): RedirectResponse
    {
        $article = Fabriq::getModelClass('article');
        $article->fill($request->validated());
        $article->template_id = 2;
        $article->save();

        return to_route('admin.articles.index')->with([
            'status' => 'Nyheten skapades.',
            'status_action_label' => 'Gå till nyheten',
            'status_action_href' => '/admin/articles/'.$article->id.'/edit',
        ]);
    }

    public function editArticle(Request $request, int $articleId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $article = Fabriq::getFqnModel('article')::query()->findOrFail($articleId);

        abort_unless($article instanceof Article, 404);

        return Inertia::render('Admin/Articles/Edit', [
            'pageTitle' => 'Redigera nyhet',
            'article' => $this->transformEditableArticle($article),
        ]);
    }

    public function updateArticle(UpdateArticleRequest $request, int $articleId): RedirectResponse
    {
        $article = Fabriq::getFqnModel('article')::query()->findOrFail($articleId);

        abort_unless($article instanceof Article, 404);

        $validated = $request->validated();

        $article->fill($validated);
        $article->updateContent($validated['content'] ?? []);
        $article->save();

        return to_route('admin.articles.edit', ['articleId' => $article->id])->with('status', 'Nyheten uppdaterades.');
    }

    public function storeContact(CreateContactRequest $request): RedirectResponse
    {
        $contact = Fabriq::getModelClass('contact');
        $contact->name = (string) $request->string('name');
        $contact->save();

        return to_route('admin.contacts.index')->with('status', 'Kontakten skapades.');
    }

    public function destroyArticle(int $articleId): RedirectResponse
    {
        $article = Fabriq::getFqnModel('article')::query()->findOrFail($articleId);

        abort_unless($article instanceof Article, 404);

        $article->delete();

        return to_route('admin.articles.index')->with('status', 'Nyheten raderades.');
    }

    public function calendar(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $month = $this->calendarMonth((string) $request->string('month'));
        [$monthStart, $monthEnd] = [$month->startOfMonth(), $month->endOfMonth()];

        return Inertia::render('Admin/Calendar/Index', [
            'pageTitle' => 'Kalender',
            'filters' => [
                'month' => $month->format('Y-m'),
            ],
            'calendar' => [
                'monthLabel' => $month->translatedFormat('F Y'),
                'startsAt' => $monthStart->toDateString(),
                'endsAt' => $monthEnd->toDateString(),
            ],
            'events' => $this->transformCalendarEvents($this->calendarEventsForMonth($monthStart, $monthEnd)),
        ]);
    }

    public function storeEvent(CreateEventRequest $request): RedirectResponse
    {
        $event = new Event;
        $event->fill($request->validated());
        $event->save();

        foreach ((array) $request->input('localizedContent', []) as $locale => $content) {
            $event->updateContent((array) $content, (string) $locale);
        }

        return $this->calendarRedirect($request, 'Händelsen skapades.');
    }

    public function updateEvent(CreateEventRequest $request, int $eventId): RedirectResponse
    {
        $event = Event::query()->findOrFail($eventId);
        $event->fill($request->validated());
        $event->save();

        foreach ((array) $request->input('localizedContent', []) as $locale => $content) {
            $event->updateContent((array) $content, (string) $locale);
        }

        return $this->calendarRedirect($request, 'Händelsen uppdaterades.');
    }

    public function destroyEvent(Request $request, int $eventId): RedirectResponse
    {
        $event = Event::query()->findOrFail($eventId);
        $event->delete();

        return $this->calendarRedirect($request, 'Händelsen raderades.');
    }

    public function editContact(Request $request, int $contactId): Response|JsonResponse
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

    public function updateContact(UpdateContactRequest $request, int $contactId): RedirectResponse
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

    public function destroyContact(int $contactId): RedirectResponse
    {
        $contact = Fabriq::getFqnModel('contact')::query()->findOrFail($contactId);

        abort_unless($contact instanceof Contact, 404);

        $contact->delete();

        return to_route('admin.contacts.index')->with('status', 'Kontakten raderades.');
    }

    public function users(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort', 'name');
        [$sortColumn, $sortDirection] = $this->normalizeUserSort($sort);

        $users = User::query()
            ->with(['roles', 'invitation'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'pageTitle' => 'Users',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'users' => [
                'data' => $this->transformUsers($users),
                'pagination' => $this->paginationMeta($users),
            ],
        ]);
    }

    public function storeUser(CreateUserRequest $request): RedirectResponse
    {
        $roleList = $request->array('role_list');

        $user = new User;
        $user->name = (string) $request->string('name');
        $user->email = (string) $request->string('email');
        $user->password = bcrypt(Str::random(12));
        $user->save();
        $user->syncRoles($roleList);

        if ($request->boolean('send_activation')) {
            $this->createAndSendInvitation($user, $request->user()->id);
        }

        return to_route('admin.users.index')->with('status', 'Användaren skapades.');
    }

    public function editUser(Request $request, int $userId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $user = Fabriq::getFqnModel('user')::query()
            ->with('roles')
            ->findOrFail($userId);

        abort_unless($user instanceof User, 404);

        $roles = Role::query()
            ->notHidden()
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Admin/Users/Edit', [
            'pageTitle' => 'Redigera användare',
            'user' => $this->transformEditableUser($user),
            'roles' => $this->transformRoleOptions($roles),
        ]);
    }

    public function updateUser(UpdateUserRequest $request, int $userId): RedirectResponse
    {
        $user = Fabriq::getFqnModel('user')::query()->findOrFail($userId);

        abort_unless($user instanceof User, 404);

        $user->fill($request->validated());
        $user->save();

        return to_route('admin.users.edit', ['userId' => $user->id])
            ->with('status', 'Användaren har uppdaterats.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return to_route('admin.users.index')->with('status', 'Du kan inte radera dig själv.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('status', 'Användaren raderades.');
    }

    public function storeUserInvitation(Request $request, User $user): RedirectResponse
    {
        $this->createAndSendInvitation($user, $request->user()->id);

        return to_route('admin.users.index')->with('status', 'Aktivering skickad.');
    }

    public function destroyUserInvitation(User $user): RedirectResponse
    {
        Invitation::query()
            ->where('user_id', $user->id)
            ->delete();

        return to_route('admin.users.index')->with('status', 'Inbjudan togs bort.');
    }

    public function menus(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $menus = Menu::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Menus/Index', [
            'pageTitle' => 'Menus',
            'menus' => $this->transformMenus($menus),
        ]);
    }

    public function editMenu(Request $request, int $menuId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $menu = Menu::query()->findOrFail($menuId);

        return Inertia::render('Admin/Menus/Edit', [
            'pageTitle' => 'Redigera meny',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => (string) $menu->slug,
                'updatedAt' => $menu->updated_at?->toIso8601String(),
            ],
            'menuTree' => $this->menuTree($menu),
            'pageOptions' => $this->pageTreeOptions(),
        ]);
    }

    public function storeMenuItem(UpdateMenuItemRequest $request, int $menuId): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menuId);
        $root = $this->menuRoot($menu);

        $menuItem = Fabriq::getModelClass('menuItem');
        $menuItem->menu_id = $menu->id;
        $menuItem->parent_id = $root->id;
        $menuItem->type = (string) $request->input('item.type');
        $menuItem->page_id = $this->menuItemPageId($request);
        $menuItem->save();
        $menuItem->updateMetaContent($request->input('content', []));

        return to_route('admin.menus.edit', ['menuId' => $menu->id])
            ->with('status', 'Menypunkten skapades.');
    }

    public function updateMenuItem(UpdateMenuItemRequest $request, int $menuItemId): RedirectResponse
    {
        $menuItem = MenuItem::query()->findOrFail($menuItemId);
        $menuItem->type = (string) $request->input('item.type');
        $menuItem->page_id = $this->menuItemPageId($request);
        $menuItem->save();
        $menuItem->updateMetaContent($request->input('content', []));

        return to_route('admin.menus.edit', ['menuId' => $menuItem->menu_id])
            ->with('status', 'Menypunkten uppdaterades.');
    }

    public function destroyMenuItem(int $menuItemId): RedirectResponse
    {
        $menuItem = MenuItem::query()->findOrFail($menuItemId);
        $menuId = $menuItem->menu_id;
        $menuItem->delete();

        return to_route('admin.menus.edit', ['menuId' => $menuId])
            ->with('status', 'Menypunkten raderades.');
    }

    public function updateMenuTree(Request $request, int $menuId): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menuId);

        Fabriq::getFqnModel('menuItem')::rebuildSubtree($this->menuRoot($menu), $request->input('tree', []));

        return to_route('admin.menus.edit', ['menuId' => $menu->id])
            ->with('status', 'Menyn uppdaterades.');
    }

    public function images(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = $this->normalizeImageSort((string) $request->string('sort', '-created_at'));
        /** @var class-string<Image> $imageModel */
        $imageModel = Fabriq::getFqnModel('image');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $images = QueryBuilder::for($imageModel, $request)
            ->allowedSorts([
                'created_at',
                'updated_at',
                'alt_text',
                AllowedSort::custom('c_name', new ImageSort, 'name'),
                AllowedSort::custom('size', new ImageSort),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->has('mediaImages')
            ->with('tags')
            ->paginate(20);

        return Inertia::render('Admin/Images/Index', [
            'pageTitle' => 'Bilder',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'images' => [
                'data' => $this->transformImages($images),
                'pagination' => $this->paginationMeta($images),
            ],
        ]);
    }

    public function files(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = $this->normalizeFileSort((string) $request->string('sort', '-created_at'));
        /** @var class-string<File> $fileModel */
        $fileModel = Fabriq::getFqnModel('file');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $files = QueryBuilder::for($fileModel, $request)
            ->allowedSorts([
                'created_at',
                'updated_at',
                AllowedSort::custom('file_name', new FileSort),
                AllowedSort::custom('size', new FileSort),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->with('tags')
            ->paginate(20);

        return Inertia::render('Admin/Files/Index', [
            'pageTitle' => 'Filer',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'files' => [
                'data' => $this->transformFiles($files),
                'pagination' => $this->paginationMeta($files),
            ],
        ]);
    }

    public function videos(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = $this->normalizeVideoSort((string) $request->string('sort', '-created_at'));
        /** @var class-string<Video> $videoModel */
        $videoModel = Fabriq::getFqnModel('video');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $videos = QueryBuilder::for($videoModel, $request)
            ->allowedSorts([
                'created_at',
                'updated_at',
                'alt_text',
                AllowedSort::custom('file_name', new VideoSort),
                AllowedSort::custom('size', new VideoSort),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->with('tags')
            ->paginate(20);

        return Inertia::render('Admin/Videos/Index', [
            'pageTitle' => 'Videos',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'videos' => [
                'data' => $this->transformVideos($videos),
                'pagination' => $this->paginationMeta($videos),
            ],
        ]);
    }

    public function profileSettings(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return Inertia::render('Admin/Profile/Settings', [
            'pageTitle' => 'Din information',
            'profile' => $this->transformProfile($user->loadMissing('image')),
        ]);
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeUserSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'email', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return ['name', 'asc'];
        }

        return [$column, $direction];
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeArticleSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'publishes_at', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return ['publishes_at', 'desc'];
        }

        return [$column, $direction];
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

    private function normalizeImageSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['created_at', 'updated_at', 'alt_text', 'c_name', 'size'];

        if (! in_array($column, $allowed, true)) {
            return '-created_at';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    private function normalizeFileSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['created_at', 'updated_at', 'file_name', 'size'];

        if (! in_array($column, $allowed, true)) {
            return '-created_at';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    private function normalizeVideoSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['created_at', 'updated_at', 'alt_text', 'file_name', 'size'];

        if (! in_array($column, $allowed, true)) {
            return '-created_at';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeSmartBlockSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return ['name', 'asc'];
        }

        return [$column, $direction];
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeBlockTypeSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'created_at', 'updated_at', 'component_name'];

        if (! in_array($column, $allowed, true)) {
            return ['name', 'asc'];
        }

        return [$column, $direction];
    }

    /**
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     pagination: array<string, int|null>
     * }
     */
    private function paginatedNotifications(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $this->transformNotifications($paginator),
            'pagination' => $this->paginationMeta($paginator),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformNotification(Notification $notification): array
    {
        $notifiable = $notification->notifiable;
        $isComment = $notifiable instanceof Comment;
        $page = $isComment ? $notifiable->commentable : null;
        $author = $isComment ? $notifiable->user : null;
        $excerptSource = $isComment ? (string) $notifiable->comment : (string) ($notification->content ?? '');

        return [
            'id' => $notification->id,
            'title' => $isComment ? 'Omnämnd i kommentar' : 'Notis',
            'excerpt' => (string) Str::of(strip_tags($excerptSource))->squish()->limit(180),
            'createdAt' => $notification->created_at?->toIso8601String(),
            'createdAtLabel' => $notification->created_at?->diffForHumans(),
            'isCleared' => $notification->cleared_at !== null,
            'authorName' => $author?->name,
            'pageName' => $page?->name,
            'openPath' => $page ? '/admin/pages/'.$page->getKey().'/edit?openComments=1&commentId='.$notification->notifiable_id : null,
            'clearPath' => '/admin/notifications/'.$notification->id.'/clear',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformNotifications(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $notification) {
            if (! $notification instanceof Notification) {
                continue;
            }

            $items[] = $this->transformNotification($notification);
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformUsers(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $roles = [];

            foreach ($user->roles as $role) {
                $roles[] = [
                    'name' => $role->name,
                    'displayName' => $role->display_name,
                ];
            }

            $items[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'verifiedAt' => $user->email_verified_at?->toIso8601String(),
                'updatedAt' => $user->updated_at?->toIso8601String(),
                'editPath' => '/admin/users/'.$user->id.'/edit',
                'invitationSentAt' => $user->invitation?->created_at?->toIso8601String(),
            ];
        }

        return $items;
    }

    /**
     * @param  iterable<int, User>  $users
     * @return array<int, array{id: int, name: string, email: string}>
     */
    private function transformCommentUsers(iterable $users): array
    {
        $items = [];

        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $items[] = [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformArticles(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $article) {
            if (! $article instanceof Article) {
                continue;
            }

            $items[] = [
                'id' => $article->id,
                'name' => $article->name,
                'isPublished' => (bool) $article->is_published,
                'publishesAt' => $article->publishes_at?->toIso8601String(),
                'unpublishesAt' => $article->unpublishes_at?->toIso8601String(),
                'hasUnpublishedTime' => (bool) $article->has_unpublished_time,
                'updatedAt' => $article->updated_at?->toIso8601String(),
                'editPath' => '/admin/articles/'.$article->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformSmartBlocks(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $smartBlock) {
            if (! $smartBlock instanceof SmartBlock) {
                continue;
            }

            $items[] = [
                'id' => $smartBlock->id,
                'name' => $smartBlock->name,
                'updatedAt' => $smartBlock->updated_at?->toIso8601String(),
                'editPath' => '/admin/smart-blocks/'.$smartBlock->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableSmartBlock(SmartBlock $smartBlock): array
    {
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $localizedContent = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $content = $smartBlock->getSimpleFieldContent($smartBlock->revision, $isoCode)->toArray();
            $localizedContent[$isoCode] = [
                ...$content,
                'boxes' => is_array(data_get($content, 'boxes'))
                    ? array_values((array) data_get($content, 'boxes', []))
                    : [],
            ];
        }

        return [
            'id' => $smartBlock->id,
            'name' => $smartBlock->name,
            'localizedContent' => $localizedContent,
        ];
    }

    /**
     * @param  iterable<int, BlockType>  $blockTypes
     * @return array<int, array<string, mixed>>
     */
    private function transformBlockTypes(iterable $blockTypes): array
    {
        $items = [];

        foreach ($blockTypes as $blockType) {
            $options = $this->normalizeBlockTypeOptions($blockType->options);

            $items[] = [
                'id' => $blockType->id,
                'name' => $blockType->name,
                'componentName' => $blockType->component_name,
                'base64Svg' => $blockType->base_64_svg,
                'hasChildren' => (bool) $blockType->has_children,
                'options' => $options,
                'createdAt' => $blockType->created_at?->toIso8601String(),
                'updatedAt' => $blockType->updated_at?->toIso8601String(),
                'editPath' => '/admin/block-types/'.$blockType->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableBlockType(BlockType $blockType): array
    {
        $options = $this->normalizeBlockTypeOptions($blockType->options);

        return [
            'id' => $blockType->id,
            'name' => $blockType->name,
            'componentName' => $blockType->component_name,
            'base64Svg' => $blockType->base_64_svg,
            'hasChildren' => (bool) $blockType->has_children,
            'options' => $options,
        ];
    }

    /**
     * @param  mixed  $value
     * @return array{recommendedFor: array<int, string>, visibleFor: array<int, string>, hiddenFor: array<int, string>}
     */
    private function normalizeBlockTypeOptions($value): array
    {
        $options = is_array($value) ? $value : [];

        return [
            'recommendedFor' => array_values(array_filter((array) data_get($options, 'recommended_for', []), fn ($item) => is_string($item) && $item !== '')),
            'visibleFor' => array_values(array_filter((array) data_get($options, 'visible_for', []), fn ($item) => is_string($item) && $item !== '')),
            'hiddenFor' => array_values(array_filter((array) data_get($options, 'hidden_for', []), fn ($item) => is_string($item) && $item !== '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableArticle(Article $article): array
    {
        $content = $article->getFieldContent($article->revision);
        $publishesAt = $article->publishes_at?->timezone('Europe/Stockholm');
        $unpublishesAt = $article->unpublishes_at?->timezone('Europe/Stockholm');

        return [
            'id' => $article->id,
            'name' => $article->name,
            'publishesAt' => $publishesAt?->format('Y-m-d\TH:i'),
            'unpublishesAt' => $unpublishesAt?->format('Y-m-d\TH:i'),
            'hasUnpublishedTime' => (bool) $article->has_unpublished_time,
            'content' => [
                'title' => (string) $content->get('title', ''),
                'preamble' => (string) $content->get('preamble', ''),
                'body' => (string) $content->get('body', ''),
                'image' => $content->get('image'),
            ],
        ];
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return array<int, array<string, mixed>>
     */
    private function transformCalendarEvents(Collection $events): array
    {
        return $events
            ->sortBy([
                ['start', 'asc'],
                ['start_time', 'asc'],
            ])
            ->values()
            ->map(function (Event $event): array {
                return $this->transformEditableEvent($event);
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformContacts(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $contact) {
            if (! $contact instanceof Contact) {
                continue;
            }

            $items[] = [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'mobile' => $contact->mobile,
                'sortindex' => $contact->sortindex,
                'published' => (bool) $contact->published,
                'imageThumbUrl' => data_get($contact->image, 'thumb_src'),
                'tags' => $contact->tags->pluck('name')->values()->all(),
                'editPath' => '/admin/contacts/'.$contact->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformFiles(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $file) {
            if (! $file instanceof File) {
                continue;
            }

            $media = $file->getFirstMedia('files');

            if (! $media) {
                continue;
            }

            $items[] = [
                'id' => $file->id,
                'name' => $file->readable_name ?: $media->name,
                'fileName' => $media->file_name,
                'extension' => Str::upper(Str::afterLast($media->file_name, '.')),
                'thumbnailUrl' => $media->hasGeneratedConversion('file_thumb') ? $media->getUrl('file_thumb') : null,
                'sourceUrl' => $media->getUrl(),
                'caption' => $file->caption,
                'size' => $media->size,
                'createdAt' => $file->created_at?->toIso8601String(),
                'updatedAt' => $file->updated_at?->toIso8601String(),
                'tags' => $file->tags->pluck('name')->values()->all(),
            ];
        }

        return $items;
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
     * @return array<string, mixed>
     */
    private function transformEditableEvent(Event $event): array
    {
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $localizedContent = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $localizedContent[$isoCode] = $event->getSimpleFieldContent($event->revision, $isoCode)->toArray();
        }

        $title = (string) data_get($localizedContent, 'sv.title', $event->title);

        return [
            'id' => $event->id,
            'title' => $title,
            'start' => $event->start?->toIso8601String(),
            'end' => $event->end?->toIso8601String(),
            'startDate' => $event->start?->toDateString(),
            'endDate' => $event->end?->toDateString(),
            'startTime' => $event->start_time,
            'endTime' => $event->end_time,
            'dailyInterval' => (int) $event->daily_interval,
            'hasInterval' => (bool) $event->daily_interval,
            'localizedContent' => $localizedContent,
            'preview' => [
                'description' => (string) data_get($localizedContent, 'sv.description', ''),
                'location' => (string) data_get($localizedContent, 'sv.location', ''),
            ],
            'updatedAt' => $event->updated_at?->toIso8601String(),
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformImages(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $image) {
            if (! $image instanceof Image) {
                continue;
            }

            $media = $image->getFirstMedia('images');

            if (! $media) {
                continue;
            }

            $items[] = [
                'id' => $image->id,
                'name' => $media->name,
                'fileName' => $media->file_name,
                'extension' => Str::upper(Str::afterLast($media->file_name, '.')),
                'thumbnailUrl' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                'sourceUrl' => $media->getUrl(),
                'altText' => $image->alt_text,
                'caption' => $image->caption,
                'size' => $media->size,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
                'processing' => (bool) $media->getCustomProperty('processing'),
                'processingFailed' => (bool) $media->getCustomProperty('processing_failed'),
                'createdAt' => $image->created_at?->toIso8601String(),
                'updatedAt' => $image->updated_at?->toIso8601String(),
                'tags' => $image->tags->pluck('name')->values()->all(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformVideos(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $video) {
            if (! $video instanceof Video) {
                continue;
            }

            $media = $video->getFirstMedia('videos');

            if (! $media) {
                continue;
            }

            $thumbnailUrl = null;

            if ($media->hasGeneratedConversion('thumb')) {
                $thumbnailUrl = $media->getUrl('thumb');
            } elseif ($media->hasGeneratedConversion('poster')) {
                $thumbnailUrl = $media->getUrl('poster');
            }

            $items[] = [
                'id' => $video->id,
                'name' => $media->name,
                'fileName' => $media->file_name,
                'extension' => Str::upper(Str::afterLast($media->file_name, '.')),
                'thumbnailUrl' => $thumbnailUrl,
                'sourceUrl' => $media->getUrl(),
                'altText' => $video->alt_text,
                'caption' => $video->caption,
                'size' => $media->size,
                'createdAt' => $video->created_at?->toIso8601String(),
                'updatedAt' => $video->updated_at?->toIso8601String(),
                'tags' => $video->tags->pluck('name')->values()->all(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roleList' => $user->roles->pluck('name')->values()->all(),
        ];
    }

    /**
     * @param  iterable<int, Role>  $roles
     * @return array<int, array{name: string, displayName: string}>
     */
    private function transformRoleOptions(iterable $roles): array
    {
        $items = [];

        foreach ($roles as $role) {
            $items[] = [
                'name' => $role->name,
                'displayName' => $role->display_name,
            ];
        }

        return $items;
    }

    /**
     * @param  iterable<int, Menu>  $menus
     * @return array<int, array<string, mixed>>
     */
    private function transformMenus(iterable $menus): array
    {
        $items = [];

        foreach ($menus as $menu) {
            $items[] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => (string) $menu->slug,
                'updatedAt' => $menu->updated_at?->toIso8601String(),
                'editPath' => '/admin/menus/'.$menu->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function menuTree(Menu $menu): array
    {
        $root = $this->menuRoot($menu);

        /** @var mixed $treeQuery */
        $treeQuery = $root->descendants();

        $tree = $treeQuery
            ->with('page')
            ->defaultOrder()
            ->get()
            ->toTree();

        return $this->transformMenuTreeItems($tree);
    }

    /**
     * @param  iterable<int, MenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function transformMenuTreeItems(iterable $items): array
    {
        $tree = [];

        foreach ($items as $item) {
            $content = $this->menuItemContent($item);
            $file = data_get($content, 'file');

            $tree[] = [
                'id' => $item->id,
                'title' => $item->type === 'internal'
                    ? ($item->page?->name ?? $item->title)
                    : ((string) data_get($content, 'title', $item->title)),
                'type' => (string) $item->type,
                'pageId' => $item->page_id ? (int) $item->page_id : null,
                'page' => $item->page ? [
                    'id' => $item->page->id,
                    'name' => $item->page->name,
                ] : null,
                'sortindex' => $item->sortindex !== null ? (int) $item->sortindex : null,
                'content' => [
                    'title' => (string) data_get($content, 'title', ''),
                    'external_url' => (string) data_get($content, 'external_url', ''),
                    'body' => (string) data_get($content, 'body', ''),
                    'file' => is_array($file) && data_get($file, 'id') ? $file : null,
                ],
                'children' => $this->transformMenuTreeItems($item->children ?? []),
            ];
        }

        return $tree;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProfile(User $user): array
    {
        $media = $user->image?->getFirstMedia('profile_image');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'emailVerifiedAt' => $user->email_verified_at?->toIso8601String(),
            'image' => [
                'id' => $user->image?->id,
                'thumbSrc' => $media?->getUrl('thumb'),
                'src' => $media?->getUrl(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageTree(): array
    {
        $pageRoot = Fabriq::getFqnModel('page')::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->first();

        if ($pageRoot === null) {
            return [];
        }

        $tree = Fabriq::getFqnModel('page')::query()
            ->orderBy('sortindex')
            ->with('template')
            ->descendantsOf($pageRoot->id)
            ->toTree();

        return $this->transformPageTreeItems($tree);
    }

    /**
     * @return array<int, array{id: int, name: string, label: string, depth: int}>
     */
    private function pageTreeOptions(): array
    {
        $pageRoot = Fabriq::getFqnModel('page')::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->first();

        if ($pageRoot === null) {
            return [];
        }

        $tree = Fabriq::getFqnModel('page')::query()
            ->orderBy('sortindex')
            ->descendantsOf($pageRoot->id)
            ->toTree();

        return $this->transformPageTreeOptions($tree);
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    private function transformPageTreeItems(iterable $items): array
    {
        $tree = [];

        foreach ($items as $item) {
            $tree[] = [
                'id' => $item->id,
                'name' => $item->name,
                'template' => [
                    'id' => $item->template?->id,
                    'name' => $item->template?->name ?? 'Okänd mall',
                ],
                'editPath' => '/admin/pages/'.$item->id.'/edit',
                'children' => $this->transformPageTreeItems($item->children ?? []),
            ];
        }

        return $tree;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditablePage(Page $page): array
    {
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $hasMultipleLocales = $supportedLocales->count() > 1;
        $localizedContent = [];
        $pathSummary = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $content = $page->getSimpleFieldContent($page->revision, $isoCode)->toArray();
            $slugs = $page->slugs
                ->where('locale', $isoCode)
                ->pluck('slug')
                ->filter(fn ($slug) => is_string($slug) && $slug !== '')
                ->values();

            $localizedContent[$isoCode] = $content;

            $pathSummary[$isoCode] = [
                'label' => (string) data_get($locale, 'native', strtoupper($isoCode)),
                'slugs' => $slugs->map(fn (string $slug): string => '/'.$slug)->all(),
                'absolutePaths' => $slugs
                    ->map(fn (string $slug): string => rtrim((string) config('fabriq.front_end_domain'), '/')
                        .($hasMultipleLocales ? '/'.$isoCode : '')
                        .'/'.$slug)
                    ->all(),
                'boxesCount' => is_array(data_get($content, 'boxes')) ? count((array) data_get($content, 'boxes')) : 0,
            ];
        }

        $fieldGroups = $page->template?->fields
            ?->groupBy(fn ($field) => (string) ($field->group ?: 'main_content'))
            ->map(fn ($fields, $group): array => [
                'name' => $group,
                'fields' => $fields
                    ->map(fn (RevisionTemplateField $field): array => [
                        'id' => (int) $field->id,
                        'name' => (string) $field->name,
                        'key' => (string) $field->key,
                        'type' => (string) $field->type,
                        'translated' => (bool) $field->translated,
                        'repeater' => (bool) $field->repeater,
                        'options' => is_array($field->options) ? $field->options : [],
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all() ?? [];

        return [
            'id' => $page->id,
            'name' => $page->name,
            'revision' => (int) $page->revision,
            'publishedVersion' => $page->published_version ? (int) $page->published_version : null,
            'updatedAt' => $page->updated_at?->toIso8601String(),
            'updatedByName' => $page->updatedByUser?->name,
            'template' => [
                'id' => $page->template?->id,
                'name' => $page->template?->name ?? 'Okänd mall',
                'slug' => $page->template?->slug ? (string) $page->template->slug : null,
                'locked' => (bool) ($page->template?->locked ?? false),
                'sourceModelId' => $page->template?->source_model_id ? (int) $page->template->source_model_id : null,
                'sourceEditPath' => $page->template?->source_model_id
                    ? '/admin/pages/'.$page->template->source_model_id.'/edit'
                    : null,
            ],
            'fieldGroups' => $fieldGroups,
            'localizedContent' => $localizedContent,
            'paths' => $pathSummary,
        ];
    }

    private function persistPageEditorState(Page $page, Request $request): Page
    {
        $page->name = (string) $request->string('name');
        $page->localizedContent = (array) $request->input('localizedContent', []);
        $page->updated_by = $request->user()->id;
        $page->save();

        return $this->loadPageEditorRelations($page->fresh());
    }

    private function pageMutationResponse(Request $request, Page $page, string $status): RedirectResponse|JsonResponse
    {
        $page = $this->loadPageEditorRelations($page);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'page' => $this->transformEditablePage($page),
            ]);
        }

        return to_route('admin.pages.edit', ['pageId' => $page->id])->with('status', $status);
    }

    private function loadPageEditorRelations(Page $page): Page
    {
        $page->loadMissing([
            'template.fields',
            'slugs',
            'updatedByUser',
        ]);

        return $page;
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @return array<int, array{id: int, name: string, label: string, depth: int}>
     */
    private function transformPageTreeOptions(iterable $items, int $depth = 1): array
    {
        $options = [];

        foreach ($items as $item) {
            $prefix = str_repeat('-', max($depth, 1));

            $options[] = [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'label' => $prefix.' '.$item->name,
                'depth' => $depth,
            ];

            foreach ($this->transformPageTreeOptions($item->children ?? [], $depth + 1) as $child) {
                $options[] = $child;
            }
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageTemplates(): array
    {
        $templates = RevisionTemplate::query()
            ->where('type', 'page')
            ->orderBy('name')
            ->get();

        $items = [];

        foreach ($templates as $template) {
            $items[] = [
                'id' => $template->id,
                'name' => $template->name,
                'locked' => (bool) $template->locked,
                'sourceModelId' => $template->source_model_id ? (int) $template->source_model_id : null,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, type: string}>
     */
    private function templateOptions(): array
    {
        return RevisionTemplate::query()
            ->orderBy('name')
            ->get()
            ->map(fn (RevisionTemplate $template): array => [
                'id' => (int) $template->id,
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'type' => (string) $template->type,
            ])
            ->values()
            ->all();
    }

    private function renderBlockTypesPage(Request $request, ?int $editingBlockTypeId = null): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $sort = (string) $request->string('sort', 'name');
        [$sortColumn, $sortDirection] = $this->normalizeBlockTypeSort($sort);

        $blockTypes = BlockType::query()
            ->where('active', 1)
            ->orderBy($sortColumn, $sortDirection)
            ->get();

        $editingBlockType = null;

        if ($editingBlockTypeId !== null) {
            $editingBlockTypeModel = BlockType::query()->findOrFail($editingBlockTypeId);
            $editingBlockType = $this->transformEditableBlockType($editingBlockTypeModel);
        }

        return Inertia::render('Admin/BlockTypes/Index', [
            'pageTitle' => 'Blocktyper',
            'filters' => [
                'sort' => ($sortDirection === 'desc' ? '-' : '').$sortColumn,
            ],
            'blockTypes' => $this->transformBlockTypes($blockTypes),
            'editingBlockType' => $editingBlockType,
            'templates' => $this->templateOptions(),
        ]);
    }

    private function pageRoot(): object
    {
        return Fabriq::getFqnModel('page')::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->firstOrFail();
    }

    private function calendarMonth(string $value): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            try {
                return CarbonImmutable::parse($value.'-01')->startOfMonth();
            } catch (\Throwable) {
                return CarbonImmutable::now()->startOfMonth();
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /**
     * @return Collection<int, Event>
     */
    private function calendarEventsForMonth(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        $events = Event::query()
            ->dateRange($monthStart->toDateString(), $monthEnd->toDateString())
            ->orderBy('start')
            ->get();

        $computedEvents = CalendarService::getComputedDailyIntervals($events, $monthEnd);

        return $events
            ->toBase()
            ->merge($computedEvents)
            ->filter(fn ($event) => $event instanceof Event)
            ->values();
    }

    private function calendarRedirect(Request $request, string $status): RedirectResponse
    {
        return to_route('admin.calendar.index', [
            'month' => $this->calendarMonth((string) $request->input('month'))->format('Y-m'),
        ])->with('status', $status);
    }

    private function menuRoot(Menu $menu): MenuItem
    {
        /** @var MenuItem $root */
        $root = MenuItem::query()->firstOrCreate([
            'menu_id' => $menu->id,
            'parent_id' => null,
        ], [
            'type' => 'internal',
        ]);

        return $root;
    }

    /**
     * @return array<string, mixed>
     */
    private function menuItemContent(MenuItem $menuItem): array
    {
        /** @var mixed $content */
        $content = $menuItem->getFieldContent($menuItem->revision);

        if (is_object($content) && method_exists($content, 'toArray')) {
            return $content->toArray();
        }

        if (is_array($content)) {
            return $content;
        }

        return [];
    }

    private function menuItemPageId(UpdateMenuItemRequest $request): ?int
    {
        if ((string) $request->input('item.type') !== 'internal') {
            return null;
        }

        $pageId = $request->input('item.page_id');

        if ($pageId === null || $pageId === '') {
            return null;
        }

        return (int) $pageId;
    }

    private function createAndSendInvitation(User $user, int $invitedBy): void
    {
        Invitation::query()
            ->where('user_id', $user->id)
            ->delete();

        $invitation = $user->createInvitation($invitedBy);
        $invitation->load('invitedBy', 'user');

        Mail::to($user->email)
            ->queue(new AccountInvitation($invitation));
    }

    private function jsonGuard(Request $request): ?JsonResponse
    {
        if (! $request->wantsJson()) {
            return null;
        }

        return response()->json('Get outta here!', 404);
    }
}
