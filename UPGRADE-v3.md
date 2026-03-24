# Upgrade Guide: v2.x to v3.x

This guide covers upgrading an existing Fabriq installation from v2.x to v3.x.

## Breaking Changes At A Glance

| Area | What changed in v3 | Impact if skipped | Required action |
| --- | --- | --- | --- |
| Namespaces | Package classes moved from Ikoncept to Karabin namespaces | Class not found errors and failing custom integrations | Replace imports/usages from Ikoncept\\* to Karabin\\* in app code and config |
| Polymorphic relation types | Legacy class-name model types are converted to morph aliases (for example fabriq_page) | Missing related data for media/comments/tags/slugs/roles | Run migrations and verify no legacy model_type values remain |
| Page content structure | Page/revision content is migrated to full JSON structure | Content read/write behavior can break in custom logic | Run migrations and test create/edit/publish flows per locale |
| Revisions and i18n storage | New translatable revision snapshot/i18n lookup schema and indexes | Slow or incorrect revision/translation lookups | Run migrations and verify revision history + locale behavior |
| Route registration patterns | Route registrar usage is expected for API and web route groups | Missing endpoints or auth/middleware mismatch | Align routes with current Fabriq::routes examples |
| Published package assets | Stubs/views/frontend/config may be stale from older release | UI/runtime inconsistencies after upgrade | Republish relevant tags and re-apply local customizations |

Scope:
- Existing projects already running Fabriq v2.x
- Namespace migration from Ikoncept to Karabin
- Data and schema migrations introduced in v3

Out of scope:
- Fresh installs
- Non-upgrade refactors unrelated to v3 migration safety

## Tested Baseline

Use this guide with the following minimum tested baseline:
- PHP 8.3+
- Laravel 11+ app bootstrap style
- MySQL 8+

Notes:
- The package composer constraints are broader, but this guide is written for the baseline above.
- The current CI workflow runs PHP 8.3, 8.4, and 8.5.

## Before You Start

1. Create a full database backup.
2. Run the upgrade in a staging environment first.
3. Schedule a maintenance window for production.
4. Ensure your project is on a clean git branch for the migration work.

## 1. Update Dependency

Update Fabriq to v3 in your project:

```bash
composer require karabinse/fabriq "^3.0" -W
```

Then install dependencies and refresh autoloading:

```bash
composer install
composer dump-autoload
```

## 2. Migrate Namespaces (Ikoncept -> Karabin)

v3 uses Karabin namespaces. Update all application code that imports or references old Ikoncept classes.

Search for old references:

```bash
rg "Ikoncept\\\\Fabriq|Ikoncept\\\\" app config routes database tests
```

Typical replacements:
- Ikoncept\\Fabriq\\* -> Karabin\\Fabriq\\*
- Ikoncept\\TranslatableRevisions\\* -> Karabin\\TranslatableRevisions\\*

High-priority places to check:
- User model inheritance
- Policies and listeners
- Custom service providers
- Config model bindings
- Custom serializers that may store class names

## 3. Publish Updated Package Assets

If your app publishes package assets, update them before running feature verification.

```bash
php artisan vendor:publish --provider="Karabin\\Fabriq\\FabriqCoreServiceProvider" --tag=fabriq-frontend-assets --force
php artisan vendor:publish --provider="Karabin\\Fabriq\\FabriqCoreServiceProvider" --tag=fabriq-stubs --force
php artisan vendor:publish --provider="Karabin\\Fabriq\\FabriqCoreServiceProvider" --tag=fabriq-views --force
php artisan vendor:publish --provider="Karabin\\Fabriq\\FabriqCoreServiceProvider" --tag=config --force
```

Review and keep your project-specific customizations after publishing.

## 4. Run Migrations

Run all pending migrations:

```bash
php artisan migrate
```

Important v3 migration areas:
- Morph type conversion for polymorphic relations
- Content migration to full JSON structure
- Translatable revision snapshot and i18n lookup changes

These are implemented by migrations such as:
- database/migrations/2024_09_01_063043_convert_morph_names.php
- database/migrations/2024_10_16_081051_migrate_data_to_full_json_structure.php
- database/migrations/2026_03_17_101732_create_translatable_revision_snapshots_table.php
- database/migrations/2026_03_17_101734_add_revision_meta_indexes.php
- database/migrations/2026_03_17_101736_add_i18n_definition_unique_term_locale.php
- database/migrations/2026_03_17_101737_add_i18n_term_lookup_columns.php

## 5. Verify Polymorphic Type Migration

Fabriq now relies on morph aliases (for example fabriq_page, fabriq_article) instead of legacy class-name model types in relation columns.

Check that old values are gone:

```bash
php artisan tinker --execute="dump(DB::table('media')->where('model_type', 'like', 'Ikoncept%')->count());"
php artisan tinker --execute="dump(DB::table('taggables')->where('taggable_type', 'like', 'Ikoncept%')->count());"
php artisan tinker --execute="dump(DB::table('comments')->where('commentable_type', 'like', 'Ikoncept%')->count());"
php artisan tinker --execute="dump(DB::table('model_has_roles')->where('model_type', 'like', 'Ikoncept%')->orWhere('model_type', 'like', 'App%')->count());"
```

Expected result: zero legacy type rows.

## 6. Update Routes and Bootstrap Assumptions

Ensure your route registration follows the package route registrar pattern.

API routes example:

```php
use Karabin\Fabriq\Fabriq;

Fabriq::routes(function ($router) {
    $router->forApiAdminProtected();
}, [
    'middleware' => ['auth:sanctum', 'role:admin', 'verified'],
    'prefix' => 'admin',
]);

Fabriq::routes(function ($router) {
    $router->forApiProtected();
}, [
    'middleware' => ['auth:sanctum'],
]);

Fabriq::routes(function ($router) {
    $router->forPublicApi();
});
```

Web routes example:

```php
use Karabin\Fabriq\Fabriq;

Fabriq::routes(function ($router) {
    $router->allWeb();
});
```

If your app is Laravel 11+, make sure your bootstrap and middleware setup are aligned with the published stubs.

## 7. Custom Code Audit Checklist

Review these extension points in your application:
- Custom models extending Fabriq models
- Any overridden getMorphClass behavior
- Repository interface implementations
- Custom content getters / page-content transforms
- Event listeners depending on legacy payload shapes

If you bind custom models in config, verify model keys and class names in your app config stay valid after namespace replacement.

## 8. Verification Playbook

Run automated checks:

```bash
php artisan migrate:status
php artisan test
```

Run focused smoke tests:
1. Admin login and dashboard access
2. Create/edit/publish page in at least one locale
3. Confirm public API responses for page slug and image srcset
4. Upload and attach media to content
5. Comment/tag/slug flows

Confirm no lingering old namespaces:

```bash
rg "Ikoncept\\\\Fabriq|Ikoncept\\\\TranslatableRevisions|Ikoncept\\\\" app config routes database tests
```

## Troubleshooting

Class not found after upgrade:
- Cause: leftover Ikoncept namespace references in app code.
- Fix: replace imports/usages and run composer dump-autoload.

Polymorphic relations return empty data:
- Cause: old model_type values not migrated.
- Fix: confirm migration completion and check legacy values in affected tables.

Admin or API routes missing:
- Cause: route groups not registered with current Fabriq::routes patterns.
- Fix: align routes with updated API and web examples above.

Unexpected frontend/admin issues:
- Cause: stale published assets.
- Fix: republish relevant tags with --force and re-apply local customizations.

## Rollback Guidance

Data migrations in v3 transform content and relation metadata. Rollback should be treated as database-restore driven.

Recommended rollback method:
1. Restore full database backup.
2. Revert dependency update commit.
3. Reinstall dependencies.
