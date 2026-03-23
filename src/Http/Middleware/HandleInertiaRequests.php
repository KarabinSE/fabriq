<?php

namespace Karabin\Fabriq\Http\Middleware;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Karabin\TranslatableRevisions\Models\I18nLocale;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'vendor.fabriq.index';

    /**
     * @var array<string, string>
     */
    private const MODULE_ROUTE_ALIASES = [
        'home.index' => 'admin.dashboard',
        'pages.index' => 'admin.pages.index',
        'smartBlocks.index' => 'admin.smart-blocks.index',
        'articles.index' => 'admin.articles.index',
        'contacts.index' => 'admin.contacts.index',
        'calendar.index' => 'admin.calendar.index',
        'users.index' => 'admin.users.index',
        'menus.index' => 'admin.menus.index',
        'images.index' => 'admin.media.images.index',
        'videos.index' => 'admin.media.videos.index',
        'files.index' => 'admin.media.files.index',
    ];

    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user && method_exists($user, 'loadMissing')) {
            $relations = [];

            if (method_exists($user, 'roles')) {
                $relations[] = 'roles';
            }

            if (method_exists($user, 'image')) {
                $relations[] = 'image.media';
            }

            if ($relations !== []) {
                $user->loadMissing($relations);
            }
        }

        $unreadNotificationsCount = $user && method_exists($user, 'notifications')
            ? $user->notifications()->whereNull('cleared_at')->count()
            : 0;
        $fop = I18nLocale::all();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $this->transformAuthUser($user, $unreadNotificationsCount),
            ],
            'fabriq' => [
                'appName' => config('app.name', 'Fabriq CMS'),
                'modules' => $this->resolveModules($user),
                'wsPrefix' => config('fabriq.ws_prefix'),
                'supportedLocales' => I18nLocale::where('enabled', 1)->get(),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'status_action_label' => fn () => $request->session()->get('status_action_label'),
                'status_action_href' => fn () => $request->session()->get('status_action_href'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transformAuthUser(?Authenticatable $user, int $unreadNotificationsCount): ?array
    {
        if (! $user) {
            return null;
        }

        $roles = method_exists($user, 'roles')
            ? $user->roles->pluck('name')->values()->all()
            : [];

        $image = null;

        if (method_exists($user, 'image')) {
            $profileImage = $user->image;
            $media = $profileImage?->getFirstMedia('profile_image');

            $image = [
                'id' => $profileImage?->id,
                'thumbSrc' => $media?->getUrl('thumb'),
                'src' => $media?->getUrl(),
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles,
            'timezone' => 'Europe/Stockholm',
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'unread_notifications_count' => $unreadNotificationsCount,
            'image' => $image,
        ];
    }

    /**
     * @return array<int, array{label: string, href: string, icon: string}>
     */
    private function resolveModules(?Authenticatable $user): array
    {
        $userRoles = method_exists($user, 'roles')
            ? array_values(array_filter($user->roles->pluck('name')->all(), fn ($role) => is_string($role) && $role !== ''))
            : [];

        /** @var array<int, mixed> $configuredModules */
        $configuredModules = config('fabriq.modules', []);
        $modules = [];

        foreach ($configuredModules as $configuredModule) {
            if (! is_array($configuredModule) || ! ($configuredModule['enabled'] ?? false)) {
                continue;
            }

            $roles = array_values(array_filter(
                is_array($configuredModule['roles'] ?? null) ? $configuredModule['roles'] : [],
                fn ($role) => is_string($role) && $role !== '',
            ));

            if ($roles !== [] && array_intersect($userRoles, $roles) === []) {
                continue;
            }

            $routeName = $this->resolveModuleRouteName((string) ($configuredModule['route'] ?? ''));

            if ($routeName === null) {
                continue;
            }

            $label = (string) ($configuredModule['title'] ?? '');

            if ($label === '') {
                continue;
            }

            $modules[] = [
                'label' => $label,
                'href' => route($routeName, absolute: false),
                'icon' => (string) ($configuredModule['icon'] ?? ''),
            ];
        }

        return $modules;
    }

    private function resolveModuleRouteName(string $routeName): ?string
    {
        if ($routeName === '') {
            return null;
        }

        $resolvedRouteName = self::MODULE_ROUTE_ALIASES[$routeName] ?? $routeName;

        return Route::has($resolvedRouteName) ? $resolvedRouteName : null;
    }
}
