<?php

namespace Karabin\Fabriq;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\AcceptInvitationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ArticleController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\AskToLeaveNotificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\AuthenticatedUserController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\BlockTypeController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\BustCacheController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ClonePageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\CommentableController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\CommentController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ConfigController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ContactController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ContactSortController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\DeclineToLeaveNotificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\DownloadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\EmailVerificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\EventController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\FileController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\FileUploadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageableController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageSourceSetController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ImageUploadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\InvitationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\MediaDownloadController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\MenuController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\MenuItemController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\MenuItemTreeController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\ModelCountController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\NotificationController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PagePathController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageSignedUrlController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageSlugPreviewController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageSlugsController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PageTreeController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\PublishPageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\RevisionTemplateController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\RoleController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\SmartBlockController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\TagController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\UserController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\UserImageController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\VideoController;
use Karabin\Fabriq\Http\Controllers\Api\Fabriq\VideoUploadController;
use Karabin\Fabriq\Http\Controllers\PermalinksRedirectController;
use Karabin\Fabriq\Http\Controllers\SpaController;
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

        Route::prefix('admin')->group(function () {
            Route::get('/email/verify', function ($request) {
                return view('auth.verify-email', ['request' => $request]);
            })->middleware('auth')->name('verification.notice');

            Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
                $request->fulfill();

                return redirect('/profile/settings');
            })->middleware(['auth', 'signed'])->name('verification.verify');

            Route::get('/email/verification-notification', function () {
                config('fabriq.models.user')::find(1)->sendEmailVerificationNotification();

                return 'ok';
            })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

            Route::get('/', [SpaController::class, 'index'])->middleware('auth');
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
        Route::resource('articles', ArticleController::class);
    }

    public function forContacts(): void
    {
        Route::post('contacts/sort-contacts', ContactSortController::class)
            ->name('contacts.sort');
        Route::resource('contacts', ContactController::class);
    }

    public function forBlockTypes(): void
    {
        Route::resource('block-types', BlockTypeController::class);
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
        Route::resource('events', EventController::class);
    }

    public function forFiles(): void
    {
        Route::get('files', [FileController::class, 'index']);
        Route::get('files/{id}', [FileController::class, 'show']);
        Route::patch('files/{id}', [FileController::class, 'update']);
        Route::delete('files/{id}', [FileController::class, 'destroy']);
    }

    public function forImages(): void
    {
        Route::get('images/{id}/src-set', [ImageSourceSetController::class, 'show']);
        Route::get('/{model}/{id}/images', [ImageableController::class, 'index']);
        Route::post('/images/{id}/{model}', [ImageableController::class, 'store']);
        Route::get('images', [ImageController::class, 'index']);
        Route::get('images/{id}', [ImageController::class, 'show']);
        Route::patch('images/{id}', [ImageController::class, 'update']);
        Route::delete('images/{id}', [ImageController::class, 'destroy']);
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
        Route::get('menus/{slug}/public', [MenuItemTreeController::class, 'show']);
        Route::get('{model}/count', [ModelCountController::class, 'show']);

        // Uploads
        Route::post('uploads/images', [ImageUploadController::class, 'store']);
        Route::post('uploads/files', [FileUploadController::class, 'store']);
        Route::post('uploads/videos', [VideoUploadController::class, 'store']);
    }

    public function forMenus(): void
    {
        Route::get('menus', [MenuController::class, 'index']);
        Route::post('menus', [MenuController::class, 'store']);
        Route::get('menus/{id}', [MenuController::class, 'show']);
        Route::patch('menus/{id}', [MenuController::class, 'update']);
        Route::delete('menus/{id}', [MenuController::class, 'destroy']);
        Route::get('menus/{id}/items/tree', [MenuItemTreeController::class, 'index']);
        Route::patch('menus/{id}/items/tree', [MenuItemTreeController::class, 'update']);
        Route::post('/menus/{id}/items', [MenuItemController::class, 'store']);

        Route::get('menu-items/{id}', [MenuItemController::class, 'show']);
        Route::patch('menu-items/{id}', [MenuItemController::class, 'update']);
        Route::delete('menu-items/{id}', [MenuItemController::class, 'destroy']);
    }

    public function forPages(): void
    {
        Route::get('pages-tree', [PageTreeController::class, 'index']);
        Route::patch('pages-tree', [PageTreeController::class, 'update']);
        Route::get('pages/{slug}/live', [PageSlugsController::class, 'show']);
        Route::get('pages', [PageController::class, 'index']);
        Route::post('pages', [PageController::class, 'store']);
        Route::get('pages/{id}', [PageController::class, 'show']);
        Route::patch('pages/{id}', [PageController::class, 'update']);
        Route::delete('pages/{id}', [PageController::class, 'destroy']);
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
        Route::resource('smart-blocks', SmartBlockController::class);
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
        Route::resource('users', UserController::class);
    }

    public function forNotifications()
    {
        Route::post('/notifications/ask-to-leave/{userId}', AskToLeaveNotificationController::class)
            ->name('notifications.ask-to-leave');
        Route::post('/notifications/decline-to-leave/{userId}', DeclineToLeaveNotificationController::class)
            ->name('notifications.decline-to-leave');
        Route::get('/user/notifications', [NotificationController::class, 'index']);
        Route::patch('/user/notifications/{id}', [NotificationController::class, 'update']);
    }

    public function forVideos(): void
    {
        Route::get('videos', [VideoController::class, 'index']);
        Route::get('videos/{id}', [VideoController::class, 'show']);
        Route::patch('videos/{id}', [VideoController::class, 'update']);
        Route::delete('videos/{id}', [VideoController::class, 'destroy']);
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
