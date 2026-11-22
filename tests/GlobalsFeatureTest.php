<?php

namespace Tests\Feature;

use Karabin\Fabriq\Actions\BustCacheWithWebhook;
use Karabin\Fabriq\Exceptions\ContentFieldNotFound;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\GlobalSection;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class GlobalsFeatureTest extends AdminUserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Fabriq::routes(fn ($router) => $router->forPublicGlobalSections(), ['prefix' => 'public']);
        Fabriq::routes(fn ($router) => $router->forGlobalSections(), [
            'prefix' => 'protected',
            'middleware' => ['auth', 'role:admin'],
        ]);
    }

    public function test_commands_create_sections_and_fields_idempotently_and_reject_conflicts(): void
    {
        $this->artisan('fabriq:create-global', ['name' => 'Footer', 'handle' => 'footer'])->assertSuccessful();
        $this->artisan('fabriq:create-global', ['name' => 'Footer', 'handle' => 'footer'])->assertSuccessful();
        $this->artisan('fabriq:create-global', ['name' => 'Other', 'handle' => 'footer'])->assertFailed();
        $arguments = [
            'handle' => 'footer', 'name' => 'Address', 'key' => 'address', 'type' => 'textarea',
            '--group' => 'Default', '--translated' => '1', '--order' => '10',
        ];
        $this->artisan('fabriq:add-global-field', $arguments)->assertSuccessful();
        $this->artisan('fabriq:add-global-field', $arguments)->assertSuccessful();
        $this->artisan('fabriq:add-global-field', array_merge($arguments, ['type' => 'image']))->assertFailed();

        $section = GlobalSection::where('handle', 'footer')->firstOrFail();
        $this->assertSame(1, $section->template->fields()->count());
        $this->assertTrue($section->template->fields()->first()->translated);
    }

    public function test_editor_saves_localized_text_and_shared_images_for_public_api(): void
    {
        $this->createFooter();
        $image = Image::factory()->create(['alt_text' => 'Company logo']);

        $this->putJson('/globals/footer', [
            'locale' => 'en', 'content' => ['address' => 'English address', 'logo' => $image->id],
        ])->assertOk()->assertJsonPath('data.content.logo.id', $image->id);
        $this->putJson('/globals/footer', [
            'locale' => 'sv', 'content' => ['address' => 'Svensk adress'],
        ])->assertOk()->assertJsonPath('data.content.address', 'Svensk adress');

        $this->getJson('/public/globals/footer?locale_override=en')
            ->assertOk()->assertJsonPath('data.content.address', 'English address')
            ->assertJsonPath('data.content.logo.id', $image->id);
        $this->getJson('/public/globals/footer?locale_override=sv')
            ->assertOk()->assertJsonPath('data.content.address', 'Svensk adress')
            ->assertJsonPath('data.content.logo.id', $image->id);

        $this->putJson('/globals/footer', ['locale' => 'sv', 'content' => ['logo' => null]])->assertOk();
        $this->getJson('/public/globals/footer?locale_override=en')->assertJsonPath('data.content.logo', null);
    }

    public function test_unknown_fields_invalid_images_and_locales_are_rejected_without_writes(): void
    {
        $this->createFooter();
        $this->putJson('/globals/footer', ['locale' => 'en', 'content' => ['address' => 'Valid', 'other' => 'No']])
            ->assertUnprocessable()->assertJsonValidationErrors('content.other');
        $this->putJson('/globals/footer', ['locale' => 'en', 'content' => ['logo' => 999999]])
            ->assertUnprocessable()->assertJsonValidationErrors('content.logo');
        $this->putJson('/globals/footer', ['locale' => 'xx', 'content' => ['address' => 'No']])
            ->assertUnprocessable()->assertJsonValidationErrors('locale');
        $this->putJson('/globals/footer', ['locale' => 'en', 'content' => ['address' => ['invalid']]])
            ->assertUnprocessable()->assertJsonValidationErrors('content.address');
        $this->putJson('/globals/footer', ['locale' => 'en', 'content' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('content');
        $this->putJson('/globals/missing', [])->assertNotFound();
        $this->getJson('/public/globals/footer')->assertJsonPath('data.content.address', null);
        $this->getJson('/public/globals/missing')->assertNotFound();
    }

    public function test_global_responses_preserve_their_shapes(): void
    {
        $this->artisan('fabriq:create-global', ['name' => 'Empty', 'handle' => 'empty'])->assertSuccessful();
        $section = GlobalSection::where('handle', 'empty')->firstOrFail();

        $this->getJson('/globals')->assertOk()->assertExactJson([
            'data' => [['id' => $section->id, 'name' => 'Empty', 'handle' => 'empty']],
        ]);
        $editor = json_decode($this->getJson('/globals/empty')->assertOk()->getContent());
        $this->assertEquals((object) [
            'id' => $section->id,
            'name' => 'Empty',
            'handle' => 'empty',
            'fields' => [],
            'content' => (object) [],
        ], $editor->data);
        $public = json_decode($this->getJson('/public/globals/empty')->assertOk()->getContent());
        $this->assertEquals((object) ['handle' => 'empty', 'content' => (object) []], $public->data);

        $this->createFooter();
        $update = json_decode($this->putJson('/globals/footer', [
            'locale' => 'en', 'content' => ['address' => 'Address'],
        ])->assertOk()->getContent());
        $this->assertEquals((object) [
            'handle' => 'footer',
            'content' => (object) ['address' => 'Address', 'logo' => null],
        ], $update->data);
    }

    public function test_a_section_cannot_write_a_field_from_another_section(): void
    {
        $this->createFooter();
        $this->artisan('fabriq:create-global', ['name' => 'SEO', 'handle' => 'seo'])->assertSuccessful();
        $this->artisan('fabriq:add-global-field', [
            'handle' => 'seo', 'name' => 'Description', 'key' => 'description', 'type' => 'text', '--no-interaction' => true,
        ])->assertSuccessful();

        $this->expectException(ContentFieldNotFound::class);
        GlobalSection::where('handle', 'footer')->firstOrFail()->updateContent(['description' => 'Wrong section'], 'en');
    }

    public function test_admin_routes_require_authentication_and_admin_role(): void
    {
        $this->getJson('/protected/globals')->assertForbidden();
        $this->user->assignRole('admin');
        $this->getJson('/protected/globals')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/protected/globals')->assertUnauthorized();
        $this->getJson('/public/globals/missing')->assertNotFound();
    }

    public function test_global_and_referenced_image_changes_invalidate_the_global_key(): void
    {
        $this->createFooter();
        $image = Image::factory()->create();
        $recorder = new class extends BustCacheWithWebhook
        {
            public array $keys = [];

            public function handle(array $keysToForget, array $tagsToFlush = [], bool $rateLimit = true): void
            {
                $this->keys = [...$this->keys, ...$keysToForget];
            }
        };
        $this->app->instance(BustCacheWithWebhook::class, $recorder);
        config(['fabriq.webhooks.enabled' => true]);
        $this->putJson('/globals/footer', ['locale' => 'en', 'content' => ['logo' => $image->id]])->assertOk();
        $this->assertContains('fabriq_globals_handle_footer', $recorder->keys);

        $recorder->keys = [];
        $image->update(['alt_text' => 'Changed logo']);
        $this->assertContains('fabriq_globals_handle_footer', $recorder->keys);
    }

    public function test_globals_can_be_saved_with_a_cache_store_that_does_not_support_tags(): void
    {
        $this->createFooter();
        $this->app['cache']->setDefaultDriver('database');

        $this->putJson('/globals/footer', ['locale' => 'en', 'content' => ['address' => 'Address']])
            ->assertOk();
        $this->getJson('/public/globals/footer?locale_override=en')
            ->assertJsonPath('data.content.address', 'Address');
    }

    private function createFooter(): void
    {
        $this->artisan('fabriq:create-global', ['name' => 'Footer', 'handle' => 'footer'])->assertSuccessful();
        $this->artisan('fabriq:add-global-field', [
            'handle' => 'footer', 'name' => 'Address', 'key' => 'address', 'type' => 'textarea', '--translated' => '1', '--no-interaction' => true,
        ])->assertSuccessful();
        $this->artisan('fabriq:add-global-field', [
            'handle' => 'footer', 'name' => 'Logo', 'key' => 'logo', 'type' => 'image', '--no-interaction' => true,
        ])->assertSuccessful();
    }
}
