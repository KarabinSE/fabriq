# Upgrade Guide: v4.x to v5.x

This guide covers upgrading an existing Fabriq v4 installation to v5. v5 removes the `karabinse/translatable-revisions` package and the revision/versioning model. Content is now stored by Fabriq itself, one row per model field, with all locales kept in a single JSON value. Page "boxes" become `blocks` rows.

The upgrade includes **destructive, one-way data migrations**. Read the migration sections before you run them.

## Breaking Changes At A Glance

| Area                  | What changed in v5                                                                                                   | Required action                                                                          |
| --------------------- | -------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| PHP                   | Minimum PHP is 8.2 (was 8.0).                                                                                         | Upgrade PHP before upgrading the package.                                                |
| Dependencies          | `karabinse/translatable-revisions` is removed. `spatie/laravel-translatable` is added.                               | Remove any direct usage of `Karabin\TranslatableRevisions\*`.                            |
| Revisions             | Revision history, `published_version`, and the `revision` columns are gone. Saving a field overwrites its value.      | Stop using revision numbers. Publishing is the `published` boolean on pages.            |
| Content trait         | `HasTranslatedRevisions` is replaced by `Karabin\Fabriq\Traits\HasContent`.                                          | Swap the trait on custom content models.                                                 |
| Options class         | `RevisionOptions` / `getRevisionOptions()` is now `ContentOptions` / `getContentOptions()`.                          | Rename in custom models (see below).                                                     |
| Getter argument       | Getters receive `Karabin\Fabriq\Models\ContentValue` instead of `RevisionMeta`.                                      | Update custom getter type hints.                                                         |
| Templates             | `revision_templates` / `revision_template_fields` tables are now `content_templates` / `content_template_fields`.    | Update raw queries, custom models and seeders.                                           |
| Page boxes            | Page boxes are migrated from JSON content into the new `blocks` table.                                               | Review block output; update custom frontend code that read `boxes`.                      |
| Search indexing       | `UpdateSearchTerms` is model-agnostic and driven by `ContentOptions::setIndexable()`.                                | Call `setIndexable()` on every model you want indexed.                                   |
| Template filtering    | `getSimpleFieldContent()` and `getFieldContent()` return only keys defined on the model's content template.          | Make sure every stored field has a template field.                                       |
| Removed classes       | `PublishPageController` and `BustPageCacheListener` are removed. `MakeRevisionField` is now `MakeTemplateField`.    | Remove references.                                                                       |
| Live page responses   | Live page content is returned at `data.content` without an inner `data` wrapper.                                     | Update frontend consumers of the live page endpoint.                                     |
| Config                | `i18nDefinition` is removed from `models`. `block` is added to `models`. `block` and `preview_page` DTOs are added.  | Republish or merge `config/fabriq.php`.                                                  |

## Before You Start

1. **Back up the database.** The migrations cannot be rolled back (their `down()` methods throw). A backup is your only way back.
2. Test the upgrade on a copy of production data first. Check how long it takes and that it completes without errors.
3. Put the application in maintenance mode while migrating. Content is rewritten and old and new code must not run against the same tables.
4. Create a clean Git branch and commit or preserve local changes.
5. Confirm the application runs on PHP 8.2 or later.

## 1. Upgrade Fabriq

```bash
composer require karabinse/fabriq:^5.0 -W
composer dump-autoload
```

Composer removes `karabinse/translatable-revisions`. If your application requires it directly or imports its classes, migrate that code first (see step 3).

## 2. Run the Migrations

```bash
php artisan fabriq:update
```

Or run `php artisan migrate` directly. Review the output and confirm that all migrations completed (`php artisan migrate:status`).

The migrations run in this order. All dates are in the 2026 migration file names.

### Blocks and menu paths (additive)

| Migration                                         | What it does                                                                                    |
| ------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `2026_10_14_065053_create_blocks_table`           | Creates `blocks` (name, locale, JSON `content`, `hidden`, nested `parent_id`, polymorphic owner, `block_type_id`, `sortindex`). |
| `2026_10_20_141500_add_path_to_menu_items_table`  | Adds a nullable JSON `path` column to `menu_items`. Paths are updated when content is saved.    |

These only add structures and are safe to repeat on a copy of the data.

### `2026_10_20_085139_convert_boxes_to_blocks`

Reads each page's legacy `boxes` field (from `i18n_terms` and `i18n_definitions`, per locale) and creates `blocks` rows: one per box, plus child rows for nested `children`. The box's `block_type.id` becomes `block_type_id`, and the remaining box data becomes the block's JSON `content`.

Implications:

- It must run **before** the content consolidation below, because it reads the old tables. Do not reorder migrations.
- It is skipped in the `testing` environment.
- It only copies. The old `boxes` content is left in place until the consolidation migration rewrites it, but `boxes` is not a template field in v5 and is ignored when reading content. Check that your pages render their blocks correctly.
- Custom frontend code or API consumers that read `boxes` from page content must read blocks instead.

### `2026_10_21_090000_replace_revision_content_with_translations` (destructive)

This is the main migration. It collapses the revision system into one content row per field:

- For each model and field, it selects the current version (terms with a matching `model_version`, or the highest version if none matches) and ignores older versions.
- It merges all locales into one `i18n_definitions` row, stored as a JSON object keyed by locale. Unlocalised values from `revision_meta` are stored under the `*` key.
- It resolves legacy references to other terms into their values.
- It renames each term key to `{table}-{model_id}-{field_key}` and updates `slugs.source_key` to match.
- It drops `i18n_definitions.locale`, `i18n_terms.model_version`, and the related indexes. It adds a unique index on `(model_type, model_id, field_key)`.
- It drops the `revision_meta` and `revision_snapshots` tables.
- It drops `revision`, `published_version` and (for pages) `published_at` from `pages` and `articles`, and `revision` from `blocks`.

Implications:

- **Revision history is permanently lost.** Only the current version of each field is kept. Back up the data if you need the history.
- Page publishing relies on the `published` boolean (added in v4, `2026_08_10_114418`). `published_version` and `published_at` no longer exist. Replace any code, queries, exports or reports that used them.
- The existing `i18n_definitions` and `i18n_terms` rows are deleted and rebuilt inside a transaction. On databases that cannot roll back DDL (e.g. MySQL), the schema changes after the transaction are not covered, so a failure part-way needs a restore from backup.
- Duration grows with the number of terms and definitions. Expect it to be the slowest migration.

### `2026_10_21_100000_drop_legacy_i18n_term_columns`

Copies each term's `field_key` into `slugs.source_key` for any rows still using the old term key, then drops `i18n_terms.key`, `description` and `group` (and their indexes). Code that looks up terms by `key` must use `model_type`, `model_id` and `field_key` instead.

### `2026_10_21_110000_ensure_menu_item_title_template_field`

Makes sure the `menu-item` template exists and has the `title`, `url`, `body`, `file` and `external_url` fields (translated, group `menu`). Existing fields are not touched. This matters because v5 only returns content for fields that exist on the template. Without a `title` field, menu item titles would disappear.

### `2026_10_21_120000_rename_revision_templates_to_content_templates`

Renames `revision_templates` to `content_templates` and `revision_template_fields` to `content_template_fields`. Foreign keys follow the rename. Update any raw SQL, views, custom models or seeders that reference the old names. Fabriq's models are `ContentTemplate` and `ContentTemplateField`, and the DTO is `ContentTemplateData`.

### `2026_10_21_130000_consolidate_i18n_definitions` (destructive)

Moves `model_type`, `model_id` and `field_key` from `i18n_terms` onto `i18n_definitions`, adds the unique index `i18n_definitions_model_field_unique`, then drops `i18n_definitions.term_id` (with its foreign key), `i18n_definitions.approved` and the whole `i18n_terms` table.

The migration **aborts before changing anything** (throws `RuntimeException`) when:

- a definition has no term, or its term has an empty model type, model id or field key,
- two definitions exist for the same model field, or
- media is attached to an `I18nDefinition` (`fabriq_i18nDefinition`, `Karabin\Fabriq\Models\I18nDefinition` or `Ikoncept\Fabriq\Models\I18nDefinition`). Media Library support was removed from definitions.

If it aborts, clean up the reported rows (delete orphans, merge duplicates, move or delete the media) and run it again.

Implications:

- `I18nDefinition` now holds the content directly. The `i18nDefinition` entry in `config/fabriq.php` `models` is removed.
- `approved` is gone.

### Other changes to existing migrations

The older migrations (2020 to 2026-03) were edited to use Fabriq's own tables instead of the `translatable-revisions` package, and `convert_morph_names` gained a class-name resolver for edge cases. Databases that already ran them are unaffected. They only matter for fresh installs.

### Rollback

None of the destructive migrations can be reversed. To go back, restore the database backup and reinstall v4.

## 3. Update Application Code

### Models using revisions

Replace the trait and the options method.

```php
// v4
use Karabin\TranslatableRevisions\Traits\HasTranslatedRevisions;
use Karabin\TranslatableRevisions\Traits\RevisionOptions;

class Report extends Model
{
    use HasTranslatedRevisions;

    public function getRevisionOptions(): RevisionOptions
    {
        return RevisionOptions::create()
            ->registerSpecialTypes(['image'])
            ->registerGetters(['image' => 'getImages'])
            ->registerCacheKeysToFlush(['fabriq_reports'])
            ->setIndexable(true, ['title'], 'title');
    }

    public function getImages(RevisionMeta $meta)
    {
        return ImageGetter::get($meta);
    }
}
```

```php
// v5
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Traits\HasContent;

class Report extends Model
{
    use HasContent;

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes(['image'])
            ->registerGetters(['image' => 'getImages'])
            ->registerResourceReferenceKeys(['image'])
            ->setIndexable(true, ['title'], 'title');
    }

    public function getContentCacheKeys(): array
    {
        return ['fabriq_reports'];
    }

    public function getImages(ContentValue $contentValue)
    {
        return ImageGetter::get($contentValue);
    }
}
```

Notes:

- Cache keys to flush are now returned by `getContentCacheKeys()`, not registered on the options.
- `registerResourceReferenceKeys()` lists the keys that reference images, files, videos, pages or smart blocks. They are loaded in batches (`ContentResourceContext`) instead of one query per item. Controllers wrap serialisation in the context for you. In custom code, wrap your own serialisation in `ContentResourceContext::withModels()` or you get one query per reference.
- If a model's template is not found through `template_id`, override `getContentTemplateSlug()` (for example `'article'`) to set the fallback template slug.

### Reading and writing content

- Use `updateContent(array $fieldData, ?string $locale = null)`. There is no revision argument.
- Use `getSimpleFieldContent()` for raw stored values and `getFieldContent()` for values after getters and repeaters. Both **drop keys that are not fields on the model's content template**. Add a template field (via a migration, seeder or the admin) for any key you still need to read.
- Saving content dispatches `ContentUpdated`. Listeners (slug, cache busting webhook, search terms, tag cache flush) hang off it. Replace any `BustPageCacheListener` registration with the `ContentUpdated` listeners.

### Search terms

Indexing is no longer hard-coded to pages. A model is indexed when its `ContentOptions` say so:

```php
->setIndexable(true, ['title', 'preamble', 'publishes_at'], 'title')
```

Arguments are: indexable flag, the keys to index (content fields and model attributes), the key used as the title, and an optional callable that replaces the default writer. `Page` is indexed only while `published` is true. A model that never calls `setIndexable()` is no longer indexed. When no path exists for a locale, the first slug is used.

Search terms are not rebuilt by the migrations. Re-save content or run your own reindex to refresh them after the upgrade.

### Templates

- Rename `RevisionTemplate`, `RevisionTemplateField`, `RevisionTemplateData` and `RevisionTemplateController` usage to `ContentTemplate`, `ContentTemplateField`, `ContentTemplateData` and `ContentTemplateController`.
- The `fabriq:make-revision-field` command is now `fabriq:make-template-field`. Update any scripts that call it.
- Custom page template migrations that insert into `revision_templates` / `revision_template_fields` must use the new table names.

### Pages and publishing

- Remove uses of `published_version`, `published_at` and revision numbers.
- The `PublishPageController` is removed. Publish by setting `published`.

### Live page API

Live page content is returned directly under `data.content`. The admin `PageData` response still wraps content as `data.content.data`. Update the frontend reading the live page response accordingly.

## 4. Merge Configuration

Compare `config/fabriq.php` with the package version, or republish it:

```php
'models' => [
    // removed: 'i18nDefinition' => I18nDefinition::class,
    'block' => Block::class,
],
'data_transfer_objects' => [
    'block' => BlockData::class,
    'preview_page' => PreviewPageData::class,
],
```

If you override `models` or `data_transfer_objects`, add these entries.

## 5. Regenerate TypeScript Types

New and renamed DTOs (`BlockData`, `PreviewPageData`, `ContentTemplateData`, the media content DTOs) change the generated types. Regenerate them if your application publishes Fabriq's TypeScript definitions.

## 6. Verify the Upgrade

1. `php artisan migrate:status` shows no pending migrations.
2. Spot-check content in every locale: open pages, articles, smart blocks, contacts and menu items and compare them with the pre-upgrade copy.
3. Confirm each page's blocks render with the correct nesting and order.
4. Confirm menu items have titles and paths, and that slugs still resolve.
5. Save a page and an article. Confirm search terms are updated and caches are flushed.
6. Check the public site for slow pages. Many queries per page indicate a custom code path outside `ContentResourceContext`.
7. Run your application's test suite.

## Troubleshooting

| Symptom                                                     | Cause and fix                                                                                          |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `Cannot consolidate i18n tables: ... no valid model and field identity.` | Orphan or incomplete definitions. Delete or repair them, then rerun the migration.      |
| `... multiple definitions exist for the same model field.`  | Duplicates after the content migration. Remove duplicates, then rerun.                                  |
| `Cannot remove Media Library support from i18n definitions` | Media is attached to definitions. Move or delete the media, then rerun.                                 |
| A field is missing from content responses                   | No matching template field. Add it to the model's content template.                                     |
| Model is not found in search                                | `setIndexable()` is not configured for that model, or the content was not re-saved.                     |
| `Class 'Karabin\TranslatableRevisions\...' not found`       | Application code still uses the removed package. Migrate it as described in step 3.                     |
