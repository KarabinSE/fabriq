<?php

namespace Karabin\Fabriq;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Karabin\Fabriq\Http\Controllers\Admin\ArticleController;
use Karabin\Fabriq\Http\Controllers\Admin\BlockTypeController;
use Karabin\Fabriq\Http\Controllers\Admin\CalendarController;
use Karabin\Fabriq\Http\Controllers\Admin\ContactController;
use Karabin\Fabriq\Http\Controllers\Admin\DashboardController;
use Karabin\Fabriq\Http\Controllers\Admin\EventController;
use Karabin\Fabriq\Http\Controllers\Admin\FileController;
use Karabin\Fabriq\Http\Controllers\Admin\ImageController;
use Karabin\Fabriq\Http\Controllers\Admin\MenuController;
use Karabin\Fabriq\Http\Controllers\Admin\MenuItemController;
use Karabin\Fabriq\Http\Controllers\Admin\MenuItemTreeController;
use Karabin\Fabriq\Http\Controllers\Admin\NotificationController;
use Karabin\Fabriq\Http\Controllers\Admin\PageCloneController;
use Karabin\Fabriq\Http\Controllers\Admin\PageController;
use Karabin\Fabriq\Http\Controllers\Admin\PagePreviewUrlController;
use Karabin\Fabriq\Http\Controllers\Admin\PagePublishController;
use Karabin\Fabriq\Http\Controllers\Admin\PageTreeController;
use Karabin\Fabriq\Http\Controllers\Admin\ProfileSettingsController;
use Karabin\Fabriq\Http\Controllers\Admin\SmartBlockController;
use Karabin\Fabriq\Http\Controllers\Admin\UserController;
use Karabin\Fabriq\Http\Controllers\Admin\UserInvitationController;
use Karabin\Fabriq\Http\Controllers\Admin\VideoController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\AcceptInvitationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\AskToLeaveNotificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\AuthenticatedUserController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\BustCacheController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ClonePageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\CommentableController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\CommentController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ConfigController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ContactSortController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\DeclineToLeaveNotificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\DownloadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\EmailVerificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\FileUploadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageableController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageSourceSetController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageUploadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\InvitationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\MediaDownloadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ModelCountController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PagePathController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageSignedUrlController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageSlugPreviewController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageSlugsController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PublishPageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\RevisionTemplateController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\RoleController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\TagController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\UserImageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\VideoUploadController;
use Karabin\Fabriq\Http\Controllers\PermalinksRedirectController;
use Karabin\Fabriq\Http\Controllers\SpaController;
use Karabin\Fabriq\Http\Middleware\HandleInertiaRequests;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class RouteRegistrar
{
    /**
     * The router implementation.
     *
     * @var Registrar
     */
    protected $router;

    /**
     * Create a new route registrar instance.
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for web.
     */
    public function allWeb(): void
    {
        if (config('fabriq.redirect_to_admin')) {
            Route::redirect('/', '/admin');
        }
        Route::get('/permalink/{hash}/{locale?}', PermalinksRedirectController::class)
            ->name('permalink.redirect');

        Route::get('/invitations/accept/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.accept');
        Route::post('/invitations/accept/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.accept.store');
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::prefix('admin')->middleware([HandleInertiaRequests::class])->group(function () {
            Route::get('/email/verify', function ($request) {
                return view('auth.verify-email', ['request' => $request]);
            })->middleware('auth')->name('verification.notice');

            Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
                $request->fulfill();

                return redirect('/admin/profile/settings');
            })->middleware(['auth', 'signed'])->name('verification.verify');

            Route::get('/email/verification-notification', function () {
                config('fabriq.models.user')::find(1)->sendEmailVerificationNotification();

                return 'ok';
            })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

            Route::redirect('/', '/admin/dashboard')->middleware('auth');
            Route::get('/dashboard', [DashboardController::class, 'show'])->middleware('auth')->name('admin.dashboard');
            Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth')->name('admin.notifications');
            Route::post('/notifications/{notification}/clear', [NotificationController::class, 'update'])->middleware('auth')->name('admin.notifications.clear');
            Route::get('/pages', [PageController::class, 'index'])->middleware('auth')->name('admin.pages.index');
            Route::get('/pages/{pageId}/edit', [PageController::class, 'show'])->middleware('auth')->name('admin.pages.edit');
            Route::patch('/pages/{pageId}', [PageController::class, 'update'])->middleware('auth')->name('admin.pages.update');
            Route::post('/pages/{pageId}/publish', [PagePublishController::class, 'store'])->middleware('auth')->name('admin.pages.publish');
            Route::get('/pages/{pageId}/preview-url', [PagePreviewUrlController::class, 'show'])->middleware('auth')->name('admin.pages.preview-url');
            Route::post('/pages', [PageController::class, 'store'])->middleware('auth')->name('admin.pages.store');
            Route::patch('/pages-tree', [PageTreeController::class, 'update'])->middleware('auth')->name('admin.pages.tree.update');
            Route::post('/pages/{pageId}/clone', [PageCloneController::class, 'store'])->middleware('auth')->name('admin.pages.clone');
            Route::delete('/pages/{pageId}', [PageController::class, 'destroy'])->middleware('auth')->name('admin.pages.destroy');
            Route::get('/smart-blocks', [SmartBlockController::class, 'index'])->middleware('auth')->name('admin.smart-blocks.index');
            Route::post('/smart-blocks', [SmartBlockController::class, 'store'])->middleware('auth')->name('admin.smart-blocks.store');
            Route::get('/smart-blocks/{smartBlockId}/edit', [SmartBlockController::class, 'show'])->middleware('auth')->name('admin.smart-blocks.edit');
            Route::patch('/smart-blocks/{smartBlockId}', [SmartBlockController::class, 'update'])->middleware('auth')->name('admin.smart-blocks.update');
            Route::delete('/smart-blocks/{smartBlockId}', [SmartBlockController::class, 'destroy'])->middleware('auth')->name('admin.smart-blocks.destroy');
            Route::get('/block-types', [BlockTypeController::class, 'index'])->middleware('auth')->name('admin.block-types.index');
            Route::get('/block-types/{blockTypeId}/edit', [BlockTypeController::class, 'show'])->middleware('auth')->name('admin.block-types.edit');
            Route::post('/block-types', [BlockTypeController::class, 'store'])->middleware('auth')->name('admin.block-types.store');
            Route::patch('/block-types/{blockTypeId}', [BlockTypeController::class, 'update'])->middleware('auth')->name('admin.block-types.update');
            Route::delete('/block-types/{blockTypeId}', [BlockTypeController::class, 'destroy'])->middleware('auth')->name('admin.block-types.destroy');
            Route::get('/articles', [ArticleController::class, 'index'])->middleware('auth')->name('admin.articles.index');
            Route::post('/articles', [ArticleController::class, 'store'])->middleware('auth')->name('admin.articles.store');
            Route::get('/articles/{articleId}/edit', [ArticleController::class, 'show'])->middleware('auth')->name('admin.articles.edit');
            Route::patch('/articles/{articleId}', [ArticleController::class, 'update'])->middleware('auth')->name('admin.articles.update');
            Route::delete('/articles/{articleId}', [ArticleController::class, 'destroy'])->middleware('auth')->name('admin.articles.destroy');
            Route::get('/calendar', [CalendarController::class, 'index'])->middleware('auth')->name('admin.calendar.index');
            Route::post('/events', [EventController::class, 'store'])->middleware('auth')->name('admin.events.store');
            Route::patch('/events/{eventId}', [EventController::class, 'update'])->middleware('auth')->name('admin.events.update');
            Route::delete('/events/{eventId}', [EventController::class, 'destroy'])->middleware('auth')->name('admin.events.destroy');
            Route::get('/contacts', [ContactController::class, 'index'])->middleware('auth')->name('admin.contacts.index');
            Route::post('/contacts', [ContactController::class, 'store'])->middleware('auth')->name('admin.contacts.store');
            Route::get('/contacts/{contactId}/edit', [ContactController::class, 'show'])->middleware('auth')->name('admin.contacts.edit');
            Route::patch('/contacts/{contactId}', [ContactController::class, 'update'])->middleware('auth')->name('admin.contacts.update');
            Route::delete('/contacts/{contactId}', [ContactController::class, 'destroy'])->middleware('auth')->name('admin.contacts.destroy');
            Route::get('/users', [UserController::class, 'index'])->middleware('auth')->name('admin.users.index');
            Route::post('/users', [UserController::class, 'store'])->middleware('auth')->name('admin.users.store');
            Route::get('/users/{userId}/edit', [UserController::class, 'show'])->middleware('auth')->name('admin.users.edit');
            Route::patch('/users/{userId}', [UserController::class, 'update'])->middleware('auth')->name('admin.users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('auth')->name('admin.users.destroy');
            Route::post('/users/{user}/invitation', [UserInvitationController::class, 'store'])->middleware('auth')->name('admin.users.invitations.store');
            Route::delete('/users/{user}/invitation', [UserInvitationController::class, 'destroy'])->middleware('auth')->name('admin.users.invitations.destroy');
            Route::get('/menus', [MenuController::class, 'index'])->middleware('auth')->name('admin.menus.index');
            Route::get('/menus/{menuId}/edit', [MenuController::class, 'show'])->middleware('auth')->name('admin.menus.edit');
            Route::post('/menus/{menuId}/items', [MenuItemController::class, 'store'])->middleware('auth')->name('admin.menus.items.store');
            Route::patch('/menus/{menuId}/items/tree', [MenuItemTreeController::class, 'update'])->middleware('auth')->name('admin.menus.items.tree.update');
            Route::patch('/menu-items/{menuItemId}', [MenuItemController::class, 'update'])->middleware('auth')->name('admin.menu-items.update');
            Route::delete('/menu-items/{menuItemId}', [MenuItemController::class, 'destroy'])->middleware('auth')->name('admin.menu-items.destroy');
            Route::get('/media/images', [ImageController::class, 'index'])->middleware('auth')->name('admin.media.images.index');
            Route::get('/media/files', [FileController::class, 'index'])->middleware('auth')->name('admin.media.files.index');
            Route::get('/media/videos', [VideoController::class, 'index'])->middleware('auth')->name('admin.media.videos.index');
            Route::get('/profile/settings', [ProfileSettingsController::class, 'show'])->middleware('auth')->name('admin.profile.settings');
            Route::get('/{any}', [SpaController::class, 'index'])->where('any', '.*')->middleware('auth');
        });
    }

    /**
     * Register routes forPublic API endpoints.
     */
    public function all(): void
    {
        $this->forMiscRoutes();
        $this->forArticles();
        $this->forContacts();
        $this->forBlockTypes();
        $this->forComments();
        $this->forEvents();
        $this->forFiles();
        $this->forImages();
        $this->forDownloads();
        $this->forMenus();
        $this->forPages();
        $this->forRoles();
        $this->forSmartBlocks();
        $this->forTags();
        $this->forUsers();
        $this->forVideos();
        $this->forNotifications();
        $this->forAuthenticatedUsers();
        $this->forConfig();
        $this->forPageSlugs();
        $this->forPagePaths();
        $this->forInvitations();
    }

    public function forApiProtected()
    {
        $this->forNotifications();
        $this->forAuthenticatedUsers();
        $this->forConfig();
    }

    public function forApiAdminProtected()
    {
        $this->forMiscRoutes();
        $this->forArticles();
        $this->forContacts();
        $this->forBlockTypes();
        $this->forComments();
        $this->forEvents();
        $this->forFiles();
        $this->forImages();
        $this->forDownloads();
        $this->forMenus();
        $this->forPages();
        $this->forRoles();
        $this->forSmartBlocks();
        $this->forTags();
        $this->forUsers();
        $this->forVideos();
        $this->forPagePaths();
        $this->forInvitations();
    }

    public function forPublicApi()
    {
        $this->forPageSlugs();
        $this->forImageSrcSet();
        // Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);
    }

    public function forDevProtected()
    {
        Route::post('bust-cache', [BustCacheController::class, 'store']);
    }

    public function forArticles(): void
    {
        Route::resource('articles', Http\Controllers\Api\Fabriq\ArticleController::class);
    }

    public function forContacts(): void
    {
        Route::post('contacts/sort-contacts', ContactSortController::class)
            ->name('contacts.sort');
        Route::resource('contacts', Http\Controllers\Api\Fabriq\ContactController::class);
    }

    public function forBlockTypes(): void
    {
        Route::resource('block-types', Http\Controllers\Api\Fabriq\BlockTypeController::class);
    }

    public function forComments(): void
    {
        Route::get('{model}/{id}/comments', [CommentableController::class, 'index']);
        Route::post('{model}/{id}/comments', [CommentableController::class, 'store']);
        Route::patch('comments/{id}', [CommentController::class, 'update']);
        Route::delete('comments/{id}', [CommentController::class, 'destroy']);
    }

    public function forConfig(): void
    {
        Route::get('config', [ConfigController::class, 'index']);
    }

    public function forEvents(): void
    {
        Route::resource('events', Http\Controllers\Api\Fabriq\EventController::class);
    }

    public function forFiles(): void
    {
        Route::get('files', [Http\Controllers\Api\Fabriq\FileController::class, 'index']);
        Route::get('files/{id}', [Http\Controllers\Api\Fabriq\FileController::class, 'show']);
        Route::patch('files/{id}', [Http\Controllers\Api\Fabriq\FileController::class, 'update']);
        Route::delete('files/{id}', [Http\Controllers\Api\Fabriq\FileController::class, 'destroy']);
    }

    public function forImages(): void
    {
        Route::get('images/{id}/src-set', [ImageSourceSetController::class, 'show']);
        Route::get('/{model}/{id}/images', [ImageableController::class, 'index'])->whereNumber('id');
        Route::post('/images/{id}/{model}', [ImageableController::class, 'store'])->whereNumber('id');
        Route::get('images', [Http\Controllers\Api\Fabriq\ImageController::class, 'index']);
        Route::get('images/{id}', [Http\Controllers\Api\Fabriq\ImageController::class, 'show']);
        Route::patch('images/{id}', [Http\Controllers\Api\Fabriq\ImageController::class, 'update']);
        Route::delete('images/{id}', [Http\Controllers\Api\Fabriq\ImageController::class, 'destroy']);
    }

    public function forDownloads(): void
    {
        Route::get('media/downloads/{uuid}', [MediaDownloadController::class, 'show']);
        Route::get('downloads', [DownloadController::class, 'index']);
        Route::get('downloads/{id}', [DownloadController::class, 'show']);
    }

    public function forMiscRoutes(): void
    {
        Route::get('templates', [RevisionTemplateController::class, 'index']);
        Route::get('menus/{slug}/public', [Http\Controllers\Api\Fabriq\MenuItemTreeController::class, 'show']);
        Route::get('{model}/count', [ModelCountController::class, 'show']);

        // Uploads
        Route::post('uploads/images', [ImageUploadController::class, 'store']);
        Route::post('uploads/files', [FileUploadController::class, 'store']);
        Route::post('uploads/videos', [VideoUploadController::class, 'store']);
    }

    public function forMenus(): void
    {
        Route::get('menus', [Http\Controllers\Api\Fabriq\MenuController::class, 'index']);
        Route::post('menus', [Http\Controllers\Api\Fabriq\MenuController::class, 'store']);
        Route::get('menus/{id}', [Http\Controllers\Api\Fabriq\MenuController::class, 'show']);
        Route::patch('menus/{id}', [Http\Controllers\Api\Fabriq\MenuController::class, 'update']);
        Route::delete('menus/{id}', [Http\Controllers\Api\Fabriq\MenuController::class, 'destroy']);
        Route::get('menus/{id}/items/tree', [Http\Controllers\Api\Fabriq\MenuItemTreeController::class, 'index']);
        Route::patch('menus/{id}/items/tree', [Http\Controllers\Api\Fabriq\MenuItemTreeController::class, 'update']);
        Route::post('/menus/{id}/items', [Http\Controllers\Api\Fabriq\MenuItemController::class, 'store']);

        Route::get('menu-items/{id}', [Http\Controllers\Api\Fabriq\MenuItemController::class, 'show']);
        Route::patch('menu-items/{id}', [Http\Controllers\Api\Fabriq\MenuItemController::class, 'update']);
        Route::delete('menu-items/{id}', [Http\Controllers\Api\Fabriq\MenuItemController::class, 'destroy']);
    }

    public function forPages(): void
    {
        Route::get('pages-tree', [Http\Controllers\Api\Fabriq\PageTreeController::class, 'index']);
        Route::patch('pages-tree', [Http\Controllers\Api\Fabriq\PageTreeController::class, 'update']);
        Route::get('pages/{slug}/live', [PageSlugsController::class, 'show']);
        Route::get('pages', [Http\Controllers\Api\Fabriq\PageController::class, 'index']);
        Route::post('pages', [Http\Controllers\Api\Fabriq\PageController::class, 'store']);
        Route::get('pages/{id}', [Http\Controllers\Api\Fabriq\PageController::class, 'show']);
        Route::patch('pages/{id}', [Http\Controllers\Api\Fabriq\PageController::class, 'update']);
        Route::delete('pages/{id}', [Http\Controllers\Api\Fabriq\PageController::class, 'destroy']);
        Route::post('pages/{id}/clone', [ClonePageController::class, 'store'])
            ->name('pages.clone.store');
        Route::post('pages/{id}/publish', [PublishPageController::class, 'store']);
        Route::get('pages/{id}/signed-url', [PageSignedUrlController::class, 'show']);
    }

    public function forInvitations(): void
    {
        // Route::get('/invitations/accept/{token}', [Karabin\Fabriq\Http\Controllers\Api\Fabriq\AcceptInvitationController::class, 'show'])->name('invitation.accept');
        Route::post('invitations/{userId}', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('invitations/{userId}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    }

    public function forPageSlugs()
    {
        Route::get('pages/{slug}/preview', [PageSlugPreviewController::class, 'show'])->name('pages.show.preview');
    }

    public function forRoles(): void
    {
        Route::get('roles', [RoleController::class, 'index']);
    }

    public function forSmartBlocks(): void
    {
        Route::resource('smart-blocks', Http\Controllers\Api\Fabriq\SmartBlockController::class);
    }

    public function forTags(): void
    {
        Route::get('tags', [TagController::class, 'index']);
        Route::post('tags', [TagController::class, 'store']);
    }

    public function forAuthenticatedUsers(): void
    {
        Route::get('user', [AuthenticatedUserController::class, 'index']);
        Route::patch('user', [AuthenticatedUserController::class, 'update']);
        Route::post('user/image', [UserImageController::class, 'store'])->name('user.image.store');
        Route::delete('user/image', [UserImageController::class, 'destroy'])->name('user.image.destroy');
        Route::patch('user/self', [AuthenticatedUserController::class, 'update']);
        Route::post('user/send-email-verification', [EmailVerificationController::class, 'store']);
    }

    public function forUsers()
    {
        Route::resource('users', Http\Controllers\Api\Fabriq\UserController::class);
    }

    public function forNotifications()
    {
        Route::post('/notifications/ask-to-leave/{userId}', AskToLeaveNotificationController::class)
            ->name('notifications.ask-to-leave');
        Route::post('/notifications/decline-to-leave/{userId}', DeclineToLeaveNotificationController::class)
            ->name('notifications.decline-to-leave');
        Route::get('/user/notifications', [Http\Controllers\Api\Fabriq\NotificationController::class, 'index']);
        Route::patch('/user/notifications/{id}', [Http\Controllers\Api\Fabriq\NotificationController::class, 'update']);
    }

    public function forVideos(): void
    {
        Route::get('videos', [Http\Controllers\Api\Fabriq\VideoController::class, 'index']);
        Route::get('videos/{id}', [Http\Controllers\Api\Fabriq\VideoController::class, 'show']);
        Route::patch('videos/{id}', [Http\Controllers\Api\Fabriq\VideoController::class, 'update']);
        Route::delete('videos/{id}', [Http\Controllers\Api\Fabriq\VideoController::class, 'destroy']);
    }

    public function forImageSrcSet(): void
    {
        Route::get('images/{id}/src-set', [ImageSourceSetController::class, 'show']);
    }

    public function forPagePaths(): void
    {
        Route::get('pages/{id}/paths', [PagePathController::class, 'index'])
            ->name('pages.paths.index')
            ->middleware('locale');
    }
}
