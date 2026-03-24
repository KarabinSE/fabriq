[![Latest Stable Version](http://poser.pugx.org/karabinse/fabriq/v)](https://packagist.org/packages/karabinse/fabriq)
[![tests](https://github.com/karabinse/fabriq/actions/workflows/phpunit.yml/badge.svg)](https://github.com/karabinse/fabriq/actions/workflows/phpunit.yml)
[![PHPStanLevel7](https://github.com/karabinse/fabriq/actions/workflows/phpStan.yml/badge.svg)](https://github.com/karabinse/fabriq/actions/workflows/phpStan.yml)
[![PHP Version Require](http://poser.pugx.org/karabinse/fabriq/require/php)](https://packagist.org/packages/karabinse/fabriq)


![Fabriq CMS logo](https://media.fabriq-cms.se/public/fabriq-og-image-1200.jpg)

## Fabriq CMS

## Upgrade guides

- See [UPGRADE-v3.md](UPGRADE-v3.md) for upgrading an existing project from v2.x to v3.x.

## Installation instructions 💻

Add the customer repository url for the make-user-command in your `composer.json` file:

```
...
    "repositories": {
        "0": {
            "type": "vcs",
            "url": "https://github.com/KarabinSE/laravel-make-user"
        }
    },

```

Install Fabriq:

```
composer require karabinse/fabriq "^2.0" -W
```

If you're planning on using AWS s3:
```bash
# Laravel > 9
composer require --with-all-dependencies league/flysystem-aws-s3-v3 "^1.0"

# Laravel 9+
composer require league/flysystem-aws-s3-v3 "^3.0"
```

Install the Mailgun driver
```bash
composer require symfony/mailgun-mailer symfony/http-client
```


Install [Laravel Sanctum](https://github.com/laravel/sanctum) as well for authentication
```
composer require laravel/sanctum
```

Add the domain to the `.env` file:
```
SANCTUM_STATEFUL_DOMAINS=your-domain.test
SESSION_DOMAIN=your-domain.test
```

Publish the configurations:
```
php artisan vendor:publish --provider="Karabin\Fabriq\FabriqCoreServiceProvider" --tag=config
php artisan vendor:publish --provider="Karabin\TranslatableRevisions\TranslatableRevisionsServiceProvider" --tag=config
```

Setup your database using the .env


## Modify the user model 🧘

The user model need to extend the Fabriq\Models\User::class

```php
// app/Models/User.php

//...
use Karabin\Fabriq\Models\User as FabriqUser;
//...

class User extends FabriqUser

// ...
```

Run the `fabriq:install` command:
```
php artisan fabriq:install
```
This command will publish front end assets and views. It will also run the migrations

**Important** Delete the files `app.js` and `bootstrap.js` in the `resources/js` directory
```
rm resources/js/app.js && rm resources/js/bootstrap.js
```


Run `pnpm install` and `pnpm production` to build assets
```
pnpm install && pnpm production
```

## Auth configuration 🗝


### Laravel v11 and above

>[!NOTE]
> On Laravel 11 and up the step below is not necessary since the files are overwritten when installing

### Laravel below v11
Enable the Laravel Sanctum middleware in `app\Http\Kernel.php`
```php
    // app\Http\Kernel.php

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // <---
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

```


## Register routes 🛣

>[!NOTE]
> On Laravel 11 and up the this is not necessary since the files are overwritten when installing

Register the routes that makes sense for your app. See below examples
```php
// routes/api.php
use Karabin\Fabriq\Fabriq;

Fabriq::routes(function ($router) {
    $router->forDevProtected();
}, [
    'middleware' => ['auth:sanctum', 'role:dev', 'verified'],
    'prefix' => 'dev'
]);

Fabriq::routes(function ($router) {
    $router->forApiAdminProtected();
}, [
    'middleware' => ['auth:sanctum', 'role:admin', 'verified'],
    'prefix' => 'admin'
]);

Fabriq::routes(function ($router) {
    $router->forApiProtected();
}, [
    'middleware' => ['auth:sanctum']
]);

Fabriq::routes(function ($router) {
    $router->forPublicApi();
});


```

```php
// routes/web.php

use Karabin\Fabriq\Fabriq;

Fabriq::routes(
    function ($router) {
        $router->allWeb();
    }
);
```


Create your first user in the database, or by using a package like [michaeldyrynda/laravel-make-user](https://github.com/michaeldyrynda/laravel-make-user)


#### Publishing assets 🗄️
Assets can be published using their respective tags. The tags that are available are:
* `config` - The config file
* `fabriq-translations` - Translations for auth views and validation messages
* `fabriq-frontend-assets` - Front end build system and Vue project files
* `fabriq-views` - Blade views and layouts

You can publish these assets using the command below:
```
php artisan vendor:publish --provider="Karabin\Fabriq\FabriqCoreServiceProvider" --tag=the-tag
```

If you want to overwrite your old published assets with new ones (for example when the package has updated views) you can use the `--force` flag
```
php artisan vendor:publish --provider="Karabin\Fabriq\FabriqCoreServiceProvider" --tag=fabriq-views --force
```

**Note** _Above tags have been published when the `fabriq:install` was run_

### Broadcasting 📢
Fabriq leverages [laravel/echo](https://github.com/laravel/echo) as a front end dependency to communicate with a pusher server. This package is preconfigured to use Ikoncept's own websocket server, but a pusher implementation can be swapped in.

For the migrated Inertia admin, Echo is initialized on demand from the package runtime when websocket config is available. There is no manual `resources/js/plugins/index.js` import step anymore, and there is no client-side route middleware wiring to maintain.

Don't forget to add the proper `.env` variables:

```
BROADCAST_DRIVER=ikoncept_pusher
PUSHER_APP_ID=400
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
PUSHER_APP_CLUSTER=mt1
```

Presence and broadcast subscriptions for the migrated admin are package-owned and are wired from the relevant Inertia surfaces, primarily the page editor/comments runtime. Host apps only need the websocket configuration and backend broadcasting setup.



### Updating ♻️
You can publish new front end assets with the `php artisan fabriq:update` command. This command will publish new front end assets and run migrations.

### Done? 🎉
That should be it, serve the app and login at `/login`

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Testing

```
composer test
```
