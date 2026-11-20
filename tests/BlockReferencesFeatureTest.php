<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Actions\BustCacheWithWebhook;
use Karabin\Fabriq\Actions\UpdateMenuItemPaths;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Http\Middleware\BatchCacheWebhooks;
use Karabin\Fabriq\Models\Block;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Models\SmartBlock;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\Services\BlockReferenceIndex;
use Karabin\Fabriq\Services\CacheWebhookBatcher;
use Karabin\Fabriq\Services\DefinitionReferenceIndex;
use Karabin\Fabriq\Services\ReferenceRegistry;
use Karabin\Fabriq\Tests\AdminUserTestCase;
use Karabin\Fabriq\Traits\HasContent;
use Karabin\Fabriq\Traits\InvalidatesReferences;
use RuntimeException;
use Spatie\WebhookServer\Events\DispatchingWebhookCallEvent;

class BlockReferencesFeatureTest extends AdminUserTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', true);
    }

    private function recordWebhooks(): BustCacheWithWebhook
    {
        $recorder = new class extends BustCacheWithWebhook
        {
            public array $calls = [];

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->calls[] = [$keysToForget, $tagsToFlush, $rateLimit];
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);

        return $recorder;
    }

    private function pageWithSlug(string $slug): Page
    {
        $page = Page::factory()->create();
        Slug::create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'source_string' => $slug,
            'source_key' => 'page_title',
            'locale' => 'sv',
        ]);

        return $page;
    }

    public function test_index_tracks_nested_references_and_replaces_removed_values(): void
    {
        $page = Page::factory()->create();
        $block = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'content' => [
                'gallery' => [['image' => [4, 5, 4]]],
                'video' => 6,
                'buttons' => [
                    ['linkType' => 'internal', 'page_id' => 7, 'file' => 9],
                    ['linkType' => 'file', 'file' => ['id' => 8]],
                    ['linkType' => 'external', 'page_id' => 10],
                ],
                'unrelated' => ['page_id' => 11],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['image:4', 'image:5', 'video:6', 'page:7', 'file:8'],
            DB::table('block_references')->where('block_id', $block->id)->get()
                ->map(fn ($row) => $row->resource_type.':'.$row->resource_id)->all(),
        );

        $block->content = ['image' => 12];
        $block->save();
        $this->assertSame(['image:12'], DB::table('block_references')->where('block_id', $block->id)->get()
            ->map(fn ($row) => $row->resource_type.':'.$row->resource_id)->all());
        $block->delete();
        $this->assertDatabaseMissing('block_references', ['block_id' => $block->id]);
    }

    public function test_rebuild_indexes_legacy_content_and_cascaded_children_are_removed(): void
    {
        $page = Page::factory()->create();
        $type = BlockType::factory()->create();
        $parent = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'block_type_id' => $type->id,
        ]);
        $child = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'parent_id' => $parent->id,
            'content' => ['image' => 1],
        ]);
        DB::table('blocks')->where('id', $child->id)->update([
            'content' => json_encode(['image' => [['id' => 13], ['id' => 14]]]),
        ]);

        $this->artisan('fabriq:rebuild-block-references')->assertSuccessful();
        $this->assertDatabaseHas('block_references', ['block_id' => $child->id, 'resource_type' => 'image', 'resource_id' => 13]);
        $this->assertDatabaseHas('block_references', ['block_id' => $child->id, 'resource_type' => 'image', 'resource_id' => 14]);
        $this->assertDatabaseMissing('block_references', ['block_id' => $child->id, 'resource_id' => 1]);
        $this->artisan('fabriq:rebuild-block-references')->assertSuccessful();
        $this->assertSame(2, DB::table('block_references')->where('block_id', $child->id)->count());

        Block::updateBlocks([], $page);
        $this->assertDatabaseMissing('block_references', ['block_id' => $child->id]);
    }

    public function test_block_webhook_is_sent_after_its_references_are_updated(): void
    {
        $page = $this->pageWithSlug('updated-page');
        $block = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'content' => ['image' => 1],
        ]);
        $recorder = new class($block->id) extends BustCacheWithWebhook
        {
            public array $references = [];

            public function __construct(private int $blockId) {}

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->references[] = DB::table('block_references')->where('block_id', $this->blockId)
                    ->pluck('resource_id')->all();
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);
        $block->content = ['image' => 2];
        $block->save();

        $this->assertSame([[2]], $recorder->references);
    }

    public function test_index_errors_are_surfaced_without_sending_the_block_webhook(): void
    {
        $page = $this->pageWithSlug('failed-index');
        $block = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'content' => ['image' => 1],
        ]);
        $recorder = $this->recordWebhooks();
        $this->mock(BlockReferenceIndex::class)
            ->shouldReceive('sync')->once()->andThrow(new RuntimeException('index failed'));

        $block->content = ['image' => 2];
        try {
            $block->save();
            $this->fail('Expected index error.');
        } catch (RuntimeException $exception) {
            $this->assertSame('index failed', $exception->getMessage());
        }

        $this->assertSame(2, $block->fresh()->content['image']);
        $this->assertSame([], $recorder->calls);
    }

    public function test_block_tree_notifies_after_removed_children_are_deleted(): void
    {
        $page = $this->pageWithSlug('tree-page');
        $type = BlockType::factory()->create();
        $parent = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'block_type_id' => $type->id, 'locale' => 'sv',
        ]);
        $child = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'parent_id' => $parent->id, 'content' => ['image' => 1],
        ]);
        $recorder = new class($child->id) extends BustCacheWithWebhook
        {
            public array $childExists = [];

            public function __construct(private int $childId) {}

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->childExists[] = DB::table('block_references')->where('block_id', $this->childId)->exists();
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);

        Block::updateOrCreateBlock([
            'uuid' => $parent->uuid, 'name' => $parent->name,
            'locale' => 'sv', 'block_type_id' => $type->id, 'content' => [], 'children' => [],
        ], $page);

        $this->assertNotEmpty($recorder->childExists);
        $this->assertFalse(end($recorder->childExists));
    }

    public function test_block_update_sends_one_webhook_after_all_blocks_and_references_are_written(): void
    {
        $page = $this->pageWithSlug('batched-blocks');
        $type = BlockType::factory()->create();
        $parent = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'block_type_id' => $type->id, 'locale' => 'sv', 'content' => ['image' => 1],
        ]);
        $child = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'block_type_id' => $type->id, 'parent_id' => $parent->id, 'content' => ['image' => 2],
        ]);
        $referrer = $this->pageWithSlug('block-referrer');
        Block::factory()->create([
            'model_id' => $referrer->id, 'model_type' => $referrer->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $page->id]],
        ]);
        $recorder = new class($page->id) extends BustCacheWithWebhook
        {
            public array $calls = [];

            public function __construct(private int $pageId) {}

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->calls[] = [
                    $keysToForget,
                    DB::table('block_references')->join('blocks', 'blocks.id', '=', 'block_references.block_id')
                        ->where('blocks.model_id', $this->pageId)
                        ->orderBy('resource_id')->pluck('resource_id')->all(),
                ];
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);

        $this->putJson(route('blocks.update', $page->id), ['blocks' => [
            [
                'uuid' => $parent->uuid, 'name' => 'Parent', 'locale' => 'sv',
                'block_type_id' => $type->id, 'content' => ['image' => 3],
                'children' => [['name' => 'New child', 'content' => ['image' => 4]]],
            ],
            [
                'name' => 'Second block', 'locale' => 'sv',
                'block_type_id' => $type->id, 'content' => ['image' => 5],
            ],
        ]])->assertOk();

        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_batched-blocks', $recorder->calls[0][0]);
        $this->assertContains('fabriq_pages_slug_block-referrer', $recorder->calls[0][0]);
        $this->assertSame([3, 4, 5], $recorder->calls[0][1]);
        $this->assertDatabaseMissing('blocks', ['id' => $child->id]);
    }

    public function test_page_update_combines_content_and_slug_notifications_after_save(): void
    {
        $page = $this->pageWithSlug('old-page-slug');
        $source = $this->pageWithSlug('linking-page');
        Block::factory()->create([
            'model_id' => $source->id, 'model_type' => $source->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $page->id]],
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $page->template_id, 'key' => 'meta_og_image', 'type' => 'image', 'translated' => true,
        ]);
        $recorder = new class($page->id) extends BustCacheWithWebhook
        {
            public array $calls = [];

            public function __construct(private int $pageId) {}

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->calls[] = [
                    $keysToForget,
                    DB::table('pages')->where('id', $this->pageId)->value('name'),
                    DB::table('content_references')->join('i18n_definitions', 'i18n_definitions.id', '=', 'content_references.definition_id')
                        ->where('i18n_definitions.model_type', 'fabriq_page')
                        ->where('i18n_definitions.model_id', $this->pageId)
                        ->orderBy('resource_id')->pluck('resource_id')->all(),
                ];
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);

        $this->patchJson('/pages/'.$page->id, [
            'name' => 'Updated page', 'published' => true,
            'localizedContent' => [
                'sv' => ['page_title' => 'New Swedish title', 'meta_og_image' => 31],
                'en' => ['page_title' => 'New English title', 'meta_og_image' => 32],
            ],
        ])->assertOk();

        $this->assertCount(1, $recorder->calls);
        $keys = $recorder->calls[0][0];
        $this->assertContains('fabriq_pages_slug_old-page-slug', $keys);
        $this->assertContains('fabriq_pages_slug_new-swedish-title', $keys);
        $this->assertContains('fabriq_pages_slug_new-english-title', $keys);
        $this->assertContains('fabriq_pages_slug_linking-page', $keys);
        $this->assertSame('Updated page', $recorder->calls[0][1]);
        $this->assertSame([31, 32], $recorder->calls[0][2]);

        $recorder->calls = [];
        $this->patchJson('/pages/'.$page->id, [
            'name' => 'Updated again', 'published' => true,
            'localizedContent' => ['sv' => ['page_title' => 'New Swedish title']],
        ])->assertOk();
        $this->assertCount(1, $recorder->calls);
        $this->assertSame('Updated again', $recorder->calls[0][1]);
    }

    public function test_page_content_update_invalidates_pages_with_internal_button_references(): void
    {
        $target = $this->pageWithSlug('target-page');
        $target->published = true;
        $target->save();
        $blockOwner = $this->pageWithSlug('block-owner');
        $definitionOwner = $this->pageWithSlug('definition-owner');
        $this->pageWithSlug('unrelated-page');
        Block::factory()->create([
            'model_id' => $blockOwner->id, 'model_type' => $blockOwner->getMorphClass(),
            'content' => ['buttons' => [['type' => 'link', 'linkType' => 'internal', 'page_id' => $target->id]]],
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $definitionOwner->template_id, 'key' => 'button', 'type' => 'button', 'translated' => true,
        ]);
        $definitionOwner->updateContent(['button' => ['linkType' => 'internal', 'page_id' => $target->id]], 'sv');
        ContentTemplateField::factory()->create([
            'template_id' => $target->template_id, 'key' => 'meta_og_image', 'type' => 'image', 'translated' => true,
        ]);

        $recorder = new class($target->id) extends BustCacheWithWebhook
        {
            public array $calls = [];

            public function __construct(private int $targetId) {}

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->calls[] = [
                    $keysToForget,
                    DB::table('content_references')->join('i18n_definitions', 'i18n_definitions.id', '=', 'content_references.definition_id')
                        ->where('i18n_definitions.model_type', 'fabriq_page')
                        ->where('i18n_definitions.model_id', $this->targetId)
                        ->where('resource_type', 'image')->pluck('resource_id')->all(),
                ];
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);

        $this->patchJson('/pages/'.$target->id, [
            'name' => $target->name, 'published' => true,
            'localizedContent' => ['sv' => ['meta_og_image' => 42]],
        ])->assertOk();

        $this->assertCount(1, $recorder->calls);
        $keys = $recorder->calls[0][0];
        $this->assertContains('fabriq_pages_slug_target-page', $keys);
        $this->assertContains('fabriq_pages_slug_block-owner', $keys);
        $this->assertContains('fabriq_pages_slug_definition-owner', $keys);
        $this->assertNotContains('fabriq_pages_slug_unrelated-page', $keys);
        $this->assertSame([42], $recorder->calls[0][1]);
    }

    public function test_page_model_and_content_updates_invalidate_referring_pages(): void
    {
        $target = $this->pageWithSlug('direct-target');
        $source = $this->pageWithSlug('direct-source');
        Block::factory()->create([
            'model_id' => $source->id, 'model_type' => $source->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $target->id]],
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $target->template_id, 'key' => 'summary', 'type' => 'text', 'translated' => true,
        ]);
        $recorder = $this->recordWebhooks();

        $target->name = 'Updated name';
        $target->save();
        $this->assertContains('fabriq_pages_slug_direct-source', collect($recorder->calls)->pluck(0)->flatten()->all());

        $recorder->calls = [];
        $target->updateContent(['summary' => 'Updated summary'], 'sv');
        $this->assertContains('fabriq_pages_slug_direct-source', collect($recorder->calls)->pluck(0)->flatten()->all());
    }

    public function test_failed_batch_flushes_completed_changes_and_does_not_leak_into_later_saves(): void
    {
        $recorder = $this->recordWebhooks();
        $batcher = app(CacheWebhookBatcher::class);
        try {
            $batcher->defer(function () use ($batcher) {
                $batcher->send(['first']);
                $batcher->defer(function () use ($batcher) {
                    $batcher->send(['first', 'second'], ['tag']);
                    throw new RuntimeException('save failed');
                });
            });
            $this->fail('Expected save error.');
        } catch (RuntimeException $exception) {
            $this->assertSame('save failed', $exception->getMessage());
        }

        $this->assertSame([[['first', 'second'], ['tag'], false]], $recorder->calls);
        $batcher->send(['later']);
        $this->assertSame([['later'], [], true], $recorder->calls[1]);
    }

    public function test_custom_route_middleware_flushes_on_failure_without_leaking(): void
    {
        $recorder = $this->recordWebhooks();
        Route::middleware(BatchCacheWebhooks::class)->post('/test-reference-batch', function () {
            app(CacheWebhookBatcher::class)->send(['completed']);
            throw new RuntimeException('request failed');
        });

        try {
            $this->withoutExceptionHandling()->postJson('/test-reference-batch');
            $this->fail('Expected request error.');
        } catch (RuntimeException $exception) {
            $this->assertSame('request failed', $exception->getMessage());
        }

        $this->assertSame([[['completed'], [], false]], $recorder->calls);
        app(CacheWebhookBatcher::class)->send(['later']);
        $this->assertSame([['later'], [], true], $recorder->calls[1]);
    }

    public function test_resource_and_media_changes_invalidate_only_referring_pages(): void
    {
        $first = $this->pageWithSlug('first-reference');
        $second = $this->pageWithSlug('second-reference');
        $unrelated = $this->pageWithSlug('unrelated');
        $image = Image::factory()->create();
        $type = BlockType::factory()->create();
        foreach ([$first, $second] as $page) {
            Block::factory()->create([
                'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
                'block_type_id' => $type->id, 'content' => ['image' => $image->id],
            ]);
        }
        $recorder = $this->recordWebhooks();

        $image->update(['alt_text' => 'Updated']);

        $keys = $recorder->calls[0][0];
        $this->assertContains('fabriq_pages_slug_first-reference', $keys);
        $this->assertContains('fabriq_pages_slug_second-reference', $keys);
        $this->assertNotContains('fabriq_pages_slug_unrelated', $keys);
        $this->assertFalse($recorder->calls[0][2]);
        $this->getJson(route('pages.blocks.index', $first->id))
            ->assertOk()->assertJsonPath('0.content.image.alt_text', 'Updated');

        $recorder->calls = [];
        $media = $image->getFirstMedia('images');
        $media->setCustomProperty('tiny_svg', 'new');
        $media->save();
        $this->assertContains('fabriq_pages_slug_first-reference', $recorder->calls[0][0]);

        $recorder->calls = [];
        $image->delete();
        $this->assertContains('fabriq_pages_slug_first-reference', collect($recorder->calls)->pluck(0)->flatten()->all());
        $this->assertDatabaseHas('block_references', ['resource_type' => 'image', 'resource_id' => $image->id]);
    }

    public function test_asset_updates_send_one_webhook_after_both_records_are_saved(): void
    {
        $page = $this->pageWithSlug('asset-update');
        $image = Image::factory()->create();
        $file = File::factory()->create();
        $video = Video::factory()->create();
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'content' => ['image' => $image->id, 'file' => $file->id, 'video' => $video->id],
        ]);
        $recorder = new class extends BustCacheWithWebhook
        {
            public string $table;

            public string $column;

            public int $id;

            public int $mediaId;

            public array $calls = [];

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->calls[] = [
                    $keysToForget,
                    DB::table($this->table)->where('id', $this->id)->value($this->column),
                    DB::table('media')->where('id', $this->mediaId)->value('name'),
                ];
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);
        $updates = [
            [$image, 'images', 'alt_text', ['name' => 'Updated image', 'alt_text' => 'Updated alt', 'custom_crop' => false, 'x_position' => '50%', 'y_position' => '50%']],
            [$file, 'files', 'caption', ['name' => 'Updated file', 'caption' => 'Updated file caption', 'tags' => []]],
            [$video, 'videos', 'caption', ['name' => 'Updated video', 'caption' => 'Updated video caption', 'tags' => []]],
        ];
        foreach ($updates as [$resource, $collection, $column, $payload]) {
            $recorder->table = $resource->getTable();
            $recorder->column = $column;
            $recorder->id = $resource->id;
            $recorder->mediaId = $resource->getFirstMedia($collection)->id;
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $recorder->calls = [];
                $this->patchJson('/'.$collection.'/'.$resource->id, $payload)->assertOk();
                $this->assertCount(1, $recorder->calls);
                $this->assertContains('fabriq_pages_slug_asset-update', $recorder->calls[0][0]);
                $this->assertSame($payload[$column], $recorder->calls[0][1]);
                $this->assertSame($payload['name'], $recorder->calls[0][2]);
            }
        }
    }

    public function test_deferred_media_change_is_invalidated_if_the_later_save_fails(): void
    {
        $page = $this->pageWithSlug('partial-asset-update');
        $image = Image::factory()->create();
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'content' => ['image' => $image->id],
        ]);
        $recorder = $this->recordWebhooks();
        $media = $image->getFirstMedia('images');

        try {
            app(CacheWebhookBatcher::class)->defer(function () use ($media) {
                $media->name = 'Persisted name';
                $media->save();
                throw new RuntimeException('save failed');
            });
            $this->fail('Expected save error.');
        } catch (RuntimeException $exception) {
            $this->assertSame('save failed', $exception->getMessage());
        }

        $this->assertDatabaseHas('media', ['id' => $media->id, 'name' => 'Persisted name']);
        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_partial-asset-update', $recorder->calls[0][0]);
        $image->update(['alt_text' => 'Changed separately']);
        $this->assertCount(2, $recorder->calls);
    }

    public function test_custom_reference_fields_in_blocks_and_definitions_are_rebuilt_and_invalidated(): void
    {
        $registry = app(ReferenceRegistry::class);
        $registry->registerField('product', 'product');
        $registry->registerField('featured_product', 'product', fn ($value) => [$value['entity_id']]);

        $product = ReferenceTestProduct::create(['alt_text' => 'old']);
        $blockPage = $this->pageWithSlug('product-block');
        $definitionPage = $this->pageWithSlug('product-definition');
        $block = Block::factory()->create([
            'model_id' => $blockPage->id, 'model_type' => $blockPage->getMorphClass(),
            'content' => ['product' => $product->id],
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $definitionPage->template_id, 'key' => 'hero_product',
            'type' => 'featured_product', 'translated' => true,
        ]);
        $definitionPage->updateContent(['hero_product' => ['entity_id' => $product->id]], 'sv');
        $definition = $definitionPage->contentDefinitions()->where('field_key', 'hero_product')->firstOrFail();
        $this->artisan('fabriq:rebuild-block-references')->assertSuccessful();
        $this->assertDatabaseHas('block_references', ['block_id' => $block->id, 'resource_type' => 'product', 'resource_id' => $product->id]);
        $this->assertDatabaseHas('content_references', ['definition_id' => $definition->id, 'resource_type' => 'product', 'resource_id' => $product->id]);

        $recorder = $this->recordWebhooks();
        $product->update(['alt_text' => 'new']);
        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_product-block', $recorder->calls[0][0]);
        $this->assertContains('fabriq_pages_slug_product-definition', $recorder->calls[0][0]);
        $this->assertContains('product_alt_text_old', $recorder->calls[0][0]);
        $this->assertContains('product_alt_text_new', $recorder->calls[0][0]);
    }

    public function test_custom_dependencies_are_traversed_with_cycles_and_preserved_on_delete(): void
    {
        $registry = app(ReferenceRegistry::class);
        $registry->registerField('product', 'product');
        $registry->registerField('category', 'category');
        $category = ReferenceTestCategory::create(['caption' => 'Category']);
        $product = ReferenceTestProduct::create(['alt_text' => 'Product']);
        $page = $this->pageWithSlug('category-product');
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'content' => ['product' => $product->id],
        ]);
        $definition = I18nDefinition::create([
            'model_type' => $product->getMorphClass(), 'model_id' => $product->id,
            'field_key' => 'category', 'content' => ['sv' => $category->id],
        ]);
        $registry->registerDependencies('product', fn (int $id) => [
            ['type' => 'category', 'id' => $category->id],
        ]);
        $recorder = $this->recordWebhooks();
        $category->update(['caption' => 'Changed']);
        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_category-product', $recorder->calls[0][0]);
        $this->assertContains('product_alt_text_Product', $recorder->calls[0][0]);

        $recorder->calls = [];
        $definition->delete();
        $registry->registerDependencies('category', fn (int $id) => ReferenceTestCategory::find($id)
            ? [['type' => 'product', 'id' => $product->id]]
            : []);
        $category->delete();
        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_category-product', $recorder->calls[0][0]);
    }

    public function test_reference_changes_are_resolved_after_the_batch_finishes(): void
    {
        app(ReferenceRegistry::class)->registerField('product', 'product');
        $product = ReferenceTestProduct::create(['alt_text' => 'old']);
        $page = $this->pageWithSlug('late-reference');
        $recorder = $this->recordWebhooks();

        app(CacheWebhookBatcher::class)->defer(function () use ($product, $page, $recorder) {
            $product->update(['alt_text' => 'new']);
            Block::factory()->create([
                'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
                'content' => ['product' => $product->id],
            ]);
            $this->assertSame([], $recorder->calls);
        });

        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_late-reference', $recorder->calls[0][0]);
    }

    public function test_custom_block_owners_propagate_reference_and_block_changes(): void
    {
        $registry = app(ReferenceRegistry::class);
        $registry->registerField('product', 'product');
        $registry->registerField('category', 'category');
        $category = ReferenceTestCategory::create(['caption' => 'Category']);
        $product = ReferenceTestProduct::create(['alt_text' => 'Product']);
        $page = $this->pageWithSlug('product-owner');
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'content' => ['product' => $product->id],
        ]);
        $block = Block::factory()->create([
            'model_id' => $product->id, 'model_type' => $product->getMorphClass(),
            'content' => ['category' => $category->id],
        ]);
        $recorder = $this->recordWebhooks();

        $category->update(['caption' => 'Changed']);
        $this->assertContains('fabriq_pages_slug_product-owner', $recorder->calls[0][0]);
        $this->assertContains('product_alt_text_Product', $recorder->calls[0][0]);

        $recorder->calls = [];
        $block->update(['content' => ['category' => $category->id, 'title' => 'Changed']]);
        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_product-owner', $recorder->calls[0][0]);
        $this->assertContains('product_alt_text_Product', $recorder->calls[0][0]);
    }

    public function test_custom_model_content_changes_invalidate_its_referrers(): void
    {
        app(ReferenceRegistry::class)->registerField('product', 'product');
        $product = ReferenceTestProduct::create(['alt_text' => 'Product']);
        $page = $this->pageWithSlug('product-content');
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'content' => ['product' => $product->id],
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $page->template_id, 'key' => 'product_summary', 'type' => 'text', 'translated' => true,
        ]);
        $recorder = $this->recordWebhooks();

        app(CacheWebhookBatcher::class)->defer(fn () => $product->updateContent(['product_summary' => 'Changed'], 'sv'));
        $this->assertCount(1, $recorder->calls);
        $this->assertContains('fabriq_pages_slug_product-content', $recorder->calls[0][0]);
    }

    public function test_translated_page_image_references_are_indexed_and_invalidate_the_page(): void
    {
        $page = $this->pageWithSlug('metadata-page');
        ContentTemplateField::factory()->create([
            'template_id' => $page->template_id, 'key' => 'meta_og_image', 'type' => 'image', 'translated' => true,
        ]);
        $swedishImage = Image::factory()->create();
        $englishImage = Image::factory()->create();
        $page->updateContent(['meta_og_image' => $swedishImage->id], 'sv');
        $page->updateContent(['meta_og_image' => $englishImage->id], 'en');
        $definition = $page->contentDefinitions()->where('field_key', 'meta_og_image')->firstOrFail();
        $this->assertDatabaseHas('content_references', [
            'definition_id' => $definition->id, 'resource_type' => 'image', 'resource_id' => $swedishImage->id,
        ]);
        $this->assertDatabaseHas('content_references', [
            'definition_id' => $definition->id, 'resource_type' => 'image', 'resource_id' => $englishImage->id,
        ]);

        $recorder = $this->recordWebhooks();
        $englishImage->update(['alt_text' => 'Updated']);
        $this->assertContains('fabriq_pages_slug_metadata-page', $recorder->calls[0][0]);

        config(['fabriq.webhooks.enabled' => false]);
        $page->updateContent(['meta_og_image' => null], 'sv');
        $this->assertDatabaseMissing('content_references', [
            'definition_id' => $definition->id, 'resource_type' => 'image', 'resource_id' => $swedishImage->id,
        ]);
        $this->assertDatabaseHas('content_references', [
            'definition_id' => $definition->id, 'resource_type' => 'image', 'resource_id' => $englishImage->id,
        ]);
        $definition->delete();
        $this->assertDatabaseMissing('content_references', ['definition_id' => $definition->id]);
    }

    public function test_definition_field_types_and_nested_content_are_indexed(): void
    {
        $page = Page::factory()->create();
        $fields = [
            ['hero', 'image', false],
            ['attachment', 'file', false],
            ['clip', 'video', false],
            ['items', 'repeater', true],
        ];
        foreach ($fields as [$key, $type, $repeater]) {
            ContentTemplateField::factory()->create([
                'template_id' => $page->template_id, 'key' => $key, 'type' => $type,
                'translated' => false, 'repeater' => $repeater,
            ]);
        }
        $page->updateContent([
            'hero' => 11,
            'attachment' => 12,
            'clip' => 13,
            'items' => [['image' => 14, 'button' => ['linkType' => 'file', 'file' => 15]]],
        ]);

        $this->assertEqualsCanonicalizing(
            ['image:11', 'file:12', 'video:13', 'image:14', 'file:15'],
            DB::table('content_references')->join('i18n_definitions', 'i18n_definitions.id', '=', 'content_references.definition_id')
                ->where('i18n_definitions.model_type', $page->getMorphClass())->where('i18n_definitions.model_id', $page->id)
                ->get(['resource_type', 'resource_id'])->map(fn ($row) => $row->resource_type.':'.$row->resource_id)->all(),
        );
    }

    public function test_definition_is_indexed_before_content_updated_event_and_errors_are_surfaced(): void
    {
        $page = $this->pageWithSlug('definition-order');
        ContentTemplateField::factory()->create([
            'template_id' => $page->template_id, 'key' => 'meta_og_image', 'type' => 'image', 'translated' => true,
        ]);
        $references = [];
        Event::listen(ContentUpdated::class, function () use (&$references) {
            $references[] = DB::table('content_references')->where('resource_type', 'image')
                ->pluck('resource_id')->all();
        });

        $page->updateContent(['meta_og_image' => 123], 'sv');
        $this->assertSame([[123]], $references);

        $references = [];
        $this->mock(DefinitionReferenceIndex::class)
            ->shouldReceive('sync')->once()->andThrow(new RuntimeException('index failed'));
        try {
            $page->updateContent(['meta_og_image' => 456], 'sv');
            $this->fail('Expected index error.');
        } catch (RuntimeException $exception) {
            $this->assertSame('index failed', $exception->getMessage());
        }

        $definition = $page->contentDefinitions()->where('field_key', 'meta_og_image')->firstOrFail();
        $this->assertSame(456, $definition->getTranslations('content')['sv']);
        $this->assertSame([], $references);
    }

    public function test_rebuild_indexes_existing_definitions(): void
    {
        $page = $this->pageWithSlug('old-metadata');
        ContentTemplateField::factory()->create([
            'template_id' => $page->template_id, 'key' => 'meta_og_image', 'type' => 'image', 'translated' => true,
        ]);
        $image = Image::factory()->create();
        $page->updateContent(['meta_og_image' => 1], 'sv');
        $definition = $page->contentDefinitions()->where('field_key', 'meta_og_image')->firstOrFail();
        DB::table('i18n_definitions')->where('id', $definition->id)
            ->update(['content' => json_encode(['sv' => $image->id, 'en' => ['id' => 42]])]);

        $this->artisan('fabriq:rebuild-block-references')->assertSuccessful();
        $this->assertDatabaseHas('content_references', [
            'definition_id' => $definition->id, 'resource_type' => 'image', 'resource_id' => $image->id,
        ]);
        $this->assertDatabaseHas('content_references', [
            'definition_id' => $definition->id, 'resource_type' => 'image', 'resource_id' => 42,
        ]);
        $recorder = $this->recordWebhooks();
        $image->update(['alt_text' => 'Changed']);
        $this->assertContains('fabriq_pages_slug_old-metadata', $recorder->calls[0][0]);
    }

    public function test_smart_block_resource_changes_invalidate_pages_referencing_it(): void
    {
        $page = $this->pageWithSlug('smart-block-page');
        ContentTemplateField::factory()->create([
            'template_id' => $page->template_id, 'key' => 'smartBlock', 'type' => 'smartBlock', 'translated' => true,
        ]);
        $template = ContentTemplate::query()->where('slug', 'smart_block')->firstOrFail();
        ContentTemplateField::factory()->create([
            'template_id' => $template->id, 'key' => 'image', 'type' => 'image', 'translated' => true,
        ]);
        $smartBlock = SmartBlock::factory()->create();
        $image = Image::factory()->create();
        $smartBlock->updateContent(['image' => $image->id], 'sv');
        $page->updateContent(['smartBlock' => $smartBlock->id], 'sv');
        $other = SmartBlock::factory()->create();
        foreach ([[$smartBlock, $other], [$other, $smartBlock]] as [$owner, $target]) {
            I18nDefinition::create([
                'model_type' => $owner->getMorphClass(), 'model_id' => $owner->id,
                'field_key' => 'smartBlock', 'content' => ['sv' => $target->id],
            ]);
        }
        $recorder = $this->recordWebhooks();

        $image->update(['alt_text' => 'Changed']);
        $keys = collect($recorder->calls)->pluck(0)->flatten()->all();
        $this->assertContains('fabriq_pages_slug_smart-block-page', $keys);
        $this->assertContains('fabriq_smart_blocks', $keys);

        $recorder->calls = [];
        $smartBlock->updateContent(['image' => $image->id], 'sv');
        $this->assertContains('fabriq_pages_slug_smart-block-page', collect($recorder->calls)->pluck(0)->flatten()->all());
    }

    public function test_existing_blocks_are_targeted_after_rebuilding_the_index(): void
    {
        $page = $this->pageWithSlug('existing-page');
        $image = Image::factory()->create();
        $block = Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
        ]);
        DB::table('blocks')->where('id', $block->id)->update([
            'content' => json_encode(['image' => $image->id]),
        ]);
        $recorder = $this->recordWebhooks();
        $image->update(['alt_text' => 'Updated']);
        $this->assertSame([], $recorder->calls);

        $this->artisan('fabriq:rebuild-block-references')->assertSuccessful();
        $image->update(['alt_text' => 'Updated again']);
        $this->assertContains('fabriq_pages_slug_existing-page', $recorder->calls[0][0]);
    }

    public function test_missing_reference_table_is_reported_without_sending_a_webhook(): void
    {
        $image = Image::factory()->create();
        $recorder = $this->recordWebhooks();
        Schema::shouldReceive('hasTable')->once()->with('block_references')->andReturn(false);
        Log::shouldReceive('warning')->once()
            ->with('Cannot invalidate block references: the block_references table does not exist.');

        $image->update(['alt_text' => 'Updated']);
        $this->assertSame([], $recorder->calls);
    }

    public function test_moving_media_invalidates_both_old_and_new_resource_owners(): void
    {
        $firstPage = $this->pageWithSlug('old-owner');
        $secondPage = $this->pageWithSlug('new-owner');
        $firstImage = Image::factory()->create();
        $secondImage = Image::factory()->create();
        foreach ([[$firstPage, $firstImage], [$secondPage, $secondImage]] as [$page, $image]) {
            Block::factory()->create([
                'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'content' => ['image' => $image->id],
            ]);
        }
        $recorder = $this->recordWebhooks();

        $media = $firstImage->getFirstMedia('images');
        $media->model_id = $secondImage->id;
        $media->save();
        $keys = collect($recorder->calls)->pluck(0)->flatten()->all();
        $this->assertContains('fabriq_pages_slug_old-owner', $keys);
        $this->assertContains('fabriq_pages_slug_new-owner', $keys);
    }

    public function test_file_and_video_updates_invalidate_referencing_pages(): void
    {
        $page = $this->pageWithSlug('assets-page');
        $file = File::factory()->create();
        $video = Video::factory()->create();
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(),
            'content' => ['button' => ['linkType' => 'file', 'file' => $file->id], 'video' => $video->id],
        ]);
        $recorder = $this->recordWebhooks();

        $file->caption = 'Updated';
        $file->save();
        $this->assertContains('fabriq_pages_slug_assets-page', $recorder->calls[0][0]);
        $recorder->calls = [];
        $video->caption = 'Updated';
        $video->save();
        $this->assertContains('fabriq_pages_slug_assets-page', $recorder->calls[0][0]);
    }

    public function test_linked_page_menu_path_changes_invalidate_referring_page(): void
    {
        $source = $this->pageWithSlug('link-source');
        $target = $this->pageWithSlug('link-target');
        Block::factory()->create([
            'model_id' => $source->id, 'model_type' => $source->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $target->id]],
        ]);
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id, 'page_id' => $target->id, 'locale' => 'sv']);
        $recorder = $this->recordWebhooks();

        $item->path = '/new-path';
        $item->save();
        $this->assertContains('fabriq_pages_slug_link-source', collect($recorder->calls)->pluck(0)->flatten()->all());

        $recorder->calls = [];
        $item->delete();
        $this->assertContains('fabriq_pages_slug_link-source', collect($recorder->calls)->pluck(0)->flatten()->all());
    }

    public function test_deleting_a_linked_page_notifies_referrers_and_its_old_keys(): void
    {
        $source = $this->pageWithSlug('link-source');
        $target = $this->pageWithSlug('link-target');
        Block::factory()->create([
            'model_id' => $source->id, 'model_type' => $source->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $target->id]],
        ]);
        $recorder = $this->recordWebhooks();

        $target->delete();
        $keys = collect($recorder->calls)->pluck(0)->flatten()->all();
        $this->assertContains('fabriq_pages_slug_link-source', $keys);
        $this->assertContains('fabriq_pages_slug_link-target', $keys);
    }

    public function test_linked_page_slug_change_invalidates_referrer_and_old_slug(): void
    {
        $source = $this->pageWithSlug('link-source');
        $target = $this->pageWithSlug('link-target');
        Block::factory()->create([
            'model_id' => $source->id, 'model_type' => $source->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $target->id]],
        ]);
        $recorder = $this->recordWebhooks();
        $slug = $target->slugs()->firstOrFail();
        $slug->source_string = 'changed-target';
        $slug->save();
        $keys = collect($recorder->calls)->pluck(0)->flatten()->all();
        $this->assertContains('fabriq_pages_slug_link-source', $keys);
        $this->assertContains('fabriq_pages_slug_link-target', $keys);
        $this->assertContains('fabriq_pages_slug_changed-target', $keys);
    }

    public function test_descendant_path_updates_and_parent_deletion_invalidate_linked_pages(): void
    {
        $source = $this->pageWithSlug('path-source');
        $parentPage = $this->pageWithSlug('path-parent');
        $target = $this->pageWithSlug('path-target');
        Block::factory()->create([
            'model_id' => $source->id, 'model_type' => $source->getMorphClass(),
            'content' => ['button' => ['linkType' => 'internal', 'page_id' => $target->id]],
        ]);
        $menu = Menu::factory()->create();
        $parent = MenuItem::create(['menu_id' => $menu->id, 'page_id' => $parentPage->id, 'locale' => 'sv', 'path' => '/old']);
        MenuItem::create(['menu_id' => $menu->id, 'page_id' => $target->id, 'parent_id' => $parent->id, 'locale' => 'sv', 'path' => '/old/target']);
        $recorder = $this->recordWebhooks();

        app(UpdateMenuItemPaths::class)($parent);
        $this->assertContains('fabriq_pages_slug_path-source', collect($recorder->calls)->pluck(0)->flatten()->all());
        $recorder->calls = [];
        $parent->delete();
        $this->assertContains('fabriq_pages_slug_path-source', collect($recorder->calls)->pluck(0)->flatten()->all());
    }

    public function test_webhook_payload_preserves_existing_contract(): void
    {
        $page = $this->pageWithSlug('webhook-page');
        $image = Image::factory()->create();
        Block::factory()->create([
            'model_id' => $page->id, 'model_type' => $page->getMorphClass(), 'content' => ['image' => $image->id],
        ]);
        config([
            'fabriq.webhooks.enabled' => true,
            'fabriq.webhooks.endpoint' => 'https://example.test/webhook',
            'webhook-server' => require __DIR__.'/../vendor/spatie/laravel-webhook-server/config/webhook-server.php',
        ]);
        Bus::fake();
        Event::fake([DispatchingWebhookCallEvent::class]);

        $image->update(['alt_text' => 'Updated']);
        Event::assertDispatched(DispatchingWebhookCallEvent::class, function ($event) {
            return $event->webhookUrl === 'https://example.test/webhook'
                && $event->payload['type'] === 'cache_expiration'
                && in_array('fabriq_pages_slug_webhook-page', $event->payload['invalid_cache_keys'], true)
                && $event->payload['invalid_cache_tags'] === [];
        });
    }
}

class ReferenceTestProduct extends Model
{
    use HasContent, InvalidatesReferences;

    protected $table = 'images';

    protected $guarded = [];

    public function referenceType(): string
    {
        return 'product';
    }

    public function getContentCacheKeys(): array
    {
        return ['product|alt_text'];
    }
}

class ReferenceTestCategory extends Model
{
    use InvalidatesReferences;

    protected $table = 'files';

    protected $guarded = [];

    public function referenceType(): string
    {
        return 'category';
    }
}
