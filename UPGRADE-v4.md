# Upgrade Guide: v3.x to v4.x

This guide covers upgrading an existing Fabriq v3 installation to v4. The upgrade reorganizes frontend assets and route files.

## Breaking Changes At A Glance

| Area                     | What changed in v4                                                              | Required action                                                                                  |
| ------------------------ | ------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Frontend assets          | Fabriq-owned JavaScript and CSS moved to`resources/fabriq/`.                  | Keep custom code in application-owned directories such as`resources/js` and `resources/css`. |
| JavaScript imports       | `@fabriq/` maps to `resources/fabriq/js`; `@/` maps to `resources/js`.  | Use`@fabriq/` for Fabriq modules and `@/` for application modules.                           |
| Vue initialization       | Fabriq provides`createFabriqApp()` to install its Vue plugins.                | Merge the v4 setup into your app entrypoint without removing custom setup.                       |
| CSS and Tailwind         | Fabriq uses Tailwind CSS 4 and its stylesheet is under`resources/fabriq/css`. | Merge the v4 CSS setup into your app stylesheet and keep custom styles there.                    |
| Routes and public assets | Fabriq routes and public assets use dedicated Fabriq paths.                     | Keep custom routes in the app route files and update any old asset URLs.                         |

## Quick guide

For a v3-to-v4 upgrade, the short version is:

1. Back up the database and files, and commit or preserve your current work.
2. Update the Composer package:

   ```bash
   composer require karabinse/fabriq:^4.0 -W
   ```
3. Run the normal update command:

   ```bash
   php artisan fabriq:update
   ```
4. On the first v4 update, confirm the structure migration when prompted. Fabriq moves the entire existing `resources` directory to `resources_backup` and creates a clean `resources` directory for v4. Recover your custom code and assets from the backup as needed; do not move old Fabriq-owned files back into the new tree.
5. Keep custom components, routes, and code outside `resources/fabriq`. Keep custom frontend routes and sidebar items in `resources/js/routes/`; `fabriq:update` updates Fabriq's router and built-in routes under `resources/fabriq/js/routes/` and leaves your app route files alone. The command adds `resources/js/app.js` and `resources/css/app.css` only if they are missing; if you already have either file, merge in the v4 setup yourself.
6. Install frontend dependencies and build the app using your package manager, then smoke-test the site.
7. new separate routes for fabriq are added in `routes/fabriq/web,api` , go over `routes/web,api` and remove any duplicate routes.

After changing the package version, use `php artisan fabriq:update` to publish Fabriq's updates and run migrations. Do not run the fresh-install publish command as part of routine updates.

See the detailed sections below for import, CSS, route, and verification steps.

## V4 File Layout

```text
resources/
├── css/
│   └── app.css                     # App stylesheet and custom CSS
├── js/
│   ├── app.js                      # App entry point
│   ├── block-types/                # Custom block types
│   └── routes/
│       ├── routes.js               # Custom Vue routes
│       └── sidebar-items.js        # Custom sidebar items
└── fabriq/                         # Fabriq-managed; updated by fabriq:update
    ├── css/                        # Fabriq styles
    └── js/
        ├── routes/
        │   ├── fabriq-routes.js    # Fabriq's built-in routes
        │   └── router.js           # Fabriq's router
        └── ...                     # Fabriq components, plugins, models, etc.

resources_backup/                   # Original resources directory, kept for recovery
```

Keep your custom code in `resources/js` and `resources/css`, not in `resources/fabriq`. During the first v3-to-v4 update, confirm the prompt to move the entire old `resources` directory to the sibling `resources_backup` directory. The updater then publishes the v4 structure and adds missing app entrypoints. Recover your custom components, routes, styles, views, and other app-owned assets from the backup as needed. If `resources_backup` already exists, the updater stops rather than overwriting it.

## Before You Start

1. Back up the database and any uploaded or locally managed files.
2. Test the upgrade in a staging environment before upgrading production.
3. Create a clean Git branch and commit or otherwise preserve local changes.
4. Identify local edits in published Fabriq assets, views, routes, and frontend configuration. The v4 layout separates package-owned assets from application-owned code; do not discard local work while moving files.

## 1. Upgrade Fabriq

Update the package to v4 and refresh Composer's autoloader:

```bash
composer require karabinse/fabriq:^4.0 -W
composer dump-autoload
```

If your project deploys from a lock file, review and commit the resulting `composer.lock` changes.

## 2. Migrate with `fabriq:update`

Run the normal update command:

```bash
php artisan fabriq:update
```

Review and resolve any prompt about local Git changes before continuing. The command publishes the update assets, views, and images, then runs pending database migrations.

### Move and update frontend code

- Fabriq-owned JavaScript and Vue components belong in `resources/fabriq/js`.
- Fabriq-owned styles belong in `resources/fabriq/css`.
- Keep application entry points, custom components, and project-specific code in `resources/js` and `resources/css`.
- Keep custom block types and other project extensions under application-owned paths such as `resources/js/block-types`. Do not put custom components inside the Fabriq-managed `resources/fabriq/js` tree, since update publishes are allowed to replace files there.
- Keep application-specific Vue routes in `resources/js/routes/routes.js` and sidebar items in `resources/js/routes/sidebar-items.js`. These are application-owned files and are not replaced by `fabriq:update`. Fabriq's built-in routes and router live in `resources/fabriq/js/routes/`.
- Do not delete `resources/js` or `resources/css` wholesale. Before removing old Fabriq files from those directories, move or reapply any local customizations to the corresponding v4 Fabriq files, or keep them as application-owned components.
- Change imports of Fabriq-owned modules from `@/...` to `@fabriq/...`. Leave imports of application-owned modules using `@/...`.

The v4 path aliases should resolve to:

```json
{
    "compilerOptions": {
        "target": "ESNext",
        "paths": {
            "@/*": ["./resources/js/*"],
            "@fabriq/*": ["./resources/fabriq/js/*"]
        }
    },
    "exclude": ["node_modules"]
}
```

Make sure both `vite.config.js` and `jsconfig.json` resolve the aliases consistently.

### Update the Vue entry point, app.js

V4 includes a `createFabriqApp()` wrapper that creates the Vue application and installs Fabriq's plugins. Use it from your frontend entry point, retaining any application-specific registrations or setup:

```js
import { createFabriqApp } from '@fabriq/fabriq'

import customRoutes from '@/routes/routes'
import '@/../css/app.css'
import blockTypes from '@/block-types/index.js'

createFabriqApp()
    .withRoutes(customRoutes)
    .use(blockTypes)
    .mount()
```

Export your custom routes as an array from `resources/js/routes/routes.js`; pass that array to `.withRoutes()`. Register any custom Vue plugins on Vue before calling `createFabriqApp()`. If your custom block types are already registered by the v4 package, do not register them again. Ensure that the entry point named by your Blade `@vite(...)` directive is also listed as an input in `vite.config.js`.

### Update styles and Tailwind

The v4 CSS entry point uses Tailwind 4 and imports Fabriq's stylesheet from `resources/fabriq/css`. Merge the v4 imports and Tailwind setup into your existing `resources/css/app.css`; do not replace application styles or custom configuration wholesale. Move custom styles into your application stylesheet rather than editing Fabriq's published CSS.

The following v3 Fabriq styles/configuration have moved or are no longer used at their old paths:

- `resources/css/fabriq.css`
- `resources/css/v-tooltip.css`
- `resources/css/vue-select.css`
- `resources/css/theme.css`
- `resources/css/fabriq.tailwind.config.js`
- `postcss.config.js` (remove only if it was solely for the old Tailwind setup)

Preserve any custom CSS or PostCSS plugins and migrate them to the v4 setup as appropriate. Do not remove a PostCSS configuration that is still used by other tooling.

```CSS
@import 'tailwindcss';
@import "../fabriq/css/fabriq.css";

@source "../../views/**/*.blade.php";
@source "../js/**/*.js";

@theme {
    --primary: oklch(92% 0.006 75),
    --secondary: oklch(75% 0.025 250),
    --tertiary: oklch(45% 0.03 228),

    --btn-primary: oklch(53% 0.006 75),
    --btn-secondary: oklch(75% 0.025 250),
    --btn-tertiary: oklch(55% 0.03 228),
}

@theme inline {
    --color-primary: var(--primary);
    --color-secondary: var(--secondary);
    --color-tertiary: var(--tertiary);

    --color-btn-primary: var(--btn-primary);
    --color-btn-secondary: var(--btn-secondary);
    --color-btn-tertiary: var(--btn-tertiary);
}
```

### Check routes and public asset URLs

Fabriq's default routes now live in `routes/fabriq/web.php` and `routes/fabriq/api.php`; application-specific routes remain in `routes/web.php` and `routes/api.php`. Check your Laravel routing/bootstrap setup to ensure the Fabriq route files and your application route files are all registered, with the intended middleware and prefixes. Do not move custom routes into Fabriq's package-owned route files.

The default v4 publishing paths place fonts and images in `public/fabriq/fonts` and `public/fabriq/images`. Review custom layouts, CSS, and JavaScript for hard-coded references to their old locations.

## 3. Review Views and Translations

`fabriq:update` publishes Fabriq views. If you have customized published views, compare them with the v4 package versions and reapply the changes rather than overwriting them blindly. Publish translations separately if your application needs the updated package translations:

```bash
php artisan vendor:publish \
  --provider="Karabin\\Fabriq\\FabriqCoreServiceProvider" \
  --tag=fabriq-views

php artisan vendor:publish \
  --provider="Karabin\\Fabriq\\FabriqCoreServiceProvider" \
  --tag=fabriq-translations
```

Preserve intentional translation overrides. Do not force-publish either tag over customized files.

## 4. Install Frontend Dependencies and Build

Use the package manager and lockfile used by your application. For a pnpm project:

```bash
pnpm install
pnpm run build
```

Resolve build errors before proceeding. In particular, check stale import paths, Vite aliases and entry points, and any app code that still imports Fabriq-owned files through `@/`.

## 5. Verify Database Migrations

`php artisan fabriq:update` runs pending migrations. Check that they completed:

```bash
php artisan migrate:status
```

Review the update command output and confirm all expected migrations completed before bringing the application back into service.

## 6. Verify the Upgrade

Smoke-test the application:

1. Sign in to the admin area and confirm the frontend assets load.
2. Create, edit, and publish a page; check custom block types and Fabriq components.
3. Exercise any custom web and API routes.
4. Check images, fonts, uploads, and other public assets.
5. Verify any customized views, CSS, and application-specific Vue plugins.

Search for imports that may still point to the previous Fabriq asset location:

```bash
rg "@/.*(components|plugins|models|stores|routes)" resources/js
```

Review each match; application-owned modules should remain on `@/`, while imports of Fabriq-owned modules should use `@fabriq/`.

## Troubleshooting

**Vite reports that an imported module cannot be found**

- Check whether the module is application-owned (`resources/js`, imported with `@/`) or Fabriq-owned (`resources/fabriq/js`, imported with `@fabriq/`).
- Confirm the aliases in `vite.config.js` and `jsconfig.json` point to the v4 directories.

**The admin area is missing routes or returns 404**

- Confirm that both application and Fabriq route files are registered.
- Check route prefixes and middleware in the Laravel routing/bootstrap configuration.

**The page loads without Fabriq styles or frontend plugins**

- Confirm the Blade `@vite(...)` entry matches an input in `vite.config.js`.
- Confirm the entry point calls `createFabriqApp()` and imports the application stylesheet.
- Rebuild the frontend assets after installing dependencies.

**Fonts or images return 404**

- Check that the published files exist under `public/fabriq/fonts` or `public/fabriq/images`.
- Update custom URLs that still use v3 public paths.

## Rollback Guidance

Do not treat frontend publishing or database migrations as automatically reversible. To roll back safely:

1. Restore the database and uploaded files from the backups made before the upgrade.
2. Revert the application changes on the upgrade branch, including dependency and frontend lockfile changes.
3. Restore any overwritten or moved published files from version control or backup.
4. Reinstall dependencies and rebuild assets for the restored v3 version.
